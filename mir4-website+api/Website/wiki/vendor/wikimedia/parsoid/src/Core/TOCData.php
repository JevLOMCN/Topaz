<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Core;

use Wikimedia\JsonCodec\JsonCodecable;
use Wikimedia\JsonCodec\JsonCodecableTrait;
use Wikimedia\Parsoid\Utils\CompatJsonCodec;

/**
 * Table of Contents data, including an array of section metadata.
 *
 * This is simply an array of SectionMetadata objects for now along
 * with extension data, but may include additional ToC properties in
 * the future.
 */
class TOCData implements \JsonSerializable, JsonCodecable {
	use JsonCodecableTrait;

	/**
	 * The sections in this Table of Contents.
	 * @var SectionMetadata[]
	 */
	private array $sections;

	/**
	 * Arbitrary data attached to this Table of Contents by
	 * extensions.  This data will be stored and cached in the
	 * ParserOutput object along with the rest of the table of
	 * contents data, and made available to external clients via the
	 * action API.
	 *
	 * See ParserOutput::setExtensionData() for more information on typical
	 * use, and SectionMetadata::setExtensionData() for a method appropriate
	 * for attaching information to a specific section of the ToC.
	 */
	private array $extensionData = [];

	/**
	 * --------------------------------------------------
	 * These next 4 properties are temporary state needed
	 * to construct section metadata objects used in TOC.
	 * These state properties are not useful once that is
	 * done and will not be exported or serialized.
	 * --------------------------------------------------
	 */

	/** @var int Temporary TOC State */
	private $tocLevel = 0;

	/** @var int Temporary TOC State */
	private $prevLevel = 0;

	/** @var array<int> Temporary TOC State */
	private $levelCount = [];

	/** @var array<int> Temporary TOC State */
	private $subLevelCount = [];

	/**
	 * Create a new TOCData object with the given sections and no
	 * extension data.
	 * @param SectionMetadata ...$sections
	 */
	public function __construct( ...$sections ) {
		$this->sections = $sections;
	}

	/**
	 * Return current TOC level while headings are being
	 * processed and section metadata is being constructed.
	 * @return int
	 */
	public function getCurrentTOCLevel(): int {
		return $this->tocLevel;
	}

	/**
	 * Add a new section to this TOCData.
	 * @param SectionMetadata $s
	 */
	public function addSection( SectionMetadata $s ) {
		$this->sections[] = $s;
	}

	/**
	 * Get the list of sections in the TOCData.
	 * @return SectionMetadata[]
	 */
	public function getSections() {
		return $this->sections;
	}

	/**
	 * Attaches arbitrary data to this TOCData object. This can be
	 * used to store some information about the table of contents in
	 * the ParserOutput object for later use during page output. The
	 * data will be cached along with the ParserOutput object.
	 *
	 * See ParserOutput::setExtensionData() in core for further information
	 * about typical usage in hooks, and SectionMetadata::setExtensionData()
	 * for a similar method appropriate for information about a specific
	 * section of the ToC.
	 *
	 * Setting conflicting values for the same key is not allowed.
	 * If you call ::setExtensionData() multiple times with the same key
	 * on a TOCData, is is expected that the value will be identical
	 * each time.  If you want to collect multiple pieces of data under a
	 * single key, use ::appendExtensionData().
	 *
	 * @note Only scalar values (numbers, strings, or arrays) are
	 * supported as a value.  (A future revision will allow anything
	 * that core's JsonCodec can handle.)  Attempts to set other types
	 * as extension data values will break ParserCache for the page.
	 *
	 * @todo When values more complex than scalar values get supported,
	 * __clone needs to be updated accordingly.
	 *
	 * @param string $key The key for accessing the data. Extensions
	 *   should take care to avoid conflicts in naming keys. It is
	 *   suggested to use the extension's name as a prefix.  Using
	 *   the prefix `mw:` is reserved for core.
	 *
	 * @param mixed $value The value to set.
	 *   Setting a value to null is equivalent to removing the value.
	 */
	public function setExtensionData( string $key, $value ): void {
		if (
			array_key_exists( $key, $this->extensionData ) &&
			$this->extensionData[$key] !== $value
		) {
			throw new \InvalidArgumentException( "Conflicting data for $key" );
		}
		if ( $value === null ) {
			unset( $this->extensionData[$key] );
		} else {
			$this->extensionData[$key] = $value;
		}
	}

	/**
	 * Appends arbitrary data to this TOCData. This can be used to
	 * store some information about the table of contents in the
	 * ParserOutput object for later use during page output.
	 *
	 * See ::setExtensionData() for more details on rationale and use.
	 *
	 * @param string $key The key for accessing the data. Extensions should take care to avoid
	 *   conflicts in naming keys. It is suggested to use the extension's name as a prefix.
	 *
	 * @param int|string $value The value to append to the list.
	 * @return never This method is not yet implemented.
	 */
	public function appendExtensionData( string $key, $value ): void {
		// This implementation would mirror that of
		// ParserOutput::appendExtensionData, but let's defer implementing
		// this until we're sure we need it.  In particular, we might need
		// to figure out how a merge on section data is expected to work
		// before we can determine the right semantics for this.
		throw new \InvalidArgumentException( "Not yet implemented" );
	}

