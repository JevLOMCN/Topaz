<?php

namespace MediaWiki\Extension\AbuseFilter\Parser\Exception;

use Message;

/**
 * Exceptions that we might conceivably want to report to ordinary users
 * (i.e. exceptions that don't represent bugs in the extension itself)
 */
class UserVisibleException extends ExceptionBase {
	/** @var string */
	public $mExceptionID;
	/** @var int */
	protected $mPosition;
	/** @var array */
	protected $mParams;

	/**
	 * @param string $exception_id
	 * @param int $position
	 * @param array $params
	 */
	public function __construct( $exception_id, $position, $params ) {
		$this->mExceptionID = $exception_id;
		$this->mPosition = $position;
		$this->mParams = $params;

		parent::__construct( $exception_id );
	}

	/**
	 * @return int
	 */
	public function getPosition(): int {
		return $this->mPosition;
	}

	/**
	 * Returns the error message for use in logs
	 *
	 * @return string
	 */
	public function getMessageForLogs(): string {
		return "ID: {$this->mExceptionID}; position: {$this->mPosition}; params: " . implode( ', ', $this->mParams );
	}

	/**
	 * @return Message
	 */
	public function getMessageObj(): Message {
		// Give grep a chance to find the usages:
		// abusefilter-exception-unexpectedatend, abusefilter-exception-expectednotfound
		// abusefilter-exception-unrecognisedkeyword, abusefilter-exception-unexpectedtoken
		// abusefilter-exception-unclosedstring, abusefilter-exception-invalidoperator
		// abusefilter-exception-unrecognisedtoken, abusefilter-exception-noparams
		// abusefilter-exception-dividebyzero, abusefilter-exception-unrecognisedvar
		// abusefilter-exception-notenoughargs, abusefilter-exception-regexfailure
		// abusefilter-exception-overridebuiltin, abusefilter-exception-outofbounds
		// abusefilter-exception-notarray, abusefilter-exception-unclosedcomment
		// abusefilter-exception-invalidiprange, abusefilter-exception-disabledvar
		// abusefilter-exception-variablevariable, abusefilter-exception-toomanyargs
		// abusefilter-exception-negativeoffset, abusefilter-exception-unusedvars
		// abusefilter-exception-unknownfunction, abusefilter-exception-usebuiltin
		return new Message(
			'abusefilter-exception-' . $this->mExceptionID,
			array_merge( [ $this->mPosition ], $this->mParams )
		);
	}

	/**
	 * Serialize data for edit stash
	 * @return array
	 */
	public function toArray(): array {
		return [
			'class' => static::class,
			'exceptionID' => $this->mExceptionID,
			'position' => $this->mPosition,
			'params' => $this->mParams,
		];
	}

	/**
	 * Deserialize data from edit stash
	 * @param array $value
	 * @return static
	 */
	public static function fromArray( array $value ) {
		$cls = $value['class'];
		return new $cls( $value['exceptionID'], $value['position'], $value['params'] );
	}

}
