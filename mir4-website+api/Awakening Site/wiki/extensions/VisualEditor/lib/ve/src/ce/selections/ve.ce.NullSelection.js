/*!
 * VisualEditor Null Selection class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * @class
 * @extends ve.ce.Selection
 * @constructor
 * @param {ve.ce.Surface} surface
 * @param {ve.dm.Selection} model
 */
ve.ce.NullSelection = function VeCeNullSelection() {
	// Parent constructor
	ve.ce.NullSelection.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.NullSelection, ve.ce.Selection );

/* Static Properties */

ve.ce.NullSelection.static.name = 'null';

/* Method */

/**
 * @inheritdoc
 */
ve.ce.NullSelection.prototype.getSelectionRects = function () {
	return null;
};

/**
 * @inheritdoc
 */
ve.ce.NullSelection.prototype.getSelectionStartAndEndRects = function () {
	return null;
};

/**
 * @inheritdoc
 */
ve.ce.NullSelection.prototype.getSelectionBoundingRect = function () {
	return null;
};

/**
 * @inheritdoc
 */
ve.ce.NullSelection.prototype.isFocusedNode = function () {
	return false;
};

/**
 * @inheritdoc
 */
ve.ce.NullSelection.prototype.isNativeCursor = function () {
	return false;
};

/**
 * @inheritdoc
 *
 * Null selections don't exist in the view, so just return document directionality.
 */
ve.ce.NullSelection.prototype.getDirectionality = function ( doc ) {
	return doc.getDir();
};

/* Registration */

ve.ce.selectionFactory.register( ve.ce.NullSelection );
