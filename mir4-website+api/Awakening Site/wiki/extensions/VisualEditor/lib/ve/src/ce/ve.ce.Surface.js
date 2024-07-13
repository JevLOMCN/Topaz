/*!
 * VisualEditor ContentEditable Surface class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable surface.
 *
 * @class
 * @extends OO.ui.Element
 * @mixins OO.EventEmitter
 *
 * @constructor
 * @param {ve.dm.Surface} model Surface model to observe
 * @param {ve.ui.Surface} ui Surface user interface
 * @param {Object} [config] Configuration options
 */
ve.ce.Surface = function VeCeSurface( model, ui, config ) {
	var surface = this;

	// Parent constructor
	ve.ce.Surface.super.call( this, config );

	// Mixin constructors
	OO.EventEmitter.call( this );

	// Properties
	this.surface = ui;
	this.model = model;
	this.documentView = new ve.ce.Document( model.getDocument(), this );
	this.attachedRoot = this.getDocument().getDocumentNode().getNodeFromOffset(
		model.getAttachedRoot().getOffset() + ( model.getAttachedRoot().isWrapped() ? 1 : 0 )
	);
	this.selection = null;
	this.readOnly = false;
	this.reviewMode = false;
	this.surfaceObserver = new ve.ce.SurfaceObserver( this );
	this.$window = $( this.getElementWindow() );
	this.$document = $( this.getElementDocument() );
	this.$attachedRootNode = this.attachedRoot.$element.addClass( 've-ce-attachedRootNode' );
	// Deprecated aliases
	this.$documentNode = this.$attachedRootNode;
	this.root = this.attachedRoot;
	// Window.getSelection returns a live singleton representing the document's selection
	this.nativeSelection = this.getElementWindow().getSelection();
	ve.fixSelectionNodes( this.nativeSelection );
	this.eventSequencer = new ve.EventSequencer( [
		'keydown', 'keypress', 'keyup',
		'compositionstart', 'compositionend',
		'beforeinput', 'input', 'mousedown'
	] );
	this.clipboard = null;
	this.clipboardId = Math.random().toString();
	this.clipboardIndex = 0;
	// The last non-collapsed selection in this VE surface. This will be a NullSelection
	// if there has never had a non-collapsed selection, or if the cursor is moved out of
	// the surface and a selection is made elsewhere.
	this.lastNonCollapsedDocumentSelection = new ve.dm.NullSelection();
	this.middleClickPasting = false;
	this.middleClickTargetOffset = false;
	this.renderLocks = 0;
	this.dragging = false;
	this.relocatingSelection = null;
	this.relocatingNode = null;
	this.allowedFile = null;
	this.resizing = false;
	this.focused = false;
	this.deactivated = false;
	this.showAsActivated = false;
	this.hideSelection = false;
	this.$deactivatedSelection = $( '<div>' );
	this.userSelectionDeactivate = {};
	this.drawnSelections = {};
	this.drawnSelectionCache = {};
	this.activeNode = null;
	this.contentBranchNodeChanged = false;
	this.selectionLink = null;
	this.delayedSequences = [];
	this.$highlightsFocused = $( '<div>' );
	this.$highlightsBlurred = $( '<div>' );
	this.$highlights = $( '<div>' ).append(
		this.$highlightsFocused, this.$highlightsBlurred
	);
	this.$findResults = $( '<div>' );
	this.$dropMarker = $( '<div>' ).addClass( 've-ce-surface-dropMarker oo-ui-element-hidden' );
	this.$lastDropTarget = null;
	this.lastDropPosition = null;
	this.$pasteTarget = $( '<div>' );
	this.pasting = false;
	this.beforePasteAnnotationsAtFocus = [];
	this.copying = false;
	this.pasteSpecial = false;
	this.pointerEvents = null;
	this.focusedBlockSlug = null;
	this.focusedNode = null;
	this.activeAnnotations = [];
	this.contexedAnnotations = [];
	this.previousActiveAnnotations = [];
	// This is set on entering changeModel, then unset when leaving.
	// It is used to test whether a reflected change event is emitted.
	this.newModelSelection = null;

	// Snapshot updated at keyDown. See storeKeyDownState.
	this.keyDownState = {
		event: null,
		selectionState: null
	};

	this.cursorDirectionality = null;
	this.unicorningNode = null;
	this.setUnicorningRecursionGuard = false;
	this.cursorHolderBefore = null;
	this.cursorHolderAfter = null;

	// Events
	// Debounce to prevent trying to draw every cursor position in history.
	this.onPositionDebounced = ve.debounce( this.onPosition.bind( this ) );
	this.connect( this, { position: this.onPositionDebounced } );
	this.model.connect( this, {
		select: 'onModelSelect',
		documentUpdate: 'onModelDocumentUpdate',
		insertionAnnotationsChange: 'onInsertionAnnotationsChange'
	} );

	if ( this.model.synchronizer ) {
		this.model.synchronizer.connect( this, {
			authorSelect: 'onSynchronizerAuthorUpdate',
			authorChange: 'onSynchronizerAuthorUpdate',
			authorDisconnect: 'onSynchronizerAuthorDisconnect',
			wrongDoc: 'onSynchronizerWrongDoc',
			pause: 'onSynchronizerPause'
		} );
	}

	this.onDocumentMouseUpHandler = this.onDocumentMouseUp.bind( this );
	this.$attachedRootNode.on( {
		// Mouse events shouldn't be sequenced as the event sequencer
		// is detached on blur
		mousedown: this.onDocumentMouseDown.bind( this ),
		// mouseup is bound to the whole document on mousedown
		cut: this.onCut.bind( this ),
		copy: this.onCopy.bind( this )
	} );

	this.onWindowResizeHandler = ve.debounce( this.onWindowResize.bind( this ), 50 );
	this.$window.on( 'resize', this.onWindowResizeHandler );

	this.onDocumentFocusInOutHandler = this.onDocumentFocusInOut.bind( this );
	this.$document.on( 'focusin focusout', this.onDocumentFocusInOutHandler );

	this.debounceFocusChange = ve.debounce( this.onFocusChange.bind( this ) );
	// If the document is blurred (but still has a selection) it is
	// possible to clear the selection by clicking elsewhere without
	// triggering a focus or blur event, so listen to mousedown globally.
	this.$document.on( 'mousedown', this.debounceFocusChange );
	// It is possible that when focusin fires, the selection is not yet inside
	// the document. This happens if the selection is being moved inside itself,
	// e.g. the whole html page was previously selected, including the attachedRootNode
	// In this case the selection is not moved until mouseup. T157499
	this.$attachedRootNode.on( 'mouseup', this.debounceFocusChange );

	this.$pasteTarget.add( this.$highlights ).on( {
		cut: this.onCut.bind( this ),
		copy: this.onCopy.bind( this ),
		paste: this.onPaste.bind( this )
	} );

	this.$attachedRootNode
		.on( 'paste', this.onPaste.bind( this ) )
		.on( 'focus', 'a', function () {
			// Opera <= 12 triggers 'blur' on document node before any link is
			// focused and we don't want that
			surface.$attachedRootNode[ 0 ].focus();
		} );

	this.onDocumentSelectionChangeHandler = this.onDocumentSelectionChange.bind( this );
	this.$document.on( 'selectionchange', this.onDocumentSelectionChangeHandler );

	this.$element.on( {
		dragstart: this.onDocumentDragStart.bind( this ),
		dragover: this.onDocumentDragOver.bind( this ),
		dragleave: this.onDocumentDragLeave.bind( this ),
		drop: this.onDocumentDrop.bind( this )
	} );

	// Add listeners to the eventSequencer. They won't get called until
	// eventSequencer.attach(node) has been called.
	this.eventSequencer.on( {
		keydown: this.onDocumentKeyDown.bind( this ),
		keyup: this.onDocumentKeyUp.bind( this ),
		keypress: this.onDocumentKeyPress.bind( this ),
		beforeinput: this.onDocumentBeforeInput.bind( this ),
		input: this.onDocumentInput.bind( this ),
		compositionstart: this.onDocumentCompositionStart.bind( this )
	} ).after( {
		keydown: this.afterDocumentKeyDown.bind( this )
	} );

	// Initialization
	// Support: Chrome
	// Add 'notranslate' class to prevent Chrome's translate feature from
	// completely messing up the CE DOM (T59124)
	this.$element.addClass( 've-ce-surface notranslate' );
	// Support: Edge
	// Add translate="no" attribute to prevent Chromium Edge's translate feature from
	// translating our editable surface, and leaving junk behind... (T267747)
	// Some documentation out there says it respects class="notranslate", but it doesn't.
	this.$element.attr( 'translate', 'no' );
	this.$highlights.addClass( 've-ce-surface-highlights' );
	this.$highlightsFocused.addClass( 've-ce-surface-highlights-focused' );
	this.$highlightsBlurred.addClass( 've-ce-surface-highlights-blurred' );
	this.$deactivatedSelection.addClass( 've-ce-surface-deactivatedSelection' );
	this.$pasteTarget
		.addClass( 've-ce-surface-paste' )
		// T283853
		.attr( 'aria-hidden', true )
		.prop( {
			tabIndex: -1,
			contentEditable: 'true'
		} );

	// Add elements to the DOM
	this.$highlights.append( this.$dropMarker );
	this.$element.append( this.$attachedRootNode, this.$pasteTarget );
	this.surface.$blockers.append( this.$highlights );
	this.surface.$selections.append( this.$deactivatedSelection );
};

/* Inheritance */

OO.inheritClass( ve.ce.Surface, OO.ui.Element );

OO.mixinClass( ve.ce.Surface, OO.EventEmitter );

/* Events */

/**
 * @event relocationStart
 */

/**
 * @event relocationEnd
 */

/**
 * @event keyup
 */

/**
 * When the surface or its contents changes position
 * (only after initialize has already been called).
 *
 * @event position
 */

/**
 * @event focus
 * Note that it's possible for a focus event to occur immediately after a blur event, if the focus
 * moves to or from a FocusableNode. In this case the surface doesn't lose focus conceptually, but
 * a pair of blur-focus events is emitted anyway.
 */

/**
 * @event blur
 * Note that it's possible for a focus event to occur immediately after a blur event, if the focus
 * moves to or from a FocusableNode. In this case the surface doesn't lose focus conceptually, but
 * a pair of blur-focus events is emitted anyway.
 */

/**
 * Surface activation state has changed (i.e. on activate or deactivate)
 *
 * @event activation
 */

/* Static properties */

/**
 * Attributes considered 'unsafe' for copy/paste
 *
 * These attributes may be dropped by the browser during copy/paste, so
 * any element containing these attributes will have them JSON encoded into
 * data-ve-attributes on copy.
 *
 * @type {string[]}
 */
ve.ce.Surface.static.unsafeAttributes = [
	// Support: Firefox
	// RDFa: Firefox ignores these
	'about',
	'content',
	'datatype',
	'property',
	'rel',
	'resource',
	'rev',
	'typeof',
	// CSS: Values are often added or modified
	'style'
];

/**
 * Values of InputEvent.inputType which map to a command
 *
 * Currently these are triggered when the user selects
 * undo/redo from the context menu in Chrome, or uses the
 * selection formatting tools on iOS.
 *
 * See https://w3c.github.io/input-events/
 *
 * Values of null will perform no action and preventDefault.
 *
 * @type {Object.<string,string|null>}
 */
ve.ce.Surface.static.inputTypeCommands = {
	historyUndo: 'undo',
	historyRedo: 'redo',
	formatBold: 'bold',
	formatItalic: 'italic',
	formatUnderline: 'underline',
	formatStrikeThrough: 'strikethrough',
	formatSuperscript: 'superscript',
	formatSubscript: 'subscript',
	formatJustifyFull: null,
	formatJustifyCenter: null,
	formatJustifyRight: null,
	formatJustifyLeft: null,
	formatIndent: 'indent',
	formatOutdent: 'outdent',
	formatRemove: 'clear',
	formatSetBlockTextDirection: null,
	formatSetInlineTextDirection: null,
	formatBackColor: null,
	formatFontColor: null,
	formatFontName: null
};

/**
 * Cursor holder template
 *
 * @static
 * @property {HTMLElement}
 */
ve.ce.Surface.static.cursorHolderTemplate =
	$( '<div>' )
		.addClass( 've-ce-cursorHolder' )
		.prop( 'contentEditable', 'true' )
		.append(
			// The image does not need a src for Firefox in spite of cursoring
			// bug https://bugzilla.mozilla.org/show_bug.cgi?id=989012 , because
			// you can cursor to ce=false blocks in Firefox (see bug
			// https://bugzilla.mozilla.org/show_bug.cgi?id=1155031 )
			$( '<img>' )
				.addClass( 've-ce-cursorHolder-img' )
				.attr( {
					role: 'none',
					alt: ''
				} )
		)
		.get( 0 );

/* Static methods */

/**
 * When pasting, browsers normalize HTML to varying degrees.
 * This hash creates a comparable string for validating clipboard contents.
 *
 * @param {jQuery} $elements Clipboard HTML
 * @param {Object} [beforePasteData] Paste information, including leftText and rightText to strip
 * @return {string} Hash
 */
ve.ce.Surface.static.getClipboardHash = function ( $elements, beforePasteData ) {
	beforePasteData = beforePasteData || {};
	return $elements.text()
		.slice(
			beforePasteData.leftText ? beforePasteData.leftText.length : 0,
			beforePasteData.rightText ? -beforePasteData.rightText.length : undefined
		)
		// Whitespace may be modified (e.g. ' ' to '&nbsp;'), so strip it all
		.replace( /\s/gm, '' );
};

/* Methods */

/**
 * Destroy the surface, removing all DOM elements.
 */
ve.ce.Surface.prototype.destroy = function () {
	var attachedRoot = this.attachedRoot;

	// Support: Firefox, iOS
	// FIXME T126041: Blur to make selection/cursor disappear (needed in Firefox
	// in some cases, and in iOS to hide the keyboard)
	if ( this.isFocused() ) {
		this.blur();
	}

	// Detach observer and event sequencer
	this.surfaceObserver.stopTimerLoop();
	this.surfaceObserver.detach();
	this.eventSequencer.detach();

	// Make document node not live
	attachedRoot.setLive( false );

	// Disconnect events
	this.model.disconnect( this );

	// Disconnect DOM events on the document
	this.$document.off( 'focusin focusout', this.onDocumentFocusInOutHandler );
	this.$document.off( 'mousedown', this.debounceFocusChange );
	this.$document.off( 'selectionchange', this.onDocumentSelectionChangeHandler );

	if ( this.model.synchronizer ) {
		// TODO: Move destroy to ve.dm.Surface#destroy
		this.model.synchronizer.destroy();
		this.model.synchronizer.disconnect( this );
	}

	// Disconnect DOM events on the window
	this.$window.off( 'resize', this.onWindowResizeHandler );

	// Remove DOM elements (also disconnects their events)
	this.$element.remove();
	this.$highlights.remove();
};

/**
 * Get linear model offest from a mouse event
 *
 * @param {Event} e
 * @return {number} Linear model offset, or -1 if coordinates are out of bounds
 */
ve.ce.Surface.prototype.getOffsetFromEventCoords = function ( e ) {
	return this.getOffsetFromCoords(
		e.pageX - this.surface.$scrollContainer.scrollLeft(),
		e.pageY - this.surface.$scrollContainer.scrollTop()
	);
};

/**
 * Get linear model offset from absolute coords
 *
 * @param {number} x X offset
 * @param {number} y Y offset
 * @return {number} Linear model offset, or -1 if coordinates are out of bounds
 */
ve.ce.Surface.prototype.getOffsetFromCoords = function ( x, y ) {
	var doc = this.getElementDocument();

	try {
		var offset;
		if ( doc.caretPositionFromPoint ) {
			// Gecko
			// http://dev.w3.org/csswg/cssom-view/#extensions-to-the-document-interface
			var caretPosition = document.caretPositionFromPoint( x, y );
			offset = ve.ce.getOffset( caretPosition.offsetNode, caretPosition.offset );
		} else if ( doc.caretRangeFromPoint ) {
			// Webkit
			// http://www.w3.org/TR/2009/WD-cssom-view-20090804/
			var range = document.caretRangeFromPoint( x, y );
			offset = ve.ce.getOffset( range.startContainer, range.startOffset );
		} else if ( document.body.createTextRange ) {
			// Trident
			// http://msdn.microsoft.com/en-gb/library/ie/ms536632(v=vs.85).aspx
			var textRange = document.body.createTextRange();
			textRange.moveToPoint( x, y );
			textRange.pasteHTML( '<span class="ve-ce-textRange-drop-marker">&nbsp;</span>' );
			// eslint-disable-next-line no-jquery/no-global-selector
			var $marker = $( '.ve-ce-textRange-drop-marker' );
			offset = ve.ce.getOffset( $marker.get( 0 ), 0 );
			$marker.remove();
		}
		return offset;
	} catch ( e ) {
		// Both ve.ce.getOffset and TextRange.moveToPoint can throw out of bounds exceptions
		return -1;
	}
};

/**
 * Get selection view object
 *
 * @param {ve.dm.Selection} [selection] Optional selection model, defaults to current selection
 * @return {ve.ce.Selection} Selection view
 */
ve.ce.Surface.prototype.getSelection = function ( selection ) {
	if ( selection ) {
		// Specific selection requested, bypass cache
		return ve.ce.Selection.static.newFromModel( selection, this );
	} else if ( !this.selection ) {
		this.selection = ve.ce.Selection.static.newFromModel( this.getModel().getSelection(), this );
	}
	return this.selection;
};

/**
 * Get block directionality at selection
 *
 * @return {string} 'rtl' or 'ltr'
 */
ve.ce.Surface.prototype.getSelectionDirectionality = function () {
	return this.getSelection().getDirectionality( this.getDocument() );
};

/* Initialization */

/**
 * Initialize surface.
 *
 * This should be called after the surface has been attached to the DOM.
 */
ve.ce.Surface.prototype.initialize = function () {
	this.attachedRoot.setLive( true );
	if ( $.client.profile().layout === 'gecko' ) {
		// Turn off native object editing. This must be tried after the surface has been added to DOM.
		// This is only needed in Gecko. In other engines, these properties are off by default,
		// and turning them off again is expensive; see https://phabricator.wikimedia.org/T89928
		try {
			this.$document[ 0 ].execCommand( 'enableObjectResizing', false, false );
			this.$document[ 0 ].execCommand( 'enableInlineTableEditing', false, false );
		} catch ( e ) { /* Silently ignore */ }
	}
	this.emit( 'position' );
};

/**
 * Set the read-only state of the surface
 *
 * @param {boolean} readOnly Make surface read-only
 */
ve.ce.Surface.prototype.setReadOnly = function ( readOnly ) {
	this.readOnly = !!readOnly;
	this.$element.toggleClass( 've-ce-surface-readOnly', this.readOnly );
};

/**
 * Check if the surface is read-only
 *
 * @return {boolean}
 */
ve.ce.Surface.prototype.isReadOnly = function () {
	return this.readOnly;
};

/**
 * Set the review mode state of the surface
 *
 * In review mode the surface can't be interacted with by the user
 * (unlike the read-only mode where the user can select text and
 * inspect nodes).
 *
 * Review mode does not restrict changes to the model by other means,
 * so programmatic changes can still be made from other tools.
 *
 * @param {boolean} reviewMode Set surface to review mode
 * @param {ve.ce.Node[]} highlightNodes Nodes to highlight while in review mode
 */
ve.ce.Surface.prototype.setReviewMode = function ( reviewMode, highlightNodes ) {
	this.reviewMode = !!reviewMode;
	this.$element.toggleClass( 've-ce-surface-reviewMode', this.reviewMode );
	this.$element.toggleClass( 've-ce-surface-reviewMode-highlightNodes', this.reviewMode && !!highlightNodes );
	if ( reviewMode && highlightNodes ) {
		highlightNodes.forEach( function ( node ) {
			node.$element
				.addClass( 've-ce-surface-reviewMode-highlightNode' )
				.parentsUntil( '.ve-ce-attachedRootNode' )
				.addClass( 've-ce-surface-reviewMode-highlightNode' );

		} );
	} else {
		this.$element.find( '.ve-ce-surface-reviewMode-highlightNode' )
			.removeClass( 've-ce-surface-reviewMode-highlightNode' );
	}
};

/**
 * Give focus to the surface, reapplying the model selection, or selecting the first visible offset
 * if the model selection is null.
 *
 * This is used when switching between surfaces, e.g. when closing a dialog window. Calling this
 * function will also reapply the selection, even if the surface is already focused.
 */
ve.ce.Surface.prototype.focus = function () {
	var surface = this;

	if ( !this.attachedRoot.isLive() ) {
		OO.ui.warnDeprecation( 'Tried to focus an un-initialized surface view. Wait for the ve.ui.Surface `ready` event to fire.' );
		return;
	}

	var selection = this.getSelection();
	if ( selection.getModel().isNull() ) {
		this.selectFirstVisibleStartContentOffset();
		selection = this.getSelection();
	}

	// Focus the contentEditable for text selections, or the pasteTarget for focusedNode selections
	if ( selection.isFocusedNode() ) {
		this.$pasteTarget[ 0 ].focus();
	} else if ( selection.isNativeCursor() ) {
		var nodeAndOffset;
		try {
			nodeAndOffset = this.getDocument().getNodeAndOffset( selection.getModel().getRange().start );
		} catch ( e ) {
			// Unexplained failures causing log spam: T262487
			nodeAndOffset = null;
		}
		if ( nodeAndOffset ) {
			$( nodeAndOffset.node ).closest( '[contenteditable=true]' )[ 0 ].focus();
		}
	}

	// If we are calling focus after replacing a node the selection may be gone
	// but onDocumentFocus won't fire so restore the selection here too.
	this.onModelSelect();
	// setTimeout: postpone until onDocumentFocus has been called
	setTimeout( function () {
		// Support: Chrome
		// In some browsers (e.g. Chrome) giving the document node focus doesn't
		// necessarily give you a selection (e.g. if the first child is a <figure>)
		// so if the surface isn't 'focused' (has no selection) give it a selection
		// manually
		// TODO: rename isFocused and other methods to something which reflects
		// the fact they actually mean "has a native selection"
		if ( !surface.isFocused() ) {
			surface.selectFirstVisibleStartContentOffset();
		}
	} );
	// onDocumentFocus takes care of the rest
};

/**
 * Blur the surface
 */
ve.ce.Surface.prototype.blur = function () {
	if ( this.deactivated ) {
		// Clear the model selection, so activate doesn't trigger another de-activate
		this.getModel().setNullSelection();
		this.activate();
	}
	this.removeRangesAndBlur();
	// This won't trigger focusin/focusout events, so trigger focus change manually
	this.onFocusChange();
	if ( OO.ui.isMobile() ) {
		this.updateActiveAnnotations();
		this.contexedAnnotations = [];
	}
};

/**
 * Remove all native selection ranges, and blur any active element
 *
 * This should hide all virtual keyboards when present.
 */
ve.ce.Surface.prototype.removeRangesAndBlur = function () {
	this.nativeSelection.removeAllRanges();
	if ( this.getElementDocument().activeElement === this.$attachedRootNode[ 0 ] ) {
		// Blurring the activeElement ensures the keyboard is hidden on iOS
		this.getElementDocument().activeElement.blur();
	}
};

/**
 * Handler for focusin and focusout events. Filters events and debounces to #onFocusChange.
 *
 * @param {jQuery.Event} e focusin/out event
 */
ve.ce.Surface.prototype.onDocumentFocusInOut = function () {
	this.debounceFocusChange();
};

/**
 * Handle global focus change.
 */
ve.ce.Surface.prototype.onFocusChange = function () {
	var surfaceNodes = [
		this.$attachedRootNode[ 0 ],
		this.$pasteTarget[ 0 ],
		this.$highlights[ 0 ]
	];

	var hasFocus = OO.ui.contains(
		surfaceNodes,
		this.nativeSelection.anchorNode,
		true
	) && OO.ui.contains(
		surfaceNodes,
		document.activeElement,
		true
	);

	if ( this.deactivated ) {
		if ( OO.ui.contains( this.$attachedRootNode[ 0 ], this.nativeSelection.anchorNode, true ) ) {
			this.onDocumentFocus();
		}
	} else {
		if ( hasFocus && !this.isFocused() ) {
			this.onDocumentFocus();
		} else if ( !hasFocus && this.isFocused() ) {
			this.onDocumentBlur();
		} else if ( hasFocus && OO.ui.contains( this.$highlights[ 0 ], document.activeElement, true ) ) {
			// Focus ended up in the higlight, e.g. by click on an already visible highlight.
			// Move the cursor back to pasteTarget as we do when focusableNode initially selected.
			// Without this, arrow key navigation from the focusable node would stop working.
			this.preparePasteTargetForCopy();
		}
	}
};

