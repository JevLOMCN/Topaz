<?php

namespace MediaWiki\Extension\Thanks;

use ApiMain;
use ApiUsageException;
use HTMLForm;
use MediaWiki\Linker\Linker;
use MediaWiki\Request\DerivativeRequest;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\Status\Status;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserRigorOptions;

class SpecialThanks extends FormSpecialPage {

	/**
	 * API result
	 */
	protected array $result;

	/**
	 * 'rev' for revision, 'log' for log entry, or 'flow' for Flow comment,
	 * null if no ID is specified
	 */
	protected ?string $type;

	/**
	 * Revision or Log ID ('0' = invalid) or Flow UUID
	 */
	protected ?string $id;

	private UserFactory $userFactory;

	public function __construct( UserFactory $userFactory ) {
		parent::__construct( 'Thanks' );
		$this->userFactory = $userFactory;
		$this->id = null;
	}

	public function doesWrites(): bool {
		return true;
	}

	/**
	 * Set the type and ID or UUID of the request.
	 * @param string $par The subpage name.
	 */
	protected function setParameter( $par ) {
		if ( $par === null || $par === '' ) {
			$this->type = null;
			return;
		}

		$tokens = explode( '/', $par );
		if ( $tokens[0] === 'Flow' ) {
			if ( count( $tokens ) === 1 || $tokens[1] === '' ) {
				$this->type = null;
				return;
			}
			$this->type = 'flow';
			$this->id = $tokens[1];
			return;
		}

		if ( strtolower( $tokens[0] ) === 'log' ) {
			$this->type = 'log';
			// Make sure there's a numeric ID specified as the subpage.
			if ( count( $tokens ) === 1 || $tokens[1] === '' || !( ctype_digit( $tokens[1] ) ) ) {
				$this->id = '0';
				return;
			}
			$this->id = $tokens[1];
			return;
		}

		$this->type = 'rev';
		if ( !( ctype_digit( $par ) ) ) { // Revision ID is not an integer.
			$this->id = '0';
			return;
		}

		$this->id = $par;
	}

	/**
	 * HTMLForm fields
	 * @return string[][]
	 */
	protected function getFormFields(): array {
		return [
			'id' => [
				'id' => 'mw-thanks-form-id',
				'name' => 'id',
				'type' => 'hidden',
				'default' => $this->id ?? ''
			],
			'type' => [
				'id' => 'mw-thanks-form-type',
				'name' => 'type',
				'type' => 'hidden',
				'default' => $this->type ?? '',
			],
		];
	}

	/**
	 * Return the confirmation or error message.
	 */
	protected function preHtml(): string {
		if ( $this->type === null ) {
			$msgKey = 'thanks-error-no-id-specified';
		} elseif ( $this->type === 'rev' && $this->id === '0' ) {
			$msgKey = 'thanks-error-invalidrevision';
		} elseif ( $this->type === 'log' && $this->id === '0' ) {
			$msgKey = 'thanks-error-invalid-log-id';
		} elseif ( $this->type === 'flow' ) {
			$msgKey = 'flow-thanks-confirmation-special';
		} else {
			// The following messages are used here
			// * thanks-confirmation-special-rev
			// * thanks-confirmation-special-log
			$msgKey = 'thanks-confirmation-special-' . $this->type;
		}
		return '<p>' . $this->msg( $msgKey )->escaped() . '</p>';
	}

	/**
	 * Format the submission form.
	 * @param HTMLForm $form The form object to modify.
	 */
	protected function alterForm( HTMLForm $form ) {
		if ( $this->type === null
			|| ( in_array( $this->type, [ 'rev', 'log', ] ) && $this->id === '0' )
		) {
			$form->suppressDefaultSubmit( true );
		} else {
			$form->setSubmitText( $this->msg( 'thanks-submit' )->escaped() );
		}
	}

	protected function getDisplayFormat(): string {
		return 'ooui';
	}

	/**
	 * Call the API internally.
	 * @param string[] $data The form data.
	 */
	public function onSubmit( array $data ): Status {
		if ( !isset( $data['id'] ) ) {
			return Status::newFatal( 'thanks-error-invalidrevision' );
		}

		if ( in_array( $this->type, [ 'rev', 'log' ] ) ) {
			$requestData = [
				'action' => 'thank',
				$this->type => (int)$data['id'],
				'source' => 'specialpage',
				'token' => $this->getOutput()->getCsrfTokenSet()->getToken(),
			];
		} else {
			$requestData = [
				'action' => 'flowthank',
				'postid' => $data['id'],
				'token' => $this->getOutput()->getCsrfTokenSet()->getToken(),
			];
		}

		$request = new DerivativeRequest(
			$this->getRequest(),
			$requestData,
			true // posted
		);

		$api = new ApiMain(
			$request,
			true // enable write mode
		);

		try {
			$api->execute();
		} catch ( ApiUsageException $e ) {
			return Status::wrap( $e->getStatusValue() );
		}

		$this->result = $api->getResult()->getResultData( [ 'result' ] );
		return Status::newGood();
	}

	/**
	 * Display a message to the user.
	 */
	public function onSuccess() {
		$sender = $this->getUser();
		$recipient = $this->userFactory->newFromName( $this->result['recipient'], UserRigorOptions::RIGOR_NONE );
		$link = Linker::userLink( $recipient->getId(), $recipient->getName() );

		if ( in_array( $this->type, [ 'rev', 'log' ] ) ) {
			$msgKey = 'thanks-thanked-notice';
		} else {
			$msgKey = 'flow-thanks-thanked-notice';
		}
		$msg = $this->msg( $msgKey )
			->rawParams( $link )
			->params( $recipient->getName(), $sender->getName() );
		$this->getOutput()->addHTML( $msg->parse() );
	}

	public function isListed(): bool {
		return false;
	}
}
