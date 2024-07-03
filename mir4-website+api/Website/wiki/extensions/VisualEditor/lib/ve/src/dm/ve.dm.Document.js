/*!
 * VisualEditor DataModel Document class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel document.
 *
 * WARNING: The data parameter is passed by reference. Do not modify a data array after passing
 * it to this constructor, and do not construct multiple Documents with the same data array. If you
 * need to do these things, make a deep copy (ve#copy) of the data array and operate on the
 * copy.
 *
 * @class
 * @extends ve.Document
 * @constructor
 * @param {Array|ve.dm.ElementLinearData} data Raw linear model data or ElementLinearData
 * @param {HTMLDocument} [htmlDocument] HTML document the data was converted from, if any.
 *  If omitted, a new document will be created. If data is an HTMLDocument, this parameter is
 *  ignored.
 * @param {ve.dm.Document} [parentDocument] Document to use as root for created nodes, used when cloning
 * @param {ve.dm.InternalList} [internalList] Internal list to clone; passed when creating a document slice
 * @param {(string|undefined)[]} [innerWhitespace] Inner whitespace to clone; passed when creating a document slice
 * @param {string} [lang] Language code
 * @param {string} [dir='ltr'] Directionality (ltr/rtl)
 * @param {ve.dm.Document} [originalDocument] Original document form which this was cloned.
 * @param {boolean} [sourceMode] Document is in source mode
 * @param {Object} [persistentStorage] Persistent storage object
 */
ve.dm.Document = function VeDmDocument( data, htmlDocument, parentDocument, internalList, innerWhitespace, lang, dir, originalDocument, sourceMode, persistentStorage ) {
	// Parent constructor
	ve.dm.Document.super.call( this, new ve.dm.DocumentNode() );

	// Initialization
	var doc = parentDocument || this;
	var root = this.documentNode;

	this.lang = lang || 'en';
	this.dir = dir || 'ltr';

	this.sourceMode = !!sourceMode;

	this.documentNode.setRoot( root );
	// ve.Document already called setDocument(), but it could be that doc !== this
	// so call it again
	this.documentNode.setDocument( doc );
	this.internalList = internalList ? internalList.clone( this ) : new ve.dm.InternalList( this );
	this.innerWhitespace = innerWhitespace ? ve.copy( innerWhitespace ) : new Array( 2 );
	this.metaList = new ve.dm.MetaList( this );

	// Properties
	this.parentDocument = parentDocument || null;
	this.originalDocument = originalDocument || null;
	this.nodesByType = {};
	this.origInternalListLength = null;
	this.readOnly = false;

	// Sparse array
	this.branchNodeFromOffsetCache = [];

	if ( data instanceof ve.dm.ElementLinearData ) {
		this.data = data;
	} else if ( data instanceof ve.dm.FlatLinearData ) {
		this.data = new ve.dm.ElementLinearData( data.getStore(), data.getData() );
	} else {
		this.data = new ve.dm.ElementLinearData(
			new ve.dm.HashValueStore(),
			Array.isArray( data ) ? data : []
		);
	}
	this.store = this.data.getStore();
	this.completeHistory = new ve.dm.Change();
	// Use the store by reference inside the completeHistory
	this.completeHistory.store = this.store;
	if ( this.store.getLength() > 0 ) {
		// Push an identity transaction and the initial store
		this.completeHistory.transactions.push( new ve.dm.Transaction( [ {
			type: 'retain',
			length: this.data.data.length
		} ] ) );
		this.completeHistory.storeLengthAtTransaction.push( this.store.getLength() );
	}
	this.htmlDocument = htmlDocument || ve.createDocumentFromHtml( '' );
	this.persistentStorage = persistentStorage || {};
};

/* Inheritance */

OO.inheritClass( ve.dm.Document, ve.Document );

/* Events */

/**
 * @event precommit
 * Emitted when a transaction is about to be committed.
 * @param {ve.dm.Transaction} tx Transaction that is about to be committed
 */

/**
 * @event transact
 * Emitted when a transaction has been committed.
 * @param {ve.dm.Transaction} tx Transaction that was just processed
 */

/* Static methods */

/**
 * Apply annotations to content data.
 *
 * This method modifies data in place.
 *
 * @static
 * @param {ve.dm.ElementLinearData|Array} data Data to apply annotations to
 * @param {ve.dm.AnnotationSet} annotationSet Annotations to apply
 * @param {boolean} [replaceComparable] Whether to remove annotations from the data which are comparable to those in annotationSet
 * @param {ve.dm.HashValueStore} [store] Store associated with the data; only needs to be provided if that data is associated with a different store than annotationSet
 * @param {boolean} [prepend] Whether to prepend annotationSet to the existing annotations
 */
ve.dm.Document.static.addAnnotationsToData = function ( data, annotationSet, replaceComparable, store, prepend ) {
	var ignoreChildrenDepth = 0,
		offset = prepend ? 0 : undefined;

	if ( annotationSet.isEmpty() ) {
		// Nothing to do
		return;
	}
	if ( store ) {
		store.merge( annotationSet.getStore() );
	} else {
		store = annotationSet.getStore();
	}
	if ( !( data instanceof ve.dm.ElementLinearData ) ) {
		data = new ve.dm.ElementLinearData( store, data );
	}

	// Apply annotations to data
	for ( var i = 0, length = data.getLength(); i < length; i++ ) {
		if ( data.isElementData( i ) && ve.dm.nodeFactory.shouldIgnoreChildren( data.getType( i ) ) ) {
			ignoreChildrenDepth += data.isOpenElementData( i ) ? 1 : -1;
		}
		if ( ignoreChildrenDepth ) {
			continue;
		}
		// eslint-disable-next-line no-loop-func
		var allowedAnnotations = annotationSet.filter( function ( ann ) {
			return data.canTakeAnnotationAtOffset( i, ann, true );
		} );
		var existingAnnotations = data.getAnnotationsFromOffset( i, true );
		var newAnnotationSet;
		if ( !existingAnnotations.isEmpty() ) {
			newAnnotationSet = existingAnnotations;
			if ( replaceComparable ) {
				newAnnotationSet = newAnnotationSet.withoutComparableSet( allowedAnnotations );
			}
			newAnnotationSet.addSet( allowedAnnotations, offset );
		} else {
			newAnnotationSet = allowedAnnotations;
		}
		data.setAnnotationsAtOffset( i, newAnnotationSet );
	}
};

/**
 * Provide a new, empty Document.
 *
 * @param {string} [paragraphType='empty'] Paragraph type: 'empty', 'wrapper' or null for a regular paragraph
 * @return {ve.dm.Document}
 */
ve.dm.Document.static.newBlankDocument = function ( paragraphType ) {
	var paragraph = { type: 'paragraph' };

	paragraphType = paragraphType === undefined ? 'empty' : paragraphType;

	if ( paragraphType ) {
		ve.setProp( paragraph, 'internal', 'generated', paragraphType );
	}

	return new ve.dm.Document( [
		paragraph,
		{ type: '/paragraph' },
		{ type: 'internalList' },
		{ type: '/internalList' }
	] );
};

/* Methods */

/**
 * @inheritdoc
 */
ve.dm.Document.prototype.getDocumentNode = function () {
	if ( !this.documentNode.length && !this.documentNode.getDocument().buildingNodeTree ) {
		this.buildNodeTree();
	}
	return this.documentNode;
};

/**
 * Get a range that spans the entire document (excluding the internal list)
 *
 * @return {ve.Range} Document range
 */
ve.dm.Document.prototype.getDocumentRange = function () {
	return new ve.Range( 0, this.getInternalList().getListNode().getOuterRange().start );
};

/**
 * Build the node tree.
 */
