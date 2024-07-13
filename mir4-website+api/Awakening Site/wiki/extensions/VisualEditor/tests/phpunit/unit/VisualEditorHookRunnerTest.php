<?php

namespace MediaWiki\Extension\VisualEditor\Tests;

use MediaWiki\Extension\VisualEditor\VisualEditorHookRunner;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Page\PageIdentityValue;
use MediaWiki\User\UserIdentityValue;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\VisualEditor\VisualEditorHookRunner
 */
class VisualEditorHookRunnerTest extends MediaWikiUnitTestCase {

	public function testPreSaveHook() {
		$container = $this->createNoOpMock( HookContainer::class, [ 'run' ] );
		$container->expects( $this->once() )
			->method( 'run' )
			->with(
				'VisualEditorApiVisualEditorEditPreSave',
				$this->isType( 'array' ),
				[ 'abortable' => true ]
			)
			->willReturn( true );
		$runner = new VisualEditorHookRunner( $container );

		$apiResponse = [];
		$params = [];
		$result = $runner->onVisualEditorApiVisualEditorEditPreSave(
			PageIdentityValue::localIdentity( 0, 0, 'test' ),
			UserIdentityValue::newAnonymous( '' ),
			'',
			$params,
			[],
			$apiResponse
		);
		$this->assertTrue( $result );
	}

	public function testPostSaveHook() {
		$container = $this->createNoOpMock( HookContainer::class, [ 'run' ] );
		$container->expects( $this->once() )
			->method( 'run' )
			->with(
				'VisualEditorApiVisualEditorEditPostSave',
				$this->isType( 'array' ),
				[ 'abortable' => false ]
			);
		$runner = new VisualEditorHookRunner( $container );

		$apiResponse = [];
		$runner->onVisualEditorApiVisualEditorEditPostSave(
			PageIdentityValue::localIdentity( 0, 0, 'test' ),
			UserIdentityValue::newAnonymous( '' ),
			'',
			[],
			[],
			[],
			$apiResponse
		);
	}

}
