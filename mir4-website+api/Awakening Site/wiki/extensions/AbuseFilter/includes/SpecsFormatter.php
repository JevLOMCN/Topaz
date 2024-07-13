<?php

namespace MediaWiki\Extension\AbuseFilter;

use Language;
use MediaWiki\Extension\AbuseFilter\Filter\AbstractFilter;
use MediaWiki\Language\RawMessage;
use Message;
use MessageLocalizer;

/**
 * @todo Improve this once DI around Message objects is improved in MW core.
 */
class SpecsFormatter {
	public const SERVICE_NAME = 'AbuseFilterSpecsFormatter';

	/** @var MessageLocalizer */
	private $messageLocalizer;

	/**
	 * @param MessageLocalizer $messageLocalizer
	 */
	public function __construct( MessageLocalizer $messageLocalizer ) {
		$this->messageLocalizer = $messageLocalizer;
	}

	/**
	 * @param MessageLocalizer $messageLocalizer
	 */
	public function setMessageLocalizer( MessageLocalizer $messageLocalizer ): void {
		$this->messageLocalizer = $messageLocalizer;
	}

	/**
	 * @param string $action
	 * @return string HTML
	 * @todo Replace usage with getActionMessage
	 */
	public function getActionDisplay( string $action ): string {
		// Give grep a chance to find the usages:
		// abusefilter-action-tag, abusefilter-action-throttle, abusefilter-action-warn,
		// abusefilter-action-blockautopromote, abusefilter-action-block, abusefilter-action-degroup,
		// abusefilter-action-rangeblock, abusefilter-action-disallow
		$msg = $this->messageLocalizer->msg( "abusefilter-action-$action" );
		return $msg->isDisabled() ? htmlspecialchars( $action ) : $msg->escaped();
	}

	/**
	 * @param string $action
	 * @return Message
	 */
	public function getActionMessage( string $action ): Message {
		// Give grep a chance to find the usages:
		// abusefilter-action-tag, abusefilter-action-throttle, abusefilter-action-warn,
		// abusefilter-action-blockautopromote, abusefilter-action-block, abusefilter-action-degroup,
		// abusefilter-action-rangeblock, abusefilter-action-disallow
		$msg = $this->messageLocalizer->msg( "abusefilter-action-$action" );
		// XXX Why do we expect the message to be disabled?
		return $msg->isDisabled() ? new RawMessage( $action ) : $msg;
	}

	/**
	 * @param string $action
	 * @param string[] $parameters
	 * @param Language $lang
	 * @return string
	 */
	public function formatAction( string $action, array $parameters, Language $lang ): string {
		if ( count( $parameters ) === 0 || ( $action === 'block' && count( $parameters ) !== 3 ) ) {
			$displayAction = $this->getActionDisplay( $action );
		} elseif ( $action === 'block' ) {
			// Needs to be treated separately since the message is more complex
			$messages = [
				$this->messageLocalizer->msg( 'abusefilter-block-anon' )->escaped() .
				$this->messageLocalizer->msg( 'colon-separator' )->escaped() .
				$lang->translateBlockExpiry( $parameters[1] ),
				$this->messageLocalizer->msg( 'abusefilter-block-user' )->escaped() .
				$this->messageLocalizer->msg( 'colon-separator' )->escaped() .
				$lang->translateBlockExpiry( $parameters[2] )
			];
			if ( $parameters[0] === 'blocktalk' ) {
				$messages[] = $this->messageLocalizer->msg( 'abusefilter-block-talk' )->escaped();
			}
			$displayAction = $lang->commaList( $messages );
		} elseif ( $action === 'throttle' ) {
			array_shift( $parameters );
			[ $actions, $time ] = explode( ',', array_shift( $parameters ) );

			// Join comma-separated groups in a commaList with a final "and", and convert to messages.
			// Messages used here: abusefilter-throttle-ip, abusefilter-throttle-user,
			// abusefilter-throttle-site, abusefilter-throttle-creationdate, abusefilter-throttle-editcount
			// abusefilter-throttle-range, abusefilter-throttle-page, abusefilter-throttle-none
			foreach ( $parameters as &$val ) {
				if ( strpos( $val, ',' ) !== false ) {
					$subGroups = explode( ',', $val );
					foreach ( $subGroups as &$group ) {
						$msg = $this->messageLocalizer->msg( "abusefilter-throttle-$group" );
						// We previously accepted literally everything in this field, so old entries
						// may have weird stuff.
						$group = $msg->exists() ? $msg->text() : $group;
					}
					unset( $group );
					$val = $lang->listToText( $subGroups );
				} else {
					$msg = $this->messageLocalizer->msg( "abusefilter-throttle-$val" );
					$val = $msg->exists() ? $msg->text() : $val;
				}
			}
			unset( $val );
			$groups = $lang->semicolonList( $parameters );

			$displayAction = $this->getActionDisplay( $action ) .
				$this->messageLocalizer->msg( 'colon-separator' )->escaped() .
				$this->messageLocalizer->msg( 'abusefilter-throttle-details' )
					->params( $actions, $time, $groups )->escaped();
		} else {
			$displayAction = $this->getActionDisplay( $action ) .
				$this->messageLocalizer->msg( 'colon-separator' )->escaped() .
				$lang->semicolonList( array_map( 'htmlspecialchars', $parameters ) );
		}

		return $displayAction;
	}

	/**
	 * @param string $value
	 * @param Language $lang
	 * @return string
	 */
	public function formatFlags( string $value, Language $lang ): string {
		$flags = array_filter( explode( ',', $value ) );
		$flagsDisplay = [];
		foreach ( $flags as $flag ) {
			$flagsDisplay[] = $this->messageLocalizer->msg( "abusefilter-history-$flag" )->escaped();
		}

		return $lang->commaList( $flagsDisplay );
	}

	/**
	 * @param AbstractFilter $filter
	 * @param Language $lang
	 * @return string
	 */
	public function formatFilterFlags( AbstractFilter $filter, Language $lang ): string {
		$flags = array_filter( [
			'enabled' => $filter->isEnabled(),
			'deleted' => $filter->isDeleted(),
			'hidden' => $filter->isHidden(),
			'global' => $filter->isGlobal()
		] );
		$flagsDisplay = [];
		foreach ( $flags as $flag => $_ ) {
			// The following messages are generated here:
			// * abusefilter-history-enabled
			// * abusefilter-history-deleted
			// * abusefilter-history-hidden
			// * abusefilter-history-global
			$flagsDisplay[] = $this->messageLocalizer->msg( "abusefilter-history-$flag" )->escaped();
		}

		return $lang->commaList( $flagsDisplay );
	}

	/**
	 * Gives either the user-specified name for a group,
	 * or spits the input back out when the message for the group is disabled
	 * @param string $group The filter's group (as defined in $wgAbuseFilterValidGroups)
	 * @return string A name for that filter group, or the input.
	 */
	public function nameGroup( string $group ): string {
		// Give grep a chance to find the usages: abusefilter-group-default
		$msg = $this->messageLocalizer->msg( "abusefilter-group-$group" );
		return $msg->isDisabled() ? $group : $msg->escaped();
	}
}
