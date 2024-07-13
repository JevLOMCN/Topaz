/*!
 * VisualEditor DataModel MWImageNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki image node.
 *
 * @class
 * @abstract
 * @extends ve.dm.GeneratedContentNode
 * @mixins ve.dm.FocusableNode
 * @mixins ve.dm.ResizableNode
 *
 * @constructor
 */
ve.dm.MWImageNode = function VeDmMWImageNode() {
	// Parent constructor
	ve.dm.GeneratedContentNode.call( this );

	// Mixin constructors
	ve.dm.FocusableNode.call( this );
	// ve.dm.MWResizableNode doesn't exist
	ve.dm.ResizableNode.call( this );

	this.scalablePromise = null;

	// Use 'bitmap' as default media type until we can
	// fetch the actual media type from the API
	this.mediaType = 'BITMAP';

	// Initialize
	this.constructor.static.syncScalableToType(
		this.getAttribute( 'type' ),
		this.mediaType,
		this.getScalable()
	);

	// Events
	this.connect( this, { attributeChange: 'onAttributeChange' } );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWImageNode, ve.dm.GeneratedContentNode );

OO.mixinClass( ve.dm.MWImageNode, ve.dm.FocusableNode );

OO.mixinClass( ve.dm.MWImageNode, ve.dm.ResizableNode );

/* Static methods */

ve.dm.MWImageNode.static.rdfaToTypes = ( function () {
	var rdfaToType = {};

	[ 'File', 'Image', 'Video', 'Audio' ].forEach( function ( mediaClass ) {
		rdfaToType[ 'mw:' + mediaClass ] = { mediaClass: mediaClass, frameType: 'none' };
		rdfaToType[ 'mw:' + mediaClass + '/Frameless' ] = { mediaClass: mediaClass, frameType: 'frameless' };
		// Block image only:
		rdfaToType[ 'mw:' + mediaClass + '/Thumb' ] = { mediaClass: mediaClass, frameType: 'thumb' };
		rdfaToType[ 'mw:' + mediaClass + '/Frame' ] = { mediaClass: mediaClass, frameType: 'frame' };
	} );

	return rdfaToType;
}() );

/**
 * Get RDFa type
 *
 * @static
 * @param {string} mediaClass Media class, one of 'File', 'Image', 'Video' or 'Audio'
 * @param {string} frameType Frame type, one of 'none', 'frameless', 'thumb' or 'frame'
 * @param {boolean} isError Whether the included media file is missing
 * @return {string} RDFa type
 */
ve.dm.MWImageNode.static.getRdfa = function ( mediaClass, frameType, isError ) {
	return ( isError ? 'mw:Error ' : '' ) + 'mw:' + mediaClass + {
		none: '',
		frameless: '/Frameless',
		// Block image only:
		thumb: '/Thumb',
		frame: '/Frame'
	}[ frameType ];
};

/**
 * Map media tags to source attributes
 *
 * @type {Object}
 */
ve.dm.MWImageNode.static.tagsToSrcAttrs = {
	img: 'src',
	audio: null,
	video: 'poster',
	span: null
};

/**
 * @inheritdoc ve.dm.GeneratedContentNode
 */
ve.dm.MWImageNode.static.getHashObjectForRendering = function ( dataElement ) {
	// "Rendering" is just the URL of the thumbnail, so we only
	// care about src & dimensions
	return {
		type: 'mwImage',
		resource: dataElement.attributes.resource,
		width: dataElement.attributes.width,
		height: dataElement.attributes.height
	};
};

ve.dm.MWImageNode.static.getMatchRdfaTypes = function () {
	return Object.keys( this.rdfaToTypes );
};

ve.dm.MWImageNode.static.allowedRdfaTypes = [ 'mw:Error' ];

ve.dm.MWImageNode.static.isDiffComparable = function ( element, other ) {
	// Images with different src's shouldn't be diffed
	return element.type === other.type && element.attributes.resource === other.attributes.resource;
};

ve.dm.MWImageNode.static.describeChanges = function ( attributeChanges, attributes ) {
	var customKeys = [ 'width', 'height', 'defaultSize', 'src', 'href' ],
		descriptions = [];

	function describeSize( width, height ) {
		return width + ve.msg( 'visualeditor-dimensionswidget-times' ) + height + ve.msg( 'visualeditor-dimensionswidget-px' );
	}

	if ( 'width' in attributeChanges || 'height' in attributeChanges ) {
		var sizeFrom, sizeTo;
		if ( attributeChanges.defaultSize && attributeChanges.defaultSize.from === true ) {
			sizeFrom = ve.msg( 'visualeditor-mediasizewidget-sizeoptions-default' );
		} else {
			sizeFrom = describeSize(
				'width' in attributeChanges ? attributeChanges.width.from : attributes.width,
				'height' in attributeChanges ? attributeChanges.height.from : attributes.height
			);
		}
		if ( attributeChanges.defaultSize && attributeChanges.defaultSize.to === true ) {
			sizeTo = ve.msg( 'visualeditor-mediasizewidget-sizeoptions-default' );
		} else {
			sizeTo = describeSize(
				'width' in attributeChanges ? attributeChanges.width.to : attributes.width,
				'height' in attributeChanges ? attributeChanges.height.to : attributes.height
			);
		}

		descriptions.push(
			ve.htmlMsg( 'visualeditor-changedesc-image-size', this.wrapText( 'del', sizeFrom ), this.wrapText( 'ins', sizeTo ) )
		);
	}
	for ( var key in attributeChanges ) {
		if ( customKeys.indexOf( key ) === -1 ) {
			if ( key === 'borderImage' && !attributeChanges.borderImage.from && !attributeChanges.borderImage.to ) {
				// Skip noise from the data model
				continue;
			}
			var change = this.describeChange( key, attributeChanges[ key ] );
			if ( change ) {
				descriptions.push( change );
			}
		}
	}
	return descriptions;
};

