/*!
 * VisualEditor ContentEditable Node class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Generic ContentEditable node.
 *
 * @abstract
 * @extends ve.ce.View
 * @mixins ve.Node
 *
 * @constructor
 * @param {ve.dm.Node} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.Node = function VeCeNode() {
	// Parent constructor
	ve.ce.Node.super.apply( this, arguments );

	// Mixin constructor
	ve.Node.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ce.Node, ve.ce.View );

OO.mixinClass( ve.ce.Node, ve.Node );

/* Static Members */

/**
 * Whether Enter splits this node type.
 *
 * When the user presses Enter, we split the node they're in (if splittable), then split its parent
 * if splittable, and continue traversing up the tree and stop at the first non-splittable node.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.ce.Node.static.splitOnEnter = false;

/**
 * Whether Enter removes the empty last child of this node.
 *
 * Set true on the parent of a splitOnEnter node (e.g. a ListNode) to ensure that the last splittable
 * child (e.g a ListItemNode) is removed when empty and enter is pressed.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.ce.Node.static.removeEmptyLastChildOnEnter = false;

/**
 * Whether a node supports multiline input at all.
 *
 * If set to false, pressing Enter will not perform any splitting at all. If set to null, traverse
 * up the tree until a boolean value is found.
 *
 * @static
 * @property {boolean|null}
 * @inheritable
 */
ve.ce.Node.static.isMultiline = null;

/**
 * Whether a node can take the cursor when its surface is focused
 *
 * When set to false, this will prevent the selection from being placed in
 * any of the node's descendant ContentBranchNodes.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.ce.Node.static.autoFocus = true;

/**
 * Whether a node traps the cursor when active, e.g. in table cells
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.ce.Node.static.trapsCursor = false;

/* Static Methods */

/**
 * Get a plain text description.
 *
 * @static
 * @inheritable
 * @param {ve.dm.Node} node Node model
 * @return {string} Description of node
 */
ve.ce.Node.static.getDescription = function () {
	return '';
};

/* Methods */

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.getChildNodeTypes = function () {
	return this.model.getChildNodeTypes();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.getParentNodeTypes = function () {
	return this.model.getParentNodeTypes();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.getSuggestedParentNodeTypes = function () {
	return this.model.getSuggestedParentNodeTypes();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.canHaveChildren = function () {
	return this.model.canHaveChildren();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.canHaveChildrenNotContent = function () {
	return this.model.canHaveChildrenNotContent();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.isInternal = function () {
	return this.model.isInternal();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.isMetaData = function () {
	return this.model.isMetaData();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.isWrapped = function () {
	return this.model.isWrapped();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.isUnwrappable = function () {
	return this.model.isUnwrappable();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.canContainContent = function () {
	return this.model.canContainContent();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.isContent = function () {
	return this.model.isContent();
};

/**
 * @inheritdoc ve.Node
 *
 * If this is set to true it should implement:
 *
 *     setFocused( boolean val )
 *     boolean isFocused()
 */
ve.ce.Node.prototype.isFocusable = function () {
	return this.model.isFocusable();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.isAlignable = function () {
	return this.model.isAlignable();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.isCellable = function () {
	return this.model.isCellable();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.isCellEditable = function () {
	return this.model.isCellEditable();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.hasSignificantWhitespace = function () {
	return this.model.hasSignificantWhitespace();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.handlesOwnChildren = function () {
	return this.model.handlesOwnChildren();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.shouldIgnoreChildren = function () {
	return this.model.shouldIgnoreChildren();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.getLength = function () {
	return this.model.getLength();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.getOuterLength = function () {
	return this.model.getOuterLength();
};

/**
 * @inheritdoc ve.Node
 */
ve.ce.Node.prototype.getOffset = function () {
	return this.model.getOffset();
};

/**
 * Check if the node can be split.
 *
 * @return {boolean} Node can be split
 */
ve.ce.Node.prototype.splitOnEnter = function () {
	return this.constructor.static.splitOnEnter;
};

/**
 * Check if the node removes its empty last child on 'enter'.
 *
 * @return {boolean} Node removes empty last child on 'enter'
 */
ve.ce.Node.prototype.removeEmptyLastChildOnEnter = function () {
	return this.constructor.static.removeEmptyLastChildOnEnter;
};

/**
 * Check if the node is supports multiline input.
 *
 * Traverses upstream until a boolean value is found. If no value
 * is found, reads the default from the surface.
 *
 * @return {boolean} Node supports multiline input
 */
ve.ce.Node.prototype.isMultiline = function () {
	var booleanNode = this.traverseUpstream( function ( node ) {
		return node.constructor.static.isMultiline === null;
	} );
	if ( booleanNode ) {
		return booleanNode.constructor.static.isMultiline;
	} else {
		return !this.root || this.getRoot().getSurface().getSurface().isMultiline();
	}
};

/**
 * Check if the node can take the cursor when its surface is focused
 *
 * @return {boolean} Node can be take the cursor
 */
ve.ce.Node.prototype.autoFocus = function () {
	return this.constructor.static.autoFocus;
};

/**
 * Check if the node traps cursor when active
 *
 * @return {boolean} Node traps cursor
 */
ve.ce.Node.prototype.trapsCursor = function () {
	return this.constructor.static.trapsCursor;
};

/**
 * Release all memory.
 */
ve.ce.Node.prototype.destroy = function () {
	this.parent = null;
	this.root = null;
	this.doc = null;

	// Parent method
	ve.ce.Node.super.prototype.destroy.call( this );
};

/**
 * Get the model's HTML document
 *
 * @return {HTMLDocument} Model document
 */
ve.ce.Node.prototype.getModelHtmlDocument = function () {
	return this.model.getDocument() && this.model.getDocument().getHtmlDocument();
};