	/**
	 * Gets extension data previously attached to this TOCData.
	 *
	 * @param string $key The key to look up
	 * @return mixed|null The value(s) previously set for the given key using
	 *   ::setExtensionData() or ::appendExtensionData(), or null if no
	 *  value was set for this key.
	 */
	public function getExtensionData( $key ) {
		$value = $this->extensionData[$key] ?? null;
		return $value;
	}

	/**
	 * Return as associative array, in the legacy format returned by the
	 * action API.
	 *
	 * This is helpful as b/c support while we transition to objects,
	 * but it drops some properties from this class and shouldn't be used
	 * in new code.
	 * @return array
	 */
	public function toLegacy(): array {
		return array_map(
			static function ( $s ) {
				return $s->toLegacy();
			},
			$this->sections
		);
	}

	/**
	 * Create a new TOCData object from the legacy associative array format.
	 *
	 * This is used for backward compatibility, but the associative array
	 * format does not include any properties of the TOCData other than the
	 * section list.
	 *
	 * @param array $data Associative array with ToC data in legacy format
	 * @return TOCData
	 */
	public static function fromLegacy( array $data ): TOCData {
		// The legacy format has no way to represent extension data.
		$sections = array_map(
			static function ( $d ) {
				return SectionMetadata::fromLegacy( $d );
			},
			$data
		);
		return new TOCData( ...$sections );
	}

	/**
	 * @param int $oldLevel level of the heading (H1/H2, etc.)
	 * @param int $level level of the heading (H1/H2, etc.)
	 * @param SectionMetadata $metadata This metadata will be updated
	 * This logic is copied from Parser.php::finalizeHeadings
	 */
	public function processHeading( int $oldLevel, int $level, SectionMetadata $metadata ): void {
		if ( $this->tocLevel ) {
			$this->prevLevel = $oldLevel;
		}

		if ( $level > $this->prevLevel ) {
			# increase TOC level
			$this->tocLevel++;
			$this->subLevelCount[$this->tocLevel] = 0;
		} elseif ( $level < $this->prevLevel && $this->tocLevel > 1 ) {
			# Decrease TOC level, find level to jump to
			for ( $i = $this->tocLevel; $i > 0; $i-- ) {
				if ( $this->levelCount[$i] === $level ) {
					# Found last matching level
					$this->tocLevel = $i;
					break;
				} elseif ( $this->levelCount[$i] < $level ) {
					# Found first matching level below current level
					$this->tocLevel = $i + 1;
					break;
				}
			}
			if ( $i === 0 ) {
				$this->tocLevel = 1;
			}
		}

		$this->levelCount[$this->tocLevel] = $level;

		# count number of headlines for each level
		$this->subLevelCount[$this->tocLevel]++;
		$numbering = '';
		$dot = false;
		for ( $i = 1; $i <= $this->tocLevel; $i++ ) {
			if ( !empty( $this->subLevelCount[$i] ) ) {
				if ( $dot ) {
					$numbering .= '.';
				}
				$numbering .= $this->subLevelCount[$i];
				$dot = true;
			}
		}

		$metadata->hLevel = $level;
		$metadata->tocLevel = $this->tocLevel;
		$metadata->number = $numbering;
	}

	/**
	 * Serialize all data in the TOCData as JSON.
	 *
	 * Unlike the `:toLegacy()` method, this method will include *all*
	 * of the properties in the TOCData so that the serialization is
	 * reversible.
	 *
	 * @inheritDoc
	 */
	public function jsonSerialize(): array {
		# T312589 explicitly calling jsonSerialize() on the elements of
		# $this->sections will be unnecessary in the future.
		$sections = array_map(
			static function ( SectionMetadata $s ) {
				return $s->jsonSerialize();
			},
			$this->sections
		);
		return [
			'sections' => $sections,
			'extensionData' => $this->extensionData,
		];
	}

	// JsonCodecable interface

	/** @inheritDoc */
	public function toJsonArray(): array {
		return [
			'sections' => $this->sections,
			'extensionData' => $this->extensionData,
		];
	}

	/** @inheritDoc */
	public static function newFromJsonArray( array $json ) {
		$tocData = new TOCData( ...$json['sections'] );
		foreach ( $json['extensionData'] as $key => $value ) {
			$tocData->setExtensionData( $key, $value );
		}
		return $tocData;
	}

	/** @inheritDoc */
	public static function jsonClassHintFor( string $keyName ): ?string {
		if ( $keyName === 'sections' ) {
			return SectionMetadata::class . '[]';
		}
		return null;
	}

	// Pretty-printing

	/**
	 * For use in parser tests and wherever else humans might appreciate
	 * some formatting in the JSON encoded output.
	 * @return string
	 */
	public function prettyPrint(): string {
		$out = [ "Sections:" ];
		foreach ( $this->sections as $s ) {
			$out[] = $s->prettyPrint();
		}
		if ( $this->extensionData ) {
			$out[] = "Extension Data:";
			$codec = new CompatJsonCodec();
			$out[] = json_encode( $codec->toJsonArray( $this->extensionData ) );
		}
		return implode( "\n", $out );
	}

	public function __clone() {
		foreach ( $this->sections as $k => $v ) {
			$this->sections[$k] = clone $v;
		}
	}
}
