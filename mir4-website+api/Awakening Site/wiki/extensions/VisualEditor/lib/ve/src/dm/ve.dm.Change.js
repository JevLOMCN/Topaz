/*!
 * VisualEditor DataModel Change class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel change.
 *
 * A change is a list of transactions to be applied sequentially on top of a certain history
 * state, together with a set that includes all new store values (annotations and DOM elements)
 * introduced by those transactions.
 *
 * It can be thought of more abstractly as a function f: D1 -> D2 a document in a
 * specific start state D1, modifying parts of the document to produce a specific end state D2.
 *
 * For two changes f: D1 -> D2 and g: D2 -> D3 we define f.concat(g): D1 -> D3 as the change
 * obtained by applying f then g. By associativity of functions,
 * a.concat(b.concat(c)) = a.concat(b).concat(c) for any consecutive changes a, b, c. Writing
 *
 * x * y := x.concat(y) ,
 *
 * we have a * (b * c) = (a * b) * c, so we can just write either as a * b * c.
 *
 * For a change f: D1 -> D2 we define f.reversed() as the change D2 -> D1 such that
 * f.concat(f.reversed()) is the identity change D1 -> D1. Writing
 *
 * inv(x) := x.reversed() ,
 *
 * we have f * inv(f) = the identity change on D1 .
 *
 * Given two changes f: D1 -> D2 and g: D1 -> D3 , We would like to define f.rebasedOnto(g)
 * ("f rebased onto g") as a change that maps D3 onto some D4: conceptually, it is f
 * modified so it can be applied after g. This is a useful concept because it allows changes
 * written in parallel to be sequenced into a linear order. However, for some changes there
 * is no reasonable way to do this; e.g. when f and g both change the same word to something
 * different. In this case we make f.rebasedOnto(g) return null and we say it conflicts.
 *
 * Given f: D1 -> D2 , g: D2 -> D3, and x: D1 -> D4, we give three guarantees about rebasing:
 *
 * 1. x.rebasedOnto(f) conflicts if and only if f.rebasedOnto(x) conflicts.
 * 2. If there is no conflict, f.concat(x.rebasedOnto(f)) equals x.concat(f.rebasedOnto(x)).
 * 3. If there is no conflict, x.rebasedOnto(f).rebasedOnto(g) equals x.rebasedOnto(f * g).
 *
 * We can consider a conflicting transaction starting at some document D to be 0: D->null,
 * and regard any two conflicting transactions starting at D to be equal, and just write 0
 * where D1 is clear from context. Then, writing
 *
 * x|y := x.rebasedOnto(y),
 *
 * we can write our guarantees. Given f: D1 -> D2 , g: D2 -> D3, and x: D1 -> D4:
 *
 * 1. Change conflict well definedness: x|f = 0 if and only if f|x = 0.
 * 2. Change commutativity: f * g|f equals g * f|g .
 * 3. Rebasing piecewise: if (x|f)|g != 0, then (x|f)|g equals x|(f * g) .
 *
 * These guarantees let us reorder non-conflicting changes without affecting the resulting
 * document. They also let us move in the inverse direction ("rebase under"), from sequential
 * changes to parallel ones, for if f: D1 -> D2 and g: D2 -> D3, then g|inv(f)
 * maps from D1 to some D4, and conceptually it is g modified to apply without f having been
 * applied.
 *
 * Note that rebasing piecewise is *not* equivalent for changes that conflict: if a change
 * conflicts f, it might not conflict with f*g. For example, if x|f = 0 then
 *
 * (x|f)|inv(f) = 0 but x|(f * inv(f)) = x.
 *
 * @class
 * @constructor
 * @param {number} [start] Length of the history stack at change start
 * @param {ve.dm.Transaction[]} [transactions] Transactions to apply
 * @param {ve.dm.HashValueStore[]} [stores] For each transaction, a collection of new store items
 * @param {Object} [selections] For each author ID (key), latest ve.dm.Selection
 */
ve.dm.Change = function VeDmChange( start, transactions, stores, selections ) {
	var change = this;
	this.start = start || 0;
	this.transactions = transactions || [];
	this.store = new ve.dm.HashValueStore();
	this.storeLengthAtTransaction = [];
	if ( stores ) {
		stores.forEach( function ( store ) {
			change.store.merge( store );
			change.storeLengthAtTransaction.push( change.store.getLength() );
		} );
	}
	this.selections = selections || {};
};

/* Static methods */

ve.dm.Change.static = {};

