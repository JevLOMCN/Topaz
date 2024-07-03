<?php

namespace MediaWiki\Extension\DiscussionTools;

use ErrorPageError;
use MediaWiki\Cache\LinkBatchFactory;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\SpecialPage\SpecialPage;

class SpecialTopicSubscriptions extends SpecialPage {

	private LinkRenderer $linkRenderer;
	private LinkBatchFactory $linkBatchFactory;
	private ThreadItemStore $threadItemStore;
	private ThreadItemFormatter $threadItemFormatter;

	public function __construct(
		LinkRenderer $linkRenderer,
		LinkBatchFactory $linkBatchFactory,
		ThreadItemStore $threadItemStore,
		ThreadItemFormatter $threadItemFormatter
	) {
		parent::__construct( 'TopicSubscriptions' );
		$this->linkRenderer = $linkRenderer;
		$this->linkBatchFactory = $linkBatchFactory;
		$this->threadItemStore = $threadItemStore;
		$this->threadItemFormatter = $threadItemFormatter;
	}

	/**
	 * @inheritDoc
	 * @throws ErrorPageError
	 */
	public function execute( $subpage ) {
		$this->requireNamedUser();

		parent::execute( $subpage );

		$this->getOutput()->addModules( [ 'ext.discussionTools.init' ] );

		$this->getOutput()->addHtml( $this->msg( 'discussiontools-topicsubscription-special-intro' )->parseAsBlock() );

		$this->getOutput()->enableOOUI();
		$pager = new TopicSubscriptionsPager(
			$this->getContext(),
			$this->linkRenderer,
			$this->linkBatchFactory,
			$this->threadItemStore,
			$this->threadItemFormatter
		);
		$this->getOutput()->addParserOutputContent( $pager->getFullOutput() );
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription() {
		return $this->msg( 'discussiontools-topicsubscription-special-title' );
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'login';
	}
}
