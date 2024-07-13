<?php

namespace MediaWiki\Extension\Math\InputCheck;

use Exception;
use MediaWiki\Extension\Math\Hooks\HookRunner;
use MediaWiki\Extension\Math\MathRenderer;
use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWiki\HookContainer\HookContainer;
use Message;
use WANObjectCache;

class LocalChecker extends BaseChecker {

	public const VERSION = 1;
	private const VALID_TYPES = [ 'tex', 'inline-tex', 'chem' ];
	private ?Message $error = null;
	private ?string $mathMl = null;

	private string $type;
	private WANObjectCache $cache;

	private bool $isChecked = false;
	private ?MathRenderer $context = null;
	private ?HookContainer $hookContainer = null;

	public function __construct( WANObjectCache $cache, $tex = '', string $type = 'tex', bool $purge = false ) {
		$this->cache = $cache;
		parent::__construct( $tex, $purge );
		$this->type = $type;
	}

	public function isValid(): bool {
		$this->run();
		return parent::isValid();
	}

	public function getValidTex(): ?string {
		$this->run();
		return parent::getValidTex();
	}

	public function run() {
		if ( $this->isChecked ) {
			return;
		}
		if ( !in_array( $this->type, self::VALID_TYPES, true ) ) {
			$this->error = $this->errorObjectToMessage(
				(object)[ "error" => "Unsupported type passed to LocalChecker: " . $this->type ], "LocalCheck" );
			return;
		}
		try {
			$cacheInputKey = $this->getInputCacheKey();
			if ( $this->purge ) {
				$this->cache->delete( $cacheInputKey, WANObjectCache::TTL_INDEFINITE );
			}
			$result = $this->cache->getWithSetCallback(
				$cacheInputKey,
				WANObjectCache::TTL_INDEFINITE,
				[ $this, 'runCheck' ],
				[ 'version' => self::VERSION ],
			);
		} catch ( Exception $e ) { // @codeCoverageIgnoreStart
			// This is impossible since errors are thrown only if the option debug would be set.
			$this->error = Message::newFromKey( 'math_failure' );
			return;
			// @codeCoverageIgnoreEnd
		}
		if ( $result['status'] === '+' ) {
			$this->isValid = true;
			$this->validTeX = $result['output'];
			$this->mathMl = $result['mathml'];
		} else {
			$this->error = $this->errorObjectToMessage(
				(object)[ "error" => (object)$result["error"] ],
				"LocalCheck" );
		}
		$this->isChecked = true;
	}

	/**
	 * Returns the string of the last error.
	 * @return ?Message
	 */
	public function getError(): ?Message {
		$this->run();
		return $this->error;
	}

	public function getPresentationMathMLFragment(): ?string {
		$this->run();
		return $this->mathMl;
	}

	public function getInputCacheKey(): string {
		return $this->cache->makeGlobalKey(
			self::class,
			md5( $this->type . '-' . $this->inputTeX )
		);
	}

	public function runCheck(): array {
		if ( $this->type == 'chem' ) {
			$options = [ 'usemhchem' => true, 'usemhchemtexified' => true ];
			$texifyMhchem = true;
		} else {
			$options = [];
			$texifyMhchem = false;
		}

		try {
			$warnings = [];
			$result = ( new TexVC() )->check( $this->inputTeX, $options, $warnings, $texifyMhchem );
		} catch ( Exception $e ) { // @codeCoverageIgnoreStart
			// This is impossible since errors are thrown only if the option debug would be set.
			$this->error = Message::newFromKey( 'math_failure' );

			return [];
			// @codeCoverageIgnoreEnd
		}
		if ( $result['status'] === '+' ) {
			$result['mathml'] = $result['input']->renderMML();
			$out = [
				'status' => '+',
				'output' => $result['output'],
				'mathml' => $result['mathml']
			];
		} else {
			$out = [
				'status' => $result['status'],
				'error' => $result['error'],
			];
		}
		if ( $this->context !== null && $this->hookContainer !== null ) {
			$resultObject = (object)$result;
			( new HookRunner( $this->hookContainer ) )->onMathRenderingResultRetrieved(
				$this->context,
				$resultObject
			);
		}
		return $out;
	}

	public function setContext( ?MathRenderer $renderer ): void {
		$this->context = $renderer;
	}

	public function setHookContainer( ?HookContainer $hookContainer ): void {
		$this->hookContainer = $hookContainer;
	}
}
