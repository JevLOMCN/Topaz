<?php

namespace MediaWiki\Extension\SpamBlacklist;

use ApiMessage;
use Content;
use IContextSource;
use LogicException;
use MediaWiki\Content\IContentHandlerFactory;
use MediaWiki\Content\Renderer\ContentRenderer;
use MediaWiki\EditPage\EditPage;
use MediaWiki\ExternalLinks\LinkFilter;
use MediaWiki\Hook\EditFilterHook;
use MediaWiki\Hook\EditFilterMergedContentHook;
use MediaWiki\Hook\UploadVerifyUploadHook;
use MediaWiki\Html\Html;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Status\Status;
use MediaWiki\Storage\EditResult;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Storage\Hook\ParserOutputStashForEditHook;
use MediaWiki\Storage\PageEditStash;
use MediaWiki\User\Hook\UserCanSendEmailHook;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use Message;
use MessageSpecifier;
use ParserOptions;
use UploadBase;
use Wikimedia\Assert\PreconditionException;
use WikiPage;

/**
 * Hooks for the spam blacklist extension
 */
class Hooks implements
	EditFilterHook,
	EditFilterMergedContentHook,
	UploadVerifyUploadHook,
	PageSaveCompleteHook,
	ParserOutputStashForEditHook,
	UserCanSendEmailHook
{

	/** @var PermissionManager */
	private $permissionManager;

	/** @var PageEditStash */
	private $pageEditStash;

	/** @var ContentRenderer */
	private $contentRenderer;

	/** @var IContentHandlerFactory */
	private $contentHandlerFactory;

	/**
	 * @param PermissionManager $permissionManager
	 * @param PageEditStash $pageEditStash
	 * @param ContentRenderer $contentRenderer
	 * @param IContentHandlerFactory $contentHandlerFactory
	 */
	public function __construct(
		PermissionManager $permissionManager,
		PageEditStash $pageEditStash,
		ContentRenderer $contentRenderer,
		IContentHandlerFactory $contentHandlerFactory
	) {
		$this->permissionManager = $permissionManager;
		$this->pageEditStash = $pageEditStash;
		$this->contentRenderer = $contentRenderer;
		$this->contentHandlerFactory = $contentHandlerFactory;
	}

	/**
	 * Hook function for EditFilterMergedContent
	 *
	 * @param IContextSource $context
	 * @param Content $content
	 * @param Status $status
	 * @param string $summary
	 * @param User $user
	 * @param bool $minoredit
	 *
	 * @return bool
	 */
	public function onEditFilterMergedContent(
		IContextSource $context,
		Content $content,
		Status $status,
		$summary,
		User $user,
		$minoredit
	) {
		if ( $this->permissionManager->userHasRight( $user, 'sboverride' ) ) {
			return true;
		}

		$title = $context->getTitle();
		try {
			// Try getting the update directly
			$updater = $context->getWikiPage()->getCurrentUpdate();
			$pout = $updater->getParserOutputForMetaData();
		} catch ( PreconditionException | LogicException $exception ) {
			$stashedEdit = $this->pageEditStash->checkCache(
				$title,
				$content,
				$user
			);
			if ( $stashedEdit ) {
				// Try getting the value from edit stash
				/** @var ParserOutput $output */
				$pout = $stashedEdit->output;
			} else {
				// Last resort, parse the page.
				$pout = $this->contentRenderer->getParserOutput(
					$content,
					$title,
					null,
					null,
					false
				);
			}
		}
		$links = LinkFilter::getIndexedUrlsNonReversed( array_keys( $pout->getExternalLinks() ) );
		// HACK: treat the edit summary as a link if it contains anything
		// that looks like it could be a URL or e-mail address.
		if ( preg_match( '/\S(\.[^\s\d]{2,}|[\/@]\S)/', $summary ) ) {
			$links[] = $summary;
		}

		$spamObj = BaseBlacklist::getSpamBlacklist();
		$matches = $spamObj->filter( $links, $title, $user );

		if ( $matches !== false ) {
			$error = new ApiMessage(
				wfMessage( 'spam-blacklisted-link', Message::listParam( $matches ) ),
				'spamblacklist',
				[
					'spamblacklist' => [ 'matches' => $matches ],
				]
			);
			$status->fatal( $error );
			return false;
		}

		return true;
	}

	/**
	 * @param WikiPage $page
	 * @param Content $content
	 * @param ParserOutput $output
	 * @param string $summary
	 * @param User $user
	 */
	public function onParserOutputStashForEdit(
		$page,
		$content,
		$output,
		$summary,
		$user
	) {
		$links = LinkFilter::getIndexedUrlsNonReversed( array_keys( $output->getExternalLinks() ) );
		$spamObj = BaseBlacklist::getSpamBlacklist();
		$spamObj->warmCachesForFilter( $page->getTitle(), $links, $user );
	}

	/**
	 * Verify that the user can send emails
	 *
	 * @param User $user
	 * @param array &$hookErr
	 * @return bool
	 */
	public function onUserCanSendEmail( $user, &$hookErr ) {
		if ( $this->permissionManager->userHasRight( $user, 'sboverride' ) ) {
			return true;
		}
		$blacklist = BaseBlacklist::getEmailBlacklist();
		if ( $blacklist->checkUser( $user ) ) {
			return true;
		}

		$hookErr = [ 'spam-blacklisted-email', 'spam-blacklisted-email-text', null ];

		// No other hook handler should run
		return false;
	}

	/**
	 * Hook function for EditFilter
	 * Confirm that a local blacklist page being saved is valid,
	 * and toss back a warning to the user if it isn't.
	 *
	 * @param EditPage $editPage
	 * @param string $text
	 * @param string $section
	 * @param string &$hookError
	 * @param string $summary
	 */
	public function onEditFilter( $editPage, $text, $section, &$hookError, $summary ) {
		$title = $editPage->getTitle();
		$thisPageName = $title->getPrefixedDBkey();

		if ( !BaseBlacklist::isLocalSource( $title ) ) {
			wfDebugLog( 'SpamBlacklist',
				"Spam blacklist validator: [[$thisPageName]] not a local blacklist\n"
			);
			return;
		}

		$type = BaseBlacklist::getTypeFromTitle( $title );
		if ( $type === false ) {
			return;
		}

		$lines = explode( "\n", $text );

		$badLines = SpamRegexBatch::getBadLines( $lines, BaseBlacklist::getInstance( $type ) );
		if ( $badLines ) {
			wfDebugLog( 'SpamBlacklist',
				"Spam blacklist validator: [[$thisPageName]] given invalid input lines: " .
					implode( ', ', $badLines ) . "\n"
			);

			$badList = "*<code>" .
				implode( "</code>\n*<code>",
					array_map( 'wfEscapeWikiText', $badLines ) ) .
				"</code>\n";
			$hookError =
				Html::errorBox(
					wfMessage( 'spam-invalid-lines' )->numParams( $badLines )->text() . "<br />" .
					$badList
					) .
					"\n<br clear='all' />\n";
		} else {
			wfDebugLog( 'SpamBlacklist',
				"Spam blacklist validator: [[$thisPageName]] ok or empty blacklist\n"
			);
		}
	}

	/**
	 * Hook function for PageSaveComplete
	 * Clear local spam blacklist caches on page save.
	 *
	 * @param WikiPage $wikiPage
	 * @param UserIdentity $userIdentity
	 * @param string $summary
	 * @param int $flags
	 * @param RevisionRecord $revisionRecord
	 * @param EditResult $editResult
	 */
	public function onPageSaveComplete(
		$wikiPage,
		$userIdentity,
		$summary,
		$flags,
		$revisionRecord,
		$editResult
	) {
		if ( !BaseBlacklist::isLocalSource( $wikiPage->getTitle() ) ) {
			return;
		}

		// This sucks because every Blacklist needs to be cleared
		foreach ( BaseBlacklist::getBlacklistTypes() as $type => $class ) {
			$blacklist = BaseBlacklist::getInstance( $type );
			$blacklist->clearCache();
		}
	}

	/**
	 * @param UploadBase $upload
	 * @param User $user
	 * @param array|null $props
	 * @param string $comment
	 * @param string $pageText
	 * @param array|MessageSpecifier &$error
	 */
	public function onUploadVerifyUpload(
		UploadBase $upload,
		User $user,
		?array $props,
		$comment,
		$pageText,
		&$error
	) {
		if ( $this->permissionManager->userHasRight( $user, 'sboverride' ) ) {
			return;
		}

		$title = $upload->getTitle();

		// get the link from the not-yet-saved page content.
		$content = $this->contentHandlerFactory->getContentHandler( $title->getContentModel() )
			->unserializeContent( $pageText );
		$parserOptions = ParserOptions::newFromAnon();
		$output = $this->contentRenderer->getParserOutput( $content, $title, null, $parserOptions );
		$links = LinkFilter::getIndexedUrlsNonReversed( array_keys( $output->getExternalLinks() ) );

		// HACK: treat comment as a link if it contains anything
		// that looks like it could be a URL or e-mail address.
		if ( preg_match( '/\S(\.[^\s\d]{2,}|[\/@]\S)/', $comment ) ) {
			$links[] = $comment;
		}
		if ( !$links ) {
			return;
		}

		$spamObj = BaseBlacklist::getSpamBlacklist();
		$matches = $spamObj->filter( $links, $title, $user );

		if ( $matches !== false ) {
			$error = new ApiMessage(
				wfMessage( 'spam-blacklisted-link', Message::listParam( $matches ) ),
				'spamblacklist',
				[
					'spamblacklist' => [ 'matches' => $matches ],
				]
			);
		}
	}
}
