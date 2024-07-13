/*!
 * VisualEditor ContentEditable MWNowikiAnnotation class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki nowiki annotation.
 *
 * @class
 * @extends ve.ce.Annotation
 * @constructor
 * @param {ve.dm.MWNowikiAnnotation} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWNowikiAnnotation = function VeCeMWNowikiAnnotation() {
	// Parent constructor
	ve.ce.MWNowikiAnnotation.super.apply( this, arguments );

	// DOM changes
	this.$element.addClass( 've-ce-mwNowikiAnnotation' );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWNowikiAnnotation, ve.ce.Annotation );

/* Static Properties */

ve.ce.MWNowikiAnnotation.static.name = 'mwNowiki';

ve.ce.MWNowikiAnnotation.static.tagName = 'span';

/* Registration */

ve.ce.annotationFactory.register( ve.ce.MWNowikiAnnotation );
