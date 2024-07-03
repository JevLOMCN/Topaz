( function () {
	/**
	 * Foreign notification API handler
	 *
	 * @class
	 * @extends mw.echo.api.LocalAPIHandler
	 *
	 * @constructor
	 * @param {string} apiUrl A url for the access point of the
	 *  foreign API.
	 * @param {Object} [config] Configuration object
	 * @cfg {boolean} [unreadOnly] Whether this handler should request unread
	 *  notifications by default.
	 */
	mw.echo.api.ForeignAPIHandler = function MwEchoApiForeignAPIHandler( apiUrl, config ) {
		config = config || {};

		// Parent constructor
		mw.echo.api.ForeignAPIHandler.super.call( this, config );

		this.api = new mw.ForeignApi( apiUrl );
		this.unreadOnly = config.unreadOnly !== undefined ? !!config.unreadOnly : false;
	};

	/* Setup */

	OO.inheritClass( mw.echo.api.ForeignAPIHandler, mw.echo.api.LocalAPIHandler );

	/**
	 * @inheritdoc
	 */
	mw.echo.api.ForeignAPIHandler.prototype.getTypeParams = function ( type ) {
		var params = {
			// Backwards compatibility
			notforn: 1
		};

		if ( this.unreadOnly ) {
			params = $.extend( {}, params, { notfilter: '!read' } );
		}

		return $.extend( {}, this.typeParams[ type ], params );
	};
}() );
