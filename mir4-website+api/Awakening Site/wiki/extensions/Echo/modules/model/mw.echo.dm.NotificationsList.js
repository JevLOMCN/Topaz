( function () {
	/**
	 * Notifications list data structure.
	 *
	 * This contains the list of mw.echo.dm.NotificationItem items
	 * in the specified order and reflects when the list has changed.
	 *
	 * @class
	 * @extends mw.echo.dm.SortedList
	 *
	 * @constructor
	 * @param {Object} config Configuration options
	 * @cfg {Function} [sortingCallback] A function defining the sorting order
	 *  of items in this list.
	 * @cfg {string} [title] An optional title for this notifications list
	 * @cfg {string} [name='local'] Symbolic name for this list
	 * @cfg {string} [source='local'] Symbolic name for the source of this list.
	 *  This is used mainly for recognizing where API actions should be by the
	 *  controller.
	 * @cfg {string} [sourceURL] The URL for the article base of the remote
	 *  group or wiki
	 * @cfg {string} [timestamp=0] A timestamp representing the latest item in
	 *  the list.
	 */
	mw.echo.dm.NotificationsList = function MwEchoDmNotificationsList( config ) {
		config = config || {};

		// Parent constructor
		mw.echo.dm.NotificationsList.super.call( this );

		this.name = config.name || 'local';
		this.source = config.source || 'local';
		this.sourceURL = config.sourceURL || '';
		this.title = config.title || '';
		this.fallbackTimestamp = config.timestamp || 0;

		// Sorting callback
		this.setSortingCallback( config.sortingCallback || function ( a, b ) {
			if ( !a.isRead() && b.isRead() ) {
				return -1; // Unread items are always above read items
			} else if ( a.isRead() && !b.isRead() ) {
				return 1;
			} else if ( !a.isForeign() && b.isForeign() ) {
				return -1;
			} else if ( a.isForeign() && !b.isForeign() ) {
				return 1;
			}

			// Reverse sorting
			if ( b.getTimestamp() < a.getTimestamp() ) {
				return -1;
			} else if ( b.getTimestamp() > a.getTimestamp() ) {
				return 1;
			}

			// Fallback on IDs
			return b.getId() - a.getId();
		} );

		// Events
		this.aggregate( { update: 'itemUpdate' } );
	};

	/* Initialization */
	OO.inheritClass( mw.echo.dm.NotificationsList, mw.echo.dm.SortedList );

	/* Events */

	/**
	 * @event update
	 * @param {mw.echo.dm.NotificationItem[]} items Current items in the list
	 *
	 * The list has been updated
	 */

	/**
	 * @event itemUpdate
	 * @param {mw.echo.dm.NotificationItem} item Item that has changed
	 *
	 * An item in the list has been updated
	 */

	/**
	 * @event discard
	 * @param {mw.echo.dm.NotificationItem} item Item that was discarded
	 *
	 * An item was discarded
	 */

	/* Methods */

	/**
	 * Set the items in this list
	 *
	 * @param {mw.echo.dm.NotificationItem[]} items Items to insert into the list
	 * @fires update
	 */
	mw.echo.dm.NotificationsList.prototype.setItems = function ( items ) {
		this.clearItems();
		this.addItems( items );
		this.emit( 'update', this.getItems() );
	};

	/**
	 * Discard items from the list.
	 *
	 * This is a more precise operation than 'removeItems' because when
	 * the list is resorting the position of a single item, it removes
	 * the item and reinserts it, which makes the 'remove' event unhelpful
	 * to differentiate between actually discarding items, and only
	 * temporarily moving them.
	 *
	 * @param {mw.echo.dm.NotificationItem[]} items Items to insert into the list
	 */
	mw.echo.dm.NotificationsList.prototype.discardItems = function ( items ) {
		this.removeItems( items );
		this.emit( 'discard', items );
	};

	/**
	 * Get an array of all items' IDs.
	 *
	 * @return {number[]} Item IDs
	 */
	mw.echo.dm.NotificationsList.prototype.getAllItemIds = function () {
		var idArray = [],
			items = this.getItems();

		for ( var i = 0; i < items.length; i++ ) {
			idArray.push( items[ i ].getId() );
		}

		return idArray;
	};

	/**
	 * Get an array of all items' IDs for a given type
	 *
	 * @param {string} type Notification type
	 * @return {number[]} Item IDs
	 */
	mw.echo.dm.NotificationsList.prototype.getAllItemIdsByType = function ( type ) {
		var idArray = [],
			items = this.getItems();

		for ( var i = 0; i < items.length; i++ ) {
			if ( items[ i ].getType() === type ) {
				idArray.push( items[ i ].getId() );
			}
		}

		return idArray;
	};

	/**
	 * Get the title associated with this list.
	 *
	 * @return {string} List title
	 */
	mw.echo.dm.NotificationsList.prototype.getTitle = function () {
		return this.title;
	};

	/**
	 * Get the name associated with this list.
	 *
	 * @return {string} List name
	 */
	mw.echo.dm.NotificationsList.prototype.getName = function () {
		return this.name;
	};

	/**
	 * Get the source associated with this list.
	 *
	 * @return {string} List source
	 */
	mw.echo.dm.NotificationsList.prototype.getSource = function () {
		return this.source;
	};

	/**
	 * Get the source article url associated with this list.
	 *
	 * @return {string} List source article url
	 */
	mw.echo.dm.NotificationsList.prototype.getSourceURL = function () {
		return this.sourceURL;
	};

	/**
	 * Get the timestamp of the list by taking the latest notification
	 * timestamp.
	 *
	 * @return {string} Latest timestamp
	 */
	mw.echo.dm.NotificationsList.prototype.getTimestamp = function () {
		var items = this.getItems();

		return (
			// In the cases where we want a single timestamp for a
			// group, the group is usually all unread, which makes
			// the first item its newest
			items.length > 0 ?
				items[ 0 ].getTimestamp() :
				this.fallbackTimestamp
		);
	};

	/**
	 * Find all items that match the given IDs.
	 *
	 * @param {number[]} ids An array of item IDs
	 * @return {mw.echo.dm.NotificationItem[]} An array of matching items
	 */
	mw.echo.dm.NotificationsList.prototype.findByIds = function ( ids ) {
		return this.getItems().filter( function ( item ) {
			return ids.indexOf( item.getId() ) !== -1;
		} );
	};

	/**
	 * A general method to get the number of notifications in this list
	 *
	 * @return {number} Item count
	 */
	mw.echo.dm.NotificationsList.prototype.getCount = function () {
		return this.getItemCount();
	};

	/**
	 * Check if there are unseen items in this list
	 *
	 * @return {boolean} There are unseen items in the list
	 */
	mw.echo.dm.NotificationsList.prototype.hasUnseen = function () {
		var isItemUnseen = function ( item ) {
				return !item.isSeen();
			},
			items = this.getItems();

		return !!items.some( isItemUnseen );
	};

	/**
	 * Set all notifications to seen
	 *
	 * @param {string} timestamp New seen timestamp
	 */
	mw.echo.dm.NotificationsList.prototype.updateSeenState = function ( timestamp ) {
		this.getItems().forEach( function ( notification ) {
			notification.toggleSeen(
				notification.isRead() || notification.getTimestamp() < timestamp
			);
		} );
	};

	/**
	 * @inheritdoc
	 */
	mw.echo.dm.NotificationsList.prototype.isGroup = function () {
		return false;
	};

	mw.echo.dm.NotificationsList.prototype.isForeign = function () {
		return this.getSource() !== 'local';
	};

}() );
