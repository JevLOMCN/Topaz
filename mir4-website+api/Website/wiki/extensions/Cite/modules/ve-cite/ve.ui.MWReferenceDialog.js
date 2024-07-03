'use strict';

/*!
 * VisualEditor UserInterface MediaWiki MWReferenceDialog class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * Dialog for editing MediaWiki references.
 *
 * @constructor
 * @extends ve.ui.NodeDialog
 * @param {Object} [config] Configuration options
 */
ve.ui.MWReferenceDialog = function VeUiMWReferenceDialog( config ) {
	// Parent constructor
	ve.ui.MWReferenceDialog.super.call( this, config );

	// Properties
	this.referenceModel = null;
	this.useExisting = false;
};

/* Inheritance */

OO.inheritClass( ve.ui.MWReferenceDialog, ve.ui.NodeDialog );

/* Static Properties */

ve.ui.MWReferenceDialog.static.name = 'reference';

ve.ui.MWReferenceDialog.static.title =
	OO.ui.deferMsg( 'cite-ve-dialog-reference-title' );

ve.ui.MWReferenceDialog.static.actions = [
	{
		action: 'done',
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-apply' ),
		flags: [ 'progressive', 'primary' ],
		modes: 'edit'
	},
	{
		action: 'insert',
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-insert' ),
		flags: [ 'progressive', 'primary' ],
		modes: 'insert'
	},
	{
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-cancel' ),
		flags: [ 'safe', 'close' ],
		modes: [ 'readonly', 'insert', 'edit', 'insert-select' ]
	}
];

ve.ui.MWReferenceDialog.static.modelClasses = [ ve.dm.MWReferenceNode ];

ve.ui.MWReferenceDialog.static.includeCommands = null;

ve.ui.MWReferenceDialog.static.excludeCommands = [
	// No formatting
	'paragraph',
	'heading1',
	'heading2',
	'heading3',
	'heading4',
	'heading5',
	'heading6',
	'preformatted',
	'blockquote',
	// No tables
	'insertTable',
	'deleteTable',
	'mergeCells',
	'tableCaption',
	'tableCellHeader',
	'tableCellData',
	// No structure
	'bullet',
	'bulletWrapOnce',
	'number',
	'numberWrapOnce',
	'indent',
	'outdent',
	// References
	'reference',
	'reference/existing',
	'citoid',
	'referencesList'
];

/**
 * Get the import rules for the surface widget in the dialog.
 *
 * @see ve.dm.ElementLinearData#sanitize
 * @return {Object} Import rules
 */
ve.ui.MWReferenceDialog.static.getImportRules = function () {
	const rules = ve.copy( ve.init.target.constructor.static.importRules );
	return ve.extendObject(
		rules,
		{
			all: {
				blacklist: ve.extendObject(
					{
						// Nested references are impossible
						mwReference: true,
						mwReferencesList: true,
						// Lists and tables are actually possible in wikitext with a leading
						// line break but we prevent creating these with the UI
						list: true,
						listItem: true,
						definitionList: true,
						definitionListItem: true,
						table: true,
						tableCaption: true,
						tableSection: true,
						tableRow: true,
						tableCell: true,
						mwTable: true,
						mwTransclusionTableCell: true
					},
					ve.getProp( rules, 'all', 'blacklist' )
				),
				// Headings are not possible in wikitext without HTML
				conversions: ve.extendObject(
					{
						mwHeading: 'paragraph'
					},
					ve.getProp( rules, 'all', 'conversions' )
				)
			}
		}
	);
};

/* Methods */

/**
 * Determine whether the reference document we're editing has any content.
 *
 * @return {boolean} Document has content
 */
ve.ui.MWReferenceDialog.prototype.documentHasContent = function () {
	// TODO: Check for other types of empty, e.g. only whitespace?
	return this.referenceModel && this.referenceModel.getDocument().data.hasContent();
};

/*
 * Determine whether any changes have been made (and haven't been undone).
 *
 * @return {boolean} Changes have been made
 */
ve.ui.MWReferenceDialog.prototype.isModified = function () {
	return this.documentHasContent() &&
		( this.referenceTarget.hasBeenModified() ||
		this.referenceGroupInput.getValue() !== this.originalGroup );
};

/**
 * Handle reference target widget change events
 */
ve.ui.MWReferenceDialog.prototype.onTargetChange = function () {
	const hasContent = this.documentHasContent();

	this.actions.setAbilities( {
		done: this.isModified(),
		insert: hasContent
	} );

	if ( !this.trackedInputChange ) {
		ve.track( 'activity.' + this.constructor.static.name, { action: 'input' } );
		this.trackedInputChange = true;
	}
};

