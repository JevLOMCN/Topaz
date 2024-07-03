<?php

namespace MediaWiki\Extension\TemplateData;

use MediaWiki\Status\Status;
use stdClass;

/**
 * @license GPL-2.0-or-later
 */
class TemplateDataValidator {

	public const PREDEFINED_FORMATS = [
		'block' => "{{_\n| _ = _\n}}",
		'inline' => '{{_|_=_}}',
	];

	private const VALID_ROOT_KEYS = [
		'description',
		'params',
		'paramOrder',
		'sets',
		'maps',
		'format',
	];

	private const VALID_PARAM_KEYS = [
		'label',
		'required',
		'suggested',
		'description',
		'example',
		'deprecated',
		'aliases',
		'autovalue',
		'default',
		'inherits',
		'type',
		'suggestedvalues',
	];

	private const VALID_TYPES = [
		'content',
		'line',
		'number',
		'boolean',
		'string',
		'date',
		'unbalanced-wikitext',
		'unknown',
		'url',
		'wiki-page-name',
		'wiki-user-name',
		'wiki-file-name',
		'wiki-template-name',
	];

	/** @var string[] */
	private array $validParameterTypes;

	/**
	 * @param string[] $additionalParameterTypes
	 */
	public function __construct( array $additionalParameterTypes ) {
		$this->validParameterTypes = array_merge( self::VALID_TYPES, $additionalParameterTypes );
	}

	/**
	 * @param mixed $data
	 *
	 * @return Status
	 */
	public function validate( $data ): Status {
		if ( $data === null ) {
			return Status::newFatal( 'templatedata-invalid-parse' );
		}

		if ( !( $data instanceof stdClass ) ) {
			return Status::newFatal( 'templatedata-invalid-type', 'templatedata', 'object' );
		}

		foreach ( $data as $key => $value ) {
			if ( !in_array( $key, self::VALID_ROOT_KEYS ) ) {
				return Status::newFatal( 'templatedata-invalid-unknown', $key );
			}
		}

		// Root.description
		if ( isset( $data->description ) ) {
			if ( !$this->isValidInterfaceText( $data->description ) ) {
				return Status::newFatal( 'templatedata-invalid-type', 'description',
					'string|object' );
			}
		}

		// Root.format
		if ( isset( $data->format ) ) {
			if ( !is_string( $data->format ) ||
				!( isset( self::PREDEFINED_FORMATS[$data->format] ) ||
					$this->isValidCustomFormatString( $data->format )
				)
			) {
				return Status::newFatal( 'templatedata-invalid-format', 'format' );
			}
		}

		// Root.params
		if ( !isset( $data->params ) ) {
			return Status::newFatal( 'templatedata-invalid-missing', 'params', 'object' );
		}

		if ( !( $data->params instanceof stdClass ) ) {
			return Status::newFatal( 'templatedata-invalid-type', 'params', 'object' );
		}

		return $this->validateParameters( $data->params ) ??
			$this->validateParameterOrder( $data->paramOrder ?? null, $data->params ) ??
			$this->validateSets( $data->sets ?? [], $data->params ) ??
			$this->validateMaps( $data->maps ?? (object)[], $data->params ) ??
			Status::newGood( $data );
	}

	/**
	 * @param stdClass $params
	 * @return Status|null Null on success, otherwise a Status object with the error message
	 */
	private function validateParameters( stdClass $params ): ?Status {
		foreach ( $params as $paramName => $param ) {
			if ( trim( $paramName ) === '' ) {
				return Status::newFatal( 'templatedata-invalid-unnamed-parameter' );
			}

			if ( !( $param instanceof stdClass ) ) {
				return Status::newFatal( 'templatedata-invalid-type', "params.{$paramName}",
					'object' );
			}

			$status = $this->validateParameter( $paramName, $param );
			if ( $status ) {
				return $status;
			}

			if ( isset( $param->inherits ) && !isset( $params->{ $param->inherits } ) ) {
				return Status::newFatal( 'templatedata-invalid-missing',
					"params.{$param->inherits}" );
			}
		}

		return null;
	}

