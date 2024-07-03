<?php

use MediaWiki\EditPage\EditPage;
use MediaWiki\Extension\SpamBlacklist\BaseBlacklist;
use MediaWiki\Extension\SpamBlacklist\SpamBlacklist;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Tests\User\TempUser\TempUserTestTrait;
use MediaWiki\Title\Title;

/**
 * @group SpamBlacklist
 * @group Database
 * @covers \MediaWiki\Extension\SpamBlacklist\SpamBlacklist
 */
class SpamBlacklistTest extends MediaWikiIntegrationTestCase {

	use TempUserTestTrait;

	/**
	 * @var SpamBlacklist
	 */
	protected $spamFilter;

	/**
	 * Spam blacklist regexes. Examples taken from:
	 *
	 * @see https://meta.wikimedia.org/wiki/Spam_blacklist
	 * @see https://en.wikipedia.org/wiki/MediaWiki:Spam-blacklist
	 *
	 * via Flow extension
	 *
	 * @var array
	 */
	protected $blacklist = [ '\b01bags\.com\b', 'sytes\.net' ];

	/**
	 * Spam whitelist regexes. Examples taken from:
	 *
	 * @see https://en.wikipedia.org/wiki/MediaWiki:Spam-whitelist
	 *
	 * via Flow extension
	 *
	 * @var array
	 */
	protected $whitelist = [ 'a5b\.sytes\.net' ];

	public static function spamProvider() {
		return [
			'no spam' => [
				[ 'https://example.com' ],
				false,
			],
			'revision with spam, with additional non-spam' => [
				[ 'https://foo.com', 'http://01bags.com', 'http://bar.com' ],
				[ '01bags.com' ],
			],

			'revision with spam using full width stop normalization' => [
				[ 'http://01bags．com' ],
				[ '01bags.com' ],
			],

			'revision with domain blacklisted as spam, but subdomain whitelisted' => [
				[ 'http://a5b.sytes.net' ],
				false,
			],
		];
	}

	/**
	 * @dataProvider spamProvider
	 */
	public function testSpamTempAccounts( $links, $expected ) {
		$this->enableAutoCreateTempUser();
		$this->prepareGlobals();
		$tempUserCreator = MediaWikiServices::getInstance()->getTempUserCreator();
		$user = $tempUserCreator->create(
			null,
			new FauxRequest()
		)->getUser();
		$returnValue = $this->spamFilter->filter(
			$links,
			Title::newMainPage(),
			$user
		);
		$this->assertEquals( $expected, $returnValue );
	}

	/**
	 * @dataProvider spamProvider
	 */
	public function testSpamAnonEditing( $links, $expected ) {
		$this->disableAutoCreateTempUser();
		$this->prepareGlobals();

		$userFactory = $this->getServiceContainer()->getUserFactory();
		$user = $userFactory->newAnonymous();
		$returnValue = $this->spamFilter->filter(
			$links,
			Title::newMainPage(),
			$user
		);
		$this->assertEquals( $expected, $returnValue );
	}

	public static function spamEditProvider() {
		return [
			'no spam' => [
				'https://example.com',
				true,
			],
			'revision with spam, with additional non-spam' => [
				"https://foo.com\nhttp://01bags.com\nhttp://bar.com'",
				false,
			],

			'revision with domain blacklisted as spam, but subdomain whitelisted' => [
				'http://a5b.sytes.net',
				true,
			],
		];
	}

	/**
	 * @dataProvider spamEditProvider
	 */
	public function testSpamEdit( $text, $ok ) {
		$this->prepareGlobals();
		$fields = [
			'wpTextbox1' => $text,
			'wpUnicodeCheck' => EditPage::UNICODE_CHECK,
			'wpRecreate' => true,
		];

		$req = new FauxRequest( $fields, true );

		$page = $this->getNonexistingTestPage( __METHOD__ );
		$title = $page->getTitle();

		$articleContext = new RequestContext;
		$articleContext->setRequest( $req );
		$articleContext->setWikiPage( $page );
		$articleContext->setUser( $this->getTestUser()->getUser() );

		$article = new Article( $title );
		$ep = new EditPage( $article );
		$ep->setContextTitle( $title );

		$ep->importFormData( $req );

		$status = $ep->attemptSave( $result );

		if ( $ok ) {
			$this->assertStatusOK( $status );
		} else {
			$this->assertStatusError( 'spam-blacklisted-link', $status );
		}
	}

	private function prepareGlobals(): void {
		$this->setMwGlobals( 'wgBlacklistSettings', [
			'files' => [],
		] );

		BaseBlacklist::clearInstanceCache();

		// create spam filter
		$this->spamFilter = new SpamBlacklist;

		MediaWikiServices::getInstance()->getMessageCache()->enable();
		$this->insertPage( 'MediaWiki:Spam-blacklist', implode( "\n", $this->blacklist ) );
		$this->insertPage( 'MediaWiki:Spam-whitelist', implode( "\n", $this->whitelist ) );

		// That only works if the spam blacklist is really reset
		$instance = BaseBlacklist::getInstance( 'spam' );
		$reflProp = new ReflectionProperty( $instance, 'regexes' );
		$reflProp->setAccessible( true );
		$reflProp->setValue( $instance, false );
	}

	protected function tearDown(): void {
		MediaWikiServices::getInstance()->getMessageCache()->disable();
		parent::tearDown();
	}
}
