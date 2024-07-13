<?php

namespace MediaWiki\Extension\TemplateData;

use stdClass;

/**
 * @license GPL-2.0-or-later
 */
class TemplateDataNormalizer {

	public const DEPRECATED_PARAMETER_TYPES = [
		'string/line' => 'line',
		'string/wiki-page-name' => 'wiki-page-name',
		'string/wiki-user-name' => 'wiki-user-name',
		'string/wiki-file-name' => 'wiki-file-name',
	];

	private string $contentLanguageCode;

	public function __construct( string $contentLanguageCode ) {
		$this->contentLanguageCode = $contentLanguageCode;
	}

	/**
	 * @param stdClass $data Expected to be valid according to the {@see TemplateDataValidator}
	 */
	public function normalize( stdClass $data ): void {
		$data->description ??= null;
		$data->sets ??= [];
		$data->maps ??= (object)[];
		$data->format ??= null;

		$this->normaliseInterfaceText( $data->description );
		foreach ( $data->sets as $setObj ) {
			$this->normaliseInterfaceText( $setObj->label );
		}

		if ( isset( $data->params ) ) {
			foreach ( $data->params as $param ) {
				if ( isset( $param->inherits ) && isset( $data->params->{ $param->inherits } ) ) {
					$parent = $data->params->{ $param->inherits };
					foreach ( $parent as $key => $value ) {
						if ( !isset( $param->$key ) ) {
							$param->$key = is_object( $parent->$key ) ?
								clone $parent->$key :
								$parent->$key;
						}
					}
					unset( $param->inherits );
				}
				$this->normalizeParameter( $param );
			}
		}
	}

	private function normalizeParameter( stdClass $paramObj ): void {
		$paramObj->label ??= null;
		$paramObj->description ??= null;
		$paramObj->required ??= false;
		$paramObj->suggested ??= false;
		$paramObj->deprecated ??= false;
		$paramObj->aliases ??= [];
		$paramObj->type ??= 'unknown';
		$paramObj->autovalue ??= null;
		$paramObj->default ??= null;
		$paramObj->suggestedvalues ??= [];
		$paramObj->example ??= null;

		$this->normaliseInterfaceText( $paramObj->label );
		$this->normaliseInterfaceText( $paramObj->description );
		$this->normaliseInterfaceText( $paramObj->default );
		$this->normaliseInterfaceText( $paramObj->example );

		foreach ( $paramObj->aliases as &$alias ) {
			if ( is_int( $alias ) ) {
				$alias = (string)$alias;
			}
		}

		// Map deprecated types to newer versions
		if ( isset( self::DEPRECATED_PARAMETER_TYPES[$paramObj->type] ) ) {
			$paramObj->type = self::DEPRECATED_PARAMETER_TYPES[$paramObj->type];
		}
	}

	/**
	 * @param string|stdClass &$text
	 */
	private function normaliseInterfaceText( &$text ): void {
		if ( is_string( $text ) ) {
			$text = (object)[ $this->contentLanguageCode => $text ];
		}
	}

}
