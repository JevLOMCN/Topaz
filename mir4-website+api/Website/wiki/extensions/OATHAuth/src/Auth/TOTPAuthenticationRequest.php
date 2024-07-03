<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace MediaWiki\Extension\OATHAuth\Auth;

use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Language\RawMessage;

/**
 * AuthManager value object for the TOTP second factor of an authentication:
 * a pseudorandom token that is generated from the current time independently
 * by the server and the client.
 */
class TOTPAuthenticationRequest extends AuthenticationRequest {
	/** @var string */
	public $OATHToken;

	public function describeCredentials() {
		return [
			'provider' => wfMessage( 'oathauth-describe-provider' ),
			'account' => new RawMessage( '$1', [ $this->username ] ),
		] + parent::describeCredentials();
	}

	/**
	 * @return array
	 */
	public function getFieldInfo() {
		return [
			'OATHToken' => [
				'type' => 'string',
				'label' => wfMessage( 'oathauth-auth-token-label' ),
				'help' => wfMessage( 'oathauth-auth-token-help' )
			]
		];
	}
}
