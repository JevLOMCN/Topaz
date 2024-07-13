/*!
 * VisualEditor DataModel ListNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel list node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.ListNode = function VeDmListNode() {
	// Parent constructor
	ve.dm.ListNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.ListNode, ve.dm.BranchNode );

/* Static Properties */

ve.dm.ListNode.static.name = 'list';

ve.dm.ListNode.static.childNodeTypes = [ 'listItem' ];

ve.dm.ListNode.static.defaultAttributes = {
	style: 'bullet'
};

ve.dm.ListNode.static.matchTagNames = [ 'ul', 'ol' ];

ve.dm.ListNode.static.isDiffedAsList = true;

/**
 * Creates a list item element
 *
 * @return {Object} Element data
 */
ve.dm.ListNode.static.createItem = function () {
	return { type: 'listItem' };
};

ve.dm.ListNode.static.toDataElement = function ( domElements ) {
	var style = domElements[ 0 ].nodeName.toLowerCase() === 'ol' ? 'number' : 'bullet';
	return { type: this.name, attributes: { style: style } };
};

ve.dm.ListNode.static.toDomElements = function ( dataElement, doc ) {
	var tag = dataElement.attributes && dataElement.attributes.style === 'number' ? 'ol' : 'ul';
	return [ doc.createElement( tag ) ];
};

ve.dm.ListNode.static.describeChanges = function ( attributeChanges, attributes, element ) {
	attributeChanges = ve.copy( attributeChanges );
	if ( 'listType' in attributeChanges ) {
		var mapped = false;
		[ 'from', 'to' ].forEach( function ( fromOrTo ) {
			if ( attributeChanges.listType[ fromOrTo ] === 'definitionList' ) {
				attributeChanges.style[ fromOrTo ] = 'indent';
				mapped = true;
			}
		} );
		if ( mapped ) {
			delete attributeChanges.listType;
		}
	}
	// Parent method
	return ve.dm.ListNode.super.static.describeChanges.call( this, attributeChanges, attributes, element );
};

ve.dm.ListNode.static.describeChange = function ( key, change ) {
	var messageKeys = {
		bullet: 'visualeditor-listbutton-bullet-tooltip',
		number: 'visualeditor-listbutton-number-tooltip',
		indent: 'visualeditor-changedesc-list-style-indent'
	};
	if ( key === 'style' && change.from in messageKeys && change.to in messageKeys ) {
		return ve.htmlMsg( 'visualeditor-changedesc-no-key',
			// Message keys documented above
			// eslint-disable-next-line mediawiki/msg-doc
			this.wrapText( 'del', ve.msg( messageKeys[ change.from ] ) ),
			// eslint-disable-next-line mediawiki/msg-doc
			this.wrapText( 'ins', ve.msg( messageKeys[ change.to ] ) )
		);
	}
	// Parent method
	return ve.dm.ListNode.super.static.describeChange.apply( this, arguments );
};

/* Methods */

ve.dm.ListNode.prototype.canHaveSlugAfter = function () {
	// A paragraph can be added after a list by pressing enter in an empty list item
	return false;
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.ListNode );
