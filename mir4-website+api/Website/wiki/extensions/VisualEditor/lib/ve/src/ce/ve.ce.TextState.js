/*!
 * VisualEditor annotated text content state class
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Annotated text content state (a snapshot of node content)
 *
 * @class
 *
 * @constructor
 * @param {HTMLElement} element Element whose content is to be snapshotted
 */
ve.ce.TextState = function VeCeTextState( element ) {
	/**
	 * @property {ve.ce.TextStateChunk[]|null} chunks Linearized annotated text content
	 */
	this.chunks = this.constructor.static.getChunks( element );
};

/* Inheritance */

OO.initClass( ve.ce.TextState );

/* Static methods */

/**
 * Saves a snapshot of the current text content state
 *
 * @param {HTMLElement} element Element whose content is to be snapshotted
 * @return {ve.ce.TextStateChunk[]} chunks
 */
ve.ce.TextState.static.getChunks = function ( element ) {
	// Stack of element-lists in force; each element list is equal to its predecessor extended
	// by one element. This means two chunks have object-equal element lists if they have the
	// same elements in force (i.e. if their text nodes are DOM siblings).
	var elementListStack = [ [] ],
		stackTop = 0,
		chunks = [];

	/**
	 * Add to chunks, merging content with the same elements/type into the same chunk
	 *
	 * @param {string} text Plain text
	 * @param {string} [type] If this is a unicorn then 'unicorn', else 'text' (default)
	 */
	function add( text, type ) {
		if (
			!chunks.length ||
			chunks[ chunks.length - 1 ].elements !== elementListStack[ stackTop ] ||
			chunks[ chunks.length - 1 ].type !== type
		) {
			chunks.push( new ve.ce.TextStateChunk(
				text,
				elementListStack[ stackTop ],
				type || 'text'
			) );
		} else {
			chunks[ chunks.length - 1 ].text += text;
		}
	}

	var view;
	var annotationStack = [];
	var node = element;
	while ( true ) {
		// Process node
		// If appropriate, step into first child and loop
		// If no next sibling, step out until there is (breaking if we leave element)
		// Step to next sibling and loop
		if ( node.nodeType === Node.TEXT_NODE ) {
			add( node.data.replace( /\u00A0/g, ' ' ) );
		} else if (
			// Node types that don't appear in the model
			// TODO: what about comments?
			node.nodeType !== Node.ELEMENT_NODE ||
			node.classList.contains( 've-ce-branchNode-blockSlug' ) ||
			node.classList.contains( 've-ce-cursorHolder' )
		) {
			// Do nothing
		} else if ( ( view = $( node ).data( 'view' ) ) && view instanceof ve.ce.LeafNode ) {
			// Don't return the content, but return placeholder characters so the
			// offsets match up.
			// Only return placeholders for the first element in a sibling group;
			// otherwise we'll double count this node
			if ( node === view.$element[ 0 ] ) {
				// \u2603 is the snowman character: ☃
				add( '\u2603'.repeat( view.getOuterLength() ) );
			}
		} else if ( node.classList.contains( 've-ce-unicorn' ) ) {
			add( '', 'unicorn' );
		} else if ( node.firstChild ) {
			if ( ve.ce.isAnnotationElement( node ) ) {
				// Push a new element stack state
				elementListStack.push( elementListStack[ stackTop ].concat( node ) );
				annotationStack.push( node );
				stackTop++;
			}
			node = node.firstChild;
			continue;
		}
		// else no child nodes; do nothing

		// Step out of this node, then keep stepping outwards until there is a next sibling
		while ( true ) {
			if ( node === element ) {
				break;
			}
			if ( node === annotationStack[ annotationStack.length - 1 ] ) {
				annotationStack.pop();
				elementListStack.pop();
				stackTop--;
			}
			if ( node.nextSibling ) {
				break;
			}
			node = node.parentNode;
		}
		if ( node === element ) {
			break;
		}
		node = node.nextSibling;
	}
	return chunks;
};

/* Methods */

/**
 * Test whether the text state is equal to another.
 *
 * @param {ve.ce.TextState} other The other text state
 * @return {boolean} Whether the states are equal
 */
ve.ce.TextState.prototype.isEqual = function ( other ) {
	if ( other === this ) {
		return true;
	}
	if ( !other || this.chunks.length !== other.chunks.length ) {
		return false;
	}
	for ( var i = 0, len = this.chunks.length; i < len; i++ ) {
		if ( !( this.chunks[ i ].isEqual( other.chunks[ i ] ) ) ) {
			return false;
		}
	}
	return true;
};

/**
 * Create a model transaction from a change in text state.
 * This must be fast enough to cope with the typical typing scenario (either with or
 * without an IME) where the contents of a single text node get modified several times
 * per second.
 *
 * @param {ve.ce.TextState} prev Previous text state (must be for the same node)
 * @param {ve.dm.Document} modelDoc The model document
 * @param {number} modelOffset The offset of the node in the model
 * @param {ve.dm.AnnotationSet} [unicornAnnotations] The annotations at the unicorn, if any
 * @return {ve.dm.Transaction|null} Transaction corresponding to the text state change
 */
