/*!
 * VisualEditor DataModel MWBlockImageNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki image node.
 *
 * @class
 * @extends ve.dm.BranchNode
 * @mixins ve.dm.MWImageNode
 * @mixins ve.dm.ClassAttributeNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWBlockImageNode = function VeDmMWBlockImageNode() {
	// Parent constructor
	ve.dm.MWBlockImageNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.MWImageNode.call( this );
	ve.dm.ClassAttributeNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWBlockImageNode, ve.dm.BranchNode );

// Need to mixin base class as well (T92540)
OO.mixinClass( ve.dm.MWBlockImageNode, ve.dm.GeneratedContentNode );

OO.mixinClass( ve.dm.MWBlockImageNode, ve.dm.MWImageNode );

OO.mixinClass( ve.dm.MWBlockImageNode, ve.dm.ClassAttributeNode );

/* Static Properties */

ve.dm.MWBlockImageNode.static.name = 'mwBlockImage';

ve.dm.MWBlockImageNode.static.preserveHtmlAttributes = function ( attribute ) {
	var attributes = [ 'typeof', 'class', 'src', 'resource', 'width', 'height', 'href', 'rel', 'data-mw', 'alt' ];
	return attributes.indexOf( attribute ) === -1;
};

ve.dm.MWBlockImageNode.static.handlesOwnChildren = true;

ve.dm.MWBlockImageNode.static.childNodeTypes = [ 'mwImageCaption' ];

ve.dm.MWBlockImageNode.static.matchTagNames = [ 'figure' ];

ve.dm.MWBlockImageNode.static.disallowedAnnotationTypes = [ 'link' ];

ve.dm.MWBlockImageNode.static.classAttributes = {
	'mw-image-border': { borderImage: true },
	'mw-halign-left': { align: 'left' },
	'mw-halign-right': { align: 'right' },
	'mw-halign-center': { align: 'center' },
	'mw-halign-none': { align: 'none' },
	'mw-default-size': { defaultSize: true }
};

