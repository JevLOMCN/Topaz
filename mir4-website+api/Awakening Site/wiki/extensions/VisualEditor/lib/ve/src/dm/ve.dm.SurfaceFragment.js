/*!
 * VisualEditor DataModel Fragment class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel surface fragment.
 *
 * @class
 *
 * @constructor
 * @param {ve.dm.Surface} surface Target surface
 * @param {ve.dm.Selection} [selection] Selection within target document, current selection used by default
 * @param {boolean} [noAutoSelect] Don't update the surface's selection when making changes
 * @param {boolean} [excludeInsertions] Exclude inserted content at the boundaries when updating range
 */
ve.dm.SurfaceFragment = function VeDmSurfaceFragment( surface, selection, noAutoSelect, excludeInsertions ) {
	// Short-circuit for missing-surface null fragment
	if ( !surface ) {
		return this;
	}

	// Properties
	this.document = surface.getDocument();
	this.noAutoSelect = !!noAutoSelect;
	this.excludeInsertions = !!excludeInsertions;
	this.surface = surface;
	this.selection = selection || surface.getSelection();
	this.leafNodes = null;
	this.pending = [];

	// Initialization
	this.historyPointer = this.document.getCompleteHistoryLength();
};

/* Inheritance */

OO.initClass( ve.dm.SurfaceFragment );

/* Methods */

/**
 * Get list of selected nodes and annotations.
 *
 * @param {boolean} [all] Include nodes and annotations which only cover some of the fragment
 * @return {ve.dm.Model[]} Selected models
 */
ve.dm.SurfaceFragment.prototype.getSelectedModels = function ( all ) {
	// Handle null selection
	if ( this.isNull() ) {
		return [];
	}

	var annotations = this.getAnnotations( all );

	// Filter out nodes with collapsed ranges
	var nodes;
	if ( all ) {
		nodes = this.getCoveredNodes();
		for ( var i = 0, len = nodes.length; i < len; i++ ) {
			if ( nodes[ i ].range && nodes[ i ].range.isCollapsed() ) {
				nodes.splice( i, 1 );
				len--;
				i--;
			} else {
				nodes[ i ] = nodes[ i ].node;
			}
		}
	} else {
		nodes = [];
		var selectedNode = this.getSelectedNode();
		if ( selectedNode ) {
			nodes.push( selectedNode );
		}
	}

	return nodes.concat( !annotations.isEmpty() ? annotations.get() : [] );
};

/**
 * Update selection based on un-applied transactions in the surface, or specified selection.
 *
 * @param {ve.dm.Selection} [selection] Optional selection to set
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.update = function ( selection ) {
	// Handle null selection
	if ( this.isNull() ) {
		return this;
	}

	if ( selection && !selection.equals( this.selection ) ) {
		this.selection = selection;
		this.leafNodes = null;
		this.historyPointer = this.document.getCompleteHistoryLength();
	} else if ( this.historyPointer < this.document.getCompleteHistoryLength() ) {
		// Small optimisation: check history pointer is in the past
		var txs = this.document.getCompleteHistorySince( this.historyPointer );
		this.selection = this.selection.translateByTransactions( txs, this.excludeInsertions );
		this.leafNodes = null;
		this.historyPointer += txs.length;
	}
	return this;
};

/**
 * Process a set of transactions on the surface, and update the selection if the fragment
 * is auto-selecting.
 *
 * @param {ve.dm.Transaction|ve.dm.Transaction[]} txs Transaction(s) to process
 * @param {ve.dm.Selection} [selection] Selection to set, if different from translated selection, required if the
 *   fragment is null
 * @throws {Error} If fragment is null and selection is omitted
 */
ve.dm.SurfaceFragment.prototype.change = function ( txs, selection ) {
	if ( !selection && this.isNull() ) {
		throw new Error( 'Cannot change null fragment without selection' );
	}

	if ( !Array.isArray( txs ) ) {
		txs = [ txs ];
	}
	this.surface.change(
		txs,
		!this.noAutoSelect && ( selection || this.getSelection().translateByTransactions( txs, this.excludeInsertions ) )
	);
	if ( selection ) {
		// Overwrite the selection
		this.update( selection );
	}
};

/**
 * Get the surface the fragment is a part of.
 *
 * @return {ve.dm.Surface|null} Surface of fragment
 */
ve.dm.SurfaceFragment.prototype.getSurface = function () {
	return this.surface;
};

/**
 * Get the document of the surface the fragment is a part of.
 *
 * @return {ve.dm.Document|null} Document of surface of fragment
 */
ve.dm.SurfaceFragment.prototype.getDocument = function () {
	return this.document;
};

/**
 * Get the selection of the fragment within the surface.
 *
 * This method also calls update to make sure the selection returned is current.
 *
 * @return {ve.dm.Selection} The selection
 */
ve.dm.SurfaceFragment.prototype.getSelection = function () {
	this.update();
	return this.selection;
};

/**
 * Check if the fragment is null.
 *
 * @return {boolean} Fragment is a null fragment
 */
ve.dm.SurfaceFragment.prototype.isNull = function () {
	return this.selection.isNull();
};

/**
 * Check if the surface's selection will be updated automatically when changes are made.
 *
 * @return {boolean} Will automatically update surface selection
 */
ve.dm.SurfaceFragment.prototype.willAutoSelect = function () {
	return !this.noAutoSelect;
};

/**
 * Change whether to automatically update the surface selection when making changes.
 *
 * @param {boolean} [autoSelect=true] Automatically update surface selection
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.setAutoSelect = function ( autoSelect ) {
	this.noAutoSelect = !autoSelect;
	return this;
};

/**
 * Get a clone of this SurfaceFragment, optionally with a different selection.
 *
 * @param {ve.dm.Selection} [selection] If set, use this selection rather than the old fragment's selection
 * @return {ve.dm.SurfaceFragment} Clone of this fragment
 */
ve.dm.SurfaceFragment.prototype.clone = function ( selection ) {
	return new this.constructor(
		this.surface,
		selection || this.getSelection(),
		this.noAutoSelect,
		this.excludeInsertions
	);
};

/**
 * Check whether updates to this fragment's selection will exclude content inserted at the boundaries.
 *
 * @return {boolean} Selection updates will exclude insertions
 */
