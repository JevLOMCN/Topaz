/*!
 * VisualEditor UserInterface IndentationAction class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Indentation action.
 *
 * @class
 * @extends ve.ui.Action
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 * @param {string} [source]
 */
ve.ui.IndentationAction = function VeUiIndentationAction() {
	// Parent constructor
	ve.ui.IndentationAction.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.IndentationAction, ve.ui.Action );

/* Static Properties */

ve.ui.IndentationAction.static.name = 'indentation';

ve.ui.IndentationAction.static.methods = [ 'increase', 'decrease' ];

/* Methods */

/**
 * Indent content.
 *
 * @return {boolean} Indentation increase occurred
 */
ve.ui.IndentationAction.prototype.increase = function () {
	return this.changeIndentation( 1 );
};

/**
 * Unindent content.
 *
 * @return {boolean} Indentation decrease occurred
 */
ve.ui.IndentationAction.prototype.decrease = function () {
	return this.changeIndentation( -1 );
};

/**
 * Change indentation of content.
 *
 * @param {number} indent Indentation change, 1 or -1
 * @return {boolean} Indentation decrease occurred
 */
ve.ui.IndentationAction.prototype.changeIndentation = function ( indent ) {
	var surfaceModel = this.surface.getModel(),
		selection = surfaceModel.getSelection();

	if ( !( selection instanceof ve.dm.LinearSelection ) ) {
		return false;
	}

	var documentModel = surfaceModel.getDocument();
	var groups = documentModel.getCoveredSiblingGroups( selection.getRange() );

	var fragments = [];
	var changed = false;
	// Build fragments from groups (we need their ranges since the nodes will be rebuilt on change)
	groups.forEach( function ( group ) {
		if ( group.grandparent && group.grandparent.getType() === 'list' ) {
			fragments.push( surfaceModel.getLinearFragment( group.parent.getRange(), true ) );
			changed = true;
		} else if ( group.parent && group.parent.getType() === 'list' ) {
			// In a slug, the node will be the listItem.
			fragments.push( surfaceModel.getLinearFragment( group.nodes[ 0 ].getRange(), true ) );
			changed = true;
		}
	} );

	var action = this;
	// Process each fragment (their ranges are automatically adjusted on change)
	fragments.forEach( function ( fragment ) {
		var listItem = documentModel.getBranchNodeFromOffset( fragment.getSelection().getRange().start );
		if ( indent > 0 ) {
			action.indentListItem( listItem );
		} else {
			action.unindentListItem( listItem );
		}
	} );

	return changed;
};

/**
 * Indent a list item.
 *
 * @param {ve.dm.ListItemNode} listItem List item to indent
 * @throws {Error} listItem must be a ve.dm.ListItemNode
 */
ve.ui.IndentationAction.prototype.indentListItem = function ( listItem ) {
	// This check should never fail
	/* istanbul ignore next */
	if ( !( listItem instanceof ve.dm.ListItemNode ) ) {
		throw new Error( 'listItem must be a ve.dm.ListItemNode' );
	}

	/*
	 * Indenting a list item is done as follows:
	 *
	 * 1. Wrap the listItem in a list and a listItem (<li> --> <li><ul><li>)
	 * 2. Merge this wrapped listItem into the previous listItem if present
	 *    (<li>Previous</li><li><ul><li>This --> <li>Previous<ul><li>This)
	 * 3. If this results in the wrapped list being preceded by another list,
	 *    merge those lists.
	 */

	var listType = listItem.getParent().getAttribute( 'style' );
	var listItemRange = listItem.getOuterRange();

	// CAREFUL: after initializing the variables above, we cannot use the model tree!
	// The first transaction will cause rebuilds so the nodes we have references to now
	// will be detached and useless after the first transaction. Instead, inspect
	// documentModel.data to find out things about the current structure.

	var surfaceModel = this.surface.getModel();
	// (1) Wrap the listItem in a list and a listItem
	surfaceModel.getLinearFragment( listItemRange, true )
		.wrapNodes( [ { type: 'listItem' }, { type: 'list', attributes: { style: listType } } ] );

	var documentModel = surfaceModel.getDocument();
	// (2) Merge the listItem into the previous listItem (if there is one)
	if (
		documentModel.data.getData( listItemRange.start ).type === 'listItem' &&
		documentModel.data.getData( listItemRange.start - 1 ).type === '/listItem'
	) {
		var mergeStart = listItemRange.start - 1;
		var mergeEnd = listItemRange.start + 1;
		// (3) If this results in adjacent lists, merge those too
		if (
			documentModel.data.getData( mergeEnd ).type === 'list' &&
			documentModel.data.getData( mergeStart - 1 ).type === '/list'
		) {
			mergeStart--;
			mergeEnd++;
		}
		surfaceModel.getLinearFragment( new ve.Range( mergeStart, mergeEnd ), true ).removeContent();
	}

	// TODO If this listItem has a child list, split&unwrap it
};

