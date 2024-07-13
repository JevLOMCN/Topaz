/*!
 * VisualEditor DataModel SurfaceSynchronizer class.
 *
 * @copyright See AUTHORS.txt
 */

/* global io */

/**
 * DataModel surface synchronizer.
 *
 * @class
 * @mixins OO.EventEmitter
 * @mixins ve.dm.RebaseClient
 *
 * @constructor
 * @param {ve.dm.Surface} surface Surface model to synchronize
 * @param {string} documentId Document ID
 * @param {Object} [config] Configuration options
 * @cfg {string} [server] IO server
 * @cfg {string} [defaultName] Default username
 */
ve.dm.SurfaceSynchronizer = function VeDmSurfaceSynchronizer( surface, documentId, config ) {
	config = config || {};

	// Mixin constructors
	OO.EventEmitter.call( this );
	ve.dm.RebaseClient.call( this );

	// Properties
	this.surface = surface;
	this.doc = surface.documentModel;
	this.store = this.doc.getStore();
	this.authors = {};
	this.authorSelections = {};
	this.documentId = documentId;

	// Whether the document has been initialized
	this.initialized = false;
	// Whether we are currently synchronizing the model
	this.applying = false;
	this.token = null;
	this.serverId = null;
	this.loadSessionKey();
	this.paused = false;

	// SocketIO events
	var conn;
	if ( config.peerConnection ) {
		conn = {
			peerConnection: config.peerConnection,
			handlers: new Map(),
			on: function ( type, handler ) {
				if ( !this.handlers.has( type ) ) {
					this.handlers.set( type, [] );
				}
				this.handlers.get( type ).push( handler );
			},
			send: function ( type, data ) {
				this.peerConnection.send( { type: type, data: ve.collab.serialize( data ) } );
			},
			disconnect: function () {
				this.peerConnection.close();
			}
		};
		conn.peerConnection.on( 'data', function ( data ) {
			var type = data.type;
			if ( typeof type !== 'string' ) {
				throw new Error( 'Expected .type in <' + data + '>' );
			}
			( conn.handlers.get( type ) || [] ).forEach( function ( handler ) {
				handler( data.data );
			} );
		} );
	} else {
		var path = ( config.server || '' );
		var options = {
			query: {
				docName: this.documentId,
				authorId: this.getAuthorId() || '',
				token: this.token || ''
			},
			transports: [ 'websocket' ]
		};
		conn = {
			socket: io( path, options ),
			on: function ( type, handler ) {
				this.socket.on( type, handler );
			},
			send: function ( type, data ) {
				this.socket.emit( type, data );
			},
			disconnect: function () {
				this.socket.disconnect();
			}
		};
	}
	this.conn = conn;
	this.conn.on( 'registered', this.onRegistered.bind( this ) );
	this.conn.on( 'initDoc', this.onInitDoc.bind( this ) );
	this.conn.on( 'newChange', this.onNewChange.bind( this ) );
	this.conn.on( 'authorChange', this.onAuthorChange.bind( this ) );
	this.conn.on( 'authorDisconnect', this.onAuthorDisconnect.bind( this ) );

	var authorData = ve.init.platform.sessionStorage.getObject( 've-collab-author' );
	if ( authorData ) {
		this.changeAuthor( authorData );
	} else if ( config.defaultName ) {
		this.changeAuthor( { name: config.defaultName } );
	}

	// Events
	this.surface.connect( this, {
		history: 'onSurfaceHistory',
		select: 'onSurfaceSelect'
	} );

	this.submitChangeThrottled = ve.debounce( ve.throttle( this.submitChange.bind( this ), 250 ), 0 );
};

/* Inheritance */

OO.mixinClass( ve.dm.SurfaceSynchronizer, OO.EventEmitter );
OO.mixinClass( ve.dm.SurfaceSynchronizer, ve.dm.RebaseClient );

/* Events */

/**
 * @event authorSelect
 * @param {number} authorId The author whose selection has changed
 */

