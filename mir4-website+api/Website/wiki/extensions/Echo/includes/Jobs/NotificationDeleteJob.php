<?php

namespace MediaWiki\Extension\Notifications\Jobs;

use Job;
use JobQueueGroup;
use MediaWiki\Extension\Notifications\Mapper\NotificationMapper;
use MediaWiki\Extension\Notifications\NotifUser;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

/**
 * This job is created when sending notifications to the target users.  The purpose
 * of this job is to delete older notifications when the number of notifications a
 * user has is more than $wgEchoMaxUpdateCount, it does not make sense to have tons
 * of notifications in the history while users wouldn't bother to click 'load more'
 * like 100 times to see them. What we gain from this is we could run expensive
 * queries otherwise that would requires adding index and data denormalization.
 *
 * The initial job contains multiple users, which will in turn have individual jobs
 * queued for them.
 */
class NotificationDeleteJob extends Job {
	private JobQueueGroup $jobQueueGroup;

	/**
	 * @param Title $title
	 * @param array $params
	 * @param JobQueueGroup $jobQueueGroup
	 */
	public function __construct(
		Title $title,
		array $params,
		JobQueueGroup $jobQueueGroup
	) {
		parent::__construct( 'EchoNotificationDeleteJob', $title, $params );
		$this->jobQueueGroup = $jobQueueGroup;
	}

	/**
	 * Run the job of finding & deleting older notifications
	 * @return true
	 */
	public function run() {
		global $wgEchoMaxUpdateCount;
		if ( count( $this->params['userIds'] ) > 1 ) {
			// If there are multiple users, queue a single job for each one
			$jobs = [];
			foreach ( $this->params['userIds'] as $userId ) {
				$jobs[] = new NotificationDeleteJob(
					$this->title,
					[ 'userIds' => [ $userId ] ],
					$this->jobQueueGroup
				);
			}
			$this->jobQueueGroup->push( $jobs );

			return true;
		}

		$notifMapper = new NotificationMapper();

		// Back-compat for older jobs which used [ $userId => $userId ];
		$userIds = array_values( $this->params['userIds'] );
		$userId = $userIds[0];
		$user = User::newFromId( $userId );
		$notif = $notifMapper->fetchByUserOffset( $user, $wgEchoMaxUpdateCount );
		if ( $notif ) {
			$notifMapper->deleteByUserEventOffset(
				$user, $notif->getEvent()->getId()
			);
			$notifUser = NotifUser::newFromUser( $user );
			$notifUser->resetNotificationCount();
		}

		return true;
	}

}
