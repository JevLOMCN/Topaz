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
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\Extension\Gadgets\Special;

use ContentHandler;
use HTMLForm;
use InvalidArgumentException;
use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\Extension\Gadgets\GadgetRepo;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;

/**
 * Special:Gadgets renders the data of MediaWiki:Gadgets-definition.
 *
 * @copyright 2007 Daniel Kinzler
 */
class SpecialGadgets extends SpecialPage {
	private GadgetRepo $gadgetRepo;

	public function __construct( GadgetRepo $gadgetRepo ) {
		parent::__construct( 'Gadgets' );
		$this->gadgetRepo = $gadgetRepo;
	}

	/**
	 * Return title for Special:Gadgets heading and Special:Specialpages link.
	 *
	 * @return \Message
	 */
	public function getDescription() {
		return $this->msg( 'special-gadgets' );
	}

	/**
	 * @param string|null $par Parameters passed to the page
	 */
	public function execute( $par ) {
		$parts = $par !== null ? explode( '/', $par ) : [];

		if ( count( $parts ) === 2 && $parts[0] === 'export' ) {
			$this->showExportForm( $parts[1] );
		} else {
			$this->showMainForm();
		}
	}

	/**
	 * @param string $gadgetName
	 * @return string
	 */
	private function makeAnchor( $gadgetName ) {
		return 'gadget-' . Sanitizer::escapeIdForAttribute( $gadgetName );
	}

