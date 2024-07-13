<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC;

use InvalidArgumentException;

class TexUtil {
	private static $instance = null;
	private $allFunctions;
	private $baseElements;

	/**
	 * Loads the file texutil.json
	 * allFunctions holds the root-level function keys
	 * other objects are second level elements and hold all functions which are assigned to this second level elements
	 */
	private function __construct() {
		$jsonContent = $this->getJSON();
		// dynamically create functions from the content
		$this->allFunctions = [];
		$this->baseElements = [];
		$this->allFunctions["\\begin"] = true;
		$this->allFunctions["\\end"] = true;

		foreach ( $jsonContent as $key => $value ) {
			// Adding all basic elements as functions
			foreach ( $value as $elementKey => $element ) {
				if ( !array_key_exists( $elementKey, $this->baseElements ) ) {
					$this->baseElements[$elementKey] = [];
					$this->baseElements[$elementKey][$key] = $element;

				} else {
					if ( !array_key_exists( $key, $this->baseElements[$elementKey] ) ) {
						$this->baseElements[$elementKey][$key] = $element;
					}
				}
			}
			// Adding function to all functions
			$this->allFunctions[$key] = true;
		}
	}

	public static function removeInstance() {
		self::$instance = null;
	}

	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new TexUtil();
		}

		return self::$instance;
	}

	/**
	 * Returning the base elements array.
	 * This is only used for testing in TexUtilTest.php.
	 * @return array
	 */
	public function getBaseElements() {
		return $this->baseElements;
	}

	/**
	 * Getting an element by key in allFunctions.
	 * If the key is defined, return true if not false.
	 * @param string $key string to check in allFunctions
	 * @return bool
	 */
	public function getAllFunctionsAt( string $key ) {
		if ( array_key_exists( $key, $this->allFunctions ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Allows to directly call functions defined in from the json-file.
	 * @param mixed $func
	 * @param mixed $params
	 * @return false|mixed
	 */
	public function __call( $func, $params ) {
		if ( array_key_exists( $func, $this->baseElements ) ) {
			$currentFunction = $this->baseElements[$func];
			if ( array_key_exists( $params[0], $currentFunction ) ) {
				return $currentFunction[$params[0]];
			} else {
				return false;
			}
		} else {
			throw new InvalidArgumentException( "Function not defined in json " . $func );

		}
	}

	/**
	 * Reads the json file to an object
	 * @return array
	 */
	private function getJSON() {
		$file = self::getJsonFile();
		$json = json_decode( $file, true );
		return $json;
	}

	/**
	 * @return false|string
	 */
	public static function getJsonFile() {
		return file_get_contents( __DIR__ . '/texutil.json' );
	}
}
