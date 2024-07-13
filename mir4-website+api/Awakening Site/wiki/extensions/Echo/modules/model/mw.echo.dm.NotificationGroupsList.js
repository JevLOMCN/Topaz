( function () {
	/**
	 * Notification groups list data structure.
	 * It contains mw.echo.dm.NotificationsList items
	 *
	 * This contains a list of grouped notifications by source, and
	 * serves as a list of lists for cross-wiki notifications based
	 * on their remote sources and/or wikis.
	 *
	 * @class
	 * @extends mw.echo.dm.SortedList
	 *
	 * @constructor
	 * @param {Object} [config] Configuration options
	 * @cfg {boolean} [foreign] The list contains foreign notifications
	 */
	mw.echo.dm.NotificationGroupsList = function MwEchoDmNotificationGroupsList( config ) {
		config = config || {};

		// Parent constructor
		mw.echo.dm.NotificationGroupsList.super.call( this );

		// Sorting callback
		this.setSortingCallback( function ( a, b ) {
			// Reverse sorting
			if ( b.getTimestamp() < a.getTimestamp() ) {
				return -1;
			} else if ( b.getTimestamp() > a.getTimestamp() ) {
				return 1;
			}

			// Fallback on Source
			return b.getName() - a.getName();
		} );

		this.foreign = !!config.foreign;
		this.groups = {};

		this.aggregate( { discard: 'groupDiscardItem' } );
		this.connect( this, { groupDiscardItem: 'onGroupDiscardItem' } );
	};

	/* Initialization */
	OO.inheritClass( mw.echo.dm.NotificationGroupsList, mw.echo.dm.SortedList );

	/* Events */

	/**
	 * @event discard
	 *
	 * A group was permanently removed
	 */

	/* Methods */

	/**
	 * Handle a discard event from any list.
	 * This means that one of the sources has discarded an item.
	 *
	 * @param {mw.echo.dm.NotificationsList} groupList List source model for the item
	 */
	mw.echo.dm.NotificationGroupsList.prototype.onGroupDiscardItem = function ( groupList ) {
		// Check if the list has anything at all
		if ( groupList.isEmpty() ) {
			// Remove it
			this.removeGroup( groupList.getName() );
		}
	};

	/**
	 * Add a group to the list. This is a more convenient alias to using
	 * addItems()
	 *
	 * @param {string} groupSource Symbolic name for the source of
	 *  this group, to be recognized for API operations
	 * @param {Object} sourceData Source data
	 * @param {mw.echo.dm.NotificationItem[]} [groupItems] Optional items to add to this group
	 */
	mw.echo.dm.NotificationGroupsList.prototype.addGroup = function ( groupSource, sourceData, groupItems ) {
		var groupListModel = new mw.echo.dm.NotificationsList( {
			title: sourceData.title,
			name: groupSource,
			source: groupSource,
			sourceURL: sourceData.base,
			timestamp: sourceData.ts
		} );

		if ( Array.isArray( groupItems ) && groupItems.length > 0 ) {
			groupListModel.addItems( groupItems );
		}

		// Add the group
		this.addItems( [ groupListModel ] );
	};

	/**
	 * Get the timestamp of the list by taking the latest list's
	 * timestamp.
	 *
	 * @return {string} Latest timestamp
	 */
	mw.echo.dm.NotificationGroupsList.prototype.getTimestamp = function () {
		var items = this.getItems();

		return (
			items.length > 0 ?
				items[ 0 ].getTimestamp() :
				0
		);
	};

	/**
	 * Add items to a specific group by its source identifier.
	 *
	 * @param {string} groupSource Source identifier of the group
	 * @param {mw.echo.dm.NotificationItem[]} groupItems Items to add to this group
	 */
	mw.echo.dm.NotificationGroupsList.prototype.addItemsToGroup = function ( groupSource, groupItems ) {
		var group = this.getGroupByName( groupSource );

		if ( group ) {
			group.addItems( groupItems );
		}
	};
	/**
	 * Remove a group from the list. This is an easier to use alias
	 * to 'removeItems()' method.
	 *
	 * Since this is an intentional action, we fire 'discard' event.
	 * The main reason for this is that the event 'remove' is a general
	 * event that is fired both when a user intends on removing an
	 * item and also when an item is temporarily removed to be re-added
	 * for the sake of sorting. To avoid ambiguity, we use 'discard' event.
	 *
	 * @param {string} groupName Group name
	 * @fires discard
	 */
	mw.echo.dm.NotificationGroupsList.prototype.removeGroup = function ( groupName ) {
		var group = this.getGroupByName( groupName );

		if ( group ) {
			this.removeItems( group );
			this.emit( 'discard', group );
		}
	};

	/**
	 * Get a group by its source identifier.
	 *
	 * @param {string} groupName Group name
	 * @return {mw.echo.dm.NotificationsList|null} Requested group, null if none was found.
	 */
	mw.echo.dm.NotificationGroupsList.prototype.getGroupByName = function ( groupName ) {
		var items = this.getItems();

		for ( var i = 0; i < items.length; i++ ) {
			if ( items[ i ].getName() === groupName ) {
				return items[ i ];
			}
		}
		return null;
	};
}() );
