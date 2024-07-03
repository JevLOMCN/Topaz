/*!
 * VisualEditor DataModel protocol server class.
 *
 * @copyright See AUTHORS.txt
 */

'use strict';

/**
 * Protocol server
 *
 * Handles the abstract protocol without knowing the specific transport
 *
 * @param {ve.dm.DocumentStore} documentStore The persistent storage
 * @param {Object} logger Logger class
 * @param {Function} logger.getRelativeTimestmap Return the number of ms since the logger started
 * @param {Function} logger.logEvent Stringify object argument to log
 * @param {Function} logger.logServerEvent Stringify object argument to log, adding timestamp and server ID properties
 */
ve.dm.ProtocolServer = function VeDmProtocolServer( documentStore, logger ) {
	this.logger = logger;
	this.rebaseServer = new ve.dm.RebaseServer();
	this.lastAuthorForDoc = new Map();
	this.loadingForDoc = new Map();
	this.documentStore = documentStore;
	this.logger.logServerEvent( { type: 'restart' }, 'info' );
};

OO.initClass( ve.dm.ProtocolServer );

ve.dm.ProtocolServer.static.palette = [
	'1f77b4', 'ff7f0e', '2ca02c', 'd62728', '9467bd',
	'8c564b', 'e377c2', '7f7f7f', 'bcbd22', '17becf',
	'aec7e8', 'ffbb78', '98df8a', 'ff9896', 'c5b0d5',
	'c49c94', 'f7b6d2', 'c7c7c7', 'dbdb8d', '9edae5'
];

/**
 * If the document is not loaded, load from storage (creating as empty if absent)
 *
 * @param {string} docName Name of the document
 * @return {Promise} Resolves when loaded
 */
ve.dm.ProtocolServer.prototype.ensureLoaded = function ( docName ) {
	const documentStore = this,
		rebaseServer = this.rebaseServer;

	let loading = this.loadingForDoc.get( docName );

	if ( loading ) {
		return loading;
	}
	this.logger.logServerEvent( { type: 'ProtocolServer#load', docName: docName } );
	loading = this.documentStore.load( docName ).then( function ( change ) {
		documentStore.logger.logServerEvent( {
			type: 'ProtocolServer#loaded',
			docName: docName,
			length: change.getLength()
		} );
		rebaseServer.updateDocState( docName, null, change );
	} );
	this.loadingForDoc.set( docName, loading );
	return loading;
};

/**
 * Check the client's credentials, and return a connection context object
 *
 * If the client is not recognised and authenticated, a new client ID and token are assigned.
 *
 * @param {string} docName The document name
 * @param {number|null} authorId The author ID, if any
 * @param {number|null} token The secret token, if any
 *
 * @return {Object} The connection context
 */
ve.dm.ProtocolServer.prototype.authenticate = function ( docName, authorId, token ) {
	const state = this.rebaseServer.stateForDoc.get( docName ),
		authorData = state && state.authors.get( authorId );

	if ( !authorData || token !== authorData.token ) {
		authorId = 1 + ( this.lastAuthorForDoc.get( docName ) || 0 );
		this.lastAuthorForDoc.set( docName, authorId );
		token = Math.random().toString( 36 ).slice( 2 );
	}
	const context = {
		serverId: this.documentStore.serverId,
		docName: docName,
		authorId: authorId
	};
	this.logger.logServerEvent( {
		type: 'newClient',
		doc: docName,
		authorId: context.authorId
	} );
	return context;
};

/**
 * Add an event to the log
 *
 * @param {Object} context The connection context
 * @param {Object} event Event data
 */
ve.dm.ProtocolServer.prototype.onLogEvent = function ( context, event ) {
	const ob = {};
	ob.recvTimestamp = this.logger.getRelativeTimestamp();
	ob.clientId = context.authorId;
	ob.doc = context.docName;
	for ( const key in event ) {
		ob[ key ] = event[ key ];
	}
	this.logger.logEvent( ob );
};

/**
 * Setup author on the server and send initialization events
 *
 * @param {Object} context The connection context
 * @param {number} [startLength=0] The length of the common history
 */
ve.dm.ProtocolServer.prototype.welcomeClient = function ( context, startLength ) {
	const docName = context.docName,
		serverId = context.serverId,
		authorId = context.authorId;

	if ( !startLength ) {
		startLength = 0;
	}
	this.rebaseServer.updateDocState( docName, authorId, null, {
		name: ve.init.platform.getUserName() || ve.msg( 'visualeditor-collab-user-placeholder', authorId ),
		color: this.constructor.static.palette[
			authorId % this.constructor.static.palette.length
		],
		active: true
	} );

	const state = this.rebaseServer.getDocState( docName );
	const authorData = state.authors.get( authorId );

	context.sendAuthor( 'registered', {
		serverId: serverId,
		authorId: authorId,
		token: authorData.token
	} );
	context.broadcast( 'authorChange', {
		authorId: authorId,
		authorData: {
			name: authorData.name,
			color: authorData.color
		}
	} );
	// HACK Catch the client up on the current state by sending it the entire history
	// Ideally we'd be able to initialize the client using HTML, but that's hard, see
	// comments in the /raw handler. Keeping an updated linmod on the server could be
	// feasible if TransactionProcessor was modified to have a "don't sync, just apply"
	// mode and ve.dm.Document was faked with { data: …, metadata: …, store: … }
	context.sendAuthor( 'initDoc', {
		history: state.history.mostRecent( startLength ).serialize( true ),
		authors: state.getActiveAuthors()
	} );
};

/**
 * Try to apply a received change, and broadcast the successful portion as rebased
 *
 * @param {Object} context The connection context
 * @param {Object} data The change data
 */
ve.dm.ProtocolServer.prototype.onSubmitChange = function ( context, data ) {
	const change = ve.dm.Change.static.deserialize( data.change, true );
	const applied = this.rebaseServer.applyChange( context.docName, context.authorId, data.backtrack, change );
	if ( !applied.isEmpty() ) {
		this.documentStore.onNewChange( context.docName, applied );
		context.broadcast( 'newChange', applied.serialize( true ) );
	}
};

/**
 * Apply and broadcast an author change
 *
 * @param {Object} context The connection context
 * @param {string} newData The new author data
 */
ve.dm.ProtocolServer.prototype.onChangeAuthor = function ( context, newData ) {
	this.rebaseServer.updateDocState( context.docName, context.authorId, null, {
		name: newData.name,
		color: newData.color
	} );
	context.broadcast( 'authorChange', {
		authorId: context.authorId,
		authorData: {
			name: newData.name,
			color: newData.color
		}
	} );
	this.logger.logServerEvent( {
		type: 'authorChange',
		doc: context.docName,
		authorId: context.authorId,
		authorData: {
			name: newData.name,
			color: newData.color
		}
	} );
};

/**
 * Apply and broadcast a disconnection (which may be temporary)
 *
 * @param {Object} context The connection context
 */
ve.dm.ProtocolServer.prototype.onDisconnect = function ( context ) {
	this.rebaseServer.updateDocState( context.docName, context.authorId, null, {
		active: false,
		continueBase: null,
		rejections: null
	} );
	context.broadcast( 'authorDisconnect', context.authorId );
	this.logger.logServerEvent( {
		type: 'disconnect',
		doc: context.docName,
		authorId: context.authorId
	} );
};
