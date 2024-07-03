/*!
 * VisualEditor Initialization Platform class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Generic Initialization platform.
 *
 * @abstract
 * @mixins OO.EventEmitter
 *
 * @constructor
 */
ve.init.Platform = function VeInitPlatform() {
	// Mixin constructors
	OO.EventEmitter.call( this );

	this.localStorage = this.createLocalStorage();
	this.sessionStorage = this.createSessionStorage();

	// Register
	ve.init.platform = this;

	// Provide messages to OOUI
	OO.ui.getUserLanguages = this.getUserLanguages.bind( this );
	OO.ui.msg = this.getMessage.bind( this );

	// Notify those waiting for a platform that they can finish initialization
	setTimeout( function () {
		ve.init.Platform.static.deferredPlatform.resolve( ve.init.platform );
	} );
};

/* Inheritance */

OO.mixinClass( ve.init.Platform, OO.EventEmitter );

/* Static Properties */

/**
 * A jQuery.Deferred that tracks when the platform has been created.
 *
 * @private
 */
ve.init.Platform.static.deferredPlatform = ve.createDeferred();

/**
 * A promise that tracks when ve.init.platform is ready for use.  When
 * this promise is resolved the platform will have been created and
 * initialized.
 *
 * This promise is safe to access early in VE startup before
 * `ve.init.platform` has been set.
 *
 * @property {jQuery.Promise}
 */
ve.init.Platform.static.initializedPromise = ve.init.Platform.static.deferredPlatform.promise().then( function ( platform ) {
	return platform.getInitializedPromise();
} );

/* Static Methods */

/**
 * Get client platform string from browser.
 *
 * @static
 * @inheritable
 * @return {string} Client platform string
 */
ve.init.Platform.static.getSystemPlatform = function () {
	return $.client.profile().platform;
};

/**
 * Check whether we are running in Edge.
 *
 * @static
 * @inheritable
 * @return {boolean} We are in Edge
 */
ve.init.Platform.static.isEdge = function () {
	return $.client.profile().name === 'edge';
};

/**
 * Check whether we are running on iOS
 *
 * @static
 * @inheritable
 * @return {boolean} We are running on iOS
 */
ve.init.Platform.static.isIos = function () {
	return /ipad|iphone|ipod/i.test( navigator.userAgent );
};

/* Methods */

/**
 * Get an anchored regular expression that matches allowed external link URLs
 * starting at the beginning of an input string.
 *
 * @abstract
 * @return {RegExp} Regular expression object
 */
ve.init.Platform.prototype.getExternalLinkUrlProtocolsRegExp = null;

/**
 * Get an unanchored regular expression that matches allowed external link URLs
 * anywhere in an input string.
 *
 * @abstract
 * @return {RegExp} Regular expression object
 */
ve.init.Platform.prototype.getUnanchoredExternalLinkUrlProtocolsRegExp = null;

/**
 * Get a regular expression that matches IDs used only for linking document
 * data to metadata. Use null if your document format does not have such IDs.
 *
 * @return {RegExp|null} Regular expression object
 */
ve.init.Platform.prototype.getMetadataIdRegExp = function () {
	return null;
};

/**
 * Show a read-only notification to the user.
 *
 * @abstract
 * @param {jQuery|string} message
 * @param {jQuery|string} [title]
 */
ve.init.Platform.prototype.notify = null;

/**
 * Get a platform config value
 *
 * @abstract
 * @param {string|string[]} key Config key, or list of keys
 * @return {Mixed|Object} Config value, or keyed object of config values if list of keys provided
 */
ve.init.Platform.prototype.getConfig = null;

/**
 * Get a user config value
 *
 * @abstract
 * @param {string|string[]} key Config key, or list of keys
 * @return {Mixed|Object} Config value, or keyed object of config values if list of keys provided
 */
ve.init.Platform.prototype.getUserConfig = null;

/**
 * Set a user config value
 *
 * @abstract
 * @param {string|Object} keyOrValueMap Key to set value for, or object mapping keys to values
 * @param {Mixed} [value] Value to set (optional, only in use when key is a string)
 */
ve.init.Platform.prototype.setUserConfig = null;

