<?php

namespace Shellbox;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

/**
 * Throwing wrappers for file functions
 */
class FileUtils {
	/**
	 * Copy file
	 *
	 * @param string $source
	 * @param string $dest
	 * @throws ShellboxError
	 */
	public static function copy( $source, $dest ) {
		if ( !copy( $source, $dest ) ) {
			throw new ShellboxError( "Error while copying " .
				basename( $source ) . ' to ' . basename( $dest ) );
		}
	}

	/**
	 * Get contents
	 *
	 * @param string $path
	 * @return string
	 * @throws ShellboxError
	 */
	public static function getContents( $path ) {
		$contents = file_get_contents( $path );
		if ( $contents === false ) {
			throw new ShellboxError( "Unable to read " . basename( $path ) );
		}
		return $contents;
	}

	/**
	 * Put contents
	 *
	 * @param string $path
	 * @param string $contents
	 * @throws ShellboxError
	 */
	public static function putContents( $path, $contents ) {
		if ( !file_put_contents( $path, $contents ) ) {
			throw new ShellboxError( "Unable to write " . basename( $path ) );
		}
	}

	/**
	 * Open a file in read mode
	 *
	 * @param string $path
	 * @return resource
	 * @throws ShellboxError
	 */
	public static function openInputFile( $path ) {
		$file = fopen( $path, 'r' );
		if ( !$file ) {
			throw new ShellboxError( "Error opening input file " . basename( $path ) );
		}
		return $file;
	}

	/**
	 * Open a file in write mode
	 *
	 * @param string $path
	 * @return resource
	 * @throws ShellboxError
	 */
	public static function openOutputFile( $path ) {
		$file = fopen( $path, 'w' );
		if ( !$file ) {
			throw new ShellboxError( "Error opening output file " . basename( $path ) );
		}
		return $file;
	}

	/**
	 * Open a file in read mode and convert it to a StreamInterface
	 *
	 * @param string $path
	 * @return StreamInterface
	 * @throws ShellboxError
	 */
	public static function openInputFileStream( $path ) {
		return Utils::streamFor( self::openInputFile( $path ) );
	}

	/**
	 * Open a file in write mode and convert it to a StreamInterface
	 *
	 * @param string $path
	 * @return StreamInterface
	 * @throws ShellboxError
	 */
	public static function openOutputFileStream( $path ) {
		return Utils::streamFor( self::openOutputFile( $path ) );
	}

	/**
	 * Make a directory with group/other permission bits masked out
	 *
	 * @param string $path
	 * @throws ShellboxError
	 */
	public static function mkdir( $path ) {
		if ( !mkdir( $path, 0700 ) ) {
			throw new ShellboxError( "Error creating directory " . basename( $path ) );
		}
	}
}
