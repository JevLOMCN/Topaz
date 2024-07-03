<?php
/**
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
 * https://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */
namespace MediaWiki\Extension\ReplaceText;

use ErrorPageError;
use JobQueueGroup;
use Language;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Page\MovePageFactory;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Storage\NameTableStore;
use MediaWiki\Title\NamespaceInfo;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\UserFactory;
use MediaWiki\Watchlist\WatchlistManager;
use OOUI;
use PermissionsError;
use SearchEngineConfig;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\ReadOnlyMode;

class SpecialReplaceText extends SpecialPage {
	private $target;
	private $targetString;
	private $replacement;
	private $use_regex;
	private $category;
	private $prefix;
	private $pageLimit;
	private $edit_pages;
	private $move_pages;
	private $selected_namespaces;
	private $botEdit;

	/** @var HookHelper */
	private $hookHelper;

	/** @var IConnectionProvider */
	private $dbProvider;

	/** @var Language */
	private $contentLanguage;

	/** @var JobQueueGroup */
	private $jobQueueGroup;

	/** @var LinkRenderer */
	private $linkRenderer;

	/** @var MovePageFactory */
	private $movePageFactory;

	/** @var NamespaceInfo */
	private $namespaceInfo;

	/** @var PermissionManager */
	private $permissionManager;

	/** @var ReadOnlyMode */
	private $readOnlyMode;

	/** @var SearchEngineConfig */
	private $searchEngineConfig;

	/** @var NameTableStore */
	private $slotRoleStore;

	/** @var UserFactory */
	private $userFactory;

	/** @var UserOptionsLookup */
	private $userOptionsLookup;

	private WatchlistManager $watchlistManager;
	private WikiPageFactory $wikiPageFactory;

	/** @var Search */
	private $search;

	/**
	 * @param HookContainer $hookContainer
	 * @param IConnectionProvider $dbProvider
	 * @param Language $contentLanguage
	 * @param JobQueueGroup $jobQueueGroup
	 * @param LinkRenderer $linkRenderer
	 * @param MovePageFactory $movePageFactory
	 * @param NamespaceInfo $namespaceInfo
	 * @param PermissionManager $permissionManager
	 * @param ReadOnlyMode $readOnlyMode
	 * @param SearchEngineConfig $searchEngineConfig
	 * @param NameTableStore $slotRoleStore
	 * @param UserFactory $userFactory
	 * @param UserOptionsLookup $userOptionsLookup
	 * @param WatchlistManager $watchlistManager
	 * @param WikiPageFactory $wikiPageFactory
	 */
	public function __construct(
		HookContainer $hookContainer,
		IConnectionProvider $dbProvider,
		Language $contentLanguage,
		JobQueueGroup $jobQueueGroup,
		LinkRenderer $linkRenderer,
		MovePageFactory $movePageFactory,
		NamespaceInfo $namespaceInfo,
		PermissionManager $permissionManager,
		ReadOnlyMode $readOnlyMode,
		SearchEngineConfig $searchEngineConfig,
		NameTableStore $slotRoleStore,
		UserFactory $userFactory,
		UserOptionsLookup $userOptionsLookup,
		WatchlistManager $watchlistManager,
		WikiPageFactory $wikiPageFactory
	) {
		parent::__construct( 'ReplaceText', 'replacetext' );
		$this->hookHelper = new HookHelper( $hookContainer );
		$this->dbProvider = $dbProvider;
		$this->contentLanguage = $contentLanguage;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->linkRenderer = $linkRenderer;
		$this->movePageFactory = $movePageFactory;
		$this->namespaceInfo = $namespaceInfo;
		$this->permissionManager = $permissionManager;
		$this->readOnlyMode = $readOnlyMode;
		$this->searchEngineConfig = $searchEngineConfig;
		$this->slotRoleStore = $slotRoleStore;
		$this->userFactory = $userFactory;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->watchlistManager = $watchlistManager;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->search = new Search(
			$this->getConfig(),
			$dbProvider
		);
	}

