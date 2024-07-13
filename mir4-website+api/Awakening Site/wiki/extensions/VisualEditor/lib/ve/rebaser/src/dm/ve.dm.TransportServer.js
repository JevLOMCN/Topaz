/*!
 * VisualEditor DataModel transport server class.
 *
 * @copyright See AUTHORS.txt
 */

'use strict';

/**
 * Transport server for Socket IO transport
 *
 * @constructor
 * @param {ve.dm.ProtocolServer} protocolServer The protocol server
 */
ve.dm.TransportServer = function VeDmTransportServer( protocolServer ) {
	this.protocolServer = protocolServer;
};

OO.initClass( ve.dm.TransportServer );

/**
 * Generic connection handler
 *
 * This just creates a namespace handler for the docName, if one does not already exist
 *
 * @param {Function} getRoom One-argument function taking docName, returning the corresponding room
 * @param {Object} socket The io socket
 * @return {Promise}
 */
ve.dm.TransportServer.prototype.onConnection = function ( getRoom, socket ) {
	const server = this.protocolServer,
		docName = socket.handshake.query.docName,
		authorId = +socket.handshake.query.authorId || null,
		token = socket.handshake.query.token || null;

	/**
	 * Ensure the doc is loaded when calling f
	 *
	 * @param {Function} f A method of server
	 * @param {Object} context Connection context object passed to f as first argument
	 * @return {Function} Function returning a promise resolving with f's return value
	 */
	function ensureLoadedWrap( f, context ) {
		// In theory, some protection is needed to ensure the document cannot unload
		// between the ensureLoaded promise resolving and f running. In practice,
		// this should not happen if the unloading is not too aggressive.
		return function () {
			const args = Array.prototype.slice.call( arguments );
			args.splice( 0, 0, context );
			return server.ensureLoaded( docName ).then( function () {
				return f.apply( server, args );
			} );
		};
	}

	socket.join( docName );
	return server.ensureLoaded( docName ).then( function () {
		const context = server.authenticate( docName, authorId, token );
		context.broadcast = function () {
			const room = getRoom( docName );
			room.emit.apply( room, arguments );
		};
		context.sendAuthor = socket.emit.bind( socket );
		context.connectionId = socket.client.conn.remoteAddress + ' ' + socket.handshake.url;
		socket.on( 'submitChange', ensureLoadedWrap( server.onSubmitChange, context ) );
		socket.on( 'changeAuthor', ensureLoadedWrap( server.onChangeAuthor, context ) );
		socket.on( 'disconnect', ensureLoadedWrap( server.onDisconnect, context ) );
		socket.on( 'logEvent', ensureLoadedWrap( server.onLogEvent, context ) );
		server.welcomeClient( context );
	} );
};
