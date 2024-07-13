<?php

namespace MediaWiki\Extension\DiscussionTools\ThreadItem;

use JsonSerializable;
use MediaWiki\Extension\DiscussionTools\CommentModifier;
use MediaWiki\Extension\DiscussionTools\ImmutableRange;
use MediaWiki\Parser\Sanitizer;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

/**
 * A thread item, either a heading or a comment
 */
abstract class ContentThreadItem implements JsonSerializable, ThreadItem {
	use ThreadItemTrait;

	protected string $type;
	protected ImmutableRange $range;
	protected Element $rootNode;
	protected int $level;
	protected ?ContentThreadItem $parent = null;
	/** @var string[] */
	protected array $warnings = [];

	protected string $name;
	protected string $id;
	/** @var ContentThreadItem[] */
	protected array $replies = [];
	/** @var string|bool */
	private $transcludedFrom;

	/** @var ?array[] */
	protected ?array $authors = null;
	protected int $commentCount;
	protected ?ContentCommentItem $oldestReply;
	protected ?ContentCommentItem $latestReply;

	/**
	 * @param string $type `heading` or `comment`
	 * @param int $level Indentation level
	 * @param ImmutableRange $range Object describing the extent of the comment, including the
	 *  signature and timestamp.
	 * @param bool|string $transcludedFrom
	 */
	public function __construct(
		string $type, int $level, ImmutableRange $range, $transcludedFrom
	) {
		$this->type = $type;
		$this->level = $level;
		$this->range = $range;
		$this->transcludedFrom = $transcludedFrom;
	}

	/**
	 * Get summary metadata for a thread.
	 */
	private function calculateThreadSummary(): void {
		if ( $this->authors !== null ) {
			return;
		}
		$authors = [];
		$commentCount = 0;
		$oldestReply = null;
		$latestReply = null;
		$threadScan = static function ( ContentThreadItem $comment ) use (
			&$authors, &$commentCount, &$oldestReply, &$latestReply, &$threadScan
		) {
			if ( $comment instanceof ContentCommentItem ) {
				$author = $comment->getAuthor();
				if ( !isset( $authors[ $author] ) ) {
					$authors[ $author ] = [
						'username' => $author,
						'displayNames' => [],
					];
				}
				$displayName = $comment->getDisplayName();
				if ( $displayName && !in_array( $displayName, $authors[ $author ][ 'displayNames' ], true ) ) {
					$authors[ $author ][ 'displayNames' ][] = $displayName;
				}

				if (
					!$oldestReply ||
					( $comment->getTimestamp() < $oldestReply->getTimestamp() )
				) {
					$oldestReply = $comment;
				}
				if (
					!$latestReply ||
					( $latestReply->getTimestamp() < $comment->getTimestamp() )
				) {
					$latestReply = $comment;
				}
				$commentCount++;
			}
			// Get the set of authors in the same format from each reply
			$replies = $comment->getReplies();
			array_walk( $replies, $threadScan );
		};
		$replies = $this->getReplies();
		array_walk( $replies, $threadScan );

		ksort( $authors );

		$this->authors = array_values( $authors );
		$this->commentCount = $commentCount;
		$this->oldestReply = $oldestReply;
		$this->latestReply = $latestReply;
	}

	/**
	 * Get the list of authors in the tree below this thread item.
	 *
	 * Usually called on a HeadingItem to find all authors in a thread.
	 *
	 * @return array[] Authors, with `username` and `displayNames` (list of display names) properties.
	 */
	public function getAuthorsBelow(): array {
		$this->calculateThreadSummary();
		return $this->authors;
	}

	/**
	 * Get the number of comment items in the tree below this thread item.
	 */
	public function getCommentCount(): int {
		$this->calculateThreadSummary();
		return $this->commentCount;
	}

	/**
	 * Get the latest reply in the tree below this thread item, null if there are no replies
	 */
	public function getLatestReply(): ?ContentCommentItem {
		$this->calculateThreadSummary();
		return $this->latestReply;
	}

