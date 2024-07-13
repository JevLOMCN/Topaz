/*!
 * VisualEditor DataModel MWTemplateSpecModel class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Object literal returned by the TemplataData API. Expected to be in formatversion=2,
 * guaranteed via ve.init.mw.Target#getContentApi.
 *
 * @class ve.dm.MWTemplatePageMetadata
 * @private
 */
/**
 * @property {string|boolean} [missing] Either "1" or true
 * @property {string|boolean} [notemplatedata] Either "1" or true when there is no user-provided
 *   documentation `params` are auto-detected in this case.
 * @property {string} title Template page name including the "Template:" namespace
 * @property {string|Object.<string,string>} [description] Template description
 * @property {Object.<string,ve.dm.MWTemplateParamDescription>} [params] Parameters by param name
 * @property {string[]} [paramOrder] Preferred parameter order as documented via TemplateData. If
 *  given, the TemplateData API makes sure this contains the same parameters as `params`.
 * @property {{label:(string|Object.<string,string>),params:string[]}[]} [sets] List of parameter
 *  sets, i.e. parameters that belong together (whatever that means, this feature is underspecified
 *  and unused)
 * @property {Object.<string,Object.<string,string|string[]|string[][]>>} [maps] Source to target
 *  parameter mappings for consumers like Citoid or gadgets
 */

/**
 * Object literal
 *
 * @class ve.dm.MWTemplateParamDescription
 * @private
 */
/**
 * @property {string|Object.<string,string>} [label]
 * @property {string|Object.<string,string>} [description]
 * @property {string[]} [suggestedvalues]
 * @property {string} [default]
 * @property {string|Object.<string,string>} [example]
 * @property {string} [autovalue]
 * @property {string} [type]
 * @property {string[]} [aliases]
 * @property {boolean} [required]
 * @property {boolean} [suggested]
 * @property {boolean|string} [deprecated]
 */

/**
 * Holds a mixture of:
 *
 * - A copy of a template's specification as it is documented via TemplateData.
 * - Undocumented parameters that appear in a template invocation, {@link #fillFromTemplate}.
 * - Documented aliases are also considered valid, known parameter names. Use
 *   {@link #isParameterAlias} to differentiate between the two.
 *
 * Therefore this is not the original specification but an accessor to the documentation for an
 * individual template invocation. It's possible different for every invocation.
 *
 * Meant to be in a 1:1 relationship to ve.dm.MWTemplateModel.
 *
 * The actual, unmodified specification can be found in the {@link #templateData} property, and
 * the local `specCache` in ve.dm.MWTransclusionModel.
 *
 * See <https://github.com/wikimedia/mediawiki-extensions-TemplateData/blob/master/Specification.md>
 * for the latest version of the TemplateData specification.
 *
 * @class
 *
 * @constructor
 * @param {ve.dm.MWTemplateModel} template
 */
ve.dm.MWTemplateSpecModel = function VeDmMWTemplateSpecModel( template ) {
	this.template = template;
	/**
	 * @property {Object.<string,boolean>} seenParameterNames Keeps track of any parameter from any
	 *  source and in which order they have been seen first. Includes parameters that have been removed
	 *  during the lifetime of this object, i.e. {@see fillFromTemplate} doesn't remove parameters that
	 *  have been seen before. The order is typically but not necessarily the original order in which
	 *  the parameters appear in the template. Aliases are resolved and don't appear on their original
	 *  position any more.
	 */
	this.seenParameterNames = {};
	/**
	 * @property {Object} templateData Documentation as provided by the TemplateData API
	 */
	this.templateData = { notemplatedata: true, params: {} };
	/**
	 * @property {Object.<string,string>} aliases Maps aliases to primary parameter names
	 */
	this.aliases = {};

	// Initialization
	this.fillFromTemplate();
};

OO.initClass( ve.dm.MWTemplateSpecModel );

/* Static methods */

/**
 * @private
 * @param {string|Object.<string,string>|null} stringOrObject
 * @param {string} [languageCode]
 * @return {string|null|undefined}
 */
ve.dm.MWTemplateSpecModel.static.getLocalValue = function ( stringOrObject, languageCode ) {
	return stringOrObject && typeof stringOrObject === 'object' ?
		OO.ui.getLocalValue( stringOrObject, languageCode ) :
		stringOrObject;
};