ve.dm.MWImageNode.static.describeChange = function ( key, change ) {
	switch ( key ) {
		case 'align':
			return ve.htmlMsg( 'visualeditor-changedesc-align',
				// The following messages are used here:
				// * visualeditor-align-desc-left
				// * visualeditor-align-desc-right
				// * visualeditor-align-desc-center
				// * visualeditor-align-desc-default
				// * visualeditor-align-desc-none
				this.wrapText( 'del', ve.msg( 'visualeditor-align-desc-' + change.from ) ),
				this.wrapText( 'ins', ve.msg( 'visualeditor-align-desc-' + change.to ) )
			);
		case 'originalWidth':
		case 'originalHeight':
		case 'originalClasses':
		case 'unrecognizedClasses':
			return;
		// TODO: Handle valign
	}
	// Parent method
	return ve.dm.Node.static.describeChange.apply( this, arguments );
};

/**
 * Take the given dimensions and scale them to thumbnail size.
 *
 * @param {Object} dimensions Width and height of the image
 * @param {string} [mediaType] Media type 'DRAWING' or 'BITMAP'
 * @return {Object} The new width and height of the scaled image
 */
ve.dm.MWImageNode.static.scaleToThumbnailSize = function ( dimensions, mediaType ) {
	var defaultThumbSize = mw.config.get( 'wgVisualEditorConfig' )
		.thumbLimits[ mw.user.options.get( 'thumbsize' ) ];

	mediaType = mediaType || 'BITMAP';

	if ( dimensions.width && dimensions.height ) {
		// Use dimensions
		// Resize to default thumbnail size, but only if the image itself
		// isn't smaller than the default size
		// For svg/drawings, the default wiki size is always applied
		if ( dimensions.width > defaultThumbSize || mediaType === 'DRAWING' ) {
			return ve.dm.Scalable.static.getDimensionsFromValue( {
				width: defaultThumbSize
			}, dimensions.width / dimensions.height );
		}
	}
	return dimensions;
};

/**
 * Translate the image dimensions into new ones according to the bounding box.
 *
 * @param {Object} imageDimensions Width and height of the image
 * @param {Object} boundingBox The limit of the bounding box
 * @return {Object} The new width and height of the scaled image.
 */
ve.dm.MWImageNode.static.resizeToBoundingBox = function ( imageDimensions, boundingBox ) {
	var newDimensions = ve.copy( imageDimensions ),
		scale = Math.min(
			boundingBox.height / imageDimensions.height,
			boundingBox.width / imageDimensions.width
		);

	if ( scale < 1 ) {
		// Scale down
		newDimensions = {
			width: Math.floor( newDimensions.width * scale ),
			height: Math.floor( newDimensions.height * scale )
		};
	}
	return newDimensions;
};

/**
 * Update image scalable properties according to the image type.
 *
 * @param {string} type The new image type
 * @param {string} mediaType Image media type 'DRAWING' or 'BITMAP'
 * @param {ve.dm.Scalable} scalable The scalable object to update
 */
