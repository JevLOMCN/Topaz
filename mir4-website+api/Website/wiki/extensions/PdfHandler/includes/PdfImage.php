<?php
/**
 *
 * Copyright © 2007 Xarax <jodeldi@gmx.de>
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
 */

namespace MediaWiki\Extension\PdfHandler;

use BitmapMetadataHandler;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use UtfNormal\Validator;
use Wikimedia\XMPReader\Reader as XMPReader;

/**
 * inspired by djvuimage from Brion Vibber
 * modified and written by xarax
 */

class PdfImage {

	/**
	 * @var string
	 */
	private $mFilename;

	public const ITEMS_FOR_PAGE_SIZE = [ 'Pages', 'pages', 'Page size', 'Page rot' ];

	/**
	 * @param string $filename
	 */
	public function __construct( $filename ) {
		$this->mFilename = $filename;
	}

	/**
	 * @return bool
	 */
	public function isValid() {
		return true;
	}

	/**
	 * @param array $data
	 * @param int $page
	 * @return array|bool
	 */
	public static function getPageSize( $data, $page ) {
		global $wgPdfHandlerDpi;

		if ( isset( $data['pages'][$page]['Page size'] ) ) {
			$pageSize = $data['pages'][$page]['Page size'];
		} elseif ( isset( $data['Page size'] ) ) {
			$pageSize = $data['Page size'];
		} else {
			$pageSize = false;
		}

		if ( $pageSize ) {
			if ( isset( $data['pages'][$page]['Page rot'] ) ) {
				$pageRotation = $data['pages'][$page]['Page rot'];
			} elseif ( isset( $data['Page rot'] ) ) {
				$pageRotation = $data['Page rot'];
			} else {
				$pageRotation = 0;
			}
			$size = explode( 'x', $pageSize, 2 );

			$width  = intval( (int)trim( $size[0] ) / 72 * $wgPdfHandlerDpi );
			$height = explode( ' ', trim( $size[1] ), 2 );
			$height = intval( (int)trim( $height[0] ) / 72 * $wgPdfHandlerDpi );
			if ( ( $pageRotation / 90 ) & 1 ) {
				// Swap width and height for landscape pages
				$temp = $width;
				$width = $height;
				$height = $temp;
			}

			return [
				'width' => $width,
				'height' => $height
			];
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function retrieveMetaData(): array {
		global $wgPdfInfo, $wgPdftoText, $wgShellboxShell;

		$command = MediaWikiServices::getInstance()->getShellCommandFactory()
			->createBoxed( 'pdfhandler' )
			->disableNetwork()
			->firejailDefaultSeccomp()
			->routeName( 'pdfhandler-metadata' );

		$result = $command
			->params( $wgShellboxShell, 'scripts/retrieveMetaData.sh' )
			->inputFileFromFile(
				'scripts/retrieveMetaData.sh',
				__DIR__ . '/../scripts/retrieveMetaData.sh' )
			->inputFileFromFile( 'file.pdf', $this->mFilename )
			->outputFileToString( 'meta' )
			->outputFileToString( 'pages' )
			->outputFileToString( 'text' )
			->outputFileToString( 'text_exit_code' )
			->environment( [
				'PDFHANDLER_INFO' => $wgPdfInfo,
				'PDFHANDLER_TOTEXT' => $wgPdftoText,
			] )
			->execute();

		// Record in statsd
		MediaWikiServices::getInstance()->getStatsdDataFactory()
			->increment( 'pdfhandler.shell.retrieve_meta_data' );

		// Metadata retrieval is allowed to fail, but we'd like to know why
		if ( $result->getExitCode() != 0 ) {
			wfDebug( __METHOD__ . ': retrieveMetaData.sh' .
			"\n\nExitcode: " . $result->getExitCode() . "\n\n"
			. $result->getStderr() );
		}

		$resultMeta = $result->getFileContents( 'meta' );
		$resultPages = $result->getFileContents( 'pages' );
		if ( $resultMeta !== null || $resultPages !== null ) {
			$data = $this->convertDumpToArray(
				$resultMeta ?? '',
				$resultPages ?? ''
			);
		} else {
			$data = [];
		}

		// Read text layer
		$retval = $result->wasReceived( 'text_exit_code' )
			? (int)trim( $result->getFileContents( 'text_exit_code' ) )
			: 1;
		$txt = $result->getFileContents( 'text' );
		if ( $retval == 0 && strlen( $txt ) ) {
			$txt = str_replace( "\r\n", "\n", $txt );
			$pages = explode( "\f", $txt );
			foreach ( $pages as $page => $pageText ) {
				// Get rid of invalid UTF-8, strip control characters
				// Note we need to do this per page, as \f page feed would be stripped.
				$pages[$page] = Validator::cleanUp( $pageText );
			}
			$data['text'] = $pages;
		}

		return $data;
	}

	/**
	 * @param string $metaDump
	 * @param string $infoDump
	 * @return array
	 */
	protected function convertDumpToArray( $metaDump, $infoDump ): array {
		if ( strval( $infoDump ) === '' ) {
			return [];
		}

		$lines = explode( "\n", $infoDump );
		$data = [];

		// Metadata is always the last item, and spans multiple lines.
		$inMetadata = false;

		// Basically this loop will go through each line, splitting key value
		// pairs on the colon, until it gets to a "Metadata:\n" at which point
		// it will gather all remaining lines into the xmp key.
		foreach ( $lines as $line ) {
			if ( $inMetadata ) {
				// Handle XMP differently due to difference in line break
				$data['xmp'] .= "\n$line";
				continue;
			}
			$bits = explode( ':', $line, 2 );
			if ( count( $bits ) > 1 ) {
				$key = trim( $bits[0] );
				if ( $key === 'Metadata' ) {
					$inMetadata = true;
					$data['xmp'] = '';
					continue;
				}
				$value = trim( $bits[1] );
				$matches = [];
				// "Page xx rot" will be in poppler 0.20's pdfinfo output
				// See https://bugs.freedesktop.org/show_bug.cgi?id=41867
				if ( preg_match( '/^Page +(\d+) (size|rot)$/', $key, $matches ) ) {
					$data['pages'][$matches[1]][$matches[2] == 'size' ? 'Page size' : 'Page rot'] = $value;
				} else {
					$data[$key] = $value;
				}
			}
		}
		$metaDump = trim( $metaDump );
		if ( $metaDump !== '' ) {
			$data['xmp'] = $metaDump;
		}

		return $this->postProcessDump( $data );
	}

	/**
	 * Postprocess the metadata (convert xmp into useful form, etc)
	 *
	 * This is used to generate the metadata table at the bottom
	 * of the image description page.
	 *
	 * @param array $data metadata
	 * @return array post-processed metadata
	 */
	protected function postProcessDump( array $data ) {
		$meta = new BitmapMetadataHandler();
		$items = [];
		foreach ( $data as $key => $val ) {
			switch ( $key ) {
				case 'Title':
					$items['ObjectName'] = $val;
					break;
				case 'Subject':
					$items['ImageDescription'] = $val;
					break;
				case 'Keywords':
					// Sometimes we have empty keywords. This seems
					// to be a product of how pdfinfo deals with keywords
					// with spaces in them. Filter such empty keywords
					$keyList = array_filter( explode( ' ', $val ) );
					if ( count( $keyList ) > 0 ) {
						$items['Keywords'] = $keyList;
					}
					break;
				case 'Author':
					$items['Artist'] = $val;
					break;
				case 'Creator':
					// Program used to create file.
					// Different from program used to convert to pdf.
					$items['Software'] = $val;
					break;
				case 'Producer':
					// Conversion program
					$items['pdf-Producer'] = $val;
					break;
				case 'ModTime':
					$timestamp = wfTimestamp( TS_EXIF, $val );
					if ( $timestamp ) {
						// 'if' is just paranoia
						$items['DateTime'] = $timestamp;
					}
					break;
				case 'CreationTime':
					$timestamp = wfTimestamp( TS_EXIF, $val );
					if ( $timestamp ) {
						$items['DateTimeDigitized'] = $timestamp;
					}
					break;
				// These last two (version and encryption) I was unsure
				// if we should include in the table, since they aren't
				// all that useful to editors. I leaned on the side
				// of including. However not including if file
				// is optimized/linearized since that is really useless
				// to an editor.
				case 'PDF version':
					$items['pdf-Version'] = $val;
					break;
				case 'Encrypted':
					$items['pdf-Encrypted'] = $val;
					break;
				// Note 'pages' and 'Pages' are different keys (!)
				case 'pages':
					// A pdf document can have multiple sized pages in it.
					// (However 95% of the time, all pages are the same size)
					// get a list of all the unique page sizes in document.
					// This doesn't do anything with rotation as of yet,
					// mostly because I am unsure of what a good way to
					// present that information to the user would be.
					$pageSizes = [];
					foreach ( $val as $page ) {
						if ( isset( $page['Page size'] ) ) {
							$pageSizes[$page['Page size']] = true;
						}
					}

					$pageSizeArray = array_keys( $pageSizes );
					if ( count( $pageSizeArray ) > 0 ) {
						$items['pdf-PageSize'] = $pageSizeArray;
					}
					break;
			}

		}
		$meta->addMetadata( $items, 'native' );

		if ( isset( $data['xmp'] ) && XMPReader::isSupported() ) {
			// @todo: This only handles generic xmp properties. Would be improved
			// by handling pdf xmp properties (pdf and pdfx) via a hook.
			$xmp = new XMPReader( LoggerFactory::getInstance( 'XMP' ) );
			$xmp->parse( $data['xmp'] );
			$xmpRes = $xmp->getResults();
			foreach ( $xmpRes as $type => $xmpSection ) {
				$meta->addMetadata( $xmpSection, $type );
			}
		}
		unset( $data['xmp'] );
		$data['mergedMetadata'] = $meta->getMetadataArray();
		return $data;
	}
}
