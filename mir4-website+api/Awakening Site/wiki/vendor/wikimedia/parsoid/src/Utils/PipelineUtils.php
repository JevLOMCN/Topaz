<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Utils;

use Wikimedia\Assert\Assert;
use Wikimedia\Assert\UnreachableException;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\NodeList;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\NodeData\TempData;
use Wikimedia\Parsoid\Tokens\CommentTk;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\SourceRange;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Wt2Html\Frame;

/**
 * This file contains parsing pipeline related utilities.
 */
class PipelineUtils {
	/**
	 * Creates a dom-fragment-token for processing 'content' (an array of tokens)
	 * in its own subpipeline all the way to DOM. These tokens will be processed
	 * by their own handler (DOMFragmentBuilder) in the last stage of the async
	 * pipeline.
	 *
	 * srcOffsets should always be provided to process top-level page content in a
	 * subpipeline. Without it, DSR computation and template wrapping cannot be done
	 * in the subpipeline. While unpackDOMFragment can do this on unwrapping, that can
	 * be a bit fragile and makes dom-fragments a leaky abstraction by leaking subpipeline
	 * processing into the top-level pipeline.
	 *
	 * @param Token[]|string $content The array of tokens to process.
	 * @param SourceRange $srcOffsets Wikitext source offsets (start/end) of these tokens.
	 * @param array $opts Parsing options.
	 *    - Token token The token that generated the content.
	 *    - bool  inlineContext Is this DOM fragment used in an inline context?
	 * @return SelfclosingTagTk
	 */
	public static function getDOMFragmentToken(
		$content, SourceRange $srcOffsets, array $opts = []
	): SelfclosingTagTk {
		$token = $opts['token'];
		return new SelfclosingTagTk( 'mw:dom-fragment-token', [
			new KV( 'contextTok', $token, $token->dataParsoid->tsr->expandTsrV() ),
			new KV( 'content', $content, $srcOffsets->expandTsrV() ),
			new KV( 'inlineContext', ( $opts['inlineContext'] ?? false ) ? "1" : "0" ),
			new KV( 'inPHPBlock', ( $opts['inPHPBlock'] ?? false ) ? "1" : "0" ),
		] );
	}

	/**
	 * Processes content (wikitext, array of tokens, whatever) in its own
	 * pipeline based on options.
	 *
	 * @param Env $env The environment/context for the expansion.
	 * @param Frame $frame
	 *    The parent frame within which the expansion is taking place.
	 *    Used for template expansion and source text tracking.
	 * @param string|Token|Token[] $content
	 *    This could be wikitext or single token or an array of tokens.
	 *    How this content is processed depends on what kind of pipeline
	 *    is constructed specified by opts.
	 * @param array $opts
	 *    Processing options that specify pipeline-type, opts, and callbacks.
	 *    - string pipelineType
	 *    - array  pipelineOpts
	 *    - array  tplArgs - if set, defines parameters for the child frame
	 *      - string tplArgs['name']
	 *      - KV[]   tplArgs['attribs']
	 *    - string srcText - if set, defines the source text for the expansion
	 *    - SourceRange  srcOffsets - if set, defines the range within the
	 *          source text that $content corresponds to
	 *    - bool   sol Whether tokens should be processed in start-of-line context.
	 * @return Token[]|DocumentFragment (depending on pipeline type)
	 */
	public static function processContentInPipeline(
		Env $env, Frame $frame, $content, array $opts
	) {
		// Build a pipeline
		$pipeline = $env->getPipelineFactory()->getPipeline(
			$opts['pipelineType'],
			$opts['pipelineOpts']
		);

		$pipeline->init( [
			'toplevel' => false,
			'frame' => $frame,
			'tplArgs' => $opts['tplArgs'] ?? null,
			'srcText' => $opts['srcText'] ?? $frame->getSrcText(),
			'srcOffsets' => $opts['srcOffsets'] ?? null,
		] );

		// Off the starting block ... ready, set, go!
		return $pipeline->parse( $content, [ 'sol' => $opts['sol'] ] );
	}

