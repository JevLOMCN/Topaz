/*!
 * VisualEditor DataModel TableCellNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel table cell node.
 *
 * @class
 * @extends ve.dm.BranchNode
 * @mixins ve.dm.TableCellableNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.TableCellNode = function VeDmTableCellNode() {
	// Parent constructor
	ve.dm.TableCellNode.super.apply( this, arguments );

	// Mixin constructor
	ve.dm.TableCellableNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.TableCellNode, ve.dm.BranchNode );

OO.mixinClass( ve.dm.TableCellNode, ve.dm.TableCellableNode );

/* Static Properties */

ve.dm.TableCellNode.static.name = 'tableCell';

ve.dm.TableCellNode.static.isUnwrappable = false;

ve.dm.TableCellNode.static.parentNodeTypes = [ 'tableRow' ];

ve.dm.TableCellNode.static.defaultAttributes = { style: 'data' };

ve.dm.TableCellNode.static.matchTagNames = [ 'td', 'th' ];

ve.dm.TableCellNode.static.isCellEditable = true;

// Exclude 'colspan' and 'rowspan' as they are managed explicitly
ve.dm.TableCellNode.static.preserveHtmlAttributes = function ( attribute ) {
	return attribute !== 'colspan' && attribute !== 'rowspan';
};

/* Static Methods */

ve.dm.TableCellNode.static.toDataElement = function ( domElements ) {
	var attributes = {};

	ve.dm.TableCellableNode.static.setAttributes( attributes, domElements );

	return {
		type: this.name,
		attributes: attributes
	};
};

ve.dm.TableCellNode.static.toDomElements = function ( dataElement, doc ) {
	var tag = dataElement.attributes && dataElement.attributes.style === 'header' ? 'th' : 'td',
		domElement = doc.createElement( tag ),
		attributes = dataElement.attributes;

	ve.dm.TableCellableNode.static.applyAttributes( attributes, domElement );

	return [ domElement ];
};

/**
 * Creates data that can be inserted into the model to create a new table cell.
 *
 * @param {Object} [options]
 * @param {string} [options.style='data'] Either 'header' or 'data'
 * @param {number} [options.rowspan=1] Number of rows the cell spans
 * @param {number} [options.colspan=1] Number of columns the cell spans
 * @param {Array} [options.content] Linear model data, defaults to empty wrapper paragraph
 * @return {Array} Model data for a new table cell
 */
ve.dm.TableCellNode.static.createData = function ( options ) {
	options = options || {};
	var opening = {
		type: 'tableCell',
		attributes: {
			style: options.style || 'data',
			rowspan: options.rowspan || 1,
			colspan: options.colspan || 1
		}
	};
	var content = options.content || [
		{ type: 'paragraph', internal: { generated: 'wrapper' } },
		{ type: '/paragraph' }
	];
	return [ opening ].concat( content ).concat( [ { type: '/tableCell' } ] );
};

ve.dm.TableCellNode.static.describeChange = function ( key, change ) {
	if ( key === 'style' ) {
		return ve.htmlMsg( 'visualeditor-changedesc-no-key',
			// The following messages are used here:
			// * visualeditor-table-format-data
			// * visualeditor-table-format-header
			this.wrapText( 'del', ve.msg( 'visualeditor-table-format-' + change.from ) ),
			this.wrapText( 'ins', ve.msg( 'visualeditor-table-format-' + change.to ) )
		);
	} else if ( key === 'colspan' || key === 'rowspan' ) {
		// colspan/rowspan of '1' is the same as not setting it
		if ( change.from === 1 ) {
			change.from = undefined;
		}
		if ( change.to === 1 ) {
			change.to = undefined;
		}
		// These might be the same now
		if ( change.from === change.to ) {
			return null;
		}
	} else if ( key === 'originalColspan' || key === 'originalRowspan' ) {
		return null;
	}

	// Parent method
	return ve.dm.TableCellNode.super.static.describeChange.call( this, key, change );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.TableCellNode );
