<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaStandalone;

use Exception;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaEngine;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Parser\ParserOutput;
use Wikimedia\AtEase\AtEase;

class LuaStandaloneEngine extends LuaEngine {
	/** @var int|null */
	protected static $clockTick;
	/** @var array|false */
	public $initialStatus;

	/**
	 * @var LuaStandaloneInterpreter
	 */
	protected $interpreter;

	public function load() {
		parent::load();
		if ( php_uname( 's' ) === 'Linux' ) {
			$this->initialStatus = $this->interpreter->getStatus();
		} else {
			$this->initialStatus = false;
		}
	}

	/** @inheritDoc */
	public function getPerformanceCharacteristics() {
		return [
			'phpCallsRequireSerialization' => true,
		];
	}

	/** @inheritDoc */
	public function reportLimitData( ParserOutput $parserOutput ) {
		try {
			$this->load();
		} catch ( Exception $e ) {
			return;
		}
		if ( $this->initialStatus ) {
			$status = $this->interpreter->getStatus();
			$parserOutput->setLimitReportData( 'scribunto-limitreport-timeusage',
				[
					sprintf( "%.3f", $status['time'] / $this->getClockTick() ),
					// Strip trailing .0s
					rtrim( rtrim( sprintf( "%.3f", $this->options['cpuLimit'] ), '0' ), '.' )
				]
			);
			$parserOutput->setLimitReportData( 'scribunto-limitreport-virtmemusage',
				[
					$status['vsize'],
					$this->options['memoryLimit']
				]
			);
			$parserOutput->setLimitReportData( 'scribunto-limitreport-estmemusage',
				$status['vsize'] - $this->initialStatus['vsize']
			);
		}
		$logs = $this->getLogBuffer();
		if ( $logs !== '' ) {
			$parserOutput->addModules( [ 'ext.scribunto.logs' ] );
			$parserOutput->setLimitReportData( 'scribunto-limitreport-logs', $logs );
		}
	}

	/** @inheritDoc */
	public function formatLimitData( $key, &$value, &$report, $isHTML, $localize ) {
		switch ( $key ) {
			case 'scribunto-limitreport-logs':
				if ( $isHTML ) {
					$report .= $this->formatHtmlLogs( $value, $localize );
				}
				return false;
		}
		return true;
	}

	/**
	 * @return int
	 */
	protected function getClockTick() {
		if ( self::$clockTick === null ) {
			AtEase::suppressWarnings();
			self::$clockTick = intval( shell_exec( 'getconf CLK_TCK' ) );
			AtEase::restoreWarnings();
			if ( !self::$clockTick ) {
				self::$clockTick = 100;
			}
		}
		return self::$clockTick;
	}

	/**
	 * @return LuaStandaloneInterpreter
	 */
	protected function newInterpreter() {
		return new LuaStandaloneInterpreter( $this, $this->options + [
			'logger' => LoggerFactory::getInstance( 'Scribunto' )
		] );
	}

	/** @inheritDoc */
	public function getSoftwareInfo( array &$software ) {
		$ver = LuaStandaloneInterpreter::getLuaVersion( $this->options );
		if ( $ver !== null ) {
			if ( substr( $ver, 0, 6 ) === 'LuaJIT' ) {
				$software['[http://luajit.org/ LuaJIT]'] = str_replace( 'LuaJIT ', '', $ver );
			} else {
				$software['[http://www.lua.org/ Lua]'] = str_replace( 'Lua ', '', $ver );
			}
		}
	}
}
