/*!
 * VisualEditor IME-like testing
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Text offset from start or end of string
 *
 * The offsets are measured in text node code unit boundaries. That is, there is an
 * implicit start and end position in each text node, so there are eight positions in
 * "he<b>ll</b>o".  This will not always agree exactly with a real browser's cursor
 * positions, because of grapheme clustering and positioning around tags, but is a
 * useful test shorthand.
 *
 * @class
 * @constructor
 * @param {string} direction (forward counts from string start, backward from end)
 * @param {number} offset Offset in text node code units
 */
ve.ce.TestOffset = function VeCeTestOffset( direction, offset ) {
	if ( direction !== 'forward' && direction !== 'backward' ) {
		throw new Error( 'Invalid direction "' + direction + '"' );
	}
	this.direction = direction;
	this.offset = offset;
	this.lastText = null;
	this.lastSel = null;
};

/**
 * Calculate the offset from each end of a particular HTML string
 *
 * @param {Node} node The DOM node with respect to which the offset is resolved
 * @return {Object} Offset information
 * @return {number} [return.consumed] The number of code units consumed (if n out of range)
 * @return {Node} [return.node] The node containing the offset (if n in range)
 * @return {number} [return.offset] The offset in code units / child elements (if n in range)
 * @return {string} [return.slice] String representation of the offset position (if n in range)
 */
ve.ce.TestOffset.prototype.resolve = function ( node ) {
	var reversed = ( this.direction !== 'forward' );
	return ve.ce.TestOffset.static.findTextOffset( node, this.offset, reversed );
};

/* Static Methods */
ve.ce.TestOffset.static = {};

/**
 * Find text by offset in the given node.
 * Returns the same as #resolve.
 *
 * @private
 * @static
 * @param {Node} node
 * @param {number} n Offset
 * @param {boolean} reversed
 * @return {Object} Offset information
 */
ve.ce.TestOffset.static.findTextOffset = function ( node, n, reversed ) {
	if ( node.nodeType === node.TEXT_NODE ) {
		// Test length >= n because one more boundaries than code units
		if ( node.textContent.length >= n ) {
			var offset = reversed ? node.textContent.length - n : n;
			var slice = node.textContent.slice( 0, offset ) + '|' +
				node.textContent.slice( offset );
			return { node: node, offset: offset, slice: slice };
		} else {
			return { consumed: node.textContent.length + 1 };
		}
	}
	if ( node.nodeType !== node.ELEMENT_NODE ) {
		return { consumed: 0 };
	}
	// node is an ELEMENT_NODE below here
	// TODO consecutive text nodes will cause an extra phantom boundary.
	// In realistic usage, this can't always be avoided, because normalize() will
	// close an IME.
	var childNodes = Array.prototype.slice.call( node.childNodes );

	if ( childNodes.length === 0 ) {
		if ( n === 0 ) {
			return { node: node, offset: 0, slice: '|' };
		}
		return { consumed: 0 };
	}

	if ( reversed ) {
		childNodes.reverse();
	}
	var consumed = 0;
	for ( var i = 0, len = childNodes.length; i < len; i++ ) {
		var childNode = node.childNodes[ i ];
		var found = ve.ce.TestOffset.static.findTextOffset( childNode, n - consumed, reversed );
		if ( found.node ) {
			return found;
		}
		consumed += found.consumed;
		// Extra boundary after element, if not followed by a text node
		if ( childNode.nodeType === node.ELEMENT_NODE ) {
			if ( i + 1 === len || childNodes[ i + 1 ].nodeType !== node.TEXT_NODE ) {
				consumed += 1;
				if ( consumed === n ) {
					// TODO: create a reasonable 'slice' string
					return { node: node, offset: i + 1, slice: 'XXX' };
				}
			}
		}
	}
	return { consumed: consumed };
};

/**
 * IME-like CE test class
 *
 * @class
 * @constructor
 * @param {ve.ce.Surface} surface The UI Surface
 */
ve.ce.TestRunner = function VeCeTestRunner( surface ) {
	this.view = surface;
	this.model = surface.getModel();
	this.doc = surface.getElementDocument();
	this.nativeSelection = surface.nativeSelection;

	// TODO: The code assumes that the document consists of exactly one paragraph
	this.lastText = this.getParagraph().textContent;

	// Turn off SurfaceObserver setTimeouts
	surface.surfaceObserver.pollInterval = null;

	// Take control of eventSequencer 'setTimeouts'
	ve.test.utils.hijackEventSequencerTimeouts( this.view.eventSequencer );
};

/* Methods */

/**
 * Get the paragraph node in which testing occurs
 *
 * TODO: The code assumes that the document consists of exactly one paragraph
 *
 * @return {Node} The paragraph node
 */
ve.ce.TestRunner.prototype.getParagraph = function () {
	var p = this.view.$element.find( '.ve-ce-attachedRootNode > p' )[ 0 ];
	if ( p === undefined ) {
		if ( this.view.$element.find( '.ve-ce-attachedRootNode' )[ 0 ] === undefined ) {
			throw new Error( 'no CE div' );
		}
		throw new Error( 'CE div but no p' );
	}
	return p;
};

