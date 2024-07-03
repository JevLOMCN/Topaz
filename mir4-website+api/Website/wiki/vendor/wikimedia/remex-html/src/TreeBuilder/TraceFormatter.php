<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Serializer\SerializerNode;
use Wikimedia\RemexHtml\Tokenizer\Attributes;

class TraceFormatter {
	/**
	 * Get a debug tag for an element or null
	 *
	 * @param Element|SerializerNode|null $element
	 * @return string
	 */
	public static function getDebugTag( $element ) {
		if ( !$element ) {
			return '';
		} elseif ( $element instanceof Element || $element instanceof SerializerNode ) {
			return $element->getDebugTag();
		} else {
			return get_class( $element ) . '#' . substr( md5( spl_object_hash( $element ) ), 0, 8 );
		}
	}

	/**
	 * Get a short excerpt of some text
	 *
	 * @param string $text
	 * @return string
	 */
	public static function excerpt( $text ) {
		if ( strlen( $text ) > 20 ) {
			$text = substr( $text, 0, 20 ) . '...';
		}
		return str_replace( "\n", "\\n", $text );
	}

	/**
	 * Get a readable version of the TreeBuilder preposition constants
	 * @param int $prep
	 * @return string
	 */
	public static function getPrepositionName( $prep ) {
		$names = [
			TreeBuilder::BEFORE => 'before',
			TreeBuilder::UNDER => 'under',
			TreeBuilder::ROOT => 'under root'
		];
		return $names[$prep] ?? '???';
	}

	public static function startDocument( $fns, $fn ) {
		return "startDocument";
	}

	public static function endDocument( $pos ) {
		return "endDocument pos=$pos";
	}

	public static function characters( $preposition, $refNode, $text, $start, $length,
								$sourceStart, $sourceLength
	) {
		$excerpt = self::excerpt( substr( $text, $start, $length ) );
		$prepName = self::getPrepositionName( $preposition );
		$refTag = self::getDebugTag( $refNode );

		return "characters \"$excerpt\", $prepName $refTag, pos=$sourceStart, len=$sourceLength";
	}

	public static function insertElement( $preposition, $refNode, Element $element, $void,
		$sourceStart, $sourceLength
	) {
		$prepName = self::getPrepositionName( $preposition );
		$refTag = self::getDebugTag( $refNode );
		$elementTag = self::getDebugTag( $element );
		$voidMsg = $void ? 'void' : '';
		return "insert $elementTag $voidMsg, $prepName $refTag, pos=$sourceStart, len=$sourceLength";
	}

	public static function endTag( Element $element, $sourceStart, $sourceLength ) {
		$elementTag = self::getDebugTag( $element );
		return "end $elementTag, pos=$sourceStart, len=$sourceLength";
	}

	public static function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength
	) {
		$quirksTypes = [
			TreeBuilder::QUIRKS => 'quirks',
			TreeBuilder::NO_QUIRKS => 'no-quirks',
			TreeBuilder::LIMITED_QUIRKS => 'limited-quirks'
		];
		$quirksMsg = $quirksTypes[$quirks];
		return "doctype $name, public=\"$public\", system=\"$system\", " .
			"$quirksMsg, pos=$sourceStart, len=$sourceLength";
	}

	public static function comment( $preposition, $refNode, $text, $sourceStart, $sourceLength ) {
		$prepName = self::getPrepositionName( $preposition );
		$refTag = self::getDebugTag( $refNode );
		$excerpt = self::excerpt( $text );

		return "comment \"$excerpt\", $prepName $refTag, pos=$sourceStart, len=$sourceLength";
	}

	public static function error( $text, $pos ) {
		return "error \"$text\", pos=$pos";
	}

	public static function mergeAttributes( Element $element, Attributes $attrs, $sourceStart ) {
		$elementTag = self::getDebugTag( $element );
		return "merge $elementTag, pos=$sourceStart";
	}

	public static function removeNode( Element $element, $sourceStart ) {
		$elementTag = self::getDebugTag( $element );
		return "remove $elementTag, pos=$sourceStart";
	}

	public static function reparentChildren( Element $element, Element $newParent, $sourceStart ) {
		$elementTag = self::getDebugTag( $element );
		$newParentTag = self::getDebugTag( $newParent );
		return "reparent children of $elementTag under $newParentTag, pos=$sourceStart";
	}
}
