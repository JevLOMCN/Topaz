/**
 * TemplateData Param Import Widget
 *
 * @class
 * @extends OO.ui.ButtonWidget
 * @param {Object} [config]
 */
function ParamImportWidget( config ) {
	config = config || {};

	// Parent constructor
	ParamImportWidget.parent.call( this, $.extend( {
		icon: 'parameter-set'
	}, config ) );

	// Initialize
	this.$element.addClass( 'tdg-templateDataParamImportWidget' );
}

/* Inheritance */

OO.inheritClass( ParamImportWidget, OO.ui.ButtonWidget );

/**
 * Build the parameter label in the parameter select widget
 *
 * @param {string[]} params Param names
 */
ParamImportWidget.prototype.buildParamLabel = function ( params ) {
	var paramNames = params.slice( 0, 9 ).join( mw.msg( 'comma-separator' ) ),
		$paramCount = $( '<div>' )
			.addClass( 'tdg-templateDataParamWidget-param-name' ),
		$paramNames = $( '<div>' )
			.addClass( 'tdg-templateDataParamWidget-param-description' );

	$paramCount.text( mw.msg( 'templatedata-modal-table-param-importoption', params.length ) );
	$paramNames.text( mw.msg( 'templatedata-modal-table-param-importoption-subtitle', paramNames ) );

	this.setLabel( $paramCount.add( $paramNames ) );
};

module.exports = ParamImportWidget;
