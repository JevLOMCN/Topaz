<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\NodeData\ParamInfo;
use Wikimedia\Parsoid\NodeData\TemplateInfo;
use Wikimedia\Parsoid\Tokens\CommentTk;
use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Tokens\NlTk;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\SourceRange;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PipelineUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Wt2Html\Frame;

/**
 * A helper class for TemplateHandler that encapsulates template-like syntax
 * with the appropriate meta tags, adding argument info data.
 */
class TemplateEncapsulator {
	/** @var Env */
	private $env;
	/** @var Frame */
	private $frame;
	/** @var string */
	private $wrapperType;
	/** @var string */
	private $wrappedObjectId;
	/** @var Token */
	public $token;
	/** @var string|null */
	public $variableName;
	/** @var string|null */
	public $parserFunctionName;
	/** @var string|null */
	public $resolvedTemplateTarget;

	public function __construct(
		Env $env, Frame $frame, Token $token, string $wrapperType
	) {
		$this->env = $env;
		$this->frame = $frame;
		$this->token = $token;
		$this->wrapperType = $wrapperType;
		$this->wrappedObjectId = $env->newObjectId();
	}

	/**
	 * Main entry point.
	 * Encapsulate the template element, including the arguments.
	 *
	 * @param array $tokens
	 * @return array
	 */
	public function encapTokens( array $tokens ): array {
		$toks = $this->getEncapsulationInfo( $tokens );
		$toks[] = $this->getEncapsulationInfoEndTag();
		$tplInfo = $this->getTemplateInfo();

		if ( $this->env->getSiteConfig()->addHTMLTemplateParameters() ) {
			// Parse the parameters that need parsing
			foreach ( $tplInfo->paramInfos as $paramInfo ) {
				$paramTokens = null;
				if ( $paramInfo->named ) {
					$paramTokens = $this->token->getAttributeV( $paramInfo->k );
				} else {
					$paramTokens = $this->token->attribs[$paramInfo->k]->v;
				}

				// No need to pass through a whole sub-pipeline to get the
				// html if the param is either a single string, or if it's
				// just text, comments or newlines.
				if ( $paramTokens &&
					( is_string( $paramTokens ) || self::isSimpleParam( $paramTokens ) )
				) {
					$paramInfo->html = $paramInfo->valueWt;
				} elseif (
					// FIXME: this should not have its own regex parsing separate from the PEG
					preg_match( '#^https?://[^[\]{}\s]*$#D', $paramInfo->valueWt )
				) {
					// If the param is just a simple URL, we can process it to
					// HTML directly without going through a sub-pipeline.
					$paramInfo->html = "<a rel='mw:ExtLink' href='" .
						str_replace( "'", '&#39;', $paramInfo->valueWt ) . "'>" .
						$paramInfo->valueWt . '</a>';
				} else {
					$this->getParamHTML( $paramInfo );
				}
			}
		} else {
			// Don't add the HTML template parameters, just use their wikitext
		}

		$toks[0]->dataParsoid->getTemp()->tplarginfo = $tplInfo;

		$this->env->log( 'debug', 'TemplateEncapsulator.encapTokens', $toks );
		return $toks;
	}

	/**
	 * Get the public data-mw structure that exposes the template name and
	 * parameters.
	 *
	 * @return TemplateInfo
	 */
	private function getTemplateInfo(): TemplateInfo {
		$ret = new TemplateInfo;
		$src = $this->frame->getSrcText();
		$params = $this->token->attribs;
		$paramInfos = [];
		$argIndex = 1;

		// Use source offsets to extract arg-name and arg-value wikitext
		// since the 'k' and 'v' values in params will be expanded tokens
		//
		// Ignore params[0] -- that is the template name
		for ( $i = 1, $n = count( $params );  $i < $n;  $i++ ) {
			$param = $params[$i];
			$srcOffsets = $param->srcOffsets;
			$kSrc = null;
			$vSrc = null;
			if ( $srcOffsets !== null ) {
				$kSrc = $srcOffsets->key->substr( $src );
				$vSrc = $srcOffsets->value->substr( $src );
			} else {
				$kSrc = $param->k;
				$vSrc = $param->v;
			}

			$kWt = trim( $kSrc );
			$k = TokenUtils::tokensToString( $param->k, true, [ 'stripEmptyLineMeta' => true ] );
			if ( is_array( $k ) ) {
				// The PHP parser only removes comments and whitespace to construct
				// the real parameter name, so if there were other tokens, use the
				// original text
				$k = $kWt;
			} else {
				$k = trim( $k );
			}
			$v = $vSrc;

			// Even if k is empty, we need to check v immediately follows. If not,
			// it's a blank parameter name (which is valid) and we shouldn't make it
			// positional.
			if ( $k === '' &&
				$srcOffsets &&
				$srcOffsets->key->end === $srcOffsets->value->start
			) {
				$isPositional = true;
				$k = (string)$argIndex;
				$argIndex++;
			} else {
				$isPositional = false;
				// strip ws from named parameter values
				$v = trim( $v );
			}

			if ( !isset( $paramInfos[$k] ) ) {
				$paramInfo = new ParamInfo( $k, $srcOffsets );

				Assert::invariant(
					preg_match( '/^(\s*)(?:.*\S)?(\s*)$/sD', $kSrc, $keySpaceMatch ),
					'Template argument whitespace match failed.'
				);
				$valueSpaceMatch = null;

				if ( $isPositional ) {
					// PHP parser does not strip whitespace around
					// positional params and neither will we.
					$valueSpaceMatch = [ null, '', '' ];
				} else {
					$paramInfo->named = true;
					if ( $v !== '' ) {
						Assert::invariant(
							preg_match( '/^(\s*)(?:.*\S)?(\s*)$/sD', $vSrc, $valueSpaceMatch ),
							'Template argument whitespace match failed.'
						);
					} else {
						$valueSpaceMatch = [ null, '', $vSrc ];
					}
				}

				// Preserve key and value space prefix / postfix, if any.
				// "=" is the default spacing used by the serializer,
				if ( $keySpaceMatch[1] || $keySpaceMatch[2] || $valueSpaceMatch[1] || $valueSpaceMatch[2] ) {
					// Remember non-standard spacing
					$paramInfo->spc = [
						$keySpaceMatch[1], $keySpaceMatch[2],
						$valueSpaceMatch[1], $valueSpaceMatch[2]
					];
				}
			} else {
				$paramInfo = $paramInfos[$k];
			}

			$paramInfo->valueWt = $v;
			// Only add the original parameter wikitext if named and different from
			// the actual parameter.
			if ( !$isPositional && $kWt !== $k ) {
				$paramInfo->keyWt = $kWt;
			}
			$paramInfos[$k] = $paramInfo;
		}

		$ret->paramInfos = $paramInfos;

		$tgtSrcOffsets = $params[0]->srcOffsets;
		if ( $tgtSrcOffsets ) {
			$tplTgtWT = $tgtSrcOffsets->key->substr( $src );
			$ret->targetWt = $tplTgtWT;
		}

		// Add in tpl-target/pf-name info
		// Only one of these will be set.
		if ( $this->variableName !== null ) {
			$ret->func = $this->variableName;
		} elseif ( $this->parserFunctionName !== null ) {
			$ret->func = $this->parserFunctionName;
		} elseif ( $this->resolvedTemplateTarget !== null ) {
			$ret->href = $this->resolvedTemplateTarget;
		}

		return $ret;
	}

