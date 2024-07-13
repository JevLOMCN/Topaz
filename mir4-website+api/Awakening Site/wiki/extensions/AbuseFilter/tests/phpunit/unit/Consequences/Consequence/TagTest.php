<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Consequences\Consequence;

use MediaWiki\Extension\AbuseFilter\ActionSpecifier;
use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagger;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Tag;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Tag
 * @covers ::__construct
 */
class TagTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::execute
	 */
	public function testExecute() {
		$tagsToAdd = [ 'tag1', 'tag2' ];
		$specifier = $this->createMock( ActionSpecifier::class );
		$params = $this->createMock( Parameters::class );
		$params->expects( $this->once() )->method( 'getActionSpecifier' )
			->willReturn( $specifier );
		$tagger = $this->createMock( ChangeTagger::class );
		$tagger->expects( $this->once() )->method( 'addTags' )
			->with(
				$this->identicalTo( $specifier ),
				$this->identicalTo( $tagsToAdd )
			);
		$tag = new Tag( $params, $tagsToAdd, $tagger );
		$this->assertTrue( $tag->execute() );
	}
}