/**
 * Check if the surface is deactivated.
 *
 * @return {boolean} Surface is deactivated
 */
ve.ce.Surface.prototype.isDeactivated = function () {
	return this.deactivated;
};

/**
 * Check if the surface is visibly deactivated.
 *
 * Only true if the surface was decativated by the user
 * in a way that is expected to change the rendering.
 *
 * @return {boolean} Surface is visibly deactivated
 */
ve.ce.Surface.prototype.isShownAsDeactivated = function () {
	return this.deactivated && !this.showAsActivated;
};

/**
 * Deactivate the surface, stopping the surface observer and replacing the native
 * range with a fake rendered one.
 *
 * Used by dialogs so they can take focus without losing the original document selection.
 *
 * @param {boolean} [showAsActivated=true] Surface should still show as activated
 * @param {boolean} [noSelectionChange] Don't change the native selection.
 * @param {boolean} [hideSelection] Completely hide the selection
 * @fires activation
 */
ve.ce.Surface.prototype.deactivate = function ( showAsActivated, noSelectionChange, hideSelection ) {
	var surface = this;
	this.showAsActivated = showAsActivated === undefined || !!showAsActivated;
	this.hideSelection = hideSelection;
	if ( !this.deactivated ) {
		// Disable the surface observer, there can be no observable changes
		// until the surface is activated
		this.surfaceObserver.disable();
		this.deactivated = true;
		this.previousActiveAnnotations = this.activeAnnotations;
		this.findAndExecuteDelayedSequences();
		this.$element.addClass( 've-ce-surface-deactivated' );
		// Remove ranges so the user can't accidentally type into the document,
		// and so virtual keyboards are hidden.
		if ( !noSelectionChange ) {
			this.removeRangesAndBlur();
			// iOS Safari will sometimes restore the selection immediately (T293661)
			setTimeout( function () {
				if (
					// Surface may have been immediately re-activated deliberately
					surface.deactivated &&
					OO.ui.contains( surface.$attachedRootNode[ 0 ], surface.nativeSelection.anchorNode, true )
				) {
					surface.removeRangesAndBlur();
				}
			} );
		}
		this.updateDeactivatedSelection();
		this.clearKeyDownState();
		this.emit( 'activation' );
	}
};

/**
 * Reactivate the surface and restore the native selection
 *
 * @fires activation
 */
ve.ce.Surface.prototype.activate = function () {
	if ( this.deactivated ) {
		this.deactivated = false;
		this.showAsActivated = false;
		this.hideSelection = false;
		this.updateDeactivatedSelection();
		this.surfaceObserver.enable();
		this.$element.removeClass( 've-ce-surface-deactivated' );
		if ( OO.ui.isMobile() ) {
			// Activating triggers a context hide on mobile
			this.model.emit( 'contextChange' );
		}

		var previousSelection = this.getModel().getSelection();

		if ( OO.ui.contains( this.$attachedRootNode[ 0 ], this.nativeSelection.anchorNode, true ) ) {
			// The selection has been placed back in the document, either by the user clicking
			// or by the closing window updating the model. Poll in case it was the user clicking.
			this.surfaceObserver.clear();
			this.surfaceObserver.pollOnce();
		} else {
			// Clear focused node so onModelSelect re-selects it if necessary
			this.focusedNode = null;
			this.onModelSelect();
		}

		var newSelection = this.getModel().getSelection();

		if (
			previousSelection.getCoveringRange() &&
			newSelection.getCoveringRange() &&
			previousSelection.getCoveringRange().containsRange(
				newSelection.getCoveringRange()
			)
		) {
			// If the user reactivates by clicking on their previous selection, use that selection.
			this.getModel().setSelection( previousSelection );
			// Restore active annotations
			if ( this.previousActiveAnnotations.length ) {
				var annotationClasses = this.previousActiveAnnotations.map( function ( ann ) {
					return ann.constructor;
				} );
				this.selectAnnotation( function ( view ) {
					return ve.isInstanceOfAny( view, annotationClasses );
				} );
			}
		}

		this.emit( 'activation' );
	}
};

/**
 * Update the fake selection while the surface is deactivated.
 *
 * While the surface is deactivated, all calls to showModelSelection will get redirected here.
 */
ve.ce.Surface.prototype.updateDeactivatedSelection = function () {
	var selection = this.getSelection();

	// Check we have a deactivated surface and a native selection
	if ( this.deactivated && selection.isNativeCursor() && !this.hideSelection ) {
		var textColor;
		// For collapsed selections, work out the text color to use for the cursor
		var isCollapsed = selection.getModel().isCollapsed();
		if ( isCollapsed ) {
			var currentNode = this.getDocument().getBranchNodeFromOffset(
				selection.getModel().getCoveringRange().start
			);
			if ( currentNode ) {
				// This isn't perfect as it doesn't take into account annotations.
				textColor = currentNode.$element.css( 'color' );
			}
		}
		var classes = [];
		if ( this.isShownAsDeactivated() ) {
			classes.push( 've-ce-surface-selections-deactivated-showAsDeactivated' );
		}
		if ( isCollapsed ) {
			classes.push( 've-ce-surface-selections-deactivated-collapsed' );
		}
		// Generates ve-ce-surface-selections-deactivated CSS class
		this.drawSelections( 'deactivated', [ selection ], {
			color: textColor,
			wrapperClass: classes.join( ' ' )
		} );
	} else {
		// Generates ve-ce-surface-selections-deactivated CSS class
		this.drawSelections( 'deactivated', [] );
	}
};

/**
 * Check if the surface has a native cursor selection
 *
 * On mobile platforms, this means it is likely the virtual
 * keyboard is visible.
 *
 * @return {boolean} Surface has a native cursor selection
 */
ve.ce.Surface.prototype.hasNativeCursorSelection = function () {
	return !this.isDeactivated() && this.getSelection().isNativeCursor();
};

/**
 * Draw selections.
 *
 * @param {string} name Unique name for the selection being drawn
 * @param {ve.ce.Selection[]} selections Selections to draw
 * @param {Object} [options]
 * @param {string} options.color CSS color for the selection. Should usually
 *  be set in a stylesheet using the generated class name.
 * @param {string} options.wrapperClass Additional CSS class string to add to the $selections wrapper.
 *  mapped to the same index.
 * @param {string} options.label Label shown above each selection
 */
ve.ce.Surface.prototype.drawSelections = function ( name, selections, options ) {
	var surface = this;

	options = options || {};

	var drawnSelection = ( this.drawnSelections[ name ] = this.drawnSelections[ name ] || {} );

	drawnSelection.$selections = drawnSelection.$selections ||
		// The following classes are used here:
		// * ve-ce-surface-selections-deactived
		// * ve-ce-surface-selections-<name>
		$( '<div>' ).addClass( 've-ce-surface-selections ve-ce-surface-selections-' + name ).appendTo( this.surface.$selections );

	var oldSelections = drawnSelection.selections || [];
	var oldOptions = drawnSelection.options || {};

	drawnSelection.selections = selections;
	drawnSelection.options = options;

	// Always set the 'class' attribute to ensure previously-set classes are cleared.
	drawnSelection.$selections.attr(
		'class',
		've-ce-surface-selections ve-ce-surface-selections-' + name + ' ' +
		( options.wrapperClass || '' )
	);

	var selectionsJustShown = {};
	selections.forEach( function ( selection ) {
		var $selection = surface.getDrawnSelection( name, selection.getModel(), options );
		if ( !$selection ) {
			var rects = selection.getSelectionRects();
			if ( !rects ) {
				return;
			}
			rects = ve.minimizeRects( rects );
			$selection = $( '<div>' ).addClass( 've-ce-surface-selection' );
			rects.forEach( function ( rect ) {
				var $rect = $( '<div>' ).css( {
					top: rect.top,
					left: rect.left,
					// Collapsed selections can have a width of 0, so expand
					width: Math.max( rect.width, 1 ),
					height: rect.height
				} );
				$selection.append( $rect );
				if ( options.color ) {
					$rect.css( 'background-color', options.color );
				}
			} );

			if ( options.label ) {
				var boundingRect = selection.getSelectionBoundingRect();
				$selection.append(
					$( '<div>' )
						.addClass( 've-ce-surface-selection-label' )
						.text( options.label )
						.css( {
							top: boundingRect.top,
							left: boundingRect.left,
							'background-color': options.color || ''
						} )
				);
			}
		}
		if ( !$selection.parent().length ) {
			drawnSelection.$selections.append( $selection );
		}
		var cacheKey = surface.storeDrawnSelection( $selection, name, selection.getModel(), options );
		selectionsJustShown[ cacheKey ] = true;
	} );

	// Remove any selections that were not in the latest list of selections
	oldSelections.forEach( function ( oldSelection ) {
		var cacheKey = surface.getDrawnSelectionCacheKey( name, oldSelection.getModel(), oldOptions );
		if ( !selectionsJustShown[ cacheKey ] ) {
			var $oldSelection = surface.getDrawnSelection( name, oldSelection.getModel(), oldOptions );
			if ( $oldSelection ) {
				$oldSelection.detach();
			}
		}
	} );
};

/**
 * Get a cache key for a drawn selection
 *
 * @param {string} name Name of selection group
 * @param {ve.dm.Selection} selection Selection model
 * @param {Object} [options] Selection options
 * @return {string} Cache key
 */
ve.ce.Surface.prototype.getDrawnSelectionCacheKey = function ( name, selection, options ) {
	options = options || {};
	return name + '-' + JSON.stringify( selection ) + '-' + ( options.color || '' ) + '-' + ( options.label || '' );
};

/**
 * Get an already drawn selection from the cache
 *
 * @param {string} name Name of selection group
 * @param {ve.dm.Selection} selection Selection model
 * @param {Object} [options] Selection options
 * @return {jQuery} Drawn selection
 */
ve.ce.Surface.prototype.getDrawnSelection = function ( name, selection, options ) {
	var cacheKey = this.getDrawnSelectionCacheKey( name, selection, options );
	return this.drawnSelectionCache[ cacheKey ] || null;
};

/**
 * Store an already drawn selection in the cache
 *
 * @param {jQuery} $selection Drawn selection
 * @param {string} name Name of selection group
 * @param {ve.dm.Selection} selection Selection model
 * @param {Object} [options] Selection options
 * @return {string} Cache key
 */
ve.ce.Surface.prototype.storeDrawnSelection = function ( $selection, name, selection, options ) {
	var cacheKey = this.getDrawnSelectionCacheKey( name, selection, options );
	this.drawnSelectionCache[ cacheKey ] = $selection;
	return cacheKey;
};

/**
 * Redraw selections
 *
 * This is triggered by a surface 'position' event, which fires when the surface
 * changes size, or when the document is modified. The drawnSelectionCache is
 * cleared as these two things will cause any previously calculated rectangles
 * to be incorrect.
 */
ve.ce.Surface.prototype.redrawSelections = function () {
	var surface = this;

	Object.keys( this.drawnSelections ).forEach( function ( name ) {
		var drawnSelection = surface.drawnSelections[ name ];
		drawnSelection.$selections.empty();
	} );

	this.drawnSelectionCache = {};
	Object.keys( this.drawnSelections ).forEach( function ( name ) {
		var drawnSelection = surface.drawnSelections[ name ];
		surface.drawSelections( name, drawnSelection.selections, drawnSelection.options );
	} );
};

/**
 * Handle document focus events.
 *
 * This is triggered by a global focusin/focusout event noticing a selection on the document.
 *
 * @fires focus
 */
ve.ce.Surface.prototype.onDocumentFocus = function () {
	if ( this.getModel().getSelection().isNull() ) {
		// If the document is being focused by a non-mouse/non-touch user event,
		// find the first content offset and place the cursor there.
		this.selectFirstVisibleStartContentOffset();
	}
	this.eventSequencer.attach( this.$element );
	this.surfaceObserver.startTimerLoop();
	this.focused = true;
	this.activate();
	this.$element.addClass( 've-ce-surface-focused' );
	this.emit( 'focus' );
};

/**
 * Handle document blur events.
 *
 * This is triggered by a global focusin/focusout event noticing no selection on the document.
 *
 * @fires blur
 */
ve.ce.Surface.prototype.onDocumentBlur = function () {
	var nullSelectionOnBlur = this.surface.nullSelectionOnBlur;
	if ( !nullSelectionOnBlur ) {
		// Set noSelectionChange as we already know the selection has left
		// the document and we don't want #deactivate to move it again.
		this.deactivate( false, true );
	}
	this.eventSequencer.detach();
	this.surfaceObserver.stopTimerLoop();
	this.surfaceObserver.pollOnce();
	this.surfaceObserver.clear();
	// Setting focused to false blocks selection change handler, so fire one last time here
	this.onDocumentSelectionChange();
	this.setDragging( false );
	this.focused = false;
	if ( nullSelectionOnBlur ) {
		if ( this.focusedNode ) {
			this.focusedNode.setFocused( false );
			this.focusedNode = null;
		}
		this.getModel().setNullSelection();
	}
	this.$element.removeClass( 've-ce-surface-focused' );
	this.emit( 'blur' );
};

/**
 * Check if surface is focused.
 *
 * @return {boolean} Surface is focused
 */
ve.ce.Surface.prototype.isFocused = function () {
	return this.focused;
};

/**
 * Handle document mouse down events.
 *
 * @param {jQuery.Event} e Mouse down event
 */
ve.ce.Surface.prototype.onDocumentMouseDown = function ( e ) {
	var surface = this;

	if ( e.which !== OO.ui.MouseButtons.LEFT ) {
		if ( e.which === OO.ui.MouseButtons.MIDDLE ) {
			// When middle click is also focusig the document, the selection may not end up
			// where you clicked, so record the offset from the click coordinates. (T311733)
			var targetOffset = -1;
			if ( this.getModel().getSelection().isNull() ) {
				targetOffset = this.getOffsetFromEventCoords( e );
			}
			this.middleClickTargetOffset = targetOffset !== -1 ? targetOffset : null;
			this.middleClickPasting = true;
			this.$document.one( 'mouseup', function () {
				// Stay true until other events have run, e.g. paste
				setTimeout( function () {
					surface.middleClickPasting = false;
				} );
			} );
		}
		return;
	}

	function isContexedNode( view ) {
		return surface.surface.context.getRelatedSourcesFromModels( [ view.model ] ).length;
	}

	var offset = this.getOffsetFromEventCoords( e );
	if ( offset !== -1 ) {
		var contexedAnnotations = this.annotationsAtNode( e.target, isContexedNode );
		if (
			OO.ui.isMobile() &&
			// The user has clicked on contexed annotations and ...
			contexedAnnotations.length && (
				// ... was previously on a focusable node or ...
				this.focusedNode ||
				// ... previously had different annotations selected ...
				!(
					// Shallow strict equality check
					this.contexedAnnotations.length === contexedAnnotations.length &&
					this.contexedAnnotations.every( function ( ann, i ) {
						return ann === contexedAnnotations[ i ];
					} )
				)
			)
		) {
			var node = e.target;
			setTimeout( function () {
				surface.getModel().setLinearSelection( new ve.Range( offset ) );
				// HACK: Re-activate flag so selection is repositioned
				surface.activate();
				surface.deactivate( false, false, true );
				surface.updateActiveAnnotations( node );
			} );
			this.contexedAnnotations = contexedAnnotations;
			e.preventDefault();
			return;
		}
		this.contexedAnnotations = contexedAnnotations;
	}

	// Remember the mouse is down
	this.setDragging( true );

	// Bind mouseup to the whole document in case of dragging out of the surface
	this.$document.on( 'mouseup', this.onDocumentMouseUpHandler );

	this.surfaceObserver.stopTimerLoop();
	// setTimeout: In some browsers the selection doesn't change until after the event
	// so poll in the 'after' function.
	// TODO: rewrite to use EventSequencer
	setTimeout( this.afterDocumentMouseDown.bind( this, e, this.getSelection() ) );

	// Handle triple click
	if ( e.originalEvent.detail >= 3 ) {
		// Browser default behaviour for triple click won't behave as we want
		e.preventDefault();

		var newFragment = this.getModel().getFragment()
			// After double-clicking in an inline slug, we'll get a selection like
			// <p><span><img />|</span></p><p>|Foo</p>. This selection spans a CBN boundary,
			// so we can't expand to the nearest CBN. To handle this case and other possible
			// cases where the selection spans a CBN boundary, collapse the selection before
			// expanding it. If the selection is entirely within the same CBN as it should be,
			// this won't change the result.
			.collapseToStart()
			// Cover the CBN we're in
			.expandLinearSelection( 'closest', ve.dm.ContentBranchNode )
			// …but that covered the entire CBN, we only want the contents
			.adjustLinearSelection( 1, -1 );
		// If something weird happened (e.g. no CBN found), newFragment will be null.
		// Don't select it in that case, because that'll blur the surface.
		if ( !newFragment.isNull() ) {
			newFragment.select();
		}
	}
};

/**
 * Deferred until after document mouse down
 *
 * @param {jQuery.Event} e Mouse down event
 * @param {ve.ce.Selection} selectionBefore Selection before the mouse event
 */
ve.ce.Surface.prototype.afterDocumentMouseDown = function ( e, selectionBefore ) {
	// TODO: guard with incRenderLock?
	this.surfaceObserver.pollOnce();
	if ( e.shiftKey ) {
		this.fixShiftClickSelect( selectionBefore );
	}
};

/**
 * Handle document mouse up events.
 *
 * @param {jQuery.Event} e Mouse up event
 */
ve.ce.Surface.prototype.onDocumentMouseUp = function ( e ) {
	this.$document.off( 'mouseup', this.onDocumentMouseUpHandler );
	this.surfaceObserver.startTimerLoop();
	// setTimeout: In some browsers the selection doesn't change until after the event
	// so poll in the 'after' function
	// TODO: rewrite to use EventSequencer
	setTimeout( this.afterDocumentMouseUp.bind( this, e, this.getSelection() ) );
};

/**
 * Deferred until after document mouse up
 *
 * @param {jQuery.Event} e Mouse up event
 * @param {ve.ce.Selection} selectionBefore Selection before the mouse event
 */
ve.ce.Surface.prototype.afterDocumentMouseUp = function ( e, selectionBefore ) {
	// TODO: guard with incRenderLock?
	this.surfaceObserver.pollOnce();
	if ( e.shiftKey ) {
		this.fixShiftClickSelect( selectionBefore );
	}
	this.setDragging( false );
};

/**
 * Fix shift-click selection
 *
 * Support: Chrome
 * When shift-clicking on links Chrome tries to collapse the selection
 * so check for this and fix manually.
 *
 * This can occur on mousedown or, if the existing selection covers the
 * link, on mouseup.
 *
 * https://code.google.com/p/chromium/issues/detail?id=345745
 *
 * @param {ve.ce.Selection} selectionBefore Selection before the mouse event
 */
ve.ce.Surface.prototype.fixShiftClickSelect = function ( selectionBefore ) {
	if ( !selectionBefore.isNativeCursor() ) {
		return;
	}
	var newSelection = this.getSelection();
	if ( newSelection.getModel().isCollapsed() && !newSelection.equals( selectionBefore ) ) {
		this.getModel().setLinearSelection(
			new ve.Range(
				selectionBefore.getModel().getRange().from,
				newSelection.getModel().getRange().to
			)
		);
	}
};

/**
 * Set a flag when the user is dragging a selection
 *
 * @param {boolean} dragging Dragging (mouse is down)
 */
ve.ce.Surface.prototype.setDragging = function ( dragging ) {
	this.dragging = !!dragging;
	// Class can be used to suppress hover states, such as branch slugs.
	this.$element.toggleClass( 've-ce-surface-dragging', this.dragging );
};

/**
 * Handle document selection change events.
 *
 * @param {jQuery.Event} e Selection change event
 */
ve.ce.Surface.prototype.onDocumentSelectionChange = function () {
	var selection = this.getModel().getSelection();
	if (
		// There is a non-empty selection in the VE surface. Use this if middle-click-to-paste is triggered later.
		!selection.isCollapsed() ||
		// There is no surface selection, and a native selection has been made elsewhere.
		// Null the lastNonCollapsedDocumentSelection so native middle-click-to-paste happens instead.
		( selection.isNull() && this.nativeSelection.rangeCount && !this.nativeSelection.getRangeAt( 0 ).collapsed )
	) {
		this.lastNonCollapsedDocumentSelection = selection;
	}

	// selectionChange events are only emitted from window.document, so ignore
	// any events which are fired when the document is blurred or deactivated.
	if ( !this.focused || this.deactivated ) {
		return;
	}
	this.fixupCursorPosition( 0, this.dragging );
	this.updateActiveAnnotations();
	this.surfaceObserver.pollOnceSelection();
};

/**
 * Handle document drag start events.
 *
 * @param {jQuery.Event} e Drag start event
 * @fires relocationStart
 */
ve.ce.Surface.prototype.onDocumentDragStart = function ( e ) {
	this.onCopy( e );
	this.startRelocation();
};

/**
 * Handle document drag over events.
 *
 * @param {jQuery.Event} e Drag over event
 */
ve.ce.Surface.prototype.onDocumentDragOver = function ( e ) {
	var dataTransferHandlerFactory = this.getSurface().dataTransferHandlerFactory,
		isContent = true,
		dataTransfer = e.originalEvent.dataTransfer;

	if ( this.readOnly ) {
		return;
	}

	var nodeType;
	if ( this.relocatingNode ) {
		isContent = this.relocatingNode.isContent();
		nodeType = this.relocatingNode.getType();
	} else {
		if ( this.allowedFile === null ) {
			this.allowedFile = false;
			// If we can get file metadata, check if there is a DataTransferHandler registered
			// to handle it.
			var i, l, item, fakeItem;
			if ( dataTransfer.items ) {
				for ( i = 0, l = dataTransfer.items.length; i < l; i++ ) {
					item = dataTransfer.items[ i ];
					if ( item.kind !== 'string' ) {
						fakeItem = new ve.ui.DataTransferItem( item.kind, item.type );
						if ( dataTransferHandlerFactory.getHandlerNameForItem( fakeItem ) ) {
							this.allowedFile = true;
							break;
						}
					}
				}
			} else if ( dataTransfer.files ) {
				for ( i = 0, l = dataTransfer.files.length; i < l; i++ ) {
					item = dataTransfer.items[ i ];
					fakeItem = new ve.ui.DataTransferItem( item.kind, item.type );
					if ( dataTransferHandlerFactory.getHandlerNameForItem( fakeItem ) ) {
						this.allowedFile = true;
						break;
					}
				}
			} else if ( Array.prototype.indexOf.call( dataTransfer.types || [], 'Files' ) !== -1 ) {
				// Support: Firefox
				// If we have no metadata (e.g. in Firefox) assume it is droppable
				this.allowedFile = true;
			}
		}
		// this.allowedFile is cached until the next dragleave event
		if ( this.allowedFile ) {
			isContent = false;
			nodeType = 'alienBlock';
		}
	}

	function getNearestDropTarget( node ) {
		while ( node.parent && !node.parent.isAllowedChildNodeType( nodeType ) ) {
			node = node.parent;
		}
		if ( node.parent ) {
			node.parent.traverseUpstream( function ( n ) {
				if ( n.shouldIgnoreChildren() ) {
					node = null;
					return false;
				}
			} );
			return node;
		}
	}

	if ( !isContent ) {
		e.preventDefault();
		var $target = $( e.target ).closest( '.ve-ce-branchNode, .ve-ce-leafNode' );
		var $dropTarget;
		var dropPosition;
		if ( $target.length ) {
			// Find the nearest node which will accept this node type
			var dropTargetNode = getNearestDropTarget( $target.data( 'view' ) );
			if ( dropTargetNode ) {
				$dropTarget = dropTargetNode.$element;
				dropPosition = e.originalEvent.pageY - $dropTarget.offset().top > $dropTarget.outerHeight() / 2 ? 'bottom' : 'top';
			} else {
				var targetOffset = this.getOffsetFromEventCoords( e.originalEvent );
				if ( targetOffset !== -1 ) {
					dropTargetNode = getNearestDropTarget( this.getDocument().getBranchNodeFromOffset( targetOffset ) );
					if ( dropTargetNode ) {
						$dropTarget = dropTargetNode.$element;
						dropPosition = 'top';
					}
				}
				if ( !$dropTarget ) {
					$dropTarget = this.$lastDropTarget;
					dropPosition = this.lastDropPosition;
				}
			}
		}
		if ( this.$lastDropTarget && (
			!this.$lastDropTarget.is( $dropTarget ) || dropPosition !== this.lastDropPosition
		) ) {
			this.$dropMarker.addClass( 'oo-ui-element-hidden' );
			$dropTarget = null;
		}
		if ( $dropTarget && (
			!$dropTarget.is( this.$lastDropTarget ) || dropPosition !== this.lastDropPosition
		) ) {
			var targetPosition = $dropTarget.position();
			// Go beyond margins as they can overlap
			var top = targetPosition.top + parseFloat( $dropTarget.css( 'margin-top' ) );
			var left = targetPosition.left + parseFloat( $dropTarget.css( 'margin-left' ) );
			if ( dropPosition === 'bottom' ) {
				top += $dropTarget.outerHeight();
			}
			this.$dropMarker
				.css( {
					top: top,
					left: left
				} )
				.width( $dropTarget.outerWidth() )
				.removeClass( 'oo-ui-element-hidden' );
		}
		if ( $dropTarget !== undefined ) {
			this.$lastDropTarget = $dropTarget;
			this.lastDropPosition = dropPosition;
		}
	}
};

