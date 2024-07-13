<?php

namespace MediaWiki\Extension\DiscussionTools;

class ButtonMenuSelectWidget extends \OOUI\ButtonWidget {
	/**
	 * @inheritDoc
	 */
	protected function getJavaScriptClassName() {
		return 'OO.ui.ButtonMenuSelectWidget';
	}
}
