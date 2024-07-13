/*!
 * VisualEditor DataModel Node class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Generic DataModel node.
 *
 * @abstract
 * @extends ve.dm.Model
 * @mixins OO.EventEmitter
 * @mixins ve.Node
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.Node = function VeDmNode( element ) {
	// Parent constructor
	ve.dm.Node.super.apply( this, arguments );

	// Mixin constructors
	ve.Node.call( this );
	OO.EventEmitter.call( this );

	// Properties
	this.length = 0;
	this.offset = null;
	this.element = element;
};

/**
 * @event attributeChange
 * @param {string} key
 * @param {Mixed} oldValue
 * @param {Mixed} newValue
 */

/**
 * @event lengthChange
 * @param {number} diff
 */

/**
 * @event update
 * @param {boolean} staged Transaction was applied in staging mode
 */

/* Inheritance */

OO.inheritClass( ve.dm.Node, ve.dm.Model );

OO.mixinClass( ve.dm.Node, ve.Node );

OO.mixinClass( ve.dm.Node, OO.EventEmitter );

/* Static Properties */

/**
 * Whether this node handles its own children. After converting a DOM node to a linear model
 * node of this type, the converter checks this property. If it's false, the converter will descend
 * into the DOM node's children, recursively convert them, and attach the resulting nodes as
 * children of the linear model node. If it's true, the converter will not descend, and will
 * expect the node's toDataElement() to have handled the entire DOM subtree.
 *
 * The same is true when converting from linear model data to DOM: if this property is true,
 * toDomElements() will be passed the node's data element and all of its children and will be
 * expected to convert the entire subtree. If it's false, the converter will descend into the
 * child nodes and convert each one individually.
 *
 * If .static.childNodeTypes is set to [], this property is ignored and will be assumed to be true.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.handlesOwnChildren = false;

/**
 * Whether this node's children should be ignored when iterating over the model, for example
 * content which is stored inline, but is actually conceptually out-of-band data, such as a
 * reference node which contains the reference content (like `<ref>` tags in MediaWiki).
 *
 * This property should not be used for data which isn't out-of-band but is less accessible in
 * the view, e.g. content in table cells or image captions.
 *
 * If true, this node will be treated as a leaf node even if it has children.
 * Often used in combination with handlesOwnChildren.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.ignoreChildren = false;

/**
 * Whether this node can be deleted. If false, ve.dm.Transaction#newFromRemoval will silently
 * ignore any attepts to delete this node.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.isDeletable = true;

/**
 * Whether this node type is internal. Internal node types are ignored by the converter.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.isInternal = false;

/**
 * Whether this node type has a wrapping element in the linear model. Most node types are wrapped,
 * only special node types are not wrapped.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.isWrapped = true;

/**
 * Whether this node type can be unwrapped by user input (e.g. backspace to unwrap a list item)
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.isUnwrappable = true;

/**
 * Whether this node type is a content node type. This means the node represents content, cannot
 * have children, and can only appear as children of a content container node. Content nodes are
 * also known as inline nodes.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.isContent = false;

/**
 * Whether this node type is a metadata node. This means the node represents a leaf node that
 * has no explicit view representation, and should be treated differently for the purposes of
 * round-tripping, copy/paste etc.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.isMetaData = false;

/**
 * For a non-content node type, whether this node type can be serialized in a content
 * position (e.g. for round tripping). This value is ignored if isContent is true.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.canSerializeAsContent = false;

/**
 * Whether this node type can be focused. Focusable nodes react to selections differently.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.isFocusable = false;

/**
 * Whether this node type is alignable.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.isAlignable = false;

/**
 * Whether this node type can behave as a table cell.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.isCellable = false;

/**
 * Whether this node type can contain content. The children of content container nodes must be
 * content nodes.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.canContainContent = false;

/**
 * Whether this node type behaves like a list when diffing.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.isDiffedAsList = false;

/**
 * Whether this node type behaves like a leaf when diffing.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.isDiffedAsLeaf = false;

/**
 * Whether this node type has significant whitespace. Only applies to content container nodes
 * (i.e. can only be true if canContainContent is also true).
 *
 * If a content node has significant whitespace, the text inside it is not subject to whitespace
 * stripping and preservation.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.Node.static.hasSignificantWhitespace = false;

/**
 * Array of allowed child node types for this node type.
 *
 * An empty array means no children are allowed. null means any node type is allowed as a child.
 *
 * @static
 * @property {string[]|null}
 * @inheritable
 */
