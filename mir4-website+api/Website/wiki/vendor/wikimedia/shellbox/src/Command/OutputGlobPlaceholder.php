<?php

namespace Shellbox\Command;

use Shellbox\ShellboxError;

class OutputGlobPlaceholder extends OutputGlob {
	public function getInstance( $boxedName ) {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new ShellboxError( __METHOD__ . ': not implemented' );
	}
}
