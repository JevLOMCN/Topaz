<?php
// phpcs:disable Generic.Files.LineLength.TooLong
require_once __DIR__ . '/../tools/Maintenance.php';

use MediaWiki\Settings\SettingsBuilder;
use SebastianBergmann\Diff\Differ;
use Wikimedia\Parsoid\ParserTests\Stats;
use Wikimedia\Parsoid\ParserTests\Test;
use Wikimedia\Parsoid\ParserTests\TestRunner;
use Wikimedia\Parsoid\ParserTests\TestUtils;
use Wikimedia\Parsoid\Utils\ScriptUtils;

// phpcs:ignore MediaWiki.Files.ClassMatchesFilename.WrongCase
class ParserTests extends \Wikimedia\Parsoid\Tools\Maintenance {
	use \Wikimedia\Parsoid\Tools\ExtendedOptsProcessor;

	/** @var array */
	public $processedOptions;

	// PORT-FIXME: Used to be colors::mode in all the use sites
	public static $colors_mode;

	/** @var Differ */
	private static $differ;

	public function __construct() {
		parent::__construct();
		self::setupOpts( $this );
		$this->setAllowUnregisteredOptions( false );
	}

	/**
	 * @inheritDoc
	 */
	public function finalSetup( SettingsBuilder $settingsBuilder = null ) {
		parent::finalSetup( $settingsBuilder );
		self::requireTestsAutoloader();
	}

	public function execute(): bool {
		$this->processedOptions = self::processOptions( $this );

		$testFile = $this->getArg( 0 );
		if ( $testFile ) {
			$testFilePaths = [ realpath( $testFile ) ];
		} else {
			$testFilePaths = [];
			$repos = json_decode( file_get_contents( __DIR__ . '/../tests/parserTests.json' ), true );
			foreach ( $repos as $repo => $repoInfo ) {
				foreach ( $repoInfo["targets"] as $f => $info ) {
					$testFilePaths[] = realpath( __DIR__ . '/../tests/parser/' . $f );
				}
			}
		}

		$globalStats = new Stats();
		$knownFailuresChanged = false;
		$exitCode = 0;
		if ( $this->processedOptions['integrated'] ?? false ) {
			// Some methods which are discouraged for normal code throw
			// exceptions unless we declare this is just a test.
			define( 'MW_PARSER_TEST', true );

			// See Ifaf53862b96e9127d8f375ad8dd0cc362cba9f5b in gerrit;
			// this should use the \ParserTestRunner::runParsoidTest()
			// method from core.
			throw new \RuntimeException( "Not yet implemented" );
		} else {
			foreach ( $testFilePaths as $testFile ) {
				$testRunner = new TestRunner( $testFile, 'standalone', $this->processedOptions['modes'] );
				$result = $testRunner->run( $this->processedOptions );
				$globalStats->accum( $result['stats'] ); // Sum all stats
				$knownFailuresChanged = $knownFailuresChanged || $result['knownFailuresChanged'];
				$exitCode = $exitCode ?: $result['exitCode'];
				if ( $exitCode !== 0 && $this->processedOptions['exit-unexpected'] ) {
					break;
				}
			}
		}

		$this->processedOptions['reportSummary'](
			[], $globalStats, null, null, $knownFailuresChanged, $this->processedOptions
		);

		return $exitCode === 0;
	}

