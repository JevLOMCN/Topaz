/*!
 * VisualEditor ContentEditable linear delete key down handler
 *
 * @copyright See AUTHORS.txt
 */

/* istanbul ignore next */
/**
 * Delete key down handler for linear selections.
 *
 * @class
 * @extends ve.ce.KeyDownHandler
 *
 * @constructor
 */
ve.ce.LinearDeleteKeyDownHandler = function VeCeLinearDeleteKeyDownHandler() {
	// Parent constructor - never called because class is fully static
	// ve.ui.LinearDeleteKeyDownHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.LinearDeleteKeyDownHandler, ve.ce.KeyDownHandler );

/* Static properties */

ve.ce.LinearDeleteKeyDownHandler.static.name = 'linearDelete';

ve.ce.LinearDeleteKeyDownHandler.static.keys = [ OO.ui.Keys.BACKSPACE, OO.ui.Keys.DELETE ];

ve.ce.LinearDeleteKeyDownHandler.static.supportedSelections = [ 'linear' ];

/* Static methods */

/**
 * @inheritdoc
 *
 * The handler just schedules a poll to observe the native content removal, unless
 * one of the following is true:
 * - The ctrlKey is down; or
 * - The selection is expanded; or
 * - We are directly adjacent to an element node in the deletion direction.
 * In these cases, it will perform the content removal itself.
 */
ve.ce.LinearDeleteKeyDownHandler.static.execute = function ( surface, e ) {
	var direction = e.keyCode === OO.ui.Keys.DELETE ? 1 : -1,
		unit = ( e.altKey === true || e.ctrlKey === true ) ? 'word' : 'character',
		offset = 0,
		rangeToRemove = surface.getModel().getSelection().getRange(),
		documentModel = surface.getModel().getDocument(),
		focusedNode = surface.getFocusedNode(),
		uiSurface = surface.getSurface(),
		data = documentModel.data;

	if ( surface.isReadOnly() ) {
		e.preventDefault();
		return true;
	}

	if ( direction === 1 && e.shiftKey && ve.getSystemPlatform() !== 'mac' ) {
		// Shift+Del on non-Mac platforms performs 'cut', so
		// don't handle it here.
		return false;
	}

	if ( focusedNode ) {
		var command = uiSurface.commandRegistry.getDeleteCommandForNode( focusedNode );
		if ( command ) {
			command.execute( uiSurface );
			e.preventDefault();
			return true;
		}
	}

	// Use native behaviour then poll if collapsed, unless we are adjacent to some hard tag
	// (or CTRL is down, in which case we can't reliably predict whether the native behaviour
	// would delete far enough to remove some element)
	if ( rangeToRemove.isCollapsed() && !e.ctrlKey ) {
		if ( surface.nativeSelection.focusNode === null ) {
			// Unexplained failures causing log spam: T262303
			// How can it be null when this method should only be called for linear selections?
			e.preventDefault();
			return true;
		}

		var position = ve.adjacentDomPosition(
			{
				node: surface.nativeSelection.focusNode,
				offset: surface.nativeSelection.focusOffset
			},
			direction,
			{ stop: ve.isHardCursorStep }
		);
		var skipNode = position.steps[ position.steps.length - 1 ].node;
		if ( skipNode.nodeType === Node.TEXT_NODE ) {
			surface.eventSequencer.afterOne( {
				keydown: surface.surfaceObserver.pollOnce.bind( surface.surfaceObserver )
			} );
			return true;
		}

		var range;
		// If the native action would delete an outside nail, move *two* cursor positions
		// in the deletion direction, to get inside the link just past the inside nail,
		// then preventDefault
		if (
			direction > 0 ?
				skipNode.classList.contains( 've-ce-nail-pre-open' ) :
				skipNode.classList.contains( 've-ce-nail-post-close' )
		) {
			position = ve.adjacentDomPosition(
				position,
				direction,
				{ stop: ve.isHardCursorStep }
			);
			range = document.createRange();
			range.setStart( position.node, position.offset );
			surface.nativeSelection.removeAllRanges();
			surface.nativeSelection.addRange( range );
			surface.updateActiveAnnotations();
			e.preventDefault();
			return true;
		}

		var pairNode;
		// If inside an empty link, delete it and preventDefault
		if (
			skipNode.classList &&
			skipNode.classList.contains(
				direction > 0 ?
					've-ce-nail-pre-close' :
					've-ce-nail-post-open'
			) &&
			( pairNode = (
				direction > 0 ?
					skipNode.previousSibling :
					skipNode.nextSibling
			) ) &&
			pairNode.classList &&
			pairNode.classList.contains(
				direction > 0 ?
					've-ce-nail-post-open' :
					've-ce-nail-pre-close'
			)
		) {
			var linkNode = skipNode.parentNode;
			range = document.createRange();
			// Set start to link's offset, minus 1 to allow for outer nail deletion
			// (browsers actually tend to adjust range offsets automatically
			// for previous sibling deletion, but just in case …)
			range.setStart( linkNode.parentNode, ve.parentIndex( linkNode ) - 1 );
			// Remove the outer nails, then the link itself
			linkNode.parentNode.removeChild( linkNode.previousSibling );
			linkNode.parentNode.removeChild( linkNode.nextSibling );
			linkNode.parentNode.removeChild( linkNode );

			surface.nativeSelection.removeAllRanges();
			surface.nativeSelection.addRange( range );
			surface.updateActiveAnnotations();
			e.preventDefault();
			return true;
		}

		// If the native action would delete an inside nail, move *two* cursor positions
		// in the deletion direction, to get outside the link just past the outside nail,
		// then preventDefault
		if (
			direction > 0 ?
				skipNode.classList.contains( 've-ce-nail-pre-close' ) :
				skipNode.classList.contains( 've-ce-nail-post-open' )
		) {
			position = ve.adjacentDomPosition(
				position,
				direction,
				{ stop: ve.isHardCursorStep }
			);
			range = document.createRange();
			range.setStart( position.node, position.offset );
			surface.nativeSelection.removeAllRanges();
			surface.nativeSelection.addRange( range );
			surface.updateActiveAnnotations();
			e.preventDefault();
			return true;
		}

		offset = rangeToRemove.start;
		if ( !e.ctrlKey && (
			( direction < 0 && !data.isElementData( offset - 1 ) ) ||
			( direction > 0 && !data.isElementData( offset ) )
		) ) {
			surface.eventSequencer.afterOne( {
				keydown: surface.surfaceObserver.pollOnce.bind( surface.surfaceObserver )
			} );
			return true;
		}
	}

	// Else range is uncollapsed or is adjacent to a non-nail element.
	if ( rangeToRemove.isCollapsed() ) {
		var originalRange = new ve.Range( rangeToRemove.from, rangeToRemove.to );
		// Expand rangeToRemove
		rangeToRemove = documentModel.getRelativeRange( rangeToRemove, direction, unit, true );
		if ( surface.getActiveNode() && !surface.getActiveNode().getRange().containsRange( rangeToRemove ) ) {
			e.preventDefault();
			return true;
		}

		var documentModelSelectedNodes = documentModel.selectNodes( rangeToRemove, 'siblings' );
		for ( var i = 0; i < documentModelSelectedNodes.length; i++ ) {
			var node = documentModelSelectedNodes[ i ].node;
			var nodeOuterRange = documentModelSelectedNodes[ i ].nodeOuterRange;
			var adjacentBlockSelection = null;
			if ( node instanceof ve.dm.TableNode ) {
				// Prevent backspacing/deleting over table cells
				if ( rangeToRemove.containsOffset( nodeOuterRange.start ) ) {
					adjacentBlockSelection = new ve.dm.TableSelection(
						nodeOuterRange, 0, 0
					);
				} else {
					var matrix = node.getMatrix();
					var row = matrix.getRowCount() - 1;
					var col = matrix.getColCount( row ) - 1;
					adjacentBlockSelection = new ve.dm.TableSelection(
						nodeOuterRange, col, row
					);
				}
			} else if ( node.isFocusable() ) {
				// Prevent backspacing/deleting over focusable nodes
				adjacentBlockSelection = new ve.dm.LinearSelection( node.getOuterRange() );
			}
			if ( adjacentBlockSelection ) {
				// Create a fragment from the selection as we might delete first
				var adjacentFragment = surface.getModel().getFragment( adjacentBlockSelection, true );
				var currentNode = documentModel.getDocumentNode().getNodeFromOffset( originalRange.start );
				if ( currentNode.canContainContent() && !currentNode.getLength() ) {
					// If starting in an empty CBN, delete the CBN instead (T338622)
					surface.getModel().getLinearFragment( currentNode.getOuterRange(), true ).delete( direction );
				}
				adjacentFragment.select();
				e.preventDefault();
				return true;
			}
		}

		if ( rangeToRemove.isCollapsed() ) {
			// For some reason (most likely: we're at the beginning or end of the document) we can't
			// expand the range. So, should we delete something or not?
			// The rules are:
			// * if we're literally at the start or end, and are in a content node, don't do anything
			// * if we're in a plain paragraph, don't do anything
			// * if we're in a list item and it's empty get rid of the item
			offset = rangeToRemove.start;
			var docLength = documentModel.getDocumentRange().getLength();
			if ( offset < docLength - 1 ) {
				while ( offset < docLength - 1 && data.isCloseElementData( offset ) ) {
					offset++;
				}
			}
			var startNode = documentModel.getDocumentNode().getNodeFromOffset( offset - 1 );
			var nodeRange = startNode.getOuterRange();
			if (
				// The node is not unwrappable (e.g. table cells, text nodes)
				!startNode.isUnwrappable() ||
				// Content item at the start / end?
				(
					( startNode.canContainContent() || surface.attachedRoot === startNode ) &&
					( nodeRange.start === 0 || nodeRange.end === docLength )
				)
			) {
				e.preventDefault();
				return true;
			} else {
				// Expand our removal to reflect what we actually need to remove
				switch ( startNode.getType() ) {
					case 'list':
					case 'listItem':
						uiSurface.execute( 'indentation', 'decrease' );
						e.preventDefault();
						return;
					default:
						if ( direction > 0 ) {
							rangeToRemove = new ve.Range( rangeToRemove.start, nodeRange.end );
						} else {
							rangeToRemove = new ve.Range( nodeRange.start, rangeToRemove.start - 1 );
						}
				}
			}
		}
	}

	surface.getModel().getLinearFragment( rangeToRemove, true ).delete( direction ).select();
	// Rerender selection even if it didn't change
	// TODO: is any of this necessary?
	surface.focus();
	surface.surfaceObserver.clear();
	// Check delete sequences
	surface.findAndExecuteSequences( false, true );
	e.preventDefault();
	return true;
};

/* Registration */

ve.ce.keyDownHandlerFactory.register( ve.ce.LinearDeleteKeyDownHandler );
