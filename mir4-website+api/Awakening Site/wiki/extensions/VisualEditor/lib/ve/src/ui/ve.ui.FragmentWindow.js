/*!
 * VisualEditor user interface FragmentWindow class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Mixin for window for working with fragments of content.
 *
 * @class
 * @abstract
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.FragmentWindow = function VeUiFragmentWindow() {
	// Properties
	this.fragment = null;
};

/* Inheritance */

OO.initClass( ve.ui.FragmentWindow );

/* Methods */

/**
 * Get the surface fragment the window is for.
 *
 * @return {ve.dm.SurfaceFragment|null} Surface fragment the window is for, null if the
 *   window is closed
 */
ve.ui.FragmentWindow.prototype.getFragment = function () {
	return this.fragment;
};

/**
 * @inheritdoc OO.ui.Dialog
 */
ve.ui.FragmentWindow.prototype.getActionWidgetConfig = function ( config ) {
	if ( config.action === 'done' && OO.ui.isMobile() ) {
		// Use label-less check icon on mobile (T228230)
		config = ve.extendObject( {
			icon: 'check',
			invisibleLabel: true
		}, config );
	}
	return config;
};

/**
 * @inheritdoc OO.ui.Window
 * @throws {Error} If fragment was not provided through data parameter
 */
ve.ui.FragmentWindow.prototype.getSetupProcess = function ( data, process ) {
	data = data || {};
	return process.first( function () {
		if ( !( data.fragment instanceof ve.dm.SurfaceFragment ) ) {
			throw new Error( 'Cannot open dialog: opening data must contain a fragment' );
		}
		this.fragment = data.fragment;
		this.initialFragment = data.fragment;
		this.selectFragmentOnClose = data.selectFragmentOnClose !== false;
		// Prefer this.initialFragment.getSelection() to this.previousSelection
		this.previousSelection = this.fragment.getSelection();
	}, this ).next( function () {
		// Don't allow windows to be opened for insertion in readonly mode
		if ( !this.isEditing() && this.isReadOnly() ) {
			return ve.createDeferred().reject().promise();
		}
		this.actions.setMode( this.getMode() );
	}, this );
};

/**
 * @inheritdoc OO.ui.Window
 */
ve.ui.FragmentWindow.prototype.getTeardownProcess = function ( data, process ) {
	ve.track( 'activity.' + this.constructor.static.name, { action: 'dialog-' + ( data && data.action || 'abort' ) } );
	return process.next( function () {
		this.fragment = null;
		this.initialFragment = null;
		this.previousSelection = null;
	}, this );
};

/**
 * Check if the fragment's surface is readOnly
 *
 * @return {boolean} Fragment's surface is readOnly
 */
ve.ui.FragmentWindow.prototype.isReadOnly = function () {
	var fragment = this.getFragment(),
		surface = fragment && fragment.getSurface();

	return surface && surface.isReadOnly();
};

/**
 * Get a symbolic mode name.
 *
 * By default will return 'edit' if #isEditing is true, and 'insert' otherwise.
 *
 * If the surface model is in read-only mode, will return 'readonly'.
 *
 * @return {string} Symbolic mode name
 */
ve.ui.FragmentWindow.prototype.getMode = function () {
	if ( this.isReadOnly() ) {
		return 'readonly';
	}
	if ( this.getFragment() ) {
		return this.isEditing() ? 'edit' : 'insert';
	}
	return '';
};

/**
 * Check if the current fragment is editable by this window.
 *
 * @localdoc Returns true if the fragment being edited selects at least one model,
 *
 * @return {boolean} Fragment is editable by this window
 */
ve.ui.FragmentWindow.prototype.isEditing = function () {
	return !!this.fragment.getSelectedModels().length;
};