	/**
	 * Expands value all the way to DOM.
	 *
	 * @param Env $env
	 *    The environment/context for the expansion.
	 * @param Frame $frame
	 *    The parent frame within which the expansion is taking place.
	 *    Used for template expansion and source text tracking.
	 * @param array $v
	 *    The value to process.
	 *    The value is expected to be an associative array with a "html" property.
	 *    The html property is expanded to DOM only if it is an array (of tokens).
	 *    Non-arrays are passed back unexpanded.
	 * @param bool $expandTemplates
	 *    Should any templates encountered here be expanded
	 *    (usually false for nested templates since they are never directly editable).
	 * @param bool $inTemplate
	 *    Unexpanded templates can occur in the content of extension tags.
	 * @return array
	 */
	public static function expandAttrValueToDOM(
		Env $env, Frame $frame, array $v, bool $expandTemplates, bool $inTemplate
	): array {
		if ( is_array( $v['html'] ?? null ) ) {
			// Set up pipeline options
			$opts = [
				'pipelineType' => 'tokens/x-mediawiki/expanded',
				'pipelineOpts' => [
					'attrExpansion' => true,
					'inlineContext' => true,
					'expandTemplates' => $expandTemplates,
					'inTemplate' => $inTemplate
				],
				'srcOffsets' => $v['srcOffsets'],
				'sol' => true
			];
			$content = array_merge( $v['html'], [ new EOFTk() ] );
			$domFragment = self::processContentInPipeline(
				$env, $frame, $content, $opts
			);
			// Since we aren't at the top level, data attrs
			// were not applied in cleanup.  However, tmp
			// was stripped.
			$v['html'] = ContentUtils::ppToXML(
				$domFragment, [ 'innerXML' => true ]
			);
		}
		// Remove srcOffsets after value is expanded, so they don't show
		// up in the output data-mw attribute
		unset( $v['srcOffsets'] );
		return $v;
	}

	/**
	 * @param Env $env
	 *    The environment/context for the expansion.
	 * @param Frame $frame
	 *    The parent frame within which the expansion is taking place.
	 *    Used for template expansion and source text tracking.
	 * @param array $vals
	 *    Array of values to expand.
	 *    Non-array elements of $vals are passed back unmodified.
	 *    If an array element, it is expected to be an associative array with a "html" property.
	 *    The html property is expanded to DOM only if it is an array (of tokens).
	 * @param bool $expandTemplates
	 *    Should any templates encountered here be expanded
	 *    (usually false for nested templates since they are never directly editable).
	 * @param bool $inTemplate
	 *    Unexpanded templates can occur in the content of extension tags.
	 * @return array
	 */
	public static function expandAttrValuesToDOM(
		Env $env, $frame, array $vals, bool $expandTemplates, bool $inTemplate
	): array {
		$ret = [];
		foreach ( $vals as $v ) {
			$ret[] = self::expandAttrValueToDOM( $env, $frame, $v, $expandTemplates, $inTemplate );
		}
		return $ret;
	}

	/**
	 * Convert a DOM node to a token. The node comes from a DOM whose data attributes
	 * are stored outside the DOM.
	 *
	 * @param Element $node
	 * @param string[] $attrs
	 * @return array
	 */
	private static function domAttrsToTagAttrs( Element $node, array $attrs ): array {
		$out = [];
		foreach ( $attrs as $name => $value ) {
			if ( $name !== DOMDataUtils::DATA_OBJECT_ATTR_NAME ) {
				$out[] = new KV( $name, $value );
			}
		}
		if ( DOMDataUtils::validDataMw( $node ) ) {
			$out[] = new KV( 'data-mw', PHPUtils::jsonEncode( DOMDataUtils::getDataMw( $node ) ) );
		}
		return [ 'attrs' => $out, 'dataAttrs' => DOMDataUtils::getDataParsoid( $node ) ];
	}

