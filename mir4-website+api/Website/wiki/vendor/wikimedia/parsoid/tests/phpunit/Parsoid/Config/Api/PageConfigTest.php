<?php

namespace Test\Parsoid\Config\Api;

use Wikimedia\Parsoid\Config\Api\PageConfig;
use Wikimedia\Parsoid\Config\PageContent;
use Wikimedia\Parsoid\Mocks\MockSiteConfig;
use Wikimedia\Parsoid\Utils\Title;

/**
 * @covers \Wikimedia\Parsoid\Config\Api\PageConfig
 */
class PageConfigTest extends \PHPUnit\Framework\TestCase {

	private static $pageConfigs = [];

	protected function getPageConfig( string $id ): PageConfig {
		if ( !self::$pageConfigs ) {
			foreach (
				[
					'missing' => 'ThisPageDoesNotExist',
					'existing' => 'Help:Sample_page',
				] as $name => $titleText
			) {
				$helper = new TestApiHelper( $this, $name . 'page' );
				$siteConfig = new MockSiteConfig( [] );
				$title = Title::newFromText( $titleText, $siteConfig );
				self::$pageConfigs[$name] = new PageConfig( $helper, $siteConfig, [ 'title' => $title ] );
			}
		}

		return self::$pageConfigs[$id];
	}

	public function testGetTitle() {
		$this->assertSame(
			'ThisPageDoesNotExist',
			$this->getPageConfig( 'missing' )->getLinkTarget()->getPrefixedText()
		);
		$this->assertSame(
			'Help:Sample page',
			$this->getPageConfig( 'existing' )->getLinkTarget()->getPrefixedText()
		);
	}

	public function testGetNs() {
		$this->assertSame( 0, $this->getPageConfig( 'missing' )->getNs() );
		$this->assertSame( 12, $this->getPageConfig( 'existing' )->getNs() );
	}

	public function testGetPageId() {
		$this->assertSame( -1, $this->getPageConfig( 'missing' )->getPageId() );
		$this->assertSame( 53796160, $this->getPageConfig( 'existing' )->getPageId() );
	}

	public function testGetPageLanguage() {
		$this->assertEqualsIgnoringCase(
			'en',
			$this->getPageConfig( 'missing' )->getPageLanguageBcp47()->toBcp47Code()
		);
		$this->assertEqualsIgnoringCase(
			'en',
			$this->getPageConfig( 'existing' )->getPageLanguageBcp47()->toBcp47Code()
		);
	}

	public function testGetPageLanguageDir() {
		$this->assertSame( 'ltr', $this->getPageConfig( 'missing' )->getPageLanguageDir() );
		$this->assertSame( 'ltr', $this->getPageConfig( 'existing' )->getPageLanguageDir() );
	}

	public function testGetRevisionId() {
		$this->assertNull( $this->getPageConfig( 'missing' )->getRevisionId() );
		$this->assertSame( 857016953, $this->getPageConfig( 'existing' )->getRevisionId() );
	}

	public function testGetParentRevisionId() {
		$this->assertNull( $this->getPageConfig( 'missing' )->getParentRevisionId() );
		$this->assertSame( 817557113, $this->getPageConfig( 'existing' )->getParentRevisionId() );
	}

	public function testGetRevisionTimestamp() {
		$this->assertNull( $this->getPageConfig( 'missing' )->getRevisionTimestamp() );
		$this->assertSame( '20180829005516', $this->getPageConfig( 'existing' )->getRevisionTimestamp() );
	}

	public function testGetRevisionUser() {
		$this->assertNull( $this->getPageConfig( 'missing' )->getRevisionUser() );
		$this->assertSame( 'JCW-CleanerBot', $this->getPageConfig( 'existing' )->getRevisionUser() );
	}

	public function testGetRevisionUserId() {
		$this->assertNull( $this->getPageConfig( 'missing' )->getRevisionUserId() );
		$this->assertSame( 31737083, $this->getPageConfig( 'existing' )->getRevisionUserId() );
	}

	public function testGetRevisionSha1() {
		$this->assertNull( $this->getPageConfig( 'missing' )->getRevisionSha1() );
		$this->assertSame(
			'27d4d91a06e8df01f33a2577e00305a81cd30bf0',
			$this->getPageConfig( 'existing' )->getRevisionSha1()
		);
	}

	public function testGetRevisionSize() {
		$this->assertNull( $this->getPageConfig( 'missing' )->getRevisionSize() );
		$this->assertSame( 1736, $this->getPageConfig( 'existing' )->getRevisionSize() );
	}

	public function testGetRevisionContent() {
		$this->assertNull( $this->getPageConfig( 'missing' )->getRevisionContent() );

		$c = $this->getPageConfig( 'existing' )->getRevisionContent();
		$this->assertInstanceOf( PageContent::class, $c );
		$this->assertSame( [ 'main' ], $c->getRoles() );
		$this->assertSame( 'wikitext', $c->getModel( 'main' ) );
		$this->assertSame( 'text/x-wiki', $c->getFormat( 'main' ) );
		$this->assertSame(
			"Our '''world''' is a planet where human beings have formed many societies.\n\n",
			substr( $c->getContent( 'main' ), 0, 76 )
		);
	}

}
