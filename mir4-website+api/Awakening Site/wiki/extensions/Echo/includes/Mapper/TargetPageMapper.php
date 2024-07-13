<?php

namespace MediaWiki\Extension\Notifications\Mapper;

use MediaWiki\Extension\Notifications\Model\TargetPage;

/**
 * Database mapper for TargetPage model
 */
class TargetPageMapper extends AbstractMapper {

	/**
	 * List of db fields used to construct an TargetPage model
	 * @var string[]
	 */
	protected static $fields = [
		'etp_page',
		'etp_event'
	];

	/**
	 * Insert an TargetPage instance into the database
	 *
	 * @param TargetPage $targetPage
	 * @return bool
	 */
	public function insert( TargetPage $targetPage ) {
		$dbw = $this->dbFactory->getEchoDb( DB_PRIMARY );

		$row = $targetPage->toDbArray();

		$dbw->insert( 'echo_target_page', $row, __METHOD__ );

		return true;
	}
}
