'use strict';

/*!
 * VisualEditor DataModel MWReferenceNode class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * DataModel MediaWiki reference node.
 *
 * @constructor
 * @extends ve.dm.LeafNode
 * @mixes ve.dm.FocusableNode
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWReferenceNode = function VeDmMWReferenceNode() {
	// Parent constructor
	ve.dm.MWReferenceNode.super.apply( this, arguments );

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

OO.inheritClass( ve.dm.MWReferenceNode, ve.dm.LeafNode );

OO.mixinClass( ve.dm.MWReferenceNode, ve.dm.FocusableNode );

/* Static members */

ve.dm.MWReferenceNode.static.name = 'mwReference';

ve.dm.MWReferenceNode.static.matchTagNames = null;

ve.dm.MWReferenceNode.static.matchRdfaTypes = [ 'mw:Extension/ref' ];

// Handle nodes with mw:Error as this probably just means the ref list doesn't exist (T299672)
ve.dm.MWReferenceNode.static.allowedRdfaTypes = [ 'dc:references', 'mw:Error' ];

ve.dm.MWReferenceNode.static.isContent = true;

ve.dm.MWReferenceNode.static.disallowedAnnotationTypes = [ 'link' ];

/**
 * Regular expression for parsing the listKey attribute
 *
 * Use [\s\S]* instead of .* to catch esoteric whitespace (T263698)
 *
 * @static
 * @property {RegExp}
 * @inheritable
 */
ve.dm.MWReferenceNode.static.listKeyRegex = /^(auto|literal)\/([\s\S]*)$/;

ve.dm.MWReferenceNode.static.toDataElement = function ( domElements, converter ) {
	function getReflistItemHtml( id ) {
		const elem = converter.getHtmlDocument().getElementById( id );
		return ( elem && elem.innerHTML ) || '';
	}

	const mwDataJSON = domElements[ 0 ].getAttribute( 'data-mw' );
	const mwData = mwDataJSON ? JSON.parse( mwDataJSON ) : {};
	const reflistItemId = mwData.body && mwData.body.id;
	const body = ( mwData.body && mwData.body.html ) ||
		( reflistItemId && getReflistItemHtml( reflistItemId ) ) ||
		'';
	const extendsRef = mw.config.get( 'wgCiteBookReferencing' ) && mwData.attrs && mwData.attrs.extends;
	const refGroup = mwData.attrs && mwData.attrs.group || '';
	const listGroup = this.name + '/' + refGroup;
	const autoKeyed = !mwData.attrs || mwData.attrs.name === undefined;
	const listKey = autoKeyed ?
		'auto/' + converter.internalList.getNextUniqueNumber() :
		'literal/' + mwData.attrs.name;
	const queueResult = converter.internalList.queueItemHtml( listGroup, listKey, body );
	const listIndex = queueResult.index;
	const contentsUsed = ( body !== '' && queueResult.isNew );

	const dataElement = {
		type: this.name,
		attributes: {
			mw: mwData,
			originalMw: mwDataJSON,
			listIndex: listIndex,
			listGroup: listGroup,
			listKey: listKey,
			refGroup: refGroup,
			contentsUsed: contentsUsed
		}
	};
	if ( extendsRef ) {
		dataElement.attributes.extendsRef = extendsRef;
	}
	if ( reflistItemId ) {
		dataElement.attributes.refListItemId = reflistItemId;
	}
	return dataElement;
};