	/**
	 * @inheritDoc
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * @param null|string $query
	 */
	function execute( $query ) {
		if ( !$this->getUser()->isAllowed( 'replacetext' ) ) {
			throw new PermissionsError( 'replacetext' );
		}

		// Replace Text can't be run with certain settings, due to the
		// changes they make to the DB storage setup.
		if ( $this->getConfig()->get( 'CompressRevisions' ) ) {
			throw new ErrorPageError( 'replacetext_cfg_error', 'replacetext_no_compress' );
		}
		if ( $this->getConfig()->get( 'ExternalStores' ) ) {
			throw new ErrorPageError( 'replacetext_cfg_error', 'replacetext_no_external_stores' );
		}

		$out = $this->getOutput();

		if ( $this->readOnlyMode->isReadOnly() ) {
			$permissionErrors = [ [ 'readonlytext', [ $this->readOnlyMode->getReason() ] ] ];
			$out->setPageTitle( $this->msg( 'badaccess' )->text() );
			$out->addWikiTextAsInterface( $out->formatPermissionsErrorMessage( $permissionErrors, 'replacetext' ) );
			return;
		}

		$out->enableOOUI();
		$this->setHeaders();
		$this->doSpecialReplaceText();
	}

	/**
	 * @return array namespaces selected for search
	 */
	function getSelectedNamespaces() {
		$all_namespaces = $this->searchEngineConfig->searchableNamespaces();
		$selected_namespaces = [];
		foreach ( $all_namespaces as $ns => $name ) {
			if ( $this->getRequest()->getCheck( 'ns' . $ns ) ) {
				$selected_namespaces[] = $ns;
			}
		}
		return $selected_namespaces;
	}

	/**
	 * Do the actual display and logic of Special:ReplaceText.
	 */
	function doSpecialReplaceText() {
		$out = $this->getOutput();
		$request = $this->getRequest();

		$this->target = $request->getText( 'target' );
		$this->targetString = str_replace( "\n", "\u{21B5}", $this->target );
		$this->replacement = $request->getText( 'replacement' );
		$this->use_regex = $request->getBool( 'use_regex' );
		$this->category = $request->getText( 'category' );
		$this->prefix = $request->getText( 'prefix' );
		$this->pageLimit = $request->getText( 'pageLimit' );
		$this->edit_pages = $request->getBool( 'edit_pages' );
		$this->move_pages = $request->getBool( 'move_pages' );
		$this->botEdit = $request->getBool( 'botEdit' );
		$this->selected_namespaces = $this->getSelectedNamespaces();

		if ( $request->getCheck( 'continue' ) && $this->target === '' ) {
			$this->showForm( 'replacetext_givetarget' );
			return;
		}

		if ( $request->getCheck( 'continue' ) && $this->pageLimit === '' ) {
			$this->pageLimit = $this->getConfig()->get( 'ReplaceTextResultsLimit' );
		} else {
			$this->pageLimit = (int)$this->pageLimit;
		}

		if ( $request->getCheck( 'replace' ) ) {

			// check for CSRF
			if ( !$this->checkToken() ) {
				$out->addWikiMsg( 'sessionfailure' );
				return;
			}

			$jobs = $this->createJobsForTextReplacements();
			$this->jobQueueGroup->push( $jobs );

			$count = $this->getLanguage()->formatNum( count( $jobs ) );
			$out->addWikiMsg(
				'replacetext_success',
				"<code><nowiki>{$this->targetString}</nowiki></code>",
				"<code><nowiki>{$this->replacement}</nowiki></code>",
				$count
			);
			// Link back
			$out->addHTML(
				$this->linkRenderer->makeLink(
					$this->getPageTitle(),
					$this->msg( 'replacetext_return' )->text()
				)
			);
			return;
		}

		if ( $request->getCheck( 'target' ) ) {
			// check for CSRF
			if ( !$this->checkToken() ) {
				$out->addWikiMsg( 'sessionfailure' );
				return;
			}

			// first, check that at least one namespace has been
			// picked, and that either editing or moving pages
			// has been selected
			if ( count( $this->selected_namespaces ) == 0 ) {
				$this->showForm( 'replacetext_nonamespace' );
				return;
			}
			if ( !$this->edit_pages && !$this->move_pages ) {
				$this->showForm( 'replacetext_editormove' );
				return;
			}

			// If user is replacing text within pages...
			$titles_for_edit = $titles_for_move = $unmoveable_titles = $uneditable_titles = [];
			if ( $this->edit_pages ) {
				[ $titles_for_edit, $uneditable_titles ] = $this->getTitlesForEditingWithContext();
			}
			if ( $this->move_pages ) {
				[ $titles_for_move, $unmoveable_titles ] = $this->getTitlesForMoveAndUnmoveableTitles();
			}

			// If no results were found, check to see if a bad
			// category name was entered.
			if ( count( $titles_for_edit ) == 0 && count( $titles_for_move ) == 0 ) {
				$category_title_exists = true;

				if ( $this->category ) {
					$category_title = Title::makeTitleSafe( NS_CATEGORY, $this->category );
					if ( !$category_title->exists() ) {
						$category_title_exists = false;
						$link = $this->linkRenderer->makeLink(
							$category_title,
							ucfirst( $this->category )
						);
						$out->addHTML(
							$this->msg( 'replacetext_nosuchcategory' )->rawParams( $link )->escaped()
						);
					}
				}

				if ( $this->edit_pages && $category_title_exists ) {
					$out->addWikiMsg(
						'replacetext_noreplacement',
						"<code><nowiki>{$this->targetString}</nowiki></code>"
					);
				}

				if ( $this->move_pages && $category_title_exists ) {
					$out->addWikiMsg( 'replacetext_nomove', "<code><nowiki>{$this->targetString}</nowiki></code>" );
				}
				// link back to starting form
				$out->addHTML(
					'<p>' .
					$this->linkRenderer->makeLink(
						$this->getPageTitle(),
						$this->msg( 'replacetext_return' )->text()
					)
					. '</p>'
				);
			} else {
				$warning_msg = $this->getAnyWarningMessageBeforeReplace( $titles_for_edit, $titles_for_move );
				if ( $warning_msg !== null ) {
					$warningLabel = new OOUI\LabelWidget( [
						'label' => new OOUI\HtmlSnippet( $warning_msg )
					] );
					$warning = new OOUI\MessageWidget( [
						'type' => 'warning',
						'label' => $warningLabel
					] );
					$out->addHTML( $warning );
				}

				$this->pageListForm( $titles_for_edit, $titles_for_move, $uneditable_titles, $unmoveable_titles );
			}
			return;
		}

		// If we're still here, show the starting form.
		$this->showForm();
	}