ve.dm.Node.static.childNodeTypes = null;

/**
 * Array of allowed parent node types for this node type.
 *
 * An empty array means this node type cannot be the child of any node. null means this node type
 * can be the child of any node type.
 *
 * @static
 * @property {string[]|null}
 * @inheritable
 */
ve.dm.Node.static.parentNodeTypes = null;

/**
 * Array of suggested parent node types for this node type.
 *
 * These parent node types are allowed but the editor will avoid creating them.
 *
 * An empty array means this node type should not be the child of any node. null means this node type
 * can be the child of any node type.
 *
 * @static
 * @property {string[]|null}
 * @inheritable
 */
ve.dm.Node.static.suggestedParentNodeTypes = null;

/**
 * Array of annotation types which can't be applied to this node
 *
 * @static
 * @property {string[]}
 * @inheritable
 */
ve.dm.Node.static.disallowedAnnotationTypes = [];

/**
 * Default attributes to set for newly created linear model elements. These defaults will be used
 * when creating a new element in ve.dm.NodeFactory#getDataElement when there is no DOM node or
 * existing linear model element to base the attributes on.
 *
 * This property is an object with attribute names as keys and attribute values as values.
 * Attributes may be omitted, in which case they'll simply be undefined.
 *
 * @static
 * @property {Object}
 * @inheritable
 */
ve.dm.Node.static.defaultAttributes = {};

/**
 * Sanitize the node's linear model data, typically if it was generated from an external source (e.g. copied HTML)
 *
 * @param {Object} dataElement Linear model element, modified in place
 */
ve.dm.Node.static.sanitize = function () {
};

/**
 * Remap the internal list indexes stored in a linear model data element.
 *
 * The default implementation is empty. Nodes should override this if they store internal list
 * indexes in attributes. To remap, do something like
 * dataElement.attributes.foo = mapping[dataElement.attributes.foo];
 *
 * @static
 * @inheritable
 * @param {Object} dataElement Data element (opening) to remap. Will be modified.
 * @param {Object} mapping Object mapping old internal list indexes to new internal list indexes
 * @param {ve.dm.InternalList} internalList Internal list the indexes are being mapped into.
 *  Used for refreshing attribute values that were computed with getNextUniqueNumber().
 */
ve.dm.Node.static.remapInternalListIndexes = function () {
};

/**
 * Remap the internal list keys stored in a linear model data element.
 *
 * The default implementation is empty. Nodes should override this if they store internal list
 * keys in attributes.
 *
 * @static
 * @inheritable
 * @param {Object} dataElement Data element (opening) to remap. Will be modified.
 * @param {ve.dm.InternalList} internalList Internal list the keys are being mapped into.
 */
ve.dm.Node.static.remapInternalListKeys = function () {
};

/**
 * Determine if a hybrid element is inline and allowed to be inline in this context
 *
 * We generate block elements for block tags and inline elements for inline
 * tags; unless we're in a content location, in which case we have no choice
 * but to generate an inline element.
 *
 * @static
 * @param {HTMLElement[]} domElements DOM elements being converted
 * @param {ve.dm.Converter} converter
 * @return {boolean} The element is inline
 */
ve.dm.Node.static.isHybridInline = function ( domElements, converter ) {
	var allTagsInline = true;

	for ( var i = 0, length = domElements.length; i < length; i++ ) {
		if ( ve.isBlockElement( domElements[ i ] ) ) {
			allTagsInline = false;
			break;
		}
	}

	// Force inline in content locations (but not wrappers)
	return ( converter.isExpectingContent() && !converter.isInWrapper() ) ||
		// ..also force inline in wrappers that we can't close
		( converter.isInWrapper() && !converter.canCloseWrapper() ) ||
		// ..otherwise just look at the tag names
		allTagsInline;
};

