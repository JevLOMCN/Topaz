<?php

namespace MediaWiki\Extension\DiscussionTools;

use InvalidArgumentException;
use LogicException;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentCommentItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentThreadItem;
use MediaWiki\MediaWikiServices;
use UnexpectedValueException;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

class CommentModifier {

	private function __construct() {
	}

	/**
	 * Add an attribute to a list item to remove pre-whitespace in Parsoid
	 */
	private static function whitespaceParsoidHack( Element $listItem ): void {
		// HACK: Setting data-parsoid removes the whitespace after the list item,
		// which makes nested lists work.
		// This is undocumented behaviour and probably very fragile.
		$listItem->setAttribute( 'data-parsoid', '{}' );
	}

	/**
	 * Remove extra linebreaks from a wikitext string
	 */
	public static function sanitizeWikitextLinebreaks( string $wikitext ): string {
		$wikitext = CommentUtils::htmlTrim( $wikitext );
		$wikitext = preg_replace( "/\r/", "\n", $wikitext );
		$wikitext = preg_replace( "/\n+/", "\n", $wikitext );
		return $wikitext;
	}

	/**
	 * Given a comment and a reply link, add the reply link to its document's DOM tree, at the end of
	 * the comment.
	 *
	 * @param ContentCommentItem $comment
	 * @param Node $linkNode Reply link
	 */
	public static function addReplyLink( ContentCommentItem $comment, Node $linkNode ): void {
		$target = $comment->getRange()->endContainer;

		// Insert the link before trailing whitespace.
		// In the MediaWiki parser output, <ul>/<dl> nodes are preceded by a newline. Normally it isn't
		// visible on the page. But if we insert an inline element (the reply link) after it, it becomes
		// meaningful and gets rendered, which results in additional spacing before some reply links.
		// Split the text node, so that we can insert the link before the trailing whitespace.
		if ( $target instanceof Text ) {
			preg_match( '/\s*$/', $target->nodeValue ?? '', $matches, PREG_OFFSET_CAPTURE );
			$byteOffset = $matches[0][1];
			$charOffset = mb_strlen(
				substr( $target->nodeValue ?? '', 0, $byteOffset )
			);
			$target->splitText( $charOffset );
		}

		$target->parentNode->insertBefore( $linkNode, $target->nextSibling );
	}

