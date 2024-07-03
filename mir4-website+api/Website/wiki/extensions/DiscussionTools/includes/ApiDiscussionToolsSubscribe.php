<?php

namespace MediaWiki\Extension\DiscussionTools;

use ApiBase;
use ApiMain;
use ApiUsageException;
use MediaWiki\Title\Title;
use Wikimedia\ParamValidator\ParamValidator;

class ApiDiscussionToolsSubscribe extends ApiBase {

	private SubscriptionStore $subscriptionStore;

	public function __construct(
		ApiMain $main,
		string $name,
		SubscriptionStore $subscriptionStore
	) {
		parent::__construct( $main, $name );
		$this->subscriptionStore = $subscriptionStore;
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 */
	public function execute() {
		$user = $this->getUser();
		if ( !$user->isNamed() ) {
			$this->dieWithError( 'apierror-mustbeloggedin-generic', 'notloggedin' );
		}

		$params = $this->extractRequestParams();
		$title = Title::newFromText( $params['page'] );
		$result = null;

		if ( !$title ) {
			$this->dieWithError( [ 'apierror-invalidtitle', wfEscapeWikiText( $params['page'] ) ] );
		}
		$commentName = $params['commentname'];
		$subscribe = $params['subscribe'];

		if ( $subscribe ) {
			$success = $this->subscriptionStore->addSubscriptionForUser(
				$user,
				$title,
				$commentName
			);
			if ( !$success ) {
				$this->dieWithError( 'apierror-discussiontools-subscription-failed-add', 'subscription-failed' );
			}
		} else {
			$success = $this->subscriptionStore->removeSubscriptionForUser(
				$user,
				$commentName
			);
			if ( !$success ) {
				$this->dieWithError( 'apierror-discussiontools-subscription-failed-remove', 'subscription-failed' );
			}
		}

		$result = [
			'page' => $title,
			'commentname' => $commentName,
			'subscribe' => $subscribe,
		];

		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'page' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'token' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'commentname' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'subscribe' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	public function isWriteMode() {
		return true;
	}
}
