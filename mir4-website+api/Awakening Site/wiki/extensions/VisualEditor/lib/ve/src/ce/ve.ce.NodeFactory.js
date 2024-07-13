/*!
 * VisualEditor ContentEditable NodeFactory class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable node factory.
 *
 * @class
 * @extends OO.Factory
 * @constructor
 */
ve.ce.NodeFactory = function VeCeNodeFactory() {
	// Parent constructor
	OO.Factory.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ce.NodeFactory, OO.Factory );

/* Methods */

/**
 * Get a plain text description of a node model.
 *
 * @param {ve.dm.Node} node Node to describe
 * @return {string} Description of the node
 * @throws {Error} Unknown node type
 */
ve.ce.NodeFactory.prototype.getDescription = function ( node ) {
	var type = node.constructor.static.name;
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.getDescription( node );
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if a node type splits on Enter
 *
 * @param {string} type Node type
 * @return {boolean} The node can have grandchildren
 * @throws {Error} Unknown node type
 */
ve.ce.NodeFactory.prototype.splitNodeOnEnter = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.splitOnEnter;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Create a view node from a model node.
 *
 * @param {ve.dm.Node} model Mode node
 * @return {ve.ce.Node} View node
 * @throws {Error} Unknown object name
 */
ve.ce.NodeFactory.prototype.createFromModel = function ( model ) {
	var type = model.getType();
	if ( ve.dm.nodeFactory.isMetaData( type ) ) {
		// Metadata never has an explicit view representation, so a generic
		// ve.ce.MetaItem should be fine
		type = 'meta';
	}
	return this.create( type, model );
};

/* Initialization */

// TODO: Move instantiation to a different file
ve.ce.nodeFactory = new ve.ce.NodeFactory();
