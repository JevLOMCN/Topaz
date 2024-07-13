<?php

namespace MediaWiki\Extension\DiscussionTools\Tests;

use ApiTestCase;
use ExtensionRegistry;

/**
 * @group medium
 * @group Database
 */
class ApiParseDiscussionToolsTest extends ApiTestCase {

	/**
	 * @covers \MediaWiki\Extension\DiscussionTools\CommentFormatter::addDiscussionTools
	 */
	public function testApiParseSections() {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Liquid Threads' ) ) {
			$this->setMwGlobals( 'wgLqtTalkPages', false );
		}

		$params = [
			'action' => 'parse',
			'title' => 'Talk:Test',
			'uselang' => 'en',
			'text' => "__FORCETOC__\n== foo ==\nbar ~~~~",
			'pst' => 1,
			'prop' => 'sections',
		];

		[ $result, ] = $this->doApiRequest( $params );

		$this->assertSame(
			'<span class="ext-discussiontools-init-sidebar-meta">1 comment</span>',
			$result['parse']['sections'][0]['extensionData']['DiscussionTools-html-summary']
		);
	}

}
