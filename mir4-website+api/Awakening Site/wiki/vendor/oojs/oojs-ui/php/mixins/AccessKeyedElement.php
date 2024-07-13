<?php

namespace OOUI;

/**
 * Element with an access key.
 *
 * Access keys allow an user to go to a specific element by using
 * a shortcut combination of a browser specific keys + the key
 * set to the field.
 *
 * @abstract
 */
trait AccessKeyedElement {

	/**
	 * Access key
	 *
	 * @var ?string
	 */
	protected $accessKey = null;

	/**
	 * @var Tag
	 */
	protected $accessKeyed;

	/**
	 * @param array $config Configuration options
	 *      - string $config['accessKey'] Access key. If not provided, no access key will be added
	 */
	public function initializeAccessKeyedElement( array $config = [] ) {
		// Properties
		$this->accessKeyed = $config['accessKeyed'] ?? $this;

		// Initialization
		$this->setAccessKey( $config['accessKey'] ?? null );
		$this->registerConfigCallback( function ( &$config ) {
			if ( $this->accessKey !== null ) {
				$config['accessKey'] = $this->accessKey;
			}
		} );
	}

	/**
	 * Set access key.
	 *
	 * @param string $accessKey Tag's access key, use empty string to remove
	 * @return $this
	 */
	public function setAccessKey( $accessKey ) {
		$accessKey = is_string( $accessKey ) && strlen( $accessKey ) ? $accessKey : null;

		if ( $this->accessKey !== $accessKey ) {
			if ( $accessKey !== null ) {
				$this->accessKeyed->setAttributes( [ 'accesskey' => $accessKey ] );
			} else {
				$this->accessKeyed->removeAttributes( [ 'accesskey' ] );
			}
			$this->accessKey = $accessKey;

			// Only if this is a TitledElement
			if ( method_exists( $this, 'updateTitle' ) ) {
				// @phan-suppress-next-line PhanUndeclaredMethod
				$this->updateTitle();
			}
		}

		return $this;
	}

	/**
	 * Get access key.
	 *
	 * @return string Access key string
	 */
	public function getAccessKey() {
		return $this->accessKey;
	}

	/**
	 * Add information about the access key to the element's tooltip label.
	 * (This is only public for hacky usage in FieldLayout.)
	 *
	 * @param string $title Tooltip label for `title` attribute
	 * @return string
	 */
	public function formatTitleWithAccessKey( $title ) {
		$accessKey = $this->getAccessKey();
		if ( $accessKey ) {
			$title .= " [$accessKey]";
		}
		return $title;
	}

	/**
	 * @param callable $func
	 */
	abstract public function registerConfigCallback( callable $func );
}
