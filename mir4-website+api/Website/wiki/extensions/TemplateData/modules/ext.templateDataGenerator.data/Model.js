/**
 * TemplateData Model
 *
 * @class
 * @mixes OO.EventEmitter
 *
 * @constructor
 */
function Model() {
	// Mixin constructors
	OO.EventEmitter.call( this );

	// Properties
	this.description = {};

	this.maps = undefined;
	this.mapsChanged = false;
	this.originalMaps = undefined;

	this.format = null;

	this.params = {};
	this.paramIdentifierCounter = 2;
	this.sourceCodeParameters = [];
	this.paramOrder = [];
	this.paramOrderChanged = false;

	this.originalTemplateDataObject = null;
}

/* Inheritance */

OO.mixinClass( Model, OO.EventEmitter );

/* Events */

/**
 * @event add-param
 * @param {string} key Parameter key
 * @param {Object} data Parameter data
 */

/**
 * @event change-description
 * @param {string} description New template description
 * @param {Object} [language] Description language, if supplied
 */

/**
 * @event change-paramOrder
 * @param {string[]} orderArray Parameter key array in order
 */

/**
 * @event change-property
 * @param {string} paramKey Parameter key
 * @param {string} prop Property name
 * @param {Mixed} value
 * @param {string} language
 */

/**
 * @event change
 */

/* Static Methods */

/**
 * Compare two objects or strings
 *
 * @param {Object|string} obj1 Base object
 * @param {Object|string} obj2 Compared object
 * @param {boolean} [allowSubset] Allow the second object to be a
 *  partial object (or a subset) of the first.
 * @return {boolean} Objects have equal values
 */
Model.static.compare = function ( obj1, obj2, allowSubset ) {
	if ( allowSubset && obj2 === undefined ) {
		return true;
	}

	// Make sure the objects are of the same type
	if ( typeof obj1 !== typeof obj2 ) {
		return false;
	}

	// Comparing objects or arrays
	if ( typeof obj1 === 'object' ) {
		return OO.compare( obj2, obj1, allowSubset );
	}

	// Everything else (primitive types, functions, etc)
	return obj1 === obj2;
};

/**
 * Translate obsolete parameter types into the new types
 *
 * @param {string} paramType Given type
 * @return {string} Normalized non-obsolete type
 */
Model.static.translateObsoleteParamTypes = function ( paramType ) {
	return paramType.replace( /^string\//, '' );
};

/**
 * Retrieve information about all legal properties for a parameter.
 *
 * @param {boolean} getFullData Retrieve full information about each
 *  parameter. If false, the method will return an array of property
 *  names only.
 * @return {Object|string[]} Legal property names with or without their
 *  definition data
 */
Model.static.getAllProperties = function ( getFullData ) {
	var properties = {
		name: {
			type: 'string',
			// Validation regex
			restrict: /[|=]|}}/
		},
		aliases: {
			type: 'array'
		},
		label: {
			type: 'string',
			allowLanguages: true
		},
		description: {
			type: 'string',
			allowLanguages: true
		},
		example: {
			type: 'string',
			allowLanguages: true
		},
		type: {
			type: 'select',
			children: [
				'unknown',
				'boolean',
				'content',
				'wiki-file-name',
				'line',
				'number',
				'date',
				'wiki-page-name',
				'string',
				'wiki-template-name',
				'unbalanced-wikitext',
				'url',
				'wiki-user-name'
			],
			default: 'unknown'
		},
		suggestedvalues: {
			type: 'array'
		},
		default: {
			type: 'string',
			multiline: true,
			allowLanguages: true
		},
		autovalue: {
			type: 'string'
		},
		status: {
			type: 'select',
			children: [
				'optional',
				'deprecated',
				'required',
				'suggested'
			],
			default: 'optional'
		},
		deprecated: {
			type: 'boolean',
			// This should only be defined for boolean properties.
			// Define the property that represents the text value.
			textValue: 'deprecatedValue'
		},
		deprecatedValue: {
			type: 'string'
		},
		required: {
			type: 'boolean'
		},
		suggested: {
			type: 'boolean'
		}
	};

	if ( !getFullData ) {
		return Object.keys( properties );
	} else {
		return properties;
	}
};

