<?php

namespace MediaWiki\Extension\Notifications;

use Article;
use IDBAccessObject;
use Language;
use MediaWiki\Extension\Notifications\Hooks\HookRunner;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserNameUtils;
use ParserOptions;
use ParserOutput;
use RequestContext;
use RuntimeException;
use TextContent;

abstract class DiscussionParser {
	private const HEADER_REGEX = '^(==+)\h*([^=].*)\h*\1$';

	public const DEFAULT_SNIPPET_LENGTH = 150;

	/** @var string|null */
	protected static $timestampRegex;

	/**
	 * @var array[][]
	 * FIXME: This static cache can become stale in tests, because it's never reset. We use both rev IDs and title keys
	 * to mitigate that, but it might still break!
	 */
	protected static $revisionInterpretationCache = [];

	/** @var DiffParser|null */
	protected static $diffParser = null;

	/**
	 * Given a RevisionRecord object, generates Event objects for
	 * the discussion-related actions that occurred in that Revision.
	 *
	 * @param RevisionRecord $revision
	 * @param bool $isRevert
	 */
	public static function generateEventsForRevision( RevisionRecord $revision, $isRevert ) {
		global $wgEchoMentionsOnMultipleSectionEdits;
		global $wgEchoMentionOnChanges;
		$services = MediaWikiServices::getInstance();
		$store = $services->getRevisionStore();

		// use the replica database if there is a previous revision
		if ( $store->getPreviousRevision( $revision ) ) {
			$title = Title::newFromID( $revision->getPageId() );
			// use the primary database for new page
		} else {
			$title = Title::newFromID( $revision->getPageId(), IDBAccessObject::READ_LATEST );
		}

		// not a valid title
		if ( !$title ) {
			return;
		}

		$events = [];

		$interpretation = self::getChangeInterpretationForRevision( $revision );

		$userID = $revision->getUser()->getId();
		$userName = $revision->getUser()->getName();
		$user = $userID !== 0 ? User::newFromId( $userID ) : User::newFromName( $userName, false );

		foreach ( $interpretation as $action ) {
			if ( $action['type'] === 'add-comment' ) {
				$fullSection = $action['full-section'];
				$header = self::extractHeader( $fullSection );
				$userLinks = self::getUserLinks( $action['content'], $title );
				$events = array_merge(
					$events,
					self::collectMentionEvents( $header, $userLinks, $action['content'], $revision, $user )
				);
			} elseif ( $action['type'] === 'new-section-with-comment' ) {
				$content = $action['content'];
				$header = self::extractHeader( $content );
				$userLinks = self::getUserLinks( $content, $title );
				$events = array_merge(
					$events,
					self::collectMentionEvents( $header, $userLinks, $content, $revision, $user )
				);
			} elseif ( $action['type'] === 'add-section-multiple' && $wgEchoMentionsOnMultipleSectionEdits ) {
				$content = self::stripHeader( $action['content'] );
				$content = self::stripSignature( $content );
				$userLinks = self::getUserLinks( $content, $title );
				$events = array_merge(
					$events,
					self::collectMentionEvents( $action['header'], $userLinks, $content, $revision, $user )
				);
			} elseif ( $action['type'] === 'unknown-signed-change' ) {
				$userLinks = array_diff_key(
					self::getUserLinks( $action['new_content'], $title ),
					self::getUserLinks( $action['old_content'], $title )
				);
				$header = self::extractHeader( $action['full-section'] );

				if ( $wgEchoMentionOnChanges ) {
					$events = array_merge(
						$events,
						self::collectMentionEvents( $header, $userLinks, $action['new_content'], $revision, $user )
					);
				}
			}
		}

		if ( $title->getNamespace() === NS_USER_TALK ) {
			$notifyUser = User::newFromName( $title->getText() );
			// If the recipient is a valid non-anonymous user generate a talk page post notification.
			if ( $notifyUser && $notifyUser->getId() ) {
				$permManager = MediaWikiServices::getInstance()->getPermissionManager();
				// If this is a minor edit, only notify if the agent doesn't have talk page minor
				// edit notification blocked
				if ( !$revision->isMinor() || !$permManager->userHasRight( $user, 'nominornewtalk' ) ) {
					$section = self::detectSectionTitleAndText( $interpretation, $title );
					if ( $section['section-text'] === '' ) {
						$comment = $revision->getComment( RevisionRecord::FOR_PUBLIC, $notifyUser );
						if ( $comment ) {
							$section['section-text'] = $comment->text;
						}
					}
					$events[] = [
						'type' => 'edit-user-talk',
						'title' => $title,
						'extra' => [
							'revid' => $revision->getId(),
							'minoredit' => $revision->isMinor(),
							'section-title' => $section['section-title'],
							'section-text' => $section['section-text'],
							'target-page' => $title->getArticleID(),
						],
						'agent' => $user,
					];
				}
			}
		} elseif ( $title->inNamespace( NS_USER ) ) {
			$notifyUser = User::newFromName( $title->getText() );
			// If the recipient is a valid non-anonymous user and hasn't turned
			// off their notifications, generate a talk page post Echo notification.
			if ( $notifyUser && $notifyUser->getId() ) {
				$events[] = [
					'type' => 'edit-user-page',
					'title' => $title,
					'extra' => [
						'revid' => $revision->getId(),
					],
					'agent' => $user,
				];
			}
		}

		// Notify users mentioned in edit summary
		global $wgEchoMaxMentionsInEditSummary;

		if ( $wgEchoMaxMentionsInEditSummary > 0 && !$user->isBot() && !$isRevert ) {
			$summaryParser = new SummaryParser();
			$usersInSummary = $summaryParser->parse( $revision->getComment()->text );

			// Don't allow pinging yourself
			unset( $usersInSummary[$userName] );

			$count = 0;
			$mentionedUsers = [];
			foreach ( $usersInSummary as $summaryUser ) {
				if ( $summaryUser->getTalkPage()->equals( $title ) ) {
					// Users already get a ping when their talk page is edited
					continue;
				}
				if ( $count >= $wgEchoMaxMentionsInEditSummary ) {
					break;
				}
				$mentionedUsers[$summaryUser->getId()] = $summaryUser->getId();
				$count++;
			}

			if ( $mentionedUsers ) {
				$events[] = [
					'type' => 'mention-summary',
					'title' => $title,
					'extra' => [
						'revid' => $revision->getId(),
						'mentioned-users' => $mentionedUsers,
					],
					'agent' => $user,
				];
			}
		}

		// Allow extensions to generate more events for a revision, and de-duplicate
		// against the standard events created above.
		( new HookRunner( $services->getHookContainer() ) )
			->onEchoGetEventsForRevision( $events, $revision, $isRevert );

		// Create events
		foreach ( $events as $event ) {
			Event::create( $event );
		}
	}

