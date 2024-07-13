<?php

namespace MediaWiki\Extension\DiscussionTools;

use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentCommentItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentHeadingItem;
use MediaWiki\Extension\DiscussionTools\ThreadItem\ContentThreadItem;
use MediaWiki\Html\Html;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Linker\Linker;
use MediaWiki\Page\ParserOutputAccess;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\Utils\MWTimestamp;
use ParserOptions;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

class SpecialDiscussionToolsDebug extends FormSpecialPage {

	private LanguageFactory $languageFactory;
	private ParserOutputAccess $parserOutputAccess;
	private CommentParser $commentParser;

	public function __construct(
		LanguageFactory $languageFactory,
		ParserOutputAccess $parserOutputAccess,
		CommentParser $commentParser
	) {
		parent::__construct( 'DiscussionToolsDebug' );
		$this->languageFactory = $languageFactory;
		$this->parserOutputAccess = $parserOutputAccess;
		$this->commentParser = $commentParser;
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription() {
		return $this->msg( 'discussiontoolsdebug-title' );
	}

	/**
	 * @inheritDoc
	 */
	protected function getFormFields() {
		return [
			'pagetitle' => [
				'label-message' => 'discussiontoolsdebug-pagetitle',
				'name' => 'pagetitle',
				'type' => 'title',
				'required' => true,
				'exists' => true,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getSubpageField() {
		return 'pagetitle';
	}

	/**
	 * @inheritDoc
	 */
	protected function getDisplayFormat() {
		return 'ooui';
	}

	/**
	 * @inheritDoc
	 */
	public function requiresPost() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function onSubmit( array $data ) {
		$title = Title::newFromText( $data['pagetitle'] );

		$status = $this->parserOutputAccess->getParserOutput(
			$title->toPageRecord(),
			ParserOptions::newFromAnon()
		);
		if ( !$status->isOK() ) {
			return $status;
		}

		$parserOutput = $status->getValue();
		$html = $parserOutput->getText();

		$doc = DOMUtils::parseHTML( $html );
		$container = DOMCompat::getBody( $doc );
		$threadItemSet = $this->commentParser->parse( $container, $title->getTitleValue() );

		$out = $this->getOutput();

		$out->addHTML( $this->msg(
			'discussiontoolsdebug-intro',
			$title->getPrefixedText(),
			SpecialPage::getTitleFor(
				'ApiSandbox',
				false,
				'action=discussiontoolspageinfo&prop=threaditemshtml&excludesignatures=1&page='
					. urlencode( $title->getPrefixedText() )
			)->getFullText()
		)->parseAsBlock() );

		$pageLang = $this->languageFactory->getLanguage( $parserOutput->getLanguage() );
		$pageLangAttribs = [
			'lang' => $pageLang->getHtmlCode(),
			'dir' => $pageLang->getDir(),
			'class' => 'mw-content-' . $pageLang->getDir(),
		];

		foreach ( $threadItemSet->getThreadsStructured() as $thread ) {
			$out->addHTML( $this->formatComments( $thread, $pageLangAttribs ) );
		}

		$out->addModuleStyles( 'ext.discussionTools.debug.styles' );

		return true;
	}

	/**
	 * Format a thread item with replies.
	 *
	 * @param ContentThreadItem $comment
	 * @param array $pageLangAttribs
	 * @return string HTML
	 */
	private function formatComments( ContentThreadItem $comment, array $pageLangAttribs ) {
		if ( $comment instanceof ContentHeadingItem ) {
			$contents = '<span class="mw-dt-heading">' . $comment->getHTML() . '</span>';
		} else {
			Assert::precondition( $comment instanceof ContentCommentItem, 'Must be ContentCommentItem' );
			$contents =
				'<span class="mw-dt-comment-signature">' .
					'<span class="mw-dt-comment-author">' .
						Linker::userLink( 0, $comment->getAuthor() ) .
					'</span>' . ' ' .
					'(' . Linker::userTalkLink( 0, $comment->getAuthor() ) . ') ' .
					'<span class="mw-dt-comment-timestamp">' .
						htmlspecialchars( $this->getLanguage()->getHumanTimestamp(
							new MWTimestamp( $comment->getTimestamp()->getTimestamp() )
						) ) .
					'</span>' .
				'</span>' .
				Html::rawElement( 'div', $pageLangAttribs,
					'<div class="mw-dt-comment-body mw-parser-output">' . $comment->getBodyHTML( true ) . '</div>'
				);
		}
		$level = $comment->getLevel();

		$replies = '';
		foreach ( $comment->getReplies() as $reply ) {
			$replies .= $this->formatComments( $reply, $pageLangAttribs );
		}

		return Html::rawElement( $replies ? 'details' : 'div', [
			'open' => (bool)$replies,
			'class' => 'mw-dt-comment',
			'data-level' => $level,
		], ( $replies ? Html::rawElement( 'summary', [], $contents ) : $contents ) . $replies );
	}

}
