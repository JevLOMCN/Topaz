<?php

namespace Shellbox\Command;

/**
 * The base class for output file glob patterns
 *
 * @internal
 */
abstract class OutputGlob extends OutputEntity {
	/** @var string */
	protected $prefix;
	/** @var string */
	protected $extension;
	/** @var OutputFile[] */
	protected $files = [];

	/**
	 * @param string $prefix
	 * @param string $extension
	 */
	public function __construct( $prefix, $extension ) {
		$this->prefix = $prefix;
		$this->extension = $extension;
	}

	/**
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * @return string
	 */
	public function getExtension() {
		return $this->extension;
	}

	/**
	 * Get JSON serializable data for client/server communication
	 *
	 * @return array
	 */
	public function getClientData() {
		return [
			'prefix' => $this->prefix,
			'extension' => $this->extension
		];
	}

	/**
	 * This is used on the server side to create a placeholder object for globs
	 * based on a specification received from the client. Because the content is
	 * never actually populated, it's not necessary to distinguish between the
	 * different glob types.
	 *
	 * @param array $data
	 * @return OutputGlobPlaceholder
	 */
	public static function newFromClientData( array $data ) {
		return new OutputGlobPlaceholder( $data['prefix'], $data['extension'] );
	}

	/**
	 * @return OutputFile[]
	 */
	public function getFiles() {
		return $this->files;
	}

	/**
	 * Determine whether a given relative path matches the glob pattern
	 *
	 * @param string $boxedName
	 * @return bool
	 */
	public function isMatch( $boxedName ) {
		$ext = '.' . $this->extension;
		return substr_compare( $boxedName, $this->prefix, 0, strlen( $this->prefix ) ) === 0
			&& substr_compare( $boxedName, $ext, -strlen( $ext ) ) === 0;
	}
}
