<?php

namespace MediaWiki\Extension\Interwiki;

use HTMLForm;
use LogPage;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\WikiMap\WikiMap;
use PermissionsError;
use ReadOnlyError;

/**
 * Implements Special:Interwiki
 * @ingroup SpecialPage
 */
class SpecialInterwiki extends SpecialPage {
	/**
	 * Constructor - sets up the new special page
	 */
	public function __construct() {
		parent::__construct( 'Interwiki' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Different description will be shown on Special:SpecialPage depending on
	 * whether the user can modify the data.
	 *
	 * @return \Message
	 */
	public function getDescription() {
		return $this->msg( $this->canModify() ? 'interwiki' : 'interwiki-title-norights' );
	}

	public function getSubpagesForPrefixSearch() {
		// delete, edit both require the prefix parameter.
		return [ 'add' ];
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par parameter passed to the page or null
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$out = $this->getOutput();
		$request = $this->getRequest();

		$out->addModuleStyles( 'ext.interwiki.specialpage' );

		$action = $par ?: $request->getVal( 'action', $par );

		if ( !in_array( $action, [ 'add', 'edit', 'delete' ] ) || !$this->canModify( $out ) ) {
			$this->showList();
		} else {
			$this->showForm( $action );
		}
	}

	/**
	 * Returns boolean whether the user can modify the data.
	 * @param OutputPage|bool $out If $wgOut object given, it adds the respective error message.
	 * @return bool
	 * @throws PermissionsError|ReadOnlyError
	 */
	public function canModify( $out = false ) {
		if ( !$this->getUser()->isAllowed( 'interwiki' ) ) {
			// Check permissions
			if ( $out ) {
				throw new PermissionsError( 'interwiki' );
			}

			return false;
		} elseif ( $this->getConfig()->get( 'InterwikiCache' ) ) {
			// Editing the interwiki cache is not supported
			if ( $out ) {
				$out->addWikiMsg( 'interwiki-cached' );
			}

			return false;
		} else {
			$this->checkReadOnly();
		}

		return true;
	}

	/**
	 * @param string $action The action of the form
	 */
	protected function showForm( $action ) {
		$formDescriptor = [];
		$hiddenFields = [
			'action' => $action,
		];

		$status = Status::newGood();
		$request = $this->getRequest();
		$prefix = $request->getVal( 'prefix', $request->getVal( 'hiddenPrefix' ) );

		switch ( $action ) {
			case 'add':
			case 'edit':
				$formDescriptor = [
					'prefix' => [
						'type' => 'text',
						'label-message' => 'interwiki-prefix-label',
						'name' => 'prefix',
						'autofocus' => true,
					],

					'local' => [
						'type' => 'check',
						'id' => 'mw-interwiki-local',
						'label-message' => 'interwiki-local-label',
						'name' => 'local',
					],

					'trans' => [
						'type' => 'check',
						'id' => 'mw-interwiki-trans',
						'label-message' => 'interwiki-trans-label',
						'name' => 'trans',
					],

					'url' => [
						'type' => 'url',
						'id' => 'mw-interwiki-url',
						'label-message' => 'interwiki-url-label',
						'maxlength' => 200,
						'name' => 'wpInterwikiURL',
						'size' => 60,
					],

					'api' => [
						'type' => 'url',
						'id' => 'mw-interwiki-api',
						'label-message' => 'interwiki-api-label',
						'maxlength' => 200,
						'name' => 'wpInterwikiAPI',
						'size' => 60,
					],

					'reason' => [
						'type' => 'text',
						'id' => "mw-interwiki-{$action}reason",
						'label-message' => 'interwiki_reasonfield',
						'maxlength' => 200,
						'name' => 'wpInterwikiReason',
						'size' => 60,
					],
				];

				break;
			case 'delete':
				$formDescriptor = [
					'prefix' => [
						'type' => 'hidden',
						'name' => 'prefix',
						'default' => $prefix,
					],

					'reason' => [
						'type' => 'text',
						'name' => 'reason',
						'label-message' => 'interwiki_reasonfield',
					],
				];

				break;
		}

		$formDescriptor['hiddenPrefix'] = [
			'type' => 'hidden',
			'name' => 'hiddenPrefix',
			'default' => $prefix,
		];

		if ( $action === 'edit' ) {
			$dbr = MediaWikiServices::getInstance()->getConnectionProvider()->getReplicaDatabase();
			$row = $dbr->selectRow( 'interwiki', '*', [ 'iw_prefix' => $prefix ], __METHOD__ );

			$formDescriptor['prefix']['disabled'] = true;
			$formDescriptor['prefix']['default'] = $prefix;
			$hiddenFields['prefix'] = $prefix;

			if ( !$row ) {
				$status->fatal( 'interwiki_editerror', $prefix );
			} else {
				$formDescriptor['url']['default'] = $row->iw_url;
				$formDescriptor['api']['default'] = $row->iw_api;
				$formDescriptor['trans']['default'] = $row->iw_trans;
				$formDescriptor['local']['default'] = $row->iw_local;
			}
		}

		if ( !$status->isOK() ) {
			$formDescriptor = [];
		}

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm
			->addHiddenFields( $hiddenFields )
			->setSubmitCallback( [ $this, 'onSubmit' ] );

		if ( $status->isOK() ) {
			if ( $action === 'delete' ) {
				$htmlForm->setSubmitDestructive();
			}

			$htmlForm->setSubmitTextMsg( $action !== 'add' ? $action : 'interwiki_addbutton' )
				->setPreHtml( $this->msg( $action !== 'delete' ? "interwiki_{$action}intro" :
					'interwiki_deleting', $prefix )->escaped() )
				->show();
		} else {
			$htmlForm->suppressDefaultSubmit()
				->prepareForm()
				->displayForm( $status );
		}

		$this->getOutput()->addBacklinkSubtitle( $this->getPageTitle() );
	}

	public function onSubmit( array $data ) {
		$services = MediaWikiServices::getInstance();

		$status = Status::newGood();
		$request = $this->getRequest();
		$config = $this->getConfig();
		$prefix = $this->getRequest()->getVal( 'prefix', '' );
		$do = $request->getVal( 'action' );
		// Show an error if the prefix is invalid (only when adding one).
		// Invalid characters for a title should also be invalid for a prefix.
		// Whitespace, ':', '&' and '=' are invalid, too.
		// (Bug 30599).
		$validPrefixChars = preg_replace( '/[ :&=]/', '', Title::legalChars() );
		if ( $do === 'add' && preg_match( "/\s|[^$validPrefixChars]/", $prefix ) ) {
			$status->fatal( 'interwiki-badprefix', htmlspecialchars( $prefix ) );
			return $status;
		}
		// Disallow adding local interlanguage definitions if using global
		$interwikiCentralInterlanguageDB = $config->get( 'InterwikiCentralInterlanguageDB' );
		if (
			$do === 'add' && $services->getLanguageNameUtils()->getLanguageName( $prefix )
			&& $interwikiCentralInterlanguageDB !== WikiMap::getCurrentWikiId()
			&& $interwikiCentralInterlanguageDB !== null
		) {
			$status->fatal( 'interwiki-cannotaddlocallanguage', htmlspecialchars( $prefix ) );
			return $status;
		}
		$reason = $data['reason'];
		$selfTitle = $this->getPageTitle();
		$lookup = $services->getInterwikiLookup();
		$dbw = $services->getConnectionProvider()->getPrimaryDatabase();
		switch ( $do ) {
			case 'delete':
				$dbw->newDeleteQueryBuilder()
					->deleteFrom( 'interwiki' )
					->where( [ 'iw_prefix' => $prefix ] )
					->caller( __METHOD__ )
					->execute();

				if ( $dbw->affectedRows() === 0 ) {
					$status->fatal( 'interwiki_delfailed', $prefix );
				} else {
					$this->getOutput()->addWikiMsg( 'interwiki_deleted', $prefix );
					$log = new LogPage( 'interwiki' );
					$log->addEntry(
						'iw_delete',
						$selfTitle,
						$reason,
						[ $prefix ],
						$this->getUser()
					);
					$lookup->invalidateCache( $prefix );
				}
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'add':
				$contLang = $services->getContentLanguage();
				$prefix = $contLang->lc( $prefix );
				// Fall through
			case 'edit':
				$theurl = $data['url'];
				$api = $data['api'] ?? '';
				$local = $data['local'] ? 1 : 0;
				$trans = $data['trans'] ? 1 : 0;
				$rows = [
					'iw_prefix' => $prefix,
					'iw_url' => $theurl,
					'iw_api' => $api,
					'iw_wikiid' => '',
					'iw_local' => $local,
					'iw_trans' => $trans
				];

				if ( $prefix === '' || $theurl === '' ) {
					$status->fatal( 'interwiki-submit-empty' );
					break;
				}

				// Simple URL validation: check that the protocol is one of
				// the supported protocols for this wiki.
				// (bug 30600)
				if ( !wfParseUrl( $theurl ) ) {
					$status->fatal( 'interwiki-submit-invalidurl' );
					break;
				}

				if ( $do === 'add' ) {
					$dbw->newInsertQueryBuilder()
						->insertInto( 'interwiki' )
						->ignore()
						->row( $rows )
						->caller( __METHOD__ )
						->execute();
				} else { // $do === 'edit'
					$dbw->newUpdateQueryBuilder()
						->update( 'interwiki' )
						->ignore()
						->set( $rows )
						->where( [ 'iw_prefix' => $prefix ] )
						->caller( __METHOD__ )
						->execute();
				}

				// used here: interwiki_addfailed, interwiki_added, interwiki_edited
				if ( $dbw->affectedRows() === 0 ) {
					$status->fatal( "interwiki_{$do}failed", $prefix );
				} else {
					$this->getOutput()->addWikiMsg( "interwiki_{$do}ed", $prefix );
					$log = new LogPage( 'interwiki' );
					$log->addEntry(
						'iw_' . $do,
						$selfTitle,
						$reason,
						[ $prefix, $theurl, $trans, $local ],
						$this->getUser()
					);
					// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
					$lookup->invalidateCache( $prefix );
				}
				break;
		}

		return $status;
	}

	protected function showList() {
		$canModify = $this->canModify();

		// Build lists
		$services = MediaWikiServices::getInstance();

		$lookup = $services->getInterwikiLookup();
		$iwPrefixes = $lookup->getAllPrefixes( null );
		$iwGlobalPrefixes = [];
		$iwGlobalLanguagePrefixes = [];

		$config = $this->getConfig();
		$interwikiCentralDB = $config->get( 'InterwikiCentralDB' );

		$languageNameUtils = $services->getLanguageNameUtils();

		$connectionProvider = $services->getConnectionProvider();

		if ( $interwikiCentralDB !== null && $interwikiCentralDB !== WikiMap::getCurrentWikiId() ) {
			// Fetch list from global table
			$dbrCentralDB = $connectionProvider->getReplicaDatabase( $interwikiCentralDB );

			$res = $dbrCentralDB->select( 'interwiki', '*', [], __METHOD__ );
			$retval = [];
			foreach ( $res as $row ) {
				$row = (array)$row;
				if ( !$languageNameUtils->getLanguageName( $row['iw_prefix'] ) ) {
					$retval[] = $row;
				}
			}
			$iwGlobalPrefixes = $retval;
		}

		// Almost the same loop as above, but for global inter*language* links, whereas the above is for
		// global inter*wiki* links
		$interwikiCentralInterlanguageDB = $config->get( 'InterwikiCentralInterlanguageDB' );
		$usingGlobalInterlangLinks = ( $interwikiCentralInterlanguageDB !== null );
		$isGlobalInterlanguageDB = ( $interwikiCentralInterlanguageDB === WikiMap::getCurrentWikiId() );
		$usingGlobalLanguages = $usingGlobalInterlangLinks && !$isGlobalInterlanguageDB;
		if ( $usingGlobalLanguages ) {
			// Fetch list from global table
			$dbrCentralLangDB = $connectionProvider->getReplicaDatabase( $interwikiCentralInterlanguageDB );

			$res = $dbrCentralLangDB->select( 'interwiki', '*', [], __METHOD__ );
			$retval2 = [];
			foreach ( $res as $row ) {
				$row = (array)$row;
				// Note that the above DB query explicitly *excludes* interlang ones
				// (which makes sense), whereas here we _only_ care about interlang ones!
				if ( $languageNameUtils->getLanguageName( $row['iw_prefix'] ) ) {
					$retval2[] = $row;
				}
			}
			$iwGlobalLanguagePrefixes = $retval2;
		}

		// Split out language links
		$iwLocalPrefixes = [];
		$iwLanguagePrefixes = [];
		foreach ( $iwPrefixes as $iwPrefix ) {
			if ( $languageNameUtils->getLanguageName( $iwPrefix['iw_prefix'] ) ) {
				$iwLanguagePrefixes[] = $iwPrefix;
			} else {
				$iwLocalPrefixes[] = $iwPrefix;
			}
		}

		// If using global interlanguage links, just ditch the data coming from the
		// local table and overwrite it with the global data
		if ( $usingGlobalInterlangLinks ) {
			unset( $iwLanguagePrefixes );
			$iwLanguagePrefixes = $iwGlobalLanguagePrefixes;
		}

		// Page intro content
		$this->getOutput()->addWikiMsg( 'interwiki_intro' );

		// Add 'view log' link when possible
		if ( !$config->get( 'InterwikiViewOnly' ) ) {
			$logLink = $this->getLinkRenderer()->makeLink(
				SpecialPage::getTitleFor( 'Log', 'interwiki' ),
				$this->msg( 'interwiki-logtext' )->text()
			);
			$this->getOutput()->addHTML( '<p class="mw-interwiki-log">' . $logLink . '</p>' );
		}

		// Add 'add' link
		if ( $canModify ) {
			if ( count( $iwGlobalPrefixes ) !== 0 ) {
				if ( $usingGlobalLanguages ) {
					$addtext = 'interwiki-addtext-local-nolang';
				} else {
					$addtext = 'interwiki-addtext-local';
				}
			} else {
				if ( $usingGlobalLanguages ) {
					$addtext = 'interwiki-addtext-nolang';
				} else {
					$addtext = 'interwiki_addtext';
				}
			}
			$addtext = $this->msg( $addtext )->text();
			$addlink = $this->getLinkRenderer()->makeKnownLink(
				$this->getPageTitle( 'add' ), $addtext );
			$this->getOutput()->addHTML(
				'<p class="mw-interwiki-addlink">' . $addlink . '</p>' );
		}

		$this->getOutput()->addWikiMsg( 'interwiki-legend' );

		if ( $iwPrefixes === [] && $iwGlobalPrefixes === [] ) {
			// If the interwiki table(s) are empty, display an error message
			$this->error( 'interwiki_error' );
			return;
		}

		// Add the global table
		if ( count( $iwGlobalPrefixes ) !== 0 ) {
			$this->getOutput()->addHTML(
				'<h2 id="interwikitable-global">' .
				$this->msg( 'interwiki-global-links' )->parse() .
				'</h2>'
			);
			$this->getOutput()->addWikiMsg( 'interwiki-global-description' );

			// $canModify is false here because this is just a display of remote data
			$this->makeTable( false, $iwGlobalPrefixes );
		}

		// Add the local table
		if ( count( $iwLocalPrefixes ) !== 0 ) {
			if ( count( $iwGlobalPrefixes ) !== 0 ) {
				$this->getOutput()->addHTML(
					'<h2 id="interwikitable-local">' .
					$this->msg( 'interwiki-local-links' )->parse() .
					'</h2>'
				);
				$this->getOutput()->addWikiMsg( 'interwiki-local-description' );
			} else {
				$this->getOutput()->addHTML(
					'<h2 id="interwikitable-local">' .
					$this->msg( 'interwiki-links' )->parse() .
					'</h2>'
				);
				$this->getOutput()->addWikiMsg( 'interwiki-description' );
			}
			$this->makeTable( $canModify, $iwLocalPrefixes );
		}

		// Add the language table
		if ( count( $iwLanguagePrefixes ) !== 0 ) {
			if ( $usingGlobalLanguages ) {
				$header = 'interwiki-global-language-links';
				$description = 'interwiki-global-language-description';
			} else {
				$header = 'interwiki-language-links';
				$description = 'interwiki-language-description';
			}

			$this->getOutput()->addHTML(
				'<h2 id="interwikitable-language">' .
				$this->msg( $header )->parse() .
				'</h2>'
			);
			$this->getOutput()->addWikiMsg( $description );

			// When using global interlanguage links, don't allow them to be modified
			// except on the source wiki
			$canModify = ( $usingGlobalLanguages ? false : $canModify );
			$this->makeTable( $canModify, $iwLanguagePrefixes );
		}
	}

	protected function makeTable( $canModify, $iwPrefixes ) {
		// Output the existing Interwiki prefixes table header
		$out = Html::openElement(
			'table',
			[ 'class' => 'mw-interwikitable wikitable sortable body' ]
		) . "\n";
		$out .= Html::openElement( 'thead' ) .
			Html::openElement( 'tr', [ 'class' => 'interwikitable-header' ] ) .
			Html::element( 'th', [], $this->msg( 'interwiki_prefix' )->text() ) .
			Html::element( 'th', [], $this->msg( 'interwiki_url' )->text() ) .
			Html::element( 'th', [], $this->msg( 'interwiki_local' )->text() ) .
			Html::element( 'th', [], $this->msg( 'interwiki_trans' )->text() ) .
			( $canModify ?
				Html::element(
					'th',
					[ 'class' => 'unsortable' ],
					$this->msg( 'interwiki_edit' )->text()
				) :
				''
			);
		$out .= Html::closeElement( 'tr' ) .
			Html::closeElement( 'thead' ) . "\n" .
			Html::openElement( 'tbody' );

		$selfTitle = $this->getPageTitle();

		// Output the existing Interwiki prefixes table rows
		foreach ( $iwPrefixes as $iwPrefix ) {
			$out .= Html::openElement( 'tr', [ 'class' => 'mw-interwikitable-row' ] );
			$out .= Html::element( 'td', [ 'class' => 'mw-interwikitable-prefix' ],
				$iwPrefix['iw_prefix'] );
			$out .= Html::element(
				'td',
				[ 'class' => 'mw-interwikitable-url' ],
				$iwPrefix['iw_url']
			);
			$attribs = [ 'class' => 'mw-interwikitable-local' ];
			// Green background for cells with "yes".
			if ( isset( $iwPrefix['iw_local'] ) && $iwPrefix['iw_local'] ) {
				$attribs['class'] .= ' mw-interwikitable-local-yes';
			}
			// The messages interwiki_0 and interwiki_1 are used here.
			$contents = isset( $iwPrefix['iw_local'] ) ?
				$this->msg( 'interwiki_' . $iwPrefix['iw_local'] )->text() :
				'-';
			$out .= Html::element( 'td', $attribs, $contents );
			$attribs = [ 'class' => 'mw-interwikitable-trans' ];
			// Green background for cells with "yes".
			if ( isset( $iwPrefix['iw_trans'] ) && $iwPrefix['iw_trans'] ) {
				$attribs['class'] .= ' mw-interwikitable-trans-yes';
			}
			// The messages interwiki_0 and interwiki_1 are used here.
			$contents = isset( $iwPrefix['iw_trans'] ) ?
				$this->msg( 'interwiki_' . $iwPrefix['iw_trans'] )->text() :
				'-';
			$out .= Html::element( 'td', $attribs, $contents );

			// Additional column when the interwiki table can be modified.
			if ( $canModify ) {
				$out .= Html::rawElement( 'td', [ 'class' => 'mw-interwikitable-modify' ],
					$this->getLinkRenderer()->makeKnownLink(
						$selfTitle,
						$this->msg( 'edit' )->text(),
						[],
						[ 'action' => 'edit', 'prefix' => $iwPrefix['iw_prefix'] ]
					) .
					$this->msg( 'comma-separator' )->escaped() .
					$this->getLinkRenderer()->makeKnownLink(
						$selfTitle,
						$this->msg( 'delete' )->text(),
						[],
						[ 'action' => 'delete', 'prefix' => $iwPrefix['iw_prefix'] ]
					)
				);
			}
			$out .= Html::closeElement( 'tr' ) . "\n";
		}
		$out .= Html::closeElement( 'tbody' ) .
			Html::closeElement( 'table' );

		$this->getOutput()->addHTML( $out );
		$this->getOutput()->addModuleStyles( 'jquery.tablesorter.styles' );
		$this->getOutput()->addModules( 'jquery.tablesorter' );
	}

	/**
	 * @param string ...$args
	 */
	protected function error( ...$args ) {
		$this->getOutput()->wrapWikiMsg( "<p class='error'>$1</p>", $args );
	}

	protected function getGroupName() {
		return 'wiki';
	}
}
