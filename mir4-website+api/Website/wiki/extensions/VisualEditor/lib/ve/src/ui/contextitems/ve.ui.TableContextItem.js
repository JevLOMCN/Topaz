/*!
 * VisualEditor TableContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for tables.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config] Configuration options
 */
ve.ui.TableContextItem = function VeUiTableContextItem( context, model, config ) {
	// Parent constructor
	ve.ui.TableContextItem.super.call( this, context, model, config );

	// Initialization
	this.$element.addClass( 've-ui-tableContextItem' );

	this.deleteButton = new OO.ui.ButtonWidget( {
		label: ve.msg( 'visualeditor-contextitemwidget-label-remove' ),
		flags: [ 'destructive' ]
	} );
	if ( !this.isReadOnly() ) {
		this.actionButtons.addItems( [ this.deleteButton, this.editButton ] );
	}

	// Events
	this.deleteButton.connect( this, { click: 'onDeleteButtonClick' } );

	this.editButton.setLabel( ve.msg( 'visualeditor-table-contextitem-properties' ) );
};

/* Inheritance */

OO.inheritClass( ve.ui.TableContextItem, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.TableContextItem.static.name = 'table';

ve.ui.TableContextItem.static.icon = 'table';

ve.ui.TableContextItem.static.label = OO.ui.deferMsg( 'visualeditor-toolbar-table' );

ve.ui.TableContextItem.static.commandName = 'table';

ve.ui.TableContextItem.static.embeddable = false;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.TableContextItem.static.isCompatibleWith = function ( model ) {
	return model instanceof ve.dm.Node && model.isCellable() && !OO.ui.isMobile();
};

/**
 * Handle delete button click events.
 */
ve.ui.TableContextItem.prototype.onDeleteButtonClick = function () {
	var surfaceModel = this.getFragment().getSurface();

	surfaceModel.getLinearFragment(
		surfaceModel.getSelectedNode().findParent( ve.dm.TableNode ).getOuterRange()
	).delete();

	ve.track( 'activity.table', { action: 'context-delete' } );
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.TableContextItem );
