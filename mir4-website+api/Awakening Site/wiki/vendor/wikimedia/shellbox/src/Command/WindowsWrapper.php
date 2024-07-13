<?php

namespace Shellbox\Command;

class WindowsWrapper extends Wrapper {
	/**
	 * Windows should be the outermost wrapper because of its special quoting
	 */
	public const PRIORITY = 80;

	public function wrap( Command $command ) {
		// Windows Shell bypassed, but command run is "cmd.exe /C "{$cmd}"
		// This solves some shell parsing issues, see T207248.
		// This should be unnecessary in PHP 8.0+.
		$command->unsafeCommand(
			'cmd /s /c "' . $command->getCommandString() . '"'
		);
		$command->procOpenOptions( [ 'bypass_shell' => true ] );
	}

	public function getPriority() {
		return self::PRIORITY;
	}
}