/**
 * Handle document drag leave events.
 *
 * @param {jQuery.Event} e Drag leave event
 */
ve.ce.Surface.prototype.onDocumentDragLeave = function () {
	this.allowedFile = null;
	if ( this.$lastDropTarget ) {
		this.$dropMarker.addClass( 'oo-ui-element-hidden' );
		this.$lastDropTarget = null;
		this.lastDropPosition = null;
	}
};

/**
 * Handle document drop events.
 *
 * Limits native drag and drop behaviour.
 *
 * @param {jQuery.Event} e Drop event
 * @fires relocationEnd
 */
ve.ce.Surface.prototype.onDocumentDrop = function ( e ) {
	// Properties may be nullified by other events, so cache before setTimeout
	var surfaceModel = this.getModel(),
		dataTransfer = e.originalEvent.dataTransfer,
		$dropTarget = this.$lastDropTarget,
		dropPosition = this.lastDropPosition,
		platformKey = ve.getSystemPlatform() === 'mac' ? 'mac' : 'pc';

	// Prevent native drop event from modifying view
	e.preventDefault();

	if ( this.readOnly ) {
		return;
	}

	var targetOffset;
	// Determine drop position
	if ( $dropTarget ) {
		// Block level drag and drop: use the lastDropTarget to get the targetOffset
		if ( $dropTarget ) {
			var targetRange = $dropTarget.data( 'view' ).getModel().getOuterRange();
			if ( dropPosition === 'top' ) {
				targetOffset = targetRange.start;
			} else {
				targetOffset = targetRange.end;
			}
		} else {
			return;
		}
	} else {
		targetOffset = this.getOffsetFromEventCoords( e.originalEvent );
		if ( targetOffset === -1 ) {
			return;
		}
	}
	var targetFragment = surfaceModel.getLinearFragment( new ve.Range( targetOffset ) );

	var targetViewNode = this.getDocument().getBranchNodeFromOffset(
		targetFragment.getSelection().getCoveringRange().from
	);
	// TODO: Support sanitized drop on a single line node (removing line breaks)
	var isMultiline = targetViewNode.isMultiline();

	// Internal drop
	if ( this.relocatingSelection ) {
		// Get a fragment and data of the node being dragged
		var originFragment = surfaceModel.getFragment( this.relocatingSelection );
		var originData;
		if ( !isMultiline ) {
			// Data needs to be balanced to be sanitized
			var slice = this.model.documentModel.shallowCloneFromRange( originFragment.getSelection().getCoveringRange() );
			var linearData = new ve.dm.ElementLinearData(
				originFragment.getDocument().getStore(),
				slice.getBalancedData()
			);
			linearData.sanitize( { singleLine: true } );
			originData = linearData.data;
			// Unwrap CBN
			if ( originData[ 0 ].type && ve.dm.nodeFactory.canNodeContainContent( originData[ 0 ].type ) ) {
				originData = originData.slice( 1, originData.length - 1 );
			}
		} else {
			originData = originFragment.getData();
		}

		// Start staging so we can abort in the catch later
		surfaceModel.pushStaging();

		// Dragging performs cut-and-paste by default (remove content from old location).
		// If Ctrl on PC, or Opt (alt) on Mac, is held, it performs copy-and-paste instead.
		if ( ( platformKey === 'pc' && !e.ctrlKey ) || ( platformKey === 'mac' && !e.altKey ) ) {
			originFragment.removeContent();
		}

		try {
			// Re-insert data at new location
			targetFragment.insertContent( originData );
			surfaceModel.applyStaging();
		} catch ( error ) {
			// Insert content may throw an exception if it can't find a way
			// to fixup the insertion sensibly
			surfaceModel.popStaging();
		}

	} else {
		// External drop
		// TODO: Support sanitized external drop into single line contexts
		if ( isMultiline ) {
			this.handleDataTransfer( dataTransfer, false, targetFragment );
		}
	}
	this.endRelocation();
};

/**
 * Handle document key down events.
 *
 * @param {jQuery.Event} e Key down event
 */
ve.ce.Surface.prototype.onDocumentKeyDown = function ( e ) {
	var selection = this.getModel().getSelection(),
		updateFromModel = false;

	if ( selection.isNull() ) {
		return;
	}

	if ( e.which === 229 ) {
		// Support: Chrome
		// Ignore fake IME events (emitted in Chrome)
		return;
	}

	this.surfaceObserver.stopTimerLoop();
	this.incRenderLock();
	try {
		// TODO: is this correct?
		this.surfaceObserver.pollOnce();
	} finally {
		this.decRenderLock();
	}

	this.storeKeyDownState( e );

	if ( ve.ce.keyDownHandlerFactory.executeHandlersForKey( e.keyCode, selection.getName(), this, e ) ) {
		updateFromModel = true;
	} else {
		var trigger = new ve.ui.Trigger( e );
		if ( trigger.isComplete() ) {
			var executed = this.surface.executeWithSource( trigger, false, 'trigger' );
			if ( executed || this.isBlockedTrigger( trigger ) ) {
				e.preventDefault();
				e.stopPropagation();
				updateFromModel = true;
			}
		}
	}

	if (
		!e.isDefaultPrevented() &&
		e.keyCode === OO.ui.Keys.TAB &&
		// Not modified (excluding shift)
		!( e.metaKey || e.ctrlKey || e.altKey )
	) {
		// Manually move focus to the next/previous focusable element (T341687)
		var surfaceNode = this.$element[ 0 ];
		var treeWalker = document.createTreeWalker(
			document.body,
			NodeFilter.SHOW_ELEMENT,
			function ( n ) {
				if ( surfaceNode.contains( n ) ) {
					return NodeFilter.FILTER_REJECT;
				}
				if ( OO.ui.isFocusableElement( $( n ) ) ) {
					return NodeFilter.FILTER_ACCEPT;
				}
				return NodeFilter.FILTER_SKIP;
			}
		);
		treeWalker.currentNode = surfaceNode;
		if ( e.shiftKey ) {
			treeWalker.previousNode();
		} else {
			treeWalker.nextNode();
		}
		if ( treeWalker.currentNode ) {
			treeWalker.currentNode.focus();
			e.preventDefault();
			e.stopPropagation();
			return;
		}
	}

	if (
		this.readOnly && !(
			// Allowed keystrokes in readonly mode:
			// Arrows, simple navigation
			ve.ce.LinearArrowKeyDownHandler.static.keys.indexOf( e.keyCode ) !== -1 ||
			// Potential commands:
			// Function keys...
			( e.keyCode >= 112 && e.keyCode <= 123 ) ||
			// ... or anything modified (e.g. copy / select-all), excluding shift
			e.metaKey || e.ctrlKey || e.altKey
			// Keys already handled in keyDownHandlers above do not need to be exempt here
		)
	) {
		e.preventDefault();
		e.stopPropagation();
		return;
	}

	if ( !updateFromModel ) {
		this.incRenderLock();
	}
	try {
		this.surfaceObserver.pollOnce();
	} finally {
		if ( !updateFromModel ) {
			this.decRenderLock();
		}
	}
	this.surfaceObserver.startTimerLoop();
};

/**
 * Check if a trigger event is blocked from performing its default behaviour
 *
 * If any of these triggers can't execute on the surface, (e.g. the underline
 * command has been disabled), we should still preventDefault so ContentEditable
 * native commands don't occur, leaving the view out of sync with the model.
 *
 * @param {ve.ui.Trigger} trigger Trigger to check
 * @return {boolean} Trigger should preventDefault
 */
ve.ce.Surface.prototype.isBlockedTrigger = function ( trigger ) {
	var triggerString = trigger.toString(),
		platformKey = ve.getSystemPlatform() === 'mac' ? 'mac' : 'pc',
		blockedIfRegisteredTriggers = [ 'tab', 'shift+tab' ],
		blockedTriggers = {
			mac: [ 'cmd+b', 'cmd+i', 'cmd+u', 'cmd+z', 'cmd+y', 'cmd+shift+z', 'cmd+[', 'cmd+]' ],
			pc: [ 'ctrl+b', 'ctrl+i', 'ctrl+u', 'ctrl+z', 'ctrl+y', 'ctrl+shift+z' ]
		};

	// Special case: only block Tab/Shift+Tab if indentation commands are enabled on this surface,
	// otherwise allow them to change focus
	if ( blockedIfRegisteredTriggers.indexOf( triggerString ) !== -1 ) {
		return !!this.surface.triggerListener.getCommandByTrigger( triggerString );
	}

	return blockedTriggers[ platformKey ].indexOf( triggerString ) !== -1;
};

/**
 * Handle document key press events.
 *
 * @param {jQuery.Event} e Key press event
 */
ve.ce.Surface.prototype.onDocumentKeyPress = function ( e ) {
	var selection;

	// Handle the case where keyPress Enter is fired without a matching keyDown. This can
	// happen with OS X Romanising Korean IMEs on Firefox, when pressing Enter with
	// uncommitted candidate text; see T120156. Behave as though keyDown Enter has been
	// fired.
	if (
		e.keyCode === OO.ui.Keys.ENTER &&
		!this.keyDownState.event &&
		// We're only aware of cases of this happening with uncommitted candidate text,
		// which implies a native selection. But we instead perform a weaker test - for
		// a non-null selection - to match that same test in onDocumentKeyDown
		!( ( selection = this.getModel().getSelection() ).isNull() )
	) {
		this.surfaceObserver.stopTimerLoop();
		if ( ve.ce.keyDownHandlerFactory.executeHandlersForKey( e.keyCode, selection.getName(), this, e ) ) {
			this.surfaceObserver.pollOnce();
		}
		this.surfaceObserver.startTimerLoop();
		return;
	}

	// Filter out non-character keys. Doing this prevents:
	// * Unexpected content deletion when selection is not collapsed and the user presses, for
	//   example, the Home key (Firefox fires 'keypress' for it)
	// TODO: Should be covered with Selenium tests.
	if (
		// Catches most keys that don't produce output (charCode === 0, thus no character)
		e.which === 0 || e.charCode === 0 ||
		// Opera 12 doesn't always adhere to that convention
		e.keyCode === OO.ui.Keys.TAB || e.keyCode === OO.ui.Keys.ESCAPE ||
		// Ignore all keypresses with Ctrl / Cmd modifier keys
		ve.ce.isShortcutKey( e )
	) {
		return;
	}

	this.handleInsertion();
};

/**
 * Deferred until after document key down event
 *
 * @param {jQuery.Event} e keydown event
 */
ve.ce.Surface.prototype.afterDocumentKeyDown = function ( e ) {
	var keyDownSelectionState,
		surface = this,
		documentModel = this.getModel().getDocument(),
		isArrow = (
			e.keyCode === OO.ui.Keys.UP ||
			e.keyCode === OO.ui.Keys.DOWN ||
			e.keyCode === OO.ui.Keys.LEFT ||
			e.keyCode === OO.ui.Keys.RIGHT
		);

	/**
	 * Determine whether a position is editable, and if so which focusable node it is in
	 *
	 * We can land inside ce=false in many browsers:
	 * - Firefox has normal cursor positions at most node boundaries inside ce=false
	 * - Chromium has superfluous cursor positions around a ce=false img
	 * - IE hardly restricts editing at all inside ce=false
	 * If ce=false then we have landed inside the focusable node.
	 * If we land in a non-text position, assume we should have hit the node
	 * immediately after the position we hit (in the direction of motion)
	 * If we land inside a sequence of grouped nodes, assume we should treat them as a
	 * unit instead of letting the cursor slip inside them.
	 *
	 * @private
	 * @param {Node} node DOM node of cursor position
	 * @param {number} offset Offset of cursor position
	 * @param {number} dir Cursor motion direction (1=forward, -1=backward)
	 * @return {ve.ce.Node|null} node, or null if not in a focusable node
	 */
	function getSurroundingFocusableNode( node, offset, dir ) {
		var focusNode;
		if ( node.nodeType === Node.TEXT_NODE ) {
			focusNode = node;
		} else if ( dir > 0 && offset < node.childNodes.length ) {
			focusNode = node.childNodes[ offset ];
		} else if ( dir < 0 && offset > 0 ) {
			focusNode = node.childNodes[ offset - 1 ];
		} else {
			focusNode = node;
		}

		if ( ve.isContentEditable( focusNode ) ) {
			// We are allowed to be inside this focusable node (e.g. editing a
			// table cell or caption).
			return null;
		}
		return $( focusNode ).closest( '.ve-ce-focusableNode, .ve-ce-tableNode' ).data( 'view' ) || null;
	}

	/**
	 * Compute the direction of cursor movement, if any
	 *
	 * Even if the user pressed a cursor key in the interior of the document, there may not
	 * be any movement: browser BIDI and ce=false handling can be quite quirky.
	 *
	 * Furthermore, the keydown selection nodes may have become detached since keydown (e.g.
	 * if ve.ce.ContentBranchNode#renderContents has run).
	 *
	 * @return {number|null} negative for startwards, positive for endwards, null for none
	 */
	function getDirection() {
		return (
			isArrow &&
			keyDownSelectionState &&
			ve.compareDocumentOrder(
				surface.nativeSelection.focusNode,
				surface.nativeSelection.focusOffset,
				keyDownSelectionState.focusNode,
				keyDownSelectionState.focusOffset
			)
		) || null;
	}

	if ( e !== this.keyDownState.event ) {
		return;
	}
	keyDownSelectionState = this.keyDownState.selectionState;
	this.clearKeyDownState();

	if (
		( e.keyCode === OO.ui.Keys.BACKSPACE || e.keyCode === OO.ui.Keys.DELETE ) &&
		this.nativeSelection.focusNode
	) {
		var inNonSlug = this.nativeSelection.focusNode.nodeType === Node.ELEMENT_NODE &&
			!this.nativeSelection.focusNode.classList.contains( 've-ce-branchNode-inlineSlug' );
		if ( inNonSlug ) {
			// In a non-slug element. Sync the DM, then see if we need a slug.
			this.incRenderLock();
			try {
				this.surfaceObserver.pollOnce();
			} finally {
				this.decRenderLock();
			}

			var dmSelection = surface.model.getSelection();
			if ( dmSelection instanceof ve.dm.LinearSelection ) {
				var dmFocus = dmSelection.getRange().end;
				var ceNode = this.documentView.getBranchNodeFromOffset( dmFocus );
				if ( ceNode && ceNode.getModel().hasSlugAtOffset( dmFocus ) ) {
					ceNode.setupBlockSlugs();
				}
			}
		}

		// Remove then re-set the selection, to clear any browser-native preannotations.
		// This should be IME-safe because delete/backspace key events should only happen
		// when there is no IME candidate window open.
		//
		// Note that if an IME removes text otherwise than by delete/backspace, then
		// browser-native preannotations might still get applied. This can happen: see
		// https://phabricator.wikimedia.org/T116275 .
		// That's nasty, but it's not a reason to leave the delete/backspace case broken.
		var ceSelection = new ve.SelectionState( this.nativeSelection );
		this.nativeSelection.removeAllRanges();
		this.showSelectionState( ceSelection );

		if ( inNonSlug ) {
			return;
		}
	}

	// Only fixup cursoring on linear selections.
	if ( isArrow && !( surface.model.getSelection() instanceof ve.dm.LinearSelection ) ) {
		return;
	}

	// Restore the selection and stop, if we cursored out of a table edit cell.
	// Assumption: if we cursored out of a table cell, then none of the fixups below this point
	// would have got the selection back inside the cell. Therefore it's OK to check here.
	if ( isArrow && this.restoreActiveNodeSelection() ) {
		return;
	}

	// If we landed in a cursor holder, select the corresponding focusable node instead
	// (which, for a table, will select the first cell). Else if we arrowed a collapsed
	// cursor across a focusable node, select the node instead.
	var $focusNode = $( this.nativeSelection.focusNode );
	var direction;
	var focusableNode;
	var range;
	// eslint-disable-next-line no-jquery/no-class-state
	if ( $focusNode.hasClass( 've-ce-cursorHolder' ) ) {
		// eslint-disable-next-line no-jquery/no-class-state
		if ( $focusNode.hasClass( 've-ce-cursorHolder-after' ) ) {
			direction = -1;
			focusableNode = $focusNode.prev().data( 'view' );
		} else {
			direction = 1;
			focusableNode = $focusNode.next().data( 'view' );
		}
		this.removeCursorHolders();
	} else if (
		// If we arrowed a collapsed cursor into/across a focusable node, select the node instead
		isArrow &&
		!e.ctrlKey &&
		!e.altKey &&
		!e.metaKey &&
		keyDownSelectionState &&
		keyDownSelectionState.isCollapsed &&
		this.nativeSelection.isCollapsed &&
		( direction = getDirection() ) !== null
	) {
		focusableNode = getSurroundingFocusableNode(
			this.nativeSelection.focusNode,
			this.nativeSelection.focusOffset,
			direction
		);

		if ( !focusableNode ) {
			var startOffset, endOffset, offsetDiff;
			// Calculate the DM offsets of our motion
			try {
				startOffset = ve.ce.getOffset(
					keyDownSelectionState.focusNode,
					keyDownSelectionState.focusOffset
				);
				endOffset = ve.ce.getOffset(
					this.nativeSelection.focusNode,
					this.nativeSelection.focusOffset
				);
				offsetDiff = endOffset - startOffset;
			} catch ( ex ) {
				startOffset = endOffset = offsetDiff = undefined;
			}

			if ( Math.abs( offsetDiff ) === 2 ) {
				// Test whether we crossed a focusable node
				// (this applies even if we cursored up/down)
				focusableNode = documentModel.documentNode
					.getNodeFromOffset( ( startOffset + endOffset ) / 2 );

				if ( focusableNode.isFocusable() ) {
					range = new ve.Range( startOffset, endOffset );
				} else {
					focusableNode = undefined;
				}
			}
		}
	}

	if (
		isArrow &&
		direction > 0 &&
		this.getActiveNode() instanceof ve.ce.TableCaptionNode &&
		this.getActiveNode() !== $focusNode.closest( '.ve-ce-tableCaptionNode' ).data( 'view' )
	) {
		// We cursored down out of the table caption; move to the first table cell
		var tableNode = this.getActiveNode().getParent();
		this.model.setSelection( new ve.dm.TableSelection( tableNode.getOuterRange(), 0, 0 ) );
	}

	if ( focusableNode ) {
		if ( !range ) {
			range = focusableNode.getOuterRange();
			if ( direction < 0 ) {
				range = range.flip();
			}
		}
		if ( focusableNode instanceof ve.ce.TableNode ) {
			if ( direction > 0 ) {
				var captionNode;
				if ( ( captionNode = focusableNode.getModel().getCaptionNode() ) ) {
					this.model.setLinearSelection(
						documentModel.getRelativeRange( new ve.Range( captionNode.getRange().start ), 1 )
					);
				} else {
					this.model.setSelection( new ve.dm.TableSelection(
						range, 0, 0
					) );
				}
			} else {
				var matrix = focusableNode.getModel().getMatrix();
				var row = matrix.getRowCount() - 1;
				var col = matrix.getColCount( row ) - 1;
				this.model.setSelection( new ve.dm.TableSelection(
					range, col, row
				) );
			}
		} else {
			this.model.setLinearSelection( range );
		}
		if ( e.keyCode === OO.ui.Keys.LEFT ) {
			this.cursorDirectionality = direction > 0 ? 'rtl' : 'ltr';
		} else if ( e.keyCode === OO.ui.Keys.RIGHT ) {
			this.cursorDirectionality = direction < 0 ? 'rtl' : 'ltr';
		}
		// else up/down pressed; leave this.cursorDirectionality as null
		// (it was set by setLinearSelection calling onModelSelect)
	}

	if ( direction === undefined ) {
		direction = getDirection();
	}

	var fixupCursorForUnicorn = (
		!e.shiftKey &&
		( e.keyCode === OO.ui.Keys.LEFT || e.keyCode === OO.ui.Keys.RIGHT )
	);
	var removedUnicorns = this.cleanupUnicorns( fixupCursorForUnicorn );
	if ( removedUnicorns ) {
		this.surfaceObserver.pollOnceNoCallback();
	} else {
		this.incRenderLock();
		try {
			this.surfaceObserver.pollOnce();
		} finally {
			this.decRenderLock();
		}
	}
	this.fixupCursorPosition( direction, e.shiftKey );
};

/**
 * Check whether the DOM selection has moved out of the unicorned area (i.e. is not currently
 * between two unicorns) and if so, set the model selection from the DOM selection, destroy the
 * unicorns and return true. If there are no active unicorns, this function does nothing and
 * returns false.
 *
 * If the unicorns are destroyed as a consequence of the user moving the cursor across a unicorn
 * with the left/rightarrow keys, the cursor will have to be moved again to produce the cursor
 * movement the user expected. Set the fixupCursor parameter to true to enable this behavior.
 *
 * @param {boolean} fixupCursor If destroying unicorns, fix up left/rightarrow cursor position
 * @return {boolean} Whether unicorns have been destroyed
 */
ve.ce.Surface.prototype.cleanupUnicorns = function ( fixupCursor ) {
	if ( !this.unicorningNode || !this.unicorningNode.unicorns ) {
		return false;
	}
	var preUnicorn = this.unicorningNode.unicorns[ 0 ];
	if ( !this.$attachedRootNode[ 0 ].contains( preUnicorn ) ) {
		return false;
	}

	if ( this.nativeSelection.rangeCount === 0 ) {
		// XXX do we want to clear unicorns in this case?
		return false;
	}
	var range = this.nativeSelection.getRangeAt( 0 );

	// Test whether the selection endpoint is between unicorns. If so, do nothing.
	// Unicorns can only contain text, so just move backwards until we hit a non-text node.
	var node = range.endContainer;
	if ( node.nodeType === Node.ELEMENT_NODE ) {
		node = range.endOffset > 0 ? node.childNodes[ range.endOffset - 1 ] : null;
	}
	while ( node !== null && node.nodeType === Node.TEXT_NODE ) {
		node = node.previousSibling;
	}
	if ( node === preUnicorn ) {
		return false;
	}

	// Selection endpoint is not between unicorns.
	// Test whether it is before or after the pre-unicorn (i.e. before/after both unicorns)
	var fixup;
	if ( ve.compareDocumentOrder(
		range.endContainer,
		range.endOffset,
		preUnicorn.parentNode,
		ve.parentIndex( preUnicorn )
	) <= 0 ) {
		// Before the pre-unicorn (including in the equality case, because the selection
		// endpoint is an offset between sibling positions)
		fixup = -1;
	} else {
		// At or after the pre-unicorn (actually must be after the post-unicorn)
		fixup = 1;
	}

	var contentBranchNodeBefore = this.getSelectedContentBranchNode();
	if ( this.unicorningNode !== contentBranchNodeBefore ) {
		this.setNotUnicorningAll();
		return true;
	}

	// Apply the DOM selection to the model
	var veRange = ve.ce.veRangeFromSelection( this.nativeSelection );
	if ( veRange ) {
		this.incRenderLock();
		try {
			// The most likely reason for this condition to not-pass is if we
			// try to cleanup unicorns while the native selection is outside
			// the model momentarily, as sometimes happens during paste.
			this.changeModel( null, new ve.dm.LinearSelection( veRange ) );
			if ( fixupCursor ) {
				this.moveModelCursor( fixup );
			}
		} finally {
			this.decRenderLock();
		}
	}

	var contentBranchNodeAfter = this.getSelectedContentBranchNode();
	if ( contentBranchNodeAfter ) {
		contentBranchNodeAfter.renderContents();
	}
	if ( contentBranchNodeBefore && contentBranchNodeBefore !== contentBranchNodeAfter ) {
		contentBranchNodeBefore.renderContents();
	}

	this.showModelSelection();
	return true;
};

