<?php

namespace MediaWiki\Extension\ConfirmEdit\FancyCaptcha;

use FileBackend;
use FSFileBackend;
use InvalidArgumentException;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Utils\MWTimestamp;
use MediaWiki\WikiMap\WikiMap;
use NullLockManager;
use ObjectCache;
use RequestContext;
use StatusValue;
use UnderflowException;

/**
 * FancyCaptcha for displaying captchas precomputed by captcha.py
 */
class FancyCaptcha extends SimpleCaptcha {
	// used for fancycaptcha-edit, fancycaptcha-addurl, fancycaptcha-badlogin,
	// fancycaptcha-accountcreate, fancycaptcha-create, fancycaptcha-sendemail via getMessage()
	protected static $messagePrefix = 'fancycaptcha-';

	/**
	 * @return FileBackend
	 */
	public function getBackend() {
		global $wgCaptchaFileBackend, $wgCaptchaDirectory;

		if ( $wgCaptchaFileBackend ) {
			return MediaWikiServices::getInstance()->getFileBackendGroup()
				->get( $wgCaptchaFileBackend );
		}

		static $backend = null;
		if ( !$backend ) {
			$backend = new FSFileBackend( [
				'name'           => 'captcha-backend',
				'wikiId'         => WikiMap::getCurrentWikiId(),
				'lockManager'    => new NullLockManager( [] ),
				'containerPaths' => [ $this->getStorageDir() => $wgCaptchaDirectory ],
				'fileMode'       => 777,
				'obResetFunc'    => 'wfResetOutputBuffers',
				'streamMimeFunc' => [ 'StreamFile', 'contentTypeFromPath' ]
			] );
		}

		return $backend;
	}

	/**
	 * @return int Number of captcha files
	 */
	public function getCaptchaCount() {
		$backend = $this->getBackend();
		$files = $backend->getFileList(
			[ 'dir' => $backend->getRootStoragePath() . '/' . $this->getStorageDir() ]
		);

		return iterator_count( $files );
	}

	/**
	 * @return string
	 */
	public function getStorageDir() {
		global $wgCaptchaStorageDirectory;
		return $wgCaptchaStorageDirectory;
	}

	/**
	 * Check if the submitted form matches the captcha session data provided
	 * by the plugin when the form was generated.
	 *
	 * @param string $answer
	 * @param array $info
	 * @return bool
	 */
	protected function keyMatch( $answer, $info ) {
		global $wgCaptchaSecret;

		$digest = $wgCaptchaSecret . $info['salt'] . $answer . $wgCaptchaSecret . $info['salt'];
		$answerHash = substr( md5( $digest ), 0, 16 );

		if ( $answerHash == $info['hash'] ) {
			wfDebug( "FancyCaptcha: answer hash matches expected {$info['hash']}\n" );
			return true;
		} else {
			wfDebug( "FancyCaptcha: answer hashes to $answerHash, expected {$info['hash']}\n" );
			return false;
		}
	}

	/**
	 * @param array &$resultArr
	 */
	protected function addCaptchaAPI( &$resultArr ) {
		$info = $this->pickImage();
		if ( !$info ) {
			$resultArr['captcha']['error'] = 'Out of images';
			return;
		}
		$index = $this->storeCaptcha( $info );
		$title = SpecialPage::getTitleFor( 'Captcha', 'image' );
		$resultArr['captcha'] = $this->describeCaptchaType();
		$resultArr['captcha']['id'] = $index;
		$resultArr['captcha']['url'] = $title->getLocalURL( 'wpCaptchaId=' . urlencode( $index ) );
	}

	/**
	 * @return array
	 */
	public function describeCaptchaType() {
		return [
			'type' => 'image',
			'mime' => 'image/png',
		];
	}

