/*!
 * VisualEditor MWLatexContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Abstract context item for a node of a formula provided
 * by the Math extension.
 *
 * @abstract
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} config Configuration options
 */
ve.ui.MWLatexContextItem = function VeUiMWLatexContextItem() {
	// Parent constructor
	ve.ui.MWLatexContextItem.super.apply( this, arguments );

	this.quickEditButton = new OO.ui.ButtonWidget( {
		label: ve.msg( 'math-visualeditor-mwlatexcontextitem-quickedit' ),
		flags: [ 'progressive' ]
	} );

	// Don't show quick edit button in mobile as the primary action will be quick edit
	if ( !this.context.isMobile() && !this.isReadOnly() ) {
		this.actionButtons.addItems( [ this.quickEditButton ], 0 );
	}

	this.quickEditButton.connect( this, { click: 'onInlineEditButtonClick' } );

	// Initialization
	this.$element.addClass( 've-ui-mwLatexContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLatexContextItem, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.MWLatexContextItem.static.embeddable = false;

ve.ui.MWLatexContextItem.static.inlineEditCommand = null;

/* Methods */

/**
 * Handle inline edit button click events.
 */
ve.ui.MWLatexContextItem.prototype.onInlineEditButtonClick = function () {
	this.context.getSurface().executeCommand( this.constructor.static.inlineEditCommand );
};

/**
 * @inheritdoc
 */
ve.ui.MWLatexContextItem.prototype.getCommand = function () {
	return this.context.getSurface().commandRegistry.lookup(
		this.context.isMobile() ? this.constructor.static.inlineEditCommand : this.constructor.static.commandName
	);
};

/**
 * @inheritdoc
 */
ve.ui.MWLatexContextItem.prototype.getDescription = function () {
	return ve.ce.nodeFactory.getDescription( this.model );
};
