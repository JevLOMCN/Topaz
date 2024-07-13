<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Tokenizer\Attributes;
use Wikimedia\RemexHtml\Tokenizer\TokenHandler;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;

/**
 * This is the approximate equivalent of the "tree construction dispatcher" in
 * the spec. It receives token events and distributes them to the appropriate
 * insertion mode class. It also implements some things specific to the
 * dispatcher state:
 *   - "Reset the insertion mode appropriately"
 *   - The stack of template insertion modes
 *   - The "original insertion mode"
 */
class Dispatcher implements TokenHandler {
	/**
	 * The insertion mode indexes
	 */
	public const INITIAL = 1;
	public const BEFORE_HTML = 2;
	public const BEFORE_HEAD = 3;
	public const IN_HEAD = 4;
	public const IN_HEAD_NOSCRIPT = 5;
	public const AFTER_HEAD = 6;
	public const IN_BODY = 7;
	public const TEXT = 8;
	public const IN_TABLE = 9;
	public const IN_TABLE_TEXT = 10;
	public const IN_CAPTION = 11;
	public const IN_COLUMN_GROUP = 12;
	public const IN_TABLE_BODY = 13;
	public const IN_ROW = 14;
	public const IN_CELL = 15;
	public const IN_SELECT = 16;
	public const IN_SELECT_IN_TABLE = 17;
	public const IN_TEMPLATE = 18;
	public const AFTER_BODY = 19;
	public const IN_FRAMESET = 20;
	public const AFTER_FRAMESET = 21;
	public const AFTER_AFTER_BODY = 22;
	public const AFTER_AFTER_FRAMESET = 23;
	public const IN_FOREIGN_CONTENT = 24;
	public const IN_PRE = 25;
	public const IN_TEXTAREA = 26;

	/**
	 * The handler class for each insertion mode
	 *
	 * @var array
	 */
	protected static $handlerClasses = [
		self::INITIAL => Initial::class,
		self::BEFORE_HTML => BeforeHtml::class,
		self::BEFORE_HEAD => BeforeHead::class,
		self::IN_HEAD => InHead::class,
		self::IN_HEAD_NOSCRIPT => InHeadNoscript::class,
		self::AFTER_HEAD => AfterHead::class,
		self::IN_BODY => InBody::class,
		self::TEXT => Text::class,
		self::IN_TABLE => InTable::class,
		self::IN_TABLE_TEXT => InTableText::class,
		self::IN_CAPTION => InCaption::class,
		self::IN_COLUMN_GROUP => InColumnGroup::class,
		self::IN_TABLE_BODY => InTableBody::class,
		self::IN_ROW => InRow::class,
		self::IN_CELL => InCell::class,
		self::IN_SELECT => InSelect::class,
		self::IN_SELECT_IN_TABLE => InSelectInTable::class,
		self::IN_TEMPLATE => InTemplate::class,
		self::AFTER_BODY => AfterBody::class,
		self::IN_FRAMESET => InFrameset::class,
		self::AFTER_FRAMESET => AfterFrameset::class,
		self::AFTER_AFTER_BODY => AfterAfterBody::class,
		self::AFTER_AFTER_FRAMESET => AfterAfterFrameset::class,
		self::IN_FOREIGN_CONTENT => InForeignContent::class,
		self::IN_PRE => InPre::class,
		self::IN_TEXTAREA => InTextarea::class,
	];

	// Public shortcuts for "using the rules for" actions

	/** @var InHead */
	public $inHead;
	/** @var InBody */
	public $inBody;
	/** @var InTable */
	public $inTable;
	/** @var InSelect */
	public $inSelect;
	/** @var InTemplate */
	public $inTemplate;
	/** @var InForeignContent */
	public $inForeign;

	/** @var TreeBuilder */
	protected $builder;

	/**
	 * The InsertionMode object for the current insertion mode in HTML content
	 *
	 * @var InsertionMode
	 */
	protected $handler;

	/**
	 * An array mapping insertion mode indexes to InsertionMode objects
	 *
	 * @var InsertionMode[]
	 */
	protected $dispatchTable;

	/**
	 * The insertion mode index
	 *
	 * @var int
	 */
	protected $mode;

	/**
	 * The "original insertion mode" index
	 *
	 * @var ?int
	 */
	protected $originalMode;

	/**
	 * The insertion mode sets this to true to acknowledge the tag's
	 * self-closing flag.
	 *
	 * @var bool|null
	 */
	public $ack;

	/**
	 * The stack of template insertion modes
	 *
	 * @var TemplateModeStack
	 */
	public $templateModeStack;