ve.dm.SurfaceFragment.prototype.willExcludeInsertions = function () {
	return this.excludeInsertions;
};

/**
 * Tell this fragment whether it should exclude insertions. If this option is enabled, updates to
 * this fragment's selection in response to transactions will not include content inserted at the
 * boundaries of the selection; if it is disabled, insertions will be included.
 *
 * @param {boolean} excludeInsertions Whether to exclude insertions
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.setExcludeInsertions = function ( excludeInsertions ) {
	excludeInsertions = !!excludeInsertions;
	if ( this.excludeInsertions !== excludeInsertions ) {
		// Process any deferred updates with the old value
		this.update();
		// Set the new value
		this.excludeInsertions = excludeInsertions;
	}
	return this;
};

/**
 * Get a new fragment with an adjusted position
 *
 * @param {number} [start] Adjustment for start position
 * @param {number} [end] Adjustment for end position
 * @return {ve.dm.SurfaceFragment} Adjusted fragment
 */
ve.dm.SurfaceFragment.prototype.adjustLinearSelection = function ( start, end ) {
	if ( !( this.selection instanceof ve.dm.LinearSelection ) ) {
		return this.clone();
	}
	var oldRange = this.getSelection().getRange();
	var newRange = oldRange && new ve.Range( oldRange.start + ( start || 0 ), oldRange.end + ( end || 0 ) );
	return this.clone( new ve.dm.LinearSelection( newRange ) );
};

/**
 * Get a new fragment with a truncated length.
 *
 * @param {number} limit Maximum length of new range (negative for left-side truncation)
 * @return {ve.dm.SurfaceFragment} Truncated fragment
 */
ve.dm.SurfaceFragment.prototype.truncateLinearSelection = function ( limit ) {
	if ( !( this.selection instanceof ve.dm.LinearSelection ) ) {
		return this.clone();
	}
	var range = this.getSelection().getRange();
	return this.clone( new ve.dm.LinearSelection( range.truncate( limit ) ) );
};

/**
 * Get a new fragment with a zero-length selection at the start offset.
 *
 * @return {ve.dm.SurfaceFragment} Collapsed fragment
 */
ve.dm.SurfaceFragment.prototype.collapseToStart = function () {
	return this.clone( this.getSelection().collapseToStart() );
};

/**
 * Get a new fragment with a zero-length selection at the end offset.
 *
 * @return {ve.dm.SurfaceFragment} Collapsed fragment
 */
ve.dm.SurfaceFragment.prototype.collapseToEnd = function () {
	return this.clone( this.getSelection().collapseToEnd() );
};

/**
 * Get a new fragment with a range that no longer includes leading and trailing whitespace.
 *
 * @return {ve.dm.SurfaceFragment} Trimmed fragment
 */
ve.dm.SurfaceFragment.prototype.trimLinearSelection = function () {
	if ( !( this.selection instanceof ve.dm.LinearSelection ) ) {
		return this.clone();
	}
	var oldRange = this.getSelection().getRange();
	var newRange;

	if ( this.getText().trim().length === 0 ) {
		// oldRange is only whitespace
		newRange = new ve.Range( oldRange.start );
	} else {
		newRange = this.document.data.trimOuterSpaceFromRange( oldRange );
	}

	return this.clone( new ve.dm.LinearSelection( newRange ) );
};

/**
 * Get a new fragment that covers an expanded range of the document.
 *
 * @param {string} [scope='parent'] Method of expansion:
 *  - `word`: Expands to cover the nearest word by looking for word breaks (see UnicodeJS.wordbreak)
 *  - `annotation`: Expands to cover a given annotation type (ve.dm.Annotation) within the current range
 *  - `root`: Expands to cover the entire document
 *  - `siblings`: Expands to cover all sibling nodes
 *  - `closest`: Expands to cover the closest common ancestor node of a give type (ve.dm.Node)
 *  - `parent`: Expands to cover the closest common parent node
 * @param {ve.dm.Annotation|ve.dm.Node} [type] Parameter to use with scope method if needed
 * @return {ve.dm.SurfaceFragment} Expanded fragment
 */
ve.dm.SurfaceFragment.prototype.expandLinearSelection = function ( scope, type ) {
	if ( !( this.selection instanceof ve.dm.LinearSelection ) ) {
		return this.clone();
	}

	var oldRange = this.getSelection().getRange();
	var newRange;
	var nodes, node, parent;

	switch ( scope || 'parent' ) {
		case 'word':
			if ( !oldRange.isCollapsed() ) {
				newRange = ve.Range.static.newCoveringRange( [
					this.document.data.getWordRange( oldRange.start ),
					this.document.data.getWordRange( oldRange.end )
				], oldRange.isBackwards() );
			} else {
				// Optimisation for zero-length ranges
				newRange = this.document.data.getWordRange( oldRange.start );
			}
			break;
		case 'annotation':
			newRange = this.document.data.getAnnotatedRangeFromSelection( oldRange, type );
			// Adjust selection if it does not contain the annotated range
			if ( oldRange.start > newRange.start || oldRange.end < newRange.end ) {
				// Maintain range direction
				if ( oldRange.from > oldRange.to ) {
					newRange = newRange.flip();
				}
			} else {
				// Otherwise just keep the range as is
				newRange = oldRange;
			}
			break;
		case 'root':
			newRange = this.getDocument().getDocumentRange();
			break;
		case 'siblings':
			// Grow range to cover all siblings
			nodes = this.document.selectNodes( oldRange, 'siblings' );
			if ( nodes.length === 1 ) {
				newRange = nodes[ 0 ].node.getOuterRange();
			} else {
				newRange = new ve.Range(
					nodes[ 0 ].node.getOuterRange().start,
					nodes[ nodes.length - 1 ].node.getOuterRange().end
				);
			}
			break;
		case 'closest':
			// Grow range to cover closest common ancestor node of given type
			nodes = this.document.selectNodes( oldRange, 'siblings' );
			// If the range covered the entire node check that node
			if ( nodes[ 0 ].nodeRange.equalsSelection( oldRange ) && nodes[ 0 ].node instanceof type ) {
				newRange = nodes[ 0 ].nodeOuterRange;
				break;
			}
			parent = nodes[ 0 ].node.getParent();
			while ( parent && !( parent instanceof type ) ) {
				node = parent;
				parent = parent.getParent();
			}
			if ( parent ) {
				newRange = parent.getOuterRange();
			}
			break;
		case 'parent':
			// Grow range to cover the closest common parent node
			node = this.document.selectNodes( oldRange, 'siblings' )[ 0 ].node;
			parent = node.getParent();
			if ( parent ) {
				newRange = parent.getOuterRange();
			}
			break;
		default:
			throw new Error( 'Invalid scope argument: ' + scope );
	}
	return this.clone(
		newRange ?
			new ve.dm.LinearSelection( newRange ) :
			new ve.dm.NullSelection()
	);
};

