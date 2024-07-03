<?php

namespace MediaWiki\Extension\Math;

use Exception;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use RuntimeException;
use stdClass;

/**
 * @author Moritz Schubotz
 */
class MathMathMLCli extends MathMathML {

	/**
	 * @param MathRenderer[] $renderers
	 * @return bool
	 */
	public static function batchEvaluate( array $renderers ) {
		$req = [];
		foreach ( $renderers as $renderer ) {
			'@phan-var MathMathMLCli $renderer';
			// checking if the rendering is in the database is no security issue since only the md5
			// hash of the user input string will be sent to the database
			if ( !$renderer->isInDatabase() ) {
				$req[] = $renderer->getMathoidCliQuery();
			}
		}
		if ( count( $req ) === 0 ) {
			return true;
		}
		$exitCode = 1;
		$res = self::evaluateWithCli( $req, $exitCode );
		foreach ( $renderers as $renderer ) {
			'@phan-var MathMathMLCli $renderer';
			if ( !$renderer->isInDatabase() ) {
				$renderer->initializeFromCliResponse( $res );
			}
		}

		return true;
	}

	/**
	 * @param stdClass $res
	 * @return bool
	 */
	private function initializeFromCliResponse( $res ) {
		global $wgMathoidCli;
		if ( !property_exists( $res, $this->getInputHash() ) ) {
			$this->lastError =
				$this->getError( 'math_mathoid_error', 'cli',
					var_export( get_object_vars( $res ), true ) );
			return false;
		}
		if ( $this->isEmpty() ) {
			$this->lastError = $this->getError( 'math_empty_tex' );
			return false;
		}
		$response = $res->{$this->getInputHash()};
		if ( !$response->success ) {
			$this->lastError = $this->renderError( $response );
			return false;
		}
		$this->texSecure = true;
		$this->tex = $response->sanetex;
		// The host name is only relevant for the debugging. So using file:// to indicate that the
		// cli interface seems to be OK.
		$this->processJsonResult( $response, 'file://' . $wgMathoidCli[0] );
		$this->mathStyle = $response->mathoidStyle;
		$this->changed = true;
		return true;
	}

	public function renderError( $response ) {
		$msg = $response->error;
		try {
			switch ( $response->detail->status ) {
				case "F":
					$msg .= "\n Found {$response->detail->details}" .
							$this->appendLocationInfo( $response );
					break;
				case 'S':
				case "C":
					$msg .= $this->appendLocationInfo( $response );
					break;
				case '-':
					// we do not know any cases that triggers this error
			}
		} catch ( Exception $e ) {
			// use default error message
		}

		return $this->getError( 'math_mathoid_error', 'cli', $msg );
	}

	/**
	 * @return array
	 */
	public function getMathoidCliQuery() {
		return [
			'query' => [
				'q' => $this->getTex(),
				'type' => $this->getInputType(),
				'hash' => $this->getInputHash(),
			],
		];
	}

	/**
	 * @param mixed $req request
	 * @param int|null &$exitCode
	 * @return mixed
	 */
	private static function evaluateWithCli( $req, &$exitCode = null ) {
		global $wgMathoidCli;
		$json_req = json_encode( $req );
		$cmd = MediaWikiServices::getInstance()->getShellCommandFactory()->create();
		$cmd->params( $wgMathoidCli );
		$cmd->input( $json_req );
		$result = $cmd->execute();
		if ( $result->getExitCode() != 0 ) {
			$errorMsg = $result->getStderr();
			LoggerFactory::getInstance( 'Math' )->error( 'Can not process {req} with config
			 {conf} returns {res}', [
				'req' => $req,
				'conf' => var_export( $wgMathoidCli, true ),
				'res' => var_export( $result, true ),
			] );
			throw new RuntimeException( "Failed to execute Mathoid cli '$wgMathoidCli[0]', reason: $errorMsg" );
		}
		$res = json_decode( $result->getStdout() );
		if ( !$res ) {
			throw new RuntimeException( "Mathoid cli response '$res' is no valid JSON file." );
		}

		return $res;
	}

	public function render() {
		if ( $this->getLastError() ) {
			return false;
		}

		return true;
	}

	protected function doCheck(): bool {
		// avoid that restbase is called if check is set to always
		return $this->texSecure;
	}

	/**
	 * @param stdClass $response object from cli
	 * @return string containing the location information
	 */
	private function appendLocationInfo( $response ) {
		return "in {$response->detail->line}:{$response->detail->column}";
	}
}
