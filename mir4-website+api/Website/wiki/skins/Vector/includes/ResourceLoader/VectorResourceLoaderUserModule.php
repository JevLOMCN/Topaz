<?php

namespace MediaWiki\Skins\Vector\ResourceLoader;

use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader as RL;
use MediaWiki\Skins\Vector\Constants;
use MediaWiki\Title\TitleValue;

class VectorResourceLoaderUserModule extends RL\UserModule {
	/**
	 * @inheritDoc
	 */
	protected function getPages( RL\Context $context ) {
		$user = $context->getUserIdentity();
		if ( !$user || !$user->isRegistered() ) {
			return [];
		}
		$pages = [];
		$config = $this->getConfig();
		if ( $context->getSkin() === Constants::SKIN_NAME_MODERN &&
			$config->get( 'VectorShareUserScripts' ) &&
			$config->get( MainConfigNames::AllowUserCss )
		) {
			$titleFormatter = MediaWikiServices::getInstance()->getTitleFormatter();
			$userPage = $titleFormatter->getPrefixedDBkey( new TitleValue( NS_USER, $user->getName() ) );
			$pages["$userPage/vector.js"] = [ 'type' => 'script' ];
		}
		return $pages;
	}
}
