<?php

declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Config\Api;

use Wikimedia\Parsoid\Config\Api\SiteConfig as ApiSiteConfig;
use Wikimedia\Parsoid\Config\DataAccess as IDataAccess;
use Wikimedia\Parsoid\Config\PageConfig;
use Wikimedia\Parsoid\Config\PageContent;
use Wikimedia\Parsoid\Config\SiteConfig as ISiteConfig;
use Wikimedia\Parsoid\Core\ContentMetadataCollector;
use Wikimedia\Parsoid\Core\LinkTarget;
use Wikimedia\Parsoid\Mocks\MockPageContent;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\Title;

/**
 * DataAccess via MediaWiki's Action API
 *
 * Note this is intended for testing, not performance.
 */
class DataAccess extends IDataAccess {

	/** @var ApiHelper */
	private $api;

	/**
	 * @var bool Should we strip the protocol from returned URLs?
	 * Generally this should be true, since the protocol of the API
	 * request doesn't necessarily match the protocol of article
	 * access; ie, we could be using https to access the API but emit
	 * article content which can be read with http.  But for running
	 * parserTests, we need to include the protocol in order to match
	 * the parserTest configuration in core.
	 */
	private $stripProto;

	/**
	 * @name Caching
	 * @todo Someone should librarize MediaWiki core's MapCacheLRU so we can
	 *  pull it in via composer and use it here.
	 * @{
	 */

	private const MAX_CACHE_LEN = 100;

	/**
	 * @var array
	 */
	private $cache = [];

	private ISiteConfig $siteConfig;

	/**
	 * Get from cache
	 * @param string $key
	 * @return mixed
	 */
	private function getCache( string $key ) {
		if ( isset( $this->cache[$key] ) ) {
			$ret = $this->cache[$key];
			// The LRU cache uses position in the array to indicate recency, so
			// move the accessed key to the end.
			unset( $this->cache[$key] );
			$this->cache[$key] = $ret;
			return $ret;
		}
		return null;
	}

	/**
	 * Set a value into cache
	 * @param string $key
	 * @param mixed $value Not null.
	 */
	private function setCache( string $key, $value ): void {
		if ( isset( $this->cache[$key] ) ) {
			// The LRU cache uses position in the array to indicate recency, so
			// remove the old entry so the new version goes at the end.
			unset( $this->cache[$key] );
		} elseif ( count( $this->cache ) >= self::MAX_CACHE_LEN ) {
			reset( $this->cache );
			$evictKey = key( $this->cache );
			unset( $this->cache[$evictKey] );
		}
		$this->cache[$key] = $value;
	}

	/** @} */

	/**
	 * @param ApiHelper $api
	 * @param ISiteConfig $siteConfig
	 * @param array $opts
	 */
	public function __construct( ApiHelper $api, ISiteConfig $siteConfig, array $opts ) {
		$this->api = $api;
		$this->siteConfig = $siteConfig;
		$this->stripProto = $opts['stripProto'] ?? true;
	}

	/** @inheritDoc */
	public function getPageInfo( $pageConfigOrTitle, array $titles ): array {
		$contextTitle = $pageConfigOrTitle instanceof PageConfig ?
			$pageConfigOrTitle->getLinkTarget() : $pageConfigOrTitle;

		if ( !$titles ) {
			return [];
		}

		$ret = [];
		$pageConfigTitle = $this->toPrefixedText( $contextTitle );
		foreach ( array_chunk( $titles, 50 ) as $batch ) {
			$data = $this->api->makeRequest( [
				'action' => 'query',
				'prop' => 'info',
				'inprop' => 'linkclasses',
				'inlinkcontext' => $pageConfigTitle,
				'titles' => implode( '|', $batch ),
			] )['query'];
			$norm = [];
			if ( isset( $data['normalized'] ) ) {
				foreach ( $data['normalized'] as $n ) {
					$from = $n['from'];
					if ( $n['fromencoded'] ) {
						$from = rawurldecode( $from );
					}
					$norm[$from] = $n['to'];
				}
			}
			$pages = [];
			foreach ( $data['pages'] as $p ) {
				$pages[$p['title']] = $p;
			}
			foreach ( $batch as $title ) {
				$ttitle = $title;
				while ( isset( $norm[$ttitle] ) ) {
					$ttitle = $norm[$ttitle];
				}
				$page = $pages[$ttitle] ?? [];
				$ret[$title] = [
					'pageId' => $page['pageid'] ?? null,
					'revId' => $page['lastrevid'] ?? null,
					'missing' => $page['missing'] ?? false,
					'known' => ( $page['known'] ?? false ),
					'redirect' => $page['redirect'] ?? false,
					'linkclasses' => $page['linkclasses'] ?? [],
					'invalid' => $page['invalid'] ?? false,
				];
				if ( !( $ret[$title]['missing'] || $ret[$title]['invalid'] ) ) {
					$ret[$title]['known'] = true;
				}
			}
		}

		return $ret;
	}

