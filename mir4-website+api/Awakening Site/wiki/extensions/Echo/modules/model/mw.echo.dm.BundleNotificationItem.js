( function () {
	/**
	 * Bundle notification item model. Contains a list of bundled notifications.
	 * Is expandable.
	 *
	 * @class
	 * @extends mw.echo.dm.NotificationItem
	 *
	 * @constructor
	 * @param {number} id Notification id
	 * @param {mw.echo.dm.NotificationItem[]} bundledNotificationModels
	 * @param {Object} [config] Configuration object
	 */
	mw.echo.dm.BundleNotificationItem = function MwEchoDmBundleNotificationItem( id, bundledNotificationModels, config ) {
		// Parent constructor
		mw.echo.dm.BundleNotificationItem.super.call( this, id, config );

		this.getSecondaryUrls().forEach( function ( link ) {
			// hack: put all secondary actions in the menu for now
			// this is a temporary fix for
			// - alignment of the labels
			// - make sure there isn't to many secondary links (causes a horizontal scrollbar)
			delete link.prioritized;
		} );

		this.setForeign( false );
		this.count = bundledNotificationModels.length;

		// bundled notifications use the compact header and do not have icons
		bundledNotificationModels.forEach( function ( bundled ) {
			bundled.content.header = bundled.content.compactHeader;
			delete bundled.iconURL;
		} );

		this.list = new mw.echo.dm.NotificationsList();
		this.list.setItems( bundledNotificationModels );

		this.list.connect( this, { itemUpdate: 'onItemUpdate' } );

		// For bundles, 'read' is a computed state based on
		// inner notifications.
		// Calling toggleRead here to initialize based
		// on current computed state.
		this.toggleRead( this.isRead() );
	};

	OO.inheritClass( mw.echo.dm.BundleNotificationItem, mw.echo.dm.NotificationItem );

	/* Methods */

	/**
	 * Whenever a bundled notification changes, update the read status of the parent.
	 */
	mw.echo.dm.BundleNotificationItem.prototype.onItemUpdate = function () {
		this.toggleRead( this.isRead() );
	};

	/**
	 * @return {boolean} Whether this bundle is completely read
	 */
	mw.echo.dm.BundleNotificationItem.prototype.isRead = function () {
		return this.list.getItems().every( function ( item ) {
			return item.isRead();
		} );
	};

	/**
	 * Get the list of bundled notifications
	 *
	 * @return {mw.echo.dm.NotificationsList} List of bundled notifications
	 */
	mw.echo.dm.BundleNotificationItem.prototype.getList = function () {
		return this.list;
	};

	/**
	 * Get expected item count from all sources
	 *
	 * @return {number} Item count
	 */
	mw.echo.dm.BundleNotificationItem.prototype.getCount = function () {
		return this.list.getItemCount();
	};

	/**
	 * Check if there are unseen items in any of the cross wiki source lists.
	 * This method is required for all models that are managed by the
	 * mw.echo.dm.ModelManager.
	 *
	 * @return {boolean} There are unseen items
	 */
	mw.echo.dm.BundleNotificationItem.prototype.hasUnseen = function () {
		var isUnseen = function ( item ) {
			return !item.isSeen();
		};
		return this.list.getItems().some( isUnseen );
	};

	/**
	 * Set all notifications to seen
	 *
	 * @param {number} timestamp New seen timestamp
	 */
	mw.echo.dm.BundleNotificationItem.prototype.updateSeenState = function ( timestamp ) {
		this.list.getItems().forEach( function ( notification ) {
			notification.toggleSeen(
				notification.isRead() || notification.getTimestamp() < timestamp
			);
		} );
	};

	/**
	 * This item is a group.
	 * This method is required for all models that are managed by the
	 * mw.echo.dm.ModelManager.
	 *
	 * @return {boolean} This item is a group
	 */
	mw.echo.dm.BundleNotificationItem.prototype.isGroup = function () {
		return true;
	};

	/**
	 * Get the all ids contained in this notification
	 *
	 * @return {number[]}
	 */
	mw.echo.dm.BundleNotificationItem.prototype.getAllIds = function () {
		return this.list.getItems().map( function ( item ) {
			return item.getId();
		} );
	};

	/**
	 * Find all items that match the given IDs.
	 *
	 * @param {number[]} ids An array of item IDs
	 * @return {mw.echo.dm.NotificationItem[]} An array of matching items
	 */
	mw.echo.dm.BundleNotificationItem.prototype.findByIds = function ( ids ) {
		return this.list.findByIds( ids );
	};

	/**
	 * Get bundled notifications
	 *
	 * @return {mw.echo.dm.NotificationItem[]} bundled notifications
	 */
	mw.echo.dm.BundleNotificationItem.prototype.getItems = function () {
		return this.list.getItems();
	};

	/**
	 * Get model name
	 *
	 * @return {string} model name
	 */
	mw.echo.dm.BundleNotificationItem.prototype.getName = function () {
		return this.getModelName();
	};

}() );
