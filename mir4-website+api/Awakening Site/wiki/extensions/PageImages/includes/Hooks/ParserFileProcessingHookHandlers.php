<?php

namespace PageImages\Hooks;

use DerivativeContext;
use Exception;
use File;
use FormatMetadata;
use MediaWiki\Hook\ParserAfterTidyHook;
use MediaWiki\Hook\ParserModifyImageHTMLHook;
use MediaWiki\Hook\ParserTestGlobalsHook;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Linker\LinksMigration;
use MediaWiki\Page\PageReference;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\TitleFactory;
use PageImages\PageImageCandidate;
use PageImages\PageImages;
use Parser;
use RepoGroup;
use RuntimeException;
use WANObjectCache;
use Wikimedia\Rdbms\IConnectionProvider;

/**
 * Handlers for parser hooks.
 *
 * The ParserModifyImageHTML hook handler collects candidate images, and marks
 * them with a temporary HTML comment in the parser output.
 *
 * The ParserAfterTidy hook handler processes the candidate images, identifying
 * the best image and the best free image. If $wgPageImagesLeadSectionOnly is
 * set, images following the first section header are discarded. It removes the
 * temporary comments and saves the resulting best images to page_props.
 *
 * The various query interfaces will retrieve the lead image from page_props.
 *
 * @license WTFPL
 * @author Max Semenik
 * @author Thiemo Kreuz
 */
