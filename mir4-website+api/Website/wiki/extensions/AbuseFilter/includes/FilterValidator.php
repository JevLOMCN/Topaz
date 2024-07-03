<?php

namespace MediaWiki\Extension\AbuseFilter;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagValidator;
use MediaWiki\Extension\AbuseFilter\Filter\AbstractFilter;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\UserVisibleException;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\Status\Status;
use Message;

/**
 * This class validates filters, e.g. before saving.
 */
class FilterValidator {
	public const SERVICE_NAME = 'AbuseFilterFilterValidator';

	public const CONSTRUCTOR_OPTIONS = [
		'AbuseFilterValidGroups',
		'AbuseFilterActionRestrictions'
	];

	/** @var ChangeTagValidator */
	private $changeTagValidator;

	/** @var RuleCheckerFactory */
	private $ruleCheckerFactory;

	/** @var AbuseFilterPermissionManager */
	private $permManager;

	/** @var string[] */
	private $restrictedActions;

	/** @var string[] */
	private $validGroups;

	/**
	 * @param ChangeTagValidator $changeTagValidator
	 * @param RuleCheckerFactory $ruleCheckerFactory
	 * @param AbuseFilterPermissionManager $permManager
	 * @param ServiceOptions $options
	 */
	public function __construct(
		ChangeTagValidator $changeTagValidator,
		RuleCheckerFactory $ruleCheckerFactory,
		AbuseFilterPermissionManager $permManager,
		ServiceOptions $options
	) {
		$this->changeTagValidator = $changeTagValidator;
		$this->ruleCheckerFactory = $ruleCheckerFactory;
		$this->permManager = $permManager;
		$this->restrictedActions = array_keys( array_filter( $options->get( 'AbuseFilterActionRestrictions' ) ) );
		$this->validGroups = $options->get( 'AbuseFilterValidGroups' );
	}

	/**
	 * @param AbstractFilter $newFilter
	 * @param AbstractFilter $originalFilter
	 * @param Authority $performer
	 * @return Status
	 */
	public function checkAll(
		AbstractFilter $newFilter, AbstractFilter $originalFilter, Authority $performer
	): Status {
		// TODO We might consider not bailing at the first error, so we can show all errors at the first attempt

		$syntaxStatus = $this->checkValidSyntax( $newFilter );
		if ( !$syntaxStatus->isGood() ) {
			return $syntaxStatus;
		}

		$requiredFieldsStatus = $this->checkRequiredFields( $newFilter );
		if ( !$requiredFieldsStatus->isGood() ) {
			return $requiredFieldsStatus;
		}

		$conflictStatus = $this->checkConflictingFields( $newFilter );
		if ( !$conflictStatus->isGood() ) {
			return $conflictStatus;
		}

		$actions = $newFilter->getActions();
		if ( isset( $actions['tag'] ) ) {
			$validTagsStatus = $this->checkAllTags( $actions['tag'] );
			if ( !$validTagsStatus->isGood() ) {
				return $validTagsStatus;
			}
		}

		$messagesStatus = $this->checkEmptyMessages( $newFilter );
		if ( !$messagesStatus->isGood() ) {
			return $messagesStatus;
		}

		if ( isset( $actions['throttle'] ) ) {
			$throttleStatus = $this->checkThrottleParameters( $actions['throttle'] );
			if ( !$throttleStatus->isGood() ) {
				return $throttleStatus;
			}
		}

		$globalPermStatus = $this->checkGlobalFilterEditPermission( $performer, $newFilter, $originalFilter );
		if ( !$globalPermStatus->isGood() ) {
			return $globalPermStatus;
		}

		$globalFilterMsgStatus = $this->checkMessagesOnGlobalFilters( $newFilter );
		if ( !$globalFilterMsgStatus->isGood() ) {
			return $globalFilterMsgStatus;
		}

		$restrictedActionsStatus = $this->checkRestrictedActions( $performer, $newFilter, $originalFilter );
		if ( !$restrictedActionsStatus->isGood() ) {
			return $restrictedActionsStatus;
		}

		$filterGroupStatus = $this->checkGroup( $newFilter );
		if ( !$filterGroupStatus->isGood() ) {
			return $filterGroupStatus;
		}

		return Status::newGood();
	}