	/**
	 * Given a comment, add a list item to its document's DOM tree, inside of which a reply to said
	 * comment can be added.
	 *
	 * The DOM tree is suitably rearranged to ensure correct indentation level of the reply (wrapper
	 * nodes are added, and other nodes may be moved around).
	 *
	 * @param ContentThreadItem $comment
	 * @param string $replyIndentation Reply indentation syntax to use, one of:
	 *   - 'invisible' (use `<dl><dd>` tags to output `:` in wikitext)
	 *   - 'bullet' (use `<ul><li>` tags to output `*` in wikitext)
	 * @return Element
	 */
	public static function addListItem( ContentThreadItem $comment, string $replyIndentation ): Element {
		$listTypeMap = [
			'li' => 'ul',
			'dd' => 'dl'
		];

		// 1. Start at given comment
		// 2. Skip past all comments with level greater than the given
		//    (or in other words, all replies, and replies to replies, and so on)
		// 3. Add comment with level of the given comment plus 1

		$curComment = $comment;
		while ( count( $curComment->getReplies() ) ) {
			$replies = $curComment->getReplies();
			$curComment = end( $replies );
		}

		// Tag names for lists and items we're going to insert
		if ( $replyIndentation === 'invisible' ) {
			$itemType = 'dd';
		} elseif ( $replyIndentation === 'bullet' ) {
			$itemType = 'li';
		} else {
			throw new InvalidArgumentException( "Invalid reply indentation syntax '$replyIndentation'" );
		}
		$listType = $listTypeMap[ $itemType ];

		$desiredLevel = $comment->getLevel() + 1;
		$target = $curComment->getRange()->endContainer;

		// target is a text node or an inline element at the end of a "paragraph"
		// (not necessarily paragraph node).
		// First, we need to find a block-level parent that we can mess with.
		// If we can't find a surrounding list item or paragraph (e.g. maybe we're inside a table cell
		// or something), take the parent node and hope for the best.
		$parent = CommentUtils::closestElement( $target, [ 'li', 'dd', 'p' ] ) ??
			$target->parentNode;
		while ( $target->parentNode !== $parent ) {
			$target = $target->parentNode;
		}
		// parent is a list item or paragraph (hopefully)
		// target is an inline node within it

		// If the comment is fully covered by some wrapper element, insert replies outside that wrapper.
		// This will often just be a paragraph node (<p>), but it can be a <div> or <table> that serves
		// as some kind of a fancy frame, which are often used for barnstars and announcements.
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$excludedWrapper = CommentUtils::closestElement( $target, [ 'section' ] ) ?:
			$curComment->getRootNode();
		$covered = CommentUtils::getFullyCoveredSiblings( $curComment, $excludedWrapper );
		if ( $curComment->getLevel() === 1 && $covered ) {
			$target = end( $covered );
			$parent = $target->parentNode;
		}

		// If the comment is in a transclusion, insert replies after the transclusion. (T313100)
		// This method should never be called in cases where that would be a bad idea.
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$transclusionNode = CommentUtils::getTranscludedFromElement( $target );
		if ( $transclusionNode ) {
			while (
				( $nextSibling = $transclusionNode->nextSibling ) &&
				$nextSibling instanceof Element &&
				$nextSibling->getAttribute( 'about' ) === $transclusionNode->getAttribute( 'about' )
			) {
				$transclusionNode = $nextSibling;
			}
			$target = $transclusionNode;
			$parent = $target->parentNode;
		}

		// If we can't insert a list directly inside this element, insert after it.
		// The covered wrapper check above handles most cases, but we still need this sometimes, such as:
		// * If the comment starts in the middle of a list, then ends with an unindented p/pre, the
		//   wrapper check doesn't adjust the parent
		// * If the comment consists of multiple list items (starting with a <dt>, so that the comment is
		//   considered to be unindented, that is level === 1), but not all of them, the wrapper check
		//   adjusts the parent to be the list, and the rest of the algorithm doesn't handle that well
		if (
			strtolower( $parent->tagName ) === 'p' ||
			strtolower( $parent->tagName ) === 'pre' ||
			strtolower( $parent->tagName ) === 'ul' ||
			strtolower( $parent->tagName ) === 'dl'
		) {
			$parent = $parent->parentNode;
			$target = $target->parentNode;
		}

		Assert::precondition( $target !== null, 'We have not stepped outside the document' );
		// Instead of just using $curComment->getLevel(), consider indentation of lists within the
		// comment (T252702)
		$curLevel = CommentUtils::getIndentLevel( $target, $curComment->getRootNode() ) + 1;

		$item = null;
		if ( $desiredLevel === 1 ) {
			// Special handling for top-level comments
			// We use section=new API for adding them in PHP, so this should never happen
			throw new UnexpectedValueException( "Can't add a top-level comment" );

		} elseif ( $curLevel < $desiredLevel ) {
			// Insert more lists after the target to increase nesting.

			// Parsoid puts HTML comments (and other "rendering-transparent nodes", e.g. category links)
			// which appear at the end of the line in wikitext outside the paragraph,
			// but we usually shouldn't insert replies between the paragraph and such comments. (T257651)
			// Skip over comments and whitespace, but only update target when skipping past comments.
			$pointer = $target;
			while (
				$pointer->nextSibling && (
					CommentUtils::isRenderingTransparentNode( $pointer->nextSibling ) ||
					(
						$pointer->nextSibling instanceof Text &&
						CommentUtils::htmlTrim( $pointer->nextSibling->nodeValue ?? '' ) === '' &&
						// If more that two lines of whitespace are detected, the following HTML
						// comments are not considered to be part of the reply (T264026)
						!preg_match( '/(\r?\n){2,}/', $pointer->nextSibling->nodeValue ?? '' )
					)
				)
			) {
				$pointer = $pointer->nextSibling;
				if ( CommentUtils::isRenderingTransparentNode( $pointer ) ) {
					$target = $pointer;
				}
			}

			// Insert required number of wrappers
			while ( $curLevel < $desiredLevel ) {
				$list = $target->ownerDocument->createElement( $listType );
				// Setting modified would only be needed for removeAddedListItem,
				// which isn't needed on the server
				// $list->setAttribute( 'dt-modified', 'new' );
				$item = $target->ownerDocument->createElement( $itemType );
				// $item->setAttribute( 'dt-modified', 'new' );

				$parent->insertBefore( $list, $target->nextSibling );
				$list->appendChild( $item );

				$target = $item;
				$parent = $list;
				$curLevel++;
			}
		} else {
			// Split the ancestor nodes after the target to decrease nesting.

			do {
				if ( !$target || !$parent ) {
					throw new LogicException( 'Can not decrease nesting any more' );
				}

				// If target is the last child of its parent, no need to split it
				if ( $target->nextSibling ) {
					// Create new identical node after the parent
					$newNode = $parent->cloneNode( false );
					// $parent->setAttribute( 'dt-modified', 'split' );
					$parent->parentNode->insertBefore( $newNode, $parent->nextSibling );

					// Move nodes following target to the new node
					while ( $target->nextSibling ) {
						$newNode->appendChild( $target->nextSibling );
					}
				}

				$target = $parent;
				$parent = $parent->parentNode;

				// Decrease nesting level if we escaped outside of a list
				if ( isset( $listTypeMap[ strtolower( $target->tagName ) ] ) ) {
					$curLevel--;
				}
			} while ( $curLevel >= $desiredLevel );

			// parent is now a list, target is a list item
			if ( $itemType === strtolower( $target->tagName ) ) {
				$item = $target->ownerDocument->createElement( $itemType );
				// $item->setAttribute( 'dt-modified', 'new' );
				$parent->insertBefore( $item, $target->nextSibling );

			} else {
				// This is the wrong type of list, split it one more time

				// If target is the last child of its parent, no need to split it
				if ( $target->nextSibling ) {
					// Create new identical node after the parent
					$newNode = $parent->cloneNode( false );
					// $parent->setAttribute( 'dt-modified', 'split' );
					$parent->parentNode->insertBefore( $newNode, $parent->nextSibling );

					// Move nodes following target to the new node
					while ( $target->nextSibling ) {
						$newNode->appendChild( $target->nextSibling );
					}
				}

				$target = $parent;
				$parent = $parent->parentNode;

				// Insert a list of the right type in the middle
				$list = $target->ownerDocument->createElement( $listType );
				// Setting modified would only be needed for removeAddedListItem,
				// which isn't needed on the server
				// $list->setAttribute( 'dt-modified', 'new' );
				$item = $target->ownerDocument->createElement( $itemType );
				// $item->setAttribute( 'dt-modified', 'new' );

				$parent->insertBefore( $list, $target->nextSibling );
				$list->appendChild( $item );
			}
		}

		if ( $item === null ) {
			throw new LogicException( 'No item found' );
		}

		return $item;
	}

