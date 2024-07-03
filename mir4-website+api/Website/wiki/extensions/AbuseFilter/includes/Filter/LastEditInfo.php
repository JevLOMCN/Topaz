<?php

namespace MediaWiki\Extension\AbuseFilter\Filter;

/**
 * (Mutable) value object that holds information about the last edit to a filter.
 */
class LastEditInfo {
	/** @var int */
	private $userID;
	/** @var string */
	private $userName;
	/** @var string */
	private $timestamp;

	/**
	 * @param int $userID
	 * @param string $userName
	 * @param string $timestamp
	 */
	public function __construct( int $userID, string $userName, string $timestamp ) {
		$this->userID = $userID;
		$this->userName = $userName;
		$this->timestamp = $timestamp;
	}

	/**
	 * @return int
	 */
	public function getUserID(): int {
		return $this->userID;
	}

	/**
	 * @param int $id
	 */
	public function setUserID( int $id ): void {
		$this->userID = $id;
	}

	/**
	 * @return string
	 */
	public function getUserName(): string {
		return $this->userName;
	}

	/**
	 * @param string $name
	 */
	public function setUserName( string $name ): void {
		$this->userName = $name;
	}

	/**
	 * @return string
	 */
	public function getTimestamp(): string {
		return $this->timestamp;
	}

	/**
	 * @param string $timestamp
	 */
	public function setTimestamp( string $timestamp ): void {
		$this->timestamp = $timestamp;
	}
}