	/**
	 * Process CLI opts and return
	 *
	 * @param \Wikimedia\Parsoid\Tools\Maintenance $script
	 */
	public static function setupOpts( \Wikimedia\Parsoid\Tools\Maintenance $script ): void {
		$opts = ScriptUtils::addStandardOptions( [
			'wt2html' => [
				'description' => 'Wikitext -> HTML(DOM)',
				'default' => false,
				'boolean' => true
			],
			'html2wt' => [
				'description' => 'HTML(DOM) -> Wikitext',
				'default' => false,
				'boolean' => true
			],
			'wt2wt' => [
				'description' => 'Roundtrip testing: Wikitext -> DOM(HTML) -> Wikitext',
				'default' => false,
				'boolean' => true
			],
			'html2html' => [
				'description' => 'Roundtrip testing: HTML(DOM) -> Wikitext -> HTML(DOM)',
				'default' => false,
				'boolean' => true
			],
			'selser' => [
				'description' => 'Roundtrip testing: Wikitext -> DOM(HTML) -> Wikitext (with selective serialization). ' .
					'Set to "noauto" to just run the tests with manual selser changes.',
				'boolean' => false
			],
			'changetree' => [
				'description' => 'Changes to apply to parsed HTML to generate new HTML to be serialized (useful with selser)',
				'default' => null,
				'boolean' => false
			],
			'numchanges' => [
				'description' => 'Make multiple different changes to the DOM, run a selser test for each one.',
				'default' => 20,
				'boolean' => false
			],
			'cache' => [
				'description' => 'Get tests cases from cache file',
				'boolean' => true,
				'default' => false
			],
			'filter' => [
				'description' => 'Only run tests whose descriptions match given string'
			],
			'regex' => [
				'description' => 'Only run tests whose descriptions match given regex',
				'alias' => [ 'regexp', 're' ]
			],
			'run-disabled' => [
				'description' => 'Run disabled tests',
				'default' => false,
				'boolean' => true
			],
			'run-php' => [
				'description' => 'Run php-only tests',
				'default' => false,
				'boolean' => true
			],
			'maxtests' => [
				'description' => 'Maximum number of tests to run',
				'boolean' => false
			],
			'quick' => [
				'description' => 'Suppress diff output of failed tests',
				'boolean' => true,
				'default' => false
			],
			'quiet' => [
				'description' => 'Suppress notification of passed tests (shows only failed tests)',
				'boolean' => true,
				'default' => false
			],
			'quieter' => [
				'description' => 'Suppress per-file summary and failed test diffs. ' .
					'Implies --quick and --quiet.',
				'boolean' => true,
				'default' => false
			],
			'offsetType' => [
				'description' => 'Test DSR offset conversion code while running tests.',
				'boolean' => false,
				'default' => 'byte',
			],
			'knownFailures' => [
				'description' => 'Compare against known failures',
				'default' => true,
				'boolean' => false
			],
			'updateKnownFailures' => [
				'description' => 'Update parserTests-knownFailures.json with failing tests.',
				'default' => false,
				'boolean' => true
			],
			'exit-zero' => [
				'description' => "Don't exit with nonzero status if failures are found.",
				'default' => false,
				'boolean' => true
			],
			'xml' => [
				'description' => 'Print output in JUnit XML format.',
				'default' => false,
				'boolean' => true
			],
			'exit-unexpected' => [
				'description' => 'Exit after the first unexpected result.',
				'default' => false,
				'boolean' => true
			],
			'update-tests' => [
				'description' => 'Update parserTests.txt with results from wt2html fails.',
				'default' => false,
				'boolean' => true
			],
			'update-unexpected' => [
				'description' => 'Update parserTests.txt with results from wt2html unexpected fails.',
				'default' => false,
				'boolean' => true
			],
			'update-format' => [
				'description' => 'format with which to update tests; only useful in conjunction ' .
					'with update-tests or update-unexpected. Values: raw, noDsr, actualNormalized.',
				'default' => 'noDsr',
			]
		], [
			// override defaults for standard options
			'fetchTemplates' => false,
			'usePHPPreProcessor' => false,
			'fetchConfig' => false
		] );

		foreach ( $opts as $opt => $optInfo ) {
			$script->addOption( $opt,
				$optInfo['description'], false, empty( $optInfo['boolean'] ), false );
			if ( isset( $optInfo['default'] ) ) {
				$script->setOptionDefault( $opt, $optInfo['default'] );
			}
		}
	}

