<?php

namespace MediaWiki\Extension\DiscussionTools\Tests;

use ApiTestCase;
use MediaWiki\Title\Title;

/**
 * @group medium
 * @group Database
 * @covers \MediaWiki\Extension\DiscussionTools\ApiDiscussionToolsCompare
 */
class ApiDiscussionToolsCompareTest extends ApiTestCase {

	/**
	 * @covers \MediaWiki\Extension\DiscussionTools\ApiDiscussionToolsCompare::execute
	 */
	public function testExecuteApiDiscussionToolsCompare() {
		$title = Title::newFromText( 'Talk:' . __METHOD__ );
		$page = $this->getNonexistingTestPage( $title );

		$this->editPage( $page, "== Test ==\n\nadd DT pageinfo content\n" );
		$rev1 = $page->getLatest();

		$this->editPage( $page, ':adding another edit' );
		$rev2 = $page->getLatest();

		$params = [
			'action' => 'discussiontoolscompare',
			'fromrev' => $rev1,
			'torev' => $rev2,
		];

		$result = $this->doApiRequestWithToken( $params );

		$this->assertNotEmpty( $result[0]['discussiontoolscompare'] );
		$this->assertArrayHasKey( 'fromrevid', $result[0]['discussiontoolscompare'] );
		$this->assertSame( $rev1, $result[0]['discussiontoolscompare']['fromrevid'] );
		$this->assertArrayHasKey( 'torevid', $result[0]['discussiontoolscompare'] );
		$this->assertSame( $rev2, $result[0]['discussiontoolscompare']['torevid'] );
		$this->assertArrayHasKey( 'removedcomments', $result[0]['discussiontoolscompare'] );
		$this->assertArrayHasKey( 'addedcomments', $result[0]['discussiontoolscompare'] );
	}

}
