/*!
 * VisualEditor DataModel MWImageModel class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki image model.
 *
 * @class
 * @mixins OO.EventEmitter
 *
 * @constructor
 * @param {ve.dm.Document} parentDoc Document that contains or will contain the image
 * @param {Object} [config] Configuration options
 * @cfg {string} [resourceName] The resource name of the given media file
 * @cfg {Object} [currentDimensions={}] Current dimensions, width & height
 * @cfg {Object} [minDimensions={}] Minimum dimensions, width & height
 * @cfg {boolean} [isDefaultSize=false] Object is using its default size dimensions
 */
ve.dm.MWImageModel = function VeDmMWImageModel( parentDoc, config ) {
	config = config || {};

	// Mixin constructors
	OO.EventEmitter.call( this );

	// Properties
	this.attributesCache = {};

	// Image properties
	this.parentDoc = parentDoc;
	this.captionDoc = null;
	this.caption = null;
	this.mediaType = null;
	this.altText = '';
	this.type = null;
	this.alignment = null;
	this.scalable = null;
	this.sizeType = null;
	this.border = false;
	this.borderable = false;
	this.defaultDimensions = null;
	this.changedImageSource = false;

	this.imageSrc = '';
	this.imageResourceName = '';
	this.imageHref = '';
	this.imageClassAttr = null;

	// FIXME: This is blindly being preserved but may not apply if, say,
	// a link is no longer pointing to a file description page.  When support
	// for editing the |link= media option is added, take it into account.
	this.imgWrapperClassAttr = null;

	this.boundingBox = null;
	this.initialHash = {};

	// Get wiki default thumbnail size
	this.defaultThumbSize = mw.config.get( 'wgVisualEditorConfig' )
		.thumbLimits[ mw.user.options.get( 'thumbsize' ) ];

	if ( config.resourceName ) {
		this.setImageResourceName( config.resourceName );
	}

	// Create scalable
	var currentDimensions = config.currentDimensions || {};
	var minDimensions = config.minDimensions || {};

	var scalable = new ve.dm.Scalable( {
		currentDimensions: {
			width: currentDimensions.width,
			height: currentDimensions.height
		},
		minDimensions: {
			width: minDimensions.width || 1,
			height: minDimensions.height || 1
		},
		defaultSize: !!config.isDefaultSize
	} );
	// Set the initial scalable, connect it to events
	// and request an update from the API
	this.attachScalable( scalable );
};

/* Inheritance */

OO.mixinClass( ve.dm.MWImageModel, OO.EventEmitter );

/* Events */

/**
 * Change of image alignment or of having alignment at all
 *
 * @event alignmentChange
 * @param {string} Alignment 'left', 'right', 'center' or 'none'
 */

/**
 * Change in size type between default and custom
 *
 * @event sizeDefaultChange
 * @param {boolean} Image is default size
 */

/**
 * Change in the image type
 *
 * @event typeChange
 * @param {string} Image type 'thumb', 'frame', 'frameless' or 'none'
 */

/* Static Properties */

ve.dm.MWImageModel.static.infoCache = {};

/* Static Methods */

/**
 * Create a new image node based on given parameters.
 *
 * @param {Object} attributes Image attributes
 * @param {string} [imageType] Image node type 'mwInlineImage' or 'mwBlockImage'.
 *  Defaults to 'mwBlockImage'
 * @return {ve.dm.MWImageNode} An image node
 */
ve.dm.MWImageModel.static.createImageNode = function ( attributes, imageType ) {
	var defaultThumbSize = mw.config.get( 'wgVisualEditorConfig' )
		.thumbLimits[ mw.user.options.get( 'thumbsize' ) ];

	var attrs = ve.extendObject( {
		mediaClass: 'File',
		mediaTag: 'img',
		type: 'thumb',
		align: 'default',
		width: defaultThumbSize,
		mediaType: 'BITMAP',
		defaultSize: true,
		imageClassAttr: 'mw-file-element'
	}, attributes );

	if ( attrs.defaultSize ) {
		var newDimensions = ve.dm.MWImageNode.static.scaleToThumbnailSize( attrs, attrs.mediaType );
		if ( newDimensions ) {
			attrs.width = newDimensions.width;
			attrs.height = newDimensions.height;
		}
	}

	imageType = imageType || 'mwBlockImage';

	var newNode = ve.dm.nodeFactory.createFromElement( {
		type: imageType,
		attributes: attrs
	} );

	ve.dm.MWImageNode.static.syncScalableToType( attrs.type, attrs.mediaType, newNode.getScalable() );

	return newNode;
};

/**
 * Load from image data with scalable information.
 *
 * @param {Object} attrs Image node attributes
 * @param {ve.dm.Document} parentDoc Document that contains or will contain the image
 * @return {ve.dm.MWImageModel} Image model
 */
