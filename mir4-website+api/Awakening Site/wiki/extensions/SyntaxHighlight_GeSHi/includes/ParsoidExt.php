<?php

declare( strict_types = 1 );

namespace MediaWiki\SyntaxHighlight;

use Wikimedia\Parsoid\Ext\ExtensionModule;

class ParsoidExt implements ExtensionModule {

	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'SyntaxHighlight',
			'tags' => [
				[
					'name' => 'source',
					'handler' => SyntaxHighlight::class,
					'options' => [
						// Strip nowiki markers from #tag parser-function arguments.
						// This will be used to resolve T299103.
						// This is primarily a b/c flag in Parsoid.
						'stripNowiki' => true,
						'hasWikitextInput' => false,
					]
				],
				[
					'name' => 'syntaxhighlight',
					'handler' => SyntaxHighlight::class,
					'options' => [
						// Strip nowiki markers from #tag parser-function arguments.
						// This will be used to resolve T299103.
						// This is primarily a b/c flag in Parsoid.
						'stripNowiki' => true,
						'hasWikitextInput' => false,
					]
				]
			]
		];
	}
}
