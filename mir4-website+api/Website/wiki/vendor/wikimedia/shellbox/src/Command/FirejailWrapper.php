<?php

namespace Shellbox\Command;

use Shellbox\ShellboxError;

/**
 * A wrapper that restricts the command using firejail
 */
class FirejailWrapper extends Wrapper {
	/**
	 * Firejail is a setuid-root executable which naturally goes inside systemd
	 * but outside BashWrapper, since it inherits and preserves most aspects of
	 * the system.
	 */
	public const PRIORITY = 40;

	/**
	 * @var string The path to firejail
	 */
	private $binaryPath;

	/**
	 * @var string The path to the profile file
	 */
	private $profilePath;

	/**
	 * @param string $binaryPath The path to firejail
	 * @param string $profilePath The path to the profile file
	 */
	public function __construct( $binaryPath, $profilePath ) {
		parent::__construct();
		$this->binaryPath = $binaryPath;
		$this->profilePath = $profilePath;
	}

	public function wrap( Command $command ) {
		// If there are no restrictions, don't use firejail
		if ( $command->getDisableSandbox() ) {
			$splitCommand = explode( ' ', $command->getCommandString(), 2 );
			$this->logger->debug(
				"firejail: Command {$splitCommand[0]} {params} has no restrictions",
				[ 'params' => $splitCommand[1] ?? '' ]
			);
			return;
		}

		// quiet has to come first to prevent firejail from adding
		// any output.
		$cmd = [ $this->binaryPath, '--quiet' ];
		// Use a profile that allows people to add local overrides
		// if their system is setup in an incompatible manner. Also it
		// prevents any default profiles from running.
		// FIXME: Doesn't actually override command-line switches?
		$cmd[] = '--profile=' . $this->profilePath;

		foreach ( $command->getAllowedPaths() as $path ) {
			if ( $path === '/home' ) {
				$cmd[] = '--allusers';
			} else {
				$cmd[] = "--whitelist={$path}";
			}
		}

		foreach ( $command->getDisallowedPaths() as $path ) {
			$cmd[] = '--blacklist=' . $path;
		}

		if ( $command->getPrivateUserNamespace() ) {
			$cmd[] = '--noroot';
		}

		$extraSeccomp = $command->getDisabledSyscalls();
		$useSeccomp = $extraSeccomp || $command->getFirejailDefaultSeccomp();

		if ( in_array( 'execve', $extraSeccomp ) ) {
			// Running the command in the shell won't work without the execve
			// syscall, so split the command string into literal arguments and
			// pass those to firejail for direct execution.
			// Firejail 0.9.72 or later must be used for this mode to work.
			$argv = $command->getSyntaxInfo()->getLiteralArgv();
			if ( $argv === null ) {
				throw new ShellboxError(
					"The command contained non-literal shell components but " .
					"seccomp=execve was requested"
				);
			}
		} else {
			// Wrap the whole command in a shell (T353194)
			$argv = [ '/bin/sh', '-c', '--', $command->getCommandString() ];
		}

		if ( $useSeccomp ) {
			$seccomp = '--seccomp';
			if ( $extraSeccomp ) {
				// The "@default" seccomp group will always be enabled
				$seccomp .= '=' . implode( ',', $extraSeccomp );
			}
			$cmd[] = $seccomp;
		}

		if ( $command->getPrivateDev() ) {
			$cmd[] = '--private-dev';
		}

		if ( $command->getDisableNetwork() ) {
			$cmd[] = '--net=none';
		}

		foreach ( $command->getEnvironment() as $name => $value ) {
			if ( (string)$value !== '' ) {
				$cmd[] = "--env=$name=$value";
			}
		}

		// Prefix the firejail command in front of the wanted command
		$command->replaceParams( array_merge( $cmd, $argv ) );
	}

	public function getPriority() {
		return self::PRIORITY;
	}
}
