/*!
 * VisualEditor DataModel TransactionSquasher class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Squasher to create one transaction from multiple transactions applicable in turn
 *
 * The squashed transaction has the same effect on a document as applying the original
 * transactions in turn, but it may cause rebase conflicts where the original sequence
 * of transactions would not have.
 *
 * Suppose we have linmod arrays A, B, C, D. Note that (x,A->B,m) then (x,C->D,n) becomes
 * (x,A.concat(C*)->D.concat(B*),min(m,n)) Where len = min(B.length, C.length) and
 * C*=C.slice(len) and B*=B.slice(len).  I.e. "A->B then C->D" becomes "remove A, remove C*,
 * insert D, insert B*". Examples:
 * 1234Aa5678 (4,Aa->Bb,4) 1234Bb5678 (4,B->D,5) 1234Db5678 --> (4,Aa->Db,4)
 * 1234Aa5678 (4,Aa->Bb,4) 1234Bb5678 (4,Bb5->Dd,3) 1234Dd678 --> (4,Aa5->Dd,3)
 *
 * The same sort of thing happens if the starts are not the same, e.g.
 * helloworld (2,ll>LL,1,wo>WO,3) heLLoWOrld (3,LoW>HI,4) heLHIrld --> (2,llowo>LHI,3)
 * hiwed (1,i>ello,1,e>orl,1) helloworld (2,llowor>a,2) heald --> (1,iwe>eal,1)
 *
 * However, removal followed by reinsertion cannot be stripped out entirely, because then
 * the squashed transaction, considered as a partial function between documents, would have
 * a larger preimage than the composition of the original transactions. This can probably
 * break things like associativity). Example:
 *
 * hello! (1,ello>iworld,1) hiworld! (1,i>ello,6) helloworld! --> (1,ello>elloworld,1)
 *
 * For annotations in the follow-up transaction, two forms of processing are needed: annotating
 * previously-inserted content, and adding the annotations operation into the transaction for
 * retained ranges.
 *
 * @class
 * @constructor
 * @param {ve.dm.Transaction} transaction Base transaction to clone then squash others onto
 */
ve.dm.TransactionSquasher = function VeDmTransactionSquasher( transaction ) {
	/**
	 * @property {ve.dm.Transaction} transaction Transaction being squashed together
	 */
	this.transaction = transaction.clone();

	/**
	 * @property {Object[]} operations Reference to .operations within the tx
	 */
	this.operations = this.transaction.operations;

	/**
	 * @property {Object} op During squashIn, the current op within operations
	 */
	this.op = this.operations[ 0 ];

	/**
	 * @property {number} index During squashIn, index of current op within operations
	 */
	this.index = 0;

	/**
	 * During squashIn, post-transaction offset within the current op
	 *
	 * @property {number} offset During squashIn, post-tx linmod offset within current op.
	 * "Post transaction" means that for replacements, the offset is within the insert
	 * block. The reason we care about post-transaction offsets is that the match the
	 * pre-transaction offsets of the next transaction.
	 */
	this.offset = 0;

	/**
	 * @property {number} globalOffset During squashIn, global offset over all operations
	 */
	this.globalOffset = 0;

	/**
	 * @property {Object} attributeOperations During squashIn, live references to attribute
	 * operations at current offset, keyed by attribute name, or null if at an open element
	 */
	this.attributeOperations = {};
};

/* Inheritance */

OO.initClass( ve.dm.TransactionSquasher );

/* Static methods */

/**
 * Test whether two linmod items have equal values
 *
 * @param {Mixed} item1 A data item
 * @param {Mixed} item2 Another data item
 * @return {boolean} Whether the items have equal values
 */
ve.dm.TransactionSquasher.static.equalItems = function ( item1, item2 ) {
	function stringifyItem( item ) {
		return JSON.stringify( item, function ( key, value ) {
			return key === 'changesSinceLoad' ? undefined : value;
		} );
	}
	// Compare serializations, so that reference types need not be reference-equal
	return stringifyItem( item1 ) === stringifyItem( item2 );
};

/**
 * Squash an array of consecutive transactions into a single transaction
 *
 * @param {ve.dm.Transaction[]} transactions Non-empty array of consecutive transactions
 * @return {ve.dm.Transaction} Single transaction with the same content as the transaction array
 */
ve.dm.TransactionSquasher.static.squash = function ( transactions ) {
	if ( transactions.length === 0 ) {
		throw new Error( 'Cannot squash empty transaction array' );
	}
	var squasher = new ve.dm.TransactionSquasher( transactions[ 0 ] );
	for ( var i = 1, iLen = transactions.length; i < iLen; i++ ) {
		squasher.squashIn( transactions[ i ] );
	}
	return squasher.getTransaction();
};