	/**
	 * Check all elements in a node list are of a given type
	 *
	 * Also returns false if there are no elements in the list
	 *
	 * @param iterable<Node> $nodes Node list
	 * @param string $type Element type
	 * @return bool
	 */
	private static function allOfType( iterable $nodes, string $type ): bool {
		$hasElements = false;
		foreach ( $nodes as $node ) {
			if ( $node instanceof Element ) {
				if ( strtolower( $node->tagName ) !== strtolower( $type ) ) {
					return false;
				}
				$hasElements = true;
			}
		}
		return $hasElements;
	}

	/**
	 * Remove unnecessary list wrappers from a comment fragment
	 *
	 * TODO: Implement this in JS if required
	 *
	 * @param DocumentFragment $fragment Fragment
	 */
	public static function unwrapFragment( DocumentFragment $fragment ): void {
		// Wrap orphaned list items
		$list = null;
		if ( static::allOfType( $fragment->childNodes, 'dd' ) ) {
			$list = $fragment->ownerDocument->createElement( 'dl' );
		} elseif ( static::allOfType( $fragment->childNodes, 'li' ) ) {
			$list = $fragment->ownerDocument->createElement( 'ul' );
		}
		if ( $list ) {
			while ( $fragment->firstChild ) {
				$list->appendChild( $fragment->firstChild );
			}
			$fragment->appendChild( $list );
		}

		// If all child nodes are lists of the same type, unwrap them
		while (
			static::allOfType( $fragment->childNodes, 'dl' ) ||
			static::allOfType( $fragment->childNodes, 'ul' ) ||
			static::allOfType( $fragment->childNodes, 'ol' )
		) {
			// Do not iterate over childNodes while we're modifying it
			$childNodeList = iterator_to_array( $fragment->childNodes );
			foreach ( $childNodeList as $node ) {
				static::unwrapList( $node, $fragment );
			}
		}
	}

