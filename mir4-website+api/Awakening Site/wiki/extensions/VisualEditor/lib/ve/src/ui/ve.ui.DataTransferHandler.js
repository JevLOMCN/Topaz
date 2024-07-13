/*!
 * VisualEditor UserInterface data transfer handler class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Data transfer handler.
 *
 * @class
 * @abstract
 *
 * @constructor
 * @param {ve.ui.Surface} surface
 * @param {ve.ui.DataTransferItem} item Data transfer item to handle
 */
ve.ui.DataTransferHandler = function VeUiDataTransferHandler( surface, item ) {
	// Properties
	this.surface = surface;
	this.item = item;
	this.progress = false;
	this.progressBar = null;

	this.insertableDataDeferred = ve.createDeferred();
};

/* Inheritance */

OO.initClass( ve.ui.DataTransferHandler );

/* Static properties */

/**
 * Symbolic name for this handler. Must be unique.
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.ui.DataTransferHandler.static.name = null;

/**
 * List of transfer kinds supported by this handler
 *
 * Null means all kinds are supported.
 *
 * @static
 * @property {string[]|null}
 * @inheritable
 */
ve.ui.DataTransferHandler.static.kinds = null;

/**
 * List of mime types supported by this handler
 *
 * @static
 * @property {string[]}
 * @inheritable
 */
ve.ui.DataTransferHandler.static.types = [];

/**
 * Use handler when data transfer source is a paste
 *
 * @static
 * @type {boolean}
 * @inheritable
 */
ve.ui.DataTransferHandler.static.handlesPaste = true;

/**
 * Use handler when data transfer source is a "paste special"
 *
 * @static
 * @type {boolean}
 * @inheritable
 */
ve.ui.DataTransferHandler.static.handlesPasteSpecial = false;

/**
 * Custom match function which is given the data transfer item as its only argument
 * and returns a boolean indicating if the handler matches
 *
 * Null means the handler always matches
 *
 * @static
 * @type {Function}
 * @inheritable
 */
ve.ui.DataTransferHandler.static.matchFunction = null;

/* Methods */

/**
 * Process the file
 *
 * Implementations should aim to resolve this.insertableDataDeferred.
 *
 * @abstract
 * @method
 */
ve.ui.DataTransferHandler.prototype.process = null;

/**
 * Insert the file at a specified fragment
 *
 * @return {jQuery.Promise} Promise which resolves with data to insert
 */
ve.ui.DataTransferHandler.prototype.getInsertableData = function () {
	this.process();

	return this.insertableDataDeferred.promise();
};

/**
 * Resolve the data transfer handler with some data
 *
 * @param {ve.dm.Document|string|Array} dataOrDoc Insertable data or document
 */
ve.ui.DataTransferHandler.prototype.resolve = function ( dataOrDoc ) {
	this.insertableDataDeferred.resolve( dataOrDoc );
};

/**
 * Abort the data transfer handler
 */
ve.ui.DataTransferHandler.prototype.abort = function () {
	this.insertableDataDeferred.reject();
};

/**
 * Create a progress bar with a specified label
 *
 * @param {jQuery.Promise} progressCompletePromise Promise which resolves when the progress action is complete
 * @param {jQuery|string|Function} label Progress bar label
 */
ve.ui.DataTransferHandler.prototype.createProgress = function ( progressCompletePromise, label ) {
	var handler = this;

	this.surface.createProgress( progressCompletePromise, label ).done( function ( progressBar, cancelPromise ) {
		// Set any progress that was achieved before this resolved
		progressBar.setProgress( handler.progress );
		handler.progressBar = progressBar;
		cancelPromise.fail( handler.abort.bind( handler ) );
	} );
};

/**
 * Set progress bar progress
 *
 * Progress is stored in a property in case the progress bar doesn't exist yet.
 *
 * @param {number} progress Progress percent
 */
ve.ui.DataTransferHandler.prototype.setProgress = function ( progress ) {
	this.progress = progress;
	if ( this.progressBar ) {
		this.progressBar.setProgress( this.progress );
	}
};
