<?php

namespace PageImages\Job;

use Job;
use MediaWiki\Title\Title;
use MWExceptionHandler;
use RefreshLinks;
use Wikimedia\Rdbms\LBFactory;

class InitImageDataJob extends Job {
	/** @var LBFactory */
	private $lbFactory;

	/**
	 * @param Title $title Title object associated with this job
	 * @param array $params Parameters to the job, containing an array of
	 * page ids representing which pages to process
	 * @param LBFactory $lbFactory
	 */
	public function __construct(
		Title $title,
		array $params,
		LBFactory $lbFactory
	) {
		parent::__construct( 'InitImageDataJob', $title, $params );
		$this->lbFactory = $lbFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function run() {
		foreach ( $this->params['page_ids'] as $id ) {
			try {
				RefreshLinks::fixLinksFromArticle( $id );
				$this->lbFactory->waitForReplication();
			} catch ( \Exception $e ) {
				// There are some broken pages out there that just don't parse.
				// Log it and keep on trucking.
				MWExceptionHandler::logException( $e );
			}
		}
		return true;
	}
}
