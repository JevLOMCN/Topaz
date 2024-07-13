/*!
 * VisualEditor DataModel MWInlineImage class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki image node.
 *
 * @class
 * @extends ve.dm.LeafNode
 * @mixins ve.dm.MWImageNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWInlineImageNode = function VeDmMWInlineImageNode() {
	// Parent constructor
	ve.dm.MWInlineImageNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.MWImageNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWInlineImageNode, ve.dm.LeafNode );

// Need to mixin base class as well (T92540)
OO.mixinClass( ve.dm.MWInlineImageNode, ve.dm.GeneratedContentNode );

OO.mixinClass( ve.dm.MWInlineImageNode, ve.dm.MWImageNode );

/* Static Properties */

ve.dm.MWInlineImageNode.static.isContent = true;

ve.dm.MWInlineImageNode.static.name = 'mwInlineImage';

ve.dm.MWInlineImageNode.static.preserveHtmlAttributes = function ( attribute ) {
	var attributes = [ 'typeof', 'class', 'src', 'resource', 'width', 'height', 'href', 'data-mw' ];
	return attributes.indexOf( attribute ) === -1;
};

ve.dm.MWInlineImageNode.static.matchTagNames = [ 'span' ];

ve.dm.MWInlineImageNode.static.disallowedAnnotationTypes = [ 'link' ];

ve.dm.MWInlineImageNode.static.toDataElement = function ( domElements, converter ) {
	var container = domElements[ 0 ]; // <span>
	if ( !container.children.length ) {
		// Malformed image, alienate (T267282)
		return null;
	}
	var img = container.querySelector( '.mw-file-element' ); // <img>, <video>, <audio>, or <span> if mw:Error
	// Images copied from the old parser output can have typeof=mw:Image but no resource information. T337438
	if ( !img || !img.hasAttribute( 'resource' ) ) {
		return [];
	}
	var imgWrapper = img.parentNode; // <a> or <span>
	var typeofAttrs = ( container.getAttribute( 'typeof' ) || '' ).trim().split( /\s+/ );
	var mwDataJSON = container.getAttribute( 'data-mw' );
	var mwData = mwDataJSON ? JSON.parse( mwDataJSON ) : {};
	var classes = container.getAttribute( 'class' );
	var recognizedClasses = [];
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
		originalClasses: classes,
		width: width !== null && width !== '' ? +width : null,
		height: height !== null && height !== '' ? +height : null,
		alt: img.getAttribute( 'alt' ),
		mw: mwData,
		isError: isError,
		errorText: errorText
	};

	// Extract individual classes
	classes = typeof classes === 'string' ? classes.trim().split( /\s+/ ) : [];

	// Deal with border flag
	if ( classes.indexOf( 'mw-image-border' ) !== -1 ) {
		attributes.borderImage = true;
		recognizedClasses.push( 'mw-image-border' );
	}

	// Vertical alignment
	attributes.valign = 'default';
	[ 'midde', 'baseline', 'sub', 'super', 'top', 'text-top', 'bottom', 'text-bottom' ].some( function ( valign ) {
		var className = 'mw-valign-' + valign;
		if ( classes.indexOf( className ) !== -1 ) {
			attributes.valign = valign;
			recognizedClasses.push( className );
			return true;
		}
		return false;
	} );

	// Border
	if ( classes.indexOf( 'mw-image-border' ) !== -1 ) {
		attributes.borderImage = true;
		recognizedClasses.push( 'mw-image-border' );
	}

	// Default-size
	if ( classes.indexOf( 'mw-default-size' ) !== -1 ) {
		attributes.defaultSize = true;
		recognizedClasses.push( 'mw-default-size' );
	}

	// Store unrecognized classes so we can restore them on the way out
	attributes.unrecognizedClasses = OO.simpleArrayDifference( classes, recognizedClasses );

	var dataElement = { type: this.name, attributes: attributes };

	this.storeGeneratedContents( dataElement, dataElement.attributes.src, converter.getStore() );

	return dataElement;
};

ve.dm.MWInlineImageNode.static.toDomElements = function ( dataElement, doc, converter ) {
	var attributes = dataElement.attributes,
		container = doc.createElement( 'span' ),
		imgWrapper = doc.createElement( attributes.href ? 'a' : 'span' ),
		img = doc.createElement( attributes.isError ? 'span' : attributes.mediaTag ),
		classes = [],
		originalClasses = attributes.originalClasses;

	ve.setDomAttributes( img, attributes, [ 'resource' ] );
	var width = attributes.width;
	var height = attributes.height;
	if ( width !== null ) {
		img.setAttribute( attributes.isError ? 'data-width' : 'width', width );
	}
	if ( height !== null ) {
		img.setAttribute( attributes.isError ? 'data-width' : 'height', height );
	}

	var srcAttr = this.tagsToSrcAttrs[ img.nodeName.toLowerCase() ];
	if ( srcAttr && !attributes.isError ) {
		img.setAttribute( srcAttr, attributes.src );
	}

	// TODO: This does not make sense for broken images (when img is a span node)
	if ( typeof attributes.alt === 'string' ) {
		img.setAttribute( 'alt', attributes.alt );
	}

	// RDFa type
	container.setAttribute( 'typeof', this.getRdfa( attributes.mediaClass, attributes.type, attributes.isError ) );
	if ( !ve.isEmptyObject( attributes.mw ) ) {
		container.setAttribute( 'data-mw', JSON.stringify( attributes.mw ) );
	}

	if ( attributes.defaultSize ) {
		classes.push( 'mw-default-size' );
	}

	if ( attributes.borderImage ) {
		classes.push( 'mw-image-border' );
	}

	if ( attributes.valign && attributes.valign !== 'default' ) {
		classes.push( 'mw-valign-' + attributes.valign );
	}

	if ( attributes.unrecognizedClasses ) {
		classes = OO.simpleArrayUnion( classes, attributes.unrecognizedClasses );
	}

	if (
		originalClasses &&
		ve.compare( originalClasses.trim().split( /\s+/ ).sort(), classes.sort() )
	) {
		// eslint-disable-next-line mediawiki/class-doc
		container.className = originalClasses;
	} else if ( classes.length > 0 ) {
		// eslint-disable-next-line mediawiki/class-doc
		container.className = classes.join( ' ' );
	}

	if ( attributes.href ) {
		imgWrapper.setAttribute( 'href', attributes.href );
	}

	if ( attributes.imgWrapperClassAttr ) {
		// eslint-disable-next-line mediawiki/class-doc
		imgWrapper.className = attributes.imgWrapperClassAttr;
	}

	if ( attributes.imageClassAttr ) {
		// eslint-disable-next-line mediawiki/class-doc
		img.className = attributes.imageClassAttr;
	}

	if ( attributes.isError ) {
		if ( converter.isForPreview() ) {
			imgWrapper.classList.add( 'new' );
		}
		var filename = mw.libs.ve.normalizeParsoidResourceName( attributes.resource || '' );
		img.appendChild( doc.createTextNode( attributes.errorText ? attributes.errorText : filename ) );
	}

	imgWrapper.appendChild( img );
	container.appendChild( imgWrapper );

	return [ container ];
};

/* Registration */

ve.dm.modelRegistry.unregister( ve.dm.InlineImageNode );
ve.dm.modelRegistry.register( ve.dm.MWInlineImageNode );
