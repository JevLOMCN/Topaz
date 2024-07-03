/*!
 * VisualEditor ContentEditable ImageNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable image node.
 *
 * @class
 * @abstract
 * @mixins ve.ce.FocusableNode
 * @mixins ve.ce.ResizableNode
 *
 * @constructor
 * @param {jQuery} $figure Image or figure element
 * @param {jQuery} [$image] Actual image element, if $figure is just a container
 * @param {Object} [config] Configuration options
 */
ve.ce.ImageNode = function VeCeImageNode( $figure, $image, config ) {
	config = ve.extendObject( {
		enforceMax: false,
		minDimensions: { width: 1, height: 1 },
		$bounding: this.$element
	}, config );

	this.$figure = $figure;
	this.$image = $image || $figure;

	// Mixin constructors
	ve.ce.FocusableNode.call( this, this.$figure, config );
	ve.ce.ResizableNode.call( this, this.$image, config );

	// Events
	this.$image.on( 'load', this.onLoad.bind( this ) );
	this.model.connect( this, { attributeChange: 'onAttributeChange' } );

	// Initialization
	this.$element.addClass( 've-ce-imageNode' );
};

/* Inheritance */

OO.mixinClass( ve.ce.ImageNode, ve.ce.FocusableNode );

OO.mixinClass( ve.ce.ImageNode, ve.ce.ResizableNode );

/* Static Methods */

/**
 * @inheritdoc ve.ce.Node
 */
ve.ce.ImageNode.static.getDescription = function ( model ) {
	return model.getAttribute( 'src' );
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
ve.ce.ImageNode.prototype.onAttributeChange = function ( key, from, to ) {
	switch ( key ) {
		case 'src':
			this.$image.prop( 'src', this.getResolvedAttribute( 'src' ) );
			break;

		case 'width':
		case 'height':
			this.$image.css( key, to !== null ? to : '' );
			break;
	}
};

/**
 * Handle the image load
 *
 * @param {jQuery.Event} e Load event
 */
ve.ce.ImageNode.prototype.onLoad = function () {
	if ( !this.model ) {
		// This node has probably been destroyed. (Currently there's no easy way for
		// a mixin class to disconnect listeners on destroy)
		return;
	}
	this.setOriginalDimensions( {
		width: this.$image.prop( 'naturalWidth' ),
		height: this.$image.prop( 'naturalHeight' )
	} );
};
