/*!
 * VisualEditor UserInterface LinkAnnotationInspector class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Inspector for applying and editing labeled MediaWiki internal and external links.
 *
 * @class
 * @extends ve.ui.LinkAnnotationInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLinkAnnotationInspector = function VeUiMWLinkAnnotationInspector( config ) {
	// Parent constructor
	ve.ui.MWLinkAnnotationInspector.super.call( this, ve.extendObject( { padded: false }, config ) );

	this.$element.addClass( 've-ui-mwLinkAnnotationInspector' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLinkAnnotationInspector, ve.ui.LinkAnnotationInspector );

/* Static properties */

ve.ui.MWLinkAnnotationInspector.static.name = 'link';

ve.ui.MWLinkAnnotationInspector.static.modelClasses = [
	ve.dm.MWExternalLinkAnnotation,
	ve.dm.MWInternalLinkAnnotation
];

ve.ui.MWLinkAnnotationInspector.static.actions = ve.ui.MWLinkAnnotationInspector.static.actions.concat( [
	{
		action: 'convert',
		label: null, // see #updateActions
		modes: [ 'edit', 'insert' ]
	}
] );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWLinkAnnotationInspector.prototype.initialize = function () {
	// Properties
	this.allowProtocolInInternal = false;
	this.internalAnnotationInput = this.createInternalAnnotationInput();
	this.externalAnnotationInput = this.createExternalAnnotationInput();

	this.linkTypeIndex = new OO.ui.IndexLayout( {
		expanded: false,
		framed: false
	} );

	this.linkTypeIndex.addTabPanels( [
		new OO.ui.TabPanelLayout( 'internal', {
			label: mw.config.get( 'wgSiteName' ),
			expanded: false,
			scrollable: false,
			padded: true
		} ),
		new OO.ui.TabPanelLayout( 'external', {
			label: ve.msg( 'visualeditor-linkinspector-button-link-external' ),
			expanded: false,
			scrollable: false,
			padded: true
		} )
	] );

	// Parent method
	// Parent requires createAnnotationInput to be callable, but tries to move
	// inputs in the DOM, so call this before we restructure the DOM.
	ve.ui.MWLinkAnnotationInspector.super.prototype.initialize.call( this );

	this.internalAnnotationField = this.annotationField;
	this.externalAnnotationField = new OO.ui.FieldLayout(
		this.externalAnnotationInput,
		{
			align: 'top',
			label: ve.msg( 'visualeditor-linkinspector-title' )
		}
	);

	// Events
	this.linkTypeIndex.connect( this, { set: 'onLinkTypeIndexSet' } );
	this.labelInput.connect( this, { change: 'onLabelInputChange' } );
	this.internalAnnotationInput.connect( this, { change: 'onInternalLinkChange' } );
	this.externalAnnotationInput.connect( this, { change: 'onExternalLinkChange' } );
	this.internalAnnotationInput.input.getResults().connect( this, { choose: 'onFormSubmit' } );
	// Form submit only auto triggers on enter when there is one input
	this.internalAnnotationInput.getTextInputWidget().connect( this, {
		change: 'onInternalLinkInputChange',
		enter: 'onLinkInputEnter'
	} );
	this.externalAnnotationInput.getTextInputWidget().connect( this, {
		change: 'onExternalLinkInputChange',
		enter: 'onLinkInputEnter'
	} );
	// this.internalAnnotationInput is already bound by parent class
	this.externalAnnotationInput.connect( this, { change: 'onAnnotationInputChange' } );

	this.internalAnnotationInput.input.results.connect( this, {
		add: 'onInternalLinkChangeResultsChange',
		// Listening to remove causes a flicker, and is not required
		// as 'add' is always trigger on a change too
		choose: 'onInternalLinkSearchResultsChoose'
	} );

	// Initialization
	// HACK: IndexLayout is absolutely positioned, so place actions inside it
	this.linkTypeIndex.$content.append( this.$otherActions );
	this.linkTypeIndex.getTabPanel( 'internal' ).$element.append( this.internalAnnotationField.$element );
	this.linkTypeIndex.getTabPanel( 'external' ).$element.append( this.externalAnnotationField.$element );
	// labelField gets moved between tabs when activated
	if ( OO.ui.isMobile() ) {
		this.linkTypeIndex.getTabPanel( 'internal' ).$element.prepend( this.labelField.$element );
	}
	this.form.$element.empty().append( this.linkTypeIndex.$element );
	if ( !OO.ui.isMobile() ) {
		this.externalAnnotationField.setLabel( null );
	}
};

