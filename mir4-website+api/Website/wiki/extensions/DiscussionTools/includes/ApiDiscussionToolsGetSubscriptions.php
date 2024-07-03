<?php

namespace MediaWiki\Extension\DiscussionTools;

use ApiBase;
use ApiMain;
use ApiUsageException;
use Wikimedia\ParamValidator\ParamValidator;

class ApiDiscussionToolsGetSubscriptions extends ApiBase {

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
		$itemNames = $params['commentname'];
		$items = $this->subscriptionStore->getSubscriptionItemsForUser(
			$user,
			$itemNames
		);

		// Ensure consistent formatting in JSON and XML formats
		$this->getResult()->addIndexedTagName( 'subscriptions', 'subscription' );
		$this->getResult()->addArrayType( 'subscriptions', 'kvp', 'name' );

		foreach ( $items as $item ) {
			$this->getResult()->addValue( 'subscriptions', $item->getItemName(), $item->getState() );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'commentname' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_ISMULTI => true,
			],
		];
	}
}
