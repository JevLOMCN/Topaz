<?php
/**
 * BaseTemplate class for the Timeless skin
 *
 * @ingroup Skins
 */

namespace MediaWiki\Skin\Timeless;

use BaseTemplate;
use File;
use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\ResourceLoader\SkinModule;
use MediaWiki\SpecialPage\SpecialPage;

class TimelessTemplate extends BaseTemplate {

	/** @var array */
	protected $pileOfTools;

	/** @var (array|false)[] */
	protected $sidebar;

	/** @var array|null */
	protected $otherProjects;

	/** @var array|null */
	protected $collectionPortlet;

	/** @var array[] */
	protected $languages;

	/** @var string */
	protected $afterLangPortlet;

	/**
	 * Outputs the entire contents of the page
	 */
	public function execute() {
		$this->sidebar = $this->data['sidebar'];
		$this->languages = $this->sidebar['LANGUAGES'];

		// WikiBase sidebar thing
		if ( isset( $this->sidebar['wikibase-otherprojects'] ) ) {
			$this->otherProjects = $this->sidebar['wikibase-otherprojects'];
			unset( $this->sidebar['wikibase-otherprojects'] );
		}
		// Collection sidebar thing
		if ( isset( $this->sidebar['coll-print_export'] ) ) {
			$this->collectionPortlet = $this->sidebar['coll-print_export'];
			unset( $this->sidebar['coll-print_export'] );
		}

		$this->pileOfTools = $this->getPageTools();
		$userLinks = $this->getUserLinks();

		$html = Html::openElement( 'div', [ 'id' => 'mw-wrapper', 'class' => $userLinks['class'] ] );

		$html .= Html::rawElement( 'div', [ 'id' => 'mw-header-container', 'class' => 'ts-container' ],
			Html::rawElement( 'div', [ 'id' => 'mw-header', 'class' => 'ts-inner' ],
				$userLinks['html'] .
				$this->getLogo( 'p-logo-text', 'text' ) .
				$this->getSearch()
			) .
			$this->getClear()
		);
		$html .= $this->getHeaderHack();

		// For mobile
		$html .= Html::element( 'div', [ 'id' => 'menus-cover' ] );

		$html .= Html::rawElement( 'div', [ 'id' => 'mw-content-container', 'class' => 'ts-container' ],
			Html::rawElement( 'div', [ 'id' => 'mw-content-block', 'class' => 'ts-inner' ],
				Html::rawElement( 'div', [ 'id' => 'mw-content-wrapper' ],
					$this->getContentBlock() .
					$this->getAfterContent()
				) .
				Html::rawElement( 'div', [ 'id' => 'mw-site-navigation' ],
					$this->getLogo( 'p-logo', 'image' ) .
					$this->getMainNavigation() .
					$this->getSidebarChunk(
						'site-tools',
						'timeless-sitetools',
						$this->getPortlet(
							'tb',
							$this->pileOfTools['general'],
							'timeless-sitetools'
						)
					)
				) .
				Html::rawElement( 'div', [ 'id' => 'mw-related-navigation' ],
					$this->getPageToolSidebar() .
					$this->getInterwikiLinks() .
					$this->getCategories()
				) .
				$this->getClear()
			)
		);

		$html .= Html::rawElement( 'div',
			[ 'id' => 'mw-footer-container', 'class' => 'mw-footer-container ts-container' ],
			$this->getFooterBlock( [ 'class' => [ 'mw-footer', 'ts-inner' ], 'id' => 'mw-footer' ] )
		);

		$html .= Html::closeElement( 'div' );

		// The unholy echo
		echo $html;
	}

	/**
	 * Generate the page content block
	 * Broken out here due to the excessive indenting, or stuff.
	 *
	 * @return string html
	 */
	protected function getContentBlock() {
		$templateData = $this->getSkin()->getTemplateData();
		$html = Html::rawElement(
			'div',
			[ 'id' => 'content', 'class' => 'mw-body', 'role' => 'main' ],
			$this->getSiteNotices() .
			$this->getIndicators() .
			$templateData[ 'html-title-heading' ] .
			Html::rawElement( 'div', [ 'id' => 'bodyContentOuter' ],
				Html::rawElement( 'div', [ 'id' => 'siteSub' ], $this->getMsg( 'tagline' )->parse() ) .
				Html::rawElement( 'div', [ 'id' => 'mw-page-header-links' ],
					$this->getPortlet(
						'namespaces',
						$this->pileOfTools['namespaces'],
						'timeless-namespaces',
						[ 'extra-classes' => 'tools-inline' ]
					) .
					$this->getPortlet(
						'more',
						$this->pileOfTools['more'],
						'timeless-more',
						[ 'extra-classes' => 'tools-inline' ]
					) .
					$this->getVariants() .
					$this->getPortlet(
						'views',
						$this->pileOfTools['page-primary'],
						'timeless-pagetools',
						[ 'extra-classes' => 'tools-inline' ]
					)
				) .
				$this->getClear() .
				Html::rawElement( 'div', [ 'id' => 'bodyContent' ],
					$this->getContentSub() .
					$this->get( 'bodytext' ) .
					$this->getClear()
				)
			)
		);

		return Html::rawElement( 'div', [ 'id' => 'mw-content' ], $html );
	}

