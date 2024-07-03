<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TreeBuilder;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\InternalException;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Wikitext\Consts;
use Wikimedia\RemexHtml\Tokenizer\PlainAttributes;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;
use Wikimedia\RemexHtml\TreeBuilder\TreeMutationTracer;

class RemexPipeline {
	/** @var Dispatcher */
	public $dispatcher;

	/** @var TreeBuilder */
	public $treeBuilder;

	/** @var TreeMutationRelay */
	private $relay;

	/** @var DOMBuilder */
	public $domBuilder;

	/** @var Document */
	public $doc;

	private $nextFakeLength = 1;

	/**
	 * Create a RemexHtml pipeline
	 *
	 * Since we do our own tokenizing, the Dispatcher is called directly to push
	 * data into the pipeline. A RemexHtml Tokenizer is only needed to provide
	 * stub handling for certain TreeBuilder callbacks. Those callbacks are
	 * required by the HTML 5  spec -- we get away with ignoring them because
	 * we are not actually parsing HTML.
	 *
	 * @param Env $env
	 */
	public function __construct( Env $env ) {
		$this->domBuilder = new DOMBuilder;
		$this->relay = new TreeMutationRelay( $this->domBuilder );
		if ( $env->hasTraceFlag( 'remex' ) ) {
			$tracer = new TreeMutationTracer(
				$this->relay,
				static function ( $msg ) use ( $env ) {
					$env->log( 'trace/remex', $msg );
				}
			);
		} else {
			$tracer = $this->relay;
		}
		$this->treeBuilder = new TreeBuilder( $tracer );
		$this->dispatcher = new Dispatcher( $this->treeBuilder );

		// Create dummy Tokenizer
		$tokenizer = new Tokenizer( $this->dispatcher, '', [ 'ignoreErrors' => true ] );

		$this->dispatcher->startDocument( $tokenizer, null, null );
		$this->dispatcher->doctype( 'html', '', '', false, 0, 0 );
		$this->dispatcher->startTag( 'body', new PlainAttributes(), false, 0, 0 );

		$doc = $this->domBuilder->getFragment();
		if ( $doc instanceof Document ) {
			$this->doc = $doc;
		} else {
			throw new InternalException( 'Invalid document type' );
		}
	}

	/**
	 * Dispatch a meta tag in such a way that it won't be fostered even if the
	 * currently open element is a table. This replaces the old comment hack.
	 *
	 * @param array $attribs
	 */
	public function insertUnfosteredMeta( array $attribs ) {
		$this->dispatcher->flushTableText();
		$this->dispatcher->inHead->startTag(
			'meta',
			new Attributes( $this->doc, $attribs ),
			false, 0, 0
		);
	}

	/**
	 * Dispatch a start tag and consider the generated element to be
	 * auto-inserted.
	 *
	 * @param string $name
	 * @param array $attribs
	 * @param bool $selfClose
	 */
	public function insertImplicitStartTag(
		string $name, array $attribs, bool $selfClose = false
	): void {
		$attribsObj = new Attributes( $this->doc, $attribs );
		$this->dispatcher->startTag( $name, $attribsObj, $selfClose, 0, 0 );
	}

	/**
	 * Dispatch a start tag and consider the generated element to be explicit,
	 * not auto-inserted. Return that element. If no element was created,
	 * return null.
	 *
	 * @param string $name
	 * @param array $attribs
	 * @param bool $selfClose
	 * @return Element|null
	 */
	public function insertExplicitStartTag(
		string $name, array $attribs, bool $selfClose = false
	): ?Element {
		$attribsObj = new Attributes( $this->doc, $attribs );
		$this->relay->matchStartTag( $attribsObj );
		$this->dispatcher->startTag( $name, $attribsObj, $selfClose, 0, 0 );
		$element = $this->relay->getMatchedElement();
		$this->relay->resetMatch();
		return $element;
	}

	/**
	 * Dispatch an end tag, and cause the element to be explicitly ended,
	 * i.e. without autoInsertedEnd. Return the element which was ended by the
	 * tag, or null if no element was matched.
	 *
	 * @param string $name
	 * @param bool $isHTML
	 * @return Element|null
	 */
	public function insertExplicitEndTag(
		string $name, bool $isHTML
	): ?Element {
		$fakeLength = $this->nextFakeLength++;
		$this->relay->matchEndTag( $fakeLength, $isHTML );
		$this->dispatcher->endTag( $name, 0, $fakeLength );
		$element = $this->relay->getMatchedElement();
		$this->relay->resetMatch();
		return $element;
	}

	/**
	 * Determine if the TreeBuilder is in a fosterable position, i.e. insertion
	 * of a text node will cause fostering of that text.
	 *
	 * @return bool
	 */
	public function isFosterablePosition() {
		$openElement = $this->treeBuilder->stack->current;
		return isset( Consts::$HTML['FosterablePosition'][$openElement->htmlName] );
	}

}
