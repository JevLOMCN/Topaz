<?php

namespace MediaWiki\Extension\AbuseFilter\Parser;

use MediaWiki\Extension\AbuseFilter\Parser\Exception\ExceptionBase;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\UserVisibleWarning;

class ParserStatus {
	/** @var ExceptionBase|null */
	protected $excep;
	/** @var UserVisibleWarning[] */
	protected $warnings;
	/** @var int */
	protected $condsUsed;

	/**
	 * @param ExceptionBase|null $excep An exception thrown while parsing, or null if it parsed correctly
	 * @param UserVisibleWarning[] $warnings
	 * @param int $condsUsed
	 */
	public function __construct(
		?ExceptionBase $excep,
		array $warnings,
		int $condsUsed
	) {
		$this->excep = $excep;
		$this->warnings = $warnings;
		$this->condsUsed = $condsUsed;
	}

	/**
	 * @return ExceptionBase|null
	 */
	public function getException(): ?ExceptionBase {
		return $this->excep;
	}

	/**
	 * @return UserVisibleWarning[]
	 */
	public function getWarnings(): array {
		return $this->warnings;
	}

	/**
	 * @return int
	 */
	public function getCondsUsed(): int {
		return $this->condsUsed;
	}

	/**
	 * Whether the parsing/evaluation happened successfully.
	 * @return bool
	 */
	public function isValid(): bool {
		return !$this->excep;
	}
}
