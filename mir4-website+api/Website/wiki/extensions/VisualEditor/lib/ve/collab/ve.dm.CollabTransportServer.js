/*!
 * VisualEditor collab transport server class.
 *
 * @copyright See AUTHORS.txt
 */

'use strict';

/**
 * Transport server for Socket IO transport
 *
 * @constructor
 * @param {number} startHeight Length of shared completeHistory
 */
ve.dm.CollabTransportServer = function VeDmCollabTransportServer( startHeight ) {
	const startTimestamp = Date.now();
	this.startHeight = startHeight;
	this.protocolServer = new ve.dm.ProtocolServer(
		{
			startHeight: startHeight,
			// The server ID is arbitrary
			serverId: 've-collab-server',
			load: function () {
				return Promise.resolve(
					ve.dm.Change.static.deserialize( { transactions: [] } )
				);
			},
			onNewChange: function () {
				return Promise.resolve();
			}
		},
		{
			/* eslint-disable-next-line no-console */
			logServerEvent: ( x ) => console.log( x ),
			/* eslint-disable-next-line no-console */
			logEvent: ( x ) => console.log( x ),
			getRelativeTimestamp: () => Date.now() - startTimestamp
		}
	);
	this.connections = [];
};

OO.initClass( ve.dm.CollabTransportServer );

/**
 * Generic connection handler
 *
 * @param {Object} conn The connection
 */
ve.dm.CollabTransportServer.prototype.onConnection = function ( conn ) {
	const context = this.protocolServer.authenticate( 've-collab-doc', null, null ),
		connections = this.connections,
		server = this.protocolServer,
		startHeight = this.startHeight;

	connections.push( conn );

	context.broadcast = function ( type, data ) {
		const serialized = ve.collab.serialize( data );
		connections.forEach( function ( connection ) {
			connection.send( { type: type, data: serialized } );
		} );
	};
	context.sendAuthor = function ( type, data ) {
		const serialized = ve.collab.serialize( data );
		conn.send( { type: type, data: serialized } );
	};
	conn.on( 'data', function ( data ) {
		const type = data.type;
		if ( type === 'submitChange' ) {
			server.onSubmitChange( context, data.data );
		} else if ( type === 'changeAuthor' ) {
			server.onChangeAuthor( context, data.data );
		} else if ( type === 'disconnect' ) {
			server.onDisconnect( context, data.data );
		} else if ( type === 'logEvent' ) {
			// do nothing
		} else {
			throw new Error( 'Unknown type "' + type + '"' );
		}
	} );
	conn.on( 'open', function () {
		server.welcomeClient( context, startHeight );
	} );
};
