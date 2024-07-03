<?php

/**
 * @group medium
 * @group API
 * @group Database
 * @covers \MediaWiki\Extension\Notifications\Api\ApiEchoNotifications
 */
class ApiEchoNotificationsTest extends ApiTestCase {

	public function testWithSectionGrouping() {
		// Grouping by section
		$data = $this->doApiRequest( [
			'action' => 'query',
			'meta' => 'notifications',
			'notsections' => 'alert|message',
			'notgroupbysection' => 1,
			'notlimit' => 10,
			'notprop' => 'list|count' ] );

		$this->assertArrayHasKey( 'query', $data[0] );
		$this->assertArrayHasKey( 'notifications', $data[0]['query'] );

		$result = $data[0]['query']['notifications'];

		// General count
		$this->assertArrayHasKey( 'count', $result );
		$this->assertArrayHasKey( 'rawcount', $result );

		$this->assertArrayHasKey( 'alert', $result );
		$alert = $result['alert'];
		$this->assertArrayHasKey( 'list', $alert );
		$this->assertArrayHasKey( 'continue', $alert );
		$this->assertArrayHasKey( 'rawcount', $alert );
		$this->assertArrayHasKey( 'count', $alert );

		$this->assertArrayHasKey( 'message', $result );
		$message = $result['message'];
		$this->assertArrayHasKey( 'list', $message );
		$this->assertArrayHasKey( 'continue', $message );
		$this->assertArrayHasKey( 'rawcount', $message );
		$this->assertArrayHasKey( 'count', $message );
	}

	public function testWithoutSectionGrouping() {
		$data = $this->doApiRequest( [
			'action' => 'query',
			'meta' => 'notifications',
			'notsections' => 'alert|message',
			'notlimit' => 10,
			'notprop' => 'list|count' ] );

		$this->assertArrayHasKey( 'query', $data[0] );
		$this->assertArrayHasKey( 'notifications', $data[0]['query'] );

		$result = $data[0]['query']['notifications'];

		$this->assertArrayHasKey( 'count', $result );
		$this->assertArrayHasKey( 'rawcount', $result );
		$this->assertArrayHasKey( 'list', $result );
		$this->assertArrayHasKey( 'continue', $result );

		$this->assertTrue( !isset( $result['alert'] ) );
		$this->assertTrue( !isset( $result['message'] ) );
	}

}
