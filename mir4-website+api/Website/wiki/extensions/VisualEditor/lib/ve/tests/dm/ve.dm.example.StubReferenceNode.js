/*!
 * VisualEditor DataModel StubReferenceNode class.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * @class
 * @extends ve.dm.LeafNode
 * @mixin ve.dm.FocusableNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.example.StubReferenceNode = function VeDmStubReferenceNode() {
	// Parent constructor
	ve.dm.example.StubReferenceNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.FocusableNode.call( this );

	// Event handlers
	this.connect( this, {
		root: 'onRoot',
		unroot: 'onUnroot',
		attributeChange: 'onAttributeChange'
	} );
};

/* Inheritance */

OO.inheritClass( ve.dm.example.StubReferenceNode, ve.dm.LeafNode );

OO.mixinClass( ve.dm.example.StubReferenceNode, ve.dm.FocusableNode );

/* Static members */

ve.dm.example.StubReferenceNode.static.name = 'stubReference';

ve.dm.example.StubReferenceNode.static.matchTagNames = [ 'ref' ];

ve.dm.example.StubReferenceNode.static.isContent = true;

ve.dm.example.StubReferenceNode.static.handlesOwnChildren = true;

/**
 * Regular expression for parsing the listKey attribute
 *
 * Use [\s\S]* instead of .* to catch esoteric whitespace (T263698)
 *
 * @static
 * @property {RegExp}
 * @inheritable
 */
ve.dm.example.StubReferenceNode.static.listKeyRegex = /^(auto|literal)\/([\s\S]*)$/;

ve.dm.example.StubReferenceNode.static.toDataElement = function ( domElements, converter ) {
	var refElement = domElements[ 0 ];
	var body = refElement.innerHTML;
	var refGroup = refElement.getAttribute( 'group' ) || '';
	var listGroup = this.name + '/' + refGroup;
	var name = refElement.getAttribute( 'name' );
	var listKey = name ?
		'literal/' + name :
		'auto/' + converter.internalList.getNextUniqueNumber();
	var queueResult = converter.internalList.queueItemHtml( listGroup, listKey, body );
	var listIndex = queueResult.index;
	var contentsUsed = ( body !== '' && queueResult.isNew );

	var dataElement = {
		type: this.name,
		attributes: {
			listIndex: listIndex,
			listGroup: listGroup,
			listKey: listKey,
			refGroup: refGroup,
			contentsUsed: contentsUsed
		}
	};
	return dataElement;
};

ve.dm.example.StubReferenceNode.static.toDomElements = function ( dataElements, doc ) {
	return [ doc.createElement( 'ref' ) ];
};

/**
 * Handle the node being attached to the root
 */
ve.dm.example.StubReferenceNode.prototype.onRoot = function () {
	this.addToInternalList();
};

/**
 * Handle the node being detached from the root
 *
 * @param {ve.dm.DocumentNode} oldRoot Old document root
 */
ve.dm.example.StubReferenceNode.prototype.onUnroot = function ( oldRoot ) {
	if ( this.getDocument().getDocumentNode() === oldRoot ) {
		this.removeFromInternalList();
	}
};

/**
 * Register the node with the internal list
 */
ve.dm.example.StubReferenceNode.prototype.addToInternalList = function () {
	if ( this.getRoot() === this.getDocument().getDocumentNode() ) {
		this.registeredListGroup = this.element.attributes.listGroup;
		this.registeredListKey = this.element.attributes.listKey;
		this.registeredListIndex = this.element.attributes.listIndex;
		this.getDocument().getInternalList().addNode(
			this.registeredListGroup,
			this.registeredListKey,
			this.registeredListIndex,
			this
		);
	}
};

/**
 * Unregister the node from the internal list
 */
ve.dm.example.StubReferenceNode.prototype.removeFromInternalList = function () {
	if ( !this.registeredListGroup ) {
		// Don't try to remove if we haven't been added in the first place.
		return;
	}
	this.getDocument().getInternalList().removeNode(
		this.registeredListGroup,
		this.registeredListKey,
		this.registeredListIndex,
		this
	);
};

ve.dm.example.StubReferenceNode.prototype.onAttributeChange = function ( key, from, to ) {
	if (
		( key !== 'listGroup' && key !== 'listKey' ) ||
		( key === 'listGroup' && this.registeredListGroup === to ) ||
		( key === 'listKey' && this.registeredListKey === to )
	) {
		return;
	}

	// Need the old list keys and indexes, so we register them in addToInternalList
	// They've already been updated in this.element.attributes before this code runs
	this.removeFromInternalList();
	this.addToInternalList();
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.example.StubReferenceNode );