	/**
	 * Generates a block of navigation links with a header
	 * This is some random fork of some random fork of what was supposed to be in core. Latest
	 * version copied out of MonoBook, probably. (20190719)
	 *
	 * @param string $name
	 * @param array|string $content array of links for use with makeListItem, or a block of text
	 *        Expected array format:
	 * 	[
	 * 		$name => [
	 * 			'links' => [ '0' =>
	 * 				[
	 * 					'href' => ...,
	 * 					'single-id' => ...,
	 * 					'text' => ...
	 * 				]
	 * 			],
	 * 			'id' => ...,
	 * 			'active' => ...
	 * 		],
	 * 		...
	 * 	]
	 * @param null|string|array|bool $msg
	 * @param array $setOptions miscellaneous overrides, see below
	 *
	 * @return string html
	 * @suppress PhanTypeMismatchArgumentNullable
	 */
	protected function getPortlet( $name, $content, $msg = null, $setOptions = [] ) {
		$skin = $this->getSkin();
		// random stuff to override with any provided options
		$options = array_merge( [
			'role' => 'navigation',
			// extra classes/ids
			'id' => 'p-' . $name,
			'class' => [ 'mw-portlet', 'emptyPortlet' => !$content ],
			'extra-classes' => '',
			'body-id' => null,
			'body-class' => 'mw-portlet-body',
			'body-extra-classes' => '',
			// wrapper for individual list items
			'text-wrapper' => [ 'tag' => 'span' ],
			// option to stick arbitrary stuff at the beginning of the ul
			'list-prepend' => ''
		], $setOptions );

		// Handle the different $msg possibilities
		if ( $msg === null ) {
			$msg = $name;
			$msgParams = [];
		} elseif ( is_array( $msg ) ) {
			$msgString = array_shift( $msg );
			$msgParams = $msg;
			$msg = $msgString;
		} else {
			$msgParams = [];
		}
		$msgObj = $this->getMsg( $msg, $msgParams );
		if ( $msgObj->exists() ) {
			$msgString = $msgObj->parse();
		} else {
			$msgString = htmlspecialchars( $msg );
		}

		$labelId = Sanitizer::escapeIdForAttribute( "p-$name-label" );

		if ( is_array( $content ) ) {
			$contentText = Html::openElement( 'ul',
				[ 'lang' => $this->get( 'userlang' ), 'dir' => $this->get( 'dir' ) ]
			);
			$contentText .= $options['list-prepend'];
			foreach ( $content as $key => $item ) {
				if ( is_array( $options['text-wrapper'] ) ) {
					$contentText .= $skin->makeListItem(
						$key,
						$item,
						[ 'text-wrapper' => $options['text-wrapper'] ]
					);
				} else {
					$contentText .= $skin->makeListItem(
						$key,
						$item
					);
				}
			}
			$contentText .= Html::closeElement( 'ul' );
		} else {
			$contentText = $content;
		}

		$divOptions = [
			'role' => $options['role'],
			'class' => $this->mergeClasses( $options['class'], $options['extra-classes'] ),
			'id' => Sanitizer::escapeIdForAttribute( $options['id'] ),
			'title' => Linker::titleAttrib( $options['id'] ),
			'aria-labelledby' => $labelId
		];
		$labelOptions = [
			'id' => $labelId,
			'lang' => $this->get( 'userlang' ),
			'dir' => $this->get( 'dir' )
		];

		$bodyDivOptions = [
			'class' => $this->mergeClasses( $options['body-class'], $options['body-extra-classes'] )
		];
		if ( is_string( $options['body-id'] ) ) {
			$bodyDivOptions['id'] = $options['body-id'];
		}

		$afterPortlet = '';
		$content = $this->getSkin()->getAfterPortlet( $name );
		if ( $content !== '' ) {
			$afterPortlet = Html::rawElement(
				'div',
				[ 'class' => [ 'after-portlet', 'after-portlet-' . $name ] ],
				$content
			);
		}

		if ( $name === 'lang' ) {
			$this->afterLangPortlet = $afterPortlet;
		}

		$html = Html::rawElement( 'div', $divOptions,
			Html::rawElement( 'h3', $labelOptions, $msgString ) .
			Html::rawElement( 'div', $bodyDivOptions,
				$contentText .
				$afterPortlet
			)
		);

		return $html;
	}

	/**
	 * Helper function for getPortlet
	 *
	 * Merge all provided css classes into a single array
	 * Account for possible different input methods matching what Html::element stuff takes
	 *
	 * @param string|array $class base portlet/body class
	 * @param string|array $extraClasses any extra classes to also include
	 *
	 * @return array all classes to apply
	 */
	protected function mergeClasses( $class, $extraClasses ) {
		if ( !is_array( $class ) ) {
			$class = [ $class ];
		}
		if ( !is_array( $extraClasses ) ) {
			$extraClasses = [ $extraClasses ];
		}

		return array_merge( $class, $extraClasses );
	}

