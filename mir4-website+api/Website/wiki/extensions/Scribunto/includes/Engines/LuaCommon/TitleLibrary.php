<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaCommon;

use Content;
use LogicException;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutputFlags;
use MediaWiki\Revision\RevisionAccessException;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;

class TitleLibrary extends LibraryBase {
	// Note these caches are naturally limited to
	// $wgExpensiveParserFunctionLimit + 1 actual Title objects because any
	// addition besides the one for the current page calls
	// incrementExpensiveFunctionCount()
	/** @var Title[] */
	private $titleCache = [];
	/** @var (Title|null)[] */
	private $idCache = [ 0 => null ];

	public function register() {
		$lib = [
			'newTitle' => [ $this, 'newTitle' ],
			'makeTitle' => [ $this, 'makeTitle' ],
			'getExpensiveData' => [ $this, 'getExpensiveData' ],
			'getUrl' => [ $this, 'getUrl' ],
			'getContent' => [ $this, 'getContent' ],
			'getFileInfo' => [ $this, 'getFileInfo' ],
			'protectionLevels' => [ $this, 'protectionLevels' ],
			'cascadingProtection' => [ $this, 'cascadingProtection' ],
			'redirectTarget' => [ $this, 'redirectTarget' ],
			'recordVaryFlag' => [ $this, 'recordVaryFlag' ],
			'getPageLangCode' => [ $this, 'getPageLangCode' ],
		];
		$title = $this->getTitle();
		return $this->getEngine()->registerInterface( 'mw.title.lua', $lib, [
			'thisTitle' => $title ? $this->getInexpensiveTitleData( $title ) : null,
			'NS_MEDIA' => NS_MEDIA,
		] );
	}

	/**
	 * Check a namespace parameter
	 * @param string $name Function name (for errors)
	 * @param int $argIdx Argument index (for errors)
	 * @param mixed &$arg Argument
	 * @param int|null $default Default value, if $arg is null
	 */
	private function checkNamespace( $name, $argIdx, &$arg, $default = null ) {
		if ( $arg === null && $default !== null ) {
			$arg = $default;
		} elseif ( is_numeric( $arg ) ) {
			$arg = (int)$arg;
			if ( !MediaWikiServices::getInstance()->getNamespaceInfo()->exists( $arg ) ) {
				throw new LuaError(
					"bad argument #$argIdx to '$name' (unrecognized namespace number '$arg')"
				);
			}
		} elseif ( is_string( $arg ) ) {
			$ns = MediaWikiServices::getInstance()->getContentLanguage()->getNsIndex( $arg );
			if ( $ns === false ) {
				throw new LuaError(
					"bad argument #$argIdx to '$name' (unrecognized namespace name '$arg')"
				);
			}
			$arg = $ns;
		} else {
			$this->checkType( $name, $argIdx, $arg, 'namespace number or name' );
		}
	}

	/**
	 * Extract inexpensive information from a Title object for return to Lua
	 *
	 * @param Title $title Title to return
	 * @return array Lua data
	 */
	private function getInexpensiveTitleData( Title $title ) {
		$ns = $title->getNamespace();
		$ret = [
			'isCurrentTitle' => (bool)$title->equals( $this->getTitle() ),
			'isLocal' => (bool)$title->isLocal(),
			'interwiki' => $title->getInterwiki(),
			'namespace' => $ns,
			'nsText' => $title->getNsText(),
			'text' => $title->getText(),
			'fragment' => $title->getFragment(),
			'thePartialUrl' => $title->getPartialURL(),
		];
		if ( $ns === NS_SPECIAL ) {
			// Core doesn't currently record special page links, but it may in the future.
			if ( $this->getParser() && !$title->equals( $this->getTitle() ) ) {
				$this->getParser()->getOutput()->addLink( $title );
			}
			$ret['exists'] = MediaWikiServices::getInstance()
				->getSpecialPageFactory()->exists( $title->getDBkey() );
		}
		if ( $ns !== NS_FILE && $ns !== NS_MEDIA ) {
			$ret['file'] = false;
		}
		return $ret;
	}

