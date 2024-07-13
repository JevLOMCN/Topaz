/*!
 * VisualEditor ContentEditable ActiveNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Active nodes are editable sections that are nested inside
 * uneditable sections.
 *
 * @class
 * @mixins ve.ce.ContentEditableNode
 * @abstract
 *
 * @constructor
 */
ve.ce.ActiveNode = function VeCeActiveNode() {
	// Mixin constructor
	ve.ce.ContentEditableNode.call( this );

	// Properties
	this.activeNodeSurface = null;
	this.isActiveNodeSetup = false;

	// Events
	this.connect( this, {
		setup: 'onActiveNodeSetup',
		teardown: 'onActiveNodeTeardown'
	} );

	// DOM changes
	this.$element.addClass( 've-ce-activeNode' );
};

/* Inheritance */

OO.mixinClass( ve.ce.ActiveNode, ve.ce.ContentEditableNode );

/* Methods */

/**
 * Handle node setup
 */
ve.ce.ActiveNode.prototype.onActiveNodeSetup = function () {
	// Exit if already setup or not attached
	if ( this.isActiveNodeSetup || !this.root ) {
		return;
	}

	this.activeNodeSurface = this.getRoot().getSurface();

	// Events
	this.activeNodeSurface.getModel().connect( this, { select: 'onActiveNodeSurfaceModelSelect' } );

	this.isActiveNodeSetup = true;
};

/**
 * Handle node teardown
 */
ve.ce.ActiveNode.prototype.onActiveNodeTeardown = function () {
	if ( !this.isActiveNodeSetup ) {
		return;
	}

	var surface = this.activeNodeSurface;

	// Events
	surface.getModel().disconnect( this );

	if ( surface.getActiveNode() === this ) {
		surface.setActiveNode( null );
	}

	this.isActiveNodeSetup = false;
};

/**
 * Handle select events from the surface model.
 *
 * @param {ve.dm.Selection} selection
 */
ve.ce.ActiveNode.prototype.onActiveNodeSurfaceModelSelect = function ( selection ) {
	var coveringRange = selection.getCoveringRange(),
		surface = this.activeNodeSurface,
		activeNode = this;

	if ( coveringRange && this.model.getRange().containsRange( new ve.Range( coveringRange.from ) ) ) {
		// Only set this as the active node if active node is empty, or not a
		// descendant of this node.
		if (
			!surface.getActiveNode() ||
			!surface.getActiveNode().traverseUpstream( function ( node ) {
				return node !== activeNode;
			} )
		) {
			surface.setActiveNode( this );
		}
		this.$element.addClass( 've-ce-activeNode-active' );
	} else {
		if ( surface.getActiveNode() === this ) {
			surface.setActiveNode( null );
		}
		if ( !selection.isNull() ) {
			this.$element.removeClass( 've-ce-activeNode-active' );
		}
	}
};
