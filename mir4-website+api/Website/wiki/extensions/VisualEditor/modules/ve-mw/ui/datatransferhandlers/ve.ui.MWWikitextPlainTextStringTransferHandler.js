/*!
 * VisualEditor UserInterface MWWikitextPlainTextStringTransferHandler.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Detect an attempt to paste plain text or wikitext, and allow it to be directly pasted without escaping it.
 *
 * This handler is only registered in source mode, as that's the mode where users are interacting
 * with the plain-text equivalent of the content already. Without this handler, a paste with `text/plain`
 * and `text/html` content would take the html content, run it through the normal paste flow, then convert
 * the resultant HTML into wikitext via parsoid. This would have the side-effect of escaping any wikitext
 * content that's in the paste with nowiki, which probably isn't what the paster actually wants.
 *
 * We also catch anything which has `text/x-wiki`, since it has explicitly come from a source-mode part
 * of VE, and contains something that's definitely wikitext.
 *
 * @class
 * @extends ve.ui.PlainTextStringTransferHandler
 *
 * @constructor
 * @param {ve.ui.Surface} surface
 * @param {ve.ui.DataTransferItem} item
 */
ve.ui.MWWikitextPlainTextStringTransferHandler = function VeUiMWWikitextPlainTextStringTransferHandler() {
	// Parent constructor
	ve.ui.MWWikitextPlainTextStringTransferHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWWikitextPlainTextStringTransferHandler, ve.ui.PlainTextStringTransferHandler );

/* Static properties */

ve.ui.MWWikitextPlainTextStringTransferHandler.static.name = 'wikitextPlainTextString';

ve.ui.MWWikitextPlainTextStringTransferHandler.static.types =
	ve.ui.MWWikitextPlainTextStringTransferHandler.super.static.types.concat(
		[ 'text/x-wiki' ]
	);

ve.ui.MWWikitextPlainTextStringTransferHandler.static.handlesPaste = true;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWWikitextPlainTextStringTransferHandler.prototype.process = function () {
	this.resolve( this.item.getAsString() );
};

/* Registration */

ve.ui.wikitextDataTransferHandlerFactory.register( ve.ui.MWWikitextPlainTextStringTransferHandler );
