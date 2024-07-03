<?php
/**
 * This implements the "json" content model as an extension, to allow editing
 * JSON data structures using Visual Editor.  It represents the JSON
 * structure as a nested table.
 */

declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext\JSON;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Core\ContentModelHandler;
use Wikimedia\Parsoid\Core\SelserData;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Ext\DOMUtils;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Ext\PHPUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;

/**
 * Native Parsoid implementation of the "json" contentmodel.
 */
class JSON extends ContentModelHandler implements ExtensionModule {
	private const PARSE_ERROR_HTML = "<table typeof=\"mw:Error\" data-mw='{\"errors\":[{\"key\":\"bad-json\"}]}'>";

	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'JSON content',
			'contentModels' => [
				'json' => self::class,
			],
		];
	}

	/**
	 * @param Element $parent
	 * @param array|object|string $val
	 */
	private function rootValueTable( Element $parent, $val ): void {
		if ( is_array( $val ) ) {
			// Wrap arrays in another array so they're visually boxed in a
			// container.  Otherwise they are visually indistinguishable from
			// a single value.
			self::arrayTable( $parent, [ $val ] );
			return;
		}

		if ( $val && is_object( $val ) ) {
			self::objectTable( $parent, (array)$val );
			return;
		}

		DOMCompat::setInnerHTML( $parent,
			'<table class="mw-json mw-json-single-value"><tbody><tr><td>' );
		self::primitiveValue( DOMCompat::querySelector( $parent, 'td' ), $val );
	}

	private function objectTable( Element $parent, array $val ): void {
		DOMCompat::setInnerHTML( $parent,
			'<table class="mw-json mw-json-object"><tbody>' );
		$tbody = $parent->firstChild->firstChild;
		DOMUtils::assertElt( $tbody );
		$keys = array_keys( $val );
		if ( count( $keys ) ) {
			foreach ( $val as $k => $v ) {
				self::objectRow( $tbody, (string)$k, $v );
			}
		} else {
			DOMCompat::setInnerHTML( $tbody,
				'<tr><td class="mw-json-empty">' );
		}
	}

	/**
	 * @param Element $parent
	 * @param ?string $key
	 * @param mixed $val
	 */
	private function objectRow( Element $parent, ?string $key, $val ): void {
		$tr = $parent->ownerDocument->createElement( 'tr' );
		if ( $key !== null ) {
			$th = $parent->ownerDocument->createElement( 'th' );
			$th->textContent = $key;
			$tr->appendChild( $th );
		}
		self::valueCell( $tr, $val );
		$parent->appendChild( $tr );
	}

	private function arrayTable( Element $parent, array $val ): void {
		DOMCompat::setInnerHTML( $parent,
			'<table class="mw-json mw-json-array"><tbody>' );
		$tbody = $parent->firstChild->firstChild;
		DOMUtils::assertElt( $tbody );
		if ( count( $val ) ) {
			foreach ( $val as $v ) {
				self::objectRow( $tbody, null, $v );
			}
		} else {
			DOMCompat::setInnerHTML( $tbody,
				'<tr><td class="mw-json-empty">' );
		}
	}

	/**
	 * @param Element $parent
	 * @param mixed $val
	 */
	private function valueCell( Element $parent, $val ): void {
		$td = $parent->ownerDocument->createElement( 'td' );
		if ( is_array( $val ) ) {
			self::arrayTable( $td, $val );
		} elseif ( $val && is_object( $val ) ) {
			self::objectTable( $td, (array)$val );
		} else {
			DOMCompat::getClassList( $td )->add( 'value' );
			self::primitiveValue( $td, $val );
		}
		$parent->appendChild( $td );
	}

	/**
	 * @param Element $parent
	 * @param string|int|bool|null $val
	 */
	private function primitiveValue( Element $parent, $val ): void {
		if ( $val === null ) {
			DOMCompat::getClassList( $parent )->add( 'mw-json-null' );
			$parent->textContent = 'null';
			return;
		} elseif ( is_bool( $val ) ) {
			DOMCompat::getClassList( $parent )->add( 'mw-json-boolean' );
			$parent->textContent = $val ? 'true' : 'false';
			return;
		} elseif ( is_int( $val ) || is_float( $val ) ) {
			DOMCompat::getClassList( $parent )->add( 'mw-json-number' );
		} elseif ( is_string( $val ) ) {
			DOMCompat::getClassList( $parent )->add( 'mw-json-string' );
		}
		$parent->textContent = (string)$val;
	}

	/**
	 * JSON to HTML.
	 * Implementation matches that from includes/content/JsonContent.php in
	 * mediawiki core, except that we distinguish value types.
	 * @param ParsoidExtensionAPI $extApi
	 * @return Document
	 */
	public function toDOM( ParsoidExtensionAPI $extApi ): Document {
		// @phan-suppress-next-line PhanDeprecatedFunction not ready for this yet
		$jsonText = $extApi->getPageConfig()->getPageMainContent();
		$document = $extApi->getTopLevelDoc();
		$body = DOMCompat::getBody( $document );

		// PORT-FIXME: When production moves to PHP 7.3, re-enable this try
		// catch code

		// try {
		// 	$src = json_decode( $jsonText, false, 6, JSON_THROW_ON_ERROR );
		// 	self::rootValueTable( $body, $src );
		// } catch ( JsonException $e ) {
		// 	DOMCompat::setInnerHTML( $body, self::PARSE_ERROR_HTML );
		// }

		$src = json_decode( $jsonText, false, 6 );
		if ( $src === null && json_last_error() !== JSON_ERROR_NONE ) {
			DOMCompat::setInnerHTML( $body, self::PARSE_ERROR_HTML );
		} else {
			self::rootValueTable( $body, $src );
		}

		// end of PHP 7.2 compatible error handling code, remove whem enabling
		// 7.3+ try catch code

		// We're responsible for running the standard DOMPostProcessor on our
		// resulting document.
		$extApi->postProcessDOM( $document );

		return $document;
	}

	/**
	 * RootValueTableFrom
	 * @param Element $el
	 * @return array|false|int|string|null
	 */
	private function rootValueTableFrom( Element $el ) {
		if ( DOMUtils::hasClass( $el, 'mw-json-single-value' ) ) {
			return self::primitiveValueFrom( DOMCompat::querySelector( $el, 'tr > td' ) );
		} elseif ( DOMUtils::hasClass( $el, 'mw-json-array' ) ) {
			return self::arrayTableFrom( $el )[0];
		} else {
			return self::objectTableFrom( $el );
		}
	}

	/**
	 * @param Element $el
	 * @return array
	 */
	private function objectTableFrom( Element $el ) {
		Assert::invariant( DOMUtils::hasClass( $el, 'mw-json-object' ),
			'Expected mw-json-object' );
		$tbody = $el;
		if ( $tbody->firstChild ) {
			$child = $tbody->firstChild;
			DOMUtils::assertElt( $child );
			if ( DOMCompat::nodeName( $child ) === 'tbody' ) {
				$tbody = $child;
			}
		}
		$rows = $tbody->childNodes;
		$obj = [];
		$empty = count( $rows ) === 0;
		if ( !$empty ) {
			$child = $rows->item( 0 )->firstChild;
			DOMUtils::assertElt( $child );
			if ( DOMUtils::hasClass( $child, 'mw-json-empty' ) ) {
				$empty = true;
			}
		}
		if ( !$empty ) {
			for ( $i = 0; $i < count( $rows ); $i++ ) {
				$item = $rows->item( $i );
				DOMUtils::assertElt( $item );
				self::objectRowFrom( $item, $obj, null );
			}
		}
		return $obj;
	}

	private function objectRowFrom( Element $tr, array &$obj, ?int $key ): void {
		$td = $tr->firstChild;
		if ( $key === null ) {
			$key = $td->textContent;
			$td = $td->nextSibling;
		}
		DOMUtils::assertElt( $td );
		$obj[$key] = self::valueCellFrom( $td );
	}

	private function arrayTableFrom( Element $el ): array {
		Assert::invariant( DOMUtils::hasClass( $el, 'mw-json-array' ),
			'Expected ms-json-array' );
		$tbody = $el;
		if ( $tbody->firstChild ) {
			$child = $tbody->firstChild;
			DOMUtils::assertElt( $child );
			if ( DOMCompat::nodeName( $child ) === 'tbody' ) {
				$tbody = $child;
			}
		}
		$rows = $tbody->childNodes;
		$arr = [];
		$empty = count( $rows ) === 0;
		if ( !$empty ) {
			$child = $rows->item( 0 )->firstChild;
			DOMUtils::assertElt( $child );
			if ( DOMUtils::hasClass( $child, 'mw-json-empty' ) ) {
				$empty = true;
			}
		}
		if ( !$empty ) {
			for ( $i = 0; $i < count( $rows ); $i++ ) {
				$item = $rows->item( $i );
				DOMUtils::assertElt( $item );
				self::objectRowFrom( $item, $arr, $i );
			}
		}
		return $arr;
	}

	/**
	 * @param Element $el
	 * @return array|object|false|float|int|string|null
	 */
	private function valueCellFrom( Element $el ) {
		Assert::invariant( DOMCompat::nodeName( $el ) === 'td', 'Expected tagName = td' );
		$table = $el->firstChild;
		if ( $table instanceof Element ) {
			if ( DOMUtils::hasClass( $table, 'mw-json-array' ) ) {
				return self::arrayTableFrom( $table );
			} elseif ( DOMUtils::hasClass( $table, 'mw-json-object' ) ) {
				return self::objectTableFrom( $table );
			}
		} else {
			return self::primitiveValueFrom( $el );
		}
	}

	/**
	 * @param Element $el
	 * @return false|float|int|string|null
	 */
	private function primitiveValueFrom( Element $el ) {
		if ( DOMUtils::hasClass( $el, 'mw-json-null' ) ) {
			return null;
		} elseif ( DOMUtils::hasClass( $el, 'mw-json-boolean' ) ) {
			return str_contains( $el->textContent, 'true' );
		} elseif ( DOMUtils::hasClass( $el, 'mw-json-number' ) ) {
			return floatval( $el->textContent );
		} elseif ( DOMUtils::hasClass( $el, 'mw-json-string' ) ) {
			return (string)$el->textContent;
		} else {
			return null; // shouldn't happen.
		}
	}

	/**
	 * DOM to JSON.
	 * @param ParsoidExtensionAPI $extApi
	 * @param ?SelserData $selserData
	 * @return string
	 */
	public function fromDOM(
		ParsoidExtensionAPI $extApi, ?SelserData $selserData = null
	): string {
		$body = DOMCompat::getBody( $extApi->getTopLevelDoc() );
		$t = $body->firstChild;
		DOMUtils::assertElt( $t );
		Assert::invariant( $t && DOMCompat::nodeName( $t ) === 'table',
			'Expected tagName = table' );
		self::rootValueTableFrom( $t );
		return PHPUtils::jsonEncode( self::rootValueTableFrom( $t ) );
	}

}
