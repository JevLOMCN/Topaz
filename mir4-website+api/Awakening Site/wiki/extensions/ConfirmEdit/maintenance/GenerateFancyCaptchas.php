<?php
/**
 * Generate fancy captchas using a python script and copy them into storage.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @author Aaron Schulz
 * @ingroup Maintenance
 */
if ( getenv( 'MW_INSTALL_PATH' ) ) {
	$IP = getenv( 'MW_INSTALL_PATH' );
} else {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\Extension\ConfirmEdit\FancyCaptcha\FancyCaptcha;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Shell\Shell;
use MediaWiki\Status\Status;

/**
 * Maintenance script to generate fancy captchas using a python script and copy them into storage.
 *
 * @ingroup Maintenance
 */
class GenerateFancyCaptchas extends Maintenance {
	public function __construct() {
		parent::__construct();

		// See captcha.py for argument usage
		$this->addOption( "wordlist", 'A list of words', true, true );
		$this->addOption( "font", "The font to use", true, true );
		$this->addOption( "font-size", "The font size ", false, true );
		$this->addOption( "badwordlist", "A list of words that should not be used", false, true );
		$this->addOption( "fill", "Fill the captcha container to N files", true, true );
		$this->addOption(
			"verbose",
			"Show debugging information when running the captcha python script"
		);
		$this->addOption(
			"oldcaptcha",
			"DEPRECATED: Whether to use captcha-old.py which doesn't have OCR fighting improvements"
		);
		$this->addOption( "delete", "Deletes all the old captchas" );
		$this->addOption( "threads", "The number of threads to use to generate the images",
			false, true );
		$this->addOption(
			'captchastoragedir',
			'Overrides the value of $wgCaptchaStorageDirectory',
			false,
			true
		);
		$this->addDescription( "Generate new fancy captchas and move them into storage" );

		$this->requireExtension( "FancyCaptcha" );
	}

	public function execute() {
		global $wgCaptchaSecret, $wgCaptchaDirectoryLevels;

		$totalTime = -microtime( true );

		$instance = Hooks::getInstance();
		if ( !( $instance instanceof FancyCaptcha ) ) {
			$this->fatalError( "\$wgCaptchaClass is not FancyCaptcha.\n", 1 );
		}

		// Overrides $wgCaptchaStorageDirectory for this script run
		if ( $this->hasOption( 'captchastoragedir' ) ) {
			global $wgCaptchaStorageDirectory;
			$wgCaptchaStorageDirectory = $this->getOption( 'captchastoragedir' );
		}

		$backend = $instance->getBackend();

		$deleteOldCaptchas = $this->getOption( 'delete' );

		$countGen = (int)$this->getOption( 'fill' );
		if ( !$deleteOldCaptchas ) {
			$countAct = $instance->getCaptchaCount();
			$this->output( "Current number of captchas is $countAct.\n" );
			$countGen -= $countAct;
		}

		if ( $countGen <= 0 ) {
			$this->output( "No need to generate any extra captchas.\n" );
			return;
		}

		$tmpDir = wfTempDir() . '/mw-fancycaptcha-' . time() . '-' . wfRandomString( 6 );
		if ( !wfMkdirParents( $tmpDir ) ) {
			$this->fatalError( "Could not create temp directory.\n", 1 );
		}

		$captchaScript = 'captcha.py';

		if ( $this->hasOption( 'oldcaptcha' ) ) {
			$this->output( "Using --oldcaptcha is deprecated, and captcha-old.py will be removed in the future!" );
			$captchaScript = 'captcha-old.py';
		}

		$cmd = [
			"python3",
			dirname( __DIR__ ) . '/' . $captchaScript,
			"--key",
			$wgCaptchaSecret,
			"--output",
			$tmpDir,
			"--count",
			(string)$countGen,
			"--dirs",
			$wgCaptchaDirectoryLevels
		];
		foreach (
			[ 'wordlist', 'font', 'font-size', 'badwordlist', 'verbose', 'threads' ] as $par
		) {
			if ( $this->hasOption( $par ) ) {
				$cmd[] = "--$par";
				$cmd[] = $this->getOption( $par );
			}
		}

		$this->output( "Generating $countGen new captchas.." );
		$captchaTime = -microtime( true );
		$result = Shell::command( [] )
			->params( $cmd )
			->limits( [ 'time' => 0 ] )
			->disableSandbox()
			->execute();
		if ( $result->getExitCode() !== 0 ) {
			$this->output( " Failed.\n" );
			wfRecursiveRemoveDir( $tmpDir );

			$this->fatalError(
				"An error occurred when running $captchaScript:\n{$result->getStderr()}\n",
				1
			);
		}

		$captchaTime += microtime( true );
		$this->output( " Done.\n" );

		$this->output(
			sprintf(
				"\nGenerated %d captchas in %.1f seconds\n",
				$countGen,
				$captchaTime
			)
		);

		$filesToDelete = [];
		if ( $deleteOldCaptchas ) {
			$this->output( "Getting a list of old captchas to delete..." );
			$path = $backend->getRootStoragePath() . '/' . $instance->getStorageDir();
			foreach ( $backend->getFileList( [ 'dir' => $path ] ) as $file ) {
				$filesToDelete[] = [
					'op' => 'delete',
					'src' => $path . '/' . $file,
				];
			}
			$this->output( " Done.\n" );
		}

		$this->output( "Copying the new captchas to storage..." );

		$storeTime = -microtime( true );
		$iter = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$tmpDir,
				FilesystemIterator::SKIP_DOTS
			),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		$captchasGenerated = iterator_count( $iter );
		$filesToStore = [];
		/**
		 * @var $fileInfo SplFileInfo
		 */
		foreach ( $iter as $fileInfo ) {
			if ( !$fileInfo->isFile() ) {
				continue;
			}
			[ $salt, $hash ] = $instance->hashFromImageName( $fileInfo->getBasename() );
			$dest = $instance->imagePath( $salt, $hash );
			$backend->prepare( [ 'dir' => dirname( $dest ) ] );
			$filesToStore[] = [
				'op' => 'store',
				'src' => $fileInfo->getPathname(),
				'dst' => $dest,
			];
		}

		$ret = $backend->doQuickOperations( $filesToStore );

		$storeTime += microtime( true );

		$storeSucceeded = true;
		if ( $ret->isOK() ) {
			$this->output( " Done.\n" );
			$this->output(
				sprintf(
					"\nCopied %d captchas to storage in %.1f seconds\n",
					$ret->successCount,
					$storeTime
				)
			);
			if ( !$ret->isGood() ) {
				$this->output(
					"Non fatal errors:\n" .
					Status::wrap( $ret )->getWikiText( false, false, 'en' ) .
					"\n"
				);
			}
			if ( $ret->failCount ) {
				$storeSucceeded = false;
				$this->error( sprintf( "\nFailed to copy %d captchas\n", $ret->failCount ) );
			}
			if ( $ret->successCount + $ret->failCount !== $captchasGenerated ) {
				$storeSucceeded = false;
				$this->error(
					sprintf( "Internal error: captchasGenerated: %d, successCount: %d, failCount: %d\n",
						$captchasGenerated, $ret->successCount, $ret->failCount
					)
				);
			}
		} else {
			$storeSucceeded = false;
			$this->output( "Errored.\n" );
			$this->error(
				Status::wrap( $ret )->getWikiText( false, false, 'en' ) .
				"\n"
			);
		}

		if ( $storeSucceeded && $deleteOldCaptchas ) {
			$numOriginalFiles = count( $filesToDelete );
			$this->output( "Deleting {$numOriginalFiles} old captchas...\n" );
			$deleteTime = -microtime( true );
			$ret = $backend->doQuickOperations( $filesToDelete );

			$deleteTime += microtime( true );
			if ( $ret->isOK() ) {
				$this->output( "Done.\n" );
				$this->output(
					sprintf(
						"\nDeleted %d old captchas in %.1f seconds\n",
						$numOriginalFiles,
						$deleteTime
					)
				);
				if ( !$ret->isGood() ) {
					$this->output(
						"Non fatal errors:\n" .
						Status::wrap( $ret )->getWikiText( false, false, 'en' ) .
						"\n"
					);
				}
			} else {
				$this->output( "Errored.\n" );
				$this->error(
					Status::wrap( $ret )->getWikiText( false, false, 'en' ) .
					"\n"
				);
			}

		}
		$this->output( "Removing temporary files..." );
		wfRecursiveRemoveDir( $tmpDir );
		$this->output( " Done.\n" );

		$totalTime += microtime( true );
		$this->output(
			sprintf(
				"\nWhole captchas generation process took %.1f seconds\n",
				$totalTime
			)
		);
	}
}

$maintClass = GenerateFancyCaptchas::class;
require_once RUN_MAINTENANCE_IF_MAIN;