	/**
	 * Get the oldest reply in the tree below this thread item, null if there are no replies
	 */
	public function getOldestReply(): ?ContentCommentItem {
		$this->calculateThreadSummary();
		return $this->oldestReply;
	}

	/**
	 * Get a flat list of thread items in the comment tree below this thread item.
	 *
	 * @return ContentThreadItem[] Thread items
	 */
	public function getThreadItemsBelow(): array {
		$threadItems = [];
		$getReplies = static function ( ContentThreadItem $threadItem ) use ( &$threadItems, &$getReplies ) {
			$threadItems[] = $threadItem;
			foreach ( $threadItem->getReplies() as $reply ) {
				$getReplies( $reply );
			}
		};

		foreach ( $this->getReplies() as $reply ) {
			$getReplies( $reply );
		}

		return $threadItems;
	}

	/**
	 * @inheritDoc
	 */
	public function getTranscludedFrom() {
		return $this->transcludedFrom;
	}

	/**
	 * Get the HTML of this thread item
	 *
	 * @return string HTML
	 */
	public function getHTML(): string {
		$fragment = $this->getRange()->cloneContents();
		CommentModifier::unwrapFragment( $fragment );
		// Does not work: T357812
		// $editsection = DOMCompat::querySelector( $fragment, 'mw\\:editsection' );
		for ( $n = $fragment->firstChild; $n; $n = $n->nextSibling ) {
			if ( $n instanceof Element ) {
				if ( strtolower( $n->tagName ) === 'mw:editsection' ) {
					$n->parentNode->removeChild( $n );
					break;
				}
				$editsection = DOMCompat::querySelector( $n, 'mw\\:editsection' );
				if ( $editsection ) {
					$editsection->parentNode->removeChild( $editsection );
					break;
				}
			}
		}
		return DOMUtils::getFragmentInnerHTML( $fragment );
	}

	/**
	 * Get the text of this thread item
	 *
	 * @return string Text
	 */
	public function getText(): string {
		$html = $this->getHTML();
		return Sanitizer::stripAllTags( $html );
	}

	/**
	 * @return string Thread item type
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @return int Indentation level
	 */
	public function getLevel(): int {
		return $this->level;
	}

	/**
	 * @return ContentThreadItem|null Parent thread item
	 */
	public function getParent(): ?ThreadItem {
		return $this->parent;
	}

	/**
	 * @return ImmutableRange Range of the entire thread item
	 */
	public function getRange(): ImmutableRange {
		return $this->range;
	}

	/**
	 * @return Element Root node (level is relative to this node)
	 */
	public function getRootNode(): Element {
		return $this->rootNode;
	}

	/**
	 * @return string Thread item name
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return string Thread ID
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return ContentThreadItem[] Replies to this thread item
	 */
	public function getReplies(): array {
		return $this->replies;
	}

	/**
	 * @return string[] Warnings
	 */
	public function getWarnings(): array {
		return $this->warnings;
	}

	/**
	 * @param int $level Indentation level
	 */
	public function setLevel( int $level ): void {
		$this->level = $level;
	}

	public function setParent( ContentThreadItem $parent ): void {
		$this->parent = $parent;
	}

	/**
	 * @param ImmutableRange $range Thread item range
	 */
	public function setRange( ImmutableRange $range ): void {
		$this->range = $range;
	}

	/**
	 * @param Element $rootNode Root node (level is relative to this node)
	 */
	public function setRootNode( Element $rootNode ): void {
		$this->rootNode = $rootNode;
	}

	/**
	 * @param string $name Thread item name
	 */
	public function setName( string $name ): void {
		$this->name = $name;
	}

	/**
	 * @param string $id Thread ID
	 */
	public function setId( string $id ): void {
		$this->id = $id;
	}

	public function addWarning( string $warning ): void {
		$this->warnings[] = $warning;
	}

	/**
	 * @param string[] $warnings
	 */
	public function addWarnings( array $warnings ): void {
		$this->warnings = array_merge( $this->warnings, $warnings );
	}

	/**
	 * @param ContentThreadItem $reply Reply comment
	 */
	public function addReply( ContentThreadItem $reply ): void {
		$this->replies[] = $reply;
	}
}