	/**
	 * @param AbstractFilter $filter
	 * @return Status
	 */
	public function checkValidSyntax( AbstractFilter $filter ): Status {
		$ret = Status::newGood();
		$ruleChecker = $this->ruleCheckerFactory->newRuleChecker();
		$syntaxStatus = $ruleChecker->checkSyntax( $filter->getRules() );
		if ( !$syntaxStatus->isValid() ) {
			$excep = $syntaxStatus->getException();
			$errMsg = $excep instanceof UserVisibleException
				? $excep->getMessageObj()
				: $excep->getMessage();
			$ret->error( 'abusefilter-edit-badsyntax', $errMsg );
		}
		return $ret;
	}

	/**
	 * @param AbstractFilter $filter
	 * @return Status
	 */
	public function checkRequiredFields( AbstractFilter $filter ): Status {
		$ret = Status::newGood();
		$missing = [];
		if ( $filter->getRules() === '' ) {
			$missing[] = new Message( 'abusefilter-edit-field-conditions' );
		}
		if ( trim( $filter->getName() ) === '' ) {
			$missing[] = new Message( 'abusefilter-edit-field-description' );
		}
		if ( count( $missing ) !== 0 ) {
			$ret->error(
				'abusefilter-edit-missingfields',
				Message::listParam( $missing, 'comma' )
			);
		}
		return $ret;
	}

	/**
	 * @param AbstractFilter $filter
	 * @return Status
	 */
	public function checkConflictingFields( AbstractFilter $filter ): Status {
		$ret = Status::newGood();
		// Don't allow setting as deleted an active filter
		if ( $filter->isEnabled() && $filter->isDeleted() ) {
			$ret->error( 'abusefilter-edit-deleting-enabled' );
		}
		return $ret;
	}

	/**
	 * @param string[] $tags
	 * @return Status
	 */
	public function checkAllTags( array $tags ): Status {
		$ret = Status::newGood();
		if ( count( $tags ) === 0 ) {
			$ret->error( 'tags-create-no-name' );
			return $ret;
		}
		foreach ( $tags as $tag ) {
			$curStatus = $this->changeTagValidator->validateTag( $tag );

			if ( !$curStatus->isGood() ) {
				// TODO Consider merging
				return $curStatus;
			}
		}
		return $ret;
	}

	/**
	 * @todo Consider merging with checkRequiredFields
	 * @param AbstractFilter $filter
	 * @return Status
	 */
	public function checkEmptyMessages( AbstractFilter $filter ): Status {
		$ret = Status::newGood();
		$actions = $filter->getActions();
		// TODO: Check and report both together
		if ( isset( $actions['warn'] ) && $actions['warn'][0] === '' ) {
			$ret->error( 'abusefilter-edit-invalid-warn-message' );
		} elseif ( isset( $actions['disallow'] ) && $actions['disallow'][0] === '' ) {
			$ret->error( 'abusefilter-edit-invalid-disallow-message' );
		}
		return $ret;
	}

