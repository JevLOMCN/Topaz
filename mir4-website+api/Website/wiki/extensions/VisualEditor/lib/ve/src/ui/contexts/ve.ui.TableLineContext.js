/*!
 * VisualEditor UserInterface Table Context class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context menu for editing tables.
 *
 * Three are usually generated for column, row and (on mobile) table-wide actions separately.
 *
 * @class
 * @extends ve.ui.Context
 *
 * @constructor
 * @param {ve.ce.TableNode} tableNode
 * @param {string} itemGroup Tool group to use, 'col', 'row', or 'table'
 * @param {Object} [config] Configuration options
 */
ve.ui.TableLineContext = function VeUiTableLineContext( tableNode, itemGroup, config ) {
	config = config || {};

	// Parent constructor
	ve.ui.TableLineContext.super.call( this, tableNode.surface.getSurface(), config );

	// Properties
	this.tableNode = tableNode;
	this.itemGroup = itemGroup;
	this.icon = new OO.ui.IconWidget( {
		icon: this.constructor.static.icons[ itemGroup ]
	} );
	this.popup = new OO.ui.PopupWidget( {
		classes: [ 've-ui-tableLineContext-menu' ],
		$container: this.surface.$element,
		$floatableContainer: this.icon.$element,
		position: this.constructor.static.positions[ this.itemGroup ],
		width: null
	} );

	// Events
	this.icon.$element.on( 'mousedown', this.onIconMouseDown.bind( this ) );
	this.onDocumentMouseDownHandler = this.onDocumentMouseDown.bind( this );

	// Initialization
	var labels = {
		col: ve.msg( 'visualeditor-table-context-col' ),
		row: ve.msg( 'visualeditor-table-context-row' ),
		table: ve.msg( 'visualeditor-toolbar-table' )
	};
	this.icon.$element
		.attr( 'role', 'button' )
		.attr( 'aria-label', labels[ itemGroup ] );
	this.popup.$body.append( this.$group );
	// The following classes are used here:
	// * ve-ui-tableLineContext-col
	// * ve-ui-tableLineContext-row
	// * ve-ui-tableLineContext-table
	this.$element
		.addClass( 've-ui-tableLineContext ve-ui-tableLineContext-' + itemGroup )
		.append( this.icon.$element, this.popup.$element );
	// Visibility is handled by the table overlay
	this.toggle( true );
};

/* Inheritance */

OO.inheritClass( ve.ui.TableLineContext, ve.ui.Context );

/* Static Properties */

ve.ui.TableLineContext.static.groups = {
	col: [ 'insertColumnBefore', 'insertColumnAfter', 'moveColumnBefore', 'moveColumnAfter', 'deleteColumn' ],
	row: [ 'insertRowBefore', 'insertRowAfter', 'moveRowBefore', 'moveRowAfter', 'deleteRow' ],
	table: [ 'tableProperties', 'toggleTableEditing', 'deleteTable' ]
};

ve.ui.TableLineContext.static.icons = {
	col: 'expand',
	row: 'next',
	table: 'table'
};

ve.ui.TableLineContext.static.positions = {
	col: 'below',
	row: 'after',
	table: 'after'
};

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.TableLineContext.prototype.getRelatedSources = function () {
	var items = this.constructor.static.groups[ this.itemGroup ];

	if ( !this.relatedSources ) {
		this.relatedSources = [];

		for ( var i = 0, l = items.length; i < l; i++ ) {
			this.relatedSources.push( {
				type: 'item',
				name: items[ i ]
			} );
		}
	}
	return this.relatedSources;
};

/**
 * @inheritdoc
 */
ve.ui.TableLineContext.prototype.onContextItemCommand = function () {
	this.toggleMenu( false );
};

/**
 * Handle mouse down events on the icon
 *
 * @param {jQuery.Event} e Mouse down event
 */
ve.ui.TableLineContext.prototype.onIconMouseDown = function ( e ) {
	e.preventDefault();
	this.toggleMenu( undefined, true );
};

/**
 * Handle document mouse down events
 *
 * @param {jQuery.Event} e Mouse down event
 */
ve.ui.TableLineContext.prototype.onDocumentMouseDown = function ( e ) {
	if ( !$( e.target ).closest( this.$element ).length ) {
		this.toggleMenu( false, true );
	}
};

/**
 * Handle model select events
 *
 * @param {ve.dm.Selection} selection
 */
ve.ui.TableLineContext.prototype.onModelSelect = function () {
	this.toggleMenu();
};

/**
 * @inheritdoc
 */
ve.ui.TableLineContext.prototype.toggleMenu = function ( show, restoreEditing ) {
	show = show === undefined ? !this.popup.isVisible() : !!show;

	var surfaceModel = this.surface.getModel();
	var surfaceView = this.surface.getView();

	// Remember whether the table was in editing mode, because some itemGroups
	// will force it into editing mode so their commands can work on a
	// TableSelection.
	this.wasEditing = !!this.tableNode.editingFragment;

	// Kick the table into/out of editing mode if needed:
	if ( this.itemGroup !== 'table' ) {
		if ( show ) {
			this.tableNode.setEditing( false );
		} else if ( restoreEditing && surfaceModel.getSelection() instanceof ve.dm.TableSelection ) {
			this.tableNode.setEditing( this.wasEditing );
		}
	}

	// Set up the close-if-anything-happens handlers:
	if ( show ) {
		surfaceModel.connect( this, { select: 'onModelSelect' } );
		surfaceView.$document.on( 'mousedown', this.onDocumentMouseDownHandler );
		surfaceView.deactivate();
		var dir = surfaceView.getSelectionDirectionality();
		// eslint-disable-next-line mediawiki/class-doc
		this.$element
			.removeClass( 've-ui-dir-block-rtl ve-ui-dir-block-ltr' )
			.addClass( 've-ui-dir-block-' + dir );
	} else {
		surfaceModel.disconnect( this );
		surfaceView.$document.off( 'mousedown', this.onDocumentMouseDownHandler );
		surfaceView.activate();
	}

	// Parent method - call after selection has been possibly modified above
	ve.ui.TableLineContext.super.prototype.toggleMenu.call( this, show );

	// Display the popup with correct positioning after the parent method fills in its contents
	// (or hide it).
	this.popup.toggle( show );
};
