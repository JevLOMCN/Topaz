<?php

namespace MediaWiki\Extension\AbuseFilter\Special;

use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\CentralDBManager;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesFactory;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\EditBox\EditBoxBuilderFactory;
use MediaWiki\Extension\AbuseFilter\FilterImporter;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\FilterProfiler;
use MediaWiki\Extension\AbuseFilter\FilterStore;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use MediaWiki\Extension\AbuseFilter\SpecsFormatter;
use MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGeneratorFactory;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesFormatter;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterView;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewDiff;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewEdit;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewExamine;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewHistory;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewImport;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewList;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewRevert;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewTestBatch;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewTools;
use MediaWiki\Html\Html;
use MediaWiki\Title\Title;
use Wikimedia\ObjectFactory\ObjectFactory;

class SpecialAbuseFilter extends AbuseFilterSpecialPage {

	private const PAGE_NAME = 'AbuseFilter';

	/**
	 * @var ObjectFactory
	 */
	private $objectFactory;

	private const SERVICES_PER_VIEW = [
		AbuseFilterViewDiff::class => [
			AbuseFilterPermissionManager::SERVICE_NAME,
			SpecsFormatter::SERVICE_NAME,
			FilterLookup::SERVICE_NAME,
		],
		AbuseFilterViewEdit::class => [
			'DBLoadBalancerFactory',
			'PermissionManager',
			AbuseFilterPermissionManager::SERVICE_NAME,
			FilterProfiler::SERVICE_NAME,
			FilterLookup::SERVICE_NAME,
			FilterImporter::SERVICE_NAME,
			FilterStore::SERVICE_NAME,
			EditBoxBuilderFactory::SERVICE_NAME,
			ConsequencesRegistry::SERVICE_NAME,
			SpecsFormatter::SERVICE_NAME,
		],
		AbuseFilterViewExamine::class => [
			'DBLoadBalancerFactory',
			AbuseFilterPermissionManager::SERVICE_NAME,
			FilterLookup::SERVICE_NAME,
			EditBoxBuilderFactory::SERVICE_NAME,
			VariablesBlobStore::SERVICE_NAME,
			VariablesFormatter::SERVICE_NAME,
			VariablesManager::SERVICE_NAME,
			VariableGeneratorFactory::SERVICE_NAME,
		],
		AbuseFilterViewHistory::class => [
			'UserNameUtils',
			'LinkBatchFactory',
			AbuseFilterPermissionManager::SERVICE_NAME,
			FilterLookup::SERVICE_NAME,
			SpecsFormatter::SERVICE_NAME,
		],
		AbuseFilterViewImport::class => [
			AbuseFilterPermissionManager::SERVICE_NAME,
		],
		AbuseFilterViewList::class => [
			'LinkBatchFactory',
			AbuseFilterPermissionManager::SERVICE_NAME,
			FilterProfiler::SERVICE_NAME,
			SpecsFormatter::SERVICE_NAME,
			CentralDBManager::SERVICE_NAME,
		],
		AbuseFilterViewRevert::class => [
			'DBLoadBalancerFactory',
			'UserFactory',
			AbuseFilterPermissionManager::SERVICE_NAME,
			FilterLookup::SERVICE_NAME,
			ConsequencesFactory::SERVICE_NAME,
			VariablesBlobStore::SERVICE_NAME,
			SpecsFormatter::SERVICE_NAME,
		],
		AbuseFilterViewTestBatch::class => [
			'DBLoadBalancerFactory',
			AbuseFilterPermissionManager::SERVICE_NAME,
			EditBoxBuilderFactory::SERVICE_NAME,
			RuleCheckerFactory::SERVICE_NAME,
			VariableGeneratorFactory::SERVICE_NAME,
		],
		AbuseFilterViewTools::class => [
			AbuseFilterPermissionManager::SERVICE_NAME,
			EditBoxBuilderFactory::SERVICE_NAME,
		],
	];

	/**
	 * @param AbuseFilterPermissionManager $afPermissionManager
	 * @param ObjectFactory $objectFactory
	 */
	public function __construct(
		AbuseFilterPermissionManager $afPermissionManager,
		ObjectFactory $objectFactory
	) {
		parent::__construct( self::PAGE_NAME, 'abusefilter-view', $afPermissionManager );
		$this->objectFactory = $objectFactory;
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'wiki';
	}