class ParserFileProcessingHookHandlers implements
	ParserAfterTidyHook,
	ParserModifyImageHTMLHook,
	ParserTestGlobalsHook
{
	private const CANDIDATE_REGEX = '/<!--MW-PAGEIMAGES-CANDIDATE-([0-9]+)-->/';

	private RepoGroup $repoGroup;
	private WANObjectCache $mainWANObjectCache;
	private HttpRequestFactory $httpRequestFactory;
	private IConnectionProvider $connectionProvider;
	private TitleFactory $titleFactory;
	private LinksMigration $linksMigration;

	public function __construct(
		RepoGroup $repoGroup,
		WANObjectCache $mainWANObjectCache,
		HttpRequestFactory $httpRequestFactory,
		IConnectionProvider $connectionProvider,
		TitleFactory $titleFactory,
		LinksMigration $linksMigration
	) {
		$this->repoGroup = $repoGroup;
		$this->mainWANObjectCache = $mainWANObjectCache;
		$this->httpRequestFactory = $httpRequestFactory;
		$this->connectionProvider = $connectionProvider;
		$this->titleFactory = $titleFactory;
		$this->linksMigration = $linksMigration;
	}

	/**
	 * @param array &$globals
	 */
	public function onParserTestGlobals( &$globals ) {
		$globals += [
			'wgPageImagesScores' => [
				'width' => [
					200 => 10,
					1000 => 20
				],
				'position' => [],
				'ratio' => [],
				'galleryImageWidth' => []
			],
			'wgPageImagesLeadSectionOnly' => true
		];
	}

	/**
	 * ParserModifyImageHTML hook. Save candidate images, and mark them with a
	 * comment so that we can later tell if they were in the lead section.
	 *
	 * @param Parser $parser
	 * @param File $file
	 * @param array $params
	 * @param string &$html
	 */
	public function onParserModifyImageHTML(
		Parser $parser,
		File $file,
		array $params,
		string &$html
	): void {
		$page = $parser->getPage();
		if ( !$page || !$this->processThisTitle( $page ) ) {
			return;
		}

		$this->calcWidth( $params, $file );

		$index = $this->addPageImageCandidateToParserOutput(
			PageImageCandidate::newFromFileAndParams( $file, $params ),
			$parser->getOutput()
		);
		$html .= "<!--MW-PAGEIMAGES-CANDIDATE-$index-->";
	}

	/**
	 * ParserAfterTidy hook handler. Remove candidate images which were not in
	 * the lead section.
	 *
	 * @param Parser $parser
	 * @param string &$text
	 */
	public function onParserAfterTidy( $parser, &$text ) {
		global $wgPageImagesLeadSectionOnly;
		$parserOutput = $parser->getOutput();
		$allImages = $parserOutput->getExtensionData( 'pageImages' );
		if ( !$allImages ) {
			return;
		}

		// Find and remove our special comments
		$images = [];
		if ( $wgPageImagesLeadSectionOnly ) {
			$leadEndPos = strpos( $text, '<mw:editsection' );
		} else {
			$leadEndPos = false;
		}
		$text = preg_replace_callback(
			self::CANDIDATE_REGEX,
			static function ( $m ) use ( $allImages, &$images, $leadEndPos ) {
				$offset = $m[0][1];
				$id = intval( $m[1][0] );
				$inLead = $leadEndPos === false || $offset < $leadEndPos;
				if ( $inLead && isset( $allImages[$id] ) ) {
					$images[] = PageImageCandidate::newFromArray( $allImages[$id] );
				}
				return '';
			},
			$text, -1, $count, PREG_OFFSET_CAPTURE
		);

		[ $bestImageName, $freeImageName ] = $this->findBestImages( $images );

		if ( $freeImageName ) {
			$parserOutput->setPageProperty( PageImages::getPropName( true ), $freeImageName );
		}

		// Only store the image if it's not free. Free image (if any) has already been stored above.
		if ( $bestImageName && $bestImageName !== $freeImageName ) {
			$parserOutput->setPageProperty( PageImages::getPropName( false ), $bestImageName );
		}

		// Strip comments from indicators (T298930)
		foreach ( $parserOutput->getIndicators() as $id => $value ) {
			$stripped = preg_replace( self::CANDIDATE_REGEX, '', $value );
			if ( $stripped !== $value ) {
				$parserOutput->setIndicator( $id, $stripped );
			}
		}
		// We may have comments in TOC data - Parser::cleanupTocLine strips them for us.
	}

	/**
	 * Find the best images out of an array of candidates
	 *
	 * @param PageImageCandidate[] $images
	 * @return array{string|false,string|false} The best image, and the best free image
	 */
	private function findBestImages( array $images ) {
		if ( !$images ) {
			return [ false, false ];
		}

		// Determine the image scores

		$scores = [];
		$counter = 0;

		foreach ( $images as $image ) {
			$score = $this->getScore( $image, $counter++ );
			$fileName = $image->getFileName();
			$scores[$fileName] = max( $scores[$fileName] ?? -1, $score );
		}

		$bestImageName = false;
		$freeImageName = false;

		foreach ( $scores as $name => $score ) {
			if ( $score > 0 ) {
				if ( !$bestImageName || $score > $scores[$bestImageName] ) {
					$bestImageName = $name;
				}
				if ( ( !$freeImageName || $score > $scores[$freeImageName] ) && $this->isImageFree( $name ) ) {
					$freeImageName = $name;
				}
			}
		}
		return [ $bestImageName, $freeImageName ];
	}

	/**
	 * Adds $image to $parserOutput extension data.
	 *
	 * @param PageImageCandidate $image
	 * @param ParserOutput $parserOutput
	 * @return int
	 */
	private function addPageImageCandidateToParserOutput(
		PageImageCandidate $image,
		ParserOutput $parserOutput
	) {
		$images = $parserOutput->getExtensionData( 'pageImages' ) ?: [];
		$images[] = $image->jsonSerialize();
		$parserOutput->setExtensionData( 'pageImages', $images );
		return count( $images ) - 1;
	}

	/**
	 * Returns true if data for this title should be saved
	 *
	 * @param PageReference $pageReference
	 *
	 * @return bool
	 */
	private function processThisTitle( PageReference $pageReference ) {
		global $wgPageImagesNamespaces;
		static $flipped = null;
		$flipped ??= array_flip( $wgPageImagesNamespaces );

		return isset( $flipped[$pageReference->getNamespace()] );
	}

	/**
	 * Estimates image size as displayed if not explicitly provided. We don't follow the core size
	 * calculation algorithm precisely because it's not required and editor's intentions are more
	 * important than the precise number.
	 *
	 * @param array[] &$params
	 * @param File $file
	 */
	private function calcWidth( array &$params, File $file ) {
		global $wgThumbLimits, $wgDefaultUserOptions;

		if ( isset( $params['handler']['width'] ) ) {
			return;
		}

		if ( isset( $params['handler']['height'] ) && $file->getHeight() > 0 ) {
			$params['handler']['width'] =
				$file->getWidth() * ( $params['handler']['height'] / $file->getHeight() );
		} elseif ( isset( $params['frame']['thumbnail'] )
			|| isset( $params['frame']['thumb'] )
			|| isset( $params['frame']['frameless'] )
		) {
			$params['handler']['width'] = $wgThumbLimits[$wgDefaultUserOptions['thumbsize']]
				?? 250;
		} else {
			$params['handler']['width'] = $file->getWidth();
		}
	}

	/**
	 * Returns score for image, the more the better, if it is less than zero,
	 * the image shouldn't be used for anything
	 *
	 * @param PageImageCandidate $image Associative array describing an image
	 * @param int $position Image order on page
	 *
	 * @return float
	 */
	protected function getScore( PageImageCandidate $image, $position ) {
		global $wgPageImagesScores;

		// Exclude images with class="notpageimage"
		if ( preg_match( '/(?:^|\s)notpageimage(?=\s|$)/', $image->getFrameClass() ) ) {
			return -1000;
		}

		if ( $image->getHandlerWidth() ) {
			// Standalone image
			$score = $this->scoreFromTable( $image->getHandlerWidth(), $wgPageImagesScores['width'] );
		} else {
			// From gallery
			$score = $this->scoreFromTable( $image->getFullWidth(), $wgPageImagesScores['galleryImageWidth'] );
		}

		if ( isset( $wgPageImagesScores['position'][$position] ) ) {
			$score += $wgPageImagesScores['position'][$position];
		}

		$ratio = intval( $this->getRatio( $image ) * 10 );
		$score += $this->scoreFromTable( $ratio, $wgPageImagesScores['ratio'] );

		$denylist = $this->getDenylist();
		if ( isset( $denylist[$image->getFileName()] ) ) {
			$score = -1000;
		}

		return $score;
	}

	/**
	 * Returns score based on table of ranges
	 *
	 * @param int $value The number that the various bounds are compared against
	 * to calculate the score
	 * @param float[] $scores Table of scores for different ranges of $value
	 *
	 * @return float
	 */
	protected function scoreFromTable( $value, array $scores ) {
		$lastScore = 0;

		// The loop stops at the *first* match, and therefore *requires* the input array keys to be
		// in increasing order.
		ksort( $scores, SORT_NUMERIC );
		foreach ( $scores as $upperBoundary => $score ) {
			$lastScore = $score;

			if ( $value <= $upperBoundary ) {
				break;
			}
		}

		if ( !is_numeric( $lastScore ) ) {
			wfLogWarning( 'The PageImagesScores setting must only contain numeric values!' );
		}

		return (float)$lastScore;
	}

	/**
	 * Check whether image's copyright allows it to be used freely.
	 *
	 * @param string $fileName Name of the image file
	 * @return bool
	 */
	protected function isImageFree( $fileName ) {
		$file = $this->repoGroup->findFile( $fileName );
		if ( $file ) {
			// Process copyright metadata from CommonsMetadata, if present.
			// Image is considered free if the value is '0' or unset.
			return empty( $this->fetchFileMetadata( $file )['NonFree']['value'] );
		}
		return true;
	}

	/**
	 * Fetch file metadata
	 *
	 * @param File $file File to fetch metadata from
	 * @return array
	 */
	protected function fetchFileMetadata( $file ) {
		$format = new FormatMetadata;
		$context = new DerivativeContext( $format->getContext() );
		// we don't care about the language, and specifying singleLanguage is slightly faster
		$format->setSingleLanguage( true );
		// we don't care about the language, so avoid splitting the cache by selecting English
		$context->setLanguage( 'en' );
		$format->setContext( $context );
		return $format->fetchExtendedMetadata( $file );
	}

	/**
	 * Returns width/height ratio of an image as displayed or 0 if not available
	 *
	 * @param PageImageCandidate $image
	 *
	 * @return float|int
	 */
	protected function getRatio( PageImageCandidate $image ) {
		$width = $image->getFullWidth();
		$height = $image->getFullHeight();
		return $width > 0 && $height > 0 ? $width / $height : 0;
	}

	/**
	 * Returns a list of images denylisted from influencing this extension's output
	 *
	 * @return int[] Flipped associative array in format "image BDB key" => int
	 * @throws Exception
	 */
	protected function getDenylist() {
		global $wgPageImagesDenylistExpiry;

		return $this->mainWANObjectCache->getWithSetCallback(
			$this->mainWANObjectCache->makeKey( 'pageimages-denylist' ),
			$wgPageImagesDenylistExpiry,
			function () {
				global $wgPageImagesDenylist;

				$list = [];
				foreach ( $wgPageImagesDenylist as $source ) {
					switch ( $source['type'] ) {
						case 'db':
							$list = array_merge(
								$list,
								$this->getDbDenylist( $source['db'], $source['page'] )
							);
							break;
						case 'url':
							$list = array_merge(
								$list,
								$this->getUrlDenylist( $source['url'] )
							);
							break;
						default:
							throw new RuntimeException(
								"unrecognized image denylist type '{$source['type']}'"
							);
					}
				}

				return array_flip( $list );
			}
		);
	}

	/**
	 * Returns list of images linked by the given denylist page
	 *
	 * @param string|false $dbName Database name or false for current database
	 * @param string $page
	 *
	 * @return string[]
	 */
	private function getDbDenylist( $dbName, $page ) {
		$title = $this->titleFactory->newFromText( $page );
		if ( !$title || !$title->canExist() ) {
			return [];
		}

		$dbr = $this->connectionProvider->getReplicaDatabase( $dbName );
		$id = $dbr->newSelectQueryBuilder()
			->select( 'page_id' )
			->from( 'page' )
			->where( [ 'page_namespace' => $title->getNamespace(), 'page_title' => $title->getDBkey() ] )
			->caller( __METHOD__ )->fetchField();
		if ( !$id ) {
			return [];
		}
		[ $blNamespace, $blTitle ] = $this->linksMigration->getTitleFields( 'pagelinks' );
		$queryInfo = $this->linksMigration->getQueryInfo( 'pagelinks' );

		return $dbr->newSelectQueryBuilder()
			->select( $blTitle )
			->tables( $queryInfo['tables'] )
			->joinConds( $queryInfo['joins'] )
			->where( [ 'pl_from' => (int)$id, $blNamespace => NS_FILE ] )
			->caller( __METHOD__ )->fetchFieldValues();
	}

	/**
	 * Returns list of images on given remote denylist page.
	 * Not quite 100% bulletproof due to localised namespaces and so on.
	 * Though if you beat people if they add bad entries to the list... :)
	 *
	 * @param string $url
	 *
	 * @return string[]
	 */
	private function getUrlDenylist( $url ) {
		global $wgFileExtensions;

		$list = [];
		$text = $this->httpRequestFactory->get( $url, [ 'timeout' => 3 ], __METHOD__ );
		$regex = '/\[\[:([^|\#]*?\.(?:' . implode( '|', $wgFileExtensions ) . '))/i';

		if ( $text && preg_match_all( $regex, $text, $matches ) ) {
			foreach ( $matches[1] as $s ) {
				$t = $this->titleFactory->makeTitleSafe( NS_FILE, $s );

				if ( $t ) {
					$list[] = $t->getDBkey();
				}
			}
		}

		return $list;
	}

}
