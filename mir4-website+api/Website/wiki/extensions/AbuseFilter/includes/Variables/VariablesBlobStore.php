<?php

namespace MediaWiki\Extension\AbuseFilter\Variables;

use FormatJson;
use MediaWiki\Storage\BlobAccessException;
use MediaWiki\Storage\BlobStore;
use MediaWiki\Storage\BlobStoreFactory;

/**
 * This service is used to store and load var dumps to a BlobStore
 */
class VariablesBlobStore {
	public const SERVICE_NAME = 'AbuseFilterVariablesBlobStore';

	/** @var VariablesManager */
	private $varManager;

	/** @var BlobStoreFactory */
	private $blobStoreFactory;

	/** @var BlobStore */
	private $blobStore;

	/** @var string|null */
	private $centralDB;

	/**
	 * @param VariablesManager $varManager
	 * @param BlobStoreFactory $blobStoreFactory
	 * @param BlobStore $blobStore
	 * @param string|null $centralDB
	 */
	public function __construct(
		VariablesManager $varManager,
		BlobStoreFactory $blobStoreFactory,
		BlobStore $blobStore,
		?string $centralDB
	) {
		$this->varManager = $varManager;
		$this->blobStoreFactory = $blobStoreFactory;
		$this->blobStore = $blobStore;
		$this->centralDB = $centralDB;
	}

	/**
	 * Store a var dump to a BlobStore.
	 *
	 * @param VariableHolder $varsHolder
	 * @param bool $global
	 *
	 * @return string Address of the record
	 */
	public function storeVarDump( VariableHolder $varsHolder, $global = false ) {
		// Get all variables yet set and compute old and new wikitext if not yet done
		// as those are needed for the diff view on top of the abuse log pages
		$vars = $this->varManager->dumpAllVars( $varsHolder, [ 'old_wikitext', 'new_wikitext' ] );

		// Vars is an array with native PHP data types (non-objects) now
		$text = FormatJson::encode( $vars );

		$dbDomain = $global ? $this->centralDB : false;
		$blobStore = $this->blobStoreFactory->newBlobStore( $dbDomain );

		$hints = [
			BlobStore::DESIGNATION_HINT => 'AbuseFilter',
			BlobStore::MODEL_HINT => 'AbuseFilter',
		];
		return $blobStore->storeBlob( $text, $hints );
	}

	/**
	 * Retrieve a var dump from a BlobStore.
	 *
	 * @param string $address
	 *
	 * @return VariableHolder
	 */
	public function loadVarDump( string $address ): VariableHolder {
		try {
			$blob = $this->blobStore->getBlob( $address );
		} catch ( BlobAccessException $ex ) {
			return new VariableHolder;
		}

		$vars = FormatJson::decode( $blob, true );
		$obj = VariableHolder::newFromArray( $vars );
		$this->varManager->translateDeprecatedVars( $obj );
		return $obj;
	}
}