	/**
	 * Attempts to determine what section title the edit was performed under (if any)
	 *
	 * @param array[] $interpretation Results of {@see getChangeInterpretationForRevision}
	 * @param Title|null $title
	 * @return string[] Array containing section title and text
	 */
	public static function detectSectionTitleAndText( array $interpretation, Title $title = null ) {
		$header = $snippet = '';
		$found = false;

		foreach ( $interpretation as $action ) {
			switch ( $action['type'] ) {
				case 'add-comment':
					$header = self::extractHeader( $action['full-section'] );
					$snippet = self::getTextSnippet(
						self::stripSignature( self::stripHeader( $action['content'] ), $title ),
						RequestContext::getMain()->getLanguage(),
						self::DEFAULT_SNIPPET_LENGTH,
						$title );
					break;
				case 'new-section-with-comment':
					$header = self::extractHeader( $action['content'] );
					$snippet = self::getTextSnippet(
						self::stripSignature( self::stripHeader( $action['content'] ), $title ),
						RequestContext::getMain()->getLanguage(),
						self::DEFAULT_SNIPPET_LENGTH,
						$title );
					break;
			}
			if ( $header ) {
				// If we find a second header within the same change interpretation then
				// we cannot choose just 1 to link to
				if ( $found ) {
					$found = false;
					break;
				}
				$found = true;
			}
		}
		if ( !$found ) {
			return [ 'section-title' => '', 'section-text' => '' ];
		}

		return [ 'section-title' => $header, 'section-text' => $snippet ];
	}

	/**
	 * For an action taken on a talk page, notify users whose user pages
	 * are linked.
	 * @param string $header The subject line for the discussion.
	 * @param int[] $userLinks
	 * @param string $content The content of the post, as a wikitext string.
	 * @param RevisionRecord $revision
	 * @param User $agent The user who made the comment.
	 */
	public static function generateMentionEvents(
		$header,
		array $userLinks,
		$content,
		RevisionRecord $revision,
		User $agent
	) {
		$events = self::collectMentionEvents( $header, $userLinks, $content, $revision, $agent );
		foreach ( $events as $event ) {
			Event::create( $event );
		}
	}

