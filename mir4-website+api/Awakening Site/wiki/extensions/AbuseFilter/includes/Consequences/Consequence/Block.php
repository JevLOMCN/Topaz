<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences\Consequence;

use ManualLogEntry;
use MediaWiki\Block\BlockUserFactory;
use MediaWiki\Block\DatabaseBlockStore;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\AbuseFilter\FilterUser;
use MediaWiki\Extension\AbuseFilter\GlobalNameUtils;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\UserIdentity;
use MessageLocalizer;
use Psr\Log\LoggerInterface;

/**
 * Consequence that blocks a single user.
 */
class Block extends BlockingConsequence implements ReversibleConsequence {
	/** @var bool */
	private $preventsTalkEdit;
	/** @var DatabaseBlockStore */
	private $databaseBlockStore;
	/** @var callable */
	private $blockFactory;

	/**
	 * @param Parameters $params
	 * @param string $expiry
	 * @param bool $preventTalkEdit
	 * @param BlockUserFactory $blockUserFactory
	 * @param DatabaseBlockStore $databaseBlockStore
	 * @param callable $blockFactory Should take a user name and return a DatabaseBlock or null.
	 * @param FilterUser $filterUser
	 * @param MessageLocalizer $messageLocalizer
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		Parameters $params,
		string $expiry,
		bool $preventTalkEdit,
		BlockUserFactory $blockUserFactory,
		DatabaseBlockStore $databaseBlockStore,
		callable $blockFactory,
		FilterUser $filterUser,
		MessageLocalizer $messageLocalizer,
		LoggerInterface $logger
	) {
		parent::__construct( $params, $expiry, $blockUserFactory, $filterUser, $messageLocalizer, $logger );
		$this->databaseBlockStore = $databaseBlockStore;
		$this->preventsTalkEdit = $preventTalkEdit;
		$this->blockFactory = $blockFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): bool {
		$status = $this->doBlockInternal(
			$this->parameters->getFilter()->getName(),
			$this->parameters->getFilter()->getID(),
			$this->parameters->getUser()->getName(),
			$this->expiry,
			$autoblock = true,
			$this->preventsTalkEdit
		);
		// TODO: Should we reblock in case of partial blocks? At that point we could return
		// the status of doBlockInternal
		return defined( 'MW_PHPUNIT_TEST' ) ? $status->isOK() : true;
	}

	/**
	 * @inheritDoc
	 * @todo This could use UnblockUser, but we need to check if the block was performed by the AF user
	 */
	public function revert( UserIdentity $performer, string $reason ): bool {
		// TODO: Proper DI once T255433 is resolved
		$block = ( $this->blockFactory )( $this->parameters->getUser()->getName() );
		if ( !( $block && $block->getBy() === $this->filterUser->getUserIdentity()->getId() ) ) {
			// Not blocked by abuse filter
			return false;
		}
		if ( !$this->databaseBlockStore->deleteBlock( $block ) ) {
			return false;
		}
		$logEntry = new ManualLogEntry( 'block', 'unblock' );
		$logEntry->setTarget( new TitleValue( NS_USER, $this->parameters->getUser()->getName() ) );
		$logEntry->setComment( $reason );
		$logEntry->setPerformer( $performer );
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			// This has a bazillion of static dependencies all around the place, and a nightmare to deal with in tests
			// TODO: Remove this check once T253717 is resolved
			// @codeCoverageIgnoreStart
			$logEntry->publish( $logEntry->insert() );
			// @codeCoverageIgnoreEnd
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage(): array {
		$filter = $this->parameters->getFilter();
		return [
			'abusefilter-blocked-display',
			$filter->getName(),
			GlobalNameUtils::buildGlobalName( $filter->getID(), $this->parameters->getIsGlobalFilter() )
		];
	}

	/**
	 * @return string
	 * @internal
	 */
	public function getExpiry(): string {
		return $this->expiry;
	}
}