/**
 * Get a clone of the node's document data element.
 *
 * The attributes object will be deep-copied and the .internal.generated
 * property will be removed if present.
 *
 * @static
 * @param {Object} element
 * @param {ve.dm.HashValueStore} store Hash-value store used by element
 * @param {boolean} preserveGenerated Preserve internal.generated property of element
 * @param {boolean} resetAttributes Reset attributes for an empty clone, as defined in #static-resetForClone
 * @return {Object} Cloned element object
 */
ve.dm.Node.static.cloneElement = function ( element, store, preserveGenerated, resetAttributes ) {
	var modified = false,
		clone = ve.copy( element );

	if ( !preserveGenerated ) {
		ve.deleteProp( clone, 'internal', 'generated' );
	}
	var originalDomElements = store.value( clone.originalDomElementsHash );
	// Generate a new about attribute to prevent about grouping of cloned nodes
	if ( originalDomElements ) {
		// TODO: The '#mwtNNN' is required by Parsoid. Make the name used here
		// more generic and specify the #mwt pattern in MW code.
		var about = '#mwt' + Math.floor( 1000000000 * Math.random() );
		var domElements = originalDomElements.map( function ( el ) {
			var elClone = el.cloneNode( true );
			// Check for hasAttribute as comments don't have them
			if ( elClone.hasAttribute && elClone.hasAttribute( 'about' ) ) {
				elClone.setAttribute( 'about', about );
				modified = true;
			}
			return elClone;
		} );
		if ( modified ) {
			clone.originalDomElementsHash = store.hash( domElements, domElements.map( ve.getNodeHtml ).join( '' ) );
		}
	}
	if ( resetAttributes ) {
		this.resetAttributesForClone( clone, store );
	}
	return clone;
};

/**
 * Reset attributes for a cloned element.
 *
 * This will be used when an element needs to have certain attributes cleared
 * when creating a clone, e.g. when splitting a content branch node by pressing
 * enter, some attributes are preserved (list style) but some are cleared
 * (check list item state).
 *
 * @static
 * @param {Object} clonedElement Cloned element, modified in place
 * @param {ve.dm.HashValueStore} store Hash-value store used by element
 */
ve.dm.Node.static.resetAttributesForClone = function () {};

/* Methods */

/**
 * @inheritdoc
 */
ve.dm.Node.prototype.getStore = function () {
	return this.doc && this.doc.store;
};

/**
 * @see #static-cloneElement
 * Implementations should override the static method, not this one
 *
 * @param {boolean} preserveGenerated Preserve internal.generated property of element
 * @param {boolean} resetAttributes Reset attributes for an empty clone, as defined in #static-resetForClone
 * @return {Object} Cloned element object
 */