	/**
	 * Convert a DOM to tokens. Data attributes for nodes are stored outside the DOM.
	 *
	 * @param Node $node The root of the DOM tree to convert to tokens
	 * @param Token[] $tokBuf This is where the tokens get stored
	 * @return array
	 */
	private static function convertDOMtoTokens( Node $node, array $tokBuf ): array {
		if ( $node instanceof Element ) {
			$nodeName = DOMCompat::nodeName( $node );
			$attrInfo = self::domAttrsToTagAttrs( $node, DOMUtils::attributes( $node ) );

			if ( Utils::isVoidElement( $nodeName ) ) {
				$tokBuf[] = new SelfclosingTagTk( $nodeName, $attrInfo['attrs'], $attrInfo['dataAttrs'] );
			} else {
				$tokBuf[] = new TagTk( $nodeName, $attrInfo['attrs'], $attrInfo['dataAttrs'] );
				for ( $child = $node->firstChild;  $child;  $child = $child->nextSibling ) {
					$tokBuf = self::convertDOMtoTokens( $child, $tokBuf );
				}
				$endTag = new EndTagTk( $nodeName );
				// Keep stx parity
				if ( WTUtils::isLiteralHTMLNode( $node ) ) {
					$endTag->dataParsoid->stx = 'html';
				}
				$tokBuf[] = $endTag;
			}
		} elseif ( $node instanceof Text ) {
			PHPUtils::pushArray( $tokBuf, TokenUtils::newlinesToNlTks( $node->nodeValue ) );
		} elseif ( $node instanceof Comment ) {
			$tokBuf[] = new CommentTk( $node->nodeValue );
		} else {
			// getWrapperTokens calls convertDOMToTokens with a Element
			// and children of dom elements are always text/comment/elements
			// which are all covered above.
			throw new UnreachableException( "Should never get here!" );
		}

		return $tokBuf;
	}

	/**
	 * Get tokens representing a DOM forest (from transclusions, extensions,
	 * whatever that were generated as part of a separate processing pipeline)
	 * in the token stream. These tokens will tunnel the subtree through the
	 * token processing while preserving token stream semantics as if
	 * the DOM had been converted to tokens.
	 *
	 * @param DocumentFragment $domFragment List of DOM nodes that need to be tunneled through.
	 * @param array $opts
	 * @see encapsulateExpansionHTML's doc. for more info about these options.
	 * @return Token[] List of token representatives.
	 */
	private static function getWrapperTokens(
		DocumentFragment $domFragment, array $opts
	): array {
		if ( !$domFragment->hasChildNodes() ) {
			return [ new TagTk( 'span' ), new EndTagTk( 'span' ) ];
		}

		$node = $domFragment->firstChild;

		// Do we represent this with inline or block elements?
		// This is to ensure that we get p-wrapping correct.
		//
		// * If all content is inline, we use inline-elements to represent this
		// so that this content gets swallowed into the P tag that wraps
		// adjacent inline content.
		//
		// * If any part of this is a block content, we treat extension content
		// independent of surrounding content and don't want inline content
		// here to be swallowed into a P tag that wraps adjacent inline content.
		//
		// This behavior ensures that we and clients can "drop-in" extension content
		// into the DOM without messing with fixing up paragraph tags of surrounding
		// content. It could potentially introduce minor rendering differences when
		// compared to PHP parser output, but we'll swallow it for now.
		$wrapperType = 'INLINE';
		if ( !empty( $opts['pipelineOpts']['inlineContext'] ) ) {
			// If the DOM fragment is being processed in the context where P wrapping
			// has been suppressed, we represent the DOM fragment with inline-tokens.
			//
			// FIXME(SSS): Looks like we have some "impedance mismatch" here. But, this
			// is correct in scenarios where link-content or image-captions are being
			// processed in a sub-pipeline and we don't want a <div> in the link-caption
			// to cause the <a>..</a> to get split apart.
			//
			// Filed as T49963
		} elseif ( !$opts['unpackOutput'] ) {
			// Fragments that won't be unpacked aren't amenable to inspection, since
			// the ultimate content is unknown.  For example, refs shuttle content
			// through treebuilding that ends up in the references list.
			//
			// FIXME(arlolra): Do we need a mechanism to specify content
			// categories?
		} else {
			foreach ( $domFragment->childNodes as $n ) {
				if (
					DOMUtils::isWikitextBlockNode( $n ) ||
					DOMUtils::hasBlockElementDescendant( $n )
				) {
					$wrapperType = 'BLOCK';
					break;
				}
			}
		}

		$wrapperName = null;
		if ( $wrapperType === 'BLOCK' && !DOMUtils::isWikitextBlockNode( $node ) ) {
			$wrapperName = 'div';
		} elseif ( DOMCompat::nodeName( $node ) === 'a' ) {
			// Do not use 'A' as a wrapper node because it could
			// end up getting nested inside another 'A' and the DOM
			// structure can change where the wrapper tokens are no
			// longer siblings.
			// Ex: "[http://foo.com Bad nesting [[Here]]].
			$wrapperName = 'span';
		} elseif (
			in_array( DOMCompat::nodeName( $node ), [ 'style', 'script' ], true ) &&
			( $node->nextSibling !== null )
		) {
			// <style>/<script> tags are not fostered, so if we're wrapping
			// more than a single node, they aren't a good representation for
			// the content.  It can lead to fosterable content being inserted
			// in a fosterable position after treebuilding is done, which isn't
			// roundtrippable.
			$wrapperName = 'span';
		} elseif ( !( $node instanceof Element ) ) {
			$wrapperName = 'span';
		} else {
			$wrapperName = DOMCompat::nodeName( $node );
		}

		if ( $node instanceof Element ) {
			Assert::invariant(
				// No need to look for data-mw as well.
				// Nodes that have data-mw also have data-parsoid.
				!$node->hasAttribute( 'data-parsoid' ),
				"Expected node to have its data attributes loaded" );

			$nodeData = DOMDataUtils::getNodeData( $node )->cloneNodeData();

			if ( $wrapperName !== DOMCompat::nodeName( $node ) ) {
				// Create a copy of the node without children
				$workNode = $node->ownerDocument->createElement( $wrapperName );

				// Copy over attributes
				foreach ( DOMUtils::attributes( $node ) as $name => $value ) {
					// "typeof" is ignored since it'll be removed below.
					if ( $name !== 'typeof' ) {
						$workNode->setAttribute( $name, $value );
					}
				}

				// We are applying a different wrapper.
				// So, node's data-parsoid isn't applicable.
				$nodeData->parsoid = new DataParsoid;
			} else {
				// Shallow clone since we don't want to convert the whole tree to tokens.
				$workNode = $node->cloneNode( false );

				// Reset 'tsr' since it isn't applicable. Neither is
				// any auxiliary info like 'endTSR'.
				// FIXME: The above comment is only true if we are reusing
				// DOM fragments from cache from previous revisions in
				// incremental parsing scenarios.  See T98992
				if ( isset( $nodeData->parsoid->tsr ) ) {
					$nodeData->parsoid->tsr = null;
				}
				if ( isset( $nodeData->parsoid->tmp->endTSR ) ) {
					unset( $nodeData->parsoid->tmp->endTSR );
				}

				// The "in transclusion" flag was set on the first child for template
				// wrapping in the nested pipeline, and doesn't apply to the dom
				// fragment wrapper in this pipeline.  Keeping it around can induce
				// template wrapping of a foster box if the dom fragment is found in
				// a fosterable position.
				if (
					isset( $nodeData->parsoid ) &&
					$nodeData->parsoid->getTempFlag( TempData::IN_TRANSCLUSION )
				) {
					$nodeData->parsoid->tmp->setFlag( TempData::IN_TRANSCLUSION, false );
				}
			}

			DOMDataUtils::setNodeData( $workNode, $nodeData );
		} else {
			$workNode = $node->ownerDocument->createElement( $wrapperName );
		}

		$tokens = self::convertDOMtoTokens( $workNode, [] );

		// Remove the typeof attribute from the first token.
		// It will be replaced with mw:DOMFragment.
		$tokens[0]->removeAttribute( 'typeof' );

		// Remove the about attribute from the first token.
		// We want to be able to distinguish when this wrapper was template
		// annotated.
		$tokens[0]->removeAttribute( 'about' );

		return $tokens;
	}

