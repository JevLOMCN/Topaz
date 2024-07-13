/*!
 * VisualEditor DataModel MWGalleryImageNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki gallery image node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWGalleryImageNode = function VeDmMWGalleryImageNode() {
	// Parent constructor
	ve.dm.MWGalleryImageNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWGalleryImageNode, ve.dm.BranchNode );

/* Static members */

ve.dm.MWGalleryImageNode.static.name = 'mwGalleryImage';

ve.dm.MWGalleryImageNode.static.matchTagNames = [ 'li' ];

ve.dm.MWGalleryImageNode.static.childNodeTypes = [ 'mwGalleryImageCaption' ];

ve.dm.MWGalleryImageNode.static.matchFunction = function ( element ) {
	var parentTypeof = ( element.parentNode && element.parentNode.getAttribute( 'typeof' ) ) || '';
	return element.getAttribute( 'class' ) === 'gallerybox' &&
		parentTypeof.trim().split( /\s+/ ).indexOf( 'mw:Extension/gallery' ) !== -1;
};

ve.dm.MWGalleryImageNode.static.parentNodeTypes = [ 'mwGallery' ];

ve.dm.MWGalleryImageNode.static.preserveHtmlAttributes = function ( attribute ) {
	var attributes = [ 'typeof', 'class', 'src', 'resource', 'width', 'height', 'href', 'rel', 'alt', 'data-mw' ];
	return attributes.indexOf( attribute ) === -1;
};
// By handling our own children we ensure that original DOM attributes
// are deep copied back by the converter (in renderHtmlAttributeList)
ve.dm.MWGalleryImageNode.static.handlesOwnChildren = true;

// This should be kept in sync with Parsoid's WTUtils::textContentFromCaption
// which drops <ref>s and metadata tags
ve.dm.MWGalleryImageNode.static.textContentFromCaption = function textContentFromCaption( node ) {
	var metaDataTags = [ 'base', 'link', 'meta', 'noscript', 'script', 'style', 'template', 'title' ];
	var content = '';
	var c = node.firstChild;
	while ( c ) {
		if ( c.nodeName === '#text' ) {
			content += c.nodeValue;
		} else if (
			c instanceof HTMLElement &&
			( metaDataTags.indexOf( c.nodeName.toLowerCase() ) === -1 ) &&
			!/\bmw:Extension\/ref\b/.test( c.getAttribute( 'typeOf' ) )
		) {
			content += textContentFromCaption( c );
		}
		c = c.nextSibling;
	}
	return content;
};

