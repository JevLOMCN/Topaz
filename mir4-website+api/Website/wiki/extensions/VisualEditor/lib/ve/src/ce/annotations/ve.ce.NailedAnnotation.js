/*!
 * VisualEditor ContentEditable NailedAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable nailed annotation.
 *
 * @class
 * @abstract
 * @constructor
 */
ve.ce.NailedAnnotation = function VeCeNailedAnnotation() {
	// Initialization
	this.contentFragment = document.createDocumentFragment();

	this.$element.addClass( 've-ce-nailedAnnotation' );
};

/* Inheritance */

OO.initClass( ve.ce.NailedAnnotation );

/* Static Properties */

ve.ce.NailedAnnotation.static.canBeActive = true;

/* Static Methods */

/**
 * Create a nail (a zero-width image) to add extra cursor positions around annotations
 *
 * @param {string} type Nail type, one of 'pre-open', 'pre-close', 'post-open' and 'post-close'
 * @return {HTMLElement} The new nail
 */
ve.ce.NailedAnnotation.static.makeNail = function ( type ) {
	var nail = document.createElement( 'img' );
	// Support: Firefox
	// Firefox <=37 misbehaves if we don't set an src: https://bugzilla.mozilla.org/show_bug.cgi?id=989012
	// Firefox misbehaves if we don't set an src and there is no sizing at node creation time: https://bugzilla.mozilla.org/show_bug.cgi?id=1267906
	// Setting an src in Chrome is slow, so only set it in affected versions of Firefox
	if ( $.client.profile().layout === 'gecko' || ve.inputDebug ) {
		nail.src = ve.inputDebug ? ve.ce.nailImgDataUri : ve.ce.minImgDataUri;
	}
	// The following classes are used here:
	// * ve-ce-nail-pre-open
	// * ve-ce-nail-pre-close
	// * ve-ce-nail-post-open
	// * ve-ce-nail-post-close
	nail.className = 've-ce-nail ve-ce-nail-' + type + ( ve.inputDebug ? ' ve-ce-nail-debug' : '' );
	return nail;
};

/* Methods */

/**
 * @inheritdoc ve.ce.Annotation
 */
ve.ce.NailedAnnotation.prototype.getContentContainer = function () {
	return this.contentFragment;
};

/**
 * @inheritdoc ve.ce.Annotation
 */
ve.ce.NailedAnnotation.prototype.attachContents = function () {
	var element = this.$element[ 0 ];
	// Insert post-open nail, annotation contents, and pre-close nail into the element
	element.appendChild( this.constructor.static.makeNail( 'post-open' ) );
	element.appendChild( this.contentFragment );
	element.appendChild( this.constructor.static.makeNail( 'pre-close' ) );
};

/**
 * @inheritdoc ve.ce.Annotation
 */
ve.ce.NailedAnnotation.prototype.appendTo = function ( node ) {
	// Insert pre-open nail, element, and post-close nail into a parent node
	node.appendChild( this.constructor.static.makeNail( 'pre-open' ) );
	node.appendChild( this.$element[ 0 ] );
	node.appendChild( this.constructor.static.makeNail( 'post-close' ) );
};
