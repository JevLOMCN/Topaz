/*!
 * VisualEditor DataModel MWTemplateModel class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Represents a template invocation that's part of a (possibly unbalanced) sequence of template
 * invocations and raw wikitext snippets. Meant to be an item in a {@see ve.dm.MWTransclusionModel}.
 * Holds a back-reference to its parent.
 *
 * Holds a reference to the specification of the template, i.e. how the template is documented via
 * TemplateData. The actual invocation might be entirely different, missing parameters as well as
 * containing undocumented ones.
 *
 * @class
 * @extends ve.dm.MWTransclusionPartModel
 *
 * @constructor
 * @param {ve.dm.MWTransclusionModel} transclusion
 * @param {Object} target Template target
 * @param {string} target.wt Template name as originally used in the wikitext, including optional
 *  whitespace
 * @param {string} [target.href] Hypertext reference to target, e.g. "./Template:Example"
 */
ve.dm.MWTemplateModel = function VeDmMWTemplateModel( transclusion, target ) {
	// Parent constructor
	ve.dm.MWTemplateModel.super.call( this, transclusion );

	// Properties
	this.target = target;

	// TODO: Either here or in uses of this constructor we need to validate the title
	this.title = target.href ? mw.libs.ve.normalizeParsoidResourceName( target.href ) : null;
	this.orderedParameterNames = null;
	this.params = {};
	this.spec = new ve.dm.MWTemplateSpecModel( this );
	this.originalData = null;
};

/* Inheritance */

OO.inheritClass( ve.dm.MWTemplateModel, ve.dm.MWTransclusionPartModel );

/* Events */

/**
 * Emitted when a new parameter was added to the template.
 *
 * @event add
 * @param {ve.dm.MWParameterModel} param Added param
 */

/**
 * Emitted when a parameter was removed from the template.
 *
 * @event remove
 * @param {ve.dm.MWParameterModel} param Removed param
 */

/**
 * Emitted when anything changed, e.g. a parameter was added or removed, or a parameter's value
 * edited.
 *
 * @event change
 */

/* Static Methods */

/**
 * Data is in the format provided by Parsoid.
 *
 * @param {ve.dm.MWTransclusionModel} transclusion Transclusion template is in
 * @param {Object} data Template data
 * @param {Object.<string,{wt:string}>} data.params
 * @return {ve.dm.MWTemplateModel} New template model
 */
ve.dm.MWTemplateModel.newFromData = function ( transclusion, data ) {
	var template = new ve.dm.MWTemplateModel( transclusion, data.target );

	for ( var key in data.params ) {
		template.addParameter(
			new ve.dm.MWParameterModel( template, key, data.params[ key ].wt )
		);
	}

	template.setOriginalData( data );

	return template;
};

/**
 * Name is equivalent to what would be entered between double brackets, defaulting to the Template
 * namespace, using a leading colon to access other namespaces.
 *
 * @param {ve.dm.MWTransclusionModel} transclusion Transclusion template is in
 * @param {string|mw.Title} name Template name
 * @return {ve.dm.MWTemplateModel|null} New template model
 */
ve.dm.MWTemplateModel.newFromName = function ( transclusion, name ) {
	var title,
		templateNs = mw.config.get( 'wgNamespaceIds' ).template;
	if ( name instanceof mw.Title ) {
		title = name;
		name = title.getRelativeText( templateNs );
	} else {
		title = mw.Title.newFromText( name, templateNs );
	}
	if ( title !== null ) {
		var href = title.getPrefixedText();
		return new ve.dm.MWTemplateModel( transclusion, { href: href, wt: name } );
	}

	return null;
};

/* Methods */

/**
 * @return {Object} Template target
 */
ve.dm.MWTemplateModel.prototype.getTarget = function () {
	return this.target;
};

/**
 * @return {string|null} Prefixed template title including the "Template:" namespace, if available.
 *  Use {@see ve.dm.MWTemplateSpecModel.getLabel} for a human-readable label without the namespace.
 */
ve.dm.MWTemplateModel.prototype.getTitle = function () {
	return this.title;
};

/**
 * @return {string|null} Prefixed page name including the `Template:` namespace, but with syntax
 *  elements like `subst:` stripped.
 */
