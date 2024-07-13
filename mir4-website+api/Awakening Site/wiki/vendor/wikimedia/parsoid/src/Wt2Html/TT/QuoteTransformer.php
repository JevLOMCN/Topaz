<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Tokens\NlTk;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\SourceRange;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Wt2html\TokenTransformManager;

/**
 * PORT-FIXME: Maybe we need to look at all uses of flatten
 * and move it to a real helper in PHPUtils.js
 *
 * Flattens arrays with nested arrays
 *
 * @param array $array array
 * @return array
 */
function array_flatten( array $array ): array {
	$ret = [];
	foreach ( $array as $key => $value ) {
		if ( is_array( $value ) ) {
			PHPUtils::pushArray( $ret, array_flatten( $value ) );
		} else {
			$ret[$key] = $value;
		}
	}
	return $ret;
}

/**
 * MediaWiki-compatible italic/bold handling as a token stream transformation.
 */
class QuoteTransformer extends TokenHandler {
	/** Chunks alternate between quote tokens and sequences of non-quote
	 * tokens.  The quote tokens are later replaced with the actual tag
	 * token for italic or bold.  The first chunk is a non-quote chunk.
	 * @var array
	 */
	private $chunks;

	/**
	 * The current chunk we're accumulating into.
	 * @var array
	 */
	private $currentChunk;

	/**
	 * Last italic / last bold open tag seen.  Used to add autoInserted flags
	 * where necessary.
	 * @var array
	 */
	private $last;

	/**
	 * @param TokenTransformManager $manager manager environment
	 * @param array $options options
	 */
	public function __construct( TokenTransformManager $manager, array $options ) {
		parent::__construct( $manager, $options );
		$this->reset();
	}

	/**
	 * Reset the buffering of chunks
	 *
	 */
	private function reset(): void {
		// Chunks alternate between quote tokens and sequences of non-quote
		// tokens.  The quote tokens are later replaced with the actual tag
		// token for italic or bold.  The first chunk is a non-quote chunk.
		$this->chunks = [];
		// The current chunk we're accumulating into.
		$this->currentChunk = [];
		// last italic / last bold open tag seen.  Used to add autoInserted flags
		// where necessary.
		$this->last = [];

		$this->onAnyEnabled = false;
	}

	/**
	 * Make a copy of the token context
	 *
	 */
	private function startNewChunk(): void {
		$this->chunks[] = $this->currentChunk;
		$this->currentChunk = [];
	}

	/**
	 * Handles mw-quote tokens and td/th tokens
	 * @inheritDoc
	 */
	public function onTag( Token $token ): ?TokenHandlerResult {
		$tkName = $token->getName();
		if ( $tkName === 'mw-quote' ) {
			return $this->onQuote( $token );
		} elseif ( $tkName === 'td' || $tkName === 'th' ) {
			return $this->processQuotes( $token );
		} else {
			return null;
		}
	}

	/**
	 * On encountering a NlTk, processes quotes on the current line
	 * @inheritDoc
	 */
	public function onNewline( NlTk $token ): ?TokenHandlerResult {
		return $this->processQuotes( $token );
	}

	/**
	 * On encountering an EOFTk, processes quotes on the current line
	 * @inheritDoc
	 */
	public function onEnd( EOFTk $token ): ?TokenHandlerResult {
		return $this->processQuotes( $token );
	}

	/**
	 * @inheritDoc
	 */
	public function onAny( $token ): ?TokenHandlerResult {
		$this->env->log(
			"trace/quote",
			$this->pipelineId,
			"ANY |",
			static function () use ( $token ) {
				return PHPUtils::jsonEncode( $token );
			}
		);

		if ( $this->onAnyEnabled ) {
			$this->currentChunk[] = $token;
			return new TokenHandlerResult( [] );
		} else {
			return null;
		}
	}

