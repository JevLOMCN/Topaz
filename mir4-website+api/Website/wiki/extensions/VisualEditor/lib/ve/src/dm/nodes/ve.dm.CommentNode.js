/*!
 * VisualEditor DataModel CommentNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * @class
 * @abstract
 * @extends ve.dm.LeafNode
 * @mixins ve.dm.FocusableNode
 *
 * @constructor
 * @param {Object} element Reference to element in meta-linmod
 */
ve.dm.CommentNode = function VeDmCommentNode( element ) {
	// Parent constructor
	ve.dm.CommentNode.super.call( this, element );

	// Mixin constructors
	ve.dm.FocusableNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.CommentNode, ve.dm.LeafNode );

OO.mixinClass( ve.dm.CommentNode, ve.dm.FocusableNode );

/* Static Properties */

ve.dm.CommentNode.static.isContent = true;

ve.dm.CommentNode.static.preserveHtmlAttributes = false;

ve.dm.CommentNode.static.toDataElement = function ( domElements, converter ) {
	var text;
	if ( domElements[ 0 ].nodeType === Node.COMMENT_NODE ) {
		text = ve.safeDecodeEntities( domElements[ 0 ].data );
	} else {
		text = domElements[ 0 ].getAttribute( 'data-ve-comment' );
	}
	return {
		// Disallows comment nodes between table rows and such
		type: converter.isValidChildNodeType( 'comment' ) && text !== '' ? 'comment' : 'commentMeta',
		attributes: {
			text: text
		}
	};
};

ve.dm.CommentNode.static.toDomElements = function ( dataElement, doc, converter ) {
	if ( converter.isForClipboard() ) {
		// Fake comment node
		var span = doc.createElement( 'span' );
		span.setAttribute( 'rel', 've:Comment' );
		span.setAttribute( 'data-ve-comment', dataElement.attributes.text );
		span.appendChild( doc.createTextNode( '\u00a0' ) );
		return [ span ];
	} else if ( converter.isForPreview() ) {
		// isForPreview(), use CE rendering
		var modelNode = ve.dm.nodeFactory.createFromElement( dataElement );
		modelNode.setDocument( converter.internalList.getDocument() );
		var viewNode = ve.ce.nodeFactory.createFromModel( modelNode );
		viewNode.updateInvisibleIconSync( true );
		viewNode.$element.attr( 'title', dataElement.attributes.text );
		var els = viewNode.$element.toArray();
		viewNode.destroy();
		return els;
	} else {
		// Real comment node
		// Encode & - > (see T95040, T144708)
		var data = dataElement.attributes.text.replace( /[-&>]/g, function ( c ) {
			return '&#x' + c.charCodeAt( 0 ).toString( 16 ).toUpperCase() + ';';
		} );
		return [ doc.createComment( data ) ];
	}
};

ve.dm.CommentNode.static.describeChange = function ( key, change ) {
	if ( key === 'text' ) {
		var diff = this.getAttributeDiff( change.from, change.to );
		if ( diff ) {
			// TODO: Use a word-break based diff for comment text
			return ve.htmlMsg( 'visualeditor-changedesc-comment-diff', diff );
		} else {
			return ve.htmlMsg( 'visualeditor-changedesc-comment', this.wrapText( 'del', change.from ), this.wrapText( 'ins', change.to ) );
		}
	}
};
