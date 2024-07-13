/*!
 * VisualEditor UserInterface plain text file transfer handler class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Plain text file transfer handler.
 *
 * @class
 * @extends ve.ui.FileTransferHandler
 *
 * @constructor
 * @param {ve.ui.Surface} surface
 * @param {ve.ui.DataTransferItem} item
 */
ve.ui.PlainTextFileTransferHandler = function VeUiPlainTextFileTransferHandler() {
	// Parent constructor
	ve.ui.PlainTextFileTransferHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.PlainTextFileTransferHandler, ve.ui.FileTransferHandler );

/* Static properties */

ve.ui.PlainTextFileTransferHandler.static.name = 'plainTextFile';

ve.ui.PlainTextFileTransferHandler.static.types = [ 'text/plain' ];

ve.ui.PlainTextFileTransferHandler.static.extensions = [ 'txt' ];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.PlainTextFileTransferHandler.prototype.onFileLoad = function () {
	this.resolve( this.reader.result );

	// Parent method
	ve.ui.PlainTextFileTransferHandler.super.prototype.onFileLoad.apply( this, arguments );
};

/* Registration */

ve.ui.dataTransferHandlerFactory.register( ve.ui.PlainTextFileTransferHandler );
