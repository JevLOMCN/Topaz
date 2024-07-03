<?php

namespace Wikimedia\WikiPEG;

abstract class PEGParserBase {
	protected static $FAILED;
	protected static $UNDEFINED;
	protected $currPos;
	protected $savedPos;
	protected $input;
	protected $inputLength;
	protected $options;
	protected $cache;

	/** @var array<int,array{line:int,column:int,seenCR:bool}> */
	protected $posDetailsCache;
	protected $maxFailPos;
	protected $maxFailExpected;

	/** @var array Associative arrays of expectation info */
	protected $expectations;

	/** @var Expectation[] */
	private $expectationCache;

	/** @var Tracer */
	protected $tracer;

	public function __construct() {
		if ( !self::$FAILED ) {
			self::$FAILED = new \stdClass;
		}
		if ( !self::$UNDEFINED ) {
			self::$UNDEFINED = new \stdClass;
		}
	}

	protected function traceCall( $parseFunc, $name, $argNames, $args ) {
		$argMap = [];
		foreach ( $args as $i => $argValue ) {
			$argMap[$argNames[$i]] = $argValue;
		}
		$startPos = $this->currPos;
		$this->tracer->trace( [
			'type' => 'rule.enter',
			'rule' => $name,
			'location' => $this->computeLocation( $startPos, $startPos ),
			'args' => $argMap
		] );
		$result = call_user_func_array( $parseFunc, $args );
		if ( $result !== self::$FAILED ) {
			$this->tracer->trace( [
				'type' => 'rule.match',
				'rule' => $name,
				'location' => $this->computeLocation( $startPos, $this->currPos ),
			] );
		} else {
			$this->tracer->trace( [
				'type' => 'rule.fail',
				'rule' => $name,
				'result' => $result,
				'location' => $this->computeLocation( $startPos, $startPos )
			] );
		}
		return $result;
	}

	protected function text() {
		return substr( $this->input, $this->savedPos, $this->currPos - $this->savedPos );
	}

	protected function location() {
		return $this->computeLocation( $this->savedPos, $this->currPos );
	}

	/**
	 * @param string $description
	 * @return never
	 */
	protected function expected( $description ) {
		throw $this->buildException(
			null,
			[ [ 'type' => "other", 'description' => $description ] ],
			$this->text(),
			$this->computeLocation( $this->savedPos, $this->currPos )
		);
	}

	/**
	 * @param string $message
	 * @return never
	 */
	protected function error( $message ) {
		throw $this->buildException(
			$message,
			null,
			$this->text(),
			$this->computeLocation( $this->savedPos, $this->currPos )
		);
	}

	public static function charAt( $s, $byteOffset ) {
		if ( !isset( $s[$byteOffset] ) ) {
			return '';
		}
		$char = $s[$byteOffset];
		$byte1 = ord( $char );
		if ( ( $byte1 & 0xc0 ) === 0xc0 ) {
			$char .= $s[$byteOffset + 1];
		}
		if ( ( $byte1 & 0xe0 ) === 0xe0 ) {
			$char .= $s[$byteOffset + 2];
		}
		if ( ( $byte1 & 0xf0 ) === 0xf0 ) {
			$char .= $s[$byteOffset + 3];
		}
		return $char;
	}

	public static function charsAt( $s, $byteOffset, $numChars ) {
		$ret = '';
		for ( $i = 0; $i < $numChars; $i++ ) {
			$ret .= self::consumeChar( $s, $byteOffset );
		}
		return $ret;
	}

	public static function consumeChar( $s, &$byteOffset ) {
		if ( !isset( $s[$byteOffset] ) ) {
			return '';
		}
		$char = $s[$byteOffset++];
		$byte1 = ord( $char );
		if ( ( $byte1 & 0xc0 ) === 0xc0 ) {
			$char .= $s[$byteOffset++];
		}
		if ( ( $byte1 & 0xe0 ) === 0xe0 ) {
			$char .= $s[$byteOffset++];
		}
		if ( ( $byte1 & 0xf0 ) === 0xf0 ) {
			$char .= $s[$byteOffset++];
		}
		return $char;
	}

	public static function &newRef( $value ) {
		return $value;
	}