ve.dm.Document.prototype.buildNodeTree = function () {
	var textLength = 0,
		inTextNode = false;

	// Build a tree of nodes and nodes that will be added to them after a full scan is complete,
	// then from the bottom up add nodes to their potential parents. This avoids massive length
	// updates being broadcast upstream constantly while building is underway.
	var currentStack = [];
	var parentStack = [ this.documentNode ];
	// Stack of stacks
	var nodeStack = [ parentStack, currentStack ];
	var currentNode = this.documentNode;
	var doc = this.documentNode.getDocument();

	for ( var i = 0, len = this.data.getLength(); i < len; i++ ) {
		var node;
		if ( !this.data.isElementData( i ) ) {
			// Text node opening
			if ( !inTextNode ) {
				// Create a lengthless text node
				node = new ve.dm.TextNode();
				// Put the node on the current inner stack
				currentStack.push( node );
				currentNode = node;
				// Set a flag saying we're inside a text node
				inTextNode = true;
			}
			// Track the length
			textLength++;
		} else {
			// Text node closing
			if ( inTextNode ) {
				// Finish the text node by setting the length
				currentNode.setLength( textLength );
				// Put the state variables back as they were
				currentNode = parentStack[ parentStack.length - 1 ];
				inTextNode = false;
				textLength = 0;
			}
			// Element open/close
			if ( this.data.isOpenElementData( i ) ) {
				// Branch or leaf node opening
				// Create a childless node
				node = ve.dm.nodeFactory.createFromElement( this.data.getData( i ) );
				// Put the childless node on the current inner stack
				currentStack.push( node );
				if ( ve.dm.nodeFactory.canNodeHaveChildren( node.getType() ) ) {
					// Create a new inner stack for this node
					parentStack = currentStack;
					currentStack = [];
					nodeStack.push( currentStack );
					currentNode = node;
				} else {
					// Assert that the next element is a closing element for this node,
					// and skip over it.
					if (
						!this.data.isCloseElementData( i + 1 ) ||
						this.data.getType( i + 1 ) !== this.data.getType( i )
					) {
						throw new Error( 'Opening element for node that cannot have children must be followed by closing element' );
					}
					i++;
				}
			} else {
				// Branch or leaf node closing
				if ( this.data.getType( i ) !== currentNode.getType() ) {
					throw new Error( 'Expected closing for ' + currentNode.getType() +
						' but got closing for ' + this.data.getType( i ) );
				}
				// Pop this node's inner stack from the outer stack. It'll have all of the
				// node's child nodes fully constructed
				var children = nodeStack.pop();
				currentStack = parentStack;
				parentStack = nodeStack[ nodeStack.length - 2 ];
				if ( !parentStack ) {
					// This can only happen if we got unbalanced data
					throw new Error( 'Unbalanced input passed to document' );
				}
				// Attach the children to the node
				ve.batchSplice( currentNode, 0, 0, children );
				currentNode = parentStack[ parentStack.length - 1 ];
			}
		}
	}

	if ( inTextNode ) {
		// Text node ended by end-of-input rather than by an element
		currentNode.setLength( textLength );
		// Don't bother updating currentNode et al, we don't use them below
	}

	// State variable that allows nodes to know that they are being
	// appended in order. Used by ve.dm.InternalList.
	doc.buildingNodeTree = true;

	// The end state is nodeStack = [ [this.documentNode], [ array, of, its, children ] ]
	// so attach all nodes in currentStack to the root node
	if ( nodeStack.length > 2 ) {
		// A node was opened but never closed
		throw new Error( 'Unbalanced input passed to document' );
	}
	ve.batchSplice( this.documentNode, 0, 0, currentStack );
	this.updateNodesByType( [ this.documentNode ], [ this.documentNode ] );

	doc.buildingNodeTree = false;
};

/**
 * Get the length of the document. This is also the highest valid offset in the document.
 *
 * @return {number} Length of the document
 */
ve.dm.Document.prototype.getLength = function () {
	return this.data.getLength();
};

/**
 * Get the meta list.
 *
 * @return {ve.dm.MetaList} Meta list of the surface
 */
ve.dm.Document.prototype.getMetaList = function () {
	// Ensure the DM tree has been built
	this.getDocumentNode();
	return this.metaList;
};

/**
 * Set the read-only state of the document
 *
 * Actual locking of the model is done by the surface, but
 * we pass through this flag so we can do some optimizations
 * in read-only mode, such as caching node offsets.
 *
 * TODO: It might just be easier for Documents to know which
 * Surface they belong to, although we should make sure that
 * this doesn't violate the direction of data flow.
 *
 * @param {boolean} readOnly Mark document as read-only
 */
ve.dm.Document.prototype.setReadOnly = function ( readOnly ) {
	this.readOnly = !!readOnly;
	if ( !this.readOnly ) {
		// Clear offset cache when leaving read-only mode
		this.getDocumentNode().traverse( function ( node ) {
			node.offset = null;
		} );
	}
};

/**
 * Check if the document is read-only
 *
 * @return {boolean}
 */
ve.dm.Document.prototype.isReadOnly = function () {
	return this.readOnly;
};

/**
 * Apply a transaction's effects on the content data.
 *
 * @param {ve.dm.Transaction} transaction Transaction to apply
 * @param {boolean} isStaging Transaction is being applied in staging mode
 * @fires precommit
 * @fires transact
 * @throws {Error} Cannot commit a transaction that has already been committed
 */
ve.dm.Document.prototype.commit = function ( transaction, isStaging ) {
	if ( transaction.hasBeenApplied() ) {
		throw new Error( 'Cannot commit a transaction that has already been committed' );
	}
	this.emit( 'precommit', transaction );
	this.branchNodeFromOffsetCache = [];
	new ve.dm.TransactionProcessor( this, transaction, isStaging ).process();
	this.completeHistory.pushTransaction( transaction, this.store.getLength() );
	this.emit( 'transact', transaction );
};

/**
 * Get a slice or copy of the document data.
 *
 * @param {ve.Range} [range] Range of data to get, all data will be given by default
 * @param {boolean} [deep=false] Whether to return a deep copy (WARNING! This may be very slow)
 * @return {Array} Slice or copy of document data
 */
ve.dm.Document.prototype.getData = function ( range, deep ) {
	return this.data.getDataSlice( range, deep );
};

/**
 * Get a copy of the document metadata.
 *
 * This is just a sparse shallow copy of the document data, with meta-item open tags only (i.e.
 * with blanks in place of non-meta-items and meta-item close tags).
 *
 * @param {ve.Range} [range] Range of metadata to get, all metadata will be given by default
 * @return {ve.dm.MetaItem[]} Sparse array of ve.dm.MetaItems.
 */
ve.dm.Document.prototype.getMetadata = function ( range ) {
	if ( arguments.length > 1 ) {
		throw new Error( 'Argument "deep" is no longer supported' );
	}
	var documentNode = this.getDocumentNode();
	if ( !range ) {
		range = new ve.Range( 0, documentNode.length );
	}
	var data = [];
	documentNode.traverse( function ( node ) {
		var offset;
		if ( node instanceof ve.dm.MetaItem ) {
			offset = node.getOffset();
			if ( range.start <= offset && offset < range.end ) {
				data[ offset - range.start ] = node;
			}
		}
	} );
	data.length = range.end - range.start;
	return data;
};

/**
 * Get the HTMLDocument associated with this document.
 *
 * @return {HTMLDocument} Associated document
 */
ve.dm.Document.prototype.getHtmlDocument = function () {
	return this.htmlDocument;
};

/**
 * Get the document model form which this document was cloned.
 *
 * @return {ve.dm.Document|null} Original document
 */
ve.dm.Document.prototype.getOriginalDocument = function () {
	return this.originalDocument;
};

/**
 * Get the document's hash-value store
 *
 * @return {ve.dm.HashValueStore} The document's hash-value store
 */
ve.dm.Document.prototype.getStore = function () {
	return this.store;
};

