<?php

namespace PageImages\Hooks;

use MediaWiki\Page\PageIdentity;
use MediaWiki\Page\PageProps;
use MediaWiki\Search\Hook\SearchResultProvideThumbnailHook;
use MediaWiki\Search\SearchResultThumbnailProvider;
use PageImages\PageImages;
use RepoGroup;

class SearchResultProvideThumbnailHookHandler implements SearchResultProvideThumbnailHook {

	/** @var SearchResultThumbnailProvider */
	private $thumbnailProvider;

	/** @var PageProps */
	private $pageProps;

	/** @var RepoGroup */
	private $repoGroup;

	/**
	 * @param SearchResultThumbnailProvider $thumbnailProvider
	 * @param PageProps $pageProps
	 * @param RepoGroup $repoGroup
	 */
	public function __construct(
		SearchResultThumbnailProvider $thumbnailProvider,
		PageProps $pageProps,
		RepoGroup $repoGroup
	) {
		$this->thumbnailProvider = $thumbnailProvider;
		$this->pageProps = $pageProps;
		$this->repoGroup = $repoGroup;
	}

	/**
	 * Returns a list of fileNames for a given list of PageIdentity objects (outside of NS_FILE)
	 *
	 * @param PageIdentity[] $identitiesByPageId key-value array of where key
	 *   is pageId, value is PageIdentity
	 * @return array
	 */
	private function getFileNamesByPageId( array $identitiesByPageId ): array {
		$nonFileIdentitiesByPageId = array_filter(
			$identitiesByPageId,
			static function ( PageIdentity $pageIdentity ) {
				return $pageIdentity->getNamespace() !== NS_FILE;
			}
		);

		$propValues = $this->pageProps->getProperties(
			$nonFileIdentitiesByPageId,
			// T320661: only provide free images for search purposes
			(array)PageImages::getPropNames( PageImages::LICENSE_FREE )
		);
		$fileNames = array_map( static function ( $prop ) {
			return $prop[ PageImages::getPropName( false ) ]
				?? $prop[ PageImages::getPropName( true ) ]
				?? null;
		}, $propValues );

		return array_filter( $fileNames, static function ( $fileName ) {
			return $fileName !== null;
		} );
	}

	/**
	 * @param array $pageIdentities array that contain $pageId => PageIdentity.
	 * @param array &$results Placeholder for result. $pageId => SearchResultThumbnail
	 * @param int|null $size size of thumbnail height and width in points
	 */
	public function onSearchResultProvideThumbnail( array $pageIdentities, &$results, int $size = null ): void {
		$fileNamesByPageId = $this->getFileNamesByPageId( $pageIdentities );
		$results ??= [];
		foreach ( $fileNamesByPageId as $pageId => $fileName ) {
			$file = $this->repoGroup->findFile( $fileName );
			if ( !$file ) {
				continue;
			}
			$thumbnail = $this->thumbnailProvider->buildSearchResultThumbnailFromFile( $file, $size );
			if ( $thumbnail ) {
				$results[$pageId] = $thumbnail;
			}
		}
	}
}