	/**
	 * @param int $pos
	 * @return array{line:int,column:int,seenCR:bool}
	 */
	protected function computePosDetails( $pos ) {
		if ( isset( $this->posDetailsCache[$pos] ) ) {
			return $this->posDetailsCache[$pos];
		}
		$p = $pos - 1;
		while ( !isset( $this->posDetailsCache[$p] ) ) {
			$p--;
		}

		$details = $this->posDetailsCache[$p];

		while ( $p < $pos ) {
			$ch = self::charAt( $this->input, $p );
			if ( $ch === "\n" ) {
				if ( !$details['seenCR'] ) { $details['line']++;
				}
				$details['column'] = 1;
				$details['seenCR'] = false;
			} elseif ( $ch === "\r" || $ch === "\u2028" || $ch === "\u2029" ) {
				$details['line']++;
				$details['column'] = 1;
				$details['seenCR'] = true;
			} else {
				$details['column']++;
				$details['seenCR'] = false;
			}

			$p++;
		}

		$this->posDetailsCache[$pos] = $details;
		return $details;
	}

	protected function computeLocation( $startPos, $endPos ) {
		if ( $endPos > $this->inputLength ) {
			$endPos--;
		}
		$startPosDetails = $this->computePosDetails( $startPos );
		$endPosDetails = $this->computePosDetails( $endPos );

		return new LocationRange(
			$startPos,
			$startPosDetails['line'],
			$startPosDetails['column'],
			$endPos,
			$endPosDetails['line'],
			$endPosDetails['column']
		);
	}

	protected function fail( $expected ) {
		if ( $this->currPos < $this->maxFailPos ) {
			return;
		}

		if ( $this->currPos > $this->maxFailPos ) {
			$this->maxFailPos = $this->currPos;
			$this->maxFailExpected = [];
		}

		$this->maxFailExpected[] = $expected;
	}

	/**
	 * @param array<int|array{type:string,value?:?string,description:string}> $expected
	 * @return Expectation[]
	 */
	private function expandExpectations( $expected ) {
		$expanded = [];
		foreach ( $expected as $index ) {
			if ( is_int( $index ) ) {
				if ( !isset( $this->expectationCache[$index] ) ) {
					$this->expectationCache[$index] = new Expectation( $this->expectations[$index] );
				}
				$expanded[] = $this->expectationCache[$index];
			} else {
				$expanded[] = new Expectation( $index );
			}
		}
		return $expanded;
	}

	private function buildMessage( $expected, $found ) {
		$expectedDescs = [];

		foreach ( $expected as $info ) {
			$expectedDescs[] = $info->description;
		}
		$lastDesc = array_pop( $expectedDescs );
		if ( $expectedDescs ) {
			$expectedDesc = implode( ', ', $expectedDescs ) . ' or ' . $lastDesc;
		} else {
			$expectedDesc = $lastDesc;
		}
		$foundDesc = $found ? json_encode( $found ) : "end of input";

		return "Expected " . $expectedDesc . " but " . $foundDesc . " found.";
	}

	protected function buildException( $message, $expected, $found, $location ) {
		if ( $expected !== null ) {
			sort( $expected );
			$expected = array_unique( $expected );
			$expandedExpected = $this->expandExpectations( $expected );
			usort( $expandedExpected, static function ( $a, $b ) {
				return Expectation::compare( $a, $b );
			} );
		} else {
			$expandedExpected = [];
		}

		return new SyntaxError(
			$message ?? $this->buildMessage( $expandedExpected, $found ),
			$expandedExpected,
			$found,
			$location
		);
	}

	protected function buildParseException() {
		$char = self::charAt( $this->input, $this->maxFailPos );
		return $this->buildException(
			null,
			$this->maxFailExpected,
			$char === '' ? null : $char,
			$this->computeLocation( $this->maxFailPos, $this->maxFailPos + 1 )
		);
	}

	protected function initialize() {
	}

	protected function initInternal( $input, $options ) {
		$this->currPos = 0;
		$this->savedPos = 0;
		$this->input = $input;
		$this->inputLength = strlen( $input );
		$this->options = $options;
		$this->cache = [];
		$this->posDetailsCache = [ [ 'line' => 1, 'column' => 1, 'seenCR' => false ] ];
		$this->maxFailPos = 0;
		$this->maxFailExpected = [];
		$this->tracer = $options['tracer'] ?? new DefaultTracer;

		$this->initialize();
	}

	abstract public function parse( $input, $options = [] );
}