	/**
	 * Better renderer for getFooterIcons and getFooterLinks, based on Vector's HTML output
	 * (as of 2016)
	 *
	 * @param array $setOptions Miscellaneous other options
	 * * 'id' for footer id
	 * * 'class' for footer class
	 * * 'order' to determine whether icons or links appear first: 'iconsfirst' or links, though in
	 *   practice we currently only check if it is or isn't 'iconsfirst'
	 * * 'link-prefix' to set the prefix for all link and block ids; most skins use 'f' or 'footer',
	 *   as in id='f-whatever' vs id='footer-whatever'
	 * * 'link-style' to pass to getFooterLinks: "flat" to disable categorisation of links in a
	 *   nested array
	 *
	 * @return string html
	 */
	protected function getFooterBlock( $setOptions = [] ) {
		// Set options and fill in defaults
		$options = $setOptions + [
			'id' => 'footer',
			'class' => 'mw-footer',
			'order' => 'iconsfirst',
			'link-prefix' => 'footer',
			'link-style' => null
		];

		// phpcs:ignore Generic.Files.LineLength.TooLong
		'@phan-var array{id:string,class:string,order:string,link-prefix:string,link-style:?string} $options';
		$validFooterIcons = $this->get( 'footericons' );
		$validFooterLinks = $this->getFooterLinks( $options['link-style'] );

		$html = '';

		$html .= Html::openElement( 'div', [
			'id' => $options['id'],
			'class' => $options['class'],
			'role' => 'contentinfo',
			'lang' => $this->get( 'userlang' ),
			'dir' => $this->get( 'dir' )
		] );

		$iconsHTML = '';
		if ( count( $validFooterIcons ) > 0 ) {
			$iconsHTML .= Html::openElement( 'ul', [ 'id' => "{$options['link-prefix']}-icons" ] );
			foreach ( $validFooterIcons as $blockName => $footerIcons ) {
				$iconsHTML .= Html::openElement( 'li', [
					'id' => Sanitizer::escapeIdForAttribute(
						"{$options['link-prefix']}-{$blockName}ico"
					),
					'class' => 'footer-icons'
				] );
				foreach ( $footerIcons as $icon ) {
					$iconsHTML .= $this->getSkin()->makeFooterIcon( $icon );
				}
				$iconsHTML .= Html::closeElement( 'li' );
			}
			$iconsHTML .= Html::closeElement( 'ul' );
		}

		$linksHTML = '';
		if ( count( $validFooterLinks ) > 0 ) {
			if ( $options['link-style'] === 'flat' ) {
				$linksHTML .= Html::openElement( 'ul', [
					'id' => "{$options['link-prefix']}-list",
					'class' => 'footer-places'
				] );
				foreach ( $validFooterLinks as $link ) {
					$linksHTML .= Html::rawElement(
						'li',
						[ 'id' => Sanitizer::escapeIdForAttribute( $link ) ],
						$this->get( $link )
					);
				}
				$linksHTML .= Html::closeElement( 'ul' );
			} else {
				$linksHTML .= Html::openElement( 'div', [ 'id' => "{$options['link-prefix']}-list" ] );
				foreach ( $validFooterLinks as $category => $links ) {
					$linksHTML .= Html::openElement( 'ul',
						[ 'id' => Sanitizer::escapeIdForAttribute(
							"{$options['link-prefix']}-{$category}"
						) ]
					);
					foreach ( $links as $link ) {
						$linksHTML .= Html::rawElement(
							'li',
							[ 'id' => Sanitizer::escapeIdForAttribute(
								"{$options['link-prefix']}-{$category}-{$link}"
							) ],
							$this->get( $link )
						);
					}
					$linksHTML .= Html::closeElement( 'ul' );
				}
				$linksHTML .= Html::closeElement( 'div' );
			}
		}

		if ( $options['order'] === 'iconsfirst' ) {
			$html .= $iconsHTML . $linksHTML;
		} else {
			$html .= $linksHTML . $iconsHTML;
		}

		$html .= $this->getClear() . Html::closeElement( 'div' );

		return $html;
	}

	/**
	 * Sidebar chunk containing one or more portlets
	 *
	 * @param string $id
	 * @param string $headerMessage
	 * @param string $content
	 * @param array $classes
	 *
	 * @return string html
	 */
	protected function getSidebarChunk( $id, $headerMessage, $content, $classes = [] ) {
		$html = '';

		$html .= Html::rawElement(
			'div',
			[
				'id' => Sanitizer::escapeIdForAttribute( $id ),
				'class' => array_merge( [ 'sidebar-chunk' ], $classes )
			],
			Html::rawElement( 'h2', [],
				Html::element( 'span', [],
					$this->getMsg( $headerMessage )->text()
				)
			) .
			Html::rawElement( 'div', [ 'class' => 'sidebar-inner' ], $content )
		);

		return $html;
	}