/**
 * Retrieve the list of property names that allow for multiple languages.
 *
 * @return {string[]} Property names
 */
Model.static.getPropertiesWithLanguage = function () {
	var result = [],
		propDefinitions = this.getAllProperties( true );

	for ( var prop in propDefinitions ) {
		if ( propDefinitions[ prop ].allowLanguages ) {
			result.push( prop );
		}
	}
	return result;
};

/**
 * Split a string into an array and clean/trim the values
 *
 * @param {string} str String to split
 * @param {string} [delim] Delimeter
 * @return {string[]} Clean array
 */
Model.static.splitAndTrimArray = function ( str, delim ) {
	delim = delim || mw.msg( 'comma-separator' );

	var arr = [];
	str.split( delim ).forEach( function ( part ) {
		var trimmed = part.trim();
		if ( trimmed ) {
			arr.push( trimmed );
		}
	} );

	return arr;
};

/**
 * This is an adjustment of OO.simpleArrayUnion that ignores
 * empty values when inserting into the unified array.
 *
 * @param {...Array} arrays Arrays to union
 * @return {Array} Union of the arrays
 */
Model.static.arrayUnionWithoutEmpty = function () {
	var result = OO.simpleArrayUnion.apply( this, arguments );

	// Trim and filter empty strings
	return result.filter( function ( i ) {
		return i.trim();
	} );
};

/**
 * Create a new mwTemplateData.Model from templatedata object.
 *
 * @param {Object|null} tdObject TemplateData parsed object, or null if we are creating a new object.
 * @param {string[]} paramsInSource Parameter names found in template source
 * @return {Model} New model
 */
Model.static.newFromObject = function ( tdObject, paramsInSource ) {
	var model = new Model();

	model.setSourceCodeParameters( paramsInSource || [] );

	// Store the original templatedata object for comparison later
	model.setOriginalTemplateDataObject( tdObject );

	tdObject = tdObject || { params: {} };

	// Initialize the model
	model.params = {};

	// Add params
	if ( tdObject.params ) {
		for ( var param in tdObject.params ) {
			model.addParam( param, tdObject.params[ param ] );
		}
	}

	// maps
	if ( tdObject.maps ) {
		model.setMapInfo( tdObject.maps );
	}

	model.setTemplateDescription( tdObject.description );

	// Override the param order if it exists in the templatedata string
	if ( tdObject.paramOrder && tdObject.paramOrder.length > 0 ) {
		model.setTemplateParamOrder( tdObject.paramOrder );
	}

	if ( tdObject.format !== undefined ) {
		model.setTemplateFormat( tdObject.format );
	}

	return model;
};

/* Methods */

/**
 * Go over the importable parameters and check if they are
 * included in the parameter model. Return the parameter names
 * that are not included yet.
 *
 * @return {string[]} Parameters that are not yet included in
 *  the model
 */
Model.prototype.getMissingParams = function () {
	var allParamNames = this.getAllParamNames(),
		sourceCodeParameters = this.sourceCodeParameters;

	return sourceCodeParameters.filter( function ( sourceCodeParameter ) {
		return allParamNames.indexOf( sourceCodeParameter ) === -1;
	} );
};

/**
 * Add imported parameters into the model
 *
 * @return {Object} Parameters added. -1 for failure.
 */
Model.prototype.importSourceCodeParameters = function () {
	var model = this,
		allParamNames = this.getAllParamNames(),
		existingArray = [],
		importedArray = [],
		skippedArray = [];

	// Check existing params
	allParamNames.forEach( function ( paramKey ) {
		if ( model.sourceCodeParameters.indexOf( paramKey ) !== -1 ) {
			existingArray.push( paramKey );
		}
	} );

	// Add sourceCodeParameters to the model
	this.sourceCodeParameters.forEach( function ( sourceCodeParameter ) {
		if ( existingArray.indexOf( sourceCodeParameter ) === -1 ) {
			model.addParam( sourceCodeParameter );
			importedArray.push( sourceCodeParameter );
		} else {
			skippedArray.push( sourceCodeParameter );
		}
	} );

	return {
		imported: importedArray,
		skipped: skippedArray,
		existing: existingArray
	};
};

