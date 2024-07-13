<?php

namespace LoginNotify;

use Job;
use LogicException;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;

/**
 * Class DeferredChecksJob
 * @package LoginNotify
 */
class DeferredChecksJob extends Job {
	public const TYPE_LOGIN_FAILED = 'failed';
	public const TYPE_LOGIN_SUCCESS = 'success';

	private $userFactory;

	/**
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( Title $title, array $params, UserFactory $userFactory ) {
		$this->userFactory = $userFactory;
		parent::__construct( 'LoginNotifyChecks', $title, $params );
	}

	/**
	 * Run the job
	 * @return bool Success
	 */
	public function run() {
		$checkType = $this->params['checkType'];
		$userId = $this->params['userId'];
		$user = $this->userFactory->newFromId( $userId );
		// The ID is marked as a loaded field by the factory. To check if the
		// user_id exists in the database, we need to explicitly load.
		$user->load();
		if ( !$user->getId() ) {
			throw new LogicException( "Can't find user for user id=" . print_r( $userId, true ) );
		}
		if ( !isset( $this->params['subnet'] ) || !is_string( $this->params['subnet'] ) ) {
			throw new LogicException( __CLASS__
				. " expected to receive a string parameter 'subnet', got "
				. print_r( $this->params['subnet'], true )
			);
		}
		$subnet = $this->params['subnet'];
		if ( !isset( $this->params['resultSoFar'] ) || !is_string( $this->params['resultSoFar'] ) ) {
			throw new LogicException( __CLASS__
				. " expected to receive a string parameter 'resultSoFar', got "
				. print_r( $this->params['resultSoFar'], true )
			);
		}
		$resultSoFar = $this->params['resultSoFar'];

		$loginNotify = LoginNotify::getInstance();

		switch ( $checkType ) {
			case self::TYPE_LOGIN_FAILED:
				$loginNotify->recordFailureDeferred( $user, $subnet, $resultSoFar );
				break;
			case self::TYPE_LOGIN_SUCCESS:
				$loginNotify->sendSuccessNoticeDeferred( $user, $subnet, $resultSoFar );
				break;
			default:
				throw new LogicException( 'Unknown check type ' . print_r( $checkType, true ) );
		}

		return true;
	}
}
