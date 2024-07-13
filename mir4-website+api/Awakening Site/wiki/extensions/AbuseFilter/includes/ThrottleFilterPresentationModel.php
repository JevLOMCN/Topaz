<?php

namespace MediaWiki\Extension\AbuseFilter;

use EchoEventPresentationModel;
use Message;

class ThrottleFilterPresentationModel extends EchoEventPresentationModel {

	/**
	 * @inheritDoc
	 */
	public function getIconType() {
		return 'placeholder';
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderMessage() {
		$text = $this->event->getTitle()->getText();
		[ , $filter ] = explode( '/', $text, 2 );
		$disabledActions = $this->event->getExtraParam( 'throttled-actions' );
		if ( $disabledActions === null ) {
			// BC for when we didn't include the actions here.
			return $this->msg( 'notification-header-throttle-filter' )
				->params( $this->getViewingUserForGender() )
				->numParams( $filter );
		}
		if ( $disabledActions ) {
			$specsFormatter = AbuseFilterServices::getSpecsFormatter();
			$specsFormatter->setMessageLocalizer( $this );
			$disabledActionsLocalized = [];
			foreach ( $disabledActions as $action ) {
				$disabledActionsLocalized[] = $specsFormatter->getActionMessage( $action )->text();
			}
			return $this->msg( 'notification-header-throttle-filter-actions' )
				->params( $this->getViewingUserForGender() )
				->numParams( $filter )
				->params( Message::listParam( $disabledActionsLocalized ) )
				->params( count( $disabledActionsLocalized ) );
		}
		return $this->msg( 'notification-header-throttle-filter-no-actions' )
			->params( $this->getViewingUserForGender() )
			->numParams( $filter );
	}

	/**
	 * @inheritDoc
	 */
	public function getSubjectMessage() {
		return $this->msg( 'notification-subject-throttle-filter' )
			->params( $this->getViewingUserForGender() );
	}

	/**
	 * @inheritDoc
	 */
	public function getPrimaryLink() {
		return [
			'url' => $this->event->getTitle()->getFullURL(),
			'label' => $this->msg( 'notification-link-text-show-filter' )->text()
		];
	}
}