	// removeAddedListItem is only needed in the client

	/**
	 * Unwrap a top level list, converting list item text to paragraphs
	 *
	 * Assumes that the list has a parent node, or is a root child in the provided
	 * document fragment.
	 *
	 * @param Node $list DOM node, will be wrapped if it is a list element (dl/ol/ul)
	 * @param DocumentFragment|null $fragment Containing document fragment if list has no parent
	 */
	public static function unwrapList( Node $list, ?DocumentFragment $fragment = null ): void {
		$doc = $list->ownerDocument;
		$container = $fragment ?: $list->parentNode;
		$referenceNode = $list;

		if ( !(
			$list instanceof Element && (
				strtolower( $list->tagName ) === 'dl' ||
				strtolower( $list->tagName ) === 'ol' ||
				strtolower( $list->tagName ) === 'ul'
			)
		) ) {
			// Not a list, leave alone (e.g. auto-generated ref block)
			return;
		}

		// If the whole list is a template return it unmodified (T253150)
		if ( CommentUtils::getTranscludedFromElement( $list ) ) {
			return;
		}

		while ( $list->firstChild ) {
			if ( $list->firstChild instanceof Element ) {
				// Move <dd> contents to <p>
				$p = $doc->createElement( 'p' );
				while ( $list->firstChild->firstChild ) {
					// If contents is a block element, place outside the paragraph
					// and start a new paragraph after
					if ( CommentUtils::isBlockElement( $list->firstChild->firstChild ) ) {
						if ( $p->firstChild ) {
							$insertBefore = $referenceNode->nextSibling;
							$referenceNode = $p;
							$container->insertBefore( $p, $insertBefore );
						}
						$insertBefore = $referenceNode->nextSibling;
						$referenceNode = $list->firstChild->firstChild;
						$container->insertBefore( $list->firstChild->firstChild, $insertBefore );
						$p = $doc->createElement( 'p' );
					} else {
						$p->appendChild( $list->firstChild->firstChild );
					}
				}
				if ( $p->firstChild ) {
					$insertBefore = $referenceNode->nextSibling;
					$referenceNode = $p;
					$container->insertBefore( $p, $insertBefore );
				}
				$list->removeChild( $list->firstChild );
			} else {
				// Text node / comment node, probably empty
				$insertBefore = $referenceNode->nextSibling;
				$referenceNode = $list->firstChild;
				$container->insertBefore( $list->firstChild, $insertBefore );
			}
		}
		$container->removeChild( $list );
	}

	/**
	 * Add another list item after the given one.
	 */
	public static function addSiblingListItem( Element $previousItem ): Element {
		$listItem = $previousItem->ownerDocument->createElement( $previousItem->tagName );
		$previousItem->parentNode->insertBefore( $listItem, $previousItem->nextSibling );
		return $listItem;
	}

	/**
	 * Create an element that will convert to the provided wikitext
	 */
	public static function createWikitextNode( Document $doc, string $wikitext ): Element {
		$span = $doc->createElement( 'span' );

		$span->setAttribute( 'typeof', 'mw:Transclusion' );
		$span->setAttribute( 'data-mw', json_encode( [ 'parts' => [ $wikitext ] ] ) );

		return $span;
	}

