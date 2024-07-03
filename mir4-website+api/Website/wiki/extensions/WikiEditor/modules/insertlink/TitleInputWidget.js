var InsertLinkTitleOptionWidget = require( './TitleOptionWidget.js' );

/**
 * A custom TitleInputWidget that adds support for external links
 * (any string that starts with a protocol or `www.` and doesn't exist as a page).
 *
 * @class
 * @extends mw.widgets.TitleInputWidget
 * @constructor
 */
function TitleInputWidget() {
	TitleInputWidget.super.call( this, {
		showImages: true,
		showDescriptions: true,
		showDisambigsLast: true,
		placeholder: mw.msg( 'wikieditor-toolbar-tool-link-int-target-tooltip' ),
		$overlay: this.getOverlay(),
		validateTitle: false,
		showInterwikis: true,
		required: true,
		addQueryInput: true
	} );
}

OO.inheritClass( TitleInputWidget, mw.widgets.TitleInputWidget );

/**
 * Regular expression for determining what might be an external link.
 *
 * @static
 * @property {RegExp}
 */
TitleInputWidget.static.urlRegex = new RegExp( '^(' + mw.config.get( 'wgUrlProtocols' ) + '|www\\.)', 'i' );

/**
 * When leaving the input without selecting a menu item,
 * automatically select a matching item if there is one.
 * Even though we specify addQueryInput=true in the config, the entered string
 * is not always available to be selected. See T291056.
 */
TitleInputWidget.prototype.onLookupInputBlur = function () {
	TitleInputWidget.super.prototype.onLookupInputBlur.apply( this );
	this.selectFirstMatch();
};

/**
 * Select the first matching search result
 * The first match might not be at the top of the list, nor an exact match.
 *
 * @public
 */
TitleInputWidget.prototype.selectFirstMatch = function () {
	var that = this;
	this.getLookupMenuItems().done( function ( items ) {
		// The matching item is not always the first,
		// because disambiguation pages are moved to the end.
		for ( var i = 0; i < items.length; i++ ) {
			var item = items[ i ];
			var queryVal = that.getQueryValue();
			// Check for exact match, or a match with uppercase first character.
			if ( item.getData() === queryVal ||
				item.getData() === queryVal.charAt( 0 ).toUpperCase() + queryVal.slice( 1 )
			) {
				// If a matching title is is found, fire an event and stop looking.
				that.emit( 'select', item );
				break;
			}
		}
	} );
};

/**
 * Get menu option widget data from the title and page data,
 * adding an `external` property.
 *
 * @param {string} title Page title
 * @param {Object} data Page data
 * @return {Object} Data for option widget
 */
TitleInputWidget.prototype.getOptionWidgetData = function ( title, data ) {
	var widgetData = TitleInputWidget.super.prototype.getOptionWidgetData.call( this, title, data );
	widgetData.external = data.originalData.external;
	return widgetData;
};

/**
 * Create a InsertLinkTitleOptionWidget.
 *
 * @param {Object} data Data for option widget
 * @return {OO.ui.MenuOptionWidget} The option widget
 */
TitleInputWidget.prototype.createOptionWidget = function ( data ) {
	return new InsertLinkTitleOptionWidget( data );
};

/**
 * Get pages' data from the API response, adding an `external` property for
 * pages that do not exist and which look like external URLs.
 *
 * @param {Object} response
 * @return {Object}
 */
TitleInputWidget.prototype.getLookupCacheDataFromResponse = function ( response ) {
	var res = TitleInputWidget.super.prototype.getLookupCacheDataFromResponse( response );
	// Guard against zero responses.
	if ( res.pages === undefined ) {
		return res;
	}
	for ( var pageId in res.pages ) {
		if ( Object.prototype.hasOwnProperty.call( res.pages, pageId ) ) {
			var page = res.pages[ pageId ];
			page.external = page.missing !== undefined && this.looksLikeExternalLink( page.title );
		}
	}
	return res;
};

/**
 * Handle menu item 'choose' event, updating the text input value to the value of the clicked item.
 *
 * @param {OO.ui.MenuOptionWidget} item Selected item
 */
TitleInputWidget.prototype.onLookupMenuChoose = function ( item ) {
	TitleInputWidget.super.prototype.onLookupMenuChoose.call( this, item );
	this.emit( 'select', item );
};

/**
 * Get a custom overlay for the dropdown menu, so it's not contained within the jQuery UI dialog.
 *
 * @private
 * @return {jQuery}
 */
TitleInputWidget.prototype.getOverlay = function () {
	// Overlay z-index must be greater than the jQuery UI dialog's of 1002.
	return OO.ui.getDefaultOverlay()
		.clone()
		.css( 'z-index', '1010' )
		.appendTo( document.body );
};

/**
 * Determine if a given string is likely to be an external URL.
 * External URLs start with either a valid protocol (from $wgUrlProtocols) or `www.`.
 *
 * @public
 * @param {string} urlString The possible URL.
 * @return {boolean}
 */
TitleInputWidget.prototype.looksLikeExternalLink = function ( urlString ) {
	return this.constructor.static.urlRegex.test( urlString );
};

module.exports = TitleInputWidget;
