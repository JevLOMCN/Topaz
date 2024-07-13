/*!
 * VisualEditor UserInterface HTML file transfer handler class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * HTML file transfer handler.
 *
 * @class
 * @extends ve.ui.FileTransferHandler
 *
 * @constructor
 * @param {ve.ui.Surface} surface
 * @param {ve.ui.DataTransferItem} item
 */
ve.ui.HTMLFileTransferHandler = function VeUiHTMLFileTransferHandler() {
	// Parent constructor
	ve.ui.HTMLFileTransferHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.HTMLFileTransferHandler, ve.ui.FileTransferHandler );

/* Static properties */

ve.ui.HTMLFileTransferHandler.static.name = 'htmlFile';

ve.ui.HTMLFileTransferHandler.static.types = [ 'text/html', 'application/xhtml+xml' ];

ve.ui.HTMLFileTransferHandler.static.extensions = [ 'html', 'htm', 'xhtml' ];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.HTMLFileTransferHandler.prototype.onFileLoad = function () {
	this.resolve(
		this.surface.getModel().getDocument().newFromHtml( this.reader.result, this.surface.getImportRules() )
	);

	// Parent method
	ve.ui.HTMLFileTransferHandler.super.prototype.onFileLoad.apply( this, arguments );
};

/* Registration */

ve.ui.dataTransferHandlerFactory.register( ve.ui.HTMLFileTransferHandler );