	/**
	 * Handle QUOTE tags. These are collected in italic/bold lists depending on
	 * the length of quote string. Actual analysis and conversion to the
	 * appropriate tag tokens is deferred until the next NEWLINE token triggers
	 * processQuotes.
	 *
	 * @param Token $token token
	 * @return TokenHandlerResult
	 */
	private function onQuote( Token $token ): TokenHandlerResult {
		$v = $token->getAttributeV( 'value' );
		$qlen = strlen( $v );
		$this->env->log(
			"trace/quote",
			$this->pipelineId,
			"QUOTE |",
			static function () use ( $token ) {
				return PHPUtils::jsonEncode( $token );
			}
		);

		$this->onAnyEnabled = true;

		if ( $qlen === 2 || $qlen === 3 || $qlen === 5 ) {
			$this->startNewChunk();
			$this->currentChunk[] = $token;
			$this->startNewChunk();
		}

		return new TokenHandlerResult( [] );
	}

	/**
	 * Handle NEWLINE tokens, which trigger the actual quote analysis on the
	 * collected quote tokens so far.
	 *
	 * @param Token $token token
	 * @return TokenHandlerResult|null
	 */
	private function processQuotes( Token $token ): ?TokenHandlerResult {
		if ( !$this->onAnyEnabled ) {
			// Nothing to do, quick abort.
			return null;
		}

		$this->env->log(
			"trace/quote",
			$this->pipelineId,
			"NL    |",
			static function () use( $token ) {
				return PHPUtils::jsonEncode( $token );
			}
		);

		if (
			( $token->getName() === 'td' || $token->getName() === 'th' ) &&
			( $token->dataParsoid->stx ?? '' ) === 'html'
		) {
			return null;
		}

		// count number of bold and italics
		$numbold = 0;
		$numitalics = 0;
		$chunkCount = count( $this->chunks );
		for ( $i = 1; $i < $chunkCount; $i += 2 ) {
			// quote token
			Assert::invariant( count( $this->chunks[$i] ) === 1, 'Expected a single token in the chunk' );
			$qlen = strlen( $this->chunks[$i][0]->getAttributeV( "value" ) );
			if ( $qlen === 2 || $qlen === 5 ) {
				$numitalics++;
			}
			if ( $qlen === 3 || $qlen === 5 ) {
				$numbold++;
			}
		}

		// balance out tokens, convert placeholders into tags
		if ( ( $numitalics % 2 === 1 ) && ( $numbold % 2 === 1 ) ) {
			$firstsingleletterword = -1;
			$firstmultiletterword = -1;
			$firstspace = -1;
			$chunkCount = count( $this->chunks );
			for ( $i = 1; $i < $chunkCount; $i += 2 ) {
				// only look at bold tags
				if ( strlen( $this->chunks[$i][0]->getAttributeV( "value" ) ) !== 3 ) {
					continue;
				}

				$tok = $this->chunks[$i][0];
				$lastCharIsSpace = $tok->getAttributeV( 'isSpace_1' );
				$secondLastCharIsSpace = $tok->getAttributeV( 'isSpace_2' );
				if ( $lastCharIsSpace && $firstspace === -1 ) {
					$firstspace = $i;
				} elseif ( !$lastCharIsSpace ) {
					if ( $secondLastCharIsSpace && $firstsingleletterword === -1 ) {
						$firstsingleletterword = $i;
						// if firstsingleletterword is set, we don't need
						// to look at the options options, so we can bail early
						break;
					} elseif ( $firstmultiletterword === -1 ) {
						$firstmultiletterword = $i;
					}
				}
			}

			// now see if we can convert a bold to an italic and an apostrophe
			if ( $firstsingleletterword > -1 ) {
				$this->convertBold( $firstsingleletterword );
			} elseif ( $firstmultiletterword > -1 ) {
				$this->convertBold( $firstmultiletterword );
			} elseif ( $firstspace > -1 ) {
				$this->convertBold( $firstspace );
			} else {
				// (notice that it is possible for all three to be -1 if, for
				// example, there is only one pentuple-apostrophe in the line)
				// In this case, do no balancing.
			}
		}

		// convert the quote tokens into tags
		$this->convertQuotesToTags();

		// return all collected tokens including the newline
		$this->currentChunk[] = $token;
		$this->startNewChunk();
		// PORT-FIXME: Is there a more efficient way of doing this?
		$res = new TokenHandlerResult( array_flatten( $this->chunks ) );

		$this->env->log(
			"trace/quote",
			$this->pipelineId,
			"----->",
			static function () use ( $res ) {
				return PHPUtils::jsonEncode( $res->tokens );
			}
		);

		// prepare for next line
		$this->reset();

		return $res;
	}

