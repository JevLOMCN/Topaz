/*!
 * VisualEditor debugging methods.
 *
 * @copyright See AUTHORS.txt
 */

/* eslint-disable no-console */

/**
 * @property {boolean} debug
 * @member ve
 */
ve.debug = true;

/**
 * @class ve.debug
 * @override ve
 * @singleton
 */

/* Methods */

/**
 * Logs data to the console.
 *
 * @param {...Mixed} [data] Data to log
 */
ve.log = console.log;

/**
 * Logs error to the console.
 *
 * @param {...Mixed} [data] Data to log
 */
ve.error = console.error;

/**
 * Logs an object to the console.
 *
 * @param {Object} obj Object to log
 */
ve.dir = console.dir;

/**
 * Like outerHTML serialization, but wraps each text node in a fake tag. This
 * makes it obvious whether there are split text nodes present.
 *
 * @param {Node} domNode The node to serialize
 * @return {string} Serialization of the node and its contents
 */
ve.serializeNodeDebug = function ( domNode ) {
	var html = [];
	function add( node ) {
		if ( node.nodeType === Node.TEXT_NODE ) {
			html.push( '<#text>', ve.escapeHtml( node.textContent ), '</#text>' );
			return;
		} else if ( node.nodeType !== Node.ELEMENT_NODE ) {
			html.push( '<#unknown type=\'' + node.nodeType + '\'/>' );
			return;
		}
		// else node.nodeType === Node.ELEMENT_NODE

		var i, len;
		html.push( '<', ve.escapeHtml( node.nodeName.toLowerCase() ) );
		for ( i = 0, len = node.attributes.length; i < len; i++ ) {
			var attr = node.attributes[ i ];
			html.push(
				' ',
				ve.escapeHtml( attr.name ),
				// Single quotes are less annoying in JSON escaping
				'=\'',
				ve.escapeHtml( attr.value ),
				'\''
			);
		}
		html.push( '>' );
		for ( i = 0, len = node.childNodes.length; i < len; i++ ) {
			add( node.childNodes[ i ] );
		}
		html.push( '</', ve.escapeHtml( node.nodeName.toLowerCase() ), '>' );
	}
	add( domNode );
	return html.join( '' );
};

/**
 * Get a human-readable summary of a transaction
 *
 * @param {ve.dm.Transaction} tx A transaction
 * @return {string} Human-readable summary
 */
ve.summarizeTransaction = function ( tx ) {
	function summarizeItems( items ) {
		return '\'' + items.map( function ( item ) {
			if ( item.type ) {
				return '<' + item.type + '>';
			} else if ( Array.isArray( item ) ) {
				return item[ 0 ];
			} else if ( typeof item === 'string' ) {
				return item;
			} else {
				throw new Error( 'Unknown item type: ' + item );
			}
		} ).join( '' ) + '\'';
	}
	var annotations = 0;
	return '(' + ( tx.authorId ? ( tx.authorId + ' ' ) : '' ) + tx.operations.map( function ( op ) {
		if ( op.type === 'retain' ) {
			return ( annotations ? 'annotate ' : 'retain ' ) + op.length;
		} else if ( op.type === 'replace' ) {
			if ( op.remove.length === 0 ) {
				return 'insert ' + summarizeItems( op.insert );
			} else if ( op.insert.length === 0 ) {
				return 'remove ' + summarizeItems( op.remove );
			} else {
				return 'replace ' + summarizeItems( op.remove ) +
					' -> ' + summarizeItems( op.insert );
			}
		} else if ( op.type === 'attribute' ) {
			return 'attribute';
		} else if ( op.type === 'annotate' ) {
			annotations += op.bias === 'start' ? 1 : -1;
			return 'annotate';
		} else if ( op.type.slice( -8 ) === 'Metadata' ) {
			// We don't care much because we're deprecating metadata ops
			return 'metadata';
		} else {
			throw new Error( 'Unknown op type: ' + op.type );
		}
	} ).join( ', ' ) + ')';
};

/**
 * Initialize ve.filibuster
 *
 * ve.filibuster will monitor calls in ve.{dm,ce,ui} and DM / DOM changes
 */
ve.initFilibuster = function () {
	if ( ve.filibuster ) {
		ve.filibuster.clearLogs();
		return;
	}

	var surface = ve.init.target.surface;
	ve.filibuster = new ve.Filibuster()
		.wrapClass( ve.EventSequencer )
		.wrapNamespace( ve.dm, 've.dm', [
			// nowrapList
			ve.dm.LinearSelection.prototype.getDescription,
			ve.dm.TableSelection.prototype.getDescription,
			ve.dm.NullSelection.prototype.getDescription
		] )
		.wrapNamespace( ve.ce, 've.ce' )
		.wrapNamespace( ve.ui, 've.ui', [
			// nowrapList
			ve.ui.Surface.prototype.startFilibuster,
			ve.ui.Surface.prototype.stopFilibuster
		] )
		.setObserver( 'dm doc', function () {
			// Cannot use wrapped methods here
			return JSON.stringify( ve.Filibuster.static.clonePlain(
				surface.model.documentModel.data.data
			) );
		} )
		.setObserver( 'dm selection', function () {
			// Cannot use wrapped methods here
			var selection = surface.model.selection;
			if ( !selection ) {
				return 'null';
			}
			return selection.getDescription();
		} )
		.setObserver( 'DOM doc', function () {
			// Cannot use wrapped methods here
			return ve.serializeNodeDebug( surface.view.$element[ 0 ] );
		} )
		.setObserver( 'DOM selection', function () {
			// Cannot use wrapped methods here
			var nativeSelection = surface.view.nativeSelection;
			if ( nativeSelection.focusNode === null ) {
				return 'null';
			}
			return JSON.stringify( {
				anchorNode: ve.serializeNodeDebug( nativeSelection.anchorNode ),
				anchorOffset: nativeSelection.anchorOffset,
				focusNode: (
					nativeSelection.focusNode === nativeSelection.anchorNode ?
						'(=anchorNode)' :
						ve.serializeNodeDebug( nativeSelection.focusNode )
				),
				focusOffset: nativeSelection.focusOffset
			} );
		} );
};
