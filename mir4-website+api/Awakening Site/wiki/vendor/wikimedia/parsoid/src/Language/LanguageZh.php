<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Language;

/* @note: Use of this class is currently disabled in production, see T346657 */
/** Chinese conversion code. */
class LanguageZh extends Language {

	public function __construct() {
		$variants = [
			'zh',
			'zh-hans',
			'zh-hant',
			'zh-cn',
			'zh-hk',
			'zh-mo',
			'zh-my',
			'zh-sg',
			'zh-tw'
		];
		$variantfallbacks = [
			'zh' => [ 'zh-hans', 'zh-hant', 'zh-cn', 'zh-tw', 'zh-hk', 'zh-sg', 'zh-mo', 'zh-my' ],
			'zh-hans' => [ 'zh-cn', 'zh-sg', 'zh-my' ],
			'zh-hant' => [ 'zh-tw', 'zh-hk', 'zh-mo' ],
			'zh-cn' => [ 'zh-hans', 'zh-sg', 'zh-my' ],
			'zh-sg' => [ 'zh-hans', 'zh-cn', 'zh-my' ],
			'zh-my' => [ 'zh-hans', 'zh-sg', 'zh-cn' ],
			'zh-tw' => [ 'zh-hant', 'zh-hk', 'zh-mo' ],
			'zh-hk' => [ 'zh-hant', 'zh-mo', 'zh-tw' ],
			'zh-mo' => [ 'zh-hant', 'zh-hk', 'zh-tw' ],
		];
		$manualLevel = [
			'zh' => 'disable',
			'zh-hans' => 'unidirectional',
			'zh-hant' => 'unidirectional',
		];
		$converter = new ZhConverter(
			$this,
			'zh',
			$variants,
			$variantfallbacks,
			[],
			$manualLevel
		);
		$this->setConverter( $converter );
	}

}
