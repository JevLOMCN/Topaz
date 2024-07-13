<?php

namespace MediaWiki\Extension\DiscussionTools;

use IContextSource;
use InvalidArgumentException;
use MediaWiki\Cache\LinkBatchFactory;
use MediaWiki\Extension\DiscussionTools\ThreadItem\DatabaseThreadItem;
use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Pager\TablePager;
use MediaWiki\Title\Title;
use OOUI;

class TopicSubscriptionsPager extends TablePager {

	/**
	 * Map of our field names (see ::getFieldNames()) to the column names actually used for
	 * pagination. This is needed to ensure that the values are unique, and that pagination
	 * won't get "stuck" when e.g. 50 subscriptions are all created within a second.
	 */
	private const INDEX_FIELDS = [
		// The auto-increment ID will almost always have the same order as sub_created
		// and the field already has an index.
		'_topic' => [ 'sub_id' ],
		'sub_created' => [ 'sub_id' ],
		// TODO Add indexes that cover these fields to enable sorting by them
		// 'sub_state' => [ 'sub_state', 'sub_item' ],
		// 'sub_created' => [ 'sub_created', 'sub_item' ],
		// 'sub_notified' => [ 'sub_notified', 'sub_item' ],
	];

	private LinkBatchFactory $linkBatchFactory;
	private ThreadItemStore $threadItemStore;
	private ThreadItemFormatter $threadItemFormatter;

	/** @var array<string,DatabaseThreadItem[]> */
	private array $threadItemsByName = [];

	public function __construct(
		IContextSource $context,
		LinkRenderer $linkRenderer,
		LinkBatchFactory $linkBatchFactory,
		ThreadItemStore $threadItemStore,
		ThreadItemFormatter $threadItemFormatter
	) {
		parent::__construct( $context, $linkRenderer );
		$this->linkBatchFactory = $linkBatchFactory;
		$this->threadItemStore = $threadItemStore;
		$this->threadItemFormatter = $threadItemFormatter;
	}

