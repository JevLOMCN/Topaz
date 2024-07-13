<?php

namespace MediaWiki\Extension\OATHAuth\Hook;

use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Config\Config;
use MediaWiki\Extension\OATHAuth\OATHAuth;
use MediaWiki\Extension\OATHAuth\OATHUserRepository;
use MediaWiki\Permissions\Hook\GetUserPermissionsErrorsHook;
use MediaWiki\Permissions\Hook\UserGetRightsHook;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\SpecialPage\Hook\AuthChangeFormFieldsHook;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\Hook\UserEffectiveGroupsHook;
use MediaWiki\User\User;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserGroupMembership;
use Message;
use OOUI\ButtonWidget;
use OOUI\HorizontalLayout;
use OOUI\LabelWidget;
use RequestContext;

class HookHandler implements
	AuthChangeFormFieldsHook,
	GetPreferencesHook,
	getUserPermissionsErrorsHook,
	UserEffectiveGroupsHook,
	UserGetRightsHook
{
	/**
	 * @var OATHUserRepository
	 */
	private $userRepo;

	/**
	 * @var PermissionManager
	 */
	private $permissionManager;

	/**
	 * @var UserGroupManager
	 */
	private $userGroupManager;

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @param OATHUserRepository $userRepo
	 * @param PermissionManager $permissionManager
	 * @param Config $config
	 * @param UserGroupManager $userGroupManager
	 */
	public function __construct( $userRepo, $permissionManager, $config, $userGroupManager ) {
		$this->userRepo = $userRepo;
		$this->permissionManager = $permissionManager;
		$this->config = $config;
		$this->userGroupManager = $userGroupManager;
	}

	/**
	 * @param AuthenticationRequest[] $requests
	 * @param array $fieldInfo
	 * @param array &$formDescriptor
	 * @param string $action
	 *
	 * @return bool
	 */
	public function onAuthChangeFormFields( $requests, $fieldInfo, &$formDescriptor, $action ) {
		if ( !isset( $fieldInfo['OATHToken'] ) ) {
			return true;
		}

		$formDescriptor['OATHToken'] += [
			'cssClass' => 'loginText',
			'id' => 'wpOATHToken',
			'size' => 20,
			'dir' => 'ltr',
			'autofocus' => true,
			'persistent' => false,
			'autocomplete' => 'one-time-code',
			'spellcheck' => false,
		];
		return true;
	}

	/**
	 * @param User $user
	 * @param array &$preferences
	 *
	 * @return bool
	 */
	public function onGetPreferences( $user, &$preferences ) {
		$oathUser = $this->userRepo->findByUser( $user );

		// If there is no existing module for the user, and the user is not allowed to enable it,
		// we have nothing to show.
		if (
			!$oathUser->isTwoFactorAuthEnabled() &&
			!$this->permissionManager->userHasRight( $user, 'oathauth-enable' )
		) {
			return true;
		}

		$module = $oathUser->getModule();

		$moduleLabel = $module === null ?
			wfMessage( 'oathauth-ui-no-module' ) :
			$module->getDisplayName();

		$manageButton = new ButtonWidget( [
			'href' => SpecialPage::getTitleFor( 'OATHManage' )->getLocalURL(),
			'label' => wfMessage( 'oathauth-ui-manage' )->text()
		] );

		$currentModuleLabel = new LabelWidget( [
			'label' => $moduleLabel->text()
		] );

		$control = new HorizontalLayout( [
			'items' => [
				$currentModuleLabel,
				$manageButton
			]
		] );

		$preferences['oathauth-module'] = [
			'type' => 'info',
			'raw' => true,
			'default' => (string)$control,
			'label-message' => 'oathauth-prefs-label',
			'section' => 'personal/info',
		];

		$dbGroups = $this->userGroupManager->getUserGroups( $user );
		$disabledGroups = $this->getDisabledGroups( $user, $dbGroups );
		if ( !$oathUser->isTwoFactorAuthEnabled() && $disabledGroups ) {
			$context = RequestContext::getMain();
			$list = [];
			foreach ( $disabledGroups as $disabledGroup ) {
				$list[] = UserGroupMembership::getLinkHTML( $disabledGroup, $context );
			}
			$info = $context->getLanguage()->commaList( $list );
			$disabledInfo = [ 'oathauth-disabledgroups' => [
				'type' => 'info',
				'label-message' => [ 'oathauth-prefs-disabledgroups',
					Message::numParam( count( $disabledGroups ) ) ],
				'help-message' => [ 'oathauth-prefs-disabledgroups-help',
					Message::numParam( count( $disabledGroups ) ), $user->getName() ],
				'default' => $info,
				'raw' => true,
				'section' => 'personal/info',
			] ];
			// Insert right after "Member of groups"
			$preferences = wfArrayInsertAfter( $preferences, $disabledInfo, 'usergroups' );
		}

		return true;
	}

	/**
	 * Return the groups that this user is supposed to be in, but are disabled
	 * because 2FA isn't enabled
	 *
	 * @param User $user
	 * @param string[] $groups All groups the user is supposed to be in
	 * @return string[] Groups the user should be disabled in
	 */
	private function getDisabledGroups( User $user, array $groups ): array {
		$requiredGroups = $this->config->get( 'OATHRequiredForGroups' );
		// Bail early if:
		// * No configured restricted groups
		// * The user is not in any of the restricted groups
		$intersect = array_intersect( $groups, $requiredGroups );
		if ( !$requiredGroups || !$intersect ) {
			return [];
		}

		$oathUser = $this->userRepo->findByUser( $user );
		if ( !$oathUser->isTwoFactorAuthEnabled() ) {
			// Not enabled, strip the groups
			return $intersect;
		}

		return [];
	}

	/**
	 * Remove groups if 2FA is required for them and it's not enabled
	 *
	 * @param User $user User to get groups for
	 * @param string[] &$groups Current effective groups
	 */
	public function onUserEffectiveGroups( $user, &$groups ) {
		$disabledGroups = $this->getDisabledGroups( $user, $groups );
		if ( $disabledGroups ) {
			$groups = array_diff( $groups, $disabledGroups );
		}
	}

	/**
	 * @param Title $title
	 * @param User $user
	 * @param string $action
	 * @param string &$result
	 *
	 * @return bool
	 */
	public function onGetUserPermissionsErrors( $title, $user, $action, &$result ) {
		if ( !$this->config->has( 'OATHExclusiveRights' ) ) {
			return true;
		}

		// TODO: Get the session from somewhere more... sane?
		$session = $user->getRequest()->getSession();
		if (
			!(bool)$session->get( OATHAuth::AUTHENTICATED_OVER_2FA, false ) &&
			in_array( $action, $this->config->get( 'OATHExclusiveRights' ) )
		) {
			$result = 'oathauth-action-exclusive-to-2fa';
			return false;
		}
		return true;
	}

	/**
	 * If a user has groups disabled for not having 2FA enabled, make sure they
	 * have "oathauth-enable" so they can turn it on
	 *
	 * @param User $user User to get rights for
	 * @param string[] &$rights Current rights
	 */
	public function onUserGetRights( $user, &$rights ) {
		if ( in_array( 'oathauth-enable', $rights ) ) {
			return;
		}

		$dbGroups = $this->userGroupManager->getUserGroups( $user );
		if ( $this->getDisabledGroups( $user, $dbGroups ) ) {
			// User has some disabled groups, add oathauth-enable
			$rights[] = 'oathauth-enable';
		}
	}
}
