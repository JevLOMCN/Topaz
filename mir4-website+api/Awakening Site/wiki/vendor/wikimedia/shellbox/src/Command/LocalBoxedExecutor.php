<?php

namespace Shellbox\Command;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shellbox\TempDirManager;

/**
 * An executor which runs a BoxedCommand locally.
 *
 * This is overridden by ServerBoxedExecutor, which runs a BoxedCommand in the
 * context of a Server.
 */
class LocalBoxedExecutor extends BoxedExecutor {
	/** @var UnboxedExecutor */
	protected $unboxedExecutor;
	/** @var TempDirManager */
	protected $tempDirManager;
	/** @var LoggerInterface */
	protected $logger;

	/**
	 * @param UnboxedExecutor $unboxedExecutor
	 * @param TempDirManager $tempDirManager
	 */
	public function __construct(
		UnboxedExecutor $unboxedExecutor,
		TempDirManager $tempDirManager
	) {
		$this->unboxedExecutor = $unboxedExecutor;
		$this->tempDirManager = $tempDirManager;
		$this->logger = new NullLogger;
	}

	public function executeValid( BoxedCommand $command ) {
		$command = $this->applyBoxConfig( $command );
		$this->createInputFiles( $command );
		$this->prepareOutputDirectories( $command );
		$result = new BoxedResult;
		$result->merge( $this->unboxedExecutor->execute( $command ) );
		$this->collectOutputFiles( $command, $result );
		$this->tempDirManager->teardown();
		return $result;
	}

	/**
	 * Set the logger
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Copy input files from the BoxedCommand to the working directory, ready
	 * for command execution.
	 *
	 * @param BoxedCommand $command
	 */
	protected function createInputFiles( BoxedCommand $command ) {
		foreach ( $command->getInputFiles() as $boxedName => $file ) {
			$path = $this->tempDirManager->preparePath( $boxedName );
			$file->copyTo( $path );
		}
	}

	/**
	 * Ensure that any subdirectories named in registered output files are
	 * created, so that the command can write the files there.
	 *
	 * @param BoxedCommand $command
	 */
	protected function prepareOutputDirectories( BoxedCommand $command ) {
		foreach ( $command->getOutputFiles() as $boxedName => $file ) {
			$this->tempDirManager->preparePath( $boxedName );
		}

		foreach ( $command->getOutputGlobs() as $boxedName => $file ) {
			$this->tempDirManager->preparePath( $boxedName );
		}
	}

	/**
	 * Modify the configuration of the command as required by the BoxedCommand
	 * abstraction, returning a cloned BoxedCommand.
	 *
	 * @param BoxedCommand $command
	 * @return BoxedCommand
	 */
	protected function applyBoxConfig( BoxedCommand $command ) {
		$command = clone $command;
		return $command
			->workingDirectory( $this->tempDirManager->prepareBasePath() )
			->passStdin( false )
			->forwardStderr( false );
	}

	/**
	 * This is called after the command has run. Find any output files which
	 * match output files and globs that were registered in the BoxedCommand.
	 *
	 * @param BoxedCommand $command
	 * @return OutputEntity[] OutputFile and OutputGlob objects indexed by the
	 *   found filename relative to the working directory. In the case of
	 *   OutputGlob, multiple keys may point to the same OutputGlob object.
	 */
	protected function findOutputFiles( BoxedCommand $command ) {
		$files = [];
		foreach ( $command->getOutputFiles() as $boxedName => $file ) {
			$sourcePath = $this->tempDirManager->getPath( $boxedName );
			if ( file_exists( $sourcePath ) ) {
				$files[$boxedName] = $file;
			} else {
				$this->logger->debug( 'After executing route ' . $command->getRouteName() .
					": did not find expected output file $boxedName" );
			}
		}

		foreach ( $command->getOutputGlobs() as $glob ) {
			$prefixPath = $this->tempDirManager->getPath( $glob->getPrefix() );
			foreach ( glob( "$prefixPath*.{$glob->getExtension()}" ) as $path ) {
				$base = basename( $path );
				if ( strpos( $glob->getPrefix(), '/' ) === false ) {
					$boxedName = $base;
				} else {
					$boxedName = dirname( $glob->getPrefix() ) . '/' . $base;
				}
				$files[$boxedName] = $glob;
			}
		}

		return $files;
	}

	/**
	 * This is called after the command has run. Find output files and copy
	 * them to the registered destination location, which may be either a path
	 * outside the working directory, or a string. Register the details in the
	 * supplied BoxedResult.
	 *
	 * @param BoxedCommand $command
	 * @param BoxedResult $result
	 */
	protected function collectOutputFiles( BoxedCommand $command, BoxedResult $result ) {
		foreach ( $this->findOutputFiles( $command ) as $boxedName => $outputEntity ) {
			$instance = $outputEntity->getInstance( $boxedName );
			$instance->copyFromFile( $this->tempDirManager->getPath( $boxedName ) );
			$result->addOutputFile( $boxedName, $instance );
		}
	}
}