ve.dm.MWReferenceNode.static.toDomElements = function ( dataElement, doc, converter ) {
	const isForClipboard = converter.isForClipboard();
	const el = doc.createElement( 'sup' );

	el.setAttribute( 'typeof', 'mw:Extension/ref' );

	const mwData = dataElement.attributes.mw ? ve.copy( dataElement.attributes.mw ) : {};
	mwData.name = 'ref';

	if ( isForClipboard || converter.isForParser() ) {
		let setContents = dataElement.attributes.contentsUsed;

		// This call rebuilds the document tree if it isn't built already (e.g. on a
		// document slice), so only use when necessary (i.e. not in preview mode)
		const itemNode = converter.internalList.getItemNode( dataElement.attributes.listIndex );
		const itemNodeRange = itemNode.getRange();

		const keyedNodes = converter.internalList
			.getNodeGroup( dataElement.attributes.listGroup )
			.keyedNodes[ dataElement.attributes.listKey ];

		let contentsAlreadySet = false;
		if ( setContents ) {
			// Check if a previous node has already set the content. If so, we don't overwrite this
			// node's contents.
			if ( keyedNodes ) {
				for ( let i = 0; i < keyedNodes.length; i++ ) {
					if (
						ve.compare(
							this.getInstanceHashObject( keyedNodes[ i ].element ),
							this.getInstanceHashObject( dataElement )
						)
					) {
						break;
					}
					if ( keyedNodes[ i ].element.attributes.contentsUsed ) {
						contentsAlreadySet = true;
						break;
					}
				}
			}
		} else {
			// Check if any other nodes with this key provided content. If not
			// then we attach the contents to the first reference with this key

			// Check that this is the first reference with its key
			if (
				keyedNodes &&
				ve.compare(
					this.getInstanceHashObject( dataElement ),
					this.getInstanceHashObject( keyedNodes[ 0 ].element )
				)
			) {
				setContents = true;
				// Check no other reference originally defined the contents
				// As this is keyedNodes[0] we can start at 1
				for ( let i = 1; i < keyedNodes.length; i++ ) {
					if ( keyedNodes[ i ].element.attributes.contentsUsed ) {
						setContents = false;
						break;
					}
				}
			}
		}

		// Add reference contents to data-mw.
		if ( setContents && !contentsAlreadySet ) {
			const itemNodeWrapper = doc.createElement( 'div' );
			const originalHtmlWrapper = doc.createElement( 'div' );
			converter.getDomSubtreeFromData(
				itemNode.getDocument().getFullData( itemNodeRange, 'roundTrip' ),
				itemNodeWrapper
			);
			// Returns '' if itemNodeWrapper is empty
			const itemNodeHtml = itemNodeWrapper.innerHTML;
			const originalHtml = ve.getProp( mwData, 'body', 'html' ) ||
				( ve.getProp( mwData, 'body', 'id' ) !== undefined && itemNode.getAttribute( 'originalHtml' ) ) ||
				'';
			originalHtmlWrapper.innerHTML = originalHtml;
			// Only set body.html if itemNodeHtml and originalHtml are actually different,
			// or we are writing the clipboard for use in another VE instance
			if ( isForClipboard || !originalHtmlWrapper.isEqualNode( itemNodeWrapper ) ) {
				ve.setProp( mwData, 'body', 'html', itemNodeHtml );
			}
		}

		// If we have no internal item data for this reference, don't let it get pasted into
		// another VE document. T110479
		if ( isForClipboard && itemNodeRange.isCollapsed() ) {
			el.setAttribute( 'data-ve-ignore', 'true' );
		}

		// Set extends
		if ( dataElement.attributes.extendsRef ) {
			ve.setProp( mwData, 'attrs', 'extends', dataElement.attributes.extendsRef );
		}

		// Generate name
		let name;
		const listKeyParts = dataElement.attributes.listKey.match( this.listKeyRegex );
		if ( listKeyParts[ 1 ] === 'auto' ) {
			// Only render a name if this key was reused
			if ( keyedNodes.length > 1 ) {
				// Allocate a unique list key, then strip the 'literal/'' prefix
				name = converter.internalList.getUniqueListKey(
					dataElement.attributes.listGroup,
					dataElement.attributes.listKey,
					// Generate a name starting with ':' to distinguish it from normal names
					'literal/:'
				).slice( 'literal/'.length );
			}
		} else {
			// Use literal name
			name = listKeyParts[ 2 ];
		}
		// Set name
		if ( name !== undefined ) {
			ve.setProp( mwData, 'attrs', 'name', name );
		}

		// Set or clear group
		if ( dataElement.attributes.refGroup !== '' ) {
			ve.setProp( mwData, 'attrs', 'group', dataElement.attributes.refGroup );
		} else if ( mwData.attrs ) {
			delete mwData.attrs.group;
		}
	}

	// If mwAttr and originalMw are the same, use originalMw to prevent reserialization,
	// unless we are writing the clipboard for use in another VE instance
	// Reserialization has the potential to reorder keys and so change the DOM unnecessarily
	const originalMw = dataElement.attributes.originalMw;
	if ( converter.isForParser() && originalMw && ve.compare( mwData, JSON.parse( originalMw ) ) ) {
		el.setAttribute( 'data-mw', originalMw );

		// Return the original DOM elements if possible
		if ( dataElement.originalDomElementsHash !== undefined ) {
			return ve.copyDomElements(
				converter.getStore().value( dataElement.originalDomElementsHash ), doc );
		}
	} else {
		el.setAttribute( 'data-mw', JSON.stringify( mwData ) );

		// HTML for the external clipboard, it will be ignored by the converter
		const $link = $( '<a>', doc )
			.css( 'counterReset', 'mw-Ref ' + this.getIndex( dataElement, converter.internalList ) )
			.attr( 'data-mw-group', this.getGroup( dataElement ) || null );
		$( el ).addClass( 'mw-ref reference' ).append(
			$link.append(
				$( '<span>', doc ).addClass( 'mw-reflink-text' ).text( this.getIndexLabel( dataElement, converter.internalList ) )
			)
		);
	}

	return [ el ];
};

