var Model = require( './Model.js' );

/**
 * TemplateData Source Handler
 *
 * Loads and validates the templatedata and template parameters
 * whether in the page itself or from its parent.
 *
 * @class
 * @extends OO.EventEmitter
 *
 * @constructor
 * @param {Object} [config]
 * @cfg {string} [fullPageName] The full name of the current page
 * @cfg {string} [parentPage] The name of the parent page
 * @cfg {string} [isPageSubLevel] The page is sub-level of another template
 */
function SourceHandler( config ) {
	config = config || {};

	// Mixin constructors
	OO.EventEmitter.call( this );

	this.apiCache = {};
	this.templateSourceCodePromise = null;
	this.templateSourceCodeParams = [];

	// Config
	this.setParentPage( config.parentPage );
	this.setPageSubLevel( config.isPageSubLevel );
	this.setFullPageName( config.fullPageName );
}

/* Inheritance */

OO.mixinClass( SourceHandler, OO.EventEmitter );

/**
 * Get information from the MediaWiki API
 *
 * @param {string} page Page name
 * @param {boolean} [getTemplateData] Fetch the templatedata in the page.
 * @return {jQuery.Promise} API promise
 */
SourceHandler.prototype.getApi = function ( page, getTemplateData ) {
	var type = getTemplateData ? 'templatedata' : 'query',
		api = new mw.Api(),
		baseConfig = {
			action: type,
			titles: page,
			redirects: getTemplateData ? 1 : 0
		};

	var config;
	if ( type === 'query' ) {
		config = $.extend( baseConfig, {
			prop: 'revisions',
			rvprop: 'content',
			indexpageids: '1'
		} );
	}

	// Cache
	if ( !this.apiCache[ page ] || !this.apiCache[ page ][ type ] ) {
		this.apiCache[ page ] = this.apiCache[ page ] || {};
		this.apiCache[ page ][ type ] = api.get( config );
	}
	return this.apiCache[ page ][ type ];
};

/**
 * Go over the current wikitext and build a new model.
 *
 * @param {string} [wikitext] Source of the template.
 * @return {jQuery.Promise} Promise resolving into a new Model
 *  or is rejected if the model was impossible to create.
 */
SourceHandler.prototype.buildModel = function ( wikitext ) {
	var tdObject = null,
		templateDataString = this.findModelInString( wikitext );

	if ( templateDataString !== null ) {
		try {
			tdObject = JSON.parse( templateDataString );
		} catch ( err ) {
			// The json object is invalid. There's no need to continue.
			return $.Deferred().reject();
		}
	}

	// Get parameters from source code
	// Mostly used for the import option
	return this.getParametersFromTemplateSource( wikitext )
		// This is always successful by definition
		.then( function ( templateSourceCodeParams ) {
			return Model.static.newFromObject(
				tdObject,
				templateSourceCodeParams
			);
		} );
};

/**
 * Retrieve parameters from the template code from source in this order:
 *
 * 1. Check if there's a template in the given 'wikitext' parameter. If not,
 * 2. Check if there's a template in the current page. If not,
 * 3. Check if the page is a subpage and go up a level to check for template code. If none found,
 * 4. Repeat until we are in the root of the template
 * 5. Save the name of the page where the template is taken from
 *
 * Cache the templateCodePromise so we don't have to do this all over again on each
 * template code request.
 *
 * @param {string} [wikitext] Optional. Source of the template.
 * @return {jQuery.Promise} Promise resolving into template parameter array
 */
SourceHandler.prototype.getParametersFromTemplateSource = function ( wikitext ) {
	var params = [],
		sourceHandler = this;

	if ( !this.templateSourceCodePromise ) {
		// Check given page text first
		if ( wikitext ) {
			params = this.extractParametersFromTemplateCode( wikitext );
		}

		if ( params.length > 0 ) {
			// There are parameters found; Resolve.
			this.templateSourceCodePromise = $.Deferred().resolve( params );
		} else if ( this.isPageSubLevel() && this.getParentPage() ) {
			// Get the content of the parent
			this.templateSourceCodePromise = this.getApi( this.getParentPage() ).then(
				function ( resp ) {
					var pageContent = '';

					// Verify that we have a sane response from the API.
					// This is particularly important for unit tests, since the
					// requested page from the API is the Qunit module and has no content
					if (
						resp.query.pages[ resp.query.pageids[ 0 ] ].revisions &&
						resp.query.pages[ resp.query.pageids[ 0 ] ].revisions[ 0 ]
					) {
						pageContent = resp.query.pages[ resp.query.pageids[ 0 ] ].revisions[ 0 ][ '*' ];
					}
					return sourceHandler.extractParametersFromTemplateCode( pageContent );
				},
				function () {
					// Resolve an empty parameters array
					return $.Deferred().resolve( [] );
				}
			);
		} else {
			// No template found. Resolve to empty array of parameters
			this.templateSourceCodePromise = $.Deferred().resolve( [] );
		}
	}

	return this.templateSourceCodePromise;
};