/**
 * Handle document key up events.
 *
 * @param {jQuery.Event} e Key up event
 * @fires keyup
 */
ve.ce.Surface.prototype.onDocumentKeyUp = function () {
	this.emit( 'keyup' );
};

/**
 * Handle cut events.
 *
 * @param {jQuery.Event} e Cut event
 */
ve.ce.Surface.prototype.onCut = function ( e ) {
	var surface = this,
		selection = this.getModel().getSelection();

	if ( selection.isCollapsed() ) {
		return;
	}

	this.onCopy( e );
	// setTimeout: postpone until after the setTimeout in onCopy
	setTimeout( function () {
		// Trigger a fake backspace to remove the content: this behaves differently based on the selection,
		// e.g. in a TableSelection.
		ve.ce.keyDownHandlerFactory.executeHandlersForKey( OO.ui.Keys.BACKSPACE, selection.getName(), surface, e );
	} );
};

/**
 * Handle copy (including cut) and dragstart events.
 *
 * @param {jQuery.Event} e Copy event
 * @param {ve.dm.Selection} selection Optional selection to simulate a copy on
 */
ve.ce.Surface.prototype.onCopy = function ( e, selection ) {
	// Copy or cut, but not dragstart
	var isClipboard = e.type === 'copy' || e.type === 'cut',
		htmlDoc = this.getModel().getDocument().getHtmlDocument(),
		clipboardData = isClipboard ? e.originalEvent.clipboardData : e.originalEvent.dataTransfer;

	selection = selection || this.getModel().getSelection();

	this.$pasteTarget.empty();

	if ( selection.isCollapsed() ) {
		return;
	}

	var slice = this.model.documentModel.shallowCloneFromSelection( selection );

	// Clone the elements in the slice
	slice.data.cloneElements( true );

	ve.dm.converter.getDomSubtreeFromModel( slice, this.$pasteTarget[ 0 ], ve.dm.Converter.static.CLIPBOARD_MODE );

	// Some browsers strip out spans when they match the styling of the
	// paste target (e.g. plain spans) so we must protect against this
	// by adding a dummy class, which we can remove after paste.
	this.$pasteTarget.find( 'span' ).addClass( 've-pasteProtect' );

	// When paste has no text content browsers do extreme normalization…
	if ( this.$pasteTarget.text() === '' ) {
		// …so put nbsp's in empty leaves
		// eslint-disable-next-line no-jquery/no-sizzle
		this.$pasteTarget.find( '*:not( :has( * ) )' ).text( '\u00a0' );
	}

	// Resolve attributes (in particular, expand 'href' and 'src' using the right base)
	ve.resolveAttributes(
		this.$pasteTarget[ 0 ],
		htmlDoc,
		ve.dm.Converter.static.computedAttributes
	);

	// Support: Firefox
	// Some attributes (e.g RDFa attributes in Firefox) aren't preserved by copy
	var unsafeSelector = '[' + ve.ce.Surface.static.unsafeAttributes.join( '],[' ) + ']';
	this.$pasteTarget.find( unsafeSelector ).each( function () {
		var i, val,
			attrs = {},
			ua = ve.ce.Surface.static.unsafeAttributes;

		i = ua.length;
		while ( i-- ) {
			val = this.getAttribute( ua[ i ] );
			if ( val !== null ) {
				attrs[ ua[ i ] ] = val;
			}
		}
		this.setAttribute( 'data-ve-attributes', JSON.stringify( attrs ) );
	} );

	this.clipboardIndex++;
	var clipboardKey = this.clipboardId + '-' + this.clipboardIndex;
	this.clipboard = { slice: slice, hash: null };
	// Support: Firefox<48
	// Writing the key to text/xcustom won't work in Firefox<48, so write
	// it to the HTML instead
	if ( isClipboard && !ve.isClipboardDataFormatsSupported( e ) ) {
		this.$pasteTarget.prepend(
			$( '<span>' ).attr( 'data-ve-clipboard-key', clipboardKey ).text( '\u00a0' )
		);
		// To ensure the contents with the clipboardKey isn't modified in an external editor,
		// store a hash of the contents for later validation.
		this.clipboard.hash = this.constructor.static.getClipboardHash( this.$pasteTarget.contents() );
	}

	if ( isClipboard ) {
		// Disable the default event so we can override the data
		e.preventDefault();
	}

	// Only write a custom mime type if we think the browser supports it, otherwise
	// we will have already written a key to the HTML above.
	if ( isClipboard && ve.isClipboardDataFormatsSupported( e, true ) ) {
		clipboardData.setData( 'text/xcustom', clipboardKey );
	}
	clipboardData.setData( 'text/html', this.$pasteTarget.html() );
	// innerText "approximates the text the user would get if they highlighted the
	// contents of the element with the cursor and then copied to the clipboard." - MDN
	// Use $.text as a fallback for Firefox <= 44
	clipboardData.setData( 'text/plain', this.$pasteTarget[ 0 ].innerText || this.$pasteTarget.text() || ' ' );

	ve.track( 'activity.clipboard', { action: e.type } );
};

/**
 * Get the annotation set that was a the user focus before a paste started
 *
 * @return {ve.dm.AnnotationSet} Annotation set
 */
ve.ce.Surface.prototype.getBeforePasteAnnotationSet = function () {
	var store = this.getModel().getDocument().getStore();
	var dmAnnotations = this.beforePasteAnnotationsAtFocus.map( function ( view ) {
		return view.getModel();
	} );
	return new ve.dm.AnnotationSet( store, store.hashAll( dmAnnotations ) );
};

/**
 * Handle native paste event
 *
 * @param {jQuery.Event} e Paste event
 * @return {boolean} False if the event is cancelled
 */
ve.ce.Surface.prototype.onPaste = function ( e ) {
	var surface = this;
	// Prevent pasting until after we are done
	if ( this.pasting || this.readOnly ) {
		return false;
	}
	this.beforePaste( e );
	this.surfaceObserver.disable();
	this.pasting = true;
	// setTimeout: postpone until after the default paste action
	setTimeout( function () {
		var afterPastePromise = ve.createDeferred().resolve().promise();
		try {
			if ( !e.isDefaultPrevented() ) {
				afterPastePromise = surface.afterPaste( e );
			}
		} finally {
			afterPastePromise.always( function () {
				surface.surfaceObserver.clear();
				surface.surfaceObserver.enable();

				// Allow pasting again
				surface.pasting = false;
				surface.pasteSpecial = false;
				surface.beforePasteData = null;

				// Restore original clipboard metadata if requred (was overridden by middle-click
				// paste logic in beforePaste)
				if ( surface.originalClipboardMetdata ) {
					surface.clipboardIndex = surface.originalClipboardMetdata.clipboardIndex;
					surface.clipboard = surface.originalClipboardMetdata.clipboard;
				}

				ve.track( 'activity.clipboard', { action: 'paste' } );
			} );
		}
	} );
};

/**
 * Handle pre-paste events.
 *
 * @param {jQuery.Event} e Paste event
 */
ve.ce.Surface.prototype.beforePaste = function ( e ) {
	var selection = this.getModel().getSelection(),
		clipboardData = e.originalEvent.clipboardData,
		surfaceModel = this.getModel(),
		fragment = surfaceModel.getFragment(),
		documentModel = surfaceModel.getDocument();

	var range;
	if ( selection instanceof ve.dm.LinearSelection ) {
		range = fragment.getSelection().getRange();
	} else if ( selection instanceof ve.dm.TableSelection ) {
		range = new ve.Range( selection.getRanges( documentModel )[ 0 ].start );
	} else {
		e.preventDefault();
		return;
	}

	this.beforePasteAnnotationsAtFocus = this.annotationsAtFocus();
	this.beforePasteData = {};
	this.originalClipboardMetdata = null;
	if ( this.middleClickPasting && !this.lastNonCollapsedDocumentSelection.isNull() ) {
		// Paste was triggered by middle click, and the last non-collapsed document selection was in
		// this VE surface. Simulate a fake copy to load DM data into the clipboard. If we let the
		// native middle-click paste happen, it would load CE data into the clipboard.
		// Store original clipboard metadata so it can be restored after paste,
		// and we can continue to use internal paste.
		this.originalClipboardMetdata = {
			clipboardIndex: this.clipboardIndex,
			clipboard: this.clipboard
		};
		// Use a fake clipboard index for middle click, will be restored in afterPaste
		this.clipboardIndex = -1;
		this.clipboard = {
			slice: this.model.documentModel.shallowCloneFromSelection( this.lastNonCollapsedDocumentSelection ),
			hash: null
		};
		this.beforePasteData.custom = this.clipboardId + '-' + this.clipboardIndex;
	} else if ( clipboardData ) {
		if ( this.handleDataTransfer( clipboardData, true ) ) {
			e.preventDefault();
			return;
		}
		this.beforePasteData.custom = clipboardData.getData( 'text/xcustom' );
		this.beforePasteData.html = clipboardData.getData( 'text/html' );
		if ( this.beforePasteData.html ) {
			// http://msdn.microsoft.com/en-US/en-%20us/library/ms649015(VS.85).aspx
			this.beforePasteData.html = this.beforePasteData.html
				.replace( /^[\s\S]*<!-- *StartFragment *-->/, '' )
				.replace( /<!-- *EndFragment *-->[\s\S]*$/, '' );
		}
	}

	// Save scroll position before changing focus to "offscreen" paste target
	this.beforePasteData.scrollTop = this.surface.$scrollContainer.scrollTop();

	this.$pasteTarget.empty();

	// Get node from cursor position
	var startNode = documentModel.getBranchNodeFromOffset( range.start );
	if ( startNode.canContainContent() ) {
		// If this is a content branch node, then add its DM HTML
		// to the paste target to give CE some context.
		var textStart = 0, textEnd = 0;
		var contextElement = startNode.getClonedElement();
		// Make sure that context doesn't have any attributes that might confuse
		// the importantElement check in afterPaste.
		$( documentModel.getStore().value( contextElement.originalDomElementsHash ) ).removeAttr( 'id typeof rel' );
		var context = [ contextElement ];
		// If there is content to the left of the cursor, put a placeholder
		// character to the left of the cursor
		var leftText, rightText;
		if ( range.start > startNode.getRange().start ) {
			leftText = '☀';
			context.push( leftText );
			textStart = textEnd = 1;
		}
		// If there is content to the right of the cursor, put a placeholder
		// character to the right of the cursor
		var endNode = documentModel.getBranchNodeFromOffset( range.end );
		if ( range.end < endNode.getRange().end ) {
			rightText = '☂';
			context.push( rightText );
		}
		// If there is no text context, select some text to be replaced
		if ( !leftText && !rightText ) {
			context.push( '☁' );
			textEnd = 1;
			// If we are middle click pasting we can't change the native selection, so
			// just make the text a placeholder to the left. (T311723)
			if ( this.middleClickPasting ) {
				leftText = '☁';
				textStart = 1;
			}
		}
		context.push( { type: '/' + context[ 0 ].type } );

		// Throw away 'internal', specifically inner whitespace,
		// before conversion as it can affect textStart/End offsets.
		delete contextElement.internal;
		ve.dm.converter.getDomSubtreeFromModel(
			documentModel.cloneWithData( context, true ),
			this.$pasteTarget[ 0 ]
		);

		// Giving the paste target focus too late can cause problems in FF (!?)
		// so do it up here.
		this.$pasteTarget[ 0 ].focus();

		var nativeRange = this.getElementDocument().createRange();
		// Assume that the DM node only generated one child
		var textNode = this.$pasteTarget.children().contents()[ 0 ];
		// Place the cursor between the placeholder characters
		nativeRange.setStart( textNode, textStart );
		nativeRange.setEnd( textNode, textEnd );
		this.nativeSelection.removeAllRanges();
		this.nativeSelection.addRange( nativeRange );

		this.beforePasteData.context = context;
		this.beforePasteData.leftText = leftText;
		this.beforePasteData.rightText = rightText;
	} else {
		// If we're not in a content branch node, don't bother trying to do
		// anything clever with paste context
		this.$pasteTarget[ 0 ].focus();
	}

	// Restore scroll position after focusing the paste target
	this.surface.$scrollContainer.scrollTop( this.beforePasteData.scrollTop );

};

/**
 * Handle post-paste events.
 *
 * @param {jQuery.Event} e Paste event
 * @return {jQuery.Promise} Promise which resolves when the content has been pasted
 */
ve.ce.Surface.prototype.afterPaste = function () {
	var surfaceModel = this.getModel(),
		documentModel = surfaceModel.getDocument(),
		fragment = surfaceModel.getFragment(),
		targetFragment = surfaceModel.getFragment( null, true ),
		view = this,
		beforePasteData = this.beforePasteData || {},
		done = ve.createDeferred().resolve().promise();

	// If the selection doesn't collapse after paste then nothing was inserted
	if ( !this.nativeSelection.isCollapsed ) {
		return done;
	}

	if ( this.getModel().getFragment().isNull() ) {
		return done;
	}

	if ( this.middleClickTargetOffset ) {
		targetFragment = targetFragment.clone( new ve.dm.LinearSelection( new ve.Range( this.middleClickTargetOffset ) ) );
	} else if ( this.middleClickPasting ) {
		// Middle click pasting should always collapse the selection before pasting
		targetFragment = targetFragment.collapseToEnd();
	}

	// Immedately remove any <style> tags from the pasteTarget that might
	// be changing the rendering of the whole page (T235068)
	this.$pasteTarget.find( 'style' ).remove();

	var pasteData = this.afterPasteExtractClipboardData();

	// Handle pastes into a table
	if ( fragment.getSelection() instanceof ve.dm.TableSelection ) {
		// Internal table-into-table paste can be shortcut
		if ( fragment.getSelection() instanceof ve.dm.TableSelection && pasteData.slice instanceof ve.dm.TableSlice ) {
			var tableAction = new ve.ui.TableAction( this.getSurface() );
			tableAction.importTable( pasteData.slice.getTableNode( documentModel ) );
			return ve.createDeferred().resolve().promise();
		}

		// For table selections the target is the first cell
		targetFragment = surfaceModel.getLinearFragment( fragment.getSelection().getRanges( documentModel )[ 0 ], true );
	}

	// Are we pasting into a multiline context?
	var isMultiline = this.getDocument().getBranchNodeFromOffset(
		targetFragment.getSelection().getCoveringRange().from
	).isMultiline();

	var pending;
	if ( pasteData.slice ) {
		pending = this.afterPasteAddToFragmentFromInternal( pasteData.slice, fragment, targetFragment, isMultiline );
	} else {
		pending = this.afterPasteAddToFragmentFromExternal( pasteData.clipboardKey, pasteData.$clipboardHtml, fragment, targetFragment, isMultiline );
	}
	return pending.then( function () {
		if ( view.getSelection().isNativeCursor() ) {
			// Restore focus and scroll position
			view.$attachedRootNode[ 0 ].focus();
			view.surface.$scrollContainer.scrollTop( beforePasteData.scrollTop );
			// setTimeout: Firefox sometimes doesn't change scrollTop immediately when pasting
			// line breaks at the end of a line so do it again later.
			setTimeout( function () {
				view.surface.$scrollContainer.scrollTop( beforePasteData.scrollTop );
			} );
		}

		// If original selection was linear, switch to end of pasted text
		if ( fragment.getSelection() instanceof ve.dm.LinearSelection ) {
			targetFragment.collapseToEnd().select();
			view.findAndExecuteSequences( /* isPaste */ true );
		}
	} );
};

/**
 * Extract the clipboard key and other relevant data from beforePasteData / the paste target
 *
 * @return {Object} Data
 * @return {string|undefined} return.clipboardKey Clipboard key, if present
 * @return {jQuery|undefined} return.$clipboardHtml Clipboard html, if used to extract the clipboard key
 * @return {ve.dm.DocumentSlice|undefined} return.slice Relevant slice of this document, if the key points to it
 */
ve.ce.Surface.prototype.afterPasteExtractClipboardData = function () {
	var clipboardKey, clipboardHash, $clipboardHtml,
		beforePasteData = this.beforePasteData || {};

	// Find the clipboard key
	if ( beforePasteData.custom ) {
		// text/xcustom was present, and requires no further processing
		clipboardKey = beforePasteData.custom;
	} else {
		if ( beforePasteData.html ) {
			// text/html was present, so we can check if a key was hidden in it
			$clipboardHtml = $( ve.sanitizeHtml( beforePasteData.html ) ).filter( function () {
				var val = this.getAttribute && this.getAttribute( 'data-ve-clipboard-key' );
				if ( val ) {
					clipboardKey = val;
					// Remove the clipboard key span once read
					return false;
				}
				return true;
			} );
			clipboardHash = this.constructor.static.getClipboardHash( $clipboardHtml );
		} else {
			// fall back on checking the pasteTarget

			// HTML in pasteTarget may get wrapped, so use the recursive $.find to look for the clipboard key
			clipboardKey = this.$pasteTarget.find( 'span[data-ve-clipboard-key]' ).data( 've-clipboard-key' );
			// Pass beforePasteData so context gets stripped
			clipboardHash = this.constructor.static.getClipboardHash( this.$pasteTarget, beforePasteData );
		}
	}

	var slice;
	// If we have a clipboard key, validate it and fetch data
	if ( clipboardKey === this.clipboardId + '-' + this.clipboardIndex ) {
		// Hash validation: either text/xcustom was used or the hash must be
		// equal to the hash of the pasted HTML to assert that the HTML
		// hasn't been modified in another editor before being pasted back.
		if ( beforePasteData.custom || clipboardHash === this.clipboard.hash ) {
			slice = this.clipboard.slice;
			// Clone again. The elements were cloned on copy, but we need to clone
			// on paste too in case the same thing is pasted multiple times.
			slice.data.cloneElements( true );
		}
	}

	if ( !slice && !$clipboardHtml && beforePasteData.html ) {
		$clipboardHtml = $( ve.sanitizeHtml( beforePasteData.html ) );
	}

	return {
		clipboardKey: clipboardKey,
		$clipboardHtml: $clipboardHtml,
		slice: slice
	};
};

/**
 * LinearData sanitize helper, for pasted data
 *
 * @param {ve.dm.LinearData} linearData Data to sanitize
 * @param {boolean} isMultiline Sanitize for a multiline context
 * @param {boolean} isExternal Treat as external content
 */
ve.ce.Surface.prototype.afterPasteSanitize = function ( linearData, isMultiline, isExternal ) {
	var importRules = this.afterPasteImportRules( isMultiline );
	if ( isExternal ) {
		linearData.sanitize( importRules.external || {} );
	}
	linearData.sanitize( importRules.all || {} );
};

/**
 * Helper to build import rules for pasted data
 *
 * @param {boolean} isMultiline Get rules for a multiline context
 * @return {Object.<string,Object>} Import rules
 */
ve.ce.Surface.prototype.afterPasteImportRules = function ( isMultiline ) {
	var importRules = !this.pasteSpecial ? this.getSurface().getImportRules() : { all: { plainText: true, keepEmptyContentBranches: true } };
	if ( !isMultiline ) {
		importRules = {
			all: ve.extendObject( {}, importRules.all, { singleLine: true } ),
			external: ve.extendObject( {}, importRules.external, { singleLine: true } )
		};
	}
	return importRules;
};

/**
 * After paste handler for pastes from the same document
 *
 * @param {ve.dm.DocumentSlice} slice Slice of document to paste
 * @param {ve.dm.SurfaceFragment} fragment Current fragment
 * @param {ve.dm.SurfaceFragment} targetFragment Fragment to insert into
 * @param {boolean} isMultiline Pasting to a multiline context
 * @return {jQuery.Promise} Promise which resolves when the content has been inserted
 */
ve.ce.Surface.prototype.afterPasteAddToFragmentFromInternal = function ( slice, fragment, targetFragment, isMultiline ) {
	// Pasting non-table content into table: just replace the first cell with the pasted content
	if ( fragment.getSelection() instanceof ve.dm.TableSelection ) {
		// Cell was not deleted in beforePaste to prevent flicker when table-into-table paste is
		// about to be triggered.
		targetFragment.removeContent();
	}

	// Only try original data in multiline contexts, for single line we must use balanced data

	var linearData, insertionPromise;
	// Original data + fixupInsertion
	if ( isMultiline ) {
		// Take a copy to prevent the data being annotated a second time in the balanced data path
		// and to prevent actions in the data model affecting view.clipboard
		linearData = new ve.dm.ElementLinearData(
			slice.getStore(),
			ve.copy( slice.getOriginalData() )
		);

		if ( this.pasteSpecial ) {
			this.afterPasteSanitize( linearData, isMultiline );
		}

		// ve.dm.Document#fixupInsertion may fail, in which case we fall back to balanced data
		try {
			insertionPromise = this.afterPasteInsertInternalData( targetFragment, linearData.getData() );
		} catch ( e ) {}
	}

	// Balanaced data
	if ( !insertionPromise ) {
		// Take a copy to prevent actions in the data model affecting view.clipboard
		linearData = new ve.dm.ElementLinearData(
			slice.getStore(),
			ve.copy( slice.getBalancedData() )
		);

		if ( this.pasteSpecial || !isMultiline ) {
			this.afterPasteSanitize( linearData, isMultiline );
		}

		var data = linearData.getData();

		if ( !isMultiline ) {
			// Unwrap single CBN
			if ( data[ 0 ].type ) {
				data = data.slice( 1, data.length - 1 );
			}
		}

		insertionPromise = this.afterPasteInsertInternalData( targetFragment, data );
	}

	return insertionPromise;
};

/**
 * Insert some pasted data from an internal source
 *
 * @param {ve.dm.SurfaceFragment} targetFragment Fragment to insert into
 * @param {Array} data Data to insert
 * @return {jQuery.Promise} Promise which resolves when the content has been inserted
 */
ve.ce.Surface.prototype.afterPasteInsertInternalData = function ( targetFragment, data ) {
	targetFragment.insertContent( data, this.getBeforePasteAnnotationSet() );
	return targetFragment.getPending();
};

/**
 * After paste handler for pastes from the another document
 *
 * @param {string|undefined} clipboardKey Clipboard key for pasted data
 * @param {jQuery|undefined} $clipboardHtml Clipboard HTML, if used to find the key
 * @param {ve.dm.SurfaceFragment} fragment Current fragment
 * @param {ve.dm.SurfaceFragment} targetFragment Fragment to insert into
 * @param {boolean} [isMultiline] Pasting to a multiline context
 * @param {boolean} [forceClipboardData] Ignore the paste target, and use only clipboard html
 * @return {jQuery.Promise} Promise which resolves when the content has been inserted
 */
