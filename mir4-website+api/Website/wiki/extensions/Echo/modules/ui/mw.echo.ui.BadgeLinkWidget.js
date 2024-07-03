( function () {
	/**
	 * Notification badge button widget for echo popup.
	 *
	 * @class
	 * @extends OO.ui.ButtonWidget
	 *
	 * @constructor
	 * @param {Object} [config] Configuration object
	 * @cfg {string} [type] The notification types this button represents;
	 *  'message', 'alert' or 'all'
	 * @cfg {string} [href] URL the badge links to
	 * @cfg {string} [numItems=0] The number of items that are in the button display
	 * @cfg {string} [convertedNumber] A converted version of the initial count
	 */
	mw.echo.ui.BadgeLinkWidget = function MwEchoUiBadgeLinkWidget( config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.BadgeLinkWidget.super.call( this, config );

		// Mixin constructors
		OO.ui.mixin.LabelElement.call( this, $.extend( { $label: this.$element }, config ) );
		OO.ui.mixin.ButtonElement.call( this, $.extend( { $button: this.$element }, config ) );
		OO.ui.mixin.TitledElement.call( this, $.extend( { $titled: this.$element }, config ) );
		OO.ui.mixin.FlaggedElement.call( this, $.extend( {}, config, { $flagged: this.$element } ) );

		this.$element
			.addClass( 'mw-echo-notifications-badge' );

		this.count = 0;
		this.type = config.type || 'alert';
		this.setCount( config.numItems || 0, config.convertedNumber );

		if ( config.href !== undefined && OO.ui.isSafeUrl( config.href ) ) {
			this.$element.attr( 'href', config.href );
		}
		if ( this.type === 'alert' ) {
			this.$element
				.addClass( 'oo-ui-icon-bell' );
		} else {
			this.$element
				.addClass( 'oo-ui-icon-tray' );
		}
	};

	OO.inheritClass( mw.echo.ui.BadgeLinkWidget, OO.ui.Widget );
	OO.mixinClass( mw.echo.ui.BadgeLinkWidget, OO.ui.mixin.LabelElement );
	OO.mixinClass( mw.echo.ui.BadgeLinkWidget, OO.ui.mixin.ButtonElement );
	OO.mixinClass( mw.echo.ui.BadgeLinkWidget, OO.ui.mixin.TitledElement );
	OO.mixinClass( mw.echo.ui.BadgeLinkWidget, OO.ui.mixin.FlaggedElement );

	mw.echo.ui.BadgeLinkWidget.static.tagName = 'a';

	/**
	 * Overrides ButtonElement.prototype.onClick so that it doesn't call ev.stopPropagation.
	 * This ensures the dialog dismisses other open overlays e.g. ULS (See T295796 for more
	 * information).
	 *
	 * @inheritDoc
	 */
	mw.echo.ui.BadgeLinkWidget.prototype.onClick = function ( ev ) {
		ev.preventDefault();
		this.emit( 'click' );
	};

	/**
	 * Set the count labels for this button.
	 *
	 * @param {number} numItems Number of items
	 * @param {string} [convertedNumber] Label of the button. Defaults to the default message
	 *  showing the item number.
	 */
	mw.echo.ui.BadgeLinkWidget.prototype.setCount = function ( numItems, convertedNumber ) {
		convertedNumber = convertedNumber !== undefined ? convertedNumber : numItems;

		this.$element
			.toggleClass( 'mw-echo-notifications-badge-all-read', !numItems )
			.toggleClass( 'mw-echo-notifications-badge-long-label', convertedNumber.length > 2 )
			.attr( 'data-counter-num', numItems )
			.attr( 'data-counter-text', convertedNumber );

		this.setLabel( mw.msg(
			this.type === 'alert' ?
				'echo-notification-alert' :
				'echo-notification-notice',
			convertedNumber
		) );

		if ( this.count !== numItems ) {
			this.count = numItems;

			// Fire badge count change hook
			mw.hook( 'ext.echo.badge.countChange' ).fire( this.type, this.count, convertedNumber );
		}
	};
}() );