	/**
	 * @inheritDoc
	 */
	public function preprocessResults( $result ) {
		if ( !$result->numRows() ) {
			return;
		}
		$lb = $this->linkBatchFactory->newLinkBatch();
		$itemNames = [];
		foreach ( $result as $row ) {
			$lb->add( $row->sub_namespace, $row->sub_title );
			$itemNames[] = $row->sub_item;
		}
		$lb->execute();

		// Increased limit to allow finding and skipping over some bad permalinks
		$threadItems = $this->threadItemStore->findNewestRevisionsByName( $itemNames, $this->mLimit * 5 );
		foreach ( $threadItems as $threadItem ) {
			$this->threadItemsByName[ $threadItem->getName() ][] = $threadItem;
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getFieldNames() {
		return [
			'_topic' => $this->msg( 'discussiontools-topicsubscription-pager-topic' )->text(),
			'_page' => $this->msg( 'discussiontools-topicsubscription-pager-page' )->text(),
			'sub_created' => $this->msg( 'discussiontools-topicsubscription-pager-created' )->text(),
			'sub_notified' => $this->msg( 'discussiontools-topicsubscription-pager-notified' )->text(),
			'_unsubscribe' => $this->msg( 'discussiontools-topicsubscription-pager-actions' )->text(),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function formatValue( $field, $value ) {
		/** @var stdClass $row */
		$row = $this->mCurrentRow;

		switch ( $field ) {
			case '_topic':
				return $this->formatValueTopic( $row );

			case '_page':
				return $this->formatValuePage( $row );

			case 'sub_created':
				return htmlspecialchars( $this->getLanguage()->userTimeAndDate( $value, $this->getUser() ) );

			case 'sub_notified':
				return $value ?
					htmlspecialchars( $this->getLanguage()->userTimeAndDate( $value, $this->getUser() ) ) :
					$this->msg( 'discussiontools-topicsubscription-pager-notified-never' )->escaped();

			case '_unsubscribe':
				$title = Title::makeTitleSafe( $row->sub_namespace, $row->sub_title );
				if ( !$title ) {
					// Handle invalid titles (T345648)
					// The title isn't checked when unsubscribing, as long as it's a valid title,
					// so specify something to make it possible to unsubscribe from the buggy entries.
					$title = Title::newMainPage();
				}
				return (string)new OOUI\ButtonWidget( [
					'label' => $this->msg( 'discussiontools-topicsubscription-pager-unsubscribe-button' )->text(),
					'classes' => [ 'ext-discussiontools-special-unsubscribe-button' ],
					'framed' => false,
					'flags' => [ 'destructive' ],
					'data' => [
						'item' => $row->sub_item,
						'title' => $title->getPrefixedText(),
					],
					'href' => $title->getLinkURL( [
						'action' => 'dtunsubscribe',
						'commentname' => $row->sub_item,
					] ),
					'infusable' => true,
				] );

			default:
				throw new InvalidArgumentException( "Unknown field '$field'" );
		}
	}

	/**
	 * Format items as a HTML list, unless there's just one item, in which case return it unwrapped.
	 * @param string[] $list HTML
	 * @return string HTML
	 */
	private function maybeFormatAsList( array $list ): string {
		if ( count( $list ) === 1 ) {
			return $list[0];
		} else {
			foreach ( $list as &$item ) {
				$item = Html::rawElement( 'li', [], $item );
			}
			return Html::rawElement( 'ul', [], implode( '', $list ) );
		}
	}

	private function formatValuePage( \stdClass $row ): string {
		$linkRenderer = $this->getLinkRenderer();

		if ( isset( $this->threadItemsByName[ $row->sub_item ] ) ) {
			$items = [];
			foreach ( $this->threadItemsByName[ $row->sub_item ] as $threadItem ) {
				if ( $threadItem->isCanonicalPermalink() ) {
					$items[] = $this->threadItemFormatter->formatLine( $threadItem, $this );
				}
			}
			if ( $items ) {
				return $this->maybeFormatAsList( $items );
			}

			// Found items in the permalink database, but they're not good permalinks.
			// TODO: We could link to the full list on Special:FindComment here
			// (but we don't link it from the mw.notify message either, at the moment).
		}

		// Permalink not available - display a plain link to the page title at the time of subscription
		$title = Title::makeTitleSafe( $row->sub_namespace, $row->sub_title );
		if ( !$title ) {
			// Handle invalid titles (T345648)
			return Html::element( 'span', [ 'class' => 'mw-invalidtitle' ],
				Linker::getInvalidTitleDescription(
					$this->getContext(), $row->sub_namespace, $row->sub_title )
				);
		}
		return $linkRenderer->makeLink( $title );
	}

	private function formatValueTopic( \stdClass $row ): string {
		$linkRenderer = $this->getLinkRenderer();

		$sectionText = $row->sub_section;
		$sectionLink = $row->sub_section;
		// Detect truncated section titles: either intentionally truncated by SubscriptionStore,
		// or incorrect multibyte truncation of old entries (T345648).
		$last = mb_substr( $sectionText, -1 );
		if ( $last !== '' && ( $last === "\x1f" || mb_ord( $last ) === false ) ) {
			$sectionText = substr( $sectionText, 0, -strlen( $last ) ) . $this->msg( 'ellipsis' )->text();
			$sectionLink = null;
		}

		if ( str_starts_with( $row->sub_item, 'p-topics-' ) ) {
			return '<em>' .
				$this->msg( 'discussiontools-topicsubscription-pager-newtopics-label' )->escaped() .
			'</em>';
		}

		if ( isset( $this->threadItemsByName[ $row->sub_item ] ) ) {
			$items = [];
			foreach ( $this->threadItemsByName[ $row->sub_item ] as $threadItem ) {
				if ( $threadItem->isCanonicalPermalink() ) {
					// TODO: Can we extract the current topic title out of $threadItem->getId() sometimes,
					// instead of always using the topic title at the time of subscription? (T295431)
					$items[] = $this->threadItemFormatter->makeLink( $threadItem, $sectionText );
				}
			}
			if ( $items ) {
				return $this->maybeFormatAsList( $items );
			}

			// Found items in the permalink database, but they're not good permalinks.
			// TODO: We could link to the full list on Special:FindComment here
			// (but we don't link it from the mw.notify message either, at the moment).
		}

		// Permalink not available - display a plain link to the section at the time of subscription
		if ( !$sectionLink ) {
			// We can't link to the section correctly, since the only link we have is truncated
			return htmlspecialchars( $sectionText );
		}
		$titleSection = Title::makeTitleSafe( $row->sub_namespace, $row->sub_title, $sectionLink );
		if ( !$titleSection ) {
			// Handle invalid titles of any other kind, just in case
			return htmlspecialchars( $sectionText );
		}
		return $linkRenderer->makeLink( $titleSection, $sectionText );
	}

	/**
	 * @inheritDoc
	 */
	protected function getCellAttrs( $field, $value ) {
		$attrs = parent::getCellAttrs( $field, $value );
		if ( $field === '_unsubscribe' ) {
			$attrs['style'] = 'text-align: center;';
		}
		return $attrs;
	}

	/**
	 * @inheritDoc
	 */
	public function getQueryInfo() {
		return [
			'tables' => [
				'discussiontools_subscription',
			],
			'fields' => [
				'sub_id',
				'sub_item',
				'sub_namespace',
				'sub_title',
				'sub_section',
				'sub_created',
				'sub_notified',
			],
			'conds' => [
				'sub_user' => $this->getUser()->getId(),
				'sub_state != ' . SubscriptionStore::STATE_UNSUBSCRIBED,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getDefaultSort() {
		return 'sub_created';
	}

	/**
	 * @inheritDoc
	 */
	public function getDefaultDirections() {
		return static::DIR_DESCENDING;
	}

	/**
	 * @inheritDoc
	 */
	public function getIndexField() {
		return [ static::INDEX_FIELDS[$this->mSort] ];
	}

	/**
	 * @inheritDoc
	 */
	protected function isFieldSortable( $field ) {
		// Hide the sort button for "Topic" as it is more accurately shown as "Created"
		return isset( static::INDEX_FIELDS[$field] ) && $field !== '_topic';
	}
}
