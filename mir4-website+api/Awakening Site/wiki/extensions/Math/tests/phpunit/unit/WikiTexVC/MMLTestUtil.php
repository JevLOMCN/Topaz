<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util;

use DOMDocument;
use InvalidArgumentException;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmath;

/**
 * This Utility class has some methods for running
 * tests for the Tex to MathML converters in WikiTexVC.
 * @author Johannes Stegmüller
 */
class MMLTestUtil {
	public static function getJSON( $filePath ) {
		if ( !file_exists( $filePath ) ) {
			throw new InvalidArgumentException( "No testfile found at specified path: " . $filePath );
		}
		$file = file_get_contents( $filePath );
		return json_decode( $file );
	}

	public static function createJSONstartEnd( $start, $file ) {
		if ( $start ) {
			$generated = "[\n";
		} else {
			$generated = "\n]";
		}
		if ( file_put_contents( $file, $generated, FILE_APPEND ) !== false ) {
			return true;
		}
		return false;
	}

	public static function appendToJSONFile( $dataArray, $file ) {
		$jsonData = json_encode( $dataArray, JSON_PRETTY_PRINT ) . ",";

		if ( file_put_contents( $file, $jsonData, FILE_APPEND ) !== false ) {
			return true;
		}
		return false;
	}

	public static function deleteFile( $file ) {
		if ( file_exists( $file ) ) {
			if ( unlink( $file ) ) {
				return true;
			} else {
				return false;
			}
		}
		return null;
	}

	public static function prettifyXML( $xml, $replaceHeader = true ) {
		$dom = new DOMDocument();
		// Initial block (must before load xml string)
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		// End initial block
		$dom->loadXML( $xml );
		$out = $dom->saveXML();
		if ( $replaceHeader ) {
			// replacing the xml header in a hacky way
			return substr_replace( $out, "", 0, 22 );
		}
		return $out;
	}

	public static function getMMLwrapped( $input ) {
		$math = new MMLmath();
		$mml = $math->encapsulateRaw( $input->renderMML() );
		return self::prettifyXML( $mml );
	}
}
