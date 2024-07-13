/*!
 * VisualEditor DataModel Surface class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel surface for a node within a document
 *
 * Methods do not check that ranges actually lie inside the surfaced node
 *
 * @class
 * @mixins OO.EventEmitter
 *
 * @constructor
 * @param {ve.dm.Document} doc Document model to create surface for
 * @param {ve.dm.BranchNode} [attachedRoot] Node to surface; default is document node
 * @param {Object} [config] Configuration options
 * @cfg {boolean} [sourceMode=false] Source editing mode
 */
ve.dm.Surface = function VeDmSurface( doc, attachedRoot, config ) {
	// Support old (doc, config) argument order
	// TODO: Remove this once all callers are updated
	if ( !config && ve.isPlainObject( attachedRoot ) ) {
		config = attachedRoot;
		attachedRoot = undefined;
	}

	attachedRoot = attachedRoot || doc.getDocumentNode();
	config = config || {};

	if ( !( attachedRoot instanceof ve.dm.BranchNode ) ) {
		throw new Error( 'Expected ve.dm.BranchNode for attachedRoot' );
	}

	// Mixin constructors
	OO.EventEmitter.call( this );

	// Properties
	this.documentModel = doc;
	this.attachedRoot = attachedRoot;
	this.sourceMode = !!config.sourceMode;
	this.selection = new ve.dm.NullSelection();
	// The selection before the most recent stack of changes was applied
	this.selectionBefore = this.selection;
	this.translatedSelection = null;
	this.branchNodes = {};
	this.selectedNode = null;
	this.newTransactions = [];
	this.stagingStack = [];
	this.undoStack = [];
	this.undoIndex = 0;
	this.undoConflict = false;
	this.historyTrackingInterval = null;
	this.insertionAnnotations = new ve.dm.AnnotationSet( this.getDocument().getStore() );
	this.selectedAnnotations = new ve.dm.AnnotationSet( this.getDocument().getStore() );
	this.isCollapsed = null;
	this.multiUser = false;
	this.readOnly = false;
	this.transacting = false;
	this.queueingContextChanges = false;
	this.contextChangeQueued = false;
	this.authorId = null;
	this.lastStoredChange = doc.getCompleteHistoryLength();
	this.autosaveFailed = false;
	this.autosavePrefix = '';
	this.synchronizer = null;
	this.storing = false;
	this.setStorage( ve.init.platform.sessionStorage );

	// Let document know about the attachedRoot
	this.documentModel.attachedRoot = this.attachedRoot;

	// Events
	this.getDocument().connect( this, {
		transact: 'onDocumentTransact',
		precommit: 'onDocumentPreCommit'
	} );
	this.storeChangesListener = this.storeChanges.bind( this );
	this.storeDocStorageListener = this.storeDocStorage.bind( this );
};

/* Inheritance */

OO.mixinClass( ve.dm.Surface, OO.EventEmitter );

/* Events */

/**
 * @event select
 * @param {ve.dm.Selection} selection
 */

/**
 * @event focus
 *
 * The selection was just set to a non-null selection
 */

/**
 * @event blur
 *
 * The selection was just set to a null selection
 */

/**
 * @event documentUpdate
 *
 * Emitted when a transaction has been processed on the document and the selection has been
 * translated to account for that transaction. You should only use this event if you need
 * to access the selection; in most cases, you should use {ve.dm.Document#event-transact}.
 *
 * @param {ve.dm.Transaction} tx Transaction that was processed on the document
 */

/**
 * @event contextChange
 */

/**
 * @event insertionAnnotationsChange
 * @param {ve.dm.AnnotationSet} insertionAnnotations AnnotationSet being inserted
 */

/**
 * @event history
 * Emitted when the history stacks change, or the ability to use them changes.
 */

/**
 * @event undoStackChange
 * Emitted when the main undo stack changes (this.undoStack or this.undoIndex).
 */

/**
 * @event autosaveFailed
 * Auto-save failed to store a change
 */

/* Methods */

/**
 * Set the read-only state of the surface
 *
 * @param {boolean} readOnly Make surface read-only
 */
ve.dm.Surface.prototype.setReadOnly = function ( readOnly ) {
	if ( !!readOnly !== this.readOnly ) {
		this.readOnly = !!readOnly;
		if ( readOnly ) {
			this.stopHistoryTracking();
		} else {
			this.startHistoryTracking();
		}
		this.getDocument().setReadOnly( readOnly );
		this.emit( 'contextChange' );
	}
};

/**
 * Check if the surface is read-only
 *
 * @return {boolean}
 */
ve.dm.Surface.prototype.isReadOnly = function () {
	return this.readOnly;
};

/**
 * Initialize the surface model
 *
 * @fires contextChange
 */
ve.dm.Surface.prototype.initialize = function () {
	this.startHistoryTracking();
	this.emit( 'contextChange' );
};

/**
 * Get the DOM representation of the surface's current state.
 *
 * @return {HTMLDocument|string} HTML document (visual mode) or text (source mode)
 */
ve.dm.Surface.prototype.getDom = function () {
	if ( this.sourceMode ) {
		return ve.dm.sourceConverter.getSourceTextFromModel( this.getDocument() );
	} else {
		return ve.dm.converter.getDomFromModel( this.getDocument() );
	}
};

/**
 * Get the HTML representation of the surface's current state.
 *
 * @return {string} HTML
 */
ve.dm.Surface.prototype.getHtml = function () {
	return this.sourceMode ?
		this.getDom() :
		ve.properInnerHtml( this.getDom().body );
};

/**
 * Set the surface multi-user mode
 *
 * @param {boolean} multiUser Multi-user mode
 */
ve.dm.Surface.prototype.setMultiUser = function ( multiUser ) {
	this.multiUser = multiUser;
};

/**
 * Check if the surface is in multi-user mode
 *
 * @return {boolean} Surface is in multi-user mode
 */
ve.dm.Surface.prototype.isMultiUser = function () {
	return this.multiUser;
};

/**
 * Create a surface synchronizer.
 *
 * Must be created before the surface model is added to a view.
 *
 * @param {string} documentId Document ID
 * @param {Object} [config] Configuration options
 */