/* Methods */

/**
 * Get the Transaction as-is
 *
 * @return {ve.dm.Transaction} The transaction
 */
ve.dm.TransactionSquasher.prototype.getTransaction = function () {
	return this.transaction;
};

/**
 * Modify our Transaction in-place to incorporate a follow-up transaction
 *
 * Applying the modified transaction has the same effect as applying the original
 * transaction then the follow-up, but it may cause rebase conflicts where the original
 * pair of transactions would not have.
 *
 * @param {ve.dm.Transaction} tx Follow-up transaction (that can apply immediately after this)
 */
ve.dm.TransactionSquasher.prototype.squashIn = function ( tx ) {
	// Walk over the document offsets in our transaction, modifying operations in-place
	// to incorporate the operations in tx
	this.index = 0;
	this.offset = 0;
	this.globalOffset = 0;
	this.op = this.operations[ this.index ];
	this.changes = [];
	this.oldChanges = [];
	this.readAttributes();
	// Do not cache length, as we may splice the list
	for ( var i = 0; i < tx.operations.length; i++ ) {
		var op = tx.operations[ i ];
		var consumed;
		if ( op.type === 'retain' ) {
			var retainLength = op.length;
			while ( retainLength > 0 ) {
				consumed = this.processRetain( retainLength );
				retainLength -= consumed;
			}
		} else if ( op.type === 'replace' ) {
			var items = JSON.parse( JSON.stringify( op.remove ) );
			while ( items.length > 0 ) {
				consumed = this.processRemove( items );
				items.splice( 0, consumed );
			}
			items = JSON.parse( JSON.stringify( op.insert ) );
			while ( items.length > 0 ) {
				consumed = this.processInsert( items );
				items.splice( 0, consumed );
			}
		} else if ( op.type === 'attribute' ) {
			this.processAttribute( op.key, op.from, op.to );
		} else {
			throw new Error( 'Unknown operation type ' + op.type );
		}
	}
};

/**
 * Process the retention of content, stopping part-way if convenient
 *
 * @private
 * @param {number} maxLength The maximum amount of content to retain
 * @return {number} The amount of content retained
 */
ve.dm.TransactionSquasher.prototype.processRetain = function ( maxLength ) {
	this.normalizePosition();
	if ( !this.op ) {
		throw new Error( 'Past end of transaction' );
	}

	var len;
	if ( this.op.type === 'retain' ) {
		len = Math.min( maxLength, this.op.length - this.offset );
		this.offset += len;
		this.globalOffset += len;
		this.attributeOperations = {};
		return len;
	}
	if ( this.op.type === 'replace' ) {
		// Apply annotation changes to inserted content
		len = Math.min( maxLength, this.op.insert.length - this.offset );

		// There is never any need to adjust spliceAt, because the splices are always
		// applied in the same order in which they were generated

		this.offset += len;
		this.globalOffset += len;
		this.readAttributes();
		return len;
	}
	if ( this.op.type === 'attribute' ) {
		// Do nothing
		this.index++;
		this.op = this.operations[ this.index ];
		// By assumption, this.offset is already 0
		this.attributeOperations[ this.op.key ] = this.op;
		return 0;
	}
	throw new Error( 'Unknown op type: ' + this.op.type );
};

/**
 * Process the removal of some items, stopping part-way if convenient
 *
 * If some of the removal is undoing an insertion in this.transaction, then the "cancelled"
 * content is stripped out entirely from the squashed transaction.
 *
 * @private
 * @param {Object[]} items Items to remove some of; can be modified in place (annotated)
 * @return {number} The length of the initial slice of items that was removed
 */
