<?php

namespace MediaWiki\Extension\AbuseFilter\Api;

use ApiBase;
use ApiMain;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\UserVisibleException;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use Wikimedia\ParamValidator\ParamValidator;

class CheckSyntax extends ApiBase {

	/** @var RuleCheckerFactory */
	private $ruleCheckerFactory;

	/** @var AbuseFilterPermissionManager */
	private $afPermManager;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param RuleCheckerFactory $ruleCheckerFactory
	 * @param AbuseFilterPermissionManager $afPermManager
	 */
	public function __construct(
		ApiMain $main,
		$action,
		RuleCheckerFactory $ruleCheckerFactory,
		AbuseFilterPermissionManager $afPermManager
	) {
		parent::__construct( $main, $action );
		$this->ruleCheckerFactory = $ruleCheckerFactory;
		$this->afPermManager = $afPermManager;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		// "Anti-DoS"
		if ( !$this->afPermManager->canUseTestTools( $this->getAuthority() )
			&& !$this->afPermManager->canEdit( $this->getAuthority() )
		) {
			$this->dieWithError( 'apierror-abusefilter-cantcheck', 'permissiondenied' );
		}

		$params = $this->extractRequestParams();
		$result = $this->ruleCheckerFactory->newRuleChecker()->checkSyntax( $params['filter'] );

		$r = [];
		$warnings = [];
		foreach ( $result->getWarnings() as $warning ) {
			$warnings[] = [
				'message' => $this->msg( $warning->getMessageObj() )->text(),
				'character' => $warning->getPosition()
			];
		}
		if ( $warnings ) {
			$r['warnings'] = $warnings;
		}

		if ( $result->isValid() ) {
			// Everything went better than expected :)
			$r['status'] = 'ok';
		} else {
			// TODO: Improve the type here.
			/** @var UserVisibleException $excep */
			$excep = $result->getException();
			'@phan-var UserVisibleException $excep';
			$r = [
				'status' => 'error',
				'message' => $this->msg( $excep->getMessageObj() )->text(),
				'character' => $excep->getPosition(),
			];
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $r );
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'filter' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=abusefilterchecksyntax&filter="foo"'
				=> 'apihelp-abusefilterchecksyntax-example-1',
			'action=abusefilterchecksyntax&filter="bar"%20bad_variable'
				=> 'apihelp-abusefilterchecksyntax-example-2',
		];
	}
}