	/**
	 * The logo and (optionally) site title
	 *
	 * @param string $id
	 * @param string $part whether it's only image, only text, or both
	 *
	 * @return string html
	 */
	protected function getLogo( $id = 'p-logo', $part = 'both' ) {
		$html = '';
		$config = $this->getSkin()->getContext()->getConfig();

		$html .= Html::openElement(
			'div',
			[
				'id' => Sanitizer::escapeIdForAttribute( $id ),
				'class' => 'mw-portlet',
				'role' => 'banner'
			]
		);
		$logos = SkinModule::getAvailableLogos( $config );
		if ( $part !== 'image' ) {
			$wordmarkImage = $this->getLogoImage( $config->get( 'TimelessWordmark' ), true );
			if ( !$wordmarkImage && isset( $logos['wordmark'] ) ) {
				$wordmarkData = $logos['wordmark'];
				$wordmarkImage = Html::element( 'img', [
					'src' => $wordmarkData['src'],
					'height' => $wordmarkData['height'] ?? null,
					'width' => $wordmarkData['width'] ?? null,
				] );
			}

			$titleClass = '';
			$siteTitle = '';
			if ( !$wordmarkImage ) {
				$langConv = MediaWikiServices::getInstance()->getLanguageConverterFactory()
					->getLanguageConverter( $this->getSkin()->getLanguage() );
				if ( $langConv->hasVariants() ) {
					$siteTitle = $langConv->convert( $this->getMsg( 'timeless-sitetitle' )->escaped() );
				} else {
					$siteTitle = $this->getMsg( 'timeless-sitetitle' )->escaped();
				}
				// width is 11em; 13 characters will probably fit?
				if ( mb_strlen( $siteTitle ) > 13 ) {
					$titleClass = 'long';
				}
			} else {
				$titleClass = 'wordmark';
			}
			$html .= Html::rawElement( 'a', [
					'id' => 'p-banner',
					'class' => [ 'mw-wiki-title', $titleClass ],
					'href' => $this->data['nav_urls']['mainpage']['href']
				],
				$wordmarkImage ?: $siteTitle
			);

		}
		if ( $part !== 'text' ) {
			$logoImage = $this->getLogoImage( $config->get( 'TimelessLogo' ) );
			if ( $logoImage === false ) {
				$logoSrc = $logos['svg'] ?? $logos['icon'] ?? '';
				if ( $logoSrc !== '' ) {
					$logoImage = Html::element( 'img', [
						'src' => $logoSrc,
					] );
				}
			}

			$html .= Html::rawElement(
				'a',
				array_merge(
					[
						'class' => [ 'mw-wiki-logo', !$logoImage ? 'fallback' : 'timeless-logo' ],
						'href' => $this->data['nav_urls']['mainpage']['href']
					],
					Linker::tooltipAndAccesskeyAttribs( 'p-logo' )
				),
				$logoImage ?: ''
			);
		}
		$html .= Html::closeElement( 'div' );

		return $html;
	}

	/**
	 * The search box at the top
	 *
	 * @return string html
	 */
	protected function getSearch() {
		$skin = $this->getSkin();
		$html = Html::openElement( 'div', [ 'class' => 'mw-portlet', 'id' => 'p-search' ] );

		$html .= Html::rawElement(
			'h3',
			[ 'lang' => $this->get( 'userlang' ), 'dir' => $this->get( 'dir' ) ],
			Html::rawElement( 'label', [ 'for' => 'searchInput' ], $this->getMsg( 'search' )->escaped() )
		);

		$html .= Html::rawElement( 'form', [ 'action' => $this->get( 'wgScript' ), 'id' => 'searchform' ],
			Html::rawElement( 'div', [ 'id' => 'simpleSearch' ],
				Html::rawElement( 'div', [ 'id' => 'searchInput-container' ],
					$skin->makeSearchInput( [
						'id' => 'searchInput'
					] )
				) .
				Html::hidden( 'title', $this->get( 'searchtitle' ) ) .
				$skin->makeSearchButton(
					'fulltext',
					[ 'id' => 'mw-searchButton', 'class' => 'searchButton mw-fallbackSearchButton' ]
				) .
				$skin->makeSearchButton(
					'go',
					[ 'id' => 'searchButton', 'class' => 'searchButton' ]
				)
			)
		);

		return $html . Html::closeElement( 'div' );
	}

	/**
	 * Left sidebar navigation, usually
	 *
	 * @return string html
	 */
	protected function getMainNavigation() {
		$html = '';

		// Already hardcoded into header
		$this->sidebar['SEARCH'] = false;
		// Parsed as part of pageTools
		$this->sidebar['TOOLBOX'] = false;
		// Forcibly removed to separate chunk
		$this->sidebar['LANGUAGES'] = false;
		foreach ( $this->sidebar as $name => $content ) {
			if ( $content === false ) {
				continue;
			}
			// Numeric strings gets an integer when set as key, cast back - T73639
			$name = (string)$name;
			$html .= $this->getPortlet( $name, $content );
		}

		return $this->getSidebarChunk( 'site-navigation', 'navigation', $html );
	}

