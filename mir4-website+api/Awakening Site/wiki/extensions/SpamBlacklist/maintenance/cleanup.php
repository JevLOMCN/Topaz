<?php
/**
 * An aggressive spam cleanup script.
 * Searches the database for matching pages, and reverts them to
 * the last non-spammed revision.
 * If all revisions contain spam, blanks the page
 */

use MediaWiki\Extension\SpamBlacklist\BaseBlacklist;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFormatter;
use MediaWiki\User\User;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class Cleanup extends Maintenance {
	/** @var RevisionLookup */
	private $revisionLookup;
	/** @var TitleFormatter */
	private $titleFormatter;
	/** @var WikiPageFactory */
	private $wikiPageFactory;

	public function __construct() {
		parent::__construct();
		$this->revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
		$this->titleFormatter = MediaWikiServices::getInstance()->getTitleFormatter();
		$this->wikiPageFactory = MediaWikiServices::getInstance()->getWikiPageFactory();

		$this->requireExtension( 'SpamBlacklist' );
		$this->addOption( 'dry-run', 'Only do a dry run' );
	}

	public function execute() {
		$user = User::newSystemUser( 'Spam cleanup script', [ 'steal' => true ] );

		$sb = BaseBlacklist::getSpamBlacklist();
		$regexes = $sb->getBlacklists();
		if ( !$regexes ) {
			$this->fatalError( "Invalid regex, can't clean up spam" );
		}
		$dryRun = $this->hasOption( 'dry-run' );

		$dbr = $this->getReplicaDB();
		$maxID = (int)$dbr->selectField( 'page', 'MAX(page_id)', [], __METHOD__ );
		$reportingInterval = 100;

		$this->output( "Regexes are " . implode( ', ', array_map( 'strlen', $regexes ) ) . " bytes\n" );
		$this->output( "Searching for spam in $maxID pages...\n" );
		if ( $dryRun ) {
			$this->output( "Dry run only\n" );
		}

		for ( $id = 1; $id <= $maxID; $id++ ) {
			if ( $id % $reportingInterval == 0 ) {
				printf( "%-8d  %-5.2f%%\r", $id, $id / $maxID * 100 );
			}
			$revision = $this->revisionLookup->getRevisionByPageId( $id );
			if ( $revision ) {
				$content = $revision->getContent( SlotRecord::MAIN );
				$text = ( $content instanceof TextContent ) ? $content->getText() : null;
				if ( $text ) {
					foreach ( $regexes as $regex ) {
						if ( preg_match( $regex, $text, $matches ) ) {
							$titleText = $this->titleFormatter->getPrefixedText( $revision->getPageAsLinkTarget() );
							if ( $dryRun ) {
								$this->output( "Found spam in [[$titleText]]\n" );
							} else {
								$this->output( "Cleaning up links to {$matches[0]} in [[$titleText]]\n" );
								$match = str_replace( 'http://', '', $matches[0] );
								$this->cleanupArticle( $revision, $regexes, $match, $user );
							}
						}
					}
				}
			}
		}
		// Just for satisfaction
		printf( "%-8d  %-5.2f%%\n", $id - 1, ( $id - 1 ) / $maxID * 100 );
	}

	/**
	 * Find the latest revision of the article that does not contain spam and revert to it
	 * @param RevisionRecord $rev
	 * @param array $regexes
	 * @param string $match
	 * @param User $user
	 */
	private function cleanupArticle( RevisionRecord $rev, $regexes, $match, User $user ) {
		$title = Title::newFromLinkTarget( $rev->getPageAsLinkTarget() );
		while ( $rev ) {
			$matches = false;
			$content = $rev->getContent( SlotRecord::MAIN );
			foreach ( $regexes as $regex ) {
				$matches = $matches
					|| preg_match(
						$regex,
						( $content instanceof TextContent ) ? $content->getText() : null
					);
			}
			if ( !$matches ) {
				// Didn't find any spam
				break;
			}

			$rev = $this->revisionLookup->getPreviousRevision( $rev );
		}
		if ( !$rev ) {
			// Didn't find a non-spammy revision, blank the page
			$this->output( "All revisions are spam, blanking...\n" );
			$content = ContentHandler::makeContent( '', $title );
			$comment = "All revisions matched the spam blacklist ($match), blanking";
		} else {
			// Revert to this revision
			$content = $rev->getContent( SlotRecord::MAIN ) ?:
				ContentHandler::makeContent( '', $title );
			$comment = "Cleaning up links to $match";
		}
		$wikiPage = $this->wikiPageFactory->newFromTitle( $title );
		$wikiPage->doUserEditContent( $content, $user, $comment );
	}
}

$maintClass = Cleanup::class;
require_once RUN_MAINTENANCE_IF_MAIN;
