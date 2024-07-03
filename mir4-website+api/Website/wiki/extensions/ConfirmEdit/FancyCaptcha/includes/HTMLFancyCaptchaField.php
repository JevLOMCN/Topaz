<?php

namespace MediaWiki\Extension\ConfirmEdit\FancyCaptcha;

use HTMLFormField;
use MediaWiki\Html\Html;

/**
 * Captcha input field for FancyCaptcha that displays a question and returns the answer.
 * Does not include the captcha ID; that must be included in the form as a separate hidden field.
 */
class HTMLFancyCaptchaField extends HTMLFormField {
	/** @var string */
	protected $imageUrl;

	/** @var bool */
	protected $showCreateHelp;

	protected $mClass = 'captcha';

	/**
	 * Apart from normal HTMLFormField parameters, recognizes the following keys:
	 * - 'imageUrl': (string, required) src of the captcha image
	 * - 'showCreateHelp': (bool) show some extra messaging that's only relevant at account creation
	 * @param array $params
	 */
	public function __construct( array $params ) {
		parent::__construct( $params );
		$this->imageUrl = $params['imageUrl'];
		$this->showCreateHelp = !empty( $params['showCreateHelp'] );
	}

	/**
	 * @inheritDoc
	 */
	public function getInputHTML( $value ) {
		$out = $this->mParent->getOutput();

		// Uses addModuleStyles so it is loaded even when JS is disabled.
		$out->addModuleStyles( [ 'codex-styles', 'ext.confirmEdit.fancyCaptcha.styles' ] );

		// Loaded only for clients with JS enabled
		$out->addModules( 'ext.confirmEdit.fancyCaptcha' );

		$captchaReload = Html::element(
			'small',
			[ 'class' => 'confirmedit-captcha-reload fancycaptcha-reload' ],
			$this->mParent->msg( 'fancycaptcha-reload-text' )->text()
		);

		$attribs = [
			'type' => 'text',
			'id'   => $this->mID,
			'name' => $this->mName,
			'class' => 'cdx-text-input__input',
			// max_length in captcha.py plus fudge factor
			'size' => '12',
			'dir' => $this->mDir,
			'autocomplete' => 'off',
			'autocorrect' => 'off',
			'autocapitalize' => 'off',
			'placeholder' => $this->mParent->msg( 'fancycaptcha-imgcaptcha-ph' )->text()
		];
		$attribs += $this->getAttributes( [ 'tabindex', 'required', 'autofocus' ] );

		$html = Html::openElement( 'div', [ 'class' => 'fancycaptcha-captcha-container' ] )
			. Html::openElement( 'div', [ 'class' => 'fancycaptcha-captcha-and-reload' ] )
			. Html::openElement( 'div', [ 'class' => 'fancycaptcha-image-container' ] )
			. Html::element( 'img', [
				'class'  => 'fancycaptcha-image',
				'src'    => $this->imageUrl,
				'alt'    => ''
			] ) . $captchaReload . Html::closeElement( 'div' ) . Html::closeElement( 'div' ) . "\n"
			. Html::openElement( 'div', [ 'class' => 'cdx-text-input' ] )
			. Html::element( 'input', $attribs ) . Html::closeElement( 'div' );

		if ( $this->showCreateHelp ) {
			// use raw element, the message will contain a link
			$html .= Html::rawElement( 'small', [
				'class' => 'mw-createacct-captcha-assisted'
			], $this->mParent->msg( 'createacct-imgcaptcha-help' )->parse() );
		}

		$html .= Html::closeElement( 'div' );

		return $html;
	}

	/**
	 * @inheritDoc
	 */
	public function getLabel() {
		// slight abuse of what getLabel() should mean; $mLabel is used for the pre-label text
		// as the actual label is always the same
		return $this->mParent->msg( 'captcha-label' )->text() . ' '
			. $this->mParent->msg( 'fancycaptcha-captcha' )->text();
	}

	/**
	 * @inheritDoc
	 */
	public function getLabelHtml( $cellAttributes = [] ) {
		$labelHtml = parent::getLabelHtml( $cellAttributes );
		if ( $this->mLabel ) {
			// use raw element, the message will contain a link
			$labelHtml = Html::rawElement( 'p', [], $this->mLabel ) . $labelHtml;
		}
		return $labelHtml;
	}
}
