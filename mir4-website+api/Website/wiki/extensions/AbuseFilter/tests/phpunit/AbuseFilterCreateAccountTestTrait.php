<?php

use MediaWiki\Extension\AbuseFilter\AbuseFilterPreAuthenticationProvider;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageReferenceValue;
use MediaWiki\User\User;

/**
 * This trait can be used to create accounts in integration tests.
 * NOTE: The implementing classes MUST extend MediaWikiIntegrationTestCase
 * @todo This might be moved to MediaWikiIntegrationTestCase
 */
trait AbuseFilterCreateAccountTestTrait {
	/**
	 * @param string $accountName
	 * @param bool $autocreate
	 * @param User|null $creator Defaults to the newly created user
	 * @return StatusValue
	 */
	protected function createAccount(
		string $accountName,
		bool $autocreate = false,
		User $creator = null
	): StatusValue {
		$userFactory = MediaWikiServices::getInstance()->getUserFactory();
		$user = $userFactory->newFromName( $accountName );
		$creator ??= $user;

		$provider = new AbuseFilterPreAuthenticationProvider(
			AbuseFilterServices::getVariableGeneratorFactory(),
			AbuseFilterServices::getFilterRunnerFactory(),
			new NullStatsdDataFactory(),
			$userFactory
		);
		if ( $autocreate ) {
			$status = $provider->testUserForCreation( $user, $autocreate );
		} else {
			$status = $provider->testForAccountCreation( $user, $creator, [] );
		}

		if ( $status->isGood() ) {
			// A creatable username must exist to be passed to $logEntry->setPerformer(),
			// so create the account.
			$user->addToDatabase();

			// FIXME This is a bit hacky, but AuthManager doesn't expose any methods for logging
			$subType = $autocreate ? 'autocreate' : 'create2';
			$logEntry = new ManualLogEntry( 'newusers', $subType );
			$logEntry->setPerformer( $creator );
			$logEntry->setTarget( PageReferenceValue::localReference( NS_USER, $accountName ) );
			$logEntry->setComment( 'Fooobarcomment' );
			$logEntry->setParameters( [
				'4::userid' => $user->getId(),
			] );
			$logid = $logEntry->insert();
			$logEntry->publish( $logid );
			$status->value = $logid;
		}
		return $status;
	}
}