ve.dm.MWImageModel.static.newFromImageAttributes = function ( attrs, parentDoc ) {
	var imgModel = new ve.dm.MWImageModel(
		parentDoc,
		{
			resourceName: attrs.resource,
			currentDimensions: {
				width: attrs.width,
				height: attrs.height
			},
			defaultSize: !!attrs.defaultSize
		}
	);

	// Cache the attributes so we can create a new image without
	// losing any existing information
	imgModel.cacheOriginalImageAttributes( attrs );

	imgModel.setImageSource( attrs.src );
	imgModel.setFilename( new mw.Title( mw.libs.ve.normalizeParsoidResourceName( attrs.resource ) ).getMainText() );
	imgModel.setImageHref( attrs.href );
	imgModel.setImageClassAttr( attrs.imageClassAttr );
	imgModel.setImgWrapperClassAttr( attrs.imgWrapperClassAttr );

	// Set bounding box
	imgModel.setBoundingBox( {
		width: attrs.width,
		height: attrs.height
	} );

	// Collect all the information
	imgModel.toggleBorder( !!attrs.borderImage );
	imgModel.setAltText( attrs.alt || '' );

	imgModel.setType( attrs.type );

	// Fix cases where alignment is undefined
	// Inline images have no 'align' (they have 'valign' instead)
	// But we do want an alignment case for these in case they
	// are transformed to block images
	imgModel.setAlignment( attrs.align || 'default' );

	// Default size
	imgModel.toggleDefaultSize( !!attrs.defaultSize );

	// TODO: When scale/upright is available, set the size
	// type accordingly
	imgModel.setSizeType( imgModel.isDefaultSize() ? 'default' : 'custom' );

	return imgModel;
};

/**
 * Load from existing image node.
 *
 * @param {ve.dm.MWImageNode} node Image node
 * @return {ve.dm.MWImageModel} Image model
 */
ve.dm.MWImageModel.static.newFromImageNode = function ( node ) {
	return ve.dm.MWImageModel.static.newFromImageAttributes( node.getAttributes(), node.getDocument() );
};

/* Methods */

/**
 * Get the hash object of the current image model state.
 *
 * @return {Object}
 */
ve.dm.MWImageModel.prototype.getHashObject = function () {
	var hash = {
		filename: this.getFilename(),
		altText: this.getAltText(),
		type: this.getType(),
		alignment: this.getAlignment(),
		sizeType: this.getSizeType(),
		border: this.hasBorder(),
		borderable: this.isBorderable()
	};

	if ( this.getScalable() ) {
		hash.scalable = {
			currentDimensions: ve.copy( this.getScalable().getCurrentDimensions() ),
			isDefault: this.getScalable().isDefault()
		};
	}
	return hash;
};

/**
 * Normalize the source url by stripping the protocol off.
 * This is done so when an image is replaced with the same image,
 * the imageModel can recognize that nothing has actually changed.
 *
 * Example:
 * 'http://upload.wikimedia.org/wikipedia/commons/0/Foo.png'
 * to '//upload.wikimedia.org/wikipedia/commons/0/Foo.png'
 *
 * @return {string} Normalized image source
 */
