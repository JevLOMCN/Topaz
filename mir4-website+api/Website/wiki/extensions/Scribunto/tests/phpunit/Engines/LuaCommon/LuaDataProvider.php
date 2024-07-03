<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

use Exception;
use Iterator;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaEngine;
use MediaWiki\Title\Title;

class LuaDataProvider implements Iterator {
	/** @var LuaEngine|null */
	protected $engine = null;
	/** @var mixed|null */
	protected $exports = null;
	/** @var int */
	protected $key = 1;

	/**
	 * @param LuaEngine $engine
	 * @param string $moduleName
	 */
	public function __construct( $engine, $moduleName ) {
		$this->engine = $engine;
		$this->key = 1;
		$module = $engine->fetchModuleFromParser(
			Title::makeTitle( NS_MODULE, $moduleName )
		);
		if ( $module === null ) {
			throw new Exception( "Failed to load module $moduleName" );
		}
		// Calling executeModule with null isn't the best idea, since it brings
		// the whole export table into PHP and throws away metatables and such,
		// but for this use case, we don't have anything like that to worry about
		$this->exports = $engine->executeModule( $module->getInitChunk(), null, null );
	}

	public function destroy() {
		$this->engine = null;
		$this->exports = null;
	}

	public function rewind(): void {
		$this->key = 1;
	}

	public function valid(): bool {
		return $this->key <= $this->exports['count'];
	}

	#[\ReturnTypeWillChange]
	public function key() {
		return $this->key;
	}

	public function next(): void {
		$this->key++;
	}

	#[\ReturnTypeWillChange]
	public function current() {
		return $this->engine->getInterpreter()->callFunction( $this->exports['provide'], $this->key );
	}

	/**
	 * @param string $key Test to run
	 * @return mixed Test result
	 */
	public function run( $key ) {
		[ $ret ] = $this->engine->getInterpreter()->callFunction( $this->exports['run'], $key );
		return $ret;
	}
}
