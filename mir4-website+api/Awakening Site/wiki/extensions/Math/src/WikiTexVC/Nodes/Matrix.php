<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use InvalidArgumentException;

class Matrix extends TexNode {

	/** @var string */
	private $top;
	/** @var TexArray */
	private $mainarg;

	/**
	 * @param string $top
	 * @param TexArray $mainarg
	 * @throws InvalidArgumentException if nested arguments are not of type TexArray
	 */
	public function __construct( string $top, TexArray $mainarg ) {
		foreach ( $mainarg->args as $arg ) {
			if ( !$arg instanceof TexArray ) {
				throw new InvalidArgumentException( 'Nested arguments have to be type of TexArray' );
			}
		}
		parent::__construct( $top, $mainarg );
		$this->top = $top;
		$this->mainarg = $mainarg;
	}

	/**
	 * @return string
	 */
	public function getTop(): string {
		return $this->top;
	}

	/**
	 * @return TexArray
	 */
	public function getMainarg(): TexArray {
		return $this->mainarg;
	}

	public function containsFunc( $target, $args = null ) {
		if ( $args == null ) {
			$args = [
				'\\begin{' . $this->top . '}',
				'\\end{' . $this->top . '}',
				$this->mainarg
			];
		}
		return parent::containsFunc( $target, $args );
	}

	public function inCurlies() {
		return $this->render();
	}

	public function render() {
		return '{\\begin{' . $this->top . '}' . $this->renderMatrix( $this->mainarg ) . '\\end{' . $this->top . '}}';
	}

	public function renderMML( $arguments = [], $state = [] ): string {
		return $this->parseToMML( $this->getTop(), $arguments, null );
	}

	private function renderMatrix( $matrix ) {
		$mapped = array_map( [ self::class, 'renderLine' ], $matrix->args );
		return implode( '\\\\', $mapped );
	}

	private static function renderLine( $l ) {
		$mapped = array_map( static function ( $x ){
			return $x->render();
		}, $l->args );
		return implode( '&', $mapped );
	}

	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->mainarg ];
		}

		$mapped = array_map( function ( $a ){
			return array_map( function ( $p ){
				return parent::extractIdentifiers( $p->args );
			}, $a->args );
		}, $args );

		return self::flatDeep( $mapped );
	}

	private static function flatDeep( $a ) {
		if ( !is_array( $a ) ) {
			return $a;
		}

		$reduced = array_reduce( $a, [ self::class, 'reduceCallback' ], [] );
		return $reduced;
	}

	private static function reduceCallback( $acc, $val ) {
		// Casting to array if output is string, this is required for array_merge function.
		$fld = self::flatDeep( $val );
		if ( !is_array( $fld ) ) {
			$fld = [ $fld ];
		}
		return array_merge( $acc, $fld );
	}

}