/**
 * Get data for the fragment.
 *
 * @param {boolean} [deep] Get a deep copy of the data
 * @return {Array} Fragment data
 */
ve.dm.SurfaceFragment.prototype.getData = function ( deep ) {
	var range = this.getSelection().getCoveringRange();
	if ( !range ) {
		return [];
	}
	return this.document.getData( range, deep );
};

/**
 * Get plain text for the fragment.
 *
 * @param {boolean} [maintainIndices] Maintain data offset to string index alignment by replacing elements with line breaks
 * @return {string} Fragment text
 */
ve.dm.SurfaceFragment.prototype.getText = function ( maintainIndices ) {
	var range = this.getSelection().getCoveringRange();
	if ( !range ) {
		return '';
	}
	return this.document.data.getText( maintainIndices, range );
};

/**
 * Whether the fragment contains only text, allowing annotations
 *
 * @method
 * @return {boolean} Whether there's only text
 */
ve.dm.SurfaceFragment.prototype.containsOnlyText = function () {
	var range = this.getSelection().getCoveringRange();
	if ( !range ) {
		return true;
	}
	return this.document.data.isPlainText( range, false, false, false, true );
};

/**
 * Get annotations in fragment.
 *
 * By default, this will only get annotations that completely cover the fragment. Use the {all}
 * argument to get all annotations that occur within the fragment.
 *
 * @param {boolean} [all] Get annotations which only cover some of the fragment
 * @return {ve.dm.AnnotationSet} All annotation objects range is covered by
 */
ve.dm.SurfaceFragment.prototype.getAnnotations = function ( all ) {
	var selection = this.getSelection();

	if ( selection.isCollapsed() ) {
		return this.surface.getInsertionAnnotations();
	} else {
		var annotations = null;
		var ranges = selection.getRanges( this.getDocument() );
		for ( var i = 0, l = ranges.length; i < l; i++ ) {
			var rangeAnnotations = this.getDocument().data.getAnnotationsFromRange( ranges[ i ], all, true );
			if ( !rangeAnnotations ) {
				continue;
			}
			if ( !annotations ) {
				annotations = rangeAnnotations;
			} else if ( all ) {
				annotations.addSet( rangeAnnotations );
			} else {
				var matchingAnnotations = rangeAnnotations.getComparableAnnotationsFromSet( annotations );
				if ( matchingAnnotations.isEmpty() ) {
					// Nothing matched so our intersection is empty
					annotations = matchingAnnotations;
					break;
				} else {
					// Match in the other direction, to keep all distinct compatible annotations (e.g. both b and strong)
					annotations = annotations.getComparableAnnotationsFromSet( rangeAnnotations );
					annotations.addSet( matchingAnnotations );
				}
			}
		}
		return annotations || new ve.dm.AnnotationSet( this.getDocument().getStore() );
	}
};

/**
 * Check if the fragment has any annotations
 *
 * Quicker than doing !fragment.getAnnotations( true ).isEmpty() as
 * it stops at the first sight of an annotation.
 *
 * @return {boolean} The fragment contains at least one annotation
 */
ve.dm.SurfaceFragment.prototype.hasAnnotations = function () {
	var ranges = this.getSelection().getRanges( this.getDocument() );

	for ( var i = 0, l = ranges.length; i < l; i++ ) {
		if ( this.getDocument().data.hasAnnotationsInRange( ranges[ i ] ) ) {
			return true;
		}
	}
	return false;
};

/**
 * Get all leaf nodes covered by the fragment.
 *
 * @see ve.Document#selectNodes Used to get the return value
 *
 * @return {Array} List of nodes and related information
 */
ve.dm.SurfaceFragment.prototype.getLeafNodes = function () {
	var range = this.getSelection().getCoveringRange();
	if ( !range ) {
		return [];
	}

	// Update in case the cache needs invalidating
	this.update();
	// Cache leafNodes because it's expensive to compute
	if ( !this.leafNodes ) {
		this.leafNodes = this.document.selectNodes( range, 'leaves' );
	}
	return this.leafNodes;
};

/**
 * Get all leaf nodes excluding nodes where the selection is empty.
 *
 * @return {ve.dm.Node[]} List of nodes and related information
 */
ve.dm.SurfaceFragment.prototype.getSelectedLeafNodes = function () {
	var selectedLeafNodes = [],
		leafNodes = this.getLeafNodes();
	for ( var i = 0, len = leafNodes.length; i < len; i++ ) {
		if ( len === 1 || !leafNodes[ i ].range || !leafNodes[ i ].range.isCollapsed() ) {
			selectedLeafNodes.push( leafNodes[ i ].node );
		}
	}
	return selectedLeafNodes;
};

/**
 * Get the node selected by a range, i.e. the range matches the node's range exactly.
 *
 * Note that this method operates on the fragment's range, not the document's current selection.
 * This fragment does not need to be selected for this method to work.
 *
 * @return {ve.dm.Node|null} The node selected by the range, or null if a node is not selected
 */
ve.dm.SurfaceFragment.prototype.getSelectedNode = function () {
	var surface = this.getSurface();

	// Ensure the fragment is up to date
	this.update();
	return this.selection.equals( surface.getSelection() ) ?
		// If the selection is equal to the surface's use the cached node
		surface.getSelectedNode() :
		surface.getSelectedNodeFromSelection( this.selection );
};

