( function () {
	/**
	 * Single notification item widget for echo popup.
	 *
	 * @class
	 * @extends mw.echo.ui.NotificationItemWidget
	 * @mixins OO.ui.mixin.PendingElement
	 *
	 * @constructor
	 * @param {mw.echo.Controller} controller Echo notifications controller
	 * @param {mw.echo.dm.NotificationItem} model Notification item model
	 * @param {Object} [config] Configuration object
	 * @cfg {jQuery} [$overlay] A jQuery element functioning as an overlay
	 *  for popups.
	 * @cfg {boolean} [bundle=false] This notification is part of a bundle
	 */
	mw.echo.ui.SingleNotificationItemWidget = function MwEchoUiSingleNotificationItemWidget( controller, model, config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.SingleNotificationItemWidget.super.call( this, controller, model, config );
		// Mixin constructors
		OO.ui.mixin.PendingElement.call( this, config );

		this.controller = controller;
		this.model = model;

		this.bundle = !!config.bundle;
		this.$overlay = config.$overlay || this.$element;

		// Toggle 'mark as read' functionality
		this.toggleMarkAsReadButtons( !this.model.isRead() );

		// Events
		this.model.connect( this, { update: 'updateDataFromModel' } );

		// Update read and seen states from the model
		this.updateDataFromModel();
	};

	/* Initialization */

	OO.inheritClass( mw.echo.ui.SingleNotificationItemWidget, mw.echo.ui.NotificationItemWidget );
	OO.mixinClass( mw.echo.ui.SingleNotificationItemWidget, OO.ui.mixin.PendingElement );

	/* Methods */
	/**
	 * @inheritdoc
	 */
	mw.echo.ui.SingleNotificationItemWidget.prototype.markRead = function ( isRead ) {
		isRead = isRead !== undefined ? !!isRead : true;

		if ( this.model.isForeign() ) {
			this.controller.markCrossWikiItemsRead( this.model.getId(), this.model.getSource() );
		} else {
			this.controller.markItemsRead( this.model.getId(), this.model.getModelName(), isRead );
		}
	};

	/**
	 * Extend 'toggleRead' to emit sortChange so the item can be sorted
	 * when its read state was updated
	 *
	 * @inheritdoc
	 * @fires sortChange
	 */
	mw.echo.ui.SingleNotificationItemWidget.prototype.toggleRead = function ( read ) {
		var oldState = this.read;

		// Parent method
		mw.echo.ui.SingleNotificationItemWidget.super.prototype.toggleRead.call( this, read );

		if ( oldState !== read ) {
			this.emit( 'sortChange' );
		}
	};

	/**
	 * Update item state when the item model changes.
	 */
	mw.echo.ui.SingleNotificationItemWidget.prototype.updateDataFromModel = function () {
		this.toggleRead( this.model.isRead() );
		this.toggleSeen( this.model.isSeen() );
	};
}() );