ve.dm.MWTemplateModel.prototype.getTemplateDataQueryTitle = function () {
	// FIXME: This currently doesn't strip localized versions of these magic words.
	// Strip magic words {{subst:…}} and {{safesubst:…}}, see MagicWordFactory::$mSubstIDs
	var name = this.target.wt.replace( /^\s*(?:safe)?subst:/i, '' ),
		templateNs = mw.config.get( 'wgNamespaceIds' ).template,
		title = mw.Title.newFromText( name, templateNs );
	return title ? title.getPrefixedText() : this.getTitle();
};

/**
 * @return {ve.dm.MWTemplateSpecModel} Template specification
 */
ve.dm.MWTemplateModel.prototype.getSpec = function () {
	return this.spec;
};

/**
 * Get all parameters that are currently present in this template invocation in the order as they
 * originally appear in the wikitext. This is critical for {@see serialize}. Might contain
 * placeholders with the parameter name "".
 *
 * @return {Object.<string,ve.dm.MWParameterModel>} Parameters keyed by name or alias
 */
ve.dm.MWTemplateModel.prototype.getParameters = function () {
	return this.params;
};

/**
 * @param {string} name Parameter name or alias as originally used in the wikitext
 * @return {ve.dm.MWParameterModel|undefined}
 */
ve.dm.MWTemplateModel.prototype.getParameter = function ( name ) {
	return this.params[ name ];
};

/**
 * Check if a parameter with this name or one of its aliases is currently part of this template.
 *
 * @param {string} name Parameter name or alias
 * @return {boolean} Parameter is in the template
 */
ve.dm.MWTemplateModel.prototype.hasParameter = function ( name ) {
	return this.getOriginalParameterName( name ) in this.params;
};

/**
 * @param {string} name Parameter name or alias
 * @return {string} Parameter name or alias as originally used in the wikitext
 */
ve.dm.MWTemplateModel.prototype.getOriginalParameterName = function ( name ) {
	if ( name in this.params ) {
		return name;
	}
	var aliases = this.spec.getParameterAliases( name );
	// FIXME: Should use .filter() when we dropped IE11 support
	for ( var i = 0; i < aliases.length; i++ ) {
		if ( aliases[ i ] in this.params ) {
			return aliases[ i ];
		}
	}
	return this.spec.getPrimaryParameterName( name );
};

/**
 * Get all current and potential parameter names in a canonical order that's always the same,
 * unrelated to how the parameters appear in the wikitext. Parameter names and aliases documented
 * via TemplateData are first, in their documented order. Undocumented parameters are sorted with
 * numeric names first, followed by alphabetically sorted names. The unnamed placeholder parameter
 * is last.
 *
 * @return {string[]}
 */
ve.dm.MWTemplateModel.prototype.getAllParametersOrdered = function () {
	var primaryName,
		spec = this.spec,
		usedAliases = {};

	for ( var alias in this.params ) {
		if ( spec.isParameterAlias( alias ) ) {
			primaryName = spec.getPrimaryParameterName( alias );
			if ( !usedAliases[ primaryName ] ) {
				// Skip primary name when it's not used, otherwise move it to the front
				usedAliases[ primaryName ] = primaryName in this.params ? [ primaryName ] : [];
			}
			// Append aliases in their original order (not documented order)
			usedAliases[ primaryName ].push( alias );
		}
	}

	var parameters = spec.getCanonicalParameterOrder();

	// Restore aliases originally used in the wikitext. The spec doesn't know which alias was used.
	for ( primaryName in usedAliases ) {
		var i = parameters.indexOf( primaryName );
		// TODO: parameters.splice( i, 1, ...usedAliases[ primaryName ] ) when we can use ES6
		parameters = parameters.slice( 0, i ).concat( usedAliases[ primaryName ],
			parameters.slice( i + 1 ) );
	}

	// Restore the placeholder, if present. The spec doesn't keep track of placeholders.
	if ( '' in this.params ) {
		parameters.push( '' );
	}

	// TODO: cache results
	return parameters;
};

/**
 * Returns the same parameters as {@see getParameters}, i.e. parameters that are currently present
 * in this template invocation, but sorted in a canonical order for presentational purposes.
 *
 * Don't use this if you need the parameters as they originally appear in the wikitext, or if you
 * don't care about an order. Use {@see getParameters} together with `Object.keys()` instead.
 *
 * @return {string[]} Sorted list of parameter names
 */
