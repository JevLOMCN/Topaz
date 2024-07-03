/*!
 * VisualEditor DataModel Fake mongo-like class for testing
 *
 * @copyright See AUTHORS.txt
 */

// Fake mongo class, with limited query API, that stores a single collection in memory
ve.dm.FakeMongo = function VeDmFakeMongo() {
	this.storedDataForDoc = {};
	this.dataForDoc = undefined;
	this.log = [];
};

OO.initClass( ve.dm.FakeMongo );

ve.dm.FakeMongo.prototype.connect = function () {
	this.dataForDoc = this.storedDataForDoc;
	return Promise.resolve( this );
};

ve.dm.FakeMongo.prototype.db = function () {
	return this;
};

ve.dm.FakeMongo.prototype.collection = function () {
	return this;
};

ve.dm.FakeMongo.prototype.dropDatabase = function () {
	this.storedDataForDoc = this.dataForDoc = {};
	return Promise.resolve();
};

ve.dm.FakeMongo.prototype.findOneAndUpdate = function ( search, update ) {
	var docName = search.docName;
	if ( !this.dataForDoc[ docName ] ) {
		this.dataForDoc[ docName ] = JSON.parse( JSON.stringify( update.$setOnInsert ) );
	}
	return Promise.resolve( { value: JSON.parse( JSON.stringify( this.dataForDoc[ docName ] ) ) } );
};

ve.dm.FakeMongo.prototype.updateOne = function ( search, update ) {
	var docName = search.docName,
		transactions = this.dataForDoc[ docName ].transactions,
		stores = this.dataForDoc[ docName ].stores;
	transactions.push.apply( transactions, update.$push.transactions.$each );
	stores.push.apply( stores, update.$push.stores.$each );
	return Promise.resolve();
};

ve.dm.FakeMongo.prototype.close = function () {
	this.dataForDoc = undefined;
	return Promise.resolve();
};

ve.dm.FakeMongo.prototype.logServerEvent = function ( ob ) {
	this.log.push( ob );
};
