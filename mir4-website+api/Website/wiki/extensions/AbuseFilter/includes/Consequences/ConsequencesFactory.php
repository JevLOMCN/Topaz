<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences;

use BagOStuff;
use MediaWiki\Block\BlockUserFactory;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Block\DatabaseBlockStore;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\BlockAutopromoteStore;
use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagger;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Block;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\BlockAutopromote;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Degroup;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Disallow;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\RangeBlock;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Tag;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Throttle;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Warn;
use MediaWiki\Extension\AbuseFilter\FilterUser;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Session\Session;
use MediaWiki\Session\SessionManager;
use MediaWiki\User\UserEditTracker;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserIdentityUtils;
use MessageLocalizer;
use Psr\Log\LoggerInterface;

class ConsequencesFactory {
	public const SERVICE_NAME = 'AbuseFilterConsequencesFactory';

	public const CONSTRUCTOR_OPTIONS = [
		'AbuseFilterCentralDB',
		'AbuseFilterIsCentral',
		'AbuseFilterRangeBlockSize',
		'BlockCIDRLimit',
	];

	/** @var ServiceOptions */
	private $options;

	/** @var LoggerInterface */
	private $logger;

	/** @var BlockUserFactory */
	private $blockUserFactory;

	/** @var DatabaseBlockStore */
	private $databaseBlockStore;

	/** @var UserGroupManager */
	private $userGroupManager;

	/** @var BagOStuff */
	private $mainStash;

	/** @var ChangeTagger */
	private $changeTagger;

	/** @var BlockAutopromoteStore */
	private $blockAutopromoteStore;

	/** @var FilterUser */
	private $filterUser;

	/** @var MessageLocalizer */
	private $messageLocalizer;

	/** @var UserEditTracker */
	private $userEditTracker;

	/** @var UserFactory */
	private $userFactory;

	/** @var UserIdentityUtils */
	private $userIdentityUtils;

	/** @var ?Session */
	private $session;

	/**
	 * @todo This might drag in unwanted dependencies. The alternative is to use ObjectFactory, but that's harder
	 *   to understand for humans and static analysis tools, so do that only if the dependencies list starts growing.
	 * @param ServiceOptions $options
	 * @param LoggerInterface $logger
	 * @param BlockUserFactory $blockUserFactory
	 * @param DatabaseBlockStore $databaseBlockStore
	 * @param UserGroupManager $userGroupManager
	 * @param BagOStuff $mainStash
	 * @param ChangeTagger $changeTagger
	 * @param BlockAutopromoteStore $blockAutopromoteStore
	 * @param FilterUser $filterUser
	 * @param MessageLocalizer $messageLocalizer
	 * @param UserEditTracker $userEditTracker
	 * @param UserFactory $userFactory
	 * @param UserIdentityUtils $userIdentityUtils
	 */
	public function __construct(
		ServiceOptions $options,
		LoggerInterface $logger,
		BlockUserFactory $blockUserFactory,
		DatabaseBlockStore $databaseBlockStore,
		UserGroupManager $userGroupManager,
		BagOStuff $mainStash,
		ChangeTagger $changeTagger,
		BlockAutopromoteStore $blockAutopromoteStore,
		FilterUser $filterUser,
		MessageLocalizer $messageLocalizer,
		UserEditTracker $userEditTracker,
		UserFactory $userFactory,
		UserIdentityUtils $userIdentityUtils
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->logger = $logger;
		$this->blockUserFactory = $blockUserFactory;
		$this->databaseBlockStore = $databaseBlockStore;
		$this->userGroupManager = $userGroupManager;
		$this->mainStash = $mainStash;
		$this->changeTagger = $changeTagger;
		$this->blockAutopromoteStore = $blockAutopromoteStore;
		$this->filterUser = $filterUser;
		$this->messageLocalizer = $messageLocalizer;
		$this->userEditTracker = $userEditTracker;
		$this->userFactory = $userFactory;
		$this->userIdentityUtils = $userIdentityUtils;
	}

	// Each class has its factory method for better type inference and static analysis

	/**
	 * @param Parameters $params
	 * @param string $expiry
	 * @param bool $preventsTalk
	 * @return Block
	 */
	public function newBlock( Parameters $params, string $expiry, bool $preventsTalk ): Block {
		return new Block(
			$params,
			$expiry,
			$preventsTalk,
			$this->blockUserFactory,
			$this->databaseBlockStore,
			// FIXME This is a hack until DI is possible here (T255433).
			[ DatabaseBlock::class, 'newFromTarget' ],
			$this->filterUser,
			$this->messageLocalizer,
			$this->logger
		);
	}

	/**
	 * @param Parameters $params
	 * @param string $expiry
	 * @return RangeBlock
	 */
	public function newRangeBlock( Parameters $params, string $expiry ): RangeBlock {
		return new RangeBlock(
			$params,
			$expiry,
			$this->blockUserFactory,
			$this->filterUser,
			$this->messageLocalizer,
			$this->logger,
			$this->options->get( 'AbuseFilterRangeBlockSize' ),
			$this->options->get( 'BlockCIDRLimit' )
		);
	}

	/**
	 * @param Parameters $params
	 * @param VariableHolder $vars
	 * @return Degroup
	 */
	public function newDegroup( Parameters $params, VariableHolder $vars ): Degroup {
		return new Degroup(
			$params,
			$vars,
			$this->userGroupManager,
			$this->userIdentityUtils,
			$this->filterUser,
			$this->messageLocalizer
		);
	}

	/**
	 * @param Parameters $params
	 * @param int $duration
	 * @return BlockAutopromote
	 */
	public function newBlockAutopromote( Parameters $params, int $duration ): BlockAutopromote {
		return new BlockAutopromote( $params, $duration, $this->blockAutopromoteStore, $this->messageLocalizer,
			$this->userIdentityUtils );
	}

	/**
	 * @param Parameters $params
	 * @param array $throttleParams
	 * @phan-param array{id:int|string,count:int,period:int,groups:string[]} $throttleParams
	 * @return Throttle
	 */
	public function newThrottle( Parameters $params, array $throttleParams ): Throttle {
		return new Throttle(
			$params,
			$throttleParams,
			$this->mainStash,
			$this->userEditTracker,
			$this->userFactory,
			$this->logger,
			$this->options->get( 'AbuseFilterIsCentral' ),
			$this->options->get( 'AbuseFilterCentralDB' )
		);
	}

	/**
	 * @param Parameters $params
	 * @param string $message
	 * @return Warn
	 */
	public function newWarn( Parameters $params, string $message ): Warn {
		return new Warn( $params, $message, $this->getSession() );
	}

	/**
	 * @param Parameters $params
	 * @param string $message
	 * @return Disallow
	 */
	public function newDisallow( Parameters $params, string $message ): Disallow {
		return new Disallow( $params, $message );
	}

	/**
	 * @param Parameters $params
	 * @param string[] $tags
	 * @return Tag
	 */
	public function newTag( Parameters $params, array $tags ): Tag {
		return new Tag( $params, $tags, $this->changeTagger );
	}

	/**
	 * @param Session $session
	 * @return void
	 */
	public function setSession( Session $session ): void {
		$this->session = $session;
	}

	/**
	 * @return Session
	 */
	private function getSession(): Session {
		if ( $this->session === null ) {
			$this->session = SessionManager::getGlobalSession();
		}

		return $this->session;
	}
}
