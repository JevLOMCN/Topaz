/*!
 * VisualEditor UserInterface Plain text string transfer handler class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Plain text string transfer handler.
 *
 * @class
 * @extends ve.ui.DataTransferHandler
 *
 * @constructor
 * @param {ve.ui.Surface} surface
 * @param {ve.ui.DataTransferItem} item
 */
ve.ui.PlainTextStringTransferHandler = function VeUiPlainTextStringTransferHandler() {
	// Parent constructor
	ve.ui.PlainTextStringTransferHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.PlainTextStringTransferHandler, ve.ui.DataTransferHandler );

/* Static properties */

ve.ui.PlainTextStringTransferHandler.static.name = 'plainTextString';

ve.ui.PlainTextStringTransferHandler.static.types = [ 'text/plain' ];

ve.ui.PlainTextStringTransferHandler.static.handlesPaste = false;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.PlainTextStringTransferHandler.prototype.process = function () {
	this.resolve( this.item.getAsString() );
};

/* Registration */

ve.ui.dataTransferHandlerFactory.register( ve.ui.PlainTextStringTransferHandler );