ve.ce.Surface.prototype.afterPasteAddToFragmentFromExternal = function ( clipboardKey, $clipboardHtml, fragment, targetFragment, isMultiline, forceClipboardData ) {
	var importantElement = '[id],[typeof],[rel],figure',
		items = [],
		surfaceModel = this.getModel(),
		documentModel = surfaceModel.getDocument(),
		beforePasteData = this.beforePasteData || {};

	var htmlDoc;
	// There are two potential sources of HTML to choose from:
	// * this.$pasteTarget where we we let the past happen in a context similar to the surface
	// * beforePasteData.html which is read from the clipboard API
	//
	// If clipboard API data is available, then make sure important elements haven't been dropped.
	//
	// The only reason we don't use clipboard API data unconditionally is that for simpler pastes,
	// the $pasteTarget method does a good job of merging content, e.g. paragraps into paragraphs.
	//
	// If we could do a better job of mimicking how browsers merge content, the clipboard API data
	// would produce much more consistent results, as the pasteTarget approach can also re-order
	// and destroy nodes.
	if (
		$clipboardHtml && (
			forceClipboardData ||
			// FIXME T126045: Allow the test runner to force the use of clipboardData
			clipboardKey === 'useClipboardData-0' ||
			$clipboardHtml.find( importantElement ).addBack( importantElement ).length > this.$pasteTarget.find( importantElement ).length
		)
	) {
		// CE destroyed an important element, so revert to using clipboard data
		htmlDoc = ve.sanitizeHtmlToDocument( beforePasteData.html );
		$( htmlDoc )
			// Remove the pasteProtect class. See #onCopy.
			.find( 'span' ).removeClass( 've-pasteProtect' ).end()
			// Remove the clipboard key
			.find( 'span[data-ve-clipboard-key]' ).remove().end()
			// Remove ve-attributes, we trust that clipboard data preserved these attributes
			.find( '[data-ve-attributes]' ).removeAttr( 'data-ve-attributes' );
		beforePasteData.context = null;
	}
	if ( !htmlDoc ) {
		// If we're using $pasteTarget, let CE do its sanitizing as it may
		// contain disruptive metadata (head tags etc.)
		htmlDoc = ve.sanitizeHtmlToDocument( this.$pasteTarget.html() );
	}

	// Some browsers don't provide pasted image data through the clipboardData API and
	// instead create img tags with data URLs, so detect those here
	var $body = $( htmlDoc.body );
	var $images = $body.children( 'img[src^=data\\:]' );
	// Check the body contained just images.
	// TODO: In the future this may want to trigger image uploads *and* paste the HTML.
	if ( $images.length && $images.length === $body.children().length ) {
		for ( var i = 0; i < $images.length; i++ ) {
			items.push( ve.ui.DataTransferItem.static.newFromDataUri(
				$images.eq( i ).attr( 'src' ),
				$images[ i ].outerHTML
			) );
		}
		if ( this.handleDataTransferItems( items, true ) ) {
			return ve.createDeferred().resolve().promise();
		}
	}

	this.afterPasteSanitizeExternal( $( htmlDoc.body ) );

	// HACK: Fix invalid HTML from Google Docs nested lists (T98100).
	// Converts
	// <ul><li>A</li><ul><li>B</li></ul></ul>
	// to
	// <ul><li>A<ul><li>B</li></ul></li></ul>
	$( htmlDoc.body ).find( 'ul > ul, ul > ol, ol > ul, ol > ol' ).each( function () {
		if ( this.previousElementSibling ) {
			this.previousElementSibling.appendChild( this );
		} else {
			// List starts double indented. This is invalid and a semantic nightmare.
			// Just wrap with an extra list item
			$( this ).wrap( '<li>' );
		}
	} );

	// HACK: Fix invalid HTML from copy-pasting `display: inline` lists (T239550).
	$( htmlDoc.body ).find( 'li, dd, dt' ).each( function () {
		var list,
			listType = { li: 'ul', dd: 'dl', dt: 'dl' },
			tag = this.tagName.toLowerCase(),
			// Parent node always exists because we're searching inside <body>
			parentTag = this.parentNode.tagName.toLowerCase();

		if (
			( tag === 'li' && ( parentTag !== 'ul' && parentTag !== 'ol' ) ) ||
			( ( tag === 'dd' || tag === 'dt' ) && parentTag !== 'dl' )
		) {
			// This list item's parent node is not a list. This breaks expectations in DM code.
			// Wrap this node and its list item siblings in a list node.
			list = htmlDoc.createElement( listType[ tag ] );
			this.parentNode.insertBefore( list, this );

			while (
				list.nextElementSibling &&
				listType[ list.nextElementSibling.tagName.toLowerCase() ] === listType[ tag ]
			) {
				list.appendChild( list.nextElementSibling );
			}
		}
	} );

	// HTML sanitization
	var htmlBlacklist = ve.getProp( this.afterPasteImportRules( isMultiline ), 'external', 'htmlBlacklist' );
	if ( htmlBlacklist && !clipboardKey ) {
		if ( htmlBlacklist.remove ) {
			Object.keys( htmlBlacklist.remove ).forEach( function ( selector ) {
				if ( htmlBlacklist.remove[ selector ] ) {
					$( htmlDoc.body ).find( selector ).remove();
				}
			} );
		}
		if ( htmlBlacklist.unwrap ) {
			Object.keys( htmlBlacklist.unwrap ).forEach( function ( selector ) {
				if ( htmlBlacklist.unwrap[ selector ] ) {
					$( htmlDoc.body ).find( selector ).contents().unwrap();
				}
			} );
		}
	}

	// External paste
	var pastedDocumentModel = ve.dm.converter.getModelFromDom( htmlDoc, {
		targetDoc: documentModel.getHtmlDocument(),
		fromClipboard: true
	} );
	var data = pastedDocumentModel.data;
	// Clone again
	data.cloneElements( true );

	// Sanitize
	this.afterPasteSanitize( data, isMultiline, !clipboardKey );

	data.remapInternalListKeys( documentModel.getInternalList() );

	// Initialize node tree
	pastedDocumentModel.buildNodeTree();

	if ( fragment.getSelection() instanceof ve.dm.TableSelection ) {
		// External table-into-table paste
		if (
			pastedDocumentModel.documentNode.children.length === 2 &&
			pastedDocumentModel.documentNode.children[ 0 ] instanceof ve.dm.TableNode
		) {
			var tableAction = new ve.ui.TableAction( this.getSurface() );
			tableAction.importTable( pastedDocumentModel.documentNode.children[ 0 ], true );
			return ve.createDeferred().resolve().promise();
		}

		// Pasting non-table content into table: just replace the first cell with the pasted content
		// Cell was not deleted in beforePaste to prevent flicker when table-into-table paste is about to be triggered.
		targetFragment.removeContent();
	}

	var contextRange;
	if ( beforePasteData.context ) {
		// If the paste was given context, calculate the range of the inserted data
		contextRange = this.afterPasteFromExternalContextRange( pastedDocumentModel, isMultiline, forceClipboardData );
		if ( !contextRange ) {
			return this.afterPasteAddToFragmentFromExternal( clipboardKey, $clipboardHtml, fragment, targetFragment, isMultiline, true );
		}
	} else {
		contextRange = pastedDocumentModel.getDocumentRange();
	}
	var pastedNodes = pastedDocumentModel.selectNodes( contextRange, 'siblings' ).filter( function ( node ) {
		// Ignore nodes where nothing is selected
		return !( node.range && node.range.isCollapsed() );
	} );

	// Unwrap single content branch nodes to match internal copy/paste behaviour
	// (which wouldn't put the open and close tags in the clipboard to begin with).
	if (
		pastedNodes.length === 1 &&
		pastedNodes[ 0 ].node.canContainContent()
	) {
		if ( contextRange.containsRange( pastedNodes[ 0 ].nodeRange ) ) {
			contextRange = pastedNodes[ 0 ].nodeRange;
		}
	}

	return this.afterPasteInsertExternalData( targetFragment, pastedDocumentModel, contextRange );
};

/**
 * Insert some pasted data from an external source
 *
 * @param {ve.dm.SurfaceFragment} targetFragment Fragment to insert into
 * @param {ve.dm.Document} pastedDocumentModel Model generated from pasted data
 * @param {ve.Range} contextRange Range of data in generated model to consider
 * @return {jQuery.Promise} Promise which resolves when the content has been inserted
 */
ve.ce.Surface.prototype.afterPasteInsertExternalData = function ( targetFragment, pastedDocumentModel, contextRange ) {
	var handled;
	// If the external HTML turned out to be plain text after sanitization
	// then run it as a plain text transfer item. In core this will never
	// do anything, but implementations can provide their own handler for
	// conversion actions here.
	if ( pastedDocumentModel.data.isPlainText( contextRange, true, undefined, true ) ) {
		var pastedText = pastedDocumentModel.data.getText( true, contextRange );
		if ( pastedText ) {
			handled = this.handleDataTransferItems(
				[ ve.ui.DataTransferItem.static.newFromString( pastedText ) ],
				true,
				targetFragment
			);
		}
	}
	if ( !handled ) {
		targetFragment.insertDocument( pastedDocumentModel, contextRange, this.getBeforePasteAnnotationSet() );
	}
	return targetFragment.getPending();
};

/**
 * Helper to work out the context range for an external paste
 *
 * @param {ve.dm.Document} pastedDocumentModel Model for pasted data
 * @param {boolean} isMultiline Whether pasting to a multiline context
 * @param {boolean} forceClipboardData Whether the current attempted paste is the result of forcing use of clipboard data
 * @return {ve.Range|boolean} Context range, or false if data appeared corrupted
 */
ve.ce.Surface.prototype.afterPasteFromExternalContextRange = function ( pastedDocumentModel, isMultiline, forceClipboardData ) {
	var data = pastedDocumentModel.data,
		documentRange = pastedDocumentModel.getDocumentRange(),
		beforePasteData = this.beforePasteData || {},
		context = new ve.dm.ElementLinearData(
			pastedDocumentModel.getStore(),
			ve.copy( beforePasteData.context )
		);
	// Sanitize context to match data
	this.afterPasteSanitize( context, isMultiline );

	var leftText = beforePasteData.leftText;
	var rightText = beforePasteData.rightText;

	// Remove matching context from the left
	var left = 0;
	while (
		context.getLength() &&
		ve.dm.ElementLinearData.static.compareElementsUnannotated(
			data.getData( left ),
			data.isElementData( left ) ? context.getData( 0 ) : leftText
		)
	) {
		if ( !data.isElementData( left ) ) {
			// Text context is removed
			leftText = '';
		}
		left++;
		context.splice( 0, 1 );
	}

	// Remove matching context from the right
	var right = documentRange.end;
	while (
		right > 0 &&
		context.getLength() &&
		ve.dm.ElementLinearData.static.compareElementsUnannotated(
			data.getData( right - 1 ),
			data.isElementData( right - 1 ) ? context.getData( context.getLength() - 1 ) : rightText
		)
	) {
		if ( !data.isElementData( right - 1 ) ) {
			// Text context is removed
			rightText = '';
		}
		right--;
		context.splice( context.getLength() - 1, 1 );
	}
	if ( ( leftText || rightText ) && !forceClipboardData ) {
		// If any text context is left over, assume the paste target got corrupted
		// so we should start again and try to use clipboardData instead. T193110
		return false;
	}
	// Support: Chrome
	// FIXME T126046: Strip trailing linebreaks probably introduced by Chrome bug
	while ( right > 0 && data.getType( right - 1 ) === 'break' ) {
		right--;
	}
	return new ve.Range( left, right );
};

/**
 * Helper to clean up externally pasted HTML (via pasteTarget).
 *
 * @param {jQuery} $element Root element containing pasted stuff to sanitize
 */
ve.ce.Surface.prototype.afterPasteSanitizeExternal = function ( $element ) {
	var metadataIdRegExp = ve.init.platform.getMetadataIdRegExp();

	// Remove the clipboard key
	$element.find( 'span[data-ve-clipboard-key]' ).remove();
	// Remove style tags (T185532)
	$element.find( 'style' ).remove();
	// If this is from external, run extra sanitization:

	// Do some simple transforms to catch content that is using
	// spans+styles instead of regular tags. This is very much targeted at
	// the output of Google Docs, but should work with anything fairly-
	// similar. This is *fragile*, but more in the sense that small
	// deviations will stop it from working, rather than it being terribly
	// likely to incorrectly over-format things.
	// TODO: This might be cleaner if we could move the sanitization into
	// dm.converter entirely.
	$element.find( 'span' ).each( function ( i, node ) {
		// Later sanitization will replace completely-empty spans with
		// their contents, so we can lazily-wrap here without cleaning
		// up.
		if ( !node.style ) {
			return;
		}
		var $node = $( node );
		if ( +node.style.fontWeight >= 700 || node.style.fontWeight === 'bold' ) {
			$node.wrap( '<b>' );
		}
		if ( node.style.fontStyle === 'italic' ) {
			$node.wrap( '<i>' );
		}
		if ( node.style.textDecorationLine === 'underline' ) {
			$node.wrap( '<u>' );
		}
		if ( node.style.textDecorationLine === 'line-through' ) {
			$node.wrap( '<s>' );
		}
		if ( node.style.verticalAlign === 'super' ) {
			$node.wrap( '<sup>' );
		}
		if ( node.style.verticalAlign === 'sub' ) {
			$node.wrap( '<sub>' );
		}
	} );

	// Remove style attributes. Any valid styles will be restored by data-ve-attributes.
	$element.find( '[style]' ).removeAttr( 'style' );

	if ( metadataIdRegExp ) {
		$element.find( '[id]' ).each( function () {
			var $this = $( this );
			if ( metadataIdRegExp.test( $this.attr( 'id' ) ) ) {
				$this.removeAttr( 'id' );
			}
		} );
	}

	// Remove the pasteProtect class (see #onCopy) and unwrap empty spans.
	$element.find( 'span' ).each( function () {
		var $this = $( this );
		$this.removeClass( 've-pasteProtect' );
		if ( $this.attr( 'class' ) === '' ) {
			$this.removeAttr( 'class' );
		}
		// Unwrap empty spans
		if ( !this.attributes.length ) {
			// childNodes is a NodeList
			// eslint-disable-next-line no-jquery/no-append-html
			$this.replaceWith( this.childNodes );
		}
	} );

	// Restore attributes. See #onCopy.
	$element.find( '[data-ve-attributes]' ).each( function () {
		var attrsJSON = this.getAttribute( 'data-ve-attributes' );

		// Always remove the attribute, even if the JSON has been corrupted
		this.removeAttribute( 'data-ve-attributes' );

		var attrs;
		try {
			attrs = JSON.parse( attrsJSON );
		} catch ( err ) {
			// Invalid JSON
			return;
		}
		$( this ).attr( attrs );
	} );
};

/**
 * Handle the insertion of a data transfer object
 *
 * @param {DataTransfer} dataTransfer Data transfer
 * @param {boolean} isPaste Handlers being used for paste
 * @param {ve.dm.SurfaceFragment} [targetFragment] Fragment to insert data items at, defaults to current selection
 * @return {boolean} One more items was handled
 */
ve.ce.Surface.prototype.handleDataTransfer = function ( dataTransfer, isPaste, targetFragment ) {
	var items = [],
		htmlStringData = dataTransfer.getData( 'text/html' );

	// Rules for clipboard content selection:
	//  1. If the clipboard has only HTML, proceed parsing such HTML.
	//  2. If the clipboard has only files, process them as-is.
	//  3. If the clipboard has both:
	//    a. If the HTML in the clipboard contains only images and other elements with no text, process the image files.
	//    b. Otherwise, ignore the files and process the HTML.
	//
	// Notes:
	//  - If a file is pasted/dropped, it may have HTML fallback, such as an IMG node with alt text, for example.
	//  - HTML generated from some clients has an image fallback(!) that is a screenshot of the HTML snippet (e.g. LibreOffice Calc)
	var i, l;
	if ( !htmlStringData ) {
		if ( dataTransfer.items ) {
			for ( i = 0, l = dataTransfer.items.length; i < l; i++ ) {
				if ( dataTransfer.items[ i ].kind !== 'string' ) {
					items.push( ve.ui.DataTransferItem.static.newFromItem( dataTransfer.items[ i ], htmlStringData ) );
				}
			}
		} else if ( dataTransfer.files ) {
			for ( i = 0, l = dataTransfer.files.length; i < l; i++ ) {
				items.push( ve.ui.DataTransferItem.static.newFromBlob( dataTransfer.files[ i ], htmlStringData ) );
			}
		}
	} else if ( dataTransfer.files ) {
		var htmlPreParse = $.parseHTML( htmlStringData );

		var imgCount = 0;
		var hasContent = false;
		for ( i = 0; i < htmlPreParse.length; i++ ) {
			// Count images in root nodes
			if ( htmlPreParse[ i ].nodeName === 'IMG' ) {
				imgCount++;
			} else if (
				( htmlPreParse[ i ].nodeType === 1 || htmlPreParse[ i ].nodeType === 3 ) &&
				htmlPreParse[ i ].textContent &&
				htmlPreParse[ i ].textContent.trim() !== ''
			) {
				// Only count element nodes (type 1) or text nodes (type 3)
				// that have non empty text content.
				hasContent = true;
			}

			// Count images in children
			if ( typeof htmlPreParse[ i ].querySelectorAll === 'function' ) {
				imgCount += htmlPreParse[ i ].querySelectorAll( 'img' ).length;
			}
		}

		if ( !hasContent && imgCount === dataTransfer.files.length ) {
			for ( i = 0, l = dataTransfer.files.length; i < l; i++ ) {
				// TODO: should we use image node outerHTML instead of htmlStringData?
				items.push( ve.ui.DataTransferItem.static.newFromBlob( dataTransfer.files[ i ], htmlStringData ) );
			}
		}
	}

	if ( dataTransfer.items ) {
		// Extract "string" types.
		for ( i = 0, l = dataTransfer.items.length; i < l; i++ ) {
			if (
				dataTransfer.items[ i ].kind === 'string' &&
				dataTransfer.items[ i ].type.slice( 0, 5 ) === 'text/'
			) {
				items.push( ve.ui.DataTransferItem.static.newFromString(
					dataTransfer.getData( dataTransfer.items[ i ].type ),
					dataTransfer.items[ i ].type,
					htmlStringData
				) );
			}
		}
	}

	// We care a little bit about the order of items, as the first one matched
	// is going to be the one we handle, and don't trust dataTransfer.items to
	// be in the fallback order we'd prefer. In practice, this just means that
	// we want to text/html and text/plain to be at the end of the list, as
	// they tend to show up as common fallbacks.
	var pushItemToBack = function ( array, type ) {
		var j, jlen;
		for ( j = 0, jlen = array.length; j < jlen; j++ ) {
			if ( array[ j ].type === type ) {
				return array.push( array.splice( j, 1 )[ 0 ] );
			}
		}
	};
	pushItemToBack( items, 'text/html' );
	pushItemToBack( items, 'text/plain' );

	return this.handleDataTransferItems( items, isPaste, targetFragment );
};

/**
 * Handle the insertion of data transfer items
 *
 * @param {ve.ui.DataTransferItem[]} items Data transfer items
 * @param {boolean} isPaste Handlers being used for paste
 * @param {ve.dm.SurfaceFragment} [targetFragment] Fragment to insert data items at, defaults to current selection
 * @return {boolean} One more items was handled
 */
ve.ce.Surface.prototype.handleDataTransferItems = function ( items, isPaste, targetFragment ) {
	targetFragment = targetFragment || this.getModel().getFragment();

	function insert( docOrData ) {
		var resultFragment, rootChildren;
		// For non-paste transfers, don't overwrite the selection
		resultFragment = !isPaste ? targetFragment.collapseToEnd() : targetFragment;
		if ( docOrData instanceof ve.dm.Document ) {
			rootChildren = docOrData.getDocumentNode().children;
			if (
				rootChildren[ 0 ] &&
				rootChildren[ 0 ].type === 'paragraph' &&
				( !rootChildren[ 1 ] || rootChildren[ 1 ].type === 'internalList' )
			) {
				resultFragment.insertDocument(
					docOrData,
					rootChildren[ 0 ].getRange()
				);
			} else {
				resultFragment.insertDocument( docOrData );
			}
		} else {
			resultFragment.insertContent( docOrData );
		}
		// The resultFragment's selection now covers the inserted content;
		// adjust selection to end of inserted content.
		resultFragment.collapseToEnd().select();
	}

	var dataTransferHandlerFactory = this.getSurface().dataTransferHandlerFactory;
	var handled = false;
	for ( var i = 0, l = items.length; i < l; i++ ) {
		var item = items[ i ];
		var name = dataTransferHandlerFactory.getHandlerNameForItem( item, isPaste, this.pasteSpecial );
		if ( name ) {
			dataTransferHandlerFactory.create( name, this.surface, item )
				.getInsertableData().done( insert );
			handled = true;
			break;
		} else if ( isPaste && item.type === 'text/html' ) {
			// Don't handle anything else if text/html is available, as it is handled specially in #afterPaste
			break;
		}
	}
	return handled;
};

/**
 * Select all the contents within the current context
 */
ve.ce.Surface.prototype.selectAll = function () {
	var selection = this.getModel().getSelection(),
		dmDoc = this.getModel().getDocument();

	if ( selection instanceof ve.dm.LinearSelection ) {
		var activeNode = this.getActiveNode();
		var range;
		if ( activeNode ) {
			range = activeNode.getRange();
			range = new ve.Range( range.from + 1, range.to - 1 );
		} else {
			var documentRange = this.getModel().getDocument().getDocumentRange();
			range = new ve.Range(
				dmDoc.getNearestCursorOffset( 0, 1 ),
				dmDoc.getNearestCursorOffset( documentRange.end, -1 )
			);
		}
		this.getModel().setLinearSelection( range );
	} else if ( selection instanceof ve.dm.TableSelection ) {
		var matrix = selection.getTableNode( dmDoc ).getMatrix();
		this.getModel().setSelection(
			new ve.dm.TableSelection(
				selection.tableRange,
				0, 0, matrix.getMaxColCount() - 1, matrix.getRowCount() - 1
			)
		);
	}
};

/**
 * Handle beforeinput events.
 *
 * @param {jQuery.Event} e The input event
 */
ve.ce.Surface.prototype.onDocumentBeforeInput = function ( e ) {
	if ( this.getSelection().isNativeCursor() ) {
		var surface = this,
			inputType = e.originalEvent ? e.originalEvent.inputType : null;

		// Support: Chrome (Android, Gboard)
		// Handle IMEs that emit text fragments with a trailing newline on Enter keypress (T312558)
		if (
			( inputType === 'insertText' || inputType === 'insertCompositionText' ) &&
			e.originalEvent.data && e.originalEvent.data.slice( -1 ) === '\n'
		) {
			// The event will have inserted a newline into the CE view,
			// so fix up the DM accordingly depending on the context.
			this.eventSequencer.afterOne( {
				beforeinput: surface.fixupChromiumNativeEnter.bind( surface )
			} );
		}
	}
};

/**
 * Remove unwanted DOM elements from the CE view, inserted by Chromium's native Enter handling.
 * We preventDefault an Enter keydown event, but the native handling can still happen with some
 * IME combinations, e.g. Gboard on Android Chromium in many languages including English when
 * there is candidate text (T312558).
 */
ve.ce.Surface.prototype.fixupChromiumNativeEnter = function () {
	var range,
		surface = this,
		fixedUp = false;

	function setCursorToEnd( element ) {
		if ( !element ) {
			return;
		}
		range = surface.getElementDocument().createRange();
		range.setStart( element, element.childNodes.length );
		range.setEnd( element, element.childNodes.length );
		surface.nativeSelection.removeAllRanges();
		surface.nativeSelection.addRange( range );
	}

	// Test for Chromium native Enter inside a <li class="foo"><p class="bar">...</p></li>
	// creating unwanted trailing <li class="foo"><p class="bar"><br></p></li>,
	// assuming it (initially) leaves the selection in the original list item.
	var listItemNode = $( this.nativeSelection.focusNode ).closest( 'li.ve-ce-branchNode' )[ 0 ];
	if ( listItemNode ) {
		var nextNode = listItemNode.nextElementSibling;
		if ( nextNode && !$.data( nextNode, 'view' ) ) {
			// We infer the native Enter action added a spurious DOM node that does
			// not exist in the view. Remove it and flag that we need to perform a
			// VE Enter action.
			nextNode.parentNode.removeChild( nextNode );
			fixedUp = true;
		}
	}
	// Test for Chromium native Enter inside a <p class="foo"></p>, while there is
	// candidate text, creating a spurious trailing <div><br></div>, and (immediately)
	// putting the cursor inside it.
	//
	// Note if the paragraph is a grandchild of the list item, it's not clear under what
	// circumstances Chromium creates a div vs a list item, so perform this check even if
	// a fixup already happened in the lines of code above.
	var div = $( this.nativeSelection.focusNode ).closest( 'div' )[ 0 ];
	if ( div && this.$documentNode[ 0 ].contains( div ) && !$.data( div, 'view' ) ) {
		// The div is inside a branch node, but has no view. We infer the native Enter
		// action added a spurious DOM node that does not exist in the view. Remove it and
		// flag that we need to perform a VE enter action.
		setCursorToEnd( div.previousElementSibling );
		div.parentNode.removeChild( div );
		fixedUp = true;
	}
	// If we found nodes to fixup, that means the VE Enter handler never ran (since it would
	// have prevented the Enter event), so execute it now to perform the context-appropriate
	// operation.
	if ( fixedUp ) {
		// First, poll current node for content changes, because any autocorrect change
		// will not have reached the model. The logic above ensures the cursor will be
		// inside the ContentBranchNode where the user was typing in either case.
		this.surfaceObserver.pollOnce();
		ve.ce.keyDownHandlerFactory.lookup( 'linearEnter' ).static.execute( this, new Event( 'dummy' ) );
	}
};