ve.dm.Surface.prototype.createSynchronizer = function ( documentId, config ) {
	if ( this.synchronizer ) {
		throw new Error( 'Synchronizer already set' );
	}

	this.setNullSelection();
	this.setMultiUser( true );

	this.synchronizer = new ve.dm.SurfaceSynchronizer( this, documentId, config );
};

/**
 * Start tracking state changes in history.
 */
ve.dm.Surface.prototype.startHistoryTracking = function () {
	if ( this.readOnly ) {
		return;
	}
	if ( this.historyTrackingInterval === null ) {
		this.historyTrackingInterval = setInterval( this.breakpoint.bind( this ), 3000 );
	}
};

/**
 * Stop tracking state changes in history.
 */
ve.dm.Surface.prototype.stopHistoryTracking = function () {
	if ( this.readOnly ) {
		return;
	}
	if ( this.historyTrackingInterval !== null ) {
		clearInterval( this.historyTrackingInterval );
		this.historyTrackingInterval = null;
	}
};

/**
 * Reset the timer for automatic history-tracking
 */
ve.dm.Surface.prototype.resetHistoryTrackingInterval = function () {
	this.stopHistoryTracking();
	this.startHistoryTracking();
};

/**
 * Get a list of all applied history states.
 *
 * @return {Object[]} List of applied transaction stacks
 */
ve.dm.Surface.prototype.getHistory = function () {
	var appliedUndoStack = this.undoStack.slice( 0, this.undoStack.length - this.undoIndex );
	if ( this.newTransactions.length > 0 ) {
		return appliedUndoStack.concat( [ { transactions: this.newTransactions.slice( 0 ) } ] );
	}
	return appliedUndoStack;
};

/**
 * If the surface in staging mode.
 *
 * @return {boolean} The surface in staging mode
 */
ve.dm.Surface.prototype.isStaging = function () {
	return this.stagingStack.length > 0;
};

/**
 * Get the staging state at the current staging stack depth
 *
 * @return {Object|undefined} staging Staging state object, or undefined if not staging
 * @return {ve.dm.Transaction[]} staging.transactions Staging transactions
 * @return {ve.dm.Selection} staging.selectionBefore Selection before transactions were applied
 * @return {boolean} staging.allowUndo Allow undo while staging
 */
ve.dm.Surface.prototype.getStaging = function () {
	return this.stagingStack[ this.stagingStack.length - 1 ];
};

/**
 * Undo is allowed at the current staging stack depth
 *
 * @return {boolean|undefined} Undo is allowed, or undefined if not staging
 */
ve.dm.Surface.prototype.doesStagingAllowUndo = function () {
	var staging = this.getStaging();
	return staging && staging.allowUndo;
};

/**
 * Get the staging transactions at the current staging stack depth
 *
 * The array is returned by reference so it can be pushed to.
 *
 * @return {ve.dm.Transaction[]|undefined} Staging transactions, or undefined if not staging
 */
ve.dm.Surface.prototype.getStagingTransactions = function () {
	var staging = this.getStaging();
	return staging && staging.transactions;
};

/**
 * Push another level of staging to the staging stack
 *
 * @param {boolean} [allowUndo=false] Allow undo while staging
 */
ve.dm.Surface.prototype.pushStaging = function ( allowUndo ) {
	// If we're starting staging stop history tracking
	if ( !this.isStaging() ) {
		if ( this.synchronizer ) {
			this.synchronizer.pauseChanges();
		}
		// Set a breakpoint to make sure newTransactions is clear
		this.breakpoint();
		this.stopHistoryTracking();
		this.emit( 'history' );
	}
	this.stagingStack.push( {
		transactions: [],
		// Will get overridden after the first transaction, but while the
		// stack is empty, should be equal to the previous selectionBefore.
		selectionBefore: this.isStaging() ? this.getStaging().selectionBefore : this.selectionBefore,
		allowUndo: !!allowUndo
	} );
};

/**
 * Pop a level of staging from the staging stack
 *
 * @fires history
 * @return {ve.dm.Transaction[]|undefined} Staging transactions, or undefined if not staging
 */
ve.dm.Surface.prototype.popStaging = function () {
	if ( !this.isStaging() ) {
		return;
	}

	var staging = this.stagingStack.pop();
	var transactions = staging.transactions;
	var reverseTransactions = [];

	// Not applying, so rollback transactions
	for ( var i = transactions.length - 1; i >= 0; i-- ) {
		var transaction = transactions[ i ].reversed();
		reverseTransactions.push( transaction );
	}
	this.changeInternal( reverseTransactions, staging.selectionBefore, true );

	if ( !this.isStaging() ) {
		if ( this.synchronizer ) {
			this.synchronizer.resumeChanges();
		}
		this.startHistoryTracking();
		this.emit( 'history' );
	}

	return transactions;
};

/**
 * Apply a level of staging from the staging stack
 *
 * @fires history
 */
ve.dm.Surface.prototype.applyStaging = function () {
	if ( !this.isStaging() ) {
		return;
	}

	var staging = this.stagingStack.pop();

	if ( this.isStaging() ) {
		// Merge popped transactions into the current item in the staging stack
		ve.batchPush( this.getStagingTransactions(), staging.transactions );
		// If the current level has a null selectionBefore, copy that over too
		if ( this.getStaging().selectionBefore.isNull() ) {
			this.getStaging().selectionBefore = staging.selectionBefore;
		}
	} else {
		this.truncateUndoStack();
		// Move transactions to the undo stack
		this.newTransactions = staging.transactions;
		this.selectionBefore = staging.selectionBefore;
		this.breakpoint();
	}

	if ( !this.isStaging() ) {
		if ( this.synchronizer ) {
			this.synchronizer.resumeChanges();
		}
		this.startHistoryTracking();
		this.emit( 'history' );
	}
};

/**
 * Pop the staging stack until empty
 *
 * @return {ve.dm.Transaction[]|undefined} Staging transactions, or undefined if not staging
 */
