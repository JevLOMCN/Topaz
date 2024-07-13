/*!
 * VisualEditor ContentEditable TableSectionNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable table section node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @constructor
 * @param {ve.dm.TableSectionNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.TableSectionNode = function VeCeTableSectionNode() {
	// Parent constructor
	ve.ce.TableSectionNode.super.apply( this, arguments );

	// Events
	this.model.connect( this, { update: 'onUpdate' } );
};

/* Inheritance */

OO.inheritClass( ve.ce.TableSectionNode, ve.ce.BranchNode );

/* Static Properties */

ve.ce.TableSectionNode.static.name = 'tableSection';

/* Methods */

/**
 * Get the HTML tag name.
 *
 * Tag name is selected based on the model's style attribute.
 *
 * @return {string} HTML tag name
 * @throws {Error} If style is invalid
 */
ve.ce.TableSectionNode.prototype.getTagName = function () {
	var style = this.model.getAttribute( 'style' ),
		types = { header: 'thead', body: 'tbody', footer: 'tfoot' };

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
ve.ce.TableSectionNode.prototype.onUpdate = function () {
	this.updateTagName();
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.TableSectionNode );
