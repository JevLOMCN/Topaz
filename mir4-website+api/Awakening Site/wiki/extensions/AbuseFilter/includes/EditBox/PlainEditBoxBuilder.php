<?php

namespace MediaWiki\Extension\AbuseFilter\EditBox;

use Xml;

/**
 * Class responsible for building a plain text filter edit box
 */
class PlainEditBoxBuilder extends EditBoxBuilder {
	/**
	 * @inheritDoc
	 */
	public function getEditBox( string $rules, bool $isUserAllowed, bool $externalForm ): string {
		$rules = rtrim( $rules ) . "\n";
		// Rules are in English
		$editorAttribs = [ 'dir' => 'ltr' ];
		if ( !$isUserAllowed ) {
			$editorAttribs['readonly'] = 'readonly';
		}
		if ( $externalForm ) {
			$editorAttribs['form'] = 'wpFilterForm';
		}
		return Xml::textarea( 'wpFilterRules', $rules, 40, 15, $editorAttribs );
	}

}