/**
 * @return {ve.ui.MWInternalLinkAnnotationWidget}
 */
ve.ui.MWLinkAnnotationInspector.prototype.createInternalAnnotationInput = function () {
	return new ve.ui.MWInternalLinkAnnotationWidget();
};

/**
 * @return {ve.ui.MWExternalLinkAnnotationWidget}
 */
ve.ui.MWLinkAnnotationInspector.prototype.createExternalAnnotationInput = function () {
	return new ve.ui.MWExternalLinkAnnotationWidget();
};

/**
 * Check if the current input mode is for external links
 *
 * @return {boolean} Input mode is for external links
 */
ve.ui.MWLinkAnnotationInspector.prototype.isExternal = function () {
	return this.linkTypeIndex.getCurrentTabPanelName() === 'external';
};

/**
 * Handle change events on the label input
 *
 * @param {string} value
 */
ve.ui.MWLinkAnnotationInspector.prototype.onLabelInputChange = function () {
	if ( this.isActive && !this.trackedLabelInputChange ) {
		ve.track( 'activity.' + this.constructor.static.name, { action: 'label-input' } );
		this.trackedLabelInputChange = true;
	}
};

/**
 * Handle change events on the internal link widget
 *
 * @param {ve.dm.MWInternalLinkAnnotation} annotation
 */
ve.ui.MWLinkAnnotationInspector.prototype.onInternalLinkChange = function () {
	this.updateActions();
};

/**
 * Handle list change events ('add') from the interal link's result widget
 *
 * @param {OO.ui.OptionWidget[]} items Added items
 * @param {number} index Index of insertion point
 */
ve.ui.MWLinkAnnotationInspector.prototype.onInternalLinkChangeResultsChange = function () {
	this.updateSize();
};

/**
 * Handle choose events from the result widget
 *
 * @param {OO.ui.OptionWidget} item Chosen item
 */
ve.ui.MWLinkAnnotationInspector.prototype.onInternalLinkSearchResultsChoose = function () {
	ve.track( 'activity.' + this.constructor.static.name, { action: 'search-pages-choose' } );
};

/**
 * Handle change events on the external link widget
 *
 * @param {ve.dm.MWExternalLinkAnnotation} annotation
 */
ve.ui.MWLinkAnnotationInspector.prototype.onExternalLinkChange = function () {
	this.updateActions();
};

/**
 * Handle enter events on the external/internal link inputs
 *
 * @param {jQuery.Event} e Key press event
 */
ve.ui.MWLinkAnnotationInspector.prototype.onLinkInputEnter = function () {
	var inspector = this;
	if ( this.annotationInput.getTextInputWidget().getValue().trim() === '' ) {
		this.executeAction( 'done' );
	}
	this.annotationInput.getTextInputWidget().getValidity()
		.done( function () {
			inspector.executeAction( 'done' );
		} );
};

/**
 * @inheritdoc
 */
