<?php

use MediaWiki\Extension\TitleBlacklist\TitleBlacklist;

/**
 * @group medium
 * @group Database
 * @covers \MediaWiki\Extension\TitleBlacklist\Api\ApiTitleBlacklist
 */
class ApiTitleBlacklistTest extends ApiTestCase {

	protected function setUp(): void {
		parent::setUp();

		TitleBlacklist::destroySingleton();
		$this->setMwGlobals( 'wgTitleBlacklistSources', [
			[
				'type' => 'file',
				'src'  => __DIR__ . '/testSource.txt',
			],
		] );
	}

	protected function tearDown(): void {
		TitleBlacklist::destroySingleton();
		parent::tearDown();
	}

	/**
	 * Verify we allow a title which is not blacklisted
	 */
	public function testCheckingUnlistedTitle() {
		$unlisted = $this->doApiRequest( [
			'action' => 'titleblacklist',
			// evil_acc is blacklisted as <newaccountonly>
			'tbtitle' => 'evil_acc',
			'tbaction' => 'create',
			'tbnooverride' => true,
		] );

		$this->assertEquals(
			'ok',
			$unlisted[0]['titleblacklist']['result'],
			'Not blacklisted title returns ok'
		);
	}

	/**
	 * Verify tboverride works
	 */
	public function testTboverride() {
		// Allow all users to override the titleblacklist
		$this->setGroupPermissions( '*', 'tboverride', true );

		$unlisted = $this->doApiRequest( [
			'action' => 'titleblacklist',
			'tbtitle' => 'bar',
			'tbaction' => 'create',
		] );

		$this->assertEquals(
			'ok',
			$unlisted[0]['titleblacklist']['result'],
			'Blacklisted title returns ok if the user is allowd to tboverride'
		);
	}

	/**
	 * Verify a blacklisted title gives out an error.
	 */
	public function testCheckingBlackListedTitle() {
		$listed = $this->doApiRequest( [
			'action' => 'titleblacklist',
			'tbtitle' => 'bar',
			'tbaction' => 'create',
			'tbnooverride' => true,
		] );

		$this->assertEquals(
			'blacklisted',
			$listed[0]['titleblacklist']['result'],
			'Listed title returns error'
		);
		$this->assertEquals(
			"The title \"bar\" has been banned from creation.\nIt matches the following " .
				"disallowed titles list entry: <code>[Bb]ar #example disallowed list entry</code>",
			$listed[0]['titleblacklist']['reason'],
			'Listed title error text is as expected'
		);

		$this->assertEquals(
			"titleblacklist-forbidden-edit",
			$listed[0]['titleblacklist']['message'],
			'Correct blacklist message name is returned'
		);

		$this->assertEquals(
			"[Bb]ar #example disallowed list entry",
			$listed[0]['titleblacklist']['line'],
			'Correct blacklist line is returned'
		);
	}

	/**
	 * Tests integration with the AntiSpoof extension
	 */
	public function testAntiSpoofIntegration() {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'AntiSpoof' ) ) {
			$this->markTestSkipped( "This test requires the AntiSpoof extension" );
		}

		$listed = $this->doApiRequest( [
			'action' => 'titleblacklist',
			'tbtitle' => 'AVVVV',
			'tbaction' => 'create',
			'tbnooverride' => true,
		] );

		$this->assertEquals(
			'blacklisted',
			$listed[0]['titleblacklist']['result'],
			'Spoofed title is blacklisted'
		);
	}
}
