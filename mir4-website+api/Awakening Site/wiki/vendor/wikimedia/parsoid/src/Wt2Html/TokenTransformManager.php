<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html;

use Generator;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Config\Profile;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Wt2Html\TT\TokenHandler;
use Wikimedia\Parsoid\Wt2Html\TT\TraceProxy;

/**
 * Token transformation manager. Individual transformations
 * implement the TokenHandler interface. The parser pipeline
 * registers individual transformers.
 *
 * See https://www.mediawiki.org/wiki/Parsoid/Token_stream_transformations
 * for more documentation.  This abstract class could eventually be
 * eliminated and the various token transforms just extend PipelineStage
 * directly.
 */
class TokenTransformManager extends PipelineStage {
	/** @var array */
	private $options;

	/** @var string */
	private $traceType = "";

	/** @var bool */
	private $traceEnabled;

	/** @var TokenHandler[] */
	private $transformers = [];

	/** @var int For TraceProxy */
	public $tokenTimes = 0;

	/** @var Profile|null For TraceProxy */
	public $profile;

	/** @var bool */
	private $hasShuttleTokens = false;

	public function __construct(
		Env $env, array $options, string $stageId,
		?PipelineStage $prevStage = null
	) {
		parent::__construct( $env, $prevStage );
		$this->options = $options;
		$this->pipelineId = null;
		$this->traceType = 'trace/ttm:' . str_replace( 'TokenTransform', '', $stageId );
		$this->traceEnabled = $env->hasTraceFlags();
	}

	public function setPipelineId( int $id ): void {
		parent::setPipelineId( $id );
		foreach ( $this->transformers as $transformer ) {
			$transformer->setPipelineId( $id );
		}
	}

	public function getFrame(): Frame {
		return $this->frame;
	}

	public function getOptions(): array {
		return $this->options;
	}

	/**
	 * @inheritDoc
	 */
	public function addTransformer( TokenHandler $t ): void {
		if ( $this->traceEnabled ) {
			$this->transformers[] = new TraceProxy( $this, $this->options, $this->traceType, $t );
		} else {
			$this->transformers[] = $t;
		}
	}

	public function shuttleTokensToEndOfStage( array $toks ): array {
		$this->hasShuttleTokens = true;
		$ttmEnd = new SelfclosingTagTk( 'mw:ttm-end' );
		$ttmEnd->dataParsoid->getTemp()->shuttleTokens = $toks;
		return [ $ttmEnd ];
	}

	/**
	 * Push the tokens through all the registered transformers.
	 * @inheritDoc
	 */
	public function processChunk( array $tokens ): ?array {
		// Trivial case
		if ( !$tokens ) {
			return $tokens;
		}

		$startTime = null;
		$profile = $this->profile = $this->env->profiling() ? $this->env->getCurrentProfile() : null;

		if ( $profile ) {
			$startTime = microtime( true );
			$this->tokenTimes = 0;
		}

		foreach ( $this->transformers as $transformer ) {
			if ( !$transformer->isDisabled() ) {
				if ( !$tokens ) {
					break;
				}
				$tokens = $transformer->process( $tokens );
			}
		}

		// Unpack tokens that were shuttled to the end of the stage.  This happens
		// when we used a nested pipeline to process tokens to the end of the
		// current stage but then they need to be reinserted into the stream
		// and we don't want them to be processed by subsequent handlers again.
		if ( $this->hasShuttleTokens ) {
			$this->hasShuttleTokens = false;
			$accum = [];
			foreach ( $tokens as $i => $t ) {
				if ( $t instanceof SelfclosingTagTk && $t->getName() === 'mw:ttm-end' ) {
					$toks = $t->dataParsoid->getTemp()->shuttleTokens;
					PHPUtils::pushArray( $accum, $toks );
				} else {
					$accum[] = $t;
				}
			}
			$tokens = $accum;
		}

		if ( $profile ) {
			$profile->bumpTimeUse( 'TTM',
				( microtime( true ) - $startTime ) * 1000 - $this->tokenTimes,
				'TTM' );
		}

		return $tokens;
	}

	/**
	 * @inheritDoc
	 */
	public function resetState( array $opts ): void {
		$this->hasShuttleTokens = false;
		parent::resetState( $opts );
		foreach ( $this->transformers as $transformer ) {
			$transformer->resetState( $opts );
		}
	}

	/**
	 * See PipelineStage::process docs as well. This doc block refines
	 * the generic arg types to be specific to this pipeline stage.
	 *
	 * Process a chunk of tokens.
	 *
	 * @param array $tokens Array of tokens to process
	 * @param ?array $opts
	 * @return array Returns the array of processed tokens
	 */
	public function process( $tokens, ?array $opts = null ): array {
		return $this->processChunk( $tokens );
	}

	/**
	 * @inheritDoc
	 */
	public function processChunkily( $input, array $opts = null ): Generator {
		if ( $this->prevStage ) {
			foreach ( $this->prevStage->processChunkily( $input, $opts ) as $chunk ) {
				'@phan-var array $chunk'; // @var array $chunk
				yield $this->processChunk( $chunk );
			}
		} else {
			yield $this->process( $input, $opts );
		}
	}
}