/* Methods */

/**
 * Template spec data is available from the TemplateData extension's API.
 *
 * @param {ve.dm.MWTemplatePageMetadata} data
 */
ve.dm.MWTemplateSpecModel.prototype.setTemplateData = function ( data ) {
	if ( !data || !ve.isPlainObject( data ) ) {
		return;
	}

	this.templateData = data;
	// This is currently not optional in the TemplateData API but might be in the future
	if ( !this.templateData.params ) {
		this.templateData.params = {};
	}
	// Incomplete server validation makes this possible, but the empty string is reserved for
	// {@see ve.ui.MWAddParameterPage}.
	delete this.templateData.params[ '' ];

	var resolveAliases = false;

	for ( var primaryName in this.templateData.params ) {
		this.seenParameterNames[ primaryName ] = true;

		var aliases = this.getParameterAliases( primaryName );
		for ( var i = 0; i < aliases.length; i++ ) {
			var alias = aliases[ i ];
			this.aliases[ alias ] = primaryName;
			if ( alias in this.seenParameterNames ) {
				resolveAliases = true;
			}
		}
	}

	if ( resolveAliases ) {
		var primaryNames = {};
		for ( var name in this.seenParameterNames ) {
			primaryNames[ this.getPrimaryParameterName( name ) ] = true;
		}
		this.seenParameterNames = primaryNames;
	}
};

/**
 * Adds all (possibly undocumented) parameters from the linked template to the list of known
 * parameters, {@see getKnownParameterNames}. This should be called every time a parameter is added
 * to the template.
 */
ve.dm.MWTemplateSpecModel.prototype.fillFromTemplate = function () {
	for ( var name in this.template.getParameters() ) {
		// Ignore placeholder parameters with no name
		if ( name && !this.isKnownParameterOrAlias( name ) ) {
			// There is no information other than the names of the parameters, that they exist, and
			// in which order
			this.seenParameterNames[ name ] = true;
		}
	}
};

/**
 * @return {string} Normalized template name without the "Template:" namespace prefix, if possible.
 *  Otherwise the unnormalized template name as used in the wikitext. Might even be a string like
 *  `{{example}}` when a template name is dynamically generated.
 */
ve.dm.MWTemplateSpecModel.prototype.getLabel = function () {
	var title = this.template.getTemplateDataQueryTitle();
	if ( title ) {
		try {
			// Normalize and remove namespace prefix if in the Template: namespace
			title = new mw.Title( title )
				.getRelativeText( mw.config.get( 'wgNamespaceIds' ).template );
		} catch ( e ) { }
	}
	return title || this.template.getTarget().wt;
};

/**
 * @param {string} [languageCode]
 * @return {string|null} Template description or null if not available
 */
ve.dm.MWTemplateSpecModel.prototype.getDescription = function ( languageCode ) {
	return this.constructor.static.getLocalValue( this.templateData.description || null, languageCode );
};

/**
 * True it the template does have any user-provided documentation. Note that undocumented templates
 * can still have auto-detected `params` and a `paramOrder`, while documented templates might not
 * have `params`. Use `{@see getDocumentedParameterOrder()}.length` to differentiate.
 *
 * @return {boolean}
 */
ve.dm.MWTemplateSpecModel.prototype.isDocumented = function () {
	return !this.templateData.notemplatedata;
};

/**
 * Preferred order of parameters via TemplateData, without aliases or undocumented parameters. Empty
 * if the template is not documented. Otherwise the explicit `paramOrder` if given, or the order of
 * parameters as they appear in TemplateData. Returns a copy, i.e. it's safe to manipulate the
 * array.
 *
 * @return {string[]} Preferred order of parameters via TemplateData, if given
 */
ve.dm.MWTemplateSpecModel.prototype.getDocumentedParameterOrder = function () {
	return Array.isArray( this.templateData.paramOrder ) ?
		this.templateData.paramOrder.filter( function ( name ) {
			return name;
		} ) :
		Object.keys( this.templateData.params );
};

/**
 * The returned array is a copy, i.e. it's safe to manipulate.
 *
 * @return {string[]}
 */