ve.dm.Surface.prototype.popAllStaging = function () {
	if ( !this.isStaging() ) {
		return;
	}

	var transactions = [];
	while ( this.isStaging() ) {
		ve.batchSplice( transactions, 0, 0, this.popStaging() );
	}
	return transactions;
};

/**
 * Apply the staging stack until empty
 */
ve.dm.Surface.prototype.applyAllStaging = function () {
	while ( this.isStaging() ) {
		this.applyStaging();
	}
};

/**
 * Get annotations that will be used upon insertion.
 *
 * @return {ve.dm.AnnotationSet} Insertion annotations
 */
ve.dm.Surface.prototype.getInsertionAnnotations = function () {
	return this.insertionAnnotations.clone();
};

/**
 * Set annotations that will be used upon insertion.
 *
 * @param {ve.dm.AnnotationSet|null} annotations Insertion annotations to use or null to disable them
 * @fires insertionAnnotationsChange
 * @fires contextChange
 */
ve.dm.Surface.prototype.setInsertionAnnotations = function ( annotations ) {
	if ( this.readOnly ) {
		return;
	}
	this.insertionAnnotations = annotations !== null ?
		annotations.clone() :
		new ve.dm.AnnotationSet( this.getDocument().getStore() );

	this.emit( 'insertionAnnotationsChange', this.insertionAnnotations );
	this.emit( 'contextChange' );
};

/**
 * Add an annotation to be used upon insertion.
 *
 * @param {ve.dm.Annotation|ve.dm.AnnotationSet} annotations Insertion annotation to add
 * @fires insertionAnnotationsChange
 * @fires contextChange
 */
ve.dm.Surface.prototype.addInsertionAnnotations = function ( annotations ) {
	if ( this.readOnly ) {
		return;
	}
	if ( annotations instanceof ve.dm.Annotation ) {
		this.insertionAnnotations.push( annotations );
	} else if ( annotations instanceof ve.dm.AnnotationSet ) {
		this.insertionAnnotations.addSet( annotations );
	} else {
		throw new Error( 'Invalid annotations' );
	}

	this.emit( 'insertionAnnotationsChange', this.insertionAnnotations );
	this.emit( 'contextChange' );
};

/**
 * Remove an annotation from those that will be used upon insertion.
 *
 * @param {ve.dm.Annotation|ve.dm.AnnotationSet} annotations Insertion annotation to remove
 * @fires insertionAnnotationsChange
 * @fires contextChange
 */
ve.dm.Surface.prototype.removeInsertionAnnotations = function ( annotations ) {
	if ( this.readOnly ) {
		return;
	}
	if ( annotations instanceof ve.dm.Annotation ) {
		this.insertionAnnotations.remove( annotations );
	} else if ( annotations instanceof ve.dm.AnnotationSet ) {
		this.insertionAnnotations.removeSet( annotations );
	} else {
		throw new Error( 'Invalid annotations' );
	}

	this.emit( 'insertionAnnotationsChange', this.insertionAnnotations );
	this.emit( 'contextChange' );
};

/**
 * Check if redo is allowed in the current state.
 *
 * @return {boolean} Redo is allowed
 */
ve.dm.Surface.prototype.canRedo = function () {
	return this.undoIndex > 0 && !this.readOnly;
};

/**
 * Check if undo is allowed in the current state.
 *
 * @return {boolean} Undo is allowed
 */
ve.dm.Surface.prototype.canUndo = function () {
	return this.hasBeenModified() && !this.readOnly && ( !this.isStaging() || this.doesStagingAllowUndo() ) && !this.undoConflict;
};

/**
 * Check if the surface has been modified.
 *
 * This only checks if there are transactions which haven't been undone.
 *
 * @return {boolean} The surface has been modified
 */
ve.dm.Surface.prototype.hasBeenModified = function () {
	return this.undoStack.length - this.undoIndex > 0 || !!this.newTransactions.length;
};

/**
 * Get the document model.
 *
 * @return {ve.dm.Document} Document model of the surface
 */
ve.dm.Surface.prototype.getDocument = function () {
	return this.documentModel;
};

/**
 * Get the surfaced node
 *
 * @return {ve.dm.BranchNode} The surfaced node
 */
ve.dm.Surface.prototype.getAttachedRoot = function () {
	return this.attachedRoot;
};

/**
 * Get the selection.
 *
 * @return {ve.dm.Selection} Current selection
 */
ve.dm.Surface.prototype.getSelection = function () {
	return this.selection;
};

/**
 * Get the selection translated for the transaction that's being committed, if any.
 *
 * @return {ve.dm.Selection} Current selection translated for new transaction
 */
ve.dm.Surface.prototype.getTranslatedSelection = function () {
	return this.translatedSelection || this.selection;
};

/**
 * Get a fragment for a selection.
 *
 * @param {ve.dm.Selection} [selection] Selection within target document, current selection used by default
 * @param {boolean} [noAutoSelect] Don't update the surface's selection when making changes
 * @param {boolean} [excludeInsertions] Exclude inserted content at the boundaries when updating range
 * @return {ve.dm.SurfaceFragment} Surface fragment
 */
ve.dm.Surface.prototype.getFragment = function ( selection, noAutoSelect, excludeInsertions ) {
	selection = selection || this.selection;
	// TODO: Use a factory pattern to generate fragments
	return this.sourceMode ?
		new ve.dm.SourceSurfaceFragment( this, selection, noAutoSelect, excludeInsertions ) :
		new ve.dm.SurfaceFragment( this, selection, noAutoSelect, excludeInsertions );
};

/**
 * Get a fragment for a linear selection's range.
 *
 * @param {ve.Range} range Selection's range
 * @param {boolean} [noAutoSelect] Don't update the surface's selection when making changes
 * @param {boolean} [excludeInsertions] Exclude inserted content at the boundaries when updating range
 * @return {ve.dm.SurfaceFragment} Surface fragment
 */
ve.dm.Surface.prototype.getLinearFragment = function ( range, noAutoSelect, excludeInsertions ) {
	return this.getFragment( new ve.dm.LinearSelection( range ), noAutoSelect, excludeInsertions );
};

/**
 * Prevent future states from being redone.
 *
 * Callers should eventually emit a 'history' event after using this method.
 *
 * @fires undoStackChange
 */
