<?php

namespace Cite\Tests\Unit;

use Cite\Cite;
use Cite\Parsoid\ReferencesData;
use Cite\Parsoid\RefGroupItem;
use MediaWikiUnitTestCase;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

/**
 * @covers \Cite\Parsoid\ReferencesData
 * @license GPL-2.0-or-later
 */
class ReferencesDataTest extends MediaWikiUnitTestCase {

	public function testMinimalSetup() {
		$data = new ReferencesData();
		$this->assertSame( [], $data->embeddedErrors );
		$this->assertSame( Cite::DEFAULT_GROUP, $data->referencesGroup );
		$this->assertFalse( $data->inReferencesContent() );
		$this->assertFalse( $data->inEmbeddedContent() );
		$this->assertNull( $data->getRefGroup( Cite::DEFAULT_GROUP ) );
		$this->assertSame( [], $data->getRefGroups() );
	}

	public function testAllocIfMissing() {
		$data = new ReferencesData();
		$group = $data->getRefGroup( 'note', true );
		$this->assertSame( 'note', $group->name );
		$data->removeRefGroup( 'note' );
		$this->assertNull( $data->getRefGroup( 'note' ) );
	}

	public function testEmbeddedInAnyContent() {
		$data = new ReferencesData();
		$data->pushEmbeddedContentFlag();
		$this->assertTrue( $data->inEmbeddedContent() );
		$this->assertFalse( $data->inReferencesContent() );
		$data->popEmbeddedContentFlag();
		$this->assertFalse( $data->inEmbeddedContent() );
	}

	public function testEmbeddedInReferencesContent() {
		$data = new ReferencesData();
		$data->pushEmbeddedContentFlag( 'references' );
		$this->assertTrue( $data->inEmbeddedContent() );
		$this->assertTrue( $data->inReferencesContent() );
		$data->popEmbeddedContentFlag();
		$this->assertFalse( $data->inReferencesContent() );
	}

	public function testAddUnnamedRef() {
		$data = new ReferencesData();
		$ref = $data->add(
			$this->createNoOpMock( ParsoidExtensionAPI::class ),
			Cite::DEFAULT_GROUP,
			'',
			''
		);

		$expected = new RefGroupItem();
		$expected->key = 'cite_ref-1';
		$expected->id = 'cite_ref-1';
		$expected->target = 'cite_note-1';
		$this->assertEquals( $expected, $ref );

		$group = $data->getRefGroup( Cite::DEFAULT_GROUP );
		$this->assertEquals( [ $expected ], $group->refs );
		$this->assertSame( [], $group->indexByName );
	}

	public function testAddNamedRef() {
		$data = new ReferencesData();
		$ref = $data->add(
			$this->createNoOpMock( ParsoidExtensionAPI::class ),
			'note',
			'wales',
			'rtl'
		);

		$expected = new RefGroupItem();
		$expected->dir = 'rtl';
		$expected->group = 'note';
		$expected->key = 'cite_ref-wales_1';
		$expected->id = 'cite_ref-wales_1-0';
		$expected->name = 'wales';
		$expected->target = 'cite_note-wales-1';
		$this->assertEquals( $expected, $ref );

		$group = $data->getRefGroup( 'note' );
		$this->assertEquals( [ $expected ], $group->refs );
		$this->assertEquals( [ 'wales' => $expected ], $group->indexByName );
	}

}
