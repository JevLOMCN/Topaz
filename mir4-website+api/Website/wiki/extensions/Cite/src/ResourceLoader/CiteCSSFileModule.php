<?php

namespace Cite\ResourceLoader;

use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\FileModule;

/**
 * ResourceLoader FileModule for adding the content language Cite CSS
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */
class CiteCSSFileModule extends FileModule {

	/**
	 * @inheritDoc
	 */
	public function __construct(
		array $options = [],
		$localBasePath = null,
		$remoteBasePath = null
	) {
		$contLang = MediaWikiServices::getInstance()->getContentLanguage();
		parent::__construct( $options, $localBasePath, $remoteBasePath );

		// Get the content language code, and all the fallbacks. The first that
		// has a ext.cite.style.<lang code>.css file present will be used.
		foreach ( [ $contLang->getCode(), ...$contLang->getFallbackLanguages() ] as $lang ) {
			$langStyleFile = 'ext.cite.style.' . strtr( $lang, '-', '_' ) . '.css';
			if ( file_exists( $this->getLocalPath( $langStyleFile ) ) ) {
				$this->styles[] = $langStyleFile;
				break;
			}
		}
	}

}
