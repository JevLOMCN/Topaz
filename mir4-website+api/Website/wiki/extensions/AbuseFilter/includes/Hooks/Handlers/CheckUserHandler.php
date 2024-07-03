<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks\Handlers;

use MediaWiki\CheckUser\Hook\CheckUserInsertChangesRowHook;
use MediaWiki\CheckUser\Hook\CheckUserInsertLogEventRowHook;
use MediaWiki\CheckUser\Hook\CheckUserInsertPrivateEventRowHook;
use MediaWiki\Extension\AbuseFilter\FilterUser;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityUtils;
use RecentChange;

class CheckUserHandler implements
	CheckUserInsertChangesRowHook,
	CheckUserInsertPrivateEventRowHook,
	CheckUserInsertLogEventRowHook
{

	/** @var FilterUser */
	private $filterUser;

	/** @var UserIdentityUtils */
	private $userIdentityUtils;

	/**
	 * @param FilterUser $filterUser
	 * @param UserIdentityUtils $userIdentityUtils
	 */
	public function __construct(
		FilterUser $filterUser,
		UserIdentityUtils $userIdentityUtils
	) {
		$this->filterUser = $filterUser;
		$this->userIdentityUtils = $userIdentityUtils;
	}

	/**
	 * Any edits by the filter user should always be marked as by the software
	 * using IP 127.0.0.1, no XFF and no UA.
	 *
	 * @inheritDoc
	 */
	public function onCheckUserInsertChangesRow(
		string &$ip, &$xff, array &$row, UserIdentity $user, ?RecentChange $rc
	) {
		if (
			$this->userIdentityUtils->isNamed( $user ) &&
			$this->filterUser->isSameUserAs( $user )
		) {
			$ip = '127.0.0.1';
			$xff = false;
			$row['cuc_agent'] = '';
		}
	}

	/**
	 * Any log actions by the filter user should always be marked as by the software
	 * using IP 127.0.0.1, no XFF and no UA.
	 *
	 * @inheritDoc
	 */
	public function onCheckUserInsertLogEventRow(
		string &$ip, &$xff, array &$row, UserIdentity $user, int $id, ?RecentChange $rc
	) {
		if (
			$this->userIdentityUtils->isNamed( $user ) &&
			$this->filterUser->isSameUserAs( $user )
		) {
			$ip = '127.0.0.1';
			$xff = false;
			$row['cule_agent'] = '';
		}
	}

	/**
	 * Any log actions by the filter user should always be marked as by the software
	 * using IP 127.0.0.1, no XFF and no UA.
	 *
	 * @inheritDoc
	 */
	public function onCheckUserInsertPrivateEventRow(
		string &$ip, &$xff, array &$row, UserIdentity $user, ?RecentChange $rc
	) {
		if (
			$this->userIdentityUtils->isNamed( $user ) &&
			$this->filterUser->isSameUserAs( $user )
		) {
			$ip = '127.0.0.1';
			$xff = false;
			$row['cupe_agent'] = '';
		}
	}
}
