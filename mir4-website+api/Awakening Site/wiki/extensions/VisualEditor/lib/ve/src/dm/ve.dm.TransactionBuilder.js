/*!
 * VisualEditor DataModel Transaction builder class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Transaction builder: build transactions based on current ve.dm.Document state
 *
 * @class
 * @constructor
 */
ve.dm.TransactionBuilder = function VeDmTransactionBuilder() {
	// TODO: rewrite to store the current ve.dm.Document as a property
	this.transaction = new ve.dm.Transaction();
};

/* Inheritance */

OO.initClass( ve.dm.TransactionBuilder );

/* Static Methods */

/**
 * Generate a transaction that replaces data in a range.
 *
 * @param {ve.dm.Document} doc Document in pre-transaction state
 * @param {ve.Range} range Range of data to remove
 * @param {Array} data Data to insert
 * @param {boolean} [removeMetadata=false] Remove metadata instead of collapsing it
 * @return {ve.dm.Transaction} Transaction that replaces data
 * @throws {Error} Invalid range
 */
ve.dm.TransactionBuilder.static.newFromReplacement = function ( doc, range, data, removeMetadata ) {
	var txBuilder = new ve.dm.TransactionBuilder();
	var endOffset = txBuilder.pushRemoval( doc, 0, range, removeMetadata );
	endOffset = txBuilder.pushInsertion( doc, endOffset, endOffset, data );
	txBuilder.pushFinalRetain( doc, endOffset );
	return txBuilder.getTransaction();
};

/**
 * Generate a transaction that inserts data at an offset.
 *
 * @static
 * @param {ve.dm.Document} doc Document in pre-transaction state
 * @param {number} offset Offset to insert at
 * @param {Array} data Data to insert
 * @return {ve.dm.Transaction} Transaction that inserts data
 */
ve.dm.TransactionBuilder.static.newFromInsertion = function ( doc, offset, data ) {
	var txBuilder = new ve.dm.TransactionBuilder(),
		endOffset = txBuilder.pushInsertion( doc, 0, offset, data );
	// Retain to end of document, if needed (for completeness)
	txBuilder.pushFinalRetain( doc, endOffset );
	return txBuilder.getTransaction();
};

/**
 * Generate a transaction that removes data from a range.
 *
 * There are three possible results from a removal:
 *
 * - Remove content only
 *    - Occurs when the range starts and ends on elements of different type, depth or ancestry
 * - Remove entire elements and their content
 *    - Occurs when the range spans across an entire element
 * - Merge two elements by removing the end of one and the beginning of another
 *    - Occurs when the range starts and ends on elements of similar type, depth and ancestry
 *
 * This function uses the following logic to decide what to actually remove:
 *
 * 1. Elements are only removed if range being removed covers the entire element
 * 2. Elements can only be merged if {@link ve.dm.Node#canBeMergedWith} returns true
 * 3. Merges take place at the highest common ancestor
 *
 * @param {ve.dm.Document} doc Document in pre-transaction state
 * @param {ve.Range} range Range of data to remove
 * @param {boolean} [removeMetadata=false] Remove metadata instead of collapsing it
 * @return {ve.dm.Transaction} Transaction that removes data
 * @throws {Error} Invalid range
 */
ve.dm.TransactionBuilder.static.newFromRemoval = function ( doc, range, removeMetadata ) {
	var txBuilder = new ve.dm.TransactionBuilder(),
		endOffset = txBuilder.pushRemoval( doc, 0, range, removeMetadata );

	// Ensure no transaction leaves the document in a completely empty state
	if ( range.start === 0 && range.end >= doc.getDocumentRange().end ) {
		endOffset = txBuilder.pushInsertion( doc, endOffset, endOffset, [
			{ type: 'paragraph' },
			{ type: '/paragraph' }
		] );
	}

	// Retain to end of document, if needed (for completeness)
	txBuilder.pushFinalRetain( doc, endOffset );
	return txBuilder.getTransaction();
};

/**
 * Build a transaction that inserts the contents of a document at a given offset.
 *
 * This is typically used to merge changes to a document slice back into the main document. If newDoc
 * is a document slice of doc, it's assumed that there were no changes to doc's internal list since
 * the slice, so any differences between internal items that doc and newDoc have in common will
 * be resolved in newDoc's favor.
 *
 * @param {ve.dm.Document} doc Main document in the state to which the transaction start applies
 * @param {number} offset Offset to insert at
 * @param {ve.dm.Document} newDoc Document to insert
 * @param {ve.Range} [newDocRange] Range from the new document to insert (defaults to entire document)
 * @return {ve.dm.Transaction} Transaction that inserts the nodes and updates the internal list
 */