/**
 * Get nodes covered by the fragment.
 *
 * Does not descend into nodes that are entirely covered by the range. The result is
 * similar to that of {ve.dm.SurfaceFragment.prototype.getLeafNodes} except that if a node is
 * entirely covered, its children aren't returned separately.
 *
 * @see ve.Document#selectNodes for more information about the return value
 *
 * @return {Array} List of nodes and related information
 */
ve.dm.SurfaceFragment.prototype.getCoveredNodes = function () {
	var range = this.getSelection().getCoveringRange();
	if ( !range ) {
		return [];
	}
	return this.document.selectNodes( range, 'covered' );
};

/**
 * Get nodes covered by the fragment.
 *
 * Includes adjacent siblings covered by the range, descending if the range is in a single node.
 *
 * @see ve.Document#selectNodes for more information about the return value.
 *
 * @return {Array} List of nodes and related information
 */
ve.dm.SurfaceFragment.prototype.getSiblingNodes = function () {
	var range = this.getSelection().getCoveringRange();
	if ( !range ) {
		return [];
	}
	return this.document.selectNodes( range, 'siblings' );
};

/**
 * Check if the nodes at the current fragment have an ancestor with matching type and attribute values.
 *
 * @param {string} type Node type to match
 * @param {Object} [attributes] Node attributes to match
 * @param {boolean} [matchFirstAncestorOfType] Require the match to be the first of its type, e.g. if type is 'list',
 *  only match the first 'list' ancestor, then check if the attributes match.
 * @return {boolean} Nodes have a matching ancestor
 */
ve.dm.SurfaceFragment.prototype.hasMatchingAncestor = function ( type, attributes, matchFirstAncestorOfType ) {
	var selection = this.getSelection();

	var all;
	if ( selection instanceof ve.dm.LinearSelection ) {
		var nodes = this.getSelectedLeafNodes();
		all = !!nodes.length;
		for ( var i = 0, len = nodes.length; i < len; i++ ) {
			if ( matchFirstAncestorOfType ) {
				var node = nodes[ i ].findMatchingAncestor( type );
				if ( !( node && node.compareAttributes( attributes ) ) ) {
					all = false;
					break;
				}
			} else {
				if ( !nodes[ i ].hasMatchingAncestor( type, attributes ) ) {
					all = false;
					break;
				}
			}
		}
	} else if ( selection instanceof ve.dm.TableSelection ) {
		var cells = selection.getMatrixCells( this.getDocument() );
		all = true;
		for ( var j = cells.length - 1; j >= 0; j-- ) {
			if ( !cells[ j ].node.matches( type, attributes ) ) {
				all = false;
				break;
			}
		}
	}

	return all;
};

/**
 * Clear a fragment's pending list
 */
ve.dm.SurfaceFragment.prototype.clearPending = function () {
	this.pending = [];
};

/**
 * Push a promise to the fragment's pending list
 *
 * @param {jQuery.Promise} promise
 */
ve.dm.SurfaceFragment.prototype.pushPending = function ( promise ) {
	this.pending.push( promise );
};

/**
 * Get a promise that resolves when the pending list is complete
 *
 * @return {jQuery.Promise}
 */
ve.dm.SurfaceFragment.prototype.getPending = function () {
	return ve.promiseAll( this.pending );
};

