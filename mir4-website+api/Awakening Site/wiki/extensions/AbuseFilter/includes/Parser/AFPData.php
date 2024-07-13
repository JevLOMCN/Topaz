<?php

namespace MediaWiki\Extension\AbuseFilter\Parser;

use InvalidArgumentException;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\InternalException;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\UserVisibleException;
use RuntimeException;

class AFPData {
	// Datatypes
	public const DINT = 'int';
	public const DSTRING = 'string';
	public const DNULL = 'null';
	public const DBOOL = 'bool';
	public const DFLOAT = 'float';
	public const DARRAY = 'array';
	// Special purpose type for non-initialized stuff
	public const DUNDEFINED = 'undefined';

	/**
	 * Translation table mapping shell-style wildcards to PCRE equivalents.
	 * Derived from <http://www.php.net/manual/en/function.fnmatch.php#100207>
	 * @internal
	 */
	public const WILDCARD_MAP = [
		'\*' => '.*',
		'\+' => '\+',
		'\-' => '\-',
		'\.' => '\.',
		'\?' => '.',
		'\[' => '[',
		'\[\!' => '[^',
		'\\' => '\\\\',
		'\]' => ']',
	];

	/**
	 * @var string One of the D* const from this class
	 * @internal Use $this->getType() instead
	 */
	public $type;
	/**
	 * @var mixed|null|AFPData[] The actual data contained in this object
	 * @internal Use $this->getData() instead
	 */
	public $data;

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return AFPData[]|mixed|null
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @param string $type
	 * @param AFPData[]|mixed|null $val
	 */
	public function __construct( $type, $val = null ) {
		if ( $type === self::DUNDEFINED && $val !== null ) {
			// Sanity
			throw new InvalidArgumentException( 'DUNDEFINED cannot have a non-null value' );
		}
		$this->type = $type;
		$this->data = $val;
	}

	/**
	 * @param mixed $var
	 * @return AFPData
	 * @throws InternalException
	 */
	public static function newFromPHPVar( $var ) {
		switch ( gettype( $var ) ) {
			case 'string':
				return new AFPData( self::DSTRING, $var );
			case 'integer':
				return new AFPData( self::DINT, $var );
			case 'double':
				return new AFPData( self::DFLOAT, $var );
			case 'boolean':
				return new AFPData( self::DBOOL, $var );
			case 'array':
				$result = [];
				foreach ( $var as $item ) {
					$result[] = self::newFromPHPVar( $item );
				}
				return new AFPData( self::DARRAY, $result );
			case 'NULL':
				return new AFPData( self::DNULL );
			default:
				throw new InternalException(
					'Data type ' . gettype( $var ) . ' is not supported by AbuseFilter'
				);
		}
	}

	/**
	 * @param AFPData $orig
	 * @param string $target
	 * @return AFPData
	 */
	public static function castTypes( AFPData $orig, $target ) {
		if ( $orig->type === $target ) {
			return $orig;
		}
		if ( $orig->type === self::DUNDEFINED ) {
			// This case should be handled at a higher level, to avoid implicitly relying on what
			// this method will do for the specific case.
			throw new InternalException( 'Refusing to cast DUNDEFINED to something else' );
		}
		if ( $target === self::DNULL ) {
			// We don't expose any method to cast to null. And, actually, should we?
			return new AFPData( self::DNULL );
		}

		if ( $orig->type === self::DARRAY ) {
			if ( $target === self::DBOOL ) {
				return new AFPData( self::DBOOL, (bool)count( $orig->data ) );
			} elseif ( $target === self::DFLOAT ) {
				return new AFPData( self::DFLOAT, floatval( count( $orig->data ) ) );
			} elseif ( $target === self::DINT ) {
				return new AFPData( self::DINT, count( $orig->data ) );
			} elseif ( $target === self::DSTRING ) {
				$s = '';
				foreach ( $orig->data as $item ) {
					$s .= $item->toString() . "\n";
				}

				return new AFPData( self::DSTRING, $s );
			}
		}

		if ( $target === self::DBOOL ) {
			return new AFPData( self::DBOOL, (bool)$orig->data );
		} elseif ( $target === self::DFLOAT ) {
			return new AFPData( self::DFLOAT, floatval( $orig->data ) );
		} elseif ( $target === self::DINT ) {
			return new AFPData( self::DINT, intval( $orig->data ) );
		} elseif ( $target === self::DSTRING ) {
			return new AFPData( self::DSTRING, strval( $orig->data ) );
		} elseif ( $target === self::DARRAY ) {
			// We don't expose any method to cast to array
			return new AFPData( self::DARRAY, [ $orig ] );
		}
		throw new InternalException( 'Cannot cast ' . $orig->type . " to $target." );
	}

