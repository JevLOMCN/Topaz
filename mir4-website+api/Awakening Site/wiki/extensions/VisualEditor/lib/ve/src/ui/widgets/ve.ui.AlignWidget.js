/*!
 * VisualEditor UserInterface AlignWidget class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Widget that lets the user edit alignment of an object
 *
 * @class
 * @extends OO.ui.ButtonSelectWidget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {string} [dir='ltr'] Interface directionality
 */
ve.ui.AlignWidget = function VeUiAlignWidget( config ) {
	config = config || {};

	// Parent constructor
	ve.ui.AlignWidget.super.call( this, config );

	var alignButtons = [
		new OO.ui.ButtonOptionWidget( {
			data: 'left',
			icon: 'alignLeft',
			label: ve.msg( 'visualeditor-align-widget-left' )
		} ),
		new OO.ui.ButtonOptionWidget( {
			data: 'center',
			icon: 'alignCenter',
			label: ve.msg( 'visualeditor-align-widget-center' )
		} ),
		new OO.ui.ButtonOptionWidget( {
			data: 'right',
			icon: 'alignRight',
			label: ve.msg( 'visualeditor-align-widget-right' )
		} )
	];

	if ( config.dir === 'rtl' ) {
		alignButtons = alignButtons.reverse();
	}

	this.addItems( alignButtons, 0 );

};

/* Inheritance */

OO.inheritClass( ve.ui.AlignWidget, OO.ui.ButtonSelectWidget );
