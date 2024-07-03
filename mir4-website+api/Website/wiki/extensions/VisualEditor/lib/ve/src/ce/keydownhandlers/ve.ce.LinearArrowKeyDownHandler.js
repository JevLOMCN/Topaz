/*!
 * VisualEditor ContentEditable linear arrow key down handler
 *
 * @copyright See AUTHORS.txt
 */

/* istanbul ignore next */
/**
 * Arrow key down handler for linear selections.
 *
 * @class
 * @extends ve.ce.KeyDownHandler
 *
 * @constructor
 */
ve.ce.LinearArrowKeyDownHandler = function VeCeLinearArrowKeyDownHandler() {
	// Parent constructor - never called because class is fully static
	// ve.ui.LinearArrowKeyDownHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.LinearArrowKeyDownHandler, ve.ce.KeyDownHandler );

/* Static properties */

ve.ce.LinearArrowKeyDownHandler.static.name = 'linearArrow';

ve.ce.LinearArrowKeyDownHandler.static.keys = [
	OO.ui.Keys.UP, OO.ui.Keys.DOWN, OO.ui.Keys.LEFT, OO.ui.Keys.RIGHT,
	OO.ui.Keys.HOME, OO.ui.Keys.END, OO.ui.Keys.PAGEUP, OO.ui.Keys.PAGEDOWN
];

ve.ce.LinearArrowKeyDownHandler.static.supportedSelections = [ 'linear' ];

/* Static methods */

/**
 * @inheritdoc
 */
ve.ce.LinearArrowKeyDownHandler.static.execute = function ( surface, e ) {
	var isBlockMove = e.keyCode === OO.ui.Keys.UP || e.keyCode === OO.ui.Keys.DOWN ||
			e.keyCode === OO.ui.Keys.PAGEUP || e.keyCode === OO.ui.Keys.PAGEDOWN ||
			e.keyCode === OO.ui.Keys.HOME || e.keyCode === OO.ui.Keys.END,
		keyBlockDirection = e.keyCode === OO.ui.Keys.DOWN || e.keyCode === OO.ui.Keys.PAGEDOWN || e.keyCode === OO.ui.Keys.END ? 1 : -1,
		range = surface.model.getSelection().getRange(),
		activeNode = surface.getActiveNode();

	// TODO: onDocumentKeyDown did this already
	surface.surfaceObserver.stopTimerLoop();
	// TODO: onDocumentKeyDown did this already
	surface.surfaceObserver.pollOnce();

	var direction, directionality;
	if ( surface.focusedBlockSlug ) {
		// Block level selection, so directionality is just css directionality
		if ( isBlockMove ) {
			direction = keyBlockDirection;
		} else {
			directionality = $( surface.focusedBlockSlug ).css( 'direction' );
			// eslint-disable-next-line no-bitwise
			if ( e.keyCode === OO.ui.Keys.LEFT ^ directionality === 'rtl' ) {
				// Left arrow in ltr, or right arrow in rtl
				direction = -1;
			} else {
				// Left arrow in rtl, or right arrow in ltr
				direction = 1;
			}
		}
		range = surface.model.getDocument().getRelativeRange(
			range,
			direction,
			'character',
			e.shiftKey,
			activeNode && ( e.shiftKey || activeNode.trapsCursor() ) ? activeNode.getRange() : null
		);
		surface.model.setLinearSelection( range );
		e.preventDefault();
		return true;
	}

	if ( surface.focusedNode ) {
		if ( isBlockMove ) {
			direction = keyBlockDirection;
		} else {
			directionality = surface.getFocusedNodeDirectionality();
			// eslint-disable-next-line no-bitwise
			if ( e.keyCode === OO.ui.Keys.LEFT ^ directionality === 'rtl' ) {
				// Left arrow in ltr, or right arrow in rtl
				direction = -1;
			} else {
				// Left arrow in rtl, or right arrow in ltr
				direction = 1;
			}
		}

		if ( !surface.focusedNode.isContent() ) {
			// Block focusable node: move back/forward in DM (and DOM) and preventDefault
			range = surface.model.getDocument().getRelativeRange(
				range,
				direction,
				'character',
				e.shiftKey,
				activeNode && ( e.shiftKey || activeNode.trapsCursor() ) ? activeNode.getRange() : null
			);
			surface.model.setLinearSelection( range );
			e.preventDefault();
			return true;
		}
		// Else inline focusable node

		if ( e.shiftKey ) {
			// There is no DOM range to expand (because the selection is faked), so
			// use "collapse to focus - observe - expand". Define "focus" to be the
			// edge of the focusedNode in the direction of motion (so the selection
			// always grows). This means that clicking on the focusableNode then
			// modifying the selection will always include the node.
			// eslint-disable-next-line no-bitwise
			if ( direction === -1 ^ range.isBackwards() ) {
				range = range.flip();
			}
			surface.model.setLinearSelection( new ve.Range( range.to ) );
		} else {
			// Move to start/end of node in the model in DM (and DOM)
			range = new ve.Range( direction === 1 ? range.end : range.start );
			surface.model.setLinearSelection( range );
			if ( !isBlockMove ) {
				// Un-shifted left/right: we've already moved so preventDefault
				e.preventDefault();
				return true;
			}
			// Else keep going with the cursor in the new place
		}
	}
	// Else keep DM range and DOM selection as-is

	var collapseNode, collapseOffset;
	if ( e.shiftKey && !ve.supportsSelectionExtend && range.isBackwards() ) {
		// If the browser doesn't support backwards selections, but the dm range
		// is backwards, then use "collapse to anchor - observe - expand".
		collapseNode = surface.nativeSelection.anchorNode;
		collapseOffset = surface.nativeSelection.anchorOffset;
	} else if ( e.shiftKey && !range.isCollapsed() && isBlockMove ) {
		// If selection is expanded and cursoring is up/down, use
		// "collapse to focus - observe - expand" to work round quirks.
		collapseNode = surface.nativeSelection.focusNode;
		collapseOffset = surface.nativeSelection.focusOffset;
	}
	// Else don't collapse the selection

	if ( collapseNode ) {
		var nativeRange = surface.getElementDocument().createRange();
		nativeRange.setStart( collapseNode, collapseOffset );
		nativeRange.setEnd( collapseNode, collapseOffset );
		surface.nativeSelection.removeAllRanges();
		surface.nativeSelection.addRange( nativeRange );
	}

	var startFocusNode = surface.nativeSelection.focusNode;
	var startFocusOffset = surface.nativeSelection.focusOffset;

	// Re-expand (or fixup) the selection after the native action, if necessary
	surface.eventSequencer.afterOne( { keydown: function () {
		// Support: Chrome
		// Chrome bug lets you cursor into a multi-line contentEditable=false with up/down…
		var viewNode = $( surface.nativeSelection.focusNode ).closest( '.ve-ce-leafNode,.ve-ce-branchNode' ).data( 'view' );
		if ( !viewNode ) {
			// Irrelevant selection (or none)
			return;
		}

		var newRange;
		if ( viewNode.isFocusable() ) {
			var afterDirection;
			// We've landed in a focusable node; fixup the range
			if ( isBlockMove ) {
				// The intended direction is clear, even if the cursor did not move
				// or did something completely preposterous
				afterDirection = keyBlockDirection;
			} else {
				// Observe which way the cursor moved
				afterDirection = ve.compareDocumentOrder(
					surface.nativeSelection.focusNode,
					surface.nativeSelection.focusOffset,
					startFocusNode,
					startFocusOffset
				);
			}
			newRange = (
				afterDirection > 0 ?
					viewNode.getOuterRange() :
					viewNode.getOuterRange().flip()
			);
		} else {
			// Check where the range has moved to
			surface.surfaceObserver.pollOnceNoCallback();
			newRange = new ve.Range( surface.surfaceObserver.getRange().to );
		}

		// Adjust range to use old anchor, if necessary
		if ( e.shiftKey ) {
			newRange = new ve.Range( range.from, newRange.to );
			surface.getModel().setLinearSelection( newRange );
		}
		surface.updateActiveAnnotations();
		surface.surfaceObserver.pollOnce();
	} } );

	return true;
};

/* Registration */

ve.ce.keyDownHandlerFactory.register( ve.ce.LinearArrowKeyDownHandler );
