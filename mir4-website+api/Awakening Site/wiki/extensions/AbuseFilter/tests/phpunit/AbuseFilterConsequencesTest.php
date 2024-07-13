<?php

use MediaWiki\Block\BlockUser;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Extension\AbuseFilter\FilterRunnerFactory;
use MediaWiki\Extension\AbuseFilter\Hooks\Handlers\FilteredActionsHandler;
use MediaWiki\Extension\AbuseFilter\Parser\AFPData;
use MediaWiki\MediaWikiServices;
use MediaWiki\Status\Status;
use MediaWiki\Storage\PageEditStash;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\WikiMap\WikiMap;
use Psr\Log\LoggerInterface;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * Complete tests where filters are saved, actions are executed and the right
 *   consequences are expected to be taken
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GPL-2.0-or-later
 */

/**
 * @group Test
 * @group AbuseFilter
 * @group AbuseFilterConsequences
 * @group Database
 * @group Large
 *
 * @covers \MediaWiki\Extension\AbuseFilter\FilterRunner
 * @covers \MediaWiki\Extension\AbuseFilter\Hooks\Handlers\FilteredActionsHandler
 * @covers \MediaWiki\Extension\AbuseFilter\VariableGenerator\RunVariableGenerator::getDeleteVars
 * @covers \MediaWiki\Extension\AbuseFilter\VariableGenerator\RunVariableGenerator::getMoveVars
 * @covers \MediaWiki\Extension\AbuseFilter\VariableGenerator\RunVariableGenerator::getUploadVars
 * @covers \MediaWiki\Extension\AbuseFilter\AbuseFilterPreAuthenticationProvider
 * @covers \MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagger
 * @covers \MediaWiki\Extension\AbuseFilter\BlockAutopromoteStore
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Block
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\BlockAutopromote
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\BlockingConsequence
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Consequence
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Degroup
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Disallow
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\RangeBlock
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Tag
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Throttle
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Warn
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesLookup
 * @covers \MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesExecutor
 */
class AbuseFilterConsequencesTest extends MediaWikiIntegrationTestCase {
	use AbuseFilterCreateAccountTestTrait;
	use AbuseFilterUploadTestTrait;

	/**
	 * @var User The user performing actions
	 */
	private $user;

	// phpcs:disable Generic.Files.LineLength
	/** @var array Filters that may be created, their key is the ID. */
	private const FILTERS = [
		1 => [
			'af_pattern' => 'added_lines irlike "foo"',
			'af_public_comments' => 'Mock filter for edit',
			'actions' => [
				'warn' => [
					'abusefilter-my-warning'
				],
				'tag' => [
					'filtertag'
				]
			]
		],
		2 => [
			'af_pattern' => 'action = "move" & moved_to_title contains "test" & moved_to_title === moved_to_text',
			'af_public_comments' => 'Mock filter for move',
			'af_hidden' => 1,
			'actions' => [
				'disallow' => [],
				'block' => [
					'blocktalk',
					'8 hours',
					'infinity'
				]
			]
		],
		3 => [
			'af_pattern' => 'action = "delete" & "test" in lcase(page_prefixedtitle) & page_prefixedtitle === article_prefixedtext',
			'af_public_comments' => 'Mock filter for delete',
			'af_global' => 1,
			'actions' => [
				'degroup' => []
			]
		],
		5 => [
			// XXX Need to hardcode UTSysop here because this is a constant
			'af_pattern' => 'user_name == "UTSysop"',
			'af_public_comments' => 'Mock filter',
			'actions' => [
				'tag' => [
					'firstTag',
					'secondTag'
				]
			]
		],
		6 => [
			'af_pattern' => 'edit_delta === 7',
			'af_public_comments' => 'Mock filter with edit_delta',
			'af_hidden' => 1,
			'af_global' => 1,
			'actions' => [
				'disallow' => [
					'abusefilter-disallowed-really'
				]
			]
		],
		7 => [
			'af_pattern' => 'timestamp === string(timestamp)',
			'af_public_comments' => 'Mock filter with timestamp',
			'actions' => [
				'degroup' => []
			]
		],
		8 => [
			'af_pattern' => 'added_lines_pst irlike "\\[\\[Link\\|Link\\]\\]"',
			'af_public_comments' => 'Mock filter with pst',
			'actions' => [
				'disallow' => [],
				'block' => [
					'NoTalkBlockSet',
					'4 hours',
					'4 hours'
				]
			]
		],
		9 => [
			'af_pattern' => 'new_size > old_size',
			'af_public_comments' => 'Mock filter with size',
			'af_hidden' => 1,
			'actions' => [
				'disallow' => [],
				'block' => [
					'blocktalk',
					'3 hours',
					'3 hours'
				]
			]
		],
		10 => [
			'af_pattern' => '1 == 1',
			'af_public_comments' => 'Mock throttled filter',
			'af_hidden' => 1,
			'af_throttled' => 1,
			'actions' => [
				'tag' => [
					'testTag'
				],
				'block' => [
					'blocktalk',
					'infinity',
					'infinity'
				]
			]
		],
		11 => [
			'af_pattern' => '1 == 1',
			'af_public_comments' => 'Catch-all filter which throttles',
			'actions' => [
				'throttle' => [
					11,
					'1,3600',
					'site'
				],
				'disallow' => []
			]
		],
		12 => [
			'af_pattern' => 'page_title == user_name & user_name === page_title',
			'af_public_comments' => 'Mock filter for userpage',
			'actions' => [
				'disallow' => [],
				'block' => [
					'blocktalk',
					'8 hours',
					'1 day'
				],
				'degroup' => []
			]
		],
		13 => [
			'af_pattern' => '2 == 2',
			'af_public_comments' => 'Another throttled mock filter',
			'af_throttled' => 1,
			'actions' => [
				'block' => [
					'blocktalk',
					'8 hours',
					'1 day'
				],
				'degroup' => []
			]
		],
		14 => [
			'af_pattern' => '5/int(article_text) == 3',
			'af_public_comments' => 'Filter with a possible division by zero',
			'actions' => [
				'disallow' => []
			]
		],
		15 => [
			'af_pattern' => 'action contains "createaccount"',
			'af_public_comments' => 'Catch-all for account creations',
			'af_hidden' => 1,
			'actions' => [
				'disallow' => []
			]
		],
		18 => [
			'af_pattern' => '1 == 1',
			'af_public_comments' => 'Global filter',
			'af_global' => 1,
			'actions' => [
				'warn' => [
					'abusefilter-warning'
				],
				'disallow' => []
			]
		],
		19 => [
			// XXX Need to hardcode UTSysop here because this is a constant
			'af_pattern' => 'user_name === "UTSysop"',
			'af_public_comments' => 'Another global filter',
			'af_global' => 1,
			'actions' => [
				'tag' => [
					'globalTag'
				]
			]
		],
		20 => [
			'af_pattern' => 'page_title === "Cellar door"',
			'af_public_comments' => 'Yet another global filter',
			'af_global' => 1,
			'actions' => [
				'disallow' => [],
			]
		],
		21 => [
			'af_pattern' => '1==1',
			'af_public_comments' => 'Dangerous filter',
			'actions' => [
				'blockautopromote' => []
			]
		],
		22 => [
			'af_pattern' => 'action contains "upload" & "Block me" in added_lines & file_size > 0 & ' .
				'file_mime contains "/" & file_width + file_height > 0 & summary !== ""',
			'af_public_comments' => 'Filter for uploads',
			'actions' => [
				'warn' => [
					'abusefilter-random-upload'
				],
				'blockautopromote' => []
			]
		],
		23 => [
			'af_pattern' => '1 === 1',
			'af_public_comments' => 'Catch-all for warning + disallow',
			'actions' => [
				'warn' => [
					'abusefilter-my-warning'
				],
				'disallow' => [
					'abusefilter-my-disallow'
				]
			]
		],
		24 => [
			// We used this for testRevIdSet()
			'af_pattern' => '1 == 1',
			'af_public_comments' => 'Always matches, no actions',
			'actions' => []
		],
	];
	// phpcs:enable Generic.Files.LineLength

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();
		// Ensure that our user is a sysop
		$this->user = $this->getTestSysop()->getUser();

