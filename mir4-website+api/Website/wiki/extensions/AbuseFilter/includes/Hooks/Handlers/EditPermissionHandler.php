<?php
// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

namespace MediaWiki\Extension\AbuseFilter\Hooks\Handlers;

use JsonContent;
use MediaWiki\Content\Hook\JsonValidateSaveHook;
use MediaWiki\Extension\AbuseFilter\BlockedDomainStorage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Permissions\Hook\GetUserPermissionsErrorsHook;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\User;
use MessageSpecifier;
use StatusValue;

/**
 * This hook handler is for very simple checks, rather than the much more advanced ones
 * undertaken by the FilteredActionsHandler.
 */
class EditPermissionHandler implements GetUserPermissionsErrorsHook, JsonValidateSaveHook {

	/** @var string[] */
	private const JSON_OBJECT_FIELDS = [
		'domain',
		'notes'
	];

	private const JSON_OPTIONAL_FIELDS = [ 'addedBy' ];

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/getUserPermissionsErrors
	 *
	 * @param Title $title
	 * @param User $user
	 * @param string $action
	 * @param array|string|MessageSpecifier &$result
	 * @return bool|void
	 */
	public function onGetUserPermissionsErrors( $title, $user, $action, &$result ) {
		$services = MediaWikiServices::getInstance();

		// Only do anything if we're enabled on this wiki.
		if ( !$services->getMainConfig()->get( 'AbuseFilterEnableBlockedExternalDomain' ) ) {
			return;
		}

		// Ignore all actions and pages except MediaWiki: edits (and creates)
		// to the page we care about
		if (
			!( $action == 'create' || $action == 'edit' ) ||
			!$title->inNamespace( NS_MEDIAWIKI ) ||
			$title->getDBkey() !== BlockedDomainStorage::TARGET_PAGE
		) {
			return;
		}

		if ( $services->getPermissionManager()->userHasRight( $user, 'editinterface' ) ) {
			return;
		}

		// Prohibit direct actions on our page.
		$result = [ 'abusefilter-blocked-domains-cannot-edit-directly', BlockedDomainStorage::TARGET_PAGE ];
		return false;
	}

	/**
	 * @param JsonContent $content
	 * @param PageIdentity $pageIdentity
	 * @param StatusValue $status
	 * @return bool|void
	 */
	public function onJsonValidateSave( JsonContent $content, PageIdentity $pageIdentity, StatusValue $status ) {
		$services = MediaWikiServices::getInstance();

		// Only do anything if we're enabled on this wiki.
		if ( !$services->getMainConfig()->get( 'AbuseFilterEnableBlockedExternalDomain' ) ) {
			return;
		}

		$title = TitleValue::newFromPage( $pageIdentity );
		if ( !$title->inNamespace( NS_MEDIAWIKI ) || $title->getText() !== BlockedDomainStorage::TARGET_PAGE ) {
			return;
		}
		$data = $content->getData()->getValue();

		if ( !is_array( $data ) ) {
			$status->fatal( 'abusefilter-blocked-domains-json-error' );
			return;
		}

		$isValid = true;
		$entryNumber = 0;
		foreach ( $data as $element ) {
			$entryNumber++;
			// Check if each element is an object with all known fields, allow optional fields but no other fields
			if ( is_object( $element ) && count( get_object_vars( $element ) ) >= count( self::JSON_OBJECT_FIELDS ) ) {
				foreach ( self::JSON_OBJECT_FIELDS as $field ) {
					if ( !property_exists( $element, $field ) || !is_string( $element->{$field} ) ) {
						$isValid = false;
						break 2;
					}
				}

				foreach ( self::JSON_OPTIONAL_FIELDS as $field ) {
					if ( property_exists( $element, $field ) && !is_string( $element->{$field} ) ) {
						$isValid = false;
						break 2;
					}
				}
				foreach ( $element as $field => $value ) {
					if (
						!in_array( $field, array_merge( self::JSON_OPTIONAL_FIELDS, self::JSON_OBJECT_FIELDS ) )
					) {
						$isValid = false;
						break;
					}
				}
			} else {
				$isValid = false;
				break;
			}
		}

		if ( !$isValid ) {
			$status->fatal( 'abusefilter-blocked-domains-invalid-entry', $entryNumber );
		}
	}

}
