<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Core;

/**
 * Helper trait for implementations of ContentMetadataCollector.
 *
 * This trait is ideally empty.  However, all implementations of
 * ContentMetadataCollector should `use` it.  Then, when a method is
 * changed in the ContentMetadataCollector implementation, compatibility
 * code can be temporarily added to this trait in order to facilitate
 * migration.
 *
 * For example, suppose that the method `getFoo()` in ContentMetadataCollector`
 * was renamed to `getBar()`.  Before, third-party code contains:
 * ```
 * class MyCollector implements ContentMetadataCollector {
 *   use ContentMetadataCollectorCompat;
 *
 *   public function getFoo() { ... }
 * }
 * ```
 * When the method is renamed in the `ContentMetadataCollector` interface
 * we then add the following to `ContentMetadataCollectorCompat`:
 * ```
 * trait ContentMetadataCollectorCompat {
 *   public function getBar() {
 *     return $this->getFoo();
 *   }
 * }
 * ```
 *
 * This prevents `MyCollector` from failing to implement
 * `ContentMetadataCollector` when Parsoid is upgraded to the latest version.
 * Over time, `MyCollector` will rename the method in its own implementation
 * and that will override the default implementation inherited from the
 * `ContentMetadataCollectorCompat` class.  Then eventually the
 * compatibility method can be removed from this trait and we're back
 * where we started.
 *
 * Similarly, if we want to collect some new type of metadata, the
 * collection method can be added to `ContentMetadataCollector` at the
 * same time a default implementation is added to
 * `ContentMetadataCollectorCompat`; again ensuring that we don't
 * unnecessarily break classes which implement
 * `ContentMetadataCollector`.  The default implementation could do
 * nothing, effectively ignoring the collection request, or it could
 * record portions of the metadata using other collection methods.
 */
trait ContentMetadataCollectorCompat {
	/* This trait is empty, in an ideal world. */

	// ContentMetadataCollector::setPageProperty() should be removed
	// at the same time these transitional methods are removed from
	// Compat, leaving only ::setIndexedPageProperty and
	// ::setUnindexedPageProperty in CMC.

	/**
	 * Set a numeric page property whose *value* is intended to be sorted
	 * and indexed.  The sort key used for the property will be the value,
	 * coerced to a number.
	 *
	 * See `::setPageProperty()` for details.
	 *
	 * In the future, we may allow the value to be specified independent
	 * of sort key (T357783).
	 *
	 * @param string $propName The name of the page property
	 * @param int|float|string $numericValue the numeric value
	 * @since 1.42
	 */
	public function setNumericPageProperty( string $propName, $numericValue ): void {
		if ( !is_numeric( $numericValue ) ) {
			throw new \TypeError( __METHOD__ . " with non-numeric value" );
		}
		// @phan-suppress-next-line PhanUndeclaredMethod in CMC interface
		$this->setPageProperty( $propName, 0 + $numericValue );
	}

	/**
	 * Set a page property whose *value* is not intended to be sorted and
	 * indexed.
	 *
	 * See `::setPageProperty()` for details.  It is recommended to
	 * use the empty string if you need a placeholder value (ie, if
	 * it is the *presence* of the property which is important, not
	 * the *value* the property is set to).
	 *
	 * It is still possible to efficiently look up all the pages with
	 * a certain property (the "presence" of it *is* indexed; see
	 * Special:PagesWithProp, list=pageswithprop).
	 *
	 * @param string $propName The name of the page property
	 * @param string $value Optional value; defaults to the empty string.
	 * @since 1.42
	 */
	public function setUnsortedPageProperty( string $propName, string $value = '' ): void {
		// $value is already coerced to string by the argument type hint
		// @phan-suppress-next-line PhanUndeclaredMethod in CMC interface
		$this->setPageProperty( $name, $value );
	}
}
