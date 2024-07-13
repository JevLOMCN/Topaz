/**
 * This plugin provides a way to build a wiki-text editing user interface around a textarea.
 *
 * @example To initialize without any modules,
 * overqualified `div#edittoolbar` to avoid MediaWiki's heading to id automatism:
 *     $( 'div#edittoolbar' ).wikiEditor();
 *
 * @example To initialize with one or more modules, or to add modules after it's already been initialized:
 *     $( 'textarea#wpTextbox1' ).wikiEditor( 'addModule', 'toolbar', { ... config ... } );
 *
 */
var hasOwn = Object.prototype.hasOwnProperty,

	/**
	 * Array of language codes.
	 */
	fallbackChain = ( function () {
		// eslint-disable-next-line no-jquery/no-class-state
		var isRTL = $( document.body ).hasClass( 'rtl' ),
			chain = mw.language.getFallbackLanguageChain();

		// Do not fallback to 'en'
		if ( chain.length >= 2 && !/^en-/.test( chain[ chain.length - 2 ] ) ) {
			chain.pop();
		}
		if ( isRTL ) {
			chain.push( 'default-rtl' );
		}
		chain.push( 'default' );
		return chain;
	}() );

/**
 * Helper function to mark the automatic message functionality in
 * the autoMsg and autoSafeMsg functions as deprecated.
 *
 * @param {string} property
 * @param {string} key
 */
function deprecateAutoMsg( property, key ) {
	var searchParam = mw.config.get( 'wgSearchType' ) === 'CirrusSearch' ?
		'insource:/' + property + 'Msg: \'' + key + '\'/' :
		property + 'Msg: ' + key;
	var searchUri = mw.config.get( 'wgServer' ) +
		mw.util.getUrl(
			'Special:Search',
			{ search: searchParam, ns2: 1, ns8: 1 }
		);
	if ( searchUri.slice( 0, 2 ) === '//' ) {
		searchUri = location.protocol + searchUri;
	}

	var messageMethod;
	if ( property === 'html' || property === 'text' || property === 'title' ) {
		messageMethod = 'mw.message( ' + JSON.stringify( key ) + ' ).parse()';
	} else {
		messageMethod = 'mw.msg( ' + JSON.stringify( key ) + ' )';
	}
	var deprecationMsg = mw.log.makeDeprecated(
		'wikiEditor_autoMsg',
		'WikiEditor: Use `' + property + ': ' + messageMethod + '` instead of `' + property + 'Msg: ' + JSON.stringify( key ) + '`.\nSearch: ' + searchUri
	);
	deprecationMsg();
}

/**
 * Global static object for wikiEditor that provides generally useful functionality to all modules and contexts.
 */