	public static function processOptions( \Wikimedia\Parsoid\Tools\Maintenance $script ): array {
		$options = $script->optionsToArray();

		if ( $options['help'] ) {
			$script->maybeHelp();
			print "Additional dump options specific to parserTests script:\n"
			 . "* dom:post-changes  : Dumps DOM after applying selser changetree\n"
			 . "Examples\n"
			 . "\$ php parserTests.php --selser --filter '...' --dump dom:post-changes\n"
			 . "\$ php parserTests.php --selser --filter '...' --changetree '...' --dump dom:post-changes\n";
			die( 0 );
		}

		ScriptUtils::setColorFlags( $options );

		if ( !( $options['wt2wt'] || $options['wt2html'] || $options['html2wt'] || $options['html2html']
			|| isset( $options['selser'] ) )
		) {
			$options['wt2wt'] = true;
			$options['wt2html'] = true;
			$options['html2html'] = true;
			$options['html2wt'] = true;
			if ( ScriptUtils::booleanOption( $options['updateKnownFailures'] ?? null ) ) {
				// turn on all modes by default for --updateKnownFailures
				$options['selser'] = true;
				// double checking options are valid (T53448 asks to be able to use --filter here)
				if ( isset( $options['filter'] ) || isset( $options['regex'] ) ||
					isset( $options['maxtests'] ) || $options['exit-unexpected']
				) {
					print "\nERROR: can't combine --updateKnownFailures with --filter, --maxtests or --exit-unexpected";
					die( 1 );
				}
			}
		}

		if ( $options['xml'] ) {
			$options['reportResult']  = [ self::class, 'reportResultXML' ];
			$options['reportStart']   = [ self::class, 'reportStartXML' ];
			$options['reportSummary'] = [ self::class, 'reportSummaryXML' ];
			$options['reportFailure'] = [ self::class, 'reportFailureXML' ];
			self::$colors_mode = 'none';
		}

		if ( !is_callable( $options['reportFailure'] ?? null ) ) {
			// default failure reporting is standard out,
			// see printFailure for documentation of the default.
			$options['reportFailure'] = [ self::class, 'printFailure' ];
		}

		if ( !is_callable( $options['reportSuccess'] ?? null ) ) {
			// default success reporting is standard out,
			// see printSuccess for documentation of the default.
			$options['reportSuccess'] = [ self::class, 'printSuccess' ];
		}

		if ( !is_callable( $options['reportStart'] ?? null ) ) {
			// default summary reporting is standard out,
			// see reportStart for documentation of the default.
			$options['reportStart'] = [ self::class, 'reportStartOfTests' ];
		}

		if ( !is_callable( $options['reportSummary'] ?? null ) ) {
			// default summary reporting is standard out,
			// see reportSummary for documentation of the default.
			$options['reportSummary'] = [ self::class, 'reportSummary' ];
		}

		if ( !is_callable( $options['reportResult'] ?? null ) ) {
			// default result reporting is standard out,
			// see printResult for documentation of the default.
			$options['reportResult'] = function ( ...$args ) use ( &$options ) {
				return self::printResult( $options['reportFailure'], $options['reportSuccess'], ...$args );
			};
		}

		if ( !is_callable( $options['getDiff'] ?? null ) ) {
			// this is the default for diff-getting, but it can be overridden
			// see doDiff for documentation of the default.
			$options['getDiff'] = [ self::class, 'doDiff' ];
		}

		if ( !is_callable( $options['getActualExpected'] ?? null ) ) {
			// this is the default for getting the actual and expected
			// outputs, but it can be overridden
			// see getActualExpected for documentation of the default.
			$options['getActualExpected'] = [ self::class, 'getActualExpected' ];
		}

		$options['modes'] = [];
		foreach ( Test::ALL_TEST_MODES as $m ) {
			if ( ( $m !== 'selser' && $options[$m] ) ||
				( $m === 'selser' && isset( $options[$m] ) )
			) {
				$options['modes'][] = $m;
			}
		}

		return $options;
	}

	/**
	 * Colorize given number if <> 0.
	 *
	 * @param int $count
	 * @param string $color
	 * @return string Colorized count
	 */
	private static function colorizeCount( int $count, string $color ): string {
		$s = (string)$count;
		return TestUtils::colorString( $s, $color );
	}