ve.dm.TransactionBuilder.static.newFromDocumentInsertion = function ( doc, offset, newDoc, newDocRange ) {
	var listNode = doc.internalList.getListNode(),
		listNodeRange = listNode.getRange(),
		newListNode = newDoc.internalList.getListNode(),
		newListNodeRange = newListNode.getRange(),
		newListNodeOuterRange = newListNode.getOuterRange();

	var data;
	if ( newDocRange ) {
		data = new ve.dm.ElementLinearData( doc.getStore(), newDoc.getData( newDocRange, true ) );
	} else {
		// Get the data, but skip over the internal list
		data = new ve.dm.ElementLinearData( doc.getStore(),
			newDoc.getData( new ve.Range( 0, newListNodeOuterRange.start ), true ).concat(
				newDoc.getData( new ve.Range( newListNodeOuterRange.end, newDoc.data.getLength() ), true )
			)
		);
	}

	// Merge the stores
	doc.getStore().merge( newDoc.getStore() );

	var listMerge = doc.internalList.merge( newDoc.internalList, newDoc.origInternalListLength || 0 );
	// Remap the indexes in the data
	data.remapInternalListIndexes( listMerge.mapping, doc.internalList );
	// Get data for the new internal list
	var linearData, listData;
	if ( newDoc.origInternalListLength !== null ) {
		// newDoc is a document slice based on doc, so all the internal list items present in doc
		// when it was cloned are also in newDoc. We need to get the newDoc version of these items
		// so that changes made in newDoc are reflected.
		var oldEndOffset, newEndOffset;
		if ( newDoc.origInternalListLength > 0 ) {
			oldEndOffset = doc.internalList.getItemNode( newDoc.origInternalListLength - 1 ).getOuterRange().end;
			newEndOffset = newDoc.internalList.getItemNode( newDoc.origInternalListLength - 1 ).getOuterRange().end;
		} else {
			oldEndOffset = listNodeRange.start;
			newEndOffset = newListNodeRange.start;
		}
		linearData = new ve.dm.ElementLinearData(
			doc.getStore(),
			newDoc.getData( new ve.Range( newListNodeRange.start, newEndOffset ), true )
		);
		listData = linearData.data
			.concat( doc.getData( new ve.Range( oldEndOffset, listNodeRange.end ), true ) );
	} else {
		// newDoc is brand new, so use doc's internal list as a base
		listData = doc.getData( listNodeRange, true );
	}
	var i, len;
	for ( i = 0, len = listMerge.newItemRanges.length; i < len; i++ ) {
		linearData = new ve.dm.ElementLinearData(
			doc.getStore(),
			newDoc.getData( listMerge.newItemRanges[ i ], true )
		);
		listData = listData.concat( linearData.data );
	}

	var txBuilder = new ve.dm.TransactionBuilder();

	var insertion;
	if ( offset <= listNodeRange.start ) {
		// offset is before listNodeRange
		// First replace the node, then the internal list

		// Fix up the node insertion
		insertion = doc.fixupInsertion( data.data, offset );
		txBuilder.pushRetain( insertion.offset );
		txBuilder.pushReplacement( doc, insertion.offset, insertion.remove, insertion.data, true );
		txBuilder.pushRetain( listNodeRange.start - ( insertion.offset + insertion.remove ) );
		txBuilder.pushReplacement( doc, listNodeRange.start, listNodeRange.end - listNodeRange.start,
			listData, true
		);
		txBuilder.pushFinalRetain( doc, listNodeRange.end );
	} else if ( offset >= listNodeRange.end ) {
		// offset is after listNodeRange
		// First replace the internal list, then the node

		// Fix up the node insertion
		insertion = doc.fixupInsertion( data.data, offset );
		txBuilder.pushRetain( listNodeRange.start );
		txBuilder.pushReplacement( doc, listNodeRange.start, listNodeRange.end - listNodeRange.start,
			listData, true
		);
		txBuilder.pushRetain( insertion.offset - listNodeRange.end );
		txBuilder.pushReplacement( doc, insertion.offset, insertion.remove, insertion.data, true );
		txBuilder.pushFinalRetain( doc, insertion.offset + insertion.remove );
	} else if ( offset >= listNodeRange.start && offset <= listNodeRange.end ) {
		// offset is within listNodeRange
		// Merge data into listData, then only replace the internal list
		// Find the internalItem we are inserting into
		i = 0;
		// Find item node in doc
		var spliceItemRange;
		while (
			( spliceItemRange = doc.internalList.getItemNode( i ).getRange() ) &&
			offset > spliceItemRange.end
		) {
			i++;
		}

		var spliceListNodeRange;
		if ( newDoc.origInternalListLength !== null ) {
			// Get spliceItemRange from newDoc
			spliceItemRange = newDoc.internalList.getItemNode( i ).getRange();
			spliceListNodeRange = newListNodeRange;
		} else {
			// Get spliceItemRange from doc; the while loop has already set it
			spliceListNodeRange = listNodeRange;
		}
		ve.batchSplice( listData, spliceItemRange.start - spliceListNodeRange.start,
			spliceItemRange.end - spliceItemRange.start, data.data );

		txBuilder.pushRetain( listNodeRange.start );
		txBuilder.pushReplacement( doc, listNodeRange.start, listNodeRange.end - listNodeRange.start,
			listData, true
		);
		txBuilder.pushFinalRetain( doc, listNodeRange.end );
	}
	return txBuilder.getTransaction();
};

/**
 * Generate a transaction that changes one or more attributes.
 *
 * @static
 * @param {ve.dm.Document} doc Document in pre-transaction state
 * @param {number} offset Offset of element
 * @param {Object.<string,Mixed>} attr List of attribute key and value pairs, use undefined value
 *  to remove an attribute
 * @return {ve.dm.Transaction} Transaction that changes an element
 * @throws {Error} Cannot set attributes to non-element data
 * @throws {Error} Cannot set attributes on closing element
 */
