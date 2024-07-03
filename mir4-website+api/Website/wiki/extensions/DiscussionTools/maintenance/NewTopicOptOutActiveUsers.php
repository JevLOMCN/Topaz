<?php

namespace MediaWiki\Extension\DiscussionTools\Maintenance;

use Maintenance;
use MediaWiki\Extension\DiscussionTools\Hooks\HookUtils;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserFactory;
use Wikimedia\Rdbms\IDatabase;

class NewTopicOptOutActiveUsers extends Maintenance {

	private IDatabase $dbw;
	private UserFactory $userFactory;

	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'DiscussionTools' );
		$this->addDescription( 'Opt out active users from the new topic tool' );
		$this->addOption( 'dry-run', 'Output information, do not save changes' );
		$this->addOption( 'save', 'Save the changes to the database' );
		$this->setBatchSize( 100 );
	}

	public function execute() {
		if ( $this->hasOption( 'dry-run' ) ) {
			$save = false;
			$this->output( "Dry run:\n" );
		} elseif ( $this->hasOption( 'save' ) ) {
			$save = true;
			$this->output( "CHANGING PREFERENCES!\n" );
			$this->countDown( 5 );
		} else {
			$this->error( "Please provide '--dry-run' or '--save' option" );
			return;
		}

		$this->dbw = $this->getDB( DB_PRIMARY );
		$this->userFactory = MediaWikiServices::getInstance()->getUserFactory();

		$userRows = $this->dbw->newSelectQueryBuilder()
			->caller( __METHOD__ )
			->table( 'querycachetwo' )
			->where( [
				'qcc_type' => 'activeusers',
				'qcc_namespace' => NS_USER,
			] )
			->join( 'user', null, 'qcc_title=user_name' )
			->where( [ 'user_editcount >= 100' ] )
			->fields( [ 'user_id', 'user_name' ] )
			->fetchResultSet();

		$count = count( $userRows );
		$countUpdated = 0;
		$this->output( "Found $count active users with enough edits\n" );

		foreach ( $userRows as $i => $row ) {
			$skipReason = $this->skipReason( $row->user_id );
			if ( $skipReason ) {
				$this->output( "Won't update '$row->user_name' because: $skipReason\n" );
			} else {
				$this->output( "Will update '$row->user_name'\n" );
				$countUpdated++;
				if ( $save ) {
					$this->updatePrefs( $row->user_id );
					if ( $countUpdated % $this->getBatchSize() === 0 ) {
						$this->waitForReplication();
					}
				}
			}
		}

		if ( $save ) {
			$this->output( "Updated $countUpdated out of $count users\n" );
		} else {
			$this->output( "Would update $countUpdated out of $count users\n" );
		}
	}

	private function skipReason( int $userId ): ?string {
		// We can't use UserOptionsLookup here, because we're not interested in the default options,
		// but only in the options actually stored in the database.

		// We're not looking at global preferences, because if the user has set them, then they will
		// override our local preferences anyway.

		// Check that the user has not already set their preference for new topic tool to any value
		$foundRow = $this->dbw->newSelectQueryBuilder()
			->caller( __METHOD__ )
			->table( 'user_properties' )
			->where( [ 'up_user' => $userId, 'up_property' => 'discussiontools-' . HookUtils::NEWTOPICTOOL ] )
			->field( '1' )
			->fetchField();
		if ( $foundRow ) {
			return HookUtils::NEWTOPICTOOL;
		}

		// Check that the user has not already opted into the beta feature
		$foundRow = $this->dbw->newSelectQueryBuilder()
			->caller( __METHOD__ )
			->table( 'user_properties' )
			->where( [
				'up_user' => $userId,
				'up_property' => 'discussiontools-betaenable',
				'up_value' => 1,
			] )
			->field( '1' )
			->fetchField();
		if ( $foundRow ) {
			return 'betaenable';
		}

		// Skip accounts that shouldn't have non-default preferences
		$user = $this->userFactory->newFromId( $userId );
		if ( $user->isSystemUser() ) {
			return 'system';
		}
		if ( $user->isBot() ) {
			return 'bot';
		}
		if ( $user->isTemp() ) {
			return 'temp';
		}

		return null;
	}

	private function updatePrefs( int $userId ): void {
		// We can't use UserOptionsManager here, because we want to store the preference
		// in the database even if it's identical to the current default
		// (this script is only used when we're about to change the default).
		$this->dbw->newInsertQueryBuilder()
			->table( 'user_properties' )
			->row( [
				'up_user' => $userId,
				'up_property' => 'discussiontools-' . HookUtils::NEWTOPICTOOL,
				'up_value' => 0,
			] )
			->caller( __METHOD__ )
			->execute();
	}

}

$maintClass = NewTopicOptOutActiveUsers::class;