	/**
	 * @param TreeBuilder $builder
	 */
	public function __construct( TreeBuilder $builder ) {
		$this->builder = $builder;
		$this->templateModeStack = new TemplateModeStack;
	}

	/**
	 * Switch the insertion mode, and return the new handler
	 *
	 * @param int $mode
	 * @return InsertionMode
	 */
	public function switchMode( $mode ) {
		$this->mode = $mode;
		$this->handler = $this->dispatchTable[$mode];
		return $this->handler;
	}

	/**
	 * Let the original insertion mode be the current insertion mode, and
	 * switch the insertion mode to some new value. Return the new handler.
	 *
	 * @param int $mode
	 * @return InsertionMode
	 */
	public function switchAndSave( $mode ) {
		$this->originalMode = $this->mode;
		$this->mode = $mode;
		$this->handler = $this->dispatchTable[$mode];
		return $this->handler;
	}

	/**
	 * Switch the insertion mode to the original insertion mode and return the
	 * new handler.
	 *
	 * @return InsertionMode
	 */
	public function restoreMode() {
		if ( $this->originalMode === null ) {
			throw new TreeBuilderError( "original insertion mode is not set" );
		}
		$mode = $this->mode = $this->originalMode;
		$this->originalMode = null;
		$this->handler = $this->dispatchTable[$mode];
		return $this->handler;
	}

	/**
	 * Get the handler for the current insertion mode in HTML content.
	 * This is used by the "in foreign" handler to execute the HTML insertion
	 * mode. It does not necessarily correspond to the handler currently being
	 * executed.
	 *
	 * @return InsertionMode
	 */
	public function getHandler() {
		return $this->handler;
	}

	/**
	 * True if we are in a table mode, for the purposes of switching to
	 * IN_SELECT_IN_TABLE as opposed to IN_SELECT.
	 *
	 * @return bool
	 */
	public function isInTableMode() {
		static $tableModes = [
			self::IN_TABLE => true,
			self::IN_CAPTION => true,
			self::IN_TABLE_BODY => true,
			self::IN_ROW => true,
			self::IN_CELL => true ];
		return isset( $tableModes[$this->mode] );
	}

	/**
	 * If the insertion mode is "in table text", flush the pending table text.
	 * This is a facility allowing users to insert into the DOM more cleanly.
	 */
	public function flushTableText() {
		if ( $this->mode === self::IN_TABLE_TEXT
			&& $this->handler instanceof InTableText
		) {
			$this->handler->flush();
		}
	}

	/**
	 * Reset the insertion mode appropriately, and return the new handler.
	 *
	 * @return InsertionMode
	 */
	public function reset() {
		return $this->switchMode( $this->getAppropriateMode() );
	}

	/**
	 * Get the insertion mode index which is switched to when we reset the
	 * insertion mode appropriately.
	 *
	 * @return int
	 */
	protected function getAppropriateMode() {
		$builder = $this->builder;
		$stack = $builder->stack;
		$last = false;
		for ( $idx = $stack->length() - 1; $idx >= 0; $idx-- ) {
			$node = $stack->item( $idx );
			if ( $idx === 0 ) {
				$last = true;
				if ( $builder->isFragment ) {
					$node = $builder->fragmentContext;
				}
			}

			switch ( $node->htmlName ) {
				case 'select':
					if ( $last ) {
						return self::IN_SELECT;
					}
					for ( $ancestorIdx = $idx - 1; $ancestorIdx >= 1; $ancestorIdx-- ) {
						$ancestor = $stack->item( $ancestorIdx );
						if ( $ancestor->htmlName === 'template' ) {
							return self::IN_SELECT;
						} elseif ( $ancestor->htmlName === 'table' ) {
							return self::IN_SELECT_IN_TABLE;
						}
					}
					return self::IN_SELECT;

				case 'td':
				case 'th':
					if ( !$last ) {
						return self::IN_CELL;
					}
					break;

				case 'tr':
					return self::IN_ROW;

				case 'tbody':
				case 'thead':
				case 'tfoot':
					return self::IN_TABLE_BODY;

				case 'caption':
					return self::IN_CAPTION;

				case 'colgroup':
					return self::IN_COLUMN_GROUP;

				case 'table':
					return self::IN_TABLE;

				case 'template':
					return $this->templateModeStack->current;

				case 'head':
					if ( $last ) {
						return self::IN_BODY;
					} else {
						return self::IN_HEAD;
					}

				case 'body':
					return self::IN_BODY;

				case 'frameset':
					return self::IN_FRAMESET;

				case 'html':
					if ( $builder->headElement === null ) {
						return self::BEFORE_HEAD;
					} else {
						return self::AFTER_HEAD;
					}
			}
		}

		return self::IN_BODY;
	}

