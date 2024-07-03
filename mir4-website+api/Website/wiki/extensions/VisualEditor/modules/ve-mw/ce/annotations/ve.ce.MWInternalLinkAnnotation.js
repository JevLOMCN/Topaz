/*!
 * VisualEditor ContentEditable MWInternalLinkAnnotation class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki internal link annotation.
 *
 * @class
 * @extends ve.ce.LinkAnnotation
 * @constructor
 * @param {ve.dm.MWInternalLinkAnnotation} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this annotation
 * @param {Object} [config] Configuration options
 */
ve.ce.MWInternalLinkAnnotation = function VeCeMWInternalLinkAnnotation() {
	// Parent constructor
	ve.ce.MWInternalLinkAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$anchor.addClass( 've-ce-mwInternalLinkAnnotation' );

	this.updateClasses();
};

/* Inheritance */

OO.inheritClass( ve.ce.MWInternalLinkAnnotation, ve.ce.LinkAnnotation );

/* Static Properties */

ve.ce.MWInternalLinkAnnotation.static.name = 'link/mwInternal';

/* Static Methods */

/**
 * @inheritdoc
 */
ve.ce.MWInternalLinkAnnotation.static.getDescription = function ( model ) {
	return model.getAttribute( 'title' );
};

/* Methods */

/**
 * Update CSS classes form model state
 */
ve.ce.MWInternalLinkAnnotation.prototype.updateClasses = function () {
	var model = this.getModel();

	if ( model.element.originalDomElementsHash ) {
		// If the link came from Parsoid, use the 'new' class to
		// determine if this is a 'missing' link.
		var entry = {};
		entry[ model.getAttribute( 'lookupTitle' ) ] = {
			// eslint-disable-next-line no-jquery/no-class-state
			missing: this.$anchor.hasClass( 'new' )
		};
		ve.init.platform.linkCache.setMissing( entry );
	} else {
		// otherwise do an API/cache lookup
		ve.init.platform.linkCache.styleElement(
			model.getAttribute( 'lookupTitle' ),
			this.$anchor,
			!!model.getFragment()
		);
	}
};

/* Registration */

ve.ce.annotationFactory.register( ve.ce.MWInternalLinkAnnotation );
