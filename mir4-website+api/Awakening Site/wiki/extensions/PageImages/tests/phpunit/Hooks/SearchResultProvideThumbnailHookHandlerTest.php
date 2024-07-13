<?php

namespace PageImages\Tests\Hooks;

use LocalFile;
use LogicException;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Page\PageIdentityValue;
use MediaWiki\Page\PageProps;
use MediaWiki\Search\SearchResultThumbnailProvider;
use MediaWikiIntegrationTestCase;
use PageImages\Hooks\SearchResultProvideThumbnailHookHandler;
use PageImages\PageImages;
use RepoGroup;
use ThumbnailImage;

/**
 * @covers \PageImages\Hooks\SearchResultProvideThumbnailHookHandler
 *
 * @group PageImages
 */
class SearchResultProvideThumbnailHookHandlerTest extends MediaWikiIntegrationTestCase {
	/**
	 * Creates mock object for LocalFile
	 * @param int $size
	 * @param LocalFile $file
	 * @return ThumbnailImage
	 */
	private function getMockThumbnailImage(
		int $size,
		LocalFile $file
	): ThumbnailImage {
		$thumbnail = $this->getMockBuilder( ThumbnailImage::class )
			->disableOriginalConstructor()
			->onlyMethods( [
				'getWidth',
				'getFile',
				'getUrl'
			] )
			->getMock();

			$thumbnail->expects( $this->once() )
				->method( 'getUrl' )
				->willReturn( 'https://example.org/test.url' );

			$thumbnail->expects( $this->once() )
				->method( 'getWidth' )
				->willReturn( $size );

			$thumbnail->expects( $this->once() )
				->method( 'getFile' )
				->willReturn( $file );

		return $thumbnail;
	}

	/**
	 * Creates mock object for LocalFile
	 * @param int $size
	 * @param string $filename
	 * @return LocalFile
	 */
	private function getMockLocalFile( int $size, $filename ): LocalFile {
		$file = $this->getMockBuilder( LocalFile::class )
			->disableOriginalConstructor()
			->onlyMethods( [
				'getName',
				'transform',
				'getMimeType'
			] )
			->getMock();

		$file->expects( $this->once() )
			->method( 'getName' )
			->willReturn( $filename );

		$file->expects( $this->once() )
			->method( 'transform' )
			->willReturn( $this->getMockThumbnailImage( $size, $file ) );

		$file->expects( $this->once() )
			->method( 'getMimeType' )
			->willReturn( 'image/jpg' );

		return $file;
	}

	public function testProvideThumbnail() {
		$pageProps = $this->getMockBuilder( PageProps::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getProperties' ] )
			->getMock();

		$pageIdentities = [
			1 => new PageIdentityValue( 1, NS_MAIN, 'dbKey1', PageIdentity::LOCAL ),
			2 => new PageIdentityValue( 2, NS_MAIN, 'dbKey2', PageIdentity::LOCAL ),
			3 => new PageIdentityValue( 3, NS_FILE, 'dbKey3', PageIdentity::LOCAL ),
			4 => new PageIdentityValue( 4, NS_FILE, 'dbKey4', PageIdentity::LOCAL )
		];

		$pageProps->expects( $this->once() )
			->method( 'getProperties' )
			->with(
				$this->anything(),
				(array)PageImages::getPropNames( PageImages::LICENSE_FREE )
			)->willReturn( [
				1 => [
					PageImages::getPropName( true ) => 'File1_free.jpg'
				],
				2 => [
					PageImages::getPropName( true ) => 'File2_free.jpg',
				] ] );

		$repoGroup = $this->getMockBuilder( RepoGroup::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'findFile' ] )
			->getMock();

		$repoGroup->expects( $this->exactly( 2 ) )
			->method( 'findFile' )
			->willReturnCallback( function ( $title ) {
				switch ( $title ) {
					case 'File1_free.jpg':
						return $this->getMockLocalFile(
							SearchResultThumbnailProvider::THUMBNAIL_SIZE,
							$title
						);
					case 'File2_free.jpg':
						return null;
					default:
						throw new LogicException( "Unexpected title $title" );
				}
			} );

		$provider = new SearchResultThumbnailProvider( $repoGroup, $this->createHookContainer() );
		$handler = new SearchResultProvideThumbnailHookHandler( $provider, $pageProps, $repoGroup );

		$results = [ 1 => null, 2 => null, 3 => null, 4 => null ];
		$handler->onSearchResultProvideThumbnail( $pageIdentities, $results );

		$this->assertNull( $results[ 2 ] );
		$this->assertNull( $results[ 3 ] );
		$this->assertNull( $results[ 4 ] );

		$this->assertNotNull( $results[ 1 ] );
		$this->assertSame( 'File1_free.jpg', $results[ 1 ]->getName() );
		$this->assertSame(
			SearchResultThumbnailProvider::THUMBNAIL_SIZE,
			$results[ 1 ]->getWidth()
		);
		$this->assertSame( 'https://example.org/test.url', $results[ 1 ]->getUrl() );
	}
}
