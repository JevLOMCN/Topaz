/*!
 * VisualEditor Context Item widget class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Proxy for a tool, displaying information about the current context.
 *
 * Use with ve.ui.ContextSelectWidget.
 *
 * @class
 * @extends OO.ui.DecoratedOptionWidget
 *
 * @constructor
 * @param {Function} tool Tool item is a proxy for
 * @param {ve.dm.Node|ve.dm.Annotation} model Node or annotation item is related to
 * @param {Object} [config] Configuration options
 */
ve.ui.ContextOptionWidget = function VeUiContextOptionWidget( tool, model, config ) {
	// Config initialization
	config = config || {};

	// Parent constructor
	ve.ui.ContextOptionWidget.super.call( this, config );

	// Properties
	this.tool = tool;
	this.model = model;

	// Initialization
	this.$element.addClass( 've-ui-contextOptionWidget' );
	this.setIcon( this.tool.static.icon );

	this.setLabel( this.getDescription() );
};

/* Setup */

OO.inheritClass( ve.ui.ContextOptionWidget, OO.ui.DecoratedOptionWidget );

/* Methods */

/**
 * Get a description of the model.
 *
 * @return {string} Description of model
 */
ve.ui.ContextOptionWidget.prototype.getDescription = function () {
	var description;

	if ( this.model instanceof ve.dm.Annotation ) {
		description = ve.ce.annotationFactory.getDescription( this.model );
	} else if ( this.model instanceof ve.dm.Node ) {
		description = ve.ce.nodeFactory.getDescription( this.model );
	}
	if ( !description ) {
		description = this.tool.static.title;
	}

	return description;
};

/**
 * Get the command for this item.
 *
 * @return {ve.ui.Command}
 */
ve.ui.ContextOptionWidget.prototype.getCommand = function () {
	return this.tool.static.getCommand( this.context.getSurface() );
};