ve.dm.MWReferenceNode.static.remapInternalListIndexes = function (
	dataElement, mapping, internalList
) {
	// Remap listIndex
	dataElement.attributes.listIndex = mapping[ dataElement.attributes.listIndex ];

	// Remap listKey if it was automatically generated
	const listKeyParts = dataElement.attributes.listKey.match( this.listKeyRegex );
	if ( listKeyParts[ 1 ] === 'auto' ) {
		dataElement.attributes.listKey = 'auto/' + internalList.getNextUniqueNumber();
	}
};

ve.dm.MWReferenceNode.static.remapInternalListKeys = function ( dataElement, internalList ) {
	let suffix = '';
	// Try name, name2, name3, ... until unique
	while ( internalList.keys.indexOf( dataElement.attributes.listKey + suffix ) !== -1 ) {
		suffix = suffix ? suffix + 1 : 2;
	}
	if ( suffix ) {
		dataElement.attributes.listKey += suffix;
	}
};

/**
 * Gets the index for the reference
 *
 * @static
 * @param {Object} dataElement Element data
 * @param {ve.dm.InternalList} internalList Internal list
 * @return {number} Index
 */
ve.dm.MWReferenceNode.static.getIndex = function ( dataElement, internalList ) {
	const overrideIndex = ve.getProp( dataElement, 'internal', 'overrideIndex' );
	const attrs = dataElement.attributes;
	return overrideIndex ||
		( internalList.getIndexPosition( attrs.listGroup, attrs.listIndex ) + 1 );
};

/**
 * Gets the group for the reference
 *
 * @static
 * @param {Object} dataElement Element data
 * @return {string} Group
 */
ve.dm.MWReferenceNode.static.getGroup = function ( dataElement ) {
	return dataElement.attributes.refGroup;
};

/**
 * Gets the index label for the reference
 *
 * @static
 * @param {Object} dataElement Element data
 * @param {ve.dm.InternalList} internalList Internal list
 * @return {string} Reference label
 */
ve.dm.MWReferenceNode.static.getIndexLabel = function ( dataElement, internalList ) {
	const refGroup = dataElement.attributes.refGroup;
	const index = dataElement.attributes.placeholder ? '…' :
		ve.dm.MWReferenceNode.static.getIndex( dataElement, internalList );

	return '[' + ( refGroup ? refGroup + ' ' : '' ) + index + ']';
};

/**
 * @override
 * @see ve.dm.Node
 */
ve.dm.MWReferenceNode.static.cloneElement = function () {
	const clone = ve.dm.MWReferenceNode.super.static.cloneElement.apply( this, arguments );
	delete clone.attributes.contentsUsed;
	delete clone.attributes.mw;
	delete clone.attributes.originalMw;
	// HACK: Generate a fake hash so this element is never instance comparable to other elements
	// Without originalMw this hash will not get used in toDomElements
	clone.originalDomElementsHash = Math.random();
	return clone;
};

/**
 * @override
 * @see ve.dm.LeafNode
 */
