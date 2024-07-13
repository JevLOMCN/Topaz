/*!
 * VisualEditor ContentEditable ListNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable list node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @constructor
 * @param {ve.dm.ListNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.ListNode = function VeCeListNode() {
	// Parent constructor
	ve.ce.ListNode.super.apply( this, arguments );

	// Events
	this.model.connect( this, { update: 'onUpdate' } );
};

/* Inheritance */

OO.inheritClass( ve.ce.ListNode, ve.ce.BranchNode );

/* Static Properties */

ve.ce.ListNode.static.name = 'list';

ve.ce.ListNode.static.removeEmptyLastChildOnEnter = true;

/* Methods */

/**
 * Get the HTML tag name.
 *
 * Tag name is selected based on the model's style attribute.
 *
 * @return {string} HTML tag name
 * @throws {Error} If style is invalid
 */
ve.ce.ListNode.prototype.getTagName = function () {
	var style = this.model.getAttribute( 'style' ),
		types = { bullet: 'ul', number: 'ol' };

	if ( !Object.prototype.hasOwnProperty.call( types, style ) ) {
		throw new Error( 'Invalid style' );
	}
	return types[ style ];
};

/**
 * Handle model update events.
 *
 * If the style changed since last update the DOM wrapper will be replaced with an appropriate one.
 */
ve.ce.ListNode.prototype.onUpdate = function () {
	this.updateTagName();
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.ListNode );
