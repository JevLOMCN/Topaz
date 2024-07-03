<?php

use MediaWiki\Extension\TemplateData\TemplateDataStatus;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Status\Status;

/**
 * @covers \MediaWiki\Extension\TemplateData\TemplateDataStatus
 * @license GPL-2.0-or-later
 */
class SerializationTest extends MediaWikiIntegrationTestCase {

	public function testParserOutputPersistenceForwardCompatibility() {
		$output = new ParserOutput();

		$status = Status::newFatal( 'a', 'b', 'c' );
		$status->fatal( 'f' );
		$status->warning( 'd', 'e' );

		// Set JSONified state. Should work before we set JSON-serializable data,
		// to be robust against old code reading new data after a rollback.
		$output->setExtensionData( 'TemplateDataStatus',
			TemplateDataStatus::jsonSerialize( $status )
		);

		$result = TemplateDataStatus::newFromJson( $output->getExtensionData( 'TemplateDataStatus' ) );
		$this->assertEquals( $status->getStatusValue(), $result->getStatusValue() );
		$this->assertSame( (string)$status, (string)$result );
	}

	public function testParserOutputPersistenceBackwardCompatibility() {
		$output = new ParserOutput();

		$status = Status::newFatal( 'a', 'b', 'c' );
		$status->fatal( 'f' );
		$status->warning( 'd', 'e' );

		// Set the object directly. Should still work once we normally set JSON-serializable data.
		$output->setExtensionData( 'TemplateDataStatus', $status );

		$result = TemplateDataStatus::newFromJson( $output->getExtensionData( 'TemplateDataStatus' ) );
		$this->assertEquals( $status->getStatusValue(), $result->getStatusValue() );
		$this->assertSame( (string)$status, (string)$result );
	}

	public static function provideStatus() {
		yield [ Status::newGood() ];
		$status = Status::newFatal( 'a', 'b', 'c' );
		$status->fatal( 'f' );
		$status->warning( 'd', 'e' );
		yield [ $status ];
	}

	/**
	 * @dataProvider provideStatus
	 */
	public function testParserOutputPersistenceRoundTrip( Status $status ) {
		$parserOutput = new ParserOutput();
		$parserOutput->setExtensionData( 'TemplateDataStatus', TemplateDataStatus::jsonSerialize( $status ) );
		$result = TemplateDataStatus::newFromJson( $parserOutput->getExtensionData( 'TemplateDataStatus' ) );
		$this->assertEquals( $status->getStatusValue(), $result->getStatusValue() );
		$this->assertSame( (string)$status, (string)$result );
	}

	/**
	 * @dataProvider provideStatus
	 */
	public function testJsonRoundTrip( Status $status ) {
		$json = TemplateDataStatus::jsonSerialize( $status );
		$result = TemplateDataStatus::newFromJson( $json );
		$this->assertEquals( $status->getStatusValue(), $result->getStatusValue() );
		$this->assertSame( (string)$status, (string)$result );
	}

}
