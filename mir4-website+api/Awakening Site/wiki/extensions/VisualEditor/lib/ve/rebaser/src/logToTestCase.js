'use strict';

// eslint-disable-next-line n/no-missing-require
const ve = require( '../dist/ve-rebaser.js' ),
	fs = require( 'fs' );

/**
 * Parse log file contents.
 *
 * @param {string} log Newline-separated list of JSON objects
 * @return {Object[]} Array of parsed objects
 */
function parseLog( log ) {
	const result = [],
		lines = log.split( '\n' );
	for ( let i = 0; i < lines.length; i++ ) {
		if ( lines[ i ] === '' ) {
			continue;
		}
		try {
			result.push( JSON.parse( lines[ i ] ) );
		} catch ( e ) {
			console.warn( e, lines[ i ] );
		}
	}
	return result;
}

function toTestCase( parsedLog ) {
	const clients = [],
		ops = [],
		clientStates = {};
	for ( let i = 0; i < parsedLog.length; i++ ) {
		const type = parsedLog[ i ].type;
		const authorId = parsedLog[ i ].authorId;
		const clientId = parsedLog[ i ].clientId;
		if ( type === 'newClient' ) {
			clients.push( authorId );
			clientStates[ authorId ] = {
				unsent: 0,
				submitting: false
			};
		} else if ( type === 'applyChange' ) {
			if ( clientStates[ authorId ].submitting ) {
				ops.push( [ authorId, 'deliver' ] );
				clientStates[ authorId ].submitting = false;
			}
		} else if ( type === 'acceptChange' ) {
			const unsent = ve.dm.Change.static.deserialize( parsedLog[ i ].unsent, true );
			const newChanges = unsent.mostRecent( unsent.start + clientStates[ clientId ].unsent );
			// HACK: Deliberately using .getLength() > 0 instead of .isEmpty() to ignore selection-only changes
			if ( newChanges.getLength() > 0 ) {
				ops.push( [ clientId, 'apply', newChanges.serialize( true ) ] );
				clientStates[ clientId ].unsent = unsent.getLength();
			}

			// .change is not a Change, but an array [start, length]
			if ( parsedLog[ i ].change[ 1 ] > 0 ) {
				ops.push( [ clientId, 'receive' ] );
			}
		} else if ( type === 'submitChange' ) {
			const changes = ve.dm.Change.static.deserialize( parsedLog[ i ].change, true );
			const newChanges = changes.mostRecent( changes.start + clientStates[ clientId ].unsent );
			if ( newChanges.getLength() > 0 ) {
				ops.push( [ clientId, 'apply', newChanges.serialize( true ) ] );
			}

			if ( clientStates[ clientId ].unsent + newChanges.getLength() > 0 ) {
				ops.push( [ clientId, 'submit' ] );
				clientStates[ clientId ].unsent = 0;
				clientStates[ clientId ].submitting = true;
			}
		}
	}
	return {
		initialData: [],
		clients: clients,
		ops: ops
	};
}

fs.readFile( process.argv[ 2 ], { encoding: 'utf8' }, function ( err, data ) {
	const parsed = parseLog( data ),
		testCase = toTestCase( parsed );
	process.stdout.write( JSON.stringify( testCase ) );
} );

// acceptChange
// submitChange
// applyChange
// newClient
// disconnect
