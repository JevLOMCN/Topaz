<?php

require_once __DIR__ . '/../tools/Maintenance.php';

use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\ScriptUtils;
use Wikimedia\ScopedCallback;

// phpcs:ignore MediaWiki.Files.ClassMatchesFilename.NotMatch
class RegressionTesting extends \Wikimedia\Parsoid\Tools\Maintenance {
	use \Wikimedia\Parsoid\Tools\ExtendedOptsProcessor;

	private $titlesPath = '/tmp/titles';

	public function __construct() {
		parent::__construct( false /* Doesn't actually require parsoid */ );
		$this->addDescription(
			"Validate round-trip testing results.\n" .
			"Typical usage:\n" .
			"\tphp " . basename( $this->getName() ) . " --uid <username> <knownGood> <maybeBad>\n" .
			"\n" .
			"You likely also need either the --url or --titles options.\n" .
			"See --help for detailed usage."
		);
		$this->addArg(
			'knownGood',
			"git commit hash to use as the oracle ('known good')",
			false
		);
		$this->addArg(
			'maybeBad',
			"git commit hash to test ('maybe bad')",
			false
		);
		$this->addOption(
			"uid",
			"The bastion username you use to login to scandium/testreduce1002",
			false, true, 'u'
		);
		$this->addOption(
			"contentVersion",
			"The outputContentVersion to use, if different from the default",
			false, true
		);
		$this->addOption(
			"titles",
			"File containing list of pages to test, formatted as lines of dbname:title",
			false, true, 't'
		);
		$this->addOptionWithDefault(
			"url",
			"URL to use to fetch pages to test",
			'http://localhost:8003/regressions/between/<good>/<bad>'
		);
		$this->addOptionWithDefault(
			"nSem",
			"Number of semantic errors to check, -1 means 'all of them'",
			-1 /* default */, 'n' );
		$this->addOptionWithDefault(
			"nSyn",
			"Number of syntactic errors to check, -1 means 'all of them'",
			25 /* default */, 'm' );
		$this->addOptionWithDefault(
			"updateTestreduce",
			"Should testreduce1002 also be updated? (default true)",
			true );
		$this->setAllowUnregisteredOptions( true );
	}

	/**
	 * Safely execute a shell command.
	 * @param array $cmd The shell command to execute.
	 * @param bool $use_cwd Whether to execute command in "current working
	 *    directory" or else in a fixed directory (defaults to false).
	 * @throws Error if the command does not successfully execute
	 */
	private function sh( array $cmd, bool $use_cwd = false ): void {
		if ( !$this->hasOption( 'quiet' ) ) {
			error_log( implode( ' ', $cmd ) );
		}
		$descriptors = [ STDIN, STDOUT, STDERR ];
		if ( $this->hasOption( 'quiet' ) ) {
			$descriptors[1] = [ 'file', '/tmp/rt.out', 'a' ];
		}
		// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions.proc_open
		$process = proc_open(
			$cmd, $descriptors, $pipes,
			$use_cwd ? null : __DIR__
		);
		if ( $process === false ) {
			throw new Error( "Command failed: " . implode( ' ', $cmd ) );
		}
		$return_value = proc_close( $process );
		if ( $return_value !== 0 ) {
			throw new Error( "Command returned non-zero status: $return_value" );
		}
	}

	/**
	 * Safely execute a command on another host, using ssh.
	 * @param array $cmd The shell command to execute.
	 * @param string|null $hostname The host on which to execute the command.
	 * @throws Error if the command does not successfully execute
	 */
	private function ssh( array $cmd, string $hostname = null ): void {
		array_unshift( $cmd, 'ssh', $this->hostname( $hostname ) );
		$this->sh( $cmd );
	}

	/**
	 * Helper function to glue strings and arrays together.
	 * Arguments passed as strings are automatically split on the space
	 * character.  Arguments passed as arrays are merged as-is, protecting
	 * any embedded spaces in the argument values.
	 * @param string|array<string> ...$commands
	 * @return array<string>
	 */
	private static function cmd( ...$commands ): array {
		return array_merge( ...array_map( static function ( $item ) {
			return is_string( $item ) ? explode( ' ', $item ) : $item;
		}, $commands ) );
	}