	/**
	 * @param int $tabIndex
	 * @return array
	 */
	public function getFormInformation( $tabIndex = 1 ) {
		$title = SpecialPage::getTitleFor( 'Captcha', 'image' );
		$info = $this->getCaptcha();
		$index = $this->storeCaptcha( $info );

		$captchaReload = Html::element(
			'small',
			[
				'class' => 'confirmedit-captcha-reload fancycaptcha-reload'
			],
			wfMessage( 'fancycaptcha-reload-text' )->text()
		);

		$form = Html::openElement( 'div' ) .
			Html::element( 'label', [
					'for' => 'wpCaptchaWord',
				],
				wfMessage( 'captcha-label' )->text() . ' ' . wfMessage( 'fancycaptcha-captcha' )->text()
			) .
			Html::openElement( 'div', [ 'class' => 'fancycaptcha-captcha-container' ] ) .
			Html::openElement( 'div', [ 'class' => 'fancycaptcha-captcha-and-reload' ] ) .
			Html::openElement( 'div', [ 'class' => 'fancycaptcha-image-container' ] ) .
			Html::element( 'img', [
					'class'  => 'fancycaptcha-image',
					'src'    => $title->getLocalURL( 'wpCaptchaId=' . urlencode( $index ) ),
					'alt'    => ''
				]
			) . $captchaReload . Html::closeElement( 'div' ) . Html::closeElement( 'div' ) . "\n" .
			// FIXME: This should use CodexHTMLForm rather than Html::element
			Html::openElement( 'div', [ 'class' => 'cdx-text-input' ] ) .
			Html::element( 'input', [
					'name' => 'wpCaptchaWord',
					'class' => 'cdx-text-input__input',
					'id'   => 'wpCaptchaWord',
					'type' => 'text',
					// max_length in captcha.py plus fudge factor
					'size' => '12',
					'autocomplete' => 'off',
					'autocorrect' => 'off',
					'autocapitalize' => 'off',
					'required' => 'required',
					// tab in before the edit textarea
					'tabindex' => $tabIndex,
					'placeholder' => wfMessage( 'fancycaptcha-imgcaptcha-ph' )->text()
				]
			) . Html::closeElement( 'div' );
		if ( $this->action == 'createaccount' ) {
			// use raw element, because the message can contain links or some other html
			$form .= Html::rawElement( 'small', [
					'class' => 'mw-createacct-captcha-assisted'
				], wfMessage( 'createacct-imgcaptcha-help' )->parse()
			);
		}
		$form .= Html::element( 'input', [
				'type'  => 'hidden',
				'name'  => 'wpCaptchaId',
				'id'    => 'wpCaptchaId',
				'value' => $index
			]
		) . Html::closeElement( 'div' ) . Html::closeElement( 'div' ) . "\n";

		return [
			'html' => $form,
			'modules' => [ 'ext.confirmEdit.fancyCaptcha' ],
			// Uses addModuleStyles so it is loaded when JS is disabled.
			'modulestyles' => [ 'codex-styles', 'ext.confirmEdit.fancyCaptcha.styles' ],
		];
	}

	/**
	 * Select a previously generated captcha image from the queue.
	 * @return mixed tuple of (salt key, text hash) or false if no image to find
	 */
	protected function pickImage() {
		global $wgCaptchaDirectoryLevels;

		// number of times another process claimed a file before this one
		$lockouts = 0;
		$baseDir = $this->getBackend()->getRootStoragePath() . '/' . $this->getStorageDir();
		return $this->pickImageDir( $baseDir, $wgCaptchaDirectoryLevels, $lockouts );
	}

	/**
	 * @param string $directory
	 * @param int $levels
	 * @param int &$lockouts
	 * @return array|bool
	 */
	protected function pickImageDir( $directory, $levels, &$lockouts ) {
		if ( $levels <= 0 ) {
			// $directory has regular files
			return $this->pickImageFromDir( $directory, $lockouts );
		}

		$backend = $this->getBackend();
		$cache = ObjectCache::getLocalClusterInstance();

		$key = $cache->makeGlobalKey(
			'fancycaptcha-dirlist',
			$backend->getDomainId(),
			sha1( $directory )
		);

		// check cache
		$dirs = $cache->get( $key );
		if ( !is_array( $dirs ) || !count( $dirs ) ) {
			// cache miss
			$dirs = [];
			// subdirs actually present...
			foreach ( $backend->getTopDirectoryList( [ 'dir' => $directory ] ) as $entry ) {
				if ( ctype_xdigit( $entry ) && strlen( $entry ) == 1 ) {
					$dirs[] = $entry;
				}
			}
			wfDebug( "Cache miss for $directory subdirectory listing.\n" );
			if ( count( $dirs ) ) {
				$cache->set( $key, $dirs, 86400 );
			}
		}

		if ( !count( $dirs ) ) {
			// Remove this directory if empty so callers don't keep looking here
			$backend->clean( [ 'dir' => $directory ] );
			// none found
			return false;
		}

		// pick a random subdir
		$place = mt_rand( 0, count( $dirs ) - 1 );
		// In case all dirs are not filled, cycle through next digits...
		$fancyCount = count( $dirs );
		for ( $j = 0; $j < $fancyCount; $j++ ) {
			$char = $dirs[( $place + $j ) % count( $dirs )];
			$info = $this->pickImageDir( "$directory/$char", $levels - 1, $lockouts );
			if ( $info ) {
				// found a captcha
				return $info;
			} else {
				wfDebug( "Could not find captcha in $directory.\n" );
				// files changed on disk?
				$cache->delete( $key );
			}
		}

		// didn't find any images in this directory... empty?
		return false;
	}

