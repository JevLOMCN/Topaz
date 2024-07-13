<?php declare( strict_types=1 );
/**
 * Copyright 2021 Timo Tijhof
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @file
 * @license Apache-2.0
 */

namespace Wikimedia\Minify;

/**
 * Implementation of `minify` CLI.
 *
 * @internal For use by `bin/minify` only.
 */
final class Cli {
	/** @var int $exitCode */
	private $exitCode = 0;
	/** @var resource $in */
	private $in;
	/** @var resource $out */
	private $out;
	/** @var string $self */
	private $self;
	/** @var string $command */
	private $command;
	/** @var string[] $params */
	private $params;

	/**
	 * @param resource $in An open stream for reading input with `fgets()`
	 * @param resource $out An open stream for writing output with `fwrite()`
	 * @param string[] $argv
	 */
	public function __construct( $in, $out, array $argv ) {
		$this->in = $in;
		$this->out = $out;
		$this->self = basename( $argv[0] ?? '/minify' );
		$this->command = $argv[1] ?? '';
		$this->params = array_slice( $argv, 2 );
	}

	/** Perform the specified command. */
	public function run(): void {
		try {
			switch ( $this->command ) {
			case 'css':
				$this->runCss( ...$this->params );
				break;
			case 'css-remap':
				$this->runCssRemap( ...$this->params );
				break;
			case 'js':
				$this->runJs( ...$this->params );
				break;
			case 'jsmap-web':
				$this->runJsMapWeb( ...$this->params );
				break;
			case 'jsmap-raw':
				$this->runJsMapRaw( ...$this->params );
				break;
			case '':
			case 'help':
				$this->exitCode = 1;
				$this->help();
				break;
			default:
				$this->error( 'Unknown command' );
				break;
			}
		} catch ( \Throwable $e ) {
			$this->exitCode = 1;
			$this->output( (string)$e );
		}
	}

	private function runCss( string $file = null ): void {
		$data = $file === null ? stream_get_contents( $this->in ) : file_get_contents( $file );
		$this->output( CSSMin::minify( $data ) );
	}

	private function runCssRemap( string $file = null ): void {
		if ( $file === null ) {
			$this->error( 'Remapping requires a filepath' );
			return;
		}
		$fulldir = dirname( realpath( $file ) );
		$data = file_get_contents( $file );
		$data = CSSMin::remap( $data, $fulldir, $fulldir );
		$this->output( CSSMin::minify( $data ) );
	}

	private function runJs( string $file = null ): void {
		$data = $file === null ? stream_get_contents( $this->in ) : file_get_contents( $file );
		$this->output( JavaScriptMinifier::minify( $data ) );
	}

	private function runJsMapWeb( string $file = null ): void {
		$data = $file === null ? stream_get_contents( $this->in ) : file_get_contents( $file );
		$sourceName = $file === null ? 'file.js' : basename( $file );
		$mapper = JavaScriptMinifier::createSourceMapState();
		$mapper->addSourceFile( $sourceName, $data, true );
		$this->output( rtrim( $mapper->getSourceMap(), "\n" ) );
	}

	private function runJsMapRaw( string $file = null ): void {
		$data = $file === null ? stream_get_contents( $this->in ) : file_get_contents( $file );
		$sourceName = $file === null ? 'file.js' : basename( $file );
		$mapper = JavaScriptMinifier::createSourceMapState();
		$mapper->addSourceFile( $sourceName, $data, true );
		$this->output( rtrim( $mapper->getRawSourceMap(), "\n" ) );
	}

	private function help(): void {
		$this->output( <<<TEXT
usage: {$this->self} <command>

commands:
   css [<file>]        Minify input data as CSS code, and write to output.
                       Reads from stdin by default, or can read from a file.
   css-remap <file>    Remap and process any "@embed" comments in a CSS file,
                       and write the minified code to output.
   js  [<file>]        Minify input data as JavaScript code, write to output.
                       Reads from stdin by default, or can read from a file.
   jsmap-raw [<file>]  Minify JavaScript code and write a raw source map to
                       output. Such a source map should not be delivered over
                       HTTP due to XSSI concerns.
   jsmap-web [<file>]  Minify JavaScript code and write a source map with XSSI
                       prefix.
TEXT
		);
	}

	private function error( string $text ): void {
		$this->exitCode = 1;
		$this->output( "\n{$this->self} error: $text\n\n------\n" );
		$this->help();
	}

	private function output( string $text ): void {
		fwrite( $this->out, $text . "\n" );
	}

	/** @return int */
	public function getExitCode(): int {
		return $this->exitCode;
	}
}