	private function getEncapsulationInfo( ?array $chunk = null ): array {
		// TODO
		// * only add this information for top-level includes, but track parameter
		// expansion in lower-level templates
		// * ref all tables to this (just add about)
		// * ref end token to this, add property="mw:Transclusion/End"

		$attrs = [
			new KV( 'typeof', $this->wrapperType ),
			new KV( 'about', '#' . $this->wrappedObjectId )
		];
		$dp = new DataParsoid;
		$dp->tsr = clone $this->token->dataParsoid->tsr;
		$dp->src = $this->token->dataParsoid->src;

		$meta = [ new SelfclosingTagTk( 'meta', $attrs, $dp ) ];
		$chunk = $chunk ? array_merge( $meta, $chunk ) : $meta;
		return $chunk;
	}

	private function getEncapsulationInfoEndTag(): Token {
		$tsr = $this->token->dataParsoid->tsr ?? null;
		$dp = new DataParsoid;
		$dp->tsr = new SourceRange( null, $tsr ? $tsr->end : null );
		return new SelfclosingTagTk( 'meta',
			[
				new KV( 'typeof', $this->wrapperType . '/End' ),
				new KV( 'about', '#' . $this->wrappedObjectId )
			],
			$dp
		);
	}

	/**
	 * Parameter processing helpers.
	 *
	 * @param mixed $tokens
	 * @return bool
	 */
	private static function isSimpleParam( $tokens ): bool {
		if ( !is_array( $tokens ) ) {
			$tokens = [ $tokens ];
		}
		foreach ( $tokens as $t ) {
			if ( !is_string( $t ) && !( $t instanceof CommentTk ) && !( $t instanceof NlTk ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Add its HTML conversion to a parameter
	 *
	 * @param ParamInfo $paramInfo
	 */
	private function getParamHTML( ParamInfo $paramInfo ): void {
		$srcStart = $paramInfo->srcOffsets->value->start;
		$srcEnd = $paramInfo->srcOffsets->value->end;
		if ( !empty( $paramInfo->spc ) ) {
			$srcStart += strlen( $paramInfo->spc[2] );
			$srcEnd -= strlen( $paramInfo->spc[3] );
		}

		$domFragment = PipelineUtils::processContentInPipeline(
			$this->env, $this->frame,
			$paramInfo->valueWt,
			[
				'pipelineType' => 'text/x-mediawiki/full',
				'pipelineOpts' => [
					'isInclude' => false,
					'expandTemplates' => true,
					// No need to do paragraph-wrapping here
					'inlineContext' => true
				],
				'srcOffsets' => new SourceRange( $srcStart, $srcEnd ),
				'sol' => true
			]
		);
		// FIXME: We're better off setting a pipeline option above
		// to skip dsr computation to begin with.  Worth revisiting
		// if / when `addHTMLTemplateParameters` is enabled.
		// Remove DSR from children
		DOMUtils::visitDOM( $domFragment, static function ( $node ) {
			if ( !( $node instanceof Element ) ) {
				return;
			}
			$dp = DOMDataUtils::getDataParsoid( $node );
			$dp->dsr = null;
		} );
		$paramInfo->html = ContentUtils::ppToXML(
			$domFragment, [ 'innerXML' => true ]
		);
	}

}
