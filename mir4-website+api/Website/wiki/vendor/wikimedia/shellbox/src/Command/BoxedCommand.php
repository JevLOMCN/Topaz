<?php

namespace Shellbox\Command;

use Psr\Http\Message\StreamInterface;
use Shellbox\Shellbox;
use Shellbox\ShellboxError;

/**
 * A command with input and output files in an otherwise empty working directory.
 */
class BoxedCommand extends Command {
	/** @var string|null */
	private $routeName;
	/** @var InputFile[] */
	private $inputFiles = [];
	/** @var OutputFile[] */
	private $outputFiles = [];
	/** @var OutputGlob[] */
	private $outputGlobs = [];
	/** @var BoxedExecutor */
	private $executor;

	/**
	 * @internal Use BoxedExecutor::createCommand()
	 * @param BoxedExecutor $boxedExecutor
	 */
	public function __construct( BoxedExecutor $boxedExecutor ) {
		$this->executor = $boxedExecutor;
	}

	/**
	 * Set the route name. This should be a short string used by system
	 * administrators to identify the command being run, in order to route it
	 * to the correct container.
	 *
	 * @param string $routeName
	 * @return $this
	 */
	public function routeName( string $routeName ) {
		$this->routeName = $routeName;
		return $this;
	}

	/**
	 * Add an input file, with the contents given by a string.
	 *
	 * @param string $boxedName The file name relative to the working directory
	 * @param string $contents The file contents
	 * @return $this
	 */
	public function inputFileFromString( string $boxedName, string $contents ) {
		$boxedName = $this->normalizeBoxedPath( $boxedName );
		$this->inputFiles[$boxedName] = new InputFileFromString( $contents );
		return $this;
	}

	/**
	 * Add an input file, with the contents copied from another file.
	 *
	 * @param string $boxedName The destination file name relative to the
	 *   working directory
	 * @param string $sourcePath The path of the source file
	 * @return $this
	 */
	public function inputFileFromFile( string $boxedName, string $sourcePath ) {
		$boxedName = $this->normalizeBoxedPath( $boxedName );
		$this->inputFiles[$boxedName] = new InputFileFromFile( $sourcePath );
		return $this;
	}

	/**
	 * Add an input file, with the contents copied from a stream.
	 *
	 * @param string $boxedName The destination file name relative to the
	 *   working directory
	 * @param StreamInterface $stream The source stream
	 * @return $this
	 */
	public function inputFileFromStream( string $boxedName, StreamInterface $stream ) {
		$boxedName = $this->normalizeBoxedPath( $boxedName );
		$this->inputFiles[$boxedName] = new InputFileFromStream( $stream );
		return $this;
	}

	/**
	 * Register an output file. If the command creates it, the contents will
	 * be read into memory.
	 *
	 * @param string $boxedName The expected location of the file relative to
	 *   the working directory.
	 * @return $this
	 */
	public function outputFileToString( string $boxedName ) {
		$boxedName = $this->normalizeBoxedPath( $boxedName );
		$this->outputFiles[$boxedName] = new OutputFileToString;
		return $this;
	}

	/**
	 * Register an output file. If the command creates it, the contents will
	 * be copied to a specified location.
	 *
	 * @param string $boxedName The expected location of the file relative to
	 *   the working directory.
	 * @param string $destPath The place where the file will be copied to
	 * @return $this
	 */
	public function outputFileToFile( string $boxedName, string $destPath ) {
		$boxedName = $this->normalizeBoxedPath( $boxedName );
		$this->outputFiles[$boxedName] = new OutputFileToFile( $destPath );
		return $this;
	}

	/**
	 * Register an output file. If the command creates it, the contents will be
	 * copied to a stream.
	 *
	 * @param string $boxedName The expected location of the file relative to
	 *   the working directory.
	 * @param StreamInterface $stream
	 * @return $this
	 */
	public function outputFileToStream( string $boxedName, StreamInterface $stream ) {
		$boxedName = $this->normalizeBoxedPath( $boxedName );
		$this->outputFiles[$boxedName] = new OutputFileToStream( $stream );
		return $this;
	}

	/**
	 * Register a series of expected output files identified by the pattern
	 *   <prefix>*.<extension>
	 *
	 * Each file that appears in the working directory which matches the
	 * specified pattern will be read into memory.
	 *
	 * @param string $prefix The prefix, potentially including a subdirectory
	 *   relative to the working directory.
	 * @param string $extension The file extension, not including the dot.
	 * @return $this
	 */
	public function outputGlobToString( string $prefix, string $extension ) {
		$prefix = $this->normalizeBoxedPath( $prefix );
		$this->checkExtension( $extension );
		$this->outputGlobs["$prefix.$extension"] = new OutputGlobToString( $prefix, $extension );
		return $this;
	}