	/**
	 * @param array $modesRan
	 * @param Stats $stats
	 *  - failedTests int Number of failed tests due to differences in output.
	 *  - passedTests int Number of tests passed without any special consideration.
	 *  - modes array All of the stats (failedTests, passedTests) per-mode.
	 * @param ?string $file
	 * @param ?array $testFilter
	 * @param bool $knownFailuresChanged
	 * @param array $options
	 */
	public static function reportSummary(
		array $modesRan, Stats $stats, ?string $file, ?array $testFilter,
		bool $knownFailuresChanged, array $options
	): void {
		$curStr = null;
		$mode = null;
		$thisMode = null;
		$failTotalTests = $stats->failedTests;
		$happiness = $stats->passedTestsUnexpected === 0 && $stats->failedTestsUnexpected === 0;
		$filename = $file === null ? 'ALL TESTS' : $file;

		$quieter = ScriptUtils::booleanOption( $options['quieter'] ?? '' );
		if ( $quieter && $file !== null ) {
			return;
		}

		if ( !$quieter ) {
			print "==========================================================\n";
			print 'SUMMARY:' . TestUtils::colorString( $filename, $happiness ? 'green' : 'red' ) .
				"\n";
		}
		if ( $file !== null ) {
			print 'Execution time: ' . round( 1000 * ( microtime( true ) - $stats->startTime ), 3 ) . "ms\n";
		}

		if ( $failTotalTests !== 0 ) {
			foreach ( $modesRan as $mode ) {
				$curStr = $mode . ': ';
				$thisMode = $stats->modes[$mode];
				$curStr .= self::colorizeCount( $thisMode->passedTests, 'green' ) . ' passed (';
				$curStr .= self::colorizeCount( $thisMode->passedTestsUnexpected, 'red' ) . ' unexpected) / ';
				$curStr .= self::colorizeCount( $thisMode->failedTests, 'red' ) . ' failed (';
				$curStr .= self::colorizeCount( $thisMode->failedTestsUnexpected, 'red' ) . ' unexpected)';
				print $curStr . "\n";
			}

			$curStr = 'TOTAL' . ': ';
			$curStr .= self::colorizeCount( $stats->passedTests, 'green' ) . ' passed (';
			$curStr .= self::colorizeCount( $stats->passedTestsUnexpected, 'red' ) . ' unexpected) / ';
			$curStr .= self::colorizeCount( $stats->failedTests, 'red' ) . ' failed (';
			$curStr .= self::colorizeCount( $stats->failedTestsUnexpected, 'red' ) . ' unexpected)';
			print $curStr . "\n";

			if ( $file === null ) {
				$buf = self::colorizeCount( $stats->passedTests, 'green' );
				$buf .= ' total passed tests (expected ';
				$buf .= (string)( $stats->passedTests - $stats->passedTestsUnexpected + $stats->failedTestsUnexpected );
				$buf .=	'), ';
				$buf .= self::colorizeCount( $failTotalTests, 'red' ) . ' total failures (expected ';
				$buf .= (string)( $stats->failedTests - $stats->failedTestsUnexpected + $stats->passedTestsUnexpected );
				$buf .= ")\n";
				print $buf;
			}
		} else {
			if ( $testFilter !== null ) {
				$buf = 'Passed ' . $stats->passedTests . ' of '
					. $stats->passedTests . ' tests matching ' . $testFilter['raw'];
			} else {
				// Should not happen if it does: Champagne!
				$buf = 'Passed ' . $stats->passedTests . ' of ' . $stats->passedTests .	' tests';
			}
			print $buf . '... ' . TestUtils::colorString( 'ALL TESTS PASSED!', 'green' ) . "\n";
		}

		// If we logged error messages, complain about it.
		$logMsg = TestUtils::colorString( 'No errors logged.', 'green' );
		if ( $stats->loggedErrorCount > 0 ) {
			$logMsg = TestUtils::colorString( $stats->loggedErrorCount . ' errors logged.', 'red' );
		}
		if ( $file === null ) {
			if ( $stats->loggedErrorCount > 0 ) {
				$logMsg = TestUtils::colorString( '' . $stats->loggedErrorCount, 'red' );
			} else {
				$logMsg = TestUtils::colorString( '' . $stats->loggedErrorCount, 'green' );
			}
			$logMsg .= ' errors logged.';
		}
		print $logMsg . "\n";

		$failures = $stats->allFailures();

		// If the knownFailures changed, complain about it.
		if ( $knownFailuresChanged ) {
			print TestUtils::colorString( 'Known failures changed!', 'red' ) . "\n";
		}

		if ( $file === null ) {
			if ( $failures === 0 ) {
				print '--> ' . TestUtils::colorString( 'NO UNEXPECTED RESULTS', 'green' ) . " <--\n";
				if ( $knownFailuresChanged ) {
					print "Perhaps some tests were deleted or renamed.\n";
					print "Use `php bin/parserTests.php --updateKnownFailures` to update knownFailures list.\n";
				}
			} else {
				print TestUtils::colorString( '--> ' . $failures . ' UNEXPECTED RESULTS. <--', 'red' ) . "\n";
			}
		}
	}