/**
 * Deserialize a change from a JSONable object
 *
 * Store values can be deserialized, or kept verbatim; the latter is an optimization if the
 * Change object will be rebased and reserialized without ever being applied to a document.
 *
 * @param {Object} data Change serialized as a JSONable object
 * @param {boolean} [preserveStoreValues] Keep store values verbatim instead of deserializing
 * @param {boolean} [unsafe] Use unsafe deserialization (skipping DOMPurify), used via #unsafeDeserialize
 * @return {ve.dm.Change} Deserialized change
 */
ve.dm.Change.static.deserialize = function ( data, preserveStoreValues, unsafe ) {
	var hasOwn = Object.prototype.hasOwnProperty,
		getTransactionInfo = this.getTransactionInfo,
		deserializeValue = this.deserializeValue,
		selections = {},
		transactions = [],
		// If stores is undefined, create an array of nulls
		stores = data.stores || data.transactions.map( function () {
			return null;
		} );

	/**
	 * Apply annotations in-place to array of code units
	 *
	 * @param {string[]} items Array of code units
	 * @param {string[]|null} annotations Annotations to apply uniformly, or null
	 */
	function annotate( items, annotations ) {
		var j, jLen;
		if ( !annotations || !annotations.length ) {
			return;
		}
		for ( j = 0, jLen = items.length; j < jLen; j++ ) {
			items[ j ] = [ items[ j ], annotations.slice() ];
		}
	}

	for ( var authorId in data.selections ) {
		selections[ authorId ] = ve.dm.Selection.static.newFromJSON(
			data.selections[ authorId ]
		);
	}
	var deserializeStore = ve.dm.HashValueStore.static.deserialize.bind(
		null,
		preserveStoreValues ? function noop( x ) {
			return x;
		} : function ( x ) {
			return deserializeValue( x, unsafe );
		}
	);
	var prevInfo;
	for ( var i = 0, iLen = data.transactions.length; i < iLen; i++ ) {
		var txSerialized = data.transactions[ i ];
		var tx;
		if ( typeof txSerialized === 'string' ) {
			var insertion = txSerialized.split( '' );
			annotate(
				insertion,
				prevInfo.uniformInsert && prevInfo.uniformInsert.annotations
			);
			tx = new ve.dm.Transaction( [
				{ type: 'retain', length: prevInfo.end },
				{ type: 'replace', remove: [], insert: insertion },
				{ type: 'retain', length: prevInfo.docLength - prevInfo.end }
			], prevInfo.authorId );
			if ( tx.operations[ 2 ].length === 0 ) {
				tx.operations.pop();
			}
		} else {
			tx = ve.dm.Transaction.static.deserialize( txSerialized );
			if ( prevInfo && !hasOwn.call( txSerialized, 'authorId' ) ) {
				tx.authorId = prevInfo.authorId;
			}
		}
		transactions.push( tx );
		prevInfo = getTransactionInfo( tx );
	}
	return new ve.dm.Change(
		data.start,
		transactions,
		stores.map( deserializeStore ),
		selections
	);
};

/**
 * Deserialize a change from a JSONable object without sanitizing DOM nodes
 *
 * @param {Object} data
 * @return {ve.dm.Change} Deserialized change
 */
ve.dm.Change.static.unsafeDeserialize = function ( data ) {
	return this.deserialize( data, false, true );
};

ve.dm.Change.static.serializeValue = function ( value ) {
	if ( value instanceof ve.dm.Annotation ) {
		return { type: 'annotation', value: value.element };
	} else if ( Array.isArray( value ) && value[ 0 ] instanceof Node ) {
		return { type: 'domNodes', value: value.map( ve.getNodeHtml ).join( '' ) };
	} else {
		return { type: 'plain', value: value };
	}
};

ve.dm.Change.static.deserializeValue = function ( serialized, unsafe ) {
	if ( serialized.type === 'annotation' ) {
		return ve.dm.annotationFactory.createFromElement( serialized.value );
	} else if ( serialized.type === 'domNodes' ) {
		if ( unsafe ) {
			// We can use jQuery here because unsafe sanitization
			// only happens in browser clients.
			// eslint-disable-next-line no-undef
			return $.parseHTML( serialized.value, undefined, true );
		} else {
			// Convert NodeList to Array
			return Array.prototype.slice.call( ve.sanitizeHtml( serialized.value ) );
		}
	} else if ( serialized.type === 'plain' ) {
		return serialized.value;
	} else {
		throw new Error( 'Unrecognized type: ' + serialized.type );
	}
};

