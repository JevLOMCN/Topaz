<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * DOMException
 *
 * @see https://dom.spec.whatwg.org/#interface-domexception
 *
 * @property string $name
 * @property string $message
 * @property int $code
 * @phan-forbid-undeclared-magic-properties
 */
interface DOMException {

	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @return string
	 */
	public function getMessage();

	/**
	 * @return int
	 */
	public function getCode();

	/** @var int */
	public const INDEX_SIZE_ERR = 1;

	/** @var int */
	public const DOMSTRING_SIZE_ERR = 2;

	/** @var int */
	public const HIERARCHY_REQUEST_ERR = 3;

	/** @var int */
	public const WRONG_DOCUMENT_ERR = 4;

	/** @var int */
	public const INVALID_CHARACTER_ERR = 5;

	/** @var int */
	public const NO_DATA_ALLOWED_ERR = 6;

	/** @var int */
	public const NO_MODIFICATION_ALLOWED_ERR = 7;

	/** @var int */
	public const NOT_FOUND_ERR = 8;

	/** @var int */
	public const NOT_SUPPORTED_ERR = 9;

	/** @var int */
	public const INUSE_ATTRIBUTE_ERR = 10;

	/** @var int */
	public const INVALID_STATE_ERR = 11;

	/** @var int */
	public const SYNTAX_ERR = 12;

	/** @var int */
	public const INVALID_MODIFICATION_ERR = 13;

	/** @var int */
	public const NAMESPACE_ERR = 14;

	/** @var int */
	public const INVALID_ACCESS_ERR = 15;

	/** @var int */
	public const VALIDATION_ERR = 16;

	/** @var int */
	public const TYPE_MISMATCH_ERR = 17;

	/** @var int */
	public const SECURITY_ERR = 18;

	/** @var int */
	public const NETWORK_ERR = 19;

	/** @var int */
	public const ABORT_ERR = 20;

	/** @var int */
	public const URL_MISMATCH_ERR = 21;

	/** @var int */
	public const QUOTA_EXCEEDED_ERR = 22;

	/** @var int */
	public const TIMEOUT_ERR = 23;

	/** @var int */
	public const INVALID_NODE_TYPE_ERR = 24;

	/** @var int */
	public const DATA_CLONE_ERR = 25;

}
