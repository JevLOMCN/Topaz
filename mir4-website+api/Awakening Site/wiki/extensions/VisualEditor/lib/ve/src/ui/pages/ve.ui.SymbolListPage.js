/*!
 * VisualEditor user interface SymbolListPage class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Special symbol toolbar dialog.
 *
 * @class
 * @extends OO.ui.PageLayout
 *
 * @constructor
 * @param {string} name Unique symbolic name of page
 * @param {Object} [config] Configuration options
 * @cfg {string} [label] Group label
 * @cfg {Object} [symbols] Symbol set
 * @cfg {Object} [attributes] Extra attributes for the group, e.g. `lang` & `dir`
 */
ve.ui.SymbolListPage = function VeUiSymbolListPage( name, config ) {
	// Parent constructor
	ve.ui.SymbolListPage.super.apply( this, arguments );

	this.label = config.label;

	var $symbols = $( '<div>' ).addClass( 've-ui-symbolListPage-symbols' );
	var symbolsNode = $symbols[ 0 ];

	// It is assumed this loop may contain hundreds or thousands of symbols,
	// so avoid jQuery and use plain DOM.
	config.symbols.forEach( function ( symbol ) {
		var symbolNode = document.createElement( 'div' );
		symbolNode.classList.add( 've-ui-symbolListPage-symbol' );
		if ( symbol.classes ) {
			DOMTokenList.prototype.add.apply( symbolNode.classList, symbol.classes );
		}
		if ( symbol.titleMsg ) {
			// eslint-disable-next-line mediawiki/msg-doc
			symbolNode.setAttribute( 'title', ve.msg( symbol.titleMsg ) );
		}
		symbolNode.textContent = Object.prototype.hasOwnProperty.call( symbol, 'label' ) ? symbol.label : symbol;

		$.data( symbolNode, 'symbol', symbol );

		symbolsNode.appendChild( symbolNode );
	} );

	if ( config.attributes ) {
		$symbols.attr( config.attributes );
	}

	this.$element
		.addClass( 've-ui-symbolListPage' )
		.append( $( '<h3>' ).text( this.label ), $symbols );
};

/* Inheritance */

OO.inheritClass( ve.ui.SymbolListPage, OO.ui.PageLayout );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.SymbolListPage.prototype.setupOutlineItem = function () {
	// Parent method
	ve.ui.SymbolListPage.super.prototype.setupOutlineItem.apply( this, arguments );

	this.outlineItem.setLabel( this.label );
};
