<?php

namespace MediaWiki\Extension\Thanks\Api;

use ApiBase;
use Flow\Container;
use Flow\Conversion\Utils;
use Flow\Exception\FlowException;
use Flow\Model\PostRevision;
use Flow\Model\UUID;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module to send Flow thanks notifications
 *
 * This API does not prevent sending thanks using post IDs that refer to topic
 * titles, though Thank buttons are only shown for comments in the UI.
 *
 * @ingroup API
 * @ingroup Extensions
 */

class ApiFlowThank extends ApiThank {

	public function execute() {
		$user = $this->getUser();
		$this->dieOnBadUser( $user );
		$this->dieOnUserBlockedFromThanks( $user );

		$params = $this->extractRequestParams();

		try {
			$postId = UUID::create( $params['postid'] );
		} catch ( FlowException $e ) {
			$this->dieWithError( 'thanks-error-invalidpostid', 'invalidpostid' );
		}

		$data = $this->getFlowData( $postId );

		$recipient = $this->getRecipientFromPost( $data['post'] );
		$this->dieOnBadRecipient( $user, $recipient );

		if ( $this->userAlreadySentThanksForId( $user, $postId ) ) {
			$this->markResultSuccess( $recipient->getName() );
			return;
		}

		$rootPost = $data['root'];
		$workflowId = $rootPost->getPostId();
		$rawTopicTitleText = Utils::htmlToPlaintext(
			Container::get( 'templating' )->getContent( $rootPost, 'topic-title-html' )
		);
		// Truncate the title text to prevent issues with database storage.
		$topicTitleText = $this->getLanguage()->truncateForDatabase( $rawTopicTitleText, 200 );
		$pageTitle = $this->getPageTitleFromRootPost( $rootPost );
		$this->dieOnUserBlockedFromTitle( $user, $pageTitle );

		/** @var PostRevision $post */
		$post = $data['post'];
		$postText = Utils::htmlToPlaintext( $post->getContent() );
		$postText = $this->getLanguage()->truncateForDatabase( $postText, 200 );

		$topicTitle = $this->getTopicTitleFromRootPost( $rootPost );

		$this->sendThanks(
			$user,
			$recipient,
			$postId,
			$workflowId,
			$topicTitleText,
			$pageTitle,
			$postText,
			$topicTitle
		);
	}

	private function userAlreadySentThanksForId( User $user, UUID $id ) {
		return $user->getRequest()->getSessionData( "flow-thanked-{$id->getAlphadecimal()}" );
	}

	/**
	 * @param UUID $postId UUID of the post to thank for
	 * @return array containing 'post' and 'root' as keys
	 */
	private function getFlowData( UUID $postId ) {
		$rootPostLoader = Container::get( 'loader.root_post' );
		'@phan-var \Flow\Repository\RootPostLoader $rootPostLoader';

		try {
			$data = $rootPostLoader->getWithRoot( $postId );
		} catch ( FlowException $e ) {
			$this->dieWithError( 'thanks-error-invalidpostid', 'invalidpostid' );
		}

		if ( $data['post'] === null ) {
			$this->dieWithError( 'thanks-error-invalidpostid', 'invalidpostid' );
		}
		// @phan-suppress-next-line PhanTypeMismatchReturnNullable T240141
		return $data;
	}

	/**
	 * @param PostRevision $post
	 * @return User
	 */
	private function getRecipientFromPost( PostRevision $post ) {
		$recipient = User::newFromId( $post->getCreatorId() );
		if ( !$recipient->loadFromId() ) {
			$this->dieWithError( 'thanks-error-invalidrecipient', 'invalidrecipient' );
		}
		return $recipient;
	}

	/**
	 * @param PostRevision $rootPost
	 * @return Title
	 */
	private function getPageTitleFromRootPost( PostRevision $rootPost ) {
		$workflow = Container::get( 'storage' )->get( 'Workflow', $rootPost->getPostId() );
		return $workflow->getOwnerTitle();
	}

	/**
	 * @param PostRevision $rootPost
	 * @return Title
	 */
	private function getTopicTitleFromRootPost( PostRevision $rootPost ) {
		$workflow = Container::get( 'storage' )->get( 'Workflow', $rootPost->getPostId() );
		return $workflow->getArticleTitle();
	}

	/**
	 * @param User $user
	 * @param User $recipient
	 * @param UUID $postId
	 * @param UUID $workflowId
	 * @param string $topicTitleText
	 * @param Title $pageTitle
	 * @param string $postTextExcerpt
	 * @param Title $topicTitle
	 * @throws FlowException
	 */
	private function sendThanks(
		User $user,
		User $recipient,
		UUID $postId,
		UUID $workflowId,
		$topicTitleText,
		Title $pageTitle,
		$postTextExcerpt,
		Title $topicTitle
	) {
		$uniqueId = 'flow-' . $postId->getAlphadecimal();
		// Do one last check to make sure we haven't sent Thanks before
		if ( $this->haveAlreadyThanked( $user, $uniqueId ) ) {
			// Pretend the thanks were sent
			$this->markResultSuccess( $recipient->getName() );
			return;
		}

		// Create the notification via Echo extension
		Event::create( [
			'type' => 'flow-thank',
			'title' => $pageTitle,
			'extra' => [
				'post-id' => $postId->getAlphadecimal(),
				'workflow' => $workflowId->getAlphadecimal(),
				'thanked-user-id' => $recipient->getId(),
				'topic-title' => $topicTitleText,
				'excerpt' => $postTextExcerpt,
				'target-page' => $topicTitle->getArticleID(),
			],
			'agent' => $user,
		] );

		// And mark the thank in session for a cheaper check to prevent duplicates (T48690).
		$user->getRequest()->setSessionData( "flow-thanked-{$postId->getAlphadecimal()}", true );
		// Set success message.
		$this->markResultSuccess( $recipient->getName() );
		$this->logThanks( $user, $recipient, $uniqueId );
	}

	public function getAllowedParams() {
		return [
			'postid' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'token' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=flowthank&postid=xyz789&token=123ABC'
				=> 'apihelp-flowthank-example-1',
		];
	}
}
