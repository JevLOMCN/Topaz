<?php
// phpcs:disable Generic.Files.LineLength -- Long html test examples
// @phan-file-suppress PhanUndeclaredClassMethod, PhanUndeclaredClassConstant Other extensions used for testing purposes

use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * A maintenance script that generates sample notifications for testing purposes.
 */
class GenerateSampleNotifications extends Maintenance {

	/** @var string[] */
	private $supportedNotificationTypes = [
		'welcome',
		'edit-user-talk',
		'mention',
		'page-linked',
		'reverted',
		'email',
		'user-rights',
		'cx',
		'osm',
		'edit-thanks',
		'edu',
		'page-connection',
	];

	/** @var int */
	private $timestampCounter = 5;

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Generate sample notifications" );

		$this->addOption(
			'force',
			'Bypass confirmation',
			false, false, 'f' );

		$allTypes = implode( ',', $this->supportedNotificationTypes );
		$this->addOption(
			'types',
			"Comma-separated list of notification types to generate ($allTypes)",
			false, true, 't' );

		$this->addOption(
			'user',
			'Name of the user receiving the notifications',
			true, true, 'u' );

		$this->addOption(
			'agent',
			'Name of the user creating the notifications',
			true, true, 'a' );

		$this->addOption(
			'other',
			'Name of another user involved with the notifications',
			true, true, 'o' );

		$this->requireExtension( 'Echo' );

