/*!
 * VisualEditor Linear Selection class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * @class
 * @extends ve.dm.Selection
 * @constructor
 * @param {ve.Range} range
 */
ve.dm.LinearSelection = function VeDmLinearSelection( range ) {
	// Parent constructor
	if ( ve.dm.Document && arguments[ 0 ] instanceof ve.dm.Document ) {
		throw new Error( 'Got obsolete ve.dm.Document argument' );
	}
	ve.dm.LinearSelection.super.call( this );

	this.range = range;
};

/* Inheritance */

OO.inheritClass( ve.dm.LinearSelection, ve.dm.Selection );

/* Static Properties */

ve.dm.LinearSelection.static.name = 'linear';

/* Static Methods */

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.static.newFromHash = function ( hash ) {
	return new ve.dm.LinearSelection( ve.Range.static.newFromHash( hash.range ) );
};

/* Methods */

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.prototype.toJSON = function () {
	return {
		type: this.constructor.static.name,
		range: this.range
	};
};

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.prototype.getDescription = function () {
	return 'Linear: ' + this.range.from + ' - ' + this.range.to;
};

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.prototype.collapseToStart = function () {
	return new this.constructor( new ve.Range( this.getRange().start ) );
};

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.prototype.collapseToEnd = function () {
	return new this.constructor( new ve.Range( this.getRange().end ) );
};

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.prototype.collapseToFrom = function () {
	return new this.constructor( new ve.Range( this.getRange().from ) );
};

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.prototype.collapseToTo = function () {
	return new this.constructor( new ve.Range( this.getRange().to ) );
};

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.prototype.isCollapsed = function () {
	return this.getRange().isCollapsed();
};

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.prototype.translateByTransaction = function ( tx, excludeInsertion ) {
	return new this.constructor( tx.translateRange( this.getRange(), excludeInsertion ) );
};

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.prototype.translateByTransactionWithAuthor = function ( tx, authorId ) {
	return new this.constructor( tx.translateRangeWithAuthor( this.getRange(), authorId ) );
};

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.prototype.getRanges = function () {
	return [ this.range ];
};

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.prototype.getCoveringRange = function () {
	return this.range;
};

/**
 * Get the range for this selection
 *
 * @return {ve.Range}
 */
ve.dm.LinearSelection.prototype.getRange = function () {
	return this.range;
};

/**
 * @inheritdoc
 */
ve.dm.LinearSelection.prototype.equals = function ( other ) {
	return this === other || (
		!!other &&
		other.constructor === this.constructor &&
		this.getRange().equals( other.getRange() )
	);
};

/* Registration */

ve.dm.selectionFactory.register( ve.dm.LinearSelection );
