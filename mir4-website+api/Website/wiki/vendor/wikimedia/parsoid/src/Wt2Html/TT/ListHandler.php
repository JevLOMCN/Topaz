<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Tokens\NlTk;
use Wikimedia\Parsoid\Tokens\SourceRange;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Wt2Html\TokenTransformManager;

/**
 * Create list tag around list items and map wiki bullet levels to html.
 */
class ListHandler extends TokenHandler {
	/** @var array<ListFrame> */
	private array $listFrames = [];
	/** @var ?ListFrame */
	private $currListFrame;
	/** @var int */
	private $nestedTableCount;
	/**
	 * Debug string output of bullet character mappings.
	 * @var array<string,array<string,string>>
	 */
	private static $bullet_chars_map = [
		'*' => [ 'list' => 'ul', 'item' => 'li' ],
		'#' => [ 'list' => 'ol', 'item' => 'li' ],
		';' => [ 'list' => 'dl', 'item' => 'dt' ],
		':' => [ 'list' => 'dl', 'item' => 'dd' ]
	];

	/**
	 * The HTML5 parsing spec says that when encountering a closing tag for a
	 * certain set of open tags we should generate implied ends to list items,
	 * https://html.spec.whatwg.org/multipage/parsing.html#parsing-main-inbody:generate-implied-end-tags-5
	 *
	 * So, in order to roundtrip accurately, we should follow suit.  However,
	 * we choose an ostensible superset of those tags, our wikitext blocks, to
	 * have this behaviour.  Hopefully the differences aren't relevant.
	 *
	 * @param string $tagName
	 * @return bool
	 */
	private static function generateImpliedEndTags( string $tagName ): bool {
		return TokenUtils::isWikitextBlockTag( $tagName );
	}

	/**
	 * @param TokenTransformManager $manager manager environment
	 * @param array $options options
	 */
	public function __construct( TokenTransformManager $manager, array $options ) {
		parent::__construct( $manager, $options );
		$this->reset();
	}

	/**
	 * Resets the list handler
	 */
	private function reset(): void {
		$this->onAnyEnabled = false;
		$this->nestedTableCount = 0;
		$this->resetCurrListFrame();
	}

	/**
	 * Resets the current list frame
	 */
	private function resetCurrListFrame(): void {
		$this->currListFrame = null;
	}

	/**
	 * @inheritDoc
	 */
	public function onTag( Token $token ): ?TokenHandlerResult {
		return $token->getName() === 'listItem' ? $this->onListItem( $token ) : null;
	}