/**
 * Rebase parallel transactions transactionA and transactionB onto each other
 *
 * Recalling that a transaction is a mapping from one ve.dm.ElementLinearData state to another,
 * suppose we have two parallel transactions, i.e.:
 *
 * - transactionA mapping docstate0 to some docstateA, and
 * - transactionB mapping docstate0 to some docstateB .
 *
 * Then we want rebasing to give us two new transactions:
 *
 * - aRebasedOntoB mapping docstateB to some docstateC, and
 * - bRebasedOntoA mapping docstateA to docstateC ,
 *
 * so that applying transactionA then bRebasedOntoA results in the same document state as
 * applying transactionB then aRebasedOntoB .
 *
 * However, it is useful to regard some transaction pairs as "conflicting" or unrebasable. In
 * this implementation, transactions are considered to conflict if they have active ranges that
 * overlap, where a transaction's "active range" means the smallest single range in the *start*
 * document outside which the contents are unchanged by the transaction. (In practice the
 * operations within the transaction actually specify which ranges map to where, giving a
 * natural and unambiguous definition of "active range". Also, the identity transaction on a
 * document state has no active range but is trivially rebasable with any parallel
 * transaction).
 *
 * For non-conflicting transactions, rebasing of each transaction is performed by resizing the
 * inactive range either before or after the transaction to accommodate the length difference
 * caused by the other transaction. There is ambiguity in the case where both transactions have
 * a zero-length active range at the same position (i.e. two inserts in the same place); in this
 * case, transactionA's insertion is put before transactionB's.
 *
 * It is impossible for rebasing defined this way to create an invalid transaction that breaks
 * tree validity. This is clear because every position in the rebased transaction's active
 * range has the same node ancestry as the corresponding position before the rebase (else a
 * tag must have changed both before and after that position, contradicting the fact that the
 * transactions' active ranges do not overlap).
 *
 * Also it is clear that for a pair of non-conflicting parallel transactions, applying either
 * one followed by the other rebased will result in the same final document state, as required.
 *
 * @param {ve.dm.Transaction} transactionA Transaction A
 * @param {ve.dm.Transaction} transactionB Transaction B, with the same document start state
 * @return {Mixed[]} [ aRebasedOntoB, bRebasedOntoA ], or [ null, null ] if conflicting
 */
ve.dm.Change.static.rebaseTransactions = function ( transactionA, transactionB ) {
	transactionA = transactionA.clone();
	transactionB = transactionB.clone();
	var infoA = transactionA.getActiveRangeAndLengthDiff();
	var infoB = transactionB.getActiveRangeAndLengthDiff();

	if ( infoA.start === undefined || infoB.start === undefined ) {
		// One of the transactions is a no-op: only need to adjust its retain length.
		// We can safely adjust both, because the no-op must have diff 0
		transactionA.adjustRetain( 'start', infoB.diff );
		transactionB.adjustRetain( 'start', infoA.diff );
	} else if ( infoA.end <= infoB.start ) {
		// This includes the case where both transactions are insertions at the same
		// point
		transactionB.adjustRetain( 'start', infoA.diff );
		transactionA.adjustRetain( 'end', infoB.diff );
	} else if ( infoB.end <= infoA.start ) {
		transactionA.adjustRetain( 'start', infoB.diff );
		transactionB.adjustRetain( 'end', infoA.diff );
	} else {
		// The active ranges overlap: conflict
		return [ null, null ];
	}
	return [ transactionA, transactionB ];
};

