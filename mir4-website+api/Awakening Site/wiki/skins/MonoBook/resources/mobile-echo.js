/* eslint-disable no-jquery/no-global-selector */
module.exports = function () {
	var mobileMediaQuery = window.matchMedia( 'screen and (max-width: 550px)' ),
		echoHacked = false,
		echoHackActive = false,
		notifications = $( '#pt-notifications-alert a' ).data( 'counter-num' ) + $( '#pt-notifications-notice a' ).data( 'counter-num' ),
		notificationsString;

	// When the icons are clicked for the first time, they are replaced with a JS interface,
	// so don't cache this in a long-lived variable
	function getNotificationIcons() {
		return $( '#pt-notifications-alert, #pt-notifications-notice' );
	}

	// Move echo badges in/out of p-personal
	function monoBookMobileMoveEchoIcons() {
		var $notificationIcons = getNotificationIcons();
		if ( $notificationIcons.length ) {
			if ( !echoHackActive && mobileMediaQuery.matches ) {
				$( '#echo-hack-badges' ).append( $notificationIcons );

				echoHackActive = true;
			} else if ( echoHackActive && !mobileMediaQuery.matches ) {
				$( $notificationIcons ).insertBefore( '#pt-mytalk' );

				echoHackActive = false;
			}
		}
	}

	function monoBookMobileEchoHack() {
		var $notificationIcons = getNotificationIcons();
		if ( $notificationIcons.length ) {
			if ( !echoHacked && mobileMediaQuery.matches ) {
				if ( notifications ) {
					notificationsString = mw.msg( 'monobook-notifications-link', notifications );
				} else {
					notificationsString = mw.msg( 'monobook-notifications-link-none' );
				}

				// add inline p-personal echo link
				mw.util.addPortletLink(
					'p-personal',
					mw.util.getUrl( 'Special:Notifications' ),
					notificationsString,
					'pt-notifications',
					$( '#pt-notifications-notice' ).attr( 'tooltip' ),
					null,
					'#pt-preferences'
				);

				$( '#p-personal-toggle' ).append( $( '<ul>' ).attr( 'id', 'echo-hack-badges' ) );

				echoHacked = true;
			}

			monoBookMobileMoveEchoIcons();
		}
	}

	$( window ).on( 'resize', monoBookMobileEchoHack );
	monoBookMobileEchoHack();
};
