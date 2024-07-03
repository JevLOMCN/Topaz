<?php

namespace MediaWiki\Extension\ConfirmEdit;

/**
 * Simple value object for storing a captcha question + answer.
 */
class CaptchaValue {
	/**
	 * ID that is used to store the captcha in cache.
	 * @var string
	 */
	protected $id;

	/**
	 * Answer to the captcha.
	 * @var string
	 */
	protected $solution;

	/**
	 * @var mixed
	 */
	protected $data;

}