	/**
	 * Returns $uid@$hostname
	 * @param string|null $host The hostname to use.
	 * @return string
	 */
	private function hostname( string $host = null ): string {
		if ( $host === null ) {
			// default hostname
			$host = 'testreduce1002.eqiad.wmnet';
		}
		if ( $this->hasOption( 'uid' ) ) {
			$host = $this->getOption( 'uid' ) . "@$host";
		}
		return $host;
	}

	/**
	 * Return the command-line argument fragment to use if an explicit
	 * content version was passed as a command-line option.
	 * @return string[]
	 */
	private function outputContentVersion(): array {
		if ( !$this->hasOption( 'contentVersion' ) ) {
			return [];
		}
		return [ '--outputContentVersion', $this->getOption( 'contentVersion' ) ];
	}

	/**
	 * Print out a heading on the console.
	 * @param string|null $heading The heading text, or null to print a line of dashes
	 * @param bool $force Whether to print the heading even if --quiet
	 */
	private function dashes( string $heading = null, bool $force = false ): void {
		if ( $this->hasOption( 'quiet' ) && !$force ) {
			return;
		}
		if ( $heading ) {
			echo( "----- $heading -----\n" );
		} else {
			echo( "---------------------\n" );
		}
	}

	/**
	 * Run tests on the given commit on the remote host.
	 * @param string $commit The commit to test
	 */
	public function runTest( $commit ): void {
		$cdDir = self::cmd( 'cd /srv/parsoid-testing' );
		$restartPHP = self::cmd( 'sudo systemctl restart php7.4-fpm.service' );
		$resultPath = "/tmp/results.$commit.json";
		$testScript = self::cmd(
			$cdDir, '&&',
			'node tools/runRtTests.js --proxyURL http://scandium.eqiad.wmnet:80 --parsoidURL http://DOMAIN/w/rest.php',
			$this->outputContentVersion(),
			[ '-f', $this->titlesPath ],
			[ '-o', $resultPath ]
		);

		$this->dashes( "Checking out $commit on scandium" );
		$this->ssh( self::cmd(
			$cdDir, '&&',
			"git fetch", '&&',
			'git checkout', [ $commit ], '&&',
			$restartPHP
		), 'scandium.eqiad.wmnet' );
		if ( ScriptUtils::booleanOption( $this->getOption( 'updateTestreduce' ) ) ) {
			# Check out on testreduce1002 as well to ensure HTML version changes
			# don't trip up our test script and we don't have to mess with passing in
			# the --contentVersion option in most scenarios
			$this->dashes( "Checking out $commit on testreduce1002" );
			$this->ssh( self::cmd(
				$cdDir, '&&',
				"git fetch", '&&',
				'git checkout', [ $commit ] ), 'testreduce1002.eqiad.wmnet' );
		}

		$this->dashes( "Running tests" );
		$this->ssh( self::cmd(
			'sudo rm -f', [ $resultPath ], '&&',
			$testScript
		) );
		$this->sh( self::cmd(
			'scp',
			$this->hasOption( 'quiet' ) ? '-q' : [],
			[ $this->hostname() . ":" . $resultPath ],
			'/tmp/'
		) );
	}

	/**
	 * Load the JSON-format results for the given commit.
	 * @param string $commit
	 * @return array
	 */
	public function readResults( string $commit ): array {
		$resultsPath = "/tmp/results.$commit.json";
		$result = [];
		foreach ( json_decode( file_get_contents( $resultsPath ), true ) as $r ) {
			$result[$r['prefix'] . ':' . $r['title']] = $r['results'];
		}
		return $result;
	}

	/**
	 * Helper function to do a 'deep' comparison on two array values.
	 * @param mixed $a
	 * @param mixed $b
	 * @return bool True iff the arrays contain the same contents
	 */
	private static function deepEquals( $a, $b ): bool {
		if ( is_array( $a ) && is_array( $b ) ) {
			// Are the keys the same?
			$ka = array_keys( $a );
			$kb = array_keys( $b );
			if ( count( $ka ) !== count( $kb ) ) {
				return false;
			}
			foreach ( $ka as $k ) {
				if ( !array_key_exists( $k, $b ) ) {
					return false;
				}
				if ( !self::deepEquals( $a[$k], $b[$k] ) ) {
					return false;
				}
			}
			return true;
		} elseif ( is_array( $a ) || is_array( $b ) ) {
			return false;
		} else {
			return $a === $b;
		}
	}

