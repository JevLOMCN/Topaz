( function () {
	/**
	 * A class defining Echo API instructions and network operations
	 *
	 * @class
	 *
	 * @constructor
	 * @param {Object} config Configuration options
	 * @cfg {number} [limit=25] Number of notifications to fetch
	 */
	mw.echo.api.EchoApi = function MwEchoApiEchoApi( config ) {
		config = config || {};

		this.network = new mw.echo.api.NetworkHandler( config );

		this.fetchingPromise = null;
		this.limit = config.limit || 25;
		this.fetchingPrioritizer = new mw.echo.api.PromisePrioritizer();
	};

	OO.initClass( mw.echo.api.EchoApi );

	/**
	 * Register a set of foreign sources.
	 *
	 * @param {Object} sources Object mapping source names to config objects
	 * @param {boolean} [unreadOnly=false] Fetch only unread notifications
	 * @param {number} [limit] Specific limit of notifications. Defaults to
	 *  the default limit stated in the class.
	 */
	mw.echo.api.EchoApi.prototype.registerForeignSources = function ( sources, unreadOnly, limit ) {
		limit = limit || this.limit;

		for ( var s in sources ) {
			this.network.setApiHandler( s, new mw.echo.api.ForeignAPIHandler( sources[ s ].url, {
				unreadOnly: !!unreadOnly,
				limit: limit
			} ) );
		}
	};

	/**
	 * Register a set of local sources.
	 *
	 * @param {string[]} sources An array of source names
	 */
	mw.echo.api.EchoApi.prototype.registerLocalSources = function ( sources ) {
		var localHandler = this.network.getApiHandler( 'local' );

		for ( var i = 0; i < sources.length; i++ ) {
			this.network.setApiHandler( sources[ i ], localHandler );
		}
	};

	/**
	 * Fetch all pages with unread notifications in them per wiki
	 *
	 * @param {string[]} [sources=all] Requested sources
	 * @return {jQuery.Promise} Promise that is resolved with an object
	 *  of pages with the number of unread notifications per wiki
	 */
	mw.echo.api.EchoApi.prototype.fetchUnreadNotificationPages = function ( sources ) {
		return this.network.getApiHandler( 'local' ).fetchUnreadNotificationPages( sources )
			.then( function ( data ) {
				return OO.getProp( data, 'query', 'unreadnotificationpages' );
			} );
	};

	/**
	 * Fetch notifications from a given source with given filters
	 *
	 * @param {string} type Notification type to fetch: 'alert', 'message', or 'all'
	 * @param {string} [source] The source from which to fetch the notifications.
	 *  If not given, the local notifications will be fetched.
	 * @param {Object} [filters] Filter values
	 * @return {jQuery.Promise} Promise that is resolved with all notifications for the
	 *  requested types.
	 */
	mw.echo.api.EchoApi.prototype.fetchFilteredNotifications = function ( type, source, filters ) {
		source = source || 'local';

		if ( source === 'local' ) {
			return this.fetchNotifications( type, source, true, filters );
		} else {
			return this.fetchNotificationsFromRemoteSource( type, source, true, filters );
		}
	};

	/**
	 * Convert the filter object to the relevant API parameters.
	 *
	 * @param {Object} [filterObject] The filter object
	 * @param {string} [filterObject.continue] A continue variable
	 *  defining the offset to fetch notifications
	 * @param {string} [filterObject.readState] Notification read
	 *  state, 'all', 'read' or 'unread'
	 * @param {boolean} [filterObject.unreadFirst] Fetch unread notifications
	 *  first in the sorting order.
	 * @param {boolean} [filterObject.bundle] Bundle local notifications
	 * @param {string|string[]} [filterObject.titles] Requested titles. To request notifications with no title,
	 *  use null (standalone or as an array element).
	 * @return {Object} API parameter definitions to override
	 */
	mw.echo.api.EchoApi.prototype.convertFiltersToAPIParams = function ( filterObject ) {
		var overrideParams = {};

		filterObject = filterObject || {};

		if ( filterObject.continue ) {
			overrideParams.notcontinue = filterObject.continue;
		}

		if ( filterObject.unreadFirst ) {
			overrideParams.notunreadfirst = 1;
		}

		if ( filterObject.bundle ) {
			overrideParams.notbundle = 1;
		}

		if ( filterObject.readState && filterObject.readState !== 'all' ) {
			overrideParams.notfilter = filterObject.readState === 'read' ?
				'read' :
				'!read';
		}

		if ( filterObject.titles ) {
			var titles = Array.isArray( filterObject.titles ) ? filterObject.titles : [ filterObject.titles ];
			if ( titles.indexOf( null ) !== -1 ) {
				// Map null to '[]'
				titles.splice( titles.indexOf( null ), 1, '[]' );
			}
			overrideParams.nottitles = titles;
		}

		return overrideParams;
	};

	/**
	 * Fetch remote notifications from a given source. This skips the local fetching that is
	 * usually done and calls the remote wiki directly.
	 *
	 * @param {string} type Notification type to fetch: 'alert', 'message', or 'all'
	 * @param {string|string[]} [source] The source from which to fetch the notifications.
	 *  If not given, the local notifications will be fetched.
	 * @param {boolean} [isForced] Force a refresh on the fetch notifications promise
	 * @param {Object} [filters] Filter values
	 * @return {jQuery.Promise} Promise that is resolved with all notifications for the
	 *  requested types.
	 */
	mw.echo.api.EchoApi.prototype.fetchNotificationsFromRemoteSource = function ( type, source, isForced, filters ) {
		var handler = this.network.getApiHandler( source );

		if ( !handler ) {
			return $.Deferred().reject().promise();
		}

		return this.fetchingPrioritizer.prioritize( handler.fetchNotifications(
			type,
			// For the remote source, we are fetching 'local' notifications
			'local',
			!!isForced,
			this.convertFiltersToAPIParams( filters )
		) )
			.then( function ( result ) {
				return OO.getProp( result.query, 'notifications' );
			} );
	};

	/**
	 * Fetch notifications from the server based on type
	 *
	 * @param {string} type Notification type to fetch: 'alert', 'message', or 'all'
	 * @param {string|string[]} [sources] The source from which to fetch the notifications.
	 *  If not given, the local notifications will be fetched.
	 * @param {boolean} [isForced] Force a refresh on the fetch notifications promise
	 * @param {Object} [filters] Filter values
	 * @return {jQuery.Promise} Promise that is resolved with all notifications for the
	 *  requested types.
	 */
	mw.echo.api.EchoApi.prototype.fetchNotifications = function ( type, sources, isForced, filters ) {
		sources = Array.isArray( sources ) ?
			sources :
			sources ?
				[ sources ] :
				'local';

		return this.fetchingPrioritizer.prioritize( this.network.getApiHandler( 'local' ).fetchNotifications(
			type,
			sources,
			isForced,
			this.convertFiltersToAPIParams( filters )
		) )
			.then( function ( result ) {
				return OO.getProp( result.query, 'notifications' );
			} );
	};

	/**
	 * Fetch notifications from several sources
	 *
	 * @param {string[]} sourceArray An array of sources to fetch from the group
	 * @param {string} type Notification type
	 * @param {boolean} bundle Bundle local notifications
	 * @return {jQuery.Promise} A promise that resolves with an object that maps wiki
	 *  names to an array of their items' API data objects.
	 */
	mw.echo.api.EchoApi.prototype.fetchNotificationGroups = function ( sourceArray, type, bundle ) {
		var overrideParams = { notcrosswikisummary: false, notbundle: bundle };
		return this.network.getApiHandler( 'local' ).fetchNotifications( type, sourceArray, true, overrideParams )
			.then( function ( result ) {
				var items = OO.getProp( result, 'query', 'notifications', 'list' ),
					groups = {};

				// Split the items to groups
				for ( var i = 0; i < items.length; i++ ) {
					groups[ items[ i ].wiki ] = groups[ items[ i ].wiki ] || [];
					groups[ items[ i ].wiki ].push( items[ i ] );
				}

				return groups;
			} );
	};

	/**
	 * Mark items as read in the API.
	 *
	 * @param {string[]} itemIds An array of item IDs to mark as read
	 * @param {string} source The source that these items belong to
	 * @param {boolean} [isRead] The read state of the item; true for marking the
	 *  item as read, false for marking the item as unread
	 * @return {jQuery.Promise} A promise that is resolved when the operation
	 *  is complete, with the number of unread notifications still remaining
	 *  for that type in the given source
	 */
	mw.echo.api.EchoApi.prototype.markItemsRead = function ( itemIds, source, isRead ) {
		// markasread is proxied via the local API
		return this.network.getApiHandler( 'local' ).markItemsRead( source, itemIds, isRead );
	};

	/**
	 * Mark all notifications for a given type as read in the given source.
	 *
	 * @param {string} source Symbolic name of notifications source
	 * @param {string} type Notifications type
	 * @return {jQuery.Promise} A promise that is resolved when the operation
	 *  is complete, with the number of unread notifications still remaining
	 *  for that type in the given source
	 */
	mw.echo.api.EchoApi.prototype.markAllRead = function ( source, type ) {
		// markasread is proxied via the local API
		return this.network.getApiHandler( 'local' ).markAllRead( source, type );
	};

	/**
	 * Fetch the number of unread notifications for the given type in the given
	 * source.
	 *
	 * @param {string} source Notifications source
	 * @param {string} type Notification type
	 * @param {boolean} [localOnly] Fetches only the count of local notifications,
	 *  and ignores cross-wiki notifications.
	 * @return {jQuery.Promise} A promise that is resolved with the number of
	 *  unread notifications for the given type and source.
	 */
	mw.echo.api.EchoApi.prototype.fetchUnreadCount = function ( source, type, localOnly ) {
		return this.network.getApiHandler( source ).fetchUnreadCount( type, localOnly );
	};

	/**
	 * Update the seenTime property for the given type.
	 * We only need to update this in a single source for the seenTime
	 * to be updated globally - but we will let the consumer of
	 * this method override the choice of which source to update.
	 *
	 * @param {string} [type='alert,message'] Notification type
	 * @param {string} [source='local'] Notification source
	 * @return {jQuery.Promise} A promise that is resolved when the operation is complete.
	 */
	mw.echo.api.EchoApi.prototype.updateSeenTime = function ( type, source ) {
		source = source || 'local';
		type = type || [ 'alert', 'message' ];

		return this.network.getApiHandler( source ).updateSeenTime( type );
	};

	/**
	 * Send a general query to the API. This is mostly for dynamic actions
	 * where other extensions may set up API actions that are unique and
	 * unanticipated.
	 *
	 * @param {Object} params API parameters
	 * @param {string} [source='local'] Requested source to query
	 * @return {jQuery.Promise} Promise that is resolved when the action
	 *  is complete
	 */
	mw.echo.api.EchoApi.prototype.queryAPI = function ( params, source ) {
		source = source || 'local';
		return this.network.getApiHandler( source )
			.queryAPI( params );
	};

	/**
	 * Check whether the API promise for fetch notification is in an error
	 * state for the given source and notification type.
	 *
	 * @param {string} source Notification source.
	 * @param {string} type Notification type
	 * @return {boolean} The API response for fetching notification has
	 *  resolved in an error state, or is rejected.
	 */
	mw.echo.api.EchoApi.prototype.isFetchingErrorState = function ( source, type ) {
		return this.network.getApiHandler( source ).isFetchingErrorState( type, [ source ] );
	};

	/**
	 * Get the fetch notifications promise active for the current source and type.
	 *
	 * @param {string} source Notification source.
	 * @param {string} type Notification type
	 * @return {jQuery.Promise} Promise that is resolved when notifications are
	 *  fetched from the API.
	 */
	mw.echo.api.EchoApi.prototype.getFetchNotificationPromise = function ( source, type ) {
		return this.network.getApiHandler( source ).getFetchNotificationPromise( type );
	};

	/**
	 * Get the set limit for fetching notifications per request
	 *
	 * @return {number} Limit of notifications per request
	 */
	mw.echo.api.EchoApi.prototype.getLimit = function () {
		return this.limit;
	};
}() );
