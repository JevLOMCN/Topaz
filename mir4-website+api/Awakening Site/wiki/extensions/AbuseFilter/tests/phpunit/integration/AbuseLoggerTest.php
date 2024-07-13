<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration;

use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\AbuseFilter\AbuseLogger
 * @group Database
 */
class AbuseLoggerTest extends MediaWikiIntegrationTestCase {
	/**
	 * Tests that the AbuseFilter logs are sent to CheckUser, and that CheckUser actually inserts the row to the
	 * cu_private_event table.
	 */
	public function testSendingLogsToCheckUser() {
		$this->markTestSkippedIfExtensionNotLoaded( 'CheckUser' );
		AbuseFilterServices::getAbuseLoggerFactory()->newLogger(
			$this->getExistingTestPage()->getTitle(),
			$this->getTestUser()->getUser(),
			VariableHolder::newFromArray( [ 'action' => 'edit' ] )
		)->addLogEntries( [ 1 => [ 'warn' ] ] );
		// Sending the log to CheckUser happens on a DeferredUpdate, so we need to run the updates.
		DeferredUpdates::doUpdates();
		// Assert that an insert into the cu_private_event table occurred for the abusefilter hit.
		// This may be a bit brittle if the cu_private_event table schema changes, but as AbuseFilter is
		// a dependency of CheckUser in CI and the columns checked are unlikely to change, this is acceptable.
		$this->assertSame(
			1,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'cu_private_event' )
				->where( [ 'cupe_log_type' => 'abusefilter', 'cupe_log_action' => 'hit' ] )
				->fetchField()
		);
	}
}
