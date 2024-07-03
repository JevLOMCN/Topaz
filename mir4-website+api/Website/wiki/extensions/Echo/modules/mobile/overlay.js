var mobile = require( 'mobile.startup' ),
	Overlay = mobile.Overlay,
	list = require( './list.js' ),
	promisedView = mobile.promisedView,
	View = mobile.View;

/**
 * @param {Overlay} overlay
 * @param {Function} exit
 * @return {void}
 */
function onBeforeExitAnimation( overlay, exit ) {
	if ( getComputedStyle( overlay.$el[ 0 ] ).transitionDuration !== '0s' ) {
		// Manually detach the overlay from DOM once hide animation completes.
		overlay.$el[ 0 ].addEventListener( 'transitionend', exit, { once: true } );

		// Kick off animation.
		overlay.$el[ 0 ].classList.remove( 'visible' );
	} else {
		exit();
	}
}

/**
 * This callback is displayed as a global member.
 *
 * @callback FunctionCountChangeCallback
 * @param {number} count a capped (0-99 or 99+) count
 */

/**
 * @param {number} count a capped (0-99 or 99+) count.
 */
function onCountChange( count ) {
	mw.hook( 'ext.echo.badge.countChange' ).fire(
		'all',
		count,
		mw.msg( 'echo-badge-count',
			mw.language.convertNumber( count )
		)
	);
}

/**
 * Make a notification overlay
 *
 * @param {Function} onBeforeExit
 * @return {Overlay}
 */
function notificationsOverlay( onBeforeExit ) {
	var markAllReadButton,
		oouiPromise = mw.loader.using( 'oojs-ui' ).then( function () {
			markAllReadButton = new OO.ui.ButtonWidget( {
				icon: 'checkAll'
			} );
			return View.make(
				{ class: 'notifications-overlay-header-markAllRead' },
				[ markAllReadButton.$element ]
			);
		} ),
		markAllReadButtonView = promisedView( oouiPromise );
	// hide the button spinner as it is confusing to see in the top right corner
	markAllReadButtonView.$el.hide();

	var overlay = Overlay.make(
		{
			heading: '<strong>' + mw.message( 'notifications' ).escaped() + '</strong>',
			footerAnchor: {
				href: mw.util.getUrl( 'Special:Notifications' ),
				progressive: true,
				additionalClassNames: 'footer-link notifications-archive-link',
				label: mw.msg( 'echo-overlay-link' )
			},
			headerActions: [ markAllReadButtonView ],
			isBorderBox: false,
			className: 'overlay notifications-overlay navigation-drawer',
			onBeforeExit: function ( exit ) {
				onBeforeExit( function () {
					onBeforeExitAnimation( overlay, exit );
				} );
			}
		},
		promisedView(
			oouiPromise.then( function () {
				return list( mw.echo, markAllReadButton, onCountChange );
			} )
		)
	);
	return overlay;
}

module.exports = notificationsOverlay;
