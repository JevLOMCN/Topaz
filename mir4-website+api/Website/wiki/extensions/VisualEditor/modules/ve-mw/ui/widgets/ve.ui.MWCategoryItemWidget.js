/*!
 * VisualEditor UserInterface MWCategoryItemWidget class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Creates an ve.ui.MWCategoryItemWidget object.
 *
 * @class
 * @abstract
 * @extends OO.ui.ButtonWidget
 * @mixins OO.ui.mixin.DraggableElement
 *
 * @constructor
 * @param {Object} config
 * @cfg {Object} item Category item
 * @cfg {string} item.name Category name
 * @cfg {string} item.value
 * @cfg {string} [item.sortKey='']
 * @cfg {ve.dm.MWCategoryMetaItem} item.metaItem
 * @cfg {boolean} [hidden] Whether the category is hidden or not
 * @cfg {boolean} [missing] Whether the category's description page is missing
 * @cfg {string} [redirectTo] The name of the category this category's page redirects to.
 */
ve.ui.MWCategoryItemWidget = function VeUiMWCategoryItemWidget( config ) {
	// Config initialization
	config = ve.extendObject( { indicator: 'down' }, config );

	// Parent constructor
	ve.ui.MWCategoryItemWidget.super.call( this, config );

	// Mixin constructors
	OO.ui.mixin.DraggableElement.call( this, config );

	// Properties
	this.name = config.item.name;
	this.value = config.item.value;
	this.sortKey = config.item.sortKey || '';
	this.metaItem = config.item.metaItem;
	this.isHidden = config.hidden;
	this.isMissing = config.missing;

	// Initialization
	this.setLabel( config.redirectTo || this.value );
	if ( config.redirectTo ) {
		ve.init.platform.linkCache.styleElement( mw.Title.newFromText(
			config.redirectTo,
			mw.config.get( 'wgNamespaceIds' ).category
		).getPrefixedText(), this.$label );
	} else {
		ve.init.platform.linkCache.styleElement( this.name, this.$label );
	}

	// Events
	this.on( 'click', this.onButtonClick.bind( this ) );

	this.$element.addClass( 've-ui-mwCategoryItemWidget' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWCategoryItemWidget, OO.ui.ButtonWidget );

OO.mixinClass( ve.ui.MWCategoryItemWidget, OO.ui.mixin.DraggableElement );

/* Events */

/**
 * @event togglePopupMenu
 * @param {ve.ui.MWCategoryItemWidget} item Item to load into popup
 */

/* Methods */

/**
 * Handle button widget click events.
 *
 * @fires togglePopupMenu on click.
 */
ve.ui.MWCategoryItemWidget.prototype.onButtonClick = function () {
	this.emit( 'togglePopupMenu', this );
};