/**
 * Handle input events.
 *
 * @param {jQuery.Event} e The input event
 */
ve.ce.Surface.prototype.onDocumentInput = function ( e ) {
	// Synthetic events don't have the originalEvent property (T176104)
	var surface = this,
		inputType = e.originalEvent ? e.originalEvent.inputType : null;

	// Special handling of NBSP insertions. T53045
	// NBSPs are converted to normal spaces in ve.ce.TextState as they can be
	// inserted by ContentEditable in unexpected places, or accidentally imported
	// by copy-paste. Usually they are not intended, but if we detect an NBSP in
	// an insertion event that means it was probably intentional, e.g. inserted
	// by a specific keyboard shortcut, or IME sequence.
	if (
		this.getSelection().isNativeCursor() &&
		( inputType === 'insertText' || inputType === 'insertCompositionText' ) &&
		e.originalEvent.data === '\u00a0'
	) {
		// Wait for the insertion to happen
		setTimeout( function () {
			var
				fragment = surface.getModel().getFragment().adjustLinearSelection( -1 ),
				nbspContent = '&nbsp;';
			if ( surface.getSurface().getMode() === 'visual' ) {
				nbspContent = ve.init.platform.decodeEntities( nbspContent );
			}
			// Check a plain space was inserted and replace it with an NBSP.
			if ( fragment.getText() === ' ' ) {
				fragment.insertContent( nbspContent ).collapseToEnd().select();
			}
		} );
	}

	var inputTypeCommands = this.constructor.static.inputTypeCommands;
	if (
		inputType &&
		Object.prototype.hasOwnProperty.call( inputTypeCommands, inputType )
	) {
		// Value can be null, in which case we still want to preventDefault.
		if ( inputTypeCommands[ inputType ] ) {
			this.getSurface().executeCommand( this.constructor.static.inputTypeCommands[ inputType ] );
		}
		e.preventDefault();
		return;
	}
	this.incRenderLock();
	try {
		this.surfaceObserver.pollOnce();
	} finally {
		this.decRenderLock();
	}
};

/**
 * Handle compositionstart events.
 * Note that their meaning varies between browser/OS/IME combinations
 *
 * @param {jQuery.Event} e The compositionstart event
 */
ve.ce.Surface.prototype.onDocumentCompositionStart = function () {
	// Eagerly trigger emulated deletion on certain selections, to ensure a ContentEditable
	// native node merge never happens. See https://phabricator.wikimedia.org/T123716 .
	if (
		this.model.selection instanceof ve.dm.TableSelection &&
		$.client.profile().layout === 'gecko'
	) {
		// Support: Firefox
		// Work around a segfault on blur+focus in Firefox compositionstart handlers.
		// It would get triggered by handleInsertion emptying the table cell then putting
		// a linear selection inside it. See:
		// https://phabricator.wikimedia.org/T86589
		// https://bugzilla.mozilla.org/show_bug.cgi?id=1230473
		return;
	}
	this.handleInsertion();
};

/* Custom Events */

/**
 * Handle model select events.
 *
 * @see ve.dm.Surface#method-change
 */
ve.ce.Surface.prototype.onModelSelect = function () {
	var selection = this.getModel().getSelection();

	setTimeout( this.findAndExecuteDelayedSequences.bind( this ) );

	this.cursorDirectionality = null;
	this.contentBranchNodeChanged = false;
	this.selection = null;

	if ( selection.isNull() ) {
		this.removeCursorHolders();
	}

	if ( selection instanceof ve.dm.LinearSelection ) {
		var blockSlug = this.findBlockSlug( selection.getRange() );
		if ( blockSlug !== this.focusedBlockSlug ) {
			if ( this.focusedBlockSlug ) {
				this.focusedBlockSlug.classList.remove(
					've-ce-branchNode-blockSlug-focused'
				);
				this.focusedBlockSlug = null;
			}

			if ( blockSlug ) {
				blockSlug.classList.add( 've-ce-branchNode-blockSlug-focused' );
				this.focusedBlockSlug = blockSlug;
				this.preparePasteTargetForCopy();
			}
		}

		var focusedNode = this.findFocusedNode( selection.getRange() );

		if ( this.isDeactivated() && !this.isShownAsDeactivated() && !blockSlug && !focusedNode ) {
			// If deactivated without showing (e.g. by preparePasteTargetForCopy),
			// reactivate when changing selection (T221291)
			// TODO: It is really messy that the surface can get reactivated based on the state of
			// a flag that should just be used for rendering (isShownAsDeactivated). This led to
			// T236400 so should be fixed.
			this.activate();
		}

		// If focus has changed, update nodes and this.focusedNode
		if ( focusedNode !== this.focusedNode ) {
			if ( this.focusedNode ) {
				this.focusedNode.setFocused( false );
				this.focusedNode = null;
			}
			if ( focusedNode ) {
				focusedNode.setFocused( true );
				this.focusedNode = focusedNode;

				// If dragging, we already have a native selection, so don't mess with it
				if ( !this.dragging ) {
					this.preparePasteTargetForCopy();
					// Since the selection is no longer in the root, clear the SurfaceObserver's
					// selection state. Otherwise, if the user places the selection back into the root
					// in exactly the same place where it was before, the observer won't consider that a change.
					this.surfaceObserver.clear();
				}
			}
		}
	} else {
		if ( selection instanceof ve.dm.TableSelection ) {
			this.preparePasteTargetForCopy();
		}
		if ( this.focusedNode ) {
			this.focusedNode.setFocused( false );
		}
		this.focusedNode = null;
	}

	// Deactivate immediately if mobile and read-only to avoid showing keyboard (T281771)
	if ( this.isReadOnly() && OO.ui.isMobile() ) {
		this.deactivate( false, false, true );
	}

	// Ignore the selection if changeModelSelection is currently being
	// called with the same (object-identical) selection object
	// (i.e. if the model is calling us back)
	if ( !this.isRenderingLocked() && selection !== this.newModelSelection ) {
		this.showModelSelection();
		this.cleanupUnicorns( false );
	}
	// Update the selection state in the SurfaceObserver
	this.surfaceObserver.pollOnceNoCallback();
};

/**
 * Prepare the paste target for a copy event by selecting some text
 *
 * @param {boolean} force Force a native selection, even on mobile (used for click-to-copy)
 */
ve.ce.Surface.prototype.preparePasteTargetForCopy = function ( force ) {
	// As FF won't fire a copy event with nothing selected, create a native selection.
	// If there is a focusedNode available, use its text content so that context menu
	// items such as "Search for [SELECTED TEXT]" make sense. If the text is empty or
	// whitespace, use a single unicode character as this is required for programmatic
	// selection to work correctly in all browsers (e.g. Safari won't select a single space).
	// #onCopy will ignore this native selection and use the DM selection
	if ( force || !OO.ui.isMobile() ) {
		this.$pasteTarget.text( ( this.focusedNode && this.focusedNode.$element.text().trim() ) || '☢' );
		ve.selectElement( this.$pasteTarget[ 0 ] );
		this.$pasteTarget[ 0 ].focus();
	} else {
		// Selecting the paste target fails on mobile:
		// * On iOS The selection stays visible and causes scrolling
		// * The user is unlikely to be able to trigger a keyboard copy anyway
		// Instead just deactivate the surface so the native cursor doesn't
		// get in the way and the on screen keyboard doesn't show.
		// TODO: Provide a copy tool in the context menu (T202278)
		this.deactivate( true );
	}
};

/**
 * Get the focused node (optionally at a specified range), or null if one is not present
 *
 * @param {ve.Range} [range] Optional range to check for focused node, defaults to current selection's range
 * @return {ve.ce.Node|null} Focused node
 */
ve.ce.Surface.prototype.getFocusedNode = function ( range ) {
	if ( !range ) {
		return this.focusedNode;
	}
	var selection = this.getModel().getSelection();
	if (
		selection instanceof ve.dm.LinearSelection &&
		range.equalsSelection( selection.getRange() )
	) {
		return this.focusedNode;
	}
	return this.findFocusedNode( range );
};

/**
 * Find the block slug a given range is in.
 *
 * @param {ve.Range} range Range to check
 * @return {HTMLElement|null} Slug, or null if no slug or if range is not collapsed
 * @throws {Error} If range is inside internal list
 */
ve.ce.Surface.prototype.findBlockSlug = function ( range ) {
	if ( !range.isCollapsed() ) {
		return null;
	}
	var node = this.documentView.getBranchNodeFromOffset( range.end );
	if ( !node || !node.canHaveChildrenNotContent() ) {
		// Node can not have block slugs (only inline slugs)
		return null;
	}
	return node.getSlugAtOffset( range.end );
};

/**
 * Find the focusedNode at a specified range
 *
 * @param {ve.Range} range Range to search at for a focusable node
 * @return {ve.ce.Node|null} Focused node
 */
ve.ce.Surface.prototype.findFocusedNode = function ( range ) {
	var documentNode = this.getDocument().getDocumentNode();
	// Detect when only a single focusable element is selected
	var startNode;
	if ( !range.isCollapsed() ) {
		startNode = documentNode.getNodeFromOffset( range.start + 1 );
		if ( startNode && startNode.isFocusable() ) {
			var endNode = documentNode.getNodeFromOffset( range.end - 1 );
			if ( startNode === endNode ) {
				return startNode;
			}
		}
	} else {
		// Check if the range is inside a focusable node with a collapsed selection
		startNode = documentNode.getNodeFromOffset( range.start );
		if ( startNode && startNode.isFocusable() ) {
			return startNode;
		}
	}
	return null;
};

/**
 * Handle documentUpdate events on the surface model.
 */
ve.ce.Surface.prototype.onModelDocumentUpdate = function () {
	var surface = this;
	if ( this.contentBranchNodeChanged ) {
		// Update the selection state from model
		this.onModelSelect();
	}
	// Update the state of the SurfaceObserver
	this.surfaceObserver.pollOnceNoCallback();
	// setTimeout: Wait for other documentUpdate listeners to run before emitting
	setTimeout( function () {
		surface.emit( 'position' );
	} );
};

/**
 * Handle insertionAnnotationsChange events on the surface model.
 *
 * @param {ve.dm.AnnotationSet} insertionAnnotations
 */
ve.ce.Surface.prototype.onInsertionAnnotationsChange = function () {
	var changed = this.renderSelectedContentBranchNode();
	if ( !changed ) {
		return;
	}
	// Must re-apply the selection after re-rendering
	this.forceShowModelSelection();
	this.surfaceObserver.pollOnceNoCallback();
};

/**
 * Get the ContentBranchNode containing the selection focus, if any
 *
 * @return {ve.ce.ContentBranchNode|null} ContentBranchNode containing selection focus, or null
 */
ve.ce.Surface.prototype.getSelectedContentBranchNode = function () {
	var selection = this.model.getSelection();

	if ( !( selection instanceof ve.dm.LinearSelection ) ) {
		return null;
	}
	var node = this.documentView.getBranchNodeFromOffset( selection.getRange().to );
	if ( !node || !( node instanceof ve.ce.ContentBranchNode ) ) {
		return null;
	}
	return node;
};

/**
 * Re-render the ContentBranchNode containing the selection focus, if any
 *
 * @return {boolean} Whether a re-render actually happened
 */
ve.ce.Surface.prototype.renderSelectedContentBranchNode = function () {
	var node = this.getSelectedContentBranchNode();
	if ( !node ) {
		return false;
	}
	return node.renderContents();
};

/**
 * Handle changes observed from the DOM
 *
 * These are normally caused by the user interacting directly with the contenteditable.
 *
 * @param {ve.ce.RangeState|null} oldState The prior range state, if any
 * @param {ve.ce.RangeState} newState The changed range state
 */
ve.ce.Surface.prototype.handleObservedChanges = function ( oldState, newState ) {
	var surface = this,
		dmDoc = this.getModel().getDocument(),
		insertedText = false,
		removedText = false;

	if ( newState.contentChanged ) {
		if ( this.readOnly ) {
			newState.node.renderContents();
			this.showModelSelection();
			return;
		} else {
			var transaction = newState.textState.getChangeTransaction(
				oldState.textState,
				dmDoc,
				newState.node.getOffset(),
				newState.node.unicornAnnotations
			);
			if ( transaction ) {
				this.incRenderLock();
				try {
					this.changeModel( transaction );
				} finally {
					this.decRenderLock();
				}
				insertedText = transaction.operations.some( function ( op ) {
					return op.type === 'replace' && op.insert.length;
				} );
				removedText = transaction.operations.some( function ( op ) {
					return op.type === 'replace' && op.remove.length;
				} );
			}
		}
	}

	if (
		!this.readOnly &&
		newState.branchNodeChanged &&
		oldState &&
		oldState.node &&
		oldState.node.root &&
		oldState.node instanceof ve.ce.ContentBranchNode
	) {
		oldState.node.renderContents();
	}

	if ( newState.selectionChanged && !(
		// Ignore when the newRange is just a flipped oldRange
		oldState &&
		oldState.veRange &&
		newState.veRange &&
		!newState.veRange.isCollapsed() &&
		oldState.veRange.equalsSelection( newState.veRange )
	) ) {
		var newSelection;
		if ( newState.veRange ) {
			if ( newState.veRange.isCollapsed() ) {
				var offset = dmDoc.getNearestCursorOffset( newState.veRange.from, 0 );
				if ( offset === -1 ) {
					// First, if we're in a document which outright doesn't
					// have any content to select, don't try to set one. These
					// would be niche documents, since slugs normally exist
					// and catch those cases.
					newSelection = new ve.dm.NullSelection();
					// TODO: Unset whatever native selection got us here, to match
					// the model state (assuming it is in the CE document)
				} else {
					// If we're placing the cursor, make sure it winds up in a
					// cursorable location. Failure to do this can result in
					// strange behavior when inserting content immediately after
					// clicking on the surface.
					newSelection = new ve.dm.LinearSelection( new ve.Range( offset ) );
				}
			} else {
				newSelection = new ve.dm.LinearSelection( newState.veRange );
			}
		} else {
			newSelection = new ve.dm.NullSelection();
		}
		this.incRenderLock();
		try {
			this.changeModel( null, newSelection );
			if ( newSelection instanceof ve.dm.LinearSelection && newSelection.isCollapsed() ) {
				var blockSlug = this.findBlockSlug( newSelection.getRange() );
				if ( blockSlug ) {
					// Set the DOM selection, in case the model selection did not change but
					// the DOM selection did (T201599).
					this.preparePasteTargetForCopy();
					this.surfaceObserver.pollOnceNoCallback();
				}
			}
		} finally {
			this.decRenderLock();
		}
		var removedUnicorns = this.cleanupUnicorns( false );
		if ( removedUnicorns ) {
			this.surfaceObserver.pollOnceNoCallback();
		}

		// Ensure we don't observe a selection that breaks out of the active node
		var activeNode = this.getActiveNode();
		var coveringRange = newSelection.getCoveringRange();
		if ( activeNode && coveringRange ) {
			var nodeRange = activeNode.getRange();
			var containsStart = nodeRange.containsRange( new ve.Range( coveringRange.start ) );
			var containsEnd = nodeRange.containsRange( new ve.Range( coveringRange.end ) );
			// If the range starts xor ends in the active node, but not both, then it must
			// span an active node boundary, so fixup.
			if ( containsStart !== containsEnd ) {
				newSelection = oldState && oldState.veRange ?
					new ve.dm.LinearSelection( oldState.veRange ) :
					new ve.dm.NullSelection();
				// TODO: setTimeout: document purpose
				setTimeout( function () {
					surface.changeModel( null, newSelection );
					surface.showModelSelection();
				} );
			}
		}

		// Support: Firefox
		// Firefox lets you create multiple selections within a single paragraph
		// which our model doesn't support, so detect and prevent these.
		// This shouldn't create problems with IME candidates as only an explicit user
		// action can create a multiple selection (CTRL+click), and we remove it
		// immediately, so there can never be a multiple selection while the user is
		// typing text; therefore the selection change will never commit IME candidates
		// prematurely.
		while ( this.nativeSelection.rangeCount > 1 ) {
			// The current range is the last range, so remove ranges from the front
			this.nativeSelection.removeRange( this.nativeSelection.getRangeAt( 0 ) );
		}
	}

	if ( insertedText ) {
		surface.afterRenderLock( function () {
			surface.findAndExecuteSequences();
			surface.maybeSetBreakpoint();
		} );
	} else if ( removedText ) {
		surface.afterRenderLock( function () {
			surface.findAndExecuteSequences( false, true );
			surface.maybeSetBreakpoint();
		} );
	}
	if ( newState.branchNodeChanged && newState.node ) {
		this.updateCursorHolders();
		this.showModelSelection();
	}
	if ( !insertedText ) {
		// Two likely cases here:
		// 1. The cursor moved. If so, fire off a breakpoint to catch any transactions
		//    that were pending, in case a word was being typed.
		// 2. Text was deleted. If so, make a breakpoint. A future enhancement could be
		//    to make this only break after a sequence of deletes. (Maybe combine new
		//    breakpoints with the former breakpoint based on the new transactions?)
		surface.getModel().breakpoint();
	}
};

/**
 * Create a slug out of a DOM element
 *
 * @param {HTMLElement} element Slug element
 */
ve.ce.Surface.prototype.createSlug = function ( element ) {
	var surface = this,
		offset = ve.ce.getOffsetOfSlug( element ),
		documentModel = this.getModel().getDocument();

	this.changeModel( ve.dm.TransactionBuilder.static.newFromInsertion(
		documentModel, offset, [
			{ type: 'paragraph', internal: { generated: 'slug' } },
			{ type: '/paragraph' }
		]
	), new ve.dm.LinearSelection( new ve.Range( offset + 1 ) ) );

	// Animate the slug open
	var $slug = this.getDocument().getDocumentNode().getNodeFromOffset( offset + 1 ).$element;
	$slug.addClass( 've-ce-branchNode-newSlug' );
	// setTimeout: postpone until after animation is complete
	setTimeout( function () {
		$slug.addClass( 've-ce-branchNode-newSlug-open' );
		setTimeout( function () {
			surface.emit( 'position' );
		}, 200 );
	} );

	this.onModelSelect();
};

/**
 * Move cursor if it is between annotation nails
 *
 * @param {number} direction Direction of travel, 1=forwards, -1=backwards, 0=unknown
 * @param {boolean} extend Whether the anchor should stay where it is
 *
 * TODO: Improve name
 */
ve.ce.Surface.prototype.fixupCursorPosition = function ( direction, extend ) {
	// Default to moving start-wards, to mimic typical Chromium behaviour
	direction = direction > 0 ? 1 : -1;

	if ( this.nativeSelection.rangeCount === 0 ) {
		return;
	}
	var node = this.nativeSelection.focusNode;
	var offset = this.nativeSelection.focusOffset;
	if ( node.nodeType !== Node.ELEMENT_NODE ) {
		return;
	}
	var previousNode = node.childNodes[ offset - 1 ];
	var nextNode = node.childNodes[ offset ];

	if (
		!(
			previousNode &&
			previousNode.nodeType === Node.ELEMENT_NODE && (
				previousNode.classList.contains( 've-ce-nail-pre-open' ) ||
				previousNode.classList.contains( 've-ce-nail-pre-close' )
			)
		) && !(
			nextNode &&
			nextNode.nodeType === Node.ELEMENT_NODE && (
				nextNode.classList.contains( 've-ce-nail-post-open' ) ||
				nextNode.classList.contains( 've-ce-nail-post-close' )
			)
		)
	) {
		return;
	}
	// Between nails: cross the one in the specified direction
	var fixedPosition = ve.adjacentDomPosition(
		{ node: node, offset: offset },
		direction,
		{ stop: ve.isHardCursorStep }
	);
	node = fixedPosition.node;
	offset = fixedPosition.offset;
	if ( direction === -1 ) {
		// Support: Firefox
		// Moving startwards: left-bias the fixed position
		// Avoids Firefox bug "cursor disappears at left of img inside link":
		// https://bugzilla.mozilla.org/show_bug.cgi?id=1175495
		fixedPosition = ve.adjacentDomPosition(
			fixedPosition,
			direction,
			{ stop: ve.isHardCursorStep }
		);
		if ( fixedPosition.node.nodeType === Node.TEXT_NODE ) {
			// Have crossed into a text node; go back to its end
			node = fixedPosition.node;
			offset = fixedPosition.node.length;
		}
	}

	this.showSelectionState( new ve.SelectionState( {
		anchorNode: extend ? this.nativeSelection.anchorNode : node,
		anchorOffset: extend ? this.nativeSelection.anchorOffset : offset,
		focusNode: node,
		focusOffset: offset
	} ) );
};

/**
 * Find sequence matches at the current surface offset
 *
 * @param {boolean} [isPaste] Whether this in the context of a paste
 * @param {boolean} [isDelete] Whether this is after content being deleted
 */
ve.ce.Surface.prototype.findMatchingSequences = function ( isPaste, isDelete ) {
	var selection = this.getSelection();

	if ( !selection.isNativeCursor() ) {
		return [];
	}

	return this.getSurface().sequenceRegistry.findMatching(
		this.getModel().getDocument().data,
		selection.getModel().getCoveringRange().end,
		isPaste,
		isDelete
	);
};

/**
 * Find sequence matches at the current surface offset and execute them
 *
 * @param {boolean} [isPaste] Whether this in the context of a paste
 * @param {boolean} [isDelete] Whether this is after content being deleted
 */
ve.ce.Surface.prototype.findAndExecuteSequences = function ( isPaste, isDelete ) {
	this.executeSequences( this.findMatchingSequences( isPaste, isDelete ) );
};

// Deprecated alias
ve.ce.Surface.prototype.checkSequences = ve.ce.Surface.prototype.findAndExecuteSequences;

/**
 * Check if any of the previously delayed sequences no longer match with current offset,
 * and therefore should be executed.
 */
ve.ce.Surface.prototype.findAndExecuteDelayedSequences = function () {
	var sequences = [],
		selection = this.getSelection();

	var matchingSequences;
	if ( this.deactivated || !selection.isNativeCursor() ) {
		matchingSequences = [];
	} else {
		matchingSequences = this.findMatchingSequences();
	}
	var matchingByName = {};
	var i;
	for ( i = 0; i < matchingSequences.length; i++ ) {
		matchingByName[ matchingSequences[ i ].sequence.getName() ] = matchingSequences[ i ];
	}

	for ( i = 0; i < this.delayedSequences.length; i++ ) {
		var matchingSeq = matchingByName[ this.delayedSequences[ i ].sequence.getName() ];
		if (
			!matchingSeq ||
			matchingSeq.range.start !== this.delayedSequences[ i ].range.start
		) {
			// This sequence stopped matching; execute it with the previously saved range
			this.delayedSequences[ i ].wasDelayed = true;
			sequences.push( this.delayedSequences[ i ] );
		}
	}
	// Discard any delayed sequences; they will be checked for again when the user starts typing
	this.delayedSequences = [];

	this.executeSequences( sequences );
};

// Deprecated alias
ve.ce.Surface.prototype.checkDelayedSequences = ve.ce.Surface.prototype.findAndExecuteDelayedSequences;

