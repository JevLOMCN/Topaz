<?php

use Flow\Model\AbstractRevision;
use Flow\Model\PostRevision;
use Flow\Model\UUID;
use Flow\Model\Workflow;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\WikiMap\WikiMap;

/**
 * Integration tests for the Thanks Flow api module
 *
 * @covers \MediaWiki\Extension\Thanks\Api\ApiFlowThank
 *
 * @group Thanks
 * @group Database
 * @group medium
 * @group API
 * @group Flow
 *
 * @author Benjamin Chen
 */
class ApiFlowThankIntegrationTest extends ApiTestCase {

	/** @var PostRevision */
	public $topic;

	/** @var User */
	public $meUser;

	/** @var User */
	public $otherUser;

	/** @var PostRevision */
	public $postByOtherUser;

	/** @var PostRevision */
	public $postByMe;

	public function setUp(): void {
		parent::setUp();

		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Flow' ) ) {
			$this->markTestSkipped( 'Flow is not installed' );
		}

		// mock topic and post
		$this->meUser = $this->getMutableTestUser()->getUser();
		$this->otherUser = $this->getMutableTestUser()->getUser();
		$this->topic = $this->generateObject();
		$this->postByOtherUser = $this->generateObject( [
				'tree_orig_user_id' => $this->otherUser->getId(),
				'tree_parent_id' => $this->topic->getPostId()->getBinary(),
			], [], 1 );
		$this->postByMe = $this->generateObject( [
				'tree_orig_user_id' => $this->meUser->getId(),
				'tree_parent_id' => $this->topic->getPostId()->getBinary(),
			], [], 1 );

		// Set up mock classes in Container.
		$mockLoader = $this->createMock( \Flow\Repository\RootPostLoader::class );
		$mockLoader->expects( $this->any() )
			->method( 'getWithRoot' )
			->willReturnCallback(
				function ( $postId ) {
					switch ( $postId ) {
						case $this->postByOtherUser->getPostId():
							return [
								'post' => $this->postByOtherUser,
								'root' => $this->topic
							];

						case $this->postByMe->getPostId():
							return [
								'post' => $this->postByMe,
								'root' => $this->topic
							];

						default:
							return [ 'post' => null ];
					}
				}
			);

		$mockWorkflow = $this->createMock( Workflow::class );
		$mockWorkflow->expects( $this->any() )
			->method( 'getOwnerTitle' )
			->willReturn( $this->createMock( Title::class ) );
		$mockWorkflow->expects( $this->any() )
			->method( 'getArticleTitle' )
			->willReturn( $this->createMock( Title::class ) );

		$mockStorage = $this->createMock( \Flow\Data\ManagerGroup::class );

		$mockStorage->expects( $this->any() )
			->method( 'get' )
			->willReturn( $mockWorkflow );

		$mockTemplating = $this->createMock( \Flow\Templating::class );

		$mockTemplating->expects( $this->any() )
			->method( 'getContent' )
			->willReturn( 'test content' );

		Flow\Container::reset();
		$container = Flow\Container::getContainer();
		$container[ 'loader.root_post' ] = $mockLoader;
		$container[ 'storage' ] = $mockStorage;
		$container[ 'templating' ] = $mockTemplating;

		DeferredUpdates::clearPendingUpdates();
	}

	public function testRequestWithoutToken() {
		$this->expectApiErrorCode( 'missingparam' );
		$this->doApiRequest( [
			'action' => 'flowthank',
			'postid' => UUID::create( '42' )->getAlphadecimal(),
		] );
	}

	public function testInvalidRequest() {
		$this->expectApiErrorCode( 'missingparam' );
		$this->doApiRequestWithToken( [ 'action' => 'flowthank' ] );
	}

	public function testValidRequest() {
		[ $result,, ] = $this->doApiRequestWithToken( [
			'action' => 'flowthank',
			'postid' => $this->postByOtherUser->getPostId()->getAlphadecimal(),
		] );
		$this->assertSuccess( $result );
	}

	public function testRequestWithInvalidId() {
		$this->expectApiErrorCode( 'invalidpostid' );
		$this->doApiRequestWithToken( [
			'action' => 'flowthank',
			'postid' => UUID::create( '42' )->getAlphadecimal(),
		] );
	}

	public function testRequestWithOwnId() {
		$this->expectApiErrorCode( 'invalidrecipient' );
		$this->doApiRequestWithToken( [
			'action' => 'flowthank',
			'postid' => $this->postByMe->getPostId()->getAlphadecimal(),
		], null, $this->meUser );
	}

	protected function assertSuccess( $result ) {
		$this->assertSame( 1, $result[ 'result' ][ 'success' ] );
	}

	/**
	 * This method is obtained from Flow/tests/PostRevisionTestCase.php
	 *
	 * Returns an array, representing flow_revision & flow_tree_revision db
	 * columns.
	 *
	 * You can pass in arguments to override default data.
	 * With no arguments tossed in, default data (resembling a newly-created
	 * topic title) will be returned.
	 *
	 * @param array $row DB row data (only specify override columns)
	 * @return array
	 */
	protected function generateRow( array $row = [] ) {
		$uuidPost = UUID::create();
		$uuidRevision = UUID::create();

		$user = $this->meUser;
		$userId = $user->getId();
		$userIp = null;

		return $row + [
			// flow_revision
			'rev_id' => $uuidRevision->getBinary(),
			'rev_type' => 'post',
			'rev_user_id' => $userId,
			'rev_user_ip' => $userIp,
			'rev_user_wiki' => WikiMap::getCurrentWikiId(),
			'rev_parent_id' => null,
			'rev_flags' => 'html',
			'rev_content' => 'test content',
			'rev_change_type' => 'new-post',
			'rev_mod_state' => AbstractRevision::MODERATED_NONE,
			'rev_mod_user_id' => null,
			'rev_mod_user_ip' => null,
			'rev_mod_user_wiki' => null,
			'rev_mod_timestamp' => null,
			'rev_mod_reason' => null,
			'rev_last_edit_id' => null,
			'rev_edit_user_id' => null,
			'rev_edit_user_ip' => null,
			'rev_edit_user_wiki' => null,

			// flow_tree_revision
			'tree_rev_descendant_id' => $uuidPost->getBinary(),
			'rev_type_id' => $uuidPost->getBinary(),
			'tree_rev_id' => $uuidRevision->getBinary(),
			'tree_orig_create_time' => wfTimestampNow(),
			'tree_orig_user_id' => $userId,
			'tree_orig_user_ip' => $userIp,
			'tree_orig_user_wiki' => WikiMap::getCurrentWikiId(),
			'tree_parent_id' => null,
		];
	}

	/**
	 * This method is obtained from Flow/tests/PostRevisionTestCase.php
	 *
	 * Returns a PostRevision object.
	 *
	 * You can pass in arguments to override default data.
	 * With no arguments tossed in, a default revision (resembling a newly-
	 * created topic title) will be returned.
	 *
	 * @param array $row DB row data (only specify override columns)
	 * @param array $children Array of child PostRevision objects
	 * @param int $depth Depth of the PostRevision object
	 * @return PostRevision
	 */
	protected function generateObject( array $row = [], $children = [], $depth = 0 ) {
		$row = $this->generateRow( $row );

		$revision = PostRevision::fromStorageRow( $row );
		$revision->setChildren( $children );
		$revision->setDepth( $depth );

		return $revision;
	}

}
