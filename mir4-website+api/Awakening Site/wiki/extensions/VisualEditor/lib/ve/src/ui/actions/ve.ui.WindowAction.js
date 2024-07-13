/*!
 * VisualEditor UserInterface WindowAction class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Window action.
 *
 * @class
 * @extends ve.ui.Action
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 * @param {string} [source]
 */
ve.ui.WindowAction = function VeUiWindowAction() {
	// Parent constructor
	ve.ui.WindowAction.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.WindowAction, ve.ui.Action );

/* Static Properties */

ve.ui.WindowAction.static.name = 'window';

ve.ui.WindowAction.static.methods = [ 'open', 'close', 'toggle' ];

/* Methods */

/**
 * Open a window.
 *
 * @param {string} name Symbolic name of window to open
 * @param {Object} [data] Window opening data
 * @param {string} [action] Action to execute after opening, or immediately if the window is already open
 * @return {boolean|jQuery.Promise} Action was executed; if a Promise, it'll resolve once the action is finished executing
 */
ve.ui.WindowAction.prototype.open = function ( name, data, action ) {
	data = data || {};
	var windowAction = this,
		windowType = this.getWindowType( name ),
		windowManager = windowType && this.getWindowManager( windowType ),
		currentWindow = windowManager.getCurrentWindow(),
		autoClosePromises = [],
		surface = this.surface,
		surfaceFragment = data.fragment || surface.getModel().getFragment( undefined, true ),
		dir = surface.getView().getSelectionDirectionality(),
		windowClass = ve.ui.windowFactory.lookup( name ),
		isFragmentWindow = !!windowClass.prototype.getFragment,
		mayRequireFragment = isFragmentWindow ||
			// HACK: Pass fragment to toolbar dialogs as well
			windowType === 'toolbar',
		// TODO: Add 'doesHandleSource' method to factory
		sourceMode = surface.getMode() === 'source' && !windowClass.static.handlesSource,
		openDeferred = ve.createDeferred(),
		openPromise = openDeferred.promise();

	ve.track(
		'activity.' + name,
		{ action: 'window-open-from-' + ( this.source || 'command' ) }
	);

	if ( !windowManager ) {
		return false;
	}

	var fragmentPromise;
	var originalFragment;
	if ( !mayRequireFragment ) {
		fragmentPromise = ve.createDeferred().resolve().promise();
	} else if ( sourceMode ) {
		var text = surfaceFragment.getText( true );
		originalFragment = surfaceFragment;

		fragmentPromise = surfaceFragment.convertFromSource( text ).then( function ( selectionDocument ) {
			var tempSurfaceModel = new ve.dm.Surface( selectionDocument ),
				tempFragment = tempSurfaceModel.getLinearFragment(
					// TODO: Select all content using content offset methods
					new ve.Range(
						1,
						Math.max( 1, selectionDocument.getDocumentRange().end - 1 )
					)
				);

			return tempFragment;
		} );
	} else {
		fragmentPromise = ve.createDeferred().resolve( surfaceFragment ).promise();
	}

	data = ve.extendObject( { dir: dir }, data, { surface: surface, $returnFocusTo: null } );

	if ( windowType === 'toolbar' || windowType === 'inspector' ) {
		// Auto-close the current window if it is different to the one we are
		// trying to open.
		// TODO: Make auto-close a window manager setting
		if ( currentWindow && currentWindow.constructor.static.name !== name ) {
			autoClosePromises.push( windowManager.closeWindow( currentWindow ).closed );
		}
	}

	// If we're opening a dialog, close all inspectors first
	if ( windowType === 'dialog' ) {
		var inspectorWindowManager = windowAction.getWindowManager( 'inspector' );
		var currentInspector = inspectorWindowManager.getCurrentWindow();
		if ( currentInspector ) {
			autoClosePromises.push( inspectorWindowManager.closeWindow( currentInspector ).closed );
		}
	}

	fragmentPromise.then( function ( fragment ) {
		ve.extendObject( data, { fragment: fragment } );

		ve.promiseAll( autoClosePromises ).always( function () {
			windowManager.getWindow( name ).then( function ( win ) {
				var instance = windowManager.openWindow( win, data );

				if ( sourceMode ) {
					win.sourceMode = sourceMode;
				}

				if ( !win.constructor.static.activeSurface ) {
					surface.getView().deactivate( false );
				}

				instance.opened.then( function () {
					if ( sourceMode ) {
						// HACK: initialFragment/previousSelection is assumed to be in the visible surface
						win.initialFragment = null;
						win.previousSelection = null;
					}
				} );
				instance.opened.always( function () {
					// This uses .always() so that the action is executed even if the window is already open
					// (in which case opening it again fails). Hopefully we'll never have a situation where
					// it's closed, the opening fails for some reason, and then weird things happen.
					if ( action ) {
						win.executeAction( action );
					}
					openDeferred.resolve( instance );
				} );

				if ( !win.constructor.static.activeSurface ) {
					windowManager.once( 'closing', function () {
						// Collapsed mobile selection: We need to re-activate the surface in case an insertion
						// annotation was generated. We also need to do it during the same event cycle otherwise
						// the device may not open the virtual keyboard, so use the 'closing' event. (T203517)
						if ( OO.ui.isMobile() && surface.getModel().getSelection().isCollapsed() ) {
							surface.getView().activate();
						} else {
							// Otherwise use the `closed` promise to wait until the dialog has performed its actions,
							// such as creating new annotations or moving focus, before re-activating.
							instance.closed.then( function () {
								// Don't activate if mobile and expanded
								if ( !( OO.ui.isMobile() && !surface.getModel().getSelection().isCollapsed() ) ) {
									surface.getView().activate();
								}
							} );
						}
					} );
				}

				instance.closed.then( function ( closedData ) {
					// Sequence-triggered window closed without action, undo
					if ( data.strippedSequence && !( closedData && closedData.action ) ) {
						surface.getModel().undo();
						// Prevent redoing (which would remove the typed text)
						surface.getModel().truncateUndoStack();
						surface.getModel().emit( 'history' );
					}
					if ( sourceMode && fragment && fragment.getSurface().hasBeenModified() ) {
						// Action may be async, so we use auto select to ensure the content is selected
						originalFragment.setAutoSelect( true );
						originalFragment.insertDocument( fragment.getDocument() );
					}
					surface.getView().emit( 'position' );
				} );
			} );
		} );
	} );

	return openPromise;
};