/**
 * Get the document's internal list
 *
 * @return {ve.dm.InternalList} The document's internal list
 */
ve.dm.Document.prototype.getInternalList = function () {
	return this.internalList;
};

/**
 * Get the document's inner whitespace
 *
 * @return {(string|undefined)[]} The document's inner whitespace
 */
ve.dm.Document.prototype.getInnerWhitespace = function () {
	return this.innerWhitespace;
};

/**
 * Clone a sub-document from a shallow copy of this document.
 *
 * The new document's elements, internal list and store will only contain references to data within the slice.
 *
 * @param {ve.dm.Selection} selection Selection to create sub-document from
 * @return {ve.dm.DocumentSlice} New document
 */
ve.dm.Document.prototype.shallowCloneFromSelection = function ( selection ) {
	if ( selection instanceof ve.dm.LinearSelection ) {
		return this.shallowCloneFromRange( selection.getRange() );
	} else if ( selection instanceof ve.dm.TableSelection ) {
		var data = [];
		var ranges = selection.getTableSliceRanges( this );
		for ( var i = 0, l = ranges.length; i < l; i++ ) {
			data = data.concat( this.data.slice( ranges[ i ].start, ranges[ i ].end ) );
		}
		var linearData = new ve.dm.ElementLinearData( this.getStore(), data );

		var tableRange = new ve.Range( 0, data.length );

		// Copy over the internal list
		ve.batchSplice(
			linearData.data, linearData.getLength(), 0,
			this.getData( this.getInternalList().getListNode().getOuterRange(), true )
		);

		// The internalList is rebuilt by the document constructor
		return new ve.dm.TableSlice(
			linearData, this.getHtmlDocument(), undefined, this.getInternalList(), tableRange, this
		);
	} else {
		return this.shallowCloneFromRange( new ve.Range( 0 ) );
	}
};

/**
 * Clone a sub-document from a shallow copy of this document.
 *
 * The new document's elements, internal list and store will only contain references to data within the slice.
 *
 * @param {ve.Range} [range] Range of data to slice; defaults to whole document
 * @return {ve.dm.DocumentSlice} New document
 */
ve.dm.Document.prototype.shallowCloneFromRange = function ( range ) {
	var linearData, originalRange, balancedRange,
		balanceOpenings = [],
		balanceClosings = [],
		contextOpenings = [],
		contextClosings = [];

	if ( !range ) {
		// Default to the whole document
		linearData = this.data.sliceObject();
		originalRange = balancedRange = this.getDocumentRange();
	} else {
		var selection = this.selectNodes( range, 'siblings' );
		var first = selection[ 0 ];
		var last = selection[ selection.length - 1 ];
		var firstNode = first.node;
		var lastNode = last.node;

		// Use first/lastNode if they are non-content branch nodes, otherwise use getBranchNodeFromOffset.
		var startNode = !firstNode.hasChildren() && !firstNode.isContent() ? firstNode : this.getBranchNodeFromOffset( range.start );
		var endNode = !lastNode.hasChildren() && !lastNode.isContent() ? lastNode : this.getBranchNodeFromOffset( range.end );

		// Fix up selection to remove empty items in unwrapped nodes
		// TODO: fix this is selectNodes
		while ( selection[ 0 ] && selection[ 0 ].range && selection[ 0 ].range.isCollapsed() && !selection[ 0 ].node.isWrapped() ) {
			selection.shift();
		}

		var i = selection.length - 1;
		while ( selection[ i ] && selection[ i ].range && selection[ i ].range.isCollapsed() && !selection[ i ].node.isWrapped() ) {
			selection.pop();
			i--;
		}

		var balancedNodes;
		if ( selection.length === 0 || range.isCollapsed() ) {
			// Nothing selected
			linearData = new ve.dm.ElementLinearData( this.getStore(), [
				{ type: 'paragraph', internal: { generated: 'empty' } },
				{ type: '/paragraph' }
			] );
			originalRange = balancedRange = new ve.Range( 1 );
		} else if ( startNode === endNode ) {
			// Nothing to balance
			balancedNodes = selection;
		} else {
			// Selection is not balanced
			while ( !firstNode.isWrapped() ) {
				firstNode = firstNode.getParent();
			}
			while ( !lastNode.isWrapped() ) {
				lastNode = lastNode.getParent();
			}

			if ( first.range ) {
				while ( true ) {
					while ( !startNode.isWrapped() ) {
						startNode = startNode.getParent();
					}
					balanceOpenings.push( startNode.getClonedElement() );
					if ( startNode === firstNode ) {
						break;
					}
					startNode = startNode.getParent();
				}
			}

			if ( last !== first && last.range ) {
				while ( true ) {
					while ( !endNode.isWrapped() ) {
						endNode = endNode.getParent();
					}
					balanceClosings.push( { type: '/' + endNode.getType() } );
					if ( endNode === lastNode ) {
						break;
					}
					endNode = endNode.getParent();
				}
			}

			balancedNodes = this.selectNodes(
				new ve.Range( firstNode.getOuterRange().start, lastNode.getOuterRange().end ),
				'covered'
			);
		}

		// eslint-disable-next-line no-inner-declarations
		function nodeNeedsContext( node ) {
			return node.getParentNodeTypes() !== null || node.isContent();
		}

		if ( !balancedRange ) {
			// Check if any of the balanced siblings need more context for insertion anywhere
			var needsContext = false;
			for ( i = balancedNodes.length - 1; i >= 0; i-- ) {
				if ( nodeNeedsContext( balancedNodes[ i ].node ) ) {
					needsContext = true;
					break;
				}
			}

			if ( needsContext ) {
				startNode = balancedNodes[ 0 ].node;
				// Keep wrapping until the outer node can be inserted anywhere
				while ( startNode.getParent() && nodeNeedsContext( startNode ) ) {
					var isContent = startNode.isContent();
					startNode = startNode.getParent();
					var contextElement = startNode.getClonedElement();
					if ( isContent ) {
						ve.setProp( contextElement, 'internal', 'generated', 'wrapper' );
					}
					contextOpenings.push( contextElement );
					contextClosings.push( { type: '/' + contextElement.type } );
				}
			}

			// Final data:
			//  contextOpenings + balanceOpenings + data slice + balanceClosings + contextClosings
			linearData = new ve.dm.ElementLinearData(
				this.getStore(),
				contextOpenings.reverse()
					.concat( balanceOpenings.reverse() )
					.concat( this.data.slice( range.start, range.end ) )
					.concat( balanceClosings )
					.concat( contextClosings )
			);
			originalRange = new ve.Range(
				contextOpenings.length + balanceOpenings.length,
				contextOpenings.length + balanceOpenings.length + range.getLength()
			);
			balancedRange = new ve.Range(
				contextOpenings.length,
				contextOpenings.length + balanceOpenings.length + range.getLength() + balanceClosings.length
			);
		}

		// Shallow copy over the internal list
		ve.batchSplice(
			linearData.data, linearData.getLength(), 0,
			this.getData( this.getInternalList().getListNode().getOuterRange() )
		);
	}

	// The internalList is rebuilt by the document constructor
	var slice = new ve.dm.DocumentSlice(
		linearData, this.getHtmlDocument(), undefined, this.getInternalList(), originalRange, balancedRange, this
	);
	return slice;
};

/**
 * Clone a sub-document from a range in this document. The new document's elements, store and internal list
 * will be clones of the ones in this document.
 *
 * @param {ve.Range} [range] Range of data to clone, clones the whole document if ommitted.
 * @param {boolean} [detachedCopy] The copy is not intended to be merged into the original
 * @param {string} [mode] Mode for getting data, see #getFullData
 * @return {ve.dm.Document} New document
 */
