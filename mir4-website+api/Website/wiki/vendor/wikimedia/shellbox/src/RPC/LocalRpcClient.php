<?php

namespace Shellbox\RPC;

use Shellbox\ShellboxError;
use Throwable;

/**
 * An implementation of RPCClient that executes the provided commands locally.
 */
class LocalRpcClient implements RpcClient {

	public function call(
		string $routeName,
		$functionName,
		array $params = [],
		array $options = []
	) {
		$binary = !empty( $options['binary'] );

		if ( $binary ) {
			$params = array_map( static function ( $param ) {
				return (string)$param;
			}, $params );
		}
		foreach ( $options['sources' ] ?? [] as $source ) {
			require_once $source;
		}

		// We don't need to require $options['classes'] sources - local client
		// is run in the same environment as the calling code, so classes
		// be autoloaded.

		try {
			$result = call_user_func_array( $functionName, $params );
		} catch ( Throwable $e ) {
			throw new ShellboxError( $e->getMessage(), $e->getCode(), $e );
		}

		if ( $binary ) {
			$result = (string)$result;
		}

		return $result;
	}

}
