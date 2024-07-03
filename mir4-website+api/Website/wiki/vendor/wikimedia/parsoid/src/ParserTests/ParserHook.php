<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\ParserTests;

use Closure;
use Error;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\WTUtils;

/**
 * See tests/parser/ParserTestParserHook.php in core.
 */
class ParserHook extends ExtensionTagHandler implements ExtensionModule {

	/** @inheritDoc */
	public function sourceToDom(
		ParsoidExtensionAPI $extApi, string $content, array $args
	): DocumentFragment {
		$extName = $extApi->extTag->getName();
		if ( $extApi->extTag->isSelfClosed() ) {
			$content = null;
		}
		switch ( $extName ) {
			case 'tag':
			case 'tåg':
				return $extApi->htmlToDom(
					"<pre>\n" .
						var_export( $content, true ) . "\n" .
						var_export( $extApi->extArgsToArray( $args ), true ) . "\n" .
					"</pre>"
				);

			case 'statictag':
				// FIXME: Choose a better DOM representation that doesn't mess with
				// newline constraints.
				return $extApi->htmlToDom( '<span />' );

			case 'asidetag':
				// T278565
				return $extApi->htmlToDom( '<aside>Some aside content</aside>' );

			case 'pwraptest':
				return $extApi->htmlToDom( '<!--CMT--><style>p{}</style>' );

			case 'spantag':
				// "Transparent" tag which wraps wikitext in a <span>;
				// useful in testing various parsoid wrapping scenarios
				// (we used to use <ref> for this)
				// NOTE: This tag disables p-wrapping and indent-pre transforms.
				return $extApi->extTagToDOM( $args, $content, [
					'wrapperTag' => 'span',
					'parseOpts' => [
						'extTag' => $extName,
						'context' => 'inline',
					],
				] );

			default:
				throw new Error( "Unexpected tag name: $extName in ParserHook" );
		}
	}

	/** @inheritDoc */
	public function processAttributeEmbeddedHTML(
		ParsoidExtensionAPI $extApi, Element $elt, Closure $proc
	): void {
		$dataMw = DOMDataUtils::getDataMw( $elt );
		if ( isset( $dataMw->body->html ) ) {
			$dataMw->body->html = $proc( $dataMw->body->html );
		}
	}

	/** @inheritDoc */
	public function domToWikitext(
		ParsoidExtensionAPI $extApi, Element $node, bool $wrapperUnmodified
	) {
		$dataMw = DOMDataUtils::getDataMw( $node );
		$extName = WTUtils::getExtTagName( $node ) ?? $dataMw->name;
		if ( $extName !== 'spantag' ) {
			return false; // use default serialization
		}
		$html2wtOpts = [
			'extName' => $extName,
			// FIXME: One-off PHP parser state leak. This needs a better solution.
			'inPHPBlock' => true
		];
		$src = '';
		if ( $wrapperUnmodified && isset( $dataMw->body->extsrc ) ) {
			$src = $dataMw->body->extsrc;
		} elseif ( isset( $dataMw->body->html ) ) {
			// First look for the extension's content in data-mw.body.html
			$src = $extApi->htmlToWikitext( $html2wtOpts, $dataMw->body->html );
		} else {
			$src = $extApi->htmlToWikitext( $html2wtOpts, DOMCompat::getInnerHTML( $node ) );
		}
		return "<$extName>" . $src . "</$extName>";
	}

	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'ParserHook',
			'tags' => [
				[ 'name' => 'tag', 'handler' => self::class ],
				[ 'name' => 'tåg', 'handler' => self::class ],
				[ 'name' => 'statictag', 'handler' => self::class ],
				[ 'name' => 'asidetag', 'handler' => self::class ],
				[ 'name' => 'pwraptest', 'handler' => self::class ],
				[ 'name' => 'spantag', 'handler' => self::class,
				  'options' => [
					  'wt2html' => [
						  'embedsHTMLInAttributes' => true,
					  ],
					  'outputHasCoreMwDomSpecMarkup' => true,
				  ],
				],
			],
			'domProcessors' => [
				ParserHookProcessor::class
			]
		];
	}
}
