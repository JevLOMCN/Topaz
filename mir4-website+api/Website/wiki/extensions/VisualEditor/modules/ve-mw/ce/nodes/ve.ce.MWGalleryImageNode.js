/*!
 * VisualEditor ContentEditable MWGalleryImageNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki gallery image node.
 *
 * @class
 * @extends ve.ce.BranchNode
 *
 * @constructor
 * @param {ve.dm.MWGalleryImageNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWGalleryImageNode = function VeCeMWGalleryImageNode( model ) {
	// Parent constructor
	ve.ce.MWGalleryImageNode.super.apply( this, arguments );

	// DOM hierarchy for MWGalleryImageNode:
	//   <li> this.$element (gallerybox)
	//     <div> thumbDiv
	//       <span> innerDiv
	//         <a> a
	//           <img> img
	//     <a> filenameA (galleryfilename)
	//     <figcaption> ve.ce.MWGalleryImageCaptionNode

	var defaults = mw.config.get( 'wgVisualEditorConfig' ).galleryOptions;
	var attributes = model.getAttributes();
	var galleryMwAttrs = model.parent.getAttribute( 'mw' ).attrs;

	// Putting all this setup in the constructor works because MWGalleryImageNodes are never updated,
	// only created from scratch

	// These dimensions are different depending on the gallery mode.
	// (This only vaguely approximates the actual rendering.)
	var mode = galleryMwAttrs.mode || defaults.mode;
	var innerDivWidth, innerDivHeight, innerDivMargin, outerDivWidth;
	if ( mode === 'traditional' || mode === 'nolines' || mode === 'slideshow' ) {
		var imagePadding = ( mode === 'traditional' ? 30 : 0 );
		innerDivWidth = parseInt( galleryMwAttrs.widths || defaults.imageWidth ) + imagePadding;
		innerDivHeight = parseInt( galleryMwAttrs.heights || defaults.imageHeight ) + imagePadding;
		if ( mode === 'traditional' ) {
			var imageHeight = parseInt( attributes.height );
			innerDivMargin = ( ( innerDivHeight - imageHeight ) / 2 ) + 'px auto';
		} else {
			innerDivMargin = 0;
		}
		outerDivWidth = innerDivWidth + 8;
	} else {
		innerDivWidth = parseInt( attributes.width );
		innerDivHeight = parseInt( galleryMwAttrs.heights || defaults.imageHeight );
		innerDivMargin = 0;
		outerDivWidth = innerDivWidth + 4;
	}

	var resourceTitle = mw.Title.newFromText( mw.libs.ve.normalizeParsoidResourceName( attributes.resource ) );

	this.$element
		.addClass( 'gallerybox' )
		.css( 'width', outerDivWidth + 'px' );
	var $thumbDiv = $( '<div>' )
		.addClass( 'thumb' )
		.css( 'width', innerDivWidth + 'px' )
		.css( 'height', innerDivHeight + 'px' );
	var $innerDiv = $( '<span>' )
		.css( 'margin', innerDivMargin );
	var $a = $( '<a>' ).addClass( 'mw-file-description' );

	var $img;
	if ( model.getAttribute( 'isError' ) ) {
		$img = $( '<span>' )
			.addClass( 'mw-file-element mw-broken-media' )
			.text( model.getAttribute( 'errorText' ) );
	} else {
		$img = $( '<img>' )
			.addClass( 'mw-file-element' )
			.attr( 'resource', attributes.resource )
			.attr( 'alt', attributes.altText )
			.attr( 'src', attributes.src )
			.attr( 'height', attributes.height )
			.attr( 'width', attributes.width );
	}

	this.$filenameA = $( '<a>' )
		.attr( 'href', '#' ) // Just to make it look like a link
		.text( resourceTitle ? resourceTitle.getMainText() : attributes.resource )
		.toggleClass( 'oo-ui-element-hidden', galleryMwAttrs.showfilename !== 'yes' );

	this.$element.prepend(
		$thumbDiv.append(
			$innerDiv.append(
				$a.append(
					$img
				)
			)
		),
		this.$filenameA
	);

	this.model.parent.connect( this, { attributeChange: 'onGalleryAttributeChange' } );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWGalleryImageNode, ve.ce.BranchNode );

/* Static Properties */

ve.ce.MWGalleryImageNode.static.name = 'mwGalleryImage';

ve.ce.MWGalleryImageNode.static.tagName = 'li';

/* Methods */

ve.ce.MWGalleryImageNode.prototype.onGalleryAttributeChange = function ( key, from, to ) {
	if ( key !== 'mw' ) {
		return;
	}

	this.$filenameA.toggleClass( 'oo-ui-element-hidden', to.attrs.showfilename !== 'yes' );
};

ve.ce.MWGalleryImageNode.prototype.getDomPosition = function () {
	// We need to override this because this.$element can have children other than renderings of child
	// CE nodes (specifically, the image, this.$thumbDiv), which throws the calculations out of whack.
	// Luckily, MWGalleryImageNode is very simple and can contain at most one other node: its caption,
	// which is always inserted at the end.
	var domNode = this.$element.last()[ 0 ];
	return {
		node: domNode,
		offset: domNode.childNodes.length
	};
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWGalleryImageNode );
