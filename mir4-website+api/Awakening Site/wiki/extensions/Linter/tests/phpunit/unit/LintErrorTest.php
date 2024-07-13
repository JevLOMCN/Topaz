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

namespace MediaWiki\Linter\Test\Unit;

use MediaWiki\Linter\LintError;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Linter\LintError
 */
class LintErrorTest extends MediaWikiUnitTestCase {
	public function testLintError() {
		$error1 = new LintError(
			'fostered',
			[ 0, 10 ],
			[]
		);
		$this->assertInstanceOf( LintError::class, $error1 );
		$this->assertEquals( 'fostered', $error1->category );
		$this->assertEquals( [ 0, 10 ], $error1->location );
		$this->assertEquals( [], $error1->params );
		$this->assertNull( $error1->templateInfo );
		$this->assertEquals( 'fostered,0,10,[]', $error1->id() );

		$error2 = new LintError(
			'obsolete-tag',
			[ 10, 20 ],
			[ 'name' => 'big' ],
			null,
			5
		);
		$this->assertInstanceOf( LintError::class, $error2 );
		$this->assertEquals( 'obsolete-tag', $error2->category );
		$this->assertEquals( [ 10, 20 ], $error2->location );
		$this->assertEquals( [ 'name' => 'big' ], $error2->params );
		$this->assertEquals( [ 'name' => 'big' ], $error2->getExtraParams() );
		$this->assertNull( $error2->templateInfo );
		$this->assertEquals( 5, $error2->lintId );
		$this->assertEquals( 'obsolete-tag,10,20,{"name":"big"}', $error2->id() );

		$error3 = new LintError(
			'obsolete-tag',
			[ 10, 20 ],
			'{"name":"big","templateInfo":{"name":"1x"}}'
		);
		$this->assertInstanceOf( LintError::class, $error3 );
		$this->assertEquals( 'obsolete-tag', $error3->category );
		$this->assertEquals( [ 10, 20 ], $error3->location );
		$this->assertEquals( [ 'name' => 'big', 'templateInfo' => [ 'name' => '1x' ] ], $error3->params );
		$this->assertEquals( [ 'name' => '1x' ], $error3->templateInfo );
		$this->assertEquals( [ 'name' => 'big' ], $error3->getExtraParams() );
		$this->assertEquals(
			'obsolete-tag,10,20,{"name":"big","templateInfo":{"name":"1x"}}',
			$error3->id()
		);
	}

}