	/** @inheritDoc */
	public function getFileInfo( PageConfig $pageConfig, array $files ): array {
		$pageConfigTitle = $this->toPrefixedText( $pageConfig->getLinkTarget() );
		$sc = $this->siteConfig;
		if ( $sc instanceof ApiSiteConfig && $sc->hasVideoInfo() ) {
			$prefix = "vi";
			$propName = "videoinfo";
		} else {
			$prefix = "ii";
			$propName = "imageinfo";
		}
		$apiArgs2 = [
			'action' => 'query',
			'format' => 'json',
			'formatversion' => 2,
			'rawcontinue' => 1,
			'prop' => $propName,
			"{$prefix}badfilecontexttitle" => $pageConfigTitle,
			"{$prefix}prop" => implode( '|', [
				'mediatype', 'mime', 'size', 'url', 'badfile', 'sha1', 'timestamp'
			] )
		];
		if ( $prefix === 'vi' ) {
			$apiArgs2["viprop"] .= '|derivatives|timedtext';
		}
		$ret = [];
		foreach ( $files as $file ) {
			$apiArgs = $apiArgs2;  // Copy since we modify it
			$name = $file[0];
			$dims = $file[1];

			$imgNS = $sc->namespaceName( $sc->canonicalNamespaceId( 'file' ) );
			$apiArgs['titles'] = "$imgNS:$name";
			$needsWidth = isset( $dims['page'] ) || isset( $dims['lang'] );
			if ( isset( $dims['width'] ) ) {
				$apiArgs["{$prefix}urlwidth"] = $dims['width'];
				if ( $needsWidth ) {
					if ( isset( $dims['page'] ) ) {  // PDF
						$apiArgs["{$prefix}urlparam"] = "page{$dims['page']}-{$dims['width']}px";
					} elseif ( isset( $dims['lang'] ) ) {  // SVG
						$apiArgs["{$prefix}urlparam"] = "lang{$dims['lang']}-{$dims['width']}px";
					}
					$needsWidth = false;
				}
			}
			if ( isset( $dims['height'] ) ) {
				$apiArgs["{$prefix}urlheight"] = $dims['height'];
			}
			if ( isset( $dims['seek'] ) ) {
				$apiArgs["{$prefix}urlparam"] = "seek={$dims['seek']}";
			}

			do {
				$data = $this->api->makeRequest( $apiArgs );
				// Expect exactly 1 row
				$fileinfo = $data['query']['pages'][0][$propName][0];
				// Corner case: if page is set, the core ImageInfo API doesn't
				// respect it *unless* width is set as well.  So repeat the
				// request if necessary.
				if ( isset( $fileinfo['pagecount'] ) && !isset( $dims['page'] ) ) {
					$dims['page'] = 1; # also ensures we won't get here again
					$needsWidth = true;
				}
				if ( $needsWidth && !isset( $fileinfo['filemissing'] ) ) {
					$needsWidth = false; # ensure we won't get here again
					$width = $fileinfo['width'];
					$apiArgs["{$prefix}urlwidth"] = $width;
					if ( isset( $dims['page'] ) ) {  // PDF
						$apiArgs["{$prefix}urlparam"] = "page{$dims['page']}-{$width}px";
					} elseif ( isset( $dims['lang'] ) ) {  // SVG
						$apiArgs["{$prefix}urlparam"] = "lang{$dims['lang']}-{$width}px";
					}
					continue;
				}
				break;
			} while ( true );

			if ( isset( $fileinfo['filemissing'] ) ) {
				$fileinfo = null;
			} else {
				$fileinfo['badFile'] = $data['query']['pages'][0]['badfile'];
				$this->stripProto( $fileinfo, 'url' );
				$this->stripProto( $fileinfo, 'thumburl' );
				$this->stripProto( $fileinfo, 'descriptionurl' );
				$this->stripProto( $fileinfo, 'descriptionshorturl' );
				foreach ( $fileinfo['responsiveUrls'] ?? [] as $density => $url ) {
					$this->stripProto( $fileinfo['responsiveUrls'], (string)$density );
				}
				if ( $prefix === 'vi' ) {
					foreach ( $fileinfo['thumbdata']['derivatives'] ?? [] as $j => $d ) {
						$this->stripProto( $fileinfo['thumbdata']['derivatives'][$j], 'src' );
					}
					foreach ( $fileinfo['thumbdata']['timedtext'] ?? [] as $j => $d ) {
						$this->stripProto( $fileinfo['thumbdata']['timedtext'][$j], 'src' );
					}
				}
			}
			$ret[] = $fileinfo;
		}
		return $ret;
	}

