/*!
 * VisualEditor ContentEditable ContentEditableNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * A ContentEditableNode maintains its own contentEditable property
 *
 * @class
 * @abstract
 *
 * @constructor
 */
ve.ce.ContentEditableNode = function VeCeContentEditableNode() {
	this.ceSurface = null;
	this.setContentEditable( true );
	this.setReadOnly( false );

	this.connect( this, {
		setup: 'onContentEditableSetup',
		teardown: 'onContentEditableTeardown'
	} );
};

/* Inheritance */

OO.initClass( ve.ce.ContentEditableNode );
// Assumes ve.ce.Node as a base class

/* Methods */

/**
 * Handle setup events on the node
 */
ve.ce.ContentEditableNode.prototype.onContentEditableSetup = function () {
	// Exit if already setup or not attached
	if ( this.ceSurface || !this.root ) {
		return;
	}
	this.ceSurface = this.root.getSurface().getSurface();

	this.ceSurface.connect( this, { readOnly: 'onSurfaceReadOnly' } );
	// Set initial state
	this.setReadOnly( this.ceSurface.isReadOnly() );
};

/**
 * Handle teardown events on the node
 */
ve.ce.ContentEditableNode.prototype.onContentEditableTeardown = function () {
	// Exit if not setup
	if ( !this.ceSurface ) {
		return;
	}
	this.ceSurface.disconnect( this, { readOnly: 'onSurfaceReadOnly' } );
	this.ceSurface = null;
};

/**
 * Handle readOnly events from the surface
 *
 * @param {boolean} readOnly Surface is read-only
 */
ve.ce.ContentEditableNode.prototype.onSurfaceReadOnly = function ( readOnly ) {
	this.setReadOnly( readOnly );
};

/**
 * Called when the surface read-only state changes
 *
 * @param {boolean} readOnly Surface is read-only
 */
ve.ce.ContentEditableNode.prototype.setReadOnly = function ( readOnly ) {
	this.$element.prop( 'spellcheck', !readOnly );
};

/**
 * Enable or disable editing on this node
 *
 * @param {boolean} enabled Whether to enable editing
 */
ve.ce.ContentEditableNode.prototype.setContentEditable = function ( enabled ) {
	this.$element.prop( 'contentEditable', ( !!enabled ).toString() );
};

/**
 * Check if the node is currently editable
 *
 * @return {boolean} Node is currently editable
 */
ve.ce.ContentEditableNode.prototype.isContentEditable = function () {
	return this.$element.prop( 'contentEditable' ) === 'true';
};
