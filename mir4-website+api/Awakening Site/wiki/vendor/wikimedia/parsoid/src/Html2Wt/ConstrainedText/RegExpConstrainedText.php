<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\ConstrainedText;

/**
 * This subclass allows specification of a regular expression for
 * acceptable (or prohibited) leading (and/or trailing) contexts.
 *
 * This is an abstract class; it's intended to be subclassed, not
 * used directly, and so it not included in the lists of types
 * tried by `fromSelSer`.
 */
abstract class RegExpConstrainedText extends ConstrainedText {
	/** @var \Closure(string):bool */
	public $prefixMatcher;
	/** @var \Closure(string):bool */
	public $suffixMatcher;

	protected function __construct( array $args ) {
		parent::__construct( $args );
		$this->prefix ??= '<nowiki/>';
		$this->suffix ??= '<nowiki/>';
		// functions which return true if escape prefix/suffix need to be added
		$matcher = static function ( string $re, bool $invert ): callable {
			return ( static function ( string $context ) use ( $re, $invert ): bool {
				return ( preg_match( $re, $context ) ) ? !$invert : $invert;
			} );
		};
		$false = static function ( string $context ): bool {
			return false;
		};
		$this->prefixMatcher =
			( $args['goodPrefix'] ?? false ) ?
				$matcher( $args['goodPrefix'], true ) :
			( ( $args['badPrefix'] ?? false ) ?
				$matcher( $args['badPrefix'], false ) : $false );
		$this->suffixMatcher =
			( $args['goodSuffix'] ?? false ) ?
				$matcher( $args['goodSuffix'], true ) :
			( ( $args['badSuffix'] ?? false ) ?
				$matcher( $args['badSuffix'], false ) : $false );
	}

	/** @inheritDoc */
	public function escape( State $state ): Result {
		$result = new Result( $this->text );
		if ( call_user_func( $this->prefixMatcher, $state->leftContext ) ) {
			$result->prefix = $this->prefix;
		}
		if ( call_user_func( $this->suffixMatcher, $state->rightContext ) ) {
			$result->suffix = $this->suffix;
		}
		return $result;
	}
}
