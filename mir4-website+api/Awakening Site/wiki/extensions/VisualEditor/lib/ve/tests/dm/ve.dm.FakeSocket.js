/*!
 * VisualEditor DataModel Fake socket.io-like class for testing
 *
 * @copyright See AUTHORS.txt
 */

// Fake socket class, with limited API
ve.dm.FakeSocket = function VeDmFakeSocket( server, query ) {
	server.sockets.sockets.push( this );
	this.handlers = new Map();
	this.rooms = new Set();
	this.pending = [];
	this.handshake = { query: query, url: 'fakeurl' };
	this.client = { conn: { remoteAddress: 'fakeaddress' } };
	this.storedDataForDoc = {};
	this.dataForDoc = undefined;
	this.log = [];
};

OO.initClass( ve.dm.FakeSocket );

ve.dm.FakeSocket.static.makeServer = function () {
	var sockets = [],
		log = [];

	var getRoom = function ( roomName ) {
		return { emit: function () {
			var i, socket;
			for ( i = 0; i < sockets.length; i++ ) {
				socket = sockets[ i ];
				if ( socket.rooms.has( roomName ) ) {
					socket.emit.apply( socket, arguments );
				}
			}
		} };
	};
	var reset = function () {
		sockets.length = 0;
		log.length = 0;
	};
	return { sockets: { sockets: sockets, log: log, in: getRoom, reset: reset } };
};

ve.dm.FakeSocket.prototype.join = function ( roomName ) {
	this.rooms.add( roomName );
};

ve.dm.FakeSocket.prototype.emit = function ( eventName ) {
	var args = Array.from( arguments ).slice( 1 ),
		handlers = this.handlers.get( eventName ) || [];
	for ( var i = 0; i < handlers.length; i++ ) {
		this.pending.push( handlers[ i ].apply( null, args ) );
	}
};

ve.dm.FakeSocket.prototype.on = function ( eventName, handler ) {
	if ( !this.handlers.has( eventName ) ) {
		this.handlers.set( eventName, [] );
	}
	this.handlers.get( eventName ).push( handler );
};

ve.dm.FakeSocket.prototype.receive = function ( eventName ) {
	var args = Array.from( arguments ).slice( 1 ),
		handlers = this.handlers.get( eventName ) || [];
	for ( var i = 0; i < handlers.length; i++ ) {
		this.pending.push( handlers[ i ].apply( null, args ) );
	}
};

ve.dm.FakeSocket.prototype.wait = function () {
	return Promise.all( this.pending );
};
