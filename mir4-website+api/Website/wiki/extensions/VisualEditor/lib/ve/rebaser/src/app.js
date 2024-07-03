/*!
 * VisualEditor rebase server script.
 *
 * @copyright See AUTHORS.txt
 */

'use strict';

const
	express = require( 'express' ),
	http = require( 'http' ),
	socketIO = require( 'socket.io' ),
	mongodb = require( 'mongodb' ),
	Logger = require( './Logger.js' ),
	ve = require( '../../dist/ve-rebaser.js' ),
	packageInfo = require( '../package.json' );

function initApp( options ) {
	const app = express();

	// get the options and make them available in the app
	app.logger = options.logger; // the logging device
	app.metrics = options.metrics; // the metrics
	app.conf = options.config; // this app's config options
	app.info = packageInfo; // this app's package info

	app.use( express.static( __dirname + '/../..' ) );
	app.set( 'view engine', 'ejs' );

	app.get( '/', function ( req, res ) {
		res.render( 'index' );
	} );

	// eslint-disable-next-line prefer-regex-literals
	app.get( new RegExp( '/doc/edit/(.*)' ), function ( req, res ) {
		const docName = req.params[ 0 ];
		res.render( 'editor', { docName: docName } );
	} );

	// eslint-disable-next-line prefer-regex-literals
	app.get( new RegExp( '/doc/raw/(.*)' ), function ( req, res ) {
		// TODO return real data
		// In order to provide HTML here, we'd need all of ve.dm (Document, Converter, all nodes)
		// and none of that code is likely to work in nodejs without some work because of how heavily
		// it uses the DOM.
		// var docName = req.params[ 0 ];
		res.status( 401 ).send( 'DOM in nodejs is hard' );
	} );

	const logger = new Logger( app.logger );
	const mongoClient = new mongodb.MongoClient(
		'mongodb://' + app.conf.mongodb.host + ':' + app.conf.mongodb.port
	);
	const documentStore = new ve.dm.DocumentStore( mongoClient, 'test', logger );
	const protocolServer = new ve.dm.ProtocolServer( documentStore, logger );
	app.transportServer = new ve.dm.TransportServer( protocolServer );
	app.logger.log( 'info', 'Connecting to document store' );

	return documentStore.connect().then( function () {
		const dropDatabase = ( process.argv.includes( '--drop' ) );
		if ( dropDatabase ) {
			app.logger.log( 'info', 'Dropping database' );
		}
		return ( dropDatabase ? documentStore.dropDatabase() : Promise.resolve() ).then( function () {
			return app;
		} );
	} );
}

function createServer( app ) {
	// return a promise which creates an HTTP server,
	// attaches the app to it, and starts accepting
	// incoming client requests
	let server;

	return new Promise( function ( resolve ) {
		server = http.createServer( app ).listen(
			app.conf.port,
			app.conf.interface,
			resolve
		);
		const io = socketIO( server );
		io.on(
			'connection',
			app.transportServer.onConnection.bind(
				app.transportServer,
				io.sockets.in.bind( io.sockets )
			)
		);
	} ).then( function () {
		app.logger.log( 'info',
			'Worker ' + process.pid + ' listening on ' + ( app.conf.interface || '*' ) + ':' + app.conf.port );
		return server;
	} );
}

/**
 * The service's entry point. It takes over the configuration
 * options and the logger and metrics-reporting objects from
 * service-runner and starts an HTTP server, attaching the application
 * object to it.
 *
 * @param {Object} options
 * @return {Promise} a promise for an http server.
 */
module.exports = function ( options ) {
	return initApp( options )
		.then( createServer );
};