	/**
	 * Convert a bold token to italic to balance an uneven number of both bold and
	 * italic tags. In the process, one quote needs to be converted back to text.
	 *
	 * @param int $i index into chunks
	 */
	private function convertBold( int $i ): void {
		// this should be a bold tag.
		Assert::invariant( $i > 0 && count( $this->chunks[$i] ) === 1
			&& strlen( $this->chunks[$i][0]->getAttributeV( "value" ) ) === 3,
			'this should be a bold tag' );

		// we're going to convert it to a single plain text ' plus an italic tag
		$this->chunks[$i - 1][] = "'";
		$oldbold = $this->chunks[$i][0];
		$tsr = $oldbold->dataParsoid->tsr ?? null;
		if ( $tsr ) {
			$tsr = new SourceRange( $tsr->start + 1, $tsr->end );
		}
		$dp = new DataParsoid;
		$dp->tsr = $tsr;
		$newbold = new SelfclosingTagTk( 'mw-quote', [], $dp );
		$newbold->setAttribute( "value", "''" ); // italic!
		$this->chunks[$i] = [ $newbold ];
	}

	/**
	 * Convert quote tokens to tags, using the same state machine as the
	 * PHP parser uses.
	 */
	private function convertQuotesToTags(): void {
		$lastboth = -1;
		$state = '';

		$chunkCount = count( $this->chunks );
		for ( $i = 1; $i < $chunkCount; $i += 2 ) {
			Assert::invariant( count( $this->chunks[$i] ) === 1, 'expected count chunks[i] == 1' );
			$qlen = strlen( $this->chunks[$i][0]->getAttributeV( "value" ) );
			if ( $qlen === 2 ) {
				if ( $state === 'i' ) {
					$this->quoteToTag( $i, [ new EndTagTk( 'i' ) ] );
					$state = '';
				} elseif ( $state === 'bi' ) {
					$this->quoteToTag( $i, [ new EndTagTk( 'i' ) ] );
					$state = 'b';
				} elseif ( $state === 'ib' ) {
					// annoying!
					$this->quoteToTag( $i, [
						new EndTagTk( 'b' ),
						new EndTagTk( 'i' ),
						new TagTk( 'b' ),
					], true );
					$state = 'b';
				} elseif ( $state === 'both' ) {
					$this->quoteToTag( $lastboth, [ new TagTk( 'b' ), new TagTk( 'i' ) ] );
					$this->quoteToTag( $i, [ new EndTagTk( 'i' ) ] );
					$state = 'b';
				} else { // state can be 'b' or ''
					$this->quoteToTag( $i, [ new TagTk( 'i' ) ] );
					$state .= 'i';
				}
			} elseif ( $qlen === 3 ) {
				if ( $state === 'b' ) {
					$this->quoteToTag( $i, [ new EndTagTk( 'b' ) ] );
					$state = '';
				} elseif ( $state === 'ib' ) {
					$this->quoteToTag( $i, [ new EndTagTk( 'b' ) ] );
					$state = 'i';
				} elseif ( $state === 'bi' ) {
					// annoying!
					$this->quoteToTag( $i, [
						new EndTagTk( 'i' ),
						new EndTagTk( 'b' ),
						new TagTk( 'i' ),
					], true );
					$state = 'i';
				} elseif ( $state === 'both' ) {
					$this->quoteToTag( $lastboth, [ new TagTk( 'i' ), new TagTk( 'b' ) ] );
					$this->quoteToTag( $i, [ new EndTagTk( 'b' ) ] );
					$state = 'i';
				} else { // state can be 'i' or ''
					$this->quoteToTag( $i, [ new TagTk( 'b' ) ] );
					$state .= 'b';
				}
			} elseif ( $qlen === 5 ) {
				if ( $state === 'b' ) {
					$this->quoteToTag( $i, [ new EndTagTk( 'b' ), new TagTk( 'i' ) ] );
					$state = 'i';
				} elseif ( $state === 'i' ) {
					$this->quoteToTag( $i, [ new EndTagTk( 'i' ), new TagTk( 'b' ) ] );
					$state = 'b';
				} elseif ( $state === 'bi' ) {
					$this->quoteToTag( $i, [ new EndTagTk( 'i' ), new EndTagTk( 'b' ) ] );
					$state = '';
				} elseif ( $state === 'ib' ) {
					$this->quoteToTag( $i, [ new EndTagTk( 'b' ), new EndTagTk( 'i' ) ] );
					$state = '';
				} elseif ( $state === 'both' ) {
					$this->quoteToTag( $lastboth, [ new TagTk( 'i' ), new TagTk( 'b' ) ] );
					$this->quoteToTag( $i, [ new EndTagTk( 'b' ), new EndTagTk( 'i' ) ] );
					$state = '';
				} else { // state == ''
					$lastboth = $i;
					$state = 'both';
				}
			}
		}

		// now close all remaining tags.  notice that order is important.
		if ( $state === 'both' ) {
			$this->quoteToTag( $lastboth, [ new TagTk( 'b' ), new TagTk( 'i' ) ] );
			$state = 'bi';
		}
		if ( $state === 'b' || $state === 'ib' ) {
			$this->currentChunk[] = new EndTagTk( 'b' );
			$this->last["b"]->dataParsoid->autoInsertedEndToken = true;
		}
		if ( $state === 'i' || $state === 'bi' || $state === 'ib' ) {
			$this->currentChunk[] = new EndTagTk( 'i' );
			$this->last["i"]->dataParsoid->autoInsertedEndToken = true;
		}
		if ( $state === 'bi' ) {
			$this->currentChunk[] = new EndTagTk( 'b' );
			$this->last["b"]->dataParsoid->autoInsertedEndToken = true;
		}
	}

