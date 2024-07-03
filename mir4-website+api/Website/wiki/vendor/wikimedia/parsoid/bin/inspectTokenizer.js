#!/usr/bin/env node

'use strict';

var yargs = require('yargs');
var PegTokenizer = require('../lib/wt2html/tokenizer.js').PegTokenizer;
var fs = require('fs');

yargs.usage('Inspect the PEG.js grammar and generated source.');

//	'Inspect the PEG.js grammar and generated source');

yargs.options({
	'source': {
		description: 'Show tokenizer source code',
		'boolean': true,
		'default': false,
	},

	'rules': {
		description: 'Show rule action source code',
		'boolean': true,
		'default': false,
	},

	'callgraph': {
		description: 'Write out a DOT graph of rule dependencies',
		'boolean': true,
		'default': false,
	},

	'list-orphans': {
		description: 'List rules that are not called by any other rule',
		'boolean': true,
		'default': false,
	},

	'outfile': {
		description: 'File name to write the output to',
		'boolean': false,
		'default': '-',
		'alias': 'o'
	},

	'php': {
		description: 'Use the PHP grammar',
		'boolean': true,
		'default': false,
	},

	'trace': {
		description: 'Generate code that logs rule transitions to stdout',
		'boolean': true,
		'default': false,
	},

	'header-comment-file': {
		description: 'File containing the comment to add in the header of the generated file',
		'boolean': false,
		'default': __dirname + '/grammarheader-script.txt'
	}
});

yargs.help();

function getOutputStream(opts) {
	if (!opts.outfile || opts.outfile === '-') {
		return process.stdout;
	} else {
		return fs.createWriteStream(opts.outfile);
	}
}

function generateSource(opts) {
	var file = getOutputStream(opts);
	var tokenizer = new PegTokenizer();
	var headerComment = fs.readFileSync(opts['header-comment-file']).toString();
	var pegOpts = { trace: opts.trace, headerComment: headerComment };
	var source = tokenizer.compileTokenizer(tokenizer.parseTokenizer(pegOpts), pegOpts);
	file.write(source, 'utf8');
}

function generateRules(opts) {
	var file = getOutputStream(opts);
	var tokenizer = new PegTokenizer();
	var pegOpts = { php: opts.php };
	var ast = tokenizer.parseTokenizer(pegOpts);
	var visitor = require('wikipeg/lib/compiler/visitor');

	// Current code style seems to use spaces in the tokenizer.
	var tab = '    ';
	// Add some eslint overrides and define globals.
	var rulesSource = '/* eslint-disable indent,camelcase,no-unused-vars */\n';
	rulesSource += "\n'use strict';\n\n";
	rulesSource += 'var options, location, input, text, peg$cache, peg$currPos, peg$savedPos;\n';
	// Prevent redefinitions of variables involved in choice expressions
	var seen = new Set();
	var addVar = function(name) {
		if (!seen.has(name)) {
			rulesSource += tab + 'var ' + name + ' = null;\n';
			seen.add(name);
		}
	};
	// Collect all the code blocks in the AST.
	var dumpCode = function(node) {
		if (node.code) {
			// remove trailing whitespace for single-line predicates
			var code = node.code.replace(/[ \t]+$/, '');
			// wrap with a function, to prevent spurious errors caused
			// by redeclarations or multiple returns in a block.
			rulesSource += tab + '(function() {\n' + code + '\n' +
				tab + '})();\n';
		}
	};
	var visit = visitor.build({
		initializer: function(node) {
			if (node.code) {
				rulesSource += node.code + '\n';
			}
		},
		semantic_and: dumpCode,
		semantic_node: dumpCode,
		rule: function(node) {
			rulesSource += 'function rule_' + node.name + '() {\n';
			seen.clear();
			visit(node.expression);
			rulesSource += '}\n';
		},
		labeled: function(node) {
			addVar(node.label);
			visit(node.expression);
		},
		labeled_param: function(node) {
			addVar(node.label);
		},
		named: function(node) {
			addVar(node.name);
			visit(node.expression);
		},
		action: function(node) {
			visit(node.expression);
			dumpCode(node);
		},
	});
	visit(ast);
	// Write rules to file.
	file.write(rulesSource, 'utf8');
}

function generateCallgraph(opts) {
	var file = getOutputStream(opts);
	var tokenizer = new PegTokenizer();
	var pegOpts = { php: opts.php };
	var ast = tokenizer.parseTokenizer(pegOpts);
	var visitor = require('wikipeg/lib/compiler/visitor');
	var edges = [];
	var currentRuleName;

	var visit = visitor.build({
		rule: function(node) {
			currentRuleName = node.name;
			visit(node.expression);
		},

		rule_ref: function(node) {
			var edge = "\t" + currentRuleName + " -> " + node.name + ";";
			if (!edges.includes(edge)) {
				edges.push(edge);
			}
		}
	});

	visit(ast);

	var dot = "digraph {\n" +
		edges.join("\n") + "\n" +
		"}\n";

	file.write(dot, 'utf8');
}

function listOrphans(opts) {
	var file = getOutputStream(opts);
	var tokenizer = new PegTokenizer();
	var pegOpts = { php: opts.php };
	var ast = tokenizer.parseTokenizer(pegOpts);
	var visitor = require('wikipeg/lib/compiler/visitor');

	var rules = {};

	visitor.build({
		rule: function(node) {
			rules[node.name] = true;
		},
	})(ast);

	visitor.build({
		rule_ref: function(node) {
			delete rules[node.name];
		},
	})(ast);

	file.write(Object.getOwnPropertyNames(rules).join('\n') + '\n');
}

var opts = yargs.argv;

if (opts.source) {
	generateSource(opts);
} else if (opts.rules) {
	generateRules(opts);
} else if (opts.callgraph) {
	generateCallgraph(opts);
} else if (opts['list-orphans']) {
	listOrphans(opts);
} else {
	console.error("Either --source, --rules, --callgraph or --list-orphans must be specified");
	process.exit(1);
}
