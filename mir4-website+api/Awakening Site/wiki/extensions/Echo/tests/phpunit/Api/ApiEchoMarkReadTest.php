<?php

/**
 * @group medium
 * @group API
 * @group Database
 * @covers \MediaWiki\Extension\Notifications\Api\ApiEchoMarkRead
 */
class ApiEchoMarkReadTest extends ApiTestCase {

	public function testMarkReadWithList() {
		// Grouping by section
		$data = $this->doApiRequestWithToken( [
			'action' => 'echomarkread',
			'notlist' => '121|122|123',
		] );

		$this->assertArrayHasKey( 'query', $data[0] );
		$this->assertArrayHasKey( 'echomarkread', $data[0]['query'] );

		$result = $data[0]['query']['echomarkread'];

		// General count
		$this->assertArrayHasKey( 'count', $result );
		$this->assertArrayHasKey( 'rawcount', $result );

		$this->assertArrayHasKey( 'alert', $result );
		$alert = $result['alert'];
		$this->assertArrayHasKey( 'rawcount', $alert );
		$this->assertArrayHasKey( 'count', $alert );

		$this->assertArrayHasKey( 'message', $result );
		$message = $result['message'];
		$this->assertArrayHasKey( 'rawcount', $message );
		$this->assertArrayHasKey( 'count', $message );
	}

	public function testMarkReadWithAll() {
		// Grouping by section
		$data = $this->doApiRequestWithToken( [
			'action' => 'echomarkread',
			'notall' => '1',
		] );

		$this->assertArrayHasKey( 'query', $data[0] );
		$this->assertArrayHasKey( 'echomarkread', $data[0]['query'] );

		$result = $data[0]['query']['echomarkread'];

		// General count
		$this->assertArrayHasKey( 'count', $result );
		$this->assertArrayHasKey( 'rawcount', $result );

		$this->assertArrayHasKey( 'alert', $result );
		$alert = $result['alert'];
		$this->assertArrayHasKey( 'rawcount', $alert );
		$this->assertArrayHasKey( 'count', $alert );

		$this->assertArrayHasKey( 'message', $result );
		$message = $result['message'];
		$this->assertArrayHasKey( 'rawcount', $message );
		$this->assertArrayHasKey( 'count', $message );
	}

	public function testMarkReadWithSections() {
		// Grouping by section
		$data = $this->doApiRequestWithToken( [
			'action' => 'echomarkread',
			'sections' => 'alert|message',
		] );

		$this->assertArrayHasKey( 'query', $data[0] );
		$this->assertArrayHasKey( 'echomarkread', $data[0]['query'] );

		$result = $data[0]['query']['echomarkread'];

		// General count
		$this->assertArrayHasKey( 'count', $result );
		$this->assertArrayHasKey( 'rawcount', $result );

		$this->assertArrayHasKey( 'alert', $result );
		$alert = $result['alert'];
		$this->assertArrayHasKey( 'rawcount', $alert );
		$this->assertArrayHasKey( 'count', $alert );

		$this->assertArrayHasKey( 'message', $result );
		$message = $result['message'];
		$this->assertArrayHasKey( 'rawcount', $message );
		$this->assertArrayHasKey( 'count', $message );
	}

}