	/**
	 * Extract expensive information from a Title object for return to Lua
	 *
	 * This records a link to this title in the current ParserOutput and caches the
	 * title for repeated lookups. It may call incrementExpensiveFunctionCount() if
	 * the title is not already cached.
	 *
	 * @internal
	 * @param string $text Title text
	 * @return array Lua data
	 */
	public function getExpensiveData( $text ) {
		$this->checkType( 'getExpensiveData', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return [ null ];
		}
		$dbKey = $title->getPrefixedDBkey();
		if ( isset( $this->titleCache[$dbKey] ) ) {
			// It was already cached, so we already did the expensive work and added a link
			$title = $this->titleCache[$dbKey];
		} else {
			if ( !$title->equals( $this->getTitle() ) ) {
				$this->incrementExpensiveFunctionCount();

				// Record a link
				if ( $this->getParser() ) {
					$this->getParser()->getOutput()->addLink( $title );
				}
			}

			// Cache it
			$this->titleCache[$dbKey] = $title;
			if ( $title->getArticleID() > 0 ) {
				$this->idCache[$title->getArticleID()] = $title;
			}
		}

		$ret = [
			'isRedirect' => (bool)$title->isRedirect(),
			'id' => $title->getArticleID(),
			'contentModel' => $title->getContentModel(),
		];
		if ( $title->getNamespace() === NS_SPECIAL ) {
			$ret['exists'] = MediaWikiServices::getInstance()
				->getSpecialPageFactory()->exists( $title->getDBkey() );
		} else {
			// bug 70495: don't just check whether the ID != 0
			$ret['exists'] = $title->exists();
		}
		return [ $ret ];
	}

	/**
	 * Handler for title.new
	 *
	 * Calls Title::newFromID or Title::newFromTitle as appropriate for the
	 * arguments.
	 *
	 * @internal
	 * @param string|int $text_or_id Title or page_id to fetch
	 * @param string|int|null $defaultNamespace Namespace name or number to use if
	 *  $text_or_id doesn't override
	 * @return array Lua data
	 */
	public function newTitle( $text_or_id, $defaultNamespace = null ) {
		$type = $this->getLuaType( $text_or_id );
		if ( $type === 'number' ) {
			if ( array_key_exists( $text_or_id, $this->idCache ) ) {
				$title = $this->idCache[$text_or_id];
			} else {
				$this->incrementExpensiveFunctionCount();
				$title = Title::newFromID( $text_or_id );
				$this->idCache[$text_or_id] = $title;

				// Record a link
				if ( $title && $this->getParser() && !$title->equals( $this->getTitle() ) ) {
					$this->getParser()->getOutput()->addLink( $title );
				}
			}
			if ( $title ) {
				$this->titleCache[$title->getPrefixedDBkey()] = $title;
			} else {
				return [ null ];
			}
		} elseif ( $type === 'string' ) {
			$this->checkNamespace( 'title.new', 2, $defaultNamespace, NS_MAIN );

			// Note this just fills in the given fields, it doesn't fetch from
			// the page table.
			$title = Title::newFromText( $text_or_id, $defaultNamespace );
			if ( !$title ) {
				return [ null ];
			}
		} else {
			$this->checkType( 'title.new', 1, $text_or_id, 'number or string' );
			throw new LogicException( 'checkType above should have failed' );
		}

		return [ $this->getInexpensiveTitleData( $title ) ];
	}

	/**
	 * Handler for title.makeTitle
	 *
	 * Calls Title::makeTitleSafe.
	 *
	 * @internal
	 * @param string|int $ns Namespace
	 * @param string $text Title text
	 * @param string|null $fragment URI fragment
	 * @param string|null $interwiki Interwiki code
	 * @return array Lua data
	 */
	public function makeTitle( $ns, $text, $fragment = null, $interwiki = null ) {
		$this->checkNamespace( 'makeTitle', 1, $ns );
		$this->checkType( 'makeTitle', 2, $text, 'string' );
		$this->checkTypeOptional( 'makeTitle', 3, $fragment, 'string', '' );
		$this->checkTypeOptional( 'makeTitle', 4, $interwiki, 'string', '' );

		// Note this just fills in the given fields, it doesn't fetch from the
		// page table.
		$title = Title::makeTitleSafe( $ns, $text, $fragment, $interwiki );
		if ( !$title ) {
			return [ null ];
		}

		return [ $this->getInexpensiveTitleData( $title ) ];
	}