	/**
	 * @return AFPData
	 */
	public function boolInvert() {
		if ( $this->type === self::DUNDEFINED ) {
			return new AFPData( self::DUNDEFINED );
		}
		return new AFPData( self::DBOOL, !$this->toBool() );
	}

	/**
	 * @param AFPData $exponent
	 * @return AFPData
	 */
	public function pow( AFPData $exponent ) {
		if ( $this->type === self::DUNDEFINED || $exponent->type === self::DUNDEFINED ) {
			return new AFPData( self::DUNDEFINED );
		}
		$res = pow( $this->toNumber(), $exponent->toNumber() );
		$type = is_int( $res ) ? self::DINT : self::DFLOAT;

		return new AFPData( $type, $res );
	}

	/**
	 * @param AFPData $d2
	 * @param bool $strict whether to also check types
	 * @return bool
	 * @throws InternalException if $this or $d2 is a DUNDEFINED. This shouldn't happen, because this method
	 *  only returns a boolean, and thus the type of the result has already been decided and cannot
	 *  be changed to be a DUNDEFINED from here.
	 * @internal
	 */
	public function equals( AFPData $d2, $strict = false ) {
		if ( $this->type === self::DUNDEFINED || $d2->type === self::DUNDEFINED ) {
			throw new InternalException(
				__METHOD__ . " got a DUNDEFINED. This should be handled at a higher level"
			);
		} elseif ( $this->type !== self::DARRAY && $d2->type !== self::DARRAY ) {
			$typecheck = $this->type === $d2->type || !$strict;
			return $typecheck && $this->toString() === $d2->toString();
		} elseif ( $this->type === self::DARRAY && $d2->type === self::DARRAY ) {
			$data1 = $this->data;
			$data2 = $d2->data;
			if ( count( $data1 ) !== count( $data2 ) ) {
				return false;
			}
			$length = count( $data1 );
			for ( $i = 0; $i < $length; $i++ ) {
				// @phan-suppress-next-line PhanTypeArraySuspiciousNullable Array type
				if ( $data1[$i]->equals( $data2[$i], $strict ) === false ) {
					return false;
				}
			}
			return true;
		} else {
			// Trying to compare an array to something else
			if ( $strict ) {
				return false;
			}
			if ( $this->type === self::DARRAY && count( $this->data ) === 0 ) {
				return ( $d2->type === self::DBOOL && $d2->toBool() === false ) || $d2->type === self::DNULL;
			} elseif ( $d2->type === self::DARRAY && count( $d2->data ) === 0 ) {
				return ( $this->type === self::DBOOL && $this->toBool() === false ) ||
					$this->type === self::DNULL;
			} else {
				return false;
			}
		}
	}

	/**
	 * @return AFPData
	 */
	public function unaryMinus() {
		if ( $this->type === self::DUNDEFINED ) {
			return new AFPData( self::DUNDEFINED );
		} elseif ( $this->type === self::DINT ) {
			return new AFPData( $this->type, -$this->toInt() );
		} else {
			return new AFPData( $this->type, -$this->toFloat() );
		}
	}

