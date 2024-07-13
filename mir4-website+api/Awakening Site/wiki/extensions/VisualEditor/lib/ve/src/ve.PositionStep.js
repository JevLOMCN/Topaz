/*!
 * VisualEditor representation of a node traversal step
 *
 * @copyright See AUTHORS.txt
 */

/**
 * @class
 *
 * @constructor
 * @param {Node} node The element or text node being crossed
 * @param {string} type The type of step: "enter", "leave", "cross" or (for text nodes) "internal"
 * @param {number} offset For "internal" text node steps only: the character offset being crossed
 */
ve.PositionStep = function VePositionStep( node, type, offset ) {
	this.node = node;
	this.type = type;
	this.offset = offset;
};
