/*!
 * VisualEditor UserInterface LanguageResultWidget class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Creates an ve.ui.LanguageResultWidget object.
 *
 * @class
 * @extends OO.ui.OptionWidget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.LanguageResultWidget = function VeUiLanguageResultWidget( config ) {
	// Parent constructor
	ve.ui.LanguageResultWidget.super.call( this, config );

	// Initialization
	this.$element.addClass( 've-ui-languageResultWidget' );
	this.name = new OO.ui.LabelWidget( { classes: [ 've-ui-languageResultWidget-name' ] } );
	this.otherMatch = new OO.ui.LabelWidget( { classes: [ 've-ui-languageResultWidget-otherMatch' ] } );
	this.setLabel( this.otherMatch.$element.add( this.name.$element ) );
};

/* Inheritance */

OO.inheritClass( ve.ui.LanguageResultWidget, OO.ui.OptionWidget );

/* Methods */

/**
 * Update labels based on query
 *
 * @param {string} query Query text which matched this result
 * @param {string} matchedProperty Data property which matched the query text
 * @param {Function} [compare] String comparator
 * @return {ve.ui.LanguageResultWidget}
 * @chainable
 */
ve.ui.LanguageResultWidget.prototype.updateLabel = function ( query, matchedProperty, compare ) {
	var data = this.getData();

	if ( matchedProperty === 'name' ) {
		this.name.setHighlightedQuery( data.name, query, compare );
	} else {
		this.name.setLabel( data.name );
	}
	if ( matchedProperty === 'code' || matchedProperty === 'autonym' ) {
		this.otherMatch.setHighlightedQuery( data[ matchedProperty ], query, compare );
	} else {
		this.otherMatch.setLabel( data.code );
	}

	return this;
};
