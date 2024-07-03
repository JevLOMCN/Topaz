/**
 * Dialog Module for wikiEditor
 */
var dialogsModule = {

	/**
	 * API accessible functions
	 */
	api: {
		addDialog: function ( context, data ) {
			dialogsModule.fn.create( context, data );
		},
		openDialog: function ( context, module ) {
			if ( module in dialogsModule.modules ) {
				var mod = dialogsModule.modules[ module ];
				var $dialog = $( '#' + mod.id );
				if ( $dialog.length === 0 ) {
					dialogsModule.fn.reallyCreate( context, mod, module );
					$dialog = $( '#' + mod.id );
				}

				// Workaround for bug in jQuery UI: close button in top right retains focus
				$dialog.closest( '.ui-dialog' )
					.find( '.ui-dialog-titlebar-close' )
					.removeClass( 'ui-state-focus' );

				$dialog.dialog( 'open' );
			}
		},
		closeDialog: function ( context, module ) {
			if ( module in dialogsModule.modules ) {
				$( '#' + dialogsModule.modules[ module ].id ).dialog( 'close' );
			}
		}
	},

	/**
	 * Internally used functions
	 */
	fn: {
		/**
		 * Creates a dialog module within a wikiEditor
		 *
		 * @param {Object} context Context object of editor to create module in
		 * @param {Object} config Configuration object to create module from
		 */
		create: function ( context, config ) {
			// Defer building of modules, unless they require immediate creation
			for ( var mod in config ) {
				var module = config[ mod ];
				// Only create the dialog if it isn't filtered and doesn't exist yet
				var filtered = false;
				if ( typeof module.filters !== 'undefined' ) {
					for ( var i = 0; i < module.filters.length; i++ ) {
						if ( $( module.filters[ i ] ).length === 0 ) {
							filtered = true;
							break;
						}
					}
				}
				// If the dialog already exists, but for another textarea, simply remove it
				var $existingDialog = $( '#' + module.id );
				if ( $existingDialog.length > 0 && $existingDialog.data( 'context' ).$textarea !== context.$textarea ) {
					$existingDialog.remove();
				}
				// Re-select from the DOM, we might have removed the dialog just now
				$existingDialog = $( '#' + module.id );
				if ( !filtered && $existingDialog.length === 0 ) {
					dialogsModule.modules[ mod ] = module;
					context.$textarea.trigger( 'wikiEditor-dialogs-setup-' + mod );
					// If this dialog requires immediate creation, create it now
					if ( typeof module.immediateCreate !== 'undefined' && module.immediateCreate ) {
						dialogsModule.fn.reallyCreate( context, module, mod );
					}
				}
			}
		},

		/**
		 * Build the actual dialog. This done on-demand rather than in create()
		 *
		 * @param {Object} context Context object of editor dialog belongs to
		 * @param {Object} module Dialog module object
		 * @param {string} name Dialog name (key in dialogsModule.modules)
		 */
		reallyCreate: function ( context, module, name ) {
			var configuration = module.dialog;
			// Add some stuff to configuration
			configuration.bgiframe = true;
			configuration.autoOpen = false;
			// By default our dialogs are modal, unless explicitly defined in their specific configuration.
			if ( typeof configuration.modal === 'undefined' ) {
				configuration.modal = true;
			}
			configuration.title = $.wikiEditor.autoSafeMsg( module, 'title' );
			// Transform messages in keys
			// Stupid JS won't let us do stuff like
			// foo = { mw.msg( 'bar' ): baz }
			configuration.newButtons = {};
			for ( var msg in configuration.buttons ) {
				// eslint-disable-next-line mediawiki/msg-doc
				configuration.newButtons[ mw.msg( msg ) ] = configuration.buttons[ msg ];
			}
			configuration.buttons = configuration.newButtons;
			var $content;
			if ( module.htmlTemplate ) {
				$content = mw.template.get( 'ext.wikiEditor', module.htmlTemplate ).render();
			} else if ( module.html instanceof $ ) {
				$content = module.html;
			} else {
				$content = $( $.parseHTML( module.html ) );
			}
			// Create the dialog <div>
			var $dialogDiv = $( '<div>' )
				.attr( 'id', module.id )
				.append( $content )
				.data( 'context', context )
				.appendTo( document.body )
				.each( module.init )
				.dialog( configuration );

			$dialogDiv.on( 'dialogclose', function () {
				context.fn.restoreSelection();
			} );

			// Let the outside world know we set up this dialog
			context.$textarea.trigger( 'wikiEditor-dialogs-loaded-' + name );
		}
	},

	// This stuff is just hanging here, perhaps we could come up with a better home for this stuff
	modules: {},

	quickDialog: function ( body, settings ) {
		$( '<div>' )
			.text( body )
			.appendTo( document.body )
			.dialog( $.extend( {
				bgiframe: true,
				modal: true
			}, settings ) )
			.dialog( 'open' );
	}

};

module.exports = dialogsModule;
