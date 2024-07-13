<?php

use MediaWiki\Extension\Notifications\SummaryParser;
use MediaWiki\User\User;

/**
 * @group Echo
 */
class SummaryParserTest extends MediaWikiIntegrationTestCase {
	/** @var string[] */
	private $existingUsers = [
		'Werdna',
		'Jorm',
		'Jim Carter',
	];

	/**
	 * @covers \MediaWiki\Extension\Notifications\SummaryParser::parse
	 * @dataProvider provideParse
	 *
	 * @param string $summary
	 * @param string[] $expectedUsers
	 */
	public function testParse( $summary, array $expectedUsers ) {
		$parser = new SummaryParser( function ( User $user ) {
			if ( in_array( $user->getName(), $this->existingUsers ) ) {
				return crc32( $user->getName() );
			}
			return 0;
		} );

		$users = $parser->parse( $summary );
		foreach ( $users as $name => $user ) {
			$this->assertInstanceof( User::class, $user );
			$this->assertEquals( $name, $user->getName() );
		}

		$users = array_keys( $users );

		$this->assertArrayEquals( $expectedUsers, $users );
	}

	public static function provideParse() {
		return [
			[ '', [] ],
			[ " \t\r\n   ", [] ],
			[ 'foo bar', [] ],
			[ 'Werdna', [] ],
			[ 'User:Werdna', [] ],
			[ '[User:Werdna]', [] ],
			[ '[[]]', [] ],
			[ '[[:]]', [] ],
			[ '[[|]]', [] ],
			[ '[[:|]]', [] ],
			[ '[[:|test]]', [] ],
			[ '[[User:Nonexistent]]', [] ],
			[ '/* [[User:Werdna */', [] ],
			[ '[[User talk:Werdna]]', [] ],
			[ '[[User:Werdna]]', [ 'Werdna' ] ],
			[ 'this is [[ [[User:Werdna]] ]]', [ 'Werdna' ] ],
			[ '[[User:Werdna|]]', [ 'Werdna' ] ],
			[ '[[User:Werdna| ]]', [ 'Werdna' ] ],
			[ '[[User:Werdna|Wer | d[n]a]]', [ 'Werdna' ] ],
			[ '[[User:Werdna]][[User:Werdna]][[User:Werdna]]', [ 'Werdna' ] ],
			[ '/**/[[User:Werdna]][[user:jorm]]', [ 'Werdna', 'Jorm' ] ],
			[ '/* [[User:Werdna]] */ [[ user : jim_ Carter_]]', [ 'Jim Carter' ] ],
			[ '[[User:/* Jorm */]][[User:/* remove me */Werdna]]', [] ],
			[ '[[:User:Werdna]]', [] ],
			[ '[[:User:Werdna|]]', [] ],
			[ '[[:User:Werdna|foo]]', [] ],
		];
	}
}
