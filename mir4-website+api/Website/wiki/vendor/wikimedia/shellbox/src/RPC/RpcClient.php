<?php

namespace Shellbox\RPC;

use Shellbox\ShellboxError;

/**
 * A client that executes PHP code on Shellbox server.
 */
interface RpcClient {

	/**
	 * Call a PHP function remotely.
	 *
	 * @param string $routeName A short string identifying the function
	 * @param array|string $functionName A JSON-serializable callback
	 * @param array $params Function parameters. If "binary" is false or absent,
	 *   the parameters must be JSON-serializable, which means that any strings
	 *   must be valid UTF-8. If "binary" is true, the parameters must all be
	 *   strings.
	 * @param array $options An associative array of options:
	 *    - sources: An array of source file paths, to be executed on the
	 *      remote side prior to calling the function.
	 *    - classes: An array of class names. The source files for the classes
	 *      will be identified using reflection, and the files will be sent to
	 *      the server as if they were specified in the "sources" array.
	 *    - binary: If true, $params will be sent as 8-bit clean strings, and the
	 *      return value will be similarly converted to a string.
	 * @return mixed
	 * @throws ShellboxError
	 */
	public function call(
		string $routeName,
		$functionName,
		array $params = [],
		array $options = []
	);

}
