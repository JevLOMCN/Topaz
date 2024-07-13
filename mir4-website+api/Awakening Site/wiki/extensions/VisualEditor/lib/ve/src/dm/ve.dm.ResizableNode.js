/*!
 * VisualEditor DataModel Resizable node.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * A mixin class for resizable nodes. This class is mostly a base
 * interface for resizable nodes to be able to produce scalable
 * objects for further calculation.
 *
 * @class
 * @abstract
 * @constructor
 */
ve.dm.ResizableNode = function VeDmResizableNode() {
	this.scalable = null;

	this.connect( this, { attributeChange: 'onResizableAttributeChange' } );
};

/* Inheritance */

OO.initClass( ve.dm.ResizableNode );

/**
 * Get a scalable object for this node.
 *
 * #createScalable is called if one doesn't already exist.
 *
 * @return {ve.dm.Scalable}
 */
ve.dm.ResizableNode.prototype.getScalable = function () {
	if ( !this.scalable ) {
		this.scalable = this.createScalable();
	}
	return this.scalable;
};

/**
 * Create a scalable object based on the current object's width and height.
 *
 * @abstract
 * @return {ve.dm.Scalable}
 */
ve.dm.ResizableNode.prototype.createScalable = null;

/**
 * Handle attribute change events from the model.
 *
 * @param {string} key Attribute key
 * @param {string} from Old value
 * @param {string} to New value
 */
ve.dm.ResizableNode.prototype.onResizableAttributeChange = function ( key ) {
	if ( key === 'width' || key === 'height' ) {
		this.getScalable().setCurrentDimensions( this.getCurrentDimensions() );
	}
};

/**
 * Get the current dimensions from the model
 *
 * @return {Object} Current dimensions
 */
ve.dm.ResizableNode.prototype.getCurrentDimensions = function () {
	return {
		width: this.getAttribute( 'width' ),
		height: this.getAttribute( 'height' )
	};
};
