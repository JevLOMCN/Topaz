<?php

namespace Shellbox\Command;

use Shellbox\Shellbox;
use Shellbox\ShellboxError;

class Validator {
	/** @var array */
	private $config;

	/**
	 * @var array Things that are in BoxedCommand::getClientData() but are not
	 * "options" for validation purposes.
	 */
	private static $nonOptionDataKeys = [
		'routeName',
		'inputFiles',
		'outputFiles',
		'outputGlobs',
		'command'
	];

	/**
	 * @param array $config
	 */
	public function __construct( $config ) {
		$this->config = $config;
	}

	/**
	 * Validate a command
	 *
	 * @param BoxedCommand $command
	 * @throws ValidationError
	 */
	public function validate( BoxedCommand $command ) {
		$route = $command->getRouteName();
		$allowedRoutes = $this->config['allowedRoutes'] ?? null;
		if ( $allowedRoutes !== null && !in_array( $route, $allowedRoutes, true ) ) {
			throw new ValidationError( "The route \"$route\" is not in the list of allowed routes" );
		}

		$routeSpecs = $this->config['routeSpecs'] ?? [];
		if ( !isset( $routeSpecs[$route] ) ) {
			return;
		}
		$this->validateWithSpec( $command, $routeSpecs[$route] );
	}

	/**
	 * Validate a command against a given route spec
	 *
	 * @param BoxedCommand $command
	 * @param array $spec
	 * @throws ValidationError
	 */
	private function validateWithSpec( BoxedCommand $command, $spec ) {
		foreach ( $spec as $target => $targetSpec ) {
			switch ( $target ) {
				case 'inputFiles':
					$this->validateInputFiles( $targetSpec, $command->getInputFiles() );
					break;

				case 'outputFiles':
					$this->validateOutputFiles( $targetSpec, $command->getOutputFiles() );
					break;

				case 'outputGlobs':
					$this->validateOutputGlobs( $targetSpec, $command->getOutputGlobs() );
					break;

				case 'shellFeatures':
					$this->validateShellFeatures(
						$targetSpec, $command->getSyntaxInfo()->getFeatureList() );
					break;

				case 'argv':
					$this->validateArgv( $targetSpec, $command->getSyntaxInfo()->getLiteralArgv() );
					break;

				case 'options':
					$options = array_filter( $command->getClientData() );
					foreach ( self::$nonOptionDataKeys as $key ) {
						unset( $options[$key] );
					}
					$this->validateOptions( $targetSpec, $options );
					break;

				default:
					throw new ValidationError( "Unknown validation target \"$target\"" );
			}
		}
	}

	/**
	 * Validate input files
	 *
	 * @param array $spec
	 * @param InputFile[] $files
	 * @throws ValidationError
	 */
	private function validateInputFiles( $spec, $files ) {
		foreach ( $files as $fileName => $file ) {
			if ( !isset( $spec[$fileName] ) ) {
				throw new ValidationError( "Unexpected input file \"$fileName\"" );
			}
		}
	}

	/**
	 * Validate output files
	 *
	 * @param array $spec
	 * @param OutputFile[] $files
	 * @throws ValidationError
	 */
	private function validateOutputFiles( $spec, $files ) {
		foreach ( $files as $fileName => $file ) {
			if ( !isset( $spec[$fileName] ) ) {
				throw new ValidationError( "Unexpected output file \"$fileName\"" );
			}
		}
	}

	/**
	 * Validate output globs
	 *
	 * @param array $spec
	 * @param OutputGlob[] $globs
	 * @throws ValidationError
	 */
	private function validateOutputGlobs( $spec, $globs ) {
		foreach ( $globs as $glob ) {
			$globName = $glob->getPrefix() . '*.' . $glob->getExtension();
			if ( !isset( $spec[$globName] ) ) {
				throw new ValidationError( "Unexpected glob \"$globName\"" );
			}
		}
	}

	/**
	 * Validate the shell feature list
	 *
	 * @param array $spec
	 * @param string[] $features
	 * @throws ValidationError
	 */
	private function validateShellFeatures( $spec, $features ) {
		$disallowed = array_diff( $features, array_values( $spec ) );
		if ( $disallowed ) {
			throw new ValidationError( "Command uses unexpected shell feature: " .
				implode( ', ', $disallowed ) );
		}
	}