/**
 * Unindent a list item.
 *
 * TODO: Refactor functionality into {ve.dm.SurfaceFragment}.
 *
 * @param {ve.dm.ListItemNode} listItem List item to unindent
 * @throws {Error} listItem must be a ve.dm.ListItemNode
 */
ve.ui.IndentationAction.prototype.unindentListItem = function ( listItem ) {
	// This check should never fail
	/* istanbul ignore next */
	if ( !( listItem instanceof ve.dm.ListItemNode ) ) {
		throw new Error( 'listItem must be a ve.dm.ListItemNode' );
	}

	var tx;
	var surfaceModel = this.surface.getModel();
	var documentModel = surfaceModel.getDocument();
	var fragment = surfaceModel.getLinearFragment( listItem.getOuterRange(), true );
	var list = listItem.getParent();
	var listElement = list.getClonedElement();
	var grandParentType = list.getParent().getType();
	var listItemRange = listItem.getOuterRange();

	/*
	 * Outdenting a list item is done as follows:
	 * 1. Split the parent list to isolate the listItem in its own list
	 * 1a. Split the list before the listItem if it's not the first child
	 * 1b. Split the list after the listItem if it's not the last child
	 * 2. If this isolated list's parent is not a listItem, unwrap the listItem and the isolated list, and stop.
	 * 3. Split the parent listItem to isolate the list in its own listItem
	 * 3a. Split the listItem before the list if it's not the first child
	 * 3b. Split the listItem after the list if it's not the last child
	 * 4. Unwrap the now-isolated listItem and the isolated list
	 */
	// TODO: Child list handling, gotta figure that out.
	// CAREFUL: after initializing the variables above, we cannot use the model tree!
	// The first transaction will cause rebuilds so the nodes we have references to now
	// will be detached and useless after the first transaction. Instead, inspect
	// documentModel.data to find out things about the current structure.

	// (1) Split the listItem into a separate list
	if ( documentModel.data.getData( listItemRange.start - 1 ).type !== 'list' ) {
		// (1a) listItem is not the first child, split the list before listItem
		tx = ve.dm.TransactionBuilder.static.newFromInsertion( documentModel, listItemRange.start,
			[ { type: '/list' }, listElement ]
		);
		surfaceModel.change( tx );
		// tx.translateRange( listItemRange ) doesn't do what we want
		listItemRange = listItemRange.translate( 2 );
	}
	if ( documentModel.data.getData( listItemRange.end ).type !== '/list' ) {
		// (1b) listItem is not the last child, split the list after listItem
		tx = ve.dm.TransactionBuilder.static.newFromInsertion( documentModel, listItemRange.end,
			[ { type: '/list' }, listElement ]
		);
		surfaceModel.change( tx );
		// listItemRange is not affected by this transaction
	}
	var splitListRange = new ve.Range( listItemRange.start - 1, listItemRange.end + 1 );

	if ( grandParentType !== 'listItem' ) {
		// The user is trying to unindent a list item that's not nested
		// (2) Unwrap both the list and the listItem, dumping the listItem's contents
		// into the list's parent
		surfaceModel.getLinearFragment( new ve.Range( listItemRange.start + 1, listItemRange.end - 1 ), true )
			.unwrapNodes( 2 );

		// Ensure paragraphs are not generated paragraphs now that they are not in a list
		var children = fragment.getSiblingNodes();
		for ( var i = 0, length = children.length; i < length; i++ ) {
			var child = children[ i ].node;
			if ( child.type === 'paragraph' && ve.getProp( child.element, 'internal', 'generated' ) ) {
				surfaceModel.getLinearFragment( child.getOuterRange(), true ).convertNodes( 'paragraph', child.getAttributes(), {} );
			}
		}
	} else {
		// (3) Split the list away from parentListItem into its own listItem
		// TODO factor common split logic somehow?
		if ( documentModel.data.getData( splitListRange.start - 1 ).type !== 'listItem' ) {
			// (3a) Split parentListItem before list
			tx = ve.dm.TransactionBuilder.static.newFromInsertion( documentModel, splitListRange.start,
				[ { type: '/listItem' }, { type: 'listItem' } ]
			);
			surfaceModel.change( tx );
			// tx.translateRange( splitListRange ) doesn't do what we want
			splitListRange = splitListRange.translate( 2 );
		}
		if ( documentModel.data.getData( splitListRange.end ).type !== '/listItem' ) {
			// (3b) Split parentListItem after list
			tx = ve.dm.TransactionBuilder.static.newFromInsertion( documentModel, splitListRange.end,
				[ { type: '/listItem' }, { type: 'listItem' } ]
			);
			surfaceModel.change( tx );
			// splitListRange is not affected by this transaction
		}

		// (4) Unwrap the list and its containing listItem
		surfaceModel.getLinearFragment( new ve.Range( splitListRange.start + 1, splitListRange.end - 1 ), true )
			.unwrapNodes( 2 );
	}
};

/* Registration */

ve.ui.actionFactory.register( ve.ui.IndentationAction );
