/*!
 * VisualEditor Standalone Initialization Platform class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Initialization Standalone platform.
 *
 *     @example
 *     var platform = new ve.init.sa.Platform( ve.messagePaths );
 *     platform.initialize().done( function () {
 *         $( document.body ).append( $( '<p>' ).text(
 *             platform.getMessage( 'visualeditor' )
 *         ) );
 *     } );
 *
 * @class
 * @extends ve.init.Platform
 *
 * @constructor
 * @param {string[]} [messagePaths] Message folder paths
 */
ve.init.sa.Platform = function VeInitSaPlatform( messagePaths ) {
	// Parent constructor
	ve.init.Platform.call( this );

	// Properties
	this.externalLinkUrlProtocolsRegExp = /^https?:\/\//i;
	this.unanchoredExternalLinkUrlProtocolsRegExp = /https?:\/\//i;
	this.messagePaths = messagePaths || [];
	this.parsedMessages = {};
	this.userLanguages = [ 'en' ];
};

/* Inheritance */

OO.inheritClass( ve.init.sa.Platform, ve.init.Platform );

/* Methods */

/** @inheritdoc */
ve.init.sa.Platform.prototype.getExternalLinkUrlProtocolsRegExp = function () {
	return this.externalLinkUrlProtocolsRegExp;
};

/** @inheritdoc */
ve.init.sa.Platform.prototype.getUnanchoredExternalLinkUrlProtocolsRegExp = function () {
	return this.unanchoredExternalLinkUrlProtocolsRegExp;
};

/** @inheritdoc */
ve.init.sa.Platform.prototype.notify = function ( message, title ) {
	var $notification = $( '<div>' ).addClass( 've-init-notification' );

	if ( title ) {
		$notification.append(
			// Never appends strings directly
			// eslint-disable-next-line no-jquery/no-append-html
			$( '<div>' ).addClass( 've-init-notification-title' ).append(
				typeof title === 'string' ? document.createTextNode( title ) : title
			)
		);
	}
	$notification.append(
		// Never appends strings directly
		// eslint-disable-next-line no-jquery/no-append-html
		$( '<div>' ).addClass( 've-init-notification-message' ).append(
			typeof message === 'string' ? document.createTextNode( message ) : message
		)
	);

	var $notificationWrapper = $( '<div>' ).addClass( 've-init-notification-wrapper' );
	$notificationWrapper.append( $notification );

	if ( !this.$notifications ) {
		this.$notifications = $( '<div>' ).addClass( 've-init-notifications' );
		$( document.body ).append( this.$notifications );
	}

	var closeId;

	function remove() {
		$notificationWrapper.remove();
	}
	function collapse() {
		$notificationWrapper.addClass( 've-init-notification-collapse' );
		setTimeout( remove, 250 );
	}
	function close() {
		clearTimeout( closeId );
		$notificationWrapper.removeClass( 've-init-notification-open' );
		$notificationWrapper.css( 'height', $notificationWrapper[ 0 ].clientHeight );
		setTimeout( collapse, 250 );
	}
	function open() {
		$notificationWrapper.addClass( 've-init-notification-open' );
		closeId = setTimeout( close, 5000 );
	}

	var rAF = window.requestAnimationFrame || setTimeout;
	rAF( open );

	$notification.on( 'click', close );

	this.$notifications.append( $notificationWrapper );
};

/**
 * Get message folder paths
 *
 * @return {string[]} Message folder paths
 */
ve.init.sa.Platform.prototype.getMessagePaths = function () {
	return this.messagePaths;
};

/** @inheritdoc */
ve.init.sa.Platform.prototype.addMessages = function ( messages ) {
	$.i18n().load( messages, $.i18n().locale );
};

/**
 * @method
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.getMessage = $.i18n;

/**
 * @method
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.parseNumber = function ( value ) {
	// TODO: Support separated numbers such as (en)123,456.78 or (fr)123.456,78
	return parseFloat( value );
};

/**
 * @method
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.formatNumber = function ( number ) {
	return number.toLocaleString();
};

/**
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.getHtmlMessage = function ( key ) {
	var $message = $( [] ),
		lastOffset = 0,
		args = arguments,
		message = this.getMessage( key );
	message.replace( /\$[0-9]+/g, function ( placeholder, offset ) {
		$message = $message.add( ve.sanitizeHtml( message.slice( lastOffset, offset ) ) );
		var placeholderIndex = +( placeholder.slice( 1 ) );
		var arg = args[ placeholderIndex ];
		$message = $message.add(
			typeof arg === 'string' ?
				// Arguments come from the code so shouldn't be sanitized
				document.createTextNode( arg ) :
				arg
		);
		lastOffset = offset + placeholder.length;
	} );
	$message = $message.add( ve.sanitizeHtml( message.slice( lastOffset ) ) );
	return $message.toArray();
};

/**
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.getConfig = function () {
	/* Standalone has no config yet */
	return null;
};