ve.dm.Node.prototype.getClonedElement = function ( preserveGenerated, resetAttributes ) {
	var store = this.getStore();
	if ( !store ) {
		throw new Error( 'Node must be attached to the document to be cloned.' );
	}
	return this.constructor.static.cloneElement( this.element, store, preserveGenerated, resetAttributes );
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.getChildNodeTypes = function () {
	return this.constructor.static.childNodeTypes;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.getParentNodeTypes = function () {
	return this.constructor.static.parentNodeTypes;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.getSuggestedParentNodeTypes = function () {
	return this.constructor.static.suggestedParentNodeTypes;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.canHaveChildren = function () {
	return ve.dm.nodeFactory.canNodeHaveChildren( this.type );
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.canHaveChildrenNotContent = function () {
	return ve.dm.nodeFactory.canNodeHaveChildrenNotContent( this.type );
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.isInternal = function () {
	return this.constructor.static.isInternal;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.isMetaData = function () {
	return this.constructor.static.isMetaData;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.isWrapped = function () {
	return this.constructor.static.isWrapped;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.isUnwrappable = function () {
	return this.isWrapped() && this.constructor.static.isUnwrappable;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.canContainContent = function () {
	return this.constructor.static.canContainContent;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.isContent = function () {
	return this.constructor.static.isContent;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.isFocusable = function () {
	return this.constructor.static.isFocusable;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.isAlignable = function () {
	return this.constructor.static.isAlignable;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.isCellable = function () {
	return this.constructor.static.isCellable;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.isCellEditable = function () {
	return this.constructor.static.isCellEditable;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.isDiffedAsList = function () {
	return this.constructor.static.isDiffedAsList;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.isDiffedAsLeaf = function () {
	return this.constructor.static.isDiffedAsLeaf;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.isDiffedAsDocument = function () {
	return this.getChildNodeTypes() === null;
};

/**
 * Check if the node can have a slug before it.
 *
 * @return {boolean} Whether the node can have a slug before it
 */
ve.dm.Node.prototype.canHaveSlugBefore = function () {
	return !this.canContainContent() && this.getParentNodeTypes() === null;
};

/**
 * Check if the node can have a slug after it.
 *
 * @method
 * @return {boolean} Whether the node can have a slug after it
 */
ve.dm.Node.prototype.canHaveSlugAfter = ve.dm.Node.prototype.canHaveSlugBefore;

/**
 * A string identifier used to suppress slugs
 *
 * If sequential nodes have the same non-null suppressSlugType, then
 * no slug is shown, e.g. two floated images can return 'float' to
 * suppress the slug between them.
 *
 * @return {string|null} Type
 */
ve.dm.Node.prototype.suppressSlugType = function () {
	return null;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.hasSignificantWhitespace = function () {
	return this.constructor.static.hasSignificantWhitespace;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.handlesOwnChildren = function () {
	return this.constructor.static.handlesOwnChildren;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.shouldIgnoreChildren = function () {
	return this.constructor.static.ignoreChildren;
};

/**
 * Check if the node can be the root of a branch exposed in a ve.ce.Surface
 *
 * @return {boolean} Node can be the root of a surfaced branch
 */
ve.dm.Node.prototype.isSurfaceable = function () {
	return this.hasChildren() && !this.canContainContent() && !this.isMetaData() && !this.getChildNodeTypes();
};

/**
 * Find the first ancestor with matching type and attribute values.
 *
 * @param {string} type Node type to match
 * @param {Object} [attributes] Node attributes to match
 * @return {ve.dm.Node|null} Ancestor with matching type and attribute values
 */
ve.dm.Node.prototype.findMatchingAncestor = function ( type, attributes ) {
	return this.traverseUpstream( function ( node ) {
		return !node.matches( type, attributes );
	} );
};

/**
 * Check if the node has an ancestor with matching type and attribute values.
 *
 * @param {string} type Node type to match
 * @param {Object} [attributes] Node attributes to match
 * @return {boolean} Node has an ancestor with matching type and attribute values
 */
ve.dm.Node.prototype.hasMatchingAncestor = function ( type, attributes ) {
	return !!this.findMatchingAncestor( type, attributes );
};

/**
 * Check if the node matches type and attribute values.
 *
 * @param {string} type Node type to match
 * @param {Object} [attributes] Node attributes to match
 * @return {boolean} Node matches type and attribute values
 */
ve.dm.Node.prototype.matches = function ( type, attributes ) {
	if ( this.getType() !== type ) {
		return false;
	}

	if ( attributes ) {
		return this.compareAttributes( attributes );
	}
	return true;
};

/**
 * Check if specific attributes match those in the node
 *
 * @param {Object} attributes Node attributes to match
 * @return {boolean} Attributes sepcified match those in the node
 */
ve.dm.Node.prototype.compareAttributes = function ( attributes ) {
	for ( var key in attributes ) {
		if ( this.getAttribute( key ) !== attributes[ key ] ) {
			return false;
		}
	}
	return true;
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.getLength = function () {
	return this.length;
};

/**
 * Set the inner length of the node.
 *
 * This should only be called after a relevant change to the document data. Calling this method will
 * not change the document data.
 *
 * @param {number} length Length of content
 * @fires lengthChange
 * @fires update
 * @throws {Error} Invalid content length error if length is less than 0
 */
ve.dm.Node.prototype.setLength = function ( length ) {
	if ( length < 0 ) {
		throw new Error( 'Length cannot be negative' );
	}
	// Compute length adjustment from old length
	var diff = length - this.length;
	// Set new length
	this.length = length;
	// Adjust the parent's length
	if ( this.parent ) {
		this.parent.adjustLength( diff );
	}
	// Emit events
	this.emit( 'lengthChange', diff );
	this.emit( 'update' );
};

/**
 * Adjust the length.
 *
 * This should only be called after a relevant change to the document data. Calling this method will
 * not change the document data.
 *
 * @param {number} adjustment Amount to adjust length by
 * @fires lengthChange
 * @fires update
 * @throws {Error} Invalid adjustment error if resulting length is less than 0
 */
ve.dm.Node.prototype.adjustLength = function ( adjustment ) {
	this.setLength( this.length + adjustment );
};

/**
 * @inheritdoc ve.Node
 */
ve.dm.Node.prototype.getOffset = function () {
	if ( !this.parent ) {
		return 0;
	}

	if ( this.doc.isReadOnly() && this.offset !== null ) {
		return this.offset;
	}

	// Find our index in the parent and add up lengths while we do so
	var siblings = this.parent.children;
	var offset = this.parent.getOffset() + ( this.parent === this.root ? 0 : 1 );
	var i, len;
	for ( i = 0, len = siblings.length; i < len; i++ ) {
		if ( siblings[ i ] === this ) {
			break;
		}
		offset += siblings[ i ].getOuterLength();
	}
	if ( i === len ) {
		throw new Error( 'Node not found in parent\'s children array' );
	}
	if ( this.doc.isReadOnly() ) {
		// Cache offset, only used in read-only mode (when the offset can't change)
		// This cache is additionally cleared when leaving read-only mode in ve.dm.Document#setReadOnly
		this.offset = offset;
	}
	return offset;
};

/**
 * Check if the node can be merged with another.
 *
 * For two nodes to be mergeable, the two nodes must either be the same node or:
 *  - Are comparable according to #compareForMerging (by default, have the same type)
 *  - Have the same depth
 *  - Have similar ancestry (each node upstream must have the same type)
 *
 * @param {ve.dm.Node} node Node to consider merging with
 * @return {boolean} Nodes can be merged
 */
ve.dm.Node.prototype.canBeMergedWith = function ( node ) {
	var n1 = this,
		n2 = node;

	// Content node can be merged with node that can contain content, for instance: TextNode
	// and ParagraphNode. When this method is called for such case (one node is a content node and
	// the other one can contain content) make sure to start traversal from node that can contain
	// content (instead of content node itself).
	if ( n1.canContainContent() && n2.isContent() ) {
		n2 = n2.getParent();
	} else if ( n2.canContainContent() && n1.isContent() ) {
		n1 = n1.getParent();
	}
	// Move up from n1 and n2 simultaneously until we find a common ancestor
	while ( n1 !== n2 ) {
		if (
			// Check if we have reached a root (means there's no common ancestor or unequal depth)
			( n1 === null || n2 === null ) ||
			// Ensure that nodes are comparable for merging
			!n1.compareForMerging( n2 )
		) {
			return false;
		}
		// Move up
		n1 = n1.getParent();
		n2 = n2.getParent();
	}
	return true;
};

/**
 * Compare with another node for merging (see #canBeMergedWidth)
 *
 * The default implementation just compares node types.
 *
 * @param {ve.dm.Node} otherNode Other node to compare with
 * @return {boolean} Nodes are comparable
 */
ve.dm.Node.prototype.compareForMerging = function ( otherNode ) {
	return this.getType() === otherNode.getType();
};
