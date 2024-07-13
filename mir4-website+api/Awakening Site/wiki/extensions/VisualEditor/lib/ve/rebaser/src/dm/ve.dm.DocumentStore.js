/*!
 * VisualEditor document store class.
 *
 * @copyright See AUTHORS.txt
 */

'use strict';

/**
 * @constructor
 * @param {Object} storageClient MongoClient-like object; passed as a parameter for testing purposes
 * @param {string} dbName Database name
 * @param {Object} logger Logger class
 * @param {Function} logger.logServerEvent Stringify object argument to log, adding timestamp and server ID properties
 */
ve.dm.DocumentStore = function VeDmDocumentStore( storageClient, dbName, logger ) {
	this.storageClient = storageClient;
	this.dbName = dbName;
	this.logger = logger;
	this.db = null;
	this.collection = null;
	this.startForDoc = new Map();
	this.serverId = null;
};

/**
 * @return {Promise} Resolves when connected
 */
ve.dm.DocumentStore.prototype.connect = function () {
	const documentStore = this;
	return this.storageClient.connect().then( function ( client ) {
		const db = client.db( documentStore.dbName );
		documentStore.logger.logServerEvent( { type: 'DocumentStore#connected', dbName: documentStore.dbName }, 'info' );
		documentStore.db = db;
		documentStore.collection = db.collection( 'vedocstore' );
		return documentStore.collection.findOneAndUpdate(
			{ config: 'options' },
			{ $setOnInsert: { serverId: Math.random().toString( 36 ).slice( 2 ) } },
			{ upsert: true, returnDocument: 'after' }
		);
	} ).then( function ( result ) {
		documentStore.serverId = result.value.serverId;
	} );
};

/**
 * @return {Promise} Drops the entire database
 */
ve.dm.DocumentStore.prototype.dropDatabase = function () {
	this.logger.logServerEvent( { type: 'DocumentStore#dropDatabase', dbName: this.dbName }, 'info' );
	return this.db.dropDatabase();
};

/**
 * Load a document from storage (creating as empty if absent)
 *
 * @param {string} docName Name of the document
 * @return {Promise} Confirmed document history as a ve.dm.Change
 */
ve.dm.DocumentStore.prototype.load = function ( docName ) {
	const documentStore = this;
	return this.collection.findOneAndUpdate(
		{ docName: docName },
		{ $setOnInsert: { start: 0, transactions: [], stores: [] } },
		{ upsert: true, returnDocument: 'after' }
	).then( function ( result ) {
		const length = result.value.transactions.length || 0;
		documentStore.logger.logServerEvent( { type: 'DocumentStore#loaded', docName: docName, length: length } );
		documentStore.startForDoc.set( docName, result.value.start + length );
		return ve.dm.Change.static.deserialize( {
			start: 0,
			transactions: result.value.transactions,
			stores: result.value.stores,
			selections: {}
		}, true );
	} );
};

/**
 * Save a new change to storage
 *
 * @param {string} docName Name of the document
 * @param {ve.dm.Change} change The new change
 * @return {Promise} Resolves when saved
 */
ve.dm.DocumentStore.prototype.onNewChange = function ( docName, change ) {
	const documentStore = this,
		serializedChange = change.serialize( true ),
		expectedStart = this.startForDoc.get( docName ) || 0;

	if ( expectedStart !== serializedChange.start ) {
		return Promise.reject( 'Unmatched starts:', expectedStart, serializedChange.start );
	}
	this.startForDoc.set( docName, serializedChange.start + serializedChange.transactions.length );
	return this.collection.updateOne(
		{ docName: docName },
		{
			$push: {
				transactions: { $each: serializedChange.transactions },
				stores: { $each: serializedChange.stores || serializedChange.transactions.map( function () {
					return null;
				} ) }
			}
		}
	).then( function () {
		documentStore.logger.logServerEvent( {
			type: 'DocumentStore#onNewChange',
			docName: docName,
			start: serializedChange.start,
			length: serializedChange.transactions.length
		} );
	} );
};

ve.dm.DocumentStore.prototype.onClose = function () {
	this.logger.logServerEvent( { type: 'DocumentStore#onClose' }, 'info' );
	this.storageClient.close();
};
