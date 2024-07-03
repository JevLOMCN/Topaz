/*!
 * VisualEditor UserInterface HTML string transfer handler class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * HTML string transfer handler.
 *
 * @class
 * @extends ve.ui.DataTransferHandler
 *
 * @constructor
 * @param {ve.ui.Surface} surface
 * @param {ve.ui.DataTransferItem} item
 */
ve.ui.HTMLStringTransferHandler = function VeUiHTMLStringTransferHandler() {
	// Parent constructor
	ve.ui.HTMLStringTransferHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.HTMLStringTransferHandler, ve.ui.DataTransferHandler );

/* Static properties */

ve.ui.HTMLStringTransferHandler.static.name = 'htmlString';

ve.ui.HTMLStringTransferHandler.static.types = [ 'text/html', 'application/xhtml+xml' ];

ve.ui.HTMLStringTransferHandler.static.handlesPaste = false;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.HTMLStringTransferHandler.prototype.process = function () {
	this.resolve(
		this.surface.getModel().getDocument().newFromHtml( this.item.getAsString(), this.surface.getImportRules() )
	);
};

/* Registration */

ve.ui.dataTransferHandlerFactory.register( ve.ui.HTMLStringTransferHandler );
