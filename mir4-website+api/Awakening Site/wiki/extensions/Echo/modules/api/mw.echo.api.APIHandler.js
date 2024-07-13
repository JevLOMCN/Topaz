( function () {
	/**
	 * Abstract notification API handler
	 *
	 * @abstract
	 * @class
	 *
	 * @constructor
	 * @param {mw.Api} api
	 * @param {Object} [config] Configuration object
	 * @cfg {number} [limit=25] The limit on how many notifications to fetch
	 * @cfg {string} [userLang=mw.config.get( 'wgUserLanguage' )] User language. Defaults
	 *  to the default user language configuration settings.
	 */
	mw.echo.api.APIHandler = function MwEchoApiAPIHandler( api, config ) {
		config = config || {};

		this.fetchNotificationsPromise = {};
		this.apiErrorState = {};

		this.limit = config.limit || 25;
		this.userLang = config.userLang || mw.config.get( 'wgUserLanguage' );

		this.api = api;

		// Map the logical type to the type
		// that the API recognizes
		this.normalizedType = {
			message: 'message',
			alert: 'alert',
			all: 'message|alert'
		};

		// Parameters that are sent through
		// to the 'fetch notification' promise
		// per type
		this.typeParams = {
			message: {},
			alert: {},
			all: {}
		};
	};

	/* Setup */

	OO.initClass( mw.echo.api.APIHandler );

	/**
	 * Fetch notifications from the API.
	 *
	 * @param {string} type Notification type
	 * @param {Object} [overrideParams] An object defining parameters to override in the API
	 *  fetching call.
	 * @return {jQuery.Promise} A promise that resolves with an object containing the
	 *  notification items
	 */
	mw.echo.api.APIHandler.prototype.fetchNotifications = null;

	/**
	 * Send a general query to the API. This is mostly for dynamic actions
	 * where other extensions may set up API actions that are unique and
	 * unanticipated.
	 *
	 * @param {Object} data Data object about the operation.
	 * @param {string} [data.tokenType=csrf] Token type, 'csrf', 'watch', etc
	 * @param {Object} [data.params] Parameters to pass to the API call
	 * @return {jQuery.Promise} Promise that is resolved when the action
	 *  is complete
	 */
	mw.echo.api.APIHandler.prototype.queryAPI = function ( data ) {
		return this.api.postWithToken( data.tokenType || 'csrf', data.params );
	};

	/**
	 * Fetch all pages with unread notifications in them per wiki
	 *
	 * @param {string|string[]} [sources=*] Requested sources. If not given
	 *  or if a '*' is given, all available sources will be queried
	 * @return {jQuery.Promise} Promise that is resolved with an object
	 *  of pages with the number of unread notifications per wiki
	 */
	mw.echo.api.APIHandler.prototype.fetchUnreadNotificationPages = function ( sources ) {
		sources = sources || '*';
		var params = {
			action: 'query',
			meta: 'unreadnotificationpages',
			uselang: this.userLang,
			unpgrouppages: true,
			unpwikis: sources
		};

		return this.api.get( params );
	};

	/**
	 * Check if the given source is local
	 *
	 * @param {string|string[]} sources Source names
	 * @return {boolean} Source is local
	 */
	mw.echo.api.APIHandler.prototype.isSourceLocal = function ( sources ) {
		return Array.isArray( sources ) ?
			(
				sources.indexOf( 'local' ) !== -1 ||
				sources.indexOf( mw.config.get( 'wgWikiID' ) ) !== -1
			) :
			(
				sources === 'local' ||
				sources === mw.config.get( 'wgWikiID' )
			);
	};

	/**
	 * Create a new fetchNotifications promise that queries the API and overrides
	 * the cached promise.
	 *
	 * @param {string} type Notification type
	 * @param {string[]} [sources] An array of sources to query
	 * @param {Object} [overrideParams] An object defining parameters to override in the API
	 *  fetching call.
	 * @return {jQuery.Promise} Promise that is resolved when notifications are
	 *  fetched from the API.
	 */
	mw.echo.api.APIHandler.prototype.createNewFetchNotificationPromise = function ( type, sources, overrideParams ) {
		var fetchingSource = 'local',
			me = this,
			params = $.extend( {
				action: 'query',
				formatversion: 2,
				meta: 'notifications',
				notsections: this.normalizedType[ type ],
				notformat: 'model',
				notlimit: this.limit,
				notprop: 'list|count|seenTime',
				uselang: this.userLang
			}, this.getTypeParams( type ) );

		if ( !this.isSourceLocal( sources ) ) {
			params.notwikis = sources;
			params.notfilter = '!read';
			fetchingSource = 'foreign';
		}

		// Initialize the nested value if it doesn't yet exist
		this.fetchNotificationsPromise[ type ] = this.fetchNotificationsPromise[ type ] || {};
		me.apiErrorState[ type ] = me.apiErrorState[ type ] || {};

		// Reset cached values
		this.fetchNotificationsPromise[ type ][ fetchingSource ] = null;
		this.apiErrorState[ type ][ fetchingSource ] = false;

		// Create the fetch promise
		var fetchNotifPromise = this.api.get( $.extend( true, params, overrideParams ) );

		// Only cache promises that don't have override params in them
		if ( !overrideParams ) {
			this.fetchNotificationsPromise[ type ][ fetchingSource ] = fetchNotifPromise;
		}

		return fetchNotifPromise
			.fail( function () {
				// Mark API error state
				me.apiErrorState[ type ][ fetchingSource ] = true;
			} );
	};

	/**
	 * Update the seen timestamp
	 *
	 * @param {string|string[]} [types] Notification type 'message', 'alert' or [ 'message', 'alert' ].
	 * @return {jQuery.Promise} A promise that resolves with the seen timestamp, as a full UTC
	 *   ISO 8601 timestamp.
	 */
	mw.echo.api.APIHandler.prototype.updateSeenTime = null;

	/**
	 * Mark all notifications as read
	 *
	 * @param {string} source Wiki name
	 * @param {string|string[]} type Notification type 'message', 'alert' or 'all'.
	 * @return {jQuery.Promise} A promise that resolves when all notifications
	 *  are marked as read.
	 */
	mw.echo.api.APIHandler.prototype.markAllRead = null;

	/**
	 * Mark multiple notification items as read using specific IDs
	 *
	 * @abstract
	 * @param {string} source Wiki name
	 * @param {string[]} itemIdArray An array of notification item IDs
	 * @param {boolean} [isRead] Item's new read state; true for marking the item
	 *  as read, false for marking the item as unread
	 * @return {jQuery.Promise} A promise that resolves when all given notifications
	 *  are marked as read.
	 */
	mw.echo.api.APIHandler.prototype.markItemsRead = null;

	/**
	 * Update the read status of a notification item in the API
	 *
	 * @param {string} itemId Item id
	 * @param {boolean} [isRead] Item's new read state; true for marking the item
	 *  as read, false for marking the item as unread
	 * @return {jQuery.Promise} A promise that resolves when the notifications
	 *  are marked as read.
	 */
	mw.echo.api.APIHandler.prototype.markItemRead = function ( itemId, isRead ) {
		return this.markItemsRead( [ itemId ], isRead );
	};

	/**
	 * Query the API for unread count of the notifications in this model
	 *
	 * @param {string} type Notification type 'message', 'alert' or 'all'.
	 * @return {jQuery.Promise} jQuery promise that's resolved when the unread count is fetched
	 *  and the badge label is updated.
	 */
	mw.echo.api.APIHandler.prototype.fetchUnreadCount = null;

	/**
	 * Check whether the model has an API error state flagged
	 *
	 * @param {string} type Notification type, 'alert', 'message' or 'all'
	 * @param {string|string[]} sources Source names
	 * @return {boolean} The model is in API error state
	 */
	mw.echo.api.APIHandler.prototype.isFetchingErrorState = function ( type, sources ) {
		var fetchingSource = 'local';

		if ( !this.isSourceLocal( sources ) ) {
			fetchingSource = 'foreign';
		}
		return !!( this.apiErrorState[ type ] && this.apiErrorState[ type ][ fetchingSource ] );
	};

	/**
	 * Return the fetch notifications promise
	 *
	 * @param {string} type Notification type, 'alert', 'message' or 'all'
	 * @param {string|string[]} [sources] A name of a source or an array of sources to query
	 * @param {Object} [overrideParams] An object defining parameters to override in the API
	 *  fetching call.
	 * @return {jQuery.Promise} Promise that is resolved when notifications are
	 *  fetched from the API.
	 */
	mw.echo.api.APIHandler.prototype.getFetchNotificationPromise = function ( type, sources, overrideParams ) {
		var fetchingSource = 'local';

		if ( !this.isSourceLocal( sources ) ) {
			fetchingSource = 'foreign';
		}
		if ( overrideParams || !this.fetchNotificationsPromise[ type ] || !this.fetchNotificationsPromise[ type ][ fetchingSource ] ) {
			this.createNewFetchNotificationPromise( type, sources, overrideParams );
		}
		return this.fetchNotificationsPromise[ type ][ fetchingSource ];
	};

	/**
	 * Get the extra parameters for fetching notifications for a given
	 * notification type.
	 *
	 * @param {string} type Notification type
	 * @return {Object} Extra API parameters for fetch notifications
	 */
	mw.echo.api.APIHandler.prototype.getTypeParams = function ( type ) {
		return this.typeParams[ type ];
	};
}() );