/**
 * @event authorChange
 * @param {number} authorId The author whose data has changed
 */

/**
 * @event wrongDoc
 */

/**
 * @event initDoc
 * @param {Error} error Error, if there was a problem initializing the document
 */

/**
 * @event disconnect
 */

/**
 * @event pause
 * The synchronizer is paused or resumes
 */

/* Methods */

/**
 * Destroy the synchronizer
 */
ve.dm.SurfaceSynchronizer.prototype.destroy = function () {
	this.conn.disconnect();
	this.doc.disconnect( this );
	this.surface.disconnect( this );
	this.initialized = false;
};

/**
 * Pause sending/receiving changes
 */
ve.dm.SurfaceSynchronizer.prototype.pauseChanges = function () {
	if ( this.paused ) {
		return;
	}
	this.paused = true;
	this.queuedChanges = [];
	this.emit( 'pause' );
};

/**
 * Resume sending/receiving changes
 */
ve.dm.SurfaceSynchronizer.prototype.resumeChanges = function () {
	if ( !this.paused ) {
		return;
	}
	this.applying = true;
	try {
		// Don't cache length, as it's not inconceivable acceptChange could
		// cause another change to arrive in some weird setup
		for ( var i = 0; i < this.queuedChanges.length; i++ ) {
			this.acceptChange( this.queuedChanges[ i ] );
		}
	} finally {
		this.applying = false;
	}
	this.paused = false;
	// Schedule submission of unsent local changes, if any
	this.submitChangeThrottled();
	this.emit( 'pause' );
};

/**
 * @inheritdoc
 */
ve.dm.SurfaceSynchronizer.prototype.getChangeSince = function ( start, toSubmit ) {
	var change = this.doc.getChangeSince( start ),
		selection = this.surface.getSelection();
	if ( !selection.equals( this.lastSubmittedSelection ) ) {
		change.selections[ this.getAuthorId() ] = selection;
		if ( toSubmit ) {
			this.lastSubmittedSelection = selection;
		}
	}
	return change;
};

/**
 * @inheritdoc
 */
ve.dm.SurfaceSynchronizer.prototype.submitChange = function () {
	// Prevent submission before initialization is complete
	if ( !this.initialized ) {
		return;
	}
	// Parent method
	ve.dm.RebaseClient.prototype.submitChange.apply( this, arguments );
};

/**
 * @inheritdoc
 */
ve.dm.SurfaceSynchronizer.prototype.sendChange = function ( backtrack, change ) {
	this.conn.send( 'submitChange', {
		backtrack: this.backtrack,
		// Serialize (don't rely on the transport to perform implicit serialization)
		change: change.serialize()
	} );
};

/**
 * @inheritdoc
 */
ve.dm.SurfaceSynchronizer.prototype.applyChange = function ( change ) {
	// Author selections are superseded by change.selections, so no need to translate them
	for ( var authorId in change.selections ) {
		authorId = +authorId;
		delete this.authorSelections[ authorId ];
	}
	change.applyTo( this.surface );
	// HACK: After applyTo(), the selections are wrong and applying them could crash.
	// The only reason this doesn't happen is because everything that tries to do that uses setTimeout().
	// Translate the selections that aren't going to be overwritten by change.selections
	this.applyNewSelections( this.authorSelections, change );
	// Apply the overwrites from change.selections
	this.applyNewSelections( change.selections );
};

/**
 * @inheritdoc
 */
ve.dm.SurfaceSynchronizer.prototype.unapplyChange = function ( change ) {
	change.unapplyTo( this.surface );
	// Translate all selections for what we just unapplied
	// HACK: After unapplyTo(), the selections are wrong and applying them could crash.
	// The only reason this doesn't happen is because everything that tries to do that uses setTimeout().
	this.applyNewSelections( this.authorSelections, change.reversed() );
};

/**
 * @inheritdoc
 */
