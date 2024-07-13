<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Assert\Assert;
use Wikimedia\Assert\UnreachableException;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Tokens\NlTk;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\PipelineUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wt2Html\Frame;
use Wikimedia\Parsoid\Wt2Html\PegTokenizer;
use Wikimedia\Parsoid\Wt2Html\TokenTransformManager;

/**
 * Generic attribute expansion handler.
 */
class AttributeExpander extends TokenHandler {
	private const META_TYPE_MATCHER = '#(mw:(LanguageVariant|Transclusion|Param|Includes|Annotation/)(.*)$)#D';

	/**
	 * Used for re-tokenizing attribute strings that need to be re-expanded
	 * @var PegTokenizer
	 */
	private $tokenizer;

	/**
	 * @param TokenTransformManager $manager
	 * @param array $options
	 *  - bool inTemplate Is this being invoked while processing a template?
	 *  - bool expandTemplates Should we expand templates encountered here?
	 *  - bool standalone Is this AttributeExpander used as part of a pipeline
	 *                    or is it being used standalone as an utility class?
	 */
	public function __construct( TokenTransformManager $manager, array $options ) {
		parent::__construct( $manager, $options );
		$this->tokenizer = new PegTokenizer( $manager->getEnv() );
	}

	private static function nlTkIndex(
		bool $nlTkOkay, array $tokens, bool $atTopLevel
	): int {
		// Moving this check here since it makes the
		// callsite cleaner and simpler.
		if ( $nlTkOkay ) {
			return -1;
		}

		// Check if we have a newline token in the attribute key/value token stream.
		// However, newlines are acceptable inside a <*include*>..</*include*> directive
		// since they are stripped out.
		//
		// let includeRE = !atTopLevel ?
		//     /(?:^|\s)mw:Includes\/NoInclude(\/.*)?(?:\s|$)/ :
		//     /(?:^|\s)mw:Includes\/(?:Only)?Include(?:Only)?(\/.*)?(?:\s|$)/;
		//
		// SSS FIXME: We cannot support this usage for <*include*> directives currently
		// since they don't go through template encapsulation and don't have a data-mw
		// format with "wt" and "transclusion" parts that we can use to just track bits
		// of wikitext that don't have a DOM representation.
		//
		// So, for now, we just suppress all newlines contained within these directives.
		$includeRE = '#(?:^|\s)mw:Includes/(?:No|Only)?Include(?:Only)?(/.*)?(?:\s|$)#D';
		$inInclude = false;
		foreach ( $tokens as $i => $t ) {
			if ( $t instanceof SelfclosingTagTk ) {
				$type = $t->getAttributeV( 'typeof' );
				$typeMatch = [];
				if ( $type && preg_match( $includeRE, $type, $typeMatch, PREG_UNMATCHED_AS_NULL ) ) {
					$inInclude = !str_ends_with( $typeMatch[1] ?? '', '/End' );
				}
			} elseif ( !$inInclude && $t instanceof NlTk ) {
				// newline token outside <*include*>
				return $i;
			}
		}

		return -1;
	}