	/**
	 * Generates wrapper tokens for a HTML expansion -- the wrapper
	 * tokens are placeholders that adequately represent semantics
	 * of the HTML DOM for the purposes of additional token transformations
	 * that will be applied to them.
	 *
	 * @param Env $env
	 *    The active environment/context.
	 * @param Token $token
	 *    The token that generated the DOM.
	 * @param array $expansion
	 *    - string html HTML of the expansion.
	 *    - DocumentFragment domFragment Outermost nodes of the HTML.
	 * @param array $opts
	 *    - SourceRange tsr
	 *            The TSR to set on the generated tokens. This TSR is
	 *            used to compute DSR on the placeholder tokens.
	 *            The computed DSR is transferred over to the unpacked DOM
	 *            if setDSR is true (see below).
	 *    - bool  setDSR
	 *            When the DOM fragment is unpacked, this option governs
	 *            whether the DSR from the placeholder node is transferred
	 *            over to the unpacked DOM or not.
	 *            For example: Cite, reused transclusions.
	 *    - bool  fromCache
	 *    - array pipelineOpts
	 *    - bool  unpackOutput
	 *    - string wrapperName
	 * @return Token[]
	 */
	public static function encapsulateExpansionHTML(
		Env $env, Token $token, array $expansion, array $opts
	): array {
		$opts['unpackOutput'] ??= true; // Default
		// Get placeholder tokens to get our subdom through the token processing
		// stages. These will be finally unwrapped on the DOM.
		$toks = self::getWrapperTokens( $expansion['domFragment'], $opts );
		$firstWrapperToken = $toks[0];

		// Add the DOMFragment type so that we get unwrapped later.
		$fragmentType = 'mw:DOMFragment' . ( !$opts['unpackOutput'] ? '/sealed/' . $opts['wrapperName'] : '' );
		$firstWrapperToken->setAttribute( 'typeof', $fragmentType );

		// Assign the HTML fragment to the data-parsoid.html on the first wrapper token.
		$firstWrapperToken->dataParsoid->html = $expansion['html'];

		// Pass through setDSR flag
		if ( !empty( $opts['setDSR'] ) ) {
			$firstWrapperToken->dataParsoid->setTempFlag(
				TempData::SET_DSR, $opts['setDSR'] );
		}

		// Pass through fromCache flag
		if ( !empty( $opts['fromCache'] ) ) {
			$firstWrapperToken->dataParsoid->setTempFlag(
				TempData::FROM_CACHE, $opts['fromCache'] );
		}

		// Transfer the tsr.
		// The first token gets the full width, the following tokens zero width.
		$tokenTsr = $opts['tsr'] ?? $token->dataParsoid->tsr ?? null;
		if ( $tokenTsr ) {
			$firstWrapperToken->dataParsoid->tsr = $tokenTsr;
			$firstWrapperToken->dataParsoid->extTagOffsets = $token->dataParsoid->extTagOffsets ?? null;
			// XXX to investigate: if $tokenTsr->end is null, then we're losing
			// the 'hint' we'd like to provide here that this is a zero-width
			// source range.
			// ->end can be set to null by WikiLinkHandler::bailTokens()
			$endTsr = new SourceRange( $tokenTsr->end, $tokenTsr->end );
			for ( $i = 1;  $i < count( $toks );  $i++ ) {
				$toks[$i]->dataParsoid->tsr = clone $endTsr;
			}
		}

		return $toks;
	}