/**
 * Rebase a change on top of a parallel committed one
 *
 * Since a change is a stack of transactions, we define change rebasing in terms of transaction
 * rebasing. We require transaction rebasing to meet the three guarantees described above for
 * change rebasing. To be precise, given any transactions a:D1->D2, b:D2->D3 and x:D1->D4, we
 * require that:
 *
 * 1. Transaction conflict well definedness: a|x = 0 if and only if x|a = 0.
 * 2. Transaction commutativity: a * x|a equals x * a|x .
 * 3. Rebasing piecewise: if (x|a)|b != 0, then (x|a)|b equals x|(a * b) .
 *
 * Given committed history consisting of transactions a1,a2,…,aN, and an uncommitted update
 * consisting of transactions b1,b2,…,bM, our approach is to rebase the whole list a1,…,aN
 * over b1, and at the same time rebase b1 onto a1*…*aN.
 * Then we repeat the process for b2, and so on. To rebase a1,…,aN over b1, the following
 * approach would work:
 *
 * a1' := a1|b1
 * a2' := a2|(inv(a1) * b1 * a1')
 * a3' := a3|(inv(a2) * inv(a1) * b1 * a1' * a2')
 * ⋮
 *
 * That is, rebase a_i under a_i-1,…,a_1, then over b1,…,bM, then over a'1,…,a_i-1' .
 *
 * However, because of the way transactions are written, it's not actually easy to implement
 * transaction concatenation, so we would want to calculate a2' as piecewise rebases
 *
 * a2' = ((a2|inv(a1))|b1)|a1'
 *
 * which is unsatisfactory because a2|inv(a1) may well conflict even if a2|(inv(a1) * b1 * a1')
 * as a whole would not conflict (e.g. if b1 modifies only parts of the document distant from a1
 * and a2).
 *
 * So observe that by transaction commutivity we can rewrite a2' as:
 *
 * a2' := a2|(inv(a1) * a1 * b1|a1)
 *      = a2|(b1|a1)
 *
 * and that b1|a1 conflicts only if a1|b1 conflicts (so this introduces no new conflicts). In
 * general we can write:
 *
 * a1' := a1|b1
 * b1' := b1|a1
 * a2' := a2|b1'
 * b1'' := b1'|a2
 * a3' := a3|b1''
 * b1''' := a1''|a3
 *
 * Continuing in this way, we obtain a1',…,aN' rebased over b1, and b1''''''' (N primes)
 * rebased onto a1 * … * aN . Iteratively we can take the same approach to rebase over
 * b2,…,bM, giving both rebased lists as required.
 *
 * If any of the transaction rebases conflict, then we rebase the largest possible
 * non-conflicting initial segment b1,…,bK onto all of a1,…,aN (so clearly K < M).
 *
 * If there are two parallel inserts at the same location, then ordering is ambiguous. We
 * resolve this by putting the insert for the transaction with the highest author ID
 * first (Javascript less-than is used, so comparisons with a null author ID do not fail).
 * If the author IDs are the same, then A's insertion is put before B's.
 *
 * @param {ve.dm.Change} history Committed history
 * @param {ve.dm.Change} uncommitted New transactions, with same start as history
 * @return {Object} Rebased
 * @return {ve.dm.Change} return.rebased Rebase onto history of uncommitted (or an initial segment of it)
 * @return {ve.dm.Change} return.transposedHistory Rebase of history onto initial segment of uncommitted
 * @return {ve.dm.Change|null} return.rejected Unrebasable final segment of uncommitted
 */
ve.dm.Change.static.rebaseUncommittedChange = function ( history, uncommitted ) {
	if ( history.start !== uncommitted.start ) {
		throw new Error( 'Different starts: ' + history.start + ' and ' + uncommitted.start );
	}

	var transactionsA = history.transactions.slice(),
		transactionsB = uncommitted.transactions.slice(),
		storesA = history.getStores(),
		storesB = uncommitted.getStores(),
		selectionsA = OO.cloneObject( history.selections ),
		selectionsB = OO.cloneObject( uncommitted.selections ),
		rejected = null;

	// For each element b_i of transactionsB, rebase the whole list transactionsA over b_i.
	// To rebase a1, a2, a3, …, aN over b_i, first we rebase a1 onto b_i. Then we rebase
	// a2 onto some b', defined as
	//
	// b_i' := b_i|a1 , that is b_i.rebasedOnto(a1)
	//
	// (which as proven above is equivalent to inv(a1) * b_i * a1)
	//
	// Similarly we rebase a3 onto b_i'' := b_i'|a2, and so on.
	//
	// The rebased a_j are used for the transposed history: they will all get rebased over the
	// rest of transactionsB in the same way.
	// The fully rebased b_i forms the i'th element of the rebased transactionsB.
	//
	// If any rebase b_i|a_j fails, we stop rebasing at b_i (i.e. finishing with b_{i-1}).
	// We return
	// - rebased: (uncommitted sliced up to i) rebased onto history
	// - transposedHistory: history rebased onto (uncommitted sliced up to i)
	// - rejected: uncommitted sliced from i onwards
	bLoop:
	for ( var i = 0, iLen = transactionsB.length; i < iLen; i++ ) {
		var b = transactionsB[ i ];
		var storeB = storesB[ i ];
		var rebasedTransactionsA = [];
		var rebasedStoresA = [];
		for ( var j = 0, jLen = transactionsA.length; j < jLen; j++ ) {
			var a = transactionsA[ j ];
			var storeA = storesA[ j ];
			var rebases;
			if ( b.authorId < a.authorId ) {
				rebases = ve.dm.Change.static.rebaseTransactions( b, a ).reverse();
			} else {
				rebases = ve.dm.Change.static.rebaseTransactions( a, b );
			}
			if ( rebases[ 0 ] === null ) {
				rejected = uncommitted.mostRecent( uncommitted.start + i );
				transactionsB.length = i;
				storesB.length = i;
				selectionsB = {};
				break bLoop;
			}
			rebasedTransactionsA[ j ] = rebases[ 0 ];
			rebasedStoresA[ j ] = storeA.difference( storeB );
			b = rebases[ 1 ];
			storeB = storeB.difference( storeA );
		}
		transactionsA = rebasedTransactionsA;
		storesA = rebasedStoresA;
		transactionsB[ i ] = b;
		storesB[ i ] = storeB;
	}

	// Length calculations below assume no removal of empty rebased transactions
	var rebased = new ve.dm.Change(
		uncommitted.start + transactionsA.length,
		transactionsB,
		storesB,
		{}
	);
	var transposedHistory = new ve.dm.Change(
		history.start + transactionsB.length,
		transactionsA,
		storesA,
		{}
	);
	var authorId;
	for ( authorId in selectionsB ) {
		authorId = +authorId;
		rebased.selections[ authorId ] = selectionsB[ authorId ].translateByChange( transposedHistory, authorId );
	}
	for ( authorId in selectionsA ) {
		authorId = +authorId;
		transposedHistory.selections[ authorId ] = selectionsA[ authorId ].translateByChange( rebased, authorId );
	}
	return {
		rebased: rebased,
		transposedHistory: transposedHistory,
		rejected: rejected
	};
};

