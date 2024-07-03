/**
 * Data transfer item wrapper
 *
 * @class
 * @constructor
 * @param {string} kind Item kind, e.g. 'string' or 'file'
 * @param {string} type MIME type
 * @param {Object} [data] Data object to wrap or convert
 * @param {string} [data.dataUri] Data URI to convert to a blob
 * @param {Blob} [data.blob] File blob
 * @param {string} [data.stringData] String data
 * @param {string} [data.htmlStringData] HTML string data
 * @param {DataTransferItem} [data.item] Native data transfer item
 * @param {string} [name] Item's name, for types which support it, e.g. File
 */
ve.ui.DataTransferItem = function VeUiDataTransferItem( kind, type, data, name ) {
	this.kind = kind;
	this.type = type;
	this.data = data || {};
	this.blob = this.data.blob || null;
	this.stringData = this.data.stringData || ve.getProp( this.blob, 'name' ) || '';
	this.name = name;
};

/* Inheritance */

OO.initClass( ve.ui.DataTransferItem );

/* Static methods */

/**
 * Create a data transfer item from a file blob.
 *
 * @param {Blob} blob File blob
 * @param {string} [htmlStringData] HTML string representation of data transfer
 * @return {ve.ui.DataTransferItem} New data transfer item
 */
ve.ui.DataTransferItem.static.newFromBlob = function ( blob, htmlStringData ) {
	return new ve.ui.DataTransferItem( 'file', blob.type, { blob: blob, htmlStringData: htmlStringData }, blob.name );
};

/**
 * Create a data transfer item from a data URI.
 *
 * @param {string} dataUri Data URI
 * @param {string} [htmlStringData] HTML string representation of data transfer
 * @return {ve.ui.DataTransferItem} New data transfer item
 */
ve.ui.DataTransferItem.static.newFromDataUri = function ( dataUri, htmlStringData ) {
	var parts = dataUri.split( ',' );
	return new ve.ui.DataTransferItem( 'file', parts[ 0 ].match( /^data:([^;]+)/ )[ 1 ], { dataUri: parts[ 1 ], htmlStringData: htmlStringData } );
};

/**
 * Create a data transfer item from string data.
 *
 * @param {string} stringData Native string data
 * @param {string} [type] Native MIME type
 * @param {string} [htmlStringData] HTML string representation of data transfer
 * @return {ve.ui.DataTransferItem} New data transfer item
 */
ve.ui.DataTransferItem.static.newFromString = function ( stringData, type, htmlStringData ) {
	return new ve.ui.DataTransferItem( 'string', type || 'text/plain', { stringData: stringData, htmlStringData: htmlStringData } );
};

/**
 * Create a data transfer item from a native data transfer item.
 *
 * @param {DataTransferItem} item Native data transfer item
 * @param {string} [htmlStringData] HTML string representation of data transfer
 * @return {ve.ui.DataTransferItem} New data transfer item
 */
ve.ui.DataTransferItem.static.newFromItem = function ( item, htmlStringData ) {
	return new ve.ui.DataTransferItem( item.kind, item.type, { item: item, htmlStringData: htmlStringData }, item.getAsFile().name );
};

/**
 * Get file blob
 *
 * Generically getAsFile returns a Blob, which could be a File.
 *
 * @return {Blob} File blob
 */
ve.ui.DataTransferItem.prototype.getAsFile = function () {
	if ( this.data.item ) {
		return this.data.item.getAsFile();
	}

	if ( !this.blob && this.data.dataUri ) {
		var binary = atob( this.data.dataUri );
		delete this.data.dataUri;
		var array = [];
		for ( var i = 0; i < binary.length; i++ ) {
			array.push( binary.charCodeAt( i ) );
		}
		this.blob = new Blob(
			[ new Uint8Array( array ) ],
			{ type: this.type }
		);
	}
	return this.blob;
};

/**
 * Get the extension of the item's name
 *
 * @return {string|null} The extension of the item's name, or null if not present
 */
ve.ui.DataTransferItem.prototype.getExtension = function () {
	return this.name ? this.name.split( '.' ).pop() : null;
};

/**
 * Get string data
 *
 * Differs from native DataTransferItem#getAsString by being synchronous
 *
 * @return {string} String data
 */
ve.ui.DataTransferItem.prototype.getAsString = function () {
	return this.stringData;
};
