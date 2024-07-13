<?php

namespace MediaWiki\Extension\AbuseFilter;

use MediaWiki\User\ActorMigrationBase;
use MediaWiki\User\ActorStoreFactory;

/**
 * Temporary class for actor migration
 */
class AbuseFilterActorMigration extends ActorMigrationBase {

	public const SERVICE_NAME = 'AbuseFilterActorMigration';

	/**
	 * @param int $stage
	 * @param ActorStoreFactory $actorStoreFactory
	 */
	public function __construct( $stage, ActorStoreFactory $actorStoreFactory ) {
		parent::__construct(
			[
				'af_user' => [],
				'afh_user' => [],
			],
			$stage,
			$actorStoreFactory,
			[ 'allowUnknown' => false ]
		);
	}

}
