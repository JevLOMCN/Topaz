<?php

namespace Shellbox\Command;

use Shellbox\Multipart\MultipartReader;
use Shellbox\ShellboxError;

class OutputFilePlaceholder extends OutputFile {
	public function copyFromFile( $sourcePath ) {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new ShellboxError( __METHOD__ . ': not implemented' );
	}

	public function getContents() {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new ShellboxError( __METHOD__ . ': not implemented' );
	}

	public function readFromMultipart( MultipartReader $multipartReader ) {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new ShellboxError( __METHOD__ . ': not implemented' );
	}
}
