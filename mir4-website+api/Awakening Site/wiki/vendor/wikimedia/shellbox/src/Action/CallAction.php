<?php

namespace Shellbox\Action;

/**
 * PHP RPC handler
 */
class CallAction extends MultipartAction {
	/**
	 * @param string[] $pathParts @phan-unused-param
	 */
	protected function execute( $pathParts ) {
		$this->forgetConfig( 'secretKey' );

		$functionName = $this->getRequiredParam( 'functionName' );
		$binary = $this->getRequiredParam( 'binary' );
		if ( $binary ) {
			$params = [];
			for ( $i = 0; ; $i++ ) {
				$param = $this->getParam( "param$i" );
				if ( $param === null ) {
					break;
				}
				$params[] = $param;
			}
		} else {
			$params = $this->getParam( 'params', [] );
		}
		$this->runSources();
		$result = call_user_func_array( $functionName, $params );

		if ( $binary ) {
			$this->writeResult( [], [ 'returnValue' => (string)$result ] );
		} else {
			$this->writeResult( [ 'returnValue' => $result ] );
		}
	}

	protected function getActionName() {
		return 'call';
	}

	/**
	 * Execute the source files which were included in the request
	 */
	private function runSources() {
		$sources = $this->getParam( 'sources', [] );
		foreach ( $sources as $sourceFileName ) {
			require $this->tempDirManager->getPath( $sourceFileName );
		}
	}
}
