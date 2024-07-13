<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace Wikimedia\Parsoid\Tools;

/**
 * Class for processing CLI args
 *
 * This code has been extracted from Core's Maintenance.php class
 * by removing all non-CLI arg processing state and code.
 */
class OptsProcessor {

	/**
	 * Array of desired params
	 * @var array
	 */
	public $params = [];

	/**
	 * Array of mapping short parameters to long ones
	 * @var array
	 */
	public $shortParamsMap = [];

	/**
	 * Array of desired args
	 * @var array
	 */
	public $argList = [];

	/**
	 * This is the array of options that were actually passed
	 * @var array
	 */
	protected $options = [];

	/**
	 * This is the array of arguments that were actually passed
	 * @var array
	 */
	public $args = [];

	/**
	 * Allow arbitrary options to be passed, or only specified ones?
	 * @var bool
	 */
	public $allowUnregisteredOptions = false;

	/**
	 * Name of the script currently running
	 * @var string
	 */
	private $self;

	/**
	 * Is the script running in quiet mode?
	 * @var bool
	 */
	private $quiet = false;

	/**
	 * Add a description of the script
	 * @var string
	 */
	private $description = '';

	/**
	 * Generic options added by addDefaultParams()
	 * Generic options which might or not be supported by the script
	 * @var array
	 */
	private $genericParameters = [];

	/**
	 * Generic options which might or not be supported by the script
	 * @var array
	 */
	private $dependentParameters = [];

	/**
	 * Used to read the options in the order they were passed.
	 * Useful for option chaining (Ex. dumpBackup.php). It will
	 * be an empty array if the options are passed in through
	 * loadParamsAndArgs( $self, $opts, $args ).
	 *
	 * This is an array of arrays where
	 * 0 => the option and 1 => parameter value.
	 *
	 * @var array
	 */
	public $orderedOptions = [];

	/**
	 * Adds default args (--help, --quiet right now)
	 */
	public function __construct() {
		$this->addDefaultParams();
		global $argv;
		$this->self = $argv[0];
	}

	/**
	 * Set the description text.
	 * @param string $text The text of the description
	 */
	public function addDescription( string $text ): void {
		$this->description = $text;
	}

	/**
	 * Get the script's name
	 * @return string
	 */
	public function getName(): string {
		return $this->self;
	}

	/**
	 * Checks to see if a particular option is supported. Normally this means
	 * it has been registered by the script via addOption.
	 * @param string $name The name of the option
	 * @return bool true if the option exists, false otherwise
	 */
	public function supportsOption( string $name ): bool {
		return isset( $this->params[$name] );
	}

	/**
	 * Add a parameter to the script. Will be displayed on --help
	 * with the associated description
	 *
	 * @param string $name The name of the param (help, version, etc)
	 * @param string $description The description of the param to show on --help
	 * @param bool $required Is the param required?
	 * @param bool $withArg Is an argument required with this option?
	 * @param string|bool $shortName Character to use as short name
	 * @param bool $multiOccurrence Can this option be passed multiple times?
	 */
	public function addOption(
		string $name, string $description, bool $required = false,
		bool $withArg = false, $shortName = false,
		bool $multiOccurrence = false
	): void {
		$this->params[$name] = [
			'desc' => $description,
			'require' => $required,
			'withArg' => $withArg,
			'shortName' => $shortName,
			'multiOccurrence' => $multiOccurrence,
		];

		if ( $shortName !== false ) {
			$this->shortParamsMap[$shortName] = $name;
		}
	}

	/**
	 * Checks to see if a particular option exists.
	 * @param string $name The name of the option
	 * @return bool
	 */
	public function hasOption( string $name ): bool {
		return isset( $this->options[$name] );
	}

	/**
	 * Get an option, or return the default.
	 *
	 * If the option was added to support multiple occurrences,
	 * this will return an array.
	 *
	 * @param string $name The name of the param
	 * @param mixed $default Anything you want, default null
	 * @return mixed
	 */
	public function getOption( $name, $default = null ) {
		if ( $this->hasOption( $name ) ) {
			return $this->options[$name];
		} else {
			// Set it so we don't have to provide the default again
			$this->options[$name] = $default;

			return $this->options[$name];
		}
	}

	/**
	 * Add some args that are needed
	 * @param string $arg Name of the arg, like 'start'
	 * @param string $description Short description of the arg
	 * @param bool $required Is this required?
	 */
	public function addArg( string $arg, string $description, bool $required = true ) {
		$this->argList[] = [
			'name' => $arg,
			'desc' => $description,
			'require' => $required
		];
	}

	/**
	 * Remove an option.  Useful for removing options that won't be used in your script.
	 * @param string $name The option to remove.
	 */
	public function deleteOption( string $name ): void {
		unset( $this->params[$name] );
	}

	/**
	 * Sets whether to allow unregistered options, which are options passed to
	 * a script that do not match an expected parameter.
	 * @param bool $allow Should we allow?
	 */
	public function setAllowUnregisteredOptions( bool $allow ): void {
		$this->allowUnregisteredOptions = $allow;
	}

