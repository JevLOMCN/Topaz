<?php

use MediaWiki\Extension\Notifications\DiscussionParser;

/**
 * @covers \MediaWiki\Extension\Notifications\DiffParser
 * @group Echo
 */
class DiffParserTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider provider_getChangeSet
	 */
	public function testGetChangeSet( $message, array $expect, $leftText, $rightText ) {
		$changeSet = DiscussionParser::getMachineReadableDiff( $leftText, $rightText );
		unset( $changeSet['_info'] );
		$this->assertEquals( $expect, $changeSet, $message );
	}

	public static function provider_getChangeSet() {
		return [

			[
				'Duplicate content must generate no changes',
				// Expected change set
				[],
				// Left text
				"a\nb\nc",
				// Right text
				"a\nb\nc",
			],

			[
				'Removing blank lines must generate no changes',
				// Expected change set
				[],
				// Left text
				"a\n\nb\n\nc",
				// Right text
				"a\nb\nc\n",
			],

			[
				'Must generate a single add change with only lines added',
				// Expected change set
				[ self::mockAction( 'add', "foo\nbar", 1 ) ],
				// Left
				"something",
				// Right
				"foo\nbar\nsomething",
			],

			[
				'Must generate a single subtract change with only lines subtracted',
				// Expected change set
				[ self::mockAction( 'subtract', "Zomg\nHiHiHi", 2 ) ],
				// Left
				"dummy\nZomg\nHiHiHi",
				// Right
				"dummy",
			],

			[
				'Adding content separated by no change must generate multiple changes',
				// Expected change set
				[
					self::mockAction( 'add', 'b1', 3 ),
					self::mockAction( 'add', "d1\nd2", 5, 6 ),
				],
				// Left text
				"a\nb\nc\nd\ne\nf",
				// Right text
				"a\nb\nb1\nc\nd\nd1\nd2\ne\nf",
			],

			[
				'Additon with empty line should be one action',
				// Expected change set
				[ self::mockAction( 'add', "User\n\nSignature", 1 ) ],
				// Left text
				"",
				// Right text
				"User\n\nSignature",
			],

			[
				'Extra blank lines on the edges must be trimmed',
				// Expected change set
				[ self::mockAction( 'add', "Zomg\nHiHiHi", 1 ) ],
				// Left text
				"",
				// Right text
				"\nZomg\nHiHiHi\n",
			],

			[
				'Extra blank lines inside the content must not be trimmed',
				// Expected change set
				[ self::mockAction( 'add', "\nZomg\nHiHiHi\n", 2 ) ],
				// Left text
				"foo\nbar",
				// Right text
				"foo\n\nZomg\nHiHiHi\n\nbar",
			],

			[
				'A blank line replaced with content must be an add',
				// Expected change set
				[ self::mockAction( 'add', 'cowbell', 1 ) ],
				// Left Text
				"",
				// Right Text
				"cowbell",
			],

			[
				'A blanked out line must be a subtraction',
				// Expected change set
				[ self::mockAction( 'subtract', 'cowbell', 1 ) ],
				// Left text
				"cowbell",
				// Right text
				"",
			],

			[
				'A line with its content replaced must be a change',
				// Expected change set
				[ self::mockChange( 'Its all about the journey', 'dummy', 1 ) ],
				// Left text
				"Its all about the journey",
				// Right text
				"dummy",
			],

			[
				'Changing lines and adding more must result in two changes',
				// Expected change set
				[
					self::mockChange( 'Must be in a hurry to finish this thing', 'Must be in a hurry', 1 ),
					self::mockAction( 'add', 'Finish this thing', 2 ),
				],
				// Left text
				"Must be in a hurry to finish this thing",
				// Right text
				"Must be in a hurry\nFinish this thing",
			],

			[
				'Changing multiple lines and adding more must result in two changes',
				// Expected change set
				[
					self::mockChange( "Must not be\nin much of a hurry", "Must be\nin a hurry", 2 ),
					self::mockAction( 'subtract', "to finish\nthis thing", 4 ),
				],
				// Left text
				"abc\nMust not be\nin much of a hurry\nto finish\nthis thing",
				// Right text
				"abc\nMust be\nin a hurry",
			],

			[
				'Must generate multiple add, change, and subtract actions',
				// Expected change set
				[
					self::mockChange( "abc\nSome", "Other\nThings", 1 ),
					self::mockAction( 'subtract', "Stuff", 3 ),
					self::mockChange( "And\nThen", "There\nWas", 6, 5 ),
					self::mockAction( 'add', 'Fencing', 8, 7 ),
				],
				// Left text
				"abc\nSome\nStuff\ndef\nghi\nAnd\nThen\njkl\nmno",
				// Right text
				"\nOther\nThings\ndef\nghi\nThere\nWas\nFencing\njkl\nmno",
			],
		];
	}

	protected static function mockAction( $action, $content, $left, $right = null ) {
		return [
			'action' => $action,
			'content' => $content,
			'left-pos' => $left,
			'right-pos' => $right ?? $left,
		];
	}

	public static function mockChange( $oldContent, $newContent, $left, $right = null ) {
		return [
			'action' => 'change',
			'old_content' => $oldContent,
			'new_content' => $newContent,
			'left-pos' => $left,
			'right-pos' => $right ?? $left,
		];
	}
}
