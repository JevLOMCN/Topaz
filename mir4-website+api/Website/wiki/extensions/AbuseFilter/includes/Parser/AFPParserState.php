<?php

namespace MediaWiki\Extension\AbuseFilter\Parser;

class AFPParserState {
	/** @var AFPToken */
	public $token;
	/** @var int */
	public $pos;

	/**
	 * @param AFPToken $token
	 * @param int $pos
	 */
	public function __construct( AFPToken $token, $pos ) {
		$this->token = $token;
		$this->pos = $pos;
	}
}
