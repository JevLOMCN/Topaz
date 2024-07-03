<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences\Consequence;

use LogPage;
use MediaWiki\Block\BlockUserFactory;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\AbuseFilter\FilterUser;
use MediaWiki\Status\Status;
use MessageLocalizer;
use Psr\Log\LoggerInterface;
use Wikimedia\IPUtils;

/**
 * Base class for consequences that block a user
 */
abstract class BlockingConsequence extends Consequence implements HookAborterConsequence {
	/** @var BlockUserFactory */
	private $blockUserFactory;

	/** @var FilterUser */
	protected $filterUser;

	/** @var MessageLocalizer */
	private $messageLocalizer;

	/** @var LoggerInterface */
	private $logger;

	/** @var string Expiry of the block */
	protected $expiry;

	/**
	 * @param Parameters $params
	 * @param string $expiry
	 * @param BlockUserFactory $blockUserFactory
	 * @param FilterUser $filterUser
	 * @param MessageLocalizer $messageLocalizer
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		Parameters $params,
		string $expiry,
		BlockUserFactory $blockUserFactory,
		FilterUser $filterUser,
		MessageLocalizer $messageLocalizer,
		LoggerInterface $logger
	) {
		parent::__construct( $params );
		$this->expiry = $expiry;
		$this->blockUserFactory = $blockUserFactory;
		$this->filterUser = $filterUser;
		$this->messageLocalizer = $messageLocalizer;
		$this->logger = $logger;
	}

	/**
	 * Perform a block by the AbuseFilter system user
	 * @param string $ruleDesc
	 * @param int|string $ruleNumber
	 * @param string $target
	 * @param string $expiry
	 * @param bool $isAutoBlock
	 * @param bool $preventEditOwnUserTalk
	 * @return Status
	 */
	protected function doBlockInternal(
		string $ruleDesc,
		$ruleNumber,
		string $target,
		string $expiry,
		bool $isAutoBlock,
		bool $preventEditOwnUserTalk
	): Status {
		$reason = $this->messageLocalizer->msg(
			'abusefilter-blockreason',
			$ruleDesc,
			$ruleNumber
		)->inContentLanguage()->text();

		$blockUser = $this->blockUserFactory->newBlockUser(
			$target,
			$this->filterUser->getAuthority(),
			$expiry,
			$reason,
			[
				'isHardBlock' => false,
				'isAutoblocking' => $isAutoBlock,
				'isCreateAccountBlocked' => true,
				'isUserTalkEditBlocked' => $preventEditOwnUserTalk
			]
		);
		if (
			strpos( $this->parameters->getAction(), 'createaccount' ) !== false &&
			IPUtils::isIPAddress( $target )
		) {
			$blockUser->setLogDeletionFlags( LogPage::SUPPRESSED_ACTION );
		}
		$status = $blockUser->placeBlockUnsafe();
		if ( !$status->isGood() ) {
			$this->logger->warning(
				'AbuseFilter block to {block_target} failed: {errors}',
				[ 'block_target' => $target, 'errors' => $status->__toString() ]
			);
		}
		return $status;
	}
}
