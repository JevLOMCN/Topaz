/*!
 * VisualEditor UserInterface MWWikitextAction class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Content action.
 *
 * @class
 * @extends ve.ui.Action
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 */
ve.ui.MWWikitextAction = function VeUiMWWikitextAction() {
	// Parent constructor
	ve.ui.MWWikitextAction.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWWikitextAction, ve.ui.Action );

/* Static Properties */

ve.ui.MWWikitextAction.static.name = 'mwWikitext';

ve.ui.MWWikitextAction.static.methods = [ 'toggleWrapSelection', 'wrapSelection', 'wrapLine' ];

/* Methods */

/**
 * Wrap an selection inline
 *
 * @param {string} before Text to go before selection
 * @param {string} after Text to go after selection
 * @param {Function|string} placeholder Placeholder text to insert at an empty selection
 * @param {Function} [expandOffsetsCallback] Function that returns a tuple of offsets to expand to selection to in order to get relevant text for unwrapping
 * @param {Function} [unwrapOffsetsCallback] Function that returns a tuple of offsets to unwrap from the selected text,
 *  e.g. "''Foo'''" -> [2,3] to unwrap 2 from the left and 3 from the right
 * @return {boolean} Action was executed
 */
ve.ui.MWWikitextAction.prototype.toggleWrapSelection = function ( before, after, placeholder, expandOffsetsCallback, unwrapOffsetsCallback ) {
	var originalFragment = this.surface.getModel().getFragment( null, false, true /* excludeInsertions */ ),
		fragment = originalFragment;

	var textBefore, textAfter;
	if ( expandOffsetsCallback ) {
		var contextRange = fragment.expandLinearSelection( 'siblings' ).getSelection().getCoveringRange();
		var data = fragment.getDocument().data;
		var range = fragment.getSelection().getCoveringRange();
		textBefore = data.getText( true, new ve.Range( contextRange.start, range.start ) );
		textAfter = data.getText( true, new ve.Range( range.end, contextRange.end ) );
		var expandOffsets = expandOffsetsCallback( textBefore, textAfter );
		if ( expandOffsets ) {
			fragment = originalFragment.adjustLinearSelection( expandOffsets[ 0 ], expandOffsets[ 1 ] );
		}
	}

	if ( unwrapOffsetsCallback ) {
		var unwrapOffsets = unwrapOffsetsCallback( fragment.getText(), textBefore, textAfter );
		if ( unwrapOffsets ) {
			fragment.unwrapText( unwrapOffsets[ 0 ], unwrapOffsets[ 1 ] );
		} else {
			fragment.wrapText( before, after, placeholder, true );
		}
		originalFragment.select();
		return true;
	}

	fragment.wrapText( before, after, placeholder ).select();
	originalFragment.select();
	return true;
};

/**
 * Wrap an selection inline
 *
 * @param {string} before Text to go before selection
 * @param {string} after Text to go after selection
 * @param {Function|string} placeholder Placeholder text to insert at an empty selection
 * @return {boolean} Action was executed
 */
ve.ui.MWWikitextAction.prototype.wrapSelection = function ( before, after, placeholder ) {
	var fragment = this.surface.getModel().getFragment( null, false, true /* excludeInsertions */ );
	fragment.wrapText( before, after, placeholder ).select();
	return true;
};

/**
 * Wrap an selection as a block element on its own line
 *
 * If the selection is collapsed, it expands to take the whole line, otherwise it splits
 * the paragraph to make sure it is one line
 *
 * @param {string} before Text to go before each line
 * @param {string} after Text to go after each line
 * @param {Function|string} placeholder Placeholder text to insert at an empty selection
 * @param {Function} [unwrapOffsetsCallback] Function that returns a tuple of offsets to unwrap from the selected text,
 *  e.g. '== Foo ===' -> [2,3] to unwrap 2 from the left and 3 from the right
 * @return {boolean} Action was executed
 */
ve.ui.MWWikitextAction.prototype.wrapLine = function ( before, after, placeholder, unwrapOffsetsCallback ) {
	var originalFragment = this.surface.getModel().getFragment( null, false, true /* excludeInsertions */ ),
		selectedNodes = originalFragment.getLeafNodes();

	var unwrapped = false;
	for ( var i = selectedNodes.length - 1; i >= 0; i-- ) {
		if ( selectedNodes.length > 1 && selectedNodes[ i ].nodeRange.isCollapsed() ) {
			continue;
		}
		var fragment = this.surface.getModel().getLinearFragment( selectedNodes[ i ].nodeRange, true );
		var unwrapOffsets = unwrapOffsetsCallback && unwrapOffsetsCallback( fragment.getText() );

		if ( selectedNodes.length === 1 && originalFragment.getSelection().isCollapsed() ) {
			originalFragment = fragment;
		}

		if ( unwrapOffsets ) {
			fragment.unwrapText( unwrapOffsets[ 0 ], unwrapOffsets[ 1 ] );
			unwrapped = true;
		}

		var wrappedFragment = fragment.wrapText( before, after, placeholder );
		if ( !unwrapped && wrappedFragment !== fragment ) {
			if ( !ve.dm.LinearData.static.isElementData(
				wrappedFragment.collapseToStart().adjustLinearSelection( -1, 0 ).getData()[ 0 ]
			) ) {
				wrappedFragment.collapseToStart().insertContent( [ { type: '/paragraph' }, { type: 'paragraph' } ] );
			}
			if ( !ve.dm.LinearData.static.isElementData(
				wrappedFragment.collapseToEnd().adjustLinearSelection( 0, 1 ).getData()[ 0 ]
			) ) {
				wrappedFragment.collapseToEnd().insertContent( [ { type: '/paragraph' }, { type: 'paragraph' } ] );
			}
		}
	}
	originalFragment.select();
	return true;
};

/* Registration */

ve.ui.actionFactory.register( ve.ui.MWWikitextAction );