	/**
	 * Validate the argv specification
	 *
	 * @param array $spec
	 * @param string[]|null $argv
	 * @throws ValidationError
	 */
	private function validateArgv( $spec, $argv ) {
		if ( $argv === null ) {
			throw new ValidationError( "argv may only contain literal strings" );
		}
		foreach ( $spec as $i => $argSpec ) {
			$this->validateLiteralOrAllow( $argSpec, "argv[$i]", $argv[$i] ?? null );
		}
	}

	/**
	 * Validate a spec node which may either be a scalar value specifying the
	 * expected value, or an array with the key "allow" and the value being a
	 * type or array of types.
	 *
	 * @param mixed $expected The spec node
	 * @param string $name The name of the thing being validated, for error messages
	 * @param mixed $value The actual value
	 * @throws ValidationError
	 */
	private function validateLiteralOrAllow( $expected, $name, $value ) {
		if ( !is_array( $expected ) ) {
			if ( $expected !== $value ) {
				throw new ValidationError(
					"$name does not match the expected value \"$expected\"" );
			}
		} else {
			foreach ( $expected as $restrictionName => $restrictionValue ) {
				switch ( $restrictionName ) {
					case 'allow':
						$this->validateAllow( $restrictionValue, $name, $value );
						break;

					default:
						throw new ValidationError(
							"Unknown configured restriction type \"$restrictionName\"" );
				}
			}
		}
	}

	/**
	 * Confirm that the value is the allowed type or types
	 *
	 * @param string|string[] $allowedTypes
	 * @param string $name The name of the thing being validated, for error messages
	 * @param mixed $value
	 * @throws ValidationError
	 */
	private function validateAllow( $allowedTypes, $name, $value ) {
		if ( is_array( $allowedTypes ) ) {
			$pass = false;
			foreach ( $allowedTypes as $type ) {
				if ( $this->isType( $type, $value ) ) {
					$pass = true;
					break;
				}
			}
			if ( !$pass ) {
				throw new ValidationError( "$name must be one of: " .
					implode( ', ', $allowedTypes ) );
			}
		} else {
			if ( !$this->isType( $allowedTypes, $value ) ) {
				throw new ValidationError( "$name must be of type $allowedTypes" );
			}
		}
	}

	/**
	 * Validate an array of command options
	 *
	 * @param array $spec The spec node
	 * @param array $options
	 * @throws ValidationError
	 */
	private function validateOptions( $spec, $options ) {
		foreach ( $options as $name => $value ) {
			if ( !isset( $spec[$name] ) ) {
				throw new ValidationError( "unexpected option $name" );
			}
			$this->validateLiteralOrAllow( $spec[$name], $name, $value );
		}
	}

	/**
	 * Verify that the value is of the given type. The known types are:
	 *
	 *  - any: always passes
	 *  - literal: any non-null value
	 *  - float: a float
	 *  - integer: an integer
	 *  - relative: a string containing a valid relative path name with no
	 *    path traversal or components which would be invalid in Windows.
	 *
	 * @param string $type
	 * @param mixed $value
	 * @return bool
	 * @throws ValidationError
	 */
	private function isType( $type, $value ) {
		if ( $type === 'any' ) {
			return true;
		} elseif ( $type === 'literal' ) {
			if ( $value === null ) {
				return false;
			}
		} elseif ( $type === 'float' ) {
			if ( !is_float( $value ) ) {
				return false;
			}
		} elseif ( $type === 'integer' ) {
			if ( !is_int( $value ) ) {
				return false;
			}
		} elseif ( $type === 'relative' ) {
			return $this->isRelative( $value );
		} else {
			throw new ValidationError( "unknown validation type \"$type\"" );
		}
		return true;
	}

	/**
	 * Check if a given string is a valid relative path.
	 *
	 * @param string $path
	 * @return bool
	 */
	private function isRelative( $path ) {
		try {
			Shellbox::normalizePath( $path );
		} catch ( ShellboxError $e ) {
			return false;
		}
		return true;
	}
}