ve.dm.TransactionBuilder.static.newFromAttributeChanges = function ( doc, offset, attr ) {
	var txBuilder = new ve.dm.TransactionBuilder(),
		data = doc.getData();
	// Verify element exists at offset
	if ( data[ offset ].type === undefined ) {
		throw new Error( 'Cannot set attributes to non-element data' );
	}
	// Verify element is not a closing
	if ( data[ offset ].type.charAt( 0 ) === '/' ) {
		throw new Error( 'Cannot set attributes on closing element' );
	}
	// Retain up to element
	txBuilder.pushRetain( offset );
	// Change attributes
	txBuilder.pushAttributeChanges( attr, data[ offset ].attributes || {} );
	// Retain to end of document
	txBuilder.pushFinalRetain( doc, offset );
	return txBuilder.getTransaction();
};

/**
 * Generate a transaction that annotates content.
 *
 * @static
 * @param {ve.dm.Document} doc Document in pre-transaction state
 * @param {ve.Range} range Range to annotate
 * @param {string} method Annotation mode
 *  - `set`: Adds annotation to all content in range
 *  - `clear`: Removes instances of annotation from content in range
 * @param {ve.dm.Annotation} annotation Annotation to set or clear
 * @return {ve.dm.Transaction} Transaction that annotates content
 */
ve.dm.TransactionBuilder.static.newFromAnnotation = function ( doc, range, method, annotation ) {
	var clear = method === 'clear',
		run = null,
		runs = [],
		data = doc.data,
		hash = doc.getStore().hash( annotation ),
		insideContentNode = false,
		ignoreChildrenDepth = 0;

	var i;

	/**
	 * Return the array index of the annotation in the annotation array for the offset
	 *
	 * @param {number} offset Document offset
	 * @return {number} Index in the annotation array (-1 if not present)
	 */
	function findAnnotation() {
		return data.getAnnotationHashesFromOffset( i ).lastIndexOf( hash );
	}

	function startRun() {
		run = {
			start: i,
			end: null,
			data: null,
			spliceAt: clear ? findAnnotation() : null
		};
	}

	function endRun() {
		run.end = i;
		if ( !clear ) {
			run.spliceAt = data.getCommonAnnotationArrayLength(
				new ve.Range( run.start, i )
			);
		}
		run.data = doc.getData( new ve.Range( run.start, run.end ) );
		for ( var j = 0, jLen = run.data.length; j < jLen; j++ ) {
			var item = ve.copy( run.data[ j ] );
			var anns = new ve.dm.AnnotationSet(
				doc.getStore(),
				ve.dm.ElementLinearData.static.getAnnotationHashesFromItem( item )
			);
			if ( clear ) {
				anns.remove( annotation );
			} else {
				anns.add( annotation, run.spliceAt );
			}
			item = ve.dm.ElementLinearData.static.replaceAnnotationHashesForItem( item, anns.getHashes() );
			run.data[ j ] = item;
		}
		runs.push( run );
		run = null;
	}

	var covered, arrayIndex;
	// Iterate over all data in range, finding "runs" to annotate
	for ( i = range.start; i < range.end; i++ ) {
		if ( data.isElementData( i ) && ve.dm.nodeFactory.shouldIgnoreChildren( data.getType( i ) ) ) {
			ignoreChildrenDepth += data.isOpenElementData( i ) ? 1 : -1;
		}
		var annotatable = !ignoreChildrenDepth && data.canTakeAnnotationAtOffset( i, annotation );

		if (
			!annotatable ||
			( insideContentNode && !data.isCloseElementData( i ) )
		) {
			// Structural element opening or closing, or entering a content node
			if ( run ) {
				endRun();
			}
			continue;
		}

		if ( data.isCloseElementData( i ) ) {
			// Content closing, skip
			insideContentNode = false;
			continue;
		}

		if ( insideContentNode ) {
			continue;
		}

		// Else we're annotatable, not inside a content node, and have character or
		// content element opening
		if ( data.isElementData( i ) ) {
			insideContentNode = true;
		}
		if ( !clear ) {
			// Don't re-apply matching annotation
			covered = data.getAnnotationsFromOffset( i ).containsComparable( annotation );
		} else {
			// Expect comparable annotations to be removed individually otherwise
			// we might try to remove more than one annotation per character, which
			// a single transaction can't do.
			arrayIndex = findAnnotation();
		}
		if ( run && (
			( clear && arrayIndex !== run.spliceAt ) ||
			( !clear && covered )
		) ) {
			// Inside a run and:
			// - if clearing, annotation is absent entirely or at a new array index
			// - if setting, Matching annotation is present already
			endRun();
		}
		if ( !run && (
			( clear && arrayIndex > -1 ) ||
			( !clear && !covered )
		) ) {
			// Not inside a run, and:
			// - if clearing, annotation is present
			// - if setting, annotation is not present
			startRun();
		}
	}
	if ( run ) {
		endRun();
	}

	var txBuilder = new ve.dm.TransactionBuilder();
	var iLen;
	for ( i = 0, iLen = runs.length; i < iLen; i++ ) {
		run = runs[ i ];
		txBuilder.pushRetain( run.start - ( i > 0 ? runs[ i - 1 ].end : 0 ) );
		txBuilder.pushReplacement( doc, run.start, run.end - run.start, run.data, false );
	}
	txBuilder.pushFinalRetain( doc, runs.length > 0 ? runs[ runs.length - 1 ].end : 0 );
	return txBuilder.getTransaction();
};

