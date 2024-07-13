/**
 * Container for textual elements, which should be collapsed to one line by default.
 *
 * A "more / less" button is used to toggle additional lines.
 *
 * @class
 * @extends OO.ui.Element
 * @mixins OO.EventEmitter
 *
 * @constructor
 * @param {Object} config
 * @cfg {jQuery} $content
 */
ve.ui.MWExpandableContentElement = function VeUiMWExpandableContentElement( config ) {
	// Parent constructor
	ve.ui.MWExpandableContentElement.super.call( this, config );

	// Mixin constructors
	OO.EventEmitter.call( this );

	this.$content = config.$content;

	this.collapsed = true;
	this.toggle( false );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWExpandableContentElement, OO.ui.Element );

OO.mixinClass( ve.ui.MWExpandableContentElement, OO.EventEmitter );

/* Methods */

/**
 * @private
 * @return {number}
 */
ve.ui.MWExpandableContentElement.prototype.getLineHeight = function () {
	return parseInt( this.$content.css( 'line-height' ) );
};

/**
 * @private
 * @return {number}
 */
ve.ui.MWExpandableContentElement.prototype.calculateCurrentTextHeight = function () {
	var currentHeight = this.$content.height(),
		expandedHeight = this.$content.css( 'height', 'auto' ).height();
	if ( expandedHeight !== currentHeight ) {
		this.$content.css( 'height', currentHeight );
	}
	return expandedHeight;
};

/**
 * @private
 */
ve.ui.MWExpandableContentElement.prototype.makeCollapsible = function () {
	this.button = new OO.ui.ButtonWidget( {
		framed: false,
		flags: [ 'progressive' ],
		label: ve.msg( 'visualeditor-expandable-more' ),
		classes: [ 've-ui-expandableContent-toggle' ],
		invisibleLabel: ve.ui.MWTransclusionDialog.static.isSmallScreen(),
		icon: 'expand'
	} ).on( 'click', this.onButtonClick.bind( this ) );

	this.$content.on( 'click', this.onDescriptionClick.bind( this ) )
		.addClass( 've-ui-expandableContent-collapsible' );

	this.$container = $( '<div>' )
		.addClass( 've-ui-expandableContent-container' )
		.append(
			$( '<div>' ).addClass( 've-ui-expandableContent-fade' ),
			this.button.$element
		)
		.appendTo( this.$element );
};

/**
 * @private
 */
ve.ui.MWExpandableContentElement.prototype.recalculateVisuals = function () {
	var height = this.calculateCurrentTextHeight() + this.button.$element.height(),
		collapsedHeight = this.getLineHeight(),
		label = this.collapsed ? 'visualeditor-expandable-more' : 'visualeditor-expandable-less';

	this.$content.css( 'height', this.collapsed ? collapsedHeight : height );
	this.$container.removeClass( 'oo-ui-element-hidden' )
		.height( collapsedHeight );
	// The following messages are used here:
	// * visualeditor-expandable-more
	// * visualeditor-expandable-less
	this.button.setLabel( ve.msg( label ) )
		.setInvisibleLabel( ve.ui.MWTransclusionDialog.static.isSmallScreen() )
		.setIcon( this.collapsed ? 'expand' : 'collapse' );
};

/**
 * @private
 */
ve.ui.MWExpandableContentElement.prototype.reset = function () {
	this.$content.css( 'height', 'auto' );
	this.$container.addClass( 'oo-ui-element-hidden' );
	this.button.setInvisibleLabel( false );
};

/**
 * @private
 */
ve.ui.MWExpandableContentElement.prototype.onButtonClick = function () {
	this.collapsed = !this.collapsed;
	this.recalculateVisuals();
};

/**
 * @private
 */
ve.ui.MWExpandableContentElement.prototype.onDescriptionClick = function () {
	if ( this.button.invisibleLabel ) {
		// Don't toggle the description if the user is trying to select the text.
		if ( window.getSelection().toString() === '' ) {
			this.onButtonClick();
		}
	}
};

ve.ui.MWExpandableContentElement.prototype.updateSize = function () {
	this.toggle( true );

	if ( Math.floor( this.calculateCurrentTextHeight() / this.getLineHeight() ) > 3 ) {
		if ( !this.$container ) {
			this.makeCollapsible();
		}
		this.recalculateVisuals();
	} else {
		if ( this.$container ) {
			this.reset();
		}
	}
};
