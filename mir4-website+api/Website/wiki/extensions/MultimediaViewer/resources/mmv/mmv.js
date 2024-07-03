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

const { getMediaHash } = require( 'mmv.head' );
const ViewLogger = require( './logging/mmv.logging.ViewLogger.js' );
const {
	Api,
	FileRepoInfo,
	GuessedThumbnailInfo,
	ImageInfo,
	ImageProvider,
	ThumbnailInfo
} = require( './provider/mmv.provider.js' );
const {
	ImageModel,
	IwTitle,
	License,
	Repo,
	ForeignApiRepo,
	ForeignDbRepo,
	TaskQueue,
	Thumbnail,
	ThumbnailWidth
} = require( './model/mmv.model.js' );
const {
	Canvas,
	CanvasButtons,
	Description,
	Dialog,
	DownloadDialog,
	UiElement,
	MetadataPanel,
	MetadataPanelScroller,
	Permission,
	ProgressBar,
	ReuseDialog,
	StripeButtons,
	TruncatableTextField,
	OptionsDialog
} = require( './ui/index.js' );
const LightboxImage = require( './mmv.lightboximage.js' );
const LightboxInterface = require( './mmv.lightboxinterface.js' );
const ThumbnailWidthCalculator = require( './mmv.ThumbnailWidthCalculator.js' );