	/**
	 * Generate mention event data for a talk page action
	 * @param string $header The subject line for the discussion.
	 * @param int[] $userLinks
	 * @param string $content The content of the post, as a wikitext string.
	 * @param RevisionRecord $revision
	 * @param User $agent The user who made the comment.
	 * @return array List of event info arrays
	 */
	protected static function collectMentionEvents(
		$header,
		array $userLinks,
		$content,
		RevisionRecord $revision,
		User $agent
	) {
		global $wgEchoMaxMentionsCount, $wgEchoMentionStatusNotifications;

		$title = Title::newFromLinkTarget( $revision->getPageAsLinkTarget() );
		if ( !$title ) {
			return [];
		}
		$content = self::stripHeader( $content );
		$content = self::stripSignature( $content, $title );

		if ( !$userLinks ) {
			return [];
		}

		$userMentions = self::getUserMentions(
			$title, $revision->getUser( RevisionRecord::RAW )->getId(), $userLinks
		);
		$overallMentionsCount = self::getOverallUserMentionsCount( $userMentions );
		if ( $overallMentionsCount === 0 ) {
			return [];
		}

		$events = [];
		$stats = MediaWikiServices::getInstance()->getStatsdDataFactory();

		if ( $overallMentionsCount > $wgEchoMaxMentionsCount ) {
			if ( $wgEchoMentionStatusNotifications ) {
				$events[] = [
					'type' => 'mention-failure-too-many',
					'title' => $title,
					'extra' => [
						'max-mentions' => $wgEchoMaxMentionsCount,
						'section-title' => $header,
					],
					'agent' => $agent,
				];
				$stats->increment( 'echo.event.mention.notification.failure-too-many' );
			}
			return $events;
		}

		if ( $userMentions['validMentions'] ) {
			$events[] = [
				'type' => 'mention',
				'title' => $title,
				'extra' => [
					'content' => $content,
					'section-title' => $header,
					'revid' => $revision->getId(),
					'mentioned-users' => $userMentions['validMentions'],
				],
				'agent' => $agent,
			];
		}

		if ( $wgEchoMentionStatusNotifications ) {
			// TODO batch?
			foreach ( $userMentions['validMentions'] as $mentionedUserId ) {
				$events[] = [
					'type' => 'mention-success',
					'title' => $title,
					'extra' => [
						'subject-name' => User::newFromId( $mentionedUserId )->getName(),
						'section-title' => $header,
						'revid' => $revision->getId(),
					],
					'agent' => $agent,
				];
				$stats->increment( 'echo.event.mention.notification.success' );
			}

			// TODO batch?
			foreach ( $userMentions['anonymousUsers'] as $anonymousUser ) {
				$events[] = [
					'type' => 'mention-failure',
					'title' => $title,
					'extra' => [
						'failure-type' => 'user-anonymous',
						'subject-name' => $anonymousUser,
						'section-title' => $header,
						'revid' => $revision->getId(),
					],
					'agent' => $agent,
				];
				$stats->increment( 'echo.event.mention.notification.failure-user-anonymous' );
			}

			// TODO batch?
			foreach ( $userMentions['unknownUsers'] as $unknownUser ) {
				$events[] = [
					'type' => 'mention-failure',
					'title' => $title,
					'extra' => [
						'failure-type' => 'user-unknown',
						'subject-name' => $unknownUser,
						'section-title' => $header,
						'revid' => $revision->getId(),
					],
					'agent' => $agent,
				];
				$stats->increment( 'echo.event.mention.notification.failure-user-unknown' );
			}
		}

		return $events;
	}

	private static function getOverallUserMentionsCount( array $userMentions ) {
		return count( $userMentions, COUNT_RECURSIVE ) - count( $userMentions );
	}

	/**
	 * @param Title $title
	 * @param int $revisionUserId
	 * @param int[] $userLinks
	 * @return array[]
	 * Set of arrays containing valid mentions and possible intended but failed mentions.
	 * - [validMentions]: An array of valid users to mention with ID => ID.
	 * - [unknownUsers]: An array of DBKey strings representing unknown users.
	 * - [anonymousUsers]: An array of DBKey strings representing anonymous IP users.
	 */
	public static function getUserMentions( Title $title, $revisionUserId, array $userLinks ) {
		global $wgEchoMaxMentionsCount;

		$userMentions = [
			'validMentions' => [],
			'unknownUsers' => [],
			'anonymousUsers' => [],
		];

		$count = 0;
		$stats = MediaWikiServices::getInstance()->getStatsdDataFactory();
		$userNameUtils = MediaWikiServices::getInstance()->getUserNameUtils();

		foreach ( $userLinks as $dbk => $page_id ) {
			// If more users are being pinged this is likely a spam/attack vector
			// Don't send any mention notifications.
			if ( $count > $wgEchoMaxMentionsCount ) {
				$stats->increment( 'echo.event.mention.error.tooMany' );
				break;
			}

			// we should not add user to 'mention' notification list if
			// 1. the user link links to a subpage
			if ( self::hasSubpage( $dbk ) ) {
				continue;
			}

			// 2. user is an anonymous IP
			if ( $userNameUtils->isIP( $dbk ) ) {
				$userMentions['anonymousUsers'][] = $dbk;
				$count++;
				$stats->increment( 'echo.event.mention.error.anonUser' );
				continue;
			}

			$user = User::newFromName( $dbk );
			// 3. the user name is not valid
			if ( !$user ) {
				$userMentions['unknownUsers'][] = str_replace( '_', ' ', $dbk );
				$count++;
				$stats->increment( 'echo.event.mention.error.invalidUser' );
				continue;
			}

			// 4. the user mentions themselves
			if ( $user->getId() === $revisionUserId ) {
				$stats->increment( 'echo.event.mention.error.sameUser' );
				continue;
			}

			// 5. the user is the owner of the talk page
			if ( $title->getNamespace() === NS_USER_TALK && $title->getDBkey() === $dbk ) {
				$stats->increment( 'echo.event.mention.error.ownPage' );
				continue;
			}

			// 6. user does not exist
			if ( $user->getId() === 0 ) {
				$userMentions['unknownUsers'][] = str_replace( '_', ' ', $dbk );
				$count++;
				$stats->increment( 'echo.event.mention.error.unknownUser' );
				continue;
			}

			$userMentions['validMentions'][$user->getId()] = $user->getId();
			$count++;
		}

		return $userMentions;
	}

	/**
	 * @param string $content
	 * @param Title $title
	 * @return int[]
	 * Array of links in the user namespace with DBKey => ID.
	 */
	public static function getUserLinks( $content, Title $title ) {
		$output = self::parseNonEditWikitext( $content, new Article( $title ) );
		$links = $output->getLinks();

		if ( !isset( $links[NS_USER] ) || !is_array( $links[NS_USER] ) ) {
			return [];
		}

		return $links[NS_USER];
	}