/**
 * Create a safe storage object
 *
 * @abstract
 * @return {ve.init.SafeStorage}
 */
ve.init.Platform.prototype.createSafeStorage = null;

/**
 * Create a list storage object from a safe storage object
 *
 * @param {ve.init.SafeStorage} safeStorage
 * @return {ve.init.ConflictableStorage}
 */
ve.init.Platform.prototype.createConflictableStorage = function ( safeStorage ) {
	return ve.init.createConflictableStorage( safeStorage );
};

ve.init.Platform.prototype.createLocalStorage = function () {
	var localStorage;

	try {
		localStorage = window.localStorage;
	} catch ( e ) {}

	return this.createConflictableStorage( this.createSafeStorage( localStorage ) );
};

ve.init.Platform.prototype.createSessionStorage = function () {
	var sessionStorage;

	try {
		sessionStorage = window.sessionStorage;
	} catch ( e ) {}

	return this.createConflictableStorage( this.createSafeStorage( sessionStorage ) );
};

/**
 * Add multiple messages to the localization system.
 *
 * @abstract
 * @param {Object} messages Containing plain message values
 */
ve.init.Platform.prototype.addMessages = null;

/**
 * Get a message from the localization system.
 *
 * @abstract
 * @param {string} key Message key
 * @param {...Mixed} [args] List of arguments which will be injected at $1, $2, etc. in the message
 * @return {string} Localized message, or key or '<' + key + '>' if message not found
 */
ve.init.Platform.prototype.getMessage = null;

/**
 * Get the current user's name, if the platform supports it
 *
 * @return {string|null} User name, or null if not applicable
 */
ve.init.Platform.prototype.getUserName = function () {
	return null;
};

/**
 * Parse a string into a number
 *
 * @abstract
 * @param {string} value String to be converted
 * @return {number} Number value, NaN if not a number
 */
ve.init.Platform.prototype.parseNumber = null;

/**
 * For a number as a string
 *
 * @abstract
 * @param {number} number Number to be formatted
 * @return {string} Formatted number
 */
ve.init.Platform.prototype.formatNumber = null;

/**
 * Get an HTML message from the localization system, with HTML or DOM arguments
 *
 * @abstract
 * @param {string} key Message key
 * @param {...Mixed} [args] List of arguments which will be injected at $1, $2, etc. in the message
 * @return {Node[]} Localized message, or key or '<' + key + '>' if message not found
 */
ve.init.Platform.prototype.getHtmlMessage = null;

/**
 * Add multiple parsed messages to the localization system.
 *
 * @abstract
 * @param {Object} messages Map of message-key/html pairs
 */
ve.init.Platform.prototype.addParsedMessages = null;

/**
 * Get a parsed message as HTML string.
 *
 * Does not support $# replacements.
 *
 * @abstract
 * @param {string} key Message key
 * @return {string} Parsed localized message as HTML string
 */
ve.init.Platform.prototype.getParsedMessage = null;

/**
 * Get the user language and any fallback languages.
 *
 * @abstract
 * @return {string[]} User language strings
 */
ve.init.Platform.prototype.getUserLanguages = null;

/**
 * Get a list of URL entry points where media can be found.
 *
 * @abstract
 * @return {string[]} API URLs
 */
ve.init.Platform.prototype.getMediaSources = null;

/**
 * Get a list of all language codes.
 *
 * @abstract
 * @return {string[]} Language codes
 */
ve.init.Platform.prototype.getLanguageCodes = null;

/**
 * Get a language's name from its code, in the current user language if possible.
 *
 * @abstract
 * @param {string} code Language code
 * @return {string} Language name
 */
ve.init.Platform.prototype.getLanguageName = null;

/**
 * Get a language's autonym from its code.
 *
 * @abstract
 * @param {string} code Language code
 * @return {string} Language autonym
 */
ve.init.Platform.prototype.getLanguageAutonym = null;

/**
 * Get a language's direction from its code.
 *
 * @abstract
 * @param {string} code Language code
 * @return {string} Language direction
 */
ve.init.Platform.prototype.getLanguageDirection = null;