	/**
	 * Validate throttle parameters
	 *
	 * @param array $params Throttle parameters
	 * @return Status
	 */
	public function checkThrottleParameters( array $params ): Status {
		[ $throttleCount, $throttlePeriod ] = explode( ',', $params[1], 2 );
		$throttleGroups = array_slice( $params, 2 );
		$validGroups = [
			'ip',
			'user',
			'range',
			'creationdate',
			'editcount',
			'site',
			'page'
		];

		$ret = Status::newGood();
		if ( preg_match( '/^[1-9][0-9]*$/', $throttleCount ) === 0 ) {
			$ret->error( 'abusefilter-edit-invalid-throttlecount' );
		} elseif ( preg_match( '/^[1-9][0-9]*$/', $throttlePeriod ) === 0 ) {
			$ret->error( 'abusefilter-edit-invalid-throttleperiod' );
		} elseif ( !$throttleGroups ) {
			$ret->error( 'abusefilter-edit-empty-throttlegroups' );
		} else {
			$valid = true;
			// Groups should be unique in three ways: no direct duplicates like 'user' and 'user',
			// no duplicated subgroups, not even shuffled ('ip,user' and 'user,ip') and no duplicates
			// within subgroups ('user,ip,user')
			$uniqueGroups = [];
			$uniqueSubGroups = true;
			// Every group should be valid, and subgroups should have valid groups inside
			foreach ( $throttleGroups as $group ) {
				if ( strpos( $group, ',' ) !== false ) {
					$subGroups = explode( ',', $group );
					// @phan-suppress-next-line PhanPossiblyUndeclaredVariable
					if ( $subGroups !== array_unique( $subGroups ) ) {
						$uniqueSubGroups = false;
						break;
					}
					foreach ( $subGroups as $subGroup ) {
						if ( !in_array( $subGroup, $validGroups ) ) {
							$valid = false;
							break 2;
						}
					}
					sort( $subGroups );
					$uniqueGroups[] = implode( ',', $subGroups );
				} else {
					if ( !in_array( $group, $validGroups ) ) {
						$valid = false;
						break;
					}
					$uniqueGroups[] = $group;
				}
			}

			if ( !$valid ) {
				$ret->error( 'abusefilter-edit-invalid-throttlegroups' );
			} elseif ( !$uniqueSubGroups || $uniqueGroups !== array_unique( $uniqueGroups ) ) {
				$ret->error( 'abusefilter-edit-duplicated-throttlegroups' );
			}
		}

		return $ret;
	}

	/**
	 * @param Authority $performer
	 * @param AbstractFilter $newFilter
	 * @param AbstractFilter $originalFilter
	 * @return Status
	 */
	public function checkGlobalFilterEditPermission(
		Authority $performer,
		AbstractFilter $newFilter,
		AbstractFilter $originalFilter
	): Status {
		if (
			!$this->permManager->canEditFilter( $performer, $newFilter ) ||
			!$this->permManager->canEditFilter( $performer, $originalFilter )
		) {
			return Status::newFatal( 'abusefilter-edit-notallowed-global' );
		}
		return Status::newGood();
	}

	/**
	 * @param AbstractFilter $filter
	 * @return Status
	 */
	public function checkMessagesOnGlobalFilters( AbstractFilter $filter ): Status {
		$ret = Status::newGood();
		$actions = $filter->getActions();
		if (
			$filter->isGlobal() && (
				( isset( $actions['warn'] ) && $actions['warn'][0] !== 'abusefilter-warning' ) ||
				( isset( $actions['disallow'] ) && $actions['disallow'][0] !== 'abusefilter-disallowed' )
			)
		) {
			$ret->error( 'abusefilter-edit-notallowed-global-custom-msg' );
		}
		return $ret;
	}

	/**
	 * @param Authority $performer
	 * @param AbstractFilter $newFilter
	 * @param AbstractFilter $originalFilter
	 * @return Status
	 */
	public function checkRestrictedActions(
		Authority $performer,
		AbstractFilter $newFilter,
		AbstractFilter $originalFilter
	): Status {
		$ret = Status::newGood();
		$allEnabledActions = $newFilter->getActions() + $originalFilter->getActions();
		if (
			array_intersect_key( array_fill_keys( $this->restrictedActions, true ), $allEnabledActions )
			&& !$this->permManager->canEditFilterWithRestrictedActions( $performer )
		) {
			$ret->error( 'abusefilter-edit-restricted' );
		}
		return $ret;
	}

	/**
	 * @param AbstractFilter $filter
	 * @return Status
	 */
	public function checkGroup( AbstractFilter $filter ): Status {
		$ret = Status::newGood();
		$group = $filter->getGroup();
		if ( !in_array( $group, $this->validGroups, true ) ) {
			$ret->error( 'abusefilter-edit-invalid-group' );
		}
		return $ret;
	}
}
