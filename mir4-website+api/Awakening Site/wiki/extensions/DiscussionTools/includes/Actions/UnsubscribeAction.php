<?php

namespace MediaWiki\Extension\DiscussionTools\Actions;

use Article;
use ErrorPageError;
use FormAction;
use HTMLForm;
use IContextSource;
use MediaWiki\Extension\DiscussionTools\SubscriptionItem;
use MediaWiki\Extension\DiscussionTools\SubscriptionStore;
use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use UserNotLoggedIn;

class UnsubscribeAction extends FormAction {

	protected SubscriptionStore $subscriptionStore;
	protected ?SubscriptionItem $subscriptionItem = null;

	public function __construct(
		Article $page,
		IContextSource $context,
		SubscriptionStore $subscriptionStore
	) {
		parent::__construct( $page, $context );
		$this->subscriptionStore = $subscriptionStore;
	}

	/**
	 * @inheritDoc
	 */
	protected function getPageTitle() {
		if ( $this->subscriptionItem && !str_starts_with( $this->subscriptionItem->getItemName(), 'p-topics-' ) ) {
			$title = Title::newFromLinkTarget( $this->subscriptionItem->getLinkTarget() );
			return $this->msg( 'discussiontools-topicsubscription-action-title' )
				->plaintextParams( $title->getPrefixedText(), $title->getFragment() );
		} else {
			return parent::getPageTitle();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function show() {
		$commentName = $this->getRequest()->getVal( 'commentname' );

		if ( $commentName ) {
			$subscriptionItems = $this->subscriptionStore->getSubscriptionItemsForUser(
				$this->getUser(),
				[ $commentName ]
				// We could check the user is still subscribed, but then we'd need more error messages
			);

			if ( count( $subscriptionItems ) > 0 ) {
				$this->subscriptionItem = $subscriptionItems[ 0 ];
			}
		}

		parent::show();
	}

	/**
	 * @inheritDoc
	 */
	public function getName() {
		return 'dtunsubscribe';
	}

	/**
	 * @inheritDoc
	 */
	public function requiresUnblock() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	protected function getDescription() {
		return '';
	}

	/**
	 * @inheritDoc
	 */
	protected function getFormFields() {
		if ( $this->subscriptionItem ) {
			return [
				'commentname' => [
					'name' => 'commentname',
					'type' => 'hidden',
					'default' => $this->getRequest()->getVal( 'commentname' ),
				],
				'intro' => [
					'type' => 'info',
					'raw' => true,
					'default' => $this->msg( str_starts_with( $this->subscriptionItem->getItemName(), 'p-topics-' ) ?
						'discussiontools-topicsubscription-action-unsubscribe-prompt-newtopics' :
						'discussiontools-topicsubscription-action-unsubscribe-prompt' )->parse(),
				],
			];
		} else {
			return [];
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function alterForm( HTMLForm $form ) {
		$form->setSubmitTextMsg( 'discussiontools-topicsubscription-action-unsubscribe-button' );
	}

	/**
	 * @inheritDoc
	 */
	public function onSubmit( $data ) {
		$commentName = $this->getRequest()->getVal( 'commentname' );

		return $this->subscriptionStore->removeSubscriptionForUser(
			$this->getUser(),
			$commentName
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onSuccess() {
		$this->getOutput()->addHTML(
			Html::element(
				'p',
				[],
				$this->msg( str_starts_with( $this->subscriptionItem->getItemName(), 'p-topics-' ) ?
					'discussiontools-newtopicssubscription-notify-unsubscribed-body' :
					'discussiontools-topicsubscription-notify-unsubscribed-body' )->text()
			)
		);
		$this->getOutput()->addReturnTo( $this->subscriptionItem->getLinkTarget() );
		$this->getOutput()->addReturnTo( SpecialPage::getTitleFor( 'TopicSubscriptions' ) );
	}

	/**
	 * @inheritDoc
	 */
	protected function usesOOUI() {
		return true;
	}

	/**
	 * @inheritDoc
	 * @throws ErrorPageError
	 */
	protected function checkCanExecute( User $user ) {
		// Must be logged in
		if ( !$user->isNamed() ) {
			throw new UserNotLoggedIn();
		}

		if ( !$this->subscriptionItem ) {
			throw new ErrorPageError(
				'discussiontools-topicsubscription-error-not-found-title',
				'discussiontools-topicsubscription-error-not-found-body'
			);
		}

		parent::checkCanExecute( $user );
	}
}
