( function () {
	/**
	 * Confirmation overlay widget, especially for mobile display.
	 * The behavior of this widget is to appear with a given confirmation
	 * message and then disappear after a given interval.
	 *
	 * @class
	 * @extends OO.ui.Widget
	 *
	 * @constructor
	 * @param {Object} [config] Configuration object
	 * @cfg {number} [interval=2000] The number of milliseconds that it takes
	 *  for the popup to disappear after appearing.
	 */
	mw.echo.ui.ConfirmationPopupWidget = function MwEchoUiConfirmationPopupWidget( config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.ConfirmationPopupWidget.super.call( this, config );

		this.labelWidget = new OO.ui.LabelWidget( config );
		this.iconWidget = new OO.ui.IconWidget( $.extend( { icon: 'checkAll' }, config ) );
		this.interval = config.interval || 2000;

		this.$element
			.addClass( 'mw-echo-ui-confirmationPopupWidget' )
			.append(
				$( '<div>' )
					.addClass( 'mw-echo-ui-confirmationPopupWidget-popup' )
					.append( this.iconWidget.$element, this.labelWidget.$element )
			)
			// We're using explicit hide here because the widget uses
			// animated fadeOut
			.hide();
	};

	/* Initialization */

	OO.inheritClass( mw.echo.ui.ConfirmationPopupWidget, OO.ui.Widget );

	/**
	 * Show the widget and then animate its fade out.
	 */
	mw.echo.ui.ConfirmationPopupWidget.prototype.showAnimated = function () {
		// OOUI removes the oo-ui-image-invert class when it is initialized
		// without explicit flag classes, so we have to re-add this when we
		// display the icon for the icon to be inverted
		this.iconWidget.$element.addClass( 'oo-ui-image-invert' );
		this.$element.show();
		setTimeout( this.hide.bind( this ), this.interval );
	};

	/**
	 * Hide the widget by fading it out
	 *
	 * @private
	 */
	mw.echo.ui.ConfirmationPopupWidget.prototype.hide = function () {
		// FIXME: Use CSS transition
		// eslint-disable-next-line no-jquery/no-fade
		this.$element.fadeOut();
	};

	/**
	 * Delegate to labelWidget.setLabel()
	 *
	 * @param {jQuery|string|OO.ui.HtmlSnippet|Function|null} label Label nodes; text; a function that returns nodes or
	 *  text; or null for no label
	 */
	mw.echo.ui.ConfirmationPopupWidget.prototype.setLabel = function ( label ) {
		this.labelWidget.setLabel( label );
	};
}() );