	/**
	 * The colour bars
	 * Split this out so we don't have to look at it/can easily kill it later
	 *
	 * @return string html
	 */
	protected function getHeaderHack() {
		$html = '';

		// These are almost exactly the same and this is stupid.
		$html .= Html::rawElement( 'div', [ 'id' => 'mw-header-hack', 'class' => 'color-bar' ],
			Html::rawElement( 'div', [ 'class' => 'color-middle-container' ],
				Html::element( 'div', [ 'class' => 'color-middle' ] )
			) .
			Html::element( 'div', [ 'class' => 'color-left' ] ) .
			Html::element( 'div', [ 'class' => 'color-right' ] )
		);
		$html .= Html::rawElement( 'div', [ 'id' => 'mw-header-nav-hack' ],
			Html::rawElement( 'div', [ 'class' => 'color-bar' ],
				Html::rawElement( 'div', [ 'class' => 'color-middle-container' ],
					Html::element( 'div', [ 'class' => 'color-middle' ] )
				) .
				Html::element( 'div', [ 'class' => 'color-left' ] ) .
				Html::element( 'div', [ 'class' => 'color-right' ] )
			)
		);

		return $html;
	}

	/**
	 * Page tools in sidebar
	 *
	 * @return string html
	 */
	protected function getPageToolSidebar() {
		$pageTools = $this->getPortlet(
			'cactions',
			$this->pileOfTools['page-secondary'],
			'timeless-pageactions'
		);
		$pageTools .= $this->getPortlet(
			'userpagetools',
			$this->pileOfTools['user'],
			'timeless-userpagetools'
		);
		$pageTools .= $this->getPortlet(
			'pagemisc',
			$this->pileOfTools['page-tertiary'],
			'timeless-pagemisc'
		);
		if ( isset( $this->collectionPortlet ) ) {
			$pageTools .= $this->getPortlet(
				'coll-print_export',
				$this->collectionPortlet
			);
		}

		return $this->getSidebarChunk( 'page-tools', 'timeless-pageactions', $pageTools );
	}

	/**
	 * Personal/user links portlet for header
	 *
	 * @return array [ html, class ], where class is an extra class to apply to surrounding objects
	 * (for width adjustments)
	 */
	protected function getUserLinks() {
		$skin = $this->getSkin();
		$user = $skin->getUser();
		$personalTools = $skin->getPersonalToolsForMakeListItem( $this->get( 'personal_urls' ) );
		// Preserve standard username label to allow customisation (T215822)
		$userName = $personalTools['userpage']['links'][0]['text'] ?? $user->getName();

		$extraTools = [];

		// Remove anon placeholder
		if ( isset( $personalTools['anonuserpage'] ) ) {
			unset( $personalTools['anonuserpage'] );
		}
		// Remove temp user placeholder, as we display the user name in the dropdown header instead.
		// Removing the use of .mw-userpage-tmp class also prevents the anchored popup from appearing,
		// which is good, because there's no reasonable place to put it.
		if (
			isset( $personalTools['userpage'] ) &&
			in_array( 'mw-userpage-tmp', $personalTools['userpage']['links'][0]['class'] ?? [] )
		) {
			unset( $personalTools['userpage'] );
		}

		// Remove Echo badges
		if ( isset( $personalTools['notifications-alert'] ) ) {
			$extraTools['notifications-alert'] = $personalTools['notifications-alert'];
			unset( $personalTools['notifications-alert'] );
		}
		if ( isset( $personalTools['notifications-notice'] ) ) {
			$extraTools['notifications-notice'] = $personalTools['notifications-notice'];
			unset( $personalTools['notifications-notice'] );
		}
		$class = $extraTools === [] ? '' : 'extension-icons';

		// Re-label some messages
		if ( isset( $personalTools['userpage'] ) ) {
			$personalTools['userpage']['links'][0]['text'] = $this->getMsg( 'timeless-userpage' )->text();
		}
		if ( isset( $personalTools['mytalk'] ) ) {
			$personalTools['mytalk']['links'][0]['text'] = $this->getMsg( 'timeless-talkpage' )->text();
		}

		// Labels
		if ( $user->isNamed() ) {
			$dropdownHeader = $userName;
			$headerMsg = [ 'timeless-loggedinas', $userName ];
		} elseif ( $user->isTemp() ) {
			$dropdownHeader = $user->getName();
			$headerMsg = 'timeless-notloggedin';
		} else {
			$dropdownHeader = $this->getMsg( 'timeless-anonymous' )->text();
			$headerMsg = 'timeless-notloggedin';
		}
		$html = Html::openElement( 'div', [ 'id' => 'user-tools' ] );

		$html .= Html::rawElement( 'div', [ 'id' => 'personal' ],
			Html::rawElement( 'h2', [],
				Html::element( 'span', [], $dropdownHeader )
			) .
			Html::rawElement( 'div', [ 'id' => 'personal-inner', 'class' => 'dropdown' ],
				$this->getPortlet( 'personal', $personalTools, $headerMsg )
			)
		);

		// Extra icon stuff (echo etc)
		if ( $extraTools !== [] ) {
			$iconList = '';
			foreach ( $extraTools as $key => $item ) {
				$iconList .= $skin->makeListItem( $key, $item );
			}

			$html .= Html::rawElement(
				'div',
				[ 'id' => 'personal-extra', 'class' => 'p-body' ],
				Html::rawElement( 'ul', [], $iconList )
			);
		}

		$html .= Html::closeElement( 'div' );

		return [
			'html' => $html,
			'class' => $class
		];
	}

