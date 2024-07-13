<?php

use MediaWiki\Extension\ReplaceText\Search;

/**
 * @covers \MediaWiki\Extension\ReplaceText\Search
 */
class SearchTest extends \MediaWikiUnitTestCase {

	public function testGetReplacedText() {
		$text = 'This is the line being tested';
		$search = 'line';
		$replacement = 'word';
		$regex = false;
		$op = Search::getReplacedText( $text, $search, $replacement, $regex );
		$trueOp = 'This is the word being tested';
		$this->assertSame( $trueOp, $op );
	}
}