/**
 * Retrieve all existing language codes in the current templatedata model
 *
 * @return {string[]} Language codes in use
 */
Model.prototype.getExistingLanguageCodes = function () {
	var result = [];

	// Take languages from the template description
	if ( $.isPlainObject( this.description ) ) {
		result = Object.keys( this.description );
	}

	var languageProps = this.constructor.static.getPropertiesWithLanguage();
	// Go over the parameters
	for ( var param in this.params ) {
		// Go over the properties
		for ( var prop in this.params[ param ] ) {
			if ( languageProps.indexOf( prop ) !== -1 ) {
				result = this.constructor.static.arrayUnionWithoutEmpty( result, Object.keys( this.params[ param ][ prop ] ) );
			}
		}
	}

	return result;
};

/**
 * Add parameter to the model
 *
 * @param {string} key Parameter key
 * @param {Object} [paramData] Parameter data
 * @fires add-param
 * @fires change
 */
Model.prototype.addParam = function ( key, paramData ) {
	var existingNames = this.getAllParamNames(),
		data = $.extend( true, {}, paramData );

	var name = key;
	// Check that the parameter is not already in the model
	if ( this.params[ key ] || existingNames.indexOf( key ) !== -1 ) {
		// Change parameter key
		key = this.getNewValidParameterKey( key );
	}

	// Initialize
	this.params[ key ] = {};

	// Store the key
	this.params[ key ].name = name;

	// Mark the parameter if it is in the template source
	if ( this.sourceCodeParameters.indexOf( key ) !== -1 ) {
		this.params[ key ].inSource = true;
	}

	// Translate types
	if ( data.type !== undefined ) {
		data.type = this.constructor.static.translateObsoleteParamTypes( data.type );
		this.params[ key ].type = data.type;
	}

	// Get the deprecated value
	if ( typeof data.deprecated === 'string' ) {
		this.params[ key ].deprecatedValue = data.deprecated;
	}

	// Go over the rest of the data
	if ( data ) {
		var language = this.getDefaultLanguage();
		var propertiesWithLanguage = this.constructor.static.getPropertiesWithLanguage();
		var allProps = this.constructor.static.getAllProperties( true );
		for ( var prop in data ) {
			var propToSet = prop;
			if (
				// This is to make sure that forwards compatibility is achieved
				// and the code doesn't die on properties that aren't valid
				allProps[ prop ] &&
				// Check if property should have its text represented in another internal property
				// (for example, deprecated and deprecatedValue)
				allProps[ prop ].textValue
			) {
				// Set the textValue property
				propToSet = allProps[ prop ].textValue;
				// Set the boolean value in the current property
				this.setParamProperty( key, prop, !!data[ prop ], language );
				if ( typeof data[ prop ] === 'boolean' ) {
					// Only set the value of the dependent if the value is a string or
					// language. Otherwise, if the value is boolean, keep the dependent
					// empty.
					continue;
				}
			}

			if (
				propertiesWithLanguage.indexOf( propToSet ) !== -1 &&
				$.isPlainObject( data[ prop ] )
			) {
				// Add all language properties
				for ( var lang in data[ prop ] ) {
					this.setParamProperty( key, propToSet, data[ prop ], lang );
				}
			} else {
				this.setParamProperty( key, propToSet, data[ prop ], language );
			}
		}
	}

	// Add to paramOrder
	this.addKeyTemplateParamOrder( key );

	// Trigger the add parameter event
	this.emit( 'add-param', key, this.params[ key ] );
	this.emit( 'change' );
};