	private static function wrapAccum(
		Document $doc, array &$textCommentAccum
	): void {
		// Wrap accumulated nodes in a span
		$span = $doc->createElement( 'span' );
		$parentNode = $textCommentAccum[0]->parentNode;
		$parentNode->insertBefore( $span, $textCommentAccum[0] );
		foreach ( $textCommentAccum as $n ) {
			$span->appendChild( $n );
		}
		$dp = new DataParsoid;
		$dp->setTempFlag( TempData::WRAPPER );
		DOMDataUtils::setDataParsoid( $span, $dp );
		$textCommentAccum = [];
	}

	/**
	 * Wrap text and comment nodes in a node list into spans, so that all
	 * top-level nodes are elements.
	 *
	 * @param NodeList $nodes List of DOM nodes to wrap, mix of node types.
	 * @param ?Node $startAt
	 * @param ?Node $stopAt
	 */
	public static function addSpanWrappers(
		$nodes,
		?Node $startAt = null,
		?Node $stopAt = null
	): void {
		$textCommentAccum = [];
		$doc = $nodes->item( 0 )->ownerDocument;

		// Build a real array out of nodes.
		//
		// Operating directly on DOM child-nodes array
		// and manipulating them by adding span wrappers
		// changes the traversal itself
		$nodeBuf = [];
		foreach ( $nodes as $node ) {
			$nodeBuf[] = $node;
		}

		$start = ( $startAt === null );
		foreach ( $nodeBuf as $node ) {
			if ( !$start ) {
				if ( $startAt !== $node ) {
					continue;
				}
				$start = true;
			}
			if ( $node instanceof Text || $node instanceof Comment ) {
				$textCommentAccum[] = $node;
			} elseif ( count( $textCommentAccum ) ) {
				self::wrapAccum( $doc, $textCommentAccum );
			}
			if ( $node === $stopAt ) {
				break;
			}
		}

		if ( count( $textCommentAccum ) ) {
			self::wrapAccum( $doc, $textCommentAccum );
		}
	}