ve.ui.MWLinkAnnotationInspector.prototype.updateActions = function () {
	var msg = null;

	ve.ui.MWLinkAnnotationInspector.super.prototype.updateActions.call( this );

	// show/hide convert action
	var content = this.fragment ? this.fragment.getText() : '';
	var annotation = this.annotationInput.getAnnotation();
	var href = annotation && annotation.getHref();
	if ( href && ve.dm.MWMagicLinkNode.static.validateHref( content, href ) ) {
		var type = ve.dm.MWMagicLinkType.static.fromContent( content ).type;
		msg = 'visualeditor-linkinspector-convert-link-' + type.toLowerCase();
	}

	// Once we toggle the visibility of the ActionWidget, we can't filter
	// it with `get` any more.  So we have to use `forEach`:
	this.actions.forEach( null, function ( action ) {
		if ( action.getAction() === 'convert' ) {
			if ( msg ) {
				// The following messages are used here:
				// * visualeditor-linkinspector-convert-link-isbn
				// * visualeditor-linkinspector-convert-link-pmid
				// * visualeditor-linkinspector-convert-link-rfc
				action.setLabel( OO.ui.deferMsg( msg ) );
				action.toggle( true );
			} else {
				action.toggle( false );
			}
		}
	} );
};

/**
 * Handle change events on the internal link widget's input
 *
 * @param {string} value Current value of input widget
 */