/**
 * Retrieve template parameters from the template code.
 *
 * Adapted from https://he.wikipedia.org/wiki/MediaWiki:Gadget-TemplateParamWizard.js
 *
 * @param {string} templateCode Source of the template.
 * @return {string[]} An array of parameters that appear in the template code
 */
SourceHandler.prototype.extractParametersFromTemplateCode = function ( templateCode ) {
	var paramNames = [],
		normalizedParamNames = [],
		// This regex matches the one in TemplateDataBlob.php
		paramExtractor = /{{{+([^\n#={|}]*?)([<|]|}}})/mg;

	// Ignore non-wikitext content in comments and wikitext-escaping tags
	templateCode = templateCode.replace( /<!--[\s\S]*?-->/g, '' )
		.replace( /<nowiki\s*>[\s\S]*?<\/nowiki\s*>/g, '' )
		.replace( /<pre\s*>[\s\S]*?<\/pre\s*>/g, '' );

	var matches;
	while ( ( matches = paramExtractor.exec( templateCode ) ) !== null ) {
		// This normalization process is repeated in PHP in TemplateDataBlob.php
		var normalizedParamName = matches[ 1 ].replace( /[-_ ]+/, ' ' ).trim().toLowerCase();
		if ( !normalizedParamName || normalizedParamNames.indexOf( normalizedParamName ) !== -1 ) {
			continue;
		}
		if ( paramNames.indexOf( matches[ 1 ] ) === -1 ) {
			normalizedParamNames.push( normalizedParamName );
			paramNames.push( matches[ 1 ].trim() );
		}
	}

	return paramNames;
};

/**
 * Look for a templatedata json string and return it, if it exists.
 *
 * @param {string} templateDataString Wikitext templatedata string
 * @return {string|null} The isolated json string. Empty if no
 * templatedata string was found.
 */
SourceHandler.prototype.findModelInString = function ( templateDataString ) {
	var parts = templateDataString.match(
		/<templatedata>([\s\S]*?)<\/templatedata>/i
	);

	if ( parts && parts[ 1 ] && parts[ 1 ].trim().length > 0 ) {
		return parts[ 1 ].trim();
	} else {
		return null;
	}
};

/**
 * Set the page as a sub page of the main template
 *
 * @param {boolean} isSubLevel Page is sublevel
 */
SourceHandler.prototype.setPageSubLevel = function ( isSubLevel ) {
	this.subLevel = !!isSubLevel;
};

/**
 * Set the page as a sub page of the main template
 *
 * @return {boolean} Page is sublevel
 */
SourceHandler.prototype.isPageSubLevel = function () {
	return this.subLevel;
};

/**
 * Get full page name
 *
 * @param {string} pageName Page name
 */
SourceHandler.prototype.setFullPageName = function ( pageName ) {
	this.fullPageName = pageName || '';
};

/**
 * Get page full name
 *
 * @return {string} Page full name
 */
SourceHandler.prototype.getFullPageName = function () {
	return this.fullPageName;
};

/**
 * Set parent page
 *
 * @param {string} parent Parent page
 */
SourceHandler.prototype.setParentPage = function ( parent ) {
	this.parentPage = parent || '';
};

/**
 * Get parent page
 *
 * @return {string} Parent page
 */
SourceHandler.prototype.getParentPage = function () {
	return this.parentPage;
};

/**
 * Set template source code parameters
 *
 * @param {string[]} params Parameters from the template source code
 */
SourceHandler.prototype.setTemplateSourceCodeParams = function ( params ) {
	this.templateSourceCodeParams = params;
};

/**
 * Set template source code parameters
 *
 * @return {string[]} Parameters from the template source code
 */
SourceHandler.prototype.getTemplateSourceCodeParams = function () {
	return this.templateSourceCodeParams;
};

module.exports = SourceHandler;
