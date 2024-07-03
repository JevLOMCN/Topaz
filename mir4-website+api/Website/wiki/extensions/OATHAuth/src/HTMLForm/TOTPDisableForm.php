<?php

namespace MediaWiki\Extension\OATHAuth\HTMLForm;

use MediaWiki\Extension\OATHAuth\Key\TOTPKey;
use MediaWiki\Logger\LoggerFactory;
use Message;
use MWException;

class TOTPDisableForm extends OATHAuthOOUIHTMLForm {
	/**
	 * Add content to output when operation was successful
	 */
	public function onSuccess() {
		$this->getOutput()->addWikiMsg( 'oathauth-disabledoath' );
	}

	/**
	 * @return array
	 */
	protected function getDescriptors() {
		return [
			'token' => [
				'type' => 'text',
				'label-message' => 'oathauth-entertoken',
				'name' => 'token',
				'required' => true,
				'autofocus' => true,
				'dir' => 'ltr',
				'autocomplete' => 'one-time-code',
				'spellcheck' => false,
				'help' => $this->msg( 'oathauth-hint' )->parse(),
			],
		];
	}

	/**
	 * @param array $formData
	 * @return array|bool
	 * @throws MWException
	 */
	public function onSubmit( array $formData ) {
		// Don't increase pingLimiter, instead check for the limit being exceeded.
		if ( $this->oathUser->getUser()->pingLimiter( 'badoath', 0 ) ) {
			// Arbitrary duration given here
			LoggerFactory::getInstance( 'authentication' )->info(
				'OATHAuth {user} rate limited while disabling 2FA from {clientip}', [
					'user' => $this->getUser()->getName(),
					'clientip' => $this->getRequest()->getIP(),
				]
			);
			return [ 'oathauth-throttled', Message::durationParam( 60 ) ];
		}

		foreach ( $this->oathUser->getKeys() as $key ) {
			if ( !( $key instanceof TOTPKey ) ) {
				continue;
			}

			if ( !$key->verify( [ 'token' => $formData['token'] ], $this->oathUser ) ) {
				continue;
			}

			$this->oathRepo->removeKey(
				$this->oathUser,
				$key,
				$this->getRequest()->getIP(),
				true
			);

			return true;
		}

		LoggerFactory::getInstance( 'authentication' )->info(
			'OATHAuth {user} failed to provide a correct token while disabling 2FA from {clientip}', [
				'user' => $this->getUser()->getName(),
				'clientip' => $this->getRequest()->getIP(),
			]
		);

		// Increase rate limit counter for failed request
		$this->getUser()->pingLimiter( 'badoath' );

		return [ 'oathauth-failedtovalidateoath' ];
	}
}