ve.dm.Surface.prototype.truncateUndoStack = function () {
	if ( this.undoIndex ) {
		this.undoStack = this.undoStack.slice( 0, this.undoStack.length - this.undoIndex );
		this.undoIndex = 0;
		this.emit( 'undoStackChange' );
	}
};

/**
 * Start queueing up calls to #emitContextChange until #stopQueueingContextChanges is called.
 * While queueing is active, contextChanges are also collapsed, so if #emitContextChange is called
 * multiple times, only one contextChange event will be emitted by #stopQueueingContextChanges.
 *
 *     this.emitContextChange(); // emits immediately
 *     this.startQueueingContextChanges();
 *     this.emitContextChange(); // doesn't emit
 *     this.emitContextChange(); // doesn't emit
 *     this.stopQueueingContextChanges(); // emits one contextChange event
 *
 * @private
 */
ve.dm.Surface.prototype.startQueueingContextChanges = function () {
	if ( !this.queueingContextChanges ) {
		this.queueingContextChanges = true;
		this.contextChangeQueued = false;
	}
};

/**
 * Emit a contextChange event. If #startQueueingContextChanges has been called, then the event
 * is deferred until #stopQueueingContextChanges is called.
 *
 * @private
 * @fires contextChange
 */
ve.dm.Surface.prototype.emitContextChange = function () {
	if ( this.queueingContextChanges ) {
		this.contextChangeQueued = true;
	} else {
		this.emit( 'contextChange' );
	}
};

/**
 * Stop queueing contextChange events. If #emitContextChange was called previously, a contextChange
 * event will now be emitted. Any future calls to #emitContextChange will once again emit the
 * event immediately.
 *
 * @private
 * @fires contextChange
 */
ve.dm.Surface.prototype.stopQueueingContextChanges = function () {
	if ( this.queueingContextChanges ) {
		this.queueingContextChanges = false;
		if ( this.contextChangeQueued ) {
			this.contextChangeQueued = false;
			this.emit( 'contextChange' );
		}
	}
};

/**
 * Set a linear selection at a specified range on the model
 *
 * @param {ve.Range} range Range to create linear selection at
 */
ve.dm.Surface.prototype.setLinearSelection = function ( range ) {
	this.setSelection( new ve.dm.LinearSelection( range ) );
};

/**
 * Set a null selection on the model
 */
ve.dm.Surface.prototype.setNullSelection = function () {
	this.setSelection( new ve.dm.NullSelection() );
};

/**
 * Grows a range so that any partially selected links are totally selected
 *
 * @param {ve.Range} range The range to regularize
 * @return {ve.Range} Regularized range, possibly object-identical to the original
 */
ve.dm.Surface.prototype.fixupRangeForLinks = function ( range ) {
	if ( range.isCollapsed() ) {
		return range;
	}

	var linearData = this.getDocument().data;

	function getLinks( offset ) {
		return linearData.getAnnotationsFromOffset( offset ).filter( function ( ann ) {
			return ann.name === 'link';
		} );
	}

	// Search for links at start/end that don't cover the whole range.
	// Assume at most one such link at each end.
	var start = range.start;
	var end = range.end;
	var rangeAnnotations = linearData.getAnnotationsFromRange( range );
	var startLink = getLinks( start ).diffWith( rangeAnnotations ).getHash( 0 );
	var endLink = getLinks( end ).diffWith( rangeAnnotations ).getHash( 0 );

	if ( startLink === undefined && endLink === undefined ) {
		return range;
	}

	if ( startLink !== undefined ) {
		while ( start > 0 && getLinks( start - 1 ).containsHash( startLink ) ) {
			start--;
		}
	}
	if ( endLink !== undefined ) {
		while ( end < linearData.getLength() && getLinks( end ).containsHash( endLink ) ) {
			end++;
		}
	}

	if ( range.isBackwards() ) {
		return new ve.Range( end, start );
	} else {
		return new ve.Range( start, end );
	}
};

/**
 * Change the selection
 *
 * @param {ve.dm.Selection} selection New selection
 *
 * @fires select
 * @fires contextChange
 */
