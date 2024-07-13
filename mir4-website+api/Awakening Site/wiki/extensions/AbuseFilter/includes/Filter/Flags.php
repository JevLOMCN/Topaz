<?php

namespace MediaWiki\Extension\AbuseFilter\Filter;

/**
 * (Mutable) value object to represent flags that can be *manually* set on a filter.
 */
class Flags {
	/** @var bool */
	private $enabled;
	/** @var bool */
	private $deleted;
	/** @var bool */
	private $hidden;
	/** @var bool */
	private $global;

	/**
	 * @param bool $enabled
	 * @param bool $deleted
	 * @param bool $hidden
	 * @param bool $global
	 */
	public function __construct( bool $enabled, bool $deleted, bool $hidden, bool $global ) {
		$this->enabled = $enabled;
		$this->deleted = $deleted;
		$this->hidden = $hidden;
		$this->global = $global;
	}

	/**
	 * @return bool
	 */
	public function getEnabled(): bool {
		return $this->enabled;
	}

	/**
	 * @param bool $enabled
	 */
	public function setEnabled( bool $enabled ): void {
		$this->enabled = $enabled;
	}

	/**
	 * @return bool
	 */
	public function getDeleted(): bool {
		return $this->deleted;
	}

	/**
	 * @param bool $deleted
	 */
	public function setDeleted( bool $deleted ): void {
		$this->deleted = $deleted;
	}

	/**
	 * @return bool
	 */
	public function getHidden(): bool {
		return $this->hidden;
	}

	/**
	 * @param bool $hidden
	 */
	public function setHidden( bool $hidden ): void {
		$this->hidden = $hidden;
	}

	/**
	 * @return bool
	 */
	public function getGlobal(): bool {
		return $this->global;
	}

	/**
	 * @param bool $global
	 */
	public function setGlobal( bool $global ): void {
		$this->global = $global;
	}
}