	/**
	 * Returns the set of MediaWiki jobs that will do all the actual replacements.
	 *
	 * @return array jobs
	 */
	function createJobsForTextReplacements() {
		$replacement_params = [
			'user_id' => $this->getReplaceTextUser()->getId(),
			'target_str' => $this->target,
			'replacement_str' => $this->replacement,
			'use_regex' => $this->use_regex,
			'create_redirect' => false,
			'watch_page' => false,
			'botEdit' => $this->botEdit
		];
		$replacement_params['edit_summary'] = $this->msg(
			'replacetext_editsummary',
			$this->targetString, $this->replacement
		)->inContentLanguage()->plain();

		$request = $this->getRequest();
		foreach ( $request->getValues() as $key => $value ) {
			if ( $key == 'create-redirect' && $value == '1' ) {
				$replacement_params['create_redirect'] = true;
			} elseif ( $key == 'watch-pages' && $value == '1' ) {
				$replacement_params['watch_page'] = true;
			}
		}

		$jobs = [];
		$pages_to_edit = [];
		// These are OOUI checkboxes - we don't determine whether they
		// were checked by their value (which will be null), but rather
		// by whether they were submitted at all.
		foreach ( $request->getValues() as $key => $value ) {
			if ( $key === 'replace' || $key === 'use_regex' ) {
				continue;
			}
			if ( strpos( $key, 'move-' ) !== false ) {
				$title = Title::newFromID( (int)substr( $key, 5 ) );
				$replacement_params['move_page'] = true;
				if ( $title !== null ) {
					$jobs[] = new Job( $title, $replacement_params,
						$this->movePageFactory,
						$this->permissionManager,
						$this->userFactory,
						$this->watchlistManager,
						$this->wikiPageFactory
					);
				}
				unset( $replacement_params['move_page'] );
			} elseif ( strpos( $key, '|' ) !== false ) {
				// Bundle multiple edits to the same page for a different slot into one job
				[ $page_id, $role ] = explode( '|', $key, 2 );
				$pages_to_edit[$page_id][] = $role;
			}
		}
		// Create jobs for the bundled page edits
		foreach ( $pages_to_edit as $page_id => $roles ) {
			$title = Title::newFromID( (int)$page_id );
			$replacement_params['roles'] = $roles;
			if ( $title !== null ) {
				$jobs[] = new Job( $title, $replacement_params,
					$this->movePageFactory,
					$this->permissionManager,
					$this->userFactory,
					$this->watchlistManager,
					$this->wikiPageFactory
				);
			}
			unset( $replacement_params['roles'] );
		}

		return $jobs;
	}