	/**
	 * Check if an element created by ::createWikitextNode() starts with list item markup.
	 */
	private static function isWikitextNodeListItem( Element $node ): bool {
		$dataMw = json_decode( $node->getAttribute( 'data-mw' ) ?? '', true );
		$wikitextLine = $dataMw['parts'][0] ?? null;
		return $wikitextLine && is_string( $wikitextLine ) &&
			in_array( $wikitextLine[0], [ '*', '#', ':', ';' ], true );
	}

	/**
	 * Append a user signature to the comment in the container.
	 */
	public static function appendSignature( DocumentFragment $container, string $signature ): void {
		$doc = $container->ownerDocument;

		// If the last node isn't a paragraph (e.g. it's a list created in visual mode),
		// or looks like a list item created in wikitext mode (T263217),
		// then add another paragraph to contain the signature.
		$wrapperNode = $container->lastChild;
		if (
			!( $wrapperNode instanceof Element ) ||
			strtolower( $wrapperNode->tagName ) !== 'p' ||
			(
				// This would be easier to check in prepareWikitextReply(), but that would result
				// in an empty list item being added at the end if we don't need to add a signature.
				( $wtNode = $wrapperNode->lastChild ) &&
				$wtNode instanceof Element &&
				static::isWikitextNodeListItem( $wtNode )
			)
		) {
			$container->appendChild( $doc->createElement( 'p' ) );
		}
		// If the last node is empty, trim the signature to prevent leading whitespace triggering
		// preformatted text (T269188, T276612)
		if ( !$container->lastChild->firstChild ) {
			$signature = ltrim( $signature, ' ' );
		}
		// Sign the last line
		$container->lastChild->appendChild(
			static::createWikitextNode(
				$doc,
				$signature
			)
		);
	}

	/**
	 * Append a user signature to the comment in the provided wikitext.
	 */
	public static function appendSignatureWikitext( string $wikitext, string $signature ): string {
		$wikitext = CommentUtils::htmlTrim( $wikitext );

		$lines = explode( "\n", $wikitext );
		$lastLine = end( $lines );

		// If last line looks like a list item, add an empty line afterwards for the signature (T263217)
		if ( $lastLine && in_array( $lastLine[0], [ '*', '#', ':', ';' ], true ) ) {
			$wikitext .= "\n";
			// Trim the signature to prevent leading whitespace triggering preformatted text (T269188, T276612)
			$signature = ltrim( $signature, ' ' );
		}

		return $wikitext . $signature;
	}

	/**
	 * Add a reply to a specific comment
	 *
	 * @param ContentThreadItem $comment Comment being replied to
	 * @param DocumentFragment $container Container of comment DOM nodes
	 */
	public static function addReply( ContentThreadItem $comment, DocumentFragment $container ): void {
		$services = MediaWikiServices::getInstance();
		$dtConfig = $services->getConfigFactory()->makeConfig( 'discussiontools' );
		$replyIndentation = $dtConfig->get( 'DiscussionToolsReplyIndentation' );

		$newParsoidItem = null;
		// Transfer comment DOM to Parsoid DOM
		// Wrap every root node of the document in a new list item (dd/li).
		// In wikitext mode every root node is a paragraph.
		// In visual mode the editor takes care of preventing problematic nodes
		// like <table> or <h2> from ever occurring in the comment.
		while ( $container->childNodes->length ) {
			if ( !$newParsoidItem ) {
				$newParsoidItem = static::addListItem( $comment, $replyIndentation );
			} else {
				$newParsoidItem = static::addSiblingListItem( $newParsoidItem );
			}

			// Suppress space after the indentation character to support nested lists (T238218).
			// By request from the community, avoid this if possible after bullet indentation (T259864).
			if ( !(
				$replyIndentation === 'bullet' &&
				( $wtNode = $container->firstChild->lastChild ) &&
				$wtNode instanceof Element &&
				!static::isWikitextNodeListItem( $wtNode )
			) ) {
				static::whitespaceParsoidHack( $newParsoidItem );
			}

			$newParsoidItem->appendChild( $container->firstChild );
		}
	}

