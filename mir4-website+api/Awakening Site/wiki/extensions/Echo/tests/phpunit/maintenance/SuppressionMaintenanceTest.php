<?php

use MediaWiki\Extension\Notifications\SuppressionRowUpdateGenerator;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;

/**
 * @group Echo
 * @covers \MediaWiki\Extension\Notifications\SuppressionRowUpdateGenerator
 * @group Database
 */
class SuppressionMaintenanceTest extends MediaWikiIntegrationTestCase {

	public static function provider_updateRow() {
		$input = [
			'event_id' => 2,
			'event_type' => 'mention',
			'event_variant' => null,
			'event_agent_id' => 3,
			'event_agent_ip' => null,
			'event_page_title' => '',
			'event_page_namespace' => 0,
			'event_page_extra' => null,
			'event_extra' => null,
			'event_page_id' => null,
		];

		return [
			[ 'Unrelated row must result in no update', [], $input ],

			[
				'Page title and namespace for non-existant page must move into event_extra',
				[
					// expected update
					'event_extra' => serialize( [
						'page_title' => 'Yabba Dabba Do',
						'page_namespace' => NS_MAIN
					] ),
				],
				[
					// input row
					'event_page_title' => 'Yabba Dabba Do',
					'event_page_namespace' => NS_MAIN,
				] + $input,
			],

			[
				'Page title and namespace for existing page must be result in update to event_page_id',
				[
					// expected update
					'event_page_id' => 42,
				],
				[
					// input row
					'event_page_title' => 'Mount Rushmore',
					'event_page_namespace' => NS_MAIN,
				] + $input,
				self::attachTitleFor( 42, 'Mount Rushmore', NS_MAIN )
			],

			[
				'When updating non-existent page must keep old extra data',
				[
					// expected update
					'event_extra' => serialize( [
						'foo' => 'bar',
						'page_title' => 'Yabba Dabba Do',
						'page_namespace' => NS_MAIN
					] ),
				],
				[
					// input row
					'event_page_title' => 'Yabba Dabba Do',
					'event_page_namespace' => NS_MAIN,
					'event_extra' => serialize( [ 'foo' => 'bar' ] ),
				] + $input,
			],

			[
				'Must update link-from-title/namespace to link-from-page-id for page-linked events',
				[
					// expected update
					'event_extra' => serialize( [ 'link-from-page-id' => 99 ] ),
				],
				[
					// input row
					'event_type' => 'page-linked',
					'event_extra' => serialize( [
						'link-from-title' => 'Horse',
						'link-from-namespace' => NS_USER_TALK
					] ),
				] + $input,
				self::attachTitleFor( 99, 'Horse', NS_USER_TALK )
			],

			[
				'Must perform both generic update and page-linked update at same time',
				[
					// expected update
					'event_extra' => serialize( [ 'link-from-page-id' => 8675309 ] ),
					'event_page_id' => 8675309,
				],
				[
					// input row
					'event_type' => 'page-linked',
					'event_extra' => serialize( [
						'link-from-title' => 'Jenny',
						'link-from-namespace' => NS_MAIN,
					] ),
					'event_page_title' => 'Jenny',
					'event_page_namespace' => NS_MAIN,
				] + $input,
				self::attachTitleFor( 8675309, 'Jenny', NS_MAIN ),
			],
		];
	}

	protected static function attachTitleFor( $id, $providedText, $providedNamespace ) {
		return static function (
			TestCase $test,
			SuppressionRowUpdateGenerator $gen
		) use ( $id, $providedText, $providedNamespace ) {
			$title = $test->createMock( Title::class );
			$title->expects( $test->any() )
				->method( 'getArticleId' )
				->willReturn( $id );

			$titles = [ $providedNamespace => [ $providedText => $title ] ];

			$gen->setNewTitleFromNsAndText( static function ( $namespace, $text ) use ( $titles ) {
				return $titles[$namespace][$text] ?? Title::makeTitleSafe( $namespace, $text );
			} );
		};
	}

	/**
	 * @dataProvider provider_updateRow
	 */
	public function testUpdateRow( $message, array $expected, array $input, callable $callable = null ) {
		$gen = new SuppressionRowUpdateGenerator;
		if ( $callable ) {
			call_user_func( $callable, $this, $gen );
		}
		$update = $gen->update( (object)$input );
		$this->assertEquals( $expected, $update, $message );
	}
}
