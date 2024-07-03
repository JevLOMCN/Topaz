/*!
 * VisualEditor MWMediaContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a MWImageNode.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @constructor
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWMediaContextItem = function VeUiMWMediaContextItem( context, model ) {
	// Parent constructor
	ve.ui.MWMediaContextItem.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwMediaContextItem' );

	var mediaTag = model.getAttribute( 'mediaTag' ) || 'img';

	this.setIcon( {
		img: 'image',
		span: 'imageBroken',
		// TODO: Better icons for audio/video
		audio: 'play',
		video: 'play'
	}[ mediaTag ] );

	var messagePostfix = ( mediaTag === 'audio' || mediaTag === 'video' ) ? mediaTag : 'image';

	// The following messages are used here:
	// * visualeditor-media-title-audio
	// * visualeditor-media-title-image
	// * visualeditor-media-title-video
	this.setLabel( ve.msg( 'visualeditor-media-title-' + messagePostfix ) );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMediaContextItem, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.MWMediaContextItem.static.name = 'mwMedia';

ve.ui.MWMediaContextItem.static.icon = 'image';

ve.ui.MWMediaContextItem.static.label =
	OO.ui.deferMsg( 'visualeditor-media-title-image' );

ve.ui.MWMediaContextItem.static.modelClasses = [ ve.dm.MWBlockImageNode, ve.dm.MWInlineImageNode ];

ve.ui.MWMediaContextItem.static.commandName = 'media';

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWMediaContextItem.prototype.getDescription = function () {
	return ve.ce.nodeFactory.getDescription( this.model );
};

/**
 * @inheritdoc
 */
ve.ui.MWMediaContextItem.prototype.renderBody = function () {
	var title = mw.Title.newFromText( mw.libs.ve.normalizeParsoidResourceName( this.model.getAttribute( 'resource' ) ) );
	var $link = $( '<a>' )
		.text( this.getDescription() )
		.attr( {
			target: '_blank',
			rel: 'noopener'
		} );
	// T322704
	ve.setAttributeSafe( $link[ 0 ], 'href', title.getUrl(), '#' );

	this.$body.append( $link );
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWMediaContextItem );
