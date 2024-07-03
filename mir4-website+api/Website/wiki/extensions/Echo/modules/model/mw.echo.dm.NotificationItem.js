( function () {
	/* global moment:false */
	/**
	 * Notification item data structure.
	 *
	 * @class
	 * @mixins OO.EventEmitter
	 * @mixins OO.SortedEmitterList
	 *
	 * @constructor
	 * @param {number} id Notification id,
	 * @param {Object} [config] Configuration object
	 * @cfg {string} [iconUrl] A URL for the given icon.
	 * @cfg {string} [iconType] A string noting the icon type.
	 * @cfg {Object} [content] The message object defining the text for the header and,
	 *  optionally, the body of the notification.
	 * @cfg {string} [content.header=''] The header text of the notification
	 * @cfg {string} [content.body=''] The body text of the notification
	 * @cfg {string} [category] The category of this notification. The category identifies
	 *  where the notification originates from.
	 * @cfg {string} [type='message'] The notification type 'message' or 'alert'
	 * @cfg {boolean} [read=false] State the read state of the option
	 * @cfg {boolean} [seen=false] State the seen state of the option
	 * @cfg {string} [timestamp] Notification timestamp in ISO 8601 format
	 * @cfg {string} [primaryUrl] Notification primary link in raw url format
	 * @cfg {boolean} [foreign=false] This notification is from a foreign source
	 * @cfg {boolean} [bundled=false] This notification is part of a bundle
	 * @cfg {number[]} [bundledIds] IDs of notifications bundled with this one
	 * @cfg {string} [modelName='local'] The name of the model this item belongs to
	 * @cfg {string} [source] The source this notification is coming from, if it is foreign
	 * @cfg {Object[]} [secondaryUrls] An array of objects defining the secondary URLs
	 *  for this notification. The secondary URLs are expected to have this structure:
	 *    {
	 *      "iconType": "userAvatar", // A symbolic name for the icon.
	 *                                // Will render as oo-ui-icon-* class.
	 *      "label": "", // The label for the link
	 *      "prioritized": true/false, // Prioritized links are outside of the popup
	 *                                 // menu, whenever possible.
	 *      "url": "..." // The url for the secondary link
	 *    }
	 */
	mw.echo.dm.NotificationItem = function MwEchoDmNotificationItem( id, config ) {
		config = config || {};

		// Mixin constructor
		OO.EventEmitter.call( this );

		// Properties
		this.id = id;
		this.modelName = config.modelName || 'local';
		this.content = $.extend( { header: '', body: '' }, config.content );
		this.category = config.category || '';
		this.type = config.type || 'message';
		this.foreign = !!config.foreign;
		this.bundled = !!config.bundled;
		this.source = config.source || '';
		this.iconType = config.iconType;
		this.iconURL = config.iconURL;

		this.read = !!config.read;
		this.seen = !!config.seen;

		this.timestamp = config.timestamp || moment.utc().format( 'YYYY-MM-DD[T]HH:mm:ss[Z]' );
		this.setPrimaryUrl( config.primaryUrl );
		this.setSecondaryUrls( config.secondaryUrls );
		this.bundledIds = config.bundledIds;
	};

	/* Initialization */

	OO.initClass( mw.echo.dm.NotificationItem );
	OO.mixinClass( mw.echo.dm.NotificationItem, OO.EventEmitter );

	/* Events */

	/**
	 * @event update
	 *
	 * Item details have changed or were updated
	 */

	/* Methods */

	/**
	 * Get NotificationItem id
	 *
	 * @return {string} NotificationItem Id
	 */
	mw.echo.dm.NotificationItem.prototype.getId = function () {
		return this.id;
	};

	/**
	 * Get NotificationItem content header
	 *
	 * @return {string} NotificationItem content
	 */
	mw.echo.dm.NotificationItem.prototype.getContentHeader = function () {
		return this.content.header;
	};

	/**
	 * Get NotificationItem content body
	 *
	 * @return {string} NotificationItem content body
	 */
	mw.echo.dm.NotificationItem.prototype.getContentBody = function () {
		return this.content.body;
	};

	/**
	 * Get NotificationItem category
	 *
	 * @return {string} NotificationItem category
	 */
	mw.echo.dm.NotificationItem.prototype.getCategory = function () {
		return this.category;
	};

	/**
	 * Get NotificationItem type
	 *
	 * @return {string} NotificationItem type
	 */
	mw.echo.dm.NotificationItem.prototype.getType = function () {
		return this.type;
	};

	/**
	 * Check whether this notification item is read
	 *
	 * @return {boolean} Notification item is read
	 */
	mw.echo.dm.NotificationItem.prototype.isRead = function () {
		return this.read;
	};

	/**
	 * Check whether this notification item is seen
	 *
	 * @return {boolean} Notification item is seen
	 */
	mw.echo.dm.NotificationItem.prototype.isSeen = function () {
		return this.seen;
	};

	/**
	 * Check whether this notification item is foreign
	 *
	 * @return {boolean} Notification item is foreign
	 */
	mw.echo.dm.NotificationItem.prototype.isForeign = function () {
		return this.foreign;
	};

	/**
	 * Check whether this notification item is part of a bundle
	 *
	 * @return {boolean} Notification item is part of a bundle
	 */
	mw.echo.dm.NotificationItem.prototype.isBundled = function () {
		return this.bundled;
	};

	/**
	 * Set this notification item as foreign
	 *
	 * @param {boolean} isForeign Notification item is foreign
	 */
	mw.echo.dm.NotificationItem.prototype.setForeign = function ( isForeign ) {
		this.foreign = isForeign;
	};

	/**
	 * Toggle the read state of the widget
	 *
	 * @param {boolean} [read] The current read state. If not given, the state will
	 *  become the opposite of its current state.
	 * @fires update
	 */
	mw.echo.dm.NotificationItem.prototype.toggleRead = function ( read ) {
		read = read !== undefined ? read : !this.read;
		if ( this.read !== read ) {
			this.read = read;
			this.emit( 'update' );
			this.emit( 'sortChange' );
		}
	};

	/**
	 * Toggle the seen state of the widget
	 *
	 * @param {boolean} [seen] The current seen state. If not given, the state will
	 *  become the opposite of its current state.
	 * @fires update
	 */
	mw.echo.dm.NotificationItem.prototype.toggleSeen = function ( seen ) {
		seen = seen !== undefined ? seen : !this.seen;
		if (
			this.seen !== seen &&
			// Do not change the state of a read item, since its
			// seen state (never 'unseen') never changes
			!this.isRead()
		) {
			this.seen = seen;
			this.emit( 'update' );
		}
	};

	/**
	 * Get the notification timestamp
	 *
	 * @return {number} Notification timestamp in MediaWiki timestamp format
	 */
	mw.echo.dm.NotificationItem.prototype.getTimestamp = function () {
		return this.timestamp;
	};

	/**
	 * Set the notification link
	 *
	 * @param {string} link Notification url
	 */
	mw.echo.dm.NotificationItem.prototype.setPrimaryUrl = function ( link ) {
		this.primaryUrl = link;
	};

	/**
	 * Get the notification link
	 *
	 * @return {string} Notification url
	 */
	mw.echo.dm.NotificationItem.prototype.getPrimaryUrl = function () {
		return this.primaryUrl;
	};

	/**
	 * Get the notification icon URL
	 *
	 * @return {string} Notification icon URL
	 */
	mw.echo.dm.NotificationItem.prototype.getIconURL = function () {
		return this.iconURL;
	};

	/**
	 * Get the notification icon type
	 *
	 * @return {string} Notification icon type
	 */
	mw.echo.dm.NotificationItem.prototype.getIconType = function () {
		return this.iconType;
	};

	/**
	 * Set the notification's secondary links
	 * See constructor documentation for the structure of these links objects.
	 *
	 * @param {Object[]} links Secondary url definitions
	 */
	mw.echo.dm.NotificationItem.prototype.setSecondaryUrls = function ( links ) {
		this.secondaryUrls = links || [];
	};

	/**
	 * Get the notification's secondary links
	 *
	 * @return {Object[]} Secondary url definitions
	 */
	mw.echo.dm.NotificationItem.prototype.getSecondaryUrls = function () {
		return this.secondaryUrls;
	};

	/**
	 * Get the notification's source
	 *
	 * @return {string} Notification source
	 */
	mw.echo.dm.NotificationItem.prototype.getSource = function () {
		return this.source;
	};

	/**
	 * Get the notification's model name
	 *
	 * @return {string} Notification model name
	 */
	mw.echo.dm.NotificationItem.prototype.getModelName = function () {
		return this.modelName;
	};

	/**
	 * Get the all ids contained in this notification
	 *
	 * @return {number[]}
	 */
	mw.echo.dm.NotificationItem.prototype.getAllIds = function () {
		return [ this.getId() ].concat( this.bundledIds || [] );
	};

}() );