/**
 * Retrieve an array of all used parameter names. Note that parameter
 * names can be different than their stored keys.
 *
 * @return {string[]} Used parameter names
 */
Model.prototype.getAllParamNames = function () {
	var result = [];
	for ( var key in this.params ) {
		var param = this.params[ key ];
		result.push( param.name );
		if ( param.aliases ) {
			result = result.concat( param.aliases );
		}
	}

	return result;
};

/**
 * Set the template description
 *
 * @param {string|Object} desc New template description
 * @param {string} [language] Description language, if supplied. If not given,
 *  will default to the wiki language.
 * @fires change-description
 * @fires change
 */
Model.prototype.setTemplateDescription = function ( desc, language ) {
	language = language || this.getDefaultLanguage();

	if ( !this.constructor.static.compare( this.description[ language ], desc ) ) {
		if ( typeof desc === 'object' ) {
			$.extend( this.description, desc );
			this.emit( 'change-description', desc[ language ], language );
		} else {
			this.description[ language ] = desc;
			this.emit( 'change-description', desc, language );
		}
		this.emit( 'change' );
	}
};

/**
 * Get the template description.
 *
 * @param {string} [language] Optional language key
 * @return {string}
 */
Model.prototype.getTemplateDescription = function ( language ) {
	language = language || this.getDefaultLanguage();
	return this.description[ language ];
};

/**
 * @param {Object|undefined} map New template map info
 * @fires change-map
 * @fires change
 */
Model.prototype.setMapInfo = function ( map ) {
	if ( map !== undefined ) {
		if ( !this.constructor.static.compare( this.maps, map ) ) {
			if ( this.mapsChanged === false ) {
				this.originalMaps = OO.copy( map );
				this.mapsChanged = true;
			}
			this.maps = map;
			this.emit( 'change-map', map );
			this.emit( 'change' );
		}
	}
};

/**
 * Get the template info.
 *
 * @return {Object|undefined} The template map info.
 */
Model.prototype.getMapInfo = function () {
	return this.maps;
};

/**
 * Get the template info.
 *
 * @return {Object|undefined} The Original template map info.
 */
Model.prototype.getOriginalMapsInfo = function () {
	return this.originalMaps;
};

/**
 * Get a specific parameter's localized property
 *
 * @param {string} paramKey Parameter key
 * @param {string} property Property name
 * @param {string} [language] Optional language key
 * @return {string} Parameter property in specified language
 */
Model.prototype.getParamValue = function ( paramKey, property, language ) {
	language = language || this.getDefaultLanguage();
	return OO.getProp( this.params, paramKey, property, language ) || '';
};

/**
 * Get the current wiki language code. Defaults on 'en'.
 *
 * @return {string} Wiki language
 */
Model.prototype.getDefaultLanguage = function () {
	return mw.config.get( 'wgContentLanguage' ) || 'en';
};

/**
 * Set template param order array.
 *
 * @param {string[]} [orderArray] Parameter key array in order
 * @fires change-paramOrder
 * @fires change
 */
Model.prototype.setTemplateParamOrder = function ( orderArray ) {
	orderArray = orderArray || [];
	// TODO: Make the compare method verify order of array?
	// Copy the array
	this.paramOrder = orderArray.slice();
	this.emit( 'change-paramOrder', orderArray );
	this.emit( 'change' );
};

/**
 * Set template format.
 *
 * @param {string|null} [format=null] Preferred format
 * @fires change-format
 * @fires change
 */
Model.prototype.setTemplateFormat = function ( format ) {
	format = format || null;
	if ( this.format !== format ) {
		this.format = format;
		this.emit( 'change-format', format );
		this.emit( 'change' );
	}
};

/**
 * Add a key to the end of the paramOrder
 *
 * @param {string} key New key the add into the paramOrder
 * @fires add-paramOrder
 * @fires change
 */
Model.prototype.addKeyTemplateParamOrder = function ( key ) {
	if ( this.paramOrder.indexOf( key ) === -1 ) {
		this.paramOrder.push( key );
		this.emit( 'add-paramOrder', key );
		this.emit( 'change' );
	}
};

