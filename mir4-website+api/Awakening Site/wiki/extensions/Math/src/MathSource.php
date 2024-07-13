<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2012 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz and other MediaWiki
 * contributors
 * @license GPL-2.0-or-later
 *
 * Contains everything related to <math> </math> parsing
 * @file
 */

namespace MediaWiki\Extension\Math;

use LogicException;
use MediaWiki\Html\Html;

/**
 * Takes LaTeX fragments and outputs the source directly to the browser
 *
 * @author Tomasz Wegrzanowski
 * @author Brion Vibber
 * @author Moritz Schubotz
 * @ingroup Parser
 */
class MathSource extends MathRenderer {
	/**
	 * @param string $tex
	 * @param array $params
	 */
	public function __construct( $tex = '', $params = [] ) {
		parent::__construct( $tex, $params );
		$this->setMode( MathConfig::MODE_SOURCE );
	}

	/**
	 * Renders TeX by outputting it to the browser in a span tag
	 *
	 * @return string span tag with TeX
	 */
	public function getHtmlOutput() {
		# No need to render or parse anything more!
		# New lines are replaced with spaces, which avoids confusing our parser (bugs 23190, 22818)
		if ( $this->getMathStyle() == 'display' ) {
			$class = 'mwe-math-fallback-source-display';
		} else {
			$class = 'mwe-math-fallback-source-inline';
		}
		return Html::element( 'span',
			$this->getAttributes(
				'span',
				[
					// the former class name was 'tex'
					// for backwards compatibility we keep this classname T348938
					'class' => $class . ' tex',
					'dir' => 'ltr'
				]
			),
			'$ ' . str_replace( "\n", " ", $this->getTex() ) . ' $'
		);
	}

	/**
	 * @throws LogicException always
	 * @return never
	 */
	protected function getMathTableName() {
		throw new LogicException( 'in math source mode no database caching should happen' );
	}

	/**
	 * No rendering required in plain text mode
	 * @return bool
	 */
	public function render() {
		// assume unchanged to avoid unnecessary database access
		$this->changed = false;
		return true;
	}
}