		$this->addOption(
			'timestamp',
			'Add notification timestamps (Epoch time format). All notifications that are not ' .
				'related directly to edits will be created with a timestamp starting 5 minutes ' .
				'before the given timestamp, and increasing by 1 minute per notification.',
			false, false, 'k' );
	}

	public function execute() {
		$user = $this->getOptionUser( 'user' );
		$agent = $this->getOptionUser( 'agent' );
		$otherUser = $this->getOptionUser( 'other' );
		$title = Title::newFromText( 'This is a pretty long page title lets see if it is going to be truncated' );

		$types = $this->getOption( 'types' );
		if ( $types ) {
			$types = explode( ',', $types );
		} else {
			$types = $this->supportedNotificationTypes;
		}

		$this->confirm();

		$this->output( "Started processing...\n" );

		if ( $this->shouldGenerate( 'welcome', $types ) ) {
			$this->generateWelcome( $user );
		}

		if ( $this->shouldGenerate( 'edit-user-talk', $types ) ) {
			$this->generateEditUserTalk( $user, $agent );
		}

		if ( $this->shouldGenerate( 'mention', $types ) ) {
			$this->generateMention( $user, $agent, $otherUser, $title );
		}

		if ( $this->shouldGenerate( 'page-linked', $types ) ) {
			$this->generatePageLink( $user, $agent );
		}

		if ( $this->shouldGenerate( 'reverted', $types ) ) {
			$this->generateReverted( $user, $agent );
		}

		if ( $this->shouldGenerate( 'email', $types ) ) {
			$this->generateEmail( $user, $agent );
		}

		if ( $this->shouldGenerate( 'user-rights', $types ) ) {
			$this->generateUserRights( $user, $agent );
		}

		if ( $this->shouldGenerate( 'cx', $types ) ) {
			$this->generateContentTranslation( $user );
		}

		if ( $this->shouldGenerate( 'osm', $types ) ) {
			$this->generateOpenStackManager( $user, $agent );
		}

		if ( $this->shouldGenerate( 'edit-thanks', $types ) ) {
			$this->generateEditThanks( $user, $agent, $otherUser );
		}

		if ( $this->shouldGenerate( 'edu', $types ) ) {
			$this->generateEducationProgram( $user, $agent );
		}

		if ( $this->shouldGenerate( 'page-connection', $types ) ) {
			$this->generateWikibase( $user, $agent );
		}

		$this->output( "Completed \n" );
	}

	/**
	 * Get the set timestamp of the event
	 *
	 * @param bool $getEpoch Get the epoch value
	 * @return int Timestamp for the operation
	 */
	private function getTimestamp( $getEpoch = false ) {
		$startTime = $this->getOption( 'timestamp' ) ?: time();

		// Incrementally decrease X minutes from start time
		$timestamp = strtotime( '-' . $this->timestampCounter++ . ' minute', $startTime );

		return $getEpoch ? $timestamp : (int)wfTimestamp( TS_UNIX, $timestamp );
	}

	/**
	 * Add a timestamp string to the output, if a timestamp option was given,
	 * to note the time of the new generated event.
	 *
	 * @param string $output New output message with timestamp
	 * @return string
	 */
	private function addTimestampToOutput( $output ) {
		if ( $this->getOption( 'timestamp' ) ) {
			$output .= ' (Using timestamp: ' . date( 'Y-m-d H:i:s', $this->getTimestamp( true ) ) . ')';
		}
		return $output;
	}

	private function generateEditUserTalk( User $user, User $agent ) {
		$this->output( "{$agent->getName()} is writing on {$user->getName()}'s user talk page\n" );
		$editId = $this->generateRandomString();
		$section = "== section $editId ==\n\nthis is the text $editId\n\n~~~~\n\n";
		$this->addToUserTalk( $user, $agent, $section );
	}

	private function getOptionUser( $optionName ) {
		$username = $this->getOption( $optionName );
		$user = User::newFromName( $username );
		if ( !$user->isRegistered() ) {
			$this->fatalError( "User $username does not seem to exist in this wiki" );
		}
		return $user;
	}

	private function generateRandomString( $length = 10 ) {
		return substr( str_shuffle( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ), 0, $length );
	}

	protected function confirm() {
		if ( $this->getOption( 'force', false ) ) {
			return;
		}
		$this->output( "===   WARNING   ===\n" );
		$this->output( "This script modifies the content of several pages,\n" );
		$this->output( "including user's talk pages.\n" );
		$this->output( "ONLY RUN ON TEST WIKIS\n" );
		$this->output( "Enter 'yes' if you wish to continue or any other key to exit\n" );
		$confirm = $this->readconsole();
		if ( $confirm !== 'yes' ) {
			$this->fatalError( 'Safe decision' );
		}
	}

	private function addToUserTalk( User $user, User $agent, $contentText ) {
		$this->addToPageContent( $user->getTalkPage(), $agent, $contentText );
	}

	private function addToPageContent( Title $title, User $agent, $contentText ) {
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$previousContent = "";
		$page->loadPageData( IDBAccessObject::READ_LATEST );
		$revision = $page->getRevisionRecord();
		if ( $revision ) {
			$content = $revision->getContent( SlotRecord::MAIN, RevisionRecord::FOR_PUBLIC );
			if ( $content instanceof WikitextContent ) {
				$previousContent = $content->getText();
			}
		}
		$status = $page->doUserEditContent(
			new WikitextContent( $contentText . $previousContent ),
			$agent,
			'generating sample notifications'
		);

		if ( !$status->isGood() ) {
			$this->error( "Failed to edit {$title->getPrefixedText()}: {$status->getMessage()->text()}" );
		}

		return $status->getValue()['revision-record'];
	}

	private function generateMention( User $user, User $agent, User $otherUser, Title $title ) {
		$mention = "== section {$this->generateRandomString()} ==\nHello [[User:{$user->getName()}]] \n~~~~\n";

		// article talk
		$this->output( "{$agent->getName()} is mentioning {$user->getName()} on {$title->getTalkPage()->getPrefixedText()}\n" );
		$this->addToPageContent( $title->getTalkPage(), $agent, $mention );

		// agent talk
		$this->output( "{$agent->getName()} is mentioning {$user->getName()} on {$agent->getTalkPage()->getPrefixedText()}\n" );
		$this->addToPageContent( $agent->getTalkPage(), $agent, $mention );

		// user talk
		$this->output( "{$agent->getName()} is mentioning {$user->getName()} on {$otherUser->getTalkPage()->getPrefixedText()}\n" );
		$this->addToPageContent( $otherUser->getTalkPage(), $agent, $mention );

		// any other page
		$this->output( "{$agent->getName()} is mentioning {$user->getName()} on {$title->getPrefixedText()}\n" );
		$this->addToPageContent( $title, $agent, $mention );
	}

	private function generatePageLink( User $user, User $agent ) {
		$this->generateOnePageLink( $user, $agent );
		$this->generateMultiplePageLinks( $user, $agent );
	}

	private function generateNewPageTitle() {
		return Title::newFromText( $this->generateRandomString() );
	}

	private function generateReverted( User $user, User $agent ) {
		$services = $this->getServiceContainer();
		$services->getUserGroupManager()->addUserToGroup( $agent, 'sysop' );

		// revert (undo)
		$moai = Title::newFromText( 'Moai' );
		$page = $services->getWikiPageFactory()->newFromTitle( $moai );
		$this->output( "{$agent->getName()} is reverting {$user->getName()}'s edit on {$moai->getPrefixedText()}\n" );
		$this->addToPageContent( $moai, $agent, "\ncreating a good revision here\n" );
		$this->addToPageContent( $moai, $user, "\nadding a line here\n" );

		$undoRev = $page->getRevisionRecord();
		$previous = $services->getRevisionLookup()
			->getPreviousRevision( $undoRev );

		$handler = $services->getContentHandlerFactory()
			->getContentHandler(
				$undoRev->getSlot( SlotRecord::MAIN, RevisionRecord::RAW )
					->getModel()
			);
		$undoContent = $undoRev->getContent( SlotRecord::MAIN );
		$previousContent = $previous->getContent( SlotRecord::MAIN );

		if ( !$undoContent ) {
			$this->error( "Failed to undo {$moai->getPrefixedText()}: undoContent is null." );
			return;
		}

		if ( !$previousContent ) {
			$this->error( "Failed to undo {$moai->getPrefixedText()}: previousContent is null." );
			return;
		}

		$content = $handler->getUndoContent(
			$undoContent,
			$undoContent,
			$previousContent,
			// undoIsLatest
			true
		);

		$status = $page->doUserEditContent(
			$content,
			$agent,
			'undo',
			// $flags
			0,
			// $originalRevId
			false,
			// $tags
			[],
			$undoRev->getId()
		);

		if ( !$status->isGood() ) {
			$this->error( "Failed to undo {$moai->getPrefixedText()}: {$status->getMessage()->text()}" );
		}
	}

	private function generateWelcome( User $user ) {
		$this->output( "Welcoming {$user->getName()}\n" );
		Event::create( [
			'type' => 'welcome',
			'agent' => $user,
			'timestamp' => $this->getTimestamp(),
		] );
	}

	private function generateEmail( User $user, User $agent ) {
		$output = $this->addTimestampToOutput( "{$agent->getName()} is emailing {$user->getName()}" );
		$this->output( "$output\n" );
		Event::create( [
			'type' => 'emailuser',
			'extra' => [
				'to-user-id' => $user->getId(),
				'subject' => 'Long time no see',
			],
			'agent' => $agent,
			'timestamp' => $this->getTimestamp(),
		] );
	}

	private function generateUserRights( User $user, User $agent ) {
		$output = $this->addTimestampToOutput( "{$agent->getName()} is changing {$user->getName()}'s rights" );
		$this->output( "$output\n" );
		$this->createUserRightsNotification( $user, $agent, [ 'OnlyAdd-1' ], null );
		$this->createUserRightsNotification( $user, $agent, null, [ 'JustRemove-1', 'JustRemove-2' ] );
		$this->createUserRightsNotification( $user, $agent, [ 'Add-1', 'Add-2' ], [ 'Remove-1', 'Remove-2' ] );
	}

	private function createUserRightsNotification( User $user, User $agent, $add, $remove ) {
		Event::create(
			[
				'type' => 'user-rights',
				'extra' => [
					'user' => $user->getId(),
					'add' => $add,
					'remove' => $remove,
					'reason' => 'This is the [[reason]] for changing your user rights.',
				],
				'agent' => $agent,
				'timestamp' => $this->getTimestamp(),
			]
		);
	}

	private function generateContentTranslation( User $user ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'ContentTranslation' ) ) {
			return;
		}

		$this->output( "Generating CX notifications\n" );
		foreach ( [ 'cx-first-translation', 'cx-tenth-translation', 'cx-hundredth-translation' ] as $eventType ) {
			Event::create(
				[
					'type' => $eventType,
					'extra' => [
						'recipient' => $user->getId(),
					],
					'timestamp' => $this->getTimestamp(),
				]
			);
		}

		Event::create(
			[
				'type' => 'cx-suggestions-available',
				'extra' => [
					'recipient' => $user->getId(),
					'lastTranslationTitle' => 'History of the People\'s Republic of China'
				],
				'timestamp' => $this->getTimestamp(),
			]
		);
	}

	private function generateOnePageLink( User $user, User $agent ) {
		$pageBeingLinked = $this->generateNewPageTitle();
		$this->addToPageContent( $pageBeingLinked, $user, "this is a new page" );

		$pageLinking = $this->generateNewPageTitle();
		$content = "checkout [[{$pageBeingLinked->getPrefixedText()}]]!";
		$this->output( "{$agent->getName()} is linking to {$pageBeingLinked->getPrefixedText()} from {$pageLinking->getPrefixedText()}\n" );
		$this->addToPageContent( $pageLinking, $agent, $content );
	}

	private function generateMultiplePageLinks( User $user, User $agent ) {
		$pageBeingLinked = $this->generateNewPageTitle();
		$this->addToPageContent( $pageBeingLinked, $user, "this is a new page" );

		$content = "checkout [[{$pageBeingLinked->getPrefixedText()}]]!";
		$this->output( "{$agent->getName()} is linking to {$pageBeingLinked->getPrefixedText()} from multiple pages\n" );
		$this->addToPageContent( $this->generateNewPageTitle(), $agent, $content );
		// @phan-suppress-next-line PhanPluginDuplicateAdjacentStatement
		$this->addToPageContent( $this->generateNewPageTitle(), $agent, $content );
		// @phan-suppress-next-line PhanPluginDuplicateAdjacentStatement
		$this->addToPageContent( $this->generateNewPageTitle(), $agent, $content );
	}

	private function generateOpenStackManager( User $user, User $agent ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'OpenStackManager' ) ) {
			return;
		}

		$this->output( "Generating OpenStackManager notifications\n" );

		foreach ( [ 'build-completed', 'reboot-completed', 'deleted' ] as $action ) {
			Event::create( [
				'type' => "osm-instance-$action",
				'title' => Title::newFromText( "Moai" ),
				'agent' => $user,
				'extra' => [
					'instanceName' => 'instance1',
					'projectName' => 'TheProject',
					'notifyAgent' => true,
				],
				'timestamp' => $this->getTimestamp(),
			] );
		}

		Event::create( [
			'type' => 'osm-projectmembers-add',
			'title' => Title::newFromText( "Moai" ),
			'agent' => $agent,
			'extra' => [ 'userAdded' => $user->getId() ],
			'timestamp' => $this->getTimestamp(),
		] );
	}

	private function shouldGenerate( $type, array $types ) {
		return in_array( $type, $types );
	}

	private function generateEditThanks( User $user, User $agent, User $otherUser ) {
		$this->generateOneEditThanks( $user, $agent );
		$this->generateMultipleEditThanks( $user, $agent, $otherUser );
	}

	private function generateOneEditThanks( User $user, User $agent ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Thanks' ) ) {
			return;
		}
		// make an edit, thank it once
		$title = $this->generateNewPageTitle();
		$revisionRecord = $this->addToPageContent( $title, $user, "an awesome edit! ~~~~" );
		Event::create( [
			'type' => 'edit-thank',
			'title' => $title,
			'extra' => [
				'revid' => $revisionRecord->getId(),
				'thanked-user-id' => $user->getId(),
				'source' => 'generateSampleNotifications.php',
			],
			'agent' => $agent,
			'timestamp' => $this->getTimestamp(),
		] );
		$output = $this->addTimestampToOutput( "{$agent->getName()} is thanking {$user->getName()} for edit {$revisionRecord->getId()} on {$title->getPrefixedText()}" );
		$this->output( "$output\n" );
	}

	private function generateMultipleEditThanks( User $user, User $agent, User $otherUser ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Thanks' ) ) {
			return;
		}
		// make an edit, thank it twice
		$title = $this->generateNewPageTitle();
		$revisionRecord = $this->addToPageContent( $title, $user, "an even better edit! ~~~~" );
		Event::create( [
			'type' => 'edit-thank',
			'title' => $title,
			'extra' => [
				'revid' => $revisionRecord->getId(),
				'thanked-user-id' => $user->getId(),
				'source' => 'generateSampleNotifications.php',
			],
			'agent' => $agent,
			'timestamp' => $this->getTimestamp(),
		] );
		Event::create( [
			'type' => 'edit-thank',
			'title' => $title,
			'extra' => [
				'revid' => $revisionRecord->getId(),
				'thanked-user-id' => $user->getId(),
				'source' => 'generateSampleNotifications.php',
			],
			'agent' => $otherUser,
			'timestamp' => $this->getTimestamp(),
		] );
		$output = $this->addTimestampToOutput( "{$agent->getName()} and {$otherUser->getName()} are thanking {$user->getName()} for edit {$revisionRecord->getId()} on {$title->getPrefixedText()}" );
		$this->output( "$output\n" );
	}

	private function generateEducationProgram( User $user, User $agent ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'EducationProgram' ) ) {
			$this->output( "Skipping EducationProgram. Extension not installed.\n" );
			return;
		}

		$chem101 = Title::newFromText( 'School/Chemistry101' );
		if ( !$chem101->exists() ) {
			$this->addToPageContent( $chem101, $agent, "\nThis is the main page for the Chemistry 101 course\n" );
		}

		$notificationManager = EducationProgram\Extension::globalInstance()->getNotificationsManager();

		$output = $this->addTimestampToOutput( "{$agent->getName()} is adding {$user->getName()} to {$chem101->getPrefixedText()} as instructor, student, campus volunteer and online volunteer" );
		$this->output( "$output\n" );

		$types = [
			'ep-instructor-add-notification',
			'ep-online-add-notification',
			'ep-campus-add-notification',
			'ep-student-add-notification',
		];
		foreach ( $types as $type ) {
			$notificationManager->trigger(
				$type,
				[
					'role-add-title' => $chem101,
					'agent' => $agent,
					'users' => [ $user->getId() ],
				]
			);
		}

		// NOTE: Not generating 'ep-course-talk-notification' for now
		// as it requires a full setup to actually work (institution, course, instructors, students).
	}

	private function generateWikibase( User $user, User $agent ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseClient' ) ) {
			$this->output( "Skipping Wikibase. Extension not installed.\n" );
			return;
		}

		$title = $this->generateNewPageTitle();
		$this->addToPageContent( $title, $user, "this is a new page" );
		$helpPage = Title::newFromText( 'Project:Wikidata' );
		$this->addToPageContent( $helpPage, $user, "this is the help page" );

		$output = $this->addTimestampToOutput( "{$agent->getName()} is connecting {$user->getName()}'s page {$title->getPrefixedText()} to an item" );
		$this->output( "$output\n" );
		Event::create( [
			'type' => EchoNotificationsHandlers::NOTIFICATION_TYPE,
			'title' => $title,
			'extra' => [
				'url' => Title::newFromText( 'Item:Q1' )->getFullURL(),
				'repoSiteName' => 'Wikidata',
				'entity' => 'Q1',
			],
			'agent' => $agent,
			'timestamp' => $this->getTimestamp(),
		] );
	}
}

$maintClass = GenerateSampleNotifications::class;
require_once RUN_MAINTENANCE_IF_MAIN;
