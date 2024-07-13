( function () {
	/**
	 * Placeholder notification option widget for echo popup.
	 *
	 * @class
	 * @extends OO.ui.Widget
	 * @mixins OO.ui.mixin.LabelElement
	 *
	 * @constructor
	 * @param {Object} [config] Configuration object
	 * @cfg {string} [link] A link that this widget leads to.
	 */
	mw.echo.ui.PlaceholderItemWidget = function MwEchoUiPlaceholderItemWidget( config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.PlaceholderItemWidget.super.call( this, $.extend( { data: null }, config ) );

		// Mixin constructor
		OO.ui.mixin.LabelElement.call( this, config );

		this.$element.addClass( 'mw-echo-ui-placeholderItemWidget' );

		this.setLink( config.link );
	};

	OO.inheritClass( mw.echo.ui.PlaceholderItemWidget, OO.ui.Widget );
	OO.mixinClass( mw.echo.ui.PlaceholderItemWidget, OO.ui.mixin.LabelElement );

	/**
	 * Set (or unset) the main link url for this widget
	 *
	 * @param {string} [url] The widget url
	 */
	mw.echo.ui.PlaceholderItemWidget.prototype.setLink = function ( url ) {
		if ( url ) {
			var $link = $( '<a>' )
				.addClass( 'mw-echo-ui-placeholderItemWidget-link' )
				.attr( 'href', url );
			this.$element.html( $link.append( this.$label ) );
		} else {
			this.$element.html( this.$label );
		}
	};

	/**
	 * Return false on 'isRead' call for the notification list
	 * sorting.
	 *
	 * @return {boolean} false
	 */
	mw.echo.ui.PlaceholderItemWidget.prototype.isRead = function () {
		return false;
	};

	/**
	 * Return false on 'isForeign' call for the notification list
	 * sorting.
	 *
	 * @return {boolean} false
	 */
	mw.echo.ui.PlaceholderItemWidget.prototype.isForeign = function () {
		return false;
	};

	/**
	 * Return 0 on getTimestamp call for the notification list
	 * sorting.
	 *
	 * @return {number} 0
	 */
	mw.echo.ui.PlaceholderItemWidget.prototype.getTimestamp = function () {
		return 0;
	};

	/**
	 * Return 0 on getId call for the notification list
	 * sorting.
	 *
	 * @return {number} 0
	 */
	mw.echo.ui.PlaceholderItemWidget.prototype.getId = function () {
		return 0;
	};

	/**
	 * Do nothing for resetInitiallyUnseen since it is requested by the list widget
	 */
	mw.echo.ui.PlaceholderItemWidget.prototype.resetInitiallyUnseen = function () {};

}() );
