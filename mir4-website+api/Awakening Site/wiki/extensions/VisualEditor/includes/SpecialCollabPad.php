<?php
/**
 * CollabPad special page
 *
 * @file
 * @ingroup Extensions
 * @copyright 2011-2020 VisualEditor Team and others; see AUTHORS.txt
 * @license MIT
 */

namespace MediaWiki\Extension\VisualEditor;

use MediaWiki\Title\Title;
use MediaWiki\Widget\TitleInputWidget;
use OOUI\ActionFieldLayout;
use OOUI\ButtonWidget;
use OOUI\FieldsetLayout;
use OOUI\FormLayout;
use OOUI\ProgressBarWidget;
use OOUI\TextInputWidget;
use SpecialPage;
use User;

class SpecialCollabPad extends SpecialPage {

	public function __construct() {
		parent::__construct( 'CollabPad' );
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'wiki';
	}

	/**
	 * @inheritDoc
	 */
	public function userCanExecute( User $user ) {
		return $this->getConfig()->get( 'VisualEditorRebaserURL' ) &&
			parent::userCanExecute( $user );
	}

	/**
	 * @inheritDoc
	 */
	public function isListed() {
		return (bool)$this->getConfig()->get( 'VisualEditorRebaserURL' );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		$this->setHeaders();
		$this->checkPermissions();

		$output = $this->getOutput();

		$output->addJsConfigVars( 'collabPadPageName', $subPage );
		$output->addModuleStyles( 'ext.visualEditor.collabTarget.init.styles' );
		$output->addModuleStyles( 'oojs-ui.styles.icons-editing-core' );
		$output->addModuleStyles( 'oojs-ui.styles.icons-content' );

		$output->addModules( 'ext.visualEditor.collabTarget.init' );

		$output->enableOOUI();

		$documentNameFieldset = new FieldsetLayout( [
			'label' => $this->msg( 'visualeditor-rebase-client-document-create-edit' )->text(),
			'icon' => 'edit',
			'items' => [
				new ActionFieldLayout(
					new TextInputWidget( [
						'classes' => [ 've-init-mw-collabTarget-nameInput' ],
						'placeholder' => $this->msg( 'visualeditor-rebase-client-document-name' )->text(),
						'autofocus' => true,
						'infusable' => true
					] ),
					new ButtonWidget( [
						'classes' => [ 've-init-mw-collabTarget-nameButton' ],
						'label' => $this->msg( 'visualeditor-rebase-client-document-create-edit' )->text(),
						'flags' => [ 'primary', 'progressive' ],
						// Only enable once JS has loaded
						'disabled' => true,
						'infusable' => true
					] ),
					[
						'align' => 'top',
						'classes' => [ 've-init-mw-collabTarget-nameField' ],
						'infusable' => true
					]
				)
			]
		] );
		$importFieldset = new FieldsetLayout( [
			'label' => $this->msg( 'visualeditor-rebase-client-import' )->text(),
			'icon' => 'download',
			'items' => [
				new ActionFieldLayout(
					new TitleInputWidget( [
						'classes' => [ 've-init-mw-collabTarget-importInput' ],
						'placeholder' => $this->msg( 'visualeditor-rebase-client-import-name' )->text(),
						'infusable' => true,
					] ),
					new ButtonWidget( [
						'classes' => [ 've-init-mw-collabTarget-importButton' ],
						'label' => $this->msg( 'visualeditor-rebase-client-import' )->text(),
						'flags' => [ 'progressive' ],
						// Only enable once JS has loaded
						'disabled' => true,
						'infusable' => true
					] ),
					[
						'align' => 'top',
						'classes' => [ 've-init-mw-collabTarget-importField' ],
						'infusable' => true
					]
				)
			]
		] );

		$form = new FormLayout( [
			'classes' => [ 've-init-mw-collabTarget-form' ],
			'items' => [
				$documentNameFieldset,
				$importFieldset
			],
			'infusable' => true
		] );

		$progressBar = new ProgressBarWidget( [
			'classes' => [ 've-init-mw-collabTarget-loading' ],
			'infusable' => true
		] );

		if ( $subPage ) {
			$title = Title::newFromText( $subPage );
			$output->setPageTitleMsg( $this->msg( 'collabpad-doctitle', $title->getPrefixedText() ) );
			$form->addClasses( [ 'oo-ui-element-hidden' ] );
		} else {
			// Scripts only, styles already added above
			$output->addModules( 'ext.visualEditor.collabTarget' );
			$progressBar->addClasses( [ 'oo-ui-element-hidden' ] );
		}
		$output->addHTML( $progressBar . $form );
	}
}