	/**
	 * @inheritDoc
	 */
	public function onAny( $token ): ?TokenHandlerResult {
		$this->env->log( 'trace/list', $this->pipelineId,
			'ANY:', static function () use ( $token ) {
				return PHPUtils::jsonEncode( $token );
			} );
		$tokens = null;

		if ( !$this->currListFrame ) {
			// this.currListFrame will be null only when we are in a table
			// that in turn was seen in a list context.
			//
			// Since we are not in a list within the table, nothing to do.
			// Just send the token back unchanged.
			if ( $token instanceof EndTagTk && $token->getName() === 'table' ) {
				if ( $this->nestedTableCount === 0 ) {
					$this->currListFrame = array_pop( $this->listFrames );
				} else {
					$this->nestedTableCount--;
				}
			} elseif ( $token instanceof TagTk && $token->getName() === 'table' ) {
				$this->nestedTableCount++;
			}

			$this->env->log( 'trace/list', $this->pipelineId, 'RET: ', $token );
			return null;
		}

		// Keep track of open tags per list frame in order to prevent colons
		// starting lists illegally. Php's findColonNoLinks.
		if ( $token instanceof TagTk
			// Table tokens will push the frame and remain balanced.
			// They're safe to ignore in the bookkeeping.
			&& $token->getName() !== 'table' ) {
			$this->currListFrame->numOpenTags += 1;
		} elseif ( $token instanceof EndTagTk && $this->currListFrame->numOpenTags > 0 ) {
			$this->currListFrame->numOpenTags -= 1;
		}

		if ( $token instanceof EndTagTk ) {
			if ( $token->getName() === 'table' ) {
				// close all open lists and pop a frame
				$ret = $this->closeLists( $token );
				$this->currListFrame = array_pop( $this->listFrames );
				return new TokenHandlerResult( $ret );
			} elseif ( self::generateImpliedEndTags( $token->getName() ) ) {
				if ( $this->currListFrame->numOpenBlockTags === 0 ) {
					// Unbalanced closing block tag in a list context ==> close all previous lists
					return new TokenHandlerResult( $this->closeLists( $token ) );
				} else {
					$this->currListFrame->numOpenBlockTags--;
					if ( $this->currListFrame->atEOL ) {
						// Non-list item in newline context ==> close all previous lists
						return new TokenHandlerResult( $this->closeLists( $token ) );
					} else {
						$this->env->log( 'trace/list', $this->pipelineId, 'RET: ', $token );
						return null;
					}
				}
			}

			/* Non-block tag -- fall-through to other tests below */
		}

		if ( $this->currListFrame->atEOL ) {
			if ( !$token instanceof NlTk && TokenUtils::isSolTransparent( $this->env, $token ) ) {
				// Hold on to see where the token stream goes from here
				// - another list item, or
				// - end of list
				if ( $this->currListFrame->nlTk ) {
					$this->currListFrame->solTokens[] = $this->currListFrame->nlTk;
					$this->currListFrame->nlTk = null;
				}
				$this->currListFrame->solTokens[] = $token;
				return new TokenHandlerResult( [] );
			} else {
				// Non-list item in newline context ==> close all previous lists
				return new TokenHandlerResult( $this->closeLists( $token ) );
			}
		}

		if ( $token instanceof NlTk ) {
			$this->currListFrame->atEOL = true;
			$this->currListFrame->nlTk = $token;
			// php's findColonNoLinks is run in doBlockLevels, which examines
			// the text line-by-line. At nltk, any open tags will cease having
			// an effect.
			$this->currListFrame->numOpenTags = 0;
			return new TokenHandlerResult( [] );
		}

		if ( $token instanceof TagTk ) {
			if ( $token->getName() === 'table' ) {
				$this->listFrames[] = $this->currListFrame;
				$this->resetCurrListFrame();
			} elseif ( self::generateImpliedEndTags( $token->getName() ) ) {
				$this->currListFrame->numOpenBlockTags++;
			}
			$this->env->log( 'trace/list', $this->pipelineId, 'RET: ', $token );
			return null;
		}

		// Nothing else left to do
		$this->env->log( 'trace/list', $this->pipelineId, 'RET: ', $token );
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function onEnd( EOFTk $token ): ?TokenHandlerResult {
		$this->env->log( 'trace/list', $this->pipelineId,
			'END:', static function () use ( $token ) { return PHPUtils::jsonEncode( $token );
			} );

		$this->listFrames = [];
		if ( !$this->currListFrame ) {
			// init here so we dont have to have a check in closeLists
			// That way, if we get a null frame there, we know we have a bug.
			$this->currListFrame = new ListFrame;
		}
		$toks = $this->closeLists( $token );
		$this->reset();
		return new TokenHandlerResult( $toks );
	}

	/**
	 * Handle close list processing
	 *
	 * @param Token|string $token
	 * @return array
	 */
	private function closeLists( $token ): array {
		// pop all open list item tokens
		$tokens = $this->popTags( count( $this->currListFrame->bstack ) );

		// purge all stashed sol-tokens
		PHPUtils::pushArray( $tokens, $this->currListFrame->solTokens );
		if ( $this->currListFrame->nlTk ) {
			$tokens[] = $this->currListFrame->nlTk;
		}
		$tokens[] = $token;

		// remove any transform if we dont have any stashed list frames
		if ( count( $this->listFrames ) === 0 ) {
			$this->onAnyEnabled = false;
		}

		$this->resetCurrListFrame();

		$this->env->log( 'trace/list', $this->pipelineId, '----closing all lists----' );
		$this->env->log( 'trace/list', $this->pipelineId, 'RET: ', $tokens );

		return $tokens;
	}

	/**
	 * Handle a list item
	 *
	 * @param Token $token
	 * @return TokenHandlerResult|null
	 */
	private function onListItem( Token $token ): ?TokenHandlerResult {
		if ( $token instanceof TagTk ) {
			$this->onAnyEnabled = true;
			if ( $this->currListFrame ) {
				// Ignoring colons inside tags to prevent illegal overlapping.
				// Attempts to mimic findColonNoLinks in the php parser.
				$bullets = $token->getAttributeV( 'bullets' );
				if ( PHPUtils::lastItem( $bullets ) === ':'
					&& $this->currListFrame->numOpenTags > 0
				) {
					$this->env->log( 'trace/list', $this->pipelineId, 'RET: ', ':' );
					return new TokenHandlerResult( [ ':' ] );
				}
			} else {
				$this->currListFrame = new ListFrame;
			}
			// convert listItem to list and list item tokens
			$res = $this->doListItem( $this->currListFrame->bstack, $token->getAttributeV( 'bullets' ),
				$token );
			return new TokenHandlerResult( $res );
		}

		$this->env->log( 'trace/list', $this->pipelineId, 'RET: ', $token );
		return null;
	}

	/**
	 * Determine the minimum common prefix length
	 *
	 * @param array $x
	 * @param array $y
	 * @return int
	 */
	private function commonPrefixLength( array $x, array $y ): int {
		$minLength = min( count( $x ), count( $y ) );
		$i = 0;
		for ( ; $i < $minLength; $i++ ) {
			if ( $x[$i] !== $y[$i] ) {
				break;
			}
		}
		return $i;
	}

	/**
	 * Push a list
	 *
	 * @param array $container
	 * @param DataParsoid $dp1
	 * @param DataParsoid $dp2
	 * @return array
	 */
	private function pushList( array $container, DataParsoid $dp1, DataParsoid $dp2 ): array {
		$this->currListFrame->endtags[] = new EndTagTk( $container['list'] );
		$this->currListFrame->endtags[] = new EndTagTk( $container['item'] );

		return [
			new TagTk( $container['list'], [], $dp1 ),
			new TagTk( $container['item'], [], $dp2 )
		];
	}

	/**
	 * Handle popping tags after processing
	 *
	 * @param int $n
	 * @return array
	 */
	private function popTags( int $n ): array {
		$tokens = [];

		while ( $n > 0 ) {
			// push list item..
			$temp = array_pop( $this->currListFrame->endtags );
			if ( !empty( $temp ) ) {
				$tokens[] = $temp;
			}
			// and the list end tag
			$temp = array_pop( $this->currListFrame->endtags );
			if ( !empty( $temp ) ) {
				$tokens[] = $temp;
			}
			$n--;
		}
		return $tokens;
	}

	/**
	 * Check for Dt Dd sequence
	 *
	 * @param string $a
	 * @param string $b
	 * @return bool
	 */
	private function isDtDd( string $a, string $b ): bool {
		$ab = [ $a, $b ];
		sort( $ab );
		return ( $ab[0] === ':' && $ab[1] === ';' );
	}

	/**
	 * Handle do list item processing
	 *
	 * @param array $bs
	 * @param array $bn
	 * @param Token $token
	 * @return array
	 */
	private function doListItem( array $bs, array $bn, Token $token ): array {
		$this->env->log( 'trace/list', $this->pipelineId,
			'BEGIN:', static function () use ( $token ) { return PHPUtils::jsonEncode( $token );
			} );

		$prefixLen = $this->commonPrefixLength( $bs, $bn );
		$prefix = array_slice( $bn, 0, $prefixLen/*CHECK THIS*/ );
		$dp = $token->dataParsoid;

		$makeDP = static function ( $k, $j ) use ( $dp ) {
			$newDP = $dp->clone();
			$tsr = $dp->tsr ?? null;
			if ( $tsr ) {
				$newDP->tsr = new SourceRange(
					$tsr->start + $k, $tsr->start + $j
				);
			}
			return $newDP;
		};

		$this->currListFrame->bstack = $bn;

		$res = null;
		$itemToken = null;

		// emit close tag tokens for closed lists
		$this->env->log( 'trace/list', $this->pipelineId, static function () use ( $bs, $bn ) {
			return '    bs: ' . PHPUtils::jsonEncode( $bs ) . '; bn: ' . PHPUtils::jsonEncode( $bn );
		} );

		if ( count( $prefix ) === count( $bs ) && count( $bn ) === count( $bs ) ) {
			$this->env->log( 'trace/list', $this->pipelineId, '    -> no nesting change' );

			// same list item types and same nesting level
			$itemToken = array_pop( $this->currListFrame->endtags );
			$this->currListFrame->endtags[] = new EndTagTk( $itemToken->getName() );
			$res = array_merge( [ $itemToken ],
				$this->currListFrame->solTokens,
				[
					// this list item gets all the bullets since this is
					// a list item at the same level
					//
					// **a
					// **b
					$this->currListFrame->nlTk ?: '',
					new TagTk( $itemToken->getName(), [], $makeDP( 0, count( $bn ) ) )
				]
			);
		} else {
			$prefixCorrection = 0;
			$tokens = [];
			if ( count( $bs ) > $prefixLen
				&& count( $bn ) > $prefixLen
				&& $this->isDtDd( $bs[$prefixLen], $bn[$prefixLen] ) ) {
				/* ------------------------------------------------
				 * Handle dd/dt transitions
				 *
				 * Example:
				 *
				 * **;:: foo
				 * **::: bar
				 *
				 * the 3rd bullet is the dt-dd transition
				 * ------------------------------------------------ */

				$tokens = $this->popTags( count( $bs ) - $prefixLen - 1 );
				$tokens = array_merge( $this->currListFrame->solTokens, $tokens );
				$newName = self::$bullet_chars_map[$bn[$prefixLen]]['item'];
				$endTag = array_pop( $this->currListFrame->endtags );
				$this->currListFrame->endtags[] = new EndTagTk( $newName );

				$newTag = null;
				if ( isset( $dp->stx ) && $dp->stx === 'row' ) {
					// stx='row' is only set for single-line dt-dd lists (see tokenizer)
					// In this scenario, the dd token we are building a token for has no prefix
					// Ex: ;a:b, *;a:b, #**;a:b, etc. Compare with *;a\n*:b, #**;a\n#**:b
					$this->env->log( 'trace/list', $this->pipelineId,
						'    -> single-line dt->dd transition' );
					$newTag = new TagTk( $newName, [], $makeDP( 0, 1 ) );
				} else {
					$this->env->log( 'trace/list', $this->pipelineId, '    -> other dt/dd transition' );
					$newTag = new TagTk( $newName, [], $makeDP( 0, $prefixLen + 1 ) );
				}

				$tokens[] = $endTag;
				$tokens[] = $this->currListFrame->nlTk ?: '';
				$tokens[] = $newTag;

				$prefixCorrection = 1;
			} else {
				$this->env->log( 'trace/list', $this->pipelineId, '    -> reduced nesting' );
				$tokens = array_merge(
					$this->currListFrame->solTokens,
					$tokens,
					$this->popTags( count( $bs ) - $prefixLen )
				);
				if ( $this->currListFrame->nlTk ) {
					$tokens[] = $this->currListFrame->nlTk;
				}
				if ( $prefixLen > 0 && count( $bn ) === $prefixLen ) {
					$itemToken = array_pop( $this->currListFrame->endtags );
					$tokens[] = $itemToken;
					// this list item gets all bullets upto the shared prefix
					$tokens[] = new TagTk( $itemToken->getName(), [], $makeDP( 0, count( $bn ) ) );
					$this->currListFrame->endtags[] = new EndTagTk( $itemToken->getName() );
				}
			}

			for ( $i = $prefixLen + $prefixCorrection; $i < count( $bn ); $i++ ) {
				if ( !self::$bullet_chars_map[$bn[$i]] ) {
					throw new \InvalidArgumentException( 'Unknown node prefix ' . $prefix[$i] );
				}

				// Each list item in the chain gets one bullet.
				// However, the first item also includes the shared prefix.
				//
				// Example:
				//
				// **a
				// ****b
				//
				// Yields:
				//
				// <ul><li-*>
				// <ul><li-*>a
				// <ul><li-FIRST-ONE-gets-***>
				// <ul><li-*>b</li></ul>
				// </li></ul>
				// </li></ul>
				// </li></ul>
				//
				// Unless prefixCorrection is > 0, in which case we've
				// already accounted for the initial bullets.
				//
				// prefixCorrection is for handling dl-dts like this
				//
				// ;a:b
				// ;;c:d
				//
				// ";c:d" is embedded within a dt that is 1 char wide(;)

				$listDP = null;
				$listItemDP = null;
				if ( $i === $prefixLen ) {
					$this->env->log( 'trace/list', $this->pipelineId,
						'    -> increased nesting: first'
					);
					$listDP = $makeDP( 0, 0 );
					$listItemDP = $makeDP( 0, $i + 1 );
				} else {
					$this->env->log( 'trace/list', $this->pipelineId,
						'    -> increased nesting: 2nd and higher'
					);
					$listDP = $makeDP( $i, $i );
					$listItemDP = $makeDP( $i, $i + 1 );
				}

				PHPUtils::pushArray( $tokens, $this->pushList(
					self::$bullet_chars_map[$bn[$i]], $listDP, $listItemDP
				) );
			}
			$res = $tokens;
		}

		// clear out sol-tokens
		$this->currListFrame->solTokens = [];
		$this->currListFrame->nlTk = null;
		$this->currListFrame->atEOL = false;

		$this->env->log( 'trace/list', $this->pipelineId,
			'RET:', static function () use ( $res ) { return PHPUtils::jsonEncode( $res );
			} );
		return $res;
	}
}