ve.dm.MWGalleryImageNode.static.toDataElement = function ( domElements, converter ) {
	// TODO: Improve handling of missing files. See 'isError' in MWBlockImageNode#toDataElement
	var li = domElements[ 0 ];
	var img = li.querySelector( '.mw-file-element' );
	var imgWrapper = img.parentNode;
	var container = imgWrapper.parentNode;

	// Get caption (may be missing for mode="packed-hover" galleries)
	var captionNode = li.querySelector( '.gallerytext' );
	if ( captionNode ) {
		captionNode = captionNode.cloneNode( true );
		// If showFilename is 'yes', the filename is also inside the caption, so throw this out
		var filename = captionNode.querySelector( '.galleryfilename' );
		if ( filename ) {
			filename.remove();
		}
	}

	// For video thumbnails, the `alt` attribute is only in the data-mw of the container (see: T348703)
	var mwDataJSON = container.getAttribute( 'data-mw' );
	var mwData = mwDataJSON ? JSON.parse( mwDataJSON ) : {};
	var mwAttribs = mwData.attribs || [];
	var containerAlt;
	for ( var i = mwAttribs.length - 1; i >= 0; i-- ) {
		if ( mwAttribs[ i ][ 0 ] === 'alt' && mwAttribs[ i ][ 1 ].txt ) {
			containerAlt = mwAttribs[ i ][ 1 ].txt;
			break;
		}
	}

	var altPresent = img.hasAttribute( 'alt' ) || containerAlt !== undefined;
	var altText = null;
	if ( altPresent ) {
		altText = img.hasAttribute( 'alt' ) ? img.getAttribute( 'alt' ) : containerAlt;
	}

	var altFromCaption = captionNode ?
		ve.dm.MWGalleryImageNode.static.textContentFromCaption( captionNode ).trim() : '';
	var altTextSame = altPresent && altFromCaption &&
		( altText.trim() === altFromCaption );

	var caption;
	if ( captionNode ) {
		caption = converter.getDataFromDomClean( captionNode, { type: 'mwGalleryImageCaption' } );
	} else {
		caption = [
			{ type: 'mwGalleryImageCaption' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{ type: '/paragraph' },
			{ type: '/mwGalleryImageCaption' }
		];
	}

	var typeofAttrs = container.getAttribute( 'typeof' ).trim().split( /\s+/ );
	var errorIndex = typeofAttrs.indexOf( 'mw:Error' );
	var isError = errorIndex !== -1;
	var errorText = isError ? img.textContent : null;
	var width = img.getAttribute( isError ? 'data-width' : 'width' );
	var height = img.getAttribute( isError ? 'data-height' : 'height' );

	if ( isError ) {
		typeofAttrs.splice( errorIndex, 1 );
	}

	var types = ve.dm.MWImageNode.static.rdfaToTypes[ typeofAttrs[ 0 ] ];

	var href = imgWrapper.getAttribute( 'href' );
	if ( href ) {
		// Convert absolute URLs to relative if the href refers to a page on this wiki.
		// Otherwise Parsoid generates |link= options for copy-pasted images (T193253).
		var targetData = mw.libs.ve.getTargetDataFromHref( href, converter.getTargetHtmlDocument() );
		if ( targetData.isInternal ) {
			href = mw.libs.ve.encodeParsoidResourceName( targetData.title );
		}
	}

	var dataElement = {
		type: this.name,
		attributes: {
			mediaClass: types.mediaClass,
			mediaTag: img.nodeName.toLowerCase(),
			resource: img.getAttribute( 'resource' ),
			altText: altText,
			altTextSame: altTextSame,
			href: href,
			// 'src' for images, 'poster' for video/audio
			src: img.getAttribute( 'src' ) || img.getAttribute( 'poster' ),
			width: width !== null && width !== '' ? +width : null,
			height: height !== null && height !== '' ? +height : null,
			isError: isError,
			errorText: errorText,
			mw: mwData,
			imageClassAttr: img.getAttribute( 'class' ),
			imgWrapperClassAttr: imgWrapper.getAttribute( 'class' )
		}
	};

	return [ dataElement ]
		.concat( caption )
		.concat( { type: '/' + this.name } );
};

ve.dm.MWGalleryImageNode.static.toDomElements = function ( data, doc, converter ) {
	// ImageNode:
	//   <li> li (gallerybox)
	//     <div> thumbDiv
	//       <span> container
	//         <a> a
	//           <img> img (or span if error)
	var model = data[ 0 ],
		attributes = model.attributes,
		li = doc.createElement( 'li' ),
		thumbDiv = doc.createElement( 'div' ),
		container = doc.createElement( 'span' ),
		imgWrapper = doc.createElement( attributes.href ? 'a' : 'span' ),
		img = doc.createElement( attributes.isError ? 'span' : attributes.mediaTag ),
		alt = attributes.altText,
		mwData = ve.copy( attributes.mw ) || {};

	li.classList.add( 'gallerybox' );
	thumbDiv.classList.add( 'thumb' );
	container.setAttribute( 'typeof', ve.dm.MWImageNode.static.getRdfa(
		attributes.mediaClass, 'none', attributes.isError
	) );

	if ( attributes.href ) {
		imgWrapper.setAttribute( 'href', attributes.href );
	}

	if ( attributes.imageClassAttr ) {
		// eslint-disable-next-line mediawiki/class-doc
		img.className = attributes.imageClassAttr;
	}

	if ( attributes.imgWrapperClassAttr ) {
		// eslint-disable-next-line mediawiki/class-doc
		imgWrapper.className = attributes.imgWrapperClassAttr;
	}

	img.setAttribute( 'resource', attributes.resource );
	if ( attributes.isError ) {
		var filename = mw.libs.ve.normalizeParsoidResourceName( attributes.resource || '' );
		img.appendChild( doc.createTextNode( attributes.errorText ? attributes.errorText : filename ) );
	} else {
		var srcAttr = ve.dm.MWImageNode.static.tagsToSrcAttrs[ img.nodeName.toLowerCase() ];
		img.setAttribute( srcAttr, attributes.src );
	}
	img.setAttribute( attributes.isError ? 'data-width' : 'width', attributes.width );
	img.setAttribute( attributes.isError ? 'data-height' : 'height', attributes.height );

	imgWrapper.appendChild( img );
	container.appendChild( imgWrapper );
	thumbDiv.appendChild( container );
	li.appendChild( thumbDiv );

	var captionData = data.slice( 1, -1 );
	var captionWrapper = doc.createElement( 'div' );
	converter.getDomSubtreeFromData( captionData, captionWrapper );
	while ( captionWrapper.firstChild ) {
		li.appendChild( captionWrapper.firstChild );
	}
	var captionText = ve.dm.MWGalleryImageNode.static.textContentFromCaption( li ).trim();

	if ( img.nodeName.toLowerCase() === 'img' ) {
		if ( attributes.altTextSame && captionText ) {
			img.setAttribute( 'alt', captionText );
		} else if ( typeof alt === 'string' ) {
			img.setAttribute( 'alt', alt );
		}
	} else {
		var mwAttribs = mwData.attribs || [];
		mwAttribs = mwAttribs.filter(
			function ( attr ) {
				return attr[ 0 ] !== 'alt';
			}
		);
		// Parsoid only sets an alt in the data-mw.attribs if it's explicit
		// in the source
		if ( !attributes.altTextSame && typeof alt === 'string' ) {
			mwAttribs.push( [ 'alt', { txt: alt } ] );
		}
		if ( mwData.attribs || mwAttribs.length ) {
			mwData.attribs = mwAttribs;
		}
	}

	if ( !ve.isEmptyObject( mwData ) ) {
		container.setAttribute( 'data-mw', JSON.stringify( mwData ) );
	}

	return [ li ];
};

ve.dm.MWGalleryImageNode.static.describeChange = function ( key ) {
	if ( key === 'altText' ) {
		// Parent method
		return ve.dm.MWGalleryImageNode.super.static.describeChange.apply( this, arguments );
	}
	// All other attributes are computed, or result in nodes being incomparable (`resource`)
	return null;
};

ve.dm.MWGalleryImageNode.static.isDiffComparable = function ( element, other ) {
	// Images with different src's shouldn't be diffed
	return element.type === other.type && element.attributes.resource === other.attributes.resource;
};

/* Methods */

/**
 * Get the image's caption node.
 *
 * @return {ve.dm.MWImageCaptionNode|null} Caption node, if present
 */
ve.dm.MWGalleryImageNode.prototype.getCaptionNode = function () {
	return this.children.length > 0 ? this.children[ 0 ] : null;
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWGalleryImageNode );
