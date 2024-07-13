<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Variables;

use FormatJson;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Parser\AFPData;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Storage\BlobAccessException;
use MediaWiki\Storage\BlobStore;
use MediaWiki\Storage\BlobStoreFactory;
use MediaWikiUnitTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore
 * @covers ::__construct
 */
class VariablesBlobStoreTest extends MediaWikiUnitTestCase {

	private function getStore(
		BlobStoreFactory $blobStoreFactory = null,
		BlobStore $blobStore = null
	): VariablesBlobStore {
		$manager = $this->createMock( VariablesManager::class );
		$manager->method( 'dumpAllVars' )->willReturnCallback( static function ( VariableHolder $holder ) {
			$ret = [];
			foreach ( $holder->getVars() as $name => $var ) {
				if ( $var instanceof AFPData ) {
					$ret[$name] = $var->toNative();
				}
			}
			return $ret;
		} );
		$manager->method( 'translateDeprecatedVars' )->willReturnCallback( static function ( VariableHolder $holder ) {
			$depVars = TestingAccessWrapper::constant( KeywordsManager::class, 'DEPRECATED_VARS' );
			foreach ( $holder->getVars() as $name => $value ) {
				if ( array_key_exists( $name, $depVars ) ) {
					$holder->setVar( $depVars[$name], $value );
					$holder->removeVar( $name );
				}
			}
		} );
		return new VariablesBlobStore(
			$manager,
			$blobStoreFactory ?? $this->createMock( BlobStoreFactory::class ),
			$blobStore ?? $this->createMock( BlobStore::class ),
			null
		);
	}

	/**
	 * @covers ::storeVarDump
	 */
	public function testStoreVarDump() {
		$expectID = 123456;
		$blobStore = $this->createMock( BlobStore::class );
		$blobStore->expects( $this->once() )->method( 'storeBlob' )->willReturn( $expectID );
		$blobStoreFactory = $this->createMock( BlobStoreFactory::class );
		$blobStoreFactory->method( 'newBlobStore' )->willReturn( $blobStore );
		$varBlobStore = $this->getStore( $blobStoreFactory );
		$this->assertSame( $expectID, $varBlobStore->storeVarDump( new VariableHolder() ) );
	}

	/**
	 * @covers ::loadVarDump
	 */
	public function testLoadVarDump() {
		$vars = [ 'foo-variable' => 42 ];
		$blob = FormatJson::encode( $vars );
		$blobStore = $this->createMock( BlobStore::class );
		$blobStore->expects( $this->once() )->method( 'getBlob' )->willReturn( $blob );
		$varBlobStore = $this->getStore( null, $blobStore );
		$loadedVars = $varBlobStore->loadVarDump( 'foo' )->getVars();
		$this->assertArrayHasKey( 'foo-variable', $loadedVars );
		$this->assertSame( 42, $loadedVars['foo-variable']->toNative() );
	}

	/**
	 * @covers ::loadVarDump
	 */
	public function testLoadVarDump_fail() {
		$blobStore = $this->createMock( BlobStore::class );
		$blobStore->expects( $this->once() )->method( 'getBlob' )->willThrowException( new BlobAccessException );
		$varBlobStore = $this->getStore( null, $blobStore );
		$this->assertCount( 0, $varBlobStore->loadVarDump( 'foo' )->getVars() );
	}

	private function getBlobStore(): BlobStore {
		return new class implements BlobStore {
			private $blobs;

			private function getKey( string $data ) {
				return md5( $data );
			}

			public function getBlob( $blobAddress, $queryFlags = 0 ) {
				return $this->blobs[$blobAddress];
			}

			public function getBlobBatch( $blobAddresses, $queryFlags = 0 ) {
			}

			public function storeBlob( $data, $hints = [] ) {
				$key = $this->getKey( $data );
				$this->blobs[$key] = $data;
				return $key;
			}

			public function isReadOnly() {
			}
		};
	}

	/**
	 * @covers ::storeVarDump
	 * @covers ::loadVarDump
	 * @dataProvider provideVariables
	 */
	public function testRoundTrip( array $toStore, array $expected = null ) {
		$blobStore = $this->getBlobStore();
		$blobStoreFactory = $this->createMock( BlobStoreFactory::class );
		$blobStoreFactory->method( 'newBlobStore' )->willReturn( $blobStore );
		$varBlobStore = $this->getStore( $blobStoreFactory, $blobStore );

		$storeID = $varBlobStore->storeVarDump( VariableHolder::newFromArray( $toStore ) );
		$this->assertIsString( $storeID );
		$loadedVars = $varBlobStore->loadVarDump( $storeID )->getVars();
		$nativeLoadedVars = array_map( static function ( AFPData $el ) {
			return $el->toNative();
		}, $loadedVars );
		$this->assertSame( $expected ?? $toStore, $nativeLoadedVars );
	}

	/**
	 * Data provider for testVarDump
	 *
	 * @return array
	 */
	public static function provideVariables() {
		return [
			'Only basic variables' => [
				[
					'action' => 'edit',
					'old_wikitext' => 'Old text',
					'new_wikitext' => 'New text'
				]
			],
			'Normal case' => [
				[
					'action' => 'edit',
					'old_wikitext' => 'Old text',
					'new_wikitext' => 'New text',
					'user_editcount' => 15,
					'added_lines' => [ 'Foo', '', 'Bar' ]
				]
			],
			'Deprecated variables' => [
				[
					'action' => 'edit',
					'old_wikitext' => 'Old text',
					'new_wikitext' => 'New text',
					'article_articleid' => 11745,
					'article_first_contributor' => 'Good guy'
				],
				[
					'action' => 'edit',
					'old_wikitext' => 'Old text',
					'new_wikitext' => 'New text',
					'page_id' => 11745,
					'page_first_contributor' => 'Good guy'
				]
			],
			'Move action' => [
				[
					'action' => 'move',
					'old_wikitext' => 'Old text',
					'new_wikitext' => 'New text',
					'all_links' => [ 'https://en.wikipedia.org' ],
					'moved_to_id' => 156,
					'moved_to_prefixedtitle' => 'MediaWiki:Foobar.js',
					'new_content_model' => CONTENT_MODEL_JAVASCRIPT
				]
			],
			'Delete action' => [
				[
					'old_wikitext' => 'Old text',
					'new_wikitext' => 'New text',
					'timestamp' => 1546000295,
					'action' => 'delete',
					'page_namespace' => 114
				]
			],
			'Disabled vars' => [
				[
					'action' => 'edit',
					'old_wikitext' => 'Old text',
					'new_wikitext' => 'New text',
					'old_html' => 'Foo <small>bar</small> <s>lol</s>.',
					'old_text' => 'Foobar'
				]
			],
			'Account creation' => [
				[
					'action' => 'createaccount',
					'accountname' => 'XXX'
				]
			]
		];
	}
}