	/**
	 * Register a series of expected output files identified by the pattern
	 *   <prefix>*.<extension>
	 *
	 * Each file that appears in the working directory which matches the
	 * specified pattern will be copied to the specified destination directory
	 *
	 * @param string $prefix The prefix, potentially including a subdirectory
	 *   relative to the working directory.
	 * @param string $extension The file extension, not including the dot.
	 * @param string $destDir The destination directory, which must already
	 *   exist.
	 * @return $this
	 */
	public function outputGlobToFile( string $prefix, string $extension, string $destDir ) {
		$prefix = $this->normalizeBoxedPath( $prefix );
		$this->checkExtension( $extension );
		$this->outputGlobs["$prefix.$extension"] = new OutputGlobToFile(
			$prefix, $extension, $destDir );
		return $this;
	}

	/**
	 * Execute the command
	 * @return BoxedResult
	 */
	public function execute(): BoxedResult {
		if ( !$this->executor ) {
			throw new ShellboxError( __METHOD__ .
				' cannot be called unless the executor is set' );
		}
		if ( $this->routeName === null ) {
			throw new ShellboxError( __CLASS__ . ': the route name must be set' );
		}
		return $this->executor->execute( $this );
	}

	/**
	 * Get command parameters for JSON serialization by the client.
	 *
	 * @internal
	 * @return array
	 */
	public function getClientData() {
		$inputFiles = [];
		foreach ( $this->inputFiles as $name => $file ) {
			$inputFiles[$name] = [];
		}
		$outputFiles = [];
		foreach ( $this->outputFiles as $name => $file ) {
			$outputFiles[$name] = $file->getClientData();
		}
		$outputGlobs = [];
		foreach ( $this->outputGlobs as $name => $glob ) {
			$outputGlobs[$name] = $glob->getClientData();
		}
		// phpcs:ignore Generic.WhiteSpace.LanguageConstructSpacing.IncorrectSingle
		return
			[
				'routeName' => $this->routeName,
				'inputFiles' => $inputFiles,
				'outputFiles' => $outputFiles,
				'outputGlobs' => $outputGlobs
			] + parent::getClientData();
	}

	/**
	 * Set command parameters using a data array created by getClientData()
	 *
	 * @internal
	 * @param array $data
	 */
	public function setClientData( $data ) {
		foreach ( $data as $name => $value ) {
			switch ( $name ) {
				case 'routeName':
					$this->routeName = $value;
					break;

				case 'inputFiles':
					$this->inputFiles = [];
					foreach ( $value as $fileName => $fileData ) {
						$this->inputFiles[$fileName] =
							InputFile::newFromClientData( $fileData );
					}
					break;

				case 'outputFiles':
					$this->outputFiles = [];
					foreach ( $value as $fileName => $fileData ) {
						$this->outputFiles[$fileName] =
							OutputFile::newFromClientData( $fileData );
					}
					break;

				case 'outputGlobs':
					$this->outputGlobs = [];
					foreach ( $value as $fileName => $globData ) {
						$this->outputGlobs[$fileName] =
							OutputGlob::newFromClientData( $globData );
					}
					break;
			}
		}
		parent::setClientData( $data );
	}

	/**
	 * Validate a path within the working directory for path traversal safety
	 * and cross-platform file name compliance. Under Windows, the path may
	 * contain backslashes, which will be replaced with slashes.
	 *
	 * @param string $path
	 * @return string
	 * @throws ShellboxError
	 */
	private function normalizeBoxedPath( $path ) {
		return Shellbox::normalizePath( $path );
	}

	/**
	 * Check an extension for path traversal safety and cross-platform file
	 * name compliance. Throw an exception if it is not acceptable.
	 *
	 * @param string $extension
	 * @throws ShellboxError
	 */
	private function checkExtension( $extension ) {
		if ( !preg_match( '/^[0-9a-zA-Z\-_]*$/', $extension ) ) {
			throw new ShellboxError( "invalid extension \"$extension\"" );
		}
	}

	/**
	 * Get the route name
	 *
	 * @return string|null
	 */
	public function getRouteName() {
		return $this->routeName;
	}

	/**
	 * Get InputFile objects describing the registered input files.
	 *
	 * @internal
	 * @return InputFile[]
	 */
	public function getInputFiles() {
		return $this->inputFiles;
	}

	/**
	 * Get OutputFile objects describing the registered output files.
	 *
	 * @internal
	 * @return OutputFile[]
	 */
	public function getOutputFiles() {
		return $this->outputFiles;
	}

	/**
	 * Get OutputGlob objects describing the registered output globs.
	 *
	 * @internal
	 * @return OutputGlob[]
	 */
	public function getOutputGlobs() {
		return $this->outputGlobs;
	}
}
