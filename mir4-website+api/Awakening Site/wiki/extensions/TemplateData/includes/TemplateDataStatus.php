<?php

namespace MediaWiki\Extension\TemplateData;

use MediaWiki\Status\Status;

/**
 * @license GPL-2.0-or-later
 */
class TemplateDataStatus {

	/**
	 * @param Status $status
	 * @return array contains StatusValue ok and errors fields (does not serialize value)
	 */
	public static function jsonSerialize( Status $status ): array {
		if ( $status->isOK() ) {
			return [ 'ok' => true ];
		}

		[ $errorsOnlyStatus, $warningsOnlyStatus ] = $status->splitByErrorType();
		// note that non-scalar values are not supported in errors or warnings
		return [
			'ok' => false,
			'errors' => $errorsOnlyStatus->getErrors(),
			'warnings' => $warningsOnlyStatus->getErrors()
		];
	}

	/**
	 * @param Status|array|null $json contains StatusValue ok and errors fields (does not serialize value)
	 * @return Status|null
	 */
	public static function newFromJson( $json ): ?Status {
		if ( !is_array( $json ) ) {
			return $json;
		}

		if ( $json['ok'] ) {
			return Status::newGood();
		}

		$status = new Status();
		foreach ( $json['errors'] as $error ) {
			$status->fatal( $error['message'], ...$error['params'] );
		}
		foreach ( $json['warnings'] as $warning ) {
			$status->warning( $warning['message'], ...$warning['params'] );
		}
		return $status;
	}

}
