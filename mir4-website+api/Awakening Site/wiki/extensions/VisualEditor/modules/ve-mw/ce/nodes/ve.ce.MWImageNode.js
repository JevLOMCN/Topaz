/*!
 * VisualEditor ContentEditable MWImageNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki image node.
 *
 * @class
 * @abstract
 * @extends ve.ce.GeneratedContentNode
 * @mixins ve.ce.FocusableNode
 * @mixins ve.ce.MWResizableNode
 *
 * @constructor
 * @param {jQuery} $focusable Focusable part of the node
 * @param {jQuery} $image Image part of the node
 * @param {Object} [config] Configuration options
 */
ve.ce.MWImageNode = function VeCeMWImageNode( $focusable, $image, config ) {
	config = ve.extendObject( {
		enforceMax: false,
		minDimensions: { width: 1, height: 1 },
		$bounding: this.$element
	}, config );

	// Properties
	this.$image = $image;
	// Parent constructor triggers render so this must precede it
	this.renderedDimensions = null;

	// Parent constructor
	ve.ce.GeneratedContentNode.call( this );

	// Mixin constructors
	ve.ce.FocusableNode.call( this, $focusable, config );
	ve.ce.MWResizableNode.call( this, this.$image, config );

	// Events
	this.model.connect( this, { attributeChange: 'onAttributeChange' } );

	// Initialization
	this.updateMediaType();
};

/* Inheritance */

OO.inheritClass( ve.ce.MWImageNode, ve.ce.GeneratedContentNode );

OO.mixinClass( ve.ce.MWImageNode, ve.ce.FocusableNode );

// Need to mixin base class as well (T92540)
OO.mixinClass( ve.ce.MWImageNode, ve.ce.ResizableNode );

OO.mixinClass( ve.ce.MWImageNode, ve.ce.MWResizableNode );

/* Static Properties */

ve.ce.MWImageNode.static.primaryCommandName = 'media';

/* Static Methods */

/**
 * @inheritdoc ve.ce.Node
 */
ve.ce.MWImageNode.static.getDescription = function ( model ) {
	var title = new mw.Title( model.getFilename() );
	return title.getMainText();
};

/* Methods */

/**
 * Update the rendering of the 'align', src', 'width' and 'height' attributes
 * when they change in the model.
 *
 * @param {string} key Attribute key
 * @param {string} from Old value
 * @param {string} to New value
 */
ve.ce.MWImageNode.prototype.onAttributeChange = function () {
	this.update();
};

/**
 * @inheritdoc ve.ce.GeneratedContentNode
 */
ve.ce.MWImageNode.prototype.onGeneratedContentNodeUpdate = function () {
	// Do nothing to avoid re-rendering every time the caption is changed.
	// Call update inside onAttributeChange instead.
};

/**
 * @inheritdoc ve.ce.GeneratedContentNode
 */
ve.ce.MWImageNode.prototype.generateContents = function () {
	var model = this.getModel(),
		width = model.getAttribute( 'width' ),
		height = model.getAttribute( 'height' ),
		mwData = model.getAttribute( 'mw' ) || {},
		deferred = ve.createDeferred();

	// If the current rendering is larger don't fetch a new image, just let the browser resize
	if ( this.renderedDimensions && this.renderedDimensions.width > width ) {
		return deferred.reject().promise();
	}

	var params;
	if ( mwData.thumbtime !== undefined ) {
		params = 'seek=' + mwData.thumbtime;
	} else if ( mwData.page !== undefined ) {
		params = 'page' + mwData.page + '-' + width + 'px';
		// Don't send width twice
		width = undefined;
	}

	var xhr = ve.init.target.getContentApi( this.getModel().getDocument() ).get( {
		action: 'query',
		prop: 'imageinfo',
		iiprop: 'url',
		iiurlwidth: width,
		iiurlheight: height,
		iiurlparam: params,
		titles: this.getModel().getFilename()
	} )
		.done( this.onParseSuccess.bind( this, deferred ) )
		.fail( this.onParseError.bind( this, deferred ) );

	return deferred.promise( { abort: xhr.abort } );
};

/**
 * Handle a successful response from the parser for the image src.
 *
 * @param {jQuery.Deferred} deferred The Deferred object created by generateContents
 * @param {Object} response Response data
 */
ve.ce.MWImageNode.prototype.onParseSuccess = function ( deferred, response ) {
	var thumburl = ve.getProp( response.query.pages[ 0 ], 'imageinfo', 0, 'thumburl' );
	if ( thumburl ) {
		deferred.resolve( thumburl );
	} else {
		deferred.reject();
	}
};

/**
 * @inheritdoc ve.ce.GeneratedContentNode
 */
ve.ce.MWImageNode.prototype.render = function ( generatedContents ) {
	this.$image.attr( 'src', generatedContents );
	// As we only re-render when the image is larger than last rendered size
	// this will always be the largest ever rendering
	this.renderedDimensions = ve.copy( this.model.getScalable().getCurrentDimensions() );
	if ( this.live ) {
		this.afterRender();
	}
};

/**
 * Handle an unsuccessful response from the parser for the image src.
 *
 * @param {jQuery.Deferred} deferred The promise object created by generateContents
 * @param {Object} response Response data
 */
ve.ce.MWImageNode.prototype.onParseError = function ( deferred ) {
	deferred.reject();
};

/**
 * Update rendering when media type changes
 */
ve.ce.MWImageNode.prototype.updateMediaType = function () {
	this.$image.toggleClass( 've-ce-mwImageNode-audioPlayer', this.model.getMediaType() === 'AUDIO' );
};