	/**
	 * @param AFPData $b
	 * @param string $op
	 * @return AFPData
	 * @throws InternalException
	 */
	public function boolOp( AFPData $b, $op ) {
		$a = $this->type === self::DUNDEFINED ? false : $this->toBool();
		$b = $b->type === self::DUNDEFINED ? false : $b->toBool();

		if ( $op === '|' ) {
			return new AFPData( self::DBOOL, $a || $b );
		} elseif ( $op === '&' ) {
			return new AFPData( self::DBOOL, $a && $b );
		} elseif ( $op === '^' ) {
			return new AFPData( self::DBOOL, $a xor $b );
		}
		// Should never happen.
		// @codeCoverageIgnoreStart
		throw new InternalException( "Invalid boolean operation: {$op}" );
		// @codeCoverageIgnoreEnd
	}

	/**
	 * @param AFPData $b
	 * @param string $op
	 * @return AFPData
	 * @throws InternalException
	 */
	public function compareOp( AFPData $b, $op ) {
		if ( $this->type === self::DUNDEFINED || $b->type === self::DUNDEFINED ) {
			return new AFPData( self::DUNDEFINED );
		}
		if ( $op === '==' || $op === '=' ) {
			return new AFPData( self::DBOOL, $this->equals( $b ) );
		} elseif ( $op === '!=' ) {
			return new AFPData( self::DBOOL, !$this->equals( $b ) );
		} elseif ( $op === '===' ) {
			return new AFPData( self::DBOOL, $this->equals( $b, true ) );
		} elseif ( $op === '!==' ) {
			return new AFPData( self::DBOOL, !$this->equals( $b, true ) );
		}

		$a = $this->toString();
		$b = $b->toString();
		if ( $op === '>' ) {
			return new AFPData( self::DBOOL, $a > $b );
		} elseif ( $op === '<' ) {
			return new AFPData( self::DBOOL, $a < $b );
		} elseif ( $op === '>=' ) {
			return new AFPData( self::DBOOL, $a >= $b );
		} elseif ( $op === '<=' ) {
			return new AFPData( self::DBOOL, $a <= $b );
		}
		// Should never happen
		// @codeCoverageIgnoreStart
		throw new InternalException( "Invalid comparison operation: {$op}" );
		// @codeCoverageIgnoreEnd
	}

	/**
	 * @param AFPData $b
	 * @param string $op
	 * @param int $pos
	 * @return AFPData
	 * @throws UserVisibleException
	 * @throws InternalException
	 */
	public function mulRel( AFPData $b, $op, $pos ) {
		if ( $b->type === self::DUNDEFINED ) {
			// The LHS type is checked later, because we first need to ensure we're not
			// dividing or taking modulo by 0 (and that should throw regardless of whether
			// the LHS is undefined).
			return new AFPData( self::DUNDEFINED );
		}

		$b = $b->toNumber();

		if (
			( $op === '/' && (float)$b === 0.0 ) ||
			( $op === '%' && (int)$b === 0 )
		) {
			$lhs = $this->type === self::DUNDEFINED ? 0 : $this->toNumber();
			throw new UserVisibleException( 'dividebyzero', $pos, [ $lhs ] );
		}

		if ( $this->type === self::DUNDEFINED ) {
			return new AFPData( self::DUNDEFINED );
		}
		$a = $this->toNumber();

		if ( $op === '*' ) {
			$data = $a * $b;
		} elseif ( $op === '/' ) {
			$data = $a / $b;
		} elseif ( $op === '%' ) {
			$data = (int)$a % (int)$b;
		} else {
			// Should never happen
			// @codeCoverageIgnoreStart
			throw new InternalException( "Invalid multiplication-related operation: {$op}" );
			// @codeCoverageIgnoreEnd
		}

		$type = is_int( $data ) ? self::DINT : self::DFLOAT;

		return new AFPData( $type, $data );
	}

