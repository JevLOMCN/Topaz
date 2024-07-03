<?php

namespace MediaWiki\Extension\AbuseFilter\ChangeTags;

use MediaWiki\Extension\AbuseFilter\ActionSpecifier;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\UserIdentityValue;
use RecentChange;

/**
 * Class that collects change tags to be later applied
 * @internal This interface should be improved and is not ready for external use
 */
class ChangeTagger {
	public const SERVICE_NAME = 'AbuseFilterChangeTagger';

	/** @var array (Persistent) map of (action ID => string[]) */
	private static $tagsToSet = [];

	/**
	 * @var ChangeTagsManager
	 */
	private $changeTagsManager;

	/**
	 * @param ChangeTagsManager $changeTagsManager
	 */
	public function __construct( ChangeTagsManager $changeTagsManager ) {
		$this->changeTagsManager = $changeTagsManager;
	}

	/**
	 * Clear any buffered tag
	 */
	public function clearBuffer(): void {
		self::$tagsToSet = [];
	}

	/**
	 * @param ActionSpecifier $specifier
	 */
	public function addConditionsLimitTag( ActionSpecifier $specifier ): void {
		$this->addTags( $specifier, [ $this->changeTagsManager->getCondsLimitTag() ] );
	}

	/**
	 * @param ActionSpecifier $specifier
	 * @param array $tags
	 */
	public function addTags( ActionSpecifier $specifier, array $tags ): void {
		$id = $this->getActionID( $specifier );
		$this->bufferTagsToSetByAction( [ $id => $tags ] );
	}

	/**
	 * @param string[][] $tagsByAction Map of (string => string[])
	 */
	private function bufferTagsToSetByAction( array $tagsByAction ): void {
		foreach ( $tagsByAction as $actionID => $tags ) {
			self::$tagsToSet[ $actionID ] = array_unique(
				array_merge( self::$tagsToSet[ $actionID ] ?? [], $tags )
			);
		}
	}

	/**
	 * @param string $id
	 * @param bool $clear
	 * @return array
	 */
	private function getTagsForID( string $id, bool $clear = true ): array {
		$val = self::$tagsToSet[$id] ?? [];
		if ( $clear ) {
			unset( self::$tagsToSet[$id] );
		}
		return $val;
	}

	/**
	 * @param RecentChange $recentChange
	 * @param bool $clear
	 * @return array
	 */
	public function getTagsForRecentChange( RecentChange $recentChange, bool $clear = true ): array {
		$id = $this->getIDFromRecentChange( $recentChange );
		return $this->getTagsForID( $id, $clear );
	}

	/**
	 * @param RecentChange $recentChange
	 * @return string
	 */
	private function getIDFromRecentChange( RecentChange $recentChange ): string {
		$title = new TitleValue(
			$recentChange->getAttribute( 'rc_namespace' ),
			$recentChange->getAttribute( 'rc_title' )
		);

		$logType = $recentChange->getAttribute( 'rc_log_type' ) ?: 'edit';
		if ( $logType === 'newusers' ) {
			$action = $recentChange->getAttribute( 'rc_log_action' ) === 'autocreate' ?
				'autocreateaccount' :
				'createaccount';
		} else {
			$action = $logType;
		}
		$user = new UserIdentityValue(
			$recentChange->getAttribute( 'rc_user' ),
			$recentChange->getAttribute( 'rc_user_text' )
		);
		$specifier = new ActionSpecifier(
			$action,
			$title,
			$user,
			$recentChange->getAttribute( 'rc_ip' ) ?? '',
			$user->getName()
		);
		return $this->getActionID( $specifier );
	}

	/**
	 * Get a unique identifier for the given action
	 *
	 * @param ActionSpecifier $specifier
	 * @return string
	 */
	private function getActionID( ActionSpecifier $specifier ): string {
		$username = $specifier->getUser()->getName();
		$title = $specifier->getTitle();
		if ( strpos( $specifier->getAction(), 'createaccount' ) !== false ) {
			// TODO Move this to ActionSpecifier?
			$username = $specifier->getAccountName();
			'@phan-var string $username';
			$title = new TitleValue( NS_USER, $username );
		}

		// Use a character that's not allowed in titles and usernames
		$glue = '|';
		return implode(
			$glue,
			[
				$title->getNamespace() . ':' . $title->getText(),
				$username,
				$specifier->getAction()
			]
		);
	}
}
