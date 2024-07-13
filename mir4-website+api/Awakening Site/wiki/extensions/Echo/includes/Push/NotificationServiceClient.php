<?php

namespace MediaWiki\Extension\Notifications\Push;

use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Status\Status;
use MWHttpRequest;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class NotificationServiceClient implements LoggerAwareInterface {

	use LoggerAwareTrait;

	/** @var HttpRequestFactory */
	private $httpRequestFactory;

	/** @var string */
	private $endpointBase;

	/**
	 * @param HttpRequestFactory $httpRequestFactory
	 * @param string $endpointBase push service notification request endpoint base URL
	 */
	public function __construct( HttpRequestFactory $httpRequestFactory, string $endpointBase ) {
		$this->httpRequestFactory = $httpRequestFactory;
		$this->endpointBase = $endpointBase;
	}

	/**
	 * Send a CHECK_ECHO notification request to the push service for each subscription found.
	 * TODO: Update the service to handle multiple providers in a single request (T254379)
	 * @param array $subscriptions Subscriptions for which to send the message
	 */
	public function sendCheckEchoRequests( array $subscriptions ): void {
		$tokenMap = [];
		foreach ( $subscriptions as $subscription ) {
			$provider = $subscription->getProvider();
			$topic = $subscription->getTopic() ?? 0;
			if ( !isset( $tokenMap[$topic][$provider] ) ) {
				$tokenMap[$topic][$provider] = [];
			}

			$tokenMap[$topic][$provider][] = $subscription->getToken();
		}
		foreach ( $tokenMap as $topic => $providerMap ) {
			foreach ( $providerMap as $provider => $tokens ) {
				$payload = [
					'deviceTokens' => $tokens,
					'messageType' => 'checkEchoV1'
				];
				if ( $topic !== 0 ) {
					$payload['topic'] = $topic;
				}
				$this->sendRequest( $provider, $payload );
			}
		}
	}

	/**
	 * Send a notification request for a single push provider
	 * @param string $provider Provider endpoint to which to send the message
	 * @param array $payload message payload
	 */
	protected function sendRequest( string $provider, array $payload ): void {
		$request = $this->constructRequest( $provider, $payload );
		$status = $request->execute();
		if ( !$status->isOK() ) {
			$errors = $status->getErrorsByType( 'error' );
			$this->logger->warning(
				serialize( Status::wrap( $status )->getMessage( false, false, 'en' ) ),
				[
					'error' => $errors,
					'caller' => __METHOD__,
					'content' => $request->getContent()
				]
			);
		}
	}

	/**
	 * Construct a MWHttpRequest object based on the subscription and payload.
	 * @param string $provider
	 * @param array $payload
	 * @return MWHttpRequest
	 */
	private function constructRequest( string $provider, array $payload ): MWHttpRequest {
		$url = "$this->endpointBase/$provider";
		$opts = [ 'method' => 'POST', 'postData' => json_encode( $payload ) ];
		$req = $this->httpRequestFactory->create( $url, $opts );
		$req->setHeader( 'Content-Type', 'application/json; charset=utf-8' );
		return $req;
	}

}
