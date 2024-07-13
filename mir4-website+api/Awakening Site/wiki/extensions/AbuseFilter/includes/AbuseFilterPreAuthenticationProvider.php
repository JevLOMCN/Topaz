<?php

namespace MediaWiki\Extension\AbuseFilter;

use IBufferingStatsdDataFactory;
use MediaWiki\Auth\AbstractPreAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGeneratorFactory;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use StatusValue;

/**
 * AuthenticationProvider used to filter account creations. This runs after normal preauth providers
 * to keep the log cleaner.
 */
class AbuseFilterPreAuthenticationProvider extends AbstractPreAuthenticationProvider {
	/** @var VariableGeneratorFactory */
	private $variableGeneratorFactory;
	/** @var FilterRunnerFactory */
	private $filterRunnerFactory;
	/** @var IBufferingStatsdDataFactory */
	private $statsd;
	/** @var UserFactory */
	private $userFactory;

	/**
	 * @param VariableGeneratorFactory $variableGeneratorFactory
	 * @param FilterRunnerFactory $filterRunnerFactory
	 * @param IBufferingStatsdDataFactory $statsd
	 * @param UserFactory $userFactory
	 */
	public function __construct(
		VariableGeneratorFactory $variableGeneratorFactory,
		FilterRunnerFactory $filterRunnerFactory,
		IBufferingStatsdDataFactory $statsd,
		UserFactory $userFactory
	) {
		$this->variableGeneratorFactory = $variableGeneratorFactory;
		$this->filterRunnerFactory = $filterRunnerFactory;
		$this->statsd = $statsd;
		$this->userFactory = $userFactory;
	}

	/**
	 * @param User $user
	 * @param User $creator
	 * @param AuthenticationRequest[] $reqs
	 * @return StatusValue
	 */
	public function testForAccountCreation( $user, $creator, array $reqs ): StatusValue {
		return $this->testUser( $user, $creator, false );
	}

	/**
	 * @param User $user
	 * @param bool|string $autocreate
	 * @param array $options
	 * @return StatusValue
	 */
	public function testUserForCreation( $user, $autocreate, array $options = [] ): StatusValue {
		// if this is not an autocreation, testForAccountCreation already handled it
		if ( $autocreate ) {
			// Make sure to use an anon as the creator, see T272244
			return $this->testUser( $user, $this->userFactory->newAnonymous(), true );
		}
		return StatusValue::newGood();
	}

	/**
	 * @param User $user The user being created or autocreated
	 * @param User $creator The user who caused $user to be created (can be anonymous)
	 * @param bool $autocreate Is this an autocreation?
	 * @return StatusValue
	 */
	private function testUser( $user, $creator, $autocreate ): StatusValue {
		$startTime = microtime( true );
		if ( $user->getName() === wfMessage( 'abusefilter-blocker' )->inContentLanguage()->text() ) {
			return StatusValue::newFatal( 'abusefilter-accountreserved' );
		}

		$title = SpecialPage::getTitleFor( 'Userlogin' );
		$builder = $this->variableGeneratorFactory->newRunGenerator( $creator, $title );
		$vars = $builder->getAccountCreationVars( $user, $autocreate );

		// pass creator in explicitly to prevent recording the current user on autocreation - T135360
		$runner = $this->filterRunnerFactory->newRunner( $creator, $title, $vars, 'default' );
		$status = $runner->run();

		$this->statsd->timing( 'timing.createaccountAbuseFilter', microtime( true ) - $startTime );

		return $status->getStatusValue();
	}
}