ve.dm.Surface.prototype.setSelection = function ( selection ) {
	var oldSelection = this.selection;
	var maxOffset;
	if (
		selection instanceof ve.dm.LinearSelection &&
		( maxOffset = this.getDocument().getDocumentRange().end ) &&
		maxOffset < selection.getRange().end
	) {
		// Selection is out of range
		ve.error( 'Attempted to set an out of bounds selection: ' + JSON.stringify( selection ) + ', adjusting' );
		// Fix up the selection so things don't break if the caller subsequently
		// tries to use the selection
		selection = new ve.dm.LinearSelection( new ve.Range(
			Math.min( maxOffset, selection.getRange().start ),
			maxOffset
		) );
		// TODO: Check table selections too
	}
	this.translatedSelection = null;

	if ( this.transacting ) {
		// Update the selection but don't do any processing
		this.selection = selection;
		return;
	}

	var selectionChange = false;
	// this.selection needs to be updated before we call setInsertionAnnotations
	if ( !oldSelection.equals( selection ) ) {
		selectionChange = true;
		this.selection = selection;
	}

	function intersectRanges( rangeA, rangeB ) {
		var rangeStart = Math.max( rangeA.start, rangeB.start );
		var rangeEnd = Math.min( rangeA.end, rangeB.end );

		return new ve.Range(
			rangeStart,
			// If rangeStart > rangeEnd there is no overlap, just return
			// a collapsed range at rangeStart (this shouldn't happen here)
			Math.max( rangeStart, rangeEnd )
		);
	}

	var range;
	var selectedNode;
	var branchNodes = {};
	var contextChange = false;
	if ( selection instanceof ve.dm.LinearSelection ) {
		range = selection.getRange();

		// Update branch nodes
		branchNodes.start = this.getDocument().getBranchNodeFromOffset( range.start );
		branchNodes.startRange = intersectRanges( branchNodes.start.getRange(), range );
		if ( !range.isCollapsed() ) {
			branchNodes.end = this.getDocument().getBranchNodeFromOffset( range.end );
			branchNodes.endRange = intersectRanges( branchNodes.end.getRange(), range );
		} else {
			branchNodes.end = branchNodes.start;
			branchNodes.endRange = branchNodes.startRange;
		}
		selectedNode = this.getSelectedNodeFromSelection( selection );

		// Source mode optimization
		if ( !this.sourceMode ) {
			var linearData = this.getDocument().data;
			// Reset insertionAnnotations based on the neighbouring document data
			var insertionAnnotations = linearData.getInsertionAnnotationsFromRange( range );
			// If there's *any* difference in insertion annotations (even order), then:
			// * emit insertionAnnotationsChange
			// * emit contextChange (TODO: is this desirable?)
			if ( !insertionAnnotations.equalsInOrder( this.insertionAnnotations ) ) {
				this.setInsertionAnnotations( insertionAnnotations );
			}

			var selectedAnnotations;
			// Reset selectedAnnotations
			if ( range.isCollapsed() ) {
				selectedAnnotations = linearData.getAnnotationsFromOffset( range.start );
			} else {
				selectedAnnotations = linearData.getAnnotationsFromRange( range, true );
			}
			if ( !selectedAnnotations.compareTo( this.selectedAnnotations ) ) {
				this.selectedAnnotations = selectedAnnotations;
				contextChange = true;
			}

			// Did the annotations at the focus point of a non-collapsed selection
			// change? (i.e. did the selection move in/out of an annotation as it
			// expanded?)
			if ( selectionChange && !range.isCollapsed() && oldSelection instanceof ve.dm.LinearSelection ) {
				var rangeFocus = new ve.Range( range.to );
				var oldRangeFocus = new ve.Range( oldSelection.getRange().to );
				var focusRangeMovingBack = rangeFocus.to < oldRangeFocus.to;
				// If we're moving back in the document, getInsertionAnnotationsFromRange
				// needs to be told to fetch the annotations after the cursor, otherwise
				// it'll trigger one position too soon.
				if (
					!linearData.getInsertionAnnotationsFromRange( rangeFocus, focusRangeMovingBack ).compareTo( linearData.getInsertionAnnotationsFromRange( oldRangeFocus, focusRangeMovingBack ) )
				) {
					contextChange = true;
				}
			}
		}
	} else if ( selection instanceof ve.dm.TableSelection ) {
		selectedNode = selection.getMatrixCells( this.getDocument() )[ 0 ].node;
		contextChange = true;
	} else if ( selection.isNull() ) {
		contextChange = true;
	}

	if ( range && range.isCollapsed() !== this.isCollapsed ) {
		// selectedAnnotations won't have changed if going from insertion annotations to
		// selection of the same annotations, but some tools will consider that a context change
		// (e.g. ClearAnnotationTool).
		this.isCollapsed = range.isCollapsed();
		contextChange = true;
	}

	// If branchNodes or selectedNode changed emit a contextChange
	if (
		selectedNode !== this.selectedNode ||
		branchNodes.start !== this.branchNodes.start ||
		branchNodes.end !== this.branchNodes.end
	) {
		this.branchNodes = branchNodes;
		this.selectedNode = selectedNode;
		contextChange = true;
	} else if (
		branchNodes.startRange && this.branchNodes.startRange && (
			branchNodes.startRange.isCollapsed() !== this.branchNodes.startRange.isCollapsed() ||
			branchNodes.endRange.isCollapsed() !== this.branchNodes.endRange.isCollapsed()
		)
	) {
		// If the collapsed-ness of the selection within an edge node changes, the result of
		// ve.dm.Surface#getSelectedLeafNodes could change, so emit a contextChange
		this.branchNodes = branchNodes;
		contextChange = true;
	}

	// If selection changed emit a select
	if ( selectionChange ) {
		this.emit( 'select', this.selection );
		if ( oldSelection.isNull() ) {
			this.emit( 'focus' );
		}
		if ( selection.isNull() ) {
			this.emit( 'blur' );
		}
	}

	if ( contextChange ) {
		this.emitContextChange();
	}

};

/**
 * Apply a transactions and selection changes to the document.
 *
 * @param {ve.dm.Transaction|ve.dm.Transaction[]|null} transactions One or more transactions to
 *  process, or null to process none
 * @param {ve.dm.Selection} [selection] Selection to apply
 * @fires contextChange
 */
ve.dm.Surface.prototype.change = function ( transactions, selection ) {
	this.changeInternal( transactions, selection, false );
};

/**
 * Internal implementation of change(). Do not use this, use change() instead.
 *
 * @private
 * @param {ve.dm.Transaction|ve.dm.Transaction[]|null} transactions
 * @param {ve.dm.Selection} [selection]
 * @param {boolean} [skipUndoStack=false] If true, do not modify the undo stack. Used by undo/redo
 * @fires select
 * @fires history
 * @fires contextChange
 */
ve.dm.Surface.prototype.changeInternal = function ( transactions, selection, skipUndoStack ) {
	var selectionBefore = this.selection,
		contextChange = false;

	this.startQueueingContextChanges();

	// Process transactions
	if ( transactions && !this.readOnly ) {
		if ( transactions instanceof ve.dm.Transaction ) {
			transactions = [ transactions ];
		}
		this.transacting = true;
		for ( var i = 0, len = transactions.length; i < len; i++ ) {
			if ( !transactions[ i ].isNoOp() ) {
				var committed;
				// The .commit() call below indirectly invokes setSelection()
				try {
					committed = false;
					this.getDocument().commit( transactions[ i ], this.isStaging() );
					committed = true;
				} finally {
					if ( !committed ) {
						this.stopQueueingContextChanges();
					}
				}
				if ( !skipUndoStack ) {
					if ( this.isStaging() ) {
						if ( !this.getStagingTransactions().length ) {
							this.getStaging().selectionBefore = selectionBefore;
						}
						this.getStagingTransactions().push( transactions[ i ] );
					} else {
						this.truncateUndoStack();
						if ( !this.newTransactions.length ) {
							this.selectionBefore = selectionBefore;
						}
						this.newTransactions.push( transactions[ i ] );
					}
				}
				if ( transactions[ i ].hasElementAttributeOperations() ) {
					contextChange = true;
				}
			}
		}
		this.transacting = false;
		this.undoConflict = false;
		this.emit( 'history' );
	}
	var selectionAfter = this.selection;

	// Apply selection change
	if ( selection ) {
		this.setSelection( selection );
	} else if ( transactions ) {
		// Call setSelection() to trigger selection processing that was bypassed earlier
		this.setSelection( this.selection );
	}

	// If the selection changed while applying the transactions but not while applying the
	// selection change, setSelection() won't have emitted a 'select' event. We don't want that
	// to happen, so emit one anyway.
	if (
		!selectionBefore.equals( selectionAfter ) &&
		selectionAfter.equals( this.selection )
	) {
		this.emit( 'select', this.selection );
	}

	if ( contextChange ) {
		this.emitContextChange();
	}

	this.stopQueueingContextChanges();
};