	/**
	 * @param string|null $subpage
	 */
	public function execute( $subpage ) {
		$out = $this->getOutput();
		$request = $this->getRequest();

		$out->addModuleStyles( 'ext.abuseFilter' );

		$this->setHeaders();
		$this->addHelpLink( 'Extension:AbuseFilter' );

		$this->checkPermissions();

		if ( $request->getVal( 'result' ) === 'success' ) {
			$out->setSubtitle( $this->msg( 'abusefilter-edit-done-subtitle' ) );
			$changedFilter = intval( $request->getVal( 'changedfilter' ) );
			$changeId = intval( $request->getVal( 'changeid' ) );
			$out->addHTML( Html::successBox(
				$this->msg(
					'abusefilter-edit-done',
					$changedFilter,
					$changeId,
					$this->getLanguage()->formatNum( $changedFilter )
				)->parse()
			) );
		}

		[ $view, $pageType, $params ] = $this->getViewClassAndPageType( $subpage );

		// Links at the top
		$this->addNavigationLinks( $pageType );

		$view = $this->instantiateView( $view, $params );
		$view->show();
	}

	/**
	 * Instantiate the view class
	 *
	 * @suppress PhanTypeInvalidCallableArraySize
	 *
	 * @param class-string<AbuseFilterView> $viewClass
	 * @param array $params
	 * @return AbuseFilterView
	 */
	public function instantiateView( string $viewClass, array $params ): AbuseFilterView {
		return $this->objectFactory->createObject( [
			'class' => $viewClass,
			'services' => self::SERVICES_PER_VIEW[$viewClass],
			'args' => [ $this->getContext(), $this->getLinkRenderer(), self::PAGE_NAME, $params ]
		] );
	}

	/**
	 * Determine the view class to instantiate
	 *
	 * @param string|null $subpage
	 * @return array A tuple of three elements:
	 *      - a subclass of AbuseFilterView
	 *      - type of page for addNavigationLinks
	 *      - array of parameters for the class
	 * @phan-return array{0:class-string,1:string,2:array}
	 */
	public function getViewClassAndPageType( $subpage ): array {
		// Filter by removing blanks.
		$params = array_values( array_filter(
			explode( '/', $subpage ?: '' ),
			static function ( $value ) {
				return $value !== '';
			}
		) );

		if ( $subpage === 'tools' ) {
			return [ AbuseFilterViewTools::class, 'tools', [] ];
		}

		if ( $subpage === 'import' ) {
			return [ AbuseFilterViewImport::class, 'import', [] ];
		}

		if ( is_numeric( $subpage ) || $subpage === 'new' ) {
			return [
				AbuseFilterViewEdit::class,
				'edit',
				[ 'filter' => is_numeric( $subpage ) ? (int)$subpage : null ]
			];
		}

		if ( $params ) {
			if ( count( $params ) === 2 && $params[0] === 'revert' && is_numeric( $params[1] ) ) {
				$params[1] = (int)$params[1];
				return [ AbuseFilterViewRevert::class, 'revert', $params ];
			}

			if ( $params[0] === 'test' ) {
				return [ AbuseFilterViewTestBatch::class, 'test', $params ];
			}

			if ( $params[0] === 'examine' ) {
				return [ AbuseFilterViewExamine::class, 'examine', $params ];
			}

			if ( $params[0] === 'history' || $params[0] === 'log' ) {
				if ( count( $params ) <= 2 ) {
					$params = isset( $params[1] ) ? [ 'filter' => (int)$params[1] ] : [];
					return [ AbuseFilterViewHistory::class, 'recentchanges', $params ];
				}
				if ( count( $params ) === 4 && $params[2] === 'item' ) {
					return [
						AbuseFilterViewEdit::class,
						'',
						[ 'filter' => (int)$params[1], 'history' => (int)$params[3] ]
					];
				}
				if ( count( $params ) === 5 && $params[2] === 'diff' ) {
					// Special:AbuseFilter/history/<filter>/diff/<oldid>/<newid>
					return [ AbuseFilterViewDiff::class, '', $params ];
				}
			}
		}

		return [ AbuseFilterViewList::class, 'home', [] ];
	}

	/**
	 * Static variant to get the associated Title.
	 *
	 * @param string|int $subpage
	 * @return Title
	 */
	public static function getTitleForSubpage( $subpage ): Title {
		return self::getTitleFor( self::PAGE_NAME, $subpage );
	}
}
