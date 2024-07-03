<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\NodeData;

use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\Tokens\SourceRange;

/**
 * A class for temporary node-related data, stored in DataParsoid->tmp
 *
 * We use undeclared properties to reduce memory usage, since there are
 * typically very many instances of this class.
 *
 * An associative array with keys "key" and "params", set on span typeof=mw:I18n
 * elements to carry wfMessage() parameters.
 * @property array|null $i18n
 *
 * The original DSR for a quote (b/i) element prior to its adjustment by ComputeDSR.
 * @property DomSourceRange|null $origDSR
 *
 * This is set on h1-h6 tokens to track section numbers.
 * @property int|null $headingIndex
 *
 * This is an array of key-value pairs [[k,v], [k,v]] set by AttributeExpander
 * on template tokens. It filters through to data-mw attribs.
 * @property array|null $templatedAttribs
 *
 * Information about a template invocation
 * @property TemplateInfo|null $tplarginfo
 *
 * The TSR of the end tag
 * @property SourceRange|null $endTSR
 *
 * Used to shuttle tokens to the end of a stage in the TTM
 * @property array|null $shuttleTokens
 *
 * Section data associated with a heading
 * @property array|null $section
 */
#[\AllowDynamicProperties]
class TempData {
	/**
	 * Whether a DOM node is a new node added during an edit session. figureHandler()
	 * sets this on synthetic div elements.
	 */
	public const IS_NEW = 1 << 0;

	/**
	 * The tokenizer sets this on table cells originating in wikitext-style syntax
	 * with no attributes set in the input.
	 */
	public const NO_ATTRS = 1 << 1;

	/**
	 * This is set on cell elements that could not be combined with the previous
	 * cell. Private to TableFixups.
	 */
	public const FAILED_REPARSE = 1 << 2;

	/**
	 * This is set on span tags that are created by PipelineUtils::addSpanWrappers().
	 */
	public const WRAPPER = 1 << 3;

	/**
	 * This is set on wrapper tokens created by PipelineUtils::encapsulateExpansionHTML()
	 * to propagate the setDSR option to that function.
	 */
	public const SET_DSR = 1 << 4;

	/**
	 * This is set on wrapper tokens created by PipelineUtils::encapsulateExpansionHTML()
	 * to propagate the fromCache option to that function.
	 */
	public const FROM_CACHE = 1 << 5;

	/**
	 * A flag private to Linter, used to suppress duplicate messages.
	 */
	public const LINTED = 1 << 6;

	/**
	 * A flag private to Linter to help it traverse a DOM
	 */
	public const PROCESSED_TIDY_WS_BUG = 1 << 7;

	/**
	 * This is set on all elements that originate in a template. It controls
	 * the insertion of mw:Transclusion markers in MarkFosteredContent.
	 */
	public const IN_TRANSCLUSION = 1 << 8;

	/**
	 * MarkFosteredContent sets this on meta mw:Transclusion tags. It is only used
	 * in an assertion.
	 */
	public const FROM_FOSTER = 1 << 9;

	/**
	 * Used to indicate that media dimensions have redundant units.
	 */
	public const BOGUS_PX = 1 << 10;

	/**
	 * All elements inserted by TreeBuilderStage receive an integer ID. It is used
	 * in findAutoInsertedTags() in conjunction with data-stag to identify
	 * auto-inserted tags, and for debugging.
	 * @var int|null
	 */
	public $tagId;

	/**
	 * A combination of flags combined from consts on this class.
	 * @var int
	 */
	public $bits = 0;

	/**
	 * Node temporary attribute key-value pair to be processed in post-process steps.
	 * Some extensions need to store data to be post-processed due to custom state
	 * implementation.
	 *
	 * Make this property private and leave for ParsoidExtensionAPI to manipulate its
	 * content.
	 *
	 * @var array|null
	 */
	private ?array $tagData;

	/**
	 * Check whether a bit is set in $this->bits
	 *
	 * @param int $flag
	 * @return bool
	 */
	public function getFlag( int $flag ): bool {
		return (bool)( $this->bits & $flag );
	}

	/**
	 * Set a bit in $this->bits
	 *
	 * @param int $flag
	 * @param bool $value
	 */
	public function setFlag( int $flag, bool $value = true ): void {
		if ( $value ) {
			$this->bits |= $flag;
		} else {
			$this->bits &= ~$flag;
		}
	}

	/**
	 * Set a tag attribute for a specific extension with a given key
	 *
	 * @param string $key identifier to support a map for multiple extensions
	 * @param mixed $data
	 */
	public function setTagData( string $key, $data ) {
		$this->tagData ??= [];
		$this->tagData[$key] = $data;
	}

	/**
	 * Get a tag attribute for a specific extension tag with a given key
	 *
	 * @param string $key identifier to support a map for multiple tags
	 * @return mixed
	 */
	public function getTagData( string $key ) {
		return $this->tagData[$key] ?? null;
	}
}
