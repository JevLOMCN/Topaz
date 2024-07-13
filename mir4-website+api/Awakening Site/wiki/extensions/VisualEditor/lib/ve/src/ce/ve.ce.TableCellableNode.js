/*!
 * VisualEditor ContentEditable TableCellableNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable node which can behave as a table cell
 *
 * @class
 *
 * @abstract
 * @constructor
 */
ve.ce.TableCellableNode = function VeCeTableCellableNode() {
	if ( this.isCellable() && !this.isCellEditable() ) {
		this.$element.attr( 'title', ve.msg( 'visualeditor-aliennode-tooltip' ) );
	}

	this.$element.addClass( 've-ce-tableCellableNode' );
};

/* Inheritance */

OO.initClass( ve.ce.TableCellableNode );

/* Static Methods */

/* Methods */

/**
 * Set the editing mode of a table cell node
 *
 * @param {boolean} enable Enable editing
 */
ve.ce.TableCellableNode.prototype.setEditing = function () {
};

/**
 * Get the HTML tag name.
 *
 * Tag name is selected based on the model's style attribute.
 *
 * @return {string} HTML tag name
 * @throws {Error} Invalid style
 */
ve.ce.TableCellableNode.prototype.getTagName = function () {
	var style = this.model.getAttribute( 'style' ),
		types = { data: 'td', header: 'th' };

	if ( !Object.prototype.hasOwnProperty.call( types, style ) ) {
		throw new Error( 'Invalid style' );
	}
	return types[ style ];
};