ve.dm.Document.prototype.cloneFromRange = function ( range, detachedCopy, mode ) {
	var listRange = this.getInternalList().getListNode().getOuterRange(),
		data = ve.copy( this.getFullData( range, mode || 'roundTrip' ) );
	if ( range && ( range.start > listRange.start || range.end < listRange.end ) ) {
		// The range does not include the entire internal list, so add it
		data = data.concat( this.getFullData( listRange ) );
	}
	return this.cloneWithData( data, true, detachedCopy );
};

/**
 * Create a sub-document associated with this document like #cloneFromRange, but without cloning
 * any data from a range in this document: instead, use the specified data.
 *
 * @param {Array|ve.dm.ElementLinearData} data Raw linear model data or ElementLinearData
 * @param {boolean} [copyInternalList] Copy the internal list
 * @param {boolean} [detachedCopy] The copy is not intended to be merged into the original
 * @return {ve.dm.Document} New document
 */
ve.dm.Document.prototype.cloneWithData = function ( data, copyInternalList, detachedCopy ) {
	var newDoc;

	if ( Array.isArray( data ) ) {
		data = new ve.dm.ElementLinearData( this.getStore().slice(), data );
	}

	newDoc = new this.constructor(
		data,
		// htmlDocument
		this.getHtmlDocument(),
		// parentDocument
		undefined,
		// internalList
		copyInternalList ? this.getInternalList() : undefined,
		// innerWhitespace
		undefined,
		// lang+dir
		this.getLang(), this.getDir(),
		// originalDocument
		this,
		// sourceMode
		this.sourceMode,
		// persistentStorage
		this.getStorage()
	);
	if ( copyInternalList && !detachedCopy ) {
		// Record the length of the internal list at the time the slice was created so we can
		// reconcile additions properly
		newDoc.origInternalListLength = this.internalList.getItemNodeCount();
	}
	return newDoc;
};

/**
 * Get document data, possibly with inline MetaItem load offsets restored, possibly without metadata
 *
 * @param {ve.Range} [range] Range to get full data for. If omitted, all data will be returned
 * @param {string} [mode] If 'roundTrip', restore load offsets of inlined meta items from unchanged
 * branches. If 'noMetadata', don't include metadata items.
 * @return {Array} Data, with load offset info removed (some items are referenced, others copied)
 */
ve.dm.Document.prototype.getFullData = function ( range, mode ) {
	var insertedMetaItems = [],
		insertions = {},
		iLen = range ? range.end : this.data.getLength(),
		result = [];

	function stripMetaLoadInfo( element ) {
		if ( !element || !element.internal ) {
			return element;
		}
		element = ve.copy( element );
		delete element.internal.changesSinceLoad;
		delete element.internal.metaItems;
		delete element.internal.loadMetaParentHash;
		delete element.internal.loadMetaParentOffset;
		if ( Object.keys( element.internal ).length === 0 ) {
			delete element.internal;
		}
		return element;
	}

	for ( var i = range ? range.start : 0; i < iLen; i++ ) {
		var item = this.data.getData( i );
		if (
			ve.dm.LinearData.static.isOpenElementData( item ) &&
			ve.dm.nodeFactory.isMetaData( item.type ) &&
			(
				mode === 'noMetadata' ||
				mode === 'roundTrip' && (
					// Already inserted
					insertedMetaItems.indexOf( item.originalDomElementsHash ) !== -1 ||
					// Removable meta item that was not handled yet, which means that its entire branch node
					// must have been removed, so it's out of place and should be removed too
					ve.dm.nodeFactory.isRemovableMetaData( item.type ) && ve.getProp( item, 'internal', 'loadMetaParentOffset' )
				)
			)
		) {
			// Skip this item and its matching close tag
			i += 1;
			continue;
		}
		var metaItem, metaItems, internal;
		var j, jLen;
		if (
			mode === 'roundTrip' &&
			( internal = item.internal ) &&
			( metaItems = internal.metaItems )
		) {
			if ( !internal.changesSinceLoad ) {
				// eslint-disable-next-line no-loop-func, no-shadow
				this.data.modifyData( i, function ( item ) {
					// Re-fetch unfrozen metaItems.
					metaItems = item.internal.metaItems;
					// No changes, so restore meta item offsets
					for ( j = 0, jLen = metaItems.length; j < jLen; j++ ) {
						metaItem = metaItems[ j ];
						var offset = i + metaItem.internal.loadMetaParentOffset;
						if ( !insertions[ offset ] ) {
							insertions[ offset ] = [];
						}

						delete metaItem.internal.loadBranchNodeHash;
						delete metaItem.internal.loadBranchNodeOffset;
						if ( Object.keys( metaItem.internal ).length === 0 ) {
							delete metaItem.internal;
						}
						insertions[ offset ].push( stripMetaLoadInfo( metaItem ) );
						insertedMetaItems.push( metaItem.originalDomElementsHash );
					}
				} );
			} else {
				// Had changes, so remove removable meta items that are out of place now
				for ( j = 0, jLen = metaItems.length; j < jLen; j++ ) {
					metaItem = metaItems[ j ];
					if ( ve.dm.nodeFactory.isRemovableMetaData( metaItem.type ) ) {
						insertedMetaItems.push( metaItem.originalDomElementsHash );
					}
				}
			}
		}
		result.push( stripMetaLoadInfo( item ) );
		if ( mode === 'roundTrip' && insertions[ i ] ) {
			for ( j = 0, jLen = insertions[ i ].length; j < jLen; j++ ) {
				metaItem = insertions[ i ][ j ];
				result.push( metaItem );
				result.push( { type: '/' + metaItem.type } );
			}
		}
	}
	return result;
};

/**
 * Get the nearest word boundary.
 *
 * @param {number} offset Offset to start from
 * @param {number} [direction] Direction to prefer matching offset in, -1 for left and 1 for right
 * @return {number} Nearest word boundary
 */
ve.dm.Document.prototype.getSiblingWordBoundary = function ( offset, direction ) {
	var dataString = new ve.dm.DataString( this.getData() );
	return unicodeJS.wordbreak.moveBreakOffset( direction, dataString, offset, true );
};

/**
 * Get the relative word or character boundary.
 *
 * @param {number} offset Offset to start from
 * @param {number} direction Direction to prefer matching offset in, -1 for left and 1 for right
 * @param {string} [unit] Unit [word|character]
 * @return {number} Relative offset
 */
ve.dm.Document.prototype.getRelativeOffset = function ( offset, direction, unit ) {
	var data = this.data;
	if ( unit === 'word' ) { // Word
		// Method getSiblingWordBoundary does not "move/jump" over element data. If passed offset is
		// an element data offset then the same offset is returned - and in such case this method
		// fallback to the other path (character) which does "move/jump" over element data.
		var newOffset = this.getSiblingWordBoundary( offset, direction );
		if ( offset === newOffset ) {
			newOffset = this.getRelativeOffset( offset, direction, 'character' );
		}
		return newOffset;
	} else { // Character
		// Check if we are adjacent to a focusable node
		var adjacentDataOffset = offset + ( direction > 0 ? 0 : -1 );
		if (
			data.isElementData( adjacentDataOffset ) &&
			ve.dm.nodeFactory.isNodeFocusable( data.getType( adjacentDataOffset ) )
		) {
			// We are adjacent to a focusableNode, move inside it
			return offset + direction;
		}
		var relativeContentOffset = data.getRelativeContentOffset( offset, direction );
		var relativeStructuralOffset = data.getRelativeStructuralOffset( offset, direction, true );
		var isFocusable;
		// Check the structural offset is not in the wrong direction
		if ( ( relativeStructuralOffset - offset < 0 ? -1 : 1 ) !== direction ) {
			relativeStructuralOffset = offset;
		} else {
			isFocusable = ( relativeStructuralOffset - offset < 0 ? -1 : 1 ) === direction &&
				data.isElementData( relativeStructuralOffset + direction ) &&
				ve.dm.nodeFactory.isNodeFocusable( data.getType( relativeStructuralOffset + direction ) );
		}
		// Check if we've moved into a slug or a focusableNode
		if ( isFocusable || this.hasSlugAtOffset( relativeStructuralOffset ) ) {
			if ( isFocusable ) {
				relativeStructuralOffset += direction;
			}
			// Check if the relative content offset is in the opposite direction we are trying to go
			if (
				relativeContentOffset === offset ||
				( relativeContentOffset - offset < 0 ? -1 : 1 ) !== direction
			) {
				return relativeStructuralOffset;
			}
			// There's a slug nearby, go into it if it's closer
			return direction > 0 ?
				Math.min( relativeContentOffset, relativeStructuralOffset ) :
				Math.max( relativeContentOffset, relativeStructuralOffset );
		} else {
			// Don't allow the offset to move in the wrong direction
			return direction > 0 ?
				Math.max( relativeContentOffset, offset ) :
				Math.min( relativeContentOffset, offset );
		}
	}
};

