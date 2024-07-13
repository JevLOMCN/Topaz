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

namespace MediaWiki\Extension\OATHAuth\Api\Module;

use ApiBase;
use ApiResult;
use FormatJson;
use MediaWiki\Extension\OATHAuth\IModule;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Validate an OATH token.
 *
 * @ingroup API
 * @ingroup Extensions
 */
class ApiOATHValidate extends ApiBase {
	public function execute() {
		$this->requirePostedParameters( [ 'token', 'data' ] );
		// messages used: right-oathauth-api-all, action-oathauth-api-all,
		$this->checkUserRightsAny( 'oathauth-api-all' );

		$params = $this->extractRequestParams();
		if ( $params['user'] === null ) {
			$user = $this->getUser();
		} else {
			$user = MediaWikiServices::getInstance()->getUserFactory()
				->newFromName( $params['user'] );
			if ( $user === null ) {
				$this->dieWithError( 'noname' );
			}
		}

		// Don't increase pingLimiter, just check for limit exceeded.
		if ( $user->pingLimiter( 'badoath', 0 ) ) {
			$this->dieWithError( 'apierror-ratelimited' );
		}

		$result = [
			ApiResult::META_BC_BOOLS => [ 'enabled', 'valid' ],
			'enabled' => false,
			'valid' => false,
		];

		if ( $user->isNamed() ) {
			$userRepo = MediaWikiServices::getInstance()->getService( 'OATHUserRepository' );
			$authUser = $userRepo->findByUser( $user );
			if ( $authUser ) {
				$module = $authUser->getModule();
				if ( $module instanceof IModule ) {
					$data = [];
					$decoded = FormatJson::decode( $params['data'], true );
					if ( is_array( $decoded ) ) {
						$data = $decoded;
					}

					$result['enabled'] = $module->isEnabled( $authUser );
					$result['valid'] = $module->verify( $authUser, $data ) !== false;

					if ( !$result['valid'] ) {
						// Increase rate limit counter for failed request
						$user->pingLimiter( 'badoath' );

						LoggerFactory::getInstance( 'authentication' )->info(
							'OATHAuth user {user} failed OTP token/recovery code from {clientip}',
							[
								'user'     => $user,
								'clientip' => $user->getRequest()->getIP(),
							]
						);
					}
				}
			}
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	/** @inheritDoc */
	public function isInternal() {
		return true;
	}

	/** @inheritDoc */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'user' => [
				ParamValidator::PARAM_TYPE => 'user',
			],
			'data' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}

	/**
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=oathvalidate&data={"token":"123456"}&token=123ABC'
				=> 'apihelp-oathvalidate-example-1',
			'action=oathvalidate&user=Example&data={"token":"123456"}&token=123ABC'
				=> 'apihelp-oathvalidate-example-3',
		];
	}
}
