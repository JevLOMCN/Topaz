<?php

namespace Shellbox\Command;

use Shellbox\Shellbox;
use Shellbox\ShellboxError;

/**
 * A wrapper which runs the command via systemd-run
 */
class SystemdWrapper extends Wrapper {
	/**
	 * Systemd needs to be an outer wrapper since it doesn't directly exec the
	 * binary and doesn't inherit permissions
	 */
	public const PRIORITY = 60;

	public function __construct() {
		parent::__construct();

		if ( !function_exists( 'posix_getuid' ) ) {
			throw new ShellboxError( 'SystemdWrapper requires the posix extension' );
		}
	}

	public function wrap( Command $command ) {
		if ( $command->getDisableSandbox() ) {
			return;
		}

		$args = [ '/usr/bin/systemd-run', '--user', '--pipe', '--quiet', '--no-ask-password' ];

		$wd = $command->getWorkingDirectory();
		if ( $wd !== null ) {
			$args[] = "-pWorkingDirectory=$wd";
		}

		$cpu = $command->getCpuTimeLimit();
		if ( $cpu ) {
			$args[] = "-pLimitCPU=$cpu";
		}

		$mem = $command->getMemoryLimit();
		if ( $mem ) {
			$args[] = "-pMemoryMax=$mem";
			$args[] = "-pMemorySwapMax=$mem";
		}

		$fileSize = $command->getFileSizeLimit();
		if ( $fileSize ) {
			$args[] = "-pLimitFSIZE=$fileSize";
		}

		$paths = $command->getAllowedPaths();
		if ( $paths ) {
			$args[] = '-pReadWritePaths=' . $this->makeList( $paths );
		}

		$paths = $command->getDisallowedPaths();
		if ( $paths ) {
			$args[] = '-pTemporaryFileSystem=' . $this->makeList( $paths );
		}

		if ( $command->getDisableNetwork() ) {
			$args[] = '-pPrivateNetwork=yes';
		}

		$disabledSyscalls = $command->getDisabledSyscalls();
		if ( $disabledSyscalls ) {
			$args[] = '-pSystemCallFilter=~' . $this->makeList( $disabledSyscalls );
		}

		if ( $command->getNoNewPrivs() ) {
			$args[] = '-pNoNewPrivileges=yes';
		}

		if ( $command->getPrivateUserNamespace() ) {
			$args[] = '-pPrivateUsers=yes';
		}

		if ( $command->getPrivateDev() ) {
			$args[] = '-pPrivateDevices=yes';
		}

		foreach ( $command->getEnvironment() as $name => $value ) {
			$args[] = "-E$name=$value";
		}

		if ( in_array( 'execve', $disabledSyscalls ) ) {
			$command->unsafeCommand(
				Shellbox::escape( $args ) . ' ' .
				$command->getCommandString() );
		} else {
			$args[] = '/bin/sh';
			$args[] = '-c';
			$args[] = $command->getCommandString();
			$command->replaceParams( $args );
		}
		$command->environment( [
			'XDG_RUNTIME_DIR' => '/run/user/' . posix_getuid()
		] );
	}

	/**
	 * systemd-run uses space-separated lists as config, an odd convention.
	 * Ensure that the list members do not contain spaces before combining them
	 * into a string.
	 *
	 * @param string[] $paths
	 * @return string
	 * @throws ShellboxError
	 */
	private function makeList( $paths ) {
		foreach ( $paths as $path ) {
			if ( strpos( $path, ' ' ) !== false ) {
				throw new ShellboxError( 'SystemdWrapper: property list cannot contain a space' );
			}
		}
		return implode( ' ', $paths );
	}

	public function getPriority() {
		return self::PRIORITY;
	}
}
