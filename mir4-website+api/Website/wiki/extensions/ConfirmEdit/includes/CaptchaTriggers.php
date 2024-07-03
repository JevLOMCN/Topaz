<?php

namespace MediaWiki\Extension\ConfirmEdit;

/**
 * A class with constants of the CAPTCHA triggers built-in in ConfirmEdit. Other extensions may
 * add more possible triggers, which are not included in this class.
 */
abstract class CaptchaTriggers {
	public const EDIT = 'edit';
	public const CREATE = 'create';
	public const SENDEMAIL = 'sendemail';
	public const ADD_URL = 'addurl';
	public const CREATE_ACCOUNT = 'createaccount';
	public const BAD_LOGIN = 'badlogin';
	public const BAD_LOGIN_PER_USER = 'badloginperuser';

	public const EXT_REG_ATTRIBUTE_NAME = 'CaptchaTriggers';
}