	/**
	 * Notices that may appear above the firstHeading
	 *
	 * @return string html
	 */
	protected function getSiteNotices() {
		$html = '';

		if ( $this->data['sitenotice'] ) {
			$html .= Html::rawElement( 'div', [ 'id' => 'siteNotice' ], $this->get( 'sitenotice' ) );
		}
		if ( $this->data['newtalk'] ) {
			$html .= Html::rawElement( 'div', [ 'class' => 'usermessage' ], $this->get( 'newtalk' ) );
		}

		return $html;
	}

	/**
	 * Links and information that may appear below the firstHeading
	 *
	 * @return string html
	 */
	protected function getContentSub() {
		$html = Html::openElement( 'div', [ 'id' => 'contentSub' ] );
		if ( $this->data['subtitle'] ) {
			$html .= $this->get( 'subtitle' );
		}
		if ( $this->data['undelete'] ) {
			$html .= $this->get( 'undelete' );
		}
		return $html . Html::closeElement( 'div' );
	}

	/**
	 * The data after content, catlinks, and potential other stuff that may appear within
	 * the content block but after the main content
	 *
	 * @return string html
	 */
	protected function getAfterContent() {
		$html = '';

		if ( $this->data['catlinks'] || $this->data['dataAfterContent'] ) {
			$html .= Html::openElement( 'div', [ 'id' => 'content-bottom-stuff' ] );
			if ( $this->data['catlinks'] ) {
				$html .= $this->get( 'catlinks' );
			}
			if ( $this->data['dataAfterContent'] ) {
				$html .= $this->get( 'dataAfterContent' );
			}
			$html .= Html::closeElement( 'div' );
		}

		return $html;
	}

	/**
	 * Generate pile of all the tools
	 *
	 * We can make a few assumptions based on where a tool started out:
	 *     If it's in the cactions region, it's a page tool, probably primary or secondary
	 *     ...that's all I can think of
	 *
	 * @return array of array of tools information (portlet formatting)
	 */
	protected function getPageTools() {
		$title = $this->getSkin()->getTitle();
		$namespace = $title->getNamespace();

		$sortedPileOfTools = [
			'namespaces' => [],
			'page-primary' => [],
			'page-secondary' => [],
			'user' => [],
			'page-tertiary' => [],
			'more' => [],
			'general' => []
		];

		// Tools specific to the page
		$pileOfEditTools = [];
		$contentNavigation = $this->data['content_navigation'];

		foreach ( $contentNavigation as $navKey => $navBlock ) {
			// Just use namespaces items as they are
			if ( $navKey == 'namespaces' ) {
				if ( $namespace < 0 && count( $navBlock ) < 2 ) {
					// Put special page ns_pages in the more pile so they're not so lonely
					$sortedPileOfTools['page-tertiary'] = $navBlock;
				} else {
					$sortedPileOfTools['namespaces'] = $navBlock;
				}
			} elseif ( $navKey == 'variants' ) {
				// wat
				$sortedPileOfTools['variants'] = $navBlock;
			} else {
				$pileOfEditTools = array_merge( $pileOfEditTools, $navBlock );
			}
		}

		// Tools that may be general or page-related (typically the toolbox)
		$pileOfTools = $this->sidebar['TOOLBOX'];
		if ( $namespace >= 0 ) {
			$pileOfTools['pagelog'] = [
				'text' => $this->getMsg( 'timeless-pagelog' )->text(),
				'href' => SpecialPage::getTitleFor( 'Log' )->getLocalURL(
					[ 'page' => $title->getPrefixedText() ]
				),
				'id' => 't-pagelog'
			];
		}

		// Mobile toggles
		$pileOfTools['more'] = [
			'text' => $this->getMsg( 'timeless-more' )->text(),
			'id' => 'ca-more',
			'class' => 'dropdown-toggle'
		];
		// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
		if ( !empty( $this->sidebar['LANGUAGES'] ) || $sortedPileOfTools['variants']
			|| isset( $this->otherProjects ) ) {
			$pileOfTools['languages'] = [
				'text' => $this->getMsg( 'timeless-languages' )->escaped(),
				'id' => 'ca-languages',
				'class' => 'dropdown-toggle'
			];
		}

		// This is really dumb, and you're an idiot for doing it this way.
		// Obviously if you're not the idiot who did this, I don't mean you.
		foreach ( $pileOfEditTools as $navKey => $navBlock ) {
			if ( in_array( $navKey, [
				'watch',
				'unwatch'
			] ) ) {
				$currentSet = 'namespaces';
			} elseif ( in_array( $navKey, [
				'edit',
				'view',
				'history',
				'addsection',
				'viewsource'
			] ) ) {
				$currentSet = 'page-primary';
			} elseif ( in_array( $navKey, [
				'delete',
				'rename',
				'protect',
				'unprotect',
				'move'
			] ) ) {
				$currentSet = 'page-secondary';
			} else {
				// Catch random extension ones?
				$currentSet = 'page-primary';
			}
			$sortedPileOfTools[$currentSet][$navKey] = $navBlock;
		}
		foreach ( $pileOfTools as $navKey => $navBlock ) {
			$currentSet = null;

			if ( $navKey === 'contributions' ) {
				$currentSet = 'page-primary';
			} elseif ( in_array( $navKey, [
				'blockip',
				'userrights',
				'log',
				'emailuser'

			] ) ) {
				$currentSet = 'user';
			} elseif ( in_array( $navKey, [
				'whatlinkshere',
				'print',
				'info',
				'pagelog',
				'recentchangeslinked',
				'permalink',
				'wikibase',
				'cite'
			] ) ) {
				$currentSet = 'page-tertiary';
			} elseif ( in_array( $navKey, [
				'more',
				'languages'
			] ) ) {
				$currentSet = 'more';
			} else {
				$currentSet = 'general';
			}
			$sortedPileOfTools[$currentSet][$navKey] = $navBlock;
		}

		// Extra sorting for Extension:ProofreadPage namespace items
		$tabs = [
			// This is the order we want them in...
			'proofreadPageScanLink',
			'proofreadPageIndexLink',
			'proofreadPageNextLink',
		];
		foreach ( $tabs as $tab ) {
			if ( isset( $sortedPileOfTools['namespaces'][$tab] ) ) {
				$toMove = $sortedPileOfTools['namespaces'][$tab];
				unset( $sortedPileOfTools['namespaces'][$tab] );

				// move to end!
				$sortedPileOfTools['namespaces'][$tab] = $toMove;
			}
		}

		return $sortedPileOfTools;
	}

