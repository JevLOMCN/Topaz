<?php

namespace Shellbox\Action;

use Shellbox\Command\BoxedCommand;
use Shellbox\Command\ServerBoxedExecutor;
use Shellbox\Command\ServerUnboxedExecutor;
use Shellbox\ShellboxError;

/**
 * Shell command handler
 */
class ShellAction extends MultipartAction {
	/**
	 * @param string[] $pathParts
	 */
	protected function execute( $pathParts ) {
		$commandData = $this->getRequiredParam( 'command' );
		$command = $this->createCommand( $commandData );
		$this->validateInputFiles( array_keys( $command->getInputFiles() ) );
		$this->validateRoute( $command->getRouteName(), $pathParts );
		$result = $command->execute();
		$binaryData = [];
		if ( $result->getStdout() !== null ) {
			$binaryData['stdout'] = $result->getStdout();
		}
		if ( $result->getStderr() !== null ) {
			$binaryData['stderr'] = $result->getStderr();
		}
		$this->writeResult(
			[ 'exitCode' => $result->getExitCode() ],
			$binaryData,
			$result->getFileNames()
		);
	}

	protected function getActionName() {
		return 'shell';
	}

	/**
	 * @return ServerBoxedExecutor
	 */
	private function createExecutor() {
		$unboxedExecutor = new ServerUnboxedExecutor( $this->tempDirManager );
		$unboxedExecutor->setLogger( $this->logger );
		$unboxedExecutor->addWrappersFromConfiguration( [
			'useSystemd' => $this->getConfig( 'useSystemd' ),
			'useBashWrapper' => $this->getConfig( 'useBashWrapper' ),
			'useFirejail' => $this->getConfig( 'useFirejail' ),
			'firejailPath' => $this->getConfig( 'firejailPath' ),
			'firejailProfile' => $this->getConfig( 'firejailProfile' )
		] );

		$executor = new ServerBoxedExecutor( $unboxedExecutor, $this->tempDirManager );
		$executor->setLogger( $this->logger );
		$executor->setValidationConfig( [
			'allowedRoutes' => $this->getConfig( 'allowedRoutes' ),
			'routeSpecs' => $this->getConfig( 'routeSpecs' )
		] );
		return $executor;
	}

	/**
	 * @param array $commandData
	 * @return BoxedCommand
	 */
	private function createCommand( $commandData ) {
		$executor = $this->createExecutor();
		$command = $executor->createCommand();
		$command->setClientData( $commandData );
		$stdin = $this->getParam( 'stdin' );
		if ( $stdin !== null ) {
			$command->stdin( $stdin );
		}
		return $command;
	}

	/**
	 * Confirm that the received input file list matches the one in the command
	 * client data. This ensures that the input file list will be correctly
	 * validated by Validator.
	 *
	 * @param string[] $files
	 * @throws ShellboxError
	 */
	private function validateInputFiles( $files ) {
		$received = $this->getReceivedFileNames();
		sort( $received );
		sort( $files );

		if ( $files !== $received ) {
			throw new ShellboxError(
				"The received file list does not match the command client data" );
		}
	}

	/**
	 * Confirm that the path route matches the command parameter route
	 *
	 * @param string $commandRoute
	 * @param string[] $pathParts
	 * @throws ShellboxError
	 */
	private function validateRoute( $commandRoute, $pathParts ) {
		if ( count( $pathParts ) !== 1 || $pathParts[0] !== $commandRoute ) {
			throw new ShellboxError(
				"The request path does not match the route in the command" );
		}
	}

}