/**
 * Generate a transaction that converts elements that can contain content.
 *
 * @static
 * @param {ve.dm.Document} doc Document in pre-transaction state
 * @param {ve.Range} range Range to convert
 * @param {string} type Symbolic name of element type to convert to
 * @param {Object} [attr] Attributes to initialize element with
 * @param {Object} [internal] Internal attributes to initialize element with
 * @return {ve.dm.Transaction} Transaction that converts content branches
 */
ve.dm.TransactionBuilder.static.newFromContentBranchConversion = function ( doc, range, type, attr, internal ) {
	var txBuilder = new ve.dm.TransactionBuilder(),
		selection = doc.selectNodes( range, 'leaves' ),
		opening = { type: type },
		closing = { type: '/' + type },
		previousBranch,
		previousBranchOuterRange;

	// Add attributes to opening if needed
	if ( attr ) {
		if ( !ve.isEmptyObject( attr ) ) {
			opening.attributes = attr;
		}
	} else {
		// Set to empty object for comparison
		attr = {};
	}
	if ( internal ) {
		if ( !ve.isEmptyObject( internal ) ) {
			opening.internal = internal;
		}
	} else {
		// Set to empty object for comparison
		internal = {};
	}

	// Replace the wrappings of each content branch in the range
	for ( var i = 0, len = selection.length; i < len; i++ ) {
		var selected = selection[ i ];
		// TODO: This duplicates the logic of ve.dm.SurfaceFragment#getSelectedLeafNodes
		// This could perhaps be a new mode in ve.Document#selectNodes?
		if ( !( len === 1 || !selected.range || !selected.range.isCollapsed() ) ) {
			continue;
		}

		var branch = selected.node.isContent() ? selected.node.getParent() : selected.node;
		if ( branch.canContainContent() ) {
			// Skip branches that are already of the target type and have matching attributes
			if (
				branch.getType() === type &&
				ve.compare( attr, branch.getAttributes() ) &&
				ve.compare( internal, branch.element.internal || {} )
			) {
				continue;
			}
			var branchOuterRange = branch.getOuterRange();
			// Don't convert the same branch twice
			if ( branch === previousBranch ) {
				continue;
			}

			// Retain up to this branch, considering where the previous one left off
			txBuilder.pushRetain(
				branchOuterRange.start - ( previousBranch ? previousBranchOuterRange.end : 0 )
			);
			if (
				branch.getType() === type &&
				ve.compare( internal, branch.element.internal || {} )
			) {
				// Same type and internal, different attributes, so we only need an attribute change
				txBuilder.pushAttributeChanges( attr, branch.getAttributes() );
				// Retain the branch, including its opening and closing
				txBuilder.pushRetain( branch.getOuterLength() );
			} else {
				// Types or internal differ, so we need to replace the opening and closing
				// Replace the opening
				txBuilder.pushReplacement( doc, branchOuterRange.start, 1, [ ve.copy( opening ) ] );
				// Retain the contents
				txBuilder.pushRetain( branch.getLength() );
				// Replace the closing
				txBuilder.pushReplacement( doc, branchOuterRange.end - 1, 1, [ ve.copy( closing ) ] );
			}
			// Remember this branch and its range for next time
			previousBranch = branch;
			previousBranchOuterRange = branchOuterRange;
		}
	}
	// Retain until the end
	txBuilder.pushFinalRetain( doc, previousBranch ? previousBranchOuterRange.end : 0 );
	return txBuilder.getTransaction();
};

/**
 * Generate a transaction that wraps, unwraps or replaces structure.
 *
 * The unwrap parameters are checked against the actual model data, and
 * an exception is thrown if the type fields don't match. This means you
 * can omit attributes from the unwrap parameters, those are automatically
 * picked up from the model data instead.
 *
 * NOTE: This function currently does not fix invalid parent/child relationships, so it will
 * happily convert paragraphs to listItems without wrapping them in a list if that's what you
 * ask it to do. We'll probably fix this later but for now the caller is responsible for giving
 * valid instructions.
 *
 * Changing a paragraph to a header:
 *     Before: [ {type: 'paragraph'}, 'a', 'b', 'c', {type: '/paragraph'} ]
 *     newFromWrap( new ve.Range( 1, 4 ), [ {type: 'paragraph'} ], [ {type: 'heading', level: 1 } ] );
 *     After: [ {type: 'heading', level: 1 }, 'a', 'b', 'c', {type: '/heading'} ]
 *
 * Changing a set of paragraphs to a list:
 *     Before: [ {type: 'paragraph'}, 'a', {type: '/paragraph'}, {'type':'paragraph'}, 'b', {'type':'/paragraph'} ]
 *     newFromWrap( new ve.Range( 0, 6 ), [], [ {type: 'list' } ], [], [ {type: 'listItem', attributes: {styles: ['bullet']}} ] );
 *     After: [ {type: 'list'}, {type: 'listItem', attributes: {styles: ['bullet']}}, {'type':'paragraph'} 'a',
 *              {type: '/paragraph'}, {type: '/listItem'}, {type: 'listItem', attributes: {styles: ['bullet']}},
 *              {type: 'paragraph'}, 'b', {type: '/paragraph'}, {type: '/listItem'}, {type: '/list'} ]
 *
 * @param {ve.dm.Document} doc Document in pre-transaction state
 * @param {ve.Range} range Range to wrap/unwrap/replace around
 * @param {Array} unwrapOuter Opening elements to unwrap. These must be immediately *outside* the range
 * @param {Array} wrapOuter Opening elements to wrap around the range
 * @param {Array} unwrapEach Opening elements to unwrap from each top-level element in the range
 * @param {Array} wrapEach Opening elements to wrap around each top-level element in the range
 * @return {ve.dm.Transaction}
 */