/**
 * TODO: document
 *
 * @param {string} key
 * @param {number} newIndex
 * @fires change-paramOrder
 * @fires change
 */
Model.prototype.reorderParamOrderKey = function ( key, newIndex ) {
	var keyIndex = this.paramOrder.indexOf( key );
	// Move the parameter, account for left shift if moving forwards
	this.paramOrder.splice(
		newIndex - ( newIndex > keyIndex ? 1 : 0 ),
		0,
		this.paramOrder.splice( keyIndex, 1 )[ 0 ]
	);

	this.paramOrderChanged = true;

	// Emit event
	this.emit( 'change-paramOrder', this.paramOrder );
	this.emit( 'change' );
};

/**
 * Add a key to the end of the paramOrder
 *
 * @param {string} key New key the add into the paramOrder
 * @fires change-paramOrder
 * @fires change
 */
Model.prototype.removeKeyTemplateParamOrder = function ( key ) {
	var keyPos = this.paramOrder.indexOf( key );
	if ( keyPos > -1 ) {
		this.paramOrder.splice( keyPos, 1 );
		this.emit( 'change-paramOrder', this.paramOrder );
		this.emit( 'change' );
	}
};

/**
 * Retrieve the template paramOrder array
 *
 * @return {string[]} orderArray Parameter keys in order
 */
Model.prototype.getTemplateParamOrder = function () {
	return this.paramOrder;
};

/**
 * Retrieve the template preferred format
 *
 * @return {string|null} Preferred format
 */
Model.prototype.getTemplateFormat = function () {
	return this.format;
};

/**
 * Set a specific parameter's property
 *
 * @param {string} paramKey Parameter key
 * @param {string} prop Property name
 * @param {Mixed} value
 * @param {string} [language] Value language
 * @return {boolean} Operation was successful
 * @fires change-property
 * @fires change
 */
Model.prototype.setParamProperty = function ( paramKey, prop, value, language ) {
	var allProps = this.constructor.static.getAllProperties( true ),
		status = false;

	language = language || this.getDefaultLanguage();
	if ( !allProps[ prop ] ) {
		// The property isn't supported yet
		return status;
	}

	var propertiesWithLanguage = this.constructor.static.getPropertiesWithLanguage();
	// Check if the property is split by language code
	if ( propertiesWithLanguage.indexOf( prop ) !== -1 ) {
		// Initialize property if necessary
		if ( !$.isPlainObject( this.params[ paramKey ][ prop ] ) ) {
			this.params[ paramKey ][ prop ] = {};
		}
		value = $.isPlainObject( value ) ? value[ language ] : value;
		// Compare with language
		if ( !this.constructor.static.compare( this.params[ paramKey ][ prop ][ language ], value ) ) {
			this.params[ paramKey ][ prop ][ language ] = value;
			this.emit( 'change-property', paramKey, prop, value, language );
			this.emit( 'change' );
			status = true;
		}
	} else {
		// Compare without language
		if ( !this.constructor.static.compare( this.params[ paramKey ][ prop ], value ) ) {
			var oldValue = this.params[ paramKey ][ prop ];
			this.params[ paramKey ][ prop ] = value;

			var newKey;
			if ( prop === 'name' && oldValue !== value ) {
				newKey = value;
				// See if the parameters already has something with this new key
				if ( this.params[ newKey ] && !this.params[ newKey ].deleted ) {
					// Change the key to be something else
					newKey = this.getNewValidParameterKey( newKey );
				}
				// Copy param details to new name
				this.params[ newKey ] = this.params[ paramKey ];
				// Delete the old param
				this.params[ paramKey ] = { deleted: true };
			}

			this.emit( 'change-property', paramKey, prop, value, language );
			this.emit( 'change' );

			if ( prop === 'name' ) {
				this.paramOrder[ this.paramOrder.indexOf( paramKey ) ] = newKey || value;
				this.paramOrderChanged = true;
				this.emit( 'change-paramOrder', this.paramOrder );
				this.emit( 'change' );
			}

			status = true;
		}
	}

	if ( allProps[ prop ].textValue && value === false ) {
		// Unset the text value if the boolean it depends on is false
		status = this.setParamProperty( paramKey, allProps[ prop ].textValue, '', language );
	}

	return status;
};