	private static function prettyPrintIOptions(
		?array $iopts = null
	): string {
		if ( !$iopts ) {
			return '';
		}

		$ppValue = null; // Forward declaration
		$ppValue = static function ( $v ) use ( &$ppValue ) {
			if ( is_array( $v ) ) {
				return implode( ',', array_map( $ppValue, $v ) );
			}

			if ( is_string( $v ) &&
				( preg_match( '/^\[\[[^\]]*\]\]$/D', $v ) || preg_match( '/^[-\w]+$/D', $v ) )
			) {
				return $v;
			}

			return json_encode( $v );
		};

		$strPieces = array_map(
			static function ( $k ) use ( $iopts, $ppValue ) {
				if ( $iopts[$k] === '' ) {
					return $k;
				}
				return $k . '=' . $ppValue( $iopts[$k] );
			},
			array_keys( $iopts )
		);
		return implode( ' ', $strPieces );
	}

	/**
	 * @param Stats $stats
	 * @param Test $item
	 * @param array $options
	 * @param string $mode
	 * @param string $title
	 * @param array $actual
	 * @param array $expected
	 * @param ?string $expectFail If this test was expected to fail (on knownFailures list), then the expected failure output; otherwise null.
	 * @return bool true if the failure was expected.
	 */
	public static function printFailure(
		Stats $stats, Test $item, array $options, string $mode, string $title,
		array $actual, array $expected, ?string $expectFail
	): bool {
		$quiet = ScriptUtils::booleanOption( $options['quiet'] ?? null );
		$quieter = ScriptUtils::booleanOption( $options['quieter'] ?? null );
		$quick = ScriptUtils::booleanOption( $options['quick'] ?? null );
		$failureOnly = $quieter || $quick;
		$extTitle = str_replace( "\n", ' ', "$title ($mode)" );

		$knownFailures = false;
		if ( ScriptUtils::booleanOption( $options['knownFailures'] ?? null ) && $expectFail !== null ) {
			// compare with remembered output
			$offsetType = $options['offsetType'] ?? 'byte';
			if ( TestUtils::normalizeAbout( $expectFail ) !== TestUtils::normalizeAbout( $actual['raw'] ) && $offsetType === 'byte' ) {
				$knownFailures = true;
			} else {
				if ( !$quiet && !$quieter ) {
					print TestUtils::colorString( 'EXPECTED FAIL', 'red' ) . ': ' . TestUtils::colorString( $extTitle, 'yellow' ) . "\n";
				}
				return true;
			}
		}

		if ( !$failureOnly ) {
			print "=====================================================\n";
		}

		if ( $knownFailures ) {
			print TestUtils::colorString( 'UNEXPECTED CHANGE TO KNOWN FAILURE OUTPUT', 'red', true ) . ': '
				. TestUtils::colorString( $extTitle, 'yellow' ) . "\n";
			print TestUtils::colorString( 'Known failure, but the output changed!', 'red' ) . "\n";
		} else {
			print TestUtils::colorString( 'UNEXPECTED FAIL', 'red', true ) . ': '
				. TestUtils::colorString( $extTitle, 'yellow' ) . "\n";
		}

		if ( $mode === 'selser' ) {
			if ( $item->wt2wtPassed ) {
				print TestUtils::colorString( 'Even worse, the non-selser wt2wt test passed!', 'red' ) . "\n";
			} elseif ( $actual && $item->wt2wtResult !== $actual['raw'] ) {
				print TestUtils::colorString( 'Even worse, the non-selser wt2wt test had a different result!', 'red' ) . "\n";
			}
		}

		if ( !$failureOnly ) {
			// PORT-FIXME: Removed comments .. maybe need to put it back
			// print implode( "\n", $item->comments ) . "\n";
			if ( $options ) {
				print TestUtils::colorString( 'OPTIONS', 'cyan' ) . ':' . "\n";
				print self::prettyPrintIOptions( $item->options ) . "\n";
			}
			print TestUtils::colorString( 'INPUT', 'cyan' ) . ':' . "\n";
			print $actual['input'] . "\n";
			if ( $knownFailures ) {
				print "\n" . TestUtils::colorString( 'KNOWN FAILURE OUTPUT', 'cyan' ) . ":\n";
				print $expectFail . "\n";
			}
			print $options['getActualExpected']( $actual, $expected, $options['getDiff'] ) . "\n";
		}

		return false;
	}

