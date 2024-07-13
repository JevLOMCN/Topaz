/*!
 * VisualEditor DataModel MWSyntaxHighlightNode class.
 *
 * @copyright VisualEditor Team and others
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki syntax highlight node.
 *
 * @class
 * @abstract
 *
 * @constructor
 */
ve.dm.MWSyntaxHighlightNode = function VeDmMWSyntaxHighlightNode() {
};

/* Inheritance */

OO.initClass( ve.dm.MWSyntaxHighlightNode );

/* Static members */

ve.dm.MWSyntaxHighlightNode.static.name = 'mwSyntaxHighlight';

ve.dm.MWSyntaxHighlightNode.static.extensionName = 'syntaxhighlight';

ve.dm.MWSyntaxHighlightNode.static.getMatchRdfaTypes = function () {
	return [ 'mw:Extension/syntaxhighlight', 'mw:Extension/source' ];
};

/* Static methods */

/**
 * @inheritdoc
 */
ve.dm.MWSyntaxHighlightNode.static.toDataElement = function ( domElements, converter ) {
	var isInline = this.isHybridInline( domElements, converter ),
		type = isInline ? 'mwInlineSyntaxHighlight' : 'mwBlockSyntaxHighlight';
	// Parent method
	return ve.dm.MWExtensionNode.static.toDataElement.call( this, domElements, converter, type );
};

( function () {
	var supportedLanguages = [ undefined ],
		geshiToPygmentsMap,
		pygmentsToAceMap;

	/**
	 * Register supported Pygments languages.
	 * Called from PHP's ResourceLoaderSyntaxHighlightVisualEditorModule.
	 *
	 * @param {string[]} languages List of language names, e.g. "html"
	 */
	ve.dm.MWSyntaxHighlightNode.static.addPygmentsLanguages = function ( languages ) {
		ve.batchPush( supportedLanguages, languages );
	};

	/**
	 * Register map from Geshi to pygments lexer names.
	 * Called from PHP's ResourceLoaderSyntaxHighlightVisualEditorModule.
	 *
	 * @param {Object.<string.string>} map Map of compatible lexers, e.g. { "html5": "html" }
	 */
	ve.dm.MWSyntaxHighlightNode.static.addGeshiToPygmentsMap = function ( map ) {
		geshiToPygmentsMap = map;
		ve.batchPush( supportedLanguages, Object.keys( geshiToPygmentsMap ) );
	};

	/**
	 * Register a map from pygments to Ace lexer names.
	 * Called from PHP's ResourceLoaderSyntaxHighlightVisualEditorModule.
	 *
	 * @param {Object.<string.string>} map Map of compatible lexers, e.g. { "js": "JavaScript" }
	 */
	ve.dm.MWSyntaxHighlightNode.static.addPygmentsToAceMap = function ( map ) {
		pygmentsToAceMap = map;
	};

	/**
	 * Converts a (valid) language as recognized by the SyntaxHighlight wikicode
	 * to a compatible Ace lexer name (to be used by CodeEditor)
	 *
	 * @param {string} language Language name
	 * @return {string} The name of the ace lexer
	 */
	ve.dm.MWSyntaxHighlightNode.static.convertLanguageToAce = function ( language ) {
		language = geshiToPygmentsMap[ language ] || language;
		return ( pygmentsToAceMap[ language ] || language ).toLowerCase();
	};

	/**
	 * Check if a language is supported
	 *
	 * @param {string} [language] Language name
	 * @return {boolean} The language is supported; always true when called with no language
	 */
	ve.dm.MWSyntaxHighlightNode.static.isLanguageSupported = function ( language ) {
		return supportedLanguages.indexOf( language || undefined ) !== -1;
	};

	/**
	 * Get an array of all languages (both Pygments and former GeSHi names)
	 *
	 * @return {string[]} All currently supported languages, including an undefined entry for "none"
	 */
	ve.dm.MWSyntaxHighlightNode.static.getLanguages = function () {
		return supportedLanguages.slice();
	};
}() );

/* Methods */

/**
 * Check if the node's current language is supported
 *
 * @return {boolean} The language is supported
 */
ve.dm.MWSyntaxHighlightNode.prototype.isLanguageSupported = function () {
	return this.constructor.static.isLanguageSupported( this.getLanguage() );
};

ve.dm.MWSyntaxHighlightNode.prototype.getLanguage = function () {
	return this.getAttribute( 'mw' ).attrs.lang.toLowerCase();
};
