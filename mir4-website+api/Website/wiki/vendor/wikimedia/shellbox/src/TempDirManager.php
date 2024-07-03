<?php

namespace Shellbox;

use DirectoryIterator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Manager for a temporary directory which is lazily created, with lazily
 * created subdirectories underneath, and some path traversal protection to
 * make sure files stay inside the directory. All files within the directory
 * are deleted when teardown() is called or when the object is destroyed.
 */
class TempDirManager {
	/** @var string */
	private $path;
	/** @var bool */
	private $baseSetupDone = false;
	/** @var bool[] */
	private $subdirSetupDone = [];
	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param string $path
	 */
	public function __construct( $path ) {
		$this->path = $path;
		$this->logger = new NullLogger;
	}

	public function __destruct() {
		$this->teardown();
	}

	/**
	 * Set the logger.
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Destroy the base directory and all files within it
	 */
	public function teardown() {
		if ( $this->baseSetupDone ) {
			$this->deleteDirectory( $this->path );
			$this->baseSetupDone = false;
			$this->subdirSetupDone = [];
		}
	}

	/**
	 * Recursively delete a specified directory. Note that this may fail in
	 * adversarial situations. For example, a subdirectory with mode 000 cannot
	 * be read and so files within it cannot be unlinked.
	 *
	 * @param string $path
	 */
	private function deleteDirectory( $path ) {
		foreach ( new DirectoryIterator( $path ) as $fileInfo ) {
			if ( $fileInfo->isDot() ) {
				continue;
			}
			if ( $fileInfo->isDir() ) {
				$this->deleteDirectory( "$path/$fileInfo" );
			} else {
				// phpcs:ignore Generic.PHP.NoSilencedErrors
				if ( !@unlink( "$path/$fileInfo" ) ) {
					$this->logger->warning( "Unable to remove file \"$path/$fileInfo\"" );
				} else {
					$this->logger->debug( "Removed file \"$path/$fileInfo\"" );
				}
			}
		}
		// phpcs:ignore Generic.PHP.NoSilencedErrors
		if ( !@rmdir( $path ) ) {
			$this->logger->warning( "Unable to remove directory \"$path\"" );
		} else {
			$this->logger->debug( "Removed directory \"$path\"" );
		}
	}

	/**
	 * Create directories necessary to make sure a relative path exists,
	 * and return the absolute path.
	 *
	 * @param string $name
	 * @return string
	 * @throws ShellboxError
	 */
	public function preparePath( $name ) {
		$this->checkTraversal( $name );
		$this->setupBase();
		$dir = '';
		$components = explode( '/', $name );
		for ( $i = 0; $i < count( $components ) - 1; $i++ ) {
			$component = $components[$i];
			$dir .= $component;
			$this->setupSubdirectory( $dir );
		}
		return "{$this->path}/$name";
	}

	/**
	 * Make sure the specified filename is acceptable. Throw an exception if it
	 * is not.
	 *
	 * @param string $name
	 * @throws ShellboxError
	 */
	private function checkTraversal( $name ) {
		// Backslashes should have been normalized to slashes
		if ( strlen( $name ) === 0
			|| strcspn( $name, "\0\\" ) !== strlen( $name )
		) {
			throw new ShellboxError( "Invalid file name: \"$name\"" );
		}

		foreach ( explode( '/', $name ) as $component ) {
			if ( $component === '' || $component === '.' || $component === '..' ) {
				throw new ShellboxError( "Invalid path traversal: \"$name\"" );
			}
		}
	}

	/**
	 * Get the base path. Create it if it doesn't exist.
	 *
	 * @return string
	 */
	public function prepareBasePath() {
		$this->setupBase();
		return $this->path;
	}

	/**
	 * Convert a relative path to an absolute path, but don't create any
	 * directories. This can be used before attempting to read a file.
	 *
	 * @param string $name
	 * @return string
	 */
	public function getPath( $name ) {
		$this->checkTraversal( $name );
		return "{$this->path}/$name";
	}

	/**
	 * Create the base directory if we haven't done that already.
	 * Note that this will throw if the directory already exists, to prevent
	 * another process from attacking Shellbox by creating the subdirectory in
	 * advance.
	 *
	 * @throws ShellboxError
	 */
	private function setupBase() {
		if ( !$this->baseSetupDone ) {
			$this->logger->debug( "Creating base path {$this->path}" );
			FileUtils::mkdir( $this->path );
			$this->baseSetupDone = true;
		}
	}

	/**
	 * Create a subdirectory if it hasn't already been created.
	 *
	 * @param string $subdir The relative path
	 * @throws ShellboxError
	 */
	private function setupSubdirectory( $subdir ) {
		if ( !isset( $this->subdirSetupDone[$subdir] ) ) {
			$this->setupBase();
			$this->logger->debug( "Creating subdirectory $subdir" );
			FileUtils::mkdir( "{$this->path}/$subdir" );
			$this->subdirSetupDone[$subdir] = true;
		}
	}
}