ve.dm.MWImageModel.prototype.getNormalizedImageSource = function () {
	// Strip the url prefix 'http' / 'https' etc
	return this.getImageSource().replace( /^https?:\/\//, '//' );
};

/**
 * Adjust the model parameters based on a new image
 *
 * @param {Object} attrs New image source attributes
 * @param {Object} [APIinfo] The image's API info
 * @throws {Error} Image has insufficient details to compute the imageModel details.
 */
ve.dm.MWImageModel.prototype.changeImageSource = function ( attrs, APIinfo ) {
	var imageModel = this;

	this.changedImageSource = true;

	if ( attrs.mediaType ) {
		this.setMediaType( attrs.mediaType );
	}
	if ( attrs.href ) {
		this.setImageHref( attrs.href );
	}

	// FIXME: Account for falsey but present values
	if ( attrs.imageClassAttr ) {
		this.setImageClassAttr( attrs.imageClassAttr );
	}

	// FIXME: Account for falsey but present values
	if ( attrs.imgWrapperClassAttr ) {
		this.setImgWrapperClassAttr( attrs.imgWrapperClassAttr );
	}

	if ( attrs.resource ) {
		this.setImageResourceName( attrs.resource );
		this.setFilename( new mw.Title( mw.libs.ve.normalizeParsoidResourceName( attrs.resource ) ).getMainText() );
	}

	if ( attrs.src ) {
		this.setImageSource( attrs.src );
	}

	// Remove the scalable default and original dimensions
	this.scalable.clearOriginalDimensions();
	this.scalable.clearDefaultDimensions();
	this.scalable.clearMaxDimensions();
	this.scalable.clearMinDimensions();
	// This is a different image so clear the attributes cache
	delete this.attributesCache.originalWidth;
	delete this.attributesCache.originalHeight;

	// If we already have dimensions from the API, use them
	if ( APIinfo ) {
		this.scalable.setOriginalDimensions( {
			width: APIinfo.width,
			height: APIinfo.height
		} );
		// Update media type
		this.setMediaType( APIinfo.mediatype );
		// Update defaults
		ve.dm.MWImageNode.static.syncScalableToType(
			this.getType(),
			APIinfo.mediatype,
			this.scalable
		);
		this.updateScalableDetails( {
			width: APIinfo.width,
			height: APIinfo.height
		} );
	} else {
		// Call for updated scalable if we don't have dimensions from the API info
		if ( this.getFilename() ) {
			// Update anyway
			ve.dm.MWImageNode.static.getScalablePromise( this.getFilename() ).done( function ( info ) {
				imageModel.scalable.setOriginalDimensions( {
					width: info.width,
					height: info.height
				} );
				// Update media type
				imageModel.setMediaType( info.mediatype );
				// Update defaults
				ve.dm.MWImageNode.static.syncScalableToType(
					imageModel.getType(),
					info.mediatype,
					imageModel.scalable
				);
				imageModel.updateScalableDetails( {
					width: info.width,
					height: info.height
				} );
			} );
		} else {
			throw new Error( 'Cannot compute details for an image without remote filename and without sizing info.' );
		}
	}
};

/**
 * Get the current image node type according to the attributes.
 * If either of the parameters are given, the node type is tested
 * against them, otherwise, it is tested against the current image
 * parameters.
 *
 * @param {string} [imageType] Optional. Image type.
 * @param {string} [align] Optional. Image alignment.
 * @return {string} Node type 'mwInlineImage' or 'mwBlockImage'
 */
ve.dm.MWImageModel.prototype.getImageNodeType = function ( imageType, align ) {
	imageType = imageType || this.getType();

	if (
		( this.getType() === 'frameless' || this.getType() === 'none' ) &&
		( !this.isAligned( align ) || this.isDefaultAligned( imageType, align ) )
	) {
		return 'mwInlineImage';
	} else {
		return 'mwBlockImage';
	}
};

/**
 * Get the original bounding box
 *
 * @return {Object} Bounding box with width and height
 */
ve.dm.MWImageModel.prototype.getBoundingBox = function () {
	return this.boundingBox;
};

/**
 * Update an existing image node by changing its attributes
 *
 * @param {ve.dm.MWImageNode} node Image node to update
 * @param {ve.dm.Surface} surfaceModel Surface model of main document
 */
ve.dm.MWImageModel.prototype.updateImageNode = function ( node, surfaceModel ) {
	var doc = surfaceModel.getDocument();

	// Update the caption
	if ( node.getType() === 'mwBlockImage' ) {
		var captionNode = node.getCaptionNode();
		if ( !captionNode ) {
			// There was no caption before, so insert one now
			surfaceModel.getFragment()
				.adjustLinearSelection( 1 )
				.collapseToStart()
				.insertContent( [ { type: 'mwImageCaption' }, { type: '/mwImageCaption' } ] );
			// Update the caption node
			captionNode = node.getCaptionNode();
		}

		var captionRange = captionNode.getRange();

		// Remove contents of old caption
		surfaceModel.change(
			ve.dm.TransactionBuilder.static.newFromRemoval(
				doc,
				captionRange,
				true
			)
		);

		// Add contents of new caption
		surfaceModel.change(
			ve.dm.TransactionBuilder.static.newFromDocumentInsertion(
				doc,
				captionRange.start,
				this.getCaptionDocument()
			)
		);
	}

	// Update attributes
	surfaceModel.change(
		ve.dm.TransactionBuilder.static.newFromAttributeChanges(
			doc,
			node.getOffset(),
			this.getUpdatedAttributes()
		)
	);
};

/**
 * Insert image into a surface.
 *
 * Image is inserted at the current fragment position.
 *
 * @param {ve.dm.SurfaceFragment} fragment Fragment covering range to insert at
 * @return {ve.dm.SurfaceFragment} Fragment covering inserted image
 * @throws {Error} Unknown image node type
 */
ve.dm.MWImageModel.prototype.insertImageNode = function ( fragment ) {
	var nodeType = this.getImageNodeType(),
		surfaceModel = fragment.getSurface();

	if ( !( fragment.getSelection() instanceof ve.dm.LinearSelection ) ) {
		return fragment;
	}

	var selectedNode = fragment.getSelectedNode();

	// If there was a previous node, remove it first
	if ( selectedNode ) {
		// Remove the old image
		fragment.removeContent();
	}

	var contentToInsert = this.getData();

	var offset;
	switch ( nodeType ) {
		case 'mwInlineImage':
			if ( selectedNode && selectedNode.type === 'mwBlockImage' ) {
				// If converting from a block image, create a wrapper paragraph for the inline image to go in.
				fragment.insertContent( [ { type: 'paragraph', internal: { generated: 'wrapper' } }, { type: '/paragraph' } ] );
				offset = fragment.getSelection().getRange().start + 1;
			} else {
				// Try to put the image inside the nearest content node
				offset = fragment.getDocument().data.getNearestContentOffset( fragment.getSelection().getRange().start );
			}
			if ( offset > -1 ) {
				fragment = fragment.clone( new ve.dm.LinearSelection( new ve.Range( offset ) ) );
			}
			fragment.insertContent( contentToInsert );
			return fragment;

		case 'mwBlockImage':
			// Try to put the image in front of the structural node
			offset = fragment.getDocument().data.getNearestStructuralOffset( fragment.getSelection().getRange().start, -1 );
			if ( offset > -1 ) {
				fragment = fragment.clone( new ve.dm.LinearSelection( new ve.Range( offset ) ) );
			}
			fragment.insertContent( contentToInsert );
			// Add contents of new caption
			surfaceModel.change(
				ve.dm.TransactionBuilder.static.newFromDocumentInsertion(
					surfaceModel.getDocument(),
					fragment.getSelection().getRange().start + 2,
					this.getCaptionDocument()
				)
			);
			return fragment;

		default:
			throw new Error( 'Unknown image node type ' + nodeType );
	}
};

/**
 * Get linear data representation of the image
 *
 * @return {Array} Linear data
 */
ve.dm.MWImageModel.prototype.getData = function () {
	var originalAttrs = ve.copy( this.getOriginalImageAttributes() ),
		editAttributes = ve.extendObject( originalAttrs, this.getUpdatedAttributes() ),
		nodeType = this.getImageNodeType();

	// Remove old classes
	delete editAttributes.originalClasses;
	delete editAttributes.unrecognizedClasses;
	// Newly created images must have valid URLs, so remove the error attribute
	if ( this.isChangedImageSource() ) {
		delete editAttributes.isError;
	}

	var data = [
		{
			type: nodeType,
			attributes: editAttributes
		},
		{ type: '/' + nodeType }
	];

	if ( nodeType === 'mwBlockImage' ) {
		data.splice( 1, 0, { type: 'mwImageCaption' }, { type: '/mwImageCaption' } );
	}
	return data;
};

/**
 * Return all updated attributes that belong to the node.
 *
 * @return {Object} Updated attributes
 */
ve.dm.MWImageModel.prototype.getUpdatedAttributes = function () {
	var origAttrs = this.getOriginalImageAttributes();

	var currentDimensions;
	// Adjust default dimensions if size is set to default
	if ( this.scalable.isDefault() && this.scalable.getDefaultDimensions() ) {
		currentDimensions = this.scalable.getDefaultDimensions();
	} else {
		currentDimensions = this.getCurrentDimensions();
	}

	var attrs = {
		mediaClass: 'File',
		mediaTag: this.getMediaTag(),
		type: this.getType(),
		width: currentDimensions.width,
		height: currentDimensions.height,
		defaultSize: this.isDefaultSize(),
		borderImage: this.hasBorder()
	};

	if ( this.getAltText() || typeof origAttrs.alt === 'string' ) {
		attrs.alt = this.getAltText();
	}

	if ( this.isDefaultAligned() ) {
		attrs.align = 'default';
	} else if ( !this.isAligned() ) {
		attrs.align = 'none';
	} else {
		attrs.align = this.getAlignment();
	}

	attrs.src = this.getImageSource();
	attrs.href = this.getImageHref();
	attrs.imageClassAttr = this.getImageClassAttr();
	attrs.imgWrapperClassAttr = this.getImgWrapperClassAttr();
	attrs.resource = this.getImageResourceName();

	return attrs;
};

/**
 * Deal with default change on the scalable object
 *
 * @param {boolean} isDefault
 */
ve.dm.MWImageModel.prototype.onScalableDefaultSizeChange = function ( isDefault ) {
	this.toggleDefaultSize( isDefault );
};

/**
 * Set the image file source
 *
 * @param {string} src The source of the given media file
 */
ve.dm.MWImageModel.prototype.setImageSource = function ( src ) {
	this.imageSrc = src;
};

/**
 * Set the image file resource name
 *
 * @param {string} resourceName The resource name of the given image file
 */
ve.dm.MWImageModel.prototype.setImageResourceName = function ( resourceName ) {
	this.imageResourceName = resourceName;
};

/**
 * Set the image href value
 *
 * @param {string} href The destination href of the given media file
 */
ve.dm.MWImageModel.prototype.setImageHref = function ( href ) {
	this.imageHref = href;
};

/**
 * Set the original bounding box
 *
 * @param {Object} box Bounding box with width and height
 */
ve.dm.MWImageModel.prototype.setBoundingBox = function ( box ) {
	this.boundingBox = box;
};

/**
 * Set the initial hash object of the image to be compared to when
 * checking if the model is modified.
 *
 * @param {Object} hash The initial hash object
 */
ve.dm.MWImageModel.prototype.storeInitialHash = function ( hash ) {
	this.initialHash = hash;
};

/**
 * Set symbolic name of media type.
 *
 * Example values: "BITMAP" for JPEG or PNG images; "DRAWING" for SVG graphics
 *
 * @param {string|undefined} type Symbolic media type name, or undefined if empty
 */
ve.dm.MWImageModel.prototype.setMediaType = function ( type ) {
	this.mediaType = type;
};

/**
 * Check whether the image is set to default size
 *
 * @return {boolean} Default size flag on or off
 */
ve.dm.MWImageModel.prototype.isDefaultSize = function () {
	// An image with 'frame' always ignores the size specification
	return this.scalable.isDefault() || this.getType() === 'frame';
};

/**
 * Check whether the image has the border flag set
 *
 * @return {boolean} Border flag on or off
 */
ve.dm.MWImageModel.prototype.hasBorder = function () {
	return this.border;
};

/**
 * Check whether the image source is changed
 *
 * @return {boolean} changedImageSource flag on or off
 */
ve.dm.MWImageModel.prototype.isChangedImageSource = function () {
	return this.changedImageSource;
};

/**
 * Check whether the image has floating alignment set
 *
 * @param {string} [align] Optional. Alignment value to test against.
 * @return {boolean} hasAlignment flag on or off
 */
ve.dm.MWImageModel.prototype.isAligned = function ( align ) {
	align = align || this.alignment;
	// The image is aligned if it has alignment (not undefined and not null)
	// and if its alignment is not 'none'.
	// Inline images initially have null alignment value (and are not aligned)
	return align && align !== 'none';
};

/**
 * Check whether the image is set to default alignment
 * We explicitly repeat tests so to avoid recursively calling
 * the other methods.
 *
 * @param {string} [imageType] Type of the image.
 * @param {string} [align] Optional alignment value to test against.
 * Supplying this parameter would test whether this align parameter
 * would mean the image is aligned to its default position.
 * @return {boolean} defaultAlignment flag on or off
 */
ve.dm.MWImageModel.prototype.isDefaultAligned = function ( imageType, align ) {
	var alignment = align || this.getAlignment(),
		defaultAlignment = ( this.parentDoc.getDir() === 'rtl' ) ? 'left' : 'right';

	imageType = imageType || this.getType();
	// No alignment specified means default alignment always
	// Inline images have no align attribute; during the initialization
	// stage of the model we have to account for that option. Later the
	// model creates a faux alignment for inline images ('none' for default)
	// but if initially the alignment is null or undefined, it means the image
	// is inline without explicit alignment (which makes it default aligned)
	if ( !alignment ) {
		return true;
	}

	if (
		(
			( imageType === 'frameless' || imageType === 'none' ) &&
			alignment === 'none'
		) ||
		(
			( imageType === 'thumb' || imageType === 'frame' ) &&
			alignment === defaultAlignment
		)
	) {
		return true;
	}

	return false;
};

/**
 * Check whether the image can have a border set on it
 *
 * @return {boolean} Border possible or not
 */
ve.dm.MWImageModel.prototype.isBorderable = function () {
	return this.borderable;
};

/**
 * Get the image file resource name
 *
 * @return {string} resourceName The resource name of the given media file
 */
ve.dm.MWImageModel.prototype.getResourceName = function () {
	return this.imageResourceName;
};

/**
 * Get the image alternate text
 *
 * @return {string} Alternate text
 */
ve.dm.MWImageModel.prototype.getAltText = function () {
	return this.altText || '';
};

/**
 * Get image wikitext type; 'thumb', 'frame', 'frameless' or 'none/inline'
 *
 * @return {string} Image type
 */
ve.dm.MWImageModel.prototype.getType = function () {
	return this.type;
};

/**
 * Get the image size type of the image
 *
 * @return {string} Size type
 */
ve.dm.MWImageModel.prototype.getSizeType = function () {
	return this.sizeType;
};

/**
 * Get symbolic name of media type.
 *
 * Example values: "BITMAP" for JPEG or PNG images; "DRAWING" for SVG graphics
 *
 * @return {string|undefined} Symbolic media type name, or undefined if empty
 */
ve.dm.MWImageModel.prototype.getMediaType = function () {
	return this.mediaType;
};

/**
 * Get media tag: img, video or audio
 *
 * @return {string} Tag name
 */
ve.dm.MWImageModel.prototype.getMediaTag = function () {
	var mediaType = this.getMediaType();

	if ( mediaType === 'VIDEO' ) {
		return 'video';
	}
	if ( mediaType === 'AUDIO' ) {
		return 'audio';
	}
	return 'img';
};

/**
 * Get image alignment 'left', 'right', 'center', 'none' or 'default'
 *
 * @return {string|null} Image alignment. Inline images have initial alignment
 * value of null.
 */
ve.dm.MWImageModel.prototype.getAlignment = function () {
	return this.alignment;
};

/**
 * Get image vertical alignment
 * 'middle', 'baseline', 'sub', 'super', 'top', 'text-top', 'bottom', 'text-bottom' or 'default'
 *
 * @return {string} Image alignment
 */
ve.dm.MWImageModel.prototype.getVerticalAlignment = function () {
	return this.verticalAlignment;
};

/**
 * Get the scalable object responsible for size manipulations
 * for the given image
 *
 * @return {ve.dm.Scalable}
 */
ve.dm.MWImageModel.prototype.getScalable = function () {
	return this.scalable;
};

/**
 * Get the image current dimensions
 *
 * @return {Object} Current dimensions width/height
 * @return {number} dimensions.width The width of the image
 * @return {number} dimensions.height The height of the image
 */
ve.dm.MWImageModel.prototype.getCurrentDimensions = function () {
	return this.scalable.getCurrentDimensions();
};

/**
 * Get image caption document.
 *
 * Auto-generates a blank document if no document exists.
 *
 * @return {ve.dm.Document} Caption document
 */
ve.dm.MWImageModel.prototype.getCaptionDocument = function () {
	if ( !this.captionDoc ) {
		this.captionDoc = this.parentDoc.cloneWithData( [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		] );
	}
	return this.captionDoc;
};

/**
 * Toggle the option of whether this image can or cannot have
 * a border set on it.
 *
 * @param {boolean} [borderable] Set or unset borderable. If not
 *  specified, the current state is toggled.
 */
ve.dm.MWImageModel.prototype.toggleBorderable = function ( borderable ) {
	borderable = borderable !== undefined ? !!borderable : !this.isBorderable();

	this.borderable = borderable;
};

/**
 * Toggle the border flag of the image
 *
 * @param {boolean} [hasBorder] Border flag. Omit to toggle current value.
 */
ve.dm.MWImageModel.prototype.toggleBorder = function ( hasBorder ) {
	hasBorder = hasBorder !== undefined ? !!hasBorder : !this.hasBorder();

	this.border = !!hasBorder;
};

/**
 * Toggle the default size flag of the image
 *
 * @param {boolean} [isDefault] Default size flag. Omit to toggle current value.
 * @fires sizeDefaultChange
 */
ve.dm.MWImageModel.prototype.toggleDefaultSize = function ( isDefault ) {
	isDefault = isDefault !== undefined ? !!isDefault : !this.isDefaultSize();

	if ( this.isDefaultSize() !== isDefault ) {
		this.scalable.toggleDefault( !!isDefault );
		this.resetDefaultDimensions();
		this.emit( 'sizeDefaultChange', !!isDefault );
	}
};

/**
 * Cache all image attributes
 *
 * @param {Object} attrs Image attributes
 */
ve.dm.MWImageModel.prototype.cacheOriginalImageAttributes = function ( attrs ) {
	this.attributesCache = attrs;
};

/**
 * Get the cache of all image attributes
 *
 * @return {Object} attrs Image attributes
 */
ve.dm.MWImageModel.prototype.getOriginalImageAttributes = function () {
	return this.attributesCache;
};

/**
 * Set the current dimensions of the image.
 * Normalize in case only one dimension is available.
 *
 * @param {Object} dimensions Dimensions width and height
 * @param {number} dimensions.width The width of the image
 * @param {number} dimensions.height The height of the image
 */
ve.dm.MWImageModel.prototype.setCurrentDimensions = function ( dimensions ) {
	var normalizedDimensions = ve.dm.Scalable.static.getDimensionsFromValue( dimensions, this.scalable.getRatio() );
	this.scalable.setCurrentDimensions( normalizedDimensions );
};

/**
 * Set alternate text
 *
 * @param {string} text Alternate text
 */
ve.dm.MWImageModel.prototype.setAltText = function ( text ) {
	this.altText = text;
};

/**
 * Set image type
 *
 * @see #getType
 *
 * @param {string} type Image type
 * @fires typeChange
 */
ve.dm.MWImageModel.prototype.setType = function ( type ) {
	var isDefaultAligned = this.isDefaultAligned( this.imageCurrentType );

	this.type = type;

	// If we're switching between inline and block or vice versa,
	// check if the old type image was default aligned
	if ( isDefaultAligned && this.imageCurrentType !== this.type ) {
		if ( this.type === 'none' || this.type === 'frameless' ) {
			// Reset default alignment for switching to inline images
			this.setAlignment( 'none' );
		} else {
			// Reset default alignment for all other images
			this.setAlignment( 'default' );
		}
	}

	// Cache the current type for next check
	this.imageCurrentType = type;

	if ( type === 'frame' || type === 'thumb' ) {
		// Disable border option
		this.toggleBorderable( false );
	} else {
		// Enable border option
		this.toggleBorderable( true );
	}

	// If type is frame, set to 'default' size
	if ( type === 'frame' ) {
		this.toggleDefaultSize( true );
	}

	// Let the image node update scalable considerations
	// for default and max dimensions as per the new type.
	ve.dm.MWImageNode.static.syncScalableToType( type, this.getMediaType(), this.getScalable() );

	this.emit( 'typeChange', type );
};

/**
 * Reset the default dimensions of the image based on its type
 * and on whether we have the originalDimensions object from
 * the API
 */
ve.dm.MWImageModel.prototype.resetDefaultDimensions = function () {
	var originalDimensions = this.scalable.getOriginalDimensions();

	if ( !ve.isEmptyObject( originalDimensions ) ) {
		if ( this.getType() === 'thumb' || this.getType() === 'frameless' ) {
			// Default is thumb size
			if ( originalDimensions.width <= this.defaultThumbSize ) {
				this.scalable.setDefaultDimensions( originalDimensions );
			} else {
				this.scalable.setDefaultDimensions(
					ve.dm.Scalable.static.getDimensionsFromValue( {
						width: this.defaultThumbSize
					}, this.scalable.getRatio() )
				);
			}
		} else {
			// Default is original size
			this.scalable.setDefaultDimensions( originalDimensions );
		}
	} else {
		this.scalable.clearDefaultDimensions();
	}
};

/**
 * Retrieve the currently set default dimensions from the scalable
 * object attached to the image.
 *
 * @return {Object} Image default dimensions
 */
ve.dm.MWImageModel.prototype.getDefaultDimensions = function () {
	return this.scalable.getDefaultDimensions();
};

/**
 * Change size type of the image
 *
 * @param {string} type Size type 'default', 'custom' or 'scale'
 */
ve.dm.MWImageModel.prototype.setSizeType = function ( type ) {
	if ( this.sizeType !== type ) {
		this.sizeType = type;
		this.toggleDefaultSize( type === 'default' );
	}
};

/**
 * Set image alignment
 *
 * @see #getAlignment
 *
 * @param {string} align Alignment
 * @fires alignmentChange
 */
ve.dm.MWImageModel.prototype.setAlignment = function ( align ) {
	if ( align === 'default' ) {
		// If default, set the alignment to language dir default
		align = this.getDefaultDir();
	}

	this.alignment = align;
	this.emit( 'alignmentChange', align );
};

/**
 * Set image vertical alignment
 *
 * @see #getVerticalAlignment
 *
 * @param {string} valign Alignment
 * @fires alignmentChange
 */
ve.dm.MWImageModel.prototype.setVerticalAlignment = function ( valign ) {
	this.verticalAlignment = valign;
	this.emit( 'alignmentChange', valign );
};

/**
 * Get the default alignment according to the document direction
 *
 * @param {string} [imageNodeType] Optional. The image node type that we would
 * like to get the default direction for. Supplying this parameter allows us
 * to check what the default alignment of a specific type of node would be.
 * If the parameter is not supplied, the default alignment will be calculated
 * based on the current node type.
 * @return {string} Node alignment based on document direction
 */
ve.dm.MWImageModel.prototype.getDefaultDir = function ( imageNodeType ) {
	imageNodeType = imageNodeType || this.getImageNodeType();

	if ( this.parentDoc.getDir() === 'rtl' ) {
		// Assume position is 'left'
		return ( imageNodeType === 'mwBlockImage' ) ? 'left' : 'none';
	} else {
		// Assume position is 'right'
		return ( imageNodeType === 'mwBlockImage' ) ? 'right' : 'none';
	}
};

/**
 * Get the image file source
 * The image file source that points to the location of the
 * file on the Web.
 * For instance, '//upload.wikimedia.org/wikipedia/commons/0/0f/Foo.jpg'
 *
 * @return {string} The source of the given media file
 */
ve.dm.MWImageModel.prototype.getImageSource = function () {
	return this.imageSrc;
};

/**
 * Get the image file resource name.
 * The resource name represents the filename without the full
 * source url.
 * For example, './File:Foo.jpg'
 *
 * @return {string} The resource name of the given media file
 */
ve.dm.MWImageModel.prototype.getImageResourceName = function () {
	return this.imageResourceName;
};

/**
 * Get the image href value.
 * This is the link that the image leads to. It usually contains
 * the link to the source of the image in commons or locally, but
 * may hold an alternative link if link= is supplied in the wikitext.
 * For example, './File:Foo.jpg' or 'http://www.wikipedia.org'
 *
 * @return {string} The destination href of the given media file
 */
ve.dm.MWImageModel.prototype.getImageHref = function () {
	return this.imageHref;
};

/**
 * @param {string|null} classAttr
 */
ve.dm.MWImageModel.prototype.setImageClassAttr = function ( classAttr ) {
	this.imageClassAttr = classAttr;
};

/**
 * @return {string|null}
 */
ve.dm.MWImageModel.prototype.getImageClassAttr = function () {
	return this.imageClassAttr;
};

/**
 * @param {string|null} classAttr
 */
ve.dm.MWImageModel.prototype.setImgWrapperClassAttr = function ( classAttr ) {
	this.imgWrapperClassAttr = classAttr;
};

/**
 * @return {string|null}
 */
ve.dm.MWImageModel.prototype.getImgWrapperClassAttr = function () {
	return this.imgWrapperClassAttr;
};

/**
 * Attach a new scalable object to the model and request the
 * information from the API.
 *
 * @param {ve.dm.Scalable} scalable
 */
ve.dm.MWImageModel.prototype.attachScalable = function ( scalable ) {
	var imageName = mw.libs.ve.normalizeParsoidResourceName( this.getResourceName() ),
		imageModel = this;

	if ( this.scalable instanceof ve.dm.Scalable ) {
		this.scalable.disconnect( this );
	}
	this.scalable = scalable;

	// Events
	this.scalable.connect( this, { defaultSizeChange: 'onScalableDefaultSizeChange' } );

	// Call for updated scalable
	if ( imageName ) {
		ve.dm.MWImageNode.static.getScalablePromise( imageName ).done( function ( info ) {
			imageModel.scalable.setOriginalDimensions( {
				width: info.width,
				height: info.height
			} );
			// Update media type
			imageModel.setMediaType( info.mediatype );
			// Update according to type
			ve.dm.MWImageNode.static.syncScalableToType(
				imageModel.getType(),
				imageModel.getMediaType(),
				imageModel.getScalable()
			);

			// We have to adjust the details in the initial hash if the original
			// image was 'default' since we didn't have default until now and the
			// default dimensions that were 'recorded' were wrong
			if ( !ve.isEmptyObject( imageModel.initialHash ) && imageModel.initialHash.scalable.isDefault ) {
				imageModel.initialHash.scalable.currentDimensions = imageModel.scalable.getDefaultDimensions();
			}

		} );
	}
};

/**
 * Set the filename of the current image
 *
 * @param {string} filename Image filename (without namespace)
 */
ve.dm.MWImageModel.prototype.setFilename = function ( filename ) {
	this.filename = filename;
};

/**
 * Get the filename of the current image
 *
 * @return {string} filename Image filename (without namespace)
 */
ve.dm.MWImageModel.prototype.getFilename = function () {
	return this.filename;
};

/**
 * If the image changed, update scalable definitions.
 *
 * @param {Object} originalDimensions Image original dimensions
 */
ve.dm.MWImageModel.prototype.updateScalableDetails = function ( originalDimensions ) {
	var newDimensions;

	// Resize the new image's current dimensions to default or based on the bounding box
	if ( this.isDefaultSize() ) {
		// Scale to default
		newDimensions = ve.dm.MWImageNode.static.scaleToThumbnailSize( originalDimensions );
	} else {
		if ( this.getBoundingBox() ) {
			// Scale the new image by its width
			newDimensions = ve.dm.MWImageNode.static.resizeToBoundingBox(
				originalDimensions,
				{
					width: this.boundingBox.width,
					height: Infinity
				}
			);
		} else {
			newDimensions = originalDimensions;
		}
	}

	if ( newDimensions ) {
		this.getScalable().setCurrentDimensions( newDimensions );
	}
};

/**
 * Set image caption document.
 *
 * @param {ve.dm.Document} doc Image caption document
 */
ve.dm.MWImageModel.prototype.setCaptionDocument = function ( doc ) {
	this.captionDoc = doc;
};

/**
 * Check if the model attributes and parameters have been modified by
 * comparing the current hash to the new hash object.
 *
 * @return {boolean} Model has been modified
 */
ve.dm.MWImageModel.prototype.hasBeenModified = function () {
	if ( this.initialHash ) {
		return !ve.compare( this.initialHash, this.getHashObject() );
	}
	return true;
};
