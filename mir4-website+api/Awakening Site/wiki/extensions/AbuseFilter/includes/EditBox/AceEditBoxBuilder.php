<?php

namespace MediaWiki\Extension\AbuseFilter\EditBox;

use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Parser\AbuseFilterTokenizer;
use MediaWiki\Extension\AbuseFilter\Parser\FilterEvaluator;
use MediaWiki\Output\OutputPage;
use MediaWiki\Permissions\Authority;
use MessageLocalizer;
use OOUI\ButtonWidget;
use OOUI\HorizontalLayout;
use OOUI\Widget;
use Xml;

/**
 * Class responsible for building filter edit boxes with both the Ace and the plain version
 */
class AceEditBoxBuilder extends EditBoxBuilder {

	/** @var PlainEditBoxBuilder */
	private $plainBuilder;

	/**
	 * @inheritDoc
	 * @param PlainEditBoxBuilder $plainBuilder
	 */
	public function __construct(
		AbuseFilterPermissionManager $afPermManager,
		KeywordsManager $keywordsManager,
		MessageLocalizer $messageLocalizer,
		Authority $authority,
		OutputPage $output,
		PlainEditBoxBuilder $plainBuilder
	) {
		parent::__construct( $afPermManager, $keywordsManager, $messageLocalizer, $authority, $output );
		$this->plainBuilder = $plainBuilder;
	}

	/**
	 * @inheritDoc
	 */
	protected function getEditBox( string $rules, bool $isUserAllowed, bool $externalForm ): string {
		$rules = rtrim( $rules ) . "\n";

		$attribs = [
			// Rules are in English
			'dir' => 'ltr',
			'name' => 'wpAceFilterEditor',
			'id' => 'wpAceFilterEditor',
			'class' => 'mw-abusefilter-editor'
		];
		$rulesContainer = Xml::element( 'div', $attribs, $rules );
		$editorConfig = $this->getAceConfig( $isUserAllowed );
		$this->output->addJsConfigVars( 'aceConfig', $editorConfig );
		return $rulesContainer . $this->plainBuilder->getEditBox( $rules, $isUserAllowed, $externalForm );
	}

	/**
	 * @inheritDoc
	 */
	protected function getEditorControls(): Widget {
		$base = parent::getEditorControls();
		$switchEditor = new ButtonWidget(
			[
				'label' => $this->localizer->msg( 'abusefilter-edit-switch-editor' )->text(),
				'id' => 'mw-abusefilter-switcheditor'
			]
		);
		return new Widget( [
			'content' => new HorizontalLayout( [
				'items' => [ $switchEditor, $base ]
			] )
		] );
	}

	/**
	 * Extract values for syntax highlight
	 *
	 * @param bool $canEdit
	 * @return array
	 */
	private function getAceConfig( bool $canEdit ): array {
		$values = $this->keywordsManager->getBuilderValues();
		$deprecatedVars = $this->keywordsManager->getDeprecatedVariables();

		$builderVariables = implode( '|', array_keys( $values['vars'] ) );
		$builderFunctions = implode( '|', array_keys( FilterEvaluator::FUNCTIONS ) );
		// AbuseFilterTokenizer::KEYWORDS also includes constants (true, false and null),
		// but Ace redefines these constants afterwards so this will not be an issue
		$builderKeywords = implode( '|', AbuseFilterTokenizer::KEYWORDS );
		// Extract operators from tokenizer like we do in AbuseFilterParserTest
		$operators = implode( '|', array_map( static function ( $op ) {
			return preg_quote( $op, '/' );
		}, AbuseFilterTokenizer::OPERATORS ) );
		$deprecatedVariables = implode( '|', array_keys( $deprecatedVars ) );
		$disabledVariables = implode( '|', array_keys( $this->keywordsManager->getDisabledVariables() ) );

		return [
			'variables' => $builderVariables,
			'functions' => $builderFunctions,
			'keywords' => $builderKeywords,
			'operators' => $operators,
			'deprecated' => $deprecatedVariables,
			'disabled' => $disabledVariables,
			'aceReadOnly' => !$canEdit
		];
	}
}
