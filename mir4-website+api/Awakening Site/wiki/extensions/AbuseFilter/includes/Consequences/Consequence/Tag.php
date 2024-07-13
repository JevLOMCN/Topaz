<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences\Consequence;

use MediaWiki\Extension\AbuseFilter\ChangeTags\ChangeTagger;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;

/**
 * Consequence that adds change tags once the edit is saved
 */
class Tag extends Consequence {
	/** @var string[] */
	private $tags;
	/** @var ChangeTagger */
	private $tagger;

	/**
	 * @param Parameters $parameters
	 * @param string[] $tags
	 * @param ChangeTagger $tagger
	 */
	public function __construct( Parameters $parameters, array $tags, ChangeTagger $tagger ) {
		parent::__construct( $parameters );
		$this->tags = $tags;
		$this->tagger = $tagger;
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): bool {
		$this->tagger->addTags( $this->parameters->getActionSpecifier(), $this->tags );
		return true;
	}
}