/**
 * Get the relative range.
 *
 * @param {ve.Range} range Input range
 * @param {number} direction Direction to look in, +1 or -1
 * @param {string} unit Unit [word|character]
 * @param {boolean} expand Expanding range
 * @param {ve.Range} [limit] Optional limiting range. If the relative range is not in this range
 *                           the input range is returned instead.
 * @return {ve.Range} Relative range
 */
ve.dm.Document.prototype.getRelativeRange = function ( range, direction, unit, expand, limit ) {
	var to = range.to;

	// If you have a non-collapsed range and you move, collapse to the end
	// in the direction you moved, provided you end up at a content or slug offset
	if ( !range.isCollapsed() && !expand ) {
		var newOffset = direction > 0 ? range.end : range.start;
		if ( this.data.isContentOffset( newOffset ) || this.hasSlugAtOffset( newOffset ) ) {
			return new ve.Range( newOffset );
		} else {
			to = newOffset;
		}
	}

	var contentOrSlugOffset = this.getRelativeOffset( to, direction, unit );

	var focusableNode = this.getNearestFocusableNode( to, direction, contentOrSlugOffset );
	var newRange;
	if ( focusableNode ) {
		newRange = focusableNode.getOuterRange( direction === -1 );
	} else {
		newRange = new ve.Range( contentOrSlugOffset );
	}
	if ( limit && !limit.containsRange( newRange ) ) {
		return range;
	}
	if ( expand ) {
		return new ve.Range( range.from, newRange.to );
	} else {
		return newRange;
	}
};

/**
 * Get the nearest node matching a test.
 *
 * @param {Function} test Function to test whether a node matches, called with the nodeType
 * @param {number} offset Offset to start looking at
 * @param {number} direction Direction to look in, +1 or -1
 * @param {number} limit Stop looking after reaching certain offset
 * @return {ve.dm.Node|null} Nearest matching node, or null if not found
 */
ve.dm.Document.prototype.getNearestNodeMatching = function ( test, offset, direction, limit ) {
	// It is never an offset of the node, but just an offset for which getNodeFromOffset should
	// return that node. Usually it would be node offset + 1 or offset of node closing tag.
	var coveredOffset;
	this.data.getRelativeOffset(
		offset,
		direction === 1 ? 0 : -1,
		function ( index, lim ) {
			// Our result must be between offset and limit
			if ( index >= Math.max( offset, lim ) || index < Math.min( offset, lim ) ) {
				return true;
			}
			if (
				this.isOpenElementData( index ) &&
				test( this.getType( index ) )
			) {
				coveredOffset = index + 1;
				return true;
			}
			if (
				this.isCloseElementData( index ) &&
				test( this.getType( index ) )
			) {
				coveredOffset = index;
				return true;
			}
		},
		limit
	);
	if ( coveredOffset ) {
		return this.getDocumentNode().getNodeFromOffset( coveredOffset );
	} else {
		return null;
	}
};

/**
 * Get the nearest focusable node.
 *
 * @param {number} offset Offset to start looking at
 * @param {number} direction Direction to look in, +1 or -1
 * @param {number} limit Stop looking after reaching certain offset
 * @return {ve.dm.Node|null} Nearest focusable node, or null if not found
 */
ve.dm.Document.prototype.getNearestFocusableNode = function ( offset, direction, limit ) {
	return this.getNearestNodeMatching( function ( nodeType ) {
		return ve.dm.nodeFactory.isNodeFocusable( nodeType );
	}, offset, direction, limit );
};

/**
 * Get the nearest offset that a cursor can be placed at.
 *
 * Note that an offset in the other direction can be returned if there are no valid offsets in the
 * preferred direction.
 *
 * @param {number} offset Offset to start looking at
 * @param {number} [direction=-1] Direction to check first, +1 or -1; if 0, find the closest offset
 * @return {number} Nearest offset a cursor can be placed at, or -1 if there are no valid offsets in
 *     data
 */
ve.dm.Document.prototype.getNearestCursorOffset = function ( offset, direction ) {
	if ( direction === 0 ) {
		var left = this.getNearestCursorOffset( offset, -1 );
		var right = this.getNearestCursorOffset( offset, 1 );
		// If only one of `left` and `right` is valid, return the valid one.
		// If neither is valid, this returns -1.
		if ( right === -1 ) {
			return left;
		} else if ( left === -1 ) {
			return right;
		}
		return offset - left < right - offset ? left : right;
	}

	direction = direction > 0 ? 1 : -1;
	if (
		this.data.isContentOffset( offset ) ||
		this.hasSlugAtOffset( offset )
	) {
		return offset;
	}

	var contentOffset = this.data.getNearestContentOffset( offset, direction );
	var structuralOffset = this.data.getNearestStructuralOffset( offset, direction, true );

	// If only one of `contentOffset` and `structuralOffset` is valid, return the valid one.
	// If neither is valid, this returns -1.
	if ( structuralOffset === -1 || !this.hasSlugAtOffset( structuralOffset ) ) {
		return contentOffset;
	} else if ( contentOffset === -1 ) {
		return structuralOffset;
	}

	if ( direction === 1 ) {
		if ( contentOffset < offset ) {
			return structuralOffset;
		} else {
			return Math.min( contentOffset, structuralOffset );
		}
	} else {
		if ( contentOffset > offset ) {
			return structuralOffset;
		} else {
			return Math.max( contentOffset, structuralOffset );
		}
	}
};

/**
 * @inheritdoc
 */
ve.dm.Document.prototype.getBranchNodeFromOffset = function ( offset ) {
	if ( offset < 0 || offset > this.data.getLength() ) {
		throw new Error( 've.dm.Document.getBranchNodeFromOffset(): offset ' + offset + ' is out of bounds' );
	}
	if ( !this.branchNodeFromOffsetCache[ offset ] ) {
		this.branchNodeFromOffsetCache[ offset ] = ve.Document.prototype.getBranchNodeFromOffset.call( this, offset );
	}
	return this.branchNodeFromOffsetCache[ offset ];
};

/**
 * Check if there is a slug at an offset.
 *
 * @param {number} offset Offset to check for a slug at
 * @return {boolean} There is a slug at the offset
 */
ve.dm.Document.prototype.hasSlugAtOffset = function ( offset ) {
	var node;
	try {
		node = this.getBranchNodeFromOffset( offset );
	} catch ( e ) {
		// Offset was out of bounds
	}
	return node ? node.hasSlugAtOffset( offset ) : false;
};

/**
 * Get the content data of a node.
 *
 * @param {ve.dm.Node} node Node to get content data for
 * @return {Array|null} List of content and elements inside node or null if node is not found
 */
ve.dm.Document.prototype.getDataFromNode = function ( node ) {
	var offset = node.getOffset();
	if ( offset >= 0 ) {
		// FIXME T126023: If the node is wrapped in an element than we should increment
		// the offset by one so we only return the content inside the element.
		if ( node.isWrapped() ) {
			offset++;
		}
		return this.data.slice( offset, offset + node.getLength() );
	}
	return null;
};