	/**
	 * @param AFPData $b
	 * @return AFPData
	 */
	public function sum( AFPData $b ) {
		if ( $this->type === self::DUNDEFINED || $b->type === self::DUNDEFINED ) {
			return new AFPData( self::DUNDEFINED );
		} elseif ( $this->type === self::DSTRING || $b->type === self::DSTRING ) {
			return new AFPData( self::DSTRING, $this->toString() . $b->toString() );
		} elseif ( $this->type === self::DARRAY && $b->type === self::DARRAY ) {
			return new AFPData( self::DARRAY, array_merge( $this->toArray(), $b->toArray() ) );
		} else {
			$res = $this->toNumber() + $b->toNumber();
			$type = is_int( $res ) ? self::DINT : self::DFLOAT;

			return new AFPData( $type, $res );
		}
	}

	/**
	 * @param AFPData $b
	 * @return AFPData
	 */
	public function sub( AFPData $b ) {
		if ( $this->type === self::DUNDEFINED || $b->type === self::DUNDEFINED ) {
			return new AFPData( self::DUNDEFINED );
		}
		$res = $this->toNumber() - $b->toNumber();
		$type = is_int( $res ) ? self::DINT : self::DFLOAT;

		return new AFPData( $type, $res );
	}

	/**
	 * Check whether this instance contains the DUNDEFINED type, recursively
	 * @return bool
	 */
	public function hasUndefined(): bool {
		if ( $this->type === self::DUNDEFINED ) {
			return true;
		}
		if ( $this->type === self::DARRAY ) {
			foreach ( $this->data as $el ) {
				if ( $el->hasUndefined() ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Return a clone of this instance where DUNDEFINED is replaced with DNULL
	 * @return $this
	 */
	public function cloneAsUndefinedReplacedWithNull(): self {
		if ( $this->type === self::DUNDEFINED ) {
			return new self( self::DNULL );
		}
		if ( $this->type === self::DARRAY ) {
			$data = [];
			foreach ( $this->data as $el ) {
				$data[] = $el->cloneAsUndefinedReplacedWithNull();
			}
			return new self( self::DARRAY, $data );
		}
		return clone $this;
	}

	/** Convert shorteners */

	/**
	 * @throws RuntimeException
	 * @return mixed
	 */
	public function toNative() {
		switch ( $this->type ) {
			case self::DBOOL:
				return $this->toBool();
			case self::DSTRING:
				return $this->toString();
			case self::DFLOAT:
				return $this->toFloat();
			case self::DINT:
				return $this->toInt();
			case self::DARRAY:
				$input = $this->toArray();
				$output = [];
				foreach ( $input as $item ) {
					$output[] = $item->toNative();
				}

				return $output;
			case self::DNULL:
			case self::DUNDEFINED:
				return null;
			default:
				// @codeCoverageIgnoreStart
				throw new RuntimeException( "Unknown type" );
				// @codeCoverageIgnoreEnd
		}
	}

	/**
	 * @return bool
	 */
	public function toBool() {
		return self::castTypes( $this, self::DBOOL )->data;
	}

	/**
	 * @return string
	 */
	public function toString() {
		return self::castTypes( $this, self::DSTRING )->data;
	}

	/**
	 * @return float
	 */
	public function toFloat() {
		return self::castTypes( $this, self::DFLOAT )->data;
	}

	/**
	 * @return int
	 */
	public function toInt() {
		return self::castTypes( $this, self::DINT )->data;
	}

	/**
	 * @return int|float
	 */
	public function toNumber() {
		// Types that can be cast to int
		$intLikeTypes = [
			self::DINT,
			self::DBOOL,
			self::DNULL
		];
		return in_array( $this->type, $intLikeTypes, true ) ? $this->toInt() : $this->toFloat();
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return self::castTypes( $this, self::DARRAY )->data;
	}
}
