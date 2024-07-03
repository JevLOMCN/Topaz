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
 *
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Parser;

use HashBagOStuff;
use MediaWiki\Extension\AbuseFilter\Parser\AbuseFilterTokenizer;

/**
 * @group Test
 * @group AbuseFilter
 * @group AbuseFilterParser
 */
class TokenizerTest extends ParserTestCase {
	/**
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AbuseFilterTokenizer
	 * @dataProvider unclosedComment
	 */
	public function testUnclosedCommentException( $expr, $caller ) {
		$this->exceptionTest( 'unclosedcomment', $expr, $caller );
	}

	/**
	 * Data provider for testUnclosedCommentException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public function unclosedComment() {
		return [
			[ '     /****    /  *  /', 'nextToken' ],
		];
	}

	/**
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AbuseFilterTokenizer
	 * @dataProvider unrecognisedToken
	 */
	public function testUnrecognisedTokenException( $expr, $caller ) {
		$this->exceptionTest( 'unrecognisedtoken', $expr, $caller );
	}

	/**
	 * Data provider for testUnrecognisedTokenException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public function unrecognisedToken() {
		return [
			[ '#', 'nextToken' ],
		];
	}

	/**
	 * @param string $expr The expression to test
	 * @param string $caller The function where the exception is thrown
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AbuseFilterTokenizer
	 * @dataProvider unclosedString
	 */
	public function testUnclosedStringException( $expr, $caller ) {
		$this->exceptionTest( 'unclosedstring', $expr, $caller );
	}

	/**
	 * Data provider for testUnclosedStringException
	 * The second parameter is the function where the exception is raised.
	 * One expression for each throw.
	 *
	 * @return array
	 */
	public function unclosedString() {
		return [
			[ '"', 'readStringLiteral' ],
		];
	}

	/**
	 * Test that tokenized code is saved in cache.
	 *
	 * @param string $code To be tokenized
	 * @dataProvider provideCode
	 * @covers \MediaWiki\Extension\AbuseFilter\Parser\AbuseFilterTokenizer::getTokens
	 */
	public function testCaching( $code ) {
		$cache = new HashBagOStuff();
		$tokenizer = new AbuseFilterTokenizer( $cache );

		$key = $tokenizer->getCacheKey( $code );

		$tokenizer->getTokens( $code );
		$this->assertNotFalse( $cache->get( $key ) );
	}

	/**
	 * Data provider for testCaching
	 *
	 * @return array
	 */
	public static function provideCode() {
		return [
			[ '1 === 1' ],
			[ 'added_lines irlike "test"' ],
			[ 'edit_delta > 57 & action === "edit"' ],
			[ '!("confirmed") in user_groups' ]
		];
	}
}