	/**
	 * @param string $paramName
	 * @param stdClass $param
	 * @return Status|null Null on success, otherwise a Status object with the error message
	 */
	private function validateParameter( string $paramName, stdClass $param ): ?Status {
		foreach ( $param as $key => $value ) {
			if ( !in_array( $key, self::VALID_PARAM_KEYS ) ) {
				return Status::newFatal( 'templatedata-invalid-unknown',
					"params.{$paramName}.{$key}" );
			}
		}

		// Param.label
		if ( isset( $param->label ) ) {
			if ( !$this->isValidInterfaceText( $param->label ) ) {
				return Status::newFatal( 'templatedata-invalid-type',
					"params.{$paramName}.label", 'string|object' );
			}
		}

		// Param.required
		if ( isset( $param->required ) ) {
			if ( !is_bool( $param->required ) ) {
				return Status::newFatal( 'templatedata-invalid-type',
					"params.{$paramName}.required", 'boolean' );
			}
		}

		// Param.suggested
		if ( isset( $param->suggested ) ) {
			if ( !is_bool( $param->suggested ) ) {
				return Status::newFatal( 'templatedata-invalid-type',
					"params.{$paramName}.suggested", 'boolean' );
			}
		}

		// Param.description
		if ( isset( $param->description ) ) {
			if ( !$this->isValidInterfaceText( $param->description ) ) {
				return Status::newFatal( 'templatedata-invalid-type',
					"params.{$paramName}.description", 'string|object' );
			}
		}

		// Param.example
		if ( isset( $param->example ) ) {
			if ( !$this->isValidInterfaceText( $param->example ) ) {
				return Status::newFatal( 'templatedata-invalid-type',
					"params.{$paramName}.example", 'string|object' );
			}
		}

		// Param.deprecated
		if ( isset( $param->deprecated ) ) {
			if ( !is_bool( $param->deprecated ) && !is_string( $param->deprecated ) ) {
				return Status::newFatal( 'templatedata-invalid-type',
					"params.{$paramName}.deprecated", 'boolean|string' );
			}
		}

		// Param.aliases
		if ( isset( $param->aliases ) ) {
			if ( !is_array( $param->aliases ) ) {
				return Status::newFatal( 'templatedata-invalid-type',
					"params.{$paramName}.aliases", 'array' );
			}
			foreach ( $param->aliases as $i => $alias ) {
				if ( !is_int( $alias ) && !is_string( $alias ) ) {
					return Status::newFatal( 'templatedata-invalid-type',
						"params.{$paramName}.aliases[$i]", 'int|string' );
				}
			}
		}

		// Param.autovalue
		if ( isset( $param->autovalue ) ) {
			if ( !is_string( $param->autovalue ) ) {
				// TODO: Validate the autovalue values.
				return Status::newFatal( 'templatedata-invalid-type',
					"params.{$paramName}.autovalue", 'string' );
			}
		}

		// Param.default
		if ( isset( $param->default ) ) {
			if ( !$this->isValidInterfaceText( $param->default ) ) {
				return Status::newFatal( 'templatedata-invalid-type',
					"params.{$paramName}.default", 'string|object' );
			}
		}

		// Param.type
		if ( isset( $param->type ) ) {
			if ( !is_string( $param->type ) ) {
				return Status::newFatal( 'templatedata-invalid-type',
					"params.{$paramName}.type", 'string' );
			}

			if ( !in_array( $param->type, $this->validParameterTypes ) ) {
				return Status::newFatal( 'templatedata-invalid-value',
					'params.' . $paramName . '.type' );
			}
		}

		// Param.suggestedvalues
		if ( isset( $param->suggestedvalues ) ) {
			if ( !is_array( $param->suggestedvalues ) ) {
				return Status::newFatal( 'templatedata-invalid-type',
					"params.{$paramName}.suggestedvalues", 'array' );
			}
			foreach ( $param->suggestedvalues as $i => $value ) {
				if ( !is_string( $value ) ) {
					return Status::newFatal( 'templatedata-invalid-type',
						"params.{$paramName}.suggestedvalues[$i]", 'string' );
				}
			}
		}

		return null;
	}

	/**
	 * @param mixed $paramOrder
	 * @param stdClass $params
	 *
	 * @return Status|null
	 */
	private function validateParameterOrder( $paramOrder, stdClass $params ): ?Status {
		if ( $paramOrder === null ) {
			return null;
		} elseif ( !is_array( $paramOrder ) ) {
			return Status::newFatal( 'templatedata-invalid-type', 'paramOrder', 'array' );
		} elseif ( count( $paramOrder ) < count( (array)$params ) ) {
			$missing = array_diff( array_keys( (array)$params ), $paramOrder );
			return Status::newFatal( 'templatedata-invalid-missing',
				'paramOrder[ "' . implode( '", "', $missing ) . '" ]' );
		}

		// Validate each of the values corresponds to a parameter and that there are no
		// duplicates
		$seen = [];
		foreach ( $paramOrder as $i => $param ) {
			if ( !isset( $params->$param ) ) {
				return Status::newFatal( 'templatedata-invalid-value', "paramOrder[ \"$param\" ]" );
			}
			if ( isset( $seen[$param] ) ) {
				return Status::newFatal( 'templatedata-invalid-duplicate-value',
					"paramOrder[$i]", "paramOrder[{$seen[$param]}]", $param );
			}
			$seen[$param] = $i;
		}

		return null;
	}