	/**
	 * Does a given argument exist?
	 * @param int $argId The integer value (from zero) for the arg
	 * @return bool
	 */
	public function hasArg( int $argId = 0 ): bool {
		return isset( $this->args[$argId] );
	}

	/**
	 * Get an argument.
	 * @param int $argId The integer value (from zero) for the arg
	 * @param mixed $default The default if it doesn't exist
	 * @return mixed
	 */
	public function getArg( int $argId = 0, $default = null ) {
		return $this->hasArg( $argId ) ? $this->args[$argId] : $default;
	}

	public function isQuiet(): bool {
		return $this->quiet;
	}

	/**
	 * Add the default parameters to the scripts
	 */
	public function addDefaultParams(): void {
		# Generic (non script dependent) options:

		$this->addOption( 'help', 'Display this help message', false, false, 'h' );
		$this->addOption( 'quiet', 'Whether to suppress non-error output', false, false, 'q' );

		# Save generic options to display them separately in help
		$this->genericParameters = $this->params;

		# Save additional script dependent options to display
		# them separately in help
		$this->dependentParameters = array_diff_key( $this->params, $this->genericParameters );
	}

	/**
	 * Clear all params and arguments.
	 */
	public function clearParamsAndArgs(): void {
		$this->options = [];
		$this->args = [];
	}

	/**
	 * Load params and arguments from a given array
	 * of command-line arguments
	 *
	 * @param array $argv
	 */
	public function loadWithArgv( array $argv ): void {
		$options = [];
		$args = [];
		$this->orderedOptions = [];

		# Parse arguments
		for ( $arg = reset( $argv ); $arg !== false; $arg = next( $argv ) ) {
			if ( $arg == '--' ) {
				# End of options, remainder should be considered arguments
				$arg = next( $argv );
				while ( $arg !== false ) {
					$args[] = $arg;
					$arg = next( $argv );
				}
				break;
			} elseif ( substr( $arg, 0, 2 ) == '--' ) {
				# Long options
				$option = substr( $arg, 2 );
				if ( isset( $this->params[$option] ) && $this->params[$option]['withArg'] ) {
					$param = next( $argv );
					if ( $param === false ) {
						$this->error( "\nERROR: $option parameter needs a value after it\n" );
						$this->maybeHelp( true );
					}

					$this->setParam( $options, $option, $param );
				} else {
					$bits = explode( '=', $option, 2 );
					$this->setParam( $options, $bits[0], $bits[1] ?? 1 );
				}
			} elseif ( $arg == '-' ) {
				# Lonely "-", often used to indicate stdin or stdout.
				$args[] = $arg;
			} elseif ( substr( $arg, 0, 1 ) == '-' ) {
				# Short options
				$argLength = strlen( $arg );
				for ( $p = 1; $p < $argLength; $p++ ) {
					$option = $arg[$p];
					if ( !isset( $this->params[$option] ) && isset( $this->shortParamsMap[$option] ) ) {
						$option = $this->shortParamsMap[$option];
					}

					if ( isset( $this->params[$option]['withArg'] ) && $this->params[$option]['withArg'] ) {
						$param = next( $argv );
						if ( $param === false ) {
							$this->error( "\nERROR: $option parameter needs a value after it\n" );
							$this->maybeHelp( true );
						}
						$this->setParam( $options, $option, $param );
					} else {
						$this->setParam( $options, $option, 1 );
					}
				}
			} else {
				$args[] = $arg;
			}
		}

		$this->options = $options;
		$this->args = $args;
	}

	/**
	 * Helper function used solely by loadParamsAndArgs
	 * to prevent code duplication
	 *
	 * This sets the param in the options array based on
	 * whether or not it can be specified multiple times.
	 *
	 * @param array &$options
	 * @param string $option
	 * @param mixed $value
	 */
	private function setParam( array &$options, string $option, $value ): void {
		$this->orderedOptions[] = [ $option, $value ];

		if ( isset( $this->params[$option] ) ) {
			$multi = $this->params[$option]['multiOccurrence'];
		} else {
			$multi = false;
		}
		$exists = array_key_exists( $option, $options );
		if ( $multi && $exists ) {
			$options[$option][] = $value;
		} elseif ( $multi ) {
			$options[$option] = [ $value ];
		} elseif ( !$exists ) {
			$options[$option] = $value;
		} else {
			$this->error( "\nERROR: $option parameter given twice\n" );
			$this->maybeHelp( true );
		}
	}

	/**
	 * Process command line arguments
	 * $options becomes an array with keys set to the option names
	 * $args becomes a zero-based array containing the non-option arguments
	 *
	 * @param ?string $self The name of the script, if any
	 * @param ?array $opts An array of options, in form of key=>value
	 * @param ?array $args An array of command line arguments
	 */
	public function loadParamsAndArgs(
		?string $self = null, ?array $opts = null, ?array $args = null
	): void {
		# If we were given opts or args, set those and return early
		if ( $self ) {
			$this->self = $self;
		}
		if ( $opts ) {
			$this->options = $opts;
		}
		if ( $args ) {
			$this->args = $args;
		}

		global $argv;
		$this->self = $argv[0];
		$this->loadWithArgv( array_slice( $argv, 1 ) );
	}

