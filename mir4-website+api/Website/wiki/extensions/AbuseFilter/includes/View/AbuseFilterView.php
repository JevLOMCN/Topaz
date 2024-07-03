<?php

namespace MediaWiki\Extension\AbuseFilter\View;

use ContextSource;
use Flow\Data\Listener\RecentChangesListener;
use IContextSource;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use OOUI;
use RecentChange;
use UnexpectedValueException;
use Wikimedia\Rdbms\Platform\ISQLPlatform;
use Xml;

abstract class AbuseFilterView extends ContextSource {

	/**
	 * @var AbuseFilterPermissionManager
	 */
	protected $afPermManager;

	/**
	 * @var array The parameters of the current request
	 */
	protected $mParams;

	/**
	 * @var LinkRenderer
	 */
	protected $linkRenderer;

	/** @var string */
	protected $basePageName;

	/**
	 * @param AbuseFilterPermissionManager $afPermManager
	 * @param IContextSource $context
	 * @param LinkRenderer $linkRenderer
	 * @param string $basePageName
	 * @param array $params
	 */
	public function __construct(
		AbuseFilterPermissionManager $afPermManager,
		IContextSource $context,
		LinkRenderer $linkRenderer,
		string $basePageName,
		array $params
	) {
		$this->mParams = $params;
		$this->setContext( $context );
		$this->linkRenderer = $linkRenderer;
		$this->basePageName = $basePageName;
		$this->afPermManager = $afPermManager;
	}

	/**
	 * @param string|int $subpage
	 * @return Title
	 */
	public function getTitle( $subpage = '' ) {
		return SpecialPage::getTitleFor( $this->basePageName, $subpage );
	}

	/**
	 * Function to show the page
	 */
	abstract public function show();

	/**
	 * Build input and button for loading a filter
	 *
	 * @return string
	 */
	public function buildFilterLoader() {
		$loadText =
			new OOUI\TextInputWidget(
				[
					'type' => 'number',
					'name' => 'wpInsertFilter',
					'id' => 'mw-abusefilter-load-filter'
				]
			);
		$loadButton =
			new OOUI\ButtonWidget(
				[
					'label' => $this->msg( 'abusefilter-test-load' )->text(),
					'id' => 'mw-abusefilter-load'
				]
			);
		$loadGroup =
			new OOUI\ActionFieldLayout(
				$loadText,
				$loadButton,
				[
					'label' => $this->msg( 'abusefilter-test-load-filter' )->text()
				]
			);
		// CSS class for reducing default input field width
		return Xml::tags(
			'div',
			[ 'class' => 'mw-abusefilter-load-filter-id' ],
			$loadGroup
		);
	}

	/**
	 * @param ISQLPlatform $db
	 * @param string|false $action 'edit', 'move', 'createaccount', 'delete' or false for all
	 * @return string
	 */
	public function buildTestConditions( ISQLPlatform $db, $action = false ) {
		$editSources = [
			RecentChange::SRC_EDIT,
			RecentChange::SRC_NEW,
		];
		if ( in_array( 'flow', $this->getConfig()->get( 'AbuseFilterValidGroups' ), true ) ) {
			// TODO Should this be separated somehow? Also, this case should be handled via a hook, not
			// by special-casing Flow here.
			// @phan-suppress-next-line PhanUndeclaredClassConstant Temporary solution
			$editSources[] = RecentChangesListener::SRC_FLOW;
		}
		// If one of these is true, we're abusefilter compatible.
		switch ( $action ) {
			case 'edit':
				return $db->makeList( [
					// Actually, this is only one condition, but this way we get it as string
					'rc_source' => $editSources
				], LIST_AND );
			case 'move':
				return $db->makeList( [
					'rc_source' => RecentChange::SRC_LOG,
					'rc_log_type' => 'move',
					'rc_log_action' => 'move'
				], LIST_AND );
			case 'createaccount':
				return $db->makeList( [
					'rc_source' => RecentChange::SRC_LOG,
					'rc_log_type' => 'newusers',
					'rc_log_action' => [ 'create', 'autocreate' ]
				], LIST_AND );
			case 'delete':
				return $db->makeList( [
					'rc_source' => RecentChange::SRC_LOG,
					'rc_log_type' => 'delete',
					'rc_log_action' => 'delete'
				], LIST_AND );
			case 'upload':
				return $db->makeList( [
					'rc_source' => RecentChange::SRC_LOG,
					'rc_log_type' => 'upload',
					'rc_log_action' => [ 'upload', 'overwrite', 'revert' ]
				], LIST_AND );
			case false:
				// Done later
				break;
			default:
				throw new UnexpectedValueException( __METHOD__ . ' called with invalid action: ' . $action );
		}

		return $db->makeList( [
			'rc_source' => $editSources,
			$db->makeList( [
				'rc_source' => RecentChange::SRC_LOG,
				$db->makeList( [
					$db->makeList( [
						'rc_log_type' => 'move',
						'rc_log_action' => 'move'
					], LIST_AND ),
					$db->makeList( [
						'rc_log_type' => 'newusers',
						'rc_log_action' => [ 'create', 'autocreate' ]
					], LIST_AND ),
					$db->makeList( [
						'rc_log_type' => 'delete',
						'rc_log_action' => 'delete'
					], LIST_AND ),
					$db->makeList( [
						'rc_log_type' => 'upload',
						'rc_log_action' => [ 'upload', 'overwrite', 'revert' ]
					], LIST_AND ),
				], LIST_OR ),
			], LIST_AND ),
		], LIST_OR );
	}

	/**
	 * @todo Core should provide a method for this (T233222)
	 * @param ISQLPlatform $db
	 * @param Authority $authority
	 * @return array
	 */
	public function buildVisibilityConditions( ISQLPlatform $db, Authority $authority ): array {
		if ( !$authority->isAllowed( 'deletedhistory' ) ) {
			$bitmask = RevisionRecord::DELETED_USER;
		} elseif ( !$authority->isAllowedAny( 'suppressrevision', 'viewsuppressed' ) ) {
			$bitmask = RevisionRecord::DELETED_USER | RevisionRecord::DELETED_RESTRICTED;
		} else {
			$bitmask = 0;
		}
		return $bitmask
			? [ $db->bitAnd( 'rc_deleted', $bitmask ) . " != $bitmask" ]
			: [];
	}

	/**
	 * @param string|int $id
	 * @param string|null $text
	 * @return string HTML
	 */
	public function getLinkToLatestDiff( $id, $text = null ) {
		return $this->linkRenderer->makeKnownLink(
			$this->getTitle( "history/$id/diff/prev/cur" ),
			$text
		);
	}

}
