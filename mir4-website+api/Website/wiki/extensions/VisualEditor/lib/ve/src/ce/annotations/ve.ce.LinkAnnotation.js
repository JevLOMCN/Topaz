/*!
 * VisualEditor ContentEditable LinkAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable link annotation.
 *
 * @class
 * @extends ve.ce.Annotation
 * @mixins ve.ce.NailedAnnotation
 * @constructor
 * @param {ve.dm.LinkAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.LinkAnnotation = function VeCeLinkAnnotation( model, parentNode, config ) {
	// Parent constructor
	ve.ce.LinkAnnotation.super.call( this, model, parentNode, ve.extendObject( { $element: $( '<a>' ) }, config ) );

	// Mixin constructor
	ve.ce.NailedAnnotation.call( this );

	this.$element.addClass( 've-ce-linkAnnotation' )
		.prop( {
			title: this.constructor.static.getDescription( this.model )
		} );
	// T322704
	ve.setAttributeSafe( this.$element[ 0 ], 'href', ve.resolveUrl( this.model.getHref(), this.getModelHtmlDocument() ), '#' );

	this.$element.on( 'click', this.onClick.bind( this ) );
	// Deprecated, use this.$element
	this.$anchor = this.$element;
};

/* Inheritance */

OO.inheritClass( ve.ce.LinkAnnotation, ve.ce.Annotation );

OO.mixinClass( ve.ce.LinkAnnotation, ve.ce.NailedAnnotation );

/* Static Properties */

ve.ce.LinkAnnotation.static.name = 'link';

ve.ce.LinkAnnotation.static.tagName = 'span';

/* Static Methods */

/**
 * @inheritdoc
 */
ve.ce.LinkAnnotation.static.getDescription = function ( model ) {
	return model.getHref();
};

/* Methods */

/* istanbul ignore next */
/**
 * Handle click events.
 *
 * @param {jQuery.Event} e Mouse click event
 */
ve.ce.LinkAnnotation.prototype.onClick = function ( e ) {
	if ( e.which === OO.ui.MouseButtons.LEFT && ( e.ctrlKey || e.metaKey ) ) {
		window.open( this.$element.prop( 'href' ) );
		// Prevent multiple windows being opened, or other action being performed (e.g. middle click paste)
		e.preventDefault();
	}
};

/* Registration */

ve.ce.annotationFactory.register( ve.ce.LinkAnnotation );