ve.dm.TransactionSquasher.prototype.processRemove = function ( items ) {
	var tryUnsplit = false;

	this.normalizePosition();
	if ( !this.op ) {
		throw new Error( 'Past end of transaction' );
	}
	var removal;
	var len;
	if ( this.op.type === 'retain' ) {
		this.splitIfInterior();
		// Now we must be at the start of a retain
		len = Math.min( items.length, this.op.length );
		this.op.length -= len;
		if ( this.op.length === 0 ) {
			tryUnsplit = true;
			// Remove empty retain
			this.operations.splice( this.index, 1 );
			// this.op may become undefined; ok
			this.op = this.operations[ this.index ];
		}
		removal = items.slice( 0, len );
		if ( this.offset === 0 && this.op && this.op.type === 'replace' ) {
			// If we're at the start of a replace op, prepend to it
			ve.batchSplice(
				this.op.remove,
				0,
				0,
				removal
			);
		} else {
			// Get the immediately preceding replace op, or insert an empty one
			var replaceOp = this.operations[ this.index - 1 ];
			if ( !replaceOp || replaceOp.type !== 'replace' ) {
				replaceOp = { type: 'replace', remove: [], insert: [] };
				this.operations.splice( this.index, 0, replaceOp );
				this.index++;
			}
			ve.batchSplice(
				replaceOp.remove,
				replaceOp.remove.length,
				0,
				removal
			);
		}
		if ( tryUnsplit ) {
			this.tryUnsplit();
		}
		this.readAttributes();
		return len;
	}
	if ( this.op.type === 'replace' ) {
		// Check removal against insertion, then cancel them out
		len = Math.min( items.length, this.op.insert.length - this.offset );
		// len must be greater than zero, since we're not at the end of this op
		removal = items.slice( 0, len );
		for ( var i = 0; i < len; i++ ) {
			if ( !this.constructor.static.equalItems(
				removal[ i ],
				this.op.insert[ this.offset + i ]
			) ) {
				throw new Error( 'Remove does not match insert' );
			}
		}
		this.op.insert.splice( this.offset, len );
		if ( this.op.remove.length === 0 && this.op.insert.length === 0 ) {
			// Empty replacement: delete it
			this.operations.splice( this.index, 1 );
			this.op = this.operations[ this.index ];
			// By assumption, this.offset is already 0
			this.tryUnsplit();
		}
		this.readAttributes();
		return len;
	}
	if ( this.op.type === 'attribute' ) {
		// Apply the reversed attribute change to the item being removed
		this.changeElement( items[ 0 ], this.op.key, this.op.to, this.op.from );
		this.operations.splice( this.index, 1 );
		this.op = this.operations[ this.index ];
		// By assumption, this.offset is already 0
		this.tryUnsplit();
		return 0;
	}
	throw new Error( 'Unknown operation type ' + this.op.type );
};

/**
 * Process the insertion of some items, stopping part-way if convenient
 *
 * If some of the insertion is undoing a removal in this.transaction, then the "cancelled"
 * content effectively becomes part of an identity replacement: replace 'foo' with 'foo'.
 * (The content cannot be stripped out entirely from the squashed transaction, because then
 * the squashed transaction, considered as a partial function between documents, would have
 * a larger preimage than the composition of the original transactions. This can probably
 * break things like associativity).
 *
 * @private
 * @param {Object[]} items Items to insert some of
 * @return {number} The length of the initial slice of items that was inserted
 */
ve.dm.TransactionSquasher.prototype.processInsert = function ( items ) {
	this.normalizePosition();
	if ( !this.op || this.op.type === 'retain' || this.op.type === 'attribute' ) {
		if ( this.op && this.op.type === 'retain' ) {
			// in a retain
			this.splitIfInterior();
		}
		// We must be at the start of this.op (or at the end if !this.op)
		// Get the immediately preceding replace op, or insert an empty one
		var replaceOp = this.operations[ this.index - 1 ];
		if ( !replaceOp || replaceOp.type !== 'replace' ) {
			replaceOp = { type: 'replace', remove: [], insert: [] };
			this.operations.splice( this.index, 0, replaceOp );
			this.index++;
			// By hypothesis, this.offset is already zero
		}
		ve.batchSplice(
			replaceOp.insert,
			replaceOp.insert.length,
			0,
			items
		);
		this.globalOffset += items.length;
		this.attributeOperations = {};
		return items.length;
	}
	if ( this.op.type === 'replace' ) {
		// Do *not* try to cancel insertions against a matching prior removal,
		// because it would cause the squashed transaction to "forget" prior
		// linmod content that the original transaction pair requires. This
		// breaks useful properties like associativity.
		ve.batchSplice( this.op.insert, this.offset, 0, items );
		this.offset += items.length;
		this.globalOffset += items.length;
		this.attributeOperations = {};
		return items.length;
	}
	throw new Error( 'Unknown operation type ' + this.op.type );
};

/**
 * Process the setting of an attribute
 *
 * The case from === to is possible. An identity attribute change still proves there is
 * an open element at this position, so cannot be stripped
 *
 * @private
 * @param {string} key The attribute key
 * @param {Mixed} from The old value
 * @param {Mixed} to The new value
 */
ve.dm.TransactionSquasher.prototype.processAttribute = function ( key, from, to ) {
	this.normalizePosition();
	if ( this.op && this.op.type === 'replace' && this.offset < this.op.insert.length ) {
		// If we're at some insert content, then the attribute should apply to it
		var item = this.op.insert[ this.offset ];
		if ( !item || !item.type || item.type[ 0 ] === '/' ) {
			throw new Error( 'Expected open element' );
		}
		this.changeElement( this.op.insert[ this.offset ], key, from, to );
	} else {
		var op = this.attributeOperations[ key ];
		if ( op ) {
			if ( !this.constructor.static.equalItems( op.to, from ) ) {
				throw new Error( 'Unexpected prior attribute value' );
			}
			// Modify in place
			op.to = to;
		} else {
			op = {
				type: 'attribute',
				key: key,
				from: from,
				to: to
			};
			this.splitIfInterior();
			this.operations.splice( this.index, 0, op );
			this.attributeOperations[ key ] = op;
			this.index++;
		}
	}
};