	/**
	 * Convert the given URL into protocol-relative form.
	 *
	 * @param ?array &$obj
	 * @param string $key
	 */
	private function stripProto( ?array &$obj, string $key ): void {
		if ( $obj !== null && !empty( $obj[$key] ) && $this->stripProto ) {
			$obj[$key] = preg_replace( '#^https?://#', '//', $obj[$key] );
		}
	}

	/** @inheritDoc */
	public function doPst( PageConfig $pageConfig, string $wikitext ): string {
		$pageConfigTitle = $this->toPrefixedText( $pageConfig->getLinkTarget() );
		$key = implode( ':', [ 'pst', md5( $pageConfigTitle ), md5( $wikitext ) ] );
		$ret = $this->getCache( $key );
		if ( $ret === null ) {
			$data = $this->api->makeRequest( [
				'action' => 'parse',
				'title' => $pageConfigTitle,
				'text' => $wikitext,
				'contentmodel' => 'wikitext',
				'onlypst' => 1,
			] );
			$ret = $data['parse']['text'];
			$this->setCache( $key, $ret );
		}
		return $ret;
	}

	/**
	 * Transfer the metadata returned in an API result into our
	 * ContentMetadataCollector.
	 * @param array $data
	 * @param ContentMetadataCollector $metadata
	 */
	private function mergeMetadata( array $data, ContentMetadataCollector $metadata ): void {
		foreach ( ( $data['categories'] ?? [] ) as $c ) {
			$metadata->addCategory( $c['category'], $c['sortkey'] );
		}
		$metadata->addModules( $data['modules'] ?? [] );
		$metadata->addModuleStyles( $data['modulestyles'] ?? [] );
		foreach ( ( $data['jsconfigvars'] ?? [] ) as $key => $value ) {
			$strategy = 'write-once';
			if ( is_array( $value ) ) {
				// Strategy value will be exposed by change
				// I974d9ecfb4ca8b22361d25c4c70fc5e55c39d5ed in core.
				$strategy = $value['_mw-strategy'] ?? 'write-once';
				unset( $value['_mw-strategy'] );
			}
			if ( $strategy === 'union' ) {
				foreach ( $value as $item ) {
					$metadata->appendJsConfigVar( $key, $item );
				}
			} else {
				$metadata->setJsConfigVar( $key, $value );
			}
		}
		foreach ( ( $data['externallinks'] ?? [] ) as $url ) {
			$metadata->addExternalLink( $url );
		}
		foreach ( ( $data['properties'] ?? [] ) as $name => $value ) {
			$metadata->setPageProperty( $name, $value );
		}
	}

