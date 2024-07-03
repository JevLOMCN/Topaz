<?php

namespace MediaWiki\Extension\AbuseFilter\Api;

use ApiBase;
use ApiMain;
use MediaWiki\Extension\AbuseFilter\BlockAutopromoteStore;
use MediaWiki\ParamValidator\TypeDef\UserDef;
use Wikimedia\ParamValidator\ParamValidator;

class UnblockAutopromote extends ApiBase {

	/** @var BlockAutopromoteStore */
	private $afBlockAutopromoteStore;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param BlockAutopromoteStore $afBlockAutopromoteStore
	 */
	public function __construct(
		ApiMain $main,
		$action,
		BlockAutopromoteStore $afBlockAutopromoteStore
	) {
		parent::__construct( $main, $action );
		$this->afBlockAutopromoteStore = $afBlockAutopromoteStore;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->checkUserRightsAny( 'abusefilter-modify' );

		$params = $this->extractRequestParams();
		$target = $params['user'];

		$block = $this->getAuthority()->getBlock();
		if ( $block && $block->isSitewide() ) {
			$this->dieBlocked( $block );
		}

		$msg = $this->msg( 'abusefilter-tools-restoreautopromote' )->inContentLanguage()->text();
		$res = $this->afBlockAutopromoteStore->unblockAutopromote( $target, $this->getUser(), $msg );

		if ( !$res ) {
			$this->dieWithError( [ 'abusefilter-reautoconfirm-none', $target->getName() ], 'notsuspended' );
		}

		$finalResult = [ 'user' => $target->getName() ];
		$this->getResult()->addValue( null, $this->getModuleName(), $finalResult );
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	public function mustBePosted() {
		return true;
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'user' => [
				ParamValidator::PARAM_TYPE => 'user',
				ParamValidator::PARAM_REQUIRED => true,
				UserDef::PARAM_RETURN_OBJECT => true,
				UserDef::PARAM_ALLOWED_USER_TYPES => [ 'name' ],
			],
			'token' => null,
		];
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @codeCoverageIgnore Merely declarative
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=abusefilterunblockautopromote&user=Example&token=123ABC'
				=> 'apihelp-abusefilterunblockautopromote-example-1',
		];
	}
}
