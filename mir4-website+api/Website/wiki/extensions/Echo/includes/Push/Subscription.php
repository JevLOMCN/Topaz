<?php

namespace MediaWiki\Extension\Notifications\Push;

use Wikimedia\Timestamp\ConvertibleTimestamp;

class Subscription {

	/** @var string */
	private $provider;

	/** @var string */
	private $token;

	/** @var ConvertibleTimestamp */
	private $updated;

	/** @var string|null */
	private $topic;

	/**
	 * Construct a subscription from a DB result row.
	 * @param \stdClass $row echo_push_subscription row from IResultWrapper::fetchRow
	 * @return Subscription
	 */
	public static function newFromRow( object $row ) {
		return new self(
			$row->epp_name,
			$row->eps_token,
			$row->ept_text,
			new ConvertibleTimestamp( $row->eps_updated )
		);
	}

	/**
	 * @param string $provider
	 * @param string $token
	 * @param string|null $topic
	 * @param ConvertibleTimestamp $updated
	 */
	public function __construct( string $provider, string $token, ?string $topic, ConvertibleTimestamp $updated ) {
		$this->provider = $provider;
		$this->token = $token;
		$this->topic = $topic;
		$this->updated = $updated;
	}

	/** @return string provider */
	public function getProvider(): string {
		return $this->provider;
	}

	/** @return string token */
	public function getToken(): string {
		return $this->token;
	}

	/** @return string|null topic */
	public function getTopic(): ?string {
		return $this->topic;
	}

	/** @return ConvertibleTimestamp last updated timestamp */
	public function getUpdated(): ConvertibleTimestamp {
		return $this->updated;
	}

}