	/**
	 * Get a URL referring to this title
	 * @internal
	 * @param string $text Title text.
	 * @param string $which 'fullUrl', 'localUrl', or 'canonicalUrl'
	 * @param string|array|null $query Query string or query string data.
	 * @param string|null $proto 'http', 'https', 'relative', or 'canonical'
	 * @return array
	 */
	public function getUrl( $text, $which, $query = null, $proto = null ) {
		static $protoMap = [
			'http' => PROTO_HTTP,
			'https' => PROTO_HTTPS,
			'relative' => PROTO_RELATIVE,
			'canonical' => PROTO_CANONICAL,
		];

		$this->checkType( 'getUrl', 1, $text, 'string' );
		$this->checkType( 'getUrl', 2, $which, 'string' );
		if ( !in_array( $which, [ 'fullUrl', 'localUrl', 'canonicalUrl' ], true ) ) {
			$this->checkType( 'getUrl', 2, $which, "'fullUrl', 'localUrl', or 'canonicalUrl'" );
		}

		// May call the following Title methods:
		// getFullUrl, getLocalUrl, getCanonicalUrl
		$func = "get" . ucfirst( $which );

		$args = [ $query, false ];
		if ( !is_string( $query ) && !is_array( $query ) ) {
			$this->checkTypeOptional( $which, 1, $query, 'table or string', '' );
		}
		if ( $which === 'fullUrl' ) {
			$this->checkTypeOptional( $which, 2, $proto, 'string', 'relative' );
			if ( !isset( $protoMap[$proto] ) ) {
				$this->checkType( $which, 2, $proto, "'http', 'https', 'relative', or 'canonical'" );
			}
			$args[] = $protoMap[$proto];
		}

		$title = Title::newFromText( $text );
		if ( !$title ) {
			return [ null ];
		}
		return [ $title->$func( ...$args ) ];
	}

	/**
	 * Utility to get a Content object from a title
	 *
	 * The title is counted as a transclusion.
	 *
	 * @param string $text Title text
	 * @return Content|null The Content object of the title, null if missing
	 */
	private function getContentInternal( $text ) {
		$title = Title::newFromText( $text );
		if ( !$title || $title->isExternal() ) {
			return null;
		}

		// Record in templatelinks, so edits cause the page to be refreshed
		$this->getParser()->getOutput()->addTemplate(
			$title, $title->getArticleID(), $title->getLatestRevID()
		);

		$rev = $this->getParser()->fetchCurrentRevisionRecordOfTitle( $title );

		if ( $title->equals( $this->getTitle() ) ) {
			$parserOutput = $this->getParser()->getOutput();
			$parserOutput->setOutputFlag( ParserOutputFlags::VARY_REVISION_SHA1 );
			$parserOutput->setRevisionUsedSha1Base36( $rev ? $rev->getSha1() : '' );
			wfDebug( __METHOD__ . ": set vary-revision-sha1 for '$title'" );
		}

		if ( !$rev ) {
			return null;
		}

		try {
			$content = $rev->getContent( SlotRecord::MAIN );
		} catch ( RevisionAccessException $ex ) {
			$logger = LoggerFactory::getInstance( 'Scribunto' );
			$logger->warning(
				__METHOD__ . ': Unable to transclude revision content',
				[ 'exception' => $ex ]
			);
			$content = null;
		}
		return $content;
	}

	/**
	 * Handler for getContent
	 * @internal
	 * @param string $text
	 * @return string[]|null[]
	 */
	public function getContent( $text ) {
		$this->checkType( 'getContent', 1, $text, 'string' );
		$content = $this->getContentInternal( $text );
		return [ $content ? $content->serialize() : null ];
	}