	/**
	 * Returns the set of Titles whose contents would be modified by this
	 * replacement, along with the "search context" string for each one.
	 *
	 * @return array The set of Titles and their search context strings
	 */
	function getTitlesForEditingWithContext() {
		$titles_for_edit = [];

		$res = $this->search->doSearchQuery(
			$this->target,
			$this->selected_namespaces,
			$this->category,
			$this->prefix,
			$this->pageLimit,
			$this->use_regex
		);

		$titles_to_process = $this->hookHelper->filterPageTitlesForEdit( $res );
		$titles_to_skip = [];

		foreach ( $res as $row ) {
			$title = Title::makeTitleSafe( $row->page_namespace, $row->page_title );
			if ( $title == null ) {
				continue;
			}

			if ( !isset( $titles_to_process[ $title->getPrefixedText() ] ) ) {
				// Title has been filtered out by the hook: ReplaceTextFilterPageTitlesForEdit
				$titles_to_skip[] = $title;
				continue;
			}

			// @phan-suppress-next-line SecurityCheck-ReDoS target could be a regex from user
			$context = $this->extractContext( $row->old_text, $this->target, $this->use_regex );
			$role = $this->extractRole( (int)$row->slot_role_id );
			$titles_for_edit[] = [ $title, $context, $role ];
		}

		return [ $titles_for_edit, $titles_to_skip ];
	}

	/**
	 * Returns two lists: the set of titles that would be moved/renamed by
	 * the current text replacement, and the set of titles that would
	 * ordinarily be moved but are not moveable, due to permissions or any
	 * other reason.
	 *
	 * @return array
	 */
	function getTitlesForMoveAndUnmoveableTitles() {
		$titles_for_move = [];
		$unmoveable_titles = [];

		$res = $this->search->getMatchingTitles(
			$this->target,
			$this->selected_namespaces,
			$this->category,
			$this->prefix,
			$this->pageLimit,
			$this->use_regex
		);

		$titles_to_process = $this->hookHelper->filterPageTitlesForRename( $res );

		foreach ( $res as $row ) {
			$title = Title::makeTitleSafe( $row->page_namespace, $row->page_title );
			if ( !$title ) {
				continue;
			}

			if ( !isset( $titles_to_process[ $title->getPrefixedText() ] ) ) {
				$unmoveable_titles[] = $title;
				continue;
			}

			$new_title = Search::getReplacedTitle(
				$title,
				$this->target,
				$this->replacement,
				$this->use_regex
			);
			if ( !$new_title ) {
				// New title is not valid because it contains invalid characters.
				$unmoveable_titles[] = $title;
				continue;
			}

			$mvPage = $this->movePageFactory->newMovePage( $title, $new_title );
			$moveStatus = $mvPage->isValidMove();
			$permissionStatus = $mvPage->checkPermissions( $this->getUser(), null );

			if ( $permissionStatus->isOK() && $moveStatus->isOK() ) {
				$titles_for_move[] = $title;
			} else {
				$unmoveable_titles[] = $title;
			}
		}

		return [ $titles_for_move, $unmoveable_titles ];
	}

	/**
	 * Get the warning message if the replacement string is either blank
	 * or found elsewhere on the wiki (since undoing the replacement
	 * would be difficult in either case).
	 *
	 * @param array $titles_for_edit
	 * @param array $titles_for_move
	 * @return string|null Warning message, if any
	 */
	function getAnyWarningMessageBeforeReplace( $titles_for_edit, $titles_for_move ) {
		if ( $this->replacement === '' ) {
			return $this->msg( 'replacetext_blankwarning' )->parse();
		} elseif ( $this->use_regex ) {
			// If it's a regex, don't bother checking for existing
			// pages - if the replacement string includes wildcards,
			// it's a meaningless check.
			return null;
		} elseif ( count( $titles_for_edit ) > 0 ) {
			$res = $this->search->doSearchQuery(
				$this->replacement,
				$this->selected_namespaces,
				$this->category,
				$this->prefix,
				$this->pageLimit,
				$this->use_regex
			);
			$titles = $this->hookHelper->filterPageTitlesForEdit( $res );
			$count = count( $titles );
			if ( $count > 0 ) {
				return $this->msg( 'replacetext_warning' )->numParams( $count )
					->params( "<code><nowiki>{$this->replacement}</nowiki></code>" )->parse();
			}
		} elseif ( count( $titles_for_move ) > 0 ) {
			$res = $this->search->getMatchingTitles(
				$this->replacement,
				$this->selected_namespaces,
				$this->category,
				$this->prefix,
				$this->pageLimit,
				$this->use_regex
			);
			$titles = $this->hookHelper->filterPageTitlesForRename( $res );
			$count = count( $titles );
			if ( $count > 0 ) {
				return $this->msg( 'replacetext_warning' )->numParams( $count )
					->params( $this->replacement )->parse();
			}
		}

		return null;
	}

