/*!
 * VisualEditor DataModel TableSelectionNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel table section node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.TableSectionNode = function VeDmTableSectionNode() {
	// Parent constructor
	ve.dm.TableSectionNode.super.apply( this, arguments );

	// Events
	this.connect( this, { splice: 'onSplice' } );
};

/* Inheritance */

OO.inheritClass( ve.dm.TableSectionNode, ve.dm.BranchNode );

/**
 * @event cellAttributeChange
 * @param {ve.dm.TableCellableNode} cell
 */

/* Static Properties */

ve.dm.TableSectionNode.static.name = 'tableSection';

ve.dm.TableSectionNode.static.childNodeTypes = [ 'tableRow' ];

ve.dm.TableSectionNode.static.parentNodeTypes = [ 'table' ];

ve.dm.TableSectionNode.static.defaultAttributes = { style: 'body' };

ve.dm.TableSectionNode.static.matchTagNames = [ 'thead', 'tbody', 'tfoot' ];

/* Static Methods */

ve.dm.TableSectionNode.static.toDataElement = function ( domElements ) {
	var styles = {
			thead: 'header',
			tbody: 'body',
			tfoot: 'footer'
		},
		style = styles[ domElements[ 0 ].nodeName.toLowerCase() ] || 'body';
	return { type: this.name, attributes: { style: style } };
};

ve.dm.TableSectionNode.static.toDomElements = function ( dataElement, doc ) {
	var tags = {
			header: 'thead',
			body: 'tbody',
			footer: 'tfoot'
		},
		tag = tags[ dataElement.attributes && dataElement.attributes.style || 'body' ];
	return [ doc.createElement( tag ) ];
};

/* Methods */

/**
 * Handle splicing of child nodes
 */
ve.dm.TableSectionNode.prototype.onSplice = function () {
	if ( this.getRoot() ) {
		this.getParent().getMatrix().invalidate();
	}
	var nodes = Array.prototype.slice.call( arguments, 2 );
	for ( var i = 0; i < nodes.length; i++ ) {
		nodes[ i ].connect( this, {
			cellAttributeChange: 'onCellAttributeChange'
		} );
	}
};

/**
 * Handle cell attribute changes
 *
 * @param {ve.dm.TableCellableNode} cell
 * @fires cellAttributeChange
 */
ve.dm.TableSectionNode.prototype.onCellAttributeChange = function ( cell ) {
	this.emit( 'cellAttributeChange', cell );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.TableSectionNode );
