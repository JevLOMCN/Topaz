<?php

namespace MediaWiki\Extension\AbuseFilter\VariableGenerator;

use Content;
use LogicException;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Extension\AbuseFilter\TextExtractor;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MimeAnalyzer;
use MWFileProps;
use UploadBase;
use Wikimedia\Assert\PreconditionException;
use WikiPage;

/**
 * This class contains the logic used to create variable holders before filtering
 * an action.
 */
class RunVariableGenerator extends VariableGenerator {
	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var Title
	 */
	private $title;

	/** @var TextExtractor */
	private $textExtractor;
	/** @var MimeAnalyzer */
	private $mimeAnalyzer;
	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/**
	 * @param AbuseFilterHookRunner $hookRunner
	 * @param UserFactory $userFactory
	 * @param TextExtractor $textExtractor
	 * @param MimeAnalyzer $mimeAnalyzer
	 * @param WikiPageFactory $wikiPageFactory
	 * @param User $user
	 * @param Title $title
	 * @param VariableHolder|null $vars
	 */
	public function __construct(
		AbuseFilterHookRunner $hookRunner,
		UserFactory $userFactory,
		TextExtractor $textExtractor,
		MimeAnalyzer $mimeAnalyzer,
		WikiPageFactory $wikiPageFactory,
		User $user,
		Title $title,
		VariableHolder $vars = null
	) {
		parent::__construct( $hookRunner, $userFactory, $vars );
		$this->textExtractor = $textExtractor;
		$this->mimeAnalyzer = $mimeAnalyzer;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->user = $user;
		$this->title = $title;
	}

	/**
	 * Get variables for pre-filtering an edit during stash
	 *
	 * @param Content $content
	 * @param string $summary
	 * @param string $slot
	 * @param WikiPage $page
	 * @return VariableHolder|null
	 */
	public function getStashEditVars(
		Content $content,
		string $summary,
		$slot,
		WikiPage $page
	): ?VariableHolder {
		$filterText = $this->getEditTextForFiltering( $page, $content, $slot );
		if ( $filterText === null ) {
			return null;
		}
		[ $oldContent, $oldAfText, $text ] = $filterText;
		return $this->newVariableHolderForEdit(
			$page, $summary, $content, $text, $oldAfText, $oldContent
		);
	}

	/**
	 * Get the text of an edit to be used for filtering
	 * @todo Full support for multi-slots
	 *
	 * @param WikiPage $page
	 * @param Content $content
	 * @param string $slot
	 * @return array|null
	 */
	private function getEditTextForFiltering( WikiPage $page, Content $content, $slot ): ?array {
		$oldRevRecord = $page->getRevisionRecord();
		if ( !$oldRevRecord ) {
			return null;
		}

		$oldContent = $oldRevRecord->getContent( SlotRecord::MAIN, RevisionRecord::RAW );
		if ( !$oldContent ) {
			// @codeCoverageIgnoreStart
			throw new LogicException( 'Content cannot be null' );
			// @codeCoverageIgnoreEnd
		}
		$oldAfText = $this->textExtractor->revisionToString( $oldRevRecord, $this->user );

		// XXX: Recreate what the new revision will probably be so we can get the full AF
		// text for all slots
		$newRevision = MutableRevisionRecord::newFromParentRevision( $oldRevRecord );
		$newRevision->setContent( $slot, $content );
		$text = $this->textExtractor->revisionToString( $newRevision, $this->user );

		// Don't trigger for null edits. Compare Content objects if available, but check the
		// stringified contents as well, e.g. for line endings normalization (T240115).
		// Don't treat content model change as null edit though.
		if (
			$content->equals( $oldContent ) ||
			( $oldContent->getModel() === $content->getModel() && strcmp( $oldAfText, $text ) === 0 )
		) {
			return null;
		}

		return [ $oldContent, $oldAfText, $text ];
	}

	/**
	 * @param WikiPage $page
	 * @param string $summary
	 * @param Content $newcontent
	 * @param string $text
	 * @param string $oldtext
	 * @param Content|null $oldcontent
	 * @return VariableHolder
	 */
	private function newVariableHolderForEdit(
		WikiPage $page,
		string $summary,
		Content $newcontent,
		string $text,
		string $oldtext,
		Content $oldcontent = null
	): VariableHolder {
		$this->addUserVars( $this->user )
			->addTitleVars( $this->title, 'page' );
		$this->vars->setVar( 'action', 'edit' );
		$this->vars->setVar( 'summary', $summary );
		if ( $oldcontent instanceof Content ) {
			$oldmodel = $oldcontent->getModel();
		} else {
			$oldmodel = '';
			$oldtext = '';
		}
		$this->vars->setVar( 'old_content_model', $oldmodel );
		$this->vars->setVar( 'new_content_model', $newcontent->getModel() );
		$this->vars->setVar( 'old_wikitext', $oldtext );
		$this->vars->setVar( 'new_wikitext', $text );

		try {
			$update = $page->getCurrentUpdate();
			$update->getParserOutputForMetaData();
		} catch ( PreconditionException | LogicException $exception ) {
			// Temporary workaround until this becomes
			// a hook parameter
			$update = null;
		}
		$this->addEditVars( $page, $this->user, true, $update );

		return $this->vars;
	}

