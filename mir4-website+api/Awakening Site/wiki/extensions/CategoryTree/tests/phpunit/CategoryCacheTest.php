<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\Extension\CategoryTree\Tests;

use MediaWiki\Category\Category;
use MediaWiki\Extension\CategoryTree\CategoryCache;
use MediaWiki\Title\TitleValue;
use MediaWikiIntegrationTestCase;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Database
 * @covers \MediaWiki\Extension\CategoryTree\CategoryCache
 */
class CategoryCacheTest extends MediaWikiIntegrationTestCase {

	public function testConstruct() {
		new CategoryCache(
			$this->createMock( ILoadBalancer::class )
		);
		$this->addToAssertionCount( 1 );
	}

	public function testDoQuery() {
		// Create a row in the category table
		$this->editPage(
			new TitleValue( NS_MAIN, 'CategoryTreeCategoryCacheTest' ),
			'[[Category:Exists]]'
		);

		$categoryCache = TestingAccessWrapper::newFromObject(
			$this->getServiceContainer()->get( 'CategoryTree.CategoryCache' )
		);

		$categoryCache->doQuery( [
			new TitleValue( NS_CATEGORY, 'Exists' ),
			new TitleValue( NS_CATEGORY, 'Missed' ),
		] );

		$this->assertCount( 2, $categoryCache->cache );
		$this->assertArrayHasKey( 'Exists', $categoryCache->cache );
		$this->assertInstanceOf( Category::class, $categoryCache->cache['Exists'] );
		$this->assertArrayHasKey( 'Missed', $categoryCache->cache );
		$this->assertNull( $categoryCache->cache['Missed'] );
	}

	public function testGetCategory() {
		// Create a row in the category table
		$this->editPage(
			new TitleValue( NS_MAIN, 'CategoryTreeCategoryCacheTest' ),
			'[[Category:Exists]]'
		);

		$categoryCache = TestingAccessWrapper::newFromObject(
			$this->getServiceContainer()->get( 'CategoryTree.CategoryCache' )
		);
		$title = new TitleValue( NS_CATEGORY, 'Exists' );

		// Test for cache miss
		$this->assertCount( 0, $categoryCache->cache );
		$this->assertInstanceOf( Category::class, $categoryCache->getCategory( $title ) );
		$this->assertCount( 1, $categoryCache->cache );

		// Test normal access
		$this->assertInstanceOf( Category::class, $categoryCache->getCategory( $title ) );
	}
}