	/**
	 * @param string|null $warning_msg Message to be shown at top of form
	 */
	function showForm( $warning_msg = null ) {
		$out = $this->getOutput();

		$out->addHTML(
			Html::openElement(
				'form',
				[
					'id' => 'powersearch',
					'action' => $this->getPageTitle()->getLocalURL(),
					'method' => 'post'
				]
			) . "\n" .
			Html::hidden( 'title', $this->getPageTitle()->getPrefixedText() ) .
			Html::hidden( 'continue', 1 ) .
			Html::hidden( 'token', $this->getToken() )
		);
		if ( $warning_msg === null ) {
			$out->addWikiMsg( 'replacetext_docu' );
		} else {
			$out->wrapWikiMsg(
				"<div class=\"errorbox\">\n$1\n</div><br clear=\"both\" />",
				$warning_msg
			);
		}

		$out->addHTML( '<table><tr><td style="vertical-align: top;">' );
		$out->addWikiMsg( 'replacetext_originaltext' );
		$out->addHTML( '</td><td>' );
		// 'width: auto' style is needed to override MediaWiki's
		// normal 'width: 100%', which causes the textarea to get
		// zero width in IE
		$out->addHTML( Html::textarea( 'target', $this->target,
			[ 'cols' => 100, 'rows' => 5, 'style' => 'width: auto;' ]
		) );
		$out->addHTML( '</td></tr><tr><td style="vertical-align: top;">' );
		$out->addWikiMsg( 'replacetext_replacementtext' );
		$out->addHTML( '</td><td>' );
		$out->addHTML( Html::textarea( 'replacement', $this->replacement,
			[ 'cols' => 100, 'rows' => 5, 'style' => 'width: auto;' ]
		) );
		$out->addHTML( '</td></tr></table>' );

		// SQLite unfortunately lack a REGEXP
		// function or operator by default, so disable regex(p)
		// searches that DB type.
		$dbr = $this->dbProvider->getReplicaDatabase();
		if ( $dbr->getType() !== 'sqlite' ) {
			$out->addHTML( Html::rawElement( 'p', [],
					Html::rawElement( 'label', [],
						Html::input( 'use_regex', '1', 'checkbox' )
						. ' ' . $this->msg( 'replacetext_useregex' )->escaped(),
					)
				) . "\n" .
				Html::element( 'p',
					[ 'style' => 'font-style: italic' ],
					$this->msg( 'replacetext_regexdocu' )->text()
				)
			);
		}

		// The interface is heavily based on the one in Special:Search.
		$namespaces = $this->searchEngineConfig->searchableNamespaces();
		$tables = $this->namespaceTables( $namespaces );
		$out->addHTML(
			"<div class=\"mw-search-formheader\"></div>\n" .
			"<fieldset class=\"ext-replacetext-searchoptions\">\n" .
			Html::element( 'h4', [], $this->msg( 'powersearch-ns' )->text() )
		);
		// The ability to select/unselect groups of namespaces in the
		// search interface exists only in some skins, like Vector -
		// check for the presence of the 'powersearch-togglelabel'
		// message to see if we can use this functionality here.
		if ( $this->msg( 'powersearch-togglelabel' )->isDisabled() ) {
			// do nothing
		} else {
			$out->addHTML(
				Html::rawElement(
					'div',
					[ 'class' => 'ext-replacetext-search-togglebox' ],
					Html::element( 'label', [],
						$this->msg( 'powersearch-togglelabel' )->text()
					) .
					Html::element( 'input', [
						'id' => 'mw-search-toggleall',
						'type' => 'button',
						'value' => $this->msg( 'powersearch-toggleall' )->text(),
					] ) .
					Html::element( 'input', [
						'id' => 'mw-search-togglenone',
						'type' => 'button',
						'value' => $this->msg( 'powersearch-togglenone' )->text()
					] )
				)
			);
		}
		$out->addHTML(
			Html::element( 'div', [ 'class' => 'ext-replacetext-divider' ] ) .
			"$tables\n</fieldset>"
		);
		$category_search_label = $this->msg( 'replacetext_categorysearch' )->escaped();
		$prefix_search_label = $this->msg( 'replacetext_prefixsearch' )->escaped();
		$page_limit_label = $this->msg( 'replacetext_pagelimit' )->escaped();
		$this->pageLimit = $this->pageLimit === 0
			? $this->getConfig()->get( 'ReplaceTextResultsLimit' )
			: $this->pageLimit;
		$out->addHTML(
			"<fieldset class=\"ext-replacetext-searchoptions\">\n" .
			Html::element( 'h4', [], $this->msg( 'replacetext_optionalfilters' )->text() ) .
			Html::element( 'div', [ 'class' => 'ext-replacetext-divider' ] ) .
			"<p>$category_search_label\n" .
			Html::element( 'input', [ 'name' => 'category', 'size' => 20, 'value' => $this->category ] ) . '</p>' .
			"<p>$prefix_search_label\n" .
			Html::element( 'input', [ 'name' => 'prefix', 'size' => 20, 'value' => $this->prefix ] ) . '</p>' .
			"<p>$page_limit_label\n" .
			Html::element( 'input', [ 'name' => 'pageLimit', 'size' => 20, 'value' => (string)$this->pageLimit,
				'type' => 'number', 'min' => 0 ] ) . "</p></fieldset>\n" .
			"<p>\n" .
			Html::rawElement( 'label', [],
				Html::input( 'edit_pages', '1', 'checkbox', [ 'checked' => true ] )
				. ' ' . $this->msg( 'replacetext_editpages' )->escaped()
			) . '<br />' .
			Html::rawElement( 'label', [],
				Html::input( 'move_pages', '1', 'checkbox' )
				. ' ' . $this->msg( 'replacetext_movepages' )->escaped()
			)
		);

		// If the user is a bot, don't even show the "Mark changes as bot edits" checkbox -
		// presumably a bot user should never be allowed to make non-bot edits.
		if ( !$this->permissionManager->userHasRight( $this->getReplaceTextUser(), 'bot' ) ) {
			$out->addHTML(
				'<br />' .
				Html::rawElement( 'label', [],
					Html::input( 'botEdit', '1', 'checkbox' ) . ' ' . $this->msg( 'replacetext_botedit' )->escaped()
				)
			);
		}
		$continueButton = new OOUI\ButtonInputWidget( [
			'type' => 'submit',
			'label' => $this->msg( 'replacetext_continue' )->text(),
			'flags' => [ 'primary', 'progressive' ]
		] );
		$out->addHTML(
			"</p>\n" .
			$continueButton .
			Html::closeElement( 'form' )
		);
		$out->addModuleStyles( 'ext.ReplaceTextStyles' );
		$out->addModules( 'ext.ReplaceText' );
	}