	private static function splitTokens(
		Frame $frame, Token $token, int $nlTkPos, array $tokens, bool $wrapTemplates
	): array {
		$preNLBuf = [];
		$postNLBuf = null;
		$startMeta = null;
		$metaTokens = null;

		// Split the token array around the first newline token.
		$startMetaIndex = null;
		foreach ( $tokens as $i => $t ) {
			if ( $i === $nlTkPos ) {
				// split here!
				$postNLBuf = array_slice( $tokens, $i );
				break;
			} else {
				if ( $wrapTemplates && $t instanceof SelfclosingTagTk ) {
					$type = $t->getAttributeV( 'typeof' );
					// We are interested in the last start meta tag.
					// Everything before it is assumed to be closed.
					$typeMatch = [];
					if ( $type &&
						preg_match( self::META_TYPE_MATCHER, $type, $typeMatch ) &&
						!str_ends_with( $typeMatch[1], '/End' )
					) {
						$startMeta = $t;
						$startMetaIndex = $i;
					}
				}

				// Use $i to make code robust if $tokens were not continugous
				$preNLBuf[$i] = $t;
			}
		}

		// Clear $startMeta from $preNLBuf - setting to '' is sufficient.
		if ( $startMeta ) {
			$preNLBuf[$startMetaIndex] = '';
		}

		// We split the token into pieces.
		// Since we no longer know where this token now ends tsr-wise,
		// set tsr->end to null
		$token->dataParsoid->tsr->end = null;

		if ( $startMeta ) {
			// Support template wrapping with the following steps:
			// - Hoist the transclusion start-meta from the first line
			//   to before the token.
			// - Update the start-meta tsr to that of the token.
			// - Record the wikitext between the token and the transclusion
			//   as an unwrappedWT data-parsoid attribute of the start-meta.
			$dp = $startMeta->dataParsoid;
			$dp->unwrappedWT = substr( $frame->getSrcText(), $token->dataParsoid->tsr->start,
				$dp->tsr->start - $token->dataParsoid->tsr->start );

			// unwrappedWT will be added to the data-mw.parts array which makes
			// this a multi-template-content-block.
			// Record the first wikitext node of this block (required by html->wt serialization)

			// FIXME spec-compliant values would be upper-case, this is just a workaround
			// for current PHP DOM implementation and could be removed in the future
			$tokenName = mb_strtoupper( $token->getName() );

			$dp->firstWikitextNode = isset( $token->dataParsoid->stx ) ?
				$tokenName . '_' . $token->dataParsoid->stx : $tokenName;

			// Update tsr->start only. Unless the end-meta token is moved as well,
			// updating tsr->end can introduce bugs in cases like:
			//
			//   {|
			//   |{{singlechart|Australia|93|artist=Madonna|album=Girls Gone Wild}}|x
			//   |}
			//
			// which can then cause dirty diffs (the "|" before the x gets dropped).
			$dp->tsr->start = $token->dataParsoid->tsr->start;
			$metaTokens = [ $startMeta ];

			return [ 'metaTokens' => $metaTokens, 'preNLBuf' => $preNLBuf, 'postNLBuf' => $postNLBuf ];
		} else {
			return [ 'metaTokens' => [], 'preNLBuf' => $tokens, 'postNLBuf' => [] ];
		}
	}

	/**
	 * This helper method strips all meta tags introduced by
	 * transclusions, etc. and returns the content.
	 *
	 * @param Env $env
	 * @param array $tokens
	 * @param bool $wrapTemplates
	 * @return array
	 */
	private static function stripMetaTags(
		Env $env, array $tokens, bool $wrapTemplates
	): array {
		$buf = [];
		$hasGeneratedContent = false;
		$annotationType = [];

		foreach ( $tokens as $t ) {
			if ( $t instanceof TagTk || $t instanceof SelfclosingTagTk ) {
				// Take advantage of this iteration of `tokens` to seek out
				// document fragments.  They're an indication that an attribute
				// value wasn't present as literal text in the input and the
				// token should be annotated with "mw:ExpandedAttrs".
				if ( TokenUtils::hasDOMFragmentType( $t ) ) {
					$hasGeneratedContent = true;
				}

				if ( $wrapTemplates ) {
					// Strip all meta tags.
					$type = $t->getAttributeV( 'typeof' );
					$typeMatch = [];
					if ( $type && preg_match( self::META_TYPE_MATCHER, $type, $typeMatch ) ) {
						if ( !str_ends_with( $typeMatch[1], '/End' ) ) {
							$hasGeneratedContent = true;
						}
						$groups = [];
						if ( preg_match( WTUtils::ANNOTATION_META_TYPE_REGEXP, $type, $groups ) ) {
							$annotationType[] = $groups[1];
						}
					} else {
						$buf[] = $t;
						continue;
					}
				}

				if ( $t->getName() !== 'meta' ) {
					// Dont strip token if it is not a meta-tag
					$buf[] = $t;
				}
			} else {
				$buf[] = $t;
			}
		}

		return [
			'hasGeneratedContent' => $hasGeneratedContent,
			'annotationType' => $annotationType,
			'value' => $buf
		];
	}