/**
 * Get info about a transaction if it is a "simple replacement", or null if not
 *
 * A simple replacement transaction is one that has just one retain op
 *
 * @param {ve.dm.Transaction} tx The transaction
 * @return {Object|null} Info about the transaction if a simple replacement, else null
 * @return {number} return.start The start offset of the replacement
 * @return {number} return.end The end offset of the replacement (after replacement)
 * @return {number} return.docLength The total length of the document (after replacement)
 * @return {number} return.authorId The author ID
 * @return {Object|null} return.uniformInsert The insertion as uniform text, or null if not
 * @return {string} return.uniformInsert.text The plain text of the uniform text
 * @return {string} return.uniformInsert.annotations Annotation hashes for all text
 * @return {string} return.uniformInsert.annotationString Comma-separated annotation hashes
 */
ve.dm.Change.static.getTransactionInfo = function ( tx ) {
	// Copy of ve.dm.ElementLinearData.static.getAnnotationHashesFromItem, but we
	// don't want to load all of ElementLinearData and its dependencies on the server-side.
	function getAnnotations( item ) {
		if ( typeof item === 'string' ) {
			return [];
		} else if ( item.annotations ) {
			return item.annotations.slice();
		} else if ( item[ 1 ] ) {
			return item[ 1 ].slice();
		} else {
			return [];
		}
	}

	/**
	 * Get an item's single code unit (without annotation), or null if not a code unit
	 *
	 * @param {Object|Array|string} item The item
	 * @return {string|null} The single code unit, or null if not a code unit
	 */
	function getSingleCodeUnit( item ) {
		if ( typeof item === 'string' && item.length === 1 ) {
			return item;
		}
		if ( Array.isArray( item ) && item[ 0 ].length === 1 ) {
			return item[ 0 ];
		}
		return null;
	}

	/**
	 * Get info about the "uniform text" from an item array, or null if not uniform text
	 *
	 * The item array is uniform text if all items have the same annotations, and
	 * every item is a single code unit of text
	 *
	 * @param {Array} items The items
	 * @return {Object|null} Info about the uniform text, or null if not uniform text
	 * @return {string} return.text The code units, in a single string
	 * @return {string} return.annotations Annotation hashes for all text
	 * @return {string} return.annotationString Comma-separated annotation hashes
	 */
	function getUniformText( items ) {
		var codeUnits = [];
		if ( items.length === 0 ) {
			return null;
		}
		var codeUnit = getSingleCodeUnit( items[ 0 ] );
		if ( codeUnit === null ) {
			return null;
		}
		codeUnits.push( codeUnit );
		var annotations = getAnnotations( items[ 0 ] );
		var annotationString = annotations.join( ',' );
		for ( var i = 1, iLen = items.length; i < iLen; i++ ) {
			codeUnit = getSingleCodeUnit( items[ i ] );
			if ( codeUnit === null ) {
				return null;
			}
			codeUnits.push( codeUnit );
			if ( annotationString !== getAnnotations( items[ i ] ).join( ',' ) ) {
				return null;
			}
		}
		return {
			text: codeUnits.join( '' ),
			annotations: annotations,
			annotationString: annotationString
		};
	}

	var op0 = tx.operations[ 0 ];
	var op1 = tx.operations[ 1 ];
	var op2 = tx.operations[ 2 ];
	var replaceOp, start, end, docLength;
	if (
		op0 &&
		op0.type === 'replace' &&
		( !op1 || op1.type === 'retain' ) &&
		!op2
	) {
		replaceOp = op0;
		start = 0;
		end = start + replaceOp.insert.length;
		docLength = end;
	} else if (
		op0 &&
		op0.type === 'retain' &&
		op1 &&
		op1.type === 'replace' &&
		( !op2 || op2.type === 'retain' )
	) {
		replaceOp = op1;
		start = op0.length;
		end = start + replaceOp.insert.length;
		docLength = end + ( op2 ? op2.length : 0 );
	} else {
		return null;
	}

	return {
		start: start,
		end: end,
		docLength: docLength,
		authorId: tx.authorId,
		uniformInsert: getUniformText( replaceOp.insert )
	};
};

