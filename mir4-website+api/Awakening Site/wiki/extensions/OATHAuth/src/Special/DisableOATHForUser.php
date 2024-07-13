<?php

namespace MediaWiki\Extension\OATHAuth\Special;

use HTMLForm;
use ManualLogEntry;
use MediaWiki\Config\ConfigException;
use MediaWiki\Extension\OATHAuth\OATHUserRepository;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use Message;
use MWException;
use UserBlockedError;
use UserNotLoggedIn;

class DisableOATHForUser extends FormSpecialPage {

	private OATHUserRepository $userRepo;

	private UserFactory $userFactory;

	/**
	 * @param OATHUserRepository $userRepo
	 * @param UserFactory $userFactory
	 */
	public function __construct( $userRepo, $userFactory ) {
		// messages used: disableoathforuser (display "name" on Special:SpecialPages),
		// right-oathauth-disable-for-user, action-oathauth-disable-for-user
		parent::__construct( 'DisableOATHForUser', 'oathauth-disable-for-user' );

		$this->userRepo = $userRepo;
		$this->userFactory = $userFactory;
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'users';
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @return string
	 */
	protected function getLoginSecurityLevel() {
		return $this->getName();
	}

	/**
	 * Set the page title and add JavaScript RL modules
	 *
	 * @param HTMLForm $form
	 */
	public function alterForm( HTMLForm $form ) {
		$form->setMessagePrefix( 'oathauth' );
		$form->setWrapperLegendMsg( 'oathauth-disable-for-user' );
		$form->setPreHtml( $this->msg( 'oathauth-disable-intro' )->parse() );
		$form->getOutput()->setPageTitleMsg( $this->msg( 'oathauth-disable-for-user' ) );
	}

	/**
	 * @return string
	 */
	protected function getDisplayFormat() {
		return 'ooui';
	}

	/**
	 * @return bool
	 */
	public function requiresUnblock() {
		return false;
	}

	/**
	 * @param User $user
	 * @throws UserBlockedError
	 * @throws UserNotLoggedIn
	 */
	protected function checkExecutePermissions( User $user ) {
		parent::checkExecutePermissions( $user );

		$this->requireLogin();
	}

	/**
	 * @param string $par
	 */
	public function execute( $par ) {
		$this->getOutput()->disallowUserJs();
		parent::execute( $par );
	}

	/**
	 * @return array[]
	 */
	protected function getFormFields() {
		return [
			'user' => [
				'type' => 'user',
				'default' => '',
				'label-message' => 'oathauth-enteruser',
				'name' => 'user',
				'required' => true,
			],
			'reason' => [
				'type' => 'text',
				'default' => '',
				'label-message' => 'oathauth-enterdisablereason',
				'name' => 'reason',
				'required' => true,
			],
		];
	}

	/**
	 * @param array $formData
	 * @return array|bool
	 * @throws ConfigException
	 * @throws MWException
	 */
	public function onSubmit( array $formData ) {
		$user = $this->userFactory->newFromName( $formData['user'] );
		if ( !$user || ( $user->getId() === 0 ) ) {
			return [ 'oathauth-user-not-found' ];
		}

		$oathUser = $this->userRepo->findByUser( $user );

		if ( !$oathUser->isTwoFactorAuthEnabled() ) {
			return [ 'oathauth-user-not-does-not-have-oath-enabled' ];
		}

		if ( $this->getUser()->pingLimiter( 'disableoath', 0 ) ) {
			// Arbitrary duration given here
			return [ 'oathauth-throttled', Message::durationParam( 60 ) ];
		}

		$this->userRepo->removeAll( $oathUser, $this->getRequest()->getIP(), false );

		// messages used: logentry-oath-disable-other, log-action-oath-disable-other
		$logEntry = new ManualLogEntry( 'oath', 'disable-other' );
		$logEntry->setPerformer( $this->getUser() );
		$logEntry->setTarget( $user->getUserPage() );
		$logEntry->setComment( $formData['reason'] );
		$logEntry->insert();

		LoggerFactory::getInstance( 'authentication' )->info(
			'OATHAuth disabled for {usertarget} by {user} from {clientip}', [
				'user' => $this->getUser()->getName(),
				'usertarget' => $formData['user'],
				'clientip' => $this->getRequest()->getIP(),
			]
		);

		return true;
	}

	public function onSuccess() {
		$this->getOutput()->addWikiMsg( 'oathauth-disabledoath' );
		$this->getOutput()->returnToMain();
	}

}
