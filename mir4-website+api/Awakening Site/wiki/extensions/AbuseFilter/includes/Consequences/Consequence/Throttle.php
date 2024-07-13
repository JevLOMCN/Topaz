<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences\Consequence;

use BagOStuff;
use InvalidArgumentException;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequenceNotPrecheckedException;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Title\Title;
use MediaWiki\User\UserEditTracker;
use MediaWiki\User\UserFactory;
use Psr\Log\LoggerInterface;
use Wikimedia\IPUtils;

/**
 * Consequence that delays executing other actions until certain conditions are met
 */
class Throttle extends Consequence implements ConsequencesDisablerConsequence {
	/** @var array */
	private $throttleParams;
	/** @var BagOStuff */
	private $mainStash;
	/** @var UserEditTracker */
	private $userEditTracker;
	/** @var UserFactory */
	private $userFactory;
	/** @var LoggerInterface */
	private $logger;
	/** @var $bool */
	private $filterIsCentral;
	/** @var string|null */
	private $centralDB;

	/** @var bool|null */
	private $hitThrottle;

	private const IPV4_RANGE = '16';
	private const IPV6_RANGE = '64';

	/**
	 * @param Parameters $parameters
	 * @param array $throttleParams
	 * @phan-param array{groups:string[],id:int|string,count:int,period:int} $throttleParams
	 * @param BagOStuff $mainStash
	 * @param UserEditTracker $userEditTracker
	 * @param UserFactory $userFactory
	 * @param LoggerInterface $logger
	 * @param bool $filterIsCentral
	 * @param string|null $centralDB
	 */
	public function __construct(
		Parameters $parameters,
		array $throttleParams,
		BagOStuff $mainStash,
		UserEditTracker $userEditTracker,
		UserFactory $userFactory,
		LoggerInterface $logger,
		bool $filterIsCentral,
		?string $centralDB
	) {
		parent::__construct( $parameters );
		$this->throttleParams = $throttleParams;
		$this->mainStash = $mainStash;
		$this->userEditTracker = $userEditTracker;
		$this->userFactory = $userFactory;
		$this->logger = $logger;
		$this->filterIsCentral = $filterIsCentral;
		$this->centralDB = $centralDB;
	}

	/**
	 * @return bool Whether the throttling took place (i.e. the limit was NOT hit)
	 * @throws ConsequenceNotPrecheckedException
	 */
	public function execute(): bool {
		if ( $this->hitThrottle === null ) {
			throw new ConsequenceNotPrecheckedException();
		}
		foreach ( $this->throttleParams['groups'] as $throttleType ) {
			$this->setThrottled( $throttleType );
		}
		return !$this->hitThrottle;
	}

	/**
	 * @inheritDoc
	 */
	public function shouldDisableOtherConsequences(): bool {
		$this->hitThrottle = false;
		foreach ( $this->throttleParams['groups'] as $throttleType ) {
			$this->hitThrottle = $this->isThrottled( $throttleType ) || $this->hitThrottle;
		}
		return !$this->hitThrottle;
	}

	/**
	 * @inheritDoc
	 */
	public function getSort(): int {
		return 0;
	}

	/**
	 * Determines whether the throttle has been hit with the given parameters
	 * @note If caching is disabled, get() will return false, so the throttle count will never be reached (if >0).
	 *  This means that filters with 'throttle' enabled won't ever trigger any consequence.
	 *
	 * @param string $types
	 * @return bool
	 */
	private function isThrottled( string $types ): bool {
		$key = $this->throttleKey( $types );
		$newCount = (int)$this->mainStash->get( $key ) + 1;

		$this->logger->debug(
			'New value is {newCount} for throttle key {key}. Maximum is {rateCount}.',
			[
				'newCount' => $newCount,
				'key' => $key,
				'rateCount' => $this->throttleParams['count'],
			]
		);

		return $newCount > $this->throttleParams['count'];
	}

	/**
	 * Updates the throttle status with the given parameters
	 *
	 * @param string $types
	 */
	private function setThrottled( string $types ): void {
		$key = $this->throttleKey( $types );
		$this->logger->debug(
			'Increasing throttle key {key}',
			[ 'key' => $key ]
		);
		$this->mainStash->incrWithInit( $key, $this->throttleParams['period'] );
	}

	/**
	 * @param string $type
	 * @return string
	 */
	private function throttleKey( string $type ): string {
		$types = explode( ',', $type );

		$identifiers = [];

		foreach ( $types as $subtype ) {
			$identifiers[] = $this->throttleIdentifier( $subtype );
		}

		$identifier = sha1( implode( ':', $identifiers ) );

		if ( $this->parameters->getIsGlobalFilter() && !$this->filterIsCentral ) {
			return $this->mainStash->makeGlobalKey(
				'abusefilter', 'throttle', $this->centralDB, $this->throttleParams['id'], $identifier
			);
		}

		return $this->mainStash->makeKey( 'abusefilter', 'throttle', $this->throttleParams['id'], $identifier );
	}

	/**
	 * @param string $type
	 * @return string
	 */
	private function throttleIdentifier( string $type ): string {
		$user = $this->parameters->getUser();
		switch ( $type ) {
			case 'ip':
				$identifier = $this->parameters->getActionSpecifier()->getIP();
				break;
			case 'user':
				// NOTE: This is always 0 for anons. Is this good/wanted?
				$identifier = $user->getId();
				break;
			case 'range':
				$requestIP = $this->parameters->getActionSpecifier()->getIP();
				$range = IPUtils::isIPv6( $requestIP ) ? self::IPV6_RANGE : self::IPV4_RANGE;
				$identifier = IPUtils::sanitizeRange( "{$requestIP}/$range" );
				break;
			case 'creationdate':
				// TODO Inject a proper service, not UserFactory, once getRegistration is moved away from User
				$reg = (int)$this->userFactory->newFromUserIdentity( $user )->getRegistration();
				$identifier = $reg - ( $reg % 86400 );
				break;
			case 'editcount':
				// Hack for detecting different single-purpose accounts.
				$identifier = $this->userEditTracker->getUserEditCount( $user ) ?: 0;
				break;
			case 'site':
				$identifier = 1;
				break;
			case 'page':
				$title = Title::castFromLinkTarget( $this->parameters->getTarget() );
				// TODO Replace with something from LinkTarget, e.g. namespace + text
				$identifier = $title->getPrefixedText();
				break;
			default:
				// Should never happen
				throw new InvalidArgumentException( "Invalid throttle type $type." );
		}

		return "$type-$identifier";
	}
}