/**
 * Apply the fragment's range to the surface as a selection.
 *
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.select = function () {
	this.surface.setSelection( this.getSelection() );
	return this;
};

/**
 * Change one or more attributes on covered nodes.
 *
 * @param {Object} attr List of attributes to change, use undefined to remove an attribute
 * @param {string} [type] Node type to restrict changes to
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.changeAttributes = function ( attr, type ) {
	var txs = [],
		covered = this.getCoveredNodes();

	for ( var i = 0, len = covered.length; i < len; i++ ) {
		var result = covered[ i ];
		if (
			// Non-wrapped nodes have no attributes
			!result.node.isWrapped() ||
			// Filtering by node type
			( type && result.node.getType() !== type ) ||
			// Ignore zero-length results
			( result.range && result.range.isCollapsed() )
		) {
			continue;
		}
		txs.push(
			ve.dm.TransactionBuilder.static.newFromAttributeChanges(
				this.document, result.nodeOuterRange.start, attr
			)
		);
	}
	if ( txs.length ) {
		this.change( txs );
	}
	return this;
};

/**
 * Apply an annotation to content in the fragment.
 *
 * To avoid problems identified in T35108, use the {ve.dm.SurfaceFragment.trimLinearSelection} method.
 *
 * TODO: Optionally take an annotation set instead of name and data arguments and set/clear multiple
 * annotations in a single transaction.
 *
 * @param {string} method Mode of annotation, either 'set' or 'clear'
 * @param {string|ve.dm.Annotation|ve.dm.AnnotationSet} nameOrAnnotations Annotation name, for example: 'textStyle/bold',
 *  Annotation object or AnnotationSet
 * @param {Object} [data] Additional annotation data (not used if annotation object is given)
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.annotateContent = function ( method, nameOrAnnotations, data ) {
	var i, ilen,
		annotations = new ve.dm.AnnotationSet( this.getDocument().getStore() ),
		ranges = this.getSelection().getRanges( this.getDocument() ),
		txs = [];

	if ( nameOrAnnotations instanceof ve.dm.AnnotationSet ) {
		annotations = nameOrAnnotations;
	} else if ( nameOrAnnotations instanceof ve.dm.Annotation ) {
		annotations.push( nameOrAnnotations );
	} else {
		var annotation = ve.dm.annotationFactory.create( nameOrAnnotations, data );
		if ( method === 'set' ) {
			annotations.push( annotation );
		} else if ( method === 'clear' ) {
			for ( i = 0, ilen = ranges.length; i < ilen; i++ ) {
				annotations.addSet(
					this.document.data.getAnnotationsFromRange( ranges[ i ], true ).getAnnotationsByName( annotation.name )
				);
			}
		}
	}
	for ( i = 0, ilen = ranges.length; i < ilen; i++ ) {
		var range = ranges[ i ];
		if ( !range.isCollapsed() ) {
			// Apply to selection
			for ( var j = 0, jlen = annotations.getLength(); j < jlen; j++ ) {
				var tx = ve.dm.TransactionBuilder.static.newFromAnnotation( this.document, range, method, annotations.get( j ) );
				txs.push( tx );
			}
		} else {
			// Apply annotation to stack
			if ( method === 'set' ) {
				this.surface.addInsertionAnnotations( annotations );
			} else if ( method === 'clear' ) {
				this.surface.removeInsertionAnnotations( annotations );
			}
		}
	}
	this.change( txs );

	return this;
};

/**
 * Remove content in the fragment and insert content before it.
 *
 * This will move the fragment's range to cover the inserted content. Note that this may be
 * different from what a normal range translation would do: the insertion might occur
 * at a different offset if that is needed to make the document balanced.
 *
 * If the content is a plain text string containing linebreaks, each line will be wrapped
 * in a paragraph.
 *
 * @param {string|Array} content Content to insert, can be either a string or array of data
 * @param {boolean|ve.dm.AnnotationSet} [annotateOrSet] Content should be automatically annotated to match surrounding content,
 *  or an AnnotationSet from the current offset (calculated from the view)
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.insertContent = function ( content, annotateOrSet ) {
	var range = this.getSelection().getCoveringRange(),
		doc = this.getDocument();

	if ( !range ) {
		return this;
	}

	var annotations, annotate;
	if ( annotateOrSet instanceof ve.dm.AnnotationSet ) {
		annotations = annotateOrSet;
		annotate = true;
	} else {
		annotate = annotateOrSet;
	}

	if ( !range.isCollapsed() ) {
		if ( annotate ) {
			// If we're replacing content, use the annotations selected
			// instead of continuing from the left
			annotations = this.getAnnotations();
		}
		this.removeContent();
	}

	var offset = range.start;
	// Auto-convert content to array of plain text characters
	if ( typeof content === 'string' ) {
		var lines = content.split( /[\r\n]+/ );

		if ( lines.length > 1 ) {
			content = [];
			for ( var i = 0, l = lines.length; i < l; i++ ) {
				if ( lines[ i ].length ) {
					content.push( { type: 'paragraph' } );
					ve.batchPush( content, lines[ i ].split( '' ) );
					content.push( { type: '/paragraph' } );
				}
			}
		} else {
			content = content.split( '' );
		}
	}
	if ( content.length ) {
		if ( annotate && !annotations ) {
			// TODO T126021: Don't reach into properties of document
			// FIXME T126022: the logic we actually need for annotating inserted content
			// correctly is much more complicated
			annotations = doc.data
				.getAnnotationsFromOffset( offset === 0 ? 0 : offset - 1 );
		}
		if ( annotations && annotations.getLength() > 0 ) {
			// Add the annotations to the content, passing the
			// replaceComparable argument which will remove any comparable
			// annotations from content so that they can be replaced with
			// those in annotations. This has the effect of not double-
			// wrapping any annotations which are identical apart from their
			// original DOM element so we don't generate markup like
			// <b>f<b>o</b>o</b> when pasting.
			ve.dm.Document.static.addAnnotationsToData( content, annotations, true, doc.store );
		}
		var tx = ve.dm.TransactionBuilder.static.newFromInsertion( doc, offset, content );
		// Set the range to cover the inserted content; the offset translation will be wrong
		// if newFromInsertion() decided to move the insertion point
		var newRange = tx.getModifiedRange( doc );
		this.change( tx, newRange ? new ve.dm.LinearSelection( newRange ) : new ve.dm.NullSelection() );
	}

	return this;
};

/**
 * Insert HTML in the fragment.
 *
 * This will move the fragment's range to cover the inserted content. Note that this may be
 * different from what a normal range translation would do: the insertion might occur
 * at a different offset if that is needed to make the document balanced.
 *
 * @param {string} html HTML to insert
 * @param {Object} [importRules] The import rules for the target surface, if importing
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.insertHtml = function ( html, importRules ) {
	this.insertDocument( this.getDocument().newFromHtml( html, importRules ) );
	return this;
};

/**
 * Insert a ve.dm.Document in the fragment.
 *
 * This will move the fragment's range to cover the inserted content. Note that this may be
 * different from what a normal range translation would do: the insertion might occur
 * at a different offset if that is needed to make the document balanced.
 *
 * @param {ve.dm.Document} newDoc Document to insert
 * @param {ve.Range} [newDocRange] Range from the new document to insert (defaults to entire document)
 * @param {boolean|ve.dm.AnnotationSet} [annotateOrSet] Content should be automatically annotated to match surrounding content,
 *  or an AnnotationSet from the current offset (calculated from the view)
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.insertDocument = function ( newDoc, newDocRange, annotateOrSet ) {
	var range = this.getSelection().getCoveringRange(),
		doc = this.getDocument();

	if ( !range ) {
		return this;
	}

	var annotations, annotate;
	if ( annotateOrSet instanceof ve.dm.AnnotationSet ) {
		annotations = annotateOrSet;
		annotate = true;
	} else {
		annotate = annotateOrSet;
	}

	if ( !range.isCollapsed() ) {
		if ( annotate ) {
			// If we're replacing content, use the annotations selected
			// instead of continuing from the left
			annotations = this.getAnnotations();
		}
		this.removeContent();
	}

	var offset = range.start;
	if ( annotate && !annotations ) {
		// TODO T126021: Don't reach into properties of document
		annotations = doc.data
			.getAnnotationsFromOffset( offset === 0 ? 0 : offset - 1 );
	}

	var annotatedDoc;
	if ( !annotations || annotations.getLength() === 0 ) {
		annotatedDoc = newDoc;
	} else {
		// Build shallow-cloned annotatedData array, copying on write as we go
		// FIXME T126022: the logic we actually need for annotating inserted content
		// correctly is much more complicated
		var annotatedData = newDoc.data.slice();
		ve.dm.Document.static.addAnnotationsToData( annotatedData, annotations, true, newDoc.store, true );
		annotatedDoc = newDoc.cloneWithData( annotatedData );
	}
	var tx = ve.dm.TransactionBuilder.static.newFromDocumentInsertion( doc, offset, annotatedDoc, newDocRange );
	if ( !tx.isNoOp() ) {
		// Set the range to cover the inserted content; the offset translation will be wrong
		// if newFromInsertion() decided to move the insertion point
		var newRange = tx.getModifiedRange( doc );
		this.change( tx, newRange ? new ve.dm.LinearSelection( newRange ) : new ve.dm.NullSelection() );
	}
	return this;
};

/**
 * Remove content in the fragment.
 *
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.removeContent = function () {
	var range = this.getSelection().getCoveringRange();
	if ( !range ) {
		return this;
	}

	if ( !range.isCollapsed() ) {
		this.change( ve.dm.TransactionBuilder.static.newFromRemoval( this.document, range ) );
	}

	return this;
};

/**
 * Delete content and correct selection
 *
 * @param {number} [directionAfterDelete=-1] Direction to move after delete: 1 or -1 or 0
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.delete = function ( directionAfterDelete ) {
	var rangeToRemove = this.getSelection().getCoveringRange();

	if ( !rangeToRemove || rangeToRemove.isCollapsed() ) {
		return this;
	}

	// Try to build a removal transaction. At the moment the transaction processor is only
	// capable of merging nodes of the same type and at the same depth level, so some or all
	// of rangeToRemove may be left untouched (and in some cases tx may not remove anything
	// at all).
	var tx = ve.dm.TransactionBuilder.static.newFromRemoval( this.document, rangeToRemove );
	this.change( tx );
	var rangeAfterRemove = tx.translateRange( rangeToRemove );

	var endNode;
	if (
		!rangeAfterRemove.isCollapsed() &&
		( endNode = this.document.getBranchNodeFromOffset( rangeAfterRemove.end, false ) ) &&
		// If endNode is within our rangeAfterRemove, then we shouldn't delete it
		endNode.getRange().start >= rangeAfterRemove.end
	) {
		// If after processing removal transaction range is not collapsed it means that
		// not everything got merged nicely, so we process further to deal with
		// remaining content.

		var startNode = this.document.getBranchNodeFromOffset( rangeAfterRemove.start, false );
		if ( startNode.getRange().isCollapsed() ) {
			// If startNode has no content then just delete that node instead of
			// moving content from endNode to startNode. This prevents content being
			// inserted into empty structure, e.g. and empty heading will be deleted
			// rather than "converting" the paragraph beneath to a heading.
			while ( true ) {
				tx = ve.dm.TransactionBuilder.static.newFromRemoval( this.document, startNode.getOuterRange() );
				startNode = startNode.getParent();
				this.change( tx );

				// If the removal resulted in the parent node being empty (e.g.
				// when startNode was a paragraph inside a list item), loop to
				// delete the parent node. Else break.
				if ( !( startNode && startNode.children.length === 0 && (
					startNode.hasSlugAtOffset( startNode.getRange().start ) ||
					// These would be uneditable when empty, so remove
					startNode instanceof ve.dm.DefinitionListNode ||
					startNode instanceof ve.dm.ListNode
				) && startNode.canHaveChildrenNotContent() ) ) {
					break;
				}
				// Only fix up the range if we're going to loop (if we're not, the
				// range collapse using getNearestContentOffset below will already
				// do the fix up).
				rangeAfterRemove = tx.translateRange( rangeAfterRemove );
			}
		} else {
			// If startNode has content then take remaining content from endNode and
			// append it into startNode. Then remove endNode (and recursively any
			// ancestor that the removal causes to be empty).
			var endNodeData = this.document.getData( endNode.getRange() );
			var nodeToDelete = endNode;
			nodeToDelete.traverseUpstream( function ( node ) {
				var parent = node.getParent();
				if ( parent.children.length === 1 ) {
					nodeToDelete = parent;
					return true;
				} else {
					return false;
				}
			} );
			tx = ve.dm.TransactionBuilder.static.newFromRemoval(
				this.document,
				nodeToDelete.getOuterRange()
			);
			if ( !tx.isNoOp() ) {
				// Move contents of endNode into startNode, and delete nodeToDelete
				this.change( tx );
				this.change( ve.dm.TransactionBuilder.static.newFromInsertion(
					this.document,
					rangeAfterRemove.start,
					endNodeData
				) );
			}
		}
	}

	// Use a collapsed range at a content offset beside rangeAfterRemove.start
	var nearestOffset = this.document.data.getNearestContentOffset(
		rangeAfterRemove.start,
		// If undefined (e.g. cut), default to backwards movement
		directionAfterDelete || -1
	);
	if ( nearestOffset > -1 ) {
		rangeAfterRemove = new ve.Range( nearestOffset );
	} else {
		// There isn't a valid content offset. This probably means that we're
		// in a strange document which consists entirely of aliens, with no
		// text entered. This is unusual, but not impossible. As such, just
		// collapse the selection and accept that it won't really be
		// meaningful in most cases.
		rangeAfterRemove = new ve.Range( rangeAfterRemove.start );
	}

	this.change( [], new ve.dm.LinearSelection( rangeAfterRemove ) );

	return this;
};

/**
 * Convert each content branch in the fragment from one type to another.
 *
 * @param {string} type Element type to convert to
 * @param {Object} [attr] Initial attributes for new element
 * @param {Object} [internal] Internal attributes for new element
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.convertNodes = function ( type, attr, internal ) {
	var range = this.getSelection().getCoveringRange();
	if ( !range ) {
		return this;
	}

	this.change( ve.dm.TransactionBuilder.static.newFromContentBranchConversion(
		this.document, range, type, attr, internal
	) );

	return this;
};

/**
 * Wrap each node in the fragment with one or more elements.
 *
 * A wrapper object is a linear model element; a plain object containing a type property and an
 * optional attributes property.
 *
 * Example:
 *     // fragment is a selection of: <p>a</p><p>b</p>
 *     fragment.wrapNodes(
 *         [{ type: 'list', attributes: { style: 'bullet' } }, { type: 'listItem' }]
 *     )
 *     // fragment is now a selection of: <ul><li><p>a</p></li></ul><ul><li><p>b</p></li></ul>
 *
 * @param {Object|Object[]} wrapper Wrapper object, or array of wrapper objects (see above)
 * @param {string} wrapper.type Node type of wrapper
 * @param {Object} [wrapper.attributes] Attributes of wrapper
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.wrapNodes = function ( wrapper ) {
	var range = this.getSelection().getCoveringRange();
	if ( !range ) {
		return this;
	}

	if ( !Array.isArray( wrapper ) ) {
		wrapper = [ wrapper ];
	}
	this.change(
		ve.dm.TransactionBuilder.static.newFromWrap( this.document, range, [], [], [], wrapper )
	);

	return this;
};

/**
 * Unwrap nodes in the fragment out of one or more elements.
 *
 * Example:
 *     // fragment is a selection of: <ul>「<li><p>a</p></li><li><p>b</p></li>」</ul>
 *     fragment.unwrapNodes( 1, 1 )
 *     // fragment is now a selection of: 「<p>a</p><p>b</p>」
 *
 * @param {number} outerDepth Number of nodes outside the selection to unwrap
 * @param {number} innerDepth Number of nodes inside the selection to unwrap
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.unwrapNodes = function ( outerDepth, innerDepth ) {
	var range = this.getSelection().getCoveringRange();

	if ( !range ) {
		return this;
	}

	if ( range.getLength() < innerDepth * 2 ) {
		throw new Error( 'cannot unwrap by greater depth than maximum theoretical depth of selection' );
	}

	var i;
	var innerUnwrapper = [];
	var outerUnwrapper = [];
	for ( i = 0; i < innerDepth; i++ ) {
		innerUnwrapper.push( this.document.data.getData( range.start + i ) );
	}
	for ( i = outerDepth; i > 0; i-- ) {
		outerUnwrapper.push( this.document.data.getData( range.start - i ) );
	}

	this.change( ve.dm.TransactionBuilder.static.newFromWrap(
		this.document, range, outerUnwrapper, [], innerUnwrapper, []
	) );

	return this;
};

/**
 * Change the wrapping of each node in the fragment from one type to another.
 *
 * A wrapper object is a linear model element; a plain object containing a type property and an
 * optional attributes property.
 *
 * Example:
 *     // fragment is a selection of: <dl><dt><p>a</p></dt></dl><dl><dt><p>b</p></dt></dl>
 *     fragment.rewrapNodes(
 *         2,
 *         [{ type: 'list', attributes: { style: 'bullet' } }, { type: 'listItem' }]
 *     )
 *     // fragment is now a selection of: <ul><li><p>a</p></li></ul><ul><li><p>b</p></li></ul>
 *
 * @param {number} depth Number of nodes to unwrap
 * @param {Object|Object[]} wrapper Wrapper object, or array of wrapper objects (see above)
 * @param {string} wrapper.type Node type of wrapper
 * @param {Object} [wrapper.attributes] Attributes of wrapper
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.rewrapNodes = function ( depth, wrapper ) {
	var range = this.getSelection().getCoveringRange();

	if ( !range ) {
		return this;
	}

	if ( !Array.isArray( wrapper ) ) {
		wrapper = [ wrapper ];
	}

	if ( range.getLength() < depth * 2 ) {
		throw new Error( 'cannot unwrap by greater depth than maximum theoretical depth of selection' );
	}

	var unwrapper = [];
	for ( var i = 0; i < depth; i++ ) {
		unwrapper.push( this.document.data.getData( range.start + i ) );
	}

	this.change(
		ve.dm.TransactionBuilder.static.newFromWrap( this.document, range, [], [], unwrapper, wrapper )
	);

	return this;
};

/**
 * Wrap nodes in the fragment with one or more elements.
 *
 * A wrapper object is a linear model element; a plain object containing a type property and an
 * optional attributes property.
 *
 * Example:
 *     // fragment is a selection of: <p>a</p><p>b</p>
 *     fragment.wrapAllNodes(
 *         { type: 'list', attributes: { style: 'bullet' } },
 *         { type: 'listItem' }
 *     )
 *     // fragment is now a selection of: <ul><li><p>a</p></li><li><p>b</p></li></ul>
 *
 * Example:
 *     // fragment is a selection of: <p>a</p><p>b</p>
 *     fragment.wrapAllNodes(
 *         [{ type: 'list', attributes: { style: 'bullet' } }, { type: 'listItem' }]
 *     )
 *     // fragment is now a selection of: <ul><li><p>a</p><p>b</p></li></ul>
 *
 * @param {Object|Object[]} wrapOuter Opening element(s) to wrap around the range
 * @param {Object|Object[]} wrapEach Opening element(s) to wrap around each top-level element in the range
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.wrapAllNodes = function ( wrapOuter, wrapEach ) {
	var range = this.getSelection().getCoveringRange();
	if ( !range ) {
		return this;
	}

	if ( !Array.isArray( wrapOuter ) ) {
		wrapOuter = [ wrapOuter ];
	}

	wrapEach = wrapEach || [];

	if ( !Array.isArray( wrapEach ) ) {
		wrapEach = [ wrapEach ];
	}

	this.change(
		ve.dm.TransactionBuilder.static.newFromWrap( this.document, range, [], wrapOuter, [], wrapEach )
	);

	return this;
};

/**
 * Change the wrapping of nodes in the fragment from one type to another.
 *
 * A wrapper object is a linear model element; a plain object containing a type property and an
 * optional attributes property.
 *
 * Example:
 *     // fragment is a selection of: <h1><p>a</p><p>b</p></h1>
 *     fragment.rewrapAllNodes( 1, { type: 'heading', attributes: { level: 2 } } );
 *     // fragment is now a selection of: <h2><p>a</p><p>b</p></h2>
 *
 * @param {number} depth Number of nodes to unwrap
 * @param {Object|Object[]} wrapper Wrapper object, or array of wrapper objects (see above)
 * @param {string} wrapper.type Node type of wrapper
 * @param {Object} [wrapper.attributes] Attributes of wrapper
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.rewrapAllNodes = function ( depth, wrapper ) {
	var range = this.getSelection().getCoveringRange(),
		unwrapper = [];

	if ( !range ) {
		return this;
	}

	// TODO: preserve direction
	var innerRange = new ve.Range(
		range.start + depth,
		range.end - depth
	);

	if ( !Array.isArray( wrapper ) ) {
		wrapper = [ wrapper ];
	}

	if ( range.getLength() < depth * 2 ) {
		throw new Error( 'cannot unwrap by greater depth than maximum theoretical depth of selection' );
	}

	for ( var i = 0; i < depth; i++ ) {
		unwrapper.push( this.document.data.getData( range.start + i ) );
	}

	this.change(
		ve.dm.TransactionBuilder.static.newFromWrap( this.document, innerRange, unwrapper, wrapper, [], [] )
	);

	return this;
};

/**
 * Isolates the nodes in a fragment then unwraps them.
 *
 * The node selection is expanded to siblings. These are isolated such that they are the
 * sole children of the nearest parent element which can 'type' can exist in.
 *
 * The new isolated selection is then safely unwrapped.
 *
 * @param {string} isolateForType Node type to isolate for
 * @return {ve.dm.SurfaceFragment}
 * @chainable
 */