	/**
	 * Handler for getFileInfo
	 * @internal
	 * @param string $text
	 * @return array
	 */
	public function getFileInfo( $text ) {
		$this->checkType( 'getFileInfo', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return [ false ];
		}
		$ns = $title->getNamespace();
		if ( $ns !== NS_FILE && $ns !== NS_MEDIA ) {
			return [ false ];
		}

		$this->incrementExpensiveFunctionCount();
		$file = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $title );
		if ( !$file ) {
			return [ [ 'exists' => false ] ];
		}
		$this->getParser()->getOutput()->addImage(
			$file->getName(), $file->getTimestamp(), $file->getSha1()
		);
		if ( !$file->exists() ) {
			return [ [ 'exists' => false ] ];
		}
		$pageCount = $file->pageCount();
		if ( $pageCount === false ) {
			$pages = null;
		} else {
			$pages = [];
			for ( $i = 1; $i <= $pageCount; ++$i ) {
				$pages[$i] = [
					'width' => $file->getWidth( $i ),
					'height' => $file->getHeight( $i )
				];
			}
		}
		return [ [
			'exists' => true,
			'width' => $file->getWidth(),
			'height' => $file->getHeight(),
			'mimeType' => $file->getMimeType(),
			'length' => $file->getLength(),
			'size' => $file->getSize(),
			'pages' => $pages
		] ];
	}

	/**
	 * Renumber an array for return to Lua
	 * @param array $arr
	 * @return array
	 */
	private static function makeArrayOneBased( $arr ) {
		if ( !$arr ) {
			return $arr;
		}
		return array_combine( range( 1, count( $arr ) ), array_values( $arr ) );
	}

	/**
	 * Handler for protectionLevels
	 * @internal
	 * @param string $text
	 * @return array
	 */
	public function protectionLevels( $text ) {
		$this->checkType( 'protectionLevels', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return [ null ];
		}

		$restrictionStore = MediaWikiServices::getInstance()->getRestrictionStore();

		if ( !$restrictionStore->areRestrictionsLoaded( $title ) ) {
			$this->incrementExpensiveFunctionCount();
		}
		return [ array_map(
			[ self::class, 'makeArrayOneBased' ],
			$restrictionStore->getAllRestrictions( $title )
		) ];
	}

	/**
	 * Handler for cascadingProtection
	 * @internal
	 * @param string $text
	 * @return array
	 */
	public function cascadingProtection( $text ) {
		$this->checkType( 'cascadingProtection', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return [ null ];
		}

		$restrictionStore = MediaWikiServices::getInstance()->getRestrictionStore();
		$titleFormatter = MediaWikiServices::getInstance()->getTitleFormatter();

		if ( !$restrictionStore->areCascadeProtectionSourcesLoaded( $title ) ) {
			$this->incrementExpensiveFunctionCount();
		}

		[ $sources, $restrictions ] = $restrictionStore->getCascadeProtectionSources( $title );

		return [ [
			'sources' => self::makeArrayOneBased( array_map(
				static function ( $t ) use ( $titleFormatter ) {
					return $titleFormatter->getPrefixedText( $t );
				},
				$sources ) ),
			'restrictions' => array_map(
				[ self::class, 'makeArrayOneBased' ],
				$restrictions
			)
		] ];
	}

	/**
	 * Handler for redirectTarget
	 * @internal
	 * @param string $text
	 * @return string[]|null[]
	 */
	public function redirectTarget( $text ) {
		$this->checkType( 'redirectTarget', 1, $text, 'string' );
		$content = $this->getContentInternal( $text );
		$redirTitle = $content ? $content->getRedirectTarget() : null;
		return [ $redirTitle ? $this->getInexpensiveTitleData( $redirTitle ) : null ];
	}

	/**
	 * Record a ParserOutput flag when the current title is accessed
	 * @internal
	 * @param string $text
	 * @param string $flag
	 * @return array
	 */
	public function recordVaryFlag( $text, $flag ) {
		$this->checkType( 'recordVaryFlag', 1, $text, 'string' );
		$this->checkType( 'recordVaryFlag', 2, $flag, 'string' );
		$title = Title::newFromText( $text );
		if ( $title && $title->equals( $this->getTitle() ) ) {
			// XXX note that we don't check this against the values defined
			// in ParserOutputFlags
			$this->getParser()->getOutput()->setOutputFlag( $flag );
		}
		return [];
	}

	/**
	 * Handler for getPageLangCode
	 * @internal
	 * @param string $text Title text.
	 * @return array<?string>
	 */
	public function getPageLangCode( $text ) {
		$title = Title::newFromText( $text );
		if ( $title ) {
			// If the page language is coming from the page record, we've
			// probably accounted for the cost of reading the title from
			// the DB already. However, a PageContentLanguage hook handler
			// might get invoked here, and who knows how much that costs.
			// Be safe and increment here, even though this could over-count.
			$this->incrementExpensiveFunctionCount();
			return [ $title->getPageLanguage()->getCode() ];
		}
		return [ null ];
	}
}
