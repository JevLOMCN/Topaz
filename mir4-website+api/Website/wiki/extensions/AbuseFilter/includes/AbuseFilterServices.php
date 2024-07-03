<?php

namespace MediaWiki\Extension\AbuseFilter;

use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagger;
use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagsManager;
use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagValidator;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesExecutorFactory;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesFactory;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesLookup;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\EditBox\EditBoxBuilderFactory;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use MediaWiki\Extension\AbuseFilter\VariableGenerator\VariableGeneratorFactory;
use MediaWiki\Extension\AbuseFilter\Variables\LazyVariableComputer;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesFormatter;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Extension\AbuseFilter\Watcher\EmergencyWatcher;
use MediaWiki\Extension\AbuseFilter\Watcher\UpdateHitCountWatcher;
use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;

class AbuseFilterServices {

	public static function getHookRunner( ContainerInterface $services = null ): AbuseFilterHookRunner {
		return ( $services ?? MediaWikiServices::getInstance() )->get( AbuseFilterHookRunner::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return KeywordsManager
	 */
	public static function getKeywordsManager( ContainerInterface $services = null ): KeywordsManager {
		return ( $services ?? MediaWikiServices::getInstance() )->get( KeywordsManager::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return FilterProfiler
	 */
	public static function getFilterProfiler( ContainerInterface $services = null ): FilterProfiler {
		return ( $services ?? MediaWikiServices::getInstance() )->get( FilterProfiler::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return AbuseFilterPermissionManager
	 */
	public static function getPermissionManager( ContainerInterface $services = null ): AbuseFilterPermissionManager {
		return ( $services ?? MediaWikiServices::getInstance() )->get( AbuseFilterPermissionManager::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return ChangeTagger
	 */
	public static function getChangeTagger( ContainerInterface $services = null ): ChangeTagger {
		return ( $services ?? MediaWikiServices::getInstance() )->get( ChangeTagger::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return ChangeTagsManager
	 */
	public static function getChangeTagsManager( ContainerInterface $services = null ): ChangeTagsManager {
		return ( $services ?? MediaWikiServices::getInstance() )->get( ChangeTagsManager::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return ChangeTagValidator
	 */
	public static function getChangeTagValidator( ContainerInterface $services = null ): ChangeTagValidator {
		return ( $services ?? MediaWikiServices::getInstance() )->get( ChangeTagValidator::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return BlockAutopromoteStore
	 */
	public static function getBlockAutopromoteStore( ContainerInterface $services = null ): BlockAutopromoteStore {
		return ( $services ?? MediaWikiServices::getInstance() )->get( BlockAutopromoteStore::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return FilterUser
	 */
	public static function getFilterUser( ContainerInterface $services = null ): FilterUser {
		return ( $services ?? MediaWikiServices::getInstance() )->get( FilterUser::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return CentralDBManager
	 */
	public static function getCentralDBManager( ContainerInterface $services = null ): CentralDBManager {
		return ( $services ?? MediaWikiServices::getInstance() )->get( CentralDBManager::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return RuleCheckerFactory
	 */
	public static function getRuleCheckerFactory( ContainerInterface $services = null ): RuleCheckerFactory {
		return ( $services ?? MediaWikiServices::getInstance() )->get( RuleCheckerFactory::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return FilterLookup
	 */
	public static function getFilterLookup( ContainerInterface $services = null ): FilterLookup {
		return ( $services ?? MediaWikiServices::getInstance() )->get( FilterLookup::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return EmergencyCache
	 */
	public static function getEmergencyCache( ContainerInterface $services = null ): EmergencyCache {
		return ( $services ?? MediaWikiServices::getInstance() )->get( EmergencyCache::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return EmergencyWatcher
	 */
	public static function getEmergencyWatcher( ContainerInterface $services = null ): EmergencyWatcher {
		return ( $services ?? MediaWikiServices::getInstance() )->get( EmergencyWatcher::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return EchoNotifier
	 */
	public static function getEchoNotifier( ContainerInterface $services = null ): EchoNotifier {
		return ( $services ?? MediaWikiServices::getInstance() )->get( EchoNotifier::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return FilterValidator
	 */
	public static function getFilterValidator( ContainerInterface $services = null ): FilterValidator {
		return ( $services ?? MediaWikiServices::getInstance() )->get( FilterValidator::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return FilterCompare
	 */
	public static function getFilterCompare( ContainerInterface $services = null ): FilterCompare {
		return ( $services ?? MediaWikiServices::getInstance() )->get( FilterCompare::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return FilterImporter
	 */
	public static function getFilterImporter( ContainerInterface $services = null ): FilterImporter {
		return ( $services ?? MediaWikiServices::getInstance() )->get( FilterImporter::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return FilterStore
	 */
	public static function getFilterStore( ContainerInterface $services = null ): FilterStore {
		return ( $services ?? MediaWikiServices::getInstance() )->get( FilterStore::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return ConsequencesFactory
	 */
	public static function getConsequencesFactory( ContainerInterface $services = null ): ConsequencesFactory {
		return ( $services ?? MediaWikiServices::getInstance() )->get( ConsequencesFactory::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return EditBoxBuilderFactory
	 */
	public static function getEditBoxBuilderFactory( ContainerInterface $services = null ): EditBoxBuilderFactory {
		return ( $services ?? MediaWikiServices::getInstance() )->get( EditBoxBuilderFactory::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return ConsequencesLookup
	 */
	public static function getConsequencesLookup( ContainerInterface $services = null ): ConsequencesLookup {
		return ( $services ?? MediaWikiServices::getInstance() )->get( ConsequencesLookup::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return ConsequencesRegistry
	 */
	public static function getConsequencesRegistry( ContainerInterface $services = null ): ConsequencesRegistry {
		return ( $services ?? MediaWikiServices::getInstance() )->get( ConsequencesRegistry::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return AbuseLoggerFactory
	 */
	public static function getAbuseLoggerFactory( ContainerInterface $services = null ): AbuseLoggerFactory {
		return ( $services ?? MediaWikiServices::getInstance() )->get( AbuseLoggerFactory::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return UpdateHitCountWatcher
	 */
	public static function getUpdateHitCountWatcher( ContainerInterface $services = null ): UpdateHitCountWatcher {
		return ( $services ?? MediaWikiServices::getInstance() )->get( UpdateHitCountWatcher::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return VariablesBlobStore
	 */
	public static function getVariablesBlobStore( ContainerInterface $services = null ): VariablesBlobStore {
		return ( $services ?? MediaWikiServices::getInstance() )->get( VariablesBlobStore::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return ConsequencesExecutorFactory
	 */
	public static function getConsequencesExecutorFactory(
		ContainerInterface $services = null
	): ConsequencesExecutorFactory {
		return ( $services ?? MediaWikiServices::getInstance() )->get( ConsequencesExecutorFactory::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return FilterRunnerFactory
	 */
	public static function getFilterRunnerFactory( ContainerInterface $services = null ): FilterRunnerFactory {
		return ( $services ?? MediaWikiServices::getInstance() )->get( FilterRunnerFactory::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return SpecsFormatter
	 */
	public static function getSpecsFormatter( ContainerInterface $services = null ): SpecsFormatter {
		return ( $services ?? MediaWikiServices::getInstance() )->get( SpecsFormatter::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return VariablesFormatter
	 */
	public static function getVariablesFormatter( ContainerInterface $services = null ): VariablesFormatter {
		return ( $services ?? MediaWikiServices::getInstance() )->get( VariablesFormatter::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return LazyVariableComputer
	 */
	public static function getLazyVariableComputer( ContainerInterface $services = null ): LazyVariableComputer {
		return ( $services ?? MediaWikiServices::getInstance() )->get( LazyVariableComputer::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return TextExtractor
	 */
	public static function getTextExtractor( ContainerInterface $services = null ): TextExtractor {
		return ( $services ?? MediaWikiServices::getInstance() )->get( TextExtractor::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return VariablesManager
	 */
	public static function getVariablesManager( ContainerInterface $services = null ): VariablesManager {
		return ( $services ?? MediaWikiServices::getInstance() )->get( VariablesManager::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return VariableGeneratorFactory
	 */
	public static function getVariableGeneratorFactory(
		ContainerInterface $services = null
	): VariableGeneratorFactory {
		return ( $services ?? MediaWikiServices::getInstance() )->get( VariableGeneratorFactory::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return EditRevUpdater
	 */
	public static function getEditRevUpdater( ContainerInterface $services = null ): EditRevUpdater {
		return ( $services ?? MediaWikiServices::getInstance() )->get( EditRevUpdater::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return AbuseFilterActorMigration
	 */
	public static function getActorMigration( ContainerInterface $services = null ): AbuseFilterActorMigration {
		return ( $services ?? MediaWikiServices::getInstance() )->get( AbuseFilterActorMigration::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return BlockedDomainStorage
	 */
	public static function getBlockedDomainStorage( ContainerInterface $services = null ): BlockedDomainStorage {
		return ( $services ?? MediaWikiServices::getInstance() )->get( BlockedDomainStorage::SERVICE_NAME );
	}

	/**
	 * @param ContainerInterface|null $services
	 * @return BlockedDomainFilter
	 */
	public static function getBlockedDomainFilter( ContainerInterface $services = null ): BlockedDomainFilter {
		return ( $services ?? MediaWikiServices::getInstance() )->get( BlockedDomainFilter::SERVICE_NAME );
	}
}
