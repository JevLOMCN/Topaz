/* global ace */
ace.define( 'ace/mode/abusefilter_highlight_rules', [ 'require', 'exports', 'module', 'ace/lib/oop', 'ace/mode/text_highlight_rules' ], function ( require, exports ) {
	'use strict';

	var oop = require( 'ace/lib/oop' ),
		TextHighlightRules = require( './text_highlight_rules' ).TextHighlightRules,
		AFHighlightRules = function () {
			var cfg = mw.config.get( 'aceConfig' ),
				constants = ( 'true|false|null' ),
				keywords = this.createKeywordMapper(
					{
						keyword: cfg.keywords,
						'support.function': cfg.functions,
						'constant.language': constants
					},
					// Null as default used in isKeywordOrVariable
					null
				),
				variables = this.createKeywordMapper(
					{
						'variable.language': cfg.variables,
						'invalid.deprecated': cfg.deprecated,
						'invalid.illegal': cfg.disabled
					},
					'identifier',
					true
				),
				isKeywordOrVariable = function ( value ) {
					if ( keywords( value ) !== null ) {
						return keywords( value );
					} else {
						return variables( value );
					}
				},
				integer = '(?:(?:[1-9]\\d*)|(?:0))',
				fraction = '(?:\\.\\d+)',
				intPart = '(?:\\d+)',
				pointFloat = '(?:(?:' + intPart + '?' + fraction + ')|(?:' + intPart + '\\.))',
				floatNumber = '(?:' + pointFloat + ')';

			this.$rules = {
				start: [ {
					token: 'comment',
					regex: '\\/\\*',
					next: 'comment'
				}, {
					token: 'string',
					regex: '"',
					next: 'doublequotestring'
				}, {
					token: 'string',
					regex: "'",
					next: 'singlequotestring'
				}, {
					token: 'constant.numeric',
					regex: floatNumber
				}, {
					token: 'constant.numeric',
					regex: integer + '\\b'
				}, {
					token: isKeywordOrVariable,
					regex: '[a-zA-Z_][a-zA-Z0-9_]*\\b'
				}, {
					token: 'keyword.operator',
					regex: cfg.operators
				}, {
					token: 'paren.lparen',
					regex: '[\\[\\(]'
				}, {
					token: 'paren.rparen',
					regex: '[\\]\\)]'
				}, {
					token: 'text',
					regex: '\\s+|\\w+'
				} ],
				comment: [
					{ token: 'comment', regex: '\\*\\/', next: 'start' },
					{ defaultToken: 'comment' }
				],
				doublequotestring: [
					{ token: 'constant.language.escape', regex: /\\["\\]/ },
					{ token: 'string', regex: '"', next: 'start' },
					{ defaultToken: 'string' }
				],
				singlequotestring: [
					{ token: 'constant.language.escape', regex: /\\['\\]/ },
					{ token: 'string', regex: "'", next: 'start' },
					{ defaultToken: 'string' }
				]
			};

			this.normalizeRules();
		};

	oop.inherits( AFHighlightRules, TextHighlightRules );

	exports.AFHighlightRules = AFHighlightRules;
} );

ace.define( 'ace/mode/abusefilter', [ 'require', 'exports', 'module', 'ace/lib/oop', 'ace/mode/text', 'ace/mode/abusefilter_highlight_rules', 'ace/worker/worker_client' ], function ( require, exports ) {
	'use strict';

	var oop = require( 'ace/lib/oop' ),
		TextMode = require( './text' ).Mode,
		WorkerClient = require( 'ace/worker/worker_client' ).WorkerClient,
		AFHighlightRules = require( './abusefilter_highlight_rules' ).AFHighlightRules,
		MatchingBraceOutdent = require( './matching_brace_outdent' ).MatchingBraceOutdent,
		Mode = function () {
			this.HighlightRules = AFHighlightRules;
			this.$behaviour = this.$defaultBehaviour;
			this.$outdent = new MatchingBraceOutdent();
		};
	oop.inherits( Mode, TextMode );

	( function () {
		this.blockComment = {
			start: '/*',
			end: '*/'
		};
		this.getNextLineIndent = function ( state, line ) {
			var indent = this.$getIndent( line );
			return indent;
		};
		this.checkOutdent = function ( state, line, input ) {
			return this.$outdent.checkOutdent( line, input );
		};
		this.autoOutdent = function ( state, doc, row ) {
			this.$outdent.autoOutdent( doc, row );
		};

		this.createWorker = function ( session ) {
			var extPath = mw.config.get( 'wgExtensionAssetsPath' );
			ace.config.set( 'workerPath', extPath + '/AbuseFilter/modules' );
			var worker = new WorkerClient( [ 'ace' ], 'ace/mode/abusefilter_worker', 'AbuseFilterWorker' );

			var apiPath = mw.config.get( 'wgServer' ) + new mw.Api().defaults.ajax.url;
			if ( apiPath.slice( 0, 2 ) === '//' ) {
				apiPath = window.location.protocol + apiPath;
			}
			worker.$worker.postMessage( { apipath: apiPath } );

			worker.attachToDocument( session.getDocument() );

			worker.on( 'annotate', function ( results ) {
				session.setAnnotations( results.data );
			} );

			worker.on( 'terminate', function () {
				session.clearAnnotations();
			} );

			return worker;
		};
	} )
		.call( Mode.prototype );

	exports.Mode = Mode;
} );