$.wikiEditor = {
	/**
	 * For each module that is loaded, static code shared by all instances is loaded into this object organized by
	 * module name. The existence of a module in this object only indicates the module is available. To check if a
	 * module is in use by a specific context check the context.modules object.
	 */
	modules: {
		toolbar: require( './jquery.wikiEditor.toolbar.js' ),
		dialogs: require( './jquery.wikiEditor.dialogs.js' )
	},

	/**
	 * A context can be extended, such as adding iframe support, on a per-wikiEditor instance basis.
	 */
	extensions: {},

	/**
	 * In some cases like with the iframe's HTML file, it's convenient to have a lookup table of all instances of the
	 * WikiEditor. Each context contains an instance field which contains a key that corresponds to a reference to the
	 * textarea which the WikiEditor was build around. This way, by passing a simple integer you can provide a way back
	 * to a specific context.
	 */
	instances: [],

	/**
	 * Path to images - this is a bit messy, and it would need to change if this code (and images) gets moved into the
	 * core - or anywhere for that matter...
	 */
	imgPath: mw.config.get( 'wgExtensionAssetsPath' ) + '/WikiEditor/modules/images/',

	/**
	 * Checks if a module has a specific requirement
	 *
	 * @param {Object} module Module object
	 * @param {string} requirement String identifying requirement
	 * @return {boolean}
	 */
	isRequired: function ( module, requirement ) {
		if ( typeof module.req !== 'undefined' ) {
			for ( var req in module.req ) {
				if ( module.req[ req ] === requirement ) {
					return true;
				}
			}
		}
		return false;
	},

	/**
	 * Provides a way to extract messages from objects. Wraps a mw.message( ... ).text() call.
	 *
	 * FIXME: This is a security nightmare. Only use is for the help toolbar panel. Inline the
	 *        special need instead?
	 * FIXME: Also, this is ludicrously complex. Just use mw.message().text() directly.
	 *
	 * @deprecated Since v0.5.4. Use mw.message() directly instead of <key>Msg
	 *
	 * @param {Object} object Object to extract messages from
	 * @param {string} property String of name of property which contains the message. This should be the base name of the
	 * property, which means that in the case of the object { this: 'that', fooMsg: 'bar' }, passing property as 'this'
	 * would return the raw text 'that', while passing property as 'foo' would return the internationalized message
	 * with the key 'bar'.
	 * @return {string}
	 */
	autoMsg: function ( object, property ) {
		// Accept array of possible properties, of which the first one found will be used
		if ( typeof property === 'object' ) {
			for ( var i in property ) {
				if ( property[ i ] in object || property[ i ] + 'Msg' in object ) {
					property = property[ i ];
					break;
				}
			}
		}
		if ( property in object ) {
			return object[ property ];
		} else if ( property + 'Msg' in object ) {
			var p = object[ property + 'Msg' ];
			if ( Array.isArray( p ) && p.length >= 2 ) {
				deprecateAutoMsg( property, p[ 0 ] );
				return mw.message.apply( mw.message, p ).text();
			} else {
				deprecateAutoMsg( property, p );
				// eslint-disable-next-line mediawiki/msg-doc
				return mw.message( p ).text();
			}
		} else {
			return '';
		}
	},

	/**
	 * Provides a way to extract messages from objects. Wraps a mw.message( ... ).escaped() call.
	 *
	 * FIXME: This is ludicrously complex. Just use mw.message().escaped() directly.
	 *
	 * @deprecated Since v0.5.4. Use mw.message() directly instead of <key>Msg
	 *
	 * @param {Object} object Object to extract messages from
	 * @param {string} property String of name of property which contains the message. This should be the base name of the
	 * property, which means that in the case of the object { this: 'that', fooMsg: 'bar' }, passing property as 'this'
	 * would return the raw text 'that', while passing property as 'foo' would return the internationalized message
	 * with the key 'bar'. This is then escaped.
	 * @return {string}
	 */
	autoSafeMsg: function ( object, property ) {
		// Accept array of possible properties, of which the first one found will be used
		if ( typeof property === 'object' ) {
			for ( var i in property ) {
				if ( property[ i ] in object || property[ i ] + 'Msg' in object ) {
					property = property[ i ];
					break;
				}
			}
		}
		if ( property in object ) {
			return object[ property ];
		} else if ( property + 'Msg' in object ) {
			var p = object[ property + 'Msg' ];
			if ( Array.isArray( p ) && p.length >= 2 ) {
				deprecateAutoMsg( property, p[ 0 ] );
				return mw.message.apply( mw.message, p ).escaped();
			} else {
				deprecateAutoMsg( property, p );
				// eslint-disable-next-line mediawiki/msg-doc
				return mw.message( p ).escaped();
			}
		} else {
			return '';
		}
	},

	/**
	 * Provides a way to extract a property of an object in a certain language, falling back on the property keyed as
	 * 'default' or 'default-rtl'. If such key doesn't exist, the object itself is considered the actual value, which
	 * should ideally be the case so that you may use a string or object of any number of strings keyed by language
	 * with a default.
	 *
	 * @param {Object} object Object to extract property from
	 * @return {Object}
	 */
	autoLang: function ( object ) {
		for ( var i = 0; i < fallbackChain.length; i++ ) {
			var key = fallbackChain[ i ];
			if ( hasOwn.call( object, key ) ) {
				return object[ key ];
			}
		}
		return object;
	},

	/**
	 * Provides a way to extract the path of an icon in a certain language, automatically appending a version number for
	 * caching purposes and prepending an image path when icon paths are relative.
	 *
	 * @param {Object} icon Icon object from e.g. toolbar config
	 * @param {string} path Default icon path, defaults to $.wikiEditor.imgPath
	 * @return {Object}
	 */
	autoIcon: function ( icon, path ) {
		path = path || $.wikiEditor.imgPath;

		for ( var i = 0; i < fallbackChain.length; i++ ) {
			var key = fallbackChain[ i ];
			if ( icon && hasOwn.call( icon, key ) ) {
				var src = icon[ key ];

				// Return a data URL immediately
				if ( src.slice( 0, 5 ) === 'data:' ) {
					return src;
				}

				// Prepend path if src is not absolute
				if ( src.slice( 0, 7 ) !== 'http://' && src.slice( 0, 8 ) !== 'https://' && src[ 0 ] !== '/' ) {
					src = path + src;
				}
				return src;
			}
		}
		return icon;
	}
};

