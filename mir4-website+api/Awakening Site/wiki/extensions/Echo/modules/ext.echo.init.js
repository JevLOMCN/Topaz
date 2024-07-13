/* eslint-disable no-jquery/no-global-selector */
mw.echo = mw.echo || {};
mw.echo.config = mw.echo.config || {};
// Set default max prioritized action links per item
mw.echo.config.maxPrioritizedActions = 2;

/**
 * Initialise desktop Echo experience
 */
function initDesktop() {
	'use strict';

	// Remove ?markasread=XYZ from the URL
	var url = new URL( location.href );
	if ( url.searchParams.has( 'markasread' ) ) {
		url.searchParams.delete( 'markasread' );
		url.searchParams.delete( 'markasreadwiki' );
		history.replaceState( null, '', url );
	}

	// Activate OOUI
	$( function () {
		var messageController,
			alertController,
			maxNotificationCount = require( './config.json' ).EchoMaxNotificationCount,
			pollingRate = require( './config.json' ).EchoPollForUpdates,
			documentTitle = document.title,
			$existingAlertLink = $( '#pt-notifications-alert a' ),
			$existingMessageLink = $( '#pt-notifications-notice a' ),
			numAlerts = $existingAlertLink.attr( 'data-counter-num' ),
			numMessages = $existingMessageLink.attr( 'data-counter-num' ),
			badgeLabelAlerts = $existingAlertLink.attr( 'data-counter-text' ),
			badgeLabelMessages = $existingMessageLink.attr( 'data-counter-text' ),
			// eslint-disable-next-line no-jquery/no-class-state
			hasUnseenAlerts = $existingAlertLink.hasClass( 'mw-echo-unseen-notifications' ),
			// eslint-disable-next-line no-jquery/no-class-state
			hasUnseenMessages = $existingMessageLink.hasClass( 'mw-echo-unseen-notifications' ),
			// latestMessageNotifTime is the time of most recent notification that came when we called showNotificationSnippet last
			// the function showNotificationSnippet returns the time of the latest notification and latestMessageNotifTime is updated
			latestMessageNotifTime = new Date(),
			latestAlertNotifTime = new Date(),
			alertCount = parseInt( numAlerts ),
			messageCount = parseInt( numMessages ),
			loadingPromise = null,
			// Store links
			links = {
				notifications: $existingAlertLink.attr( 'href' ) || mw.util.getUrl( 'Special:Notifications' ),
				preferences: ( $( '#pt-preferences a' ).attr( 'href' ) || mw.util.getUrl( 'Special:Preferences' ) ) +
					'#mw-prefsection-echo'
			};

		function updateDocumentTitleWithNotificationCount( totalAlertCount, totalMessageCount ) {
			var totalCount = totalAlertCount + totalMessageCount,
				newTitle = documentTitle;

			if ( totalCount > 0 ) {
				var convertedTotalCount = totalCount <= maxNotificationCount ? totalCount : maxNotificationCount + 1;
				convertedTotalCount = mw.msg( 'echo-badge-count', mw.language.convertNumber( convertedTotalCount ) );
				newTitle = mw.msg( 'parentheses', convertedTotalCount ) + ' ' + documentTitle;
			}
			document.title = newTitle;
		}

		/**
		 * Show notification snippet via mw.notify of notifications which came after highestNotifTime.
		 *
		 * @param {mw.echo.dm.ModelManager} modelManager
		 * @param {Date} highestNotifTime Timestamp of latest notification the last time function was called
		 * @return {Date} Timestamp of latest notification
		 */
		function showNotificationSnippet( modelManager, highestNotifTime ) {
			var highestTime = highestNotifTime;
			modelManager.getLocalNotifications().forEach( function ( notificationItem ) {
				var timestampAsDate = new Date( notificationItem.timestamp );
				if ( timestampAsDate > highestNotifTime ) {
					if ( timestampAsDate > highestTime ) {
						highestTime = timestampAsDate;
					}
					if ( !notificationItem.seen ) {
						mw.notify( $.parseHTML( notificationItem.content.header ), { title: mw.msg( 'echo-displaysnippet-title' ) } );
					}
				}
			}
			);
			return highestTime;
		}

		/**
		 * Change the seen state of badges if there are any unseen notifications.
		 *
		 * @param {mw.echo.dm.ModelManager} modelManager
		 * @param {mw.echo.ui.NotificationBadgeWidget} badgeWidget
		 */
		function updateBadgeState( modelManager, badgeWidget ) {
			modelManager.getLocalNotifications().forEach( function ( notificationItem ) {
				if ( !notificationItem.isSeen() ) {
					badgeWidget.updateBadgeSeenState( true );
				}
			} );
		}

		function isLivePollingFeatureEnabledOnWiki() {
			return pollingRate !== 0;
		}

		/**
		 * User has opted in to preference to show notification snippets and update document title with unread count.
		 *
		 * Only useful when isLivePollingFeatureEnabledOnWiki() returns true.
		 *
		 * @return {boolean} User preference
		 */
		function userHasOptedInToLiveNotifications() {
			return mw.user.options.get( 'echo-show-poll-updates' ) === '1';
		}

		// Change document title on initialization only when polling rate feature flag is non-zero.
		if ( isLivePollingFeatureEnabledOnWiki() && userHasOptedInToLiveNotifications() ) {
			updateDocumentTitleWithNotificationCount( alertCount, messageCount );
		}

		function loadEcho() {
			if ( loadingPromise !== null ) {
				return loadingPromise;
			}
			// This part executes only once, either when header icons are clicked or after completion of 60secs whichever occur first.
			var echoApi = new mw.echo.api.EchoApi();

			loadingPromise = mw.loader.using( 'ext.echo.ui.desktop' ).then( function () {

				// Overlay
				mw.echo.ui.$overlay.appendTo( document.body );

				var unreadAlertCounter = new mw.echo.dm.UnreadNotificationCounter( echoApi, 'alert', maxNotificationCount );
				var alertModelManager = new mw.echo.dm.ModelManager( unreadAlertCounter, { type: 'alert' } );
				alertController = new mw.echo.Controller( echoApi, alertModelManager );

				mw.echo.ui.alertWidget = new mw.echo.ui.NotificationBadgeWidget(
					alertController,
					alertModelManager,
					links,
					{
						numItems: Number( numAlerts ),
						convertedNumber: badgeLabelAlerts,
						hasUnseen: hasUnseenAlerts,
						badgeIcon: 'bell',
						$overlay: mw.echo.ui.$overlay,
						href: $existingAlertLink.attr( 'href' )
					}
				);

				// Replace the link button with the ooui button
				$existingAlertLink.parent().replaceWith( mw.echo.ui.alertWidget.$element );

				alertModelManager.on( 'allTalkRead', function () {
					// If there was a talk page notification, get rid of it
					$( '#pt-talk-alert' ).remove();
				} );

				// listen to event countChange and change title only if polling rate is non-zero
				if ( isLivePollingFeatureEnabledOnWiki() ) {
					alertModelManager.getUnreadCounter().on( 'countChange', function ( count ) {
						alertController.fetchLocalNotifications().then( function () {
							updateBadgeState( alertModelManager, mw.echo.ui.alertWidget );
							if ( userHasOptedInToLiveNotifications() ) {
								latestAlertNotifTime = showNotificationSnippet( alertModelManager, latestAlertNotifTime );
								alertCount = count;
								updateDocumentTitleWithNotificationCount( count, messageCount );
							}
						} );
					} );
				}

				// Load message button and popup if messages exist
				if ( $existingMessageLink.length ) {
					var unreadMessageCounter = new mw.echo.dm.UnreadNotificationCounter( echoApi, 'message', maxNotificationCount );
					var messageModelManager = new mw.echo.dm.ModelManager( unreadMessageCounter, { type: 'message' } );
					messageController = new mw.echo.Controller( echoApi, messageModelManager );

					mw.echo.ui.messageWidget = new mw.echo.ui.NotificationBadgeWidget(
						messageController,
						messageModelManager,
						links,
						{
							$overlay: mw.echo.ui.$overlay,
							numItems: Number( numMessages ),
							hasUnseen: hasUnseenMessages,
							badgeIcon: 'tray',
							convertedNumber: badgeLabelMessages,
							href: $existingMessageLink.attr( 'href' )
						}
					);

					// Replace the link button with the ooui button
					$existingMessageLink.parent().replaceWith( mw.echo.ui.messageWidget.$element );

					// listen to event countChange and change title only if polling rate is non-zero
					if ( isLivePollingFeatureEnabledOnWiki() ) {
						messageModelManager.getUnreadCounter().on( 'countChange', function ( count ) {
							messageController.fetchLocalNotifications().then( function () {
								updateBadgeState( messageModelManager, mw.echo.ui.messageWidget );
								if ( userHasOptedInToLiveNotifications() ) {
									latestMessageNotifTime = showNotificationSnippet( messageModelManager, latestMessageNotifTime );
									messageCount = count;
									updateDocumentTitleWithNotificationCount( alertCount, count );
								}
							} );
						} );
					}
				}
			} );
			return loadingPromise;
		}

		// Respond to click on the notification button and load the UI on demand
		$( '.mw-echo-notification-badge-nojs' ).on( 'click', function ( e ) {
			var timeOfClick = mw.now(),
				$badge = $( this ),
				clickedSection = $badge.parent().prop( 'id' ) === 'pt-notifications-alert' ? 'alert' : 'message';
			if ( e.which !== 1 || $badge.data( 'clicked' ) ) {
				// Do not return false (as that calls stopPropagation)
				e.preventDefault();
				return;
			}

			$badge.data( 'clicked', true );

			// Dim the badge while we load
			$badge.addClass( 'mw-echo-notifications-badge-dimmed' );

			// Fire the notification API requests
			var echoApi = new mw.echo.api.EchoApi();
			echoApi.fetchNotifications( clickedSection )
				.then( function ( data ) {
					mw.track( 'timing.MediaWiki.echo.overlay.api', mw.now() - timeOfClick );
					return data;
				} );

			loadEcho().then( function () {
				// Now that the module loaded, show the popup
				var selectedWidget = clickedSection === 'alert' ? mw.echo.ui.alertWidget : mw.echo.ui.messageWidget;
				selectedWidget.once( 'finishLoading', function () {
					// Log timing after notifications are shown
					mw.track( 'timing.MediaWiki.echo.overlay', mw.now() - timeOfClick );
				} );
				selectedWidget.popup.toggle( true );
				mw.track( 'timing.MediaWiki.echo.overlay.ooui', mw.now() - timeOfClick );

				if ( hasUnseenAlerts || hasUnseenMessages ) {
					// Clicked on the flyout due to having unread notifications
					// This is part of tracking how likely users are to click a badge with unseen notifications.
					// The other part is the 'echo.unseen' counter, see EchoHooks::onSkinTemplateNavigationUniversal().
					mw.track( 'counter.MediaWiki.echo.unseen.click' );
				}
			}, function () {
				// Un-dim badge if loading failed
				$badge.removeClass( 'mw-echo-notifications-badge-dimmed' );
			} );
			// Prevent default. Do not return false (as that calls stopPropagation)
			e.preventDefault();
		} );

		function pollForNotificationCountUpdates() {
			alertController.refreshUnreadCount();
			messageController.refreshUnreadCount();
			// Make notification update after n*pollingRate(time in secs) where n depends on document.hidden
			setTimeout( pollForNotificationCountUpdates, ( document.hidden ? 5 : 1 ) * pollingRate * 1000 );
		}

		function pollStart() {
			if ( mw.config.get( 'skin' ) !== 'minerva' && isLivePollingFeatureEnabledOnWiki() ) {
				// load widgets if not loaded already then start polling
				loadEcho().then( pollForNotificationCountUpdates );
			}
		}

		setTimeout( pollStart, 60 * 1000 );

	} );

}

/**
 * Initialise a mobile experience instead
 */
function initMobile() {
	if ( !mw.user.isAnon() ) {
		mw.loader.using( [ 'ext.echo.mobile', 'mobile.startup' ] ).then( function ( require ) {
			require( 'ext.echo.mobile' ).init();
		} );
	}
}

$( function () {
	if ( mw.config.get( 'wgMFMode' ) ) {
		initMobile();
	} else {
		// Echo is intentionally disable for the Minerva desktop skin (https://phabricator.wikimedia.org/T343839)
		// The suggested fix is documented in https://phabricator.wikimedia.org/T343838,
		if ( mw.config.get( 'skin' ) !== 'minerva' ) {
			initDesktop();
		}
	}
} );