/* Methods */

/**
 * Create a clone of this Change
 *
 * @return {ve.dm.Change} Clone of this change
 */
ve.dm.Change.prototype.clone = function () {
	return this.constructor.static.unsafeDeserialize( this.toJSON() );
};

/**
 * @return {boolean} True if this change has no transactions or selections
 */
ve.dm.Change.prototype.isEmpty = function () {
	return this.transactions.length === 0 && Object.keys( this.selections ).length === 0;
};

/**
 * @return {number} The number of transactions
 */
ve.dm.Change.prototype.getLength = function () {
	return this.transactions.length;
};

/**
 * Get the store items introduced by transaction n
 *
 * @param {number} n The index of a transaction within the change
 * @return {ve.dm.HashValueStore} The store items introduced by transaction n
 */
ve.dm.Change.prototype.getStore = function ( n ) {
	return this.store.slice(
		n > 0 ? this.storeLengthAtTransaction[ n - 1 ] : 0,
		this.storeLengthAtTransaction[ n ]
	);
};

/**
 * Get the stores for each transaction
 *
 * @return {ve.dm.HashValueStore[]} Each transaction's store items (shallow copied store)
 */
ve.dm.Change.prototype.getStores = function () {
	var stores = [],
		start = 0;
	for ( var i = 0, len = this.getLength(); i < len; i++ ) {
		var end = this.storeLengthAtTransaction[ i ];
		stores.push( this.store.slice( start, end ) );
		start = end;
	}
	return stores;
};

/**
 * @return {number|null} The first author in a transaction or selection change, or null if empty
 */
ve.dm.Change.prototype.firstAuthorId = function () {
	if ( this.transactions.length ) {
		return this.transactions[ 0 ].authorId;
	}
	var authors = Object.keys( this.selections );
	if ( authors.length ) {
		return +authors[ 0 ];
	}
	return null;
};

/**
 * Get a human-readable summary of the change
 *
 * @return {string} Human-readable summary
 */
ve.dm.Change.prototype.summarize = function () {
	return '{ start: ' + this.start + ', txs: [ ' +
		this.transactions.map( function ( tx ) {
			return tx.summarize();
		} ).join( ', ' ) + ' ] }';
};

/**
 * Get the change that backs out this change.
 *
 * Note that applying it will not revert start or remove stored items
 *
 * @return {ve.dm.Change} The change that backs out this change
 */
ve.dm.Change.prototype.reversed = function () {
	return new ve.dm.Change(
		this.start + this.transactions.length,
		this.transactions.map( function ( tx ) {
			return ve.dm.Transaction.prototype.reversed.call( tx );
		} ).reverse(),
		// Empty store for each transaction (reverting cannot possibly add new annotations)
		this.transactions.map( function () {
			return new ve.dm.HashValueStore();
		} ),
		{}
	);
};

/**
 * Rebase this change onto other (ready to apply on top of other)
 *
 * @param {ve.dm.Change} other Other change
 * @return {ve.dm.Change|null} Rebased change applicable on top of other, or null if rebasing fails
 * @throws {Error} If this change and other have different starts
 */
ve.dm.Change.prototype.rebasedOnto = function ( other ) {
	var rebases = this.constructor.static.rebaseUncommittedChange( other, this );
	return rebases.rejected ? null : rebases.rebased;
};

/**
 * Build a composite change from two consecutive changes
 *
 * @param {ve.dm.Change} other Change that starts immediately after this
 * @return {ve.dm.Change} Composite change
 * @throws {Error} If other does not start immediately after this
 */
ve.dm.Change.prototype.concat = function ( other ) {
	if ( other.start !== this.start + this.transactions.length ) {
		throw new Error( 'this ends at ' + ( this.start + this.transactions.length ) +
			' but other starts at ' + other.start );
	}
	return new ve.dm.Change(
		this.start,
		this.transactions.concat( other.transactions ),
		this.getStores().concat( other.getStores() ),
		other.selections
	);
};

