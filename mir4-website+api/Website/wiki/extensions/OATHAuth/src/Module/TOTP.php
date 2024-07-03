<?php

namespace MediaWiki\Extension\OATHAuth\Module;

use IContextSource;
use MediaWiki\Extension\OATHAuth\Auth\TOTPSecondaryAuthenticationProvider;
use MediaWiki\Extension\OATHAuth\HTMLForm\IManageForm;
use MediaWiki\Extension\OATHAuth\HTMLForm\TOTPDisableForm;
use MediaWiki\Extension\OATHAuth\HTMLForm\TOTPEnableForm;
use MediaWiki\Extension\OATHAuth\IModule;
use MediaWiki\Extension\OATHAuth\Key\TOTPKey;
use MediaWiki\Extension\OATHAuth\OATHUser;
use MediaWiki\Extension\OATHAuth\OATHUserRepository;
use MediaWiki\Extension\OATHAuth\Special\OATHManage;
use MWException;

class TOTP implements IModule {
	public static function factory() {
		return new static();
	}

	/** @inheritDoc */
	public function getName() {
		return "totp";
	}

	/** @inheritDoc */
	public function getDisplayName() {
		return wfMessage( 'oathauth-module-totp-label' );
	}

	/**
	 * @inheritDoc
	 * @throws MWException
	 */
	public function newKey( array $data ) {
		if ( !isset( $data['secret'] ) || !isset( $data['scratch_tokens'] ) ) {
			throw new MWException( 'oathauth-invalid-data-format' );
		}
		if ( is_string( $data['scratch_tokens' ] ) ) {
			$data['scratch_tokens'] = explode( ',', $data['scratch_tokens'] );
		}

		return TOTPKey::newFromArray( $data );
	}

	/**
	 * @return TOTPSecondaryAuthenticationProvider
	 */
	public function getSecondaryAuthProvider() {
		return new TOTPSecondaryAuthenticationProvider();
	}

	/**
	 * @param OATHUser $user
	 * @param array $data
	 * @return bool
	 * @throws MWException
	 */
	public function verify( OATHUser $user, array $data ): bool {
		if ( !isset( $data['token'] ) ) {
			return false;
		}

		foreach ( $user->getKeys() as $key ) {
			if ( $key instanceof TOTPKey && $key->verify( $data, $user ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Is this module currently enabled for the given user?
	 *
	 * @param OATHUser $user
	 * @return bool
	 */
	public function isEnabled( OATHUser $user ): bool {
		foreach ( $user->getKeys() as $key ) {
			if ( $key instanceof TOTPKey ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $action
	 * @param OATHUser $user
	 * @param OATHUserRepository $repo
	 * @param IContextSource $context
	 * @return IManageForm|null
	 */
	public function getManageForm(
		$action,
		OATHUser $user,
		OATHUserRepository $repo,
		IContextSource $context
	): ?IManageForm {
		$hasTOTPKey = $this->isEnabled( $user );
		if ( $action === OATHManage::ACTION_ENABLE && !$hasTOTPKey ) {
			return new TOTPEnableForm( $user, $repo, $this, $context );
		}
		if ( $action === OATHManage::ACTION_DISABLE && $hasTOTPKey ) {
			return new TOTPDisableForm( $user, $repo, $this, $context );
		}
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getDescriptionMessage() {
		return wfMessage( 'oathauth-totp-description' );
	}

	/**
	 * @inheritDoc
	 */
	public function getDisableWarningMessage() {
		return wfMessage( 'oathauth-totp-disable-warning' );
	}
}