	/**
	 * Run some validation checks on the params, etc
	 */
	public function validateParamsAndArgs(): void {
		$die = false;
		# Check to make sure we've got all the required options
		foreach ( $this->params as $opt => $info ) {
			if ( $info['require'] && !$this->hasOption( $opt ) ) {
				$this->error( "Param $opt required!" );
				$die = true;
			}
		}
		# Check arg list too
		foreach ( $this->argList as $k => $info ) {
			if ( $info['require'] && !$this->hasArg( $k ) ) {
				$this->error( 'Argument <' . $info['name'] . '> required!' );
				$die = true;
			}
		}
		if ( !$this->allowUnregisteredOptions ) {
			# Check for unexpected options
			foreach ( $this->options as $opt => $val ) {
				if ( !$this->supportsOption( $opt ) ) {
					$this->error( "Unexpected option $opt!" );
					$die = true;
				}
			}
		}

		if ( $die ) {
			$this->maybeHelp( true );
		}
	}

	/**
	 * Maybe show the help.
	 * @param bool $force Whether to force the help to show, default false
	 */
	public function maybeHelp( bool $force = false ): void {
		if ( !$force && !$this->hasOption( 'help' ) ) {
			return;
		}

		$screenWidth = 80; // TODO: Calculate this!
		$tab = "    ";
		$descWidth = $screenWidth - ( 2 * strlen( $tab ) );

		ksort( $this->params );

		// Description ...
		if ( $this->description ) {
			print "\n" . wordwrap( $this->description, $screenWidth ) . "\n";
		}
		$output = "\nUsage: php " . basename( $this->self );

		// ... append parameters ...
		if ( $this->params ) {
			$output .= " [--" . implode( "|--", array_keys( $this->params ) ) . "]";
		}

		// ... and append arguments.
		if ( $this->argList ) {
			$output .= ' ';
			foreach ( $this->argList as $k => $arg ) {
				if ( $arg['require'] ) {
					$output .= '<' . $arg['name'] . '>';
				} else {
					$output .= '[' . $arg['name'] . ']';
				}
				if ( $k < count( $this->argList ) - 1 ) {
					$output .= ' ';
				}
			}
		}
		print "$output\n\n";

		# TODO abstract some repetitive code below

		// Generic parameters
		foreach ( $this->genericParameters as $par => $info ) {
			if ( $info['shortName'] !== false ) {
				$par .= " (-{$info['shortName']})";
			}
			$this->output(
				wordwrap( "$tab--$par: " . $info['desc'], $descWidth,
					"\n$tab$tab" ) . "\n"
			);
		}
		print "\n";

		$scriptDependantParams = $this->dependentParameters;
		if ( count( $scriptDependantParams ) > 0 ) {
			print "Script dependent parameters:\n";
			// Parameters description
			foreach ( $scriptDependantParams as $par => $info ) {
				if ( $info['shortName'] !== false ) {
					$par .= " (-{$info['shortName']})";
				}
				$this->output(
					wordwrap( "$tab--$par: " . $info['desc'], $descWidth,
						"\n$tab$tab" ) . "\n"
				);
			}
			print "\n";
		}

		// Script specific parameters not defined on construction by
		// addDefaultParams()
		$scriptSpecificParams = array_diff_key(
			# all script parameters:
			$this->params,
			# remove the default parameters:
			$this->genericParameters,
			$this->dependentParameters
		);
		if ( count( $scriptSpecificParams ) > 0 ) {
			print "Script specific parameters:\n";
			// Parameters description
			foreach ( $scriptSpecificParams as $par => $info ) {
				if ( $info['shortName'] !== false ) {
					$par .= " (-{$info['shortName']})";
				}
				$this->output(
					wordwrap( "$tab--$par: " . $info['desc'], $descWidth,
						"\n$tab$tab" ) . "\n"
				);
			}
			print "\n";
		}

		// Print arguments
		if ( count( $this->argList ) > 0 ) {
			print "Arguments:\n";
			// Arguments description
			foreach ( $this->argList as $info ) {
				$openChar = $info['require'] ? '<' : '[';
				$closeChar = $info['require'] ? '>' : ']';
				$this->output(
					wordwrap( "$tab$openChar" . $info['name'] . "$closeChar: " .
						$info['desc'], $descWidth, "\n$tab$tab" ) . "\n"
				);
			}
			print "\n";
		}

		die( 1 );
	}

	/**
	 * Override to redirect opts processor error messages.
	 *
	 * @param string $err
	 */
	protected function error( string $err ): void {
		error_log( $err );
	}

	/**
	 * Override to redirect opts processor output.
	 *
	 * @param string $out
	 */
	protected function output( string $out ): void {
		print $out;
	}
}