/**
 * Rebuild the entire node tree from linear model data.
 */
ve.dm.Document.prototype.rebuildTree = function () {
	// Never rebuild above the attachedRoot node as that would destroy
	// that node, and invalidate all references to it (T293254)
	var rootNode = this.attachedRoot || this.getDocumentNode();
	var range = rootNode.getRange();
	var data = this.data.sliceObject( range.start, range.end );
	// Build document fragment from data
	// Use plain ve.dm.Document, instead of whatever this.constructor is.
	var documentFragment = new ve.dm.Document( data, this.htmlDocument, this );
	// Get generated child nodes from the document fragment
	var addedNodes = documentFragment.getDocumentNode().getChildren();
	// Replace nodes in the model tree
	var removedNodes = ve.batchSplice( rootNode, 0, rootNode.getChildren().length, addedNodes );

	this.updateNodesByType( addedNodes, removedNodes );
};

/**
 * Update the nodes-by-type index
 *
 * @param {ve.dm.Node[]} addedNodes Added nodes
 * @param {ve.dm.Node[]} removedNodes Removed nodes
 */
ve.dm.Document.prototype.updateNodesByType = function ( addedNodes, removedNodes ) {
	var doc = this;

	function remove( node ) {
		var type = node.getType(),
			nodes = doc.nodesByType[ type ] || [],
			index = nodes.indexOf( node );

		if ( index !== -1 ) {
			nodes.splice( index, 1 );
			if ( !nodes.length ) {
				delete doc.nodesByType[ type ];
			}
		}
	}

	function add( node ) {
		var type = node.getType(),
			nodes = doc.nodesByType[ type ] = doc.nodesByType[ type ] || [];

		nodes.push( node );
	}

	function traverse( nodes, action ) {
		nodes.forEach( function ( node ) {
			if ( node.hasChildren() ) {
				node.traverse( action );
			}
			action( node );
		} );
	}

	traverse( removedNodes, remove );
	traverse( addedNodes, add );
};

/**
 * Get all nodes in the tree for a specific type
 *
 * If a string type is passed only nodes of that exact type will be returned,
 * if a node class is passed, all sub types will be matched.
 *
 * String type matching will be faster than class matching.
 *
 * @param {string|Function} type Node type name or node constructor
 * @param {boolean} sort Sort nodes by document order
 * @return {ve.dm.Node[]} Nodes of a specific type
 */
ve.dm.Document.prototype.getNodesByType = function ( type, sort ) {
	var nodes = [];
	if ( !this.documentNode.length && !this.documentNode.getDocument().buildingNodeTree ) {
		this.buildNodeTree();
	}
	if ( type instanceof Function ) {
		for ( var t in this.nodesByType ) {
			var nodeType = ve.dm.nodeFactory.lookup( t );
			if ( nodeType === type || nodeType.prototype instanceof type ) {
				nodes = nodes.concat( this.getNodesByType( t ) );
			}
		}
	} else {
		nodes = this.nodesByType[ type ] || [];
	}

	if ( sort ) {
		nodes.sort( function ( a, b ) {
			return a.getOffset() - b.getOffset();
		} );
	}
	return nodes;
};

/**
 * Fix up data so it can safely be inserted into the document data at an offset.
 *
 * TODO: this function needs more work but it seems to work, mostly
 *
 * @param {Array} data Snippet of linear model data to insert
 * @param {number} offset Offset in the linear model where the caller wants to insert data
 * @return {Object}
 * @return {Array} return.data Possibly modified copy of `data`
 * @return {number} return.offset Possibly modified offset
 * @return {number} return.remove Number of elements to remove after the modified `offset`
 * @return {number} [return.insertedDataOffset] Offset of intended insertion within fixed up data
 * @return {number} [return.insertedDataLength] Length of intended insertion within fixed up data
 */