ve.dm.TransactionBuilder.static.newFromWrap = function ( doc, range, unwrapOuter, wrapOuter, unwrapEach, wrapEach ) {
	var txBuilder = new ve.dm.TransactionBuilder(),
		depth = 0;

	/**
	 * Match items before/after an offset, skipping over MetaItems
	 *
	 * @param {string} direction Direction of match, backwards|forwards
	 * @param {number} offset First boundary for the matching items
	 * @param {Object[]} matchList List of objects with .type properties to compare
	 * @param {string} matchName Description of the match, for error messages
	 * @return {number} Other slice boundary for the matching items
	 * @throws {Error} Unmatched item foo in matchName [(found bar)]
	 */
	function match( direction, offset, matchList, matchName ) {
		var start, stop, step;
		if ( direction === 'forwards' ) {
			start = 0;
			stop = matchList.length;
			step = 1;
			// Inclusive 'from' slice boundary
			offset--;
		} else {
			start = matchList.length - 1;
			stop = -1;
			step = -1;
		}
		for ( var j = start; j !== stop; j += step ) {
			var item;
			// Move to next item, skipping MetaItems
			while ( true ) {
				offset += step;
				item = doc.data.data[ offset ];
				if ( !(
					ve.dm.LinearData.static.isElementData( item ) &&
					ve.dm.nodeFactory.isMetaData(
						ve.dm.LinearData.static.getType( item )
					)
				) ) {
					break;
				}
			}
			if ( !item || item.type !== matchList[ j ].type ) {
				throw new Error( 'Unmatched item ' + matchList[ j ].type + ' in ' +
					matchName + ' (found ' + ( item && item.type ) + ')' );
			}
		}
		if ( direction === 'forwards' ) {
			// Exclusive 'to' slice boundary
			offset++;
		}
		return offset;
	}

	// Function to generate arrays of closing elements in reverse order
	function closingArray( openings ) {
		var j, jlen,
			closings = [];
		for ( j = 0, jlen = openings.length; j < jlen; j++ ) {
			closings[ closings.length ] = { type: '/' + openings[ jlen - j - 1 ].type };
		}
		return closings;
	}

	var closingUnwrapEach = closingArray( unwrapEach );
	var closingWrapEach = closingArray( wrapEach );

	// TODO: check for and fix nesting validity like fixupInsertion does

	// Verify the data before range.start matches unwrapOuter, and find where to retain up to
	var ptr = match( 'backwards', range.start, unwrapOuter, 'unwrapOuter' );
	txBuilder.pushRetain( ptr );
	// Replace wrapper (retaining any metadata)
	txBuilder.pushReplacement( doc, ptr, range.start - ptr, ve.copy( wrapOuter ) );
	ptr = range.start;

	if ( wrapEach.length === 0 && unwrapEach.length === 0 ) {
		// There is no wrapEach/unwrapEach to be done, just retain
		// up to the end of the range
		txBuilder.pushRetain( range.end - range.start );
		ptr = range.end;
	} else {
		// Visit each top-level child and wrap/unwrap it
		// TODO figure out if we should use the tree/node functions here
		// rather than iterating over offsets, it may or may not be faster
		var startOffset;
		for ( var i = range.start; i < range.end; i++ ) {
			if ( !doc.data.isElementData( i ) ) {
				continue;
			}
			if ( doc.data.isOpenElementData( i ) ) {
				depth++;
				if (
					depth !== 1 ||
					ve.dm.nodeFactory.isMetaData(
						ve.dm.LinearData.static.getType(
							doc.data.data[ i ]
						)
					)
				) {
					continue;
				}
				// Retain any outstanding top level items (which must be MetaItems)
				txBuilder.pushRetain( i - ptr );
				// This is the start of a top-level element
				ptr = match( 'forwards', i, unwrapEach, 'unwrapEach' );
				// Replace wrapper (retaining any metadata)
				txBuilder.pushReplacement( doc, i, ptr - i, ve.copy( wrapEach ) );
				startOffset = ptr;
				continue;
			}
			// Else this is a closing element
			depth--;
			if (
				depth !== 0 ||
				ve.dm.nodeFactory.isMetaData(
					ve.dm.LinearData.static.getType( doc.data.data[ i ] )
				)
			) {
				continue;
			}
			// This is the end of a top-level element
			ptr = match( 'backwards', i + 1, closingUnwrapEach, 'closingUnwrapEach' );
			// ptr is the range end of the unwrapped data (i.e. the offset after it)
			txBuilder.pushRetain( ptr - startOffset );
			// Replace the closing elements (retaining any metadata)
			txBuilder.pushReplacement( doc, ptr, i + 1 - ptr, ve.copy( closingWrapEach ) );
			ptr = i + 1;
		}
	}

	// Retain any outstanding top level items (which must be MetaItems)
	txBuilder.pushRetain( range.end - ptr );

	ptr = match( 'forwards', range.end, closingArray( unwrapOuter ), 'unwrapOuter' );
	txBuilder.pushReplacement( doc, range.end, ptr - range.end, closingArray( wrapOuter ) );

	// Retain up to the end of the document
	txBuilder.pushFinalRetain( doc, ptr );

	return txBuilder.getTransaction();
};

