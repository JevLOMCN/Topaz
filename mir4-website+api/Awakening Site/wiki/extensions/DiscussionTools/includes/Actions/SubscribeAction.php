<?php

namespace MediaWiki\Extension\DiscussionTools\Actions;

use Article;
use ErrorPageError;
use FormAction;
use HTMLForm;
use IContextSource;
use MediaWiki\Extension\DiscussionTools\SubscriptionStore;
use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use UserNotLoggedIn;

class SubscribeAction extends FormAction {

	protected SubscriptionStore $subscriptionStore;
	protected ?Title $subscriptionTitle = null;
	protected ?string $subscriptionName = null;

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
		if ( $this->subscriptionTitle &&
			$this->subscriptionName !== null && !str_starts_with( $this->subscriptionName, 'p-topics-' )
		) {
			$title = $this->subscriptionTitle;
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
		$section = $this->getRequest()->getVal( 'section', '' );

		if ( $commentName !== null ) {
			$this->subscriptionTitle = $this->getTitle()->createFragmentTarget( $section );
			$this->subscriptionName = $commentName;
		}

		parent::show();
	}

	/**
	 * @inheritDoc
	 */
	public function getName() {
		return 'dtsubscribe';
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
		if ( $this->subscriptionTitle ) {
			return [
				'commentname' => [
					'name' => 'commentname',
					'type' => 'hidden',
					'default' => $this->getRequest()->getVal( 'commentname' ),
				],
				'section' => [
					'name' => 'section',
					'type' => 'hidden',
					'default' => $this->getRequest()->getVal( 'section', '' ),
				],
				'intro' => [
					'type' => 'info',
					'raw' => true,
					'default' => $this->msg( str_starts_with( $this->subscriptionName, 'p-topics-' ) ?
						'discussiontools-topicsubscription-action-subscribe-prompt-newtopics' :
						'discussiontools-topicsubscription-action-subscribe-prompt' )->parse(),
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
		$form->setSubmitTextMsg( 'discussiontools-topicsubscription-action-subscribe-button' );
	}

	/**
	 * @inheritDoc
	 */
	public function onSubmit( $data ) {
		return $this->subscriptionStore->addSubscriptionForUser(
			$this->getUser(),
			$this->subscriptionTitle,
			$this->subscriptionName
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
				$this->msg( str_starts_with( $this->subscriptionName, 'p-topics-' ) ?
					'discussiontools-newtopicssubscription-notify-subscribed-body' :
					'discussiontools-topicsubscription-notify-subscribed-body' )->text()
			)
		);
		$this->getOutput()->addReturnTo( $this->subscriptionTitle );
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

		if ( !$this->subscriptionTitle ) {
			throw new ErrorPageError(
				'discussiontools-topicsubscription-error-not-found-title',
				'discussiontools-topicsubscription-error-not-found-body'
			);
		}

		parent::checkCanExecute( $user );
	}
}
