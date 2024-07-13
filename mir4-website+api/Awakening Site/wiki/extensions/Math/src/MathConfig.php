<?php

namespace MediaWiki\Extension\Math;

use ExtensionRegistry;
use MediaWiki\Config\ServiceOptions;
use Message;
use Wikibase\Client\WikibaseClient;

class MathConfig {

	/** @var string[] */
	public const CONSTRUCTOR_OPTIONS = [
		'MathDisableTexFilter',
		'MathValidModes',
		'MathEntitySelectorFallbackUrl'
	];

	/** @var string */
	public const ALWAYS = 'always';

	/** @var string */
	public const NEVER = 'never';

	/** @var string */
	public const NEW = 'new';

	/** @var string use input tex as formula rendering */
	public const MODE_SOURCE = 'source';

	/** @var string render formula into MathML */
	public const MODE_MATHML = 'mathml';

	/** @var string render formula into LateXML */
	public const MODE_LATEXML = 'latexml';

	/** @var string render formula into MathML using PHP (currently in development) */
	public const MODE_NATIVE_MML = 'native';
	/** @var string render formula into MathML using PHP and output it via MathJax */
	public const MODE_NATIVE_JAX = 'mathjax';

	/** @var string[] a list of all supported rendering modes */
	private const SUPPORTED_MODES = [
		self::MODE_SOURCE,
		self::MODE_LATEXML,
		self::MODE_MATHML,
		self::MODE_NATIVE_MML,
		self::MODE_NATIVE_JAX
	];

	/**
	 * @var array mapping from rendering mode to user options value
	 */
	private const MODES_TO_USER_OPTIONS = [
		self::MODE_SOURCE => 3,
		self::MODE_MATHML => 5,
		self::MODE_LATEXML => 7,
		self::MODE_NATIVE_MML => 8,
		self::MODE_NATIVE_JAX => 9
	];

	private ServiceOptions $options;
	private ExtensionRegistry $registry;

	/**
	 * @param ServiceOptions $options
	 * @param ExtensionRegistry $registry
	 */
	public function __construct(
		ServiceOptions $options,
		ExtensionRegistry $registry

	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->registry = $registry;
	}

	/**
	 * Whether the TEX filter is disabled.
	 * @return string one of self::CHECK_* constants
	 */
	public function texCheckDisabled(): string {
		$setting = $this->options->get( 'MathDisableTexFilter' );
		if ( $setting === true ) {
			// ensure backwards compatibility
			return self::NEVER;
		}
		$setting = strtolower( $setting );
		if ( in_array( $setting, [ self::NEVER, self::ALWAYS, self::NEW ], true ) ) {
			return $setting;
		}
		return self::ALWAYS;
	}

	/**
	 * Returns an array of valid rendering modes.
	 *
	 * @return string[]
	 */
	public function getValidRenderingModes(): array {
		// NOTE: this method is copy-pasted into HookHandlers\SchemaHooksHandler::onLoadExtensionSchemaUpdates
		// since we can't inject services in there.

		$modes = array_map(
			[ __CLASS__, 'normalizeRenderingMode' ],
			$this->options->get( 'MathValidModes' )
		);
		return array_unique( $modes );
	}

	/**
	 * Get message keys for the names of the valid rendering modes.
	 *
	 * @return string[]
	 */
	public function getValidRenderingModeKeys(): array {
		$result = [];
		foreach ( $this->getValidRenderingModes() as $mode ) {
			$result[$mode] = 'mw-math-' . $mode;
		}
		return $result;
	}

	/**
	 * Get Messages for the names of the valid rendering
	 * modes.
	 *
	 * @return Message[]
	 */
	public function getValidRenderingModeNames(): array {
		$result = [];
		foreach ( $this->getValidRenderingModes() as $mode ) {
			$result[$mode] = Message::newFromKey( 'mw-math-' . $mode );
		}
		return $result;
	}

	/**
	 * Get the human-readable name of a rendering mode.
	 *
	 * @param string $mode one of self::MODE_* constants.
	 * @return Message
	 */
	public function getRenderingModeName( string $mode ): Message {
		return Message::newFromKey( 'mw-math-' . $mode );
	}

	/**
	 * Checks whether $mode is a valid rendering mode.
	 *
	 * @param string $mode
	 * @return bool
	 */
	public function isValidRenderingMode( string $mode ): bool {
		return in_array( $mode, $this->getValidRenderingModes(), true );
	}

	/**
	 * Get the normalized name of the rendering mode
	 * @param string|int $mode
	 * @param string $default rendering mode to use by default on unrecognized input
	 * @return string one of the self::MODE_* constants.
	 */
	public static function normalizeRenderingMode( $mode, string $default = self::MODE_MATHML ): string {
		if ( is_int( $mode ) ) {
			$userOptionToMode = array_flip( self::MODES_TO_USER_OPTIONS );
			return $userOptionToMode[$mode] ?? $default;
		}
		$mode = strtolower( $mode );
		if ( in_array( $mode, self::SUPPORTED_MODES, true ) ) {
			return $mode;
		}
		return $default;
	}

	/**
	 * If the WikibaseClient is enabled the API url of that client is returned, otherwise the
	 * fallback url is used.
	 * @return string url of the Wikibase url
	 */
	public function getMathEntitySelectorUrl(): string {
		// @see WikibaseSettings::isClientEnabled()
		if ( $this->registry->isLoaded( 'WikibaseClient' ) ) {
			$settings = WikibaseClient::getSettings();
			return $settings->getSetting( 'repoUrl' ) .
				$settings->getSetting( 'repoScriptPath' ) .
				'/api.php';

		}
		return $this->options->get( 'MathEntitySelectorFallbackUrl' );
	}
}
