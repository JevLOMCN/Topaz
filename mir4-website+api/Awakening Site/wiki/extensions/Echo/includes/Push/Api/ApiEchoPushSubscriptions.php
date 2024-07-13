<?php

namespace MediaWiki\Extension\Notifications\Push\Api;

use ApiBase;
use ApiModuleManager;
use ApiUsageException;
use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API parent module for administering push subscriptions.
 * Each operation (command) is implemented as a submodule. This module just performs some basic
 * checks and dispatches the execute() call.
 */
class ApiEchoPushSubscriptions extends ApiBase {

	/** array Module name => module class */
	private const SUBMODULES = [
		'create' => ApiEchoPushSubscriptionsCreate::class,
		'delete' => ApiEchoPushSubscriptionsDelete::class,
	];

	/** @var ApiModuleManager */
	private $moduleManager;

	/** @inheritDoc */
	public function execute(): void {
		$this->checkLoginState();
		$this->checkUserRightsAny( 'editmyprivateinfo' );
		$command = $this->getParameter( 'command' );
		$module = $this->moduleManager->getModule( $command, 'command' );
		$module->execute();
		$module->getResult()->addValue(
			null,
			$module->getModuleName(),
			[ 'result' => 'Success' ]
		);
	}

	/** @inheritDoc */
	public function getModuleManager(): ApiModuleManager {
		if ( !$this->moduleManager ) {
			$submodules = array_map( static function ( $class ) {
				return [
					'class' => $class,
					'factory' => "$class::factory",
				];
			}, self::SUBMODULES );
			$this->moduleManager = new ApiModuleManager(
				$this,
				MediaWikiServices::getInstance()->getObjectFactory()
			);
			$this->moduleManager->addModules( $submodules, 'command' );
		}
		return $this->moduleManager;
	}

	/** @inheritDoc */
	protected function getAllowedParams(): array {
		return [
			'command' => [
				ParamValidator::PARAM_TYPE => 'submodule',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	/**
	 * Bail out with an API error if the user is not logged in.
	 * @throws ApiUsageException
	 */
	private function checkLoginState(): void {
		if ( !$this->getUser()->isRegistered() ) {
			$this->dieWithError(
				[ 'apierror-mustbeloggedin', $this->msg( 'action-editmyprivateinfo' ) ],
				'notloggedin'
			);
		}
	}

	/** @inheritDoc */
	public function getHelpUrls(): string {
		return 'https://www.mediawiki.org/wiki/Special:MyLanguage/Extension:Echo#API';
	}

	/** @inheritDoc */
	public function isWriteMode(): bool {
		return true;
	}

	/** @inheritDoc */
	public function needsToken(): string {
		return 'csrf';
	}

	/** @inheritDoc */
	public function isInternal(): bool {
		// experimental!
		return true;
	}

}