ve.ce.Surface.prototype.executeSequences = function ( sequences ) {
	var executed = false;

	// sequences.length will likely be 0 or 1 so don't cache
	for ( var i = 0; i < sequences.length; i++ ) {
		if ( sequences[ i ].sequence.delayed && !sequences[ i ].wasDelayed ) {
			// Save the sequence and match range for execution later
			this.delayedSequences.push( sequences[ i ] );
		} else {
			executed = sequences[ i ].sequence.execute( this.surface, sequences[ i ].range ) || executed;
		}
	}
	if ( executed ) {
		this.delayedSequences = [];
		this.showModelSelection();
	}
};

/**
 * See if the just-entered content fits our criteria for setting a history breakpoint
 */
ve.ce.Surface.prototype.maybeSetBreakpoint = function () {
	var selection = this.getSelection();

	if ( !selection.isNativeCursor() ) {
		return;
	}

	// We have just entered text, probably. We want to know whether we just
	// created a word break. We can't check the current offset, since the
	// common case is that being at the end of the string, which is inherently
	// a word break. So, we check whether the previous offset is a word break,
	// which should catch cases where we have hit space or added punctuation.
	// We use getWordRange because it handles the unicode cases, and accounts
	// for single-character words where a space back is a word break because
	// it's the *start* of a word.

	// Note: Text input which isn't using word breaks, for whatever reason,
	// will get breakpoints set by the fallback timer anyway. This is the
	// main reason to not debounce that timer here, as then a reasonable
	// typist with such text would never get a breakpoint set. The compromise
	// position here will occasionally get a breakpoint set in the middle of
	// the first word typed.

	var offset = selection.getModel().getCoveringRange().end - 1;
	var data = this.getModel().getDocument().data;
	if ( data.getWordRange( offset ).end === offset ) {
		this.getModel().breakpoint();
	}
};

/**
 * Handle window resize event.
 *
 * @param {jQuery.Event} e Window resize event
 */
ve.ce.Surface.prototype.onWindowResize = function () {
	this.emit( 'position' );
	if ( OO.ui.isMobile() && !ve.init.platform.constructor.static.isIos() ) {
		// A resize event on mobile is probably a keyboard open/close (or rotate).
		// Either way, ensure the cursor is still visible (T204388).
		// On iOS, window is resized whenever you start scrolling down and the "address bar" is
		// minimized. So don't scroll back up…
		this.getSurface().scrollSelectionIntoView();
	}
};

/* Relocation */

/**
 * Start a relocation action.
 */
ve.ce.Surface.prototype.startRelocation = function () {
	// Cache the selection and selectedNode when the drag starts, to
	// avoid having to recompute them while dragging.
	this.relocatingSelection = this.getModel().getSelection();
	this.relocatingNode = this.getModel().getSelectedNode();
	this.emit( 'relocationStart' );
};

/**
 * Complete a relocation action.
 */
ve.ce.Surface.prototype.endRelocation = function () {
	this.relocatingSelection = null;
	this.relocatingNode = null;
	// Trigger a drag leave event to clear markers
	this.onDocumentDragLeave();
	this.emit( 'relocationEnd' );
};

/**
 * Set the active node
 *
 * @param {ve.ce.Node|null} node Active node
 */
ve.ce.Surface.prototype.setActiveNode = function ( node ) {
	this.activeNode = node;
};

/**
 * Get the active node
 *
 * @return {ve.ce.Node|null} Active node
 */
ve.ce.Surface.prototype.getActiveNode = function () {
	return this.activeNode;
};

/* Utilities */

/**
 * Store a state snapshot at a keydown event, to be used in an after-keydown handler
 *
 * A ve.SelectionState object is stored, but only when the key event is a cursor key.
 * (It would be misleading to save selection properties for key events where the DOM might get
 * modified, because anchorNode/focusNode are live and mutable, and so the offsets may come to
 * point confusingly to different places than they did when the selection was saved).
 *
 * @param {jQuery.Event|null} e Key down event; must be active when this call is made
 */
ve.ce.Surface.prototype.storeKeyDownState = function ( e ) {
	this.keyDownState.event = e;
	this.keyDownState.selectionState = null;

	if ( this.nativeSelection.rangeCount > 0 && e && (
		e.keyCode === OO.ui.Keys.UP ||
		e.keyCode === OO.ui.Keys.DOWN ||
		e.keyCode === OO.ui.Keys.LEFT ||
		e.keyCode === OO.ui.Keys.RIGHT
	) ) {
		this.keyDownState.selectionState = new ve.SelectionState( this.nativeSelection );
	}
};

/**
 * Clear a stored state snapshot from a key down event
 */
ve.ce.Surface.prototype.clearKeyDownState = function () {
	this.keyDownState.event = null;
	this.keyDownState.selectionState = null;
};

/**
 * Move the DM surface cursor
 *
 * @param {number} offset Distance to move (negative = toward document start)
 */
ve.ce.Surface.prototype.moveModelCursor = function ( offset ) {
	var selection = this.model.getSelection();
	if ( selection instanceof ve.dm.LinearSelection ) {
		this.model.setLinearSelection( this.model.getDocument().getRelativeRange(
			selection.getRange(),
			offset,
			'character',
			false
		) );
	}
};

/**
 * Get the directionality at the current focused node
 *
 * @return {string} 'ltr' or 'rtl'
 */
ve.ce.Surface.prototype.getFocusedNodeDirectionality = function () {
	// Use stored directionality if we have one.
	if ( this.cursorDirectionality ) {
		return this.cursorDirectionality;
	}

	// Else fall back on the CSS directionality of the focused node at the DM selection focus,
	// which is less reliable because it does not take plaintext bidi into account.
	// (range.to will actually be at the edge of the focused node, but the
	// CSS directionality will be the same).
	var range = this.model.getSelection().getRange();
	var cursorNode = this.getDocument().getNodeAndOffset( range.to ).node;
	if ( cursorNode.nodeType === Node.TEXT_NODE ) {
		cursorNode = cursorNode.parentNode;
	}
	return $( cursorNode ).css( 'direction' );
};

/**
 * Restore the selection from the model if expands outside the active node
 *
 * This is only useful if the DOM selection and the model selection are out of sync.
 *
 * @return {boolean} Whether the selection was restored
 */
ve.ce.Surface.prototype.restoreActiveNodeSelection = function () {
	var currentRange,
		activeNode = this.getActiveNode(),
		activeRange = activeNode && activeNode.getRange();
	if (
		activeRange &&
		( currentRange = ve.ce.veRangeFromSelection( this.nativeSelection ) ) &&
		( !currentRange.isCollapsed() || activeNode.trapsCursor() ) &&
		!activeRange.containsRange( currentRange )
	) {
		this.showModelSelection();
		return true;
	} else {
		return false;
	}
};

/**
 * Find a ce=false branch node that a native cursor movement from here *might* skip
 *
 * If a node is returned, then it might get skipped by a single native cursor
 * movement in the specified direction from the closest branch node at the
 * current cursor focus. However, if null is returned, then any single such
 * movement is guaranteed *not* to skip an uneditable branch node.
 *
 * Note we cannot predict precisely where/with which cursor key we might step out
 * of the current closest branch node, because it is difficult to predict the
 * behaviour of left/rightarrow (because of bidi visual cursoring) and
 * up/downarrow (because of wrapping).
 *
 * @param {number} direction -1 for before the cursor, +1 for after
 * @return {Node|null} Potentially cursor-adjacent uneditable branch node, or null
 */
ve.ce.Surface.prototype.findAdjacentUneditableBranchNode = function ( direction ) {
	var activeNode = this.getActiveNode(),
		forward = direction > 0;

	var node = $( this.nativeSelection.focusNode ).closest(
		'.ve-ce-branchNode,.ve-ce-leafNode,.ve-ce-surface-paste'
	)[ 0 ];
	if ( !node || node.classList.contains( 've-ce-surface-paste' ) ) {
		return null;
	}

	// Walk in document order till we find a ContentBranchNode (in which case
	// return null) or a FocusableNode/TableNode (in which case return the node)
	// or run out of nodes (in which case return null)
	while ( true ) {
		// Step up until we find a sibling
		while ( !( forward ? node.nextSibling : node.previousSibling ) ) {
			node = node.parentNode;
			if ( node === null ) {
				// Reached the document start/end
				return null;
			}
		}
		// Step back
		node = forward ? node.nextSibling : node.previousSibling;
		// Check and step down
		while ( true ) {
			if (
				$.data( node, 'view' ) instanceof ve.ce.ContentBranchNode ||
				// We shouldn't ever hit a raw text node, because they
				// should all be wrapped in CBNs or focusable nodes, but
				// just in case…
				node.nodeType === Node.TEXT_NODE
			) {
				// This is cursorable (must have content or slugs)
				return null;
			}
			if ( $( node ).is( '.ve-ce-focusableNode,.ve-ce-tableNode' ) ) {
				if ( activeNode ) {
					var viewNode = $( node ).data( 'view' );
					if ( !activeNode.getRange().containsRange( viewNode.getRange() ) ) {
						// Node is outside the active node
						return null;
					}
				}
				return node;
			}
			if ( !node.childNodes || node.childNodes.length === 0 ) {
				break;
			}
			node = forward ? node.firstChild : node.lastChild;
		}
	}
};

/**
 * Insert cursor holders, if they might be required as a cursor target
 */
ve.ce.Surface.prototype.updateCursorHolders = function () {
	this.updateCursorHolderBefore();
	this.updateCursorHolderAfter();
};

/**
 * Insert cursor holder between selection focus and subsequent ce=false node, if required as a cursor target
 */
ve.ce.Surface.prototype.updateCursorHolderBefore = function () {
	var doc = this.getElementDocument(),
		nodeBelow = this.findAdjacentUneditableBranchNode( 1 );
	if (
		!( nodeBelow === null && this.cursorHolderBefore === null ) &&
		!( nodeBelow && this.cursorHolderBefore && nodeBelow.nextSibling === this.cursorHolderBefore )
	) {
		// cursorHolderBefore is not correct for nodeBelow; update it
		this.removeCursorHolderBefore();
		if ( nodeBelow ) {
			this.cursorHolderBefore = doc.importNode( this.constructor.static.cursorHolderTemplate, true );
			this.cursorHolderBefore.classList.add( 've-ce-cursorHolder-before' );
			if ( ve.inputDebug ) {
				this.cursorHolderBefore.classList.add( 've-ce-cursorHolder-debug' );
			}
			// this.cursorHolderBefore is a Node
			// eslint-disable-next-line no-jquery/no-append-html
			$( nodeBelow ).before( this.cursorHolderBefore );
		}
	}
};

/**
 * Insert cursor holder between selection focus and preceding ce=false node, if required as a cursor target
 */
ve.ce.Surface.prototype.updateCursorHolderAfter = function () {
	var doc = this.getElementDocument(),
		nodeAbove = this.findAdjacentUneditableBranchNode( -1 );
	if (
		!( nodeAbove === null && this.cursorHolderAfter === null ) &&
		!( nodeAbove && this.cursorHolderAfter && nodeAbove.nextSibling === this.cursorHolderAfter )
	) {
		// cursorHolderAfter is not correct for nodeAbove; update it
		this.removeCursorHolderAfter();
		if ( nodeAbove ) {
			this.cursorHolderAfter = doc.importNode( this.constructor.static.cursorHolderTemplate, true );
			this.cursorHolderAfter.classList.add( 've-ce-cursorHolder-after' );
			if ( ve.inputDebug ) {
				this.cursorHolderAfter.classList.add( 've-ce-cursorHolder-debug' );
			}
			// this.cursorHolderAfter is a Node
			// eslint-disable-next-line no-jquery/no-append-html
			$( nodeAbove ).after( this.cursorHolderAfter );
		}
	}
};

/**
 * Remove cursor holders, if they exist
 */
ve.ce.Surface.prototype.removeCursorHolders = function () {
	this.removeCursorHolderBefore();
	this.removeCursorHolderAfter();
};

/**
 * Remove cursorHolderBefore, if it exists
 */
ve.ce.Surface.prototype.removeCursorHolderBefore = function () {
	if ( this.cursorHolderBefore ) {
		if ( this.cursorHolderBefore.parentNode ) {
			this.cursorHolderBefore.parentNode.removeChild( this.cursorHolderBefore );
		}
		this.cursorHolderBefore = null;
	}
};

/**
 * Remove cursorHolderAfter, if it exists
 */
ve.ce.Surface.prototype.removeCursorHolderAfter = function () {
	if ( this.cursorHolderAfter ) {
		if ( this.cursorHolderAfter.parentNode ) {
			this.cursorHolderAfter.parentNode.removeChild( this.cursorHolderAfter );
		}
		this.cursorHolderAfter = null;
	}
};

/**
 * Handle insertion of content.
 */
ve.ce.Surface.prototype.handleInsertion = function () {
	var surfaceModel = this.getModel(),
		fragment = surfaceModel.getFragment(),
		selection = this.getSelection();

	if ( selection instanceof ve.ce.TableSelection ) {
		// Collapse table selection to anchor cell
		surfaceModel.setSelection( selection.getModel().collapseToFrom() );
		// Delete the current contents
		ve.ce.keyDownHandlerFactory.lookup( 'tableDelete' ).static.execute( this );
		// Place selection inside the cell
		this.documentView.getBranchNodeFromOffset( selection.getModel().tableRange.start + 1 ).setEditing( true );
		// Selection has changed, update
		selection = this.getSelection();
	} else if ( selection.isFocusedNode() ) {
		// Don't allow a user to delete a non-table focusable node just by typing
		return;
	}

	if ( !( selection instanceof ve.ce.LinearSelection ) ) {
		return;
	}

	var range = selection.getModel().getRange();

	// Handles removing expanded selection before inserting new text
	if (
		this.selectionSplitsNailedAnnotation() ||
		( !range.isCollapsed() && !this.documentView.rangeInsideOneLeafNode( range ) )
	) {
		// Remove the selection to force its re-application from the DM (even if the
		// DM is too granular to detect the selection change)
		surfaceModel.setNullSelection();
		fragment.removeContent().collapseToStart().select();
		this.surfaceObserver.clear();
		this.storeKeyDownState( this.keyDownState.event );
		this.surfaceObserver.stopTimerLoop();
		this.surfaceObserver.pollOnce();
	}
};

/**
 * Place the selection at the next content offset which is selectable.
 *
 * For the purposes of this method, offsets within ve.ce.ActiveNode's
 * are not considered selectable when they are not active.
 *
 * @param {number} startOffset Offset to start from
 * @param {number} direction Search direction, -1 for left and 1 for right
 * @param {number} [endOffset] End offset to stop searching at
 * @return {number} Content offset, or -1 of not found
 */
ve.ce.Surface.prototype.getRelativeSelectableContentOffset = function ( startOffset, direction, endOffset ) {
	var documentView = this.getDocument(),
		linearData = this.getModel().getDocument().data;

	var nextOffset = linearData.getRelativeOffset(
		startOffset,
		direction,
		function ( offset ) {
			// Check we are at a content offset, according to the model
			if ( !linearData.isContentOffset( offset ) ) {
				return false;
			}
			var branchNode = documentView.getBranchNodeFromOffset( offset );
			if ( !branchNode ) {
				// This shouldn't happen in a content offset
				return false;
			}
			var noAutoFocusContainer = branchNode.traverseUpstream( function ( node ) {
				// traverseUpstream stops on a false return
				return node.autoFocus();
			} );
			if ( noAutoFocusContainer ) {
				// Don't try to place the cursor in a node which has a container with autoFocus set to false
				return false;
			}
			return true;
		}
	);

	if (
		endOffset !== undefined && (
			( direction > 0 && nextOffset > endOffset ) ||
			( direction < 0 && nextOffset < endOffset )
		)
	) {
		nextOffset = -1;
	}

	return nextOffset;
};

/**
 * Select the offset returned by #getRelativeSelectableContentOffset
 *
 * @param {number} startOffset
 * @param {number} direction
 * @param {number} [endOffset]
 */
ve.ce.Surface.prototype.selectRelativeSelectableContentOffset = function ( startOffset, direction, endOffset ) {
	var offset = this.getRelativeSelectableContentOffset( startOffset, direction, endOffset );
	if ( offset !== -1 ) {
		// Found an offset
		this.getModel().setLinearSelection( new ve.Range( offset ) );
	} else {
		// Nowhere sensible to put the cursor
		this.getModel().setNullSelection();
	}
};

/**
 * Select the first content offset which is selectable.
 *
 * See #selectRelativeSelectableContentOffset for the definition of selectable.
 */
ve.ce.Surface.prototype.selectFirstSelectableContentOffset = function () {
	this.selectRelativeSelectableContentOffset(
		this.getModel().getAttachedRoot().getOffset(),
		1
	);
};

/**
 * Select the last content offset which is selectable.
 *
 * See #selectRelativeSelectableContentOffset for the definition of selectable.
 */
ve.ce.Surface.prototype.selectLastSelectableContentOffset = function () {
	this.selectRelativeSelectableContentOffset(
		this.getModel().getDocument().getDocumentRange().end,
		-1
	);
};

/**
 * Get an approximate range covering data visible in the viewport
 *
 * It is assumed that vertical offset increases as you progress through the DM.
 * Items with custom positioning may throw off results given by this method, so
 * it should only be treated as an approximation.
 *
 * If the document doesn't contain any content offsets (e.g. it only contains
 * a transclusion), the returned range will cover the entire document. If the
 * single element is particularly large this might be very distinct from the
 * visible content.
 *
 * @param {boolean} [covering] Get a range which fully covers the viewport, otherwise
 *  get a range which is full contained within the viewport.
 * @param {number} [padding=0] Increase computed size of viewport by this amount at the top and bottom
 * @return {ve.Range|null} Range covering data visible in the viewport, null if the surface is not attached
 */
ve.ce.Surface.prototype.getViewportRange = function ( covering, padding ) {
	var surface = this,
		documentModel = this.getModel().getDocument(),
		data = documentModel.data,
		dimensions = this.surface.getViewportDimensions();

	if ( !dimensions ) {
		// Surface is not attached
		return null;
	}

	padding = padding || 0;
	var top = Math.max( 0, dimensions.top - padding );
	var bottom = dimensions.bottom + ( padding * 2 );
	var documentRange = this.attachedRoot === this.getDocument().getDocumentNode() ?
		this.getModel().getDocument().getDocumentRange() :
		this.attachedRoot.getRange();

	function highestIgnoreChildrenNode( childNode ) {
		var ignoreChildrenNode = null;
		childNode.traverseUpstream( function ( node ) {
			if ( node.shouldIgnoreChildren() ) {
				ignoreChildrenNode = node;
			}
		} );
		return ignoreChildrenNode;
	}

	function binarySearch( offset, range, side, isStart ) {
		var start = range.start,
			end = range.end,
			lastLength = Infinity;

		while ( range.getLength() < lastLength ) {
			lastLength = range.getLength();
			var mid = Math.round( ( range.start + range.end ) / 2 );
			var midNode = documentModel.documentNode.getNodeFromOffset( mid );
			var ignoreChildrenNode = highestIgnoreChildrenNode( midNode );

			if ( ignoreChildrenNode ) {
				var nodeRange = ignoreChildrenNode.getOuterRange();
				mid = side === 'top' ? nodeRange.end : nodeRange.start;
			} else {
				mid = data.getNearestContentOffset( mid );
				if ( mid === -1 ) {
					// There is no content offset available in this document.
					// Return early, with a range that'll be covering the entire document.
					return isStart ? start : end;
				}
				// Never search outisde the original range
				mid = Math.max( Math.min( mid, range.end ), range.start );
			}

			var rect = null;
			while ( !rect ) {
				// Try to create a selection of one character for more reliable
				// behaviour when text wraps.
				var contentRange;
				if ( data.isContentOffset( mid + 1 ) ) {
					contentRange = new ve.Range( mid, mid + 1 );
				} else if ( data.isContentOffset( mid - 1 ) ) {
					contentRange = new ve.Range( mid - 1, mid );
				} else {
					contentRange = new ve.Range( mid );
				}
				rect = surface.getSelection( new ve.dm.LinearSelection( contentRange ) ).getSelectionBoundingRect();

				// Node at contentRange is not rendered, find rendered parent
				if ( !rect ) {
					if ( !midNode ) {
						throw new Error( 'Offset has no rendered node container' );
					}
					var midNodeRange = midNode.getOuterRange();
					// Find the nearest content offset outside the invisible node
					mid = side === 'top' ?
						data.getRelativeContentOffset( midNodeRange.end, 1 ) :
						data.getRelativeContentOffset( midNodeRange.start, -1 );

					// Never search outisde the original range
					mid = Math.max( Math.min( mid, range.end ), range.start );

					// Check we didn't end up inside the invisible node again
					if ( midNodeRange.containsRange( new ve.Range( mid ) ) ) {
						return isStart ? start : end;
					}
					// Ensure we check parent in next iteration
					midNode = midNode.parent;
				}
			}
			if ( rect[ side ] >= offset ) {
				end = mid;
				range = new ve.Range( range.start, end );
			} else {
				start = mid;
				range = new ve.Range( start, range.end );
			}
		}
		return side === 'bottom' ? start : end;
	}

	return new ve.Range(
		binarySearch( top, documentRange, covering ? 'bottom' : 'top', true ),
		binarySearch( bottom, documentRange, covering ? 'top' : 'bottom', false )
	);
};

/**
 * Move the selection to the first visible "start content offset" in the viewport
 *
 * Where "start content offset" is the first offset within a content branch node.
 *
 * The following are used as fallbacks when such offsets can't be found:
 * - The first visible content offset (at any position in the CBN)
 * - The first selectable content offset in the doc (if fallbackToFirst is set)
 *
 * @param {boolean} [fallbackToFirst] Whether to select the first content offset if a visible offset can't be found
 */
ve.ce.Surface.prototype.selectFirstVisibleStartContentOffset = function ( fallbackToFirst ) {
	// When scrolled. add about one line height of padding so the browser doesn't try to scroll the line above the cursor into view
	var dimensions = this.surface.getViewportDimensions();
	var offset = dimensions && dimensions.top ? -20 : 0;
	var visibleRange = this.getViewportRange( false, offset );
	if ( visibleRange ) {
		var model = this.getModel();
		var startNodeOffset = -1;
		var contentOffset = this.getRelativeSelectableContentOffset( Math.max( visibleRange.start - 1, 0 ), 1, visibleRange.end );
		if ( contentOffset !== -1 ) {
			var branchNodeRange = model.getDocument().getBranchNodeFromOffset( contentOffset ).getRange();
			if ( contentOffset === branchNodeRange.start ) {
				// We luckily landed and the start of a branch node
				startNodeOffset = contentOffset;
			} else {
				// We are in the middle of a branch node, move to the end, then find the next
				// content offset, which should be the start of the next CBN
				var nextContentOffset = this.getRelativeSelectableContentOffset( branchNodeRange.end, 1, visibleRange.end );
				// If there isn't a content offset after the end of the current node, fallback to the mid-node contentOffset
				startNodeOffset = nextContentOffset !== -1 ? nextContentOffset : contentOffset;
			}
		}
		if ( startNodeOffset !== -1 ) {
			// Found an offset
			model.setLinearSelection( new ve.Range( startNodeOffset ) );
		} else {
			// Nowhere sensible to put the cursor
			model.setNullSelection();
		}
	}

	if ( fallbackToFirst && this.getSelection().getModel().isNull() ) {
		// If a visible range couldn't be determined, or a selection couldn't
		// be made for some reason, fall back to the actual first content offset.
		this.selectFirstSelectableContentOffset();
	}
};

/**
 * Apply a DM selection to the DOM, even if the old DOM selection is different but DM-equivalent
 *
 * @return {boolean} Whether the selection actually changed
 */
ve.ce.Surface.prototype.forceShowModelSelection = function () {
	return this.showModelSelection( true );
};

/**
 * Apply a DM selection to the DOM
 *
 * @param {boolean} [force] Replace the DOM selection if it is different but DM-equivalent
 * @return {boolean} Whether the selection actually changed
 */
