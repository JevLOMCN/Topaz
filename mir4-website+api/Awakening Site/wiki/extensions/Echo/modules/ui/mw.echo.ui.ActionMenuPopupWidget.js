( function () {
	/**
	 * Action menu popup widget for echo items.
	 *
	 * We don't currently have anything that properly answers the complete
	 * design for our popup menus in OOUI, so this widget serves two purposes:
	 * 1. The MenuSelectWidget is intended to deliver a menu that relates
	 *    directly to its anchor, so its sizing is dictated by whatever anchors
	 *    it. This is not what we require, so we have to override the 'click' event
	 *    to reset the width of the menu.
	 * 2. It abstracts the behavior of the item menus for easier management
	 *    in the item widget itself (which is fairly large)
	 *
	 * @class
	 * @extends OO.ui.ButtonWidget
	 *
	 * @constructor
	 * @param {Object} [config] Configuration object
	 * @cfg {jQuery} [$overlay] A jQuery element functioning as an overlay
	 *  for popups.
	 * @cfg {Object} [horizontalPosition='auto'] How to position the menu, see OO.ui.FloatableElement.
	 *  By default, 'start' will be tried first, and if that doesn't fit, 'end' will be used.
	 */
	mw.echo.ui.ActionMenuPopupWidget = function MwEchoUiActionMenuPopupWidget( config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.ActionMenuPopupWidget.super.call( this, config );

		this.$overlay = config.$overlay || this.$element;

		// Menu
		this.customMenuPosition = ( config.horizontalPosition || 'auto' ) !== 'auto';
		this.menu = new OO.ui.MenuSelectWidget( {
			$floatableContainer: this.$element,
			horizontalPosition: this.customMenuPosition ? config.horizontalPosition : 'start',
			classes: [ 'mw-echo-ui-actionMenuPopupWidget-menu' ],
			widget: this
		} );
		this.$overlay.append( this.menu.$element );

		// Events
		this.connect( this, { click: 'onAction' } );
		this.getMenu().connect( this, {
			remove: 'decideToggle',
			add: 'decideToggle',
			clear: 'decideToggle'
		} );
		// Initialization
		this.$element
			.addClass( 'mw-echo-ui-actionMenuPopupWidget' );
	};

	/* Setup */

	OO.inheritClass( mw.echo.ui.ActionMenuPopupWidget, OO.ui.ButtonWidget );

	/**
	 * Handle the button action being triggered.
	 *
	 * @private
	 */
	mw.echo.ui.ActionMenuPopupWidget.prototype.onAction = function () {
		// HACK: If config.horizontalPosition isn't set, first try 'start', then 'end'
		if ( !this.customMenuPosition ) {
			this.menu.setHorizontalPosition( 'start' );
		}
		this.menu.toggle();
		if ( !this.customMenuPosition && this.menu.isClipped() ) {
			this.menu.setHorizontalPosition( 'end' );
		}
	};

	/**
	 * Decide whether the menu should be visible, based on whether it is
	 * empty or not.
	 */
	mw.echo.ui.ActionMenuPopupWidget.prototype.decideToggle = function () {
		this.toggle( !this.getMenu().isEmpty() );
	};

	/**
	 * Get the widget's action menu
	 *
	 * @return {OO.ui.MenuSelectWidget} Menu
	 */
	mw.echo.ui.ActionMenuPopupWidget.prototype.getMenu = function () {
		return this.menu;
	};
}() );