	/**
	 * Convert a HTML5 DOM into a mw:DOMFragment and generate appropriate
	 * tokens to insert into the token stream for further processing.
	 *
	 * The DOMPostProcessor will unpack the fragment and insert the HTML
	 * back into the DOM.
	 *
	 * @param Env $env
	 *    The active environment/context.
	 * @param Token $token
	 *    The token that generated the DOM.
	 * @param DocumentFragment $domFragment
	 *    The DOM that the token expanded to.
	 * @param array $opts
	 *    Options to be passed onto the encapsulation code
	 *    See encapsulateExpansionHTML's doc. for more info about these options.
	 * @return Token[]
	 */
	public static function tunnelDOMThroughTokens(
		Env $env, Token $token, DocumentFragment $domFragment, array $opts
	): array {
		// Get placeholder tokens to get our subdom through the token processing
		// stages. These will be finally unwrapped on the DOM.
		$expansion = self::makeExpansion( $env, $domFragment );
		return self::encapsulateExpansionHTML( $env, $token, $expansion, $opts );
	}

	public static function makeExpansion(
		Env $env, DocumentFragment $domFragment
	): array {
		$fragmentId = $env->newFragmentId();
		$env->setDOMFragment( $fragmentId, $domFragment );
		return [ 'domFragment' => $domFragment, 'html' => $fragmentId ];
	}

	private static function doExtractExpansions( Env $env, array &$expansions, Node $node ): void {
		$nodes = null;
		$expAccum = null;
		while ( $node ) {
			if ( $node instanceof Element ) {
				if ( DOMUtils::matchTypeOf( $node, '#^mw:(Transclusion$|Extension/)#' ) &&
						$node->hasAttribute( 'about' )
					) {
					$dp = DOMDataUtils::getDataParsoid( $node );
					$about = DOMCompat::getAttribute( $node, 'about' );
					$nodes = WTUtils::getAboutSiblings( $node, $about );
					$key = null;
					if ( DOMUtils::hasTypeOf( $node, 'mw:Transclusion' ) ) {
						$expAccum = $expansions['transclusions'];
						$key = $dp->src;
					} elseif ( DOMUtils::matchTypeOf( $node, '#^mw:Extension/#' ) ) {
						$expAccum = $expansions['extensions'];
						$key = $dp->src;
					} else {
						$expAccum = $expansions['media'];
						// XXX gwicke: use proper key that is not
						// source-based? This also needs to work for
						// transclusion output.
						$key = null;
					}

					if ( $key ) {
						throw new UnreachableException( 'Callsite was not ported!' );
						// FIXME: makeExpansion return type changed
						// $expAccum[$key] = self::makeExpansion( $env, $nodes );
					}

					$node = end( $nodes );
				} else {
					self::doExtractExpansions( $env, $expansions, $node->firstChild );
				}
			}
			$node = $node->nextSibling;
		}
	}

	/**
	 * Extract transclusion and extension expansions from a DOM, and return
	 * them in a structure like this:
	 *     {
	 *         transclusions: {
	 *             'key1': {
	 *                  html: 'html1',
	 *                  nodes: [<node1>, <node2>]
	 *             }
	 *         },
	 *         extensions: {
	 *             'key2': {
	 *                  html: 'html2',
	 *                  nodes: [<node1>, <node2>]
	 *             }
	 *         },
	 *         files: {
	 *             'key3': {
	 *                  html: 'html3',
	 *                  nodes: [<node1>, <node2>]
	 *             }
	 *         }
	 *     }
	 *
	 * @param Env $env
	 * @param Element $body
	 * @return array
	 */
	public static function extractExpansions( Env $env, Element $body ): array {
		$expansions = [
			'transclusions' => [],
			'extensions' => [],
			'media' => []
		];
		// Kick off the extraction
		self::doExtractExpansions( $env, $expansions, $body->firstChild );
		return $expansions;
	}

	/**
	 * Fetches output of encapsulations that return HTML from the legacy parser
	 */
	public static function fetchHTML( Env $env, string $source ): ?DocumentFragment {
		$ret = $env->getDataAccess()->parseWikitext(
			$env->getPageConfig(), $env->getMetadata(), $source
		);
		return $ret === '' ? null : DOMUtils::parseHTMLToFragment(
				$env->topLevelDoc, DOMUtils::stripPWrapper( $ret )
			);
	}
}