ve.ce.Surface.prototype.showModelSelection = function ( force ) {
	if ( this.deactivated ) {
		// setTimeout: Defer until view has updated
		setTimeout( this.updateDeactivatedSelection.bind( this ) );
		return false;
	}

	var selection = this.getSelection();
	var modelRange;
	if ( selection.getModel().isNull() ) {
		if (
			!this.nativeSelection.focusNode ||
			!this.$element[ 0 ].contains( this.nativeSelection.focusNode )
		) {
			// Native selection is already null, or outside the document
			return false;
		}
		modelRange = null;
	} else {
		if ( !selection.isNativeCursor() || this.focusedBlockSlug ) {
			// Model selection is an emulated selection (e.g. table). The view is certain to
			// match it already, because there is no way to change the view selection when
			// an emulated selection is showing.
			return false;
		}
		modelRange = selection.getModel().getRange();
		if ( !force && this.$attachedRootNode.get( 0 ).contains(
			this.nativeSelection.focusNode
		) ) {
			// See whether the model range implied by the DOM selection is already equal to
			// the actual model range. This is necessary because one model selection can
			// correspond to many DOM selections, and we don't want to change a DOM
			// selection that is already valid to an arbitrary different DOM selection.
			var impliedModelRange;
			try {
				impliedModelRange = new ve.Range(
					ve.ce.getOffset(
						this.nativeSelection.anchorNode,
						this.nativeSelection.anchorOffset
					),
					ve.ce.getOffset(
						this.nativeSelection.focusNode,
						this.nativeSelection.focusOffset
					)
				);
			} catch ( e ) {
				// The nativeSelection appears to end up outside the documentNode
				// sometimes, e.g. when deleting in Safari (T306218)
				impliedModelRange = null;
			}
			if ( modelRange.equals( impliedModelRange ) ) {
				// Current native selection fits model range; don't change
				return false;
			}
		}
	}
	var changed = this.showSelectionState( this.getSelectionState( modelRange ) );
	// Support: Chrome
	// Fixes T131674, which is only triggered with Chromium-style ce=false cursoring
	// restrictions (but other cases of non-updated cursor holders can probably occur
	// in other browsers).
	if ( changed ) {
		this.updateCursorHolders();
		return true;
	}
	return false;
};

/**
 * Apply a selection state to the DOM
 *
 * If the browser cannot show a backward selection, fall back to the forward equivalent
 *
 * @param {ve.SelectionState} selection The selection state to show
 * @return {boolean} Whether the selection actually changed
 */
ve.ce.Surface.prototype.showSelectionState = function ( selection ) {
	var extendedBackwards = false,
		sel = this.nativeSelection,
		newSel = selection;

	if ( newSel.equalsSelection( sel ) ) {
		this.updateActiveAnnotations();
		return false;
	}

	if ( !newSel.getNativeRange( this.getElementDocument() ) ) {
		// You can still set a linear selection if the document doesn't have any cursorable positions.
		// That's when you end up here.
		sel.removeAllRanges();
		return true;
	}

	if ( newSel.isBackwards ) {
		if ( ve.supportsSelectionExtend ) {
			// Set the range at the anchor, and extend backwards to the focus
			var range = this.getElementDocument().createRange();
			range.setStart( newSel.anchorNode, newSel.anchorOffset );
			sel.removeAllRanges();
			sel.addRange( range );
			try {
				sel.extend( newSel.focusNode, newSel.focusOffset );
				extendedBackwards = true;
			} catch ( e ) {
				// Support: Firefox
				// Firefox sometimes fails when nodes are different
				// see https://bugzilla.mozilla.org/show_bug.cgi?id=921444
			}
		}
		if ( !extendedBackwards ) {
			// Fallback: Apply the corresponding forward selection
			newSel = newSel.flip();
			if ( newSel.equalsSelection( sel ) ) {
				this.updateActiveAnnotations();
				return false;
			}
		}
	}

	if ( !extendedBackwards ) {
		// Forward selection
		sel.removeAllRanges();
		sel.addRange( newSel.getNativeRange( this.getElementDocument() ) );
	}

	// Setting a range doesn't give focus in all browsers so make sure this happens
	// Also set focus after range to prevent scrolling to top
	var $focusTarget = $( newSel.focusNode ).closest( '[contenteditable=true]' );
	if ( $focusTarget.get( 0 ) === this.getElementDocument().activeElement ) {
		// Already focused, do nothing.
	} else if ( $focusTarget.length && !OO.ui.contains( $focusTarget.get( 0 ), this.getElementDocument().activeElement ) ) {
		// Check $focusTarget is non-empty (T259531)
		// Note: contains *doesn't* include === here. This is desired, as the
		// common case for getting here is when pressing backspace when the
		// cursor is in the middle of a block of text (thus both are a <div>),
		// and we don't want to scroll away from the caret.
		var scrollTop = this.surface.$scrollContainer.scrollTop();
		$focusTarget.trigger( 'focus' );
		// Support: Safari
		// Safari tries to scroll the CE surface into view when focusing,
		// causing unwanted page jumps (T258847)
		this.surface.$scrollContainer.scrollTop( scrollTop );
	} else {
		// Scroll the node into view
		ve.scrollIntoView(
			$( newSel.focusNode ).closest( '*' ).get( 0 )
		);
	}
	this.updateActiveAnnotations();
	return true;
};

/**
 * Update the activeAnnotations property and apply CSS classes accordingly
 *
 * An active annotation is one containing the DOM cursor, which may not be well
 * defined at annotation boundaries, except for links which use nails.
 *
 * Also the order of .activeAnnotations may not be well defined.
 *
 * @param {boolean|Node} [fromModelOrNode] If `true`, gather annotations from the model,
 *  instead of the cusor focus point. If a Node is passed, gather annotations from that node.
 */
ve.ce.Surface.prototype.updateActiveAnnotations = function ( fromModelOrNode ) {
	var activeAnnotations,
		changed = false,
		surface = this,
		canBeActive = function ( view ) {
			return view.canBeActive();
		};

	if ( fromModelOrNode === true ) {
		activeAnnotations = this.annotationsAtModelSelection( canBeActive );
	} else if ( fromModelOrNode instanceof Node ) {
		activeAnnotations = this.annotationsAtNode( fromModelOrNode, canBeActive );
	} else {
		activeAnnotations = this.annotationsAtFocus( canBeActive );
	}

	// Iterate over previously active annotations
	this.activeAnnotations.forEach( function ( annotation ) {
		// If not in the new list, turn off
		if ( activeAnnotations.indexOf( annotation ) === -1 ) {
			annotation.$element.removeClass( 've-ce-annotation-active' );
			changed = true;
		}
	} );

	// Iterate over newly active annotations
	activeAnnotations.forEach( function ( annotation ) {
		// If not in the old list, turn on
		if ( surface.activeAnnotations.indexOf( annotation ) === -1 ) {
			annotation.$element.addClass( 've-ce-annotation-active' );
			changed = true;
		}
	} );

	if ( changed ) {
		this.activeAnnotations = activeAnnotations;
		this.model.emit( 'contextChange' );
	}
};

/**
 * Update the selection to contain the contents of a node
 *
 * @param {HTMLElement} node
 * @param {string} [collapse] Collaspse to 'start' or 'end'
 * @return {boolean} Whether the selection changed
 */
ve.ce.Surface.prototype.selectNodeContents = function ( node, collapse ) {
	if ( !node ) {
		return false;
	}
	var anchor = ve.ce.nextCursorOffset( node.childNodes[ 0 ] );
	var focus = ve.ce.previousCursorOffset( node.childNodes[ node.childNodes.length - 1 ] );
	if ( collapse === 'start' ) {
		focus = anchor;
	} else if ( collapse === 'end' ) {
		anchor = focus;
	}
	return this.showSelectionState( new ve.SelectionState( {
		anchorNode: anchor.node,
		anchorOffset: anchor.offset, // Past the nail
		focusNode: focus.node,
		focusOffset: focus.offset, // Before the nail
		isCollapsed: false
	} ) );
};

/**
 * Select the inner contents of the closest annotation
 *
 * @param {Function} [filter] Function to filter view nodes by.
 */
ve.ce.Surface.prototype.selectAnnotation = function ( filter ) {
	var annotations = this.annotationsAtModelSelection( filter );

	if ( annotations.length ) {
		this.selectNodeContents( annotations[ 0 ].$element[ 0 ] );
	}
};

/**
 * Get the annotation views at the current model selection
 *
 * TODO: This doesn't work for annotations that span fewer
 * than one character, as getNodeAndOffset will never return
 * an offset inside that annotation.
 *
 * @param {Function} [filter] Function to filter view nodes by.
 * @param {number} [offset] Model offset. Defaults to start of current selection.
 * @return {ve.ce.Annotation[]} Annotation views
 */
ve.ce.Surface.prototype.annotationsAtModelSelection = function ( filter, offset ) {
	var annotations = [],
		documentRange = this.getModel().getDocument().getDocumentRange();

	if ( offset === undefined ) {
		offset = this.getModel().getSelection().getCoveringRange().start;
	}

	// getNodeAndOffset can throw when offset is out of bounds (T262354)
	// and other undiagnosed situations (T136780, T262487, T259154, T262303)

	// TODO: For annotation boundaries we have to search one place left and right
	// to find the text inside the annotation. This will give too many results for
	// adjancent annotations, and will fail for one character annotations. (T221967)
	var nodeAndOffset;
	if ( offset > documentRange.start ) {
		try {
			nodeAndOffset = this.getDocument().getNodeAndOffset( offset - 1 );
		} catch ( e ) {
			nodeAndOffset = null;
		}
		annotations = nodeAndOffset ? this.annotationsAtNode( nodeAndOffset.node, filter ) : [];
	}

	if ( offset < documentRange.end ) {
		try {
			nodeAndOffset = this.getDocument().getNodeAndOffset( offset + 1 );
		} catch ( e ) {
			nodeAndOffset = null;
		}
		annotations = OO.unique( annotations.concat( nodeAndOffset ? this.annotationsAtNode( nodeAndOffset.node, filter ) : [] ) );
	}

	return annotations;
};

/**
 * Get the annotation views containing the cursor focus
 *
 * @param {Function} [filter] Function to filter view nodes by.
 * @return {ve.ce.Annotation[]} Annotation views
 */
ve.ce.Surface.prototype.annotationsAtFocus = function ( filter ) {
	return this.annotationsAtNode( this.nativeSelection.focusNode, filter );
};

/**
 * Get the annotation views containing the cursor focus
 *
 * Only returns annotations which can be active.
 *
 * @param {Node} node Node at which to search for annotations
 * @param {Function} [filter] Function to filter view nodes by. Takes one argument which
 *  is the view node and returns a boolean.
 * @return {ve.ce.Annotation[]} Annotation views
 */
ve.ce.Surface.prototype.annotationsAtNode = function ( node, filter ) {
	var annotations = [];
	$( node ).parents( '.ve-ce-annotation' ).addBack( '.ve-ce-annotation' ).each( function () {
		var view = $( this ).data( 'view' );
		if ( view && ( !filter || filter( view ) ) ) {
			annotations.push( view );
		}
	} );
	return annotations;
};

/**
 * Get a SelectionState corresponding to a ve.Range.
 *
 * If either endpoint of the ve.Range is not a cursor offset, adjust the SelectionState
 * endpoints to be at cursor offsets. For a collapsed selection, the adjustment preserves
 * collapsedness; for a non-collapsed selection, the adjustment is in the direction that
 * grows the selection (thereby avoiding collapsing or reversing the selection).
 *
 * @param {ve.Range|null} range Range to get selection for
 * @return {ve.SelectionState} The selection
 * @return {Node|null} return.anchorNode The anchor node
 * @return {number} return.anchorOffset The anchor offset
 * @return {Node|null} return.focusNode The focus node
 * @return {number} return.focusOffset The focus offset
 * @return {boolean} return.isCollapsed True if the focus and anchor are in the same place
 * @return {boolean} return.isBackwards True if the focus is before the anchor
 */
ve.ce.Surface.prototype.getSelectionState = function ( range ) {
	var dmDoc = this.getModel().getDocument();

	if ( !range ) {
		return ve.SelectionState.static.newNullSelection();
	}

	// Anchor/focus at the nearest correct position in the direction that
	// grows the selection.
	var from = dmDoc.getNearestCursorOffset( range.from, range.isBackwards() ? 1 : -1 );
	if ( from === -1 ) {
		return ve.SelectionState.static.newNullSelection();
	}
	var anchor;
	try {
		anchor = this.documentView.getNodeAndOffset( from );
	} catch ( e ) {
		return ve.SelectionState.static.newNullSelection();
	}
	var focus;
	if ( range.isCollapsed() ) {
		focus = anchor;
	} else {
		var to = dmDoc.getNearestCursorOffset( range.to, range.isBackwards() ? -1 : 1 );
		if ( to === -1 ) {
			return ve.SelectionState.static.newNullSelection();
		}
		try {
			focus = this.documentView.getNodeAndOffset( to );
		} catch ( e ) {
			return ve.SelectionState.static.newNullSelection();
		}
	}
	return new ve.SelectionState( {
		anchorNode: anchor.node,
		anchorOffset: anchor.offset,
		focusNode: focus.node,
		focusOffset: focus.offset,
		isBackwards: range.isBackwards()
	} );
};

/**
 * Get a native range object for a specified ve.Range
 *
 * Native ranges are only used by linear selections. They don't show whether the selection
 * is backwards, so they should be used for measurement only.
 *
 * @param {ve.Range} [range] Optional range to get the native range for, defaults to current selection's range
 * @return {Range|null} Native range object, or null if there is no suitable selection
 */
ve.ce.Surface.prototype.getNativeRange = function ( range ) {
	var selectionState;

	if ( !range ) {
		// If no range specified, or range is equivalent to current native selection,
		// then use the current native selection
		selectionState = new ve.SelectionState( this.nativeSelection );
	} else {
		selectionState = this.getSelectionState( range );
	}
	return selectionState.getNativeRange( this.getElementDocument() );
};

/**
 * Append passed highlights to highlight container.
 *
 * @param {jQuery} $highlights Highlights to append
 * @param {boolean} focused Highlights are currently focused
 */
ve.ce.Surface.prototype.appendHighlights = function ( $highlights, focused ) {
	// Only one item can be blurred-highlighted at a time, so remove the others.
	// Remove by detaching so they don't lose their event handlers, in case they
	// are attached again.
	this.$highlightsBlurred.children().detach();
	if ( focused ) {
		this.$highlightsFocused.append( $highlights );
	} else {
		this.$highlightsBlurred.append( $highlights );
	}
};

/* Getters */

/**
 * Get the top-level surface.
 *
 * @return {ve.ui.Surface}
 */
ve.ce.Surface.prototype.getSurface = function () {
	return this.surface;
};

/**
 * Get the surface model.
 *
 * @return {ve.dm.Surface} Surface model
 */
ve.ce.Surface.prototype.getModel = function () {
	return this.model;
};

/**
 * Get the document view.
 *
 * @return {ve.ce.Document} Document view
 */
ve.ce.Surface.prototype.getDocument = function () {
	return this.documentView;
};

/**
 * Check whether there are any render locks
 *
 * @return {boolean} Render is locked
 */
ve.ce.Surface.prototype.isRenderingLocked = function () {
	return this.renderLocks > 0 && !this.readOnly;
};

/**
 * Add a single render lock (to disable rendering)
 */
ve.ce.Surface.prototype.incRenderLock = function () {
	this.renderLocks++;
};

/**
 * Remove a single render lock
 */
ve.ce.Surface.prototype.decRenderLock = function () {
	this.renderLocks--;
};

/**
 * Escape the current render lock
 *
 * @param {Function} callback Function to run after render lock
 */
ve.ce.Surface.prototype.afterRenderLock = function ( callback ) {
	// TODO: implement an actual tracking system that makes sure renderlock is
	// 0 when this is done.
	// setTimeout: postpone until there is definitely no render lock
	setTimeout( callback );
};

/**
 * Change the model only, not the CE surface
 *
 * This avoids event storms when the CE surface is already correct
 *
 * @param {ve.dm.Transaction|ve.dm.Transaction[]|null} transactions One or more transactions to
 * process, or null to process none
 * @param {ve.dm.Selection} selection New selection
 * @throws {Error} If calls to this method are nested
 */
ve.ce.Surface.prototype.changeModel = function ( transactions, selection ) {
	if ( this.newModelSelection !== null ) {
		throw new Error( 'Nested change of newModelSelection' );
	}
	this.newModelSelection = selection;
	try {
		this.model.change( transactions, selection );
	} finally {
		this.newModelSelection = null;
	}
};

/**
 * Inform the surface that one of its ContentBranchNodes' rendering has changed.
 *
 * @see ve.ce.ContentBranchNode#renderContents
 */
ve.ce.Surface.prototype.setContentBranchNodeChanged = function () {
	this.contentBranchNodeChanged = true;
	this.clearKeyDownState();
};

/**
 * Set the node that has the current unicorn.
 *
 * If another node currently has a unicorn, it will be rerendered, which will
 * cause it to release its unicorn.
 *
 * @param {ve.ce.ContentBranchNode|null} node The node claiming the unicorn, null to release (by rerendering) without claiming
 */
ve.ce.Surface.prototype.setUnicorning = function ( node ) {
	if ( this.setUnicorningRecursionGuard ) {
		throw new Error( 'setUnicorning recursing' );
	}
	if ( this.unicorningNode && this.unicorningNode !== node ) {
		this.setUnicorningRecursionGuard = true;
		try {
			this.unicorningNode.renderContents();
		} finally {
			this.setUnicorningRecursionGuard = false;
		}
	}
	this.unicorningNode = node;
};

/**
 * Release the current unicorn held by a given node, without rerendering
 *
 * If the node doesn't hold the current unicorn, nothing happens.
 *
 * @param {ve.ce.ContentBranchNode} node The node releasing the unicorn
 */
ve.ce.Surface.prototype.setNotUnicorning = function ( node ) {
	if ( this.unicorningNode === node ) {
		this.unicorningNode = null;
	}
};

/**
 * Ensure that no node has a unicorn.
 *
 * If the given node currently has the unicorn, it will be released and
 * no rerender will happen. If another node has the unicorn, that node
 * will be rerendered to get rid of the unicorn.
 *
 * @param {ve.ce.ContentBranchNode} node The node releasing the unicorn
 */
ve.ce.Surface.prototype.setNotUnicorningAll = function ( node ) {
	if ( this.unicorningNode === node ) {
		// Don't call back node.renderContents()
		this.unicorningNode = null;
	}
	this.setUnicorning( null );
};

/**
 * Get list of selected nodes and annotations.
 *
 * Exclude active annotations unless the CE focus is inside a link
 *
 * @param {boolean} [all] Include nodes and annotations which only cover some of the fragment
 * @return {ve.dm.Model[]} Selected models
 */
ve.ce.Surface.prototype.getSelectedModels = function () {
	if ( !( this.model.selection instanceof ve.dm.LinearSelection ) ) {
		return [];
	}
	var models = this.model.getFragment().getSelectedModels();

	if ( this.model.selection.isCollapsed() ) {
		var fragmentAfter = this.model.getFragment( new ve.dm.LinearSelection(
			new ve.Range(
				this.model.selection.range.start,
				this.model.selection.range.start + 1
			)
		) );
		models = OO.unique( [].concat(
			models,
			fragmentAfter.getSelectedModels()
		) );
	}

	var activeModels = this.activeAnnotations.map( function ( view ) {
		return view.getModel();
	} );

	if ( this.model.sourceMode ) {
		return models;
	} else {
		return models.filter( function ( annModel ) {
			// If the model is an annotation that can be active, only show it if it *is* active
			if ( annModel instanceof ve.dm.Annotation && ve.ce.annotationFactory.canAnnotationBeActive( annModel.getType() ) ) {
				return activeModels.indexOf( annModel ) !== -1;
			}
			return true;
		} );
	}
};

/**
 * Tests whether the selection covers part but not all of a nailed annotation
 *
 * @return {boolean} True if a nailed annotation is split either at the focus or at the anchor (or both)
 */
ve.ce.Surface.prototype.selectionSplitsNailedAnnotation = function () {
	return ve.ce.nailedAnnotationAt( this.nativeSelection.anchorNode ) !==
		ve.ce.nailedAnnotationAt( this.nativeSelection.focusNode );
};

/**
 * Called when the synchronizer receives a remote author selection or name change
 *
 * @param {number} authorId The author ID
 */
ve.ce.Surface.prototype.onSynchronizerAuthorUpdate = function ( authorId ) {
	this.paintAuthor( authorId );
};

/**
 * Called when the synchronizer receives a remote author disconnect
 *
 * @param {number} authorId The author ID
 */
ve.ce.Surface.prototype.onSynchronizerAuthorDisconnect = function ( authorId ) {
	this.drawSelections( 'otherUserSelection-' + authorId, [] );
	this.drawSelections( 'otherUserCursor-' + authorId, [] );
};

/**
 * Called when the synchronizer reconnects and their is a server doc ID mismatch
 */
ve.ce.Surface.prototype.onSynchronizerWrongDoc = function () {
	OO.ui.alert(
		ve.msg( 'visualeditor-rebase-missing-document-error' ),
		{ title: ve.msg( 'visualeditor-rebase-missing-document-title' ) }
	);
};

/**
 * Handle pause events from the synchronizer
 *
 * Drops the opacity of the surface to indicate that no updates are
 * being received from other users.
 */
ve.ce.Surface.prototype.onSynchronizerPause = function () {
	this.$element.toggleClass( 've-ce-surface-paused', !!this.model.synchronizer.paused );
};

/**
 * Paint a remote author's current selection, as stored in the synchronizer
 *
 * @param {number} authorId The author ID
 */
ve.ce.Surface.prototype.paintAuthor = function ( authorId ) {
	var surface = this,
		synchronizer = this.model.synchronizer,
		authorData = synchronizer.getAuthorData( authorId ),
		selection = synchronizer.authorSelections[ authorId ];

	if ( !authorData || !selection || authorId === synchronizer.getAuthorId() ) {
		return;
	}

	var color = '#' + authorData.color;

	if ( !this.userSelectionDeactivate[ authorId ] ) {
		this.userSelectionDeactivate[ authorId ] = ve.debounce( function () {
			// TODO: Transition away the user label when inactive, maybe dim selection
			if ( surface.drawnSelections[ 'otherUserSelection-' + authorId ] ) {
				surface.drawnSelections[ 'otherUserSelection-' + authorId ].$selections.addClass( 've-ce-surface-selections-otherUserSelection-inactive' );
			}
			if ( surface.drawnSelections[ 'otherUserCursor-' + authorId ] ) {
				surface.drawnSelections[ 'otherUserCursor-' + authorId ].$selections.addClass( 've-ce-surface-selections-otherUserCursor-inactive' );
			}
		}, 5000 );
	}
	this.userSelectionDeactivate[ authorId ]();

	if ( !selection || selection.isNull() ) {
		this.drawSelections( 'otherUserSelection-' + authorId, [] );
		this.drawSelections( 'otherUserCursor-' + authorId, [] );
		return;
	}

	this.drawSelections(
		'otherUserSelection-' + authorId,
		[ ve.ce.Selection.static.newFromModel( selection, this ) ],
		{
			wrapperClass: 've-ce-surface-selections-otherUserSelection',
			color: color
		}
	);

	var cursorSelection = selection instanceof ve.dm.LinearSelection && this.getFocusedNode( selection.getRange() ) ?
		selection : selection.collapseToTo();

	this.drawSelections(
		'otherUserCursor-' + authorId,
		[ ve.ce.Selection.static.newFromModel( cursorSelection, this ) ],
		{
			wrapperClass: 've-ce-surface-selections-otherUserCursor',
			color: color,
			// Label is attached to cursor for 100% opacity, but it should probably be attached
			// to the selection, so the cursor can be selectively rendered just for LinearSelection's.
			label: authorData.name
		}
	);
};

/**
 * Respond to a position event on this surface
 */
ve.ce.Surface.prototype.onPosition = function () {
	var surface = this;

	this.redrawSelections();

	if ( this.model.synchronizer ) {
		// Defer to allow surface synchronizer to adjust for transactions
		setTimeout( function () {
			var authorSelections = surface.model.synchronizer.authorSelections;
			for ( var authorId in authorSelections ) {
				surface.onSynchronizerAuthorUpdate( +authorId );
			}
		} );
	}
};