/**
 * Set a history state breakpoint.
 *
 * @return {boolean} A breakpoint was added
 * @fires undoStackChange
 */
ve.dm.Surface.prototype.breakpoint = function () {
	if ( this.readOnly ) {
		return false;
	}
	var breakpointSet = false;
	this.resetHistoryTrackingInterval();
	if ( this.newTransactions.length > 0 ) {
		this.undoStack.push( {
			start: this.getDocument().getCompleteHistoryLength() - this.newTransactions.length,
			transactions: this.newTransactions,
			selection: this.selection,
			selectionBefore: this.selectionBefore
		} );
		this.newTransactions = [];
		this.emit( 'undoStackChange' );
		breakpointSet = true;
	}
	// Update selectionBefore even if nothing has changed
	this.selectionBefore = this.selection;
	return breakpointSet;
};

/**
 * Step backwards in history.
 *
 * @fires undoStackChange
 */
ve.dm.Surface.prototype.undo = function () {
	if ( !this.canUndo() ) {
		return;
	}

	if ( this.isStaging() ) {
		this.popAllStaging();
	}

	this.breakpoint();
	this.undoIndex++;

	var transactions = [];
	var item;
	if ( !this.isMultiUser() ) {
		item = this.undoStack[ this.undoStack.length - this.undoIndex ];
		if ( item ) {
			// Apply reversed transactions in reversed order
			for ( var i = item.transactions.length - 1; i >= 0; i-- ) {
				var transaction = item.transactions[ i ].reversed();
				transactions.push( transaction );
			}
			this.changeInternal( transactions, item.selectionBefore, true );
			this.emit( 'undoStackChange' );
		}
	} else {
		// Find the most recent stack item by this user
		while ( this.undoIndex <= this.undoStack.length ) {
			item = this.undoStack[ this.undoStack.length - this.undoIndex ];
			// Assume every transaction in the stack item has the same author (see ve.dm.Change#applyTo)
			var authorId = item.transactions[ 0 ].authorId;
			if ( authorId === null || authorId === this.getAuthorId() ) {
				break;
			}
			item = null;
			this.undoIndex++;
		}
		if ( item ) {
			var history = this.getDocument().getChangeSince( item.start + item.transactions.length );
			var done = new ve.dm.Change(
				item.start,
				item.transactions,
				item.transactions.map( function () {
					// Undo cannot add store items, so we don't need to worry here
					return new ve.dm.HashValueStore();
				} ),
				{}
			);
			var result = ve.dm.Change.static.rebaseUncommittedChange( history, done.reversed() );
			if ( result.rejected ) {
				// Rebasing conflict: move pointer back and don't try again until next transaction
				this.undoIndex--;
				this.undoConflict = true;
				// Undo stack didn't change, but ability to undo did
				this.emit( 'history' );
			} else {
				var selection = item.selectionBefore.translateByChange( result.transposedHistory );
				// Undo cannot add store items, so we can safely apply just transactions
				this.changeInternal( result.rebased.transactions, selection, true );
				this.emit( 'undoStackChange' );
			}
		} else {
			// Undo stack didn't change, but ability to undo did
			this.emit( 'history' );
		}
	}
};

/**
 * Step forwards in history.
 *
 * @fires undoStackChange
 */
ve.dm.Surface.prototype.redo = function () {
	if ( !this.canRedo() ) {
		return;
	}

	this.breakpoint();

	var item = this.undoStack[ this.undoStack.length - this.undoIndex ];
	if ( item ) {
		this.undoIndex--;
		// ve.copy( item.transactions ) invokes .clone() on each transaction in item.transactions
		this.changeInternal( ve.copy( item.transactions ), item.selection, true );
		this.emit( 'undoStackChange' );
	}
};

/**
 * Respond to transactions processed on the document by translating the selection and updating
 * other state.
 *
 * @param {ve.dm.Transaction} tx Transaction that was processed
 * @fires documentUpdate
 */
ve.dm.Surface.prototype.onDocumentTransact = function ( tx ) {
	this.setSelection( this.getSelection().translateByTransactionWithAuthor( tx, this.authorId ) );
	this.emit( 'documentUpdate', tx );
};

/**
 * Get the cached selected node covering the current selection, or null
 *
 * @return {ve.dm.Node|null} Selected node
 */
ve.dm.Surface.prototype.getSelectedNode = function () {
	return this.selectedNode;
};

/**
 * Get the selected node covering a specific selection, or null
 *
 * @param {ve.dm.Selection} [selection] Selection, defaults to the current selection
 * @return {ve.dm.Node|null} Selected node
 */
ve.dm.Surface.prototype.getSelectedNodeFromSelection = function ( selection ) {
	selection = selection || this.getSelection();

	if ( !( selection instanceof ve.dm.LinearSelection ) ) {
		return null;
	}

	var selectedNode = null;
	var range = selection.getRange();
	if ( !range.isCollapsed() ) {
		var startNode = this.getDocument().documentNode.getNodeFromOffset( range.start + 1 );
		if ( startNode && startNode.getOuterRange().equalsSelection( range ) ) {
			selectedNode = startNode;
		}
	}
	return selectedNode;
};

