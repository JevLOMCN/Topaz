<?php

namespace MediaWiki\Extension\OATHAuth;

use JsonSerializable;
use stdClass;

interface IAuthKey extends JsonSerializable {

	/**
	 * @return int|null the ID of this key in the oathauth_devices table, or null if this key has not been saved yet
	 */
	public function getId(): ?int;

	/**
	 * @param array|stdClass $data
	 * @param OATHUser $user
	 * @return bool
	 */
	public function verify( $data, OATHUser $user );
}
