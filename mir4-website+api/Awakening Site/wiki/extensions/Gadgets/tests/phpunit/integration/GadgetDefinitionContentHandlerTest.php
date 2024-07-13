<?php

namespace phpunit\integration;

use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\Gadgets\Content\GadgetDefinitionContent
 * @covers \MediaWiki\Extension\Gadgets\Content\GadgetDefinitionContentHandler
 * @group Gadgets
 * @group Database
 */
class GadgetDefinitionContentHandlerTest extends MediaWikiIntegrationTestCase {

	public function testHandler() {
		$status = $this->editPage( 'MediaWiki:Gadgets/X1.json', '{}' );
		/** @var RevisionRecord $rev */
		$rev = $status->getValue()['revision-record'];
		$revText = $rev->getContent( SlotRecord::MAIN )->serialize();
		$handler = $this->getServiceContainer()->getContentHandlerFactory()->getContentHandler( 'GadgetDefinition' );
		$this->assertEquals( $handler->makeEmptyContent()->serialize(), $revText );
	}
}