ve.dm.SurfaceFragment.prototype.isolateAndUnwrap = function ( isolateForType ) {
	var startOffset, endOffset,
		insertions = [],
		outerDepth = 0,
		factory = ve.dm.nodeFactory,
		startSplitRequired = false,
		endSplitRequired = false,
		startSplitNodes = [],
		endSplitNodes = [],
		fragment = this;

	function createSplits( splitNodes, insertBefore ) {
		var i, length,
			adjustment = 0,
			data = [];

		for ( i = 0, length = splitNodes.length; i < length; i++ ) {
			data.unshift( { type: '/' + splitNodes[ i ].type } );
			data.push( splitNodes[ i ].getClonedElement() );

			if ( insertBefore ) {
				adjustment += 2;
			}
		}

		// Queue up transaction data
		insertions.push( {
			offset: insertBefore ? startOffset : endOffset,
			data: data
		} );

		startOffset += adjustment;
		endOffset += adjustment;
	}

	if ( !( this.selection instanceof ve.dm.LinearSelection ) ) {
		return this;
	}

	var allowedParents = factory.getSuggestedParentNodeTypes( isolateForType );
	var nodes = this.getSiblingNodes();

	// Find start split point, if required
	var startSplitNode = nodes[ 0 ].node;
	startOffset = startSplitNode.getOuterRange().start;
	if ( allowedParents !== null ) {
		while ( allowedParents.indexOf( startSplitNode.getParent().type ) === -1 ) {
			if ( startSplitNode.getParent().indexOf( startSplitNode ) > 0 ) {
				startSplitRequired = true;
			}
			startSplitNode = startSplitNode.getParent();
			if ( startSplitRequired ) {
				startSplitNodes.unshift( startSplitNode );
			} else {
				startOffset = startSplitNode.getOuterRange().start;
			}
			outerDepth++;
		}
	}

	// Find end split point, if required
	var endSplitNode = nodes[ nodes.length - 1 ].node;
	endOffset = endSplitNode.getOuterRange().end;
	if ( allowedParents !== null ) {
		while ( allowedParents.indexOf( endSplitNode.getParent().type ) === -1 ) {
			if ( endSplitNode.getParent().indexOf( endSplitNode ) < endSplitNode.getParent().getChildren().length - 1 ) {
				endSplitRequired = true;
			}
			endSplitNode = endSplitNode.getParent();
			if ( endSplitRequired ) {
				endSplitNodes.unshift( endSplitNode );
			} else {
				endOffset = endSplitNode.getOuterRange().end;
			}
		}
	}

	// We have to exclude insertions while doing splits, because we want the range to be
	// exactly what we're isolating, we don't want it to grow to include the separators
	// we're inserting (which would happen if one of them is immediately adjacent to the range)
	var oldExclude = this.willExcludeInsertions();
	this.setExcludeInsertions( true );

	if ( startSplitRequired ) {
		createSplits( startSplitNodes, true );
	}

	if ( endSplitRequired ) {
		createSplits( endSplitNodes, false );
	}

	insertions.forEach( function ( insertion ) {
		fragment.change(
			ve.dm.TransactionBuilder.static.newFromInsertion( fragment.getDocument(), insertion.offset, insertion.data )
		);
	} );

	this.setExcludeInsertions( oldExclude );

	this.unwrapNodes( outerDepth, 0 );

	return this;
};

