( function () {
	/* global moment:false */
	/**
	 * A wrapper widget for a fake, cloned notification. This is used
	 * for the fade in/out effects while reordering.
	 *
	 * @class
	 * @extends OO.ui.Widget
	 *
	 * @constructor
	 * @param {jQuery} $element A clone of an mw.echo.ui.NotificationItemWidget's $element
	 * @param {Object} [config] Configuration options
	 * @cfg {string} [timestamp] The timestamp for this cloned widget, in UTC and ISO 8601 format
	 * @cfg {boolean} [read=false] The read state for this cloned widget
	 * @cfg {boolean} [foreign=false] The foreignness state of this cloned widget
	 * @cfg {number} [id] The id for this cloned widget
	 */
	mw.echo.ui.ClonedNotificationItemWidget = function MwEchoUiClonedNotificationItemWidget( $element, config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.ClonedNotificationItemWidget.super.call( this, config );

		this.$element = $element;
		this.timestamp = config.timestamp || moment.utc().format( 'YYYY-MM-DD[T]HH:mm:ss[Z]' );
		this.read = !!config.read;
		this.foreign = config.foreign === undefined ? true : config.foreign;
		this.id = config.id;

		this.$element.addClass( 'mw-echo-ui-clonedNotificationItemWidget' );
	};

	/* Initialization */
	OO.inheritClass( mw.echo.ui.ClonedNotificationItemWidget, OO.ui.Widget );

	/**
	 * Get the widget's timestamp
	 *
	 * @return {string} Timestamp in UTC, ISO 8601 format
	 */
	mw.echo.ui.ClonedNotificationItemWidget.prototype.getTimestamp = function () {
		return this.timestamp;
	};

	/**
	 * Get the widget's read state
	 *
	 * @return {boolean} Widget is read
	 */
	mw.echo.ui.ClonedNotificationItemWidget.prototype.isRead = function () {
		return this.read;
	};

	/**
	 * Get the widget's id
	 *
	 * @return {number} Widget id
	 */
	mw.echo.ui.ClonedNotificationItemWidget.prototype.getId = function () {
		return this.id;
	};

	/**
	 * The foreign state of this widget
	 *
	 * @return {boolean} This item widget is foreign
	 */
	mw.echo.ui.ClonedNotificationItemWidget.prototype.isForeign = function () {
		return this.foreign;
	};

	/**
	 * This widget is fake by definition.
	 *
	 * @return {boolean} true
	 */
	mw.echo.ui.ClonedNotificationItemWidget.prototype.isFake = function () {
		return true;
	};

	/**
	 * No-op
	 */
	mw.echo.ui.ClonedNotificationItemWidget.prototype.resetInitiallyUnseen = function () {
	};

}() );