/**
 * Handle reference group input change events.
 */
ve.ui.MWReferenceDialog.prototype.onReferenceGroupInputChange = function () {
	this.actions.setAbilities( {
		done: this.isModified()
	} );

	if ( !this.trackedInputChange ) {
		ve.track( 'activity.' + this.constructor.static.name, { action: 'input' } );
		this.trackedInputChange = true;
	}
};

/**
 * Handle search results choose events.
 *
 * @param {ve.ui.MWReferenceResultWidget} item Chosen item
 */
ve.ui.MWReferenceDialog.prototype.onSearchResultsChoose = function ( item ) {
	const ref = item.getData();

	if ( this.selectedNode instanceof ve.dm.MWReferenceNode ) {
		this.getFragment().removeContent();
		this.selectedNode = null;
	}
	this.useReference( ref );
	this.executeAction( 'insert' );

	ve.track( 'activity.' + this.constructor.static.name, { action: 'reuse-choose' } );
};

/**
 * @override
 */
ve.ui.MWReferenceDialog.prototype.getReadyProcess = function ( data ) {
	return ve.ui.MWReferenceDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			if ( this.useExisting ) {
				this.search.getQuery().focus().select();
			} else {
				this.referenceTarget.focus();
			}
		}, this );
};

/**
 * @override
 */
ve.ui.MWReferenceDialog.prototype.getBodyHeight = function () {
	// Clamp value to between 300 and 400px height, preferring the actual height if available
	return Math.min(
		400,
		Math.max(
			300,
			Math.ceil( this.panels.getCurrentItem().$element[ 0 ].scrollHeight )
		)
	);
};

/**
 * Work on a specific reference.
 *
 * @param {ve.dm.MWReferenceModel} [ref] Reference model, omit to work on a new reference
 * @return {ve.ui.MWReferenceDialog}
 * @chainable
 */
ve.ui.MWReferenceDialog.prototype.useReference = function ( ref ) {
	// Properties
	if ( ref instanceof ve.dm.MWReferenceModel ) {
		// Use an existing reference
		this.referenceModel = ref;
	} else {
		// Create a new reference
		this.referenceModel = new ve.dm.MWReferenceModel( this.getFragment().getDocument() );
	}

	this.referenceTarget.setDocument( this.referenceModel.getDocument() );

	// Initialization
	this.originalGroup = this.referenceModel.getGroup();
	// Set the group input while it's disabled, so this doesn't pop up the group-picker menu
	this.referenceGroupInput.setDisabled( true );
	this.referenceGroupInput.setValue( this.originalGroup );
	this.referenceGroupInput.setDisabled( false );

	const group = this.getFragment().getDocument().getInternalList()
		.getNodeGroup( this.referenceModel.getListGroup() );
	const nodes = ve.getProp( group, 'keyedNodes', this.referenceModel.getListKey() );
	const usages = nodes ? nodes.filter( function ( node ) {
		return !node.findParent( ve.dm.MWReferencesListNode );
	} ).length : 0;

	this.reuseWarning.toggle( usages > 1 )
		.setLabel( mw.msg( 'cite-ve-dialog-reference-editing-reused-long', usages ) );

	return this;
};

/**
 * @override
 */
