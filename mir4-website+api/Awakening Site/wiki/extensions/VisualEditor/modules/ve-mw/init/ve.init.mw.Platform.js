/*!
 * VisualEditor MediaWiki Initialization Platform class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Initialization MediaWiki platform.
 *
 * @class
 * @extends ve.init.Platform
 *
 * @constructor
 */
ve.init.mw.Platform = function VeInitMwPlatform() {
	// Parent constructor
	ve.init.mw.Platform.super.call( this );

	// Properties
	this.externalLinkUrlProtocolsRegExp = new RegExp(
		'^(' + mw.config.get( 'wgUrlProtocols' ) + ')',
		'i'
	);
	this.unanchoredExternalLinkUrlProtocolsRegExp = new RegExp(
		'(' + mw.config.get( 'wgUrlProtocols' ) + ')',
		'i'
	);
	this.parsedMessages = {};
	this.linkCache = new ve.init.mw.LinkCache();
	this.imageInfoCache = new ve.init.mw.ImageInfoCache();
	this.galleryImageInfoCache = new ve.init.mw.GalleryImageInfoCache();
};

/* Inheritance */

OO.inheritClass( ve.init.mw.Platform, ve.init.Platform );

/* Methods */

/** @inheritdoc */
ve.init.mw.Platform.prototype.getExternalLinkUrlProtocolsRegExp = function () {
	return this.externalLinkUrlProtocolsRegExp;
};

/** @inheritdoc */
ve.init.mw.Platform.prototype.getUnanchoredExternalLinkUrlProtocolsRegExp = function () {
	return this.unanchoredExternalLinkUrlProtocolsRegExp;
};

/** @inheritdoc */
ve.init.mw.Platform.prototype.notify = function ( message, title, options ) {
	return mw.notify( message, ve.extendObject( { title: title }, options ) );
};

/**
 * Regular expression matching RESTBase IDs
 *
 * This isn't perfect, see T147607
 *
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.getMetadataIdRegExp = function () {
	return mw.libs.ve.restbaseIdRegExp;
};

/** @inheritdoc */
ve.init.mw.Platform.prototype.addMessages = function ( messages ) {
	return mw.messages.set( messages );
};

