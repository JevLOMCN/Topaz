/*!
 * VisualEditor MediaWiki UserInterface popup tool classes.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki UserInterface popup tool.
 *
 * @class
 * @abstract
 * @extends OO.ui.PopupTool
 * @constructor
 * @param {string} title
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config]
 * @cfg {number} [width] Popup width. Upstream default is 320.
 */
ve.ui.MWPopupTool = function VeUiMWPopupTool( title, toolGroup, config ) {
	// Configuration initialization
	config = ve.extendObject( { popup: { head: true, label: title, width: config && config.width } }, config );

	// Parent constructor
	ve.ui.MWPopupTool.super.call( this, toolGroup, config );

	this.popup.connect( this, {
		ready: 'onPopupOpened',
		closing: 'onPopupClosing'
	} );

	this.$element.addClass( 've-ui-mwPopupTool' );

	this.$link.on( 'click', this.onToolLinkClick.bind( this ) );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWPopupTool, OO.ui.PopupTool );

/**
 * Handle to call when popup is opened.
 */
ve.ui.MWPopupTool.prototype.onPopupOpened = function () {
	this.popup.closeButton.focus();
};

/**
 * Handle to call when popup is closing
 */
ve.ui.MWPopupTool.prototype.onPopupClosing = function () {
	this.$link.trigger( 'focus' );
};

/**
 * Handle clicks on the main tool button.
 *
 * @param {jQuery.Event} e Click event
 */
ve.ui.MWPopupTool.prototype.onToolLinkClick = function () {
	if ( this.popup.isVisible() ) {
		// Popup will be visible if this just opened, thanks to sequencing.
		// Can't just track this with toggle, because the notices popup is auto-opened and we
		// want to know about deliberate interactions.
		ve.track( 'activity.' + this.constructor.static.name + 'Popup', { action: 'show' } );
	}
};

/**
 * MediaWiki UserInterface notices popup tool.
 *
 * @class
 * @extends ve.ui.MWPopupTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config]
 */
ve.ui.MWNoticesPopupTool = function VeUiMWNoticesPopupTool( toolGroup, config ) {
	// Parent constructor
	ve.ui.MWNoticesPopupTool.super.call(
		this,
		ve.msg( 'visualeditor-editnotices-tooltip' ),
		toolGroup,
		ve.extendObject( config, { width: 380 } )
	);
};

/* Inheritance */

OO.inheritClass( ve.ui.MWNoticesPopupTool, ve.ui.MWPopupTool );

/* Static Properties */

ve.ui.MWNoticesPopupTool.static.name = 'notices';
ve.ui.MWNoticesPopupTool.static.group = 'notices';
ve.ui.MWNoticesPopupTool.static.icon = 'alert';
ve.ui.MWNoticesPopupTool.static.title = OO.ui.deferMsg( 'visualeditor-editnotices-tooltip' );
ve.ui.MWNoticesPopupTool.static.autoAddToCatchall = false;

/* Methods */

/**
 * Set notices to display
 *
 * @param {string[]} notices A (non-empty) list of notices
 */
ve.ui.MWNoticesPopupTool.prototype.setNotices = function ( notices ) {
	var tool = this,
		count = notices.length;

	this.popup.setLabel( ve.msg(
		'visualeditor-editnotices-tool',
		mw.language.convertNumber( count )
	) );

	if ( this.$items ) {
		this.$items.remove();
	}

	this.$items = $( '<div>' ).addClass( 've-ui-mwNoticesPopupTool-items' );
	this.noticeItems = [];

	notices.forEach( function ( item ) {
		// eslint-disable-next-line no-jquery/no-html
		var $element = $( '<div>' )
			.addClass( 've-ui-mwNoticesPopupTool-item' )
			.html( typeof item === 'string' ? item : item.message );
		ve.targetLinksToNewWindow( $element[ 0 ] );

		tool.noticeItems.push( {
			$element: $element,
			type: item.type
		} );

		tool.$items.append( $element );
	} );

	this.popup.$body.append( this.$items );
	// Fire content hook
	mw.hook( 'wikipage.content' ).fire( this.popup.$body );

	ve.track( 'activity.notices', { action: 'show' } );
};

/**
 * Get the tool title.
 *
 * @inheritdoc
 */
ve.ui.MWNoticesPopupTool.prototype.getTitle = function () {
	var items = this.toolbar.getTarget().getEditNotices();

	// eslint-disable-next-line mediawiki/msg-doc
	return ve.msg( this.constructor.static.title, items.length );
};

/* Registration */

ve.ui.toolFactory.register( ve.ui.MWNoticesPopupTool );