/**
 * Run any pending postponed calls
 *
 * Exceptions thrown will leave postponed calls in an inconsistent state
 */
ve.ce.TestRunner.prototype.endLoop = function () {
	this.view.eventSequencer.endLoop();
};

/**
 * Send an event to the ce.Surface eventSequencer
 *
 * @param {string} eventName DOM event name (such as 'keydown')
 * @param {Object} ev Fake event object with any necessary properties
 */
ve.ce.TestRunner.prototype.sendEvent = function ( eventName, ev ) {
	// Ensure ev has an originalEvent property.
	ev.originalEvent = ev.originalEvent || {};
	this.view.eventSequencer.onEvent( eventName, ev );
};

/**
 * Change the text
 *
 * TODO: it should be possible to add markup
 *
 * @param {string} text The new text
 */
ve.ce.TestRunner.prototype.changeText = function ( text ) {
	// TODO: This method doesn't handle arbitrary text changes in a paragraph
	// with non-text nodes. It just works for the main cases that are important
	// in the existing IME tests.

	// Remove all descendant text nodes
	// This may clobber the selection, so the test had better call changeSel next.
	var paragraph = this.getParagraph();
	$( paragraph ).find( '*' ).addBack().contents().each( function () {
		if ( this.nodeType === Node.TEXT_NODE ) {
			this.parentNode.removeChild( this );
		}
	} );

	var range = document.createRange();
	if ( text === '' ) {
		range.setStart( paragraph, 0 );
	} else {
		// Insert the text at the start of the paragraph, and put the cursor after
		// the insertion, to ensure consistency across test environments.
		// See T176453
		var textNode = document.createTextNode( text );
		paragraph.insertBefore( textNode, paragraph.firstChild );
		range.setStart( textNode, text.length );
	}
	this.nativeSelection.removeAllRanges();
	this.nativeSelection.addRange( range );
	this.lastText = text;
};

/**
 * Select text by offset in concatenated text nodes
 *
 * @param {ve.ce.TestOffset|number} start The start offset
 * @param {ve.ce.TestOffset|number} end The end offset
 * @return {Object} Selected range
 * @return {Node} return.startNode The node at the start of the selection
 * @return {number} return.startOffset The start offset within the node
 * @return {Node} return.endNode The node at the endof the selection
 * @return {number} return.endOffset The endoffset within the node
 */
ve.ce.TestRunner.prototype.changeSel = function ( start, end ) {
	if ( typeof start === 'number' ) {
		start = new ve.ce.TestOffset( 'forward', start );
	}
	if ( typeof end === 'number' ) {
		end = new ve.ce.TestOffset( 'forward', end );
	}

	var foundStart = start.resolve( this.getParagraph() );
	var foundEnd = start.resolve( this.getParagraph() );
	if ( !foundStart.node ) {
		throw new Error( 'Bad start offset: ' + start.offset );
	}
	if ( !foundEnd.node ) {
		throw new Error( 'Bad end offset: ', end.offset );
	}

	var nativeRange = this.doc.createRange();
	nativeRange.setStart( foundStart.node, foundStart.offset );
	nativeRange.setEnd( foundEnd.node, foundEnd.offset );
	this.nativeSelection.removeAllRanges();
	this.getParagraph().focus();
	this.nativeSelection.addRange( nativeRange, false );
	this.lastSel = [ start, end ];

	return {
		startNode: foundStart.node,
		endNode: foundEnd.node,
		startOffset: foundStart.offset,
		endOffset: foundEnd.offset
	};
};

/**
 * Call assert.equal to check the IME test has updated the DM correctly
 *
 * @param {Object} assert The QUnit assertion object
 * @param {string} testName The name of the test scenario
 * @param {number} sequence The sequence number in the test scenario
 */
ve.ce.TestRunner.prototype.testEqual = function ( assert, testName, sequence ) {
	var comment = testName + ' seq=' + sequence + ': "' + this.lastText + '"';
	assert.strictEqual( this.model.getDocument().data.getText( false ), this.lastText, comment );
};

/**
 * Call assert.notStrictEqual to check the IME test has not updated the DM correctly
 *
 * @param {Object} assert The QUnit assertion object
 * @param {string} testName The name of the test scenario
 * @param {number} sequence The sequence number in the test scenario
 */
ve.ce.TestRunner.prototype.testNotEqual = function ( assert, testName, sequence ) {
	var comment = testName + ' seq=' + sequence + ': "' + this.lastText + '"';
	assert.notStrictEqual( this.model.getDocument().data.getText( false ), this.lastText, comment );
};

ve.ce.TestRunner.prototype.ok = function ( assert, testName, sequence ) {
	var comment = testName + ' seq=' + sequence + ': "' + this.lastText + '"';
	assert.true( true, comment );
};

ve.ce.TestRunner.prototype.failDied = function ( assert, testName, sequence, ex ) {
	var comment = testName + ' seq=' + sequence + ': "' + this.lastText +
		'" ex=' + ex + ' stack=<' + ex.stack + '>';
	assert.true( false, comment );
};
