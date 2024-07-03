<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html;

use Generator;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\Tokens\SourceRange;
use Wikimedia\Parsoid\Wt2Html\TT\TokenHandler;

/**
 * This represents the abstract interface for a wt2html parsing pipeline stage
 * Currently there are 4 known pipeline stages:
 * - PEG Tokenizer
 * - Token Transform Manager
 * - HTML5 Tree Builder
 * - DOM Post Processor
 *
 * The Token Transform Manager could eventually go away and be directly replaced by
 * the very many token transformers that are represented by the abstract TokenHandler class.
 */

abstract class PipelineStage {
	/**
	 * Previous pipeline stage that generates input for this stage.
	 * Will be null for the first pipeline stage.
	 * @var ?PipelineStage
	 */
	protected $prevStage;

	/**
	 * This is primarily a debugging aid.
	 * @var int
	 */
	protected $pipelineId = -1;

	/** @var Env */
	protected $env = null;

	/** Defaults to false and resetState initializes it */
	protected bool $atTopLevel = false;

	/** @var Frame */
	protected $frame;

	public function __construct( Env $env, ?PipelineStage $prevStage = null ) {
		$this->env = $env;
		$this->prevStage = $prevStage;
	}

	public function setPipelineId( int $id ): void {
		$this->pipelineId = $id;
	}

	public function getPipelineId(): int {
		return $this->pipelineId;
	}

	public function getEnv(): Env {
		return $this->env;
	}

	/**
	 * Register a token transformer
	 * @param TokenHandler $t
	 */
	public function addTransformer( TokenHandler $t ): void {
		throw new \BadMethodCallException( "This pipeline stage doesn't accept token transformers." );
	}

	/**
	 * Resets any internal state for this pipeline stage.
	 * This is usually called so a cached pipeline can be reused.
	 *
	 * @param array $options
	 */
	public function resetState( array $options ): void {
		/* Default implementation */
		$this->atTopLevel = $options['toplevel'] ?? false;
	}

	/**
	 * Set frame on this pipeline stage
	 * @param Frame $frame Pipeline frame
	 */
	public function setFrame( Frame $frame ): void {
		$this->frame = $frame;
	}

	/**
	 * Set the source offsets for the content being processing by this pipeline
	 * This matters for when a substring of the top-level page is being processed
	 * in its own pipeline. This ensures that all source offsets assigned to tokens
	 * and DOM nodes in this stage are relative to the top-level page.
	 *
	 * @param SourceRange $so
	 */
	public function setSourceOffsets( SourceRange $so ): void {
		/* Default implementation: Do nothing */
	}

	/**
	 * Process wikitext, an array of tokens, or a DOM document depending on
	 * what pipeline stage this is. This will be entirety of the input that
	 * will be processed by this pipeline stage and no further input or an EOF
	 * signal will follow.
	 *
	 * @param string|array|Document $input
	 * @param ?array $options
	 *  - atTopLevel: (bool) Whether we are processing the top-level document
	 *  - sol: (bool) Whether input should be processed in start-of-line context
	 *  - chunky (bool) Whether we are processing the input chunkily.
	 * @return array|Document
	 */
	abstract public function process( $input, ?array $options = null );

	/**
	 * Process wikitext, an array of tokens, or a DOM document depending on
	 * what pipeline stage this is. This method will either directly or indirectly
	 * implement a generator that parses the input in chunks and yields output
	 * in chunks as well.
	 *
	 * Implementations that don't consume tokens (ex: Tokenizer, DOMPostProcessor)
	 * will provide specialized implementations that handle their input type.
	 *
	 * @param string|array|Document $input
	 * @param ?array $options
	 *  - atTopLevel: (bool) Whether we are processing the top-level document
	 *  - sol: (bool) Whether input should be processed in start-of-line context
	 * @return Generator
	 */
	abstract public function processChunkily(
		$input, ?array $options
	): Generator;
}