	/**
	 * Displays form showing the list of installed gadgets
	 */
	public function showMainForm() {
		$output = $this->getOutput();
		$this->setHeaders();
		$this->addHelpLink( 'Extension:Gadgets' );
		$output->setPageTitleMsg( $this->msg( 'gadgets-title' ) );
		$output->addWikiMsg( 'gadgets-pagetext' );

		$gadgets = $this->gadgetRepo->getStructuredList();
		if ( !$gadgets ) {
			return;
		}

		$services = MediaWikiServices::getInstance();

		$output->disallowUserJs();
		$lang = $this->getLanguage();
		$langSuffix = "";
		if ( !$lang->equals( $services->getContentLanguage() ) ) {
			$langSuffix = "/" . $lang->getCode();
		}

		$listOpen = false;

		$editDefinitionMessage = $this->getUser()->isAllowed( 'editsitejs' )
			? 'edit'
			: 'viewsource';
		$editInterfaceMessage = $this->getUser()->isAllowed( 'editinterface' )
			? 'gadgets-editdescription'
			: 'gadgets-viewdescription';

		$linkRenderer = $this->getLinkRenderer();
		$skinFactory = $services->getSkinFactory();
		foreach ( $gadgets as $section => $entries ) {
			if ( $section !== false && $section !== '' ) {
				if ( $listOpen ) {
					$output->addHTML( Html::closeElement( 'ul' ) . "\n" );
					$listOpen = false;
				}

				// H2 section heading
				$headingText = Html::rawElement(
					'span',
					[ 'class' => 'mw-headline' ],
					$this->msg( "gadget-section-$section" )->parse()
				);
				$title = Title::makeTitleSafe( NS_MEDIAWIKI, "Gadget-section-$section$langSuffix" );
				$leftBracket = Html::rawElement(
					'span',
					[ 'class' => 'mw-editsection-bracket' ],
					'['
				);
				$linkTarget = $title
					? $linkRenderer->makeLink( $title, $this->msg( $editInterfaceMessage )->text(),
						[], [ 'action' => 'edit' ] )
					: htmlspecialchars( $section );
				$rightBracket = Html::rawElement(
					'span',
					[ 'class' => 'mw-editsection-bracket' ],
					']'
				);
				$editDescriptionLink = Html::rawElement(
					'span',
					[ 'class' => 'mw-editsection' ],
					$leftBracket . $linkTarget . $rightBracket
				);
				$output->addHTML( Html::rawElement( 'h2', [], $headingText . $editDescriptionLink ) . "\n" );
			}

			/**
			 * @var Gadget $gadget
			 */
			foreach ( $entries as $gadget ) {
				$name = $gadget->getName();
				$title = Title::makeTitleSafe( NS_MEDIAWIKI, "Gadget-{$name}$langSuffix" );
				if ( !$title ) {
					continue;
				}

				$links = [];
				$definitionTitle = $this->gadgetRepo->getGadgetDefinitionTitle( $name );
				if ( $definitionTitle ) {
					$links[] = $linkRenderer->makeLink(
						$definitionTitle,
						$this->msg( $editDefinitionMessage )->text(),
						[],
						[ 'action' => 'edit' ]
					);
				}
				$links[] = $linkRenderer->makeLink(
					$title,
					$this->msg( $editInterfaceMessage )->text(),
					[],
					[ 'action' => 'edit' ]
				);
				$links[] = $linkRenderer->makeLink(
					$this->getPageTitle( "export/{$name}" ),
					$this->msg( 'gadgets-export' )->text()
				);

				$nameHtml = $this->msg( "gadget-{$name}" )->parse();

				if ( !$listOpen ) {
					$listOpen = true;
					$output->addHTML( Html::openElement( 'ul' ) );
				}

				$actionsHtml = '&#160;&#160;' .
					$this->msg( 'parentheses' )->rawParams( $lang->pipeList( $links ) )->escaped();
				$output->addHTML(
					Html::openElement( 'li', [ 'id' => $this->makeAnchor( $name ) ] ) .
						$nameHtml . $actionsHtml
				);
				// Whether the next portion of the list item contents needs
				// a line break between it and the next portion.
				// This is set to false after lists, but true after lines of text.
				$needLineBreakAfter = true;

				// Portion: Show files, dependencies, speers
				if ( $needLineBreakAfter ) {
					$output->addHTML( '<br />' );
				}
				$output->addHTML(
					$this->msg( 'gadgets-uses' )->escaped() .
					$this->msg( 'colon-separator' )->escaped()
				);
				$links = [];
				foreach ( $gadget->getPeers() as $peer ) {
					$links[] = Html::element(
						'a',
						[ 'href' => '#' . $this->makeAnchor( $peer ) ],
						$peer
					);
				}
				foreach ( $gadget->getScriptsAndStyles() as $codePage ) {
					$title = Title::newFromText( $codePage );
					if ( !$title ) {
						continue;
					}
					$links[] = $linkRenderer->makeLink( $title, $title->getText() );
				}
				$output->addHTML( $lang->commaList( $links ) );

				if ( $gadget->isPackaged() ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML( $this->msg( 'gadgets-packaged',
						$this->gadgetRepo->titleWithoutPrefix( $gadget->getScripts()[0], $gadget->getName() ) ) );
					$needLineBreakAfter = true;
				}

				// Portion: Legacy scripts
				if ( $gadget->getLegacyScripts() ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML( Html::errorBox(
						$this->msg( 'gadgets-legacy' )->parse(),
						'',
						'mw-gadget-legacy'
					) );
					$needLineBreakAfter = false;
				}

				if ( $gadget->requiresES6() ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg( 'gadgets-requires-es6' )->parse()
					);
					$needLineBreakAfter = true;
				}

				// Portion: Show required rights (optional)
				$rights = [];
				foreach ( $gadget->getRequiredRights() as $right ) {
					$rights[] = Html::element(
						'code',
						[ 'title' => $this->msg( "right-$right" )->plain() ],
						$right
					);
				}
				if ( $rights ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg( 'gadgets-required-rights', $lang->commaList( $rights ), count( $rights ) )->parse()
					);
					$needLineBreakAfter = true;
				}

				// Portion: Show required skins (optional)
				$requiredSkins = $gadget->getRequiredSkins();
				$skins = [];
				$validskins = $skinFactory->getSkinNames();
				foreach ( $requiredSkins as $skinid ) {
					if ( isset( $validskins[$skinid] ) ) {
						$skins[] = $this->msg( "skinname-$skinid" )->plain();
					} else {
						$skins[] = $skinid;
					}
				}
				if ( $skins ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg( 'gadgets-required-skins', $lang->commaList( $skins ) )
							->numParams( count( $skins ) )->parse()
					);
					$needLineBreakAfter = true;
				}