ve.dm.Document.prototype.fixupInsertion = function ( data, offset ) {
	var
		// Array where we build the return value
		newData = [],

		// Inserting block element into an empty content branch will replace it.
		remove = 0,

		// *** Stacks ***
		// Array of element openings (object). Openings in data are pushed onto this stack
		// when they are encountered and popped off when they are closed
		openingStack = [],
		// Array of node objects. Closings in data that close nodes that were
		// not opened in data (i.e. were already in the document) are pushed onto this stack
		// and popped off when balanced out by an opening in data
		closingStack = [],

		// Track the position of the original data in the fixed up data for range adjustments
		insertedDataOffset = 0,
		insertedDataLength = data.length,

		// Pointer to this document for private methods
		doc = this,

		// *** State persisting across iterations of the outer loop ***
		// The node (from the document) we're currently in. When in a node that was opened
		// in data, this is set to its first ancestor that is already in the document
		parentNode,
		// The type of the node we're currently in, even if that node was opened within data
		parentType,
		// Whether we are currently in a text node
		inTextNode,
		// Whether this is the first child of its parent
		// The test for last child isn't a loop so we don't need to cache it
		isFirstChild,

		// *** Temporary variables that do not persist across iterations ***
		// The type of the node we're currently inserting. When the to-be-inserted node
		// is wrapped, this is set to the type of the outer wrapper.
		childType,
		// Stores the return value of getParentNodeTypes( childType )
		allowedParents,
		// Stores the return value of getChildNodeTypes( parentType )
		allowedChildren,
		// Whether parentType matches allowedParents
		parentsOK,
		// Whether childType matches allowedChildren
		childrenOK,
		// Stores the return value of getSuggestedParentNodeTypes
		suggestedParents,
		// Whether parentType matches suggestedParents
		suggestedParentsOK,
		// Array of opening elements to insert (for wrapping the to-be-inserted element)
		openings,
		// Array of closing elements to insert (for splitting nodes)
		closings,
		// Array of opening elements matching the elements in closings (in the same order)
		reopenElements,

		// *** Other variables ***
		// Used to store values popped from various stacks
		popped,
		// Result of recursive call
		insertion,
		// Loop variables
		i, j;

	/**
	 * Append a linear model element to newData and update the state.
	 *
	 * This function updates parentNode, parentType, openingStack and closingStack.
	 *
	 * @private
	 * @param {Object|Array|string} element Linear model element
	 * @param {number} index Index in data that the element came from (for error reporting only)
	 */
	function writeElement( element, index ) {
		if ( element.type !== undefined ) {
			// Content, do nothing
			if ( element.type.charAt( 0 ) !== '/' ) {
				// Opening
				// Check if this opening balances an earlier closing of a node that was already in
				// the document. This is only the case if openingStack is empty (otherwise we still
				// have unclosed nodes from within data) and if this opening matches the top of
				// closingStack
				if ( openingStack.length === 0 && closingStack.length > 0 &&
					closingStack[ closingStack.length - 1 ].getType() === element.type
				) {
					// The top of closingStack is now balanced out, so remove it
					// Also restore parentNode from closingStack. While this is technically not
					// entirely accurate (the current node is a new node that's a sibling of this
					// node), it's good enough for the purposes of this algorithm
					parentNode = closingStack.pop();
				} else {
					// This opens something new, put it on openingStack
					openingStack.push( element );
				}
				parentType = element.type;
			} else {
				var expectedType;
				// Closing
				// Make sure that this closing matches the currently opened node
				if ( openingStack.length > 0 ) {
					// The opening was on openingStack, so we're closing a node that was opened
					// within data. Don't track that on closingStack
					expectedType = openingStack.pop().type;
				} else {
					// openingStack is empty, so we're closing a node that was already in the
					// document. This means we have to reopen it later, so track this on
					// closingStack
					expectedType = parentNode.getType();
					closingStack.push( parentNode );
					parentNode = parentNode.getParent();
					if ( !parentNode ) {
						throw new Error( 'Inserted data is trying to close the root node ' +
							'(at index ' + index + ')' );
					}
					parentType = expectedType;

					// Validate
					// FIXME this breaks certain input, should fix it up, not scream and die
					// For now we fall back to inserting balanced data, but then we miss out on
					// a lot of the nice content adoption abilities of just fixing up the data in
					// the context of the insertion point - an example of how this will fail is if
					// you try to insert "b</p></li></ul><p>c" into "<p>a[cursor]d</p>"
					if (
						element.type !== '/' + expectedType &&
						(
							// Only throw an error if the content can't be adopted from one content
							// branch to another
							!ve.dm.nodeFactory.canNodeContainContent( element.type.slice( 1 ) ) ||
							!ve.dm.nodeFactory.canNodeContainContent( expectedType )
						)
					) {
						throw new Error( 'Cannot adopt content from ' + element.type +
							' nodes into ' + expectedType + ' nodes (at index ' + index + ')' );
					}
				}
			}
		}
		newData.push( element );
	}

	/**
	 * Close the current element on the stack and arrange for its later reopening
	 *
	 * This function updates parentNode, parentType, closingStack, reopenElements, and closings.
	 *
	 * @private
	 * @param {string} type Current element type we're considering (for error reporting only)
	 */
	function closeElement( type ) {
		// Close the parent and try one level up
		closings.push( { type: '/' + parentType } );
		if ( openingStack.length > 0 ) {
			var element = openingStack.pop();
			parentType = element.type;
			reopenElements.push( ve.copy( element ) );
			// The opening was on openingStack, so we're closing a node that was opened
			// within data. Don't track that on closingStack
		} else {
			if ( !parentNode.getParent() ) {
				throw new Error( 'Cannot insert ' + type + ' even after closing ' +
					'all containing nodes (at index ' + i + ')' );
			}
			// openingStack is empty, so we're closing a node that was already in the
			// document. This means we have to reopen it later, so track this on
			// closingStack
			closingStack.push( parentNode );
			reopenElements.push( parentNode.getClonedElement() );
			parentNode = parentNode.getParent();
			parentType = parentNode.getType();
		}
	}

	parentNode = this.getBranchNodeFromOffset( offset );
	parentType = parentNode.getType();
	inTextNode = false;
	isFirstChild = doc.data.isOpenElementData( offset - 1 );

	for ( i = 0; i < data.length; i++ ) {
		if ( inTextNode && data[ i ].type !== undefined ) {
			parentType = openingStack.length > 0 ?
				openingStack[ openingStack.length - 1 ].type : parentNode.getType();
		}
		if ( data[ i ].type === undefined || data[ i ].type.charAt( 0 ) !== '/' ) {
			childType = data[ i ].type || 'text';
			openings = [];
			closings = [];
			reopenElements = [];
			// Opening or content
			// Make sure that opening this element here does not violate the parent/children/content
			// rules. If it does, insert stuff to fix it

			// If this node is content, check that the containing node can contain content.
			// If not, add a wrapper paragraph
			if ( ve.dm.nodeFactory.isNodeContent( childType ) &&
				!ve.dm.nodeFactory.canNodeContainContent( parentType )
			) {
				childType = 'paragraph';
				var wrapper = ve.dm.nodeFactory.getDataElement( childType );
				ve.setProp( wrapper, 'internal', 'generated', 'wrapper' );
				openings.unshift( wrapper );
			}

			// Check that this node is allowed to have the containing node as its parent. If not,
			// wrap it until it's fixed
			do {
				allowedParents = ve.dm.nodeFactory.getParentNodeTypes( childType );
				parentsOK = allowedParents === null ||
					allowedParents.indexOf( parentType ) !== -1;
				if ( !parentsOK ) {
					// We can't have this as the parent
					if ( allowedParents.length === 0 ) {
						throw new Error( 'Cannot insert ' + childType + ' because it ' +
							' cannot have a parent (at index ' + i + ')' );
					}
					// Open an allowed node around this node
					childType = allowedParents[ 0 ];
					openings.unshift( ve.dm.nodeFactory.getDataElement( childType ) );
				}
			} while ( !parentsOK );

			// Check that the node is allowed to have the containing node as
			// its parent. If not, close surrounding nodes until the node is
			// contained in an acceptable parent.
			suggestedParents = ve.dm.nodeFactory.getSuggestedParentNodeTypes( childType );
			do {
				suggestedParentsOK = suggestedParents === null ||
					suggestedParents.indexOf( parentType ) !== -1;
				if ( !suggestedParentsOK ) {
					closeElement( childType );
				}
			} while ( !suggestedParentsOK );

			// Check that the containing node can have this node as its child. If not, close nodes
			// until it's fixed
			do {
				allowedChildren = ve.dm.nodeFactory.getChildNodeTypes( parentType );
				childrenOK = allowedChildren === null ||
					allowedChildren.indexOf( childType ) !== -1;
				// Also check if we're trying to insert structure into a node that has to contain
				// content
				childrenOK = childrenOK && !(
					!ve.dm.nodeFactory.isNodeContent( childType ) &&
					ve.dm.nodeFactory.canNodeContainContent( parentType )
				);
				if ( !childrenOK ) {
					// We can't insert this into this parent
					if ( isFirstChild ) {
						// This element would be the first child of its parent, so
						// abandon this fix up and try again one offset to the left
						insertion = this.fixupInsertion( data, offset - 1 );
						if ( doc.data.isCloseElementData( offset ) && !ve.dm.nodeFactory.isNodeInternal( parentType ) ) {
							// This element would also be the last child, so that means parent is empty.
							// Remove it entirely. (Never remove the internal list though, ugh...)
							insertion.remove += 2;
						}
						return insertion;
					}

					// Close the parent and try one level up
					closeElement( childType );
				}
			} while ( !childrenOK );

			for ( j = 0; j < closings.length; j++ ) {
				// writeElement() would update openingStack/closingStack, but we've already done
				// that for closings
				if ( i === 0 ) {
					insertedDataOffset++;
				} else {
					insertedDataLength++;
				}
				newData.push( closings[ j ] );
			}
			for ( j = 0; j < openings.length; j++ ) {
				if ( i === 0 ) {
					insertedDataOffset++;
				} else {
					insertedDataLength++;
				}
				writeElement( openings[ j ], i );
			}
			writeElement( data[ i ], i );
			if ( data[ i ].type === undefined ) {
				// Special treatment for text nodes
				inTextNode = true;
				if ( openings.length > 0 ) {
					// We wrapped the text node, update parentType
					parentType = childType;
				}
				// If we didn't wrap the text node, then the node we're inserting into can have
				// content, so we couldn't have closed anything
			} else {
				parentType = data[ i ].type;
			}
		} else {
			// Closing
			writeElement( data[ i ], i );
			parentType = openingStack.length > 0 ?
				openingStack[ openingStack.length - 1 ].type : parentNode.getType();
		}
	}

	if ( closingStack.length > 0 && doc.data.isCloseElementData( offset ) ) {
		// This element would be the last child of its parent, so
		// abandon this fix up and try again one offset to the right
		return this.fixupInsertion( data, offset + 1 );
	}

	if ( inTextNode ) {
		parentType = openingStack.length > 0 ?
			openingStack[ openingStack.length - 1 ].type : parentNode.getType();
	}

	// Close unclosed openings
	while ( openingStack.length > 0 ) {
		popped = openingStack[ openingStack.length - 1 ];
		// writeElement() will perform the actual pop() that removes
		// popped from openingStack
		writeElement( { type: '/' + popped.type }, i );
	}
	// Re-open closed nodes
	while ( closingStack.length > 0 ) {
		popped = closingStack[ closingStack.length - 1 ];
		// writeElement() will perform the actual pop() that removes
		// popped from closingStack
		writeElement( popped.getClonedElement(), i );
	}

	return {
		offset: offset,
		data: newData,
		remove: remove,
		insertedDataOffset: insertedDataOffset !== 0 ? insertedDataOffset : undefined,
		insertedDataLength: insertedDataLength !== newData.length ? insertedDataLength : undefined
	};
};

