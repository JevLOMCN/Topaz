<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC;

use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;

class ParserUtil {

	/**
	 * @param TexArray|null $l
	 * @return TexArray
	 */
	public static function lst2arr( $l ) {
		$arr = new TexArray();

		while ( $l !== null ) {
			$first = $l->first();
			if ( $first !== null ) {
				$arr->push( $l->first() );
			}
			$l = $l->second();
		}

		return $arr;
	}

	/**
	 * @param array|null $options
	 * @return array
	 */
	public static function createOptions( $options ) {
		# get reference of the options for usage in functions and initialize with default values.
		$optionsBase = [
			'usemathrm' => false,
			'usemhchem' => false,
			'usemhchemtexified' => false,
			'useintent' => false,
			'oldtexvc' => false,
			'oldmhchem' => false,
			'debug' => false,
			'report_required' => false
		];
		return array_merge( $optionsBase, $options ?? [] );
	}
}