	/**
	 * Helper function to dump results
	 * @param array $res
	 */
	private function printResults( array $res ): void {
		foreach ( $res as $test => $testRes ) {
			echo( "\t$test\t=> " );
			foreach ( $testRes as $type => $count ) {
				echo( "$type: $count; " );
			}
			echo( "\n" );
		}
	}

	/**
	 * Compare the results for the given titles.
	 * @param string[] $titles The titles to compare
	 * @param string $knownGood the oracle commit
	 * @param string $maybeBad the test commit
	 */
	public function compareResults( $titles, $knownGood, $maybeBad ): void {
		$this->dashes( "Comparing results" );
		$oracleResults = $this->readResults( $knownGood );
		$commitResults = $this->readResults( $maybeBad );
		$numErrorsOracle = 0;
		$numErrorsCommit = 0;
		$numTitles = count( $titles );

		$summary = [ 'degraded' => [], 'improved' => [] ];
		foreach ( $titles as $title ) {
			$oracleRes = $oracleResults[$title] ?? null;
			$commitRes = $commitResults[$title] ?? null;
			if ( $oracleRes['html2wt']['error'] ?? 0 ) {
				$numErrorsOracle++;
			}
			if ( $commitRes['html2wt']['error'] ?? 0 ) {
				$numErrorsCommit++;
			}
			if ( self::deepEquals( $oracleRes, $commitRes ) ) {
				if ( !$this->hasOption( 'quiet' ) ) {
					echo( "$title\n" );
					echo( "No changes!\n" );
				}
			} else {
				// emit these differences even in 'quiet' mode
				$this->dashes( null, true );
				echo( "$title\n" );
				echo( "$knownGood (known good) results:\n" );
				$this->printResults( $oracleRes );
				echo( "$maybeBad (maybe bad) results:\n" );
				$this->printResults( $commitRes );
				$degraded = static function ( $newRes, $oldRes ) {
					// NOTE: We are conservatively assuming that even if semantic
					// errors go down but syntactic errors go up, it is a degradation.
					return ( $newRes['error'] ?? 0 ) > ( $oldRes['error'] ?? 0 ) ||
						( $newRes['semantic'] ?? 0 ) > ( $oldRes['semantic'] ?? 0 ) ||
						( $newRes['syntactic'] ?? 0 ) > ( $oldRes['syntactic'] ?? 0 );
				};
				if ( $degraded( $commitRes['html2wt'], $oracleRes['html2wt'] ) ||
					$degraded( $commitRes['selser'], $oracleRes['selser'] ) ) {
					$summary['degraded'][] = $title;
				} else {
					$summary['improved'][] = $title;
				}
			}
		}
		$this->dashes( null, true );
		if ( count( $summary['improved'] ) > 0 ) {
			echo( "Pages that seem to have improved (feel free to verify in other ways):\n" );
			echo( implode( "\n", $summary['improved'] ) );
			echo( "\n" );
			$this->dashes( null, true );
		}
		if ( count( $summary['degraded'] ) > 0 ) {
			echo( "Pages needing investigation:\n" );
			echo( implode( "\n", $summary['degraded'] ) );
			echo( "\n" );
		} else {
			echo( "*** No pages need investigation ***\n" );
		}

		# Sanity check
		if ( $numErrorsOracle === $numTitles ) {
			error_log( "\n***** ALL runs for $knownGood errored! *****" );
		}
		if ( $numErrorsCommit === $numTitles ) {
			error_log( "\n***** ALL runs for $maybeBad errored! *****" );
		}
	}