/* Methods */

/**
 * Get the transaction
 *
 * @return {ve.dm.Transaction} The transaction
 */
ve.dm.TransactionBuilder.prototype.getTransaction = function () {
	return this.transaction;
};

/**
 * Add a final retain operation to finish off a transaction (internal helper).
 *
 * @private
 * @param {ve.dm.Document} doc The document in the state to which the transaction applies
 * @param {number} offset Final offset edited by the transaction up to this point.
 */
ve.dm.TransactionBuilder.prototype.pushFinalRetain = function ( doc, offset ) {
	if ( offset < doc.data.getLength() ) {
		this.pushRetain( doc.data.getLength() - offset );
	}
};

/**
 * Add a retain operation.
 *
 * @param {number} length Length of content data to retain
 * @throws {Error} Cannot retain backwards
 */
ve.dm.TransactionBuilder.prototype.pushRetain = function ( length ) {
	if ( length < 0 ) {
		throw new Error( 'Invalid retain length, cannot retain backwards:' + length );
	}
	if ( length ) {
		var end = this.transaction.operations.length - 1;
		if (
			this.transaction.operations.length &&
			this.transaction.operations[ end ].type === 'retain'
		) {
			this.transaction.operations[ end ].length += length;
		} else {
			this.transaction.pushRetainOp( length );
		}
	}
};

/**
 * Adds a replace op to remove the desired range and, where required, splices in retain ops
 * to prevent the deletion of undeletable nodes.
 *
 * @param {ve.dm.Document} doc The document in the state to which the transaction applies
 * @param {number} removeStart Offset to start removing from
 * @param {number} removeEnd Offset to remove to
 * @param {boolean} [removeMetadata=false] Remove metadata instead of collapsing it
 * @return {number} End offset of the removal
 */
ve.dm.TransactionBuilder.prototype.addSafeRemoveOps = function ( doc, removeStart, removeEnd, removeMetadata ) {
	var retainStart = removeStart,
		undeletableStackDepth = 0;
	// Iterate over removal range and use a stack counter to determine if
	// we are inside an undeletable node
	var queuedRetain;
	for ( var i = removeStart; i < removeEnd; i++ ) {
		if ( doc.data.isElementData( i ) && !ve.dm.nodeFactory.isNodeDeletable( doc.data.getType( i ) ) ) {
			if ( !doc.data.isCloseElementData( i ) ) {
				if ( undeletableStackDepth === 0 ) {
					if ( queuedRetain ) {
						this.pushRetain( queuedRetain );
					}
					this.pushReplacement( doc, removeStart, i - removeStart, [], removeMetadata );
					retainStart = i;
				}
				undeletableStackDepth++;
			} else {
				undeletableStackDepth--;
				if ( undeletableStackDepth === 0 ) {
					queuedRetain = i + 1 - retainStart;
					removeStart = i + 1;
				}
			}
		}
	}
	if ( removeEnd - removeStart ) {
		if ( queuedRetain ) {
			this.pushRetain( queuedRetain );
		}
		this.pushReplacement( doc, removeStart, removeEnd - removeStart, [], removeMetadata );
		retainStart = removeEnd;
	}
	return retainStart;
};

/**
 * Add a replace operation (internal helper).
 *
 * @private
 * @param {Array} remove Data removed.
 * @param {Array} insert Data to insert.
 * @param {number} [insertedDataOffset] Inserted data offset
 * @param {number} [insertedDataLength] Inserted data length
 */
ve.dm.TransactionBuilder.prototype.pushReplaceInternal = function ( remove, insert, insertedDataOffset, insertedDataLength ) {
	if ( remove.length === 0 && insert.length === 0 ) {
		return; // No-op
	}
	this.transaction.pushReplaceOp( remove, insert, insertedDataOffset, insertedDataLength );
};

/**
 * Add a replace operation
 *
 * If metadata is collapsed instead of removed, it will be shifted backwards if necessary to
 * reach a legal position for metadata in the new document structure.
 *
 * @param {ve.dm.Document} doc The document in the state to which the transaction applies
 * @param {number} offset Offset to start at
 * @param {number} removeLength Number of data items to remove
 * @param {Array} insert Data to insert
 * @param {boolean} removeMetadata Remove metadata instead of collapsing it
 * @param {number} [insertedDataOffset] Offset of the originally inserted data in the resulting operation data
 * @param {number} [insertedDataLength] Length of the originally inserted data in the resulting operation data
 */
