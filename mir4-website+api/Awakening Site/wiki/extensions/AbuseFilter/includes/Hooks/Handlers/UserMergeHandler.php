<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks\Handlers;

use MediaWiki\Config\Config;
use MediaWiki\Extension\UserMerge\Hooks\AccountFieldsHook;

class UserMergeHandler implements AccountFieldsHook {

	private Config $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * Tables that Extension:UserMerge needs to update
	 *
	 * @param array[] &$updateFields
	 */
	public function onUserMergeAccountFields( array &$updateFields ) {
		$actorStage = $this->config->get( 'AbuseFilterActorTableSchemaMigrationStage' );
		$updateFields[] = [
			'abuse_filter',
			'af_user',
			'af_user_text',
			'batchKey' => 'af_id',
			'actorId' => 'af_actor',
			'actorStage' => $actorStage,
		];
		$updateFields[] = [
			'abuse_filter_log',
			'afl_user',
			'afl_user_text',
			'batchKey' => 'afl_id',
		];
		$updateFields[] = [
			'abuse_filter_history',
			'afh_user',
			'afh_user_text',
			'batchKey' => 'afh_id',
			'actorId' => 'afh_actor',
			'actorStage' => $actorStage,
		];
	}

}
