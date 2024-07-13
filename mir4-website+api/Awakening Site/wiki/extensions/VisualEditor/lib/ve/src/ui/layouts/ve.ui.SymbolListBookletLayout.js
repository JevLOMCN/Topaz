/*!
 * VisualEditor UserInterface SymbolListBookletLayout class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ...
 *
 * @class
 * @extends OO.ui.BookletLayout
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.SymbolListBookletLayout = function VeUiSymbolListBookletLayout( config ) {
	config = config || {};

	// Parent constructor
	ve.ui.SymbolListBookletLayout.super.call( this, ve.extendObject( {
		outlined: true,
		continuous: true
	}, config ) );

	this.$element.addClass( 've-ui-symbolListBookletLayout' );
};

/* Inheritance */

OO.inheritClass( ve.ui.SymbolListBookletLayout, OO.ui.BookletLayout );

/* Methods */

/**
 * Set symbol data
 *
 * @param {Object} symbolData
 */
ve.ui.SymbolListBookletLayout.prototype.setSymbolData = function ( symbolData ) {
	var pages = [];
	for ( var category in symbolData ) {
		pages.push(
			new ve.ui.SymbolListPage( category, symbolData[ category ] )
		);
	}
	this.addPages( pages );
	this.$element.on(
		'click',
		'.ve-ui-symbolListPage-symbol',
		this.onListClick.bind( this )
	);
};

/**
 * Handle the click event on the list
 *
 * @param {jQuery.Event} e Mouse click event
 */
ve.ui.SymbolListBookletLayout.prototype.onListClick = function ( e ) {
	var symbol = $( e.target ).data( 'symbol' );

	if ( symbol ) {
		this.emit( 'choose', symbol );
	}
};