	/** @inheritDoc */
	public function parseWikitext(
		PageConfig $pageConfig,
		ContentMetadataCollector $metadata,
		string $wikitext
	): string {
		$revid = $pageConfig->getRevisionId();
		$pageConfigTitle = $this->toPrefixedText( $pageConfig->getLinkTarget() );
		$key = implode( ':', [ 'parse', md5( $pageConfigTitle ), md5( $wikitext ), $revid ] );
		$data = $this->getCache( $key );
		if ( $data === null ) {
			$params = [
				'action' => 'parse',
				'title' => $pageConfigTitle,
				'text' => $wikitext,
				'contentmodel' => 'wikitext',
				'prop' => 'text|modules|jsconfigvars|categories|properties|externallinks',
				'disablelimitreport' => 1,
				'wrapoutputclass' => '',
				'showstrategykeys' => 1,
			];
			if ( $revid !== null ) {
				$params['revid'] = $revid;
			}
			$data = $this->api->makeRequest( $params )['parse'];
			$this->setCache( $key, $data );
		}
		$this->mergeMetadata( $data, $metadata );
		return $data['text']; # HTML
	}

	/** @inheritDoc */
	public function preprocessWikitext(
		PageConfig $pageConfig,
		ContentMetadataCollector $metadata,
		string $wikitext
	): string {
		$revid = $pageConfig->getRevisionId();
		$pageConfigTitle = $this->toPrefixedText( $pageConfig->getLinkTarget() );
		$key = implode( ':', [ 'preprocess', md5( $pageConfigTitle ), md5( $wikitext ), $revid ] );
		$data = $this->getCache( $key );
		if ( $data === null ) {
			$params = [
				'action' => 'expandtemplates',
				'title' => $pageConfigTitle,
				'text' => $wikitext,
				'prop' => 'wikitext|modules|jsconfigvars|categories|properties',
				'showstrategykeys' => 1,
			];
			if ( $revid !== null ) {
				$params['revid'] = $revid;
			}
			$data = $this->api->makeRequest( $params )['expandtemplates'];
			$this->setCache( $key, $data );
		}

		$this->mergeMetadata( $data, $metadata );

		return $data['wikitext'];
	}

	/** @inheritDoc */
	public function fetchTemplateSource(
		PageConfig $pageConfig, LinkTarget $title
	): ?PageContent {
		$title = $this->toPrefixedText( $title );
		$key = implode( ':', [ 'content', md5( $title ) ] );
		$ret = $this->getCache( $key );
		if ( $ret === null ) {
			$params = [
				'action' => 'query',
				'prop' => 'revisions',
				'rvprop' => 'content',
				'rvslots' => '*',
				'titles' => $title,
				'rvlimit' => 1,
			];

			$data = $this->api->makeRequest( $params );
			$pageData = $data['query']['pages'][0];
			if ( isset( $pageData['missing'] ) ) {
				return null;
			} else {
				$ret = $pageData['revisions'][0]['slots'];
				// PORT-FIXME set the redirect field if needed
				$this->setCache( $key, $ret );
			}
		}
		return new MockPageContent( $ret );
	}

	/** @inheritDoc */
	public function fetchTemplateData( PageConfig $pageConfig, LinkTarget $title ): ?array {
		$title = $this->toPrefixedText( $title );
		$key = implode( ':', [ 'templatedata', md5( $title ) ] );
		$ret = $this->getCache( $key );
		if ( $ret === null ) {
			$data = $this->api->makeRequest( [
				'action' => 'templatedata',
				'includeMissingTitles' => 1,
				'titles' => $title,
				'redirects' => 1,
			] )['pages'];
			$ret = reset( $data );
			$this->setCache( $key, $ret );
		}
		return $ret;
	}

	/** @inheritDoc */
	public function logLinterData( PageConfig $pageConfig, array $lints ): void {
		foreach ( $lints as $l ) {
			error_log( PHPUtils::jsonEncode( $l ) );
		}
	}

	/**
	 * Helper to turn a LinkTarget object into the "prefixed text" title form
	 * expected by the MediaWiki action API.
	 * @param LinkTarget $linkTarget
	 * @return string The title, as prefixed text
	 */
	private function toPrefixedText( LinkTarget $linkTarget ): string {
		return Title::newFromLinkTarget(
			$linkTarget, $this->siteConfig
		)->getPrefixedText();
	}
}
