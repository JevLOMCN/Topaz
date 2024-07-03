<?php
declare( strict_types = 1 );
/**
 * This file contains general utilities for scripts in
 * the bin/, tools/, tests/ directories. This file should
 * not contain any helpers that are needed by code in the
 * lib/ directory.
 */

namespace Wikimedia\Parsoid\Utils;

class ScriptUtils {
	/**
	 * Split a tracing / debugging flag string into individual flags
	 * and return them as an associative array with flags as keys and true as value.
	 *
	 * @param string $origFlag The original flag string.
	 * @return array
	 */
	private static function fetchFlagsMap( string $origFlag ): array {
		$objFlags = explode( ',', $origFlag );
		if ( in_array( 'selser', $objFlags, true ) && !in_array( 'wts', $objFlags, true ) ) {
			$objFlags[] = 'wts';
		}
		return array_fill_keys( $objFlags, true );
	}

	/**
	 * @return-taint none
	 */
	private static function getScriptName(): string {
		return basename( $_SERVER["SCRIPT_FILENAME"] );
	}

	/**
	 * Returns a help message for the tracing flags.
	 *
	 * @return string
	 */
	public static function traceUsageHelp(): string {
		$script = self::getScriptName();
		return implode(
			"\n", [
				'Tracing',
				'-------',
				'- With one or more comma-separated flags, traces those specific phases',
				'- Supported flags:',
				'  * peg       : shows tokens emitted by tokenizer',
				'  * ttm:2     : shows tokens flowing through stage 2 of the parsing pipeline',
				'  * ttm:3     : shows tokens flowing through stage 3 of the parsing pipeline',
				'  * tsp       : shows tokens flowing through the TokenStreamPatcher '
					. '(useful to see in-order token stream)',
				'  * list      : shows actions of the list handler',
				'  * sanitizer : shows actions of the sanitizer',
				'  * pre       : shows actions of the pre handler',
				'  * p-wrap    : shows actions of the paragraph wrapper',
				'  * html      : shows tokens that are sent to the HTML tree builder',
				'  * remex     : shows RemexHtml\'s tree mutation events',
				'  * dsr       : shows dsr computation on the DOM',
				'  * tplwrap   : traces template wrapping code (currently only range overlap/nest/merge code)',
				'  * wts       : trace actions of the regular wikitext serializer',
				'  * selser    : trace actions of the selective serializer',
				'  * domdiff   : trace actions of the DOM diffing code',
				'  * wt-escape : debug wikitext-escaping',
				'  * apirequest: trace all API requests',
				'  * time      : trace times for various phases',
				'',
				'--debug enables tracing of all the above phases except Token Transform Managers',
				'',
				'Examples:',
				"$ php $script --trace pre,p-wrap,html < foo",
				"$ php $script --trace ttm:3,dsr < foo",
				''
			]
		);
	}

	/**
	 * Returns a help message for the dump flags.
	 *
	 * @return string
	 */
	public static function dumpUsageHelp(): string {
		$script = self::getScriptName();
		return implode(
			"\n", [
				'Dumping state',
				'-------------',
				'- Dumps state at different points of execution',
				'- DOM dumps are always doc.outerHTML',
				'- Supported flags:',
				'',
				'  * tplsrc            : dumps preprocessed template source that will be tokenized '
					. '(via ?action=expandtemplates)',
				'  * extoutput         : dumps HTML output form extensions (via ?action=parse)',
				'',
				'  --- Dump flags for wt2html DOM passes ---',
				'  * dom:pre-XXX       : dumps DOM before pass XXX runs',
				'  * dom:pre-*         : dumps DOM before every pass',
				'  * dom:post-XXX      : dumps DOM after pass XXX runs',
				'  * dom:post-*        : dumps DOM after every pass',
				'',
				'    Available passes (in the order they run):',
				'',
				'      fostered, process-fixups, Normalize, pwrap, ',
				'      media, migrate-metas, migrate-nls, dsr, tplwrap, ',
				'      dom-unpack, pp:EXT (replace EXT with extension: Pre, Gallery, etc)',
				'      fixups, strip-metas, lang-converter, redlinks, ',
				'      displayspace, linkclasses, sections, convertoffsets',
				'      i18n, cleanup',
				'',
				'  --- Dump flags for html2wt ---',
				'  * dom:post-dom-diff : in selective serialization, dumps DOM after running dom diff',
				'  * dom:post-normal   : in serialization, dumps DOM after normalization',
				"  * wt2html:limits    : dumps used resources (along with configured limits)\n",
				"--debug dumps state at these different stages\n",
				'Examples:',
				"$ php $script --dump dom:pre-dsr,dom:pre-tplwrap < foo",
				"$ php $script --trace html --dump dom:pre-tplwrap < foo",
				"\n"
			]
		);
	}