ve.dm.TransactionBuilder.prototype.pushReplacement = function ( doc, offset, removeLength, insert, removeMetadata, insertedDataOffset, insertedDataLength ) {
	var remove = doc.getData( new ve.Range( offset, offset + removeLength ) );
	var collapse = removeMetadata ? [] : remove.filter( function ( item ) {
		var type = ve.dm.LinearData.static.isElementData( item ) &&
			ve.dm.LinearData.static.getType( item );
		return type &&
			ve.dm.nodeFactory.isMetaData( type ) &&
			!ve.dm.nodeFactory.isRemovableMetaData( type );
	} );
	if ( removeLength === collapse.length && insert.length === 0 ) {
		// Don't push no-ops
		return;
	}

	if ( collapse.length > 0 ) {
		// Push collapsed metadata, moved backwards if this position has become invalid
		// for metadata
		this.pushMeta( doc, offset, collapse );
	}
	// Merge with previous replace, if any (which may have come from the pushMeta above)
	var lastOp = this.transaction.operations[ this.transaction.operations.length - 1 ];
	if (
		lastOp &&
		lastOp.type === 'replace' &&
		!lastOp.insertedDataOffset &&
		!insertedDataOffset
	) {
		this.transaction.operations.pop();
		remove = lastOp.remove.concat( remove );
		insert = lastOp.insert.concat( insert );
	}

	var op = {
		type: 'replace',
		remove: remove,
		insert: insert
	};
	if ( insertedDataOffset !== undefined ) {
		op.insertedDataOffset = insertedDataOffset;
	}
	if ( insertedDataLength !== undefined ) {
		op.insertedDataLength = insertedDataLength;
	}
	this.transaction.operations.push( op );
};

/**
 * Add an element attribute change operation.
 *
 * @param {string} key Name of attribute to change
 * @param {Mixed} from Value change attribute from, or undefined if not previously set
 * @param {Mixed} to Value to change attribute to, or undefined to remove
 */
ve.dm.TransactionBuilder.prototype.pushReplaceElementAttribute = function ( key, from, to ) {
	this.transaction.pushAttributeOp( key, from, to );
};

/**
 * Add a series of element attribute change operations.
 *
 * @param {Object} changes Object mapping attribute names to new values
 * @param {Object} oldAttrs Object mapping attribute names to old values
 */
ve.dm.TransactionBuilder.prototype.pushAttributeChanges = function ( changes, oldAttrs ) {
	for ( var key in changes ) {
		if ( oldAttrs[ key ] !== changes[ key ] ) {
			this.pushReplaceElementAttribute( key,
				ve.copy( oldAttrs[ key ] ),
				ve.copy( changes[ key ] )
			);
		}
	}
};

/**
 * Internal helper method for newFromInsertion and newFromReplacement.
 * Adds an insertion to an existing transaction object.
 *
 * @private
 * @param {ve.dm.Document} doc The document in the state to which the transaction applies
 * @param {number} currentOffset Offset up to which the transaction has gone already
 * @param {number} insertOffset Offset to insert at
 * @param {Array} data Linear model data to insert
 * @return {number} End offset of the insertion
 */
ve.dm.TransactionBuilder.prototype.pushInsertion = function ( doc, currentOffset, insertOffset, data ) {
	// Fix up the insertion
	var insertion = doc.fixupInsertion( data, insertOffset );
	// Retain up to insertion point, if needed
	this.pushRetain( insertion.offset - currentOffset );
	// Insert data
	this.pushReplacement(
		doc, insertion.offset, insertion.remove, insertion.data, false,
		insertion.insertedDataOffset, insertion.insertedDataLength
	);
	return insertion.offset + insertion.remove;
};

/**
 * Internal helper method for newFromRemoval and newFromReplacement.
 * Adds a removal to an existing transaction object.
 *
 * @private
 * @param {ve.dm.Document} doc The document in the state to which the transaction applies
 * @param {number} currentOffset Offset up to which the transaction has gone already
 * @param {ve.Range} range Range to remove
 * @param {boolean} [removeMetadata=false] Remove metadata instead of collapsing it
 * @return {number} End offset of the removal
 */
ve.dm.TransactionBuilder.prototype.pushRemoval = function ( doc, currentOffset, range, removeMetadata ) {
	var offset = currentOffset,
		removeStart = null,
		removeEnd = null;
	// Validate range
	if ( range.isCollapsed() ) {
		// Empty range, nothing to remove
		this.pushRetain( range.start - currentOffset );
		return range.start;
	}
	// Select nodes and validate selection
	var selection = doc.selectNodes( range, 'covered' );
	if ( selection.length === 0 ) {
		// Empty selection? Something is wrong!
		throw new Error( 'Invalid range, cannot remove from ' + range.start + ' to ' + range.end );
	}
	var first = selection[ 0 ];
	var last = selection[ selection.length - 1 ];
	// If the first and last node are mergeable, merge them
	if ( first.node.canBeMergedWith( last.node ) ) {
		if ( !first.range && !last.range ) {
			// First and last node are both completely covered, remove them
			removeStart = first.nodeOuterRange.start;
			removeEnd = last.nodeOuterRange.end;
		} else {
			// Either the first node or the last node is partially covered, so remove
			// the selected content. The other node might be fully covered, in which case
			// we remove its contents (nodeRange). For fully covered content nodes, we must
			// remove the entire node (nodeOuterRange).
			removeStart = (
				first.range ||
				( first.node.isContent() ? first.nodeOuterRange : first.nodeRange )
			).start;
			removeEnd = (
				last.range ||
				( last.node.isContent() ? last.nodeOuterRange : last.nodeRange )
			).end;
		}
		this.pushRetain( removeStart - currentOffset );
		removeEnd = this.addSafeRemoveOps( doc, removeStart, removeEnd, removeMetadata );
		// All done
		return removeEnd;
	}

	// The selection wasn't mergeable, so remove nodes that are completely covered, and strip
	// nodes that aren't
	for ( var i = 0; i < selection.length; i++ ) {
		var nodeStart, nodeEnd;
		if ( !selection[ i ].range ) {
			// Entire node is covered, remove it
			nodeStart = selection[ i ].nodeOuterRange.start;
			nodeEnd = selection[ i ].nodeOuterRange.end;
		} else {
			// Part of the node is covered, remove that range
			nodeStart = selection[ i ].range.start;
			nodeEnd = selection[ i ].range.end;
		}

		// Merge contiguous removals. Only apply a removal when a gap appears, or at the
		// end of the loop
		if ( removeEnd === null ) {
			// First removal
			removeStart = nodeStart;
			removeEnd = nodeEnd;
		} else if ( removeEnd === nodeStart ) {
			// Merge this removal into the previous one
			removeEnd = nodeEnd;
		} else {
			// There is a gap between the previous removal and this one

			// Push the previous removal first
			this.pushRetain( removeStart - offset );
			offset = this.addSafeRemoveOps( doc, removeStart, removeEnd, removeMetadata );

			// Now start this removal
			removeStart = nodeStart;
			removeEnd = nodeEnd;
		}
	}
	// Apply the last removal, if any
	if ( removeEnd !== null ) {
		this.pushRetain( removeStart - offset );
		offset = this.addSafeRemoveOps( doc, removeStart, removeEnd, removeMetadata );
	}
	return offset;
};