ve.dm.MWImageNode.static.syncScalableToType = function ( type, mediaType, scalable ) {
	var defaultThumbSize = mw.config.get( 'wgVisualEditorConfig' )
		.thumbLimits[ mw.user.options.get( 'thumbsize' ) ];

	var originalDimensions = scalable.getOriginalDimensions();

	// We can only set default dimensions if we have the original ones
	if ( originalDimensions ) {
		if ( type === 'thumb' || type === 'frameless' ) {
			// Set the default size to that in the wiki configuration if
			// 1. The original image width is not smaller than the default
			// 2. If the image is an SVG drawing
			var dimensions;
			if ( originalDimensions.width >= defaultThumbSize || mediaType === 'DRAWING' ) {
				dimensions = ve.dm.Scalable.static.getDimensionsFromValue( {
					width: defaultThumbSize
				}, scalable.getRatio() );
			} else {
				dimensions = ve.dm.Scalable.static.getDimensionsFromValue(
					originalDimensions,
					scalable.getRatio()
				);
			}
			scalable.setDefaultDimensions( dimensions );
		} else {
			scalable.setDefaultDimensions( originalDimensions );
		}
	}

	// Deal with maximum dimensions for images and drawings
	if ( mediaType === 'DRAWING' ) {
		// Vector images are scalable past their original dimensions
		// EnforcedMax may have previously been set to true
		scalable.setEnforcedMax( false );

	} else if ( mediaType === 'AUDIO' ) {
		// Audio files are scalable to any width but have fixed height
		scalable.fixedRatio = false;
		scalable.setMinDimensions( { width: 1, height: 32 } );
		// TODO: No way to enforce max height but not max width
		scalable.setMaxDimensions( { width: 99999, height: 32 } );
		scalable.setEnforcedMax( true );
		scalable.setEnforcedMin( true );

		// Default dimensions for audio files are 0x0, which is no good
		scalable.setDefaultDimensions( { width: defaultThumbSize, height: 32 } );

	} else {
		// Raster image files are limited to their original dimensions
		if ( originalDimensions ) {
			scalable.setMaxDimensions( originalDimensions );
			scalable.setEnforcedMax( true );
		} else {
			scalable.setEnforcedMax( false );
		}
	}
	// TODO: Some day, when $wgSvgMaxSize works properly in MediaWiki
	// we can add it back as max dimension consideration.
};

/**
 * Get the scalable promise which fetches original dimensions from the API
 *
 * @param {string} filename The image filename whose details the scalable will represent
 * @return {jQuery.Promise} Promise which resolves after the image size details are fetched from the API
 */
ve.dm.MWImageNode.static.getScalablePromise = function ( filename ) {
	// On the first call set off an async call to update the scalable's
	// original dimensions from the API.
	if ( ve.init.platform.imageInfoCache ) {
		return ve.init.platform.imageInfoCache.get( filename ).then( function ( info ) {
			if ( !info || info.missing ) {
				return ve.createDeferred().reject().promise();
			}
			return info;
		} );
	} else {
		return ve.createDeferred().reject().promise();
	}
};

/* Methods */

/**
 * Respond to attribute change.
 * Update the rendering of the 'align', src', 'width' and 'height' attributes
 * when they change in the model.
 *
 * @param {string} key Attribute key
 * @param {string} from Old value
 * @param {string} to New value
 */
ve.dm.MWImageNode.prototype.onAttributeChange = function ( key, from, to ) {
	if ( key === 'type' ) {
		this.constructor.static.syncScalableToType( to, this.mediaType, this.getScalable() );
	}
};

/**
 * Get the normalised filename of the image
 *
 * @return {string} Filename (including namespace)
 */
ve.dm.MWImageNode.prototype.getFilename = function () {
	return mw.libs.ve.normalizeParsoidResourceName( this.getAttribute( 'resource' ) || '' );
};

/**
 * @inheritdoc
 */
ve.dm.MWImageNode.prototype.getScalable = function () {
	var imageNode = this;
	if ( !this.scalablePromise ) {
		this.scalablePromise = ve.dm.MWImageNode.static.getScalablePromise( this.getFilename() );
		// If the promise was already resolved before getScalablePromise returned, then jQuery will execute the done straight away.
		// So don't just do getScalablePromise( ... ).done because we need to make sure that this.scalablePromise gets set first.
		this.scalablePromise.done( function ( info ) {
			if ( info ) {
				imageNode.getScalable().setOriginalDimensions( {
					width: info.width,
					height: info.height
				} );
				var oldMediaType = imageNode.mediaType;
				// Update media type
				imageNode.mediaType = info.mediatype;
				// Update according to type
				imageNode.constructor.static.syncScalableToType(
					imageNode.getAttribute( 'type' ),
					imageNode.mediaType,
					imageNode.getScalable()
				);
				imageNode.emit( 'attributeChange', 'mediaType', oldMediaType, imageNode.mediaType );
			}
		} );
	}
	// Mixin method
	return ve.dm.ResizableNode.prototype.getScalable.call( this );
};

/**
 * @inheritdoc
 */
ve.dm.MWImageNode.prototype.createScalable = function () {
	return new ve.dm.Scalable( {
		currentDimensions: {
			width: this.getAttribute( 'width' ),
			height: this.getAttribute( 'height' )
		},
		minDimensions: {
			width: 1,
			height: 1
		}
	} );
};

/**
 * Get symbolic name of media type.
 *
 * Example values: "BITMAP" for JPEG or PNG images; "DRAWING" for SVG graphics
 *
 * @return {string|undefined} Symbolic media type name, or undefined if empty
 */
ve.dm.MWImageNode.prototype.getMediaType = function () {
	return this.mediaType;
};

/**
 * Get RDFa type
 *
 * @return {string} RDFa type
 */
ve.dm.MWImageNode.prototype.getRdfa = function () {
	return this.constructor.static.getRdfa(
		this.getAttribute( 'mediaClass' ),
		this.getAttribute( 'type' ),
		this.getAttribute( 'isError' )
	);
};
