<?php

namespace MediaWiki\Extension\AbuseFilter\Parser;

use BagOStuff;
use IBufferingStatsdDataFactory;
use Language;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use Psr\Log\LoggerInterface;
use Wikimedia\Equivset\Equivset;

class RuleCheckerFactory {
	public const SERVICE_NAME = 'AbuseFilterRuleCheckerFactory';

	/** @var Language */
	private $contLang;

	/** @var BagOStuff */
	private $cache;

	/** @var LoggerInterface */
	private $logger;

	/** @var KeywordsManager */
	private $keywordsManager;

	/** @var VariablesManager */
	private $varManager;

	/** @var IBufferingStatsdDataFactory */
	private $statsdDataFactory;

	/** @var Equivset */
	private $equivset;

	/** @var int */
	private $conditionsLimit;

	/**
	 * @param Language $contLang
	 * @param BagOStuff $cache
	 * @param LoggerInterface $logger
	 * @param KeywordsManager $keywordsManager
	 * @param VariablesManager $varManager
	 * @param IBufferingStatsdDataFactory $statsdDataFactory
	 * @param Equivset $equivset
	 * @param int $conditionsLimit
	 */
	public function __construct(
		Language $contLang,
		BagOStuff $cache,
		LoggerInterface $logger,
		KeywordsManager $keywordsManager,
		VariablesManager $varManager,
		IBufferingStatsdDataFactory $statsdDataFactory,
		Equivset $equivset,
		int $conditionsLimit
	) {
		$this->contLang = $contLang;
		$this->cache = $cache;
		$this->logger = $logger;
		$this->keywordsManager = $keywordsManager;
		$this->varManager = $varManager;
		$this->statsdDataFactory = $statsdDataFactory;
		$this->equivset = $equivset;
		$this->conditionsLimit = $conditionsLimit;
	}

	/**
	 * @param VariableHolder|null $vars
	 * @return FilterEvaluator
	 */
	public function newRuleChecker( VariableHolder $vars = null ): FilterEvaluator {
		return new FilterEvaluator(
			$this->contLang,
			$this->cache,
			$this->logger,
			$this->keywordsManager,
			$this->varManager,
			$this->statsdDataFactory,
			$this->equivset,
			$this->conditionsLimit,
			$vars
		);
	}
}
