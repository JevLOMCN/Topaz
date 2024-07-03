/*!
 * VisualEditor UserInterface data transfer handler class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Data transfer handler.
 *
 * @class
 * @extends ve.ui.DataTransferHandler
 * @abstract
 *
 * @constructor
 * @param {ve.ui.Surface} surface
 * @param {ve.ui.DataTransferItem} item
 */
ve.ui.FileTransferHandler = function VeUiFileTransferHandler() {
	// Parent constructor
	ve.ui.FileTransferHandler.super.apply( this, arguments );

	// Properties
	this.file = this.item.getAsFile();

	this.reader = new FileReader();

	// Events
	this.reader.addEventListener( 'progress', this.onFileProgress.bind( this ) );
	this.reader.addEventListener( 'load', this.onFileLoad.bind( this ) );
	this.reader.addEventListener( 'error', this.onFileError.bind( this ) );
};

/* Inheritance */

OO.inheritClass( ve.ui.FileTransferHandler, ve.ui.DataTransferHandler );

/* Static properties */

ve.ui.FileTransferHandler.static.kinds = [ 'file' ];

/**
 * List of file extensions supported by this handler
 *
 * This is used as a fallback if no types were matched.
 *
 * @static
 * @property {string[]}
 * @inheritable
 */
ve.ui.FileTransferHandler.static.extensions = [];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.FileTransferHandler.prototype.process = function () {
	this.createProgress( this.insertableDataDeferred.promise() );
	this.reader.readAsText( this.file );
};

/**
 * Handle progress events from the file reader
 *
 * @param {Event} e Progress event
 */
ve.ui.FileTransferHandler.prototype.onFileProgress = function ( e ) {
	if ( e.lengthComputable ) {
		this.setProgress( 100 * e.loaded / e.total );
	} else {
		this.setProgress( false );
	}
};

/**
 * Handle load events from the file reader
 *
 * @param {Event} e Load event
 */
ve.ui.FileTransferHandler.prototype.onFileLoad = function () {
	this.setProgress( 100 );
};

/**
 * Handle error events from the file reader
 *
 * @param {Event} e Error event
 */
ve.ui.FileTransferHandler.prototype.onFileError = function () {
	this.abort();
};

/**
 * @inheritdoc
 */
ve.ui.FileTransferHandler.prototype.abort = function () {
	// Parent method
	ve.ui.FileTransferHandler.super.prototype.abort.apply( this, arguments );

	this.reader.abort();
};

/**
 * Overrides the parent to make the default label the filename
 *
 * @param {jQuery.Promise} progressCompletePromise
 * @param {jQuery|string|Function} [label] Progress bar label, defaults to file name
 */
ve.ui.FileTransferHandler.prototype.createProgress = function ( progressCompletePromise, label ) {
	// Make the default label the filename
	label = label || this.file.name;

	// Parent method
	ve.ui.FileTransferHandler.super.prototype.createProgress.call( this, progressCompletePromise, label );
};
