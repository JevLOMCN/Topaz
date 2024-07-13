<?php

namespace Shellbox;

use Monolog\Handler\AbstractHandler;

class ClientLogHandler extends AbstractHandler {
	/** @var array */
	private $records = [];

	public function handle( array $record ): bool {
		$this->records[] = [
			'level' => $record['level'],
			'message' => $record['message'],
			'context' => $record['context']
		];
		return false;
	}

	/**
	 * Remove and return the accumulated log entries
	 *
	 * @return array
	 */
	public function flush() {
		$records = $this->records;
		$this->records = [];
		return $records;
	}
}
