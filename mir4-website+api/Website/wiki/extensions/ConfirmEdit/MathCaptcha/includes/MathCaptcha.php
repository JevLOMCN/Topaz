<?php

namespace MediaWiki\Extension\ConfirmEdit\MathCaptcha;

use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Extension\Math\MathRenderer;
use MediaWiki\Html\Html;

class MathCaptcha extends SimpleCaptcha {

	/**
	 * Validate a captcha response
	 * @param string $answer
	 * @param array $info
	 * @return bool
	 */
	protected function keyMatch( $answer, $info ) {
		return (int)$answer == (int)$info['answer'];
	}

	/**
	 * @param array &$resultArr
	 */
	protected function addCaptchaAPI( &$resultArr ) {
		[ $sum, $answer ] = $this->pickSum();
		$html = $this->fetchMath( $sum );
		$index = $this->storeCaptcha( [ 'answer' => $answer ] );
		$resultArr['captcha'] = $this->describeCaptchaType();
		$resultArr['captcha']['id'] = $index;
		$resultArr['captcha']['question'] = $html;
	}

	/**
	 * @return array
	 */
	public function describeCaptchaType() {
		return [
			'type' => 'math',
			'mime' => 'text/html',
		];
	}

	/**
	 * @param int $tabIndex
	 * @return array
	 */
	public function getFormInformation( $tabIndex = 1 ) {
		[ $sum, $answer ] = $this->pickSum();
		$index = $this->storeCaptcha( [ 'answer' => $answer ] );

		$form = '<table><tr><td>' . $this->fetchMath( $sum ) . '</td>';
		$form .= '<td>' . Html::input( 'wpCaptchaWord', '', '', [
			'tabindex' => $tabIndex,
			'autocomplete' => 'off',
			'required'
		] ) . '</td></tr></table>';
		$form .= Html::hidden( 'wpCaptchaId', $index );
		return [ 'html' => $form ];
	}

	/**
	 * Pick a random sum
	 * @return array
	 */
	private function pickSum() {
		$a = mt_rand( 0, 100 );
		$b = mt_rand( 0, 10 );
		$op = mt_rand( 0, 1 ) ? '+' : '-';
		$sum = "{$a} {$op} {$b} = ";
		$ans = $op == '+' ? ( $a + $b ) : ( $a - $b );
		return [ $sum, $ans ];
	}

	/**
	 * Fetch the math
	 * @param string $sum
	 * @return string
	 */
	private function fetchMath( $sum ) {
		$math = MathRenderer::getRenderer( $sum, [], 'png' );
		$math->render();
		$html = $math->getHtmlOutput();
		return preg_replace( '/alt=".*?"/', '', $html );
	}

	/**
	 * @return array
	 */
	public function getCaptcha() {
		[ $sum, $answer ] = $this->pickSum();
		return [ 'question' => $sum, 'answer' => $answer ];
	}

	/**
	 * @param array $captchaData
	 * @param string $id
	 * @return mixed
	 */
	public function getCaptchaInfo( $captchaData, $id ) {
		$sum = $captchaData['question'];
		return $this->fetchMath( $sum );
	}

	/**
	 * @param array $requests
	 * @param array $fieldInfo
	 * @param array &$formDescriptor
	 * @param string $action
	 */
	public function onAuthChangeFormFields( array $requests, array $fieldInfo,
		array &$formDescriptor, $action ) {
		/** @var CaptchaAuthenticationRequest $req */
		$req = AuthenticationRequest::getRequestByClass(
			$requests,
			CaptchaAuthenticationRequest::class,
			true
		);
		if ( !$req ) {
			return;
		}

		$formDescriptor['captchaInfo']['raw'] = true;
		$formDescriptor['captchaWord']['label-message'] = null;
	}
}

class_alias( MathCaptcha::class, 'MathCaptcha' );
