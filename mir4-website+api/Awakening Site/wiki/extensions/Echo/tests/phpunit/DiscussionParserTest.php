<?php

// phpcs:disable Generic.Files.LineLength -- Long html test examples

use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Extension\Notifications\DiscussionParser;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\MainConfigNames;
use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\Notifications\DiscussionParser
 * @group Echo
 * @group Database
 *
 * TODO test cases for:
 *  - stripHeader
 *  - stripSignature
 */
class DiscussionParserTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var string
	 */
	private const EXEMPLAR_TIMESTAMP = '20:47, 2 November 2022 (UTC)';

	/**
	 * Convenience users for use in these tests.
	 * Can be setup one by one using the setupTestUser() method
	 * Or all at once using the setupAllTestUsers() method
	 *
	 * @var array[] [username => [user preference => preference value]]
	 */
	protected $testUsers = [
		'Werdna' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'Werdna2' => [
			'nickname' => '[[User:Werdna2|Andrew]]',
			'fancysig' => '1',
		],
		'Werdna3' => [
			'nickname' => '[[User talk:Werdna3|Andrew]]',
			'fancysig' => '1',
		],
		'Werdna4' => [
			'nickname' => '[[User:Werdna4|wer]dna]]',
			'fancysig' => '1',
		],
		'We buried our secrets in the garden' => [
			'nickname' => '[[User talk:We buried our secrets in the garden#top|wbositg]]',
			'fancysig' => '1',
		],
		'I Heart Spaces' => [
			'nickname' => '[[User:I_Heart_Spaces]] ([[User_talk:I_Heart_Spaces]])',
			'fancysig' => '1',
		],
		'Jam' => [
			'nickname' => '[[User:Jam]]',
			'fancysig' => '1',
		],
		'Reverta-me' => [
			'nickname' => "[[User:Reverta-me|<span style=\"font-size:13px; color:blue;font-family:Lucida Handwriting;text-shadow:aqua 5px 3px 12px;\">Aaaaa Bbbbbbb</span>]]'' <sup>[[User Talk:Reverta-me|<font color=\"gold\" face=\"Lucida Calligraphy\">Discussão</font>]]</sup>''",
			'fancysig' => '1',
		],
		'Jorm' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'Jdforrester' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'DarTar' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'Bsitu' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'JarJar' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'Schnark' => [
			'nickname' => '[[Benutzer:Schnark]] ([[Benutzer:Schnark/js|js]])',
			'fancysig' => '1',
		],
		'Cwobeel' => [
			'nickname' => '[[User:Cwobeel|<span style="color:#339966">Cwobeel</span>]] [[User_talk:Cwobeel|<span style="font-size:80%">(talk)</span>]]',
			'fancysig' => '1',
		],
		'Bob K31416' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'X" onclick="alert(\'XSS\');" title="y' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'He7d3r' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'PauloEduardo' => [
			'nickname' => "[[User:PauloEduardo|<span style=\"font-size:13px; color:blue;font-family:Lucida Handwriting;text-shadow:aqua 5px 3px 12px;\">Paulo Eduardo</span>]]'' <sup>[[User Talk:PauloEduardo|<font color=\"gold\" face=\"Lucida Calligraphy\">Discussão</font>]]</sup>''",
			'fancysig' => '1',
		],
		'PatHadley' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'Samwalton9' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'Kudpung' => [
			'nickname' => '[[User:Kudpung|Kudpung กุดผึ้ง]] ([[User talk:Kudpung#top|talk]])',
			'fancysig' => '1',
		],
		'Jim Carter' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'Buster7' => [
			'nickname' => '',
			'fancysig' => '0',
		],
		'Admin' => [
			'nickname' => '[[:User:Admin|Admin]]',
			'fancysig' => '1',
		],
		'Test11' => [
			'nickname' => '',
			'fancysig' => '0',
		],
	];

	private function setupAllTestUsers() {
		foreach ( $this->testUsers as $username => $_ ) {
			$this->setupTestUser( $username );
		}
	}

	private function setupTestUser( $username ) {
		// Skip user creation requests that are not in the list (such as IPs)
		if ( !array_key_exists( $username, $this->testUsers ) ) {
			return;
		}

		$preferences = $this->testUsers[$username];
		$user = User::createNew( $username );

		// Set preferences
		if ( $user ) {
			$userOptionsManager = $this->getServiceContainer()->getUserOptionsManager();
			foreach ( $preferences as $option => $value ) {
				$userOptionsManager->setOption( $user, $option, $value );
			}
			$userOptionsManager->saveOptions( $user );
		}
	}

	public static function provideHeaderExtractions() {
		return [
			[ '', false ],
			[ '== Grand jury no bill reception ==', 'Grand jury no bill reception' ],
			[ '=== Echo-Test ===', 'Echo-Test' ],
			[ '==== Notificações ====', 'Notificações' ],
			[ '=====Me?=====', 'Me?' ],
		];
	}

	/**
	 * @dataProvider provideHeaderExtractions
	 */
	public function testExtractHeader( $text, $expected ) {
		$this->assertEquals( $expected, DiscussionParser::extractHeader( $text ) );
	}

	public static function generateEventsForRevisionDataProvider() {
		return [
			[
				'new' => 637638133,
				'old' => 637637213,
				'username' => 'Cwobeel',
				'lang' => 'en',
				'pages' => [
					// pages expected to exist (e.g. templates to be expanded)
					'Template:u' => '[[User:{{{1}}}|{{<includeonly>safesubst:</includeonly>#if:{{{2|}}}|{{{2}}}|{{{1}}}}}]]<noinclude>{{documentation}}</noinclude>',
				],
				// can't remember, not important here
				'title' => 'UTPage',
				'expected' => [
					// events expected to be fired going from old revision to new
					[
						'type' => 'mention',
						'agent' => 'Cwobeel',
						'section-title' => 'Grand jury no bill reception',
						/*
						 * I wish I could also compare Event::$extra data to
						 * compare user ids of mentioned users. However, due to
						 * How PHPUnit works, setUp won't be run by the time
						 * this dataset is generated, so we don't yet know the
						 * user ids of the folks we're about to insert...
						 * I'll skip that part for now.
						 */
					],
				],
			],
			[
				'new' => 138275105,
				'old' => 138274875,
				'username' => 'Schnark',
				'lang' => 'de',
				'pages' => [],
				// can't remember, not important here
				'title' => 'UTPage',
				'expected' => [
					[
						'type' => 'mention',
						'agent' => 'Schnark',
						'section-title' => 'Echo-Test',
					],
				],
			],
			[
				'new' => 40610292,
				'old' => 40608353,
				'username' => 'PauloEduardo',
				'lang' => 'pt',
				'pages' => [
					'Predefinição:U' => '[[User:{{{1|<noinclude>Exemplo</noinclude>}}}|{{{{{|safesubst:}}}#if:{{{2|}}}|{{{2}}}|{{{1|<noinclude>Exemplo</noinclude>}}}}}]]<noinclude>{{Atalho|Predefinição:U}}{{Documentação|Predefinição:Usuário/doc}}</noinclude>',
				],
				// can't remember, not important here
				'title' => 'UTPage',
				'expected' => [
					[
						'type' => 'mention',
						'agent' => 'PauloEduardo',
						'section-title' => 'Notificações',
					],
				],
			],
			[
				'new' => 646792804,
				'old' => 646790570,
				'username' => 'PatHadley',
				'lang' => 'en',
				'pages' => [
					'Template:ping' => '{{SAFESUBST:<noinclude />#if:{{{1|<noinclude>$</noinclude>}}}
 |<span class="template-ping">@[[:User:{{SAFESUBST:<noinclude />BASEPAGENAME:{{{1|Example}}}}}|{{SAFESUBST:<noinclude />BASEPAGENAME:{{{label1|{{{1|Example}}}}}}}}]]{{SAFESUBST:<noinclude />#if:{{{2|}}}
 |, [[:User:{{SAFESUBST:<noinclude />BASEPAGENAME:{{{2|Example}}}}}|{{SAFESUBST:<noinclude />BASEPAGENAME:{{{label2|{{{2|Example}}}}}}}}]]{{SAFESUBST:<noinclude />#if:{{{3|}}}
 |, [[:User:{{SAFESUBST:<noinclude />BASEPAGENAME:{{{3|Example}}}}}|{{SAFESUBST:<noinclude />BASEPAGENAME:{{{label3|{{{3|Example}}}}}}}}]]{{SAFESUBST:<noinclude />#if:{{{4|}}}
 |, [[:User:{{SAFESUBST:<noinclude />BASEPAGENAME:{{{4|Example}}}}}|{{SAFESUBST:<noinclude />BASEPAGENAME:{{{label4|{{{4|Example}}}}}}}}]]{{SAFESUBST:<noinclude />#if:{{{5|}}}
 |, [[:User:{{SAFESUBST:<noinclude />BASEPAGENAME:{{{5|Example}}}}}|{{SAFESUBST:<noinclude />BASEPAGENAME:{{{label5|{{{5|Example}}}}}}}}]]{{SAFESUBST:<noinclude />#if:{{{6|}}}
 |, [[:User:{{SAFESUBST:<noinclude />BASEPAGENAME:{{{6|Example}}}}}|{{SAFESUBST:<noinclude />BASEPAGENAME:{{{label6|{{{6|Example}}}}}}}}]]{{SAFESUBST:<noinclude />#if:{{{7|}}}
 |, [[:User:{{SAFESUBST:<noinclude />BASEPAGENAME:{{{7|Example}}}}}|{{SAFESUBST:<noinclude />BASEPAGENAME:{{{label7|{{{7|Example}}}}}}}}]]
      }}
     }}
    }}
   }}
  }}
 }}{{{p|:}}}</span>
 |{{SAFESUBST:<noinclude />Error|Error in [[Template:Replyto]]: Username not given.}}
}}<noinclude>

{{documentation}}
</noinclude>',
					'MediaWiki:Signature' => '[[User:$1|$2]] {{#ifeq:{{FULLPAGENAME}}|User talk:$1|([[User talk:$1#top|talk]])|([[User talk:$1|talk]])}}',
				],
				'title' => 'User_talk:PatHadley',
				'expected' => [
					[
						'type' => 'mention',
						'agent' => 'PatHadley',
						'section-title' => 'Wizardry required',
					],
					[
						'type' => 'edit-user-talk',
						'agent' => 'PatHadley',
						'section-title' => 'Wizardry required',
					],
				],
				'precondition' => 'isParserFunctionsInstalled',
			],
			[
				'new' => 647260329,
				'old' => 647258025,
				'username' => 'Kudpung',
				'lang' => 'en',
				'pages' => [
					'Template:U' => '[[User:{{{1}}}|{{<includeonly>safesubst:</includeonly>#if:{{{2|}}}|{{{2}}}|{{{1}}}}}]]<noinclude>{{documentation}}</noinclude>',
				],
				'title' => 'User_talk:Kudpung',
				'expected' => [
					[
						'type' => 'mention',
						'agent' => 'Kudpung',
						'section-title' => 'Me?',
					],
					[
						'type' => 'edit-user-talk',
						'agent' => 'Kudpung',
						'section-title' => 'Me?',
					],
				],
			],
			// T68512, leading colon in user page link in signature
			[
				'new' => 612485855,
				'old' => 612485595,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'User_talk:Admin',
				'expected' => [
					[
						'type' => 'mention',
						'agent' => 'Admin',
						'section-title' => 'Hi',
					],
					[
						'type' => 'edit-user-talk',
						'agent' => 'Admin',
						'section-title' => 'Hi',
					],
				],
				'precondition' => 'isParserFunctionsInstalled',
			],
			// T154406 unintended mentions when changing content
			[
				'new' => 987667999,
				'old' => 987667998,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'MultipleSignatureMentions',
				'expected' => [],
			],
			[
				'new' => 1234,
				'old' => 123,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'Pings in summary',
				'expected' => [
					[
						'type' => 'mention-summary',
						'agent' => 'Admin',
						'section-title' => null,
					]
				],
				'precondition' => '',
				'summary' => 'Hey [[User:Werdna|Werdna]] and [[User:Jorm]], [[User:Admin]] here',
			],
		];
	}

	/**
	 * @dataProvider generateEventsForRevisionDataProvider
	 */
	public function testGenerateEventsForRevision(
		$newId, $oldId, $username, $lang, $pages, $title, $expected, $precondition = '',
		$summary = ''
	) {
		if ( $precondition !== '' ) {
			$result = $this->$precondition();
			if ( $result !== true ) {
				$this->markTestSkipped( $result );

				return;
			}
		}

		$this->setupAllTestUsers();

		$revision = $this->setupTestRevisionsForEventGeneration(
			$newId, $oldId, $username, $lang, $pages, $title, $summary
		);
		$events = [];
		$this->setupEventCallbackForEventGeneration(
			static function ( Event $event ) use ( &$events ) {
				$events[] = [
					'type' => $event->getType(),
					'agent' => $event->getAgent()->getName(),
					'section-title' => $event->getExtraParam( 'section-title' ),
				];
				return false;
			}
		);

		$this->overrideConfigValues( [
			// disable mention failure and success notifications
			'EchoMentionStatusNotifications' => false,
			// enable pings from summary
			'EchoMaxMentionsInEditSummary' => 5,
		] );
		$this->clearHook( 'EchoGetEventsForRevision' );

		DiscussionParser::generateEventsForRevision( $revision, false );

		$this->assertEquals( $expected, $events );
	}

	public static function provider_generateEventsForRevision_mentionStatus() {
		return [
			[
				'new' => 747747748,
				'old' => 747747747,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'UTPage',
				'expected' => [
					[
						'type' => 'mention-failure',
						'agent' => 'Admin',
						'section-title' => 'Hello Users',
						'subject-name' => 'Ping',
					],
					[
						'type' => 'mention-failure',
						'agent' => 'Admin',
						'section-title' => 'Hello Users',
						'subject-name' => 'Po?ng',
					],
				],
			],
			[
				'new' => 747747750,
				'old' => 747747747,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'UTPage',
				'expected' => [
					[
						'type' => 'mention',
						'agent' => 'Admin',
						'section-title' => 'Hello Users',
						'subject-name' => null,
					],
					[
						'type' => 'mention-success',
						'agent' => 'Admin',
						'section-title' => 'Hello Users',
						'subject-name' => 'Test11',
					],
				],
			],
			[
				'new' => 747798766,
				'old' => 747798765,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'UTPage',
				'expected' => [
					[
						'type' => 'mention-failure',
						'agent' => 'Admin',
						'section-title' => 'Section 2',
						'subject-name' => 'NoUser',
					],
				],
			],
			[
				'new' => 747798767,
				'old' => 747798765,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'UTPage',
				'expected' => [
					[
						'type' => 'mention-failure',
						'agent' => 'Admin',
						'section-title' => 'Section 2',
						'subject-name' => 'NoUser',
					],
				],
			],
			[
				'new' => 747798768,
				'old' => 747798765,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'UTPage',
				'expected' => [],
			],
			[
				'new' => 747798770,
				'old' => 747798765,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'UTPage',
				'expected' => [
					[
						'type' => 'mention',
						'agent' => 'Admin',
						'section-title' => 'Section 1.5',
						'subject-name' => null,
					],
					[
						'type' => 'mention-success',
						'agent' => 'Admin',
						'section-title' => 'Section 1.5',
						'subject-name' => 'Test11',
					],
				],
			],
			[
				'new' => 747798771,
				'old' => 747798765,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'UTPage',
				'expected' => [
					[
						'type' => 'mention-failure',
						'agent' => 'Admin',
						'section-title' => 'Section 1.5',
						'subject-name' => 'NoUser1.5',
					],
					[
						'type' => 'mention-failure',
						'agent' => 'Admin',
						'section-title' => 'Section 2',
						'subject-name' => 'NoUser2',
					],
				],
			],
			[
				'new' => 747798772,
				'old' => 747798765,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'UTPage',
				'expected' => [
					[
						'type' => 'mention-failure',
						'agent' => 'Admin',
						'section-title' => 'Section 1',
						'subject-name' => 'NoUser1',
					],
					[
						'type' => 'mention-failure',
						'agent' => 'Admin',
						'section-title' => 'Section 1.75',
						'subject-name' => 'NoUser1.75',
					],
					[
						'type' => 'mention-failure',
						'agent' => 'Admin',
						'section-title' => 'Section 2',
						'subject-name' => 'NoUser2',
					],
				],
				[
					'new' => 987654322,
					'old' => 987654321,
					'username' => 'Admin',
					'lang' => 'en',
					'pages' => [],
					'title' => 'User_talk:Admin',
					'expected' => [ [
						'type' => 'edit-user-talk',
						'agent' => 'Admin',
						'section-title' => false,
						'subject-name' => null,
					] ],
				],
				[
					'new' => 987654323,
					'old' => 987654321,
					'username' => 'Admin',
					'lang' => 'en',
					'pages' => [],
					'title' => 'User_talk:Admin',
					'expected' => [
						[
							'type' => 'mention',
							'agent' => 'Admin',
							'section-title' => 'Section 1',
							'subject-name' => null,
						],
						[
							'type' => 'mention-success',
							'agent' => 'Admin',
							'section-title' => 'Section 1',
							'subject-name' => 'Test11',
						],
						[
							'type' => 'edit-user-talk',
							'agent' => 'Admin',
							'section-title' => 'Section 1',
							'subject-name' => null,
						],
					],
				],
			],
			[
				'new' => 987654324,
				'old' => 987654321,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'User_talk:Admin',
				'expected' => [
					[
						'type' => 'mention',
						'agent' => 'Admin',
						'section-title' => 'Section 1',
						'subject-name' => null,
					],
					[
						'type' => 'mention-success',
						'agent' => 'Admin',
						'section-title' => 'Section 1',
						'subject-name' => 'Test11',
					],
					[
						'type' => 'edit-user-talk',
						'agent' => 'Admin',
						'section-title' => false,
						'subject-name' => null,
					],
				],
			],
			[
				'new' => 987654325,
				'old' => 987654321,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'User_talk:Admin',
				'expected' => [
					[
						'type' => 'mention',
						'agent' => 'Admin',
						'section-title' => 'Section 2',
						'subject-name' => null,
					],
					[
						'type' => 'mention-success',
						'agent' => 'Admin',
						'section-title' => 'Section 2',
						'subject-name' => 'Test11',
					],
					[
						'type' => 'edit-user-talk',
						'agent' => 'Admin',
						'section-title' => 'Section 2',
						'subject-name' => null,
					],
				],
			],
			[
				'new' => 987654401,
				'old' => 987654400,
				'username' => 'Admin',
				'lang' => 'en',
				'pages' => [],
				'title' => 'UTPage2',
				'expected' => [
					[
						'type' => 'mention',
						'agent' => 'Admin',
						'section-title' => false,
						'subject-name' => null,
					],
					[
						'type' => 'mention-success',
						'agent' => 'Admin',
						'section-title' => false,
						'subject-name' => 'Test11',
					],
				],
			],
		];
	}

	/**
	 * @dataProvider provider_generateEventsForRevision_mentionStatus
	 */
	public function testGenerateEventsForRevision_mentionStatus(
		$newId, $oldId, $username, $lang, $pages, $title, $expected
	) {
		$this->setupAllTestUsers();

		$revision = $this->setupTestRevisionsForEventGeneration(
			$newId, $oldId, $username, $lang, $pages, $title
		);
		$events = [];
		$this->setupEventCallbackForEventGeneration(
			static function ( Event $event ) use ( &$events ) {
				$events[] = [
					'type' => $event->getType(),
					'agent' => $event->getAgent()->getName(),
					'section-title' => $event->getExtraParam( 'section-title' ),
					'subject-name' => $event->getExtraParam( 'subject-name' ),
				];
				return false;
			}
		);

		$this->overrideConfigValues( [
			// enable mention failure and success notifications
			'EchoMentionStatusNotifications' => true,
			// enable multiple sections mentions
			'EchoMentionsOnMultipleSectionEdits' => true,
		] );
		$this->clearHook( 'EchoGetEventsForRevision' );

		DiscussionParser::generateEventsForRevision( $revision, false );

		$this->assertEquals( $expected, $events );
	}

	public static function provider_extractSections() {
		return [
			[
				'content' => 'Just Text.',
				'result' => [
					[
						'header' => false,
						'content' => 'Just Text.',
					],
				],
			],
			[
				'content' =>
<<<TEXT
Text and a
== Headline ==
with text
TEXT
				,
				'result' => [
					[
						'header' => false,
						'content' =>
<<<TEXT
Text and a
TEXT
						,
					],
					[
						'header' => 'Headline',
						'content' =>
<<<TEXT
== Headline ==
with text
TEXT
						,
					],
				],
			],
			[
				'content' =>
<<<TEXT
== Headline ==
Text and a [[User:Test]]
TEXT
			,
				'result' => [
					[
						'header' => 'Headline',
						'content' =>
<<<TEXT
== Headline ==
Text and a [[User:Test]]
TEXT
					,
					],
				],
			],
			[
				'content' =>
<<<TEXT
Content 0
== Headline 1 ==
Content 1
=== Headline 2 ===
Content 2
TEXT
			,
				'result' => [
					[
						'header' => false,
						'content' => 'Content 0',
					],
					[
						'header' => 'Headline 1',
						'content' =>
<<<TEXT
== Headline 1 ==
Content 1
TEXT
					,
					],
					[
						'header' => 'Headline 2',
						'content' =>
<<<TEXT
=== Headline 2 ===
Content 2
TEXT
					,
					],
				],
			],
			[
				'content' =>
<<<TEXT
== Headline 1 ==
مرحبا كيف حالك
=== Headline 2 ===
انا بخير شكرا
TEXT
			,
				'result' => [
					[
						'header' => 'Headline 1',
						'content' =>
<<<TEXT
== Headline 1 ==
مرحبا كيف حالك
TEXT
					,
					],
					[
						'header' => 'Headline 2',
						'content' =>
<<<TEXT
=== Headline 2 ===
انا بخير شكرا
TEXT
					,
					],
				],
			],
			[
				'content' =>
<<<TEXT
مرحبا كيف حالك
=== Headline 1 ===
انا بخير شكرا
TEXT
			,
				'result' => [
					[
						'header' => false,
						'content' =>
<<<TEXT
مرحبا كيف حالك
TEXT
					,
					],
					[
						'header' => 'Headline 1',
						'content' =>
<<<TEXT
=== Headline 1 ===
انا بخير شكرا
TEXT
					,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider provider_extractSections
	 */
	public function testExtractSections( $content, $result ) {
		$discussionParser = TestingAccessWrapper::newFromClass( DiscussionParser::class );
		$sections = $discussionParser->extractSections( $content );

		$this->assertEquals( $result, $sections );
	}

	public function testGenerateEventsForRevision_tooManyMentionsFailure() {
		$expected = [
			[
				'type' => 'mention-failure-too-many',
				'agent' => 'Admin',
				'section-title' => 'Hello Users',
				'max-mentions' => 5,
			],
		];

		$this->setupTestUser( 'Admin' );
		$revision = $this->setupTestRevisionsForEventGeneration( 747747749, 747747747, 'Admin', 'en', [], 'UTPage' );

		$events = [];
		$this->setupEventCallbackForEventGeneration(
			static function ( Event $event ) use ( &$events ) {
				$events[] = [
					'type' => $event->getType(),
					'agent' => $event->getAgent()->getName(),
					'section-title' => $event->getExtraParam( 'section-title' ),
					'max-mentions' => $event->getExtraParam( 'max-mentions' ),
				];
				return false;
			}
		);

		$this->overrideConfigValues( [
			// enable mention failure and success notifications
			'EchoMentionStatusNotifications' => true,
			// lower limit for the mention-failure-too-many notification
			'EchoMaxMentionsCount' => 5
		] );
		$this->clearHook( 'EchoGetEventsForRevision' );

		DiscussionParser::generateEventsForRevision( $revision, false );

		$this->assertEquals( $expected, $events );
	}

	private function setupTestRevisionsForEventGeneration( $newId, $oldId, $username, $lang, $pages,
		$title, $summary = ''
	) {
		$services = $this->getServiceContainer();
		$store = $services->getRevisionStore();
		// Content language is used by the code that interprets the namespace part of titles
		// (Title::getTitleParser), so should be the fake language ;)
		$this->setContentLang( $lang );
		$this->overrideConfigValues( [
			// this one allows MediaWiki:xyz pages to be set as messages
			MainConfigNames::UseDatabaseMessages => true
		] );

		// pages to be created: templates may be used to ping users (e.g.
		// {{u|...}}) but if we don't have that template, it just won't work!
		$pages += [ $title => '' ];

		$user = $this->getTestUser()->getUser();
		$wikiPageFactory = $services->getWikiPageFactory();
		foreach ( $pages as $pageTitle => $pageText ) {
			$template = $wikiPageFactory->newFromTitle( Title::newFromText( $pageTitle ) );
			$template->doUserEditContent( new WikitextContent( $pageText ), $user, '' );
		}

		// force i18n messages to be reloaded from MessageCache (from DB, where a new message
		// might have been created as page)
		$services->resetServiceForTesting( 'MessageCache' );

		// grab revision excerpts (didn't include them in this src file because
		// they can be pretty long)
		$oldText = file_get_contents( __DIR__ . '/revision_txt/' . $oldId . '.txt' );
		$newText = file_get_contents( __DIR__ . '/revision_txt/' . $newId . '.txt' );

		// revision texts can be in different languages, where links etc are
		// different (e.g. User: becomes Benutzer: in German), so let's pretend
		// the page they belong to is from that language
		$title = Title::newFromText( $title );
		$object = new ReflectionObject( $title );
		$property = $object->getProperty( 'mDbPageLanguage' );
		$property->setAccessible( true );
		$property->setValue( $title, $lang );

		// create stub MutableRevisionRecord object
		$revision = new MutableRevisionRecord( $title );

		$content = new WikitextContent( $newText );

		$revision->setId( $newId );
		$revision->setUser( User::newFromName( $username ) );

		$revision->setParentId( $oldId );
		$comment = CommentStoreComment::newUnsavedComment(
			$summary,
			null
		);
		$revision->setComment( $comment );
		$revision->setContent( SlotRecord::MAIN, $content );

		$userName = $revision->getUser()->getName();

		// generate diff between 2 revisions
		$changes = DiscussionParser::getMachineReadableDiff( $oldText, $newText );
		$output = DiscussionParser::interpretDiff( $changes, $userName, $title );

		// store diff in some local cache var, to circumvent
		// DiscussionParser::getChangeInterpretationForRevision's attempt to
		// retrieve parent revision from DB
		$cacheKey = $revision->getId() . '|' . $revision->getPage()->getNamespace() . '|' . $revision->getPage()->getDBkey();
		TestingAccessWrapper::newFromClass( DiscussionParser::class )->revisionInterpretationCache = [ $cacheKey => $output ];
		return $revision;
	}

	private function setupEventCallbackForEventGeneration( callable $callback ) {
		$this->setTemporaryHook( 'BeforeEchoEventInsert', $callback );
	}

	public function testTimestampRegex() {
		$exemplarTimestamp = self::EXEMPLAR_TIMESTAMP;
		$timestampRegex = DiscussionParser::getTimestampRegex();

		$match = preg_match( '/' . $timestampRegex . '/u', $exemplarTimestamp );
		$this->assertSame( 1, $match );
	}

	public function testTimestampRegex_T264922() {
		$this->overrideConfigValue( MainConfigNames::LanguageCode, 'skr' );
		$this->assertIsString( DiscussionParser::getTimestampRegex(), 'does not fail' );
	}

	public function testGetTimestampPosition() {
		$line = 'Hello World. ' . self::EXEMPLAR_TIMESTAMP;
		$pos = DiscussionParser::getTimestampPosition( $line );
		$this->assertSame( 13, $pos );
	}

	/**
	 * @dataProvider signingDetectionDataProvider
	 * FIXME some of the app logic is in the test...
	 */
	public function testSigningDetection( $line, $expectedUser ) {
		if ( is_array( $expectedUser ) ) {
			$this->setupTestUser( $expectedUser[1] );
		}

		if ( !DiscussionParser::isSignedComment( $line ) ) {
			$this->assertFalse( $expectedUser );
			return;
		}

		$output = DiscussionParser::getUserFromLine( $line );

		if ( $output === false ) {
			$this->assertFalse( $expectedUser );
		} elseif ( is_array( $expectedUser ) ) {
			// Sometimes testing for correct user detection,
			// sometimes testing for offset detection
			$this->assertEquals( $expectedUser, $output );
		} else {
			$this->assertEquals( $expectedUser, $output[1] );
		}
	}

	public static function signingDetectionDataProvider() {
		$ts = self::EXEMPLAR_TIMESTAMP;

		return [
			// Basic
			[
				"I like this. [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts",
				[
					13,
					'Werdna'
				],
			],
			// Confounding
			[
				"[[User:Jorm]] is a meanie. --[[User:Werdna2|Andrew]] $ts",
				[
					29,
					"Werdna2"
				],
			],
			// Talk page link only
			[
				"[[User:Swalling|Steve]] is the best person I have ever met. --[[User talk:Werdna3|Andrew]] $ts",
				[
					62,
					'Werdna3'
				],
			],
			// Anonymous user
			[
				"I am anonymous because I like my IP address. --[[Special:Contributions/127.0.0.1|127.0.0.1]] $ts",
				[
					47,
					'127.0.0.1'
				],
			],
			// No signature
			[
				"Well, \nI do think that [[User:Newyorkbrad]] is pretty cool, but what do I know?",
				false
			],
			// Hash symbols in usernames
			[
				"What do you think? [[User talk:We buried our secrets in the garden#top|wbositg]] $ts",
				[
					19,
					'We buried our secrets in the garden'
				],
			],
			// Title that gets normalized different than it is provided in the wikitext
			[
				"Beep boop [[User:I_Heart_Spaces]] ([[User_talk:I_Heart_Spaces]]) $ts",
				[
					strlen( "Beep boop " ),
					'I Heart Spaces'
				],
			],
			// Accepts ] in the pipe
			[
				"Shake n Bake --[[User:Werdna4|wer]dna]] $ts",
				[
					strlen( "Shake n Bake --" ),
					'Werdna4',
				],
			],

			[
				"xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxã? [[User:Jam]] $ts",
				[
					strlen( "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxã? " ),
					"Jam"
				],
			],
			// extra long signature
			[
				"{{U|He7d3r}}, xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxã? [[User:Reverta-me|<span style=\"font-size:13px; color:blue;font-family:Lucida Handwriting;text-shadow:aqua 5px 3px 12px;\">Aaaaa Bbbbbbb</span>]]'' <sup>[[User Talk:Reverta-me|<font color=\"gold\" face=\"Lucida Calligraphy\">Discussão</font>]]</sup>''",
				[
					strlen( "{{U|He7d3r}}, xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxã? " ),
					'Reverta-me',
				],
			],
			// Bug: T87852
			[
				"Test --[[Benutzer:Schnark]] ([[Benutzer:Schnark/js|js]])",
				[
					strlen( "Test --" ),
					'Schnark',
				],
			],
			// when adding additional tests, make sure to add the non-anon users
			// to DiscussionParserTest::$testUsers - the DiscussionParser
			// needs the users to exist, because it'll generate a comparison
			// signature, which is different when the user is considered anon
		];
	}

	/** @dataProvider diffDataProvider */
	public function testDiff( $oldText, $newText, $expected ) {
		$actual = DiscussionParser::getMachineReadableDiff( $oldText, $newText );
		unset( $actual['_info'] );
		unset( $expected['_info'] );

		$this->assertEquals( $expected, $actual );
	}

	public static function diffDataProvider() {
		return [
			[
				<<<TEXT
line 1
line 2
line 3
line 4
TEXT
			, <<<TEXT
line 1
line 3
line 4
TEXT
			,
				[ [
					'action' => 'subtract',
					'content' => 'line 2',
					'left-pos' => 2,
					'right-pos' => 2,
				] ]
			],
			[
				<<<TEXT
line 1
line 2
line 3
line 4
TEXT
			, <<<TEXT
line 1
line 2
line 2.5
line 3
line 4
TEXT
			,
				[ [
					'action' => 'add',
					'content' => 'line 2.5',
					'left-pos' => 3,
					'right-pos' => 3,
				] ]
			],
			[
				<<<TEXT
line 1
line 2
line 3
line 4
TEXT
			, <<<TEXT
line 1
line b
line 3
line 4
TEXT
			,
				[ [
					'action' => 'change',
					'old_content' => 'line 2',
					'new_content' => 'line b',
					'left-pos' => 2,
					'right-pos' => 2,
				] ]
			],
			[
				<<<TEXT
line 1
line 2
line 3
line 4
TEXT
			, <<<TEXT
line 1
line b
line c
line d
line 3
line 4
TEXT
			,
				[
					[
						'action' => 'change',
						'old_content' => 'line 2',
						'new_content' => 'line b',
						'left-pos' => 2,
						'right-pos' => 2,
					],
					[
						'action' => 'add',
						'content' => 'line c
line d',
						'left-pos' => 3,
						'right-pos' => 3,
					],
				],
			],
		];
	}

	/** @dataProvider annotationDataProvider */
	public function testAnnotation( $message, $diff, $user, $expectedAnnotation ) {
		$this->setupTestUser( $user );
		$actual = DiscussionParser::interpretDiff( $diff, $user );
		$this->assertEquals( $expectedAnnotation, $actual, $message );
	}

	public static function annotationDataProvider() {
		$ts = self::EXEMPLAR_TIMESTAMP;

		return [

			[
				'Must detect added comments',
				// Diff
				[
					// Action
					[
						'action' => 'add',
						'content' => ":What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts",
						'left-pos' => 3,
						'right-pos' => 3,
					],
					'_info' => [
						'lhs' => [
							'== Section 1 ==',
							"I do not like you. [[User:Jorm|Jorm]] ([[User talk:Jorm|talk]]) $ts",
						],
						'rhs' => [
							'== Section 1 ==',
							"I do not like you. [[User:Jorm|Jorm]] ([[User talk:Jorm|talk]]) $ts",
							":What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts",
						],
					],
				],
				// User
				'Werdna',
				// Expected annotation
				[
					[
						'type' => 'add-comment',
						'content' => ":What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts",
						'full-section' => <<<TEXT
== Section 1 ==
I do not like you. [[User:Jorm|Jorm]] ([[User talk:Jorm|talk]]) $ts
:What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts
TEXT
					],
				],
			],

			[
				'Full Section must not include the following pre-existing section',
				// Diff
				[
					// Action
					[
						'action' => 'add',
						'content' => ":What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts",
						'left-pos' => 3,
						'right-pos' => 3,
					],
					'_info' => [
						'lhs' => [
							'== Section 1 ==',
							"I do not like you. [[User:Jorm|Jorm]] ([[User talk:Jorm|talk]]) $ts",
							'== Section 2 ==',
							"Well well well. [[User:DarTar|DarTar]] ([[User talk:DarTar|talk]]) $ts",
						],
						'rhs' => [
							'== Section 1 ==',
							"I do not like you. [[User:Jorm|Jorm]] ([[User talk:Jorm|talk]]) $ts",
							":What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts",
							'== Section 2 ==',
							"Well well well. [[User:DarTar|DarTar]] ([[User talk:DarTar|talk]]) $ts",
						],
					],
				],
				// User
				'Werdna',
				// Expected annotation
				[
					[
						'type' => 'add-comment',
						'content' => ":What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts",
						'full-section' => <<<TEXT
== Section 1 ==
I do not like you. [[User:Jorm|Jorm]] ([[User talk:Jorm|talk]]) $ts
:What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts
TEXT
					],
				],
			],

			[
				'Must detect new-section-with-comment when a new section is added',
				// Diff
				[
					// Action
					[
						'action' => 'add',
						'content' => <<<TEXT
== Section 1a ==
Hmmm? [[User:Jdforrester|Jdforrester]] ([[User talk:Jdforrester|talk]]) $ts
TEXT
					,
						'left-pos' => 4,
						'right-pos' => 4,
					],
					'_info' => [
						'lhs' => [
							'== Section 1 ==',
							"I do not like you. [[User:Jorm|Jorm]] ([[User talk:Jorm|talk]]) $ts",
							":What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts",
							'== Section 2 ==',
							"Well well well. [[User:DarTar|DarTar]] ([[User talk:DarTar|talk]]) $ts",
						],
						'rhs' => [
							'== Section 1 ==',
							"I do not like you. [[User:Jorm|Jorm]] ([[User talk:Jorm|talk]]) $ts",
							":What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts",
							'== Section 1a ==',
							'Hmmm? [[User:Jdforrester|Jdforrester]] ([[User talk:Jdforrested|talk]]) $ts',
							'== Section 2 ==',
							"Well well well. [[User:DarTar|DarTar]] ([[User talk:DarTar|talk]]) $ts",
						],
					],
				],
				// User
				'Jdforrester',
				// Expected annotation
				[
					[
						'type' => 'new-section-with-comment',
						'content' => <<<TEXT
== Section 1a ==
Hmmm? [[User:Jdforrester|Jdforrester]] ([[User talk:Jdforrester|talk]]) $ts
TEXT
					,
					],
				],
			],

			[
				'Must detect multiple added comments when multiple sections are edited',
				DiscussionParser::getMachineReadableDiff(
					<<<TEXT
== Section 1 ==
I do not like you. [[User:Jorm|Jorm]] ([[User talk:Jorm|talk]]) $ts
:What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts
== Section 2 ==
Well well well. [[User:DarTar|DarTar]] ([[User talk:DarTar|talk]]) $ts
== Section 3 ==
Hai [[User:Bsitu|Bsitu]] ([[User talk:Bsitu|talk]]) $ts
TEXT
,
					<<<TEXT
== Section 1 ==
I do not like you. [[User:Jorm|Jorm]] ([[User talk:Jorm|talk]]) $ts
:What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts
:New Comment [[User:JarJar|JarJar]] ([[User talk:JarJar|talk]]) $ts
== Section 2 ==
Well well well. [[User:DarTar|DarTar]] ([[User talk:DarTar|talk]]) $ts
== Section 3 ==
Hai [[User:Bsitu|Bsitu]] ([[User talk:Bsitu|talk]]) $ts
:Other New Comment [[User:JarJar|JarJar]] ([[User talk:JarJar|talk]]) $ts
TEXT
				),
				// User
				'JarJar',
				// Expected annotation
				[
					[
						'type' => 'add-comment',
						'content' => ":New Comment [[User:JarJar|JarJar]] ([[User talk:JarJar|talk]]) $ts",
						'full-section' => <<<TEXT
== Section 1 ==
I do not like you. [[User:Jorm|Jorm]] ([[User talk:Jorm|talk]]) $ts
:What do you think? [[User:Werdna|Werdna]] ([[User talk:Werdna|talk]]) $ts
:New Comment [[User:JarJar|JarJar]] ([[User talk:JarJar|talk]]) $ts
TEXT
					],
					[
						'type' => 'add-comment',
						'content' => ":Other New Comment [[User:JarJar|JarJar]] ([[User talk:JarJar|talk]]) $ts",
						'full-section' => <<<TEXT
== Section 3 ==
Hai [[User:Bsitu|Bsitu]] ([[User talk:Bsitu|talk]]) $ts
:Other New Comment [[User:JarJar|JarJar]] ([[User talk:JarJar|talk]]) $ts
TEXT
					],
				],
			],

			[
				'Bug T78424',
				DiscussionParser::getMachineReadableDiff(
					<<<TEXT
== Washington Post Reception Source ==

''The Boston Post'' source that was used in the reception section has a couple of problems. First, it's actually a repost of ''The Washington Post'', but ''The Washington Post'' doesn't allow the Internet Archive to preserve it. Should it still be sourced to Boston or to Washington? Second, it seems to be a lot of analysis that can't be summed up easily without trimming it out, and doesn't really fit with the reception section and should probably moved next to Wilson's testimony. Any suggestions? --[[User:RAN1|RAN1]] ([[User talk:RAN1|talk]]) 01:44, 11 December 2014 (UTC)
TEXT
,
					<<<TEXT
== Washington Post Reception Source ==

''The Boston Post'' source that was used in the reception section has a couple of problems. First, it's actually a repost of ''The Washington Post'', but ''The Washington Post'' doesn't allow the Internet Archive to preserve it. Should it still be sourced to Boston or to Washington? Second, it seems to be a lot of analysis that can't be summed up easily without trimming it out, and doesn't really fit with the reception section and should probably moved next to Wilson's testimony. Any suggestions? --[[User:RAN1|RAN1]] ([[User talk:RAN1|talk]]) 01:44, 11 December 2014 (UTC)

== Grand jury no bill reception ==

{{u|Bob K31416}} has started a process of summarizing that section, in a manner that I believe it to be counter productive. We have expert opinions from legal, law enforcement, politicians, and media outlets all of which are notable and informative. [[WP:NOTPAPER|Wikipedia is not paper]] – If the section is too long, the correct process to avoid losing good content that is well sources, is to create a sub-article with all the detail, and summarize here per [[WP:SUMMARY]]. But deleting useful and well sourced material, is not acceptable. We are here to build an encyclopedia. - [[User:Cwobeel|<span style="color:#339966">Cwobeel</span>]] [[User_talk:Cwobeel|<span style="font-size:80%">(talk)</span>]] 16:02, 11 December 2014 (UTC)
TEXT
				),
				// User
				'Cwobeel',
				// Expected annotation
				[
					[
						'type' => 'new-section-with-comment',
						'content' => '== Grand jury no bill reception ==

{{u|Bob K31416}} has started a process of summarizing that section, in a manner that I believe it to be counter productive. We have expert opinions from legal, law enforcement, politicians, and media outlets all of which are notable and informative. [[WP:NOTPAPER|Wikipedia is not paper]] – If the section is too long, the correct process to avoid losing good content that is well sources, is to create a sub-article with all the detail, and summarize here per [[WP:SUMMARY]]. But deleting useful and well sourced material, is not acceptable. We are here to build an encyclopedia. - [[User:Cwobeel|<span style="color:#339966">Cwobeel</span>]] [[User_talk:Cwobeel|<span style="font-size:80%">(talk)</span>]] 16:02, 11 December 2014 (UTC)',
					],
				],
			],
			// when adding additional tests, make sure to add the non-anon users
			// to DiscussionParserTest::$testusers - the DiscussionParser
			// needs the users to exist, because it'll generate a comparison
			// signature, which is different when the user is considered anon
		];
	}

	public static function provider_detectSectionTitleAndText() {
		// See DiscussionParserTest::$testUsers
		$name = 'Werdna';
		$comment = self::signedMessage( $name );

		return [
			[
				'Must detect first sub heading when inserting in the middle of two sub headings',
				// expected header content
				'Sub Heading 1',
				// test content format
				"
== Heading ==
$comment

== Sub Heading 1 ==
$comment
%s

== Sub Heading 2 ==
$comment
				",
				// user signing new comment
				$name
			],

			[
				'Must detect second sub heading when inserting in the end of two sub headings',
				// expected header content
				'Sub Heading 2',
				// test content format
				"
== Heading ==
$comment

== Sub Heading 1 ==
$comment

== Sub Heading 2 ==
$comment
%s
				",
				// user signing new comment
				$name
			],

			[
				'Commenting in multiple sub-headings must result in no section link',
				// expected header content
				'',
				// test content format
				"
== Heading ==
$comment

== Sub Heading 1 ==
$comment
%s

== Sub Heading 2 ==
$comment
%s

				",
				// user signing new comment
				$name
			],

			[
				'Must accept headings without a space between the = and the section name',
				// expected header content
				'Heading',
				// test content format
				"
==Heading==
$comment
%s
				",
				// user signing new comment
				$name
			],

			[
				'Must not accept invalid headings split with a return',
				// expected header content
				'',
				// test content format
				"
==Some
Heading==
$comment
%s
				",
				// user signing new comment
				$name
			],
		];
	}

	/**
	 * @dataProvider provider_detectSectionTitleAndText
	 */
	public function testDetectSectionTitleAndText( $message, $expect, $format, $name ) {
		$this->setupTestUser( $name );

		// str_replace because we want to replace multiple instances of '%s' with the same value
		$before = str_replace( '%s', '', $format );
		$after = str_replace( '%s', self::signedMessage( $name ), $format );

		$diff = DiscussionParser::getMachineReadableDiff( $before, $after );
		$interp = DiscussionParser::interpretDiff( $diff, $name );

		// There should be a section-text only if there is section-title
		$expectText = $expect ? self::message( $name ) : '';
		$this->assertEquals(
			[ 'section-title' => $expect, 'section-text' => $expectText ],
			DiscussionParser::detectSectionTitleAndText( $interp ),
			$message
		);
	}

	protected static function signedMessage( $name ) {
		return ": " . self::message() . " [[User:$name|$name]] ([[User talk:$name|talk]]) 00:17, 7 May 2013 (UTC)";
	}

	protected static function message() {
		return 'foo';
	}

	public static function provider_getFullSection() {
		$tests = [
			[
				'Extracts full section',
				// Full document content
				<<<TEXT
==Header 1==
foo
===Header 2===
bar
==Header 3==
baz
TEXT
			,
				// Map of Line numbers to expanded section content
				[
					1 => "==Header 1==\nfoo",
					2 => "==Header 1==\nfoo",
					3 => "===Header 2===\nbar",
					4 => "===Header 2===\nbar",
					5 => "==Header 3==\nbaz",
					6 => "==Header 3==\nbaz",
				],
			],
		];

		// Allow for setting an array of line numbers to expand from rather than
		// just a single line number
		$retval = [];
		foreach ( $tests as $test ) {
			foreach ( $test[2] as $lineNum => $expected ) {
				$retval[] = [
					$test[0],
					$expected,
					$test[1],
					$lineNum,
				];
			}
		}

		return $retval;
	}

	/**
	 * @dataProvider provider_getFullSection
	 */
	public function testGetFullSection( $message, $expect, $lines, $startLineNum ) {
		$section = DiscussionParser::getFullSection( explode( "\n", $lines ), $startLineNum );
		$this->assertEquals( $expect, $section, $message );
	}

	public function testGetSectionCount() {
		$one = "==Zomg==\nfoobar\n";
		$two = "===SubZomg===\nHi there\n";
		$three = "==Header==\nOh Hai!\n";

		$this->assertSame( 1, DiscussionParser::getSectionCount( $one ) );
		$this->assertSame( 2, DiscussionParser::getSectionCount( $one . $two ) );
		$this->assertSame( 2, DiscussionParser::getSectionCount( $one . $three ) );
		$this->assertSame( 3, DiscussionParser::getSectionCount( $one . $two . $three ) );
		$this->assertSame( 30, DiscussionParser::getSectionCount(
			file_get_contents( __DIR__ . '/revision_txt/637638133.txt' )
		) );
	}

	public function testGetOverallUserMentionsCount() {
		$userMentions = [
			'validMentions' => [ 1 => 1 ],
			'unknownUsers' => [ 'NotKnown1', 'NotKnown2' ],
			'anonymousUsers' => [ '127.0.0.1' ],
		];

		$discussionParser = TestingAccessWrapper::newFromClass( DiscussionParser::class );
		$this->assertSame( 4, $discussionParser->getOverallUserMentionsCount( $userMentions ) );
	}

	public static function provider_getUserMentions() {
		return [
			[
				[ 'NotKnown1' => 0 ],
				[
					'validMentions' => [],
					'unknownUsers' => [ 'NotKnown1' ],
					'anonymousUsers' => [],
				],
				1
			],
			[
				[ '127.0.0.1' => 0 ],
				[
					'validMentions' => [],
					'unknownUsers' => [],
					'anonymousUsers' => [ '127.0.0.1' ],
				],
				1
			],
		];
	}

	/**
	 * @dataProvider provider_getUserMentions
	 */
	public function testGetUserMentions( $userLinks, $expectedUserMentions, $agent ) {
		$title = Title::newFromText( 'Test' );
		$discussionParser = TestingAccessWrapper::newFromClass( DiscussionParser::class );
		$this->assertEquals( $expectedUserMentions, $discussionParser->getUserMentions( $title, $agent, $userLinks ) );
	}

	public function testGetUserMentions_validMention() {
		$userName = 'Admin';
		$this->setupTestUser( $userName );
		$userId = User::newFromName( $userName )->getId();
		$otherUserId = $userId + 1;
		$expectedUserMentions = [
			'validMentions' => [ $userId => $userId ],
			'unknownUsers' => [],
			'anonymousUsers' => [],
		];
		$userLinks = [ $userName => $userId ];
		$this->testGetUserMentions( $userLinks, $expectedUserMentions, $otherUserId );
	}

	public function testGetUserMentions_ownMention() {
		$userName = 'Admin';
		$this->setupTestUser( $userName );
		$userId = User::newFromName( 'Admin' )->getId();
		$expectedUserMentions = [
			'validMentions' => [],
			'unknownUsers' => [],
			'anonymousUsers' => [],
		];
		$userLinks = [ $userName => $userId ];
		$this->testGetUserMentions( $userLinks, $expectedUserMentions, $userId );
	}

	public function testGetUserMentions_tooManyMentions() {
		$userLinks = [
			'NotKnown1' => 0,
			'NotKnown2' => 0,
			'NotKnown3' => 0,
			'127.0.0.1' => 0,
			'127.0.0.2' => 0,
		];

		$this->overrideConfigValues( [
			// lower limit for the mention-too-many notification
			'EchoMaxMentionsCount' => 3
		] );

		$title = Title::newFromText( 'Test' );
		$discussionParser = TestingAccessWrapper::newFromClass( DiscussionParser::class );
		$this->assertSame( 4, $discussionParser->getOverallUserMentionsCount( $discussionParser->getUserMentions( $title, 1, $userLinks ) ) );
	}

	protected function isParserFunctionsInstalled() {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'ParserFunctions' ) ) {
			return true;
		} else {
			return "ParserFunctions not enabled";
		}
	}

	public function testGetTextSnippet() {
		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' );
		$this->assertEquals(
			'Page001',
			DiscussionParser::getTextSnippet(
				'[[:{{BASEPAGENAME}}]]',
				$lang,
				DiscussionParser::DEFAULT_SNIPPET_LENGTH,
				Title::newFromText( 'Page001' )
			)
		);
		$this->assertEquals(
			'Hello',
			DiscussionParser::getTextSnippet(
				'* Hello',
				$lang,
				DiscussionParser::DEFAULT_SNIPPET_LENGTH,
				null,
				true
			)
		);
		$this->assertEquals(
			'* Hello',
			DiscussionParser::getTextSnippet(
				'* Hello',
				$lang,
				DiscussionParser::DEFAULT_SNIPPET_LENGTH,
				null,
				false
			)
		);
	}
}