/**
 * Update translatedSelection early (before the commit actually occurs)
 *
 * This is so ve.ce.ContentBranchNode#getRenderedContents can consider the translated
 * selection for unicorn rendering.
 *
 * @param {ve.dm.Transaction} tx Transaction that's about to be committed
 */
ve.dm.Surface.prototype.onDocumentPreCommit = function ( tx ) {
	this.translatedSelection = this.selection.translateByTransaction( tx );
};

/**
 * Get a minimal set of ranges which have been modified by changes to the surface.
 *
 * @param {Object} [options] Options
 * @param {boolean} [options.includeCollapsed] Include collapsed ranges (removed content)
 * @param {boolean} [options.includeInternalList] Include changes within the internal list
 * @param {boolean} [options.excludeAnnotations] Exclude annotation-only changes
 * @param {boolean} [options.excludeAttributes] Exclude attribute changes
 * @return {ve.Range[]} Modified ranges
 */
ve.dm.Surface.prototype.getModifiedRanges = function ( options ) {
	var doc = this.getDocument();
	var ranges = [];

	options = options || {};

	this.getHistory().forEach( function ( stackItem ) {
		stackItem.transactions.forEach( function ( tx ) {
			var newRange = tx.getModifiedRange( doc, options );
			// newRange will by null for no-ops
			if ( newRange ) {
				// Translate previous ranges by the current transaction
				ranges.forEach( function ( range, i, arr ) {
					arr[ i ] = tx.translateRange( range, true );
				} );
				if ( options.includeCollapsed || !newRange.isCollapsed() ) {
					ranges.push( newRange );
				}
			}
		} );
	} );

	// Merge adjacent ranges
	var compactRanges = [];
	var lastRange = null;
	ranges
		.sort( function ( a, b ) {
			return a.start - b.start;
		} )
		.forEach( function ( range ) {
			if ( options.includeCollapsed || !range.isCollapsed() ) {
				if ( lastRange && lastRange.touchesRange( range ) ) {
					compactRanges.pop();
					range = lastRange.expand( range );
				}
				compactRanges.push( range );
				lastRange = range;
			}
		} );

	return compactRanges;
};

/**
 * Get a VE source-mode surface offset from a plaintext source offset.
 *
 * @param {number} offset Source text offset
 * @return {number} Surface offset
 * @throws {Error} Offset out of bounds
 */
ve.dm.Surface.prototype.getOffsetFromSourceOffset = function ( offset ) {
	if ( offset < 0 ) {
		throw new Error( 'Offset out of bounds' );
	}

	var lineOffset = 0,
		line = 0,
		lines = this.getDocument().getDocumentNode().getChildren();

	while ( lineOffset < offset + 1 ) {
		if ( !lines[ line ] || lines[ line ].isInternal() ) {
			throw new Error( 'Offset out of bounds' );
		}
		lineOffset += lines[ line ].getLength() + 1;
		line++;
	}
	return offset + line;
};

/**
 * Get a plaintext source offset from a VE source-mode surface offset.
 *
 * @param {number} offset Surface offset
 * @return {number} Source text offset
 * @throws {Error} Offset out of bounds
 */
ve.dm.Surface.prototype.getSourceOffsetFromOffset = function ( offset ) {
	if ( offset < 0 ) {
		throw new Error( 'Offset out of bounds' );
	}

	var lineOffset = 0,
		line = 0,
		lines = this.getDocument().getDocumentNode().getChildren();

	while ( lineOffset < offset ) {
		if ( !lines[ line ] || lines[ line ].isInternal() ) {
			throw new Error( 'Offset out of bounds' );
		}
		lineOffset += lines[ line ].getOuterLength();
		line++;
	}
	return offset - line;
};

/**
 * Get a VE source-mode surface range from plaintext source offsets.
 *
 * @param {number} from Source text from offset
 * @param {number} [to] Source text to offset, omit for a collapsed range
 * @return {ve.Range} Source surface offset
 */
ve.dm.Surface.prototype.getRangeFromSourceOffsets = function ( from, to ) {
	var fromOffset = this.getOffsetFromSourceOffset( from );
	return new ve.Range(
		fromOffset,
		// Skip toOffset calculation if collapsed
		to === undefined || to === from ?
			fromOffset :
			this.getOffsetFromSourceOffset( to )
	);
};

/**
 * Get the author ID
 *
 * @return {number} The author ID
 */
ve.dm.Surface.prototype.getAuthorId = function () {
	return this.authorId;
};

/**
 * Set the author ID
 *
 * @param {number} authorId The new author ID
 */
ve.dm.Surface.prototype.setAuthorId = function ( authorId ) {
	this.authorId = authorId;
};

/**
 * Store latest transactions into session storage
 */
ve.dm.Surface.prototype.storeChanges = function () {
	if ( this.autosaveFailed ) {
		return;
	}

	var dmDoc = this.getDocument();
	var change = dmDoc.getChangeSince( this.lastStoredChange );
	if ( !change.isEmpty() ) {
		var changes = this.storage.getObject( this.autosavePrefix + 've-changes' ) || [];
		changes.push( change );
		if ( this.storage.setObject( this.autosavePrefix + 've-changes', changes, this.storageExpiry ) ) {
			this.lastStoredChange = dmDoc.getCompleteHistoryLength();
			this.storage.setObject( this.autosavePrefix + 've-selection', this.getSelection(), this.storageExpiry );
			this.updateExpiry( [ 've-changes', 've-selection' ] );
		} else {
			// Auto-save failed probably because of memory limits
			// so flag it so we don't keep trying in vain.
			this.autosaveFailed = true;
			this.emit( 'autosaveFailed' );
		}
	}
};

/**
 * Store persistent document storage into session storage
 */
ve.dm.Surface.prototype.storeDocStorage = function () {
	if ( this.autosaveFailed ) {
		return;
	}

	var dmDoc = this.getDocument();
	this.storage.setObject( this.autosavePrefix + 've-docstorage', dmDoc.getStorage(), this.storageExpiry );
	this.updateExpiry( [ 've-docstorage' ] );
};

/**
 * Set an document ID for autosave.
 *
 * For session storage this is only required if there is more
 * than one document on the page.
 *
 * @param {string} docId Document ID.
 */