	/**
	 * @param string $directory
	 * @param int &$lockouts
	 * @return array|bool
	 */
	protected function pickImageFromDir( $directory, &$lockouts ) {
		$backend = $this->getBackend();
		$cache = ObjectCache::getLocalClusterInstance();

		$key = $cache->makeGlobalKey(
			'fancycaptcha-filelist',
			$backend->getDomainId(),
			sha1( $directory )
		);

		// check cache
		$files = $cache->get( $key );
		if ( !is_array( $files ) || !count( $files ) ) {
			// cache miss
			$files = [];
			foreach ( $backend->getTopFileList( [ 'dir' => $directory ] ) as $entry ) {
				$files[] = $entry;
				if ( count( $files ) >= 500 ) {
					// sanity
					wfDebug( 'Skipping some captchas; $wgCaptchaDirectoryLevels set too low?.' );
					break;
				}
			}
			if ( count( $files ) ) {
				$cache->set( $key, $files, 86400 );
			}
			wfDebug( "Cache miss for $directory captcha listing.\n" );
		}

		if ( !count( $files ) ) {
			// Remove this directory if empty so callers don't keep looking here
			$backend->clean( [ 'dir' => $directory ] );
			return false;
		}

		$info = $this->pickImageFromList( $directory, $files, $lockouts );
		if ( !$info ) {
			wfDebug( "Could not find captcha in $directory.\n" );
			// files changed on disk?
			$cache->delete( $key );
		}

		return $info;
	}

	/**
	 * @param string $directory
	 * @param array $files
	 * @param int &$lockouts
	 * @return array|bool
	 */
	protected function pickImageFromList( $directory, array $files, &$lockouts ) {
		global $wgCaptchaDeleteOnSolve;

		if ( !count( $files ) ) {
			// none found
			return false;
		}

		$backend = $this->getBackend();
		$cache = ObjectCache::getLocalClusterInstance();

		// pick a random file
		$place = mt_rand( 0, count( $files ) - 1 );
		// number of files in listing that don't actually exist
		$misses = 0;
		$fancyImageCount = count( $files );
		for ( $j = 0; $j < $fancyImageCount; $j++ ) {
			$entry = $files[( $place + $j ) % count( $files )];
			if ( preg_match( '/^image_([0-9a-f]+)_([0-9a-f]+)\\.png$/', $entry, $matches ) ) {
				if ( $wgCaptchaDeleteOnSolve ) {
					// captcha will be deleted when solved
					$key = $cache->makeGlobalKey(
						'fancycaptcha-filelock',
						$backend->getDomainId(),
						sha1( $entry )
					);
					// Try to claim this captcha for 10 minutes (for the user to solve)...
					if ( ++$lockouts <= 10 && !$cache->add( $key, '1', 600 ) ) {
						// could not acquire (skip it to avoid race conditions)
						continue;
					}
				}
				if ( !$backend->fileExists( [ 'src' => "$directory/$entry" ] ) ) {
					if ( ++$misses >= 5 ) {
						// too many files in the listing don't exist
						// listing cache too stale? break out so it will be cleared
						break;
					}
					// try next file
					continue;
				}
				return [
					'salt'   => $matches[1],
					'hash'   => $matches[2],
					'viewed' => false,
				];
			}
		}

		// none found
		return false;
	}

