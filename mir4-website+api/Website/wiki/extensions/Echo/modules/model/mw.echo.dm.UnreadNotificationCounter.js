( function () {
	/**
	 * Echo notification UnreadNotificationCounter model
	 *
	 * @class
	 * @mixins OO.EventEmitter
	 *
	 * @constructor
	 * @param {Object} api An instance of EchoAPI.
	 * @param {string} type The notification type 'message', 'alert', or 'all'.
	 * @param {number} max Maximum number supported. Above this number there is no precision, we only know it is 'more than max'.
	 * @param {Object} config Configuration object
	 * @cfg {boolean} [localOnly=false] The update only takes into account
	 *  local notifications and ignores the number of cross-wiki notifications.
	 * @cfg {string} [source='local'] The source for this counter. Specifically important if the counter
	 *  is set to be counting only local notifications
	 */
	mw.echo.dm.UnreadNotificationCounter = function mwEchoDmUnreadNotificationCounter( api, type, max, config ) {
		config = config || {};

		// Mixin constructor
		OO.EventEmitter.call( this );

		this.api = api;
		this.type = type;
		this.max = max;
		this.prioritizer = new mw.echo.api.PromisePrioritizer();

		this.count = 0;
		this.localOnly = config.localOnly === undefined ? false : !!config.localOnly;
		this.source = config.source || 'local';
	};

	/* Inheritance */

	OO.mixinClass( mw.echo.dm.UnreadNotificationCounter, OO.EventEmitter );

	/* Events */

	/**
	 * @event countChange
	 * @param {number} count Notification count
	 *
	 * The number of unread notification represented by this counter has changed.
	 */

	/* Methods */

	/**
	 * Normalizes for a capped count in case the requested count
	 * is higher than the cap.
	 *
	 * This is the client-side version of
	 * NotificationController::getCappedNotificationCount.
	 *
	 * @param {number} count Count before cap is applied
	 * @return {number} Count with cap applied
	 */
	mw.echo.dm.UnreadNotificationCounter.prototype.getCappedNotificationCount = function ( count ) {
		if ( count < 0 ) {
			return 0;
		} else if ( count <= this.max ) {
			return count;
		} else {
			return this.max + 1;
		}
	};

	/**
	 * Get the current count
	 *
	 * @return {number} current count
	 */
	mw.echo.dm.UnreadNotificationCounter.prototype.getCount = function () {
		return this.count;
	};

	/**
	 * Set the current count
	 *
	 * @param {number} count
	 * @param {boolean} isEstimation Whether this number is estimated or accurate
	 */
	mw.echo.dm.UnreadNotificationCounter.prototype.setCount = function ( count, isEstimation ) {
		if ( isEstimation ) {
			if ( this.count > this.max ) {
				// this prevents toggling between 90-ish and 99+
				return;
			}
			if ( count < 0 ) {
				// wrong estimation?
				return;
			}
		}

		// Normalize
		count = this.getCappedNotificationCount( count );

		if ( count !== this.count ) {
			this.count = count;
			this.emit( 'countChange', this.count );
		}
	};

	/**
	 * Report an estimated change to this counter
	 *
	 * @param {number} delta
	 */
	mw.echo.dm.UnreadNotificationCounter.prototype.estimateChange = function ( delta ) {
		this.setCount( this.count + delta, true );
	};

	/**
	 * Request that this counter update itself from the API
	 *
	 * @return {jQuery.Promise} Promise that is resolved when the actual unread
	 *  count is fetched, with the actual unread notification count.
	 */
	mw.echo.dm.UnreadNotificationCounter.prototype.update = function () {
		var model = this;

		if ( !this.api ) {
			return $.Deferred().reject();
		}

		return this.prioritizer.prioritize( this.api.fetchUnreadCount(
			this.source,
			this.type,
			this.localOnly
		) ).then( function ( actualCount ) {
			model.setCount( actualCount, false );

			return actualCount;
		} );
	};

	/**
	 * Set the source for this counter
	 *
	 * @param {string} source Source name
	 */
	mw.echo.dm.UnreadNotificationCounter.prototype.setSource = function ( source ) {
		this.source = source;
	};

}() );