	/**
	 * Get variables for filtering an edit.
	 *
	 * @param Content $content
	 * @param string $summary
	 * @param string $slot
	 * @param WikiPage $page
	 * @return VariableHolder|null
	 */
	public function getEditVars(
		Content $content,
		string $summary,
		$slot,
		WikiPage $page
	): ?VariableHolder {
		if ( $this->title->exists() ) {
			$filterText = $this->getEditTextForFiltering( $page, $content, $slot );
			if ( $filterText === null ) {
				return null;
			}
			[ $oldContent, $oldAfText, $text ] = $filterText;
		} else {
			// Optimization
			$oldContent = null;
			$oldAfText = '';
			$text = $this->textExtractor->contentToString( $content );
		}

		return $this->newVariableHolderForEdit(
			$page, $summary, $content, $text, $oldAfText, $oldContent
		);
	}

	/**
	 * Get variables used to filter a move.
	 *
	 * @param Title $newTitle
	 * @param string $reason
	 * @return VariableHolder
	 */
	public function getMoveVars(
		Title $newTitle,
		string $reason
	): VariableHolder {
		$this->addUserVars( $this->user )
			->addTitleVars( $this->title, 'MOVED_FROM' )
			->addTitleVars( $newTitle, 'MOVED_TO' );
		$this->vars->setVar( 'summary', $reason );
		$this->vars->setVar( 'action', 'move' );
		return $this->vars;
	}

	/**
	 * Get variables for filtering a deletion.
	 *
	 * @param string $reason
	 * @return VariableHolder
	 */
	public function getDeleteVars(
		string $reason
	): VariableHolder {
		$this->addUserVars( $this->user )
			->addTitleVars( $this->title, 'page' );

		$this->vars->setVar( 'summary', $reason );
		$this->vars->setVar( 'action', 'delete' );
		return $this->vars;
	}

	/**
	 * Get variables for filtering an upload.
	 *
	 * @param string $action
	 * @param UploadBase $upload
	 * @param string|null $summary
	 * @param string|null $text
	 * @param array|null $props
	 * @return VariableHolder|null
	 */
	public function getUploadVars(
		string $action,
		UploadBase $upload,
		?string $summary,
		?string $text,
		?array $props
	): ?VariableHolder {
		if ( !$props ) {
			$props = ( new MWFileProps( $this->mimeAnalyzer ) )->getPropsFromPath(
				$upload->getTempPath(),
				true
			);
		}

		$this->addUserVars( $this->user )
			->addTitleVars( $this->title, 'page' );
		$this->vars->setVar( 'action', $action );

		// We use the hexadecimal version of the file sha1.
		// Use UploadBase::getTempFileSha1Base36 so that we don't have to calculate the sha1 sum again
		$sha1 = \Wikimedia\base_convert( $upload->getTempFileSha1Base36(), 36, 16, 40 );

		// This is the same as AbuseFilterRowVariableGenerator::addUploadVars, but from a different source
		$this->vars->setVar( 'file_sha1', $sha1 );
		$this->vars->setVar( 'file_size', $upload->getFileSize() );

		$this->vars->setVar( 'file_mime', $props['mime'] );
		$this->vars->setVar( 'file_mediatype', $this->mimeAnalyzer->getMediaType( null, $props['mime'] ) );
		$this->vars->setVar( 'file_width', $props['width'] );
		$this->vars->setVar( 'file_height', $props['height'] );
		$this->vars->setVar( 'file_bits_per_channel', $props['bits'] );

		// We only have the upload comment and page text when using the UploadVerifyUpload hook
		if ( $summary !== null && $text !== null ) {
			// This block is adapted from self::getEditTextForFiltering()
			$page = $this->wikiPageFactory->newFromTitle( $this->title );
			if ( $this->title->exists() ) {
				$revRec = $page->getRevisionRecord();
				if ( !$revRec ) {
					return null;
				}

				$oldcontent = $revRec->getContent( SlotRecord::MAIN, RevisionRecord::RAW );
				'@phan-var Content $oldcontent';
				$oldtext = $this->textExtractor->contentToString( $oldcontent );

				// Page text is ignored for uploads when the page already exists
				$text = $oldtext;
			} else {
				$oldtext = '';
			}

			// Load vars for filters to check
			$this->vars->setVar( 'summary', $summary );
			$this->vars->setVar( 'old_wikitext', $oldtext );
			$this->vars->setVar( 'new_wikitext', $text );
			// TODO: set old_content_model and new_content_model vars, use them
			$this->addEditVars( $page, $this->user, true );
		}
		return $this->vars;
	}

	/**
	 * Get variables for filtering an account creation
	 *
	 * @param User $createdUser This is the user being created, not the creator (which is $this->user)
	 * @param bool $autocreate
	 * @return VariableHolder
	 */
	public function getAccountCreationVars(
		User $createdUser,
		bool $autocreate
	): VariableHolder {
		// generateUserVars records $this->user->getName() which would be the IP for unregistered users
		if ( $this->user->isRegistered() ) {
			$this->addUserVars( $this->user );
		} else {
			// Set the user_type for IP users, so that filters can distinguish between account
			// creations from temporary accounts and those from IP addresses.
			$this->vars->setLazyLoadVar(
				'user_type',
				'user-type',
				[ 'user-identity' => $this->user ]
			);
		}

		$this->vars->setVar( 'action', $autocreate ? 'autocreateaccount' : 'createaccount' );
		$this->vars->setVar( 'accountname', $createdUser->getName() );
		return $this->vars;
	}
}
