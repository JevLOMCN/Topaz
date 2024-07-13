<?php

namespace MediaWiki\Extension\AbuseFilter\Api;

use ApiBase;
use ApiMain;
use ApiResult;
use FormatJson;
use LogEventsList;
use LogicException;
use LogPage;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use MediaWiki\Extension\AbuseFilter\Special\SpecialAbuseLog;
use MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGeneratorFactory;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore;
use MediaWiki\Revision\RevisionRecord;
use RecentChange;
use Wikimedia\ParamValidator\ParamValidator;

class CheckMatch extends ApiBase {

	/** @var RuleCheckerFactory */
	private $ruleCheckerFactory;

	/** @var AbuseFilterPermissionManager */
	private $afPermManager;

	/** @var VariablesBlobStore */
	private $afVariablesBlobStore;

	/** @var VariableGeneratorFactory */
	private $afVariableGeneratorFactory;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param RuleCheckerFactory $ruleCheckerFactory
	 * @param AbuseFilterPermissionManager $afPermManager
	 * @param VariablesBlobStore $afVariablesBlobStore
	 * @param VariableGeneratorFactory $afVariableGeneratorFactory
	 */
	public function __construct(
		ApiMain $main,
		$action,
		RuleCheckerFactory $ruleCheckerFactory,
		AbuseFilterPermissionManager $afPermManager,
		VariablesBlobStore $afVariablesBlobStore,
		VariableGeneratorFactory $afVariableGeneratorFactory
	) {
		parent::__construct( $main, $action );
		$this->ruleCheckerFactory = $ruleCheckerFactory;
		$this->afPermManager = $afPermManager;
		$this->afVariablesBlobStore = $afVariablesBlobStore;
		$this->afVariableGeneratorFactory = $afVariableGeneratorFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$performer = $this->getAuthority();
		$params = $this->extractRequestParams();
		$this->requireOnlyOneParameter( $params, 'vars', 'rcid', 'logid' );

		// "Anti-DoS"
		if ( !$this->afPermManager->canUseTestTools( $performer ) ) {
			$this->dieWithError( 'apierror-abusefilter-canttest', 'permissiondenied' );
		}

		$vars = null;
		if ( $params['vars'] ) {
			$pairs = FormatJson::decode( $params['vars'], true );
			$vars = VariableHolder::newFromArray( $pairs );
		} elseif ( $params['rcid'] ) {
			$rc = RecentChange::newFromId( $params['rcid'] );

			if ( !$rc ) {
				$this->dieWithError( [ 'apierror-nosuchrcid', $params['rcid'] ] );
			}

			$type = (int)$rc->getAttribute( 'rc_type' );
			$deletedValue = $rc->getAttribute( 'rc_deleted' );
			if (
				(
					$type === RC_LOG &&
					!LogEventsList::userCanBitfield(
						$deletedValue,
						LogPage::SUPPRESSED_ACTION | LogPage::SUPPRESSED_USER,
						$performer
					)
				) || (
					$type !== RC_LOG &&
					!RevisionRecord::userCanBitfield( $deletedValue, RevisionRecord::SUPPRESSED_ALL, $performer )
				)
			) {
				// T223654 - Same check as in AbuseFilterChangesList
				$this->dieWithError( 'apierror-permissiondenied-generic', 'deletedrc' );
			}

			$varGenerator = $this->afVariableGeneratorFactory->newRCGenerator( $rc, $this->getUser() );
			$vars = $varGenerator->getVars();
		} elseif ( $params['logid'] ) {
			$row = $this->getDB()->selectRow(
				'abuse_filter_log',
				'*',
				[ 'afl_id' => $params['logid'] ],
				__METHOD__
			);

			if ( !$row ) {
				$this->dieWithError( [ 'apierror-abusefilter-nosuchlogid', $params['logid'] ], 'nosuchlogid' );
			}

			$visibility = SpecialAbuseLog::getEntryVisibilityForUser( $row, $performer, $this->afPermManager );
			if ( $visibility !== SpecialAbuseLog::VISIBILITY_VISIBLE ) {
				// T223654 - Same check as in SpecialAbuseLog. Both the visibility of the AbuseLog entry
				// and the corresponding revision are checked.
				$this->dieWithError( 'apierror-permissiondenied-generic', 'deletedabuselog' );
			}

			$vars = $this->afVariablesBlobStore->loadVarDump( $row->afl_var_dump );
		}
		if ( $vars === null ) {
			// @codeCoverageIgnoreStart
			throw new LogicException( 'Impossible.' );
			// @codeCoverageIgnoreEnd
		}

		$ruleChecker = $this->ruleCheckerFactory->newRuleChecker( $vars );
		if ( !$ruleChecker->checkSyntax( $params['filter'] )->isValid() ) {
			$this->dieWithError( 'apierror-abusefilter-badsyntax', 'badsyntax' );
		}

		$result = [
			ApiResult::META_BC_BOOLS => [ 'result' ],
			'result' => $ruleChecker->checkConditions( $params['filter'] )->getResult(),
		];

		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			$result
		);
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
			'vars' => null,
			'rcid' => [
				ParamValidator::PARAM_TYPE => 'integer'
			],
			'logid' => [
				ParamValidator::PARAM_TYPE => 'integer'
			],
		];
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=abusefiltercheckmatch&filter=!("autoconfirmed"%20in%20user_groups)&rcid=15'
				=> 'apihelp-abusefiltercheckmatch-example-1',
		];
	}
}