	/**
	 * Categories for the sidebar
	 *
	 * Assemble an array of categories. This doesn't show any categories for the
	 * action=history view, but that behaviour is consistent with other skins.
	 *
	 * @return string html
	 */
	protected function getCategories() {
		$skin = $this->getSkin();
		$catHeader = 'categories';
		$catList = '';

		$allCats = $skin->getOutput()->getCategoryLinks();
		if ( $allCats !== [] ) {
			if ( isset( $allCats['normal'] ) && $allCats['normal'] !== [] ) {
				$catList .= $this->getCatList(
					$allCats['normal'],
					'normal-catlinks',
					'mw-normal-catlinks',
					'categories'
				);
			} else {
				$catHeader = 'hidden-categories';
			}

			if ( isset( $allCats['hidden'] ) ) {
				$hiddenCatClass = [ 'mw-hidden-catlinks' ];
				if ( MediaWikiServices::getInstance()
					->getUserOptionsLookup()
					->getBoolOption( $skin->getUser(), 'showhiddencats' )
				) {
					$hiddenCatClass[] = 'mw-hidden-cats-user-shown';
				} elseif ( $skin->getTitle()->getNamespace() === NS_CATEGORY ) {
					$hiddenCatClass[] = 'mw-hidden-cats-ns-shown';
				} else {
					$hiddenCatClass[] = 'mw-hidden-cats-hidden';
				}
				$catList .= $this->getCatList(
					$allCats['hidden'],
					'hidden-catlinks',
					$hiddenCatClass,
					[ 'hidden-categories', count( $allCats['hidden'] ) ]
				);
			}
		}

		if ( $catList !== '' ) {
			return $this->getSidebarChunk( 'catlinks-sidebar', $catHeader, $catList );
		}

		return '';
	}

	/**
	 * List of categories
	 *
	 * @param array $list
	 * @param string $id
	 * @param string|array $class
	 * @param string|array $message i18n message name or an array of [ message name, params ]
	 *
	 * @return string html
	 */
	protected function getCatList( $list, $id, $class, $message ) {
		$html = Html::openElement( 'div', [ 'id' => "sidebar-{$id}", 'class' => $class ] );

		$makeLinkItem = static function ( $linkHtml ) {
			return Html::rawElement( 'li', [], $linkHtml );
		};

		$categoryItems = array_map( $makeLinkItem, $list );

		$categoriesHtml = Html::rawElement( 'ul',
			[],
			implode( '', $categoryItems )
		);

		$html .= $this->getPortlet( $id, $categoriesHtml, $message );

		return $html . Html::closeElement( 'div' );
	}

	/**
	 * Interlanguage links block, with variants if applicable
	 * Layout sort of assumes we're using ULS compact language handling
	 * if there's a lot of languages.
	 *
	 * @return string html
	 */
	protected function getVariants() {
		$html = '';

		if ( $this->pileOfTools['variants'] ) {
			$html .= $this->getPortlet(
				'variants-desktop',
				$this->pileOfTools['variants'],
				'variants',
				[ 'body-extra-classes' => 'dropdown' ]
			);
		}

		return $html;
	}