/**
 * Create a document given an HTML string or document.
 *
 * @param {string|HTMLDocument} html HTML string or document to insert
 * @param {Object} [importRules] The import rules with which to sanitize the HTML, if importing
 * @return {ve.dm.Document} New document
 */
ve.dm.Document.prototype.newFromHtml = function ( html, importRules ) {
	var htmlDoc = typeof html === 'string' ? ve.createDocumentFromHtml( html ) : html,
		doc = ve.dm.converter.getModelFromDom( htmlDoc, {
			targetDoc: this.getHtmlDocument(),
			fromClipboard: !!importRules
		} ),
		data = doc.data;

	if ( importRules ) {
		data.sanitize( importRules.external || {} );
		data.sanitize( importRules.all || {} );
	}

	data.remapInternalListKeys( this.getInternalList() );
	// Initialize node tree
	// BUG T75569: This shouldn't be needed
	doc.buildNodeTree();

	return doc;
};

/**
 * Find a text string within the document
 *
 * @param {string|RegExp} query Text to find, string or regex with no flags
 * @param {Object} [options] Search options
 * @param {boolean} [options.caseSensitiveString] Case sensitive search for a string query. Ignored by regexes (use 'i' flag).
 * @param {boolean} [options.diacriticInsensitiveString] Diacritic insensitive search for a string query. Ignored by regexes.
 *  Only works in browsers which support the Internationalization API
 * @param {boolean} [options.noOverlaps] Avoid overlapping matches
 * @param {boolean} [options.wholeWord] Only match whole-word occurrences
 * @return {ve.Range[]} List of ranges where the string was found
 */
ve.dm.Document.prototype.findText = function ( query, options ) {
	var data = this.data,
		documentRange = this.getDocumentRange(),
		ranges = [];

	options = options || {};

	if ( query instanceof RegExp ) {
		// Avoid multi-line matching by only matching within content (text or content elements)
		data.forEachRunOfContent( documentRange, function ( off, line ) {
			query.lastIndex = 0;
			var match;
			while ( ( match = query.exec( line ) ) !== null ) {
				var matchText = match[ 0 ];

				// Skip empty string matches (e.g. with .*)
				if ( matchText.length === 0 ) {
					// Set lastIndex to the next character to avoid an infinite
					// loop. Browsers differ in whether they do this for you
					// for empty matches; see
					// http://blog.stevenlevithan.com/archives/exec-bugs
					query.lastIndex = match.index + 1;
					continue;
				}

				// Content elements' open/close data is replaced by the replacement character U+FFFC.
				// Ensure that matches of U+FFFC contain the entire element (opening and closing data).
				// The U+FFFC placeholder is only used for elements which "are content" (.static.isContent
				// is true), and such elements are guaranteed to not contain content, so this is safe.
				// Note, however, that this character is allowed to appear in normal text (eww),
				// so we consult the actual document data to make sure we actually matched an element.

				// 1/2: If we matched opening U+FFFC at the end, extend the match forwards by 1.
				if (
					matchText[ matchText.length - 1 ] === '\uFFFC' &&
					data.isOpenElementData( off + match.index + matchText.length - 1 ) &&
					data.isCloseElementData( off + match.index + matchText.length )
				) {
					matchText += '\uFFFC';
					query.lastIndex += 1;
				}

				// 2/2: If we matched closing U+FFFC at the beginning, skip the match.
				// (We do not extend the match backwards to avoid overlapping matches.)
				if (
					matchText[ 0 ] === '\uFFFC' &&
					data.isOpenElementData( off + match.index - 1 ) &&
					data.isCloseElementData( off + match.index )
				) {
					// Continue matching at the next character, rather than the end of this match.
					query.lastIndex = match.index + 1;
					continue;
				}

				ranges.push( new ve.Range(
					off + match.index,
					off + match.index + matchText.length
				) );
				if ( !options.noOverlaps ) {
					query.lastIndex = match.index + 1;
				}
			}
		} );
	} else {
		var qLen = query.length;
		var sensitivity;
		if ( options.diacriticInsensitiveString ) {
			sensitivity = options.caseSensitiveString ? 'case' : 'base';
		} else {
			sensitivity = options.caseSensitiveString ? 'variant' : 'accent';
		}
		// Intl is only used browser clients
		var compare = new Intl.Collator( this.lang, { sensitivity: sensitivity } ).compare;
		// Iterate up to (and including) offset textLength - queryLength. Beyond that point
		// there is not enough room for the query to exist
		for ( var offset = 0, l = documentRange.getLength() - qLen; offset <= l; offset++ ) {
			var j = 0;
			while ( compare( data.getCharacterData( offset + j ), query[ j ] ) === 0 ) {
				j++;
				if ( j === qLen ) {
					ranges.push( new ve.Range( offset, offset + qLen ) );
					offset += options.noOverlaps ? qLen - 1 : 0;
					break;
				}
			}
		}
	}

	if ( options.wholeWord ) {
		var dataString = new ve.dm.DataString( this.getData() );
		ranges = ranges.filter( function ( range ) {
			return unicodeJS.wordbreak.isBreak( dataString, range.start ) &&
				unicodeJS.wordbreak.isBreak( dataString, range.end );
		} );
	}

	return ranges;
};

/**
 * Get the length of the complete history. This is also the current pointer.
 *
 * @return {number} Length of the complete history stack
 */
ve.dm.Document.prototype.getCompleteHistoryLength = function () {
	return this.completeHistory.getLength();
};

/**
 * Get all the transactions in the complete history since a specified pointer.
 *
 * @param {number} start Pointer from where to start the slice
 * @return {ve.dm.Transaction[]} Array of transaction objects
 */
ve.dm.Document.prototype.getCompleteHistorySince = function ( start ) {
	return this.completeHistory.transactions.slice( start );
};

/**
 * Single change containing most recent transactions in history stack
 *
 * @param {number} start Pointer from where to start slicing transactions
 * @return {ve.dm.Change} Single change containing transactions since pointer
 */
ve.dm.Document.prototype.getChangeSince = function ( start ) {
	var change = this.completeHistory.mostRecent( start );
	// Remove any selections that might have been added by e.g. ve.dm.Change#addToHistory
	change.selections = {};
	return change;
};

/**
 * Get the content language
 *
 * @return {string} Language code
 */
ve.dm.Document.prototype.getLang = function () {
	return this.lang;
};

/**
 * Get the content directionality
 *
 * @return {string} Directionality (ltr/rtl)
 */
ve.dm.Document.prototype.getDir = function () {
	return this.dir;
};

/**
 * Set a key/value pair in persistent static storage, or restore the whole store
 *
 * Storage is used for static variables related to document state,
 * such as InternalList's nextUniqueNumber.
 *
 * @param {string|Object} [keyOrStorage] Key, or storage object to restore
 * @param {Mixed} [value] Serializable value, if key is set
 * @fires storage
 */
ve.dm.Document.prototype.setStorage = function ( keyOrStorage, value ) {
	if ( typeof keyOrStorage === 'string' ) {
		this.persistentStorage[ keyOrStorage ] = value;
		this.emit( 'storage' );
	} else {
		this.persistentStorage = keyOrStorage;
	}
};

/**
 * Get a value from the persistent static storage, or the whole store
 *
 * @param {string} [key] Key
 * @return {Mixed|Object} Value at key, or whole storage object if key not provided
 */
ve.dm.Document.prototype.getStorage = function ( key ) {
	if ( key ) {
		return this.persistentStorage[ key ];
	} else {
		return this.persistentStorage;
	}
};
