/*!
 * VisualEditor user interface MWMediaDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/* global moment */

/**
 * MWMediaInfoFieldWidget widget for displaying media information from the API.
 *
 * @class
 * @extends OO.ui.Widget
 * @mixins OO.ui.mixin.IconElement
 * @mixins OO.ui.mixin.TitledElement
 *
 * @constructor
 * @param {jQuery|string|OO.ui.HtmlSnippet} content API response data from which to build the display
 * @param {Object} [config] Configuration options
 * @cfg {string} [href] A url encapsulating the field text. If a label is attached it will include the label.
 * @cfg {string} [labelMsg] A ve.msg() label string for the field.
 * @cfg {boolean} [isDate=false] Field text is a date that will be converted to 'fromNow' string.
 * @cfg {string} [type='attribute'] Field type, either 'description' or 'attribute'
 * @cfg {string} [descriptionHeight='4em'] Height limit for description fields
 */
ve.ui.MWMediaInfoFieldWidget = function VeUiMWMediaInfoFieldWidget( content, config ) {
	// Configuration initialization
	config = config || {};

	// Parent constructor
	ve.ui.MWMediaInfoFieldWidget.super.call( this, config );

	// Mixin constructors
	OO.ui.mixin.IconElement.call( this, config );
	OO.ui.mixin.LabelElement.call( this, ve.extendObject( { $label: $( '<div>' ) }, config ) );

	this.$text = $( '<div>' )
		.addClass( 've-ui-mwMediaInfoFieldWidget-text' );
	this.type = config.type || 'attribute';

	// Initialization
	if ( typeof content === 'string' ) {
		var datetime;
		if ( config.isDate && ( datetime = moment( content ) ).isValid() ) {
			content = datetime.fromNow();
		}

		if ( config.labelMsg ) {
			// Messages defined in ve.ui.MWMediaDialog#buildMediaInfoPanel
			// eslint-disable-next-line mediawiki/msg-doc
			content = ve.msg( config.labelMsg, content );
		}

		if ( config.href ) {
			// This variable may contain either jQuery objects or strings
			// eslint-disable-next-line no-jquery/variable-pattern
			content = $( '<a>' )
				.attr( 'href',
					// For the cases where we get urls that are "local"
					// without http(s) prefix, we will add that prefix
					// ourselves
					!/^(https?:)?\/\//.test( config.href ) ?
						'//' + config.href :
						config.href
				)
				.text( content );
		}
	}

	if ( typeof content === 'string' ) {
		this.$text.text( content );
	} else if ( content instanceof OO.ui.HtmlSnippet ) {
		// eslint-disable-next-line no-jquery/no-html
		this.$text.html( content.toString() );
	} else if ( content instanceof $ ) {
		// eslint-disable-next-line no-jquery/no-append-html
		this.$text.append( content );
	} else {
		throw new Error( 'Unexpected metadata field content' );
	}

	this.$element
		.append( this.$icon, this.$label )
		.addClass( 've-ui-mwMediaInfoFieldWidget' )
		// The following classes are used here:
		// * ve-ui-mwMediaInfoFieldWidget-description
		// * ve-ui-mwMediaInfoFieldWidget-attribute
		.addClass( 've-ui-mwMediaInfoFieldWidget-' + this.type );
	this.$icon.addClass( 've-ui-mwMediaInfoFieldWidget-icon' );

	if ( this.type === 'description' ) {
		// Limit height
		this.readMoreButton = new OO.ui.ButtonWidget( {
			framed: false,
			icon: 'expand',
			label: ve.msg( 'visualeditor-dialog-media-info-readmore' ),
			classes: [ 've-ui-mwMediaInfoFieldWidget-readmore' ]
		} );
		this.readMoreButton.toggle( false );
		this.readMoreButton.connect( this, { click: 'onReadMoreClick' } );

		this.$text
			.css( 'maxHeight', config.descriptionHeight || '4em' );

		this.$element
			.append( this.readMoreButton.$element );
	}

	this.setLabel( this.$text );
};

/* Setup */

OO.inheritClass( ve.ui.MWMediaInfoFieldWidget, OO.ui.Widget );
OO.mixinClass( ve.ui.MWMediaInfoFieldWidget, OO.ui.mixin.IconElement );
OO.mixinClass( ve.ui.MWMediaInfoFieldWidget, OO.ui.mixin.LabelElement );

/* Static Properties */

ve.ui.MWMediaInfoFieldWidget.static.tagName = 'div';

/**
 * Define a height threshold for the description fields.
 * If the rendered field's height is under the defined limit
 * (max-height + threshold) we should remove the max-height
 * and display the field as-is.
 * This prevents cases where "read more" appears but only
 * exposes only a few pixels or a line extra.
 *
 * @property {number} Threshold in pixels
 */
ve.ui.MWMediaInfoFieldWidget.static.threshold = 24;

/**
 * Toggle the read more button according to whether it should be
 * visible or not.
 */
ve.ui.MWMediaInfoFieldWidget.prototype.initialize = function () {
	if ( this.getType() === 'description' ) {
		var actualHeight = this.$text.prop( 'scrollHeight' );
		var containerHeight = this.$text.outerHeight( true );

		if ( actualHeight < containerHeight + this.constructor.static.threshold ) {
			// The contained result is big enough to show. Remove the maximum height
			this.$text
				.css( 'maxHeight', '' );
		} else {
			// Only show the readMore button if it should be shown
			this.readMoreButton.toggle( containerHeight < actualHeight );
		}
	}
};

/**
 * Respond to read more button click event.
 */
ve.ui.MWMediaInfoFieldWidget.prototype.onReadMoreClick = function () {
	this.readMoreButton.toggle( false );
	this.$text.css( 'maxHeight', this.$text.prop( 'scrollHeight' ) );
};

/**
 * Get field type; 'attribute' or 'description'
 *
 * @return {string} Field type
 */
ve.ui.MWMediaInfoFieldWidget.prototype.getType = function () {
	return this.type;
};