	/**
	 * @param Stats $stats
	 * @param Test $item
	 * @param array $options
	 * @param string $mode
	 * @param string $title
	 * @param string $raw
	 * @param bool $expectSuccess Whether this success was expected (or was it a known failure).
	 * @return bool true if the success was expected.
	 */
	public static function printSuccess(
		Stats $stats, Test $item, array $options, string $mode, string $title, string $raw, bool $expectSuccess
	): bool {
		$quiet = ScriptUtils::booleanOption( $options['quiet'] ?? null );
		$quieter = ScriptUtils::booleanOption( $options['quieter'] ?? null );
		$quick = ScriptUtils::booleanOption( $options['quick'] ?? null );
		$failureOnly = $quieter || $quick;

		$extTitle = str_replace( "\n", ' ', "$title ($mode)" );

		if ( ScriptUtils::booleanOption( $options['knownFailures'] ?? null ) && !$expectSuccess ) {
			if ( !$failureOnly ) {
				print "=====================================================\n";
			}
			print TestUtils::colorString( 'UNEXPECTED PASS', 'green', true ) . ': ' .
				TestUtils::colorString( $extTitle, 'yellow' ) . "\n";
			if ( !$failureOnly ) {
				print TestUtils::colorString( 'RAW RENDERED', 'cyan' ) . ":\n";
				print $raw . "\n";
			}
			return false;
		}
		if ( !$quiet && !$quieter ) {
			$outStr = 'EXPECTED PASS';

			$outStr = TestUtils::colorString( $outStr, 'green' ) . ': '
				. TestUtils::colorString( $extTitle, 'yellow' );

			print $outStr . "\n";

			if ( $mode === 'selser' && isset( $item->wt2wtPassed ) && !$item->wt2wtPassed ) {
				print TestUtils::colorString( 'Even better, the non-selser wt2wt test failed!', 'red' ) . "\n";
			}
		}
		return true;
	}

	/**
	 * Print the actual and expected outputs.
	 *
	 * @param array $actual
	 *  - string raw
	 *  - string normal
	 * @param array $expected
	 *  - string raw
	 *  - string normal
	 * @param callable $getDiff Returns a string showing the diff(s) for the test.
	 *  - array actual
	 *  - array expected
	 * @return string
	 */
	public static function getActualExpected( array $actual, array $expected, callable $getDiff ): string {
		if ( self::$colors_mode === 'none' ) {
			$mkVisible = static function ( $s ) {
				return $s;
			};
		} else {
			$mkVisible = static function ( $s ) {
				return preg_replace( '/\xA0/', TestUtils::colorString( "␣", "white" ),
					preg_replace( '/\n/', TestUtils::colorString( "↵\n", "white" ), $s ) );
			};
		}

		$returnStr = '';
		$returnStr .= TestUtils::colorString( 'RAW EXPECTED', 'cyan' ) . ":\n";
		$returnStr .= $expected['raw'] . "\n";

		$returnStr .= TestUtils::colorString( 'RAW RENDERED', 'cyan' ) . ":\n";
		$returnStr .= $actual['raw'] . "\n";

		$returnStr .= TestUtils::colorString( 'NORMALIZED EXPECTED', 'magenta' ) . ":\n";
		$returnStr .= $mkVisible( $expected['normal'] ) . "\n";

		$returnStr .= TestUtils::colorString( 'NORMALIZED RENDERED', 'magenta' ) . ":\n";
		$returnStr .= $mkVisible( $actual['normal'] ) . "\n";

		$returnStr .= TestUtils::colorString( 'DIFF', 'cyan' ) . ":\n";
		$returnStr .= $getDiff( $actual, $expected );

		return $returnStr;
	}