ve.dm.Surface.prototype.setAutosaveDocId = function ( docId ) {
	this.autosavePrefix = docId + '/';
};

/**
 * Set the storage interface for autosave
 *
 * @param {ve.init.ConflictableStorage} storage Storage interface
 * @param {number} [storageExpiry] Storage expiry time in seconds
 */
ve.dm.Surface.prototype.setStorage = function ( storage, storageExpiry ) {
	if ( this.storing ) {
		throw new Error( 'Can\'t change storage interface after auto-save has stared' );
	}
	this.storage = storage;
	this.storageExpiry = storageExpiry;

	var isLocalStorage = false;
	try {
		// Accessing window.localStorage can throw an exception when it is disabled
		// eslint-disable-next-line no-undef
		isLocalStorage = this.storage.store === window.localStorage;
	} catch ( e ) {}

	if ( isLocalStorage ) {
		var conflictableKeys = {};
		conflictableKeys[ this.autosavePrefix + 've-docstate' ] = true;
		conflictableKeys[ this.autosavePrefix + 've-dochtml' ] = true;
		conflictableKeys[ this.autosavePrefix + 've-selection' ] = true;
		conflictableKeys[ this.autosavePrefix + 've-changes' ] = true;
		this.storage.addConflictableKeys( conflictableKeys );
	}
};

/**
 * Start storing changes after every undoStackChange
 */
ve.dm.Surface.prototype.startStoringChanges = function () {
	this.storing = true;
	this.on( 'undoStackChange', this.storeChangesListener );
	this.getDocument().on( 'storage', this.storeDocStorageListener );
	this.updateExpiry();
};

/**
 * Stop storing changes
 */
ve.dm.Surface.prototype.stopStoringChanges = function () {
	this.storing = false;
	this.off( 'undoStackChange', this.storeChangesListener );
	this.getDocument().off( 'storage', this.storeDocStorageListener );
};

/**
 * Restore transactions from session storage
 *
 * @return {boolean} Some changes were restored
 * @throws {Error} Failed to restore auto-saved session
 */
ve.dm.Surface.prototype.restoreChanges = function () {
	var surface = this,
		restored = false,
		changes = this.storage.getObject( this.autosavePrefix + 've-changes' ) || [];

	try {
		changes.forEach( function ( data ) {
			var change = ve.dm.Change.static.unsafeDeserialize( data );
			change.applyTo( surface, true );
			surface.breakpoint();
		} );
		restored = !!changes.length;

		this.getDocument().setStorage(
			this.storage.getObject( this.autosavePrefix + 've-docstorage' ) || {}
		);

		var selection;
		try {
			selection = ve.dm.Selection.static.newFromJSON(
				this.storage.getObject( this.autosavePrefix + 've-selection' )
			);
		} catch ( e ) {
			// Didn't restore the selection, not a big deal.
		}
		if ( selection ) {
			// Wait for surface to observe selection change
			setTimeout( function () {
				surface.setSelection( selection );
			} );
		}
	} catch ( e ) {
		throw new Error( 'Failed to restore auto-saved session: ' + e );
	}

	this.lastStoredChange = this.getDocument().getCompleteHistoryLength();

	return restored;
};

/**
 * Store a snapshot of the current document state.
 *
 * If custom HTML is provided, the caller must manually set the
 * lastStoredChange pointer to the correct value.
 *
 * @param {Object} [state] JSONable object describing document state
 * @param {string} [html] Document HTML, will generate from current state if not provided
 * @return {boolean} Doc state was successfully stored
 */
ve.dm.Surface.prototype.storeDocState = function ( state, html ) {
	// Clear any changes that may have stored up to this point
	this.removeDocStateAndChanges();
	if ( state ) {
		if ( !this.updateDocState( state ) ) {
			this.stopStoringChanges();
			return false;
		}
	}
	var useLatestHtml = html === undefined;
	// Store HTML separately to avoid wasteful JSON encoding
	if ( !this.storage.set( this.autosavePrefix + 've-dochtml', useLatestHtml ? this.getHtml() : html, this.storageExpiry ) ) {
		// If we failed to store the html, wipe the docstate
		this.storage.remove( this.autosavePrefix + 've-docstate' );
		this.stopStoringChanges();
		return false;
	}
	this.updateExpiry( [ 've-dochtml' ] );

	if ( useLatestHtml ) {
		// If storing the latest HTML, reset the lastStoreChange pointer,
		// otherwise assume this will be handled by the caller.
		this.lastStoredChange = this.getDocument().getCompleteHistoryLength();
	}

	return true;
};

/**
 * Update stored document state metadata, without changing the HTML
 *
 * @param {Object} state Document state
 * @return {boolean} Document metadata was successfully stored
 */
ve.dm.Surface.prototype.updateDocState = function ( state ) {
	if ( this.storage.set( this.autosavePrefix + 've-docstate', JSON.stringify( state ), this.storageExpiry ) ) {
		this.updateExpiry( [ 've-docstate' ] );
		return true;
	}
	return false;
};

/**
 * Update the expiry value of keys in use
 *
 * @param {string[]} [skipKeys] Keys to skip (because they have just been updated)
 */
ve.dm.Surface.prototype.updateExpiry = function ( skipKeys ) {
	if ( !this.storageExpiry ) {
		return;
	}
	var surface = this;
	skipKeys = skipKeys || [];
	[ 've-docstate', 've-dochtml', 've-selection', 've-changes' ].forEach( function ( key ) {
		if ( skipKeys.indexOf( key ) === -1 ) {
			surface.storage.setExpires( surface.autosavePrefix + key, surface.storageExpiry );
		}
	} );
};

/**
 * Remove the auto-saved document state and stashed changes
 */
ve.dm.Surface.prototype.removeDocStateAndChanges = function () {
	this.storage.remove( this.autosavePrefix + 've-docstate' );
	this.storage.remove( this.autosavePrefix + 've-dochtml' );
	this.storage.remove( this.autosavePrefix + 've-selection' );
	this.storage.remove( this.autosavePrefix + 've-changes' );
};