ve.ce.TextState.prototype.getChangeTransaction = function ( prev, modelDoc, modelOffset, unicornAnnotations ) {
	/**
	 * Calculates the size of newArray - oldArray (asymmetric difference)
	 * This is O(n^2) but the lengths should be fairly low, and this doesn't
	 * happen during typical typing.
	 *
	 * @param {Array} newArray
	 * @param {Array} oldArray
	 * @param {Function} equals Two-argument, boolean valued equivalence test
	 * @return {number} Number of elements of newArray not in oldArray
	 */
	function countMissing( newArray, oldArray, equals ) {
		var i2, i2Len, j2, j2Len,
			count = 0;
		for ( i2 = 0, i2Len = newArray.length; i2 < i2Len; i2++ ) {
			for ( j2 = 0, j2Len = oldArray.length; j2 < j2Len; j2++ ) {
				if ( equals( newArray[ i2 ], oldArray[ j2 ] ) ) {
					break;
				}
			}
			if ( j2 === j2Len ) {
				count++;
			}
		}
		return count;
	}

	var oldChunks = prev.chunks,
		newChunks = this.chunks,
		modelData = modelDoc.data,
		newData = [];

	// Find first changed chunk at start/end of oldChunks/newChunks
	var change = ve.countEdgeMatches( oldChunks, newChunks, function ( a, b ) {
		return a.isEqual( b );
	} );
	if ( change === null ) {
		// No change
		return null;
	}

	var i, iLen;
	var oldChunk, newChunk;

	// Count matching characters with matching annotations at start/end of the changed chunks.
	// During typical typing, there is a single changed chunk with matching start/end chars.
	var textStart = 0;
	var textEnd = 0;
	if ( change.start + change.end < Math.min( oldChunks.length, newChunks.length ) ) {
		// Both oldChunks and newChunks include a changed chunk. Therefore the first changed
		// chunk of oldChunks and newChunks is respectively oldChunks[ change.start ] and
		// newChunks[ change.start ] . If they have matching annotations, then matching
		// characters at their start are also part of the unchanged start region.
		if ( oldChunks[ change.start ].hasEqualElements( newChunks[ change.start ] ) ) {
			oldChunk = oldChunks[ change.start ];
			newChunk = newChunks[ change.start ];
			iLen = Math.min( oldChunk.text.length, newChunk.text.length );
			for ( i = 0; i < iLen; i++ ) {
				if ( oldChunk.text[ i ] !== newChunk.text[ i ] ) {
					break;
				}
			}
			textStart = i;
		}

		// Likewise, the last changed chunk of oldChunks and newChunks is respectively
		// oldChunks[ oldChunks.length - 1 - change.end ] and
		// newChunks[ newChunks.length - 1 - change.end ] , and if they have matching
		// annotations, then matching characters at their end potentially form part of
		// the unchanged end region.
		if ( oldChunks[ oldChunks.length - 1 - change.end ].hasEqualElements(
			newChunks[ newChunks.length - 1 - change.end ]
		) ) {
			oldChunk = oldChunks[ oldChunks.length - 1 - change.end ];
			newChunk = newChunks[ newChunks.length - 1 - change.end ];
			// However, if only one chunk has changed in oldChunks/newChunks, then
			// oldChunk/newChunk is also the *first* changed chunk, in which case
			// textStart has already eaten into that chunk; so take care not to
			// overlap it. (For example, for 'ana'->'anna', textStart will be 2 so
			// we want to limit textEnd to 1, else the 'n' of 'ana' will be counted
			// twice).
			iLen = Math.min(
				oldChunk.text.length -
				( change.start + change.end === oldChunks.length - 1 ? textStart : 0 ),
				newChunk.text.length -
				( change.start + change.end === newChunks.length - 1 ? textStart : 0 )
			);
			for ( i = 0; i < iLen; i++ ) {
				if ( newChunk.text[ newChunk.text.length - 1 - i ] !==
					oldChunk.text[ oldChunk.text.length - 1 - i ]
				) {
					break;
				}
			}
			textEnd = i;
		}
	}

	// Starting just inside the node, skip past matching chunks at the array starts
	var changeOffset = modelOffset + 1;
	for ( i = 0, iLen = change.start; i < iLen; i++ ) {
		changeOffset += oldChunks[ i ].text.length;
	}

	// Calculate range of old content to remove
	var removed = 0;
	for ( i = change.start, iLen = oldChunks.length - change.end; i < iLen; i++ ) {
		removed += oldChunks[ i ].text.length;
	}
	var removeRange = new ve.Range( changeOffset + textStart, changeOffset + removed - textEnd );

	var j, jLen;

	// Prepare new content, reusing existing ve.dm.Annotation objects where possible
	for ( i = change.start, iLen = newChunks.length - change.end; i < iLen; i++ ) {
		newChunk = newChunks[ i ];
		if ( newChunk.type === 'unicorn' ) {
			// Unicorns don't exist in the model
			continue;
		}
		var data = newChunk.text.split( '' );
		if ( i === change.start ) {
			data = data.slice( textStart );
		}
		if ( i === iLen - 1 ) {
			data = data.slice( 0, data.length - textEnd );
		}
		if ( data.length === 0 ) {
			// There is nothing to add, because textStart/textEnd causes all the
			// content in this chunk to be retained,
			continue;
		}

		// Search for matching elements in old chunks adjacent to the change (i.e. removed
		// chunks or the first chunk before/after the removal). O(n^2) is fine here
		// because during typical typing there is only one changed chunk, and the worst
		// case is three new chunks (e.g. when the interior of an existing chunk is
		// annotated).
		var annotations = null;
		var missing = null;
		// In the old chunks, find the chunks adjacent to the change
		var jStart;
		var matchStartOffset;
		if ( change.start === 0 ) {
			jStart = 0;
			matchStartOffset = changeOffset;
		} else {
			// Include the last chunk before the change
			jStart = change.start - 1;
			matchStartOffset = changeOffset - oldChunks[ jStart ].text.length;
		}
		var jEnd;
		if ( change.end === 0 ) {
			jEnd = oldChunks.length;
		} else {
			// Include the first chunk after the change
			jEnd = oldChunks.length - change.end + 1;
		}

		// Search for exact match first. During typical typing there is an exact
		// match at j=1 (or j=0 if there is no previous chunk).
		var matchOffset = matchStartOffset;
		for ( j = jStart; j < jEnd; j++ ) {
			oldChunk = oldChunks[ j ];
			if ( !oldChunk.hasEqualElements( newChunk ) ) {
				matchOffset += oldChunk.text.length;
				continue;
			}
			if ( oldChunk.type === 'unicorn' ) {
				if ( !unicornAnnotations ) {
					throw new Error( 'No unicorn annotations' );
				}
				annotations = unicornAnnotations;
				break;
			}
			annotations = modelData.getInsertionAnnotationsFromRange(
				new ve.Range( matchOffset ),
				true
			);
			break;
		}
		if ( annotations === null ) {
			// No exact match: search for the old chunk whose element list covers best
			// (choosing the startmost of any tying chunks). There may be no missing
			// elements even though the match is not exact (e.g. because of removed
			// annotations and reordering).
			//
			// This block doesn't happen during typical typing, so performance is
			// less critical.
			var leastMissing = newChunk.elements.length;
			var bestOffset = null;
			matchOffset = matchStartOffset;
			for ( j = jStart; j < jEnd; j++ ) {
				oldChunk = oldChunks[ j ];
				missing = countMissing(
					newChunk.elements,
					oldChunk.elements,
					ve.ce.TextStateChunk.static.compareElements
				);
				if ( missing < leastMissing ) {
					leastMissing = missing;
					bestOffset = matchOffset;
					if ( missing === 0 ) {
						break;
					}
				}
				matchOffset += oldChunk.text.length;
			}
			var oldAnnotations;
			if ( bestOffset === null ) {
				oldAnnotations = new ve.dm.AnnotationSet( modelData.getStore() );
			} else {
				oldAnnotations = modelData.getInsertionAnnotationsFromRange(
					new ve.Range( bestOffset ),
					true
				);
			}
			// For each element in new order, add applicable old annotation, or
			// (if whitelisted) newly-created annotation.
			// TODO: this can potentially duplicate existing non-adjacent
			// annotations. Sometimes this could be required behaviour, e.g. for
			// directionality spans; in other situations it would be cleaner to
			// duplicate.
			annotations = new ve.dm.AnnotationSet( modelData.getStore() );
			for ( j = 0, jLen = newChunk.elements.length; j < jLen; j++ ) {
				var element = newChunk.elements[ j ];
				// Recover the node from jQuery data store. This can only break if the browser
				// completely rebuilds the node, but should work in cases like typing into
				// collapsed links because nails ensure the link is never completely empty.
				var view = $( element ).data( 'view' );
				var ann;
				if ( view ) {
					ann = view.getModel();
				} else {
					// No view: new annotation element (or replacement one):
					// see https://phabricator.wikimedia.org/T116269 and
					// https://code.google.com/p/chromium/issues/detail?id=546461
					var modelClass = ve.dm.modelRegistry.lookup(
						ve.dm.modelRegistry.matchElement( element )
					);
					if ( !( modelClass && modelClass.prototype instanceof ve.dm.Annotation ) ) {
						// Erroneous element; nothing we can do with it
						continue;
					}
					ann = ve.dm.annotationFactory.createFromElement(
						modelClass.static.toDataElement( [ element ], ve.dm.converter )
					);
					var oldAnn = oldAnnotations.getComparable( ann );
					if ( oldAnn ) {
						ann = oldAnn;
					} else if ( !ann.constructor.static.inferFromView ) {
						// New and un-whitelisted: drop the annotation
						continue;
					}
				}
				annotations.add( ann, annotations.getLength() );
			}
		}
		ve.dm.Document.static.addAnnotationsToData( data, annotations );
		ve.batchPush( newData, data );
	}

	return ve.dm.TransactionBuilder.static.newFromReplacement( modelDoc, removeRange, newData );
};
