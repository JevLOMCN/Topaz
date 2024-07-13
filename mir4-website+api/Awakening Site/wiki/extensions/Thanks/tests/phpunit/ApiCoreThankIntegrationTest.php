<?php

use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\User\User;

/**
 * Integration tests for the Thanks API module
 *
 * @covers \MediaWiki\Extension\Thanks\Api\ApiCoreThank
 *
 * @group Thanks
 * @group Database
 * @group medium
 * @group API
 *
 * @author Addshore
 */
class ApiCoreThankIntegrationTest extends ApiTestCase {

	/**
	 * @var int filled in setUp
	 */
	private $revId;

	/**
	 * @var User filled in setUp
	 */
	private $uploader;

	/**
	 * @var int The ID of a deletion log entry.
	 */
	protected $logId;

	public function setUp(): void {
		parent::setUp();

		$this->uploader = $this->getTestUser()->getUser();
		$user = $this->uploader;

		// Here comes the hack! Make sure to create a random, useless revision with ID 1, so that the one created below
		// that we will use to send thanks has an ID other than 1. Why? Because ApiCoreThank::getRevisionFromId thinks
		// rev ID = 1 means invalid input, see T344475. Yay!
		$this->getExistingTestPage( 'Why the heck is revID = 1 considered invalid?' );

		$pageName = __CLASS__;
		$content = __CLASS__;
		// Make sure the page doesn't exist, otherwise our edit will not result in a new revision
		$page = $this->getNonexistingTestPage( $pageName );
		$result = $this->editPage( $page, $content, 'Summary', NS_MAIN, $user );
		$this->revId = $result->getNewRevision()->getId();

		// Create a 2nd page and delete it, so we can thank for the log entry.
		$pageToDelete = $this->getExistingTestPage( 'Page to delete' );

		$deleteStatus = $pageToDelete->doDeleteArticleReal( '', $user );
		$this->logId = $deleteStatus->getValue();

		DeferredUpdates::clearPendingUpdates();
	}

	public function testRequestWithoutToken() {
		$this->expectApiErrorCode( 'missingparam' );
		$this->doApiRequest( [
			'action' => 'thank',
			'source' => 'someSource',
			'rev' => 1,
		], null, false, $this->getTestSysop()->getUser() );
	}

	public function testValidRevRequest() {
		[ $result,, ] = $this->doApiRequestWithToken( [
			'action' => 'thank',
			'rev' => $this->revId,
		], null, $this->getTestSysop()->getUser() );
		$this->assertSuccess( $result );
	}

	public function testValidLogRequest() {
		[ $result,, ] = $this->doApiRequestWithToken( [
			'action' => 'thank',
			'log' => $this->logId,
		], null, $this->getTestSysop()->getUser() );
		$this->assertSuccess( $result );
	}

	public function testLogRequestWithDisallowedLogType() {
		$this->overrideConfigValue( 'ThanksAllowedLogTypes', [] );
		$this->expectApiErrorCode( 'thanks-error-invalid-log-type' );
		$this->doApiRequestWithToken( [
			'action' => 'thank',
			'log' => $this->logId,
		], null, $this->getTestSysop()->getUser() );
	}

	public function testLogThanksForADeletedLogEntry() {
		$this->setGroupPermissions( [
			'logdeleter' => [
				'read' => true,
				'writeapi' => true,
				'deletelogentry' => true
			]
		] );

		// Mark our test log entry as deleted.
		// To do this we briefly switch to a different test user.
		$logdeleter = $this->getTestUser( [ 'logdeleter' ] )->getUser();
		$this->doApiRequestWithToken( [
			'action' => 'revisiondelete',
			'type'   => 'logging',
			'ids'    => $this->logId,
			'hide'   => 'content',
		], null, $logdeleter );

		$sysop = $this->getTestSysop()->getUser();
		// Then try to thank for it, and we should get an exception.
		$this->expectApiErrorCode( 'thanks-error-log-deleted' );
		$this->doApiRequestWithToken( [
			'action' => 'thank',
			'log' => $this->logId,
		], null, $sysop );
	}

	public function testValidRequestWithSource() {
		[ $result,, ] = $this->doApiRequestWithToken( [
			'action' => 'thank',
			'source' => 'someSource',
			'rev' => $this->revId,
		], null, $this->getTestSysop()->getUser() );
		$this->assertSuccess( $result );
	}

	protected function assertSuccess( $result ) {
		$this->assertEquals( [
			'result' => [
				'success' => 1,
				'recipient' => $this->uploader->getName(),
			],
		], $result );
	}

	public function testInvalidRequest() {
		$this->expectException( ApiUsageException::class );
		$this->doApiRequestWithToken( [ 'action' => 'thank' ] );
	}

}