/**
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.getUserConfig = function ( keys ) {
	if ( Array.isArray( keys ) ) {
		var values = {};
		for ( var i = 0, l = keys.length; i < l; i++ ) {
			values[ keys[ i ] ] = this.getUserConfig( keys[ i ] );
		}
		return values;
	} else {
		try {
			return JSON.parse( localStorage.getItem( 've-' + keys ) );
		} catch ( e ) {
			return null;
		}
	}
};

/**
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.setUserConfig = function ( keyOrValueMap, value ) {
	if ( typeof keyOrValueMap === 'object' ) {
		for ( var i in keyOrValueMap ) {
			if ( Object.prototype.hasOwnProperty.call( keyOrValueMap, i ) ) {
				if ( !this.setUserConfig( i, keyOrValueMap[ i ] ) ) {
					// localStorage will fail if the quota is full, so further
					// sets won't work anyway.
					return false;
				}
			}
		}
	} else {
		try {
			localStorage.setItem( 've-' + keyOrValueMap, JSON.stringify( value ) );
		} catch ( e ) {
			return false;
		}
	}
	return true;
};

ve.init.sa.Platform.prototype.createSafeStorage = function ( storage ) {
	return new ve.init.sa.SafeStorage( storage );
};

/**
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.addParsedMessages = function ( messages ) {
	for ( var key in messages ) {
		this.parsedMessages[ key ] = messages[ key ];
	}
};

/**
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.getParsedMessage = function ( key ) {
	if ( Object.prototype.hasOwnProperty.call( this.parsedMessages, key ) ) {
		return this.parsedMessages[ key ];
	}
	// Fallback to regular messages, html escaping applied.
	return this.getMessage( key ).replace( /['"<>&]/g, function ( char ) {
		return {
			'\'': '&#039;',
			'"': '&quot;',
			'<': '&lt;',
			'>': '&gt;',
			'&': '&amp;'
		}[ char ];
	} );
};

/**
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.getLanguageCodes = function () {
	return Object.keys( $.uls.data.getAutonyms() );
};

/**
 * @method
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.getLanguageName = $.uls.data.getAutonym;

/**
 * @method
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.getLanguageAutonym = $.uls.data.getAutonym;

/**
 * @method
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.getLanguageDirection = $.uls.data.getDir;

/**
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.getUserLanguages = function () {
	return this.userLanguages;
};

/**
 * @inheritdoc
 */
ve.init.sa.Platform.prototype.initialize = function () {
	var messagePaths = this.getMessagePaths(),
		locale = $.i18n().locale,
		languages = [ locale, 'en' ], // Always use 'en' as the final fallback
		languagesCovered = {},
		promises = [],
		fallbacks = $.i18n.fallbacks[ locale ];

	if ( !VisualEditorSupportCheck() ) {
		return ve.createDeferred().reject().promise();
	}

	if ( !fallbacks ) {
		// Try to find something that has fallbacks (which means it's a language we know about)
		// by stripping things from the end. But collect all the intermediate ones in case we
		// go past languages that don't have fallbacks but do exist.
		var localeParts = locale.split( '-' );
		localeParts.pop();
		while ( localeParts.length && !fallbacks ) {
			var partialLocale = localeParts.join( '-' );
			languages.push( partialLocale );
			fallbacks = $.i18n.fallbacks[ partialLocale ];
			localeParts.pop();
		}
	}

	if ( fallbacks ) {
		languages = languages.concat( fallbacks );
	}

	this.userLanguages = languages;

	for ( var i = 0, iLen = languages.length; i < iLen; i++ ) {
		if ( languagesCovered[ languages[ i ] ] ) {
			continue;
		}
		languagesCovered[ languages[ i ] ] = true;

		// Lower-case the language code for the filename. jQuery.i18n does not case-fold
		// language codes, so we should not case-fold the second argument in #load.
		var filename = languages[ i ].toLowerCase() + '.json';

		for ( var j = 0, jLen = messagePaths.length; j < jLen; j++ ) {
			var deferred = ve.createDeferred();
			$.i18n().load( messagePaths[ j ] + filename, languages[ i ] )
				.always( deferred.resolve );
			promises.push( deferred.promise() );
		}
	}
	return ve.promiseAll( promises );
};