		// Pin time to avoid time shifts on relative block duration
		ConvertibleTimestamp::setFakeTime( time() );

		// Make sure that the config we're using is the one we're expecting
		$this->setMwGlobals( [
			// Exclude noisy creation log
			'wgPageCreationLog' => false,
			'wgAbuseFilterActions' => [
				'throttle' => true,
				'warn' => true,
				'disallow' => true,
				'blockautopromote' => true,
				'block' => true,
				'rangeblock' => true,
				'degroup' => true,
				'tag' => true
			],
		] );
	}

	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		$this->clearUploads();
		parent::tearDown();
	}

	/**
	 * Creates new filters with the given ids, referred to self::FILTERS
	 *
	 * @param int[] $ids IDs of the filters to create
	 */
	private function createFilters( $ids ) {
		global $wgAbuseFilterActions;

		$dbw = $this->getDb();
		$defaultRowSection = [
			'af_user' => 0,
			'af_user_text' => 'FilterTester',
			'af_actor' => 1,
			'af_timestamp' => $dbw->timestamp(),
			'af_group' => 'default',
			'af_comments' => '',
			'af_hit_count' => 0,
			'af_enabled' => 1,
			'af_hidden' => 0,
			'af_throttled' => 0,
			'af_deleted' => 0,
			'af_global' => 0
		];

		foreach ( $ids as $id ) {
			$filter = self::FILTERS[$id] + $defaultRowSection;
			$actions = $filter['actions'];
			unset( $filter['actions'] );
			$filter[ 'af_actions' ] = implode( ',', array_keys( $actions ) );
			$filter[ 'af_id' ] = $id;

			$dbw->insert(
				"abuse_filter",
				$filter,
				__METHOD__
			);

			$actionsRows = [];
			foreach ( array_filter( $wgAbuseFilterActions ) as $action => $_ ) {
				if ( isset( $actions[$action] ) ) {
					$parameters = $actions[$action];

					$thisRow = [
						'afa_filter' => $id,
						'afa_consequence' => $action,
						'afa_parameters' => implode( "\n", $parameters )
					];
					$actionsRows[] = $thisRow;
				}
			}

			$dbw->insert(
				"abuse_filter_action",
				$actionsRows,
				__METHOD__
			);
		}
	}

	/**
	 * @param Title $title Title of the page to edit
	 * @param Content $content The new content of the page
	 * @param string $summary The summary of the edit
	 * @return string The status of the operation, as returned by the API.
	 */
	private function stashEdit( Title $title, Content $content, string $summary ) {
		$services = $this->getServiceContainer();
		$pageUpdater = $services->getWikiPageFactory()->newFromTitle( $title )->newPageUpdater( $this->user );
		return $services->getPageEditStash()->parseAndCache(
			$pageUpdater,
			$content,
			$this->user,
			$summary
		);
	}

	/**
	 * @param Title $title Title of the page to edit
	 * @param Content|string $oldText Old content of the page
	 * @param Content|string $newText The new content of the page
	 * @param string $summary The summary of the edit
	 * @param bool|null $fromStash Whether to stash the edit. Null means no stashing, false means
	 *   stash the edit but don't reuse it for saving, true means stash and reuse.
	 * @return Status
	 */
	private function doEdit( Title $title, $oldText, $newText, $summary, $fromStash = null ) {
		$services = $this->getServiceContainer();
		$page = $services->getWikiPageFactory()->newFromTitle( $title );
		if ( !$page->exists() ) {
			$status = $this->editPage(
				$page,
				$oldText,
				__METHOD__ . ' page creation'
			);
			if ( !$status->isGood() ) {
				throw new RuntimeException( "Could not create test page. $status" );
			}
			$page->clear();
			$title->resetArticleID( -1 );
		}

		if ( $fromStash !== null ) {
			// If we want to save from stash, submit the same text
			$stashText = $newText;
			if ( $fromStash === false ) {
				// Otherwise, stash some random text which won't match the actual edit
				$stashText = md5( uniqid( rand(), true ) );
			}
			$stashContent = $stashText instanceof Content ? $stashText : new WikitextContent( $stashText );
			$stashResult = $this->stashEdit( $title, $stashContent, $summary );
			if ( $stashResult !== PageEditStash::ERROR_NONE ) {
				throw new RuntimeException( "The edit cannot be stashed, got the following error: $stashResult" );
			}
		}

		$content = $newText instanceof Content
			? $newText
			: ContentHandler::makeContent( $newText, $title );
		$status = Status::newGood();
		$context = RequestContext::getMain();
		$context->setUser( $this->user );
		$context->setWikiPage( $page );

		$hooksHandler = new FilteredActionsHandler(
			$services->getStatsdDataFactory(),
			AbuseFilterServices::getFilterRunnerFactory(),
			AbuseFilterServices::getVariableGeneratorFactory(),
			AbuseFilterServices::getEditRevUpdater(),
			AbuseFilterServices::getBlockedDomainFilter(),
			$services->getPermissionManager()
		);
		$hooksHandler->onEditFilterMergedContent( $context, $content, $status, $summary,
			$this->user, false );

		if ( $status->isGood() ) {
			// Edit the page in case the test will expect for it to exist
			$this->editPage(
				$page,
				$newText,
				$summary,
				NS_MAIN,
				$this->user
			);
			$page->clear();
			$title->resetArticleID( -1 );
		}

		return $status;
	}

	/**
	 * Executes an action to filter
	 *
	 * @param array $params Parameters of the action
	 * @return Status
	 */
	private function doAction( $params ) {
		$target = Title::newFromTextThrow( $params['target'] );

		switch ( $params['action'] ) {
			case 'edit':
				$status = $this->doEdit( $target, $params['oldText'], $params['newText'], $params['summary'] );
				break;
			case 'stashedit':
				$stashStatus = $params['stashType'] === 'hit';
				$status = $this->doEdit(
					$target,
					$params['oldText'],
					$params['newText'],
					$params['summary'],
					$stashStatus
				);
				break;
			case 'move':
				// Ensure that the page exists
				$this->getExistingTestPage( $target );
				$newTitle = isset( $params['newTitle'] )
					? Title::newFromTextThrow( $params['newTitle'] )
					: $this->getNonExistingTestPage()->getTitle();
				$status = $this->getServiceContainer()
					->getMovePageFactory()
					->newMovePage( $target, $newTitle )
					->move( $this->user, 'AbuseFilter move test', false );
				break;
			case 'delete':
				$page = $this->getExistingTestPage( $target );
				$status = $page->doDeleteArticleReal(
					'Testing deletion in AbuseFilter',
					$this->user
				);
				break;
			case 'createaccount':
				$status = $this->createAccount( $params['username'] );
				break;
			case 'upload':
				[ $status, $this->clearPath ] = $this->doUpload(
					$this->user,
					$params['target'],
					$params['newText'] ?? 'AbuseFilter test upload',
					$params['summary'] ?? 'Test'
				);
				break;
			default:
				throw new UnexpectedValueException( 'Unrecognized action ' . $params['action'] );
		}

		// Clear cache since we'll need to retrieve some fresh data about the user
		// like blocks and groups later when checking expected values
		$this->user->clearInstanceCache();

		return $status;
	}

	/**
	 * @param array[] $actionsParams Arrays of parameters for every action
	 * @return Status[]
	 */
	private function doActions( $actionsParams ) {
		$ret = [];
		foreach ( $actionsParams as $params ) {
			$ret[] = $this->doAction( $params );
		}
		return $ret;
	}

	/**
	 * Helper function to retrieve change tags applied to an edit or log entry
	 *
	 * @param array $actionParams As given by the data provider
	 * @return string[] The applied tags
	 */
	private function getActionTags( $actionParams ) {
		$dbw = $this->getDb();
		$title = Title::newFromTextThrow( $actionParams['target'] );
		$store = $this->getServiceContainer()->getChangeTagsStore();
		if ( $actionParams['action'] === 'edit' || $actionParams['action'] === 'stashedit' ) {
			return $store->getTags( $dbw, null, $title->getLatestRevID() );
		}

		$logType = $actionParams['action'] === 'createaccount' ? 'newusers' : $actionParams['action'];
		$logAction = $logType === 'newusers' ? 'create2' : $logType;
		$id = $dbw->selectField(
			'logging',
			'log_id',
			[
				'log_namespace' => $title->getNamespace(),
				'log_title' => $title->getDBkey(),
				'log_type' => $logType,
				'log_action' => $logAction
			],
			__METHOD__,
			[],
			[ 'ORDER BY' => 'log_id DESC' ]
		);
		if ( !$id ) {
			$this->fail( 'Could not find the action in the logging table.' );
		}
		return $store->getTags( $dbw, null, null, (int)$id );
	}

	/**
	 * Checks that consequences are effectively taken and builds an array of expected and actual
	 * consequences which can be compared.
	 *
	 * @param Status $result As returned by self::doAction
	 * @param array $actionParams As it's given by data providers
	 * @param array $expectedConsequences
	 * @return array [ expected consequences, actual consequences ]
	 */
	private function checkConsequences( $result, $actionParams, $expectedConsequences ) {
		$expectedErrors = [];
		$testErrorMessage = false;
		foreach ( $expectedConsequences as $consequence => $ids ) {
			foreach ( $ids as $id ) {
				$params = self::FILTERS[$id]['actions'][$consequence];
				switch ( $consequence ) {
					case 'warn':
						// Aborts the hook with the warning message as error.
						$expectedErrors[] = $params[0] ?? 'abusefilter-warning';
						break;
					case 'disallow':
						// Aborts the hook with the disallow message error.
						$expectedErrors[] = $params[0] ?? 'abusefilter-disallowed';
						break;
					case 'block':
						// Aborts the hook with 'abusefilter-blocked-display' error. Should block
						// the user with expected duration and options.
						$userBlock = $this->user->getBlock( false );

						if ( !$userBlock ) {
							$testErrorMessage = "User isn't blocked.";
							break;
						}

						$shouldPreventTalkEdit = $params[0] === 'blocktalk';
						$edittalkCheck = $userBlock->appliesToUsertalk( $this->user->getTalkPage() ) ===
							$shouldPreventTalkEdit;
						if ( !$edittalkCheck ) {
							$testErrorMessage = 'The expected block option "edittalk" options does not ' .
								'match the actual one.';
							break;
						}

						$expectedExpiry = BlockUser::parseExpiryInput( $params[2] );
						$actualExpiry = $userBlock->getExpiry();
						if ( !wfIsInfinity( $actualExpiry ) ) {
							$actualExpiry = wfTimestamp( TS_MW, $actualExpiry );
						}
						if ( $expectedExpiry === false || $expectedExpiry !== $actualExpiry ) {
							$testErrorMessage = "The expected block expiry ($expectedExpiry) does not " .
								"match the actual one ($actualExpiry).";
							break;
						}

						$expectedErrors[] = 'abusefilter-blocked-display';
						break;
					case 'degroup':
						// Aborts the hook with 'abusefilter-degrouped' error and degroups the user.
						$expectedErrors[] = 'abusefilter-degrouped';
						$ugm = MediaWikiServices::getInstance()->getUserGroupManager();
						$groupCheck = !in_array( 'sysop', $ugm->getUserEffectiveGroups( $this->user ) );
						if ( !$groupCheck ) {
							$testErrorMessage = 'The user was not degrouped.';
						}
						break;
					case 'tag':
						// Only adds tags, to be retrieved in change_tag table.
						$appliedTags = $this->getActionTags( $actionParams );
						$tagCheck = count( array_diff( $params, $appliedTags ) ) === 0;
						if ( !$tagCheck ) {
							$expectedTags = implode( ', ', $params );
							$actualTags = implode( ', ', $appliedTags );

							$testErrorMessage = "Expected the action to have the following tags: $expectedTags. " .
								"Got the following instead: $actualTags.";
						}
						break;
					case 'throttle':
						throw new UnexpectedValueException( 'Use self::testThrottleConsequence to test throttling' );
					case 'blockautopromote':
						// Aborts the hook with 'abusefilter-autopromote-blocked' error and prevent promotion.
						$expectedErrors[] = 'abusefilter-autopromote-blocked';
						$value = AbuseFilterServices::getBlockAutopromoteStore()
							->getAutoPromoteBlockStatus( $this->user );
						if ( !$value ) {
							$testErrorMessage = "The key for blocking autopromotion wasn't set.";
						}
						break;
					default:
						throw new UnexpectedValueException( "Consequence not recognized: $consequence." );
				}

				if ( $testErrorMessage ) {
					$this->fail( $testErrorMessage );
				}
			}
		}

		$errors = $result->getErrors();

		$actual = [];
		foreach ( $errors as $error ) {
			// We don't use any of the "API" stuff in ApiMessage here, but this is the most
			// convenient way to get a Message from a StatusValue error structure.
			$msg = ApiMessage::create( $error )->getKey();
			if ( strpos( $msg, 'abusefilter' ) !== false ) {
				$actual[] = $msg;
			}
		}

		sort( $expectedErrors );
		sort( $actual );
		return [ $expectedErrors, $actual ];
	}

	/**
	 * @param array $actionParams
	 * @param array $expectedConsequences
	 */
	private function assertAbuseLog( $actionParams, $expectedConsequences ): void {
		$consequencesByFilter = [];
		foreach ( $expectedConsequences as $consequence => $ids ) {
			foreach ( $ids as $id ) {
				$consequencesByFilter[$id][] = $consequence;
			}
		}

		foreach ( $consequencesByFilter as $filter => $consequences ) {
			$action = $actionParams['action'];
			if ( $action === 'stashedit' ) {
				$action = 'edit';
			}
			$title = Title::newFromTextThrow( $actionParams['target'] );

			$row = $this->getDb()->selectRow(
				'abuse_filter_log',
				'*',
				[
					'afl_filter_id' => $filter,
					'afl_global' => 0,
				],
				__METHOD__,
				[ 'ORDER BY' => 'afl_id DESC' ]
			);
			$this->assertNotFalse( $row );

			$dumpStr = FormatJson::encode( $row );
			$this->assertSame( $action, $row->afl_action, $dumpStr );
			$this->assertNull( $row->afl_wiki, $dumpStr );
			if ( $action === 'createaccount' ) {
				$this->assertSame( $actionParams['username'], $row->afl_user_text, $dumpStr );
				$this->assertSame( -1, (int)$row->afl_namespace, $dumpStr );
			} else {
				$this->assertSame( $this->user->getName(), $row->afl_user_text, $dumpStr );
				$this->assertSame( $title->getNamespace(), (int)$row->afl_namespace, $dumpStr );
				$this->assertSame( $title->getDBkey(), $row->afl_title, $dumpStr );
			}
			if (
				in_array( 'disallow', $consequences )
				&& array_intersect( $consequences, [ 'block', 'blockautopromote', 'degroup' ] )
			) {
				$consequences = array_diff( $consequences, [ 'disallow' ] );
			}
			$this->assertArrayEquals(
				$consequences,
				explode( ',', $row->afl_actions ),
				false,
				false,
				$dumpStr
			);
		}
	}

	/**
	 * Creates new filters, execute an action and check the consequences
	 *
	 * @param int[] $createIds IDs of the filters to create
	 * @param array $actionParams Details of the action we need to execute to trigger filters
	 * @param array $expectedConsequences The consequences we're expecting
	 * @dataProvider provideFilters
	 * @covers \MediaWiki\Extension\AbuseFilter\AbuseLogger
	 */
	public function testFilterConsequences( $createIds, $actionParams, $expectedConsequences ) {
		$this->createFilters( $createIds );
		$result = $this->doAction( $actionParams );
		[ $expected, $actual ] = $this->checkConsequences( $result, $actionParams, $expectedConsequences );

		$this->assertEquals(
			$expected,
			$actual,
			'The error messages obtained by performing the action do not match.'
		);

		$this->assertAbuseLog( $actionParams, $expectedConsequences );
	}

	/**
	 * Data provider for testFilterConsequences. For every test case, we pass
	 *   - an array with the IDs of the filters to be created (listed in self::FILTERS),
	 *   - an array with details of the action to execute in order to trigger the filters,
	 *   - an array of expected consequences of the form
	 *       [ 'consequence name' => [ IDs of the filter to take its parameters from ] ]
	 *       Such IDs may be more than one if we have a warning that is shown twice.
	 *
	 * @return array
	 */
	public static function provideFilters() {
		// XXX Need to hardcode the username of $this->user here.
		$username = 'UTSysop';
		return [
			'Basic test for "edit" action' => [
				[ 1, 2 ],
				[
					'action' => 'edit',
					'target' => 'Test page',
					'oldText' => 'Some old text for the test.',
					'newText' => 'I like foo',
					'summary' => 'Test AbuseFilter for edit action.'
				],
				[ 'warn'  => [ 1 ] ]
			],
			'Basic test for "move" action' => [
				[ 2 ],
				[
					'action' => 'move',
					'target' => 'Test page',
					'newTitle' => 'Another test page'
				],
				[ 'block' => [ 2 ] ]
			],
			'Basic test for "delete" action' => [
				[ 2, 3 ],
				[
					'action' => 'delete',
					'target' => 'Test page'
				],
				[ 'degroup' => [ 3 ] ]
			],
			'Basic test for "createaccount", no consequences.' => [
				[ 1, 2, 3 ],
				[
					'action' => 'createaccount',
					'target' => 'User:AnotherUser',
					'username' => 'AnotherUser'
				],
				[]
			],
			'Basic test for "createaccount", disallowed.' => [
				[ 15 ],
				[
					'action' => 'createaccount',
					'target' => 'User:AnotherUser',
					'username' => 'AnotherUser'
				],
				[ 'disallow' => [ 15 ] ]
			],
			'Check that all tags are applied' => [
				[ 5 ],
				[
					'action' => 'edit',
					'target' => "User:$username",
					'oldText' => 'Hey.',
					'newText' => 'I am a very nice user, really!',
					'summary' => ''
				],
				[ 'tag' => [ 5 ] ]
			],
			'Check that degroup and block are executed together' => [
				[ 2, 3, 7, 8 ],
				[
					'action' => 'edit',
					'target' => 'Link',
					'oldText' => 'What is a link?',
					'newText' => 'A link is something like this: [[Link|]].',
					'summary' => 'Explaining'
				],
				[ 'degroup' => [ 7 ], 'block' => [ 8 ] ]
			],
			'Check that the block duration is the longer one' => [
				[ 8, 9 ],
				[
					'action' => 'edit',
					'target' => 'Whatever',
					'oldText' => 'Whatever is whatever',
					'newText' => 'Whatever is whatever, whatever it is. BTW, here is a [[Link|]]',
					'summary' => 'Whatever'
				],
				[ 'disallow' => [ 8 ], 'block' => [ 8 ] ]
			],
			'Check that throttled filters only execute "safe" actions' => [
				[ 10 ],
				[
					'action' => 'edit',
					'target' => 'Buffalo',
					'oldText' => 'Buffalo',
					'newText' => 'Buffalo buffalo Buffalo buffalo buffalo buffalo Buffalo buffalo.',
					'summary' => 'Buffalo!'
				],
				[ 'tag' => [ 10 ] ]
			],
			'Check that degroup and block are both executed and degroup warning is shown twice' => [
				[ 1, 3, 7, 12 ],
				[
					'action' => 'edit',
					'target' => "User:$username",
					'oldText' => '',
					'newText' => 'A couple of lines about me...',
					'summary' => 'My user page'
				],
				[ 'block' => [ 12 ], 'degroup' => [ 7, 12 ] ]
			],
			'Check that every throttled filter only executes "safe" actions' => [
				[ 10, 13 ],
				[
					'action' => 'edit',
					'target' => 'Tyger! Tyger! Burning bright',
					'oldText' => 'In the forests of the night',
					'newText' => 'What immortal hand or eye',
					'summary' => 'Could frame thy fearful symmetry?'
				],
				[ 'tag' => [ 10 ] ]
			],
			'Check that runtime exceptions (division by zero) are correctly handled' => [
				[ 14 ],
				[
					'action' => 'edit',
					'target' => '0',
					'oldText' => 'Old text',
					'newText' => 'New text',
					'summary' => 'Some summary'
				],
				[]
			],
			'Test for blockautopromote action.' => [
				[ 21 ],
				[
					'action' => 'edit',
					'target' => 'Rainbow',
					'oldText' => '',
					'newText' => '...',
					'summary' => ''
				],
				[ 'blockautopromote' => [ 21 ] ],
			],
			'No consequences' => [
				[ 8 ],
				[
					'action' => 'edit',
					'target' => 'Anything',
					'oldText' => 'Bar',
					'newText' => 'Foo',
					'summary' => ''
				],
				[]
			],
			'Test upload action' => [
				[ 5 ],
				[
					'action' => 'upload',
					'target' => 'File:MyFile.svg',
				],
				[ 'tag' => [ 5 ] ]
			],
			'Test upload action 2' => [
				[ 22 ],
				[
					'action' => 'upload',
					'target' => 'File:MyFile.svg',
					'newText' => 'Block me please!',
					'summary' => 'Asking to be blocked'
				],
				[ 'warn' => [ 22 ] ]
			],
		];
	}

	/**
	 * Check an array of results from self::doAction to ensure that all but the last actions have been
	 *   executed (i.e. no errors).
	 * @param Status[] $results As returned by self::doActions
	 * @return Status The Status of the last action, to be later checked with self::checkConsequences
	 */
	private function checkThrottleConsequence( $results ) {
		$finalRes = array_pop( $results );
		foreach ( $results as $result ) {
			if ( !$result->isGood() ) {
				$this->fail( 'Only the last actions should have triggered a filter; the other ones ' .
					'should have been allowed.' );
			}
		}

		return $finalRes;
	}

	/**
	 * Like self::testFilterConsequences but for throttle, which deserves a special treatment
	 *
	 * @param int[] $createIds IDs of the filters to create
	 * @param array[] $actionsParams Details of the action we need to execute to trigger filters
	 * @param array $consequences The consequences we're expecting
	 * @dataProvider provideThrottleFilters
	 * @covers \MediaWiki\Extension\AbuseFilter\AbuseLogger
	 */
	public function testThrottle( $createIds, $actionsParams, $consequences ) {
		$this->createFilters( $createIds );
		$results = $this->doActions( $actionsParams );
		$res = $this->checkThrottleConsequence( $results );
		$lastParams = array_pop( $actionsParams );
		[ $expected, $actual ] = $this->checkConsequences( $res, $lastParams, $consequences );

		$this->assertEquals(
			$expected,
			$actual,
			'The error messages obtained by performing the action do not match.'
		);

		$this->assertAbuseLog( $lastParams, $consequences );
	}

	/**
	 * Data provider for testThrottle. For every test case, we pass
	 *   - an array with the IDs of the filters to be created (listed in self::FILTERS),
	 *   - an array of array, where every sub-array holds the details of the action to execute in
	 *       order to trigger the filters, each one like in self::provideFilters
	 *   - an array of expected consequences for the last action (i.e. after throttling) of the form
	 *       [ 'consequence name' => [ IDs of the filter to take its parameters from ] ]
	 *       Such IDs may be more than one if we have a warning that is shown twice.
	 *
	 *
	 * @return array
	 */
	public static function provideThrottleFilters() {
		return [
			'Basic test for throttling edits' => [
				[ 11 ],
				[
					[
						'action' => 'edit',
						'target' => 'Throttle',
						'oldText' => 'What is throttle?',
						'newText' => 'Throttle is something that should happen...',
						'summary' => 'Throttle'
					],
					[
						'action' => 'edit',
						'target' => 'Throttle',
						'oldText' => 'Throttle is something that should happen...',
						'newText' => '... Right now!',
						'summary' => 'Throttle'
					]
				],
				[ 'disallow' => [ 11 ] ]
			],
			'Basic test for throttling "move"' => [
				[ 11 ],
				[
					[
						'action' => 'move',
						'target' => 'Throttle test',
						'newTitle' => 'Another throttle test'
					],
					[
						'action' => 'move',
						'target' => 'Another throttle test',
						'newTitle' => 'Yet another throttle test'
					],
				],
				[ 'disallow' => [ 11 ] ]
			],
			'Basic test for throttling "delete"' => [
				[ 11 ],
				[
					[
						'action' => 'delete',
						'target' => 'Test page'
					],
					[
						'action' => 'delete',
						'target' => 'Test page'
					]
				],
				[ 'disallow' => [ 11 ] ]
			],
			'Basic test for throttling "createaccount"' => [
				[ 11 ],
				[
					[
						'action' => 'createaccount',
						'target' => 'User:AnotherUser',
						'username' => 'AnotherUser'
					],
					[
						'action' => 'createaccount',
						'target' => 'User:YetAnotherUser',
						'username' => 'YetAnotherUser'
					]
				],
				[ 'disallow' => [ 11 ] ]
			]
		];
	}

	/**
	 * Like self::testFilterConsequences but for warn, which deserves a special treatment.
	 * Data provider passes parameters for a single action, which we repeat twice
	 *
	 * @param int[] $createIds IDs of the filters to create
	 * @param array[] $actionParams Details of the action we need to execute to trigger filters
	 * @param int[] $warnIDs IDs of the filters which will warn us
	 * @param array $consequences The consequences we're expecting
	 * @dataProvider provideWarnFilters
	 * @covers \MediaWiki\Extension\AbuseFilter\AbuseLogger
	 */
	public function testWarn( $createIds, $actionParams, $warnIDs, $consequences ) {
		$this->createFilters( $createIds );
		$params = [ $actionParams, $actionParams ];
		[ $warnedStatus, $finalStatus ] = $this->doActions( $params );

		[ $expectedWarn, $actualWarn ] = $this->checkConsequences(
			$warnedStatus,
			$actionParams,
			[ 'warn' => $warnIDs ]
		);

		$this->assertSame(
			$expectedWarn,
			$actualWarn,
			'The error messages for the first action do not match.'
		);

		[ $expectedFinal, $actualFinal ] = $this->checkConsequences(
			$finalStatus,
			$actionParams,
			$consequences
		);

		$this->assertSame(
			$expectedFinal,
			$actualFinal,
			'The error messages for the second action do not match.'
		);

		$this->assertAbuseLog( $actionParams, $consequences );
	}

	/**
	 * Data provider for testWarn. For every test case, we pass
	 *   - an array with the IDs of the filters to be created (listed in self::FILTERS),
	 *   - an array with action parameters, like in self::provideFilters. This will be executed twice.
	 *   - an array of IDs of the filter which should give a warning
	 *   - an array of expected consequences for the last action (i.e. after throttling) of the form
	 *       [ 'consequence name' => [ IDs of the filter to take its parameters from ] ]
	 *       Such IDs may be more than one if we have a warning that is shown twice.
	 *
	 * @return array
	 */
	public static function provideWarnFilters() {
		return [
			'Basic test for warning and then tag' => [
				[ 1 ],
				[
					'action' => 'edit',
					'target' => 'Foo',
					'oldText' => 'Neutral text',
					'newText' => 'First foo version',
					'summary' => ''
				],
				[ 1 ],
				[ 'tag' => [ 1 ] ],
			],
			'Basic test for warning on "move"' => [
				[ 23 ],
				[
					'action' => 'move',
					'target' => 'Test warn',
					'newTitle' => 'Another warn test'
				],
				[ 23 ],
				[ 'disallow' => [ 23 ] ]
			],
			'Basic test for warning on "delete"' => [
				[ 23 ],
				[
					'action' => 'delete',
					'target' => 'Warned'
				],
				[ 23 ],
				[ 'disallow' => [ 23 ] ]
			],
			'Basic test for warning on "createaccount"' => [
				[ 23 ],
				[
					'action' => 'createaccount',
					'target' => 'User:AnotherWarnedUser',
					'username' => 'AnotherWarnedUser'
				],
				[ 23 ],
				[ 'disallow' => [ 23 ] ]
			]
		];
	}

	/**
	 * @param int[] $createIds IDs of the filters to create
	 * @param array $actionParams Details of the action we need to execute to trigger filters
	 * @param string[] $usedVars The variables effectively computed by filters in $createIds.
	 *   We'll search these in the stored dump.
	 * @covers \MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore
	 * @dataProvider provideFiltersAndVariables
	 */
	public function testVarDump( $createIds, $actionParams, $usedVars ) {
		$this->createFilters( $createIds );
		// We don't care about consequences here
		$this->doAction( $actionParams );

		// We just take a dump from a single filters, as they're all identical for the same action
		$dumpID = $this->getDb()->selectField(
			'abuse_filter_log',
			'afl_var_dump',
			'',
			__METHOD__,
			[ 'ORDER BY' => 'afl_timestamp DESC' ]
		);

		$vars = AbuseFilterServices::getVariablesBlobStore()->loadVarDump( $dumpID )->getVars();

		$interestingVars = array_intersect_key( $vars, array_fill_keys( $usedVars, true ) );

		sort( $usedVars );
		ksort( $interestingVars );
		$this->assertEquals(
			$usedVars,
			array_keys( $interestingVars ),
			"The saved variables aren't the expected ones."
		);
		$this->assertContainsOnlyInstancesOf(
			AFPData::class,
			$interestingVars,
			'Some variables have not been computed.'
		);
	}

	/**
	 * Data provider for testVarDump
	 *
	 * @return array
	 */
	public static function provideFiltersAndVariables() {
		return [
			[
				[ 1, 2 ],
				[
					'action' => 'edit',
					'target' => 'Test page',
					'oldText' => 'Some old text for the test.',
					'newText' => 'I like foo',
					'summary' => 'Test AbuseFilter for edit action.'
				],
				[ 'added_lines', 'action' ]
			],
			[
				[ 1, 2 ],
				[
					'action' => 'stashedit',
					'target' => 'Test page',
					'oldText' => 'Some old text for the test.',
					'newText' => 'I like foo',
					'summary' => 'Test AbuseFilter for edit action.',
					'stashType' => 'hit'
				],
				[ 'added_lines', 'action' ]
			],
			[
				[ 1, 2 ],
				[
					'action' => 'stashedit',
					'target' => 'Test page',
					'oldText' => 'Some old text for the test.',
					'newText' => 'I like foo',
					'summary' => 'Test AbuseFilter for edit action.',
					'stashType' => 'miss'
				],
				[ 'added_lines', 'action' ]
			],
			[
				[ 2 ],
				[
					'action' => 'move',
					'target' => 'Test page',
					'newTitle' => 'Another test page'
				],
				[ 'action', 'moved_to_title' ]
			],
			[
				[ 2, 3 ],
				[
					'action' => 'delete',
					'target' => 'Test page'
				],
				[ 'action', 'page_prefixedtitle' ]
			],
			[
				[ 15 ],
				[
					'action' => 'createaccount',
					'target' => 'User:AnotherUser',
					'username' => 'AnotherUser'
				],
				[ 'action' ]
			],
		];
	}

	/**
	 * Same as testFilterConsequences but only for stashed edits
	 *
	 * @param string $type Either "hit" or "miss". The former saves the edit from stash, the second
	 *   stashes the edit but doesn't reuse it.
	 * @param int[] $createIds IDs of the filters to create
	 * @param array $actionParams Details of the action we need to execute to trigger filters
	 * @param array $consequences The consequences we're expecting
	 * @dataProvider provideStashedEdits
	 * @covers \MediaWiki\Extension\AbuseFilter\AbuseLogger
	 */
	public function testStashedEdit( $type, $createIds, $actionParams, $consequences ) {
		if ( $type !== 'hit' && $type !== 'miss' ) {
			throw new InvalidArgumentException( '$type must be either "hit" or "miss"' );
		}
		// Add some info in actionParams identical for all tests
		$actionParams['action'] = 'stashedit';
		$actionParams['stashType'] = $type;

		$loggerMock = $this->createMock( LoggerInterface::class );
		$loggerCalls = [];
		$loggerMock->method( 'debug' )
			->willReturnCallback( static function ( $msg, $args ) use ( &$loggerCalls ) {
				if ( isset( $args['logtype'] ) ) {
					$loggerCalls[] = $args['logtype'];
				}
			} );
		$this->setLogger( 'StashEdit', $loggerMock );

		$this->createFilters( $createIds );
		$result = $this->doAction( $actionParams );

		// Check that we stored the edit and then hit/missed the cache
		if ( !in_array( 'store', $loggerCalls, true ) ) {
			$this->fail( 'Did not store the edit in cache as expected for a stashed edit.' );
		} elseif ( !in_array( $type, $loggerCalls, true ) ) {
			$this->fail( "Did not $type the cache as expected for a stashed edit." );
		}

		[ $expected, $actual ] = $this->checkConsequences( $result, $actionParams, $consequences );

		$this->assertEquals(
			$expected,
			$actual,
			'The error messages obtained by performing the action do not match.'
		);

		$this->assertAbuseLog( $actionParams, $consequences );
	}

	/**
	 * Data provider for testStashedEdit
	 *
	 * @return array
	 */
	public static function provideStashedEdits() {
		// XXX Need to hardcode the username of $this->user here.
		$username = 'UTSysop';
		$sets = [
			[
				[ 1, 2 ],
				[
					'target' => 'Test page',
					'oldText' => 'Some old text for the test.',
					'newText' => 'I like foo',
					'summary' => 'Test AbuseFilter for edit action.'
				],
				[ 'warn'  => [ 1 ] ]
			],
			[
				[ 5 ],
				[
					'target' => "User:$username",
					'oldText' => 'Hey.',
					'newText' => 'I am a very nice user, really!',
					'summary' => ''
				],
				[ 'tag' => [ 5 ] ]
			],
			[
				[ 6 ],
				[
					'target' => 'Help:Help',
					'oldText' => 'Some help.',
					'newText' => 'Some help for you',
					'summary' => 'Help! I need somebody'
				],
				[ 'disallow' => [ 6 ] ]
			],
			[
				[ 2, 3, 7, 8 ],
				[
					'target' => 'Link',
					'oldText' => 'What is a link?',
					'newText' => 'A link is something like this: [[Link|]].',
					'summary' => 'Explaining'
				],
				[ 'degroup' => [ 7 ], 'block' => [ 8 ] ]
			],
			[
				[ 8, 9 ],
				[
					'target' => 'Whatever',
					'oldText' => 'Whatever is whatever',
					'newText' => 'Whatever is whatever, whatever it is. BTW, here is a [[Link|]]',
					'summary' => 'Whatever'
				],
				[ 'disallow' => [ 8 ], 'block' => [ 8 ] ]
			],
			[
				[ 10 ],
				[
					'target' => 'Buffalo',
					'oldText' => 'Buffalo',
					'newText' => 'Buffalo buffalo Buffalo buffalo buffalo buffalo Buffalo buffalo.',
					'summary' => 'Buffalo!'
				],
				[ 'tag' => [ 10 ] ]
			],
			[
				[ 1, 3, 7, 12 ],
				[
					'target' => "User:$username",
					'oldText' => '',
					'newText' => 'A couple of lines about me...',
					'summary' => 'My user page'
				],
				[ 'block' => [ 12 ], 'degroup' => [ 7, 12 ] ]
			],
			[
				[ 10, 13 ],
				[
					'target' => 'Tyger! Tyger! Burning bright',
					'oldText' => 'In the forests of the night',
					'newText' => 'What immortal hand or eye',
					'summary' => 'Could frame thy fearful symmetry?'
				],
				[ 'tag' => [ 10 ] ]
			],
			[
				[ 14 ],
				[
					'target' => '0',
					'oldText' => 'Old text',
					'newText' => 'New text',
					'summary' => 'Some summary'
				],
				[]
			],
		];

		$finalSets = [];
		foreach ( $sets as $set ) {
			// Test both successfully saving a stashed edit and stashing the edit but re-executing filters
			$finalSets[] = array_merge( [ 'miss' ], $set );
			$finalSets[] = array_merge( [ 'hit' ], $set );
		}
		return $finalSets;
	}

	/**
	 * Tests for global filters, defined on a central wiki and executed on another (e.g. a filter
	 * defined on meta but triggered on another wiki using meta's global filters).
	 * For simplicity, this test creates filters in the local database but makes the extension believe that it's
	 * actually external.
	 *
	 * @param int[] $createIds IDs of the filters to create
	 * @param array $actionParams Details of the action we need to execute to trigger filters
	 * @param array $consequences The consequences we're expecting
	 * @dataProvider provideGlobalFilters
	 * @covers \MediaWiki\Extension\AbuseFilter\AbuseLogger
	 */
	public function testGlobalFilters( $createIds, $actionParams, $consequences ) {
		$this->setMwGlobals( [
			'wgAbuseFilterCentralDB' => WikiMap::getCurrentWikiId(),
			'wgAbuseFilterIsCentral' => false,
		] );
		$this->createFilters( $createIds );

		AbuseFilterServices::getFilterLookup( $this->getServiceContainer() )->hideLocalFiltersForTesting();
		$result = $this->doAction( $actionParams );

		[ $expected, $actual ] = $this->checkConsequences( $result, $actionParams, $consequences );

		// First check that the filters work as expected
		$this->assertEquals(
			$expected,
			$actual,
			'The error messages obtained by performing the action do not match.'
		);

		// Check that the hits were logged on the "external" DB
		$loggedFilters = $this->getDb()->selectFieldValues(
			'abuse_filter_log',
			'afl_filter_id',
			[ 'afl_wiki IS NOT NULL' ],
			__METHOD__
		);

		// Use assertEquals because selectFieldValues returns an array of strings
		$this->assertEquals(
			$createIds,
			$loggedFilters,
			'Some filter hits were not logged in the external DB.'
		);
	}

	/**
	 * Data provider for testGlobalFilters
	 *
	 * @return array
	 */
	public static function provideGlobalFilters() {
		return [
			[
				[ 18 ],
				[
					'action' => 'edit',
					'target' => 'Global',
					'oldText' => 'Old text',
					'newText' => 'New text',
					'summary' => ''
				],
				[ 'warn' => [ 18 ] ]
			],
			[
				[ 19 ],
				[
					'action' => 'edit',
					'target' => 'A global page',
					'oldText' => 'Foo',
					'newText' => 'Bar',
					'summary' => 'Baz'
				],
				[ 'tag' => [ 19 ] ]
			],
			[
				[ 18 ],
				[
					'action' => 'move',
					'target' => 'Cellar door',
					'newTitle' => 'Attic door'
				],
				[ 'warn' => [ 18 ] ]
			],
			[
				[ 19, 20 ],
				[
					'action' => 'delete',
					'target' => 'Cellar door',
				],
				[ 'disallow' => [ 20 ] ]
			],
			[
				[ 19 ],
				[
					'action' => 'stashedit',
					'target' => 'Cellar door',
					'oldText' => '',
					'newText' => 'Too many doors',
					'summary' => '',
					'stashType' => 'hit'
				],
				[ 'tag' => [ 19 ] ]
			],
			[
				[ 18 ],
				[
					'action' => 'createaccount',
					'target' => 'User:AbuseFilterGlobalUser',
					'username' => 'AbuseFilterGlobalUser'
				],
				[ 'warn' => [ 18 ] ]
			]
		];
	}

	/**
	 * Make sure that after an edit is saved, if a filter was hit the afl_rev_id is updated
	 * to reflect the new edit
	 *
	 * Regression tests for T286140
	 */
	public function testRevIdSet() {
		// Filter 24 has no actions and always matches
		$this->createFilters( [ 24 ] );

		$targetTitle = Title::makeTitle( NS_MAIN, 'TestRevIdSet' );
		$startingRevId = $targetTitle->getLatestRevID( IDBAccessObject::READ_LATEST );

		$this->doEdit( $targetTitle, 'Old text', 'New text', 'Summary' );
		$latestRevId = $targetTitle->getLatestRevID( IDBAccessObject::READ_LATEST );

		$this->assertNotSame(
			$startingRevId,
			$latestRevId,
			'Edit should have been properly saved'
		);

		// Check the database for the filter hit
		// We don't have an easy way to retrieve the afl_id for this relevant hit,
		// so instead find the latest row for this filter
		$filterHit = $this->getDb()->selectRow(
			'abuse_filter_log',
			'*',
			[ 'afl_filter_id' => 24 ],
			__METHOD__,
			[ 'ORDER BY' => 'afl_id DESC' ]
		);

		// Helpful for debugging
		$filterHitStr = FormatJson::encode( $filterHit );
		$this->assertSame(
			$latestRevId,
			(int)( $filterHit->afl_rev_id ),
			"AbuseLog entry updated with the revision id (full filter hit: $filterHitStr)"
		);
	}

	/**
	 * Test a null edit.
	 * @covers \MediaWiki\Extension\AbuseFilter\VariableGenerator\RunVariableGenerator
	 */
	public function testNullEdit() {
		$this->setService(
			FilterRunnerFactory::SERVICE_NAME,
			$this->createNoOpMock( FilterRunnerFactory::class )
		);
		// Filter 24 has no actions and always matches
		$this->createFilters( [ 24 ] );

		$targetTitle = Title::makeTitle( NS_MAIN, 'TestNullEdit' );
		$text = 'Some text';
		$status = $this->doEdit( $targetTitle, $text, $text, 'Summary' );
		$this->assertStatusGood( $status );
	}

	/**
	 * Test a content model change.
	 * @covers \MediaWiki\Extension\AbuseFilter\VariableGenerator\RunVariableGenerator
	 */
	public function testContentModelChange() {
		// Filter 23 always matches and disables
		$this->createFilters( [ 23 ] );

		$targetTitle = Title::makeTitle( NS_MAIN, 'TestContentModelChange' );

		$text = FormatJson::encode( [ 'key' => 'value' ] );
		$oldContent = new JsonContent( $text );
		$newContent = new WikitextContent( $text );

		$status = $this->doEdit( $targetTitle, $oldContent, $newContent, 'Summary' );
		$this->assertStatusNotOK( $status );
	}
}