( function () {
	const router = require( 'mediawiki.router' );
	let comingFromHashChange = false;

	/**
	 * Analyses the page, looks for image content and sets up the hooks
	 * to manage the viewing experience of such content.
	 */
	class MultimediaViewer {
		/**
		 * @param {Config} config Config object
		 */
		constructor( config ) {
			const apiCacheMaxAge = 86400; // one day (24 hours * 60 min * 60 sec)
			const apiCacheFiveMinutes = 300; // 5 min * 60 sec
			const api = new mw.Api();

			/**
			 * @property {Config}
			 * @private
			 */
			this.config = config;

			/**
			 * @property {ImageProvider}
			 * @private
			 */
			this.imageProvider = new ImageProvider( this.config.imageQueryParameter() );

			/**
			 * @property {ImageInfo}
			 * @private
			 */
			this.imageInfoProvider = new ImageInfo( api, {
				language: this.config.language(),
				maxage: apiCacheFiveMinutes
			} );

			/**
			 * @property {FileRepoInfo}
			 * @private
			 */
			this.fileRepoInfoProvider = new FileRepoInfo( api,
				{ maxage: apiCacheMaxAge } );

			/**
			 * @property {ThumbnailInfo}
			 * @private
			 */
			this.thumbnailInfoProvider = new ThumbnailInfo( api,
				{ maxage: apiCacheMaxAge } );

			/**
			 * @property {ThumbnailInfo}
			 * @private
			 */
			this.guessedThumbnailInfoProvider = new GuessedThumbnailInfo();

			/**
			 * Image index on page.
			 *
			 * @property {number}
			 */
			this.currentIndex = 0;

			/**
			 * @property {OO.Router} router
			 */
			this.router = router;
			this.setupRouter();
			comingFromHashChange = false;

			/**
			 * UI object used to display the pictures in the page.
			 *
			 * @property {LightboxInterface}
			 * @private
			 */
			this.ui = new LightboxInterface();

			/**
			 * How many sharp images have been displayed in Media Viewer since the pageload
			 *
			 * @property {number}
			 */
			this.imageDisplayedCount = 0;

			/**
			 * How many data-filled metadata panels have been displayed in Media Viewer since the pageload
			 *
			 * @property {number}
			 */
			this.metadataDisplayedCount = 0;

			/** @property {string} documentTitle base document title, MediaViewer will expand this */
			this.documentTitle = document.title;

			/**
			 * @property {ViewLogger} view -
			 */
			this.viewLogger = new ViewLogger( this.config, window );

			/**
			 * Stores whether the real image was loaded and displayed already.
			 * This is reset when paging, so it is not necessarily accurate.
			 *
			 * @property {boolean}
			 */
			this.realThumbnailShown = false;

			/**
			 * Stores whether the a blurred placeholder is being displayed in place of the real image.
			 * When a placeholder is displayed, but it is not blurred, this is false.
			 * This is reset when paging, so it is not necessarily accurate.
			 *
			 * @property {boolean}
			 */
			this.blurredThumbnailShown = false;
		}

		/**
		 * Initialize the lightbox interface given an array of thumbnail
		 * objects.
		 *
		 * @param {Object[]} thumbs Complex structure...TODO, document this better.
		 */
		initWithThumbs( thumbs ) {
			this.thumbs = thumbs;

			for ( let i = 0; i < this.thumbs.length; i++ ) {
				const thumb = this.thumbs[ i ];
				// Create a LightboxImage object for each legit image
				thumb.image = this.createNewImage(
					thumb.$thumb.prop( 'src' ),
					thumb.link,
					thumb.title,
					i,
					thumb.thumb,
					thumb.caption,
					thumb.alt
				);

				thumb.extraStatsDeferred = $.Deferred();
			}
		}

		/**
		 * Create an image object for the lightbox to use.
		 *
		 * @protected
		 * @param {string} fileLink Link to the file - generally a thumb URL
		 * @param {string} filePageLink Link to the File: page
		 * @param {mw.Title} fileTitle Represents the File: page
		 * @param {number} index Which number file this is
		 * @param {HTMLImageElement} thumb The thumbnail that represents this image on the page
		 * @param {string} [caption] The caption, if any.
		 * @param {string} [alt] The alt text of the image
		 * @return {LightboxImage}
		 */
		createNewImage( fileLink, filePageLink, fileTitle, index, thumb, caption, alt ) {
			const thisImage = new LightboxImage( fileLink, filePageLink, fileTitle, index, thumb, caption, alt );
			const $thumb = $( thumb );

			thisImage.filePageLink = filePageLink;
			thisImage.filePageTitle = fileTitle;
			thisImage.index = index;
			thisImage.thumbnail = thumb;
			thisImage.originalWidth = parseInt( $thumb.data( 'file-width' ), 10 );
			thisImage.originalHeight = parseInt( $thumb.data( 'file-height' ), 10 );

			return thisImage;
		}

		/**
		 * Handles resize events in viewer.
		 *
		 * @protected
		 * @param {LightboxInterface} ui lightbox that got resized
		 */
		resize( ui ) {
			const image = this.thumbs[ this.currentIndex ].image;
			const ext = this.thumbs[ this.currentIndex ].title.getExtension().toLowerCase();

			this.preloadThumbnails();

			if ( image ) {
				const imageWidths = ui.canvas.getCurrentImageWidths();

				this.fetchThumbnailForLightboxImage(
					image, imageWidths.real
				).then( ( thumbnail, image2 ) => {
					// eslint-disable-next-line mediawiki/class-doc
					image2.className = ext;
					this.setImage( ui, thumbnail, image2, imageWidths );
				}, ( error ) => {
					this.ui.canvas.showError( error );
				} );
			}

			this.updateControls();
		}

		/**
		 * Updates positioning of controls, usually after a resize event.
		 */
		updateControls() {
			const numImages = this.thumbs ? this.thumbs.length : 0;
			const showNextButton = this.currentIndex < ( numImages - 1 );
			const showPreviousButton = this.currentIndex > 0;

			this.ui.updateControls( showNextButton, showPreviousButton );
		}

		/**
		 * Loads and sets the specified image. It also updates the controls.
		 *
		 * @param {LightboxInterface} ui image container
		 * @param {Thumbnail} thumbnail thumbnail information
		 * @param {HTMLImageElement} imageElement
		 * @param {ThumbnailWidth} imageWidths
		 */
		setImage( ui, thumbnail, imageElement, imageWidths ) {
			ui.canvas.setImageAndMaxDimensions( thumbnail, imageElement, imageWidths );
			this.updateControls();
		}

		/**
		 * Loads a specified image.
		 *
		 * @param {LightboxImage} image
		 * @param {HTMLImageElement} initialImage A thumbnail to use as placeholder while the image loadsx
		 */
		loadImage( image, initialImage ) {
			const $initialImage = $( initialImage );
			const extraStatsDeferred = $.Deferred();

			const pluginsPromise = this.loadExtensionPlugins( image.filePageTitle.getExtension().toLowerCase() );

			this.currentIndex = image.index;

			this.currentImageFileTitle = image.filePageTitle;

			if ( !this.isOpen ) {
				$( document ).trigger( $.Event( 'mmv-setup-overlay' ) );
				this.ui.open();
				this.isOpen = true;
			} else {
				this.ui.empty();
			}

			this.setTitle();

			// At this point we can't show the thumbnail because we don't
			// know what size it should be. We still assign it to allow for
			// size calculations in getCurrentImageWidths, which needs to know
			// the aspect ratio
			// eslint-disable-next-line mediawiki/class-doc
			$initialImage.hide()
				.removeAttr( 'style' )
				.removeClass()
				.addClass( `mw-mmv-placeholder-image ${ image.filePageTitle.getExtension().toLowerCase() }` );

			this.ui.canvas.set( image, $initialImage );

			this.preloadImagesMetadata();
			this.preloadThumbnails();
			// this.preloadFullscreenThumbnail( image ); // disabled - #474
			const imageWidths = this.ui.canvas.getCurrentImageWidths();

			const start = Date.now();

			const imagePromise = this.fetchThumbnailForLightboxImage( image, imageWidths.real, extraStatsDeferred );

			this.resetBlurredThumbnailStates();
			if ( imagePromise.state() === 'pending' ) {
				this.displayPlaceholderThumbnail( image, $initialImage, imageWidths );
			}

			this.setupProgressBar( image, imagePromise, imageWidths.real );

			const metadataPromise = this.fetchSizeIndependentLightboxInfo( image.filePageTitle );

			imagePromise.then(
				// done
				( thumbnail, imageElement ) => {
					if ( this.currentIndex !== image.index ) {
						return;
					}

					// eslint-disable-next-line mediawiki/class-doc
					imageElement.className = `mw-mmv-final-image ${ image.filePageTitle.getExtension().toLowerCase() }`;
					imageElement.alt = image.alt;

					$.when( metadataPromise, pluginsPromise ).then( ( metadata ) => {
						$( document ).trigger( $.Event( 'mmv-metadata', { viewer: this, image: image, imageInfo: metadata[ 0 ] } ) );
					} );

					this.displayRealThumbnail( thumbnail, imageElement, imageWidths, Date.now() - start );

					return $.Deferred().resolve( thumbnail, imageElement );
				},
				// fail
				( error ) => {
					this.ui.canvas.showError( error );
					return $.Deferred().reject( error );
				}
			);

			metadataPromise.then(
				// done
				( imageInfo, repoInfo ) => {
					extraStatsDeferred.resolve( { uploadTimestamp: imageInfo.anonymizedUploadDateTime } );

					if ( this.currentIndex !== image.index ) {
						return;
					}

					this.ui.panel.setImageInfo( image, imageInfo, repoInfo );

					// File reuse steals a bunch of information from the DOM, so do it last
					this.ui.setFileReuseData( imageInfo, repoInfo, image.caption, image.alt );

					return $.Deferred().resolve( imageInfo, repoInfo );
				},
				// fail
				( error ) => {
					extraStatsDeferred.reject();

					if ( this.currentIndex === image.index ) {
						// Set title to caption or file name if caption is not available;
						// see setTitle() in mmv.ui.metadataPanel for extended caption fallback
						this.ui.panel.showError( image.caption || image.filePageTitle.getNameText(), error );
					}

					return $.Deferred().reject( error );
				}
			);

			$.when( imagePromise, metadataPromise ).then( () => {
				if ( this.currentIndex !== image.index ) {
					return;
				}

				this.ui.panel.scroller.animateMetadataOnce();
				this.preloadDependencies();
			} );
		}

		/**
		 * Loads an image by its title
		 *
		 * @param {mw.Title} title
		 */
		loadImageByTitle( title ) {
			if ( !this.thumbs || !this.thumbs.length ) {
				return;
			}

			const thumb = this.thumbs.find( ( t ) => t.title.getPrefixedText() === title.getPrefixedText() );

			if ( !thumb ) {
				this.onTitleNotFound( title );
				return;
			}

			this.loadImage( thumb.image, thumb.$thumb.clone()[ 0 ] );
		}

		/**
		 * When the image to load is not present on the current page,
		 * a notification is shown to the user and the MMV is closed.
		 *
		 * @param {mw.Title} title
		 * @private
		 */
		onTitleNotFound( title ) {
			this.close();
			const text = mw.message( 'multimediaviewer-file-not-found-error', title.getMainText() ).text();
			const $link = $( '<a>' ).text( mw.message( 'multimediaviewer-file-page' ).text() ).prop( 'href', title.getUrl() );
			const $message = $( '<div>' ).text( text ).append( $( '<br>' ) ).append( $link );
			mw.notify( $message );
		}

		/**
		 * Resets the cross-request states needed to handle the blurred thumbnail logic.
		 */
		resetBlurredThumbnailStates() {
			this.realThumbnailShown = false;
			this.blurredThumbnailShown = false;
		}

		/**
		 * Display the real, full-resolution, thumbnail that was fetched with fetchThumbnail
		 *
		 * @param {Thumbnail} thumbnail
		 * @param {HTMLImageElement} imageElement
		 * @param {ThumbnailWidth} imageWidths
		 * @param {number} loadTime Time it took to load the thumbnail
		 */
		displayRealThumbnail( thumbnail, imageElement, imageWidths, loadTime ) {
			this.realThumbnailShown = true;

			this.setImage( this.ui, thumbnail, imageElement, imageWidths );

			// We only animate unblurWithAnimation if the image wasn't loaded from the cache
			// A load in < 100ms is fast enough (maybe even browser cache hit) that
			// using a 300ms animation would needlessly deter from a fast experience.
			if ( this.blurredThumbnailShown && loadTime > 100 ) {
				this.ui.canvas.unblurWithAnimation();
			} else {
				this.ui.canvas.unblur();
			}

			this.viewLogger.attach( thumbnail.url );
		}

		/**
		 * Display the blurred thumbnail from the page
		 *
		 * @param {LightboxImage} image
		 * @param {jQuery} $initialImage The thumbnail from the page
		 * @param {ThumbnailWidth} imageWidths
		 * @param {boolean} [recursion=false] for internal use, never set this when calling from outside
		 */
		displayPlaceholderThumbnail( image, $initialImage, imageWidths, recursion ) {
			const size = { width: image.originalWidth, height: image.originalHeight };

			// If the actual image has already been displayed, there's no point showing the blurry one.
			// This can happen if the API request to get the original image size needed to show the
			// placeholder thumbnail takes longer then loading the actual thumbnail.
			if ( this.realThumbnailShown ) {
				return;
			}

			// Width/height of the original image are added to the HTML by MediaViewer via a PHP hook,
			// and can be missing in exotic circumstances, e. g. when the extension has only been
			// enabled recently and the HTML cache has not cleared yet. If that is the case, we need
			// to fetch the size from the API first.
			if ( !size.width || !size.height ) {
				if ( recursion ) {
					// this should not be possible, but an infinite recursion is nasty
					// business, so we make a sense check
					throw new Error( 'MediaViewer internal error: displayPlaceholderThumbnail recursion' );
				}
				this.imageInfoProvider.get( image.filePageTitle ).done( ( imageInfo ) => {
					// Make sure the user has not navigated away while we were waiting for the size
					if ( this.currentIndex === image.index ) {
						image.originalWidth = imageInfo.width;
						image.originalHeight = imageInfo.height;
						this.displayPlaceholderThumbnail( image, $initialImage, imageWidths, true );
					}
				} );
			} else {
				this.blurredThumbnailShown = this.ui.canvas.maybeDisplayPlaceholder(
					size, $initialImage, imageWidths );
			}
		}

		/**
		 * Displays a progress bar for the image loading, if necessary, and sets up handling of
		 * all the related callbacks.
		 *
		 * @param {LightboxImage} image
		 * @param {jQuery.Promise.<Thumbnail, HTMLImageElement>} imagePromise
		 * @param {number} imageWidth needed for caching progress (FIXME)
		 */
		setupProgressBar( image, imagePromise, imageWidth ) {
			const progressBar = this.ui.panel.progressBar;
			const key = `${ image.filePageTitle.getPrefixedDb() }|${ imageWidth }`;

			if ( !this.progressCache[ key ] ) {
				// Animate progress bar to 5 to give a sense that something is happening, and make sure
				// the progress bar is noticeable, even if we're sitting at 0% stuck waiting for
				// server-side processing, such as thumbnail (re)generation
				progressBar.jumpTo( 0 );
				progressBar.animateTo( 5 );
				this.progressCache[ key ] = 5;
			} else {
				progressBar.jumpTo( this.progressCache[ key ] );
			}

			// FIXME would be nice to have a "filtered" promise which does not fire when the image is not visible
			imagePromise.then(
				// done
				( thumbnail, imageElement ) => {
					this.progressCache[ key ] = 100;
					if ( this.currentIndex === image.index ) {
						// Fallback in case the browser doesn't have fancy progress updates
						progressBar.animateTo( 100 );

						// Hide progress bar, we're done
						progressBar.hide();
					}

					return $.Deferred().resolve( thumbnail, imageElement );
				},
				// fail
				( error ) => {
					this.progressCache[ key ] = 100;

					if ( this.currentIndex === image.index ) {
						// Hide progress bar on error
						progressBar.hide();
					}

					return $.Deferred().reject( error );
				},
				// progress
				( progress ) => {
					// We pretend progress is always at least 5%, so progress events below 5% should be ignored
					// 100 will be handled by the done handler, do not mix two animations
					if ( progress >= 5 && progress < 100 ) {
						this.progressCache[ key ] = progress;

						// Touch the UI only if the user is looking at this image
						if ( this.currentIndex === image.index ) {
							progressBar.animateTo( progress );
						}
					}

					return progress;
				}
			);
		}

		/**
		 * Orders lightboximage indexes for preloading. Works similar to $.each, except it only takes
		 * the callback argument. Calls the callback with each lightboximage index in some sequence
		 * that is ideal for preloading.
		 *
		 * @private
		 * @param {function(number, LightboxImage)} callback
		 */
		eachPreloadableLightboxIndex( callback ) {
			for ( let i = 0; i <= this.preloadDistance; i++ ) {
				if ( this.currentIndex + i < this.thumbs.length ) {
					callback(
						this.currentIndex + i,
						this.thumbs[ this.currentIndex + i ].image,
						this.thumbs[ this.currentIndex + i ].extraStatsDeferred
					);
				}
				if ( i && this.currentIndex - i >= 0 ) { // skip duplicate for i==0
					callback(
						this.currentIndex - i,
						this.thumbs[ this.currentIndex - i ].image,
						this.thumbs[ this.currentIndex - i ].extraStatsDeferred
					);
				}
			}
		}

		/**
		 * A helper function to fill up the preload queues.
		 * taskFactory(lightboxImage) should return a preload task for the given lightboximage.
		 *
		 * @private
		 * @param {function(LightboxImage)} taskFactory
		 * @return {TaskQueue}
		 */
		pushLightboxImagesIntoQueue( taskFactory ) {
			const queue = new TaskQueue();

			this.eachPreloadableLightboxIndex( ( i, lightboxImage, extraStatsDeferred ) => {
				queue.push( taskFactory( lightboxImage, extraStatsDeferred ) );
			} );

			return queue;
		}

		/**
		 * Cancels in-progress image metadata preloading.
		 */
		cancelImageMetadataPreloading() {
			if ( this.metadataPreloadQueue ) {
				this.metadataPreloadQueue.cancel();
			}
		}

		/**
		 * Cancels in-progress image thumbnail preloading.
		 */
		cancelThumbnailsPreloading() {
			if ( this.thumbnailPreloadQueue ) {
				this.thumbnailPreloadQueue.cancel();
			}
		}

		/**
		 * Preload metadata for next and prev N image (N = MMVP.preloadDistance).
		 * Two images will be loaded at a time (one forward, one backward), with closer images
		 * being loaded sooner.
		 */
		preloadImagesMetadata() {
			this.cancelImageMetadataPreloading();

			this.metadataPreloadQueue = this.pushLightboxImagesIntoQueue( ( lightboxImage, extraStatsDeferred ) => {
				return () => {
					const metadataPromise = this.fetchSizeIndependentLightboxInfo( lightboxImage.filePageTitle );
					metadataPromise.done( ( imageInfo ) => {
						extraStatsDeferred.resolve( { uploadTimestamp: imageInfo.anonymizedUploadDateTime } );
					} ).fail( () => {
						extraStatsDeferred.reject();
					} );
					return metadataPromise;
				};
			} );

			this.metadataPreloadQueue.execute();
		}

		/**
		 * Preload thumbnails for next and prev N image (N = MMVP.preloadDistance).
		 * Two images will be loaded at a time (one forward, one backward), with closer images
		 * being loaded sooner.
		 */
		preloadThumbnails() {
			this.cancelThumbnailsPreloading();

			this.thumbnailPreloadQueue = this.pushLightboxImagesIntoQueue( ( lightboxImage, extraStatsDeferred ) => {
				return () => {
					// viewer.ui.canvas.getLightboxImageWidths needs the viewer to be open
					// because it needs to read the size of visible elements
					if ( !this.isOpen ) {
						return;
					}

					const imageWidths = this.ui.canvas.getLightboxImageWidths( lightboxImage );

					return this.fetchThumbnailForLightboxImage( lightboxImage, imageWidths.real, extraStatsDeferred );
				};
			} );

			this.thumbnailPreloadQueue.execute();
		}

		/**
		 * Preload the fullscreen size of the current image.
		 *
		 * @param {LightboxImage} image
		 */
		preloadFullscreenThumbnail( image ) {
			const imageWidths = this.ui.canvas.getLightboxImageWidthsForFullscreen( image );

			this.fetchThumbnailForLightboxImage( image, imageWidths.real );
		}

		/**
		 * Loads all the size-independent information needed by the lightbox (image metadata, repo
		 * information).
		 *
		 * @param {mw.Title} fileTitle Title of the file page for the image.
		 * @return {jQuery.Promise.<Image, Repo>}
		 */
		fetchSizeIndependentLightboxInfo( fileTitle ) {
			const imageInfoPromise = this.imageInfoProvider.get( fileTitle );
			const repoInfoPromise = this.fileRepoInfoProvider.get( fileTitle );

			return $.when(
				imageInfoPromise, repoInfoPromise
			).then( ( imageInfo, repoInfoHash ) => {
				return $.Deferred().resolve( imageInfo, repoInfoHash[ imageInfo.repo ] );
			} );
		}

		/**
		 * Loads size-dependent components of a lightbox - the thumbnail model and the image itself.
		 *
		 * @param {LightboxImage} image
		 * @param {number} width the width of the requested thumbnail
		 * @param {jQuery.Deferred.<string>} [extraStatsDeferred] Promise that resolves to the image's upload timestamp when the metadata is loaded
		 * @return {jQuery.Promise.<Thumbnail, HTMLImageElement>}
		 */
		fetchThumbnailForLightboxImage( image, width, extraStatsDeferred ) {
			return this.fetchThumbnail(
				image.filePageTitle,
				width,
				image.src,
				image.originalWidth,
				image.originalHeight,
				extraStatsDeferred
			);
		}

		/**
		 * Loads size-dependent components of a lightbox - the thumbnail model and the image itself.
		 *
		 * @param {mw.Title} fileTitle
		 * @param {number} width the width of the requested thumbnail
		 * @param {string} [sampleUrl] a thumbnail URL for the same file (but with different size) (might be missing)
		 * @param {number} [originalWidth] the width of the original, full-sized file (might be missing)
		 * @param {number} [originalHeight] the height of the original, full-sized file (might be missing)
		 * @param {jQuery.Deferred.<string>} [extraStatsDeferred] Promise that resolves to the image's upload timestamp when the metadata is loaded
		 * @return {jQuery.Promise.<Thumbnail, HTMLImageElement>} A promise resolving to
		 *  a thumbnail model and an <img> element. It might or might not have progress events which
		 *  return a single number.
		 */
		fetchThumbnail( fileTitle, width, sampleUrl, originalWidth, originalHeight, extraStatsDeferred ) {
			let guessing = false;
			const combinedDeferred = $.Deferred();
			let thumbnailPromise;
			let imagePromise;

			if ( fileTitle.getExtension().toLowerCase() !== 'svg' && originalWidth && width > originalWidth ) {
				// Do not request images larger than the original image
				width = originalWidth;
			}

			if ( sampleUrl && originalWidth && originalHeight && this.config.useThumbnailGuessing() ) {
				guessing = true;
				thumbnailPromise = this.guessedThumbnailInfoProvider.get(
					fileTitle, sampleUrl, width, originalWidth, originalHeight
				).then( null, () => this.thumbnailInfoProvider.get( fileTitle, width ) );
			} else {
				thumbnailPromise = this.thumbnailInfoProvider.get( fileTitle, width );
			}

			// Add thumbnail width to the extra stats passed to the performance log
			extraStatsDeferred = $.when( extraStatsDeferred || {} ).then( ( extraStats ) => {
				extraStats.imageWidth = width;
				return extraStats;
			} );

			imagePromise = thumbnailPromise.then( ( thumbnail ) => this.imageProvider.get( thumbnail.url, extraStatsDeferred ) );

			if ( guessing ) {
				// If we guessed wrong, need to retry with real URL on failure.
				// As a side effect this introduces an extra (harmless) retry of a failed thumbnailInfoProvider.get call
				// because thumbnailInfoProvider.get is already called above when guessedThumbnailInfoProvider.get fails.
				imagePromise = imagePromise
					.then( null, () => this.thumbnailInfoProvider.get( fileTitle, width )
						.then( ( thumbnail ) => this.imageProvider.get( thumbnail.url, extraStatsDeferred ) ) );
			}

			// In jQuery<3, $.when used to also relay notify, but that is no longer
			// the case - but we still want to pass it along...
			$.when( thumbnailPromise, imagePromise ).then( combinedDeferred.resolve, combinedDeferred.reject );
			imagePromise.then( null, null, ( arg, progress ) => {
				combinedDeferred.notify( progress );
			} );
			return combinedDeferred;
		}

		/**
		 * Loads an image at a specified index in the viewer's thumbnail array.
		 *
		 * @param {number} index
		 */
		loadIndex( index ) {
			if ( index < this.thumbs.length && index >= 0 ) {
				this.viewLogger.recordViewDuration();

				const thumb = this.thumbs[ index ];
				this.loadImage( thumb.image, thumb.$thumb.clone()[ 0 ] );
				router.navigateTo( null, {
					path: getMediaHash( thumb.title ),
					useReplaceState: true
				} );
			}
		}

		/**
		 * Opens the last image
		 */
		firstImage() {
			this.loadIndex( 0 );
		}

		/**
		 * Opens the last image
		 */
		lastImage() {
			this.loadIndex( this.thumbs.length - 1 );
		}

		/**
		 * Opens the next image
		 */
		nextImage() {
			this.loadIndex( this.currentIndex + 1 );
		}

		/**
		 * Opens the previous image
		 */
		prevImage() {
			this.loadIndex( this.currentIndex - 1 );
		}

		/**
		 * Handles close event coming from the lightbox
		 */
		close() {
			this.viewLogger.recordViewDuration();
			this.viewLogger.unattach();

			if ( comingFromHashChange ) {
				comingFromHashChange = false;
			} else {
				this.router.back();
			}
			// update title after route change, see T225387
			document.title = this.createDocumentTitle( null );

			// This has to happen after the hash reset, because setting the hash to # will reset the page scroll
			$( document ).trigger( $.Event( 'mmv-cleanup-overlay' ) );

			this.isOpen = false;
		}

		/**
		 * Sets up the route handlers
		 */
		setupRouter() {
			// handle empty hashes, and anchor links (page sections)
			this.router.addRoute( /^[^/]*$/, () => {
				if ( this.isOpen ) {
					comingFromHashChange = true;
					document.title = this.createDocumentTitle( null );
					if ( this.ui ) {
						// FIXME triggers mmv-close event, which calls viewer.close()
						this.ui.unattach();
					} else {
						this.close();
					}
				}
			} );
		}

		/**
		 * Updates the page title to reflect the current title.
		 */
		setTitle() {
			// update title after route change, see T225387
			document.title = this.createDocumentTitle( this.currentImageFileTitle );
		}

		/**
		 * Creates a string which can be shown as document title (the text at the top of the browser window).
		 *
		 * @param {mw.Title|null} imageTitle the title object for the image which is displayed; null when the
		 *  viewer is being closed
		 * @return {string}
		 */
		createDocumentTitle( imageTitle ) {
			if ( imageTitle ) {
				return `${ imageTitle.getNameText() } - ${ this.documentTitle }`;
			} else {
				return this.documentTitle;
			}
		}

		/**
		 * Fired when the viewer is closed. This is used by the lightbox to notify the main app.
		 *
		 * @event MultimediaViewer#mmv-close
		 */

		/**
		 * Fired when the user requests the next image.
		 *
		 * @event MultimediaViewer#mmv-next
		 */

		/**
		 * Fired when the user requests the previous image.
		 *
		 * @event MultimediaViewer#mmv-prev
		 */

		/**
		 * Fired when the screen size changes. Debounced to avoid continuous triggering while resizing with a mouse.
		 *
		 * @event MultimediaViewer#mmv-resize-end
		 */

		/**
		 * Used by components to request a thumbnail URL for the current thumbnail, with a given size.
		 *
		 * @event MultimediaViewer#mmv-request-thumbnail
		 * @param {number} size
		 */

		/**
		 * Registers all event handlers
		 */
		setupEventHandlers() {
			this.ui.connect( this, {
				first: 'firstImage',
				last: 'lastImage',
				next: 'nextImage',
				prev: 'prevImage'
			} );

			$( document ).on( 'mmv-close.mmvp', () => {
				this.close();
			} ).on( 'mmv-resize-end.mmvp', () => {
				this.resize( this.ui );
			} ).on( 'mmv-request-thumbnail.mmvp', ( e, size ) => {
				if ( this.currentImageFileTitle ) {
					return this.thumbnailInfoProvider.get( this.currentImageFileTitle, size );
				} else {
					return $.Deferred().reject();
				}
			} ).on( 'mmv-viewfile.mmvp', () => {
				this.imageInfoProvider.get( this.currentImageFileTitle ).done( ( imageInfo ) => {
					document.location = imageInfo.url;
				} );
			} );
		}

		/**
		 * Unregisters all event handlers. Currently only used in tests.
		 */
		cleanupEventHandlers() {
			$( document ).off( 'mmv-close.mmvp mmv-resize-end.mmvp' );

			this.ui.disconnect( this );
		}

		/**
		 * Preloads JS and CSS dependencies that aren't needed to display the first image, but could be needed later
		 */
		preloadDependencies() {
			mw.loader.load( [ 'mmv.ui.reuse.shareembed' ] );
		}

		/**
		 * Loads the RL module defined for a given file extension, if any
		 *
		 * @param {string} extension File extension
		 * @return {jQuery.Promise}
		 */
		loadExtensionPlugins( extension ) {
			const deferred = $.Deferred();
			const config = this.config.extensions();

			if ( !( extension in config ) || config[ extension ] === 'default' ) {
				return deferred.resolve();
			}

			mw.loader.using( config[ extension ], () => {
				deferred.resolve();
			} );

			return deferred;
		}
	}

	/**
	 * Image loading progress. Keyed by image (database) name + '|' + thumbnail width in pixels,
	 * value is undefined, 'blurred' or 'real' (meaning respectively that no thumbnail is shown
	 * yet / the thumbnail that existed on the page is shown, enlarged and blurred / the real,
	 * correct-size thumbnail is shown).
	 *
	 * @private
	 * @property {Object.<string, string>}
	 */
	MultimediaViewer.prototype.thumbnailStateCache = {};

	/**
	 * Image loading progress. Keyed by image (database) name + '|' + thumbnail width in pixels,
	 * value is a number between 0-100.
	 *
	 * @private
	 * @property {Object.<string, number>}
	 */
	MultimediaViewer.prototype.progressCache = {};

	/**
	 * Preload this many prev/next images to speed up navigation.
	 * (E.g. preloadDistance = 3 means that the previous 3 and the next 3 images will be loaded.)
	 * Preloading only happens when the viewer is open.
	 *
	 * @property {number}
	 */
	MultimediaViewer.prototype.preloadDistance = 1;

	/**
	 * Stores image metadata preloads, so they can be cancelled.
	 *
	 * @property {TaskQueue}
	 */
	MultimediaViewer.prototype.metadataPreloadQueue = null;

	/**
	 * Stores image thumbnail preloads, so they can be cancelled.
	 *
	 * @property {TaskQueue}
	 */
	MultimediaViewer.prototype.thumbnailPreloadQueue = null;

	module.exports = {
		Api,
		Canvas,
		CanvasButtons,
		Description,
		Dialog,
		DownloadDialog,
		FileRepoInfo,
		ForeignApiRepo,
		ForeignDbRepo,
		GuessedThumbnailInfo,
		ImageInfo,
		ImageModel,
		ImageProvider,
		IwTitle,
		License,
		LightboxImage,
		LightboxInterface,
		MetadataPanel,
		MetadataPanelScroller,
		MultimediaViewer,
		OptionsDialog,
		Permission,
		ProgressBar,
		Repo,
		ReuseDialog,
		StripeButtons,
		TaskQueue,
		Thumbnail,
		ThumbnailInfo,
		ThumbnailWidth,
		ThumbnailWidthCalculator,
		TruncatableTextField,
		UiElement,
		ViewLogger
	};
}() );
