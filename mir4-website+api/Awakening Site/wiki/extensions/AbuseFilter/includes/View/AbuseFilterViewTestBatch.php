<?php

namespace MediaWiki\Extension\AbuseFilter\View;

use HTMLForm;
use IContextSource;
use LogEventsList;
use LogPage;
use MediaWiki\Extension\AbuseFilter\AbuseFilterChangesList;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\EditBox\EditBoxBuilderFactory;
use MediaWiki\Extension\AbuseFilter\EditBox\EditBoxField;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGeneratorFactory;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use RecentChange;
use Wikimedia\Rdbms\LBFactory;

class AbuseFilterViewTestBatch extends AbuseFilterView {
	/**
	 * @var int The limit of changes to test, hard coded for now
	 */
	private static $mChangeLimit = 100;

	/**
	 * @var LBFactory
	 */
	private $lbFactory;
	/**
	 * @var string The text of the rule to test changes against
	 */
	private $testPattern;
	/**
	 * @var EditBoxBuilderFactory
	 */
	private $boxBuilderFactory;
	/**
	 * @var RuleCheckerFactory
	 */
	private $ruleCheckerFactory;
	/**
	 * @var VariableGeneratorFactory
	 */
	private $varGeneratorFactory;

	/**
	 * @param LBFactory $lbFactory
	 * @param AbuseFilterPermissionManager $afPermManager
	 * @param EditBoxBuilderFactory $boxBuilderFactory
	 * @param RuleCheckerFactory $ruleCheckerFactory
	 * @param VariableGeneratorFactory $varGeneratorFactory
	 * @param IContextSource $context
	 * @param LinkRenderer $linkRenderer
	 * @param string $basePageName
	 * @param array $params
	 */
	public function __construct(
		LBFactory $lbFactory,
		AbuseFilterPermissionManager $afPermManager,
		EditBoxBuilderFactory $boxBuilderFactory,
		RuleCheckerFactory $ruleCheckerFactory,
		VariableGeneratorFactory $varGeneratorFactory,
		IContextSource $context,
		LinkRenderer $linkRenderer,
		string $basePageName,
		array $params
	) {
		parent::__construct( $afPermManager, $context, $linkRenderer, $basePageName, $params );
		$this->lbFactory = $lbFactory;
		$this->boxBuilderFactory = $boxBuilderFactory;
		$this->ruleCheckerFactory = $ruleCheckerFactory;
		$this->varGeneratorFactory = $varGeneratorFactory;
	}

	/**
	 * Shows the page
	 */
	public function show() {
		$out = $this->getOutput();

		if ( !$this->afPermManager->canUseTestTools( $this->getAuthority() ) ) {
			// TODO: the message still refers to the old rights
			$out->addWikiMsg( 'abusefilter-mustviewprivateoredit' );
			return;
		}

		$this->loadParameters();

		$out->setPageTitleMsg( $this->msg( 'abusefilter-test' ) );
		$out->addHelpLink( 'Extension:AbuseFilter/Rules format' );
		$out->addWikiMsg( 'abusefilter-test-intro', self::$mChangeLimit );
		$out->enableOOUI();

		$boxBuilder = $this->boxBuilderFactory->newEditBoxBuilder( $this, $this->getAuthority(), $out );

		$rulesFields = [
			'rules' => [
				'section' => 'abusefilter-test-rules-section',
				'class' => EditBoxField::class,
				'html' => $boxBuilder->buildEditBox(
					$this->testPattern,
					true,
					true,
					false
				) . $this->buildFilterLoader()
			]
		];

		$RCMaxAge = $this->getConfig()->get( 'RCMaxAge' );
		$min = wfTimestamp( TS_ISO_8601, time() - $RCMaxAge );
		$max = wfTimestampNow();

		$optionsFields = [
			'TestAction' => [
				'type' => 'select',
				'label-message' => 'abusefilter-test-action',
				'options-messages' => [
					'abusefilter-test-search-type-all' => '0',
					'abusefilter-test-search-type-edit' => 'edit',
					'abusefilter-test-search-type-move' => 'move',
					'abusefilter-test-search-type-delete' => 'delete',
					'abusefilter-test-search-type-createaccount' => 'createaccount',
					'abusefilter-test-search-type-upload' => 'upload'
				],
			],
			'TestUser' => [
				'type' => 'user',
				'exists' => true,
				'ipallowed' => true,
				'required' => false,
				'label-message' => 'abusefilter-test-user',
			],
			'ExcludeBots' => [
				'type' => 'check',
				'label-message' => 'abusefilter-test-nobots',
			],
			'TestPeriodStart' => [
				'type' => 'datetime',
				'label-message' => 'abusefilter-test-period-start',
				'min' => $min,
				'max' => $max,
			],
			'TestPeriodEnd' => [
				'type' => 'datetime',
				'label-message' => 'abusefilter-test-period-end',
				'min' => $min,
				'max' => $max,
			],
			'TestPage' => [
				'type' => 'title',
				'label-message' => 'abusefilter-test-page',
				'creatable' => true,
				'required' => false,
			],
			'ShowNegative' => [
				'type' => 'check',
				'label-message' => 'abusefilter-test-shownegative',
			],
		];
		array_walk( $optionsFields, static function ( &$el ) {
			$el['section'] = 'abusefilter-test-options-section';
		} );
		$allFields = array_merge( $rulesFields, $optionsFields );

		HTMLForm::factory( 'ooui', $allFields, $this->getContext() )
			->setTitle( $this->getTitle( 'test' ) )
			->setId( 'wpFilterForm' )
			->setWrapperLegendMsg( 'abusefilter-test-legend' )
			->setSubmitTextMsg( 'abusefilter-test-submit' )
			->setSubmitCallback( [ $this, 'doTest' ] )
			->showAlways();
	}