	private static function hasSubpage( $dbk ) {
		return strpos( $dbk, '/' ) !== false;
	}

	/**
	 * It's like Article::prepareTextForEdit,
	 *  but not for editing (old wikitext usually)
	 * Stolen from AbuseFilter's VariableHolder
	 *
	 * @param string $wikitext
	 * @param Article $article
	 *
	 * @return ParserOutput
	 */
	public static function parseNonEditWikitext( $wikitext, Article $article ) {
		static $cache = [];

		$cacheKey = md5( $wikitext ) . ':' . $article->getTitle()->getPrefixedText();

		if ( isset( $cache[$cacheKey] ) ) {
			return $cache[$cacheKey];
		}

		$parser = MediaWikiServices::getInstance()->getParser();

		$options = new ParserOptions( $article->getContext()->getUser() );
		$output = $parser->parse( $wikitext, $article->getTitle(), $options );
		$cache[$cacheKey] = $output;

		return $output;
	}

	/**
	 * Given a Revision object, returns a talk-page-centric interpretation
	 * of the changes made in it.
	 *
	 * @param RevisionRecord $revision
	 * @see DiscussionParser::interpretDiff
	 * @return array[] See {@see interpretDiff} for details.
	 */
	private static function getChangeInterpretationForRevision( RevisionRecord $revision ) {
		if ( $revision->getId() ) {
			$page = $revision->getPage();
			$cacheKey = $revision->getId() . '|' . $page->getNamespace() . '|' . $page->getDBkey();
			if ( isset( self::$revisionInterpretationCache[$cacheKey] ) ) {
				return self::$revisionInterpretationCache[$cacheKey];
			}
		} else {
			$cacheKey = null;
		}

		$userIdentity = $revision->getUser();

		$prevText = '';
		if ( $revision->getParentId() ) {
			$store = MediaWikiServices::getInstance()->getRevisionStore();
			$prevRevision = $store->getRevisionById( $revision->getParentId() );
			if ( $prevRevision ) {
				$prevContent = $prevRevision->getContent( SlotRecord::MAIN );
				$prevText = ( $prevContent instanceof TextContent ) ? $prevContent->getText() : '';
			}
		}

		$content = $revision->getContent( SlotRecord::MAIN );
		$changes = self::getMachineReadableDiff(
			$prevText,
			( $content instanceof TextContent ) ? $content->getText() : ''
		);
		$output = self::interpretDiff(
			$changes,
			$userIdentity ? $userIdentity->getName() : '',
			Title::newFromLinkTarget( $revision->getPageAsLinkTarget() )
		);

		if ( $cacheKey ) {
			self::$revisionInterpretationCache[$cacheKey] = $output;
		}

		return $output;
	}

