<?php

namespace MediaWiki\Extension\TemplateData;

use Message;

/**
 * Represents the information about a template,
 * coming from the JSON blob in the <templatedata> tags
 * on wiki pages.
 * This implementation stores the information as a compressed gzip blob
 * in the database.
 * @license GPL-2.0-or-later
 */
class TemplateDataCompressedBlob extends TemplateDataBlob {

	// Size of MySQL 'blob' field; page_props table where the data is stored uses one.
	private const MAX_LENGTH = 65535;

	/**
	 * @var string In-object cache for {@see getJSONForDatabase}
	 */
	private string $jsonDB;

	/**
	 * @inheritDoc
	 */
	protected function __construct( string $json ) {
		parent::__construct( $json );
		$this->jsonDB = gzencode( $this->json );

		$length = strlen( $this->jsonDB );
		if ( $length > self::MAX_LENGTH ) {
			$this->status->fatal(
				'templatedata-invalid-length',
				Message::numParam( $length ),
				Message::numParam( self::MAX_LENGTH )
			);
		}
	}

	/**
	 * @return string JSON (gzip compressed)
	 */
	public function getJSONForDatabase(): string {
		return $this->jsonDB;
	}

}
