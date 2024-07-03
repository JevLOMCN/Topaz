/*!
 * VisualEditor UserInterface FindAndReplaceDialog class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Find and replace dialog.
 *
 * @class
 * @extends ve.ui.ToolbarDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.FindAndReplaceDialog = function VeUiFindAndReplaceDialog( config ) {
	// Parent constructor
	ve.ui.FindAndReplaceDialog.super.call( this, config );

	// Pre-initialization
	this.$element.addClass( 've-ui-findAndReplaceDialog' );
};

/* Inheritance */

OO.inheritClass( ve.ui.FindAndReplaceDialog, ve.ui.ToolbarDialog );

ve.ui.FindAndReplaceDialog.static.name = 'findAndReplace';

ve.ui.FindAndReplaceDialog.static.group = 'utility';

// Invisible title for accessibility
ve.ui.FindAndReplaceDialog.static.title =
	OO.ui.deferMsg( 'visualeditor-find-and-replace-title' );

ve.ui.FindAndReplaceDialog.static.handlesSource = true;

/**
 * Maximum number of results to render
 *
 * @property {number}
 */
ve.ui.FindAndReplaceDialog.static.maxRenderedResults = 100;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.FindAndReplaceDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.FindAndReplaceDialog.super.prototype.initialize.call( this );

	// Properties
	this.surface = null;
	this.invalidRegex = false;
	this.initialFragment = null;
	this.startOffset = 0;
	this.fragments = [];
	this.results = 0;
	this.isClipped = false;
	this.replacing = false;
	this.focusedIndex = 0;
	this.query = null;
	this.findText = new OO.ui.TextInputWidget( {
		placeholder: ve.msg( 'visualeditor-find-and-replace-find-text' ),
		value: ve.userConfig( 'visualeditor-findAndReplace-findText' ),
		validate: ( function ( dialog ) {
			return function () {
				return !dialog.invalidRegex;
			};
		}( this ) ),
		tabIndex: 1
	} );
	this.findText.$input.attr( 'aria-label', ve.msg( 'visualeditor-find-and-replace-find-text' ) );
	this.updateUserConfigDebounced = ve.debounce( this.updateUserConfig.bind( this ), 500 );

	this.previousButton = new OO.ui.ButtonWidget( {
		icon: 'previous',
		title: ve.msg( 'visualeditor-find-and-replace-previous-button' ) + ' ' +
			ve.ui.triggerRegistry.getMessages( 'findPrevious' ).join( ', ' ),
		tabIndex: 2
	} );
	this.nextButton = new OO.ui.ButtonWidget( {
		icon: 'next',
		title: ve.msg( 'visualeditor-find-and-replace-next-button' ) + ' ' +
			ve.ui.triggerRegistry.getMessages( 'findNext' ).join( ', ' ),
		tabIndex: 2
	} );
	this.matchCaseToggle = new OO.ui.ToggleButtonWidget( {
		icon: 'searchCaseSensitive',
		title: ve.msg( 'visualeditor-find-and-replace-match-case' ),
		value: ve.userConfig( 'visualeditor-findAndReplace-matchCase' ),
		tabIndex: 2
	} );
	this.regexToggle = new OO.ui.ToggleButtonWidget( {
		icon: 'searchRegularExpression',
		title: ve.msg( 'visualeditor-find-and-replace-regular-expression' ),
		value: ve.userConfig( 'visualeditor-findAndReplace-regex' ),
		tabIndex: 2
	} );
	this.wordToggle = new OO.ui.ToggleButtonWidget( {
		icon: 'quotes',
		title: ve.msg( 'visualeditor-find-and-replace-word' ),
		value: ve.userConfig( 'visualeditor-findAndReplace-word' ),
		tabIndex: 2
	} );
	this.diacriticToggle = new OO.ui.ToggleButtonWidget( {
		icon: 'searchDiacritics',
		title: ve.msg( 'visualeditor-find-and-replace-diacritic' ),
		value: ve.userConfig( 'visualeditor-findAndReplace-diacritic' ),
		tabIndex: 2
	} );

	this.replaceText = new OO.ui.TextInputWidget( {
		placeholder: ve.msg( 'visualeditor-find-and-replace-replace-text' ),
		value: ve.userConfig( 'visualeditor-findAndReplace-replaceText' ),
		tabIndex: 1
	} );
	this.replaceText.$input.attr( 'aria-label', ve.msg( 'visualeditor-find-and-replace-replace-text' ) );

	this.replaceButton = new OO.ui.ButtonWidget( {
		label: ve.msg( 'visualeditor-find-and-replace-replace-button' ),
		tabIndex: 1
	} );
	this.replaceAllButton = new OO.ui.ButtonWidget( {
		label: ve.msg( 'visualeditor-find-and-replace-replace-all-button' ),
		tabIndex: 1
	} );
	var doneButton = new OO.ui.ButtonWidget( {
		classes: [ 've-ui-findAndReplaceDialog-cell' ],
		label: ve.msg( 'visualeditor-find-and-replace-done' ),
		tabIndex: 1
	} );

	var optionsGroup = new OO.ui.ButtonGroupWidget( {
		classes: [ 've-ui-findAndReplaceDialog-cell' ],
		items: [
			this.matchCaseToggle,
			this.regexToggle,
			this.wordToggle,
			this.diacriticToggle
		]
	} );
	var navigateGroup = new OO.ui.ButtonGroupWidget( {
		classes: [ 've-ui-findAndReplaceDialog-cell' ],
		items: [
			this.previousButton,
			this.nextButton
		]
	} );
	var replaceGroup = new OO.ui.ButtonGroupWidget( {
		classes: [ 've-ui-findAndReplaceDialog-cell' ],
		items: [
			this.replaceButton,
			this.replaceAllButton
		]
	} );
	var $findRow = $( '<div>' ).addClass( 've-ui-findAndReplaceDialog-row' );
	var $replaceRow = $( '<div>' ).addClass( 've-ui-findAndReplaceDialog-row' );

	// Events
	this.onWindowScrollThrottled = ve.throttle( this.onWindowScroll.bind( this ), 250 );
	this.updateFragmentsThrottled = ve.throttle( this.updateFragments.bind( this ), 250 );
	this.renderFragmentsThrottled = ve.throttle( this.renderFragments.bind( this ), 250 );
	this.findText.connect( this, {
		change: 'onFindChange',
		enter: 'onFindReplaceTextEnter'
	} );
	this.replaceText.connect( this, {
		change: 'onReplaceChange',
		enter: 'onFindReplaceTextEnter'
	} );
	this.matchCaseToggle.connect( this, { change: 'onFindChange' } );
	this.regexToggle.connect( this, { change: 'onFindChange' } );
	this.wordToggle.connect( this, { change: 'onFindChange' } );
	this.diacriticToggle.connect( this, { change: 'onFindChange' } );
	this.nextButton.connect( this, { click: 'findNext' } );
	this.previousButton.connect( this, { click: 'findPrevious' } );
	this.replaceButton.connect( this, { click: 'onReplaceButtonClick' } );
	this.replaceAllButton.connect( this, { click: 'onReplaceAllButtonClick' } );
	doneButton.connect( this, { click: 'close' } );

	this.tabIndexScope = new ve.ui.TabIndexScope( {
		root: this.$element
	} );

	// Initialization
	this.$content.addClass( 've-ui-findAndReplaceDialog-content' );
	this.$body
		.append(
			$findRow.append(
				$( '<div>' ).addClass( 've-ui-findAndReplaceDialog-cell ve-ui-findAndReplaceDialog-cell-input' ).append(
					this.findText.$element
				),
				navigateGroup.$element,
				optionsGroup.$element
			),
			$replaceRow.append(
				$( '<div>' ).addClass( 've-ui-findAndReplaceDialog-cell ve-ui-findAndReplaceDialog-cell-input' ).append(
					this.replaceText.$element
				),
				replaceGroup.$element,
				doneButton.$element
			)
		);
};