/**
 * @method
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.getMessage = mw.msg.bind( mw );

/**
 * @method
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.getUserName = function () {
	return mw.user.getName();
};

/**
 * @method
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.parseNumber = function ( value ) {
	var number = $.tablesorter.getParser( 'number' ).format( value );
	// formatDigit returns -Infinity when parsing fails, change this to NaN
	return number !== -Infinity ? number : NaN;
};

/**
 * @method
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.formatNumber = function ( number ) {
	return mw.language.convertNumber( number );
};

/**
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.getHtmlMessage = function () {
	return mw.message.apply( mw.message, arguments ).parseDom().toArray();
};

/**
 * @method
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.getConfig = mw.config.get.bind( mw.config );

/**
 * All values are JSON-parsed. To get raw values, use mw.user.options.get directly.
 *
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.getUserConfig = function ( keys ) {
	if ( Array.isArray( keys ) ) {
		var values = mw.user.options.get( keys );
		var parsedValues = {};
		Object.keys( values ).forEach( function ( value ) {
			try {
				parsedValues[ value ] = JSON.parse( values[ value ] );
			} catch ( e ) {
				// We might encounter corrupted values in the store
				parsedValues[ value ] = null;
			}
		} );
		return parsedValues;
	} else {
		try {
			return JSON.parse( mw.user.options.get( keys ) );
		} catch ( e ) {
			// We might encounter corrupted values in the store
			return null;
		}
	}
};

/**
 * Options must be registered in onGetPreferences
 *
 * All values are JSON encoded. To set raw values, use mw.user.options.set directly.
 *
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.setUserConfig = function ( keyOrValueMap, value ) {
	// T214963: Don't try to set user preferences for logged-out users, it doesn't work
	if ( !mw.user.isNamed() ) {
		return false;
	}

	if ( typeof keyOrValueMap === 'object' ) {
		if ( OO.compare( keyOrValueMap, this.getUserConfig( Object.keys( keyOrValueMap ) ) ) ) {
			return false;
		}
		// JSON encode all the values for API storage
		var jsonValues = {};
		Object.keys( keyOrValueMap ).forEach( function ( key ) {
			jsonValues[ key ] = JSON.stringify( keyOrValueMap[ key ] );
		} );
		ve.init.target.getLocalApi().saveOptions( jsonValues );
		return mw.user.options.set( jsonValues );
	} else {
		if ( value === this.getUserConfig( keyOrValueMap ) ) {
			return false;
		}
		// JSON encode the value for API storage
		var jsonValue = JSON.stringify( value );
		ve.init.target.getLocalApi().saveOption( keyOrValueMap, jsonValue );
		return mw.user.options.set( keyOrValueMap, jsonValue );
	}
};

ve.init.mw.Platform.prototype.createLocalStorage = function () {
	return this.createConflictableStorage( mw.storage );
};

ve.init.mw.Platform.prototype.createSessionStorage = function () {
	return this.createConflictableStorage( mw.storage.session );
};

/**
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.addParsedMessages = function ( messages ) {
	for ( var key in messages ) {
		this.parsedMessages[ key ] = messages[ key ];
	}
};

/**
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.getParsedMessage = function ( key ) {
	if ( Object.prototype.hasOwnProperty.call( this.parsedMessages, key ) ) {
		// Prefer parsed results from VisualEditorDataModule if available.
		return this.parsedMessages[ key ];
	}
	// Fallback to regular messages, with mw.message html escaping applied.
	// eslint-disable-next-line mediawiki/msg-doc
	return mw.message( key ).escaped();
};

/**
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.getLanguageCodes = function () {
	return Object.keys(
		mw.language.getData( mw.config.get( 'wgUserLanguage' ), 'languageNames' ) ||
		$.uls.data.getAutonyms()
	);
};

/**
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.getLanguageName = function ( code ) {
	var languageNames = mw.language.getData( mw.config.get( 'wgUserLanguage' ), 'languageNames' ) ||
		$.uls.data.getAutonyms();
	return languageNames[ code ] || code;
};

/**
 * @method
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.getLanguageAutonym = $.uls.data.getAutonym;

/**
 * @method
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.getLanguageDirection = $.uls.data.getDir;

/**
 * @method
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.getUserLanguages = mw.language.getFallbackLanguageChain;

/**
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.fetchSpecialCharList = function () {
	var platform = this;
	return mw.loader.using( 'mediawiki.language.specialCharacters' ).then( function () {
		var specialCharacterGroups = require( 'mediawiki.language.specialCharacters' ),
			characters = {},
			otherGroupName = mw.msg( 'visualeditor-special-characters-group-other' ),
			otherMsg = mw.message( 'visualeditor-quick-access-characters.json' ).plain(),
			// TODO: This information should be available upstream in mw.language.specialCharacters
			rtlGroups = [ 'arabic', 'arabicextended', 'hebrew' ];

		try {
			var other = JSON.parse( otherMsg );
			if ( other ) {
				characters.other = {
					label: otherGroupName,
					symbols: platform.processSpecialCharSymbols( other ),
					attributes: { dir: mw.config.get( 'wgVisualEditorConfig' ).pageLanguageDir }
				};
			}
		} catch ( err ) {
			ve.log( 've.init.mw.Platform: Could not parse the Special Character list.' );
			ve.log( err );
		}

		// eslint-disable-next-line no-jquery/no-each-util
		$.each( specialCharacterGroups, function ( groupName, groupCharacters ) {
			var groupObject = {}; // button label => character data to insert
			// eslint-disable-next-line no-jquery/no-each-util
			$.each( groupCharacters, function ( charKey, charVal ) {
				var key, val;
				// VE has a different format and it would be a pain to change it now
				if ( typeof charVal === 'string' ) {
					key = charVal;
					val = charVal;
				} else if ( typeof charVal === 'object' && 0 in charVal && 1 in charVal ) {
					key = charVal[ 0 ];
					val = charVal[ 1 ];
				} else {
					key = charVal.label;
					val = charVal;
				}
				groupObject[ key ] = val;
			} );
			// The following messages are used here:
			// * special-characters-group-arabic
			// * special-characters-group-arabicextended
			// * special-characters-group-bangla
			// * special-characters-group-canadianaboriginal
			// * special-characters-group-cyrillic
			// * special-characters-group-devanagari
			// * special-characters-group-greek
			// * special-characters-group-greekextended
			// * special-characters-group-gujarati
			// * special-characters-group-hebrew
			// * special-characters-group-ipa
			// * special-characters-group-khmer
			// * special-characters-group-lao
			// * special-characters-group-latin
			// * special-characters-group-latinextended
			// * special-characters-group-persian
			// * special-characters-group-sinhala
			// * special-characters-group-symbols
			// * special-characters-group-tamil
			// * special-characters-group-telugu
			// * special-characters-group-thai
			characters[ groupName ] = {
				label: mw.msg( 'special-characters-group-' + groupName ),
				symbols: platform.processSpecialCharSymbols( groupObject ),
				attributes: { dir: rtlGroups.indexOf( groupName ) !== -1 ? 'rtl' : 'ltr' }
			};
		} );

		return characters;
	} );
};

/**
 * @inheritdoc
 */
ve.init.mw.Platform.prototype.decodeEntities = function ( html ) {
	var character = ve.safeDecodeEntities( html );
	return [
		{
			type: 'mwEntity',
			attributes: { character: character }
		},
		{
			type: '/mwEntity'
		}
	];
};