/**
 * Close a window
 *
 * @param {string} name Symbolic name of window to open
 * @param {Object} [data] Window closing data
 * @return {boolean} Action was executed
 */
ve.ui.WindowAction.prototype.close = function ( name, data ) {
	var windowType = this.getWindowType( name ),
		windowManager = windowType && this.getWindowManager( windowType );

	if ( !windowManager ) {
		return false;
	}

	windowManager.closeWindow( name, data );
	return true;
};

/**
 * Toggle a window between open and close
 *
 * @param {string} name Symbolic name of window to open or close
 * @param {Object} [data] Window opening or closing data
 * @return {boolean} Action was executed
 */
ve.ui.WindowAction.prototype.toggle = function ( name, data ) {
	var windowType = this.getWindowType( name ),
		windowManager = windowType && this.getWindowManager( windowType );

	if ( !windowManager ) {
		return false;
	}

	var win = windowManager.getCurrentWindow();
	if ( !win || win.constructor.static.name !== name ) {
		this.open( name, data );
	} else {
		this.close( name, data );
	}
	return true;
};

/**
 * Get the specified window type
 *
 * @param {string} name Window name
 * @return {string|null} Window type: 'inspector', 'toolbar' or 'dialog'
 */
ve.ui.WindowAction.prototype.getWindowType = function ( name ) {
	var windowClass = ve.ui.windowFactory.lookup( name );
	if ( windowClass.prototype instanceof ve.ui.FragmentInspector ) {
		return 'inspector';
	} else if ( windowClass.prototype instanceof ve.ui.ToolbarDialog ) {
		return 'toolbar';
	} else if ( windowClass.prototype instanceof OO.ui.Dialog ) {
		return 'dialog';
	}
	return null;
};

/**
 * Get the window manager for a specified window type
 *
 * @param {Function} windowType Window type: 'inspector', 'toolbar', or 'dialog'
 * @return {ve.ui.WindowManager|null} Window manager
 */
ve.ui.WindowAction.prototype.getWindowManager = function ( windowType ) {
	switch ( windowType ) {
		case 'inspector':
			return this.surface.getContext().getInspectors();
		case 'toolbar':
			return this.surface.getToolbarDialogs();
		case 'dialog':
			return this.surface.getDialogs();
	}
	return null;
};

/* Registration */

ve.ui.actionFactory.register( ve.ui.WindowAction );