	/**
	 * Given a machine-readable diff, interprets the changes
	 * in terms of discussion page actions
	 *
	 * @todo Expand recognisable actions.
	 *
	 * @param array[] $changes Output of Event::getMachineReadableDiff
	 * @param string $username
	 * @param Title|null $title
	 * @return array[] Array of associative arrays.
	 *
	 * Each entry represents an action, which is classified in the 'action' field.
	 * All types contain a 'content' field except 'unknown'
	 *  (which instead passes through the machine-readable diff in 'details')
	 *  and 'unknown-change' (which provides 'new_content' and 'old_content')
	 * action may be:
	 * - add-comment: A comment signed by the user is added to an
	 *    existing section.
	 * - new-section-with-comment: A new section is added, containing
	 *    a single comment signed by the user in question.
	 * - add-section-multiple: A new section or additions to a section
	 *    while editing multiple sections at once.
	 * - unknown-multi-signed-addition: Some signed content is added,
	 *    but it contains multiple signatures.
	 * - unknown-unsigned-addition: Some content is added, but it is
	 *    unsigned.
	 * - unknown-subtraction: Some content was removed. These actions are
	 *    not currently analysed.
	 * - unknown-change: Some content was replaced with other content.
	 * - unknown-signed-change: Same as unknown-change, but signed.
	 * - unknown-multi-signed-change: Same as unknown-change,
	 *    but it contains multiple signatures.
	 * - unknown: Unrecognised change type.
	 */
	public static function interpretDiff( array $changes, $username, Title $title = null ) {
		// One extra item in $changes for _info
		$actions = [];
		$signedSections = [];

		foreach ( $changes as $index => $change ) {
			if ( !is_numeric( $index ) ) {
				continue;
			}

			if ( !$change['action'] ) {
				// Unknown action; skip
				continue;
			}

			if ( $change['action'] === 'add' ) {
				$content = trim( $change['content'] );
				// The \A means the regex must match at the beginning of the string.
				// This is slightly different than ^ which matches beginning of each
				// line in multiline mode.
				$startSection = preg_match( '/\A' . self::HEADER_REGEX . '/um', $content );
				$sectionCount = self::getSectionCount( $content );
				$signedUsers = self::extractSignatures( $content, $title );

				if (
					count( $signedUsers ) === 1 &&
					isset( $signedUsers[$username] )
				) {
					if ( $sectionCount === 0 ) {
						$signedSections[] = self::getSectionSpan( $change['right-pos'], $changes['_info']['rhs'] );
						$fullSection = self::getFullSection( $changes['_info']['rhs'], $change['right-pos'] );
						$actions[] = [
							'type' => 'add-comment',
							'content' => $content,
							'full-section' => $fullSection,
						];
					} elseif ( $startSection && $sectionCount === 1 ) {
						$signedSections[] = self::getSectionSpan( $change['right-pos'], $changes['_info']['rhs'] );
						$actions[] = [
							'type' => 'new-section-with-comment',
							'content' => $content,
						];
					} else {
						$nextSectionStart = $change['right-pos'];
						$sectionData = self::extractSections( $content );
						foreach ( $sectionData as $section ) {
							$sectionSpan = self::getSectionSpan( $nextSectionStart, $changes['_info']['rhs'] );
							$nextSectionStart = $sectionSpan[1] + 1;
							$sectionSignedUsers = self::extractSignatures( $section['content'], $title );
							if ( $sectionSignedUsers ) {
								$signedSections[] = $sectionSpan;
								if ( !$section['header'] ) {
									$fullSection = self::getFullSection(
										$changes['_info']['rhs'],
										$change['right-pos']
									);
									$section['header'] = self::extractHeader( $fullSection );
								}
								$actions[] = [
									'type' => 'add-section-multiple',
									'content' => $section['content'],
									'header' => $section['header'],
								];
							} else {
								$actions[] = [
									'type' => 'unknown-unsigned-addition',
									'content' => $section['content'],
								];
							}
						}
					}
				} elseif ( $signedUsers !== [] ) {
					$actions[] = [
						'type' => 'unknown-multi-signed-addition',
						'content' => $content,
					];
				} else {
					$actions[] = [
						'type' => 'unknown-unsigned-addition',
						'content' => $content,
					];
				}
			} elseif ( $change['action'] === 'subtract' ) {
				$actions[] = [
					'type' => 'unknown-subtraction',
					'content' => $change['content'],
				];
			} elseif ( $change['action'] === 'change' ) {
				$actions[] = [
					'type' => 'unknown-change',
					'old_content' => $change['old_content'],
					'new_content' => $change['new_content'],
					'right-pos' => $change['right-pos'],
					'full-section' => self::getFullSection( $changes['_info']['rhs'], $change['right-pos'] ),
				];

				if ( self::hasNewSignature(
					$change['old_content'],
					$change['new_content'],
					$username,
					$title
				) ) {
					$signedSections[] = self::getSectionSpan( $change['right-pos'], $changes['_info']['rhs'] );
				}
			} else {
				$actions[] = [
					'type' => 'unknown',
					'details' => $change,
				];
			}
		}

		if ( $signedSections ) {
			$actions = self::convertToUnknownSignedChanges( $signedSections, $actions );
		}

		return $actions;
	}

	private static function hasNewSignature( $oldContent, $newContent, $username, $title ) {
		$oldSignedUsers = self::extractSignatures( $oldContent, $title );
		$newSignedUsers = self::extractSignatures( $newContent, $title );

		return !isset( $oldSignedUsers[$username] ) && isset( $newSignedUsers[$username] );
	}

	/**
	 * Converts actions of type "unknown-change" to "unknown-signed-change" if the change is in a signed section.
	 *
	 * @param array[] $signedSections Array of arrays containing first and last line number of signed sections
	 * @param array[] $actions
	 * @return array[] Converted actions
	 */
	private static function convertToUnknownSignedChanges( array $signedSections, array $actions ) {
		return array_map( function ( $action ) use( $signedSections ) {
			if (
				$action['type'] === 'unknown-change' &&
				self::isInSignedSection( $action['right-pos'], $signedSections )
			) {
				$signedUsers = self::extractSignatures( $action['new_content'], null );
				if ( count( $signedUsers ) === 1 ) {
					$action['type'] = 'unknown-signed-change';
				} else {
					$action['type'] = 'unknown-multi-signed-change';
				}
			}

			return $action;
		}, $actions );
	}

