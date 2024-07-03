<?php

namespace MediaWiki\Extension\Notifications\Jobs;

use Job;
use MediaWiki\Extension\Notifications\Controller\NotificationController;
use MediaWiki\Extension\Notifications\Mapper\EventMapper;
use MediaWiki\Title\Title;

class NotificationJob extends Job {

	public function __construct( Title $title, array $params ) {
		$command = isset( $params['jobReleaseTimestamp'] ) ? 'DelayedEchoNotificationJob' : 'EchoNotificationJob';
		parent::__construct( $command, $title, $params );
	}

	public function run() {
		$eventMapper = new EventMapper();
		$event = $eventMapper->fetchById( $this->params['eventId'], true );
		NotificationController::notify( $event, false );

		return true;
	}
}
