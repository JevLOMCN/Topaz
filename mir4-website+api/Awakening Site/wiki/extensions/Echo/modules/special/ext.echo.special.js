( function () {
	'use strict';

	/*!
	 * Echo Special:Notifications page initialization
	 */
	$( function () {
		var limitNotifications = 50,
			links = mw.config.get( 'wgNotificationsSpecialPageLinks' ),
			// FIXME: Use CSS transition
			// eslint-disable-next-line no-jquery/no-global-selector
			$content = $( '#mw-content-text' ),
			echoApi = new mw.echo.api.EchoApi( { limit: limitNotifications } ),
			unreadCounter = new mw.echo.dm.UnreadNotificationCounter( echoApi, [ 'message', 'alert' ], limitNotifications ),
			modelManager = new mw.echo.dm.ModelManager( unreadCounter, {
				type: [ 'message', 'alert' ],
				itemsPerPage: limitNotifications,
				readState: mw.config.get( 'wgEchoReadState' ),
				localCounter: new mw.echo.dm.UnreadNotificationCounter(
					echoApi,
					[ 'message', 'alert' ],
					limitNotifications,
					{
						localOnly: true,
						source: 'local'
					}
				)
			} ),
			controller = new mw.echo.Controller( echoApi, modelManager );

		// Set default max prioritized action links per item.
		// For general purpose we have 2, for mobile only 1
		mw.echo.config.maxPrioritizedActions = mw.config.get( 'skin' ) === 'minerva' ? 1 : 2;

		var specialPageContainer = new mw.echo.ui.NotificationsInboxWidget(
			controller,
			modelManager,
			{
				limit: limitNotifications,
				$overlay: mw.echo.ui.$overlay,
				prefLink: links.preferences
			}
		);

		// Overlay
		$( document.body ).append( mw.echo.ui.$overlay );

		// Notifications
		$content.empty().append( specialPageContainer.$element );
	} );
}() );
