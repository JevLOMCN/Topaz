<?php

namespace MediaWiki\Extension\OATHAuth\Key;

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

use Base32\Base32;
use DomainException;
use EmptyBagOStuff;
use Exception;
use jakobo\HOTP\HOTP;
use MediaWiki\Extension\OATHAuth\IAuthKey;
use MediaWiki\Extension\OATHAuth\OATHAuthServices;
use MediaWiki\Extension\OATHAuth\OATHUser;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MWException;
use ObjectCache;
use Psr\Log\LoggerInterface;

/**
 * Class representing a two-factor key
 *
 * Keys can be tied to OATHUsers
 *
 * @ingroup Extensions
 */
class TOTPKey implements IAuthKey {
	/** @var int|null */
	private ?int $id;

	/** @var array Two factor binary secret */
	private $secret;

	/** @var string[] List of recovery codes */
	private $recoveryCodes = [];

	/**
	 * @return TOTPKey
	 * @throws Exception
	 */
	public static function newFromRandom() {
		$object = new self(
			null,
			Base32::encode( random_bytes( 10 ) ),
			[]
		);

		$object->regenerateScratchTokens();

		return $object;
	}

	/**
	 * @param array $data
	 * @return TOTPKey|null on invalid data
	 */
	public static function newFromArray( array $data ) {
		if ( !isset( $data['secret'] ) || !isset( $data['scratch_tokens'] ) ) {
			return null;
		}
		return new static( $data['id'] ?? null, $data['secret'], $data['scratch_tokens'] );
	}

	/**
	 * @param int|null $id the database id of this key
	 * @param string $secret
	 * @param array $recoveryCodes
	 */
	public function __construct( ?int $id, $secret, array $recoveryCodes ) {
		$this->id = $id;

		// Currently hardcoded values; might be used in the future
		$this->secret = [
			'mode' => 'hotp',
			'secret' => $secret,
			'period' => 30,
			'algorithm' => 'SHA1',
		];
		$this->recoveryCodes = array_values( $recoveryCodes );
	}

	/**
	 * @return int|null
	 */
	public function getId(): ?int {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getSecret() {
		return $this->secret['secret'];
	}

	/**
	 * @return string[]
	 */
	public function getScratchTokens() {
		return $this->recoveryCodes;
	}

	/**
	 * @param array $data
	 * @param OATHUser $user
	 * @return bool
	 * @throws MWException
	 */
	public function verify( $data, OATHUser $user ) {
		global $wgOATHAuthWindowRadius;

		$token = $data['token'];

		if ( $this->secret['mode'] !== 'hotp' ) {
			throw new DomainException( 'OATHAuth extension does not support non-HOTP tokens' );
		}

		// Prevent replay attacks
		$store = MediaWikiServices::getInstance()->getMainObjectStash();

		if ( $store instanceof EmptyBagOStuff ) {
			// Try and find some usable cache if the MainObjectStash isn't useful
			$store = ObjectCache::getLocalServerInstance( CACHE_ANYTHING );
		}

		$key = $store->makeKey( 'oathauth-totp', 'usedtokens', $user->getCentralId() );
		$lastWindow = (int)$store->get( $key );

		$results = HOTP::generateByTimeWindow(
			Base32::decode( $this->secret['secret'] ),
			$this->secret['period'],
			-$wgOATHAuthWindowRadius,
			$wgOATHAuthWindowRadius
		);

		// Remove any whitespace from the received token, which can be an intended group separator
		$token = preg_replace( '/\s+/', '', $token );

		$clientIP = $user->getUser()->getRequest()->getIP();

		$logger = $this->getLogger();

		// Check to see if the user's given token is in the list of tokens generated
		// for the time window.
		foreach ( $results as $window => $result ) {
			if ( $window > $lastWindow && hash_equals( $result->toHOTP( 6 ), $token ) ) {
				$lastWindow = $window;

				$logger->info( 'OATHAuth user {user} entered a valid OTP from {clientip}', [
					'user' => $user->getAccount(),
					'clientip' => $clientIP,
				] );

				$store->set(
					$key,
					$lastWindow,
					$this->secret['period'] * ( 1 + 2 * $wgOATHAuthWindowRadius )
				);

				return true;
			}
		}

		// See if the user is using a recovery code
		foreach ( $this->recoveryCodes as $i => $recoveryCode ) {
			if ( hash_equals( $token, $recoveryCode ) ) {
				// If we used a recovery code, remove it from the recovery code list.
				// This is saved below via OATHUserRepository::persist
				array_splice( $this->recoveryCodes, $i, 1 );

				$logger->info( 'OATHAuth user {user} used a recovery token from {clientip}', [
					'user' => $user->getAccount(),
					'clientip' => $clientIP,
				] );

				OATHAuthServices::getInstance()
					->getUserRepository()
					->updateKey( $user, $this );
				return true;
			}
		}

		return false;
	}

	public function regenerateScratchTokens() {
		$scratchTokens = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$scratchTokens[] = Base32::encode( random_bytes( 10 ) );
		}
		$this->recoveryCodes = $scratchTokens;
	}

	/**
	 * Check if a token is one of the recovery codes for this two-factor key.
	 *
	 * @param string $token Token to verify
	 *
	 * @return bool true if this is a recovery code.
	 */
	public function isScratchToken( $token ) {
		$token = preg_replace( '/\s+/', '', $token );
		return in_array( $token, $this->recoveryCodes, true );
	}

	/**
	 * @return LoggerInterface
	 */
	private function getLogger() {
		return LoggerFactory::getInstance( 'authentication' );
	}

	public function jsonSerialize(): array {
		return [
			'secret' => $this->getSecret(),
			'scratch_tokens' => $this->getScratchTokens()
		];
	}
}
