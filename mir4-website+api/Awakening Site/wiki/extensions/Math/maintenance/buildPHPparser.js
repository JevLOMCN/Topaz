#!/usr/bin/env node
/**
 * This script is used for generating Parser.php from parser.pegjs.
 * This is used for creating new versions of the parser expression grammar
 * in TexVC.
 *
 * Make sure to have the node dev dependencies from package.json installed.
 * Run it with: "$node maintenance/buildPHPparser.js <optional params>"
 *
 * Parameters can be defined over CLI parameters or by changing the
 * defaultPathXYZ constants in this file.
 *
 * @author Johannes Stegmüller
 */

'use strict';

const { program } = require( 'commander' );
const peggy = require( 'peggy' );
const phpeggy = require( 'phpeggy' );
const fs = require( 'fs' );
const GENERATE_INTENT_PARSER = false;

let defaultPathInput = './src/WikiTexVC/parser.pegjs';
let defaultPathOutput = './src/WikiTexVC/Parser.php';
if ( GENERATE_INTENT_PARSER ) {
	defaultPathInput = './src/WikiTexVC/parserintent.pegjs';
	defaultPathOutput = './src/WikiTexVC/ParserIntent.php';
}

const PHP_INSERTION_LINE = 9; // indicates where the 'use_xyz' statements are inserted

program
	.name( 'buildPHPparser' )
	.option( '-i, --input <string>',
		'path of input parser.pegjs file (*.pegjs)', defaultPathInput )
	.option( '-o, --output <string>',
		'path of generated output file (*.php)', defaultPathOutput )
	.option( '-d, --debug',
		'debug logging activated', false )
	.description( 'Generates Parser.php as output from parser.pegjs as input. ' +
		'This is used for for updating the parser expression grammar in WikiTexVC ' +
		'which is located in src/WikiTexVC' )
	.version( '0.1.0' );

program.parse();

const options = program.opts();
console.log( 'Running buildPHPparser.js with this configuration: \n' +
	'input path:\t' + options.input + '\n' +
	'output path:\t' + options.output );

const parserPeg = fs.readFileSync( options.input, 'utf-8' );
let parser = peggy.generate( parserPeg, {
	plugins: [ phpeggy ],
	cache: true,
	phpeggy: {
		parserClassName: GENERATE_INTENT_PARSER ? 'ParserIntent' : 'Parser',
		parserNamespace: 'MediaWiki\\Extension\\Math\\WikiTexVC'
	}
} );

const useStatements =
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Box;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Big;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\ChemFun2u;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\ChemWord;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Curly;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Declh;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Dollar;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\DQ;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\FQ;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Fun1;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Fun1nb;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Fun2;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Fun2nb;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Fun2sq;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Fun4;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Infix;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Literal;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Lr;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Matrix;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\Mhchem;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\UQ;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\Nodes\\TexArray;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\TexUtil;\n' +
	'use MediaWiki\\Extension\\Math\\WikiTexVC\\ParserUtil;';

function addUseStatements( p, lineStart = PHP_INSERTION_LINE ) {
	// Adding the specified use statements
	const splitParser = p.split( '\n' );
	splitParser.splice( lineStart, 0, useStatements );
	return splitParser.join( '\n' );
}

if ( !GENERATE_INTENT_PARSER ) {
	parser = addUseStatements( parser );
}

/**
 * Fixing phpeggy to denote regular expressions which
 * are \x12 to \x{0012} so php can interpret them.
 * can be removed when phpeggy is fixed, see:
 * https://phabricator.wikimedia.org/T320964
 */
const regexp = /\\x(\d\d)/g;
if ( options.debug ) {
	const matches = parser.match( regexp );
	for ( const match of matches ) {
		console.log( `Found ${ match }.` );
	}
}
parser = parser
	.replace( regexp, '\\x{00$1}' )
	// declare properties for the parser that were created dynamically before PHP 8.2
	.replace( /class Parser \{/, 'class Parser {\n    private $tu;\n    private $options;' );

fs.writeFileSync( options.output, parser );
console.log( 'Generated output file at: ' + options.output );