	/**
	 * @return bool|StatusValue
	 */
	public function showImage() {
		$context = RequestContext::getMain();
		$context->getOutput()->disable();

		$index = $context->getRequest()->getVal( 'wpCaptchaId' );
		$info = $this->retrieveCaptcha( $index );
		if ( $info ) {
			$timestamp = new MWTimestamp();
			$info['viewed'] = $timestamp->getTimestamp();
			$this->storeCaptcha( $info );

			$salt = $info['salt'];
			$hash = $info['hash'];

			return $this->getBackend()->streamFile( [
				'src'     => $this->imagePath( $salt, $hash ),
				'headers' => [ "Cache-Control: private, s-maxage=0, max-age=3600" ]
			] )->isOK();
		}

		wfHttpError( 400, 'Request Error', 'Requested bogus captcha image' );
		return false;
	}

	/**
	 * @param string $salt
	 * @param string $hash
	 * @return string
	 */
	public function imagePath( $salt, $hash ) {
		global $wgCaptchaDirectoryLevels;

		$file = $this->getBackend()->getRootStoragePath() . '/' . $this->getStorageDir() . '/';
		for ( $i = 0; $i < $wgCaptchaDirectoryLevels; $i++ ) {
			$file .= $hash[ $i ] . '/';
		}
		$file .= "image_{$salt}_{$hash}.png";

		return $file;
	}

	/**
	 * @param string $basename
	 * @return array (salt, hash)
	 */
	public function hashFromImageName( $basename ) {
		if ( preg_match( '/^image_([0-9a-f]+)_([0-9a-f]+)\\.png$/', $basename, $matches ) ) {
			return [ $matches[1], $matches[2] ];
		} else {
			throw new InvalidArgumentException( "Invalid filename '$basename'.\n" );
		}
	}

	/**
	 * Delete a solved captcha image, if $wgCaptchaDeleteOnSolve is true.
	 * @inheritDoc
	 */
	protected function passCaptcha( $index, $word ) {
		global $wgCaptchaDeleteOnSolve;

		// get the captcha info before it gets deleted
		$info = $this->retrieveCaptcha( $index );
		$pass = parent::passCaptcha( $index, $word );

		if ( $pass && $wgCaptchaDeleteOnSolve ) {
			$this->getBackend()->quickDelete( [
				'src' => $this->imagePath( $info['salt'], $info['hash'] )
			] );
		}

		return $pass;
	}

	/**
	 * Returns an array with 'salt' and 'hash' keys. Hash is
	 * md5( $wgCaptchaSecret . $salt . $answer . $wgCaptchaSecret . $salt )[0..15]
	 * @return array
	 * @throws UnderflowException When a captcha image cannot be produced.
	 */
	public function getCaptcha() {
		$info = $this->pickImage();
		if ( !$info ) {
			throw new UnderflowException( 'Ran out of captcha images' );
		}
		return $info;
	}

	/**
	 * @param array $captchaData
	 * @param string $id
	 * @return string
	 */
	public function getCaptchaInfo( $captchaData, $id ) {
		$title = SpecialPage::getTitleFor( 'Captcha', 'image' );
		return $title->getLocalURL( 'wpCaptchaId=' . urlencode( $id ) );
	}

	/**
	 * @param array $requests
	 * @param array $fieldInfo
	 * @param array &$formDescriptor
	 * @param string $action
	 */
	public function onAuthChangeFormFields(
		array $requests, array $fieldInfo, array &$formDescriptor, $action
	) {
		/** @var CaptchaAuthenticationRequest $req */
		$req =
			AuthenticationRequest::getRequestByClass( $requests,
				CaptchaAuthenticationRequest::class, true );
		if ( !$req ) {
			return;
		}

		// HTMLFancyCaptchaField will include this
		unset( $formDescriptor['captchaInfo' ] );

		$formDescriptor['captchaWord'] = [
			'class' => HTMLFancyCaptchaField::class,
			'imageUrl' => $this->getCaptchaInfo( $req->captchaData, $req->captchaId ),
			'label-message' => $this->getMessage( $this->action ),
			'showCreateHelp' => in_array( $action, [
				AuthManager::ACTION_CREATE,
				AuthManager::ACTION_CREATE_CONTINUE
			], true ),
		] + $formDescriptor['captchaWord'];
	}
}

class_alias( FancyCaptcha::class, 'FancyCaptcha' );