	/**
	 * This function is not currently used, but it may get used in the
	 * future if the "1st screen" interface changes to use OOUI.
	 *
	 * @param string $label
	 * @param string $name
	 * @param bool $selected
	 * @return string HTML
	 */
	function checkLabel( $label, $name, $selected = false ) {
		$checkbox = new OOUI\CheckboxInputWidget( [
			'name' => $name,
			'value' => 1,
			'selected' => $selected
		] );
		$layout = new OOUI\FieldLayout( $checkbox, [
			'align' => 'inline',
			'label' => $label
		] );
		return $layout;
	}

	/**
	 * Copied almost exactly from MediaWiki's SpecialSearch class, i.e.
	 * the search page
	 * @param string[] $namespaces
	 * @param int $rowsPerTable
	 * @return string HTML
	 */
	function namespaceTables( $namespaces, $rowsPerTable = 3 ) {
		// Group namespaces into rows according to subject.
		// Try not to make too many assumptions about namespace numbering.
		$rows = [];
		$tables = '';
		foreach ( $namespaces as $ns => $name ) {
			$subj = $this->namespaceInfo->getSubject( $ns );
			if ( !array_key_exists( $subj, $rows ) ) {
				$rows[$subj] = '';
			}
			$name = str_replace( '_', ' ', $name );
			if ( $name == '' ) {
				$name = $this->msg( 'blanknamespace' )->text();
			}
			$id = "mw-search-ns{$ns}";
			$rows[$subj] .= Html::openElement( 'td', [ 'style' => 'white-space: nowrap' ] ) .
				Html::input( "ns{$ns}", '1', 'checkbox', [ 'id' => $id, 'checked' => in_array( $ns, $namespaces ) ] ) .
				' ' . Html::label( $name, $id ) .
				Html::closeElement( 'td' ) . "\n";
		}
		$rows = array_values( $rows );
		$numRows = count( $rows );
		// Lay out namespaces in multiple floating two-column tables so they'll
		// be arranged nicely while still accommodating different screen widths
		// Build the final HTML table...
		for ( $i = 0; $i < $numRows; $i += $rowsPerTable ) {
			$tables .= Html::openElement( 'table' );
			for ( $j = $i; $j < $i + $rowsPerTable && $j < $numRows; $j++ ) {
				$tables .= "<tr>\n" . $rows[$j] . "</tr>";
			}
			$tables .= Html::closeElement( 'table' ) . "\n";
		}
		return $tables;
	}

