<?php

namespace MediaWiki\Extension\Thanks\Storage\Exceptions;

use Exception;

class InvalidLogType extends Exception {
	private string $logType;

	public function __construct( string $logType ) {
		parent::__construct();
		$this->logType = $logType;
	}

	public function getLogType(): string {
		return $this->logType;
	}
}
