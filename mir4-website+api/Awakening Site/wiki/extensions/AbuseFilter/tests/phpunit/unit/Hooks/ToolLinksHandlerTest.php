<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Hooks;

use IContextSource;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\Hooks\Handlers\ToolLinksHandler;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Request\WebRequest;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiUnitTestCase;
use Message;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Hooks\Handlers\ToolLinksHandler
 * @covers ::__construct
 */
class ToolLinksHandlerTest extends MediaWikiUnitTestCase {

	private function getToolLinksHandler( bool $allowed = true ): ToolLinksHandler {
		$permManager = $this->createMock( AbuseFilterPermissionManager::class );
		$permManager->method( 'canViewAbuseLog' )->willReturn( $allowed );
		return new ToolLinksHandler( $permManager );
	}

	/**
	 * @covers ::onContributionsToolLinks
	 */
	public function testOnContributionsToolLinks() {
		$handler = $this->getToolLinksHandler();
		$sp = $this->createMock( SpecialPage::class );
		$sp->method( 'getUser' )->willReturn( $this->createMock( User::class ) );
		$sp->method( 'msg' )->willReturn( $this->createMock( Message::class ) );
		$title = $this->createMock( Title::class );
		$title->method( 'getText' )->willReturn( '1.1.0.0' );
		$tools = [];
		$handler->onContributionsToolLinks( 1, $title, $tools, $sp );
		$this->assertArrayHasKey( 'abuselog', $tools );
	}

	/**
	 * @covers ::onContributionsToolLinks
	 */
	public function testOnContributionsToolLinks_notAllowed() {
		$handler = $this->getToolLinksHandler( false );
		$sp = $this->createMock( SpecialPage::class );
		$sp->method( 'getUser' )->willReturn( $this->createMock( User::class ) );
		$tools = [];
		$handler->onContributionsToolLinks(
			1,
			$this->createMock( Title::class ),
			$tools,
			$sp
		);
		$this->assertCount( 0, $tools );
	}

	/**
	 * @covers ::onContributionsToolLinks
	 */
	public function testOnContributionsToolLinks_range() {
		$handler = $this->getToolLinksHandler();
		$sp = $this->createMock( SpecialPage::class );
		$sp->method( 'getUser' )->willReturn( $this->createMock( User::class ) );
		$title = $this->createMock( Title::class );
		$title->method( 'getText' )->willReturn( '1.1.0.0/16' );
		$tools = [];
		$handler->onContributionsToolLinks( 1, $title, $tools, $sp );
		$this->assertCount( 0, $tools );
	}

	/**
	 * @covers ::onHistoryPageToolLinks
	 */
	public function testOnHistoryPageToolLinks() {
		$handler = $this->getToolLinksHandler();
		$ctx = $this->createMock( IContextSource::class );
		$ctx->method( 'getUser' )->willReturn( $this->createMock( User::class ) );
		$ctx->method( 'msg' )->willReturn( $this->createMock( Message::class ) );
		$ctx->method( 'getTitle' )->willReturn( $this->createMock( Title::class ) );
		$links = [];
		$handler->onHistoryPageToolLinks( $ctx, $this->createMock( LinkRenderer::class ), $links );
		$this->assertCount( 1, $links );
	}

	/**
	 * @covers ::onHistoryPageToolLinks
	 */
	public function testOnHistoryPageToolLinks_notAllowed() {
		$handler = $this->getToolLinksHandler( false );
		$ctx = $this->createMock( IContextSource::class );
		$ctx->method( 'getUser' )->willReturn( $this->createMock( User::class ) );
		$links = [];
		$handler->onHistoryPageToolLinks( $ctx, $this->createMock( LinkRenderer::class ), $links );
		$this->assertCount( 0, $links );
	}

	/**
	 * @covers ::onUndeletePageToolLinks
	 */
	public function testOnUndeletePageToolLinks() {
		$handler = $this->getToolLinksHandler();
		$ctx = $this->createMock( IContextSource::class );
		$ctx->method( 'getUser' )->willReturn( $this->createMock( User::class ) );
		$req = $this->createMock( WebRequest::class );
		$req->method( 'getVal' )->with( 'action' )->willReturn( 'view' );
		$ctx->method( 'getRequest' )->willReturn( $req );
		$ctx->method( 'msg' )->willReturn( $this->createMock( Message::class ) );
		$ctx->method( 'getTitle' )->willReturn( $this->createMock( Title::class ) );
		$links = [];
		$handler->onUndeletePageToolLinks( $ctx, $this->createMock( LinkRenderer::class ), $links );
		$this->assertCount( 1, $links );
	}

	/**
	 * @covers ::onUndeletePageToolLinks
	 */
	public function testOnUndeletePageToolLinks_notAllowed() {
		$handler = $this->getToolLinksHandler( false );
		$ctx = $this->createMock( IContextSource::class );
		$ctx->method( 'getUser' )->willReturn( $this->createMock( User::class ) );
		$req = $this->createMock( WebRequest::class );
		$req->method( 'getVal' )->with( 'action' )->willReturn( 'view' );
		$ctx->method( 'getRequest' )->willReturn( $req );
		$links = [];
		$handler->onUndeletePageToolLinks( $ctx, $this->createMock( LinkRenderer::class ), $links );
		$this->assertCount( 0, $links );
	}

	/**
	 * @covers ::onUndeletePageToolLinks
	 */
	public function testOnUndeletePageToolLinks_historyAction() {
		$handler = $this->getToolLinksHandler();
		$ctx = $this->createMock( IContextSource::class );
		$ctx->method( 'getUser' )->willReturn( $this->createMock( User::class ) );
		$req = $this->createMock( WebRequest::class );
		$req->method( 'getVal' )->with( 'action' )->willReturn( 'history' );
		$ctx->method( 'getRequest' )->willReturn( $req );
		$links = [];
		$handler->onUndeletePageToolLinks( $ctx, $this->createMock( LinkRenderer::class ), $links );
		$this->assertCount( 0, $links );
	}
}
