/*!
 * VisualEditor UserInterface BlockquoteAction class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Blockquote action.
 *
 * @class
 * @extends ve.ui.Action
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 * @param {string} [source]
 */
ve.ui.BlockquoteAction = function VeUiBlockquoteAction() {
	// Parent constructor
	ve.ui.BlockquoteAction.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.BlockquoteAction, ve.ui.Action );

/* Static Properties */

ve.ui.BlockquoteAction.static.name = 'blockquote';

ve.ui.BlockquoteAction.static.methods = [ 'wrap', 'unwrap', 'toggle' ];

/* Methods */

/**
 * Check if the current selection is wrapped in a blockquote.
 *
 * @return {boolean} Current selection is wrapped in a blockquote
 */
ve.ui.BlockquoteAction.prototype.isWrapped = function () {
	var fragment = this.surface.getModel().getFragment();
	return fragment.hasMatchingAncestor( 'blockquote' );
};

/**
 * Toggle a blockquote around content.
 *
 * @return {boolean} Action was executed
 */
ve.ui.BlockquoteAction.prototype.toggle = function () {
	return this[ this.isWrapped() ? 'unwrap' : 'wrap' ]();
};

/**
 * Add a blockquote around content (only if it has no blockquote already).
 *
 * @return {boolean} Action was executed
 */
ve.ui.BlockquoteAction.prototype.wrap = function () {
	var surfaceModel = this.surface.getModel(),
		selection = surfaceModel.getSelection();

	if ( !( selection instanceof ve.dm.LinearSelection ) ) {
		return false;
	}

	var fragment = surfaceModel.getFragment( null, true );
	// Trim the selection range to the range of leaf nodes in the selection,
	// to avoid covering whole nodes where only start/end tag was selected.
	// For example:
	//     <p>asdf</p><p>qwer</p>   -->   <p>asdf</p><p>qwer</p>
	//        ^^^^^^^^^^^                    ^^^^
	var leaves = fragment.getSelectedLeafNodes();
	var leavesRange = new ve.Range(
		leaves[ 0 ].getRange().start,
		leaves[ leaves.length - 1 ].getRange().end
	);
	fragment = surfaceModel.getLinearFragment( leavesRange, true );

	// Expand to cover entire nodes
	fragment = fragment.expandLinearSelection( 'siblings' );

	// If the nodes can't be wrapped (e.g. they are list items), wrap the parent
	while (
		fragment.getCoveredNodes().some( function ( nodeInfo ) {
			return !nodeInfo.node.isAllowedParentNodeType( 'blockquote' ) || nodeInfo.node.isContent();
		} )
	) {
		fragment = fragment.expandLinearSelection( 'parent' );
	}

	// Wrap everything in a blockquote
	fragment.wrapAllNodes( { type: 'blockquote' } );

	return true;
};

/**
 * Remove blockquote around content (if present).
 *
 * @return {boolean} Action was executed
 */
ve.ui.BlockquoteAction.prototype.unwrap = function () {
	var surfaceModel = this.surface.getModel(),
		selection = surfaceModel.getSelection();

	if ( !( selection instanceof ve.dm.LinearSelection ) ) {
		return false;
	}

	if ( !this.isWrapped() ) {
		return false;
	}

	var fragment = surfaceModel.getFragment( null, true );
	// Trim the selection range to the range of leaf nodes in the selection,
	// to avoid covering whole nodes where only start/end tag was selected.
	// For example:
	//     <bq><p>asdf</p></bq><p>qwer</p>   -->   <bq><p>asdf</p></bq><p>qwer</p>
	//            ^^^^^^^^^^^^^^^^                        ^^^^
	var leaves = fragment.getSelectedLeafNodes();
	var leavesRange = new ve.Range(
		leaves[ 0 ].getRange().start,
		leaves[ leaves.length - 1 ].getRange().end
	);
	fragment = surfaceModel.getLinearFragment( leavesRange, true );

	fragment
		// Expand to cover entire blockquote
		.expandLinearSelection( 'closest', ve.dm.BlockquoteNode )
		// Unwrap it
		.unwrapNodes( 0, 1 );

	return true;
};

/* Registration */

ve.ui.actionFactory.register( ve.ui.BlockquoteAction );