/**
 * Change an attribute in an open element
 *
 * @private
 * @param {Object} openElement The open element
 * @param {string} key The attribute name
 * @param {Mixed} from Old value, or undefined if the attribute is being created
 * @param {Mixed} to New value, or undefined if the attribute is being removed
 */
ve.dm.TransactionSquasher.prototype.changeElement = function ( openElement, key, from, to ) {
	if ( !openElement.attributes ) {
		openElement.attributes = {};
	}
	if ( !this.constructor.static.equalItems(
		openElement.attributes[ key ],
		from
	) ) {
		throw new Error( 'Unexpected prior attribute value' );
	}
	if ( to === undefined ) {
		delete openElement.attributes[ key ];
		if ( !Object.keys( openElement.attributes ).length ) {
			delete openElement.attributes;
		}
	} else {
		openElement.attributes[ key ] = to;
	}
};

/**
 * Normalize .index, .offset and .op so we're not at the end of a replace/retain
 *
 * @private
 */
ve.dm.TransactionSquasher.prototype.normalizePosition = function () {
	while ( this.op && (
		( this.op.type === 'retain' && this.offset === this.op.length ) ||
		( this.op.type === 'replace' && this.offset === this.op.insert.length )
	) ) {
		this.index++;
		this.offset = 0;
		// op may become undefined; ok
		this.op = this.operations[ this.index ];
		this.readAttributes();
	}
};

/**
 * Read the open element at the current offset (if any)
 *
 * Sets this.openElement to the open element (or null)
 * Sets this.attribute to an object containing attribute key-values (or {})
 *
 * @private
 */
ve.dm.TransactionSquasher.prototype.readAttributes = function () {
	var index = this.index,
		op = this.operations[ index ],
		offset = this.offset;

	this.attributeOperations = {};
	while ( true ) {
		if ( !op ) {
			return;
		}
		if ( op.type === 'replace' ) {
			if ( offset < op.insert.length ) {
				// At an openElement, so there should be no attributeOperations
				return;
			}
		} else if ( op.type === 'attribute' ) {
			this.attributeOperations[ op.key ] = op;
		} else if ( op.type === 'retain' && offset < op.length ) {
			return;
		}
		// Else at the start of an insert / retain: step backwards
		index++;
		offset = 0;
		op = this.operations[ index ];
	}
};

/**
 * If in the interior of a retain operation, split it here without moving.
 *
 * For retain, the length is split at the current offset (throws an error if at the end)
 * For all other operations, throws an error if not at the start
 *
 * Afterwards, this.offset is guaranteed to be 0.
 *
 * @private
 */
ve.dm.TransactionSquasher.prototype.splitIfInterior = function () {
	var type = this.op && this.op.type;
	if ( this.offset === 0 ) {
		// No need to split
		return;
	}
	if ( type !== 'retain' ) {
		throw new Error( 'Non-zero offset, but op type is ' + type );
	}
	var len = this.op.length;
	var remainder = len - this.offset;
	if ( remainder < 0 ) {
		throw new Error( 'Beyond the end of retain' );
	}
	if ( remainder === 0 ) {
		throw new Error( 'Cannot split at the end of retain' );
	}
	this.op.length = this.offset;
	this.index++;
	this.offset = 0;
	this.op = { type: 'retain', length: remainder };
	this.operations.splice( this.index, 0, this.op );
};

/**
 * If this operation and the previous one are retains, join them
 *
 * @private
 */
ve.dm.TransactionSquasher.prototype.tryUnsplit = function () {
	var prevOp = this.operations[ this.index - 1 ];
	if ( !this.op || !prevOp ) {
		return;
	}
	if ( prevOp.type === 'retain' && this.op.type === 'retain' ) {
		this.offset += prevOp.length;
		prevOp.length += this.op.length;
		this.operations.splice( this.index, 1 );
		this.index--;
		this.op = prevOp;
	} else if ( prevOp.type === 'replace' && this.op.type === 'replace' ) {
		this.offset += prevOp.insert.length;
		ve.batchSplice(
			prevOp.remove,
			prevOp.remove.length,
			0,
			this.op.remove
		);
		ve.batchSplice(
			prevOp.insert,
			prevOp.insert.length,
			0,
			this.op.insert
		);
		this.operations.splice( this.index, 1 );
		this.index--;
		this.op = prevOp;
	}
	// Else do nothing
};
