( function () {
	/**
	 * Wrapper for the notifications widget, for view outside the popup.
	 *
	 * @class
	 * @extends OO.ui.Widget
	 * @mixins OO.ui.mixin.PendingElement
	 *
	 * @constructor
	 * @param {mw.echo.Controller} controller Echo controller
	 * @param {mw.echo.dm.ModelManager} model Notifications model manager
	 * @param {Object} [config] Configuration object
	 * @cfg {jQuery} [$overlay] A jQuery element functioning as an overlay
	 *  for popups.
	 */
	mw.echo.ui.NotificationsWrapper = function MwEchoUiNotificationsWrapper( controller, model, config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.NotificationsWrapper.super.call( this, config );

		// Mixin constructor
		OO.ui.mixin.PendingElement.call( this, config );

		this.controller = controller;
		this.model = model;

		this.notificationsWidget = new mw.echo.ui.NotificationsListWidget(
			this.controller,
			this.model,
			{
				$overlay: config.$overlay,
				types: this.controller.getTypes(),
				label: mw.msg( 'notifications' ),
				icon: 'bell'
			}
		);

		// Initialize
		this.$element
			.addClass( 'mw-echo-notificationsWrapper skin-invert' )
			.append( this.notificationsWidget.$element );
	};

	/* Initialization */

	OO.inheritClass( mw.echo.ui.NotificationsWrapper, OO.ui.Widget );
	OO.mixinClass( mw.echo.ui.NotificationsWrapper, OO.ui.mixin.PendingElement );

	/* Events */

	/**
	 * @event finishLoading
	 * Notifications have successfully finished being processed and are fully loaded
	 */

	/* Methods */

	/**
	 * Populate the notifications panel
	 *
	 * @return {jQuery.Promise} A promise that is resolved when all notifications
	 *  were fetched from the API and added to the model and UI.
	 */
	mw.echo.ui.NotificationsWrapper.prototype.populate = function () {
		var widget = this;

		this.pushPending();
		return this.controller.fetchLocalNotifications( true )
			.catch( function ( errorObj ) {
				if ( errorObj.errCode === 'notlogin-required' ) {
					// Login required message
					widget.notificationsWidget.resetLoadingOption( mw.msg( 'echo-notification-loginrequired' ) );
				} else {
					// Generic API failure message
					widget.notificationsWidget.resetLoadingOption( mw.msg( 'echo-api-failure' ) );
				}
			} )
			.always( function () {
				widget.popPending();
				widget.emit( 'finishLoading' );
				widget.promiseRunning = false;
			} );
	};
}() );
