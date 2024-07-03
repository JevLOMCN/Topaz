<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext\Gallery;

use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

abstract class Mode {
	/**
	 * The name of this mode.
	 * @var string
	 */
	protected $mode;

	/**
	 * Construct a (singleton) mode.
	 * @param string $name The name of this mode, all lowercase.
	 */
	protected function __construct( string $name ) {
		$this->mode = $name;
	}

	public function getModules(): array {
		return [];
	}

	public function getModuleStyles(): array {
		return [];
	}

	/**
	 * Format the dimensions as a string.
	 */
	abstract public function dimensions( Opts $opts ): string;

	/**
	 * Render HTML for the given lines in this mode.
	 * @param ParsoidExtensionAPI $extApi
	 * @param Opts $opts
	 * @param ?DocumentFragment $caption
	 * @param ParsedLine[] $lines
	 * @return DocumentFragment
	 */
	abstract public function render(
		ParsoidExtensionAPI $extApi, Opts $opts, ?DocumentFragment $caption,
		array $lines
	): DocumentFragment;

	/**
	 * Return the Mode object with the given name,
	 * or null if the name is invalid.
	 * @param string $name
	 * @return Mode|null
	 */
	public static function byName( string $name ): ?Mode {
		static $modesByName = null;
		if ( $modesByName === null ) {
			$modesByName = [
				'traditional' => new TraditionalMode(),
				'nolines' => new NoLinesMode(),
				'slideshow' => new SlideshowMode(),
				'packed' => new PackedMode(),
				'packed-hover' => new PackedHoverMode(),
				'packed-overlay' => new PackedOverlayMode()
			];
		}
		return $modesByName[$name] ?? null;
	}
}
