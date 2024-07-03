<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace MediaWiki\SyntaxHighlight;

/**
 * Class provides functionality to map Ace lexer definitions
 */
class SyntaxHighlightAce {
	/** @var array<string,string[]> This map is inverted, because it is easier to maintain this way */
	private static $aceLexers = [
		'ABAP'         => [ 'abap' ],
		'ABC'          => [],
		'ActionScript' => [ 'actionscript', 'actionscript3' ],
		'ADA'          => [ 'ada', 'ada2005', 'ada95' ],
		'Apache_Conf'  => [ 'apache', 'apacheconf', 'aconf' ],
		'AsciiDoc'     => [],
		'Assembly_x86' => [ 'nasm' ],
		'AutoHotKey'   => [ 'autohotkey', 'ah' ],
		'BatchFile'    => [ 'bat', 'batch', 'dosbatch', 'winbatch' ],
		'C_Cpp'        => [ 'cpp', 'c++' ],
		'C9Search'     => [],
		'Cirru'        => [ 'cirru' ],
		'Clojure'      => [ 'clojure', 'clj' ],
		'Cobol'        => [ 'cobol' ],
		'coffee'       => [ 'coffee', 'coffeescript', 'coffee-script' ],
		'ColdFusion'   => [ 'cfm' ],
		'CSharp'       => [ 'csharp', '#' ],
		'CSS'          => [ 'css' ],
		'Curly'        => [],
		'D'            => [ 'd' ],
		'Dart'         => [ 'dart' ],
		'Diff'         => [ 'diff', 'udiff' ],
		'Django'       => [ 'django', 'html+django', 'html+jinja', 'htmldjango' ],
		'Dockerfile'   => [ 'Dockerfile', 'docker' ],
		'Dot'          => [],
		'Dummy'        => [],
		'DummySyntax'  => [],
		'Eiffel'       => [ 'eiffel' ],
		'EJS'          => [],
		'Elixir'       => [ 'elixer', 'ex', 'exs' ],
		'Elm'          => [ 'elm' ],
		'Erlang'       => [ 'erlang' ],
		'Forth'        => [],
		'Fortran'      => [ 'fortran' ],
		'FTL'          => [],
		'Gcode'        => [],
		'Gherkin'      => [ 'cucumber', 'gherkin' ],
		'Gitignore'    => [],
		'Glsl'         => [ 'glsl' ],
		'Gobstones'    => [],
		'golang'       => [ 'go' ],
		'Groovy'       => [ 'groovy' ],
		'HAML'         => [ 'haml' ],
		'Handlebars'   => [ 'html+handlebars' ],
		'Haskell'      => [ 'haskell', 'hs' ],
		'haXe'         => [ 'hx', 'haxe', 'hxsl' ],
		'HTML'         => [ 'html' ],
		'HTML_Elixir'  => [],
		'HTML_Ruby'    => [ 'rhtml', 'html+erb', 'html+ruby' ],
		'INI'          => [ 'ini', 'cfg', 'dosini' ],
		'Io'           => [ 'io' ],
		'Jack'         => [ '' ],
		'Jade'         => [ 'jade' ],
		'Java'         => [ 'java' ],
		'JavaScript'   => [ 'Javascript', 'js' ],
		'JSON'         => [ 'json' ],
		'JSONiq'       => [],
		'JSP'          => [ 'jsp' ],
		'JSX'          => [],
		'Julia'        => [ 'julia', 'jl' ],
		'LaTeX'        => [ 'latex' ],
		'Lean'         => [ 'lean' ],
		'LESS'         => [ 'less' ],
		'Liquid'       => [ 'liquid' ],
		'Lisp'         => [ 'lisp', 'common-lisp', 'cl' ],
		'LiveScript'   => [ 'Livescript', 'live-script' ],
		'LogiQL'       => [],
		'LSL'          => [ 'lsl' ],
		'Lua'          => [ 'lua' ],
		'LuaPage'      => [],
		'Lucene'       => [],
		'Makefile'     => [ 'make', 'makefile', 'mf', 'bsdmake' ],
		'Markdown'     => [],
		'Mask'         => [ 'mask' ],
		'MATLAB'       => [ 'matlab' ],
		'Maze'         => [],
		'MEL'          => [],
		'MUSHCode'     => [],
		'MySQL'        => [ 'mysql' ],
		'Nix'          => [ 'nix', 'nixos' ],
		'NSIS'         => [ 'nsis', 'nsi', 'nsh' ],
		'ObjectiveC'   => [ 'objectivec', 'objective-c', 'obj-c', 'objc',
			'objective-c++', 'objectivec++', 'obj-c++', 'objc++' ],
		'OCaml'        => [ 'ocaml' ],
		'Pascal'       => [ 'pascal', 'delphi', 'pas', 'objectpascal' ],
		'Perl'         => [ 'perl', 'pl', 'perl6', 'pl6' ],
		'pgSQL'        => [ 'postgresql', 'postgres' ],
		'PHP'          => [ 'php', 'php3', 'php4', 'php5', 'html+php' ],
		'Powershell'   => [ 'powershell', 'posh', 'ps1', 'psm1' ],
		'Praat'        => [ 'praat' ],
		'Prolog'       => [ 'prolog' ],
		'Properties'   => [ 'properties', 'jproperties' ],
		'Protobuf'     => [ 'protobuf', 'proto' ],
		'Python'       => [ 'python', 'py', 'sage', 'python3', 'py3' ],
		'R'            => [],
		'Razor'        => [],
		'RDoc'         => [],
		'RHTML'        => [], // HTML with Rcode, not ruby
		'RST'          => [ 'rst', 'rest', 'restructuredtext' ],
		'Ruby'         => [ 'ruby', 'rb', 'duby' ],
		'Rust'         => [ 'rust' ],
		'SASS'         => [ 'sass' ],
		'SCAD'         => [],
		'Scala'        => [ 'scala' ],
		'Scheme'       => [ 'scheme', 'scm' ],
		'SCSS'         => [ 'scss' ],
		'SH'           => [ 'sh', 'bash', 'ksh', 'shell' ],
		'SJS'          => [],
		'Smarty'       => [ 'smarty', 'html+smarty' ],
		'snippets'     => [],
		'Soy_Template' => [],
		'Space'        => [],
		'SQL'          => [ 'sql' ],
		'SQLServer'    => [],
		'Stylus'       => [],
		'SVG'          => [],
		'Swift'        => [ 'swift' ],
		'Tcl'          => [ 'tcl' ],
		'Tex'          => [ 'tex' ],
		'Text'         => [ 'text' ],
		'Textile'      => [],
		'Toml'         => [],
		'Twig'         => [ 'html+twig', 'twig' ],
		'Typescript'   => [ 'typescript', 'ts' ],
		'Vala'         => [ 'vala', 'vapi' ],
		'VBScript'     => [],
		'Velocity'     => [ 'velocity', 'html+velocity' ],
		'Verilog'      => [ 'verilog', 'v', 'systemverilog', 'sv' ],
		'VHDL'         => [ 'vhdl' ],
		'Wollok'       => [],
		'XML'          => [ 'xml' ],
		'XQuery'       => [ 'xquery', 'xqy', 'xq', 'xql', 'xqm' ],
		'YAML'         => [ 'yaml' ],
	];

	/**
	 * @return array<string,string>
	 */
	public static function getPygmentsToAceMap(): array {
		$result = [];
		foreach ( self::$aceLexers as $aceName => $pygmentsLexers ) {
			foreach ( $pygmentsLexers as $lexer ) {
				if ( strcasecmp( $lexer, $aceName ) === 0 ) {
					continue;
				}
				if ( !array_key_exists( $lexer, $result ) ) {
					$result[ $lexer ] = $aceName;
				}
			}
		}
		return $result;
	}
}