/**
 * jQuery plugin that provides a way to initialize a wikiEditor instance on a textarea.
 *
 * @return {jQuery}
 */
$.fn.wikiEditor = function () {
	/* Initialization */

	// The wikiEditor context is stored in the element's data, so when this function gets called again we can pick up right
	// where we left off
	var context = $( this ).data( 'wikiEditor-context' );
	// On first call, we need to set things up, but on all following calls we can skip right to the API handling
	if ( !context ) {

		// Star filling the context with useful data - any jQuery selections, as usual should be named with a preceding $
		context = {
			// Reference to the textarea element which the wikiEditor is being built around
			$textarea: $( this ),
			// Reference to the focused element before the dialog opens, so it can be restored once it closes
			$focusedElem: null,
			// Container for any number of mutually exclusive views that are accessible by tabs
			views: {},
			// Container for any number of module-specific data - only including data for modules in use on this context
			modules: {},
			// General place to shove bits of data into
			data: {},
			// Unique numeric ID of this instance used both for looking up and differentiating instances of wikiEditor
			instance: $.wikiEditor.instances.push( $( this ) ) - 1,
			// Saved selection state for old IE (<=10)
			savedSelection: null,
			// List of extensions active on this context
			extensions: []
		};

		/**
		 * Externally Accessible API
		 *
		 * These are available using calls to $( selection ).wikiEditor( call, data ) where selection is a jQuery selection
		 * of the textarea that the wikiEditor instance was built around.
		 */

		context.api = {
			/*!
			 * Activates a module on a specific context with optional configuration data.
			 *
			 * @param data Either a string of the name of a module to add without any additional configuration parameters,
			 * or an object with members keyed with module names and valued with configuration objects.
			 */
			addModule: function ( ctx, data ) {
				var modules = {};
				if ( typeof data === 'string' ) {
					modules[ data ] = {};
				} else if ( typeof data === 'object' ) {
					modules = data;
				}
				for ( var module in modules ) {
					// Check for the existence of an available module with a matching name and a create function
					if ( typeof module === 'string' && typeof $.wikiEditor.modules[ module ] !== 'undefined' ) {
						// Extend the context's core API with this module's own API calls
						if ( 'api' in $.wikiEditor.modules[ module ] ) {
							for ( var call in $.wikiEditor.modules[ module ].api ) {
								// Modules may not overwrite existing API functions - first come, first serve
								if ( !( call in ctx.api ) ) {
									ctx.api[ call ] = $.wikiEditor.modules[ module ].api[ call ];
								}
							}
						}
						// Activate the module on this context
						if ( 'fn' in $.wikiEditor.modules[ module ] &&
							'create' in $.wikiEditor.modules[ module ].fn &&
							typeof ctx.modules[ module ] === 'undefined'
						) {
							// Add a place for the module to put it's own stuff
							ctx.modules[ module ] = {};
							// Tell the module to create itself on the context
							$.wikiEditor.modules[ module ].fn.create( ctx, modules[ module ] );
						}
					}
				}
			}
		};

		/**
		 * Event Handlers
		 *
		 * These act as filters returning false if the event should be ignored or returning true if it should be passed
		 * on to all modules. This is also where we can attach some extra information to the events.
		 */

		context.evt = {
			/* Empty until extensions add some; see jquery.wikiEditor.iframe.js for examples. */
		};

		/* Internal Functions */

		context.fn = {
			/**
			 * Executes core event filters as well as event handlers provided by modules.
			 *
			 * @param {string} name
			 * @param {Object} event
			 * @return {boolean}
			 */
			trigger: function ( name, event ) {
				// Event is an optional argument, but from here on out, at least the type field should be dependable
				event = event || { type: 'custom' };
				// Ensure there's a place for extra information to live
				event.data = event.data || {};

				// Allow filtering to occur
				if ( name in context.evt ) {
					if ( !context.evt[ name ]( event ) ) {
						return false;
					}
				}
				var returnFromModules = true;
				// Pass the event around to all modules activated on this context

				for ( var module in context.modules ) {
					if (
						module in $.wikiEditor.modules &&
						'evt' in $.wikiEditor.modules[ module ] &&
						name in $.wikiEditor.modules[ module ].evt
					) {
						var ret = $.wikiEditor.modules[ module ].evt[ name ]( context, event );
						if ( ret !== null ) {
							// if 1 returns false, the end result is false
							returnFromModules = returnFromModules && ret;
						}
					}
				}
				return returnFromModules;
			},

			/**
			 * Adds a button to the UI
			 *
			 * @param {Object} options
			 * @return {jQuery}
			 */
			addButton: function ( options ) {
				// Ensure that buttons and tabs are visible
				context.$controls.show();
				context.$buttons.show();
				return $( '<button>' )
					.text( $.wikiEditor.autoMsg( options, 'caption' ) )
					.on( 'click', options.action )
					.appendTo( context.$buttons );
			},

			/**
			 * Adds a view to the UI, which is accessed using a set of tabs. Views are mutually exclusive and by default a
			 * wikitext view will be present. Only when more than one view exists will the tabs will be visible.
			 *
			 * @param {Object} options
			 * @return {jQuery}
			 */
			addView: function ( options ) {
				// Adds a tab
				function addTab( opts ) {
					// Ensure that buttons and tabs are visible
					context.$controls.show();
					context.$tabs.show();
					// Return the newly appended tab
					return $( '<div>' )
						.attr( 'rel', 'wikiEditor-ui-view-' + opts.name )
						.addClass( context.view === opts.name ? 'current' : null )
						.append( $( '<a>' )
							.attr( 'tabindex', 0 )
							.on( 'mousedown', function () {
								// No dragging!
								return false;
							} )
							.on( 'click keydown', function ( event ) {
								if (
									event.type === 'click' ||
									event.type === 'keydown' && event.key === 'Enter'
								) {
									context.$ui.find( '.wikiEditor-ui-view' ).hide();
									context.$ui.find( '.' + $( this ).parent().attr( 'rel' ) ).show();
									context.$tabs.find( 'div' ).removeClass( 'current' );
									$( this ).parent().addClass( 'current' );
									$( this ).trigger( 'blur' );
									if ( 'init' in opts && typeof opts.init === 'function' ) {
										opts.init( context );
									}
									event.preventDefault();
									return false;
								}
							} )
							.text( $.wikiEditor.autoMsg( opts, 'title' ) )
						)
						.appendTo( context.$tabs );
				}
				// Automatically add the previously not-needed wikitext tab
				if ( !context.$tabs.children().length ) {
					addTab( { name: 'wikitext', title: mw.message( 'wikieditor-wikitext-tab' ).parse() } );
				}
				// Add the tab for the view we were actually asked to add
				addTab( options );
				// Return newly appended view
				// eslint-disable-next-line mediawiki/class-doc
				return $( '<div>' )
					.addClass( 'wikiEditor-ui-view wikiEditor-ui-view-' + options.name )
					.hide()
					.appendTo( context.$ui );
			},

			/**
			 * Save text selection
			 */
			saveSelection: function () {
				context.$focusedElem = $( ':focus' );
				context.$textarea.trigger( 'focus' );
				context.savedSelection = context.$textarea.textSelection( 'getCaretPosition', { startAndEnd: true } );
			},

			/**
			 * Restore text selection
			 */
			restoreSelection: function () {
				if ( context.savedSelection ) {
					context.$textarea.trigger( 'focus' );
					context.$textarea.textSelection( 'setSelection', { start: context.savedSelection[ 0 ], end: context.savedSelection[ 1 ] } );
					context.savedSelection = null;
				}
				if ( context.$focusedElem ) {
					context.$focusedElem.trigger( 'focus' );
				}
			}
		};

		/**
		 * Base UI Construction
		 *
		 * The UI is built from several containers, the outer-most being a div classed as "wikiEditor-ui". These containers
		 * provide a certain amount of "free" layout, but in some situations procedural layout is needed, which is performed
		 * as a response to the "resize" event.
		 */

		/* Preserving cursor and focus state, which will get lost due to wrapAll */
		var hasFocus = context.$textarea.is( ':focus' );
		var cursorPos = context.$textarea.textSelection( 'getCaretPosition', { startAndEnd: true } );
		// Encapsulate the textarea with some containers for layout
		context.$textarea
			.wrapAll( $( '<div>' ).addClass( 'wikiEditor-ui' ) )
			.wrapAll( $( '<div>' ).addClass( 'wikiEditor-ui-view wikiEditor-ui-view-wikitext' ) )
			.wrapAll( $( '<div>' ).addClass( 'wikiEditor-ui-left' ) )
			.wrapAll( $( '<div>' ).addClass( 'wikiEditor-ui-bottom' ) )
			.wrapAll( $( '<div>' ).addClass( 'wikiEditor-ui-text' ) );
		// Restore scroll position after this wrapAll (tracked by mediawiki.action.edit)
		context.$textarea.prop( 'scrollTop', $( '#wpScrolltop' ).val() );
		// Restore focus and cursor if needed
		if ( hasFocus ) {
			context.$textarea.trigger( 'focus' );
			context.$textarea.textSelection( 'setSelection', { start: cursorPos[ 0 ], end: cursorPos[ 1 ] } );
		}

		// Get references to some of the newly created containers
		context.$ui = context.$textarea.parent().parent().parent().parent().parent();
		context.$wikitext = context.$textarea.parent().parent().parent().parent();
		// Add in tab and button containers
		context.$wikitext.before(
			$( '<div>' ).addClass( 'wikiEditor-ui-controls' ).append(
				$( '<div>' ).addClass( 'wikiEditor-ui-tabs' ).hide(),
				$( '<div>' ).addClass( 'wikiEditor-ui-buttons' )
			),
			$( '<div>' ).addClass( 'wikiEditor-ui-clear' )
		);
		// Get references to some of the newly created containers
		context.$controls = context.$ui.find( '.wikiEditor-ui-buttons' ).hide();
		context.$buttons = context.$ui.find( '.wikiEditor-ui-buttons' );
		context.$tabs = context.$ui.find( '.wikiEditor-ui-tabs' );
		// Clear all floating after the UI
		context.$ui.after( $( '<div>' ).addClass( 'wikiEditor-ui-clear' ) );
		// Attach a right container
		context.$wikitext.append(
			$( '<div>' ).addClass( 'wikiEditor-ui-right' ),
			$( '<div>' ).addClass( 'wikiEditor-ui-clear' )
		);
		// Attach a top container to the left pane
		context.$wikitext.find( '.wikiEditor-ui-left' ).prepend( $( '<div>' ).addClass( 'wikiEditor-ui-top' ) );
		// Setup the initial view
		context.view = 'wikitext';
		// Trigger the "resize" event anytime the window is resized
		$( window ).on( 'resize', function ( event ) {
			context.fn.trigger( 'resize', event );
		} );
	}

	/* API Execution */

	// Since javascript gives arguments as an object, we need to convert them so they can be used more easily
	var args = $.makeArray( arguments );

	// Dynamically setup core extensions for modules that are required
	if ( args[ 0 ] === 'addModule' && typeof args[ 1 ] !== 'undefined' ) {
		var modulesArg = args[ 1 ];
		if ( typeof modulesArg !== 'object' ) {
			modulesArg = {};
			modulesArg[ args[ 1 ] ] = '';
		}
		for ( var m in modulesArg ) {
			if ( m in $.wikiEditor.modules ) {
				// Activate all required core extensions on context
				for ( var extension in $.wikiEditor.extensions ) {
					if (
						$.wikiEditor.isRequired( $.wikiEditor.modules[ m ], extension ) &&
						context.extensions.indexOf( extension ) === -1
					) {
						context.extensions[ context.extensions.length ] = extension;
						$.wikiEditor.extensions[ extension ]( context );
					}
				}
				break;
			}
		}
	}

	// There would need to be some arguments if the API is being called
	if ( args.length > 0 ) {
		// Handle API calls
		var callArg = args.shift();
		if ( callArg in context.api ) {
			context.api[ callArg ]( context, args[ 0 ] || {} );
		}
	}

	// Store the context for next time, and support chaining
	return $( this ).data( 'wikiEditor-context', context );

};