/**
 * Mark a parameter for deletion.
 * Don't actually delete the parameter so we can make sure it is removed
 * from the final output.
 *
 * @param {string} paramKey Parameter key
 * @fires delete-param
 * @fires change
 */
Model.prototype.deleteParam = function ( paramKey ) {
	this.params[ paramKey ].deleted = true;
	// Remove from paramOrder
	this.removeKeyTemplateParamOrder( paramKey );
	this.emit( 'delete-param', paramKey );
	this.emit( 'change' );
};

/**
 * Restore parameter by unmarking it as deleted.
 *
 * @param {string} paramKey Parameter key
 * @fires add-param
 * @fires change
 */
Model.prototype.restoreParam = function ( paramKey ) {
	if ( this.params[ paramKey ] ) {
		this.params[ paramKey ].deleted = false;
		// Add back to paramOrder
		this.addKeyTemplateParamOrder( paramKey );
		this.emit( 'add-param', paramKey, this.params[ paramKey ] );
		this.emit( 'change' );
	}
};

/**
 * Delete all data attached to a parameter
 *
 * @param {string} paramKey Parameter key
 */
Model.prototype.emptyParamData = function ( paramKey ) {
	if ( this.params[ paramKey ] ) {
		// Delete all data and readd the parameter
		delete this.params[ paramKey ];
		this.addParam( paramKey );
		// Mark this parameter as intentionally emptied
		this.params[ paramKey ].emptied = true;
	}
};

/**
 * Get a parameter property.
 *
 * @param {string} paramKey Parameter key
 * @param {string} prop Parameter property
 * @return {Mixed|null} Property value if it exists. Returns null if the
 * parameter key itself doesn't exist.
 */
Model.prototype.getParamProperty = function ( paramKey, prop ) {
	if ( this.params[ paramKey ] ) {
		return this.params[ paramKey ][ prop ];
	}
	return null;
};

/**
 * Retrieve a specific parameter data
 *
 * @param {string} key Parameter key
 * @return {Object|undefined} Parameter data
 */
Model.prototype.getParamData = function ( key ) {
	return this.params[ key ];
};

/**
 * Return the complete object of all parameters.
 *
 * @return {Object} All parameters and their data
 */
Model.prototype.getParams = function () {
	return this.params;
};

Model.prototype.isParamDeleted = function ( key ) {
	return this.params[ key ] && this.params[ key ].deleted === true;
};

Model.prototype.isParamExists = function ( key ) {
	return Object.prototype.hasOwnProperty.call( this.params, key );
};

/**
 * Set the original templatedata object
 *
 * @param {Object|null} templatedataObj TemplateData object
 */
Model.prototype.setOriginalTemplateDataObject = function ( templatedataObj ) {
	this.originalTemplateDataObject = templatedataObj ? $.extend( true, {}, templatedataObj ) : null;
};

/**
 * Get full page name
 *
 * @param {string} pageName Page name
 */
Model.prototype.setFullPageName = function ( pageName ) {
	this.fullPageName = pageName;
};

/**
 * Set parent page
 *
 * @param {string} parent Parent page
 */
Model.prototype.setParentPage = function ( parent ) {
	this.parentPage = parent;
};

/**
 * Get page full name
 *
 * @return {string} Page full name
 */
Model.prototype.getFullPageName = function () {
	return this.fullPageName;
};

/**
 * Get parent page
 *
 * @return {string} Parent page
 */
Model.prototype.getParentPage = function () {
	return this.parentPage;
};

/**
 * Get original Parameters/Info from the model and discard any changes
 */