ve.dm.MWTemplateSpecModel.prototype.getUndocumentedParameterNames = function () {
	var documentedParameters = this.templateData.params;

	return this.getKnownParameterNames().filter( function ( name ) {
		return !( name in documentedParameters );
	} );
};

/**
 * Same as {@see getKnownParameterNames}, but in a canonical order that's always the same, unrelated
 * to how the parameters appear in the wikitext. Primary parameter names documented via TemplateData
 * are first, in their documented order. Undocumented parameters are sorted with numeric names
 * first, followed by alphabetically sorted names.
 *
 * The returned array is a copy, i.e. it's safe to manipulate.
 *
 * @return {string[]}
 */
ve.dm.MWTemplateSpecModel.prototype.getCanonicalParameterOrder = function () {
	var undocumentedParameters = this.getUndocumentedParameterNames();

	undocumentedParameters.sort( function ( a, b ) {
		if ( isNaN( a ) ) {
			// If a and b are string, order alphabetically, otherwise numbers before strings
			return isNaN( b ) ? a.localeCompare( b ) : 1;
		} else {
			// If a and b are numeric, order incrementally, otherwise numbers before strings
			return !isNaN( b ) ? a - b : -1;
		}
	} );

	return this.getDocumentedParameterOrder().concat( undocumentedParameters );
};

/**
 * Check if a parameter name or alias was seen before. This includes parameters and aliases
 * documented via TemplateData as well as undocumented parameters, e.g. from the original template
 * invocation. When undocumented parameters are removed from the linked {@see ve.dm.MWTemplateModel}
 * they are still known and will still be offered via {@see getKnownParameterNames} for the lifetime
 * of this object.
 *
 * @param {string} name Parameter name or alias
 * @return {boolean}
 */
ve.dm.MWTemplateSpecModel.prototype.isKnownParameterOrAlias = function ( name ) {
	return name in this.seenParameterNames || name in this.aliases;
};

/**
 * @param {string} name Parameter name or alias
 * @return {boolean}
 */
ve.dm.MWTemplateSpecModel.prototype.isParameterAlias = function ( name ) {
	return name in this.aliases;
};

/**
 * @param {string} name Parameter name or alias
 * @return {boolean}
 */
ve.dm.MWTemplateSpecModel.prototype.isParameterDocumented = function ( name ) {
	return name in this.templateData.params || name in this.aliases;
};

/**
 * @param {string} name Parameter name or alias
 * @param {string} [languageCode]
 * @return {string} Descriptive label of the parameter, if given. Otherwise the alias or parameter
 *  name as is.
 */
ve.dm.MWTemplateSpecModel.prototype.getParameterLabel = function ( name, languageCode ) {
	var param = this.templateData.params[ this.getPrimaryParameterName( name ) ];
	return this.constructor.static.getLocalValue( param && param.label || name, languageCode );
};

/**
 * @param {string} name Parameter name or alias
 * @param {string} [languageCode]
 * @return {string|null}
 */
ve.dm.MWTemplateSpecModel.prototype.getParameterDescription = function ( name, languageCode ) {
	var param = this.templateData.params[ this.getPrimaryParameterName( name ) ];
	return this.constructor.static.getLocalValue( param && param.description || null, languageCode );
};

/**
 * @param {string} name Parameter name or alias
 * @return {string[]}
 */
ve.dm.MWTemplateSpecModel.prototype.getParameterSuggestedValues = function ( name ) {
	var param = this.templateData.params[ this.getPrimaryParameterName( name ) ];
	return param && param.suggestedvalues || [];
};

/**
 * The default value will be placed in the input field when the parameter is added. The user can
 * edit or even remove it.
 *
 * @param {string} name Parameter name or alias
 * @return {string} e.g. "{{PAGENAME}}"
 */
ve.dm.MWTemplateSpecModel.prototype.getParameterDefaultValue = function ( name ) {
	var param = this.templateData.params[ this.getPrimaryParameterName( name ) ];
	return param && param.default || '';
};

/**
 * @param {string} name Parameter name or alias
 * @param {string} [languageCode]
 * @return {string|null}
 */
ve.dm.MWTemplateSpecModel.prototype.getParameterExampleValue = function ( name, languageCode ) {
	var param = this.templateData.params[ this.getPrimaryParameterName( name ) ];
	return this.constructor.static.getLocalValue( param && param.example || null, languageCode );
};

