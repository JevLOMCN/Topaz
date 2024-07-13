/**
 * Initialise code that requires MobileFrontend.
 */

module.exports = function () {
	const
		ms = require( 'mobile.startup' ),
		PageHTMLParser = ms.PageHTMLParser,
		permissions = mw.config.get( 'wgMinervaPermissions' ) || {},
		notifyOnPageReload = ms.notifyOnPageReload,
		time = ms.time,
		preInit = require( './preInit.js' ),
		mobileRedirect = require( './mobileRedirect.js' ),
		search = require( './search.js' ),
		references = require( './references.js' ),
		TitleUtil = require( './TitleUtil.js' ),
		issues = require( './page-issues/index.js' ),
		Toolbar = require( './Toolbar.js' ),
		ToggleList = require( '../../includes/Skins/ToggleList/ToggleList.js' ),
		TabScroll = require( './TabScroll.js' ),
		router = require( 'mediawiki.router' ),
		ctaDrawers = require( './ctaDrawers.js' ),
		drawers = require( './drawers.js' ),
		desktopMMV = mw.loader.getState( 'mmv.bootstrap' ),
		overlayManager = ms.getOverlayManager(),
		currentPage = ms.currentPage(),
		currentPageHTMLParser = ms.currentPageHTMLParser(),
		api = new mw.Api(),
		namespaceIDs = mw.config.get( 'wgNamespaceIds' );

	/**
	 * Event handler for clicking on an image thumbnail
	 *
	 * @param {MouseEvent} ev
	 * @ignore
	 */
	function onClickImage( ev ) {
		// Do not interfere when a modifier key is pressed.
		if ( ev.altKey || ev.ctrlKey || ev.shiftKey || ev.metaKey ) {
			return;
		}

		const el = ev.target.closest( PageHTMLParser.THUMB_SELECTOR );
		if ( !el ) {
			return;
		}

		const thumb = currentPageHTMLParser.getThumbnail( $( el ) );
		if ( !thumb ) {
			return;
		}

		ev.preventDefault();
		routeThumbnail( thumb );
	}

	/**
	 * @param {jQuery.Element} thumbnail
	 * @ignore
	 */
	function routeThumbnail( thumbnail ) {
		router.navigate( '#/media/' + encodeURIComponent( thumbnail.getFileName() ) );
	}

	/**
	 * Add routes to images and handle clicks
	 *
	 * @method
	 * @ignore
	 * @param {HTMLElement} container Container to search within
	 */
	function initMediaViewer( container ) {
		// T360781 Ensure correct type before using `addEventListener`.
		if ( container instanceof HTMLElement ) {
			container.addEventListener( 'click', onClickImage );
		}
	}

	/**
	 * Hijack the Special:Languages link and replace it with a trigger to a languageOverlay
	 * that displays the same data
	 *
	 * @ignore
	 */
	function initButton() {
		// This catches language selectors in page actions and in secondary actions (e.g. Main Page)
		// eslint-disable-next-line no-jquery/no-global-selector
		const $primaryBtn = $( '.language-selector' );

		if ( $primaryBtn.length ) {
			// We only bind the click event to the first language switcher in page
			$primaryBtn.on( 'click', function ( ev ) {
				ev.preventDefault();

				if ( $primaryBtn.attr( 'href' ) || $primaryBtn.find( 'a' ).length ) {
					router.navigate( '/languages' );
				} else {
					mw.notify( mw.msg( 'mobile-frontend-languages-not-available' ) );
				}
			} );
		}
	}

	/**
	 * Returns a rejected promise if MultimediaViewer is available. Otherwise
	 * returns the mediaViewerOverlay
	 *
	 * @method
	 * @ignore
	 * @param {string} title the title of the image
	 * @return {void|Overlay} note must return void if the overlay should not show (see T262703)
	 *  otherwise an Overlay is expected and this can lead to e.on/off is not a function
	 */
	function makeMediaViewerOverlayIfNeeded( title ) {
		if ( mw.loader.getState( 'mmv.bootstrap' ) === 'ready' ) {
			// This means MultimediaViewer has been installed and is loaded.
			// Avoid loading it (T169622)
			return;
		}
		try {
			title = decodeURIComponent( title );
		} catch ( e ) {
			// e.g. https://ro.m.wikipedia.org/wiki/Elisabeta_I_a_Angliei#/media/Fi%C8%18ier:Elizabeth_I_Rainbow_Portrait.jpg
			return;
		}

		return ms.mediaViewer.overlay( {
			api,
			thumbnails: currentPageHTMLParser.getThumbnails(),
			title
		} );
	}

	// Routes
	overlayManager.add( /^\/media\/(.+)$/, makeMediaViewerOverlayIfNeeded );
	overlayManager.add( /^\/languages$/, function () {
		return ms.languages.languageOverlay();
	} );
	// Register a LanguageInfo overlay which has no built-in functionality;
	// a hook is fired when a language is selected, and extensions can respond
	// to that hook. See GrowthExperiments WelcomeSurvey feature (in gerrit
	// Ib558dc7c46cc56ff667957f9126bbe0471d25b8e for example usage).
	overlayManager.add( /^\/languages\/all$/, function () {
		return ms.languages.languageInfoOverlay( api, true );
	} );
	overlayManager.add( /^\/languages\/all\/no-suggestions$/, function () {
		return ms.languages.languageInfoOverlay( api, false );
	} );

	// Setup
	$( function () {
		initButton();
	} );

	/**
	 * Initialisation function for last modified module.
	 *
	 * Enhances an element representing a time
	 * to show a human friendly date in seconds, minutes, hours, days
	 * months or years
	 *
	 * @ignore
	 * @param {jQuery} $lastModifiedLink
	 */
	function initHistoryLink( $lastModifiedLink ) {
		const ts = $lastModifiedLink.data( 'timestamp' );
		if ( ts ) {
			const username = $lastModifiedLink.data( 'user-name' ) || false;
			const gender = $lastModifiedLink.data( 'user-gender' );
			const delta = time.getTimeAgoDelta( parseInt( ts, 10 ) );
			if ( time.isRecent( delta ) ) {
				const $bar = $lastModifiedLink.closest( '.last-modified-bar' );
				$bar.addClass( 'active' );
			}

			const $msg = $( '<span>' )
				// The new element should maintain the non-js element's CSS classes.
				.attr( 'class', $lastModifiedLink.attr( 'class' ) )
				.html(
					time.getLastModifiedMessage( ts, username, gender,
						// For cached HTML
						$lastModifiedLink.attr( 'href' )
					)
				);
			$lastModifiedLink.replaceWith( $msg );
		}
	}

	/**
	 * @method
	 * @param {jQuery.Event} ev
	 */
	function amcHistoryClickHandler( ev ) {
		const self = this;
		const amcOutreach = ms.amcOutreach;
		const amcCampaign = amcOutreach.loadCampaign();
		const onDismiss = function () {
			notifyOnPageReload( mw.msg( 'mobile-frontend-amc-outreach-dismissed-message' ) );
			window.location = self.href;
		};
		const drawer = amcCampaign.showIfEligible( amcOutreach.ACTIONS.onHistoryLink, onDismiss, currentPage.title, 'action=history' );

		if ( drawer ) {
			ev.preventDefault();
			// stopPropagation is needed to prevent drawer from immediately closing
			// when shown (drawers.js adds a click event to window when drawer is
			// shown
			ev.stopPropagation();

			drawers.displayDrawer( drawer, {} );
			drawers.lockScroll();
		}
	}

	/**
	 * @method
	 * @param {jQuery} $lastModifiedLink
	 * @ignore
	 */
	function initAmcHistoryLink( $lastModifiedLink ) {
		$lastModifiedLink.one( 'click', amcHistoryClickHandler );
	}

	/**
	 * Initialisation function for last modified times
	 *
	 * Enhances .modified-enhancement element
	 * to show a human friendly date in seconds, minutes, hours, days
	 * months or years
	 *
	 * @ignore
	 */
	function initModifiedInfo() {
		// eslint-disable-next-line no-jquery/no-global-selector
		$( '.modified-enhancement' ).each( function () {
			initHistoryLink( $( this ) );
		} );
		Array.prototype.forEach.call( document.querySelectorAll( '.mw-diff-timestamp' ), ( tsNode ) => {
			const ts = tsNode.dataset.timestamp;
			if ( ts ) {
				const ago = time.getTimeAgoDelta(
					parseInt(
						( new Date( ts ) ).getTime() / 1000,
						10
					)
				);
				// Supported messages:
				// * skin-minerva-time-ago-seconds
				// * skin-minerva-time-ago-minutes
				// * skin-minerva-time-ago-hours
				// * skin-minerva-time-ago-days
				// * skin-minerva-time-ago-months
				// * skin-minerva-time-ago-years
				tsNode.textContent = mw.msg(
					`skin-minerva-time-ago-${ ago.unit }`,
					mw.language.convertNumber( ago.value )
				);
			}
		} );
	}

	/**
	 * Initialisation function for user creation module.
	 *
	 * Enhances an element representing a time
	 * to show a human friendly date in seconds, minutes, hours, days
	 * months or years
	 *
	 * @ignore
	 * @param {jQuery} [$tagline]
	 */
	function initRegistrationDate( $tagline ) {
		const ts = $tagline.data( 'userpage-registration-date' );

		if ( ts ) {
			const msg = time.getRegistrationMessage( ts, $tagline.data( 'userpage-gender' ) );
			$tagline.text( msg );
		}
	}

	/**
	 * Initialisation function for registration date on user page
	 *
	 * Enhances .tagline-userpage element
	 * to show human friendly date in seconds, minutes, hours, days
	 * months or years
	 *
	 * @ignore
	 */
	function initRegistrationInfo() {
		// eslint-disable-next-line no-jquery/no-global-selector
		$( '#tagline-userpage' ).each( function () {
			initRegistrationDate( $( this ) );
		} );
	}

	/**
	 * Tests a URL to determine if it links to a local User namespace page or not.
	 *
	 * Assuming the current page visited is hosted on metawiki, the following examples would return
	 * true:
	 *
	 *   https://meta.wikimedia.org/wiki/User:Foo
	 *   /wiki/User:Foo
	 *   /wiki/User:Nonexistent_user_page
	 *
	 * The following examples return false:
	 *
	 *   https://en.wikipedia.org/wiki/User:Foo
	 *   /wiki/Foo
	 *   /wiki/User_talk:Foo
	 *
	 * @param {string} url
	 * @return {boolean}
	 */
	function isUserUri( url ) {
		const title = TitleUtil.newFromUri( url );
		const namespace = title ? title.getNamespaceId() : undefined;
		return namespace === namespaceIDs.user;
	}

	/**
	 * Strip the edit action from red links to nonexistent User namespace pages.
	 *
	 * @param {jQuery} $redLinks
	 */
	function initUserRedLinks( $redLinks ) {
		$redLinks.filter( function ( _, element ) {
			// Filter out non-User namespace pages.
			return isUserUri( element.href );
		} ).each( function ( _, element ) {
			const uri = new mw.Uri( element.href );
			if ( uri.query.action !== 'edit' ) {
				// Nothing to strip.
				return;
			}

			// Strip the action.
			delete uri.query.action;

			// Update the element with the new link.
			element.href = uri.toString();
		} );
	}

	/**
	 * Wires up the notification badge to Echo extension
	 */
	function setupEcho() {
		const echoBtn = document.querySelector( '.minerva-notifications .mw-echo-notification-badge-nojs' );
		if ( echoBtn ) {
			echoBtn.addEventListener( 'click', function ( ev ) {
				router.navigate( '#/notifications' );
				// prevent navigation to original Special:Notifications URL
				// DO NOT USE stopPropagation or you'll break click tracking in WikimediaEvents
				ev.preventDefault();

				// Mark as read.
				echoBtn.dataset.counterNum = 0;
				echoBtn.dataset.counterText = mw.msg( 'echo-badge-count',
					mw.language.convertNumber( 0 )
				);

			} );
		}
	}

	$( function () {
		// eslint-disable-next-line no-jquery/no-global-selector
		const $watch = $( '#page-actions-watch' );
		const toolbarElement = document.querySelector( Toolbar.selector );
		const userMenu = document.querySelector( '.minerva-user-menu' ); // See UserMenuDirector.
		const navigationDrawer = document.querySelector( '.navigation-drawer' );

		// The `minerva-animations-ready` class can be used by clients to prevent unwanted
		// CSS transitions from firing on page load in some browsers (see
		// https://bugs.chromium.org/p/chromium/issues/detail?id=332189 as well as
		// https://phabricator.wikimedia.org/T234570#5779890). Since JS adds this
		// class after the CSS transitions loads, this issue is circumvented. See
		// MainMenu.less for an example of how this is used.
		$( document.body ).addClass( 'minerva-animations-ready' );

		// eslint-disable-next-line no-jquery/no-global-selector
		$( '.mw-mf-page-center__mask' ).on( 'click', function ( ev ) {
			const path = router.getPath();
			// avoid jumping to the top of the page and polluting history by avoiding the
			// resetting of the hash unless the hash is being utilised (T237015).
			if ( !path ) {
				ev.preventDefault();
			}
		} );
		// Init:
		// - main menu closes when you click outside of it
		// - redirects show a toast.
		preInit();
		// - references
		references();
		// - search
		search();
		// - mobile redirect
		mobileRedirect( ms.amcOutreach, currentPage );

		// Enhance timestamps on last-modified bar and watchlist
		// to show relative time.
		initModifiedInfo();
		initRegistrationInfo();
		// eslint-disable-next-line no-jquery/no-global-selector
		initAmcHistoryLink( $( '.last-modified-bar__text a' ) );

		if ( toolbarElement ) {
			Toolbar.bind( window, toolbarElement );
			// Update the edit icon and add a download icon.
			Toolbar.render( window, toolbarElement );
		}
		if ( userMenu ) {
			ToggleList.bind( window, userMenu );
		}
		if ( navigationDrawer ) {
			ToggleList.bind( window, navigationDrawer );
			const navigationDrawerMask = navigationDrawer.querySelector( '.main-menu-mask' );
			// The 'for' attribute is used to close the drawer when the mask is clicked without JS
			// Since we are using JS to enhance the drawer behavior, we need to
			// remove the attribute to prevent the drawer from being toggled twice
			navigationDrawerMask.removeAttribute( 'for' );
		}
		TabScroll.initTabsScrollPosition();
		// Setup the issues banner on the page
		// Pages which dont exist (id 0) cannot have issues
		if (
			!currentPage.isMissing &&
			!currentPage.titleObj.isTalkPage()
		) {
			issues.init( overlayManager, currentPageHTMLParser );
		}

		// If MobileFrontend installed we add a table of contents icon to the table of contents.
		// This should probably be done in the parser.
		// setup toc icons
		mw.hook( 'wikipage.content' ).add( function ( $container ) {
			// If the MMV module is missing or disabled from the page, initialise our version
			if ( desktopMMV === null || desktopMMV === 'registered' ) {
				initMediaViewer( $container[ 0 ] );
			}

			// Mutate TOC.
			const $toctitle = $container.find( '.toctitle' );
			$( '<span>' ).addClass( 'toc-title-icon' ).prependTo( $toctitle );
			$( '<span>' ).addClass( 'toc-title-state-icon' ).appendTo( $toctitle );

			// Init red links.
			const $redLinks = currentPageHTMLParser.getRedLinks();
			ctaDrawers.initRedlinksCta(
				$redLinks.filter( function ( _, element ) {
					// Filter out local User namespace pages.
					return !isUserUri( element.href );
				} )
			);
			initUserRedLinks( $redLinks );
		} );

		// wire up watch icon if necessary
		if ( permissions.watchable && !permissions.watch ) {
			ctaDrawers.initWatchstarCta( $watch );
		}

		// If Echo is installed, wire it up.
		const echoState = mw.loader.getState( 'ext.echo.mobile' );
		// If Echo is installed, set it up.
		if ( echoState !== null && echoState !== 'registered' ) {
			setupEcho();
		}
	} );
};
