<?php

namespace MediaWiki\Extension\Math\Widget;

use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\Extension\Math\Render\RendererFactory;
use MediaWiki\Extension\Math\SpecialMathStatus;
use OOUIHTMLForm;

class MathTestInputForm extends OOUIHTMLForm {

	private SpecialMathStatus $specialPage;
	private array $modes;
	private RendererFactory $rendererFactory;

	public function __construct( SpecialMathStatus $specialPage, array $modes, RendererFactory $rendererFactory ) {
		$this->specialPage = $specialPage;
		$this->modes = $modes;
		$this->rendererFactory = $rendererFactory;
		$formDescriptor = [
			'tex' => [
				'type' => 'text'
			],
			'type' => [
				'type' => 'radio',
				'options' => [ 'tex', 'chem' ]
			],
			'display' => [
				'type' => 'radio',
				'options' => [ 'default', 'inline', 'block' ]
			]
		];
		$this->addOptions( $formDescriptor );
		parent::__construct( $formDescriptor, $specialPage->getContext() );
		$this->setSubmitCallback( [ $this, 'processInput' ] );
	}

	private function addOptions( array &$form ): void {
		static $elements = [ 'label', 'help' ];
		static $optionLabelPrefix = [
			'display' => 'math-visualeditor-mwlatexinspector-display-',
			'type' => 'math-form-type-'
		];
		foreach ( $form as $key => $control ) {
			foreach ( $elements as $element ) {
				$msg = "math-form-$key-$element";
				if ( wfMessage( $msg )->exists() ) {
					$form[$key]["$element-message"] = $msg;
				}
			}
			if ( isset( $control[ 'options' ] ) ) {
				$options = [];
				foreach ( $control['options'] as $value ) {
					// Messages that can be used here:
					// * math-form-type-tex
					// * math-form-type-chem
					$txt = wfMessage( $optionLabelPrefix[$key] . $value )->parseAsBlock();
					$options[$txt] = $value;
				}
				$form[$key]['options'] = $options;
			}
		}
	}

	public function processInput( $formData ) {
		$out = $this->specialPage->getOutput();
		foreach ( $this->modes as $mode => $modeName ) {
			$out->wrapWikiMsg( '=== $1 ===', $modeName );
			$out->addWikiMsgArray( 'math-test-start', [ $modeName ] );

			$options = [
				'type' => $formData['type']
			];
			if ( $formData['display'] !== 'default' ) {
				$options['display'] = $formData['display'];
			}
			$renderer = $this->rendererFactory->getRenderer( $formData['tex'], $options, $mode );
			if ( ( $mode === MathConfig::MODE_SOURCE || $renderer->checkTeX() )
				&& $renderer->render() ) {
				$html = $renderer->getHtmlOutput();
			} else {
				$html = $renderer->getLastError();
			}
			$out->addHTML( $html );
			$out->addWikiMsgArray( 'math-test-end', [ $modeName ] );

		}
	}
}