	/**
	 * @param mixed $a
	 * @return mixed
	 */
	private static function tplToksToString( $a ) {
		if ( !is_array( $a ) ) {
			return $a;
		}
		$ret = [];
		foreach ( $a as $t ) {
			$ret[] = TokenUtils::isTemplateToken( $t ) ? $t->dataParsoid->src : $t;
		}
		return $ret;
	}

	/**
	 * Callback for attribute expansion in AttributeTransformManager
	 * @param Token $token
	 * @param KV[] $expandedAttrs
	 * @return TokenHandlerResult
	 */
	private function buildExpandedAttrs( Token $token, array $expandedAttrs ) {
		// If we're not in a template, we'll be doing template wrapping in dom
		// post-processing (same conditional there), so take care of meta markers
		// found while processing tokens.
		$wrapTemplates = !$this->options['inTemplate'];
		$env = $this->manager->getEnv();
		$metaTokens = [];
		$postNLToks = [];
		$tmpDataMW = null;
		$oldAttrs = $token->attribs;
		// Build newAttrs lazily (on-demand) to avoid creating
		// objects in the common case where nothing of significance
		// happens in this code.
		$newAttrs = null;
		$nlTkPos = -1;
		$nlTkOkay = TokenUtils::isHTMLTag( $token ) || !TokenUtils::isTableTag( $token );
		$annotationTypes = [];

		// Identify attributes that were generated in full or in part using templates
		foreach ( $oldAttrs as $i => $oldA ) {
			$expandedA = $expandedAttrs[$i];

			// Preserve the key and value source, if available.
			// But, if 'oldA' wasn't cloned, expandedA will be the same as 'oldA'.
			if ( $oldA !== $expandedA ) {
				$expandedA->ksrc = $oldA->ksrc;
				$expandedA->vsrc = $oldA->vsrc;
				$expandedA->srcOffsets = $oldA->srcOffsets;
			}

			// Deal with two template-expansion scenarios for the attribute key (not value)
			//
			// 1. We have a template that generates multiple attributes of this token
			//    as well as content after the token.
			//    Ex: infobox templates from aircraft, ship, and other pages
			//        See enwiki:Boeing_757
			//
			//    - Split the expanded tokens into multiple lines.
			//    - Expanded attributes associated with the token are retained in the
			//      first line before a NlTk.
			//    - Content tokens after the NlTk are moved to subsequent lines.
			//    - The meta tags are hoisted before the original token to make sure
			//      that the entire token and following content is encapsulated as a unit.
			//
			// 2. We have a template that only generates multiple attributes of this
			//    token. In that case, we strip all template meta tags from the expanded
			//    tokens and assign it a mw:ExpandedAttrs type with orig/expanded
			//    values in data-mw.
			//
			// Reparse-KV-string scenario with templated attributes:
			// -----------------------------------------------------
			// In either scenario above, we need additional special handling if the
			// template generates one or more k=v style strings:
			//    <div {{1x|1=style='color:red''}}></div>
			//    <div {{1x|1=style='color:red' title='boo'}}></div>
			//
			// Real use case: Template {{ligne grise}} on frwp.
			//
			// To support this, we utilize the following hack. If we got a string of the
			// form "k=v" and our orig-v was "", we convert the token array to a string
			// and retokenize it to extract one or more attributes.
			//
			// But, we won't support scenarios like this:
			//   {| title={{1x|1='name' style='color:red;'\n|-\n|foo}}\n|}
			// Here, part of one attribute and additional complete attribute strings
			// need reparsing, and that isn't a use case that is worth more complexity here.
			//
			// FIXME:
			// ------
			// 1. It is not possible for multiple instances of scenario 1 to be triggered
			//    for the same token. So, I am not bothering trying to test and deal with it.
			//
			// 2. We trigger the Reparse-KV-string scenario only for attribute keys,
			//    since it isn't possible for attribute values to require this reparsing.
			//    However, it is possible to come up with scenarios where a template
			//    returns the value for one attribute and additional k=v strings for newer
			//    attributes. We don't support that scenario, but don't even test for it.
			//
			// Reparse-KV-string scenario with non-string attributes:
			// ------------------------------------------------------
			// This is only going to be the case with table wikitext that has special syntax
			// for attribute strings.
			//
			// {| <div>a</div> style='border:1px solid black;'
			// |- <div>b</div> style='border:1px dotted blue;'
			// | <div>c</div> style='color:red;'
			// |}
			//
			// In wikitext like the above, the PEG tokenizer doesn't recognize these as
			// valid attributes (the templated attribute scenario is a special case) and
			// orig-v will be "". So, the same strategy as above is applied here as well.

			$expandedK = $origK = $expandedA->k;
			$expandedV = $origV = $expandedA->v;
			$updatedK = null;
			$updatedV = null;
			$reparsedKV = false;
			$keyUsesMixedAttrContentTpl = false;
			$valUsesMixedAttrContentTpl = false;

			if ( $expandedK ) {
				// FIXME: We should get rid of these array/string/non-string checks
				// and probably use appropriately-named flags to convey type information.
				if ( is_array( $oldA->k ) ) {
					if ( !is_array( $expandedK ) ) {
						throw new UnreachableException( "expandedK: expected array. Found: " .
							PHPUtils::jsonEncode( $expandedK ) );
					}

					$nlTkPos = self::nlTkIndex( $nlTkOkay, $expandedK, $wrapTemplates );
					if ( $nlTkPos !== -1 ) {
						// Scenario 1 from the documentation comment above.
						$keyUsesMixedAttrContentTpl = true;
						$updatedK = self::splitTokens(
							$this->manager->getFrame(), $token, $nlTkPos, $expandedK, $wrapTemplates
						);
						$expandedK = $updatedK['preNLBuf'];
						$postNLToks = $updatedK['postNLBuf'];
						$metaTokens = $updatedK['metaTokens'];
						// We split up this attribute's key into pieces.
						if ( $expandedA->srcOffsets->key ) {
							$expandedA->srcOffsets->key->end = null;
						}
					} else {
						// Maybe scenario 2 from the documentation comment above.
						$updatedK = self::stripMetaTags( $env, $expandedK, $wrapTemplates );
						PHPUtils::pushArray( $annotationTypes, $updatedK['annotationType'] );
						$expandedK = $updatedK['value'];
					}

					$expandedA->k = $expandedK;

					// Check if we need to deal with the Reparse-KV-string scenario.
					// (See documentation comment above.)
					//
					// Don't incorrectly reparse the kv string for parser functions.
					// Ex: "#ifexpr" parser function expects the "=" equality operator.
					// We encounter those in "standalone" mode (used to expand
					// templated template targets).
					if ( $expandedA->v === '' && empty( $this->options['standalone'] ) ) {
						// Extract a parsable string from the token array.
						// Trim whitespace to ensure tokenizer isn't tripped up
						// by the presence of unnecessary whitespace.
						$kStr = trim( TokenUtils::tokensToString( $expandedK, false, [
							// These tokens haven't been expanded to DOM yet
							// so unpacking them here is justifiable
							'unpackDOMFragments' => true,
							'env' => $env
						] ) );
						$rule = $nlTkOkay ? 'generic_newline_attributes' : 'table_attributes';
						$kvs = str_contains( $kStr, '=' ) ?
							$this->tokenizer->tokenizeAs( $kStr, $rule, /* sol */true ) : null;
						if ( $kvs ) {
							// At this point, templates should have been expanded.
							// Returning a template token here probably means that
							// when we just converted to string and reparsed, we failed
							// to expand the template. This can be particularly bad
							// when we make iterative calls to expand template names.
							// So, give up template expansion and convert them to strings.
							foreach ( $kvs as $kv ) {
								$kv->k = self::tplToksToString( $kv->k );
								$kv->v = self::tplToksToString( $kv->v );

								// $kStr is based on running tokensToString on $expandedK.
								// So, $kStr might have dropped HTML tags, etc. Given that,
								// we can no longer reliably compute offsets for these
								// new key/value pairs. We could try to be more smart here,
								// but it is not worth the complexity.
								$kv->srcOffsets = null;
							}
							// SSS FIXME: Collect all keys here, not just the first key
							// i.e. in a string like {{1x|1=id='v1' title='foo' style='..'}}
							// that string is setting attributes for [id, title, style], not just id.
							//
							// That requires the ability for the data-mw.attribs[i].txt to be an array.
							// However, the spec at [[mw:Specs/HTML#Generated_attributes_of_HTML_tags]]
							// says:
							//
							//    "This spec also assumes that a template can only
							//     generate one attribute rather than multiple attributes."
							//
							// So, revision of the spec is another FIXME at which point this code can
							// be updated to reflect the revised spec.
							$expandedK = $kvs[0]->k;
							$reparsedKV = true;
							if ( !$newAttrs ) {
								$newAttrs = $i === 0 ? [] : array_slice( $expandedAttrs, 0, $i );
							}
							PHPUtils::pushArray( $newAttrs, $kvs );
						}
					}
				}

				// We have a potentially expanded value.
				// Check if the value came from a template/extension expansion.
				if ( is_string( $expandedK ) && !str_starts_with( $expandedK, 'mw:' )
					&& is_array( $oldA->v )
				) {
					$nlTkPos = self::nlTkIndex( $nlTkOkay, $expandedV, $wrapTemplates );
					if ( $nlTkPos !== -1 ) {
						// Scenario 1 from the documentation comment above.
						$valUsesMixedAttrContentTpl = true;
						$updatedV = self::splitTokens(
							$this->manager->getFrame(), $token, $nlTkPos,
							$expandedV, $wrapTemplates
						);
						$expandedV = $updatedV['preNLBuf'];
						$postNLToks = $updatedV['postNLBuf'];
						$metaTokens = $updatedV['metaTokens'];
						// We split up this attribute's value into pieces.
						if ( $expandedA->srcOffsets->value ) {
							$expandedA->srcOffsets->value->end = null;
						}
					} else {
						// Maybe scenario 2 from the documentation comment above.
						$updatedV = self::stripMetaTags( $env, $expandedV, $wrapTemplates );
						PHPUtils::pushArray( $annotationTypes, $updatedV['annotationType'] );
						$expandedV = $updatedV['value'];
					}
					$expandedA->v = $expandedV;
				}

				// Update data-mw to account for templated attributes.
				// For editability, set HTML property.
				if ( !empty( $updatedK['hasGeneratedContent'] ) ||
					!empty( $updatedV['hasGeneratedContent'] ) ||
					( $reparsedKV && count( $metaTokens ) > 0 )
				) {
					$key = TokenUtils::tokensToString( $expandedK );
					if ( !$tmpDataMW ) {
						$tmpDataMW = [];
					}

					// For the $(key|val)UsesMixedAttrContentTpl checks below,
					// it is incorrect to assign the HTML for the original wikitext
					// string since the content part will get duplicated in both
					// this data-mw and in the actual body of the table (for example)
					// and cause bugs like T249740.
					//
					// So, in this case, we assign just the key/value part of the HTML
					// ($expandedA->k or $expandedA->v), but we mark it uneditable
					// because we cannot really edit just the key/value of the attribute
					// on its own because it is only a part of the template's output.
					if ( $reparsedKV ) {
						// If we encountered a reparse-KV-string scenario,
						// we set the value's HTML to [] since we can edit
						// the transclusion either via the key's HTML or the
						// value's HTML, but not both.
						$keyHTML = $keyUsesMixedAttrContentTpl ? $expandedA->k : $origK;
						$valHTML = [];
					} else {
						Assert::invariant( !$keyUsesMixedAttrContentTpl,
							"If reparseKV was false, and we had a mixed attr-content template, " .
							"we should have landed in the valUsesMixedAttrContentTpl codepath." );
						$keyHTML = empty( $updatedK['hasGeneratedContent'] ) ? null : $origK;
						$valHTML = $valUsesMixedAttrContentTpl ? $expandedA->v : $origV;
					}

					// FIXME: Ideally we would have called them ktext, khtml, vhtml
					// since in the serialized data-mw, the "k" and "v" key strings are dropped.
					//    [{ "ktxt":..., "khtml":... }, { "vhtml":... }]
					//         is clearer and less confusing than
					//    [{ "txt":..., "html":... }, { "html":... }]
					$tmpDataMW[$key] = [
						'k' => [ 'txt' => $key, 'srcOffsets' => $expandedA->srcOffsets->key ?? null ],
						// FIXME: Why is 'txt' missing? Why are we not checking for [] ?
						'v' => [ 'html' => $valHTML, 'srcOffsets' => $expandedA->srcOffsets->value ?? null ]
					];

					if ( $keyHTML !== null ) {
						$tmpDataMW[$key]['k']['html'] = $keyHTML;
					}
					if ( $keyUsesMixedAttrContentTpl ) {
						$tmpDataMW[$key]['k']['uneditable'] = true;
					}
					if ( $valUsesMixedAttrContentTpl ) {
						$tmpDataMW[$key]['v']['uneditable'] = true;
					}
				}
			}

			// Update newAttrs
			if ( $newAttrs && !$reparsedKV ) {
				$newAttrs[] = $expandedA;
			}
		}

		$token->attribs = $newAttrs ?? $expandedAttrs;

		// If the token already has an about, it already has transclusion/extension
		// wrapping. No need to record information about templated attributes in addition.
		//
		// FIXME: If there is a real use case for extension attributes getting templated,
		// this check can be relaxed to allow that.
		// https://gerrit.wikimedia.org/r/#/c/65575 has some reference code that can be used then.

		if ( !$token->getAttributeV( 'about' ) && $tmpDataMW && count( $tmpDataMW ) > 0 ) {
			// Flatten k-v pairs.
			$vals = [];
			foreach ( $tmpDataMW as $obj ) {
				$vals[] = $obj['k'];
				$vals[] = $obj['v'];
			}

			// Clone the vals since they'll be passed to another pipeline
			// for expanding, which may destructively mutate them in the process.
			//
			// This is a problem since subsequent handlers to the
			// AttributeExpander may interact with the original tokens still
			// present as attributes of `token`.
			//
			// For example, while treebuilding, the object holding dataParsoid
			// of a token is reused as the data-parsoid attribute of the
			// corresonding node.  Thus, when we get to the DOM cleanup pass,
			// unsetting properties changes the token as well.  This was
			// the issue when an "href" was expanded and then the
			// ExternalLinkHandler tried to call tokensToString on it,
			// resulting in a transcluded entity missing its src (which, by the way,
			// had already been clobered by WrapTemplates, similar to T214241).
			//
			// The general principle here being, don't share tokens between
			// pipelines.
			$vals = Utils::clone( $vals );

			// Expand all token arrays to DOM.
			$eVals = PipelineUtils::expandAttrValuesToDOM(
				$this->env, $this->manager->getFrame(), $vals,
				$this->options['expandTemplates'],
				$this->options['inTemplate']
			);

			// Rebuild flattened k-v pairs.
			$expAttrs = [];
			for ( $j = 0;  $j < count( $eVals );  $j += 2 ) {
				$expAttrs[] = [ $eVals[$j], $eVals[$j + 1] ];
			}

			if ( $token->getName() === 'template' ) {
				// Don't add Parsoid about, typeof, data-mw attributes here since
				// we won't be able to distinguish between Parsoid-added attributes
				// and actual template attributes in cases like:
				//   {{some-tpl|about=#mwt1|typeof=mw:Transclusion}}
				// In both cases, we will encounter a template token that looks like:
				//   { ... "attribs":[{"k":"about","v":"#mwt1"},{"k":"typeof","v":"mw:Transclusion"}] .. }
				// So, record these in the tmp attribute for the template hander
				// to retrieve and process.
				$token->dataParsoid->getTemp()->templatedAttribs = $expAttrs;
			} else {
				// Mark token as having expanded attrs.
				$token->addAttribute( 'about', $this->env->newAboutId() );
				$token->addSpaceSeparatedAttribute( 'typeof', 'mw:ExpandedAttrs' );
				foreach ( $annotationTypes as $annotationType ) {
					$token->addSpaceSeparatedAttribute( 'typeof', 'mw:Annotation/' . $annotationType );
				}
				$token->addAttribute( 'data-mw', PHPUtils::jsonEncode( [ 'attribs' => $expAttrs ] ) );
			}
		}

		return new TokenHandlerResult(
			array_merge( $metaTokens, [ $token ], $postNLToks )
		);
	}