ve.dm.SurfaceSynchronizer.prototype.addToHistory = function ( change ) {
	change.addToHistory( this.doc );
};

/**
 * @inheritdoc
 */
ve.dm.SurfaceSynchronizer.prototype.removeFromHistory = function ( change ) {
	change.removeFromHistory( this.doc );
};

/**
 * @inheritdoc
 */
ve.dm.SurfaceSynchronizer.prototype.logEvent = function ( event ) {
	if ( !this.initialized ) {
		// Do not log before initialization is complete; this prevents us from logging the entire
		// document history during initialization
		return;
	}
	this.conn.send( 'logEvent', ve.extendObject( { sendTimestamp: Date.now() }, event ) );
};

/**
 * Respond to transactions happening on the document. Ignores transactions applied by
 * SurfaceSynchronizer itself.
 */
ve.dm.SurfaceSynchronizer.prototype.onSurfaceHistory = function () {
	if ( this.applying || !this.initialized || this.paused ) {
		// Ignore our own synchronization or initialization transactions
		return;
	}
	var change = this.getChangeSince( this.sentLength, true );
	var authorId = this.authorId;
	// HACK annotate transactions with authorship information
	// This relies on being able to access the transaction object by reference;
	// we should probably set the author deeper in dm.Surface or dm.Document instead.
	change.transactions.forEach( function ( tx ) {
		tx.authorId = authorId;
	} );
	// TODO deal with staged transactions somehow
	this.applyNewSelections( this.authorSelections, change );
	this.submitChangeThrottled();
};

/**
 * Respond to selection changes.
 */
ve.dm.SurfaceSynchronizer.prototype.onSurfaceSelect = function () {
	if ( this.paused ) {
		return;
	}
	this.submitChangeThrottled();
};

/**
 * Translate incoming selections by change, then apply them and fire authorSelect
 *
 * @param {Object} newSelections Each author (key) maps to a new incoming ve.dm.Selection
 * @param {ve.dm.Change|ve.dm.Transaction} [changeOrTx] Object to translate over, if any
 * @fires authorSelect
 */
ve.dm.SurfaceSynchronizer.prototype.applyNewSelections = function ( newSelections, changeOrTx ) {
	var change = changeOrTx instanceof ve.dm.Change ? changeOrTx : null,
		tx = changeOrTx instanceof ve.dm.Transaction ? changeOrTx : null;
	for ( var authorId in newSelections ) {
		authorId = +authorId;
		if ( authorId === this.authorId ) {
			continue;
		}
		var translatedSelection;
		if ( change ) {
			translatedSelection = newSelections[ authorId ].translateByChange( change, authorId );
		} else if ( tx ) {
			translatedSelection = newSelections[ authorId ].translateByTransactionWithAuthor( tx, authorId );
		} else {
			translatedSelection = newSelections[ authorId ];
		}
		if ( !translatedSelection.equals( this.authorSelections[ authorId ] ) ) {
			// This works correctly even if newSelections === this.authorSelections
			this.authorSelections[ authorId ] = translatedSelection;
			this.emit( 'authorSelect', authorId );
		}
	}
};

/**
 * Get author data object
 *
 * @param {number} [authorId] Author ID, defaults to current author
 * @return {Object} Author object, containing 'name' and 'color'
 */
ve.dm.SurfaceSynchronizer.prototype.getAuthorData = function ( authorId ) {
	if ( authorId === undefined ) {
		authorId = this.getAuthorId();
	}
	return this.authors[ authorId ];
};

ve.dm.SurfaceSynchronizer.prototype.onAuthorChange = function ( data ) {
	this.authors[ data.authorId ] = data.authorData;
	this.emit( 'authorChange', data.authorId );

	if ( data.authorId === this.getAuthorId() ) {
		ve.init.platform.sessionStorage.setObject( 've-collab-author', data.authorData );
	}
};

ve.dm.SurfaceSynchronizer.prototype.changeAuthor = function ( data ) {
	this.conn.send( 'changeAuthor', ve.extendObject( {}, this.getAuthorData( this.getAuthorId() ), data ) );
};