	/**
	 * @param array $actual
	 *  - string normal
	 * @param array $expected
	 *  - string normal
	 * @return string Colorized diff
	 */
	public static function doDiff( array $actual, array $expected ): string {
		// safe to always request color diff, because we set color mode='none'
		// if colors are turned off.
		$e = preg_replace( '/\xA0/', "␣", $expected['normal'] );
		$a = preg_replace( '/\xA0/', "␣", $actual['normal'] );
		// PORT_FIXME:
		if ( !self::$differ ) {
			self::$differ = new Differ();
		}

		$diffs = self::$differ->diff( $e, $a );
		$diffs = preg_replace_callback( '/^(-.*)/m', static function ( $m ) {
			return TestUtils::colorString( $m[0], 'green' );
		}, $diffs );
		$diffs = preg_replace_callback( '/^(\+.*)/m', static function ( $m ) {
			return TestUtils::colorString( $m[0], 'red' );
		}, $diffs );

		return $diffs;
	}

	/**
	 * @param callable $reportFailure
	 * @param callable $reportSuccess
	 * @param Stats $stats
	 * @param Test $item
	 * @param array $options
	 * @param string $mode
	 * @param array $expected
	 * @param array $actual
	 * @param ?callable $pre
	 * @param ?callable $post
	 * @return bool True if the result was as expected.
	 */
	public static function printResult(
		callable $reportFailure, callable $reportSuccess,
		Stats $stats, Test $item, array $options, string $mode,
		array $expected, array $actual, ?callable $pre = null, ?callable $post = null
	): bool {
		$title = $item->testName; // Title may be modified here, so pass it on.
		$changeTree = $item->changetree;

		$suffix = '';
		if ( $mode === 'selser' ) {
			$suffix = ' ' .
				( $changeTree === [ 'manual' ] ? '[manual]' : json_encode( $changeTree ) );
		} elseif ( $mode === 'wt2html' && isset( $item->options['langconv'] ) ) {
			$title .= ' [langconv]';
		}
		$title .= $suffix;

		$expectFail = $item->knownFailures[$mode . $suffix] ?? null;
		$fail = $expected['normal'] !== $actual['normal'];
		// Return whether the test was as expected, independent of pass/fail
		$asExpected = null;

		if ( $mode === 'wt2wt' ) {
			$item->wt2wtPassed = !$fail;
			$item->wt2wtResult = $actual['raw'];
		}

		// don't report selser fails when nothing was changed or it's a dup
		if (
			$mode === 'selser' && $changeTree !== [ 'manual' ] &&
			( $changeTree === [] || $item->duplicateChange )
		) {
			return true;
		}

		if ( is_callable( $pre ) ) {
			$pre( $stats, $mode, $title, $item->time );
		}

		if ( $fail ) {
			$stats->failedTests++;
			$stats->modes[$mode]->failedTests++;
			$asExpected = $reportFailure( $stats, $item, $options, $mode, $title, $actual, $expected, $expectFail );
			if ( !$asExpected ) {
				$stats->failedTestsUnexpected++;
				$stats->modes[$mode]->failedTestsUnexpected++;
			}
			$stats->modes[$mode]->failList[] = [
				'testName' => $item->testName,
				'suffix' => $suffix,
				'raw' => $actual['raw'] ?? null,
				'expected' => $expected['raw'] ?? null,
				'actualNormalized' => $actual['normal'] ?? null,
				'unexpected' => !$asExpected,
			];
		} else {
			$stats->passedTests++;
			$stats->modes[$mode]->passedTests++;
			$asExpected = $reportSuccess( $stats, $item, $options, $mode, $title, $actual['raw'], $expectFail === null );
			if ( !$asExpected ) {
				$stats->passedTestsUnexpected++;
				$stats->modes[$mode]->passedTestsUnexpected++;
			}
		}

		if ( is_callable( $post ) ) {
			$post( $stats, $mode );
		}

		return $asExpected;
	}

	/**
	 * Simple function for reporting the start of the tests.
	 *
	 * This method can be reimplemented in the options of the ParserTests object.
	 */
	public static function reportStartOfTests() {
	}

