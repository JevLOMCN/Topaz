/*!
 * VisualEditor CommentAnnotationContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a comment.
 *
 * @class
 * @extends ve.ui.AnnotationContextItem
 *
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config] Configuration options
 */
ve.ui.CommentAnnotationContextItem = function VeUiCommentAnnotationContextItem( context, model, config ) {
	// Parent constructor
	ve.ui.CommentAnnotationContextItem.super.call( this, context, model, config );

	// Initialization
	this.$element.addClass( 've-ui-commentAnnotationContextItem' );

	this.editButton.setLabel( ve.msg( 'visualeditor-commentannotationcontextitem-comment' ) );
};

/* Inheritance */

OO.inheritClass( ve.ui.CommentAnnotationContextItem, ve.ui.AnnotationContextItem );

/* Static Properties */

ve.ui.CommentAnnotationContextItem.static.name = 'commentAnnotation';

ve.ui.CommentAnnotationContextItem.static.icon = 'speechBubble';

ve.ui.CommentAnnotationContextItem.static.label = OO.ui.deferMsg( 'visualeditor-commentinspector-title' );

ve.ui.CommentAnnotationContextItem.static.modelClasses = [ ve.dm.CommentAnnotation ];

ve.ui.CommentAnnotationContextItem.static.embeddable = false;

ve.ui.CommentAnnotationContextItem.static.commandName = 'commentAnnotation';

ve.ui.CommentAnnotationContextItem.static.clearable = true;

ve.ui.CommentAnnotationContextItem.static.clearIcon = 'trash';

/* Static methods */

/**
 * @param {ve.dm.CommentAnnotation} model
 * @return {jQuery}
 */
ve.ui.CommentAnnotationContextItem.static.renderThread = function ( model ) {
	var $thread = $( [] );

	model.getAttribute( 'comments' ).forEach( function ( comment ) {
		var $lineDivs = comment.text.split( '\n' ).map( function ( line ) {
			return $( '<div>' ).text( line );
		} );
		$thread = $thread.add(
			$( '<div>' ).addClass( 've-ui-commentAnnotationContextItem-comment' ).append(
				$( '<strong>' ).text( comment.author ),
				$lineDivs
			)
		);
	} );

	return $thread;
};

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.CommentAnnotationContextItem.prototype.renderBody = function () {
	var $thread = this.constructor.static.renderThread( this.model );
	this.$body.append( $thread );
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.CommentAnnotationContextItem );