	/**
	 * Loads the revisions and checks the given syntax against them
	 * @param array $formData
	 * @param HTMLForm $form
	 * @return bool
	 */
	public function doTest( array $formData, HTMLForm $form ): bool {
		// Quick syntax check.
		$ruleChecker = $this->ruleCheckerFactory->newRuleChecker();

		if ( !$ruleChecker->checkSyntax( $this->testPattern )->isValid() ) {
			$form->addPreHtml(
				Html::errorBox( $this->msg( 'abusefilter-test-syntaxerr' )->parse() )
			);
			return true;
		}

		$dbr = $this->lbFactory->getReplicaDatabase();
		$rcQuery = RecentChange::getQueryInfo();
		$conds = [];

		// Normalise username
		$userTitle = Title::newFromText( $formData['TestUser'], NS_USER );
		$testUser = $userTitle ? $userTitle->getText() : '';
		if ( $testUser !== '' ) {
			$conds[$rcQuery['fields']['rc_user_text']] = $testUser;
		}

		$startTS = strtotime( $formData['TestPeriodStart'] );
		if ( $startTS ) {
			$conds[] = 'rc_timestamp>=' . $dbr->addQuotes( $dbr->timestamp( $startTS ) );
		}
		$endTS = strtotime( $formData['TestPeriodEnd'] );
		if ( $endTS ) {
			$conds[] = 'rc_timestamp<=' . $dbr->addQuotes( $dbr->timestamp( $endTS ) );
		}
		if ( $formData['TestPage'] !== '' ) {
			// The form validates the input for us, so this shouldn't throw.
			$title = Title::newFromTextThrow( $formData['TestPage'] );
			$conds['rc_namespace'] = $title->getNamespace();
			$conds['rc_title'] = $title->getDBkey();
		}

		if ( $formData['ExcludeBots'] ) {
			$conds['rc_bot'] = 0;
		}

		$action = $formData['TestAction'] !== '0' ? $formData['TestAction'] : false;
		$conds[] = $this->buildTestConditions( $dbr, $action );
		$conds = array_merge( $conds, $this->buildVisibilityConditions( $dbr, $this->getAuthority() ) );

		$res = $dbr->select(
			$rcQuery['tables'],
			$rcQuery['fields'],
			$conds,
			__METHOD__,
			[ 'LIMIT' => self::$mChangeLimit, 'ORDER BY' => 'rc_timestamp desc' ],
			$rcQuery['joins']
		);

		// Get our ChangesList
		$changesList = new AbuseFilterChangesList( $this->getContext(), $this->testPattern );
		// Note, we're initializing some rows that will later be discarded. Hopefully this won't have any overhead.
		$changesList->initChangesListRows( $res );
		$output = $changesList->beginRecentChangesList();

		$counter = 1;

		$contextUser = $this->getUser();
		$ruleChecker->toggleConditionLimit( false );
		foreach ( $res as $row ) {
			$rc = RecentChange::newFromRow( $row );
			if ( !$formData['ShowNegative'] ) {
				$type = (int)$rc->getAttribute( 'rc_type' );
				$deletedValue = (int)$rc->getAttribute( 'rc_deleted' );
				if (
					(
						$type === RC_LOG &&
						!LogEventsList::userCanBitfield(
							$deletedValue,
							LogPage::SUPPRESSED_ACTION | LogPage::SUPPRESSED_USER,
							$contextUser
						)
					) || (
						$type !== RC_LOG &&
						!RevisionRecord::userCanBitfield( $deletedValue, RevisionRecord::SUPPRESSED_ALL, $contextUser )
					)
				) {
					// If the RC is deleted, the user can't see it, and we're only showing matches,
					// always skip this row. If ShowNegative is true, we can still show the row
					// because we won't tell whether it matches the given filter.
					continue;
				}
			}

			$varGenerator = $this->varGeneratorFactory->newRCGenerator( $rc, $contextUser );
			$vars = $varGenerator->getVars();

			if ( !$vars ) {
				continue;
			}

			$ruleChecker->setVariables( $vars );
			$result = $ruleChecker->checkConditions( $this->testPattern )->getResult();

			if ( $result || $formData['ShowNegative'] ) {
				// Stash result in RC item
				$rc->filterResult = $result;
				$rc->counter = $counter++;
				$output .= $changesList->recentChangesLine( $rc, false );
			}
		}

		$output .= $changesList->endRecentChangesList();

		$form->addPostHtml( $output );

		return true;
	}

	/**
	 * Loads parameters from request
	 */
	public function loadParameters() {
		$request = $this->getRequest();

		$this->testPattern = $request->getText( 'wpFilterRules' );

		if ( $this->testPattern === ''
			&& count( $this->mParams ) > 1
			&& is_numeric( $this->mParams[1] )
		) {
			$dbr = $this->lbFactory->getReplicaDatabase();
			$pattern = $dbr->selectField( 'abuse_filter',
				'af_pattern',
				[ 'af_id' => intval( $this->mParams[1] ) ],
				__METHOD__
			);
			if ( $pattern !== false ) {
				$this->testPattern = $pattern;
			}
		}
	}
}
