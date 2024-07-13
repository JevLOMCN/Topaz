/*!
 * VisualEditor MWWikitextPasteContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item shown after a rich text paste.
 *
 * @class
 * @extends ve.ui.PersistentContextItem
 *
 * @constructor
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {Object} [data] Extra data
 * @param {Object} [config]
 */
ve.ui.MWWikitextPasteContextItem = function VeUiMWWikitextPasteContextItem() {
	// Parent constructor
	ve.ui.MWWikitextPasteContextItem.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwWikitextPasteContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWWikitextPasteContextItem, ve.ui.PersistentContextItem );

/* Static Properties */

ve.ui.MWWikitextPasteContextItem.static.name = 'wikitextPaste';

ve.ui.MWWikitextPasteContextItem.static.icon = 'wikiText';

ve.ui.MWWikitextPasteContextItem.static.label = OO.ui.deferMsg( 'visualeditor-wikitextconvert-title' );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWWikitextPasteContextItem.prototype.renderBody = function () {
	var fragment = this.data.fragment,
		doc = this.data.doc,
		contextRange = this.data.contextRange;

	var convertButton = new OO.ui.ButtonWidget( {
		label: ve.msg( 'visualeditor-wikitextconvert-convert' ),
		flags: [ 'progressive' ]
	} ).on( 'click', function () {
		fragment.insertDocument( doc, contextRange ).getPending().then( function () {
			fragment.collapseToEnd().select();
		} );
		// TODO: Show something if the promise (conversion) fails?
	} );

	this.$body.append(
		$( '<p>' ).text( ve.msg( 'visualeditor-wikitextconvert-message' ) )
	);

	if ( this.$foot ) {
		this.$foot.prepend( convertButton.$element );
	} else {
		this.$body.append( convertButton.$element );
	}
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWWikitextPasteContextItem );
