<?php

class NotificationStructureTest extends MediaWikiIntegrationTestCase {
	/**
	 * @coversNothing
	 * @dataProvider provideNotificationTypes
	 *
	 * @param string $type
	 * @param array $info
	 */
	public function testNotificationTypes( $type, array $info ) {
		if ( isset( $info['presentation-model'] ) ) {
			self::assertTrue( class_exists( $info['presentation-model'] ),
				"Presentation model class {$info['presentation-model']} for {$type} must exist"
			);
		}

		if ( isset( $info['user-locators'] ) ) {
			$locators = (array)$info['user-locators'];
			$callable = reset( $locators );
			if ( is_array( $callable ) ) {
				$callable = reset( $callable );
			}
			self::assertTrue( is_callable( $callable ),
				'User locator ' . print_r( $callable, true ) . " for {$type} must be callable"
			);
		}
	}

	public static function provideNotificationTypes() {
		global $wgEchoNotifications;

		$result = [];
		foreach ( $wgEchoNotifications as $type => $info ) {
			$result[] = [ $type, $info ];
		}

		return $result;
	}
}