	/**
	 * Convert italics/bolds into tags.
	 *
	 * @param int $chunk chunk buffer
	 * @param array $tags token
	 * @param bool $ignoreBogusTwo optional defaults to false
	 */
	private function quoteToTag( int $chunk, array $tags, bool $ignoreBogusTwo = false ): void {
		Assert::invariant( count( $this->chunks[$chunk] ) === 1, 'expected count chunks[i] == 1' );
		$result = [];
		$oldtag = $this->chunks[$chunk][0];
		// make tsr
		$tsr = $oldtag->dataParsoid->tsr ?? null;
		$startpos = $tsr ? $tsr->start : null;
		$endpos = $tsr ? $tsr->end : null;
		$numTags = count( $tags );
		for ( $i = 0; $i < $numTags; $i++ ) {
			if ( $tsr ) {
				if ( $i === 0 && $ignoreBogusTwo ) {
					$this->last[$tags[$i]->getName()]->dataParsoid->autoInsertedEndToken = true;
				} elseif ( $i === 2 && $ignoreBogusTwo ) {
					$tags[$i]->dataParsoid->autoInsertedStartToken = true;
				} elseif ( $tags[$i]->getName() === 'b' ) {
					$tags[$i]->dataParsoid->tsr = new SourceRange( $startpos, $startpos + 3 );
					$startpos = $tags[$i]->dataParsoid->tsr->end;
				} elseif ( $tags[$i]->getName() === 'i' ) {
					$tags[$i]->dataParsoid->tsr = new SourceRange( $startpos, $startpos + 2 );
					$startpos = $tags[$i]->dataParsoid->tsr->end;
				}
			}
			$this->last[$tags[$i]->getName()] = ( $tags[$i]->getType() === "EndTagTk" ) ? null : $tags[$i];
			$result[] = $tags[$i];
		}
		if ( $tsr ) {
			Assert::invariant( $startpos === $endpos, 'Start: $startpos !== end: $endpos' );
		}
		$this->chunks[$chunk] = $result;
	}
}
