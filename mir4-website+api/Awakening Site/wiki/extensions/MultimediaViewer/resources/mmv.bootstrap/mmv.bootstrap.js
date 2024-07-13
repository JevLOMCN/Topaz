/*
 * This file is part of the MediaWiki extension MultimediaViewer.
 *
 * MultimediaViewer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MultimediaViewer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MultimediaViewer.  If not, see <http://www.gnu.org/licenses/>.
 */

const { getMediaHash, ROUTE_REGEXP, LEGACY_ROUTE_REGEXP, isMediaViewerEnabledOnClick } = require( 'mmv.head' );
const Config = require( './mmv.Config.js' );
const HtmlUtils = require( './mmv.HtmlUtils.js' );

( function () {
	const mwRouter = require( 'mediawiki.router' );

	// We pass this to history.pushState/replaceState to indicate that we're controlling the page URL.
	// Then we look for this marker on page load so that if the page is refreshed, we don't generate an
	// extra history entry.
	const MANAGED_STATE = 'MMV was here!';

	/**
	 * Bootstrap code listening to thumb clicks checking the initial location.hash
	 * Loads the mmv and opens it if necessary
	 */
	class MultimediaViewerBootstrap {
		constructor() {
			// Exposed for tests
			this.hoverWaitDuration = 200;

			// TODO lazy-load config and htmlUtils
			/** @property {Config} config - */
			this.config = new Config(
				mw.config.get( 'wgMultimediaViewer', {} ),
				mw.config,
				mw.user,
				new mw.Api(),
				mw.storage
			);

			this.validExtensions = this.config.extensions();

			/** @property {HtmlUtils} htmlUtils - */
			this.htmlUtils = new HtmlUtils();

			/**
			 * This flag is set to true when we were unable to load the viewer.
			 *
			 * @property {boolean}
			 */
			this.viewerIsBroken = false;

			this.thumbsReadyDeferred = $.Deferred();
			this.thumbs = [];
			this.$thumbs = null; // will be set by processThumbs
			this.$parsoidThumbs = null; // will be set in processThumbs

			// find and setup all thumbs on this page
			// this will run initially and then every time the content changes,
			// e.g. via a VE edit or pagination in a multipage file
			mw.hook( 'wikipage.content' ).add( this.processThumbs.bind( this ) );

			// Setup the router
			this.setupRouter( mwRouter );
		}

		/**
		 * Routes to a given file.
		 *
		 * @param {string} fileName
		 */
		route( fileName ) {
			this.loadViewer( true ).then( ( viewer ) => {
				let fileTitle;
				viewer.comingFromHashChange = true;
				try {
					fileName = decodeURIComponent( fileName );
					fileTitle = new mw.Title( fileName );
					viewer.loadImageByTitle( fileTitle );
				} catch ( err ) {
					// ignore routes to invalid titles
					mw.log.warn( err );
				}
			} );
		}

		/**
		 * Sets up the route handlers
		 *
		 * @param {OO.Router} router
		 */
		setupRouter( router ) {
			router.addRoute( ROUTE_REGEXP, this.route.bind( this ) );
			router.addRoute( LEGACY_ROUTE_REGEXP, this.route.bind( this ) );
			this.router = router;
		}

		/**
		 * Loads the mmv module asynchronously and passes the thumb data to it
		 *
		 * @param {boolean} [setupOverlay]
		 * @return {jQuery.Promise}
		 */
		loadViewer( setupOverlay ) {
			const deferred = $.Deferred();
			let viewer;
			let message;

			// Don't load if someone has specifically stopped us from doing so
			if ( mw.config.get( 'wgMediaViewer' ) !== true ) {
				return deferred.reject();
			}

			if ( history.scrollRestoration ) {
				history.scrollRestoration = 'manual';
			}

			// FIXME setupOverlay is a quick hack to avoid setting up and immediately
			// removing the overlay on a not-MMV -> not-MMV hash change.
			// loadViewer is called on every click and hash change and setting up
			// the overlay is not needed on all of those; this logic really should
			// not be here.
			if ( setupOverlay ) {
				this.setupOverlay();
			}

			mw.loader.using( 'mmv', ( req ) => {
				try {
					viewer = this.getViewer( req );
				} catch ( e ) {
					message = e.message;
					if ( e.stack ) {
						message += `\n${ e.stack }`;
					}
					deferred.reject( message );
					return;
				}
				deferred.resolve( viewer );
			}, ( error ) => {
				deferred.reject( error.message );
			} );

			return deferred.promise()
				.then(
					( viewer2 ) => {
						if ( !this.viewerInitialized ) {
							if ( this.thumbs.length ) {
								viewer2.initWithThumbs( this.thumbs );
							}

							this.viewerInitialized = true;
						}
						return viewer2;
					},
					( message2 ) => {
						mw.log.warn( message2 );
						this.cleanupOverlay();
						this.viewerIsBroken = true;
						mw.notify( `Error loading MediaViewer: ${ message2 }` );
						return $.Deferred().reject( message2 );
					}
				);
		}

		/**
		 * Processes all thumbs found on the page
		 *
		 * @param {jQuery} $content Element to search for thumbs
		 */
		processThumbs( $content ) {
			// MMVB.processThumbs() is a callback for `wikipage.content` hook (see constructor)
			// which as state in the documentation can be fired when content is added to the DOM
			// https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.hook
			// The content being added can contain thumbnails that the MultimediaViewer may need to
			// process correctly and add the thumbs array, so it's necessary to invalidate the
			// viewer initialization state if this happens to let the MMVB.loadViewer() to process
			// new images correctly
			this.viewerInitialized = false;

			this.$parsoidThumbs = $content.find(
				'[typeof*="mw:File"] a.mw-file-description img, ' +
				// TODO: Remove mw:Image when version 2.4.0 of the content is no
				// longer supported
				'[typeof*="mw:Image"] a.mw-file-description img'
			);

			this.$thumbs = $content
				.find(
					'.gallery .image img, ' +
					'a.image img, ' +
					'a.mw-file-description img, ' +
					'#file a img'
				)
				// Skip duplicates that are actually Parsoid thumbs
				.not( this.$parsoidThumbs );

			try {
				this.$thumbs.each( ( i, thumb ) => this.processThumb( thumb ) );
				this.$parsoidThumbs.each( ( i, thumb ) => this.processParsoidThumb( thumb ) );
			} finally {
				this.thumbsReadyDeferred.resolve();
				// now that we have set up our real click handler we can we can remove the temporary
				// handler added in mmv.head.js which just replays clicks to the real handler
				$( document ).off( 'click.mmv-head' );
			}
		}

		/**
		 * Check if this thumbnail should be handled by MediaViewer
		 *
		 * @param {jQuery} $thumb the thumbnail (an `<img>` element) in question
		 * @return {boolean}
		 */
		isAllowedThumb( $thumb ) {
			const selectors = [
				'.metadata', // this is inside an informational template like {{refimprove}} on enwiki.
				'.noviewer', // MediaViewer has been specifically disabled for this image
				'.noarticletext', // we are on an error page for a non-existing article, the image is part of some template
				'#siteNotice',
				'ul.mw-gallery-slideshow li.gallerybox' // thumbnails of a slideshow gallery
			];
			return $thumb.closest( selectors.join( ', ' ) ).length === 0;

		}

		/**
		 * @param {mw.Title|null} title
		 * @return {boolean}
		 */
		isValidExtension( title ) {
			return title && title.getExtension() && ( title.getExtension().toLowerCase() in this.validExtensions );
		}

		/**
		 * Preload JS/CSS when the mouse cursor hovers the thumb container
		 * (thumb image + caption + border)
		 *
		 * @param {jQuery} $thumbContainer
		 */
		preloadAssets( $thumbContainer ) {
			$thumbContainer.on( {
				mouseenter: () => {
					// There is no point preloading if clicking the thumb won't open Media Viewer
					if ( !isMediaViewerEnabledOnClick() ) {
						return;
					}
					this.preloadOnHoverTimer = setTimeout( () => {
						mw.loader.load( 'mmv' );
					}, this.hoverWaitDuration );
				},
				mouseleave: () => {
					if ( this.preloadOnHoverTimer ) {
						clearTimeout( this.preloadOnHoverTimer );
					}
				}
			} );
		}

		/**
		 * Processes a thumb
		 *
		 * @param {Object} thumb
		 */
		processThumb( thumb ) {
			let title;
			const $thumb = $( thumb );
			const $link = $thumb.closest( 'a.image, a.mw-file-description' );
			const $thumbContainer = $link.closest( '.thumb' );
			const $enlarge = $thumbContainer.find( '.magnify a' );
			const link = $link.prop( 'href' );
			const alt = $thumb.attr( 'alt' );
			const isFilePageMainThumb = $thumb.closest( '#file' ).length > 0;

			if ( isFilePageMainThumb ) {
				// main thumbnail (file preview area) of a file page
				// if this is a PDF filetype thumbnail, it can trick us,
				// so we short-circuit that logic and use the file page title
				// instead of the thumbnail logic.
				title = mw.Title.newFromText( mw.config.get( 'wgTitle' ), mw.config.get( 'wgNamespaceNumber' ) );
			} else {
				title = mw.Title.newFromImg( $thumb );
			}

			if ( !this.isValidExtension( title ) ) {
				// Short-circuit event handler and interface setup, because
				// we can't do anything for this filetype
				return;
			}

			if ( !this.isAllowedThumb( $thumb ) ) {
				return;
			}

			if ( $thumbContainer.length ) {
				this.preloadAssets( $thumbContainer );
			}

			if ( isFilePageMainThumb ) {
				this.processFilePageThumb( $thumb, title );
				return;
			}

			// This is the data that will be passed onto the mmv
			this.thumbs.push( {
				thumb: thumb,
				$thumb: $thumb,
				title: title,
				link: link,
				alt: alt,
				caption: this.findCaption( $thumbContainer, $link )
			} );

			$link.add( $enlarge ).on( 'click', ( e ) => this.click( e, title ) );
		}

		/**
		 * Processes a Parsoid thumb, making use of the specified structure,
		 *   https://www.mediawiki.org/wiki/Specs/HTML#Media
		 *
		 * @param {Object} thumb
		 */
		processParsoidThumb( thumb ) {
			const $thumb = $( thumb );
			const $link = $thumb.closest( 'a.mw-file-description' );
			const $thumbContainer = $link.closest(
				'[typeof*="mw:File"], ' +
				// TODO: Remove mw:Image when version 2.4.0 of the content is
				// no longer supported
				'[typeof*="mw:Image"]'
			);
			const link = $link.prop( 'href' );
			const alt = $thumb.attr( 'alt' );
			const title = mw.Title.newFromImg( $thumb );
			let caption;
			let $thumbCaption;

			if ( !this.isValidExtension( title ) ) {
				// Short-circuit event handler and interface setup, because
				// we can't do anything for this filetype
				return;
			}

			if ( !this.isAllowedThumb( $thumb ) ) {
				return;
			}

			if ( $thumbContainer.length ) {
				this.preloadAssets( $thumbContainer );
			}

			if ( ( $thumbContainer.prop( 'tagName' ) || '' ).toLowerCase() === 'figure' ) {
				$thumbCaption = $thumbContainer.find( 'figcaption' );
				caption = this.htmlUtils.htmlToTextWithTags( $thumbCaption.html() || '' );
			} else {
				caption = $link.prop( 'title' ) || undefined;
			}

			// This is the data that will be passed onto the mmv
			this.thumbs.push( {
				thumb: thumb,
				$thumb: $thumb,
				title: title,
				link: link,
				alt: alt,
				caption: caption
			} );

			$link.on( 'click', ( e ) => this.click( e, title ) );
		}

		/**
		 * Processes the main thumbnail of a file page by adding some buttons
		 * below to open MediaViewer.
		 *
		 * @param {jQuery} $thumb
		 * @param {mw.Title} title
		 */
		processFilePageThumb( $thumb, title ) {
			const link = $thumb.closest( 'a' ).prop( 'href' );

			// remove the buttons (and the clearing element) if they are already there
			// this should not happen (at least until we support paged media) but just in case
			// eslint-disable-next-line no-jquery/no-global-selector
			$( '.mw-mmv-filepage-buttons' ).next().addBack().remove();

			const $mmvButton = $( '<button>' )
				.addClass( 'mw-mmv-view-expanded cdx-button' )
				.append( $( '<span>' ).addClass( 'cdx-button__icon' ) )
				.append( ' ' )
				.append( mw.message( 'multimediaviewer-view-expanded' ).text() );

			const $configButton = $( '<button>' )
				.attr( 'title', mw.message( 'multimediaviewer-view-config' ).text() )
				.addClass( 'mw-mmv-view-config cdx-button cdx-button--icon-only' )
				.append( $( '<span>' ).addClass( 'cdx-button__icon' ) )
				// U+200B ZERO WIDTH SPACE to accomplish same height as $mmvButton
				.append( '\u200B' );

			const $filepageButtons = $( '<div>' )
				.addClass( 'cdx-button-group mw-mmv-filepage-buttons' )
				.append( $mmvButton, $configButton );

			// eslint-disable-next-line no-jquery/no-global-selector
			$( '.fullMedia' ).append(
				$filepageButtons,
				$( '<div>' )
					.css( 'clear', 'both' )
			);

			this.thumbs.push( {
				thumb: $thumb.get( 0 ),
				$thumb: $thumb,
				title: title,
				link: link
			} );

			$mmvButton.on( 'click', () => {
				if ( this.statusInfoDialog ) {
					this.statusInfoDialog.close();
				}
				this.openImage( title );
				return false;
			} );

			$configButton.on( 'click', () => {
				if ( this.statusInfoDialog ) {
					this.statusInfoDialog.close();
				}
				$( document ).one( 'mmv-metadata', () => {
					$( document ).trigger( 'mmv-options-open' );
				} );
				this.openImage( title );
				return false;
			} );

			if ( this.config.shouldShowStatusInfo() ) {
				this.config.disableStatusInfo();
				this.showStatusInfo();
			}
		}

		/**
		 * Shows a popup notifying the user
		 */
		showStatusInfo() {
			mw.loader.using( 'oojs-ui-core' ).done( () => {
				const content = document.createElement( 'div' );
				content.textContent = mw.message( 'multimediaviewer-disable-info' ).text();

				const popupWidget = new OO.ui.PopupWidget( {
					label: mw.message( 'multimediaviewer-disable-info-title' ).text(),
					$content: $( content ),
					padded: true,
					head: true,
					anchor: true,
					align: 'center',
					position: 'above',
					autoFlip: false,
					horizontalPosition: 'start',
					// eslint-disable-next-line no-jquery/no-global-selector
					$floatableContainer: $( '.mw-mmv-view-expanded' )
				} );
				popupWidget.$element.appendTo( document.body );
				popupWidget.toggleClipping( true );
				popupWidget.toggle( true );
			} );
		}

		/**
		 * Finds the caption for an image.
		 *
		 * @param {jQuery} $thumbContainer The container for the thumbnail.
		 * @param {jQuery} $link The link that encompasses the thumbnail.
		 * @return {string|undefined} Unsafe HTML may be present - caution
		 */
		findCaption( $thumbContainer, $link ) {
			let $thumbCaption;

			if ( !$thumbContainer.length ) {
				return $link.prop( 'title' ) || undefined;
			}

			const $potentialCaptions = $thumbContainer.find( '.thumbcaption' );
			if ( $potentialCaptions.length < 2 ) {
				$thumbCaption = $potentialCaptions.eq( 0 );
			} else {
				// Template:Multiple_image or some such; try to find closest caption to the image
				// eslint-disable-next-line no-jquery/no-sizzle
				$thumbCaption = $link.closest( ':has(> .thumbcaption)', $thumbContainer )
					.find( '> .thumbcaption' );
			}

			if ( !$thumbCaption.length ) { // gallery, maybe
				$thumbCaption = $thumbContainer
					.closest( '.gallerybox' )
					.not( () => {
						// do not treat categories as galleries - the autogenerated caption they have is not helpful
						return $thumbContainer.closest( '#mw-category-media' ).length;
					} )
					.not( () => {
						// do not treat special file related pages as galleries
						const $specialFileRelatedPages = $(
							'.page-Special_NewFiles, ' +
							'.page-Special_MostLinkedFiles,' +
							'.page-Special_MostGloballyLinkedFiles, ' +
							'.page-Special_UncategorizedFiles, ' +
							'.page-Special_UnusedFiles'
						);
						return $thumbContainer.closest( $specialFileRelatedPages ).length;
					} )
					.find( '.gallerytext' );
			}

			if ( $thumbCaption.find( '.magnify' ).length ) {
				$thumbCaption = $thumbCaption.clone();
				$thumbCaption.find( '.magnify' ).remove();
			}

			return this.htmlUtils.htmlToTextWithTags( $thumbCaption.html() || '' );
		}

		/**
		 * Opens MediaViewer and loads the given thumbnail. Requires processThumb() to be called first.
		 *
		 * @param {mw.Title} title File title
		 */
		openImage( title ) {
			this.ensureEventHandlersAreSetUp();
			const hash = getMediaHash( title );
			location.hash = hash;
			history.replaceState( MANAGED_STATE, null, hash );
		}

		/**
		 * Handles a click event on a link
		 *
		 * @param {jQuery.Event} e jQuery event object
		 * @param {mw.Title} title File title
		 * @return {boolean} a value suitable for an event handler (ie. true if the click should be handled
		 *  by the browser).
		 */
		click( e, title ) {
			// Do not interfere with non-left clicks or if modifier keys are pressed.
			if ( ( e.button !== 0 && e.which !== 1 ) || e.altKey || e.ctrlKey || e.shiftKey || e.metaKey ) {
				return true;
			}

			// Don't load if someone has specifically stopped us from doing so
			if ( !isMediaViewerEnabledOnClick() ) {
				return true;
			}

			// Don't load if we already tried loading and it failed
			if ( this.viewerIsBroken ) {
				return true;
			}

			// Mark the state so that if the page is refreshed, we don't generate an extra history entry
			this.openImage( title );

			// calling this late so that in case of errors users at least get to the file page
			e.preventDefault();

			return false;
		}

		/**
		 * Returns true if the hash part of the current URL is one that's owned by MMV.
		 *
		 * @return {boolean}
		 * @private
		 */
		isViewerHash() {
			const path = location.hash.slice( 1 );
			return path.match( ROUTE_REGEXP ) || path.match( LEGACY_ROUTE_REGEXP );
		}

		/**
		 * Handles the browser location hash on pageload or hash change
		 */
		hash() {
			const isViewerHash = this.isViewerHash();

			// There is no point loading the mmv if it isn't loaded yet for hash changes unrelated to the mmv
			// Such as anchor links on the page
			if ( !this.viewerInitialized && !isViewerHash ) {
				return;
			}

			const hash = location.hash;
			if ( window.history.state !== MANAGED_STATE ) {
				// First replace the current URL with a URL with a hash.
				history.replaceState( null, null, '#' );
				history.pushState( MANAGED_STATE, null, hash );
			}
			this.router.checkRoute();
		}

		/**
		 * Instantiates a new viewer if necessary
		 *
		 * @param {Function} localRequire
		 * @return {MultimediaViewer}
		 */
		getViewer( localRequire ) {
			if ( this.viewer === undefined ) {
				const { MultimediaViewer } = localRequire( 'mmv' );
				this.viewer = new MultimediaViewer( this.config );
				this.viewer.setupEventHandlers();
			}

			return this.viewer;
		}

		/**
		 * Listens to events on the window/document
		 */
		setupEventHandlers() {
			/** @property {boolean} eventHandlersHaveBeenSetUp tracks domready event handler state */
			this.eventHandlersHaveBeenSetUp = true;

			// Interpret any hash that might already be in the url
			this.hash( true );

			$( document ).on( 'mmv-setup-overlay', () => {
				this.setupOverlay();
			} ).on( 'mmv-cleanup-overlay', () => {
				this.cleanupOverlay();
			} );
		}

		/**
		 * Cleans up event handlers, used for tests
		 */
		cleanupEventHandlers() {
			$( document ).off( 'mmv-setup-overlay mmv-cleanup-overlay' );
			this.eventHandlersHaveBeenSetUp = false;
		}

		/**
		 * Makes sure event handlers are set up properly via MultimediaViewerBootstrap.setupEventHandlers().
		 * Called before loading the main mmv module. At this point, event handers for MultimediaViewerBootstrap
		 * should have been set up, but due to bug 70756 it cannot be guaranteed.
		 */
		ensureEventHandlersAreSetUp() {
			if ( !this.eventHandlersHaveBeenSetUp ) {
				this.setupEventHandlers();
			}
		}

		/**
		 * Sets up the overlay while the viewer loads
		 */
		setupOverlay() {
			const $body = $( document.body );

			// There are situations where we can call setupOverlay while the overlay is already there,
			// such as inside this.hash(). In that case, do nothing
			if ( $body.hasClass( 'mw-mmv-lightbox-open' ) ) {
				return;
			}

			if ( !this.$overlay ) {
				this.$overlay = $( '<div>' )
					// Dark overlay should stay dark in dark mode
					.addClass( 'mw-mmv-overlay mw-no-invert' );
			}

			this.savedScrollTop = $( window ).scrollTop();

			$body.addClass( 'mw-mmv-lightbox-open' )
				.append( this.$overlay );
		}

		/**
		 * Cleans up the overlay
		 */
		cleanupOverlay() {
			$( document.body ).removeClass( 'mw-mmv-lightbox-open' );

			if ( this.$overlay ) {
				this.$overlay.remove();
			}

			if ( this.savedScrollTop !== undefined ) {
				// setTimeout because otherwise Chrome will scroll back to top after the popstate event handlers run
				setTimeout( () => {
					$( window ).scrollTop( this.savedScrollTop );
					this.savedScrollTop = undefined;
				} );
			}
		}

		whenThumbsReady() {
			return this.thumbsReadyDeferred.promise();
		}
	}

	module.exports = { MultimediaViewerBootstrap, Config, HtmlUtils };
}() );
