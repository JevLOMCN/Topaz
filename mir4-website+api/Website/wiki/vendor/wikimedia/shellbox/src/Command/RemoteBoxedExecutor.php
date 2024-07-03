<?php

namespace Shellbox\Command;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shellbox\Client;
use Shellbox\Shellbox;
use Shellbox\ShellboxError;

/**
 * A BoxedExecutor which works by running the command on a remote server via
 * HTTP/HTTPS.
 */
class RemoteBoxedExecutor extends BoxedExecutor {
	/** @var Client */
	private $client;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param Client $client
	 */
	public function __construct( Client $client ) {
		$this->client = $client;
		$this->logger = new NullLogger;
	}

	/**
	 * Set the logger
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	public function executeValid( BoxedCommand $command ) {
		$parts = [ [
			'name' => 'json-data',
			'headers' => [
				'Content-Disposition' => "json-data",
				'Content-Type' => 'application/json',
			],
			'contents' => Shellbox::jsonEncode( [
				'action' => 'shell',
				'command' => $command->getClientData(),
			] )
		] ];
		foreach ( $command->getInputFiles() as $boxedName => $file ) {
			$parts[] = [
				'name' => $boxedName,
				'headers' => [
					'Content-Disposition' =>
						"attachment; name=\"$boxedName\"; filename=\"$boxedName\"",
					'Content-Type' => 'application/octet-stream',
				],
				'contents' => $file->getStreamOrString()
			];
		}
		$stdin = $command->getStdin();
		if ( $stdin !== '' ) {
			$parts[] = [
				'name' => 'stdin',
				'headers' => [
					'Content-Disposition' => "form-data; name=stdin",
					'Content-Type' => 'application/octet-stream'
				],
				'contents' => $stdin
			];
		}
		$files = $command->getOutputFiles();
		$globs = $command->getOutputGlobs();

		$resultData = $this->client->sendRequest(
			'shell/' . $command->getRouteName(),
			$parts,
			$files,
			$globs
		);

		if ( !isset( $resultData['exitCode'] ) ) {
			throw new ShellboxError( 'Server result is missing the exit code' );
		}

		foreach ( $resultData['log'] ?? [] as $logEntry ) {
			$this->logger->log(
				$logEntry['level'],
				$logEntry['message'],
				$logEntry['context']
			);
		}

		$result = ( new BoxedResult )
			->exitCode( $resultData['exitCode'] )
			->stdout( $resultData['stdout'] ?? null )
			->stderr( $resultData['stderr'] ?? null );

		foreach ( $files as $boxedName => $file ) {
			if ( $file->wasReceived() ) {
				$result->addOutputFile( $boxedName, $file );
			}
		}

		foreach ( $globs as $glob ) {
			foreach ( $glob->getFiles() as $boxedName => $file ) {
				$result->addOutputFile( $boxedName, $file );
			}
		}

		return $result;
	}
}
