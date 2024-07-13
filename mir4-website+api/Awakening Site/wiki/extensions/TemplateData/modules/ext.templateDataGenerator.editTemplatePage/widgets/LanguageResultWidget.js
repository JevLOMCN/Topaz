/**
 * Creates a LanguageResultWidget object.
 * This is a copy of ve.ui.LanguageResultWidget
 *
 * @class
 * @extends OO.ui.OptionWidget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
function LanguageResultWidget( config ) {
	// Parent constructor
	LanguageResultWidget.parent.call( this, config );

	// Mixin constructors
	OO.ui.mixin.TabIndexedElement.call( this );

	// Events
	this.$element.on( 'keydown', this.onKeyDown.bind( this ) );

	// Initialization
	this.$element.addClass( 'tdg-languageResultWidget' );
	this.$name = $( '<div>' ).addClass( 'tdg-languageResultWidget-name' );
	this.$otherMatch = $( '<div>' ).addClass( 'tdg-languageResultWidget-otherMatch' );
	this.setLabel( this.$otherMatch.add( this.$name ) );
}

/* Inheritance */

OO.inheritClass( LanguageResultWidget, OO.ui.OptionWidget );
OO.mixinClass( LanguageResultWidget, OO.ui.mixin.TabIndexedElement );

/* Methods */

/**
 * @param {jQuery.Event} e Key down event
 * @fires choose
 */
LanguageResultWidget.prototype.onKeyDown = function ( e ) {
	if ( e.which === OO.ui.Keys.ENTER ) {
		this.emit( 'choose', this );
	}
};

/**
 * Update labels based on query
 *
 * @param {string} [query] Query text which matched this result
 * @param {string} [matchedProperty] Data property which matched the query text
 * @return {LanguageResultWidget}
 * @chainable
 */
LanguageResultWidget.prototype.updateLabel = function ( query, matchedProperty ) {
	var data = this.getData();

	// Reset text
	this.$name.text( data.name );
	this.$otherMatch.text( data.code );

	// Highlight where applicable
	if ( matchedProperty ) {
		var $highlighted = this.constructor.static.highlightQuery( data[ matchedProperty ], query );
		if ( matchedProperty === 'name' ) {
			this.$name.empty().append( $highlighted );
		} else {
			this.$otherMatch.empty().append( $highlighted );
		}
	}

	return this;
};

/**
 * Highlight text where a substring query matches
 *
 * Copied from ve#highlightQuery
 *
 * @param {string} text Text
 * @param {string} query Query to find
 * @return {jQuery} Text with query substring wrapped in highlighted span
 */
LanguageResultWidget.static.highlightQuery = function ( text, query ) {
	var $result = $( '<span>' ),
		offset = text.toLowerCase().indexOf( query.toLowerCase() );

	if ( !query.length || offset === -1 ) {
		return $result.text( text );
	}
	$result.append(
		document.createTextNode( text.slice( 0, offset ) ),
		$( '<span>' )
			.addClass( 'tdg-languageResultWidget-highlight' )
			.text( text.slice( offset, offset + query.length ) ),
		document.createTextNode( text.slice( offset + query.length ) )
	);
	return $result.contents();
};

module.exports = LanguageResultWidget;
