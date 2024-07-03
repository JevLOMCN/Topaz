<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Special;

use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\Special\SpecialAbuseFilter;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewDiff;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewEdit;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewExamine;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewHistory;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewImport;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewList;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewRevert;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewTestBatch;
use MediaWiki\Extension\AbuseFilter\View\AbuseFilterViewTools;
use MediaWikiUnitTestCase;
use Wikimedia\ObjectFactory\ObjectFactory;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Special\SpecialAbuseFilter
 */
class SpecialAbuseFilterTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::getViewClassAndPageType
	 * @dataProvider provideGetViewClassAndPageType
	 */
	public function testGetViewClassAndPageType( $subpage, $view, $pageType, $params = [] ) {
		$sp = new SpecialAbuseFilter(
			$this->createMock( AbuseFilterPermissionManager::class ),
			$this->createMock( ObjectFactory::class )
		);
		[ $viewClass, $type, $args ] = $sp->getViewClassAndPageType( $subpage );
		$this->assertSame( $view, $viewClass );
		$this->assertSame( $pageType, $type );
		$this->assertSame( $params, $args );
	}

	public static function provideGetViewClassAndPageType(): array {
		return [
			[ null, AbuseFilterViewList::class, 'home' ],
			[ 'foo', AbuseFilterViewList::class, 'home' ],
			[ '1', AbuseFilterViewEdit::class, 'edit', [ 'filter' => 1 ] ],
			[ 'new', AbuseFilterViewEdit::class, 'edit', [ 'filter' => null ] ],
			[ 'history', AbuseFilterViewHistory::class, 'recentchanges', [] ],
			[ 'history/1', AbuseFilterViewHistory::class, 'recentchanges', [ 'filter' => 1 ] ],
			[ 'history/1/item/2', AbuseFilterViewEdit::class, '', [ 'filter' => 1, 'history' => 2 ] ],
			[ 'history/foo/bar', AbuseFilterViewList::class, 'home' ],
			[ 'history/1/diff/2/3', AbuseFilterViewDiff::class, '', [ 'history', '1', 'diff', '2', '3' ] ],
			[ 'history/1/diff/prev/3', AbuseFilterViewDiff::class, '', [ 'history', '1', 'diff', 'prev', '3' ] ],
			[ 'history/1/diff/prev/cur', AbuseFilterViewDiff::class, '', [ 'history', '1', 'diff', 'prev', 'cur' ] ],
			[ 'history/1/foo/2/3', AbuseFilterViewList::class, 'home' ],
			[ 'log', AbuseFilterViewHistory::class, 'recentchanges', [] ],
			[ 'log/1', AbuseFilterViewHistory::class, 'recentchanges', [ 'filter' => 1 ] ],
			[ 'log/1/item/2', AbuseFilterViewEdit::class, '', [ 'filter' => 1, 'history' => 2 ] ],
			[ 'log/foo/bar', AbuseFilterViewList::class, 'home' ],
			[ 'log/1/diff/2/3', AbuseFilterViewDiff::class, '', [ 'log', '1', 'diff', '2', '3' ] ],
			[ 'log/1/foo/2/3', AbuseFilterViewList::class, 'home' ],
			[ 'import', AbuseFilterViewImport::class, 'import' ],
			[ 'import/1', AbuseFilterViewList::class, 'home' ],
			[ 'tools', AbuseFilterViewTools::class, 'tools' ],
			[ 'tools/1', AbuseFilterViewList::class, 'home' ],
			[ 'test', AbuseFilterViewTestBatch::class, 'test', [ 'test' ] ],
			[ 'test/1', AbuseFilterViewTestBatch::class, 'test', [ 'test', '1' ] ],
			[ 'revert', AbuseFilterViewList::class, 'home' ],
			[ 'revert/1', AbuseFilterViewRevert::class, 'revert', [ 'revert', 1 ] ],
			[ 'revert/1/foo', AbuseFilterViewList::class, 'home' ],
			[ 'examine', AbuseFilterViewExamine::class, 'examine', [ 'examine' ] ],
			[ 'examine/foo/bar', AbuseFilterViewExamine::class, 'examine', [ 'examine', 'foo', 'bar' ] ],
			[ 'examine/0/bar', AbuseFilterViewExamine::class, 'examine', [ 'examine', '0', 'bar' ] ],
			[ 'examine//foo', AbuseFilterViewExamine::class, 'examine', [ 'examine', 'foo' ] ],
		];
	}
}
