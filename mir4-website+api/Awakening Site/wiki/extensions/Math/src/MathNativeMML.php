<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2023 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\Math;

use MediaWiki\Extension\Math\InputCheck\LocalChecker;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmath;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use StatusValue;

/**
 * Converts LaTeX to MathML using PHP
 */
class MathNativeMML extends MathMathML {
	private LocalChecker $checker;

	public function __construct( $tex = '', $params = [], $cache = null ) {
		parent::__construct( $tex, $params, $cache );
		$this->setMode( MathConfig::MODE_NATIVE_MML );
	}

	protected function doRender(): StatusValue {
		$checker = $this->getChecker();
		$checker->setContext( $this );
		$checker->setHookContainer( MediaWikiServices::getInstance()->getHookContainer() );
		$presentation = $checker->getPresentationMathMLFragment();
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$attributes = [ 'class' => 'mwe-math-element' ];
		if ( $this->getID() !== '' ) {
			$attributes['id'] = $this->getID();
		}
		if ( $config->get( 'MathEnableFormulaLinks' ) &&
			isset( $this->params['qid'] ) &&
			preg_match( '/Q\d+/', $this->params['qid'] ) ) {
			$titleObj = Title::newFromLinkTarget( SpecialPage::getTitleValueFor( 'MathWikibase' ) );
			$attributes['href'] = $titleObj->getLocalURL( [ 'qid' => $this->params['qid'] ] );
		}
		if ( $this->getMathStyle() == 'display' ) {
			$attributes['display'] = 'block';
		}
		$root = new MMLmath( "", $attributes );

		$this->setMathml( $root->encapsulateRaw( $presentation ?? '' ) );
		return StatusValue::newGood();
	}

	protected function getChecker(): LocalChecker {
		$this->checker ??= Math::getCheckerFactory()
			->newLocalChecker( $this->tex, $this->getInputType(), $this->isPurge() );
		return $this->checker;
	}

	/**
	 * @inheritDoc
	 */
	public function getHtmlOutput(): string {
		return $this->getMathml();
	}

	public function readFromCache(): bool {
		return false;
	}

	public function writeCache() {
		return true;
	}
}
