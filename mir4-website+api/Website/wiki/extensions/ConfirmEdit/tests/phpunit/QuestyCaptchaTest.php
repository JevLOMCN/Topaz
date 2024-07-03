<?php

use MediaWiki\Extension\ConfirmEdit\QuestyCaptcha\QuestyCaptcha;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\QuestyCaptcha\QuestyCaptcha
 */
class QuestyCaptchaTest extends MediaWikiIntegrationTestCase {

	public function setUp(): void {
		parent::setUp();

		$this->mergeMwGlobalArrayValue(
			'wgAutoloadClasses',
			[ 'MediaWiki\\Extension\\ConfirmEdit\\QuestyCaptcha\\QuestyCaptcha'
				=> __DIR__ . '/../../QuestyCaptcha/includes/QuestyCaptcha.php' ]
		);
	}

	/**
	 * @covers \MediaWiki\Extension\ConfirmEdit\QuestyCaptcha\QuestyCaptcha::getCaptcha
	 * @dataProvider provideGetCaptcha
	 */
	public function testGetCaptcha( $config, $expected ) {
		# setMwGlobals() requires $wgCaptchaQuestion to be set
		if ( !isset( $GLOBALS['wgCaptchaQuestions'] ) ) {
			$GLOBALS['wgCaptchaQuestions'] = [];
		}
		$this->setMwGlobals( 'wgCaptchaQuestions', $config );

		$qc = new QuestyCaptcha();
		$this->assertEquals( $expected, $qc->getCaptcha() );
	}

	public static function provideGetCaptcha() {
		return [
			[
				[
					[
						'question' => 'FooBar',
						'answer' => 'Answer!',
					],
				],
				[
					'question' => 'FooBar',
					'answer' => 'Answer!',
				],
			],
			[
				[
					'FooBar' => 'Answer!',
				],
				[
					'question' => 'FooBar',
					'answer' => 'Answer!',
				],
			]
		];
	}
}
