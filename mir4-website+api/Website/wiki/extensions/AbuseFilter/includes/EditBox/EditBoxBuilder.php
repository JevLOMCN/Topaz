<?php

namespace MediaWiki\Extension\AbuseFilter\EditBox;

use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Output\OutputPage;
use MediaWiki\Permissions\Authority;
use MessageLocalizer;
use OOUI\ButtonWidget;
use OOUI\DropdownInputWidget;
use OOUI\FieldLayout;
use OOUI\FieldsetLayout;
use OOUI\Widget;
use Xml;

/**
 * Base class for classes responsible for building filter edit boxes
 */
abstract class EditBoxBuilder {
	/** @var AbuseFilterPermissionManager */
	protected $afPermManager;

	/** @var KeywordsManager */
	protected $keywordsManager;

	/** @var MessageLocalizer */
	protected $localizer;

	/** @var Authority */
	protected $authority;

	/** @var OutputPage */
	protected $output;

	/**
	 * @param AbuseFilterPermissionManager $afPermManager
	 * @param KeywordsManager $keywordsManager
	 * @param MessageLocalizer $messageLocalizer
	 * @param Authority $authority
	 * @param OutputPage $output
	 */
	public function __construct(
		AbuseFilterPermissionManager $afPermManager,
		KeywordsManager $keywordsManager,
		MessageLocalizer $messageLocalizer,
		Authority $authority,
		OutputPage $output
	) {
		$this->afPermManager = $afPermManager;
		$this->keywordsManager = $keywordsManager;
		$this->localizer = $messageLocalizer;
		$this->authority = $authority;
		$this->output = $output;
	}

	/**
	 * @param string $rules
	 * @param bool $addResultDiv
	 * @param bool $externalForm
	 * @param bool $needsModifyRights
	 * @param-taint $rules none
	 * @return string
	 */
	public function buildEditBox(
		string $rules,
		bool $addResultDiv = true,
		bool $externalForm = false,
		bool $needsModifyRights = true
	): string {
		$this->output->addModules( 'ext.abuseFilter.edit' );
		$this->output->enableOOUI();

		$isUserAllowed = $needsModifyRights ?
			$this->afPermManager->canEdit( $this->authority ) :
			$this->afPermManager->canUseTestTools( $this->authority );
		if ( !$isUserAllowed ) {
			$addResultDiv = false;
		}

		$output = $this->getEditBox( $rules, $isUserAllowed, $externalForm );

		if ( $isUserAllowed ) {
			$dropdown = $this->getSuggestionsDropdown();

			$formElements = [
				new FieldLayout( $dropdown ),
				new FieldLayout( $this->getEditorControls() )
			];

			$fieldSet = new FieldsetLayout( [
				'items' => $formElements,
				'classes' => [ 'mw-abusefilter-edit-buttons', 'mw-abusefilter-javascript-tools' ]
			] );

			$output .= $fieldSet;
		}

		if ( $addResultDiv ) {
			$output .= Xml::element(
				'div',
				[ 'id' => 'mw-abusefilter-syntaxresult', 'style' => 'display: none;' ],
				'&#160;'
			);
		}

		return $output;
	}

	/**
	 * @return DropdownInputWidget
	 */
	private function getSuggestionsDropdown(): DropdownInputWidget {
		$rawDropdown = $this->keywordsManager->getBuilderValues();

		// The array needs to be rearranged to be understood by OOUI. It comes with the format
		// [ group-msg-key => [ text-to-add => text-msg-key ] ] and we need it as
		// [ group-msg => [ text-msg => text-to-add ] ]
		// Also, the 'other' element must be the first one.
		$dropdownOptions = [ $this->localizer->msg( 'abusefilter-edit-builder-select' )->text() => 'other' ];
		foreach ( $rawDropdown as $group => $values ) {
			// Give grep a chance to find the usages:
			// abusefilter-edit-builder-group-op-arithmetic, abusefilter-edit-builder-group-op-comparison,
			// abusefilter-edit-builder-group-op-bool, abusefilter-edit-builder-group-misc,
			// abusefilter-edit-builder-group-funcs, abusefilter-edit-builder-group-vars
			$localisedGroup = $this->localizer->msg( "abusefilter-edit-builder-group-$group" )->text();
			$dropdownOptions[ $localisedGroup ] = array_flip( $values );
			$newKeys = array_map(
				function ( $key ) use ( $group ) {
					return $this->localizer->msg( "abusefilter-edit-builder-$group-$key" )->text();
				},
				array_keys( $dropdownOptions[ $localisedGroup ] )
			);
			$dropdownOptions[ $localisedGroup ] = array_combine(
				$newKeys,
				$dropdownOptions[ $localisedGroup ]
			);
		}

		$dropdownList = Xml::listDropdownOptionsOoui( $dropdownOptions );
		return new DropdownInputWidget( [
			'name' => 'wpFilterBuilder',
			'inputId' => 'wpFilterBuilder',
			'options' => $dropdownList
		] );
	}

	/**
	 * Get an additional widget that "controls" the editor, and is placed next to it
	 * Precondition: the user has full rights.
	 *
	 * @return Widget
	 */
	protected function getEditorControls(): Widget {
		return new ButtonWidget(
			[
				'label' => $this->localizer->msg( 'abusefilter-edit-check' )->text(),
				'id' => 'mw-abusefilter-syntaxcheck'
			]
		);
	}

	/**
	 * Generate the HTML for the actual edit box
	 *
	 * @param string $rules
	 * @param bool $isUserAllowed
	 * @param bool $externalForm
	 * @return string
	 */
	abstract protected function getEditBox( string $rules, bool $isUserAllowed, bool $externalForm ): string;

}