	/**
	 * @param array $titles_for_edit
	 * @param array $titles_for_move
	 * @param array $uneditable_titles
	 * @param array $unmoveable_titles
	 */
	function pageListForm( $titles_for_edit, $titles_for_move, $uneditable_titles, $unmoveable_titles ) {
		$out = $this->getOutput();

		$formOpts = [
			'id' => 'choose_pages',
			'method' => 'post',
			'action' => $this->getPageTitle()->getLocalURL()
		];
		$out->addHTML(
			Html::openElement( 'form', $formOpts ) . "\n" .
			Html::hidden( 'title', $this->getPageTitle()->getPrefixedText() ) .
			Html::hidden( 'target', $this->target ) .
			Html::hidden( 'replacement', $this->replacement ) .
			Html::hidden( 'use_regex', $this->use_regex ) .
			Html::hidden( 'move_pages', $this->move_pages ) .
			Html::hidden( 'edit_pages', $this->edit_pages ) .
			Html::hidden( 'botEdit', $this->botEdit ) .
			Html::hidden( 'replace', 1 ) .
			Html::hidden( 'token', $this->getToken() )
		);

		foreach ( $this->selected_namespaces as $ns ) {
			$out->addHTML( Html::hidden( 'ns' . $ns, 1 ) );
		}

		$out->addModules( 'ext.ReplaceText' );
		$out->addModuleStyles( 'ext.ReplaceTextStyles' );

		// Only show "invert selections" link if there are more than
		// five pages.
		if ( count( $titles_for_edit ) + count( $titles_for_move ) > 5 ) {
			$invertButton = new OOUI\ButtonWidget( [
				'label' => $this->msg( 'replacetext_invertselections' )->text(),
				'classes' => [ 'ext-replacetext-invert' ]
			] );
			$out->addHTML( $invertButton );
		}

		if ( count( $titles_for_edit ) > 0 ) {
			$out->addWikiMsg(
				'replacetext_choosepagesforedit',
				"<code><nowiki>{$this->targetString}</nowiki></code>",
				"<code><nowiki>{$this->replacement}</nowiki></code>",
				$this->getLanguage()->formatNum( count( $titles_for_edit ) )
			);

			foreach ( $titles_for_edit as $title_and_context ) {
				/**
				 * @var $title Title
				 */
				[ $title, $context, $role ] = $title_and_context;
				$checkbox = new OOUI\CheckboxInputWidget( [
					'name' => $title->getArticleID() . '|' . $role,
					'selected' => true
				] );
				if ( $role === SlotRecord::MAIN ) {
					$labelText = $this->linkRenderer->makeLink( $title ) .
						"<br /><small>$context</small>";
				} else {
					$labelText = $this->linkRenderer->makeLink( $title ) .
						" ($role) <br /><small>$context</small>";
				}
				$checkboxLabel = new OOUI\LabelWidget( [
					'label' => new OOUI\HtmlSnippet( $labelText )
				] );
				$layout = new OOUI\FieldLayout( $checkbox, [
					'align' => 'inline',
					'label' => $checkboxLabel
				] );
				$out->addHTML( $layout );
			}
			$out->addHTML( '<br />' );
		}

		if ( count( $titles_for_move ) > 0 ) {
			$out->addWikiMsg(
				'replacetext_choosepagesformove',
				$this->targetString,
				$this->replacement,
				$this->getLanguage()->formatNum( count( $titles_for_move ) )
			);
			foreach ( $titles_for_move as $title ) {
				$out->addHTML(
					Html::check( 'move-' . $title->getArticleID(), true ) . "\u{00A0}" .
					$this->linkRenderer->makeLink( $title ) . "<br />\n"
				);
			}
			$out->addHTML( '<br />' );
			$out->addWikiMsg( 'replacetext_formovedpages' );
			$out->addHTML(
				Html::rawElement( 'label', [],
					Html::input( 'create-redirect', '1', 'checkbox', [ 'checked' => true ] )
					. ' ' . $this->msg( 'replacetext_savemovedpages' )->escaped()
				) . "<br />\n" .
				Html::rawElement( 'label', [],
					Html::input( 'watch-pages', '1', 'checkbox' )
					. ' ' . $this->msg( 'replacetext_watchmovedpages' )->escaped()
				) . '<br />'
			);
			$out->addHTML( '<br />' );
		}

		$submitButton = new OOUI\ButtonInputWidget( [
			'type' => 'submit',
			'flags' => [ 'primary', 'progressive' ],
			'label' => $this->msg( 'replacetext_replace' )->text()
		] );
		$out->addHTML( $submitButton );

		$out->addHTML( '</form>' );

		if ( count( $uneditable_titles ) ) {
			$out->addWikiMsg(
				'replacetext_cannotedit',
				$this->getLanguage()->formatNum( count( $uneditable_titles ) )
			);
			$out->addHTML( $this->displayTitles( $uneditable_titles ) );
		}

		if ( count( $unmoveable_titles ) ) {
			$out->addWikiMsg(
				'replacetext_cannotmove',
				$this->getLanguage()->formatNum( count( $unmoveable_titles ) )
			);
			$out->addHTML( $this->displayTitles( $unmoveable_titles ) );
		}
	}

