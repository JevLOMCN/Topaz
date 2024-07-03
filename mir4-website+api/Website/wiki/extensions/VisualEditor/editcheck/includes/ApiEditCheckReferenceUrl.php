<?php

namespace MediaWiki\Extension\VisualEditor\EditCheck;

use ApiBase;
use ApiMain;
use ApiUsageException;
use ExtensionRegistry;
use MediaWiki\Extension\AbuseFilter\BlockedDomainStorage;
use MediaWiki\Extension\SpamBlacklist\BaseBlacklist;
use Wikimedia\ParamValidator\ParamValidator;

class ApiEditCheckReferenceUrl extends ApiBase {

	private ?BlockedDomainStorage $blockedDomainStorage;

	public function __construct(
		ApiMain $main,
		string $name,
		?BlockedDomainStorage $blockedDomainStorage
	) {
		parent::__construct( $main, $name );
		$this->blockedDomainStorage = $blockedDomainStorage;
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$url = $params['url'];

		if ( $this->isInBlockedExternalDomains( $url ) || $this->isInSpamBlackList( $url ) ) {
			$result = 'blocked';
		} else {
			$result = 'allowed';
		}

		$this->getResult()->addValue( null, $this->getModuleName(), [ $url => $result ] );
	}

	private function isInBlockedExternalDomains( string $url ): bool {
		if ( !$this->blockedDomainStorage ) {
			return false;
		}

		$domain = $this->blockedDomainStorage->validateDomain( $url );
		$blockedDomains = $this->blockedDomainStorage->loadComputed();
		return !empty( $blockedDomains[ $domain ] );
	}

	private function isInSpamBlackList( string $url ): bool {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'SpamBlacklist' ) ) {
			return false;
		}
		if ( !str_contains( $url, '//' ) ) {
			// SpamBlackist only detects full URLs
			$url = 'https://' . $url;
		}

		$matches = BaseBlacklist::getSpamBlacklist()->filter(
			[ $url ],
			null,
			$this->getUser(),
			true
		);

		return $matches !== false;
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'url' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function isInternal() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isWriteMode() {
		return false;
	}
}