ve.dm.MWReferenceNode.static.getHashObject = function ( dataElement ) {
	// Consider all references in the same group to be comparable:
	// References can't be usefully compared statically, as they are mostly
	// defined by the contents of their internal item, which exists
	// elsewhere in the document.
	// For diffing, comparing reference indexes is not useful as
	// they are auto-generated, and the reference list diff is
	// already handled separately, so will show moves etc.
	//
	// If you need to compare references with the same name, use
	// #getInstanceHashObject
	return {
		type: dataElement.type,
		attributes: {
			listGroup: dataElement.attributes.listGroup
		}
	};
};

/**
 * Get a hash unique to this instance of the reference
 *
 * As #getHashObject has been simplified to make re-used references
 * all equal (to support visual diffing), provide access to a more
 * typical hash that can be used to compare instances of reference
 * which have the same "name".
 *
 * @param {Object} dataElement
 * @return {Object}
 */
ve.dm.MWReferenceNode.static.getInstanceHashObject = function () {
	return ve.dm.MWReferenceNode.super.static.getHashObject.apply( this, arguments );
};

/**
 * @override
 * @see ve.dm.Model
 */
ve.dm.MWReferenceNode.static.describeChange = function ( key, change ) {
	if ( key === 'refGroup' ) {
		if ( !change.from ) {
			return ve.htmlMsg( 'cite-ve-changedesc-ref-group-to', this.wrapText( 'ins', change.to ) );
		} else if ( !change.to ) {
			return ve.htmlMsg( 'cite-ve-changedesc-ref-group-from', this.wrapText( 'del', change.from ) );
		} else {
			return ve.htmlMsg( 'cite-ve-changedesc-ref-group-both', this.wrapText( 'del', change.from ), this.wrapText( 'ins', change.to ) );
		}
	}
};

/* Methods */

/**
 * Don't allow reference nodes to be edited if we can't find their contents.
 *
 * @override
 * @see ve.dm.Model
 */
ve.dm.MWReferenceNode.prototype.isEditable = function () {
	const internalItem = this.getInternalItem();
	return internalItem && internalItem.getLength() > 0;
};

/**
 * Gets the internal item node associated with this node
 *
 * @return {ve.dm.InternalItemNode} Item node
 */
ve.dm.MWReferenceNode.prototype.getInternalItem = function () {
	return this.getDocument().getInternalList().getItemNode( this.getAttribute( 'listIndex' ) );
};

/**
 * Gets the index for the reference
 *
 * @return {number} Index
 */
ve.dm.MWReferenceNode.prototype.getIndex = function () {
	return this.constructor.static.getIndex( this.element, this.getDocument().getInternalList() );
};

/**
 * Gets the group for the reference
 *
 * @return {string} Group
 */
ve.dm.MWReferenceNode.prototype.getGroup = function () {
	return this.constructor.static.getGroup( this.element );
};

/**
 * Gets the index label for the reference
 *
 * @return {string} Reference label
 */
ve.dm.MWReferenceNode.prototype.getIndexLabel = function () {
	return this.constructor.static.getIndexLabel(
		this.element, this.getDocument().getInternalList() );
};

/**
 * Handle the node being attached to the root
 */
ve.dm.MWReferenceNode.prototype.onRoot = function () {
	this.addToInternalList();
};

/**
 * Handle the node being detached from the root
 *
 * @param {ve.dm.DocumentNode} oldRoot Old document root
 */
ve.dm.MWReferenceNode.prototype.onUnroot = function ( oldRoot ) {
	if ( this.getDocument().getDocumentNode() === oldRoot ) {
		this.removeFromInternalList();
	}
};

/**
 * Register the node with the internal list
 */
ve.dm.MWReferenceNode.prototype.addToInternalList = function () {
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
ve.dm.MWReferenceNode.prototype.removeFromInternalList = function () {
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

ve.dm.MWReferenceNode.prototype.onAttributeChange = function ( key, from, to ) {
	if ( key === 'placeholder' ) {
		this.getDocument().getInternalList().markGroupAsChanged( this.registeredListGroup );
	}
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

ve.dm.modelRegistry.register( ve.dm.MWReferenceNode );
