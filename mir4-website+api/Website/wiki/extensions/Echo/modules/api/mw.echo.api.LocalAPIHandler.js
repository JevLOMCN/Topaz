( function () {
	/**
	 * Notification API handler
	 *
	 * @class
	 * @extends mw.echo.api.APIHandler
	 *
	 * @constructor
	 * @param {Object} [config] Configuration object
	 */
	mw.echo.api.LocalAPIHandler = function MwEchoApiLocalAPIHandler( config ) {
		// Parent constructor
		mw.echo.api.LocalAPIHandler.super.call( this,
			new mw.Api( { ajax: { cache: false } } ),
			config
		);
	};

	/* Setup */

	OO.inheritClass( mw.echo.api.LocalAPIHandler, mw.echo.api.APIHandler );

	/**
	 * @inheritdoc
	 */
	mw.echo.api.LocalAPIHandler.prototype.fetchNotifications = function ( type, source, isForced, overrideParams ) {
		if ( overrideParams ) {
			return this.createNewFetchNotificationPromise( type, source, overrideParams );
		} else if ( isForced || this.isFetchingErrorState( type, source ) ) {
			// Force new promise
			return this.createNewFetchNotificationPromise( type, source, overrideParams );
		}

		return this.getFetchNotificationPromise( type, source, overrideParams );
	};

	/**
	 * @inheritdoc
	 */
	mw.echo.api.LocalAPIHandler.prototype.updateSeenTime = function ( type ) {
		type = Array.isArray( type ) ? type : [ type ];

		// This is a GET request, not a POST request, for multi-DC support (see T222851)
		return this.api.get( {
			action: 'echomarkseen',
			type: type.length === 1 ? type[ 0 ] : 'all',
			timestampFormat: 'ISO_8601'
		} )
			.then( function ( data ) {
				return data.query.echomarkseen.timestamp;
			} );
	};

	/**
	 * @inheritdoc
	 */
	mw.echo.api.LocalAPIHandler.prototype.markAllRead = function ( source, type ) {
		var data = {
			action: 'echomarkread'
		};
		type = Array.isArray( type ) ? type : [ type ];
		if ( type.indexOf( 'all' ) !== -1 ) {
			// As specified in the documentation of the parent function, the type parameter can be
			// 'all'. We especially handle that case here to match the PHP API. Note: Other values
			// of the array will be ignored.
			data.all = true;
		} else {
			data.sections = type;
		}
		if ( !this.isSourceLocal( source ) ) {
			data.wikis = source;
		}

		return this.api.postWithToken( 'csrf', data )
			.then( function ( result ) {
				return OO.getProp( result.query, 'echomarkread', type, 'rawcount' ) || 0;
			} );
	};

	/**
	 * @inheritdoc
	 */
	mw.echo.api.LocalAPIHandler.prototype.markItemsRead = function ( source, itemIdArray, isRead ) {
		var data = {
			action: 'echomarkread'
		};

		if ( !this.isSourceLocal( source ) ) {
			data.wikis = source;
		}

		if ( isRead ) {
			data.list = itemIdArray;
		} else {
			data.unreadlist = itemIdArray;
		}

		return this.api.postWithToken( 'csrf', data );
	};

	/**
	 * Fetch the number of unread notifications.
	 *
	 * @param {string} type Notification type, 'alert', 'message' or 'all'
	 * @param {boolean} [ignoreCrossWiki] Ignore cross-wiki notifications when fetching the count.
	 *  If set to false (by default) it counts notifications across all wikis.
	 * @return {jQuery.Promise} Promise which resolves with the unread count
	 */
	mw.echo.api.LocalAPIHandler.prototype.fetchUnreadCount = function ( type, ignoreCrossWiki ) {
		var normalizedType = this.normalizedType[ type ],
			apiData = {
				action: 'query',
				meta: 'notifications',
				notsections: normalizedType,
				notgroupbysection: 1,
				notmessageunreadfirst: 1,
				notlimit: this.limit,
				notprop: 'count',
				uselang: this.userLang
			};

		if ( !ignoreCrossWiki ) {
			apiData.notcrosswikisummary = 1;
		}

		return this.api.get( apiData )
			.then( function ( result ) {
				if ( type === 'message' || type === 'alert' ) {
					return OO.getProp( result.query, 'notifications', normalizedType, 'rawcount' ) || 0;
				} else {
					return OO.getProp( result.query, 'notifications', 'rawcount' ) || 0;
				}
			} );
	};

	/**
	 * @inheritdoc
	 */
	mw.echo.api.LocalAPIHandler.prototype.getTypeParams = function ( type ) {
		return $.extend( {}, this.typeParams[ type ], {
			notcrosswikisummary: 1
		} );
	};
}() );