/**
 * The auto-value will be used by the template in case the user doesn't provide a value. In
 * VisualEditor this is only for documentation and should not appear in a serialization.
 *
 * @param {string} name Parameter name or alias
 * @return {string}
 */
ve.dm.MWTemplateSpecModel.prototype.getParameterAutoValue = function ( name ) {
	var param = this.templateData.params[ this.getPrimaryParameterName( name ) ];
	return param && param.autovalue || '';
};

/**
 * @param {string} name Parameter name or alias
 * @return {string} e.g. "string"
 */
ve.dm.MWTemplateSpecModel.prototype.getParameterType = function ( name ) {
	var param = this.templateData.params[ this.getPrimaryParameterName( name ) ];
	return param && param.type || 'string';
};

/**
 * Warning, this does not return a copy. Don't manipulate the returned array.
 *
 * @param {string} name Parameter name or alias
 * @return {string[]} Alternate parameter names
 */
ve.dm.MWTemplateSpecModel.prototype.getParameterAliases = function ( name ) {
	var param = this.templateData.params[ this.getPrimaryParameterName( name ) ];
	return param && param.aliases || [];
};

/**
 * Get the parameter name, resolving an alias.
 *
 * If a parameter is not an alias of another, the output will be the same as the input.
 *
 * @param {string} name Parameter name or alias
 * @return {string}
 */
ve.dm.MWTemplateSpecModel.prototype.getPrimaryParameterName = function ( name ) {
	return this.aliases[ name ] || name;
};

/**
 * @param {string} name Parameter name or alias
 * @return {boolean}
 */
ve.dm.MWTemplateSpecModel.prototype.isParameterRequired = function ( name ) {
	var param = this.templateData.params[ this.getPrimaryParameterName( name ) ];
	return !!( param && param.required );
};

/**
 * @param {string} name Parameter name or alias
 * @return {boolean}
 */
ve.dm.MWTemplateSpecModel.prototype.isParameterSuggested = function ( name ) {
	var param = this.templateData.params[ this.getPrimaryParameterName( name ) ];
	return !!( param && param.suggested );
};

/**
 * @param {string} name Parameter name or alias
 * @return {boolean}
 */
ve.dm.MWTemplateSpecModel.prototype.isParameterDeprecated = function ( name ) {
	var param = this.templateData.params[ this.getPrimaryParameterName( name ) ];
	return !!( param && ( param.deprecated || typeof param.deprecated === 'string' ) );
};

/**
 * @param {string} name Parameter name or alias
 * @return {string} Explaining of why parameter is deprecated, empty if parameter is either not
 *   deprecated or no description has been specified
 */
ve.dm.MWTemplateSpecModel.prototype.getParameterDeprecationDescription = function ( name ) {
	var param = this.templateData.params[ this.getPrimaryParameterName( name ) ];
	return param && typeof param.deprecated === 'string' ? param.deprecated : '';
};

/**
 * Get all known primary parameter names, without aliases, in their original order as they became
 * known (usually but not necessarily the order in which they appear in the template). This still
 * includes undocumented parameters that have been part of the template at some point during the
 * lifetime of this object, but have been removed from the linked {@see ve.dm.MWTemplateModel} in
 * the meantime.
 *
 * The returned array is a copy, i.e. it's safe to manipulate.
 *
 * @return {string[]} Primary parameter names
 */
ve.dm.MWTemplateSpecModel.prototype.getKnownParameterNames = function () {
	return Object.keys( this.seenParameterNames );
};

/**
 * See https://www.mediawiki.org/wiki/Extension:TemplateData#Set_object
 *
 * @return {{label:(string|Object.<string,string>),params:string[]}[]}
 */
ve.dm.MWTemplateSpecModel.prototype.getParameterSets = function () {
	return this.templateData.sets || [];
};

/**
 * See https://www.mediawiki.org/wiki/Extension:TemplateData#Maps_object
 *
 * @return {Object.<string,Object.<string,string|string[]|string[][]>>}
 */
ve.dm.MWTemplateSpecModel.prototype.getMaps = function () {
	return this.templateData.maps || {};
};
