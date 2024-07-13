/*!
 * VisualEditor user interface MWGalleryItemWidget class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Draggable item widget for the MWGalleryGroupWidget
 *
 * @class
 * @extends OO.ui.Widget
 * @mixins OO.ui.mixin.DraggableElement
 *
 * @constructor
 * @param {Object} imageInfo Image information object
 * @param {Object} [config] Configuration options
 * @cfg {boolean} [isMobile=false]
 * @cfg {boolean} [draggable=true]
 */
ve.ui.MWGalleryItemWidget = function VeUiMWGalleryItemWidget( imageInfo, config ) {
	this.resource = imageInfo.resource;
	this.altText = imageInfo.altText || '';
	this.altTextSame = imageInfo.altTextSame;
	this.href = imageInfo.href;
	// Keep the original value which may be null
	this.originalAltText = imageInfo.altText;
	this.src = imageInfo.src;
	this.height = imageInfo.height;
	this.width = imageInfo.width;
	this.thumbUrl = imageInfo.thumbUrl;
	this.captionDocument = imageInfo.captionDocument;
	this.highlighted = false;
	this.tagName = imageInfo.tagName;
	this.isError = imageInfo.isError;
	this.imageClassAttr = imageInfo.imageClassAttr;
	this.imgWrapperClassAttr = imageInfo.imgWrapperClassAttr;
	this.mw = imageInfo.mw;
	this.mediaClass = imageInfo.mediaClass;
	this.mediaTag = imageInfo.mediaTag;

	// Configuration initialization
	config = config || {};

	// Parent constructor
	ve.ui.MWGalleryItemWidget.super.call( this, config );

	this.$element
		.addClass( 've-ui-mwGalleryDialog-image-container mw-no-invert' ) // TODO: put in new CSS file?
		.addClass( config.isMobile ?
			've-ui-mwGalleryDialog-image-container-mobile' :
			've-ui-mwGalleryDialog-image-container-desktop'
		)
		.css( 'background-image', 'url(' + this.thumbUrl + ')' );

	// Mixin constructors
	OO.ui.mixin.DraggableElement.call( this, ve.extendObject( { $handle: this.$element }, config ) );
	OO.ui.mixin.TabIndexedElement.call( this, config );

	this.$element.on( {
		click: this.onItemClick.bind( this ),
		keypress: this.onItemKeyPress.bind( this )
	} );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWGalleryItemWidget, OO.ui.Widget );

OO.mixinClass( ve.ui.MWGalleryItemWidget, OO.ui.mixin.DraggableElement );
OO.mixinClass( ve.ui.MWGalleryItemWidget, OO.ui.mixin.TabIndexedElement );

/* Events */

/**
 * @event edit
 */

/* Methods */

/**
 * Handle clicking on an item
 *
 * @fires edit
 */
ve.ui.MWGalleryItemWidget.prototype.onItemClick = function () {
	this.emit( 'edit', this );
};

/**
 * Handle key press events
 *
 * @param {jQuery.Event} e Key press event
 * @return {boolean}
 * @fires edit
 */
ve.ui.MWGalleryItemWidget.prototype.onItemKeyPress = function ( e ) {
	if ( e.which === OO.ui.Keys.ENTER ) {
		this.emit( 'edit', this );
		return false;
	}
};

/**
 * Set the captionDocument property
 *
 * @param {ve.dm.Document} captionDocument The caption document
 */
ve.ui.MWGalleryItemWidget.prototype.setCaptionDocument = function ( captionDocument ) {
	this.captionDocument = captionDocument;
};

/**
 * Set the altText property
 *
 * @param {string} altText The altText
 */
ve.ui.MWGalleryItemWidget.prototype.setAltText = function ( altText ) {
	this.altText = altText;
};

/**
 * Set the altTextSame property
 *
 * @param {boolean} same
 */
ve.ui.MWGalleryItemWidget.prototype.setAltTextSame = function ( same ) {
	this.altTextSame = same;
};

/**
 * Toggle highlighted class
 *
 * @param {boolean} highlighted The item is highlighted
 */
ve.ui.MWGalleryItemWidget.prototype.toggleHighlighted = function ( highlighted ) {
	highlighted = highlighted !== undefined ? highlighted : !this.highlighted;
	this.$element.toggleClass( 've-ui-mwGalleryDialog-image-container-highlighted', !!highlighted );
};
