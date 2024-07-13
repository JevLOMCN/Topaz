<?php

namespace MediaWiki\Extension\AbuseFilter\View;

use HTMLForm;
use IContextSource;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\EditBox\EditBoxBuilderFactory;
use MediaWiki\Extension\AbuseFilter\EditBox\EditBoxField;
use MediaWiki\Linker\LinkRenderer;
use Xml;

class AbuseFilterViewTools extends AbuseFilterView {

	/**
	 * @var EditBoxBuilderFactory
	 */
	private $boxBuilderFactory;

	/**
	 * @param AbuseFilterPermissionManager $afPermManager
	 * @param EditBoxBuilderFactory $boxBuilderFactory
	 * @param IContextSource $context
	 * @param LinkRenderer $linkRenderer
	 * @param string $basePageName
	 * @param array $params
	 */
	public function __construct(
		AbuseFilterPermissionManager $afPermManager,
		EditBoxBuilderFactory $boxBuilderFactory,
		IContextSource $context,
		LinkRenderer $linkRenderer,
		string $basePageName,
		array $params
	) {
		parent::__construct( $afPermManager, $context, $linkRenderer, $basePageName, $params );
		$this->boxBuilderFactory = $boxBuilderFactory;
	}

	/**
	 * Shows the page
	 */
	public function show() {
		$out = $this->getOutput();
		$out->enableOOUI();
		$out->addHelpLink( 'Extension:AbuseFilter/Rules format' );
		$request = $this->getRequest();

		if ( !$this->afPermManager->canUseTestTools( $this->getAuthority() ) ) {
			// TODO: the message still refers to the old rights
			$out->addWikiMsg( 'abusefilter-mustviewprivateoredit' );
			return;
		}

		// Header
		$out->addWikiMsg( 'abusefilter-tools-text' );

		$boxBuilder = $this->boxBuilderFactory->newEditBoxBuilder( $this, $this->getAuthority(), $out );

		// Expression evaluator
		$formDesc = [
			'rules' => [
				'class' => EditBoxField::class,
				'html' => $boxBuilder->buildEditBox(
					$request->getText( 'wpFilterRules' ),
					true,
					false,
					false
				)
			]
		];

		HTMLForm::factory( 'ooui', $formDesc, $this->getContext() )
			->setMethod( 'GET' )
			->setWrapperLegendMsg( 'abusefilter-tools-expr' )
			->setSubmitTextMsg( 'abusefilter-tools-submitexpr' )
			->setSubmitID( 'mw-abusefilter-submitexpr' )
			->setFooterHtml( Xml::element( 'pre', [ 'id' => 'mw-abusefilter-expr-result' ], ' ' ) )
			->prepareForm()
			->displayForm( false );

		$out->addModules( 'ext.abuseFilter.tools' );

		if ( $this->afPermManager->canEdit( $this->getAuthority() ) ) {
			// Hacky little box to re-enable autoconfirmed if it got disabled
			$formDescriptor = [
				'RestoreAutoconfirmed' => [
					'label-message' => 'abusefilter-tools-reautoconfirm-user',
					'type' => 'user',
					'name' => 'wpReAutoconfirmUser',
					'id' => 'reautoconfirm-user',
					'infusable' => true
				],
			];
			$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
			$htmlForm->setWrapperLegendMsg( 'abusefilter-tools-reautoconfirm' )
				->setSubmitTextMsg( 'abusefilter-tools-reautoconfirm-submit' )
				->setSubmitName( 'wpReautoconfirmSubmit' )
				->setSubmitID( 'mw-abusefilter-reautoconfirmsubmit' )
				->prepareForm()
				->displayForm( false );
		}
	}
}