ve.dm.MWTemplateModel.prototype.getOrderedParameterNames = function () {
	if ( !this.orderedParameterNames ) {
		var params = this.params;
		this.orderedParameterNames = this.getAllParametersOrdered().filter( function ( name ) {
			return name in params;
		} );
	}
	return this.orderedParameterNames;
};

/**
 * @param {ve.dm.MWParameterModel} param Parameter to add
 * @fires add
 * @fires change
 */
ve.dm.MWTemplateModel.prototype.addParameter = function ( param ) {
	var name = param.getName();
	if ( name in this.params ) {
		return;
	}

	this.orderedParameterNames = null;
	this.params[ name ] = param;
	this.spec.fillFromTemplate();
	// This forwards change events from the nested ve.dm.MWParameterModel upwards. The array
	// syntax is a way to call `this.emit( 'change' )`.
	param.connect( this, { change: [ 'emit', 'change' ] } );
	this.emit( 'add', param );
	this.emit( 'change' );
};

/**
 * Remove a parameter from this MWTemplateModel, and emit events which result in removing the
 * parameter from the UI. Note this does *not* remove the parameter from the linked specification.
 *
 * @param {ve.dm.MWParameterModel} [param]
 * @fires remove
 * @fires change
 */
ve.dm.MWTemplateModel.prototype.removeParameter = function ( param ) {
	if ( param ) {
		this.orderedParameterNames = null;
		delete this.params[ param.getName() ];
		param.disconnect( this );
		this.emit( 'remove', param );
		this.emit( 'change' );
	}
};

/**
 * Add all non-existing required and suggested parameters, if any.
 */
ve.dm.MWTemplateModel.prototype.addPromptedParameters = function () {
	var params = this.params,
		spec = this.spec,
		names = spec.getKnownParameterNames();

	for ( var i = 0; i < names.length; i++ ) {
		var name = names[ i ];
		var foundAlias = spec.getParameterAliases( name ).some( function ( alias ) {
			return alias in params;
		} );
		if (
			!foundAlias &&
			!params[ name ] &&
			(
				spec.isParameterRequired( name ) ||
				spec.isParameterSuggested( name )
			)
		) {
			this.addParameter( new ve.dm.MWParameterModel( this, names[ i ] ) );
		}
	}
};

/**
 * Set original data, to be used as a base for serialization.
 *
 * @private
 * @param {Object} data Original data
 * @param {Object.<string,Object>} [data.params]
 */
ve.dm.MWTemplateModel.prototype.setOriginalData = function ( data ) {
	this.originalData = data;
};

/**
 * @inheritdoc
 */
ve.dm.MWTemplateModel.prototype.serialize = function () {
	var origData = this.originalData || {},
		origParams = origData.params || {},
		template = { target: this.target, params: {} },
		spec = this.spec,
		params = this.params;

	for ( var name in params ) {
		if ( name === '' ) {
			continue;
		}

		if (
			// Don't add empty parameters (T101075)
			params[ name ].getValue() === '' &&
			// …unless they were present before the edit
			!Object.prototype.hasOwnProperty.call( origParams, name ) &&
			// …unless they are required (T276989)
			!spec.isParameterRequired( name )
		) {
			continue;
		}

		var origName = params[ name ].getOriginalName();
		template.params[ origName ] = ve.extendObject(
			{},
			origParams[ origName ],
			{ wt: params[ name ].getValue() }
		);

	}

	// Performs a non-deep extend, so this won't reintroduce
	// deleted parameters (T75134)
	template = ve.extendObject( {}, origData, template );
	return { template: template };
};

/**
 * @inheritdoc
 */
ve.dm.MWTemplateModel.prototype.containsValuableData = function () {
	var params = this.params;

	return Object.keys( params ).some( function ( name ) {
		// Skip unnamed placeholders
		if ( !name ) {
			return false;
		}

		var param = params[ name ],
			value = param.getValue();
		return value &&
			// This will automatically be restored, see {@see ve.dm.MWParameterModel.getValue}
			value !== param.getAutoValue() &&
			// While this isn't always meaningless, it typically is, and it's easy to restore
			value !== param.getDefaultValue();
	} );
};