ve.ui.MWLinkAnnotationInspector.prototype.onInternalLinkInputChange = function ( value ) {
	var inspector = this;

	// If this looks like an external link, switch to the correct tabPanel.
	// Note: We don't care here if it's a *valid* link, so we just
	// check whether it looks like a URI -- i.e. whether it starts with
	// something that appears to be a schema per RFC1630. Later the external
	// link inspector will use getExternalLinkUrlProtocolsRegExp for validity
	// checking.
	// Note 2: RFC1630 might be too broad in practice. You don't really see
	// schemas that use the full set of allowed characters, and we might get
	// more false positives by checking for them.
	// Note 3: We allow protocol-relative URIs here.
	if ( this.internalAnnotationInput.getTextInputWidget().getValue() !== value ) {
		return;
	}
	if ( this.isActive && !this.trackedInternalLinkInputChange && !this.switchingLinkTypes ) {
		ve.track( 'activity.' + this.constructor.static.name, { action: 'search-pages-input' } );
		this.trackedInternalLinkInputChange = true;
	}
	if (
		!this.allowProtocolInInternal &&
		( /^(?:[a-z][a-z0-9$\-_@.&!*"'(),]*:)?\/\//i ).test( value.trim() )
	) {
		this.linkTypeIndex.setTabPanel( 'external' );
		// Changing tabPanel focuses and selects the input, so collapse the cursor back to the end.
		this.externalAnnotationInput.getTextInputWidget().moveCursorToEnd();
	}

	this.internalAnnotationInput.getTextInputWidget().getValidity()
		.then(
			function () {
				inspector.internalAnnotationField.setErrors( [] );
				inspector.updateSize();
			}, function () {
				inspector.internalAnnotationField.setErrors( [ ve.msg( 'visualeditor-linkinspector-illegal-title' ) ] );
				inspector.updateSize();
			}
		);

};

/**
 * Handle change events on the external link widget's input
 *
 * @param {string} value Current value of input widget
 */
ve.ui.MWLinkAnnotationInspector.prototype.onExternalLinkInputChange = function () {
	var inspector = this;

	this.externalAnnotationInput.getTextInputWidget().getValidity()
		.then(
			function () {
				inspector.externalAnnotationField.setErrors( [] );
				inspector.updateSize();
			}, function () {
				inspector.externalAnnotationField.setErrors( [ ve.msg( 'visualeditor-linkinspector-invalid-external' ) ] );
				inspector.updateSize();
			}
		);

	if ( this.isActive && !this.trackedExternalLinkInputChange && !this.switchingLinkTypes ) {
		ve.track( 'activity.' + this.constructor.static.name, { action: 'external-link-input' } );
		this.trackedExternalLinkInputChange = true;
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWLinkAnnotationInspector.prototype.createAnnotationInput = function () {
	return this.isExternal() ? this.externalAnnotationInput : this.internalAnnotationInput;
};

/**
 * @inheritdoc
 */
ve.ui.MWLinkAnnotationInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWLinkAnnotationInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.isReady = false;

			var isReadOnly = this.isReadOnly();
			this.linkTypeIndex.setTabPanel(
				this.initialAnnotation instanceof ve.dm.MWExternalLinkAnnotation ? 'external' : 'internal'
			);
			this.annotationInput.setAnnotation( this.initialAnnotation );
			this.internalAnnotationInput.setReadOnly( isReadOnly );
			this.externalAnnotationInput.setReadOnly( isReadOnly );

			this.trackedInternalLinkInputChange = false;
			this.trackedExternalLinkInputChange = false;
			this.isActive = true;
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWLinkAnnotationInspector.prototype.getReadyProcess = function ( data ) {
	return ve.ui.MWLinkAnnotationInspector.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			this.isReady = true;
			// Focus is skipped during setup. (T321026)
			this.annotationInput.getTextInputWidget().focus();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWLinkAnnotationInspector.prototype.getActionProcess = function ( action ) {
	if ( action === 'convert' ) {
		return new OO.ui.Process( function () {
			this.close( { action: 'done', convert: true } );
		}, this );
	}
	return ve.ui.MWLinkAnnotationInspector.super.prototype.getActionProcess.call( this, action );
};

/**
 * @inheritdoc
 */
ve.ui.MWLinkAnnotationInspector.prototype.getTeardownProcess = function ( data ) {
	var fragment;
	return ve.ui.MWLinkAnnotationInspector.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			// Save the original fragment for later.
			fragment = this.getFragment();

			this.isActive = false;
		}, this )
		.next( function () {
			var selection = fragment && fragment.getSelection();

			// Handle conversion to magic link.
			if ( data && data.convert && selection instanceof ve.dm.LinearSelection ) {
				var annotations = fragment.getDocument().data
					.getAnnotationsFromRange( selection.getRange() )
					// Remove link annotations
					.filter( function ( annotation ) {
						return !/^link/.test( annotation.name );
					} );
				var linearData = new ve.dm.ElementLinearData( annotations.store, [
					{
						type: 'link/mwMagic',
						attributes: {
							content: fragment.getText()
						}
					},
					{
						type: '/link/mwMagic'
					}
				] );
				linearData.setAnnotationsAtOffset( 0, annotations );
				fragment.insertContent( linearData.getData(), true );
			}

			// Clear dialog state.
			this.allowProtocolInInternal = false;
			// Make sure both inputs are cleared
			this.internalAnnotationInput.setAnnotation( null );
			this.externalAnnotationInput.setAnnotation( null );
		}, this );
};

/**
 * Handle set events from the linkTypeIndex layout
 *
 * @param {OO.ui.TabPanelLayout} tabPanel Current tabPanel
 */
ve.ui.MWLinkAnnotationInspector.prototype.onLinkTypeIndexSet = function ( tabPanel ) {
	var text = this.annotationInput.getTextInputWidget().getValue(),
		end = text.length,
		isExternal = this.isExternal(),
		inputHasProtocol = ve.init.platform.getExternalLinkUrlProtocolsRegExp().test( text );

	this.switchingLinkTypes = true;

	this.annotationInput = isExternal ? this.externalAnnotationInput : this.internalAnnotationInput;

	if ( OO.ui.isMobile() ) {
		tabPanel.$element.prepend( this.labelField.$element );
	}

	this.updateSize();

	// If the user manually switches to internal links with an external link in the input, remember this
	if ( !isExternal && inputHasProtocol ) {
		this.allowProtocolInInternal = true;
	}

	this.annotationInput.getTextInputWidget().setValue( text );
	if ( this.isReady ) {
		// Focussing an element that isn't visible yet triggers a
		// bug in jQuery that prevents future focusses. (T321026)
		this.annotationInput.getTextInputWidget().focus();
	}
	// Select entire link when switching, for ease of replacing entire contents.
	// Most common case:
	// 1. Inspector opened, internal-link shown with the selected-word prefilled
	// 2. User clicks external link tab (unnecessary, because we'd auto-switch, but the user doesn't know that)
	// 3. User pastes a link, intending to replace the existing prefilled link
	this.annotationInput.getTextInputWidget().$input[ 0 ].setSelectionRange( 0, end );
	// Focusing a TextInputWidget normally unsets validity. However, because
	// we're kind of pretending this is the same input, just in a different
	// mode, it doesn't make sense to the user that the focus behavior occurs.
	this.annotationInput.getTextInputWidget().setValidityFlag();

	this.onAnnotationInputChange();

	if ( this.isActive ) {
		ve.track( 'activity.' + this.constructor.static.name, { action: 'panel-switch' } );
	}

	this.switchingLinkTypes = false;
};

/**
 * Gets an annotation object from a fragment.
 *
 * The type of link is automatically detected based on some crude heuristics.
 *
 * @param {ve.dm.SurfaceFragment} fragment Current selection
 * @return {ve.dm.MWInternalLinkAnnotation|ve.dm.MWExternalLinkAnnotation|null}
 */
ve.ui.MWLinkAnnotationInspector.prototype.getAnnotationFromFragment = function ( fragment ) {
	var target = fragment.getText(),
		title = mw.Title.newFromText( target );

	// Figure out if this is an internal or external link
	if ( ve.init.platform.getExternalLinkUrlProtocolsRegExp().test( target ) ) {
		// External link
		return this.newExternalLinkAnnotation( {
			type: 'link/mwExternal',
			attributes: {
				href: target
			}
		} );
	} else if ( title ) {
		// Internal link
		return this.newInternalLinkAnnotationFromTitle( title );
	} else {
		// Doesn't look like an external link and mw.Title considered it an illegal value,
		// for an internal link.
		return null;
	}
};

/**
 * @param {mw.Title} title The title to link to.
 * @return {ve.dm.MWInternalLinkAnnotation} The annotation.
 */
ve.ui.MWLinkAnnotationInspector.prototype.newInternalLinkAnnotationFromTitle = function ( title ) {
	return ve.dm.MWInternalLinkAnnotation.static.newFromTitle( title );
};

/**
 * @param {Object} element
 * @return {ve.dm.MWExternalLinkAnnotation} The annotation.
 */
ve.ui.MWLinkAnnotationInspector.prototype.newExternalLinkAnnotation = function ( element ) {
	return new ve.dm.MWExternalLinkAnnotation( element );
};

/**
 * @inheritdoc
 */
ve.ui.MWLinkAnnotationInspector.prototype.getInsertionText = function () {
	// Prefer user input, not normalized annotation, to preserve case
	var label = this.labelInput.getValue().trim();
	if ( label ) {
		return label;
	} else if ( this.isNew && this.isExternal() ) {
		return '';
	} else {
		return this.annotationInput.getTextInputWidget().getValue();
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWLinkAnnotationInspector.prototype.getInsertionData = function () {
	// If this is a new external link with no label, insert an autonumbered link instead of a link annotation
	// (applying the annotation on this later does nothing because of disallowedAnnotationTypes).
	// Otherwise call parent method to figure out the text to insert and annotate.
	if ( this.isNew && this.isExternal() && !this.labelInput.getValue().trim() ) {
		return [
			{
				type: 'link/mwNumberedExternal',
				attributes: {
					href: this.annotationInput.getHref()
				}
			},
			{ type: '/link/mwNumberedExternal' }
		];
	} else {
		return this.getInsertionText().split( '' );
	}
};

// #getInsertionText call annotationInput#getHref, which returns the link title,
// so no custmisation is needed.

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWLinkAnnotationInspector );
