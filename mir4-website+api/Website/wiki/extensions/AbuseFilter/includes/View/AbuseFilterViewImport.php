<?php

namespace MediaWiki\Extension\AbuseFilter\View;

use HTMLForm;

class AbuseFilterViewImport extends AbuseFilterView {
	/**
	 * Shows the page
	 */
	public function show() {
		$out = $this->getOutput();
		if ( !$this->afPermManager->canEdit( $this->getAuthority() ) ) {
			$out->addWikiMsg( 'abusefilter-edit-notallowed' );
			return;
		}

		$out->addWikiMsg( 'abusefilter-import-intro' );

		$formDescriptor = [
			'ImportText' => [
				'type' => 'textarea',
				'required' => true
			]
		];
		HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setTitle( $this->getTitle( 'new' ) )
			->setSubmitTextMsg( 'abusefilter-import-submit' )
			->show();
	}
}