/**
 * Initialize the platform. The default implementation is to do nothing and return a resolved
 * promise. Subclasses should override this if they have asynchronous initialization work to do.
 * The promise rejects if the platform is incompatible.
 *
 * External callers should not call this. Instead, call #getInitializedPromise.
 *
 * @private
 * @return {jQuery.Promise} Promise that will be resolved once initialization is done
 */
ve.init.Platform.prototype.initialize = function () {
	if ( !VisualEditorSupportCheck() ) {
		return ve.createDeferred().reject().promise();
	}
	return ve.createDeferred().resolve().promise();
};

/**
 * Get a promise to track when the platform has initialized. The platform won't be ready for use
 * until this promise is resolved.
 *
 * Since the initialization only happens once, and the same (resolved) promise
 * is returned when called again, and since the Platform instance is global
 * (shared between different Target instances) it is important not to rely
 * on this promise being asynchronous.
 *
 * @return {jQuery.Promise} Promise that will be resolved once the platform is ready
 */
ve.init.Platform.prototype.getInitializedPromise = function () {
	if ( !this.initialized ) {
		this.initialized = this.initialize();
	}
	return this.initialized;
};

/**
 * Post-process the symbol list.
 *
 * If a keyed object format is used, it is coverted to an array,
 * and the label property is set from the key when required.
 *
 * For individual symbols, turns the `source` property into a CSS class.
 *
 * @param {Object|Array} symbols Symbol data
 * @return {Object[]}
 */
ve.init.Platform.prototype.processSpecialCharSymbols = function ( symbols ) {
	var symbolList;
	if ( Array.isArray( symbols ) ) {
		symbolList = ve.copy( symbols );
	} else {
		symbolList = [];
		Object.keys( symbols ).forEach( function ( key ) {
			var val = symbols[ key ];
			if ( typeof val === 'object' ) {
				var symbolData = ve.copy( val );
				if ( !Object.prototype.hasOwnProperty.call( symbolData, 'label' ) ) {
					symbolData.label = key;
				}
				symbolList.push( symbolData );
			} else if ( key !== val ) {
				symbolList.push( {
					label: key,
					string: val
				} );
			} else {
				// Plain string
				symbolList.push( val );
			}
		} );
	}
	symbolList.forEach( function ( symbol ) {
		if ( symbol.source ) {
			symbol.classes = symbol.classes || [];
			symbol.classes.push( 've-ui-specialCharacterDialog-source' );
		}
	} );
	return symbolList;
};

/**
 * Fetch the special character list object
 *
 * Returns a promise which resolves with the character list
 *
 * @return {jQuery.Promise}
 */
ve.init.Platform.prototype.fetchSpecialCharList = function () {
	function tryParseJSON( json ) {
		try {
			return JSON.parse( json );
		} catch ( err ) {
			// There was no character list found, or the character list message is
			// invalid json string.
			ve.log( 've.init.Platform: Could not parse the special character list ' + json );
			ve.log( err.message );
		}
		return null;
	}

	var charsObj = {},
		groups = [ 'accents', 'mathematical', 'symbols' ];

	var platform = this;
	groups.forEach( function ( group ) {
		// The following messages are used here:
		// * visualeditor-specialcharacter-group-set-accents
		// * visualeditor-specialcharacter-group-set-mathematical
		// * visualeditor-specialcharacter-group-set-symbols
		var symbols = tryParseJSON( ve.msg( 'visualeditor-specialcharacter-group-set-' + group ) );
		if ( symbols ) {
			charsObj[ group ] = {
				// The following messages are used here:
				// * visualeditor-specialcharacter-group-label-accents
				// * visualeditor-specialcharacter-group-label-mathematical
				// * visualeditor-specialcharacter-group-label-symbols
				label: ve.msg( 'visualeditor-specialcharacter-group-label-' + group ),
				symbols: platform.processSpecialCharSymbols( symbols )
			};
		}
	} );

	// This implementation always resolves instantly
	return ve.createDeferred().resolve( charsObj ).promise();
};

/**
 * Decode HTML entities for insertion into the document
 *
 * @method
 * @param {string} html HTML string
 * @return {string}
 */
ve.init.Platform.prototype.decodeEntities = ve.safeDecodeEntities;
