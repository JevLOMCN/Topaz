/*!
 * VisualEditor Selection class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * @class
 * @abstract
 * @constructor
 */
ve.dm.Selection = function VeDmSelection() {
};

/* Inheritance */

OO.initClass( ve.dm.Selection );

/* Static Properties */

ve.dm.Selection.static.type = null;

/* Static Methods */

/**
 * Create a new selection from a JSON serialization
 *
 * @param {string|Object} json JSON serialization or hash object
 * @return {ve.dm.Selection} New selection
 * @throws {Error} Unknown selection type
 */
ve.dm.Selection.static.newFromJSON = function ( json ) {
	if ( ve.dm.Document && arguments[ 0 ] instanceof ve.dm.Document ) {
		throw new Error( 'Got obsolete ve.dm.Document argument' );
	}
	var hash = typeof json === 'string' ? JSON.parse( json ) : json;
	var constructor = ve.dm.selectionFactory.lookup( hash.type );

	if ( !constructor ) {
		throw new Error( 'Unknown selection type ' + hash.name );
	}

	return constructor.static.newFromHash( hash );
};

/**
 * Create a new selection from a hash object
 *
 * @abstract
 * @param {Object} hash
 * @return {ve.dm.Selection} New selection
 */
ve.dm.Selection.static.newFromHash = null;

/* Methods */

/**
 * Get a JSON serialization of this selection
 *
 * @abstract
 * @param {string} [key] Key in parent object
 * @return {Object} Object for JSON serialization
 */
ve.dm.Selection.prototype.toJSON = null;

/**
 * Get a textual description of this selection, for debugging purposes
 *
 * @abstract
 * @return {string} Textual description
 */
ve.dm.Selection.prototype.getDescription = null;

/**
 * Get a new selection at the start point of this one
 *
 * @abstract
 * @return {ve.dm.Selection} Collapsed selection
 */
ve.dm.Selection.prototype.collapseToStart = null;

/**
 * Get a new selection at the end point of this one
 *
 * @abstract
 * @return {ve.dm.Selection} Collapsed selection
 */
ve.dm.Selection.prototype.collapseToEnd = null;

/**
 * Get a new selection at the 'from' point of this one
 *
 * @abstract
 * @return {ve.dm.Selection} Collapsed selection
 */
ve.dm.Selection.prototype.collapseToFrom = null;

/**
 * Get a new selection at the 'to' point of this one
 *
 * @abstract
 * @return {ve.dm.Selection} Collapsed selection
 */
ve.dm.Selection.prototype.collapseToTo = null;

/**
 * Check if a selection is collapsed
 *
 * @abstract
 * @return {boolean} Selection is collapsed
 */
ve.dm.Selection.prototype.isCollapsed = null;

/**
 * Apply translations from a transaction
 *
 * @abstract
 * @param {ve.dm.Transaction} tx
 * @param {boolean} [excludeInsertion] Do not grow to cover insertions at boundaries
 * @return {ve.dm.Selection} A new translated selection
 */
ve.dm.Selection.prototype.translateByTransaction = null;

/**
 * Apply translations from a transaction, with bias depending on author ID comparison
 *
 * @abstract
 * @param {ve.dm.Transaction} tx
 * @param {number} authorId The selection's author ID
 * @return {ve.dm.Selection} A new translated selection
 */
ve.dm.Selection.prototype.translateByTransactionWithAuthor = null;

/**
 * Apply translations from a set of transactions
 *
 * @param {ve.dm.Transaction[]} txs Transactions
 * @param {boolean} [excludeInsertion] Do not grow to cover insertions at boundaries
 * @return {ve.dm.Selection} A new translated selection
 */
ve.dm.Selection.prototype.translateByTransactions = function ( txs, excludeInsertion ) {
	var selection = this;
	for ( var i = 0, l = txs.length; i < l; i++ ) {
		selection = selection.translateByTransaction( txs[ i ], excludeInsertion );
	}
	return selection;
};

/**
 * Apply translations from a change
 *
 * @param {ve.dm.Change} change
 * @param {number} authorId The author ID of this selection
 * @return {ve.dm.Selection} A new translated selection
 */
ve.dm.Selection.prototype.translateByChange = function ( change, authorId ) {
	var selection = this;
	for ( var i = 0, len = change.transactions.length; i < len; i++ ) {
		selection = selection.translateByTransactionWithAuthor(
			change.transactions[ i ],
			authorId
		);
	}
	return selection;
};

/**
 * Check if this selection is null
 *
 * @return {boolean} The selection is null
 */
ve.dm.Selection.prototype.isNull = function () {
	return false;
};

/**
 * Get the content ranges for this selection
 *
 * @abstract
 * @param {ve.dm.Document} doc The document to which this selection applies
 * @return {ve.Range[]} Ranges
 */
ve.dm.Selection.prototype.getRanges = null;

/**
 * Get the covering linear range for this selection
 *
 * @abstract
 * @return {ve.Range|null} Covering range, if not null
 */
ve.dm.Selection.prototype.getCoveringRange = null;

/**
 * Get the name of the selection type
 *
 * @return {string} Selection type name
 */
ve.dm.Selection.prototype.getName = function () {
	return this.constructor.static.name;
};

/**
 * Check if two selections are equal
 *
 * @abstract
 * @param {ve.dm.Selection} other
 * @return {boolean} Selections are equal
 */
ve.dm.Selection.prototype.equals = null;

/* Factory */

ve.dm.selectionFactory = new OO.Factory();