	/**
	 * @param mixed $sets
	 * @param stdClass $params
	 *
	 * @return Status|null
	 */
	private function validateSets( $sets, stdClass $params ): ?Status {
		if ( !is_array( $sets ) ) {
			return Status::newFatal( 'templatedata-invalid-type', 'sets', 'array' );
		}

		foreach ( $sets as $setNr => $setObj ) {
			if ( !( $setObj instanceof stdClass ) ) {
				return Status::newFatal( 'templatedata-invalid-value', "sets.{$setNr}" );
			}

			if ( !isset( $setObj->label ) ) {
				return Status::newFatal( 'templatedata-invalid-missing', "sets.{$setNr}.label",
					'string|object' );
			}

			if ( !$this->isValidInterfaceText( $setObj->label ) ) {
				return Status::newFatal( 'templatedata-invalid-type', "sets.{$setNr}.label",
					'string|object' );
			}

			if ( !isset( $setObj->params ) ) {
				return Status::newFatal( 'templatedata-invalid-missing', "sets.{$setNr}.params",
					'array' );
			}

			if ( !is_array( $setObj->params ) ) {
				return Status::newFatal( 'templatedata-invalid-type', "sets.{$setNr}.params",
					'array' );
			}

			if ( !count( $setObj->params ) ) {
				return Status::newFatal( 'templatedata-invalid-empty-array',
					"sets.{$setNr}.params" );
			}

			foreach ( $setObj->params as $i => $param ) {
				if ( !isset( $params->$param ) ) {
					return Status::newFatal( 'templatedata-invalid-value',
						"sets.{$setNr}.params[$i]" );
				}
			}
		}

		return null;
	}

	/**
	 * @param mixed $maps
	 * @param stdClass $params
	 *
	 * @return Status|null
	 */
	private function validateMaps( $maps, stdClass $params ): ?Status {
		if ( !( $maps instanceof stdClass ) ) {
			return Status::newFatal( 'templatedata-invalid-type', 'maps', 'object' );
		}

		foreach ( $maps as $consumerId => $map ) {
			if ( !( $map instanceof stdClass ) ) {
				return Status::newFatal( 'templatedata-invalid-type', "maps.$consumerId",
					'object' );
			}

			foreach ( $map as $key => $value ) {
				// Key is not validated as this is used by a third-party application
				// Value must be 2d array of parameter names, 1d array of parameter names, or valid
				// parameter name
				if ( is_array( $value ) ) {
					foreach ( $value as $key2 => $value2 ) {
						if ( is_array( $value2 ) ) {
							foreach ( $value2 as $key3 => $value3 ) {
								if ( !is_string( $value3 ) ) {
									return Status::newFatal( 'templatedata-invalid-type',
										"maps.{$consumerId}.{$key}[$key2][$key3]", 'string' );
								}
								if ( !isset( $params->$value3 ) ) {
									return Status::newFatal( 'templatedata-invalid-param', $value3,
										"maps.$consumerId.{$key}[$key2][$key3]" );
								}
							}
						} elseif ( is_string( $value2 ) ) {
							if ( !isset( $params->$value2 ) ) {
								return Status::newFatal( 'templatedata-invalid-param', $value2,
									"maps.$consumerId.{$key}[$key2]" );
							}
						} else {
							return Status::newFatal( 'templatedata-invalid-type',
								"maps.{$consumerId}.{$key}[$key2]", 'string|array' );
						}
					}
				} elseif ( is_string( $value ) ) {
					if ( !isset( $params->$value ) ) {
						return Status::newFatal( 'templatedata-invalid-param', $value,
							"maps.{$consumerId}.{$key}" );
					}
				} else {
					return Status::newFatal( 'templatedata-invalid-type',
						"maps.{$consumerId}.{$key}", 'string|array' );
				}
			}
		}

		return null;
	}

	private function isValidCustomFormatString( ?string $format ): bool {
		return $format && preg_match( '/^\n?{{ *_+\n? *\|\n? *_+ *= *_+\n? *}}\n?$/', $format );
	}

	/**
	 * @param mixed $text
	 * @return bool
	 */
	private function isValidInterfaceText( $text ): bool {
		if ( $text instanceof stdClass ) {
			$isEmpty = true;
			// An (array) cast would return private/protected properties as well
			foreach ( get_object_vars( $text ) as $languageCode => $string ) {
				// TODO: Do we need to validate if these are known interface language codes?
				if ( !is_string( $languageCode ) ||
					ltrim( $languageCode ) === '' ||
					!is_string( $string )
				) {
					return false;
				}
				$isEmpty = false;
			}
			return !$isEmpty;
		}

		return is_string( $text );
	}

}
