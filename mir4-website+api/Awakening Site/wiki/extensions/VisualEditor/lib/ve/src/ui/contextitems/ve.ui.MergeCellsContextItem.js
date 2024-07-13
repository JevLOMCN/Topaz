/*!
 * VisualEditor MergeCellsContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for mergeable cels.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config] Configuration options
 */
ve.ui.MergeCellsContextItem = function VeUiMergeCellsContextItem( context, model, config ) {
	// Parent constructor
	ve.ui.MergeCellsContextItem.super.call( this, context, model, config );

	this.dimensions = new OO.ui.LabelWidget( {
		classes: [ 've-ui-mergeCellsContextItem-dimensions' ]
	} );
	if ( this.context.isMobile() ) {
		// Use desktop-style label-only button, as otherwise the "edit" button
		// gets collapsed to just the edit icon.
		this.editButton
			.setIcon( null )
			.setInvisibleLabel( false );
	}

	// Initialization
	this.$element.addClass( 've-ui-mergeCellsContextItem' );
	this.$title.append( this.dimensions.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.MergeCellsContextItem, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.MergeCellsContextItem.static.name = 'mergeCells';

ve.ui.MergeCellsContextItem.static.icon = 'tableMergeCells';

ve.ui.MergeCellsContextItem.static.label = OO.ui.deferMsg( 'visualeditor-table-merge-cells' );

ve.ui.MergeCellsContextItem.static.commandName = 'mergeCells';

ve.ui.MergeCellsContextItem.static.embeddable = false;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MergeCellsContextItem.static.isCompatibleWith = function ( model ) {
	return model instanceof ve.dm.Node && model.isCellable();
};

/**
 * @inheritdoc
 */
ve.ui.MergeCellsContextItem.prototype.setup = function () {
	// Parent method
	ve.ui.MergeCellsContextItem.super.prototype.setup.apply( this, arguments );

	// If not disabled, selection must be table and spanning multiple matrix cells
	var selection = this.getFragment().getSurface().getSelection(),
		documentModel = this.getFragment().getDocument(),
		// There's some situations involving transclusion table cells which
		// can make us have a LinearSelection here, so make sure this will
		// work:
		isMergeable = ( selection instanceof ve.dm.TableSelection ) &&
			selection.isMergeable( documentModel ) &&
			!this.isReadOnly();

	if ( !isMergeable ) {
		// Ideally we would check this in isCompatibleWith, but only the model node is available there
		this.$element.detach();
	} else {
		this.dimensions.setLabel(
			ve.msg( 'visualeditor-table-selection-dimensions',
				selection.getColCount(),
				selection.getRowCount()
			)
		);
		this.editButton.setLabel(
			selection.isSingleCell( documentModel ) ?
				ve.msg( 'visualeditor-table-merge-cells-unmerge' ) :
				ve.msg( 'visualeditor-table-merge-cells-merge' )
		);
	}
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MergeCellsContextItem );