/**
 * @inheritdoc
 */
ve.ui.FindAndReplaceDialog.prototype.getSetupProcess = function ( data ) {
	data = data || {};
	return ve.ui.FindAndReplaceDialog.super.prototype.getSetupProcess.call( this, data )
		.first( function () {
			this.surface = data.surface;

			// Events
			this.surface.getModel().connect( this, { documentUpdate: 'onSurfaceModelDocumentUpdate' } );
			this.surface.getView().connect( this, { position: 'onSurfaceViewPosition' } );
			this.surface.$scrollListener[ 0 ].addEventListener( 'scroll', this.onWindowScrollThrottled, { passive: true } );

			this.updateFragments();
			this.renderFragments();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.FindAndReplaceDialog.prototype.getReadyProcess = function ( data ) {
	return ve.ui.FindAndReplaceDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			this.focus();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.FindAndReplaceDialog.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.FindAndReplaceDialog.super.prototype.getTeardownProcess.call( this, data )
		.next( function () {
			var surfaceView = this.surface.getView(),
				surfaceModel = this.surface.getModel();

			// Events
			this.surface.getModel().disconnect( this );
			surfaceView.disconnect( this );
			this.surface.$scrollListener[ 0 ].removeEventListener( 'scroll', this.onWindowScrollThrottled );

			var selection;
			if ( this.fragments.length ) {
				// Either the active search result…
				selection = this.fragments[ this.focusedIndex ].getSelection();
			} else {
				// … or the initial selection
				selection = this.initialFragment.getSelection();
			}
			surfaceModel.setSelection( selection );

			// Generates ve-ce-surface-selections-findResults CSS class
			surfaceView.drawSelections( 'findResults', [] );
			this.fragments = [];
			this.surface = null;
			this.focusedIndex = 0;
		}, this );
};

/**
 * Handle documentUpdate events from the surface model
 */
ve.ui.FindAndReplaceDialog.prototype.onSurfaceModelDocumentUpdate = function () {
	if ( this.replacing ) {
		return;
	}
	this.updateFragmentsThrottled();
};

/**
 * Handle position events from the surface view
 */
ve.ui.FindAndReplaceDialog.prototype.onSurfaceViewPosition = function () {
	if ( this.replacing ) {
		return;
	}
	this.renderFragmentsThrottled();
};

/**
 * Handle window scroll events
 */
ve.ui.FindAndReplaceDialog.prototype.onWindowScroll = function () {
	if ( this.isClipped ) {
		// If viewport clipping is being used, reposition results based on the current viewport
		this.renderFragments();
	}
};

/**
 * Handle change events to the find inputs (text or match case)
 */
ve.ui.FindAndReplaceDialog.prototype.onFindChange = function () {
	this.updateFragments();
	this.renderFragments();
	this.highlightFocused( true );
	this.diacriticToggle.setDisabled( this.regexToggle.getValue() );
	this.updateUserConfigDebounced();
};

/**
 * Handle change events to the replace input
 */
ve.ui.FindAndReplaceDialog.prototype.onReplaceChange = function () {
	this.updateUserConfigDebounced();
};

/**
 * Remember inputs in the dialog in user config.
 */
ve.ui.FindAndReplaceDialog.prototype.updateUserConfig = function () {
	ve.userConfig( {
		'visualeditor-findAndReplace-findText': this.findText.getValue(),
		'visualeditor-findAndReplace-matchCase': this.matchCaseToggle.getValue(),
		'visualeditor-findAndReplace-regex': this.regexToggle.getValue(),
		'visualeditor-findAndReplace-word': this.wordToggle.getValue(),
		'visualeditor-findAndReplace-diacritic': this.diacriticToggle.getValue(),
		'visualeditor-findAndReplace-replaceText': this.replaceText.getValue()
	} );
};

/**
 * Handle enter events on the find text and replace text inputs
 *
 * @param {jQuery.Event} e
 */
ve.ui.FindAndReplaceDialog.prototype.onFindReplaceTextEnter = function ( e ) {
	if ( !this.results ) {
		return;
	}
	if ( e.shiftKey ) {
		this.findPrevious();
	} else {
		this.findNext();
	}
};

/**
 * Update search result fragments
 */
ve.ui.FindAndReplaceDialog.prototype.updateFragments = function () {
	var surfaceModel = this.surface.getModel(),
		documentModel = surfaceModel.getDocument(),
		isReadOnly = surfaceModel.isReadOnly(),
		ranges = [],
		matchCase = this.matchCaseToggle.getValue(),
		isRegex = this.regexToggle.getValue(),
		wholeWord = this.wordToggle.getValue(),
		diacriticInsensitive = this.diacriticToggle.getValue(),
		find = this.findText.getValue();

	this.invalidRegex = false;

	if ( isRegex && find ) {
		try {
			// eslint-disable-next-line security/detect-non-literal-regexp
			this.query = new RegExp( find, matchCase ? 'g' : 'gi' );
		} catch ( e ) {
			this.invalidRegex = true;
			this.query = '';
		}
	} else {
		this.query = find;
	}
	this.findText.setValidityFlag();

	this.fragments = [];
	var startIndex;
	if ( this.query ) {
		ranges = documentModel.findText( this.query, {
			caseSensitiveString: matchCase,
			diacriticInsensitiveString: diacriticInsensitive,
			noOverlaps: true,
			wholeWord: wholeWord
		} );
		for ( var i = 0, l = ranges.length; i < l; i++ ) {
			this.fragments.push( surfaceModel.getLinearFragment( ranges[ i ], true, true ) );
			if ( startIndex === undefined && ranges[ i ].start >= this.startOffset ) {
				startIndex = this.fragments.length - 1;
			}
		}
	}
	this.results = this.fragments.length;
	this.focusedIndex = startIndex || 0;
	this.nextButton.setDisabled( !this.results );
	this.previousButton.setDisabled( !this.results );
	this.replaceText.setDisabled( isReadOnly );
	this.replaceButton.setDisabled( !this.results || isReadOnly );
	this.replaceAllButton.setDisabled( !this.results || isReadOnly );
};

/**
 * Position results markers
 */
ve.ui.FindAndReplaceDialog.prototype.renderFragments = function () {
	// The methods is called after a delay (renderFragmentsThrottled/onWindowScrollThrottled)
	// Check the dialog hasn't been torn down, or that the surface view hasn't been destroyed
	if ( !this.surface || !this.surface.getView().attachedRoot.isLive() ) {
		return;
	}

	var start = 0;
	var end = this.results;

	// When there are a large number of results, calculate the viewport range for clipping
	if ( this.results > 50 ) {
		var viewportRange = this.surface.getView().getViewportRange( true, 50 );
		for ( var i = 0; i < this.results; i++ ) {
			var selection = this.fragments[ i ].getSelection();
			if ( viewportRange && selection.getRange().start < viewportRange.start ) {
				start = i + 1;
				continue;
			}
			if ( viewportRange && selection.getRange().end > viewportRange.end ) {
				end = i;
				break;
			}
		}
	}

	// When there are too many results to render, just render the current one
	if ( end - start <= this.constructor.static.maxRenderedResults ) {
		this.renderRangeOfFragments( new ve.Range( start, end ) );
	} else {
		this.renderRangeOfFragments( new ve.Range( this.focusedIndex, this.focusedIndex + 1 ) );
	}
};

/**
 * Render subset of search result fragments
 *
 * @param {ve.Range} range Range of fragments to render. N.B. This is a range in the
 *  results array, not a document range.
 */
ve.ui.FindAndReplaceDialog.prototype.renderRangeOfFragments = function ( range ) {
	var selections = [];
	for ( var i = range.start; i < range.end; i++ ) {
		selections.push(
			this.surface.getView().getSelection( this.fragments[ i ].getSelection() )
		);
	}
	// Generates ve-ce-surface-selections-findResults CSS class
	this.surface.getView().drawSelections( 'findResults', selections );
	this.isClipped = range.getLength() < this.results;
	this.highlightFocused();
};

/**
 * Highlight the focused result marker
 *
 * @param {boolean} scrollIntoView Scroll the marker into view
 */
ve.ui.FindAndReplaceDialog.prototype.highlightFocused = function ( scrollIntoView ) {
	var surfaceView = this.surface.getView();

	if ( this.results ) {
		this.findText.setLabel(
			ve.msg( 'visualeditor-find-and-replace-results', this.focusedIndex + 1, this.results )
		);
	} else {
		this.findText.setLabel(
			this.invalidRegex ? ve.msg( 'visualeditor-find-and-replace-invalid-regex' ) : ''
		);
		return;
	}

	if ( this.focusedSelection ) {
		var $focusedSelection = surfaceView.getDrawnSelection( 'findResults', this.focusedSelection );
		if ( $focusedSelection ) {
			$focusedSelection.removeClass( 've-ce-surface-selections-findResult-focused' );
		}
	}

	var selection = this.fragments[ this.focusedIndex ].getSelection();
	this.startOffset = selection.getCoveringRange().start;

	var $selection = surfaceView.getDrawnSelection( 'findResults', selection );
	if ( $selection ) {
		$selection.addClass( 've-ce-surface-selections-findResult-focused' );
	}

	if ( scrollIntoView ) {
		surfaceView.getSurface().scrollSelectionIntoView(
			this.fragments[ this.focusedIndex ].getSelection(),
			{ animate: true }
		);
	}

	this.focusedSelection = selection;
};

/**
 * Focus the dialog
 */
ve.ui.FindAndReplaceDialog.prototype.focus = function () {
	this.findText.focus().select();
};

/**
 * Find the selected text on opening
 */
ve.ui.FindAndReplaceDialog.prototype.findSelected = function () {
	var fragment = this.surface.getModel().getFragment( null, true );

	this.initialFragment = fragment;
	this.startOffset = ve.getProp( this.initialFragment.getSelection().getRanges(
		this.initialFragment.getDocument()
	), 0, 'start' ) || 0;

	var text = fragment.getText();
	if ( text && text !== this.findText.getValue() ) {
		this.findText.setValue( text );
	}

	this.focus();
};

/**
 * Find the next result
 */
ve.ui.FindAndReplaceDialog.prototype.findNext = function () {
	this.focusedIndex = ( this.focusedIndex + 1 ) % this.results;
	this.highlightFocused( true );
};

/**
 * Find the previous result
 */
ve.ui.FindAndReplaceDialog.prototype.findPrevious = function () {
	this.focusedIndex = ( this.focusedIndex + this.results - 1 ) % this.results;
	this.highlightFocused( true );
};

/**
 * Handle click events on the replace button
 */
ve.ui.FindAndReplaceDialog.prototype.onReplaceButtonClick = function () {
	if ( !this.results ) {
		return;
	}

	this.replace( this.focusedIndex );

	// Find the next fragment after this one ends. Ensures that if we replace
	// 'foo' with 'foofoo' we don't select the just-inserted text.
	var end = this.fragments[ this.focusedIndex ].getSelection().getRange().end;

	this.updateFragments();

	while ( this.fragments[ this.focusedIndex ] && this.fragments[ this.focusedIndex ].getSelection().getRange().end <= end ) {
		this.focusedIndex++;
	}
	// We may have iterated off the end, or run out of results
	this.focusedIndex = this.results ? this.focusedIndex % this.results : 0;

	this.renderFragments();

	// Wherever we end up, scroll to whatever we've got focused
	this.highlightFocused( true );
};

/**
 * Handle click events on the previous all button
 */
ve.ui.FindAndReplaceDialog.prototype.onReplaceAllButtonClick = function () {
	var surfaceView = this.surface.getView(),
		wasActivated = !surfaceView.isDeactivated();

	if ( wasActivated ) {
		surfaceView.deactivate();
	}
	for ( var i = 0, l = this.results; i < l; i++ ) {
		this.replace( i );
	}
	if ( wasActivated ) {
		surfaceView.activate();
	}

	this.updateFragments();
	this.renderFragments();
};

/**
 * Replace the result at a specified index
 *
 * @param {number} index Index to replace
 */
ve.ui.FindAndReplaceDialog.prototype.replace = function ( index ) {
	var dialog = this,
		replace = this.replaceText.getValue();

	// Prevent replace from triggering throttled redraws
	this.replacing = true;

	if ( this.query instanceof RegExp ) {
		this.fragments[ index ].insertContent(
			this.fragments[ index ].getText().replace( this.query, replace ),
			true
		);
	} else {
		this.fragments[ index ].insertContent( replace, true );
	}

	// 'position' event is deferred, so block that too
	setTimeout( function () {
		dialog.replacing = false;
	} );
};

/**
 * @inheritdoc
 */
ve.ui.FindAndReplaceDialog.prototype.getActionProcess = function ( action ) {
	if ( action === 'findSelected' || action === 'findNext' || action === 'findPrevious' ) {
		return new OO.ui.Process( this[ action ], this );
	}
	return ve.ui.FindAndReplaceDialog.super.prototype.getActionProcess.call( this, action );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.FindAndReplaceDialog );