/**
 * Push a transaction, after having grown the hash value store if required
 *
 * @param {ve.dm.Transaction} transaction The transaction
 * @param {number} storeLength The corresponding store length required
 */
ve.dm.Change.prototype.pushTransaction = function ( transaction, storeLength ) {
	if ( typeof storeLength !== 'number' ) {
		throw new Error( 'Expected numerical storeLength argument, not ' + storeLength );
	}
	this.transactions.push( transaction );
	this.storeLengthAtTransaction.push( storeLength );
};

/**
 * Push another change onto this change
 *
 * @param {ve.dm.Change} other Change that starts immediately after this
 * @throws {Error} If other does not start immediately after this
 */
ve.dm.Change.prototype.push = function ( other ) {
	var change = this;
	if ( other.start !== this.start + this.getLength() ) {
		throw new Error( 'this ends at ' + ( this.start + this.getLength() ) +
			' but other starts at ' + other.start );
	}
	var stores = other.getStores();
	for ( var i = 0, iLen = other.transactions.length; i < iLen; i++ ) {
		var transaction = other.transactions[ i ];
		var store = stores[ i ];
		change.store.merge( store );
		this.pushTransaction( transaction, change.store.getLength() );
	}
	this.selections = OO.cloneObject( other.selections );
};

/**
 * Build a composite change from two parallel changes
 *
 * @param {ve.dm.Change} other Change parallel to this
 * @return {ve.dm.Change} Composite change
 * @throws {Error} If this change and other have different starts
 */
ve.dm.Change.prototype.concatRebased = function ( other ) {
	return this.concat( other.rebasedOnto( this ) );
};

/**
 * Build a change from the last (most recent) transactions
 *
 * @param {number} start Start offset
 * @return {ve.dm.Change} Subset of this change with only the most recent transactions
 */
ve.dm.Change.prototype.mostRecent = function ( start ) {
	if ( arguments.length > 1 ) {
		throw new Error( 'storeStart is no longer needed' );
	}
	return new ve.dm.Change(
		start,
		this.transactions.slice( start - this.start ),
		this.getStores().slice( start - this.start ),
		OO.cloneObject( this.selections )
	);
};

/**
 * Build a change from the first (least recent) transactions of this change.
 *
 * Always removes selections.
 *
 * @param {number} length Number of transactions
 * @return {ve.dm.Change} Subset of this change with only the least recent transactions
 */
ve.dm.Change.prototype.truncate = function ( length ) {
	if ( arguments.length > 1 ) {
		throw new Error( 'storeLength is no longer needed' );
	}
	return new ve.dm.Change(
		this.start,
		this.transactions.slice( 0, length ),
		this.getStores().slice( 0, length ),
		{}
	);
};

/**
 * Apply change to surface
 *
 * @param {ve.dm.Surface} surface Surface in change start state
 * @param {boolean} [applySelection] Apply a selection based on the modified range
 */
ve.dm.Change.prototype.applyTo = function ( surface, applySelection ) {
	var doc = surface.getDocument();
	if ( this.start !== doc.completeHistory.getLength() ) {
		throw new Error( 'Change starts at ' + this.start + ', but doc is at ' + doc.completeHistory.getLength() );
	}
	this.getStores().forEach( function ( store ) {
		doc.store.merge( store );
	} );
	// Isolate other users' changes from ours with a breakpoint
	surface.breakpoint();
	this.transactions.forEach( function ( tx ) {
		surface.change( tx );
		// Don't mark as applied: this.start already tracks this
		tx.applied = false;

		// TODO: This would be better fixed by T202730
		if ( applySelection ) {
			var range = tx.getModifiedRange( doc );
			// If the transaction only touched the internal list, there is no modified range within the main document
			if ( range ) {
				var offset = doc.getNearestCursorOffset( range.end, -1 );
				if ( offset !== -1 ) {
					surface.setSelection( new ve.dm.LinearSelection( new ve.Range( offset ) ) );
				}
			}
		}
	} );
	surface.breakpoint();
};

/**
 * Unapply change to surface, including truncating history and store
 *
 * @param {ve.dm.Surface} surface Surface in change end state
 */
ve.dm.Change.prototype.unapplyTo = function ( surface ) {
	var doc = surface.getDocument(),
		historyLength = doc.completeHistory.getLength() - this.getLength();
	if ( this.start !== historyLength ) {
		throw new Error( 'Invalid start: change starts at ' + this.start + ', but doc would be at ' + historyLength );
	}
	this.transactions.slice().reverse().forEach( function ( tx ) {
		surface.change( tx.reversed() );
	} );
	doc.completeHistory.transactions.length = historyLength;
	doc.completeHistory.storeLengthAtTransaction.length = historyLength;
	doc.store.truncate( doc.completeHistory.storeLengthAtTransaction[ historyLength - 1 ] );
};

