/*!
 * VisualEditor MediaWiki UserInterface media transfer handler class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Media transfer handler.
 *
 * @class
 * @extends ve.ui.DataTransferHandler
 *
 * @constructor
 * @param {ve.ui.Surface} surface
 * @param {ve.ui.DataTransferItem} item
 */
ve.ui.MWMediaTransferHandler = function VeUiMWMediaTransferHandler() {
	// Parent constructor
	ve.ui.MWMediaTransferHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMediaTransferHandler, ve.ui.DataTransferHandler );

/* Static properties */

ve.ui.MWMediaTransferHandler.static.name = 'media';

ve.ui.MWMediaTransferHandler.static.kinds = [ 'file' ];

// TODO: Pull available types and extensions from MW config
ve.ui.MWMediaTransferHandler.static.types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/svg+xml' ];

ve.ui.MWMediaTransferHandler.static.extensions = [ 'jpg', 'jpeg', 'png', 'gif', 'svg' ];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWMediaTransferHandler.prototype.process = function () {
	var file = this.item.getAsFile();

	var action = ve.ui.actionFactory.create( 'window', this.surface );
	action.open( 'media', { file: file } );

	this.insertableDataDeferred.reject();
};

/* Registration */

ve.ui.dataTransferHandlerFactory.register( ve.ui.MWMediaTransferHandler );
