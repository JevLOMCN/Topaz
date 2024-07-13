<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use MediaWiki\Parser\Sanitizer;
use MediaWiki\SpecialPage\SpecialPage;

class EchoPlainTextEmailFormatter extends EchoEventFormatter {
	protected function formatModel( EchoEventPresentationModel $model ) {
		$subject = Sanitizer::stripAllTags( $model->getSubjectMessage()->parse() );

		$text = Sanitizer::stripAllTags( $model->getHeaderMessage()->parse() );

		$text .= "\n\n";

		$bodyMsg = $model->getBodyMessage();
		if ( $bodyMsg ) {
			$text .= Sanitizer::stripAllTags( $bodyMsg->parse() );
		}

		$primaryLink = $model->getPrimaryLinkWithMarkAsRead();
		$colon = $this->msg( 'colon-separator' )->text();

		if ( $primaryLink ) {
			$primaryUrl = wfExpandUrl( $primaryLink['url'], PROTO_CANONICAL );
			$text .= "\n\n{$primaryLink['label']}$colon <$primaryUrl>";
		}

		foreach ( array_filter( $model->getSecondaryLinks() ) as $secondaryLink ) {
			$url = wfExpandUrl( $secondaryLink['url'], PROTO_CANONICAL );
			$text .= "\n\n{$secondaryLink['label']}$colon <$url>";
		}

		// Footer
		$text .= "\n\n{$this->getFooter()}";

		return [
			'body' => $text,
			'subject' => $subject,
		];
	}

	/**
	 * @return string
	 */
	public function getFooter() {
		global $wgEchoEmailFooterAddress;

		$footerMsg = $this->msg( 'echo-email-plain-footer', $this->user )->text();
		$prefsUrl = SpecialPage::getTitleFor( 'Preferences', false, 'mw-prefsection-echo' )
			->getFullURL( '', false, PROTO_CANONICAL );
		$text = "--\n\n$footerMsg\n$prefsUrl";

		if ( $wgEchoEmailFooterAddress !== '' ) {
			$text .= "\n\n$wgEchoEmailFooterAddress";
		}

		return $text;
	}
}
