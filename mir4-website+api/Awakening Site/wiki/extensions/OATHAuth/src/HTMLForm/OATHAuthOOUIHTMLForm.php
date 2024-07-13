<?php

namespace MediaWiki\Extension\OATHAuth\HTMLForm;

use IContextSource;
use MediaWiki\Extension\OATHAuth\IModule;
use MediaWiki\Extension\OATHAuth\OATHUser;
use MediaWiki\Extension\OATHAuth\OATHUserRepository;
use MediaWiki\Logger\LoggerFactory;
use OOUI\FieldsetLayout;
use OOUI\HtmlSnippet;
use OOUI\Layout;
use OOUI\PanelLayout;
use OOUI\Widget;
use OOUIHTMLForm;
use Psr\Log\LoggerInterface;

abstract class OATHAuthOOUIHTMLForm extends OOUIHTMLForm implements IManageForm {
	/**
	 * @var OATHUser
	 */
	protected $oathUser;
	/**
	 * @var OATHUserRepository
	 */
	protected $oathRepo;
	/**
	 * @var IModule
	 */
	protected $module;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @var Layout|null
	 */
	protected $layoutContainer = null;

	/**
	 * Make the form-wrapper panel padded
	 * @var bool
	 */
	protected $panelPadded = true;

	/**
	 * Make the form-wrapper panel framed
	 * @var bool
	 */
	protected $panelFramed = true;

	/**
	 * Initialize the form
	 *
	 * @param OATHUser $oathUser
	 * @param OATHUserRepository $oathRepo
	 * @param IModule $module
	 * @param IContextSource $context
	 */
	public function __construct(
		OATHUser $oathUser,
		OATHUserRepository $oathRepo,
		IModule $module,
		IContextSource $context
	) {
		$this->oathUser = $oathUser;
		$this->oathRepo = $oathRepo;
		$this->module = $module;
		$this->logger = $this->getLogger();

		parent::__construct( $this->getDescriptors(), $context, "oathauth" );
	}

	/**
	 * @inheritDoc
	 */
	public function show( $layout = null ) {
		$this->layoutContainer = $layout;
		return parent::show();
	}

	/**
	 * @inheritDoc
	 */
	public function displayForm( $submitResult ) {
		if ( !$this->layoutContainer instanceof Layout ) {
			parent::displayForm( $submitResult );
			return;
		}

		$this->layoutContainer->appendContent( new HtmlSnippet(
			$this->getHTML( $submitResult )
		) );
	}

	/**
	 * @return array
	 */
	protected function getDescriptors() {
		return [];
	}

	private function getLogger(): LoggerInterface {
		return LoggerFactory::getInstance( 'authentication' );
	}

	/**
	 * @inheritDoc
	 */
	protected function wrapFieldSetSection( $legend, $section, $attributes, $isRoot ) {
		// to get a user visible effect, wrap the fieldset into a framed panel layout
		$layout = new PanelLayout( [
			'expanded' => false,
			'infusable' => false,
			'padded' => $this->panelPadded,
			'framed' => $this->panelFramed,
		] );

		$layout->appendContent(
			new FieldsetLayout( [
				'label' => $legend,
				'infusable' => false,
				'items' => [
					new Widget( [
						'content' => new HtmlSnippet( $section )
					] ),
				],
			] + $attributes )
		);
		return $layout;
	}

	/**
	 * @param array $formData
	 * @return array|bool
	 */
	abstract public function onSubmit( array $formData );

	abstract public function onSuccess();
}
