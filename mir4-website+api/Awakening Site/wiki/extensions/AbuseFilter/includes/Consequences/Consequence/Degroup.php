<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences\Consequence;

use ManualLogEntry;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\AbuseFilter\FilterUser;
use MediaWiki\Extension\AbuseFilter\GlobalNameUtils;
use MediaWiki\Extension\AbuseFilter\Variables\LazyLoadedVariable;
use MediaWiki\Extension\AbuseFilter\Variables\UnsetVariableException;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityUtils;
use MessageLocalizer;

/**
 * Consequence that removes all user groups from a user.
 */
class Degroup extends Consequence implements HookAborterConsequence, ReversibleConsequence {
	/**
	 * @var VariableHolder
	 * @todo This dependency is subpar
	 */
	private $vars;

	/** @var UserGroupManager */
	private $userGroupManager;

	/** @var UserIdentityUtils */
	private $userIdentityUtils;

	/** @var FilterUser */
	private $filterUser;

	/** @var MessageLocalizer */
	private $messageLocalizer;

	/**
	 * @param Parameters $params
	 * @param VariableHolder $vars
	 * @param UserGroupManager $userGroupManager
	 * @param UserIdentityUtils $userIdentityUtils
	 * @param FilterUser $filterUser
	 * @param MessageLocalizer $messageLocalizer
	 */
	public function __construct(
		Parameters $params,
		VariableHolder $vars,
		UserGroupManager $userGroupManager,
		UserIdentityUtils $userIdentityUtils,
		FilterUser $filterUser,
		MessageLocalizer $messageLocalizer
	) {
		parent::__construct( $params );
		$this->vars = $vars;
		$this->userGroupManager = $userGroupManager;
		$this->userIdentityUtils = $userIdentityUtils;
		$this->filterUser = $filterUser;
		$this->messageLocalizer = $messageLocalizer;
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): bool {
		$user = $this->parameters->getUser();

		if ( !$this->userIdentityUtils->isNamed( $user ) ) {
			return false;
		}

		// Pull the groups from the VariableHolder, so that they will always be computed.
		// This allow us to pull the groups from the VariableHolder to undo the degroup
		// via Special:AbuseFilter/revert.
		try {
			// No point in triggering a lazy-load, instead we compute it here if necessary
			$groupsVar = $this->vars->getVarThrow( 'user_groups' );
		} catch ( UnsetVariableException $_ ) {
			$groupsVar = null;
		}
		if ( $groupsVar === null || $groupsVar instanceof LazyLoadedVariable ) {
			// The variable is unset or not computed. Compute it and update the holder so we can use it for reverts
			$groups = $this->userGroupManager->getUserEffectiveGroups( $user );
			$this->vars->setVar( 'user_groups', $groups );
		} else {
			$groups = $groupsVar->toNative();
		}

		$implicitGroups = $this->userGroupManager->listAllImplicitGroups();
		$removeGroups = array_diff( $groups, $implicitGroups );
		if ( !count( $removeGroups ) ) {
			return false;
		}

		foreach ( $removeGroups as $group ) {
			$this->userGroupManager->removeUserFromGroup( $user, $group );
		}

		// TODO Core should provide a logging method
		$logEntry = new ManualLogEntry( 'rights', 'rights' );
		$logEntry->setPerformer( $this->filterUser->getUserIdentity() );
		$logEntry->setTarget( new TitleValue( NS_USER, $user->getName() ) );
		$logEntry->setComment(
			$this->messageLocalizer->msg(
				'abusefilter-degroupreason',
				$this->parameters->getFilter()->getName(),
				$this->parameters->getFilter()->getID()
			)->inContentLanguage()->text()
		);
		$logEntry->setParameters( [
			'4::oldgroups' => $removeGroups,
			'5::newgroups' => []
		] );
		$logEntry->publish( $logEntry->insert() );
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function revert( UserIdentity $performer, string $reason ): bool {
		$user = $this->parameters->getUser();
		$currentGroups = $this->userGroupManager->getUserGroups( $user );
		// Pull the user's original groups from the vars. This is guaranteed to be set, because we
		// enforce it when performing a degroup.
		$removedGroups = $this->vars->getComputedVariable( 'user_groups' )->toNative();
		$removedGroups = array_diff(
			$removedGroups,
			$this->userGroupManager->listAllImplicitGroups(),
			$currentGroups
		);

		$addedGroups = [];
		foreach ( $removedGroups as $group ) {
			// TODO An addUserToGroups method with bulk updates would be nice
			if ( $this->userGroupManager->addUserToGroup( $user, $group ) ) {
				$addedGroups[] = $group;
			}
		}

		// Don't log if no groups were added.
		if ( !$addedGroups ) {
			return false;
		}

		// TODO Core should provide a logging method
		$logEntry = new ManualLogEntry( 'rights', 'rights' );
		$logEntry->setTarget( new TitleValue( NS_USER, $user->getName() ) );
		$logEntry->setPerformer( $performer );
		$logEntry->setComment( $reason );
		$logEntry->setParameters( [
			'4::oldgroups' => $currentGroups,
			'5::newgroups' => array_merge( $currentGroups, $addedGroups )
		] );
		$logEntry->publish( $logEntry->insert() );

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getMessage(): array {
		$filter = $this->parameters->getFilter();
		return [
			'abusefilter-degrouped',
			$filter->getName(),
			GlobalNameUtils::buildGlobalName( $filter->getID(), $this->parameters->getIsGlobalFilter() )
		];
	}
}