	/**
	 * Transfer comment DOM nodes into a list node, as if adding a reply, but without requiring a
	 * ThreadItem.
	 *
	 * @param DocumentFragment $container Container of comment DOM nodes
	 * @return Element $node List node
	 */
	public static function transferReply( DocumentFragment $container ): Element {
		$services = MediaWikiServices::getInstance();
		$dtConfig = $services->getConfigFactory()->makeConfig( 'discussiontools' );
		$replyIndentation = $dtConfig->get( 'DiscussionToolsReplyIndentation' );

		$doc = $container->ownerDocument;

		// Like addReply(), but we make our own list
		$list = $doc->createElement( $replyIndentation === 'invisible' ? 'dl' : 'ul' );
		while ( $container->childNodes->length ) {
			$item = $doc->createElement( $replyIndentation === 'invisible' ? 'dd' : 'li' );
			// Suppress space after the indentation character to support nested lists (T238218).
			// By request from the community, avoid this if possible after bullet indentation (T259864).
			if ( !(
				$replyIndentation === 'bullet' &&
				( $wtNode = $container->firstChild->lastChild ) &&
				$wtNode instanceof Element &&
				!static::isWikitextNodeListItem( $wtNode )
			) ) {
				static::whitespaceParsoidHack( $item );
			}
			$item->appendChild( $container->firstChild );
			$list->appendChild( $item );
		}
		return $list;
	}

	/**
	 * Create a container of comment DOM nodes from wikitext
	 *
	 * @param Document $doc Document where the DOM nodes will be inserted
	 * @param string $wikitext
	 * @return DocumentFragment DOM nodes
	 */
	public static function prepareWikitextReply( Document $doc, string $wikitext ): DocumentFragment {
		$container = $doc->createDocumentFragment();

		$wikitext = static::sanitizeWikitextLinebreaks( $wikitext );

		$lines = explode( "\n", $wikitext );
		foreach ( $lines as $line ) {
			$p = $doc->createElement( 'p' );
			$p->appendChild( static::createWikitextNode( $doc, $line ) );
			$container->appendChild( $p );
		}

		return $container;
	}

	/**
	 * Create a container of comment DOM nodes from HTML
	 *
	 * @param Document $doc Document where the DOM nodes will be inserted
	 * @param string $html
	 * @return DocumentFragment DOM nodes
	 */
	public static function prepareHtmlReply( Document $doc, string $html ): DocumentFragment {
		$container = DOMUtils::parseHTMLToFragment( $doc, $html );

		// Remove empty lines
		// This should really be anything that serializes to empty string in wikitext,
		// (e.g. <h2></h2>) but this will catch most cases
		// Create a non-live child node list, so we don't have to worry about it changing
		// as nodes are removed.
		$childNodeList = iterator_to_array( $container->childNodes );
		foreach ( $childNodeList as $node ) {
			if ( (
				$node instanceof Text &&
				CommentUtils::htmlTrim( $node->nodeValue ?? '' ) === ''
			) || (
				$node instanceof Element &&
				strtolower( $node->tagName ) === 'p' &&
				CommentUtils::htmlTrim( DOMCompat::getInnerHTML( $node ) ) === ''
			) ) {
				$container->removeChild( $node );
			}
		}

		return $container;
	}

	/**
	 * Add a reply in the DOM to a comment using wikitext.
	 *
	 * @param ContentCommentItem $comment Comment being replied to
	 * @param string $wikitext
	 * @param string|null $signature
	 */
	public static function addWikitextReply(
		ContentCommentItem $comment, string $wikitext, string $signature = null
	): void {
		$doc = $comment->getRange()->endContainer->ownerDocument;
		$container = static::prepareWikitextReply( $doc, $wikitext );
		if ( $signature !== null ) {
			static::appendSignature( $container, $signature );
		}
		static::addReply( $comment, $container );
	}

	/**
	 * Add a reply in the DOM to a comment using HTML.
	 *
	 * @param ContentCommentItem $comment Comment being replied to
	 * @param string $html
	 * @param string|null $signature
	 */
	public static function addHtmlReply(
		ContentCommentItem $comment, string $html, string $signature = null
	): void {
		$doc = $comment->getRange()->endContainer->ownerDocument;
		$container = static::prepareHtmlReply( $doc, $html );
		if ( $signature !== null ) {
			static::appendSignature( $container, $signature );
		}
		static::addReply( $comment, $container );
	}
}