ve.ui.MWReferenceDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.MWReferenceDialog.super.prototype.initialize.call( this );

	// Properties
	this.panels = new OO.ui.StackLayout();
	this.editPanel = new OO.ui.PanelLayout( {
		scrollable: true, padded: true
	} );
	this.searchPanel = new OO.ui.PanelLayout();

	this.reuseWarning = new OO.ui.MessageWidget( {
		inline: true,
		icon: 'alert',
		classes: [ 've-ui-mwReferenceDialog-reuseWarning' ]
	} );

	const citeCommands = Object.keys( ve.init.target.getSurface().commandRegistry.registry )
		.filter( ( command ) => command.indexOf( 'cite-' ) !== -1 );
	this.referenceTarget = ve.init.target.createTargetWidget(
		{
			includeCommands: this.constructor.static.includeCommands,
			excludeCommands: this.constructor.static.excludeCommands.concat( citeCommands ),
			importRules: this.constructor.static.getImportRules(),
			inDialog: this.constructor.static.name,
			placeholder: ve.msg( 'cite-ve-dialog-reference-placeholder' )
		}
	);

	this.contentFieldset = new OO.ui.FieldsetLayout();
	this.optionsFieldset = new OO.ui.FieldsetLayout( {
		label: ve.msg( 'cite-ve-dialog-reference-options-section' ),
		icon: 'settings'
	} );
	this.contentFieldset.$element.append( this.referenceTarget.$element );

	this.referenceGroupInput = new ve.ui.MWReferenceGroupInputWidget( {
		$overlay: this.$overlay,
		emptyGroupName: ve.msg( 'cite-ve-dialog-reference-options-group-placeholder' )
	} );
	this.referenceGroupInput.connect( this, { change: 'onReferenceGroupInputChange' } );
	this.referenceGroupField = new OO.ui.FieldLayout( this.referenceGroupInput, {
		align: 'top',
		label: ve.msg( 'cite-ve-dialog-reference-options-group-label' )
	} );
	this.search = new ve.ui.MWReferenceSearchWidget();

	// Events
	this.search.getResults().connect( this, { choose: 'onSearchResultsChoose' } );
	this.referenceTarget.connect( this, { change: 'onTargetChange' } );

	// Initialization
	this.$content.addClass( 've-ui-mwReferenceDialog' );

	this.panels.addItems( [ this.editPanel, this.searchPanel ] );
	this.editPanel.$element.append(
		this.reuseWarning.$element, this.contentFieldset.$element, this.optionsFieldset.$element );
	this.optionsFieldset.addItems( [ this.referenceGroupField ] );
	this.searchPanel.$element.append( this.search.$element );
	this.$body.append( this.panels.$element );
};

/**
 * Switches dialog to use existing reference mode.
 */
ve.ui.MWReferenceDialog.prototype.useExistingReference = function () {
	this.actions.setMode( 'insert-select' );
	this.search.buildIndex();
	this.panels.setItem( this.searchPanel );
	this.search.getQuery().focus().select();
};

/**
 * @override
 */
ve.ui.MWReferenceDialog.prototype.getActionProcess = function ( action ) {
	if ( action === 'insert' || action === 'done' ) {
		return new OO.ui.Process( function () {
			const surfaceModel = this.getFragment().getSurface();

			this.referenceModel.setGroup( this.referenceGroupInput.getValue() );

			// Insert reference (will auto-create an internal item if needed)
			if ( !( this.selectedNode instanceof ve.dm.MWReferenceNode ) ) {
				if ( !this.referenceModel.findInternalItem( surfaceModel ) ) {
					this.referenceModel.insertInternalItem( surfaceModel );
				}
				// Collapse returns a new fragment, so update this.fragment
				this.fragment = this.getFragment().collapseToEnd();
				this.referenceModel.insertReferenceNode( this.getFragment() );
			}

			// Update internal item
			this.referenceModel.updateInternalItem( surfaceModel );

			this.close( { action: action } );
		}, this );
	}
	return ve.ui.MWReferenceDialog.super.prototype.getActionProcess.call( this, action );
};

/**
 * @override
 * @param {Object} [data] Setup data
 * @param {boolean} [data.useExistingReference] Open the dialog in "use existing reference" mode
 */
ve.ui.MWReferenceDialog.prototype.getSetupProcess = function ( data ) {
	data = data || {};
	return ve.ui.MWReferenceDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.panels.setItem( this.editPanel );
			if ( this.selectedNode instanceof ve.dm.MWReferenceNode ) {
				this.useReference(
					ve.dm.MWReferenceModel.static.newFromReferenceNode( this.selectedNode )
				);
			} else {
				this.useReference( null );
				this.actions.setAbilities( { done: false, insert: false } );
			}

			this.search.setInternalList( this.getFragment().getDocument().getInternalList() );

			const isReadOnly = this.isReadOnly();
			this.referenceTarget.setReadOnly( isReadOnly );
			this.referenceGroupInput.setReadOnly( isReadOnly );

			if ( data.useExisting ) {
				this.useExistingReference();
			}
			this.useExisting = !!data.useExisting;
			this.actions.setAbilities( {
				done: false
			} );

			this.referenceGroupInput.populateMenu(
				this.getFragment().getDocument().getInternalList() );

			this.trackedInputChange = false;
		}, this );
};

/**
 * @override
 */
ve.ui.MWReferenceDialog.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWReferenceDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			this.referenceTarget.getSurface().getModel().disconnect( this );
			this.search.getQuery().setValue( '' );
			this.referenceTarget.clear();
			this.referenceModel = null;
		}, this );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWReferenceDialog );
