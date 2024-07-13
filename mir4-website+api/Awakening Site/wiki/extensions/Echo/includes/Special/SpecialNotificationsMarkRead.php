<?php

namespace MediaWiki\Extension\Notifications\Special;

use HTMLForm;
use MediaWiki\Extension\Notifications\NotifUser;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\SpecialPage\SpecialPage;

/**
 * Form for marking notifications as read by ID.
 *
 * This uses the normal HTMLForm handling when receiving POSTs.
 * However, for a better user no-JS user experience, we integrate
 * a version of the form into Special:Notifications.  Thus, this
 * page should normally not need to be visited directly.
 */
class SpecialNotificationsMarkRead extends FormSpecialPage {
	public function __construct() {
		parent::__construct( 'NotificationsMarkRead' );
	}

	public function doesWrites() {
		return true;
	}

	public function execute( $par ) {
		parent::execute( $par );

		$out = $this->getOutput();
		$out->setPageTitleMsg( $this->msg( 'echo-specialpage-markasread' ) );

		// Redirect to login page and inform user of the need to login
		$this->requireLogin( 'echo-notification-loginrequired' );
	}

	public function isListed() {
		return false;
	}

	public function getDisplayFormat() {
		return 'ooui';
	}

	/**
	 * Get an HTMLForm descriptor array
	 * @return array[]
	 */
	protected function getFormFields() {
		return [
			'id' => [
				'type' => 'hidden',
				'required' => true,
				'default' => $this->par,
				'filter-callback' => static function ( $value, $alldata ) {
					// Allow for a single value or a set of values
					return explode( ',', $value );
				},
				'validation-callback' => function ( $value, $alldata ) {
					if ( $value === [ 'ALL' ] ) {
						return true;
					}
					if ( (int)$value <= 0 ) {
						return $this->msg( 'echo-specialpage-markasread-invalid-id' );
					}
					foreach ( $value as $val ) {
						if ( (int)( $val ) <= 0 ) {
							return $this->msg( 'echo-specialpage-markasread-invalid-id' );
						}
					}
					return true;
				}
			]
		];
	}

	/**
	 * Gets a pre-filled version of the form; this should not have a legend or anything
	 *   visible, except the button.
	 *
	 * @param int|array $idValue ID or array of IDs
	 * @param string $submitButtonValue Value attribute for button
	 * @param bool $framed Whether the button should be framed
	 * @param string $submitLabelHtml Raw HTML to use for button label
	 *
	 * @return HTMLForm
	 */
	public function getMinimalForm( $idValue, $submitButtonValue, $framed, $submitLabelHtml ) {
		if ( !is_array( $idValue ) ) {
			$idValue = [ $idValue ];
		}

		$idString = implode( ',', $idValue );

		$this->setParameter( $idString );

		$form = HTMLForm::factory(
			$this->getDisplayFormat(),
			$this->getFormFields(),
			$this->getContext(),
			$this->getMessagePrefix()
		);

		// HTMLForm assumes that the main submit button is always 'primary',
		// which means it is colored.  Since this form is being embedded multiple
		// places on the page, it has to be neutral, so we make the button
		// manually.
		$form->suppressDefaultSubmit();
		$form->setTitle( $this->getPageTitle() );

		$form->addButton( [
			'name' => 'submit',
			'value' => $submitButtonValue,
			'label-raw' => $submitLabelHtml,
			'framed' => $framed,
		] );

		return $form;
	}

	/**
	 * Sets a custom label
	 *
	 * This is only called when the form is actually visited directly, which is not the
	 *   main intended use.
	 * @param HTMLForm $form
	 */
	protected function alterForm( HTMLForm $form ) {
		$form->setSubmitText( $this->msg( 'echo-notification-markasread' )->text() );
	}

	/**
	 * Process the form on POST submission.
	 * @param array $data
	 * @return bool
	 */
	public function onSubmit( array $data ) {
		$notifUser = NotifUser::newFromUser( $this->getUser() );

		// Allow for all IDs
		if ( $data['id'] === [ 'ALL' ] ) {
			return $notifUser->markAllRead();
		}

		// Allow for multiple IDs or a single ID
		$ids = $data['id'];
		return $notifUser->markRead( $ids );
	}

	public function onSuccess() {
		$page = SpecialPage::getTitleFor( 'Notifications' );
		$this->getOutput()->redirect( $page->getFullURL() );
	}
}
