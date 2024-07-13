var LanguageResultWidget = require( './LanguageResultWidget.js' );

/**
 * Creates a TemplateDataLanguageSearchWidget object.
 * This is a copy of ve.ui.LanguageSearchWidget.
 *
 * @class
 * @extends OO.ui.SearchWidget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
function LanguageSearchWidget( config ) {
	// Configuration initialization
	config = $.extend( {
		placeholder: mw.msg( 'templatedata-modal-search-input-placeholder' )
	}, config );

	// Parent constructor
	LanguageSearchWidget.parent.call( this, config );

	// Properties
	this.filteredLanguageResultWidgets = [];
	var languageCodes = Object.keys( $.uls.data.getAutonyms() ).sort();
	this.languageResultWidgets = languageCodes.map( function ( languageCode ) {
		return new LanguageResultWidget( {
			data: {
				code: languageCode,
				name: $.uls.data.getAutonym( languageCode ),
				autonym: $.uls.data.getAutonym( languageCode )
			}
		} );
	} );
	this.setAvailableLanguages();

	// Initialization
	this.$element.addClass( 'tdg-languageSearchWidget' );
}

/* Inheritance */

OO.inheritClass( LanguageSearchWidget, OO.ui.SearchWidget );

/* Methods */

/**
 * FIXME: this should be inheritdoc
 */
LanguageSearchWidget.prototype.onQueryChange = function () {
	// Parent method
	LanguageSearchWidget.parent.prototype.onQueryChange.apply( this, arguments );

	// Populate
	this.addResults();
};

/**
 * Set available languages to show
 *
 * @param {string[]} [availableLanguages] Available language codes to show, all if undefined
 */
LanguageSearchWidget.prototype.setAvailableLanguages = function ( availableLanguages ) {
	if ( !availableLanguages ) {
		this.filteredLanguageResultWidgets = this.languageResultWidgets.slice();
		return;
	}

	this.filteredLanguageResultWidgets = this.languageResultWidgets.map( function ( languageResult ) {
		var data = languageResult.getData();
		if ( availableLanguages.indexOf( data.code ) !== -1 ) {
			return languageResult;
		}
		return null;
	} ).filter( function ( languageResult ) {
		return languageResult;
	} );
};

/**
 * Update search results from current query
 */
LanguageSearchWidget.prototype.addResults = function () {
	var matchProperties = [ 'name', 'autonym', 'code' ],
		query = this.query.getValue().trim(),
		compare = window.Intl && Intl.Collator ?
			new Intl.Collator( this.lang, { sensitivity: 'base' } ).compare :
			function ( a, b ) {
				return a.toLowerCase() === b.toLowerCase() ? 0 : 1;
			},
		hasQuery = !!query.length,
		items = [];

	var results = this.getResults();
	results.clearItems();

	this.filteredLanguageResultWidgets.forEach( function ( languageResult ) {
		var data = languageResult.getData();
		var matchedProperty = null;

		matchProperties.some( function ( prop ) {
			if ( data[ prop ] && compare( data[ prop ].slice( 0, query.length ), query ) === 0 ) {
				matchedProperty = prop;
				return true;
			}
			return false;
		} );

		if ( query === '' || matchedProperty ) {
			items.push(
				languageResult
					.updateLabel( query, matchedProperty, compare )
					.setSelected( false )
					.setHighlighted( false )
					// Forward keyboard-triggered events from the OptionWidget to the SelectWidget
					.off( 'choose' )
					.connect( results, { choose: [ 'emit', 'choose' ] } )
			);
		}
	} );

	results.addItems( items );
	if ( hasQuery ) {
		results.highlightItem( results.findFirstSelectableItem() );
	}
};

module.exports = LanguageSearchWidget;