	/**
	 * Get the actual and expected outputs encoded for XML output.
	 *
	 * @inheritDoc getActualExpected
	 *
	 * @return string $The XML representation of the actual and expected outputs.
	 */
	public static function getActualExpectedXML( array $actual, array $expected, callable $getDiff ) {
		$returnStr = '';

		$returnStr .= "RAW EXPECTED:\n";
		$returnStr .= self::encodeXml( $expected['raw'] ) . "\n\n";

		$returnStr .= "RAW RENDERED:\n";
		$returnStr .= self::encodeXml( $actual['raw'] ) . "\n\n";

		$returnStr .= "NORMALIZED EXPECTED:\n";
		$returnStr .= self::encodeXml( $expected['normal'] ) . "\n\n";

		$returnStr .= "NORMALIZED RENDERED:\n";
		$returnStr .= self::encodeXml( $actual['normal'] ) . "\n\n";

		$returnStr .= "DIFF:\n";
		$returnStr .= self::encodeXml( $getDiff( $actual, $expected, false ) );

		return $returnStr;
	}

	/**
	 * Report the start of the tests output.
	 *
	 * @inheritDoc reportStart
	 */
	public static function reportStartXML(): void {
	}

	/**
	 * Report the end of the tests output.
	 *
	 * @inheritDoc reportSummary
	 */
	public static function reportSummaryXML(
		array $modesRan, Stats $stats, ?string $file, ?array $testFilter,
		bool $knownFailuresChanged, array $options
	): void {
		if ( $file === null ) {
			/* Summary for all tests; not included in XML format output. */
			return;
		}
		print '<testsuites file="' . $file . '">';
		foreach ( $modesRan as $mode ) {
			print '<testsuite name="parserTests-' . $mode . '">';
			print $stats->modes[$mode]->result;
			print '</testsuite>';
		}
		print '</testsuites>';
	}

	/**
	 * Print a failure message for a test in XML.
	 *
	 * @inheritDoc printFailure
	 */
	public static function reportFailureXML(
		Stats $stats, Test $item, array $options, string $mode, string $title,
		array $actual, array $expected, ?string $expectFail, bool $failureOnly
	): bool {
		$failEle = '';
		$knownFailures = false;
		if ( ScriptUtils::booleanOption( $options['knownFailures'] ) && $expectFail !== null ) {
			// compare with remembered output
			$knownFailures = $expectFail === $actual['raw'];
		}
		if ( !$knownFailures ) {
			$failEle .= "<failure type=\"parserTestsDifferenceInOutputFailure\">\n";
			$failEle .= self::getActualExpectedXML( $actual, $expected, $options['getDiff'] );
			$failEle .= "\n</failure>";
			$stats->modes[$mode]->result .= $failEle;
			return false;
		}
		return true;
	}

	/**
	 * Print a success method for a test in XML.
	 *
	 * @inheritDoc printSuccess
	 */
	public static function reportSuccessXML(
		Stats $stats, Test $item, array $options, string $mode, string $title, string $raw,
		bool $expectSuccess
	): bool {
		return ScriptUtils::booleanOption( $options['knownFailures'] ?? null ) && !$expectSuccess;
	}

	private static function pre(
		Stats $stats, string $mode, string $title, array $time
	): void {
		$testcaseEle = '<testcase name="' . self::encodeXml( $title ) . '" ';
		$testcaseEle .= 'assertions="1" ';

		$timeTotal = null;
		if ( $time && $time['end'] && $time['start'] ) {
			$timeTotal = $time['end'] - $time['start'];
			if ( !is_nan( $timeTotal ) ) {
				$testcaseEle .= 'time="' . ( ( $time['end'] - $time['start'] ) / 1000.0 ) . '"';
			}
		}

		$testcaseEle .= '>';
		$stats->modes[$mode]->result .= $testcaseEle;
	}

	private static function post( Stats $stats, string $mode ): void {
		$stats->modes[$mode]->result .= '</testcase>';
	}

	/**
	 * Print the result of a test in XML.
	 *
	 * @inheritDoc printResult
	 */
	public static function reportResultXML( ...$args ) {
		$args[] = [ self::class, 'pre' ];
		$args[] = [ self::class, 'post' ];
		self::printResult(
			[ self::class, 'reportFailureXML' ],
			[ self::class, 'reportSuccessXML' ],
			...$args
		);

		// In xml, test all cases always
		return true;
	}
}

$maintClass = ParserTests::class;
require_once PARSOID_RUN_MAINTENANCE_IF_MAIN;
