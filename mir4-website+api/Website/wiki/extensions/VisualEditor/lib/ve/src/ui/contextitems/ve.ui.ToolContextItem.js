/*!
 * VisualEditor ToolContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a tool.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Function} tool Tool class the item is based on
 * @param {Object} [config] Configuration options
 */
ve.ui.ToolContextItem = function VeUiToolContextItem( context, model, tool, config ) {
	// Parent constructor
	ve.ui.ToolContextItem.super.call( this, context, model, config );

	// Properties
	this.tool = tool;

	// Initialization
	this.setIcon( tool.static.icon );
	this.setLabel( tool.static.title );
	this.$element.addClass( 've-ui-toolContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.ToolContextItem, ve.ui.LinearContextItem );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.ToolContextItem.prototype.getCommand = function () {
	return this.tool.static.getCommand( this.context.getSurface() );
};

/**
 * Get a description of the model.
 *
 * @return {string} Description of model
 */
ve.ui.ToolContextItem.prototype.getDescription = function () {
	var description = '';

	if ( this.model instanceof ve.dm.Annotation ) {
		description = ve.ce.annotationFactory.getDescription( this.model );
	} else if ( this.model instanceof ve.dm.Node ) {
		description = ve.ce.nodeFactory.getDescription( this.model );
	}

	return description;
};