ve.dm.MWBlockImageNode.static.toDataElement = function ( domElements, converter ) {
	var figure = domElements[ 0 ];
	var img = figure.querySelector( '.mw-file-element' ); // <img>, <video>, <audio>, or <span> if mw:Error
	// Images copied from the old parser output can have typeof=mw:Image but no resource information. T337438
	if ( !img || !img.hasAttribute( 'resource' ) ) {
		return [];
	}
	var imgWrapper = img.parentNode; // <a> or <span>
	// NB: A caption could contain another block image with a caption, but the outer
	// image must always contain a caption for that to happen, and that one will match first.
	var captionNode = figure.querySelector( 'figcaption' );
	var classAttr = figure.getAttribute( 'class' );
	var typeofAttrs = figure.getAttribute( 'typeof' ).trim().split( /\s+/ );
	var mwDataJSON = figure.getAttribute( 'data-mw' );
	var mwData = mwDataJSON ? JSON.parse( mwDataJSON ) : {};
	var errorIndex = typeofAttrs.indexOf( 'mw:Error' );
	var isError = errorIndex !== -1;
	var errorText = isError ? img.textContent : null;
	var width = img.getAttribute( isError ? 'data-width' : 'width' );
	var height = img.getAttribute( isError ? 'data-height' : 'height' );

	var href = imgWrapper.getAttribute( 'href' );
	if ( href ) {
		// Convert absolute URLs to relative if the href refers to a page on this wiki.
		// Otherwise Parsoid generates |link= options for copy-pasted images (T193253).
		var targetData = mw.libs.ve.getTargetDataFromHref( href, converter.getTargetHtmlDocument() );
		if ( targetData.isInternal ) {
			href = mw.libs.ve.encodeParsoidResourceName( targetData.title );
		}
	}

	if ( isError ) {
		typeofAttrs.splice( errorIndex, 1 );
	}

	var types = this.rdfaToTypes[ typeofAttrs[ 0 ] ];

	var attributes = {
		mediaClass: types.mediaClass,
		mediaTag: img.nodeName.toLowerCase(),
		type: types.frameType,
		src: img.getAttribute( 'src' ) || img.getAttribute( 'poster' ),
		href: href,
		imageClassAttr: img.getAttribute( 'class' ),
		imgWrapperClassAttr: imgWrapper.getAttribute( 'class' ),
		resource: img.getAttribute( 'resource' ),
		width: width !== null && width !== '' ? +width : null,
		height: height !== null && height !== '' ? +height : null,
		alt: img.getAttribute( 'alt' ),
		mw: mwData,
		isError: isError,
		errorText: errorText
	};

	this.setClassAttributes( attributes, classAttr );

	attributes.align = attributes.align || 'default';

	// Default-size
	if ( attributes.defaultSize ) {
		// Force wiki-default size for thumb and frameless
		if (
			attributes.type === 'thumb' ||
			attributes.type === 'frameless'
		) {
			// We're going to change .width and .height, store the original
			// values so we can restore them later.
			// FIXME "just" don't modify .width and .height instead
			attributes.originalWidth = attributes.width;
			attributes.originalHeight = attributes.height;
			// Parsoid hands us images with default Wikipedia dimensions
			// rather than default MediaWiki configuration dimensions.
			// We must force local wiki default in edit mode for default
			// size images.
			var newDimensions = this.scaleToThumbnailSize( attributes );
			if ( newDimensions ) {
				attributes.width = newDimensions.width;
				attributes.height = newDimensions.height;
			}
		}
	}

	var caption;
	if ( captionNode ) {
		caption = converter.getDataFromDomClean( captionNode, { type: 'mwImageCaption' } );
	} else {
		caption = [
			{ type: 'mwImageCaption' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{ type: '/paragraph' },
			{ type: '/mwImageCaption' }
		];
	}

	var dataElement = { type: this.name, attributes: attributes };

	this.storeGeneratedContents( dataElement, dataElement.attributes.src, converter.getStore() );

	return [ dataElement ]
		.concat( caption )
		.concat( { type: '/' + this.name } );
};

// TODO: At this moment node is not resizable but when it will be then adding defaultSize class
// should be more conditional.
ve.dm.MWBlockImageNode.static.toDomElements = function ( data, doc, converter ) {
	var dataElement = data[ 0 ],
		attributes = dataElement.attributes,
		figure = doc.createElement( 'figure' ),
		imgWrapper = doc.createElement( attributes.href ? 'a' : 'span' ),
		img = doc.createElement( attributes.isError ? 'span' : attributes.mediaTag ),
		classAttr = this.getClassAttrFromAttributes( attributes );

	// RDFa type
	figure.setAttribute( 'typeof', this.getRdfa( attributes.mediaClass, attributes.type, attributes.isError ) );
	if ( !ve.isEmptyObject( attributes.mw ) ) {
		figure.setAttribute( 'data-mw', JSON.stringify( attributes.mw ) );
	}

	if ( classAttr ) {
		// eslint-disable-next-line mediawiki/class-doc
		figure.className = classAttr;
	}

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

	var width = attributes.width;
	var height = attributes.height;
	// If defaultSize is set, and was set on the way in, use the original width and height
	// we got on the way in.
	if ( attributes.defaultSize ) {
		if ( attributes.originalWidth !== undefined ) {
			width = attributes.originalWidth;
		}
		if ( attributes.originalHeight !== undefined ) {
			height = attributes.originalHeight;
		}
	}

	var srcAttr = this.tagsToSrcAttrs[ img.nodeName.toLowerCase() ];
	if ( srcAttr && !attributes.isError ) {
		img.setAttribute( srcAttr, attributes.src );
	}
	if ( attributes.isError ) {
		if ( converter.isForPreview() ) {
			imgWrapper.classList.add( 'new' );
		}
		var filename = mw.libs.ve.normalizeParsoidResourceName( attributes.resource || '' );
		img.appendChild( doc.createTextNode( attributes.errorText ? attributes.errorText : filename ) );
	}

	if ( width !== null ) {
		img.setAttribute( attributes.isError ? 'data-width' : 'width', width );
	}
	if ( height !== null ) {
		img.setAttribute( attributes.isError ? 'data-height' : 'height', height );
	}

	img.setAttribute( 'resource', attributes.resource );
	// TODO: This does not make sense for broken images (when img is a span node)
	if ( typeof attributes.alt === 'string' ) {
		img.setAttribute( 'alt', attributes.alt );
	}
	figure.appendChild( imgWrapper );
	imgWrapper.appendChild( img );

	var captionData = data.slice( 1, -1 );
	// If length of captionData is smaller or equal to 2 it means that there is no caption or that
	// it is empty - in both cases we are going to skip appending <figcaption>.
	if ( captionData.length > 2 ) {
		var captionWrapper = doc.createElement( 'div' );
		converter.getDomSubtreeFromData( captionData, captionWrapper );
		while ( captionWrapper.firstChild ) {
			figure.appendChild( captionWrapper.firstChild );
		}
	}
	return [ figure ];
};

/* Methods */

/**
 * Get the caption node of the image.
 *
 * @return {ve.dm.MWImageCaptionNode|null} Caption node, if present
 */
ve.dm.MWBlockImageNode.prototype.getCaptionNode = function () {
	var node = this.children[ 0 ];
	return node instanceof ve.dm.MWImageCaptionNode ? node : null;
};

/**
 * @inheritdoc
 */
ve.dm.MWBlockImageNode.prototype.suppressSlugType = function () {
	// TODO: Have alignment attribute changes trigger a parent branch node re-render
	var align = this.getAttribute( 'align' );
	return align !== 'none' && align !== 'center' ? 'float' : null;
};

/* Registration */

ve.dm.modelRegistry.unregister( ve.dm.BlockImageNode );
ve.dm.modelRegistry.register( ve.dm.MWBlockImageNode );
