/**
 * TemplateData parameter select widget
 *
 * @class
 * @extends OO.ui.SelectWidget
 * @mixes OO.ui.mixin.DraggableGroupElement
 *
 * @param {Object} [config] Dialog configuration object
 */
function ParamSelectWidget( config ) {
	// Parent constructor
	ParamSelectWidget.parent.call( this, config );

	// Mixin constructors
	OO.ui.mixin.DraggableGroupElement.call( this, $.extend( {}, config, { $group: this.$element } ) );

	// Initialize
	this.$element.addClass( 'tdg-templateDataParamSelectWidget' );
}

/* Inheritance */

OO.inheritClass( ParamSelectWidget, OO.ui.SelectWidget );

OO.mixinClass( ParamSelectWidget, OO.ui.mixin.DraggableGroupElement );

ParamSelectWidget.prototype.onMouseDown = function ( e ) {
	if ( $( e.target ).closest( '.oo-ui-draggableElement-handle' ).length || e.shiftKey ) {
		return true;
	}
	return ParamSelectWidget.parent.prototype.onMouseDown.apply( this, arguments );
};

module.exports = ParamSelectWidget;
