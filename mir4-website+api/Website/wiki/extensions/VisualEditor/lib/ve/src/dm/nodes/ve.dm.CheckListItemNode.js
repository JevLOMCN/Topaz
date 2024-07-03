/*!
 * VisualEditor DataModel CheckListItemNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel list item node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.CheckListItemNode = function VeDmCheckListItemNode() {
	// Parent constructor
	ve.dm.CheckListItemNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.CheckListItemNode, ve.dm.BranchNode );

/* Static Properties */

ve.dm.CheckListItemNode.static.name = 'checkListItem';

ve.dm.CheckListItemNode.static.parentNodeTypes = [ 'checkList' ];

ve.dm.CheckListItemNode.static.matchTagNames = [ 'li' ];

ve.dm.CheckListItemNode.static.matchRdfaTypes = [ 've:checkList' ];

ve.dm.CheckListItemNode.static.handlesOwnChildren = true;

ve.dm.CheckListItemNode.static.toDataElement = function ( domElements, converter ) {
	var checked = domElements[ 0 ].hasAttribute( 'data-checked' ) ||
		// Old HTML format used the invalid attribute "checked"
		domElements[ 0 ].hasAttribute( 'checked' );
	var element = { type: this.name, attributes: { checked: checked } };
	return converter.getDataFromDomClean( domElements[ 0 ], element );
};

ve.dm.CheckListItemNode.static.toDomElements = function ( data, doc, converter ) {
	var dataElement = data[ 0 ];
	var listItem = doc.createElement( 'li' );
	listItem.setAttribute( 'rel', 've:checkList' );
	if ( dataElement.attributes.checked ) {
		listItem.setAttribute( 'data-checked', 'checked' );
	}

	var contents = data.slice( 1, -1 );
	if ( contents.length ) {
		var wrapper = doc.createElement( 'div' );
		converter.getDomSubtreeFromData( contents, wrapper );
		while ( wrapper.firstChild ) {
			listItem.appendChild( wrapper.firstChild );
		}
	}

	// Formatting for external paste / preview
	// * Hide the bullet list
	// * Add a unicode checkbox to the text
	var checkboxText = document.createTextNode( dataElement.attributes.checked ? '☑' : '☐' );
	var checkbox;
	if ( converter.isForParser() ) {
		checkbox = checkboxText;
	} else {
		listItem.style.listStyle = 'none';
		checkbox = document.createElement( 'span' );
		checkbox.setAttribute( 'data-ve-ignore', 'true' );
		checkbox.appendChild( checkboxText );
	}

	// The first child should be the wrapper paragraph
	var textContainer = listItem.firstChild.nodeType === Node.TEXT_NODE ? listItem : listItem.firstChild;
	textContainer.insertBefore( document.createTextNode( ' ' ), textContainer.firstChild );
	textContainer.insertBefore( checkbox, textContainer.firstChild );

	return [ listItem ];
};

ve.dm.CheckListItemNode.static.resetAttributesForClone = function ( clonedElement ) {
	clonedElement.attributes.checked = false;
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.CheckListItemNode );
