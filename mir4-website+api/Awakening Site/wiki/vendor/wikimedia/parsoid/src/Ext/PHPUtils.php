<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext;

use Wikimedia\Parsoid\Utils\PHPUtils as PHPU;

/**
 * This class contains sundry helpers unrelated to core Parsoid
 */
class PHPUtils {

	/**
	 * Convert an iterable to an array.
	 *
	 * This function is similar to *but not the same as* the built-in
	 * iterator_to_array, because arrays are iterable but not Traversable!
	 *
	 * This function is also present in the wmde/iterable-functions library,
	 * but it's short enough that we don't need to pull in an entire new
	 * dependency here.
	 *
	 * @see https://stackoverflow.com/questions/44587973/php-iterable-to-array-or-traversable
	 * @see https://github.com/wmde/iterable-functions/blob/master/src/functions.php
	 *
	 * @phan-template T
	 * @param iterable<T> $iterable
	 * @return array<T>
	 */
	public static function iterable_to_array( iterable $iterable ): array { // phpcs:ignore MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName,Generic.Files.LineLength.TooLong
		return PHPU::iterable_to_array( $iterable );
	}

	/**
	 * json_encode wrapper function
	 * - unscapes slashes and unicode
	 *
	 * @param mixed $o
	 * @return string
	 */
	public static function jsonEncode( $o ): string {
		return PHPU::jsonEncode( $o );
	}

	/**
	 * If a string starts with a given prefix, remove the prefix. Otherwise,
	 * return the original string. Like preg_replace( "/^$prefix/", '', $subject )
	 * except about 1.14x faster in the replacement case and 2x faster in
	 * the no-op case.
	 *
	 * Note: adding type declarations to the parameters adds an overhead of 3%.
	 * The benchmark above was without type declarations.
	 *
	 * @param string $subject
	 * @param string $prefix
	 * @return string
	 */
	public static function stripPrefix( $subject, $prefix ) {
		return PHPU::stripPrefix( $subject, $prefix );
	}

	/**
	 * If a string ends with a given suffix, remove the suffix. Otherwise,
	 * return the original string. Like preg_replace( "/$suffix$/", '', $subject )
	 * except faster.
	 *
	 * @param string $subject
	 * @param string $suffix
	 * @return string
	 */
	public static function stripSuffix( $subject, $suffix ) {
		return PHPU::stripSuffix( $subject, $suffix );
	}

}
