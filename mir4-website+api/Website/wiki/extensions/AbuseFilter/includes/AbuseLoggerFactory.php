<?php

namespace MediaWiki\Extension\AbuseFilter;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Wikimedia\Rdbms\LBFactory;

class AbuseLoggerFactory {
	public const SERVICE_NAME = 'AbuseFilterAbuseLoggerFactory';

	/** @var CentralDBManager */
	private $centralDBManager;
	/** @var FilterLookup */
	private $filterLookup;
	/** @var VariablesBlobStore */
	private $varBlobStore;
	/** @var VariablesManager */
	private $varManager;
	/** @var EditRevUpdater */
	private $editRevUpdater;
	/** @var LBFactory */
	private $lbFactory;
	/** @var ServiceOptions */
	private $options;
	/** @var string */
	private $wikiID;
	/** @var string */
	private $requestIP;

	/**
	 * @param CentralDBManager $centralDBManager
	 * @param FilterLookup $filterLookup
	 * @param VariablesBlobStore $varBlobStore
	 * @param VariablesManager $varManager
	 * @param EditRevUpdater $editRevUpdater
	 * @param LBFactory $lbFactory
	 * @param ServiceOptions $options
	 * @param string $wikiID
	 * @param string $requestIP
	 */
	public function __construct(
		CentralDBManager $centralDBManager,
		FilterLookup $filterLookup,
		VariablesBlobStore $varBlobStore,
		VariablesManager $varManager,
		EditRevUpdater $editRevUpdater,
		LBFactory $lbFactory,
		ServiceOptions $options,
		string $wikiID,
		string $requestIP
	) {
		$this->centralDBManager = $centralDBManager;
		$this->filterLookup = $filterLookup;
		$this->varBlobStore = $varBlobStore;
		$this->varManager = $varManager;
		$this->editRevUpdater = $editRevUpdater;
		$this->lbFactory = $lbFactory;
		$this->options = $options;
		$this->wikiID = $wikiID;
		$this->requestIP = $requestIP;
	}

	/**
	 * @param Title $title
	 * @param User $user
	 * @param VariableHolder $vars
	 * @return AbuseLogger
	 */
	public function newLogger(
		Title $title,
		User $user,
		VariableHolder $vars
	): AbuseLogger {
		return new AbuseLogger(
			$this->centralDBManager,
			$this->filterLookup,
			$this->varBlobStore,
			$this->varManager,
			$this->editRevUpdater,
			$this->lbFactory,
			$this->options,
			$this->wikiID,
			$this->requestIP,
			$title,
			$user,
			$vars
		);
	}
}
