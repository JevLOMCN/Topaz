/*!
 * VisualEditor DataModel NodeFactory class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel node factory.
 *
 * @class
 * @extends ve.dm.ModelFactory
 * @constructor
 */
ve.dm.NodeFactory = function VeDmNodeFactory() {
	// Parent constructor
	ve.dm.NodeFactory.super.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.NodeFactory, ve.dm.ModelFactory );

/* Methods */

/**
 * Get a document data element.
 *
 * @param {string} type Node type
 * @param {Object} attributes Node attributes, defaults will be used where needed
 * @return {Object} Data element
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.getDataElement = function ( type, attributes ) {
	var element = { type: type };
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		attributes = ve.extendObject( {}, this.registry[ type ].static.defaultAttributes, attributes );
		if ( !ve.isEmptyObject( attributes ) ) {
			element.attributes = ve.copy( attributes );
		}
		return element;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Get allowed child node types for a node.
 *
 * @param {string} type Node type
 * @return {string[]|null} List of node types allowed as children or null if any type is allowed
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.getChildNodeTypes = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.childNodeTypes;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Get allowed parent node types for a node.
 *
 * @param {string} type Node type
 * @return {string[]|null} List of node types allowed as parents or null if any type is allowed
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.getParentNodeTypes = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.parentNodeTypes;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Get suggested parent node types for a node.
 *
 * @param {string} type Node type
 * @return {string[]|null} List of node types suggested as parents or null if any type is suggested
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.getSuggestedParentNodeTypes = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.suggestedParentNodeTypes;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if a node can have children.
 *
 * @param {string} type Node type
 * @return {boolean} The node can have children
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.canNodeHaveChildren = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		// If childNodeTypes is null any child is allowed, if it's an array of at least one element
		// than at least one kind of node is allowed
		var types = this.registry[ type ].static.childNodeTypes;
		return types === null || ( Array.isArray( types ) && types.length > 0 );
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if a node can have children but not content nor be content.
 *
 * @param {string} type Node type
 * @return {boolean} The node can have children but not content nor be content
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.canNodeHaveChildrenNotContent = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.canNodeHaveChildren( type ) &&
			!this.registry[ type ].static.canContainContent &&
			!this.registry[ type ].static.isContent;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if a node has a wrapped element in the document data.
 *
 * @param {string} type Node type
 * @return {boolean} Whether the node has a wrapping element
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.isNodeWrapped = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.isWrapped;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if a node is unwrappable.
 *
 * @param {string} type Node type
 * @return {boolean} Whether the node is unwrappable
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.isNodeUnwrappable = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.isNodeWrapped( type ) && this.registry[ type ].static.isUnwrappable;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if a node is a meta item element
 *
 * @param {string} type Node type
 * @return {boolean} Whether the node is meta data
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.isMetaData = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.isMetaData;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if a given type of meta item is removable
 *
 * @param {string} type Meta item type
 * @return {boolean} The type is removable
 * @throws {Error} Unknown item type
 */
ve.dm.NodeFactory.prototype.isRemovableMetaData = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.removable;
	}
	throw new Error( 'Unknown item type: ' + type );
};

/**
 * Check if a node can contain content.
 *
 * @param {string} type Node type
 * @return {boolean} The node contains content
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.canNodeContainContent = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.canContainContent;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if node can take an annotation.
 *
 * @param {string} type Node type
 * @param {ve.dm.Annotation} annotation Annotation to test
 * @return {boolean} Node can take annotations of this type
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.canNodeTakeAnnotation = function ( type, annotation ) {
	if ( !Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		throw new Error( 'Unknown node type: ' + type );
	}
	var disallowedList = this.registry[ type ].static.disallowedAnnotationTypes;

	for ( var i = 0, len = disallowedList.length; i < len; i++ ) {
		if ( annotation instanceof ve.dm.annotationFactory.lookup( disallowedList[ i ] ) ) {
			return false;
		}
	}
	return true;
};

/**
 * Check if a node is content.
 *
 * @param {string} type Node type
 * @return {boolean} The node is content
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.isNodeContent = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.isContent;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if a node can be serialized into a content position
 *
 * @param {string} type Node type
 * @return {boolean} The node is content or can be round-tripped into a content position
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.canNodeSerializeAsContent = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.isContent ||
			this.registry[ type ].static.canSerializeAsContent;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if the node is focusable.
 *
 * @param {string} type Node type
 * @return {boolean} Whether the node is focusable
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.isNodeFocusable = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.isFocusable;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if the node has significant whitespace.
 *
 * Can only be true if canContainContent is also true.
 *
 * @param {string} type Node type
 * @return {boolean} The node has significant whitespace
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.doesNodeHaveSignificantWhitespace = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.hasSignificantWhitespace;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if the node handles its own children.
 *
 * @param {string} type Node type
 * @return {boolean} Whether the node handles its own children
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.doesNodeHandleOwnChildren = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.handlesOwnChildren;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if the node's children should be ignored.
 *
 * @param {string} type Node type
 * @return {boolean} Whether the node's children should be ignored
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.shouldIgnoreChildren = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.ignoreChildren;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if the node is internal.
 *
 * @param {string} type Node type
 * @return {boolean} Whether the node is internal
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.isNodeInternal = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.isInternal;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/**
 * Check if node is deletable.
 *
 * @param {string} type Node type
 * @return {boolean} Whether the node is internal
 * @throws {Error} Unknown node type
 */
ve.dm.NodeFactory.prototype.isNodeDeletable = function ( type ) {
	if ( Object.prototype.hasOwnProperty.call( this.registry, type ) ) {
		return this.registry[ type ].static.isDeletable;
	}
	throw new Error( 'Unknown node type: ' + type );
};

/* Initialization */

ve.dm.nodeFactory = new ve.dm.NodeFactory();
