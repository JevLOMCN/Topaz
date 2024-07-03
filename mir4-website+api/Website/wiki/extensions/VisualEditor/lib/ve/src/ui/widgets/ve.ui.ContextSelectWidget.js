/*!
 * VisualEditor Context Menu widget class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Menu of items, each an inspectable attribute of the current context.
 *
 * Use with ve.ui.ContextOptionWidget.
 *
 * @class
 * @extends OO.ui.SelectWidget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.ContextSelectWidget = function VeUiContextSelectWidget( config ) {
	// Config initialization
	config = config || {};

	// Parent constructor
	ve.ui.ContextSelectWidget.super.call( this, config );

	this.connect( this, { choose: 'onChooseItem' } );

	// Initialization
	this.$element.addClass( 've-ui-contextSelectWidget' );
};

/* Setup */

OO.inheritClass( ve.ui.ContextSelectWidget, OO.ui.SelectWidget );

/* Methods */

/**
 * Handle choose item events.
 */
ve.ui.ContextSelectWidget.prototype.onChooseItem = function () {
	// Auto-deselect
	this.selectItem( null );
};
