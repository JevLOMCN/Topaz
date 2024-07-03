<?php

namespace Shellbox\Command;

/**
 * A BoxedExecutor for internal use by the Server.
 *
 * The Server reads input files directly from the multipart stream into the
 * temporary directory. So unlike LocalBoxedExecutor, there is no need to
 * create input files here. Similarly, output file destruction is deferred
 * until after the response has been sent.
 *
 * @internal
 */
class ServerBoxedExecutor extends LocalBoxedExecutor {
	public function executeValid( BoxedCommand $command ) {
		$command = $this->applyBoxConfig( $command );
		$this->prepareOutputDirectories( $command );
		$result = new ServerBoxedResult;
		$result->merge( $this->unboxedExecutor->execute( $command ) );
		$result->setFileNames( array_keys( $this->findOutputFiles( $command ) ) );
		return $result;
	}
}
