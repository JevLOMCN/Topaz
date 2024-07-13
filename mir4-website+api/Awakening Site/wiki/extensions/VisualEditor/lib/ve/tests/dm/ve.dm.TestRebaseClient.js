/*!
 * VisualEditor DataModel TestRebaseClient class
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Rebase client used for testing
 *
 * @class
 * @extends ve.dm.RebaseClient
 *
 * @constructor
 * @param {ve.dm.TestRebaseServer} server Rebase server
 * @param {Array} initialData Document
 */
ve.dm.TestRebaseClient = function VeDmTestRebaseClient( server, initialData ) {
	ve.dm.RebaseClient.apply( this );

	this.server = server;
	this.incomingPointer = 0;
	this.outgoing = [];
	this.outgoingPointer = 0;
	this.doc = new ve.dm.Document( OO.copy( initialData ) );
	this.surface = new ve.dm.Surface( this.doc );
};

OO.initClass( ve.dm.TestRebaseClient );
OO.mixinClass( ve.dm.TestRebaseClient, ve.dm.RebaseClient );

/**
 * Compact, potentially ambiguous summary of insertions/removals, disregarding location
 *
 * Example: insertion1insertion2-(removal3)insertion3/uncommittedinsertion4?/-(unsentremoval5)!
 *
 * Only insertions and removals are represented. An insertion is represented by the toString
 * content inserted; a removal by '-(' then the toString content removed then ')'. The offset
 * in the document of the insertion/removal is not represented at all. Consecutive transaction
 * representations are concatenated.
 *
 * Therefore this format is ambiguous unless the test transactions are chosen very carefully.
 * The summary 'abc' could represent any of the following changes (amongst others):
 *
 * - Append 'abc'
 * - Append 'a', then append 'bc'
 * - Append 'a' then prepend 'bc'
 * - Append 'a' then prepend 'b' then append 'c'
 *
 * @param {ve.dm.Change} change The document history
 * @param {number} [commitLength] The point above which the transactions are uncommitted
 * @param {number} [sentLength] The point above which the transactions are unsent
 * @return {string} Compact summary of the history
 */
ve.dm.TestRebaseClient.static.historySummary = function ( change, commitLength, sentLength ) {
	var parts = [];
	if ( commitLength === undefined ) {
		commitLength = change.transactions.length;
	}
	if ( sentLength === undefined ) {
		sentLength = change.transactions.length;
	}
	var committed = change.transactions.slice( 0, commitLength );
	var sent = change.transactions.slice( commitLength, sentLength );
	var unsent = change.transactions.slice( sentLength );

	function joinText( transactions ) {
		return transactions.map( function ( transaction ) {
			return transaction.operations.filter( function ( op ) {
				return op.type === 'replace';
			} ).map( function ( op ) {
				var text = [];
				if ( op.remove.length ) {
					text.push( '-(' + op.remove.map( function ( item ) {
						return item[ 0 ];
					} ).join( '' ) + ')' );
				}
				if ( op.insert.length ) {
					text.push( op.insert.map( function ( item ) {
						return item[ 0 ];
					} ).join( '' ) );
				}
				return text.join( '' );
			} ).join( '' );
		} ).join( '' );
	}
	if ( committed.length ) {
		parts.push( joinText( committed ) );
	}
	if ( sent.length ) {
		parts.push( joinText( sent ) + '?' );
	}
	if ( unsent.length ) {
		parts.push( joinText( unsent ) + '!' );
	}
	return parts.join( '/' );
};

ve.dm.TestRebaseClient.prototype.getHistorySummary = function () {
	return this.constructor.static.historySummary( this.getChangeSince( 0 ), this.commitLength, this.sentLength );
};

ve.dm.TestRebaseClient.prototype.getChangeSince = function ( start ) {
	return this.doc.getChangeSince( start );
};

ve.dm.TestRebaseClient.prototype.sendChange = function ( backtrack, change ) {
	this.outgoing.push( { backtrack: backtrack, change: change } );
};

ve.dm.TestRebaseClient.prototype.applyChange = function ( change ) {
	change.applyTo( this.surface );
};

ve.dm.TestRebaseClient.prototype.applyTransactions = function ( txs ) {
	var authorId = this.getAuthorId();
	txs.forEach( function ( transaction ) {
		if ( transaction.authorId === null ) {
			transaction.authorId = authorId;
		}
	} );
	this.surface.change( txs );
};

ve.dm.TestRebaseClient.prototype.unapplyChange = function ( change ) {
	change.unapplyTo( this.surface );
};

ve.dm.TestRebaseClient.prototype.addToHistory = function ( change ) {
	change.addToHistory( this.doc );
};

ve.dm.TestRebaseClient.prototype.removeFromHistory = function ( change ) {
	change.removeFromHistory( this.doc );
};

ve.dm.TestRebaseClient.prototype.deliverOne = function () {
	var item = this.outgoing[ this.outgoingPointer++ ];
	var rebased = this.server.applyChange(
		ve.dm.TestRebaseServer.static.fakeDocName,
		this.getAuthorId(),
		item.backtrack,
		item.change
	);
	if ( !rebased.isEmpty() ) {
		this.server.incoming.push( rebased );
	}
};

ve.dm.TestRebaseClient.prototype.receiveOne = function () {
	this.acceptChange( this.server.incoming[ this.incomingPointer++ ] );
};