Model.prototype.restoreOriginalMaps = function () {
	this.setMapInfo( this.getOriginalMapsInfo() );
};

/**
 * Get original templatedata object
 *
 * @return {Object|null} Templatedata object at the beginning of this editing session, or null
 * if we're creating a new object.
 */
Model.prototype.getOriginalTemplateDataObject = function () {
	return this.originalTemplateDataObject;
};

/**
 * Process the current model and output it
 *
 * @return {Object} Templatedata object
 */
Model.prototype.outputTemplateData = function () {
	var allProps = this.constructor.static.getAllProperties( true ),
		original = this.getOriginalTemplateDataObject() || {};
	original.params = original.params || {};
	var result = $.extend( true, {}, original ),
		defaultLang = this.getDefaultLanguage();

	var normalizedValue;
	// Template description
	if ( this.description[ defaultLang ] !== undefined ) {
		normalizedValue = this.propRemoveUnusedLanguages( this.description );
		if ( this.isOutputInLanguageObject( result.description, normalizedValue ) ) {
			result.description = normalizedValue;
		} else {
			// Store only one language as a string
			result.description = normalizedValue[ defaultLang ];
		}
	} else {
		// Delete description
		delete result.description;
	}

	// Template maps
	if ( this.maps === undefined || Object.keys( this.maps ).length === 0 ) {
		delete result.maps;
	} else {
		result.maps = this.maps;
	}

	// Param order
	if ( original.paramOrder || this.paramOrderChanged ) {
		result.paramOrder = this.paramOrder;
	} else {
		delete result.paramOrder;
	}

	// Format
	if ( this.format === null ) {
		delete result.format;
	} else {
		result.format = this.format;
	}

	// Attach sets as-is for now
	// TODO: Work properly with sets
	if ( original.sets ) {
		result.sets = original.sets;
	}

	// Go over parameters in data
	for ( var paramKey in this.params ) {
		var key = paramKey;
		if ( this.params[ key ].deleted ) {
			delete result.params[ key ];
			continue;
		}

		// If the user intentionally empties a parameter, delete it from
		// the result and treat it as a new parameter
		if ( this.params[ key ].emptied ) {
			delete result.params[ key ];
		}

		// Check if name was changed and change the key accordingly
		var name = this.params[ key ].name;
		var oldKey = key;

		// Notice for clarity:
		// Whether the parameter name was changed or not the following
		// consistency with object keys will be observed:
		// * oldKey: original will use oldKey (for comparison to the old value)
		// * key: this.params will use key (for storing without conflict)
		// * name: result will use name (for valid output)

		// Check if param is new
		if ( !result.params[ name ] ) {
			// New param. Initialize it
			result.params[ name ] = {};
		}

		// Go over all properties
		for ( var prop in allProps ) {
			if ( prop === 'status' || prop === 'deprecatedValue' || prop === 'name' ) {
				continue;
			}

			switch ( allProps[ prop ].type ) {
				case 'select':
					// Only include type if the original included type
					// or if the current type is not undefined
					if (
						original.params[ key ] &&
						original.params[ key ][ prop ] !== 'unknown' &&
						this.params[ key ][ prop ] === 'unknown'
					) {
						result.params[ name ][ prop ] = undefined;
					} else {
						result.params[ name ][ prop ] = this.params[ key ][ prop ];
					}
					break;
				case 'boolean':
					if ( !this.params[ key ][ prop ] ) {
						// Only add a literal false value if there was a false
						// value before
						if ( original.params[ oldKey ] && original.params[ oldKey ][ prop ] === false ) {
							result.params[ name ][ prop ] = false;
						} else {
							// Otherwise, delete this value
							delete result.params[ name ][ prop ];
						}
					} else {
						if ( prop === 'deprecated' ) {
							result.params[ name ][ prop ] = this.params[ key ].deprecatedValue || true;
							// Remove deprecatedValue
							delete result.params[ name ].deprecatedValue;
						} else {
							result.params[ name ][ prop ] = this.params[ key ][ prop ];
						}
					}
					break;
				case 'array':
					// Only update these if the new templatedata has an
					// array that isn't empty
					if (
						Array.isArray( this.params[ key ][ prop ] ) &&
						this.params[ key ][ prop ].length > 0
					) {
						result.params[ name ][ prop ] = this.params[ key ][ prop ];
					} else {
						// If the new array is empty, delete it from the original
						delete result.params[ name ][ prop ];
					}
					break;
				default:
					// Check if there's a value in the model
					if ( this.params[ key ][ prop ] !== undefined ) {
						var compareOrig = original.params[ oldKey ] && original.params[ oldKey ][ prop ];
						if ( allProps[ prop ].allowLanguages ) {
							normalizedValue = this.propRemoveUnusedLanguages( this.params[ key ][ prop ] );
							// Check if this should be displayed with language object or directly as string
							if ( this.isOutputInLanguageObject( compareOrig || {}, normalizedValue ) ) {
								result.params[ name ][ prop ] = normalizedValue;
							} else {
								// Store only one language as a string
								result.params[ name ][ prop ] = normalizedValue[ defaultLang ];
							}
						} else if ( this.params[ key ][ prop ] ||
							// Add empty strings only if the property existed before (empty or not)
							compareOrig !== undefined
						) {
							// Set up the result
							result.params[ name ][ prop ] = this.params[ key ][ prop ];
						}
					}
					break;
			}
		}
	}
	return result;
};