/**
 * Insert meta items, as near to the current transaction end as is legal
 *
 * @param {ve.dm.Document} doc Document in pre-transaction state
 * @param {number} offset Offset of the end of the current (partial) operations list
 * @param {Array} metaItems linear data containing just meta items
 */
ve.dm.TransactionBuilder.prototype.pushMeta = function ( doc, offset, metaItems ) {
	var position = null,
		ops = this.transaction.operations,
		relDepth = 0;

	if ( ops.length === 0 ) {
		ops.push( {
			type: 'replace',
			remove: [],
			insert: metaItems
		} );
		return;
	}

	// Start at the current end of the transaction
	position = { opIndex: ops.length - 1, itemIndex: ops[ ops.length - 1 ].length };

	var op;
	// Walk backwards over the linear data as it will be after the transaction is
	// applied, tracking depth, and disregarding tags deeper than the depth at offset.
	// - If we hit a structural open tag with restricted childNodeTypes (like list) then
	// move position to just before the tag, and keep walking
	// - If we hit a structural open tag with unrestricted childNodeTypes, or the document
	// start, then position is fine: insert metaItems there and return
	findPositionLoop:
	for ( var i = ops.length - 1; i >= 0; i-- ) {
		op = ops[ i ];
		var items;
		if ( op.type === 'replace' ) {
			items = op.insert;
			offset -= op.remove.length;
		} else if ( op.type === 'retain' ) {
			items = doc.getData( new ve.Range( offset - op.length, offset ) );
			offset -= op.length;
		} else {
			// attribute/annotate: do nothing
			continue;
		}
		for ( var j = items.length - 1; j >= 0; j-- ) {
			var type = items[ j ].type;
			if ( !type || typeof type !== 'string' ) {
				continue;
			}
			if ( type.charAt( 0 ) === '/' ) {
				// Close element, so going backwards we're getting deeper
				relDepth++;
				continue;
			}
			// Else open element
			relDepth--;
			if ( relDepth >= 0 ) {
				continue;
				// TODO: This could be a long walk. Do we want to infer stuff
				// from element types we see at relDepth 0? For instance, if
				// we see a structural node at the same depth as position, with
				// unrestricted parentNodeTypes, could we perhaps infer that
				// position is a legal place for metadata without walking the
				// whole 874 miles to the start of the document?
				//
				// This is not an immediate priority because for text
				// replacement during typing (the main performance-critical use
				// case) we only have to walk back to the start of the CBN,
				// which is unlikely to be far.
			}
			// We won't let relDepth go below -1 (see below), so this element is
			// the parent of position
			if (
				!ve.dm.nodeFactory.canNodeContainContent( type ) &&
				!ve.dm.nodeFactory.getChildNodeTypes( type )
			) {
				// This is a legal parent for metadata
				break findPositionLoop;
			}
			// Else not a legal parent for metadata: move position up and continue
			position = { opIndex: i, itemIndex: j };
			relDepth = 0;
		}
	}
	op = ops[ position.opIndex ];
	if ( op.type === 'replace' ) {
		ve.batchSplice( op.insert, position.itemIndex, 0, metaItems );
		return;
	}
	// Else it's a retain op (it cannot be attribute/annotate because we skip those above)
	if ( position.itemIndex > 0 ) {
		ops.splice( position.opIndex, 0, {
			type: 'retain',
			length: position.itemIndex
		} );
		position.opIndex++;
	}
	ops.splice( position.opIndex, 0, {
		type: 'replace',
		remove: [],
		insert: metaItems
	} );
	op.length -= position.itemIndex;
	if ( op.length === 0 ) {
		ops.splice( position.opIndex + 1, 1 );
	}
	// TODO: normalize resulting consecutive replace ops?
};