	/**
	 * Returns a help message for the debug flags.
	 *
	 * @return string
	 */
	public static function debugUsageHelp(): string {
		return implode(
			"\n", [
				'Debugging',
				'---------',
				'- With one or more comma-separated flags, ' .
					'provides more verbose tracing than the equivalent trace flag',
				'- Supported flags:',
				'  * pre       : shows actions of the pre handler',
				'  * wts       : trace actions of the regular wikitext serializer',
				'  * selser    : trace actions of the selective serializer'
			]
		);
	}

	/**
	 * Set debugging flags on an object, based on an options object.
	 *
	 * @param array &$envOptions Options to be passed to the Env constructor.
	 * @param array $cliOpts The options object to use for setting the debug flags.
	 * @return array The modified object.
	 */
	public static function setDebuggingFlags( array &$envOptions, array $cliOpts ): array {
		$traceOpt = $cliOpts['trace'] ?? null;
		$dumpOpt  = $cliOpts['dump'] ?? null;
		$debugOpt = $cliOpts['debug'] ?? null;

		// Handle the --help options
		$exit = false;
		if ( $traceOpt === 'help' ) {
			print self::traceUsageHelp();
			$exit = true;
		}
		if ( $dumpOpt === 'help' ) {
			print self::dumpUsageHelp();
			$exit = true;
		}
		if ( $debugOpt === 'help' ) {
			print self::debugUsageHelp();
			$exit = true;
		}
		if ( $exit ) {
			die( 1 );
		}

		// Ok, no help requested: process the options.
		if ( $debugOpt !== null ) {
			// Continue to support generic debugging.
			if ( $debugOpt === true ) {
				error_log( 'Warning: Generic debugging, not handler-specific.' );
				$envOptions['debug'] = self::booleanOption( $debugOpt );
			} else {
				// Setting --debug automatically enables --trace
				$envOptions['debugFlags'] = self::fetchFlagsMap( $debugOpt );
				$envOptions['traceFlags'] = $envOptions['debugFlags'];
			}
		}

		if ( $traceOpt !== null ) {
			if ( $traceOpt === true ) {
				error_log(
					"Warning: Generic tracing is no longer supported. "
					. "Ignoring --trace flag. "
					. "Please provide handler-specific tracing flags, "
					. "e.g. '--trace pre,html5', to turn it on." );
			} else {
				// Add any new trace flags to the list of existing trace flags (if
				// any were inherited from debug); otherwise, create a new list.
				$envOptions['traceFlags'] = array_merge( $envOptions['traceFlags'] ?? [],
					self::fetchFlagsMap( $traceOpt ) );
			}
		}

		if ( $dumpOpt !== null ) {
			if ( $dumpOpt === true ) {
				error_log( 'Warning: Generic dumping not enabled. Please set a flag.' );
			} else {
				$envOptions['dumpFlags'] = self::fetchFlagsMap( $dumpOpt );
			}
		}

		return $envOptions;
	}

	/**
	 * Sets templating and processing flags on an object,
	 * based on an options object.
	 *
	 * @param array &$envOptions Options to be passed to the Env constructor.
	 * @param array $cliOpts The options object to use for setting the debug flags.
	 * @return array The modified object.
	 */
	public static function setTemplatingAndProcessingFlags(
		array &$envOptions, array $cliOpts
	): array {
		$templateFlags = [
			'fetchConfig',
			'fetchTemplates',
			'fetchImageInfo',
			'expandExtensions',
			'addHTMLTemplateParameters'
		];

		foreach ( $templateFlags as $c ) {
			if ( isset( $cliOpts[$c] ) ) {
				$envOptions[$c] = self::booleanOption( $cliOpts[$c] );
			}
		}

		if ( isset( $cliOpts['usePHPPreProcessor'] ) ) {
			$envOptions['usePHPPreProcessor'] = $envOptions['fetchTemplates'] &&
				self::booleanOption( $cliOpts['usePHPPreProcessor'] );
		}

		if ( isset( $cliOpts['maxDepth'] ) ) {
			$envOptions['maxDepth'] =
				is_numeric( $cliOpts['maxdepth'] ) ?
					$cliOpts['maxdepth'] : $envOptions['maxDepth'];
		}

		if ( isset( $cliOpts['apiURL'] ) ) {
			$envOptions['mwApis'] ??= [];
			$envOptions['mwApis'][] = [ 'prefix' => 'customwiki', 'uri' => $cliOpts['apiURL'] ];
		}

		if ( isset( $cliOpts['addHTMLTemplateParameters'] ) ) {
			$envOptions['addHTMLTemplateParameters'] =
				self::booleanOption( $cliOpts['addHTMLTemplateParameters'] );
		}

		if ( isset( $cliOpts['lint'] ) ) {
			$envOptions['linting'] = true;
		}

		return $envOptions;
	}