/**
 * Check the key if it already exists in the parameter list. If it does,
 * find a new key that doesn't, and return it.
 *
 * @param {string} key New parameter key
 * @return {string} Valid new parameter key
 */
Model.prototype.getNewValidParameterKey = function ( key ) {
	var allParamNames = this.getAllParamNames();
	if ( this.params[ key ] || allParamNames.indexOf( key ) !== -1 ) {
		// Change the key to be something else
		if ( /\d$/.test( key ) ) {
			key += '-';
		}
		key += this.paramIdentifierCounter;
		this.paramIdentifierCounter++;
		return this.getNewValidParameterKey( key );
	} else {
		return key;
	}
};
/**
 * Go over a language property and remove empty language key values
 *
 * @param {Object} propData Property data
 * @return {Object} Property data with only used language keys
 */
Model.prototype.propRemoveUnusedLanguages = function ( propData ) {
	var result = {};
	if ( $.isPlainObject( propData ) ) {
		for ( var key in propData ) {
			if ( propData[ key ] ) {
				result[ key ] = propData[ key ];
			}
		}
	}
	return result;
};

/**
 * Check whether the output of the current parameter property should be
 * outputted in full language mode (object) or a simple string.
 *
 * @param {string|Object} originalPropValue Original property value
 * @param {string|Object} newPropValue New property value
 * @return {boolean} Output should be a full language object
 */
Model.prototype.isOutputInLanguageObject = function ( originalPropValue, newPropValue ) {
	if (
		(
			// The original was already split to languages
			typeof originalPropValue === 'object' &&
			// Original was not an empty object
			!$.isEmptyObject( originalPropValue )
		) ||
		(
			// The new value is split to languages
			typeof newPropValue === 'object' &&
			// New object is not empty
			!$.isEmptyObject( newPropValue ) &&
			(
				// The new value doesn't have the default language
				newPropValue[ this.getDefaultLanguage() ] === undefined ||
				// There is more than just one language in the new property
				Object.keys( newPropValue ).length > 1
			)
		)
	) {
		return true;
	}
	return false;
};

/**
 * Set the parameters that are available in the template source code
 *
 * @param {string[]} sourceParams Parameters available in template source
 */
Model.prototype.setSourceCodeParameters = function ( sourceParams ) {
	this.sourceCodeParameters = sourceParams;
};

/**
 * Get the parameters that are available in the template source code
 *
 * @return {string[]} Parameters available in template source
 */
Model.prototype.getSourceCodeParameters = function () {
	return this.sourceCodeParameters;
};

module.exports = Model;