/**
 * Insert new metadata into the document. This builds and processes a transaction that inserts
 * metadata into the document.
 *
 * Pass a plain object rather than a MetaItem into this function unless you know what you're doing.
 *
 * @param {Object|ve.dm.MetaItem} meta Metadata element (or MetaItem) to insert
 * @param {number} offset Document offset to insert at; must be a valid offset for metadata;
 * defaults to document end
 */
ve.dm.SurfaceFragment.prototype.insertMeta = function ( meta, offset ) {
	if ( arguments[ 2 ] !== undefined ) {
		throw new Error( 'Old "index" argument is no longer supported' );
	}
	if ( meta instanceof ve.dm.MetaItem ) {
		meta = meta.getElement();
	}
	var closeMeta = { type: '/' + meta.type };
	var doc = this.getDocument();
	if ( offset === undefined ) {
		offset = doc.getDocumentRange().end;
	}
	var tx = ve.dm.TransactionBuilder.static.newFromInsertion( doc, offset, [ meta, closeMeta ] );
	this.surface.change( tx );
};

/**
 * Remove a meta item from the document. This builds and processes a transaction that removes the
 * associated metadata from the document.
 *
 * @param {ve.dm.MetaItem} item Item to remove
 */
ve.dm.SurfaceFragment.prototype.removeMeta = function ( item ) {
	var tx = ve.dm.TransactionBuilder.static.newFromRemoval(
		this.getDocument(),
		item.getOuterRange(),
		true
	);
	this.surface.change( tx );
};

/**
 * Replace a MetaItem with another in-place.
 *
 * Pass a plain object rather than a MetaItem into this function unless you know what you're doing.
 *
 * @param {ve.dm.MetaItem} oldItem Old item to replace
 * @param {Object|ve.dm.MetaItem} meta Metadata element (or MetaItem) to insert
 */
ve.dm.SurfaceFragment.prototype.replaceMeta = function ( oldItem, meta ) {
	if ( meta instanceof ve.dm.MetaItem ) {
		meta = meta.getElement();
	}
	var closeMeta = { type: '/' + meta.type };
	var tx = ve.dm.TransactionBuilder.static.newFromReplacement(
		this.getDocument(),
		oldItem.getOuterRange(),
		[ meta, closeMeta ],
		true
	);
	this.surface.change( tx );
};