	/**
	 * Processes any attribute keys and values that are not simple strings.
	 * (Ex: Templated styles)
	 *
	 * @param Token $token Token whose attrs being expanded.
	 * @return TokenHandlerResult
	 */
	public function processComplexAttributes( Token $token ): TokenHandlerResult {
		$atm = new AttributeTransformManager( $this->manager->getFrame(), [
			'expandTemplates' => $this->options['expandTemplates'],
			'inTemplate' => $this->options['inTemplate']
		] );
		return $this->buildExpandedAttrs( $token, $atm->process( $token->attribs ) );
	}

	/**
	 * Token handler.
	 *
	 * For tokens that might have complex attributes, this handler
	 * processes / expands them.
	 * (Ex: Templated styles)
	 *
	 * @param Token|string $token Token whose attrs being expanded.
	 * @return TokenHandlerResult|null
	 */
	public function onAny( $token ): ?TokenHandlerResult {
		if (
			!( $token instanceof TagTk || $token instanceof SelfclosingTagTk ) ||
			!count( $token->attribs )
		) {
			return null;
		}

		$name = $token->getName();
		$property = $token->getAttributeV( 'property' ) ?? '';
		$typeOf = $token->getAttributeV( 'typeof' ) ?? '';

		if (
			// Do not process dom-fragment tokens: a separate handler deals with them.
			$name === 'mw:dom-fragment-token' ||
			(
				$name === 'meta' &&
				(
					// Parsoid generated metas don't need expansion
					preg_match( '/mw:(Placeholder|Transclusion|Param|Includes)/', $typeOf ) ||
					// The TemplateHandler runs before the AttributeExpander and
					// magic words masquerading as templates may themselves be
					// templated (as in templated template names).
					// See TemplateHandler::processSpecialMagicWord()
					// So, we may see page properties that have already been
					// expanded and annotated with mw:ExpandedAttrs.  We return
					// early to avoid the assertion below, at the expense of
					// perhaps not catching other cases where tokens are passed
					// through here doubly by mistake.
					( preg_match( '/mw:(PageProp)/', $property ) &&
						str_contains( $typeOf, 'mw:ExpandedAttrs' ) )
				)
			)
		) {
			return null;
		}

		Assert::invariant(
			!str_contains( $typeOf, 'mw:ExpandedAttrs' ),
			"Expanding an already expanded token, that's a no-no."
		);

		return $this->processComplexAttributes( $token );
	}
}