	/**
	 * Interwiki links block
	 *
	 * @return string html
	 */
	protected function getInterwikiLinks() {
		$html = '';
		$variants = '';
		$otherprojects = '';
		$show = false;
		$variantsOnly = false;

		if ( $this->pileOfTools['variants'] ) {
			$variants = $this->getPortlet(
				'variants',
				$this->pileOfTools['variants']
			);
			$show = true;
			$variantsOnly = true;
		}

		$languages = $this->getPortlet( 'lang', $this->languages, 'otherlanguages' );

		// Force rendering of this section if there are languages or when the 'lang'
		// portlet has been modified by hook even if there are no language items.
		if ( count( $this->languages ) || $this->afterLangPortlet !== '' ) {
			$show = true;
			$variantsOnly = false;
		} else {
			$languages = '';
		}

		// if using wikibase for 'in other projects'
		if ( isset( $this->otherProjects ) ) {
			$otherprojects = $this->getPortlet(
				'wikibase-otherprojects',
				$this->otherProjects
			);
			$show = true;
			$variantsOnly = false;
		}

		if ( $show ) {
			$html .= $this->getSidebarChunk(
				'other-languages',
				'timeless-projects',
				$variants . $languages . $otherprojects,
				$variantsOnly ? [ 'variants-only' ] : []
			);
		}

		return $html;
	}

	/**
	 * Generate img-based logos for proper HiDPI support
	 *
	 * @param string|array|null $logo
	 * @param bool $doLarge Render extra-large HiDPI logos for mobile devices?
	 *
	 * @return string|false html|we're not doing this
	 */
	protected function getLogoImage( $logo, $doLarge = false ) {
		if ( $logo === null ) {
			// not set, fall back to generic methods
			return false;
		}

		// Generate $logoData from a file upload
		if ( is_string( $logo ) ) {
			$file = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $logo );

			if ( !$file || !$file->canRender() ) {
				// eeeeeh bail, scary
				return false;
			}
			$logoData = [];

			// Calculate intended sizes
			$width = $file->getWidth();
			$height = $file->getHeight();
			$bound = $width > $height ? $width : $height;
			$svg = File::normalizeExtension( $file->getExtension() ) === 'svg';

			// Mobile stuff is generally a lot more than just 2ppp. Let's go with 4x?
			// Currently we're just doing this for wordmarks, which shouldn't get that
			// big in practice, so this is probably safe enough. And no need to use
			// this for desktop logos, so fall back to 2x for 2x as default...
			$large = $doLarge ? 4 : 2;

			if ( $bound <= 165 ) {
				// It's a 1x image
				$logoData['width'] = $width;
				$logoData['height'] = $height;

				if ( $svg ) {
					$logoData['1x'] = $file->createThumb( $logoData['width'] );
					$logoData['1.5x'] = $file->createThumb( (int)( $logoData['width'] * 1.5 ) );
					$logoData['2x'] = $file->createThumb( $logoData['width'] * $large );
				} elseif ( $file->mustRender() ) {
					$logoData['1x'] = $file->createThumb( $logoData['width'] );
				} else {
					$logoData['1x'] = $file->getUrl();
				}

			} elseif ( $bound >= 230 && $bound <= 330 ) {
				// It's a 2x image
				$logoData['width'] = (int)( $width / 2 );
				$logoData['height'] = (int)( $height / 2 );

				$logoData['1x'] = $file->createThumb( $logoData['width'] );
				$logoData['1.5x'] = $file->createThumb( (int)( $logoData['width'] * 1.5 ) );

				if ( $svg || $file->mustRender() ) {
					$logoData['2x'] = $file->createThumb( $logoData['width'] * 2 );
				} else {
					$logoData['2x'] = $file->getUrl();
				}
			} else {
				// Okay, whatever, we get to pick something random
				// Yes I am aware this means they might have arbitrarily tall logos,
				// and you know what, let 'em, I don't care
				$logoData['width'] = 155;
				$logoData['height'] = File::scaleHeight( $width, $height, $logoData['width'] );

				$logoData['1x'] = $file->createThumb( $logoData['width'] );
				if ( $svg || $logoData['width'] * 1.5 <= $width ) {
					$logoData['1.5x'] = $file->createThumb( (int)( $logoData['width'] * 1.5 ) );
				}
				if ( $svg || $logoData['width'] * 2 <= $width ) {
					$logoData['2x'] = $file->createThumb( $logoData['width'] * $large );
				}
			}
		} elseif ( is_array( $logo ) ) {
			// manually set logo data for non-file-uploads
			$logoData = $logo;
		} else {
			// nope
			return false;
		}

		// Render the html output!
		$attribs = [
			'alt' => $this->getMsg( 'sitetitle' )->text(),
			// Should we care? It's just a logo...
			'decoding' => 'auto',
			'width' => $logoData['width'],
			'height' => $logoData['height'],
		];

		if ( !isset( $logoData['1x'] ) && isset( $logoData['2x'] ) ) {
			// We'll allow it...
			$attribs['src'] = $logoData['2x'];
		} else {
			// Okay, we really do want a 1x otherwise. If this throws an error or
			// something because there's nothing here, GOOD.
			// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
			$attribs['src'] = $logoData['1x'];

			// Throw the rest in a srcset
			unset( $logoData['1x'], $logoData['width'], $logoData['height'] );
			$srcset = '';
			foreach ( $logoData as $res => $path ) {
				if ( $srcset != '' ) {
					$srcset .= ', ';
				}
				$srcset .= $path . ' ' . $res;
			}

			if ( $srcset !== '' ) {
				$attribs['srcset'] = $srcset;
			}
		}

		return Html::element( 'img', $attribs );
	}
}