	/**
	 * If the stack of open elements is empty, return null, otherwise return
	 * the adjusted current node.
	 * @return Element|null
	 */
	protected function dispatcherCurrentNode() {
		$current = $this->builder->stack->current;
		if ( $current && $current->stackIndex === 0 && $this->builder->isFragment ) {
			return $this->builder->fragmentContext;
		} else {
			return $current;
		}
	}

	public function startDocument( Tokenizer $tokenizer, $namespace, $name ) {
		$this->dispatchTable = [];
		foreach ( self::$handlerClasses as $mode => $class ) {
			$this->dispatchTable[$mode] = new $class( $this->builder, $this );
		}

		$this->inHead = $this->dispatchTable[self::IN_HEAD];
		$this->inBody = $this->dispatchTable[self::IN_BODY];
		$this->inTable = $this->dispatchTable[self::IN_TABLE];
		$this->inSelect = $this->dispatchTable[self::IN_SELECT];
		$this->inTemplate = $this->dispatchTable[self::IN_TEMPLATE];
		$this->inForeign = $this->dispatchTable[self::IN_FOREIGN_CONTENT];

		$this->switchMode( self::INITIAL );

		$this->builder->startDocument( $tokenizer, $namespace, $name );
		if ( $namespace !== null ) {
			if ( $namespace === HTMLData::NS_HTML && $name === 'template' ) {
				$this->templateModeStack->push( self::IN_TEMPLATE );
			}
			$this->reset();
		}
	}

	/**
	 * @inheritDoc
	 * @suppress PhanTypeMismatchPropertyProbablyReal Clears references to null
	 */
	public function endDocument( $pos ) {
		$this->handler->endDocument( $pos );

		// All references to insertion modes must be explicitly released, since
		// they have a circular reference back to $this
		$this->dispatchTable = null;
		$this->handler = null;
		$this->inHead = null;
		$this->inBody = null;
		$this->inTable = null;
		$this->inSelect = null;
		$this->inTemplate = null;
		$this->inForeign = null;
	}

	public function error( $text, $pos ) {
		$this->builder->error( $text, $pos );
	}

	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$current = $this->dispatcherCurrentNode();
		if ( !$current
			|| $current->namespace === HTMLData::NS_HTML
			|| $current->isMathmlTextIntegration()
			|| $current->isHtmlIntegration()
		) {
			$this->handler->characters( $text, $start, $length, $sourceStart, $sourceLength );
		} else {
			$this->inForeign->characters(
				$text, $start, $length, $sourceStart, $sourceLength );
		}
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$this->ack = false;
		$current = $this->dispatcherCurrentNode();
		if ( !$current
			|| $current->namespace === HTMLData::NS_HTML
			|| ( $current->isMathmlTextIntegration()
				&& $name !== 'mglyph'
				&& $name !== 'malignmark'
			)
			|| ( $name === 'svg'
				&& $current->namespace === HTMLData::NS_MATHML
				&& $current->name === 'annotation-xml'
			)
			|| $current->isHtmlIntegration()
		) {
			$this->handler->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
		} else {
			$this->inForeign->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
		}
		if ( $selfClose && !$this->ack ) {
			$this->builder->error( "unacknowledged self-closing tag", $sourceStart );
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$current = $this->dispatcherCurrentNode();
		if ( !$current || $current->namespace === HTMLData::NS_HTML ) {
			$this->handler->endTag( $name, $sourceStart, $sourceLength );
		} else {
			$this->inForeign->endTag( $name, $sourceStart, $sourceLength );
		}
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		$current = $this->dispatcherCurrentNode();
		if ( !$current || $current->namespace === HTMLData::NS_HTML ) {
			$this->handler->doctype( $name, $public, $system, $quirks,
				$sourceStart, $sourceLength );
		} else {
			$this->inForeign->doctype( $name, $public, $system, $quirks,
				$sourceStart, $sourceLength );
		}
	}

	public function comment( $text, $sourceStart, $sourceLength ) {
		$current = $this->dispatcherCurrentNode();
		if ( !$current || $current->namespace === HTMLData::NS_HTML ) {
			$this->handler->comment( $text, $sourceStart, $sourceLength );
		} else {
			$this->inForeign->comment( $text, $sourceStart, $sourceLength );
		}
	}
}
