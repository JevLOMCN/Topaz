/*!
 * VisualEditor ContentEditable HeadingNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable heading node.
 *
 * @class
 * @extends ve.ce.ContentBranchNode
 * @constructor
 * @param {ve.dm.HeadingNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.HeadingNode = function VeCeHeadingNode() {
	// Parent constructor
	ve.ce.HeadingNode.super.apply( this, arguments );

	// Events
	this.model.connect( this, { update: 'onUpdate' } );
};

/* Inheritance */

OO.inheritClass( ve.ce.HeadingNode, ve.ce.ContentBranchNode );

/* Static Properties */

ve.ce.HeadingNode.static.name = 'heading';

/* Methods */

/**
 * @inheritdoc
 */
ve.ce.HeadingNode.prototype.initialize = function () {
	ve.ce.HeadingNode.super.prototype.initialize.call( this );
	this.$element.addClass( 've-ce-headingNode' );
};

/**
 * Get the HTML tag name.
 *
 * Tag name is selected based on the model's level attribute.
 *
 * @return {string} HTML tag name
 * @throws {Error} If level is invalid
 */
ve.ce.HeadingNode.prototype.getTagName = function () {
	var level = this.model.getAttribute( 'level' ),
		types = { 1: 'h1', 2: 'h2', 3: 'h3', 4: 'h4', 5: 'h5', 6: 'h6' };

	if ( !Object.prototype.hasOwnProperty.call( types, level ) ) {
		throw new Error( 'Invalid level' );
	}
	return types[ level ];
};

/**
 * Handle model update events.
 *
 * If the level changed since last update the DOM wrapper will be replaced with an appropriate one.
 */
ve.ce.HeadingNode.prototype.onUpdate = function () {
	this.updateTagName();
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.HeadingNode );
