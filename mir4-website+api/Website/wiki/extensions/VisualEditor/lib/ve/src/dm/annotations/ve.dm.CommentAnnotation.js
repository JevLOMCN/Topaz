/*!
 * VisualEditor DataModel CommentAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel comment annotation.
 *
 * @class
 * @extends ve.dm.Annotation
 * @constructor
 * @param {Object} element
 */
ve.dm.CommentAnnotation = function VeDmCommentAnnotation() {
	// Parent constructor
	ve.dm.CommentAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.CommentAnnotation, ve.dm.Annotation );

/* Static Properties */

ve.dm.CommentAnnotation.static.name = 'commentAnnotation';

ve.dm.CommentAnnotation.static.matchTagNames = [ 'span' ];

ve.dm.CommentAnnotation.static.matchRdfaTypes = [ 've:CommentAnnotation' ];

// ve.dm.CommentAnnotation.static.applyToAppendedContent = true;

ve.dm.CommentAnnotation.static.toDataElement = function ( domElements ) {
	return {
		type: this.name,
		attributes: {
			text: domElements[ 0 ].getAttribute( 'data-text' ) || ''
		}
	};
};

ve.dm.CommentAnnotation.static.toDomElements = function ( dataElement, doc, converter ) {
	if ( converter.isForParser() || converter.isForPreview() ) {
		// TODO: Return some nodes for preview?
		return [];
	} else {
		var domElement = doc.createElement( 'span' );
		domElement.setAttribute( 'rel', 've:CommentAnnotation' );
		if ( dataElement.attributes.text ) {
			domElement.setAttribute( 'data-text', dataElement.attributes.text );
		}
		return [ domElement ];
	}
};

/* Methods */

ve.dm.CommentAnnotation.prototype.getAttribute = function ( key ) {
	// Support old documents with text attributes
	if ( key === 'comments' && this.getAttribute( 'text' ) ) {
		return [ {
			author: '',
			text: this.getAttribute( 'text' )
		} ].concat(
			ve.dm.CommentAnnotation.super.prototype.getAttribute.call( this, 'comments' ) || []
		);
	}
	// Parent method
	return ve.dm.CommentAnnotation.super.prototype.getAttribute.call( this, key );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.CommentAnnotation );
