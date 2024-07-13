<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration;

use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\AbuseFilter\Maintenance\PurgeOldLogIPData;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @group Test
 * @group AbuseFilter
 * @group Database
 * @covers \MediaWiki\Extension\AbuseFilter\Maintenance\PurgeOldLogIPData
 */
class PurgeOldLogIPDataTest extends MaintenanceBaseTestCase {

	private const FAKE_TIME = '20200115000000';
	private const MAX_AGE = 3600;

	/**
	 * @inheritDoc
	 */
	protected function getMaintenanceClass() {
		return PurgeOldLogIPData::class;
	}

	/**
	 * @inheritDoc
	 */
	public function addDBData() {
		$defaultRow = [
			'afl_ip' => '1.1.1.1',
			'afl_global' => 0,
			'afl_filter_id' => 1,
			'afl_user' => 1,
			'afl_user_text' => 'User',
			'afl_action' => 'edit',
			'afl_actions' => '',
			'afl_var_dump' => 'xxx',
			'afl_namespace' => 0,
			'afl_title' => 'Title',
			'afl_wiki' => null,
			'afl_deleted' => 0,
			'afl_patrolled_by' => 0,
			'afl_rev_id' => 42,
		];
		$oldTS = ConvertibleTimestamp::convert(
			TS_MW,
			ConvertibleTimestamp::convert( TS_UNIX, self::FAKE_TIME ) - 2 * self::MAX_AGE
		);
		$rows = [
			[ 'afl_id' => 1, 'afl_timestamp' => $this->db->timestamp( $oldTS ) ] + $defaultRow,
			[ 'afl_id' => 2, 'afl_timestamp' => $this->db->timestamp( $oldTS ), 'afl_ip' => '' ] + $defaultRow,
			[ 'afl_id' => 3, 'afl_timestamp' => $this->db->timestamp( self::FAKE_TIME ) ] + $defaultRow,
			[ 'afl_id' => 4, 'afl_timestamp' => $this->db->timestamp( self::FAKE_TIME ), 'afl_ip' => '' ] + $defaultRow,
		];
		$this->db->insert( 'abuse_filter_log', $rows, __METHOD__ );
	}

	public function testExecute() {
		ConvertibleTimestamp::setFakeTime( self::FAKE_TIME );
		$this->maintenance->setConfig( new HashConfig( [ 'AbuseFilterLogIPMaxAge' => self::MAX_AGE ] ) );
		$this->expectOutputRegex( '/1 rows/' );
		$this->maintenance->execute();
	}
}
