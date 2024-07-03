/*!
 * VisualEditor MWDefinedTransclusionContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a defined MWTransclusion.
 *
 * Templates are defined on-wiki using the message page
 * [[MediaWiki:Visualeditor-template-tools-definition.json]]
 *
 * Example:
 * {
 *   // This key is the static name of the ve.ui.ContextItem
 *   "citationNeeded": [
 *     {
 *       // Normalized title string, or list of strings (for redirects)
 *       "title": [ "Citation needed", "cn" ],
 *       // Extra params. This whole object can be accessed
 *       // via #getMatchedTool
 *       "params": {
 *         "reason": "reason",
 *         "date": "date"
 *       }
 *     },
 *     {
 *       "title": "Cite quote",
 *       "params": {
 *         "date": "date"
 *       }
 *     }
 *   ]
 * }
 *
 * @class
 * @abstract
 * @extends ve.ui.MWTransclusionContextItem
 *
 * @constructor
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWDefinedTransclusionContextItem = function VeUiMWDefinedTransclusionContextItem() {
	// Parent constructor
	ve.ui.MWDefinedTransclusionContextItem.super.apply( this, arguments );

	this.tool = this.constructor.static.getMatchedTool( this.model );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWDefinedTransclusionContextItem, ve.ui.MWTransclusionContextItem );

/* Static Properties */

ve.ui.MWDefinedTransclusionContextItem.static.name = null;

ve.ui.MWDefinedTransclusionContextItem.static.toolDefinitions = ( function () {
	var tools;

	try {
		// Must use mw.message to avoid JSON being parsed as Wikitext
		tools = JSON.parse( mw.message( 'visualeditor-template-tools-definition.json' ).plain() );
	} catch ( e ) {}

	return tools || {};
}() );

/**
 * Only display item for single-template transclusions of these templates.
 *
 * @property {string|string[]|null}
 * @static
 * @inheritable
 */
ve.ui.MWDefinedTransclusionContextItem.static.template = null;

/* Static Methods */

/**
 * @inheritdoc
 */
ve.ui.MWDefinedTransclusionContextItem.static.isCompatibleWith = function ( model ) {
	// Parent method
	return ve.ui.MWDefinedTransclusionContextItem.super.static.isCompatibleWith.apply( this, arguments ) &&
		!!this.getMatchedTool( model );
};

/**
 * Get tool definitions, indexed by normalized title
 *
 * @return {Object} Collection of tool definitions
 */
ve.ui.MWDefinedTransclusionContextItem.static.getToolsByTitle = function () {
	if ( !this.toolsByTitle ) {
		var toolsByTitle;
		this.toolsByTitle = toolsByTitle = {};
		( this.toolDefinitions[ this.name ] || [] ).forEach( function ( template ) {
			var titles = Array.isArray( template.title ) ? template.title : [ template.title ];
			// 'title' can be a single title, or list of titles (including redirects)
			titles.forEach( function ( title ) {
				toolsByTitle[ mw.Title.newFromText( title, mw.config.get( 'wgNamespaceIds' ).template ).getPrefixedText() ] = template;
			} );
		} );
	}
	return this.toolsByTitle;
};

/**
 * Get the tool definition that matches a specific model, if any
 *
 * @param {ve.dm.Model} model
 * @return {Object|null} Tool definition, or null if no match
 */
ve.ui.MWDefinedTransclusionContextItem.static.getMatchedTool = function ( model ) {
	var resource = ve.getProp( model.getAttribute( 'mw' ), 'parts', 0, 'template', 'target', 'href' );
	if ( resource ) {
		var title = mw.Title.newFromText( mw.libs.ve.normalizeParsoidResourceName( resource ) ).getPrefixedText();
		return this.getToolsByTitle()[ title ] || null;
	}
	return null;
};

/**
 * Get a template param using its canonical name
 *
 * @param {string} name Canonical parameter name
 * @return {string|null} Param wikitext, null if not found
 */
ve.ui.MWDefinedTransclusionContextItem.prototype.getCanonicalParam = function ( name ) {
	var params = this.tool.params || {};

	if ( Object.prototype.hasOwnProperty.call( params, name ) ) {
		var aliases = Array.isArray( params[ name ] ) ? params[ name ] : [ params[ name ] ];
		// Find the first non-empty value from the alias list
		for ( var i = 0; i < aliases.length; i++ ) {
			var value = ve.getProp( this.model.getAttribute( 'mw' ), 'parts', 0, 'template', 'params', aliases[ i ], 'wt' );
			if ( value ) {
				return value;
			}
		}
	}
	return null;
};
