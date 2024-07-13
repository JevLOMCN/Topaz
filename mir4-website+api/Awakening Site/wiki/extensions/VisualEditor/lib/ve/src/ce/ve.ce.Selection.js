/*!
 * VisualEditor Selection class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * @class
 * @abstract
 * @constructor
 * @param {ve.dm.Selection} model Selection model
 * @param {ve.ce.Surface} surface Surface view
 */
ve.ce.Selection = function VeCeSelection( model, surface ) {
	this.model = model;
	this.surface = surface;
};

/* Inheritance */

OO.initClass( ve.ce.Selection );

/* Static Properties */

ve.ce.Selection.static.type = null;

/* Static methods */

/**
 * Create a new selection view from a selection model
 *
 * @param {ve.dm.Selection} model Selection model
 * @param {ve.ce.Surface} surface Surface view
 * @return {ve.ce.Selection} Selection view
 */
ve.ce.Selection.static.newFromModel = function ( model, surface ) {
	return ve.ce.selectionFactory.create( model.getName(), model, surface );
};

/* Method */

/**
 * Get the surface view this selection exists on
 *
 * @return {ve.ce.Surface} Surface view
 */
ve.ce.Selection.prototype.getSurface = function () {
	return this.surface;
};

/**
 * Get the selection model
 *
 * @return {ve.dm.Selection} Selection model
 */
ve.ce.Selection.prototype.getModel = function () {
	return this.model;
};

/**
 * Get the rectangles of the selection relative to the surface.
 *
 * @abstract
 * @return {Object[]|null} Selection rectangles
 */
ve.ce.Selection.prototype.getSelectionRects = null;

/**
 * Get the start and end rectangles of the selection relative to the surface.
 *
 * @abstract
 * @return {Object.<string,Object>|null} Start and end selection rectangles
 */
ve.ce.Selection.prototype.getSelectionStartAndEndRects = function () {
	return ve.getStartAndEndRects( this.getSelectionRects() );
};

/**
 * Get the rectangle for the selection's focus end
 *
 * The default gives the bounding rectangle after using collapseToTo.
 *
 * @return {Object|null} Selection rectangle
 */
ve.ce.Selection.prototype.getSelectionFocusRect = function () {
	var toSelection = new this.constructor( this.getModel().collapseToTo(), this.surface );
	return toSelection.getSelectionBoundingRect();
};

/**
 * Get the coordinates of the selection's bounding rectangle relative to the surface.
 *
 * @abstract
 * @return {Object|null} Selection rectangle, with keys top, bottom, left, right, width, height
 */
ve.ce.Selection.prototype.getSelectionBoundingRect = null;

/**
 * Check if the selection covers a focused node
 *
 * @abstract
 * @return {boolean} The selection covers a focused node
 */
ve.ce.Selection.prototype.isFocusedNode = null;

/**
 * Check if the selection is a native cursor selection
 *
 * @abstract
 * @return {boolean} The selection is represented by a native cursor
 */
ve.ce.Selection.prototype.isNativeCursor = null;

/**
 * Check if two selections are equal
 *
 * @param {ve.ce.Selection} other Other selection
 * @return {boolean} Selections are equal
 */
ve.ce.Selection.prototype.equals = function ( other ) {
	return this.getSurface() === other.getSurface() &&
		this.getModel().equals( other.getModel() );
};

/**
 * Get the block directionality of the selection
 *
 * Note: This is not the direction of the selection (e.g. ve.Range#isBackwards); it is the
 * computed block-level CSS directionality. The actual directionality at any point within the
 * selection depends on both this and the browser's implementation of BIDI algorithm.
 *
 * @abstract
 * @param {ve.ce.Document} doc The document to which this selection applies
 * @return {string} 'rtl', 'ltr'
 */
ve.ce.Selection.prototype.getDirectionality = null;

/* Factory */

ve.ce.selectionFactory = new OO.Factory();