ve.dm.SurfaceSynchronizer.prototype.onAuthorDisconnect = function ( authorId ) {
	delete this.authors[ authorId ];
	delete this.authorSelections[ authorId ];
	this.emit( 'authorDisconnect', authorId );
};

/**
 * Respond to a "registered" event from the server
 *
 * @param {Object} data
 * @param {number} data.authorId The author ID allocated by the server
 * @param {string} data.token
 * @fires wrongDoc
 */
ve.dm.SurfaceSynchronizer.prototype.onRegistered = function ( data ) {
	if ( this.serverId && this.serverId !== data.serverId ) {
		this.conn.disconnect();
		this.emit( 'wrongDoc' );
		return;
	}
	this.serverId = data.serverId;
	this.setAuthorId( data.authorId );
	this.surface.setAuthorId( this.authorId );
	this.token = data.token;
	this.saveSessionKey();
};

ve.dm.SurfaceSynchronizer.prototype.saveSessionKey = function () {
	ve.init.platform.sessionStorage.setObject( 'visualeditor-session-key', {
		serverId: this.serverId,
		docName: this.documentId,
		authorId: this.getAuthorId(),
		token: this.token
	} );
};

ve.dm.SurfaceSynchronizer.prototype.loadSessionKey = function () {
	var data = ve.init.platform.sessionStorage.getObject( 'visualeditor-session-key' );
	if ( data && data.docName === this.documentId ) {
		this.serverId = data.serverId;
		this.setAuthorId( data.authorId );
		this.token = data.token;
	}
};

/**
 * Respond to an initDoc event from the server, catching us up on the prior history of the document.
 *
 * @param {Object} data
 * @param {Object} data.history Serialized change representing the server's history
 * @param {Object} data.authors Object mapping author IDs to author data objects (name/color)
 * @fires initDoc
 */
ve.dm.SurfaceSynchronizer.prototype.onInitDoc = function ( data ) {
	if ( this.initialized ) {
		// Ignore attempt to initialize a second time
		return;
	}
	for ( var authorId in data.authors ) {
		this.onAuthorChange( {
			authorId: +authorId,
			authorData: data.authors[ authorId ]
		} );
	}
	try {
		var history = ve.dm.Change.static.deserialize( data.history );
		this.acceptChange( history );
	} catch ( e ) {
		this.conn.disconnect();
		this.emit( 'initDoc', e );
		return;
	}
	this.emit( 'initDoc' );

	// Mark ourselves as initialized and retry any prevented submissions
	this.initialized = true;
	this.submitChangeThrottled();
};

/**
 * Respond to a newChange event from the server, signalling a newly committed change
 *
 * If the commited change is by another author, then:
 * - Rebase uncommitted changes over the committed change
 * - If there is a rebase rejection, then apply its inverse to the document
 * - Apply the rebase-transposed committed change to the document
 * - Rewrite history to have the committed change followed by rebased uncommitted changes
 *
 * If the committed change is by the local author, then it is already applied to the document
 * and at the correct point in the history: just move the commit pointer.
 *
 * @param {Object} serializedChange Serialized ve.dm.Change that the server has applied
 */
ve.dm.SurfaceSynchronizer.prototype.onNewChange = function ( serializedChange ) {
	var change = ve.dm.Change.static.deserialize( serializedChange );
	if ( this.paused ) {
		this.queuedChanges.push( change );
		return;
	}
	// Make sure we don't attempt to submit any of the transactions we commit while manipulating
	// the state of the document
	this.applying = true;
	try {
		this.acceptChange( change );
	} finally {
		this.applying = false;
	}
	// Schedule submission of unsent local changes, if any
	this.submitChangeThrottled();
};

ve.dm.SurfaceSynchronizer.prototype.onDisconnect = function () {
	this.initialized = false;
	this.emit( 'disconnect' );
};