				// Portion: Show required actions (optional)
				$actions = [];
				foreach ( $gadget->getRequiredActions() as $action ) {
					$actions[] = Html::element( 'code', [], $action );
				}
				if ( $actions ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg( 'gadgets-required-actions', $lang->commaList( $actions ) )
							->numParams( count( $actions ) )->parse()
					);
					$needLineBreakAfter = true;
				}

				// Portion: Show required namespaces (optional)
				$namespaces = $gadget->getRequiredNamespaces();
				if ( $namespaces ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg(
							'gadgets-required-namespaces',
							$lang->commaList( array_map( function ( int $ns ) use ( $lang ) {
								return $ns == NS_MAIN
									? $this->msg( 'blanknamespace' )->text()
									: $lang->getFormattedNsText( $ns );
							}, $namespaces ) )
						)->numParams( count( $namespaces ) )->parse()
					);
					$needLineBreakAfter = true;
				}

				// Portion: Show required content models (optional)
				$contentModels = [];
				foreach ( $gadget->getRequiredContentModels() as $model ) {
					$contentModels[] = Html::element(
						'code',
						[ 'title' => ContentHandler::getLocalizedName( $model, $lang ) ],
						$model
					);
				}
				if ( $contentModels ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg( 'gadgets-required-contentmodels',
							$lang->commaList( $contentModels ),
							count( $contentModels )
						)->parse()
					);
					$needLineBreakAfter = true;
				}

				// Portion: Show required categories (optional)
				$categories = [];
				foreach ( $gadget->getRequiredCategories() as $category ) {
					$title = Title::makeTitleSafe( NS_CATEGORY, $category );
					$categories[] = $title
						? $linkRenderer->makeLink( $title, $category )
						: htmlspecialchars( $category );
				}
				if ( $categories ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg( 'gadgets-required-categories' )
							->rawParams( $lang->commaList( $categories ) )
							->numParams( count( $categories ) )->parse()
					);
					$needLineBreakAfter = true;
				}

				if ( $gadget->supportsUrlLoad() ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML( $this->msg( 'gadgets-supports-urlload' )->parse() );
					$needLineBreakAfter = true;
				}

				// Portion: Show on by default (optional)
				if ( $gadget->isOnByDefault() ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML( $this->msg( 'gadgets-default' )->parse() );
					$needLineBreakAfter = true;
				}

				// Show warnings
				$warnings = GadgetRepo::singleton()->validationWarnings( $gadget );

				if ( count( $warnings ) > 0 ) {
					$output->addHTML( Html::warningBox( implode( '<br/>', array_map( static function ( $msg ) {
						return $msg->parse();
					}, $warnings ) ) ) );
					$needLineBreakAfter = false;
				}

				$output->addHTML( Html::closeElement( 'li' ) . "\n" );
			}
		}

		if ( $listOpen ) {
			$output->addHTML( Html::closeElement( 'ul' ) . "\n" );
		}
	}

	/**
	 * Exports a gadget with its dependencies in a serialized form
	 * @param string $gadget Name of gadget to export
	 */
	public function showExportForm( $gadget ) {
		global $wgScript;

		$this->addHelpLink( 'Extension:Gadgets' );
		$output = $this->getOutput();
		try {
			$g = $this->gadgetRepo->getGadget( $gadget );
		} catch ( InvalidArgumentException $e ) {
			$output->showErrorPage( 'error', 'gadgets-not-found', [ $gadget ] );
			return;
		}

		$this->setHeaders();
		$output->setPageTitleMsg( $this->msg( 'gadgets-export-title' ) );
		$output->addWikiMsg( 'gadgets-export-text', $gadget, $g->getDefinition() );

		$exportList = "MediaWiki:gadget-$gadget\n";
		foreach ( $g->getScriptsAndStyles() as $page ) {
			$exportList .= "$page\n";
		}

		$htmlForm = HTMLForm::factory( 'ooui', [], $this->getContext() );
		$htmlForm
			->setTitle( SpecialPage::getTitleFor( 'Export' ) )
			->addHiddenField( 'pages', $exportList )
			->addHiddenField( 'wpDownload', '1' )
			->addHiddenField( 'templates', '1' )
			->setAction( $wgScript )
			->setMethod( 'get' )
			->setSubmitText( $this->msg( 'gadgets-export-download' )->text() )
			->prepareForm()
			->displayForm( false );
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'wiki';
	}
}