	/**
	 * @param int $line
	 * @param array[] $signedSections
	 * @return bool
	 */
	private static function isInSignedSection( $line, array $signedSections ) {
		foreach ( $signedSections as $section ) {
			if ( $line > $section[0] && $line <= $section[1] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Finds the section that a given line is in.
	 *
	 * @param array $lines of lines in the page.
	 * @param int $offset The line to find the full section for.
	 * @return string Content of the section.
	 */
	public static function getFullSection( array $lines, $offset ) {
		$start = self::getSectionStartIndex( $offset, $lines );
		$end = self::getSectionEndIndex( $offset, $lines );
		$content = implode( "\n", array_slice( $lines, $start, $end - $start ) );

		return trim( $content, "\n" );
	}

	/**
	 * Given a line number and a text, find the first and last line of the section the line number is in.
	 * If there are subsections, the last line index will be the line before the beginning of the first subsection.
	 * @param int $offset line number
	 * @param string[] $lines
	 * @return int[] Tuple [$firstLine, $lastLine]
	 */
	private static function getSectionSpan( $offset, array $lines ) {
		return [
			self::getSectionStartIndex( $offset, $lines ),
			self::getSectionEndIndex( $offset, $lines )
		];
	}

	/**
	 * Finds the line number of the start of the section that $offset is in.
	 * @param int $offset
	 * @param string[] $lines
	 * @return int
	 */
	private static function getSectionStartIndex( $offset, array $lines ) {
		for ( $i = $offset - 1; $i >= 0; $i-- ) {
			if ( self::getSectionCount( $lines[$i] ) ) {
				break;
			}
		}

		return $i;
	}

	/**
	 * Finds the line number of the end of the section that $offset is in.
	 * @param int $offset
	 * @param array $lines
	 * @return int
	 */
	private static function getSectionEndIndex( $offset, array $lines ) {
		$lastLine = count( $lines );
		for ( $i = $offset; $i < $lastLine; $i++ ) {
			if ( self::getSectionCount( $lines[$i] ) ) {
				break;
			}
		}

		return $i;
	}

	/**
	 * Gets the number of section headers in a string.
	 *
	 * @param string $text
	 * @return int Number of section headers found.
	 */
	public static function getSectionCount( $text ) {
		$text = trim( $text );

		return (int)preg_match_all( '/' . self::HEADER_REGEX . '/um', $text );
	}

	/**
	 * Gets the title of a section or sub section
	 *
	 * @param string $text The text of the section.
	 * @return string|false The title of the section or false if not found
	 */
	public static function extractHeader( $text ) {
		$text = trim( $text );

		$matches = [];

		if ( !preg_match_all( '/' . self::HEADER_REGEX . '/um', $text, $matches ) ) {
			return false;
		}

		return trim( end( $matches[2] ) );
	}

	/**
	 * Extracts sections and their contents from text.
	 *
	 * @param string $text The text to parse.
	 * @return array[]
	 * Array of arrays containing sections with header and content.
	 * - [header]: The full header string of the section or false if there is preceding text without header.
	 * - [content]: The content of the section including the header string.
	 */
	private static function extractSections( $text ) {
		$matches = [];

		if ( !preg_match_all( '/' . self::HEADER_REGEX . '/um', $text, $matches, PREG_OFFSET_CAPTURE ) ) {
			return [ [
				'header' => false,
				'content' => $text
			] ];
		}

		$sectionNum = count( $matches[0] );
		$sections = [];

		// is there text before the first headline?
		if ( $matches[0][0][1] > 1 ) {
			$sections[] = [
				'header' => false,
				'content' => substr( $text, 0, $matches[0][0][1] - 1 )
			];
		}
		for ( $i = 0; $i < $sectionNum; $i++ ) {
			if ( $i + 1 < $sectionNum ) {
				$content = substr( $text, $matches[0][$i][1], $matches[0][$i + 1][1] - $matches[0][$i][1] );
			} else {
				$content = substr( $text, $matches[0][$i][1] );
			}
			$sections[] = [
				'header' => self::extractHeader( $matches[0][$i][0] ),
				'content' => trim( $content )
			];
		}

		return $sections;
	}

	/**
	 * Strips out a signature if possible.
	 *
	 * @param string $text The wikitext to strip
	 * @param Title|null $title
	 * @return string
	 */
	private static function stripSignature( $text, Title $title = null ) {
		$output = self::getUserFromLine( $text, $title );
		if ( $output === false ) {
			$timestampPos = self::getTimestampPosition( $text );

			return substr( $text, 0, $timestampPos );
		}

		// Use truncateForDatabase() instead of truncateHTML() because
		// truncateHTML() would not strip signature if the text contains
		// < or &.  (And we can't use truncateForVisual() because
		// self::getUserFromLine() returns byte offsets, not character
		// offsets.)
		return MediaWikiServices::getInstance()->getContentLanguage()
			->truncateForDatabase( $text, $output[0], '' );
	}

	/**
	 * Strips out a section header
	 * @param string $text The text to strip out the section header from.
	 * @return string The same text, with the section header stripped out.
	 */
	private static function stripHeader( $text ) {
		return preg_replace( '/' . self::HEADER_REGEX . '/um', '', $text );
	}

	/**
	 * Determines whether the input is a signed comment.
	 *
	 * @param string $text The text to check.
	 * @param User|bool $user If set, will only return true if the comment is
	 *  signed by this user.
	 * @param Title|null $title
	 * @return bool
	 */
	public static function isSignedComment( $text, $user = false, Title $title = null ) {
		$userData = self::getUserFromLine( $text, $title );

		if ( $userData === false ) {
			return false;
		} elseif ( $user === false ) {
			return true;
		}

		[ , $foundUser ] = $userData;
		$userNameUtils = MediaWikiServices::getInstance()->getUserNameUtils();

		return $userNameUtils->getCanonical( $foundUser, UserNameUtils::RIGOR_NONE ) ===
			$userNameUtils->getCanonical( $user, UserNameUtils::RIGOR_NONE );
	}

	/**
	 * Finds the start position, if any, of the timestamp on a line
	 *
	 * @param string $line The line to search for a signature on
	 * @return int|false Integer position
	 */
	public static function getTimestampPosition( $line ) {
		$timestampRegex = self::getTimestampRegex();
		$tsMatches = [];
		if ( !preg_match(
			"/$timestampRegex/mu",
			$line,
			$tsMatches,
			PREG_OFFSET_CAPTURE
		) ) {
			return false;
		}

		return $tsMatches[0][1];
	}

	/**
	 * Finds differences between $oldText and $newText
	 * and returns the result in a machine-readable format.
	 *
	 * @param string $oldText The "left hand side" of the diff.
	 * @param string $newText The "right hand side" of the diff.
	 * @return array[] Array of changes.
	 * Each change consists of:
	 * * An 'action', one of:
	 *   - add
	 *   - subtract
	 *   - change
	 * * 'content' that was added or removed, or in the case
	 *    of a change, 'old_content' and 'new_content'
	 * * 'left_pos' and 'right_pos' (in lines) of the change.
	 */
	public static function getMachineReadableDiff( $oldText, $newText ) {
		if ( self::$diffParser === null ) {
			self::$diffParser = new DiffParser;
		}

		return self::$diffParser->getChangeSet( $oldText, $newText );
	}

	/**
	 * Finds and extracts signatures in $text
	 *
	 * @param string $text The text in which to look for signed comments.
	 * @param Title|null $title
	 * @return array<string,string> Associative array, the key is the username, the value
	 *  is the last signature that was found.
	 */
	private static function extractSignatures( $text, Title $title = null ) {
		$lines = explode( "\n", $text );

		$output = [];

		$lineNumber = 0;

		foreach ( $lines as $line ) {
			++$lineNumber;

			// Look for the last user link on the line.
			$userData = self::getUserFromLine( $line, $title );
			if ( $userData === false ) {
				continue;
			}

			[ $signaturePos, $user ] = $userData;

			$signature = substr( $line, $signaturePos );
			$output[$user] = $signature;
		}

		return $output;
	}

	/**
	 * From a line in the signature, extract all the users linked to
	 *
	 * @param string $line Line of text potentially including linked user, user talk,
	 *  and contribution pages
	 * @return string[] array of usernames, empty array for none detected
	 */
	public static function extractUsersFromLine( $line ) {
		/*
		 * Signatures can look like anything (as defined by i18n messages
		 * "signature" & "signature-anon").
		 * A signature can, e.g., be both a link to user & user-talk page.
		 */
		// match all title-like excerpts in this line
		if ( !preg_match_all( '/\[\[([^\[]+)\]\]/', $line, $matches ) ) {
			return [];
		}

		$matches = $matches[1];

		$usernames = [];

		foreach ( $matches as $match ) {
			/*
			 * Create an object out of the link title.
			 * In theory, links can be [[text]], [[text|text]] or pipe tricks
			 * [[text|]] or [[|text]].
			 * In the case of reverse pipe trick, the value we use *could* be
			 * empty, but Parser::pstPass2 should have normalized that for us
			 * already.
			 */
			$match = explode( '|', $match, 2 );
			$title = Title::newFromText( $match[0] );

			// figure out if the link is related to a user
			if (
				$title &&
				( $title->getNamespace() === NS_USER || $title->getNamespace() === NS_USER_TALK )
			) {
				$usernames[] = $title->getText();
			} elseif ( $title && $title->isSpecial( 'Contributions' ) ) {
				$parts = explode( '/', $title->getText(), 2 );
				$usernames[] = end( $parts );
			} else {
				// move on to next matched title-like excerpt
				continue;
			}
		}

		return $usernames;
	}

	/**
	 * From a line in a wiki page, determine which user, if any,
	 *  has signed it.
	 *
	 * @param string $line
	 * @param Title|null $title
	 * @return array|false False for none, array for success.
	 * - First element is the position of the signature.
	 * - Second element is the normalised user name.
	 */
	public static function getUserFromLine( $line, Title $title = null ) {
		$parser = MediaWikiServices::getInstance()->getParser();

		/*
		 * First we call extractUsersFromLine to get all the potential usernames
		 * from the line.  Then, we loop backwards through them, figure out which
		 * match to a user, regenerate the signature based on that user, and
		 * see if it matches!
		 */
		$usernames = self::extractUsersFromLine( $line );
		$usernames = array_reverse( $usernames );
		foreach ( $usernames as $username ) {
			// generate (dateless) signature from the user we think we've
			// discovered the signature from
			// don't validate the username - anon (IP) is fine!
			$user = User::newFromName( $username, false );
			$sig = $parser->preSaveTransform(
				'~~~',
				$title ?: Title::newMainPage(),
				$user,
				new ParserOptions( $user )
			);

			// see if we can find this user's generated signature in the content
			$pos = strrpos( $line, $sig );
			if ( $pos !== false ) {
				return [ $pos, $username ];
			}
			// couldn't find sig, move on to next link excerpt and try there
		}

		// couldn't find any matching signature
		return false;
	}

	/**
	 * Find the last link beginning with a given prefix on a line.
	 *
	 * @param string $line The line to search.
	 * @param string $linkPrefix The prefix to search for.
	 * @param int|false $failureOffset
	 * @return array|false False for failure, array for success.
	 * - First element is the string offset of the link.
	 * - Second element is the user the link refers to.
	 */
	private static function getLinkFromLine( $line, $linkPrefix, $failureOffset = false ) {
		$offset = 0;

		// If extraction failed at another offset, try again.
		if ( $failureOffset !== false ) {
			$offset = $failureOffset - strlen( $line ) - 1;
		}

		// Avoid PHP warning: Offset is greater than the length of haystack string
		if ( abs( $offset ) > strlen( $line ) ) {
			return false;
		}

		$linkPos = strripos( $line, $linkPrefix, $offset );

		if ( $linkPos === false ) {
			return false;
		}

		$linkUser = self::extractUserFromLink( $line, $linkPrefix, $linkPos );

		if ( $linkUser === false ) {
			// Look for another place.
			return self::getLinkFromLine( $line, $linkPrefix, $linkPos );
		} else {
			return [ $linkPos, $linkUser ];
		}
	}

	/**
	 * Given text including a link, gives the user that that link refers to
	 *
	 * @param string $text The text to extract from.
	 * @param string $prefix The link prefix that was used to find the link.
	 * @param int $offset Optionally, the offset of the start of the link.
	 * @return bool|string Type description
	 */
	private static function extractUserFromLink( $text, $prefix, $offset = 0 ) {
		$userPart = substr( $text, strlen( $prefix ) + $offset );

		$userMatches = [];
		if ( !preg_match(
			'/^[^\|\]\#]+/u',
			$userPart,
			$userMatches
		) ) {
			// user link is invalid
			return false;
		}

		$user = $userMatches[0];
		$userNameUtils = MediaWikiServices::getInstance()->getUserNameUtils();
		if (
			!$userNameUtils->isIP( $user ) &&
			$userNameUtils->getCanonical( $user ) === false
		) {
			// Not a real username
			return false;
		}

		return $userNameUtils->getCanonical( $userMatches[0], UserNameUtils::RIGOR_NONE );
	}

	/**
	 * Gets a regular expression that will match this wiki's
	 * timestamps as given by ~~~~.
	 *
	 * @return string regular expression fragment.
	 */
	public static function getTimestampRegex() {
		if ( self::$timestampRegex !== null ) {
			return self::$timestampRegex;
		}

		// Step 1: Get an exemplar timestamp
		$title = Title::newMainPage();
		$user = User::newFromName( 'Test' );
		$options = new ParserOptions( $user );

		$parser = MediaWikiServices::getInstance()->getParser();
		$exemplarTimestamp =
			$parser->preSaveTransform( '~~~~~', $title, $user, $options );

		// Step 2: Generalise it
		// Trim off the timezone to replace at the end
		$output = $exemplarTimestamp;
		$tzRegex = '/\h*\(\w+\)\h*$/u';
		$tzMatches = [];
		if ( preg_match( $tzRegex, $output, $tzMatches, PREG_OFFSET_CAPTURE ) ) {
			$output = substr( $output, 0, $tzMatches[0][1] );
		}
		$output = preg_quote( $output, '/' );
		$output = preg_replace( '/[^\d\W]+/u', '[^\d\W]+', $output );
		$output = preg_replace( '/\d+/u', '\d+', $output );

		if ( $tzMatches ) {
			$output .= preg_quote( $tzMatches[0][0] );
		}

		if ( !preg_match( "/$output/u", $exemplarTimestamp ) ) {
			throw new RuntimeException( "Timestamp regex does not match exemplar" );
		}

		self::$timestampRegex = $output;

		return $output;
	}

	/**
	 * Parse wikitext into truncated plain text.
	 * @param string $text
	 * @param Language $lang
	 * @param int $length Length in characters (not bytes); default DEFAULT_SNIPPET_LENGTH
	 * @param Title|null $title Page from which the text snippet is being extracted
	 * @param bool $linestart Whether or not this is at the start of a line
	 * @return string
	 */
	public static function getTextSnippet(
		$text, Language $lang, $length = self::DEFAULT_SNIPPET_LENGTH, $title = null, $linestart = true
	) {
		// Parse wikitext
		$html = MediaWikiServices::getInstance()->getMessageCache()->parse( $text, $title, $linestart )->getText( [
			'enableSectionEditLinks' => false
		] );
		$plaintext = trim( Sanitizer::stripAllTags( $html ) );
		return $lang->truncateForVisual( $plaintext, $length );
	}

	/**
	 * Parse an edit summary into truncated plain text.
	 * @param string $text
	 * @param Language $lang
	 * @param int $length Length in characters (not bytes); default DEFAULT_SNIPPET_LENGTH
	 * @return string
	 */
	public static function getTextSnippetFromSummary( $text, Language $lang, $length = self::DEFAULT_SNIPPET_LENGTH ) {
		// Parse wikitext with summary parser
		$html = MediaWikiServices::getInstance()->getCommentFormatter()
			->formatLinks( Sanitizer::escapeHtmlAllowEntities( $text ) );
		$plaintext = trim( Sanitizer::stripAllTags( $html ) );
		return $lang->truncateForVisual( $plaintext, $length );
	}

	/**
	 * Extract an edit excerpt from a revision
	 *
	 * @param RevisionRecord $revision
	 * @param Language $lang
	 * @param int $length Length in characters (not bytes); default DEFAULT_SNIPPET_LENGTH
	 * @return string
	 */
	public static function getEditExcerpt(
		RevisionRecord $revision, Language $lang, $length = self::DEFAULT_SNIPPET_LENGTH
	) {
		$interpretation = self::getChangeInterpretationForRevision( $revision );
		$section = self::detectSectionTitleAndText( $interpretation );
		return $lang->truncateForVisual( $section['section-title'] . ' ' . $section['section-text'], $length );
	}
}

class_alias( DiscussionParser::class, 'EchoDiscussionParser' );
