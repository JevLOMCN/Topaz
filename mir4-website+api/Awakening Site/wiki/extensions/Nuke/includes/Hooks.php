<?php

namespace MediaWiki\Extension\Nuke;

use MediaWiki\Hook\ContributionsToolLinksHook;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use Wikimedia\IPUtils;

class Hooks implements ContributionsToolLinksHook {

	/**
	 * Shows link to Special:Nuke on Special:Contributions/username if applicable
	 *
	 * @param int $id
	 * @param Title $title
	 * @param string[] &$tools
	 * @param SpecialPage $specialPage
	 */
	public function onContributionsToolLinks( $id, Title $title, array &$tools, SpecialPage $specialPage ) {
		$username = $title->getText();
		if ( $specialPage->getUser()->isAllowed( 'nuke' ) && !IPUtils::isValidRange( $username ) ) {
			$tools['nuke'] = $specialPage->getLinkRenderer()->makeKnownLink(
				SpecialPage::getTitleFor( 'Nuke' ),
				$specialPage->msg( 'nuke-linkoncontribs' )->text(),
				[
					'title' => $specialPage->msg( 'nuke-linkoncontribs-text', $username )->text(),
					'class' => 'mw-contributions-link-nuke'
				],
				[ 'target' => $username ]
			);
		}
	}
}