	/**
	 * Extract context and highlights search text
	 *
	 * @todo The bolding needs to be fixed for regular expressions.
	 * @param string $text
	 * @param string $target
	 * @param bool $use_regex
	 * @return string
	 */
	function extractContext( $text, $target, $use_regex = false ) {
		$cw = $this->userOptionsLookup->getOption( $this->getUser(), 'contextchars', 40 );

		// Get all indexes
		if ( $use_regex ) {
			$targetq = str_replace( "/", "\\/", $target );
			preg_match_all( "/$targetq/Uu", $text, $matches, PREG_OFFSET_CAPTURE );
		} else {
			$targetq = preg_quote( $target, '/' );
			preg_match_all( "/$targetq/", $text, $matches, PREG_OFFSET_CAPTURE );
		}

		$strLengths = [];
		$poss = [];
		$match = $matches[0] ?? [];
		foreach ( $match as $_ ) {
			$strLengths[] = strlen( $_[0] );
			$poss[] = $_[1];
		}

		$cuts = [];
		for ( $i = 0; $i < count( $poss ); $i++ ) {
			$index = $poss[$i];
			$len = $strLengths[$i];

			// Merge to the next if possible
			while ( isset( $poss[$i + 1] ) ) {
				if ( $poss[$i + 1] < $index + $len + $cw * 2 ) {
					$len += $poss[$i + 1] - $poss[$i];
					$i++;
				} else {
					// Can't merge, exit the inner loop
					break;
				}
			}
			$cuts[] = [ $index, $len ];
		}

		if ( $use_regex ) {
			$targetStr = "/$target/Uu";
		} else {
			$targetq = preg_quote( $this->convertWhiteSpaceToHTML( $target ), '/' );
			$targetStr = "/$targetq/i";
		}

		$context = '';
		foreach ( $cuts as $_ ) {
			[ $index, $len, ] = $_;
			$contextBefore = substr( $text, 0, $index );
			$contextAfter = substr( $text, $index + $len );

			$contextBefore = $this->getLanguage()->truncateForDatabase( $contextBefore, -$cw, '...', false );
			$contextAfter = $this->getLanguage()->truncateForDatabase( $contextAfter, $cw, '...', false );

			$context .= $this->convertWhiteSpaceToHTML( $contextBefore );
			$snippet = $this->convertWhiteSpaceToHTML( substr( $text, $index, $len ) );
			$context .= preg_replace( $targetStr, '<span class="ext-replacetext-searchmatch">\0</span>', $snippet );

			$context .= $this->convertWhiteSpaceToHTML( $contextAfter );
		}

		// Display newlines as "line break" characters.
		$context = str_replace( "\n", "\u{21B5}", $context );
		return $context;
	}

	/**
	 * Extracts the role name
	 *
	 * @param int $role_id
	 * @return string
	 */
	private function extractRole( $role_id ) {
		return $this->slotRoleStore->getName( $role_id );
	}

	private function convertWhiteSpaceToHTML( $message ) {
		$msg = htmlspecialchars( $message );
		$msg = preg_replace( '/^ /m', "\u{00A0} ", $msg );
		$msg = preg_replace( '/ $/m', " \u{00A0}", $msg );
		$msg = str_replace( '  ', "\u{00A0} ", $msg );
		# $msg = str_replace( "\n", '<br />', $msg );
		return $msg;
	}

	private function getReplaceTextUser() {
		$replaceTextUser = $this->getConfig()->get( 'ReplaceTextUser' );
		if ( $replaceTextUser !== null ) {
			return $this->userFactory->newFromName( $replaceTextUser );
		}

		return $this->getUser();
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'wiki';
	}

	private function displayTitles( array $titlesToDisplay ): string {
		$text = "<ul>\n";
		foreach ( $titlesToDisplay as $title ) {
			$text .= "<li>" . $this->linkRenderer->makeLink( $title ) . "</li>\n";
		}
		$text .= "</ul>\n";
		return $text;
	}

	private function getToken(): string {
		return $this->getContext()->getCsrfTokenSet()->getToken();
	}

	private function checkToken(): bool {
		return $this->getContext()->getCsrfTokenSet()->matchTokenField( 'token' );
	}
}
