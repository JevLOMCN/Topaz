/*!
 * VisualEditor DataModel transport server tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.TransportServer' );

QUnit.test( 'Create', function ( assert ) {
	var done = assert.async(),
		log = [],
		io = ve.dm.FakeSocket.static.makeServer();

	var protocolServer = {
		context: null,
		ensureLoaded: Promise.resolve.bind( Promise ),
		authenticate: function ( docName, authorId, token ) {
			var context = { docName: docName, authorId: authorId, token: token };
			log.push( [ 'authenticate', ve.copy( context ) ] );
			return context;
		},
		onSubmitChange: function ( context, data ) {
			log.push( [ 'submitChange', data ] );
			context.broadcast( 'newChange', data );
		},
		onLogEvent: function ( context, event ) {
			log.push( [ 'logEvent', event ] );
		},
		onChangeAuthor: function ( context, newData ) {
			log.push( [ 'changeAuthor', newData ] );
			context.broadcast( 'authorChange', newData );
		},
		onDisconnect: function ( context ) {
			log.push( [ 'disconnect' ] );
			context.broadcast( 'authorDisconnect', context.authorId );
		},
		welcomeClient: function ( context ) {
			this.context = context;
			context.sendAuthor( 'registered', context.authorId );
			context.broadcast( 'authorChange', context.authorId );
			context.sendAuthor( 'initDoc', 'fakedoccontents' );
		}
	};

	var transportServer = new ve.dm.TransportServer( protocolServer, protocolServer );
	var socket = new ve.dm.FakeSocket( io, { docName: 'Foo', authorId: 1, token: 'xxx' } );
	socket.on( 'registered', function ( data ) {
		log.push( [ 'registered', data ] );
	} );
	socket.on( 'authorChange', function ( data ) {
		log.push( [ 'broadcast', 'authorChange', data ] );
	} );
	socket.on( 'initDoc', function ( data ) {
		log.push( [ 'initDoc', data ] );
	} );
	socket.on( 'newChange', function ( data ) {
		log.push( [ 'broadcast', 'newChange', data ] );
	} );
	socket.on( 'authorDisconnect', function ( authorId ) {
		log.push( [ 'broadcast', 'authorDisconnect', authorId ] );
	} );

	transportServer.onConnection( io.sockets.in.bind( io.sockets ), socket ).then( function () {
		socket.receive( 'submitChange', 'foo' );
		socket.receive( 'logEvent', 'bar' );
		socket.receive( 'changeAuthor', 1 );
		socket.receive( 'disconnect' );
		return socket.wait();
	} ).then( function () {
		assert.deepEqual( log, [
			[ 'authenticate', { docName: 'Foo', authorId: 1, token: 'xxx' } ],
			[ 'registered', 1 ],
			[ 'broadcast', 'authorChange', 1 ],
			[ 'initDoc', 'fakedoccontents' ],
			[ 'submitChange', 'foo' ],
			[ 'broadcast', 'newChange', 'foo' ],
			[ 'logEvent', 'bar' ],
			[ 'changeAuthor', 1 ],
			[ 'broadcast', 'authorChange', 1 ],
			[ 'disconnect' ],
			[ 'broadcast', 'authorDisconnect', 1 ]
		], 'Correct events received in correct order' );
	} ).catch( function ( err ) {
		assert.true( false, 'Exception: ' + err );
	} ).finally( () => done() );
} );