/**
 * Append change transactions to history
 *
 * @param {ve.dm.Document} documentModel
 * @throws {Error} If this change does not start at the top of the history
 */
ve.dm.Change.prototype.addToHistory = function ( documentModel ) {
	documentModel.completeHistory.push( this );
};

/**
 * Remove change transactions from history
 *
 * @param {ve.dm.Document} doc
 * @throws {Error} If this change does not end at the top of the history
 */
ve.dm.Change.prototype.removeFromHistory = function ( doc ) {
	if ( this.start + this.getLength() !== doc.completeHistory.getLength() ) {
		throw new Error( 'this ends at ' + ( this.start + this.getLength() ) +
			' but history ends at ' + doc.completeHistory.getLength() );
	}
	doc.completeHistory.transactions.length -= this.transactions.length;
	doc.completeHistory.storeLengthAtTransaction.length -= this.transactions.length;
	doc.store.truncate( doc.completeHistory.storeLengthAtTransaction[ doc.completeHistory.getLength() - 1 ] );
};

/**
 * Serialize the change to a JSONable object
 *
 * Store values can be serialized, or kept verbatim (which only makes sense if they are serialized
 * already, i.e. the Change object was created by #deserialize without deserializing store values).
 *
 * @param {boolean} [preserveStoreValues] If true, keep store values verbatim instead of serializing
 * @return {Object} JSONable object
 */
ve.dm.Change.prototype.serialize = function ( preserveStoreValues ) {
	var getTransactionInfo = this.constructor.static.getTransactionInfo,
		selections = {},
		transactions = [];

	// Recursively serialize, so this method is the inverse of deserialize
	// without having to use JSON.stringify (which is also recursive).
	for ( var authorId in this.selections ) {
		selections[ authorId ] = this.selections[ authorId ].toJSON();
	}
	var serializeStoreValues = preserveStoreValues ? function noop( x ) {
		return x;
	} : this.constructor.static.serializeValue;
	var serializeStore = function ( store ) {
		return store.serialize( serializeStoreValues );
	};
	var prevInfo;
	for ( var i = 0, iLen = this.transactions.length; i < iLen; i++ ) {
		var tx = this.transactions[ i ];
		var info = getTransactionInfo( tx );
		if (
			info &&
			prevInfo &&
			info.authorId === prevInfo.authorId &&
			info.start === prevInfo.end &&
			info.uniformInsert &&
			prevInfo.uniformInsert &&
			info.uniformInsert.annotationString === prevInfo.uniformInsert.annotationString
		) {
			transactions.push( info.uniformInsert.text );
		} else {
			var txSerialized = tx.toJSON();
			if ( i > 0 && tx.authorId === this.transactions[ i - 1 ].authorId ) {
				delete txSerialized.authorId;
			}
			transactions.push( txSerialized );
		}
		prevInfo = info;
	}
	var stores = this.getStores().map( serializeStore );
	var data = {
		start: this.start,
		transactions: transactions
	};
	// Only set stores if at least one is non-null
	if ( stores.some( function ( store ) {
		return store !== null;
	} ) ) {
		data.stores = stores;
	}
	if ( Object.keys( selections ).length ) {
		data.selections = selections;
	}
	return data;
};

/**
 * Called automatically by JSON.stringify, see #serialize.
 *
 * @param {string} [key] Key in parent object
 * @return {Object} JSONable object
 */
ve.dm.Change.prototype.toJSON = function () {
	// Ensure no native arguments are passed through to #serialize.
	return this.serialize();
};

/**
 * Get a Change with all this Change's Transactions compacted into one (or zero)
 *
 * The Change has the same effect when applied as this Change does, but it may cause
 * rebase conflicts where this change does not.
 *
 * TODO: introduce a "histLength" feature so the new change can be considered as
 * having length > 1.
 *
 * @return {ve.dm.Change} One-Transaction version of this Change (or empty change)
 */
ve.dm.Change.prototype.squash = function () {
	if ( this.transactions.length <= 1 ) {
		return this.clone();
	}
	return new ve.dm.Change(
		this.start,
		[ ve.dm.TransactionSquasher.static.squash( this.transactions ) ],
		[ this.store.slice() ],
		// Shallow clone (the individual selections are immutable so need no cloning)
		ve.cloneObject( this.selections )
	);
};