	private function makeCurlRequest( string $url ): string {
		$curlopt = [
			CURLOPT_USERAGENT => 'Parsoid-RT-Test',
			CURLOPT_CONNECTTIMEOUT => 60,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_ENCODING => '', // Enable compression
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => false
		];
		$ch = curl_init( $url );
		if ( !$ch ) {
			throw new \RuntimeException( "Failed to open curl handle to $url" );
		}
		$reset = new ScopedCallback( 'curl_close', [ $ch ] );

		if ( !curl_setopt_array( $ch, $curlopt ) ) {
			throw new \RuntimeException( "Error setting curl options: " . curl_error( $ch ) );
		}

		$res = curl_exec( $ch );

		if ( curl_errno( $ch ) !== 0 ) {
			throw new \RuntimeException( "HTTP request failed: " . curl_error( $ch ) );
		}

		$code = curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
		if ( $code !== 200 ) {
			throw new \RuntimeException( "HTTP request failed: HTTP code $code" );
		}

		ScopedCallback::consume( $reset );

		if ( !$res ) {
			throw new \RuntimeException( "HTTP request failed: Empty response" );
		}

		return $res;
	}

	private function updateSemanticErrorTitles( string $baseUrl, array &$titles ): void {
		$url = $baseUrl;
		$page = 0;
		do {
			$done = true;
			$dom = DOMUtils::parseHTML( $this->makeCurlRequest( $url ) );
			$titleRows = DOMCompat::querySelectorAll( $dom, 'tr[status=fail]' );
			foreach ( $titleRows as $tr ) {
				$titles[] = DOMCompat::querySelector( $tr, 'td[class=title] a' )->firstChild->nodeValue;
			}
			// Fetch more if necessary
			if ( !DOMCompat::querySelectorAll( $dom, 'tr[status=skip]' ) ) {
				$done = false;
				$page++;
				$url = $baseUrl . "/$page";
				if ( $page > 2 ) {
					throw new \RuntimeException( "Too many regressions? Fetched $page pages of $baseUrl. Aborting." );
				}
			}
		} while ( !$done );
	}

	/** @inheritDoc */
	public function execute() {
		$this->maybeHelp();
		$titles = [];

		if ( $this->hasOption( 'url' ) ) {
			$baseUrl = $this->getOption( 'url' );
			if ( !preg_match( "#.*/between/(.*)/(.*)#", $baseUrl, $matches ) ) {
				$this->error( "Please check the source url. Don't recognize format of $baseUrl." );
				return -1;
			}
			$knownGood = $matches[1];
			$maybeBad = $matches[2];
			$rtSelserUrl = preg_replace( "#regressions/between/.*/(.*)$#", "rtselsererrors/$1", $baseUrl );
			$titles = [];

			$this->updateSemanticErrorTitles( $baseUrl, $titles );
			$this->updateSemanticErrorTitles( $rtSelserUrl, $titles );
			$localTitlesPath = "/tmp/titles";
			file_put_contents( $localTitlesPath, implode( "\n", $titles ) );
		} elseif ( $this->hasOption( 'titles' ) ) {
			$localTitlesPath = $this->getOption( 'titles' );
			$lines = preg_split(
				'/\r\n?|\n/',
				file_get_contents( $this->getOption( 'titles' ) )
			);
			foreach ( $lines as $line ) {
				$line = preg_replace( '/ \|.*$/', '', $line );
				if ( $line !== '' ) {
					$titles[] = $line;
				}
			}

			$knownGood = $this->getArg( 0 );
			$maybeBad = $this->getArg( 1 );
			if ( !$knownGood || !$maybeBad ) {
				$this->error( "Missing known-good and maybe-bad git hashes" );
				return -1;
			}
		} else {
			$this->error( "Either --titles or --url is required." );
			return -1;
		}

		$this->ssh( self::cmd( 'sudo rm -f', [ $this->titlesPath ] ) );
		$this->sh( self::cmd(
			'scp',
			$this->hasOption( 'quiet' ) ? '-q' : [],
			[ $localTitlesPath ],
			[ $this->hostname() . ":" . $this->titlesPath ]
		), true );

		$this->runTest( $knownGood );
		$this->runTest( $maybeBad );

		$this->compareResults( $titles, $knownGood, $maybeBad );
	}
}

$maintClass = RegressionTesting::class;
require_once PARSOID_RUN_MAINTENANCE_IF_MAIN;