	/**
	 * Parse a boolean option returned by our opts processor.
	 * The strings 'false' and 'no' are also treated as false values.
	 * This allows `--debug=no` and `--debug=false` to mean the same as
	 * `--no-debug`.
	 *
	 * @param bool|string $val
	 *   a boolean, or a string naming a boolean value.
	 * @return bool
	 */
	public static function booleanOption( $val ): bool {
		return $val && $val !== 'no' && $val !== 'false';
	}

	/**
	 * Set the color flags, based on an options object.
	 *
	 * @param array $options options object to use for setting the mode of the 'color' package.
	 *  - string|boolean options.color
	 *    Whether to use color.
	 *    Passing 'auto' will enable color only if stdout is a TTY device.
	 * @suppress PhanEmptyPublicMethod
	 */
	public static function setColorFlags( array $options ): void {
		/**
		 * PORT-FIXME:
		 * if ( $options->color === 'auto' ) {
		 * if ( !$process->stdout->isTTY ) {
		 * $colors->mode = 'none';
		 * }
		 * } elseif ( !self::booleanOption( $options->color ) ) {
		 * $colors->mode = 'none';
		 * }
		 */
	}

	/**
	 * PORT-FIXME: Should some of this functionality be moved to OptsProcessor directly?
	 *
	 * Add standard options to script-specific opts
	 * This handles options parsed by `setDebuggingFlags`,
	 * `setTemplatingAndProcessingFlags`, `setColorFlags`,
	 * and standard --help options.
	 *
	 * The `defaults` option is optional, and lets you override
	 * the defaults for the standard options.
	 *
	 * @param array $opts
	 * @param array $defaults
	 * @return array
	 */
	public static function addStandardOptions( array $opts, array $defaults = [] ): array {
		$standardOpts = [
			// standard CLI options
			'help' => [
				'description' => 'Show this help message',
				'boolean' => true,
				'default' => false,
				'alias' => 'h'
			],
			// handled by `setDebuggingFlags`
			'debug' => [
				'description' => 'Provide optional flags. Use --debug=help for supported options'
			],
			'trace' => [
				'description' => 'Use --trace=help for supported options'
			],
			'dump' => [
				'description' => 'Dump state. Use --dump=help for supported options'
			],
			// handled by `setTemplatingAndProcessingFlags`
			'fetchConfig' => [
				'description' => 'Whether to fetch the wiki config from the server or use our local copy',
				'boolean' => true,
				'default' => true
			],
			'fetchTemplates' => [
				'description' => 'Whether to fetch included templates recursively',
				'boolean' => true,
				'default' => true
			],
			'fetchImageInfo' => [
				'description' => 'Whether to fetch image info via the API',
				'boolean' => true,
				'default' => true
			],
			'expandExtensions' => [
				'description' => 'Whether we should request extension tag expansions from a wiki',
				'boolean' => true,
				'default' => true
			],
			'usePHPPreProcessor' => [
				'description' => 'Whether to use the PHP preprocessor to expand templates',
				'boolean' => true,
				'default' => true
			],
			'addHTMLTemplateParameters' => [
				'description' => 'Parse template parameters to HTML and add them to template data',
				'boolean' => true,
				'default' => false
			],
			'maxdepth' => [
				'description' => 'Maximum expansion depth',
				'default' => 40
			],
			'apiURL' => [
				'description' => 'http path to remote API, e.g. http://en.wikipedia.org/w/api.php',
				'default' => null
			],
			// handled by `setColorFlags`
			'color' => [
				'description' => 'Enable color output Ex: --no-color',
				'default' => 'auto'
			]
		];

		// allow overriding defaults
		foreach ( $defaults as $name => $default ) {
			if ( isset( $standardOpts[$name] ) ) {
				$standardOpts[$name]['default'] = $default;
			}
		}

		// Values in $opts take precedence
		return $opts + $standardOpts;
	}
}
