<?php

namespace MediaWiki\Extension\DiscussionTools;

use HTMLForm;
use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\FormSpecialPage;

class SpecialFindComment extends FormSpecialPage {

	private const LIST_LIMIT = 50;

	private ThreadItemStore $threadItemStore;
	private ThreadItemFormatter $threadItemFormatter;

	public function __construct(
		ThreadItemStore $threadItemStore,
		ThreadItemFormatter $threadItemFormatter
	) {
		parent::__construct( 'FindComment' );
		$this->threadItemStore = $threadItemStore;
		$this->threadItemFormatter = $threadItemFormatter;
	}

	/**
	 * @inheritDoc
	 */
	protected function getFormFields() {
		return [
			'idorname' => [
				'label-message' => 'discussiontools-findcomment-label-idorname',
				'name' => 'idorname',
				'type' => 'text',
				'required' => true,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getSubpageField() {
		return 'idorname';
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
	protected function getShowAlways() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function alterForm( HTMLForm $form ) {
		$form->setWrapperLegend( true );
		$form->setSubmitTextMsg( 'discussiontools-findcomment-label-search' );
	}

	private string $idOrName;

	/**
	 * @inheritDoc
	 */
	public function onSubmit( array $data ) {
		// They are correctly written with underscores, but allow spaces too for consistency with
		// the behavior of internal wiki links.
		$this->idOrName = str_replace( ' ', '_', $data['idorname'] );
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onSuccess() {
		$out = $this->getOutput();
		$results = false;

		if ( $this->idOrName ) {
			$byId = $this->threadItemStore->findNewestRevisionsById( $this->idOrName, static::LIST_LIMIT + 1 );
			if ( $byId ) {
				$this->displayItems( $byId, 'discussiontools-findcomment-results-id' );
				$results = true;
			}

			$byName = $this->threadItemStore->findNewestRevisionsByName( $this->idOrName, static::LIST_LIMIT + 1 );
			if ( $byName ) {
				$this->displayItems( $byName, 'discussiontools-findcomment-results-name' );
				$results = true;
			}
		}

		if ( $results ) {
			$out->addHTML(
				$this->msg( 'discussiontools-findcomment-gotocomment', $this->idOrName )->parseAsBlock() );
		} else {
			$out->addHTML(
				$this->msg( 'discussiontools-findcomment-noresults' )->parseAsBlock() );
		}
	}

	private function displayItems( array $threadItems, string $msgKey ) {
		$out = $this->getOutput();

		$list = [];
		foreach ( $threadItems as $item ) {
			if ( count( $list ) === static::LIST_LIMIT ) {
				break;
			}
			$line = $this->threadItemFormatter->formatLine( $item, $this );
			$list[] = Html::rawElement( 'li', [], $line );
		}

		$out->addHTML( $this->msg( $msgKey, count( $list ) )->parseAsBlock() );
		$out->addHTML( Html::rawElement( 'ul', [], implode( '', $list ) ) );
		if ( count( $threadItems ) > static::LIST_LIMIT ) {
			$out->addHTML( $this->msg( 'morenotlisted' )->parseAsBlock() );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription() {
		return $this->msg( 'discussiontools-findcomment-title' );
	}
}
