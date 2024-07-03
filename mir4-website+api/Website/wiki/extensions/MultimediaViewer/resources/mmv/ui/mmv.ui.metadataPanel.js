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

const { HtmlUtils } = require( 'mmv.bootstrap' );
const Description = require( './mmv.ui.description.js' );
const UiElement = require( './mmv.ui.js' );
const MetadataPanelScroller = require( './mmv.ui.metadataPanelScroller.js' );
const Permission = require( './mmv.ui.permission.js' );
const ProgressBar = require( './mmv.ui.progressBar.js' );
const StripeButtons = require( './mmv.ui.stripeButtons.js' );
const TruncatableTextField = require( './mmv.ui.truncatableTextField.js' );

( function () {

	/**
	 * Represents the metadata panel in the viewer
	 */
	class MetadataPanel extends UiElement {
		/**
		 * @param {jQuery} $container The container for the panel (.mw-mmv-post-image).
		 * @param {jQuery} $aboveFold The brighter headline of the metadata panel (.mw-mmv-above-fold).
		 *  Called "aboveFold" for historical reasons, but actually a part of the next sibling of the element
		 *  is also above the fold (bottom of the screen).
		 * @param {mw.SafeStorage} localStorage the localStorage object, for dependency injection
		 * @param {Config} config A configuration object.
		 */
		constructor( $container, $aboveFold, localStorage, config ) {
			super( $container );

			this.$aboveFold = $aboveFold;

			/** @property {Config} config - */
			this.config = config;

			/** @property {HtmlUtils} htmlUtils - */
			this.htmlUtils = new HtmlUtils();

			this.initializeHeader( localStorage );
			this.initializeImageMetadata();
			this.initializeAboutLinks();
		}

		/**
		 * FIXME this should be in the jquery.fullscreen plugin.
		 *
		 * @return {boolean}
		 */
		isFullscreened() {
			return $( this.$container ).closest( '.jq-fullscreened' ).length > 0;
		}

		attach() {
			this.scroller.attach();
			this.buttons.attach();
			this.title.attach();
			this.creditField.attach();

			this.$title
				.add( this.$authorAndSource )
				.add( this.title.$ellipsis )
				.add( this.creditField.$ellipsis )
				.on( 'click.mmv-mp', ( e ) => {
					const clickTargetIsLink = $( e.target ).is( 'a' );
					const clickTargetIsTruncated = !!$( e.target ).closest( '.mw-mmv-ttf-truncated' ).length;
					const someTextIsExpanded = !!$( e.target ).closest( '.mw-mmv-untruncated' ).length;

					if ( !clickTargetIsLink && // don't interfere with clicks on links in the text
						clickTargetIsTruncated && // don't expand when non-truncated text is clicked
						!someTextIsExpanded // ignore clicks if text is already expanded
					) {
						if ( this.isFullscreened() ) {
							this.revealTruncatedText();
						} else {
							this.scroller.toggle( 'up' );
						}
					}
				} );

			$( this.$container ).on( 'mmv-metadata-open.mmv-mp mmv-metadata-reveal-truncated-text.mmv-mp', () => {
				this.revealTruncatedText();
			} ).on( 'mmv-metadata-close.mmv-mp', () => {
				this.hideTruncatedText();
			} ).on( 'mouseleave.mmv-mp', () => {
				if ( this.isFullscreened() ) {
					const duration = parseFloat( this.$container.css( 'transition-duration' ) ) * 1000 || 0;
					this.panelShrinkTimeout = setTimeout( () => {
						this.hideTruncatedText();
					}, duration );
				}
			} ).on( 'mouseenter.mmv-mp', () => {
				clearTimeout( this.panelShrinkTimeout );
			} ).on( 'mmv-permission-grow.mmv-mp', () => {
				this.$permissionLink
					.text( mw.message( 'multimediaviewer-permission-link-hide' ).text() );
			} ).on( 'mmv-permission-shrink.mmv-mp', () => {
				this.$permissionLink
					.text( mw.message( 'multimediaviewer-permission-link' ).text() );
			} );

			this.handleEvent( 'fullscreenchange.lip', () => {
				this.hideTruncatedText();
			} );
		}

		unattach() {
			this.scroller.freezeHeight();

			this.$title
				.add( this.title.$ellipsis )
				.add( this.$authorAndSource )
				.add( this.creditField.$ellipsis )
				.off( 'click.mmv-mp' );

			$( this.$container ).off( '.mmv-mp' );

			this.scroller.unattach();
			this.buttons.unattach();
			this.clearEvents();
		}

		empty() {
			this.scroller.freezeHeight();
			this.scroller.empty();

			this.buttons.empty();

			this.description.empty();
			this.permission.empty();

			this.$title.removeClass( 'error' );
			this.title.empty();
			this.creditField.empty();

			this.$license.empty().prop( 'href', '#' );
			this.$licenseLi.addClass( 'empty' );
			this.$permissionLink.hide();
			this.$restrictions.children().hide();

			this.$filename.empty();
			this.$filenamePrefix.empty();
			this.$filenameLi.addClass( 'empty' );

			this.$datetimeCreated.empty();
			this.$datetimeCreatedLi.addClass( 'empty' );
			this.$datetimeUpdated.empty();
			this.$datetimeUpdatedLi.addClass( 'empty' );

			this.$location.empty();
			this.$locationLi.addClass( 'empty' );

			this.progressBar.empty();

			this.$container.removeClass( 'mw-mmv-untruncated' );
		}

		/* Initialization methods */
		/**
		 * Initializes the header, which contains the title, credit, and license elements.
		 *
		 * @param {mw.SafeStorage} localStorage the localStorage object, for dependency injection
		 */
		initializeHeader( localStorage ) {
			this.progressBar = new ProgressBar( this.$aboveFold );

			this.scroller = new MetadataPanelScroller( this.$container, this.$aboveFold,
				localStorage );

			this.$titleDiv = $( '<div>' )
				.addClass( 'mw-mmv-title-contain' )
				.appendTo( this.$aboveFold );

			this.$container.append( this.$aboveFold );

			this.initializeButtons(); // float, needs to be on top
			this.initializeTitle();
		}

		/**
		 * Initializes the title elements.
		 */
		initializeTitle() {
			this.$titlePara = $( '<p>' )
				.addClass( 'mw-mmv-title-para mw-parser-output' )
				.appendTo( this.$aboveFold );

			this.$title = $( '<span>' )
				.addClass( 'mw-mmv-title' );

			this.title = new TruncatableTextField( this.$titlePara, this.$title, {
				styles: [ 'mw-mmv-title-small', 'mw-mmv-title-smaller' ]
			} );
			this.title.setTitle(
				mw.message( 'multimediaviewer-title-popup-text' ),
				mw.message( 'multimediaviewer-title-popup-text-more' )
			);

			this.$title.add( this.title.$ellipsis );
		}

		initializeButtons() {
			this.buttons = new StripeButtons( this.$titleDiv );
		}

		/**
		 * Initializes the main body of metadata elements.
		 */
		initializeImageMetadata() {
			this.$container.addClass( 'mw-mmv-ttf-ellipsis-container' );

			this.$imageMetadata = $( '<div>' )
				.addClass( 'mw-mmv-image-metadata' )
				.appendTo( this.$container );

			this.$imageMetadataLeft = $( '<div>' )
				.addClass( 'mw-mmv-image-metadata-column mw-mmv-image-metadata-desc-column' )
				.appendTo( this.$imageMetadata );

			this.$imageMetadataRight = $( '<div>' )
				.addClass( 'mw-mmv-image-metadata-column mw-mmv-image-metadata-links-column' )
				.appendTo( this.$imageMetadata );

			this.initializeCredit();
			this.description = new Description( this.$imageMetadataLeft );
			this.permission = new Permission( this.$imageMetadataLeft, this.scroller );
			this.initializeImageLinks();
		}

		/**
		 * Initializes the credit elements.
		 */
		initializeCredit() {
			this.$credit = $( '<p>' )
				.addClass( 'mw-mmv-credit empty' )
				.appendTo( this.$imageMetadataLeft );

			// we need an inline container for tipsy, otherwise it would be centered weirdly
			this.$authorAndSource = $( '<span>' )
				.addClass( 'mw-mmv-source-author' );

			this.creditField = new TruncatableTextField(
				this.$credit,
				this.$authorAndSource,
				{ styles: [] }
			);

			this.creditField.setTitle(
				mw.message( 'multimediaviewer-credit-popup-text' ),
				mw.message( 'multimediaviewer-credit-popup-text-more' )
			);

			this.$authorAndSource.add( this.creditField.$ellipsis );
		}

		/**
		 * Initializes the list of image metadata on the right side of the panel.
		 */
		initializeImageLinks() {
			this.$imageLinkDiv = $( '<div>' )
				.addClass( 'mw-mmv-image-links-div' )
				.appendTo( this.$imageMetadataRight );

			this.$imageLinks = $( '<ul>' )
				.addClass( 'mw-mmv-image-links' )
				.appendTo( this.$imageLinkDiv );

			this.initializeLicense();
			this.initializeFilename();
			this.initializeDatetime();
			this.initializeLocation();
		}

		/**
		 * Initializes the license elements.
		 */
		initializeLicense() {
			this.$licenseLi = $( '<li>' )
				.addClass( 'mw-mmv-license-li empty' )
				.appendTo( this.$imageLinks );

			this.$license = $( '<a>' )
				.addClass( 'mw-mmv-license' )
				.prop( 'href', '#' )
				.appendTo( this.$licenseLi );

			this.$restrictions = $( '<span>' )
				.addClass( 'mw-mmv-restrictions' )
				.appendTo( this.$licenseLi );

			this.$permissionLink = $( '<span>' )
				.addClass( 'mw-mmv-permission-link mw-mmv-label' )
				.text( mw.message( 'multimediaviewer-permission-link' ).text() )
				.appendTo( this.$licenseLi )
				.hide()
				.on( 'click', () => {
					if ( this.permission.isFullSize() ) {
						this.permission.shrink();
					} else {
						this.permission.grow();
						this.scroller.toggle( 'up' );
					}
					return false;
				} );
		}

		/**
		 * Initializes the filename element.
		 */
		initializeFilename() {
			this.$filenameLi = $( '<li>' )
				.addClass( 'mw-mmv-filename-li empty' )
				.appendTo( this.$imageLinks );

			this.$filenamePrefix = $( '<span>' )
				.addClass( 'mw-mmv-filename-prefix' )
				.appendTo( this.$filenameLi );

			this.$filename = $( '<span>' )
				.addClass( 'mw-mmv-filename' )
				.appendTo( this.$filenameLi );
		}

		/**
		 * Initializes the upload date/time element.
		 */
		initializeDatetime() {
			this.$datetimeCreatedLi = $( '<li>' )
				.addClass( 'mw-mmv-datetime-li empty' )
				.appendTo( this.$imageLinks );

			this.$datetimeCreated = $( '<span>' )
				.addClass( 'mw-mmv-datetime' )
				.appendTo( this.$datetimeCreatedLi );

			this.$datetimeUpdatedLi = $( '<li>' )
				.addClass( 'mw-mmv-datetime-li empty' )
				.appendTo( this.$imageLinks );

			this.$datetimeUpdated = $( '<span>' )
				.addClass( 'mw-mmv-datetime' )
				.appendTo( this.$datetimeUpdatedLi );
		}

		/**
		 * Initializes the geolocation element.
		 */
		initializeLocation() {
			this.$locationLi = $( '<li>' )
				.addClass( 'mw-mmv-location-li empty' )
				.appendTo( this.$imageLinks );

			this.$location = $( '<a>' )
				.addClass( 'mw-mmv-location' )
				.appendTo( this.$locationLi );
		}

		/**
		 * Initializes two about links at the bottom of the panel.
		 */
		initializeAboutLinks() {
			this.$mmvAboutLink = $( '<a>' )
				.prop( 'href', mw.config.get( 'wgMultimediaViewer' ).infoLink )
				.text( mw.message( 'multimediaviewer-about-mmv' ).text() )
				.addClass( 'mw-mmv-about-link' );

			this.$mmvAboutLinks = $( '<div>' )
				.addClass( 'mw-mmv-about-links' )
				.append(
					this.$mmvAboutLink
				)
				.appendTo( this.$imageMetadata );
		}

		/* Setters */
		/**
		 * Sets the image title at the top of the metadata panel.
		 * The title will be the first one available form the options below:
		 * - the image caption
		 * - the description from the filepage
		 * - the filename (without extension)
		 *
		 * @param {LightboxImage} image
		 * @param {Image} imageData
		 */
		setTitle( image, imageData ) {
			let title;

			if ( image.caption ) {
				title = image.caption;
			} else if ( imageData.description ) {
				title = imageData.description;
			} else {
				title = image.filePageTitle.getNameText();
			}

			this.title.set( title );
		}

		/**
		 * Sets the file name in the panel.
		 *
		 * @param {string} filename The file name to set, without prefix
		 */
		setFileName( filename ) {
			this.$filenamePrefix.text( 'File:' );
			this.$filename.text( filename );

			this.$filenameLi.removeClass( 'empty' );
		}

		/**
		 * Set source and author.
		 *
		 * @param {string} attribution Custom attribution string
		 * @param {string} source With unsafe HTML
		 * @param {string} author With unsafe HTML
		 * @param {number} authorCount
		 * @param {string} filepageUrl URL of the file page (used when other data is not available)
		 */
		setCredit( attribution, source, author, authorCount, filepageUrl ) {
			// sanitization will be done by TruncatableTextField.set()
			if ( attribution && ( authorCount <= 1 || !authorCount ) ) {
				this.creditField.set( this.wrapAttribution( attribution ) );
			} else if ( author && source ) {
				this.creditField.set(
					mw.message(
						'multimediaviewer-credit',
						this.wrapAuthor( author, authorCount, filepageUrl ),
						this.wrapSource( source )
					).plain()
				);
			} else if ( author ) {
				this.creditField.set( this.wrapAuthor( author, authorCount, filepageUrl ) );
			} else if ( source ) {
				this.creditField.set( this.wrapSource( source ) );
			} else {
				this.creditField.set(
					$( '<a>' )
						.addClass( 'mw-mmv-credit-fallback' )
						.prop( 'href', filepageUrl )
						.text( mw.message( 'multimediaviewer-credit-fallback' ).plain() )
						.get( 0 ).outerHTML
				);
			}

			this.$credit.removeClass( 'empty' );
		}

		/**
		 * Wraps a source string it with MediaViewer styles
		 *
		 * @param {string} source Warning - unsafe HTML sometimes goes here
		 * @return {string} unsafe HTML
		 */
		wrapSource( source ) {
			return $( '<span>' )
				.addClass( 'mw-mmv-source' )
				.append( $.parseHTML( source ) )
				.get( 0 ).outerHTML;
		}

		/**
		 * Wraps an author string with MediaViewer styles
		 *
		 * @param {string} author Warning - unsafe HTML sometimes goes here
		 * @param {number} authorCount
		 * @param {string} filepageUrl URL of the file page (used when some author data is not available)
		 * @return {string} unsafe HTML
		 */
		wrapAuthor( author, authorCount, filepageUrl ) {
			const $wrapper = $( '<span>' )
				.addClass( 'mw-mmv-author' );

			if ( authorCount > 1 ) {
				const moreText = this.htmlUtils.jqueryToHtml(
					$( '<a>' )
						.addClass( 'mw-mmv-more-authors' )
						.text( mw.message( 'multimediaviewer-multiple-authors', authorCount - 1 ).text() )
						.attr( 'href', filepageUrl )
				);
				$wrapper.append( mw.message( 'multimediaviewer-multiple-authors-combine', author, moreText ).text() );
			} else {
				$wrapper.append( author );
			}

			return $wrapper.get( 0 ).outerHTML;
		}

		/**
		 * Wraps an attribution string with MediaViewer styles
		 *
		 * @param {string} attribution Warning - unsafe HTML sometimes goes here
		 * @return {string} unsafe HTML
		 */
		wrapAttribution( attribution ) {
			return $( '<span>' )
				.addClass( 'mw-mmv-author' )
				.addClass( 'mw-mmv-source' )
				.append( $.parseHTML( attribution ) )
				.get( 0 ).outerHTML;
		}

		/**
		 * Sets the license display in the panel
		 *
		 * @param {License|null} license license data (could be missing)
		 * @param {string} filePageUrl URL of the file description page
		 */
		setLicense( license, filePageUrl ) {
			let shortName;
			let url;
			let isCc;
			let isPd;

			filePageUrl += `?uselang=${ mw.config.get( 'wgUserLanguage' ) }#${ mw.message( 'license-header' ).text() }`;

			if ( license ) {
				shortName = license.getShortName();
				url = license.deedUrl || filePageUrl;
				isCc = license.isCc();
				isPd = license.isPd();
			} else {
				shortName = mw.message( 'multimediaviewer-license-default' ).text();
				url = filePageUrl;
				isCc = isPd = false;
			}

			this.$license
				.text( shortName )
				.prop( 'href', url )
				.prop( 'target', license && license.deedUrl ? '_blank' : '' );

			this.$licenseLi
				.toggleClass( 'cc-license', isCc )
				.toggleClass( 'pd-license', isPd )
				.removeClass( 'empty' );
		}

		/**
		 * Set an extra permission text which should be displayed.
		 *
		 * @param {string} permission
		 */
		setPermission( permission ) {
			this.$permissionLink.show();
			this.permission.set( permission );
		}

		/**
		 * Sets any special restrictions that should be displayed.
		 *
		 * @param {string[]} restrictions Array of restrictions
		 */
		setRestrictions( restrictions ) {
			const restrictionsSet = {};
			let showDefault = false;
			let validRestrictions = 0;

			restrictions.forEach( ( value, index ) => {
				// The following messages are used here:
				// * multimediaviewer-restriction-2257
				// * multimediaviewer-restriction-aus-reserve
				// * multimediaviewer-restriction-communist
				// * multimediaviewer-restriction-costume
				// * multimediaviewer-restriction-currency
				// * multimediaviewer-restriction-design
				// * multimediaviewer-restriction-fan-art
				// * multimediaviewer-restriction-ihl
				// * multimediaviewer-restriction-insignia
				// * multimediaviewer-restriction-ita-mibac
				// * multimediaviewer-restriction-nazi
				// * multimediaviewer-restriction-personality
				// * multimediaviewer-restriction-trademarked
				// * multimediaviewer-restriction-default
				// * multimediaviewer-restriction-default-and-others
				if ( !mw.message( `multimediaviewer-restriction-${ value }` ).exists() || value === 'default' || index + 1 > MetadataPanel.MAX_RESTRICT ) {
					showDefault = true; // If the restriction isn't defined or there are more than MAX_RESTRICT of them, show a generic symbol at the end
					return;
				}
				if ( restrictionsSet[ value ] ) {
					return; // Only show one of each symbol
				} else {
					restrictionsSet[ value ] = true;
				}

				this.$restrictions.append( this.createRestriction( value ) );
				validRestrictions++; // See how many defined restrictions are added so we know which default i18n msg to use
			} );

			if ( showDefault ) {
				if ( validRestrictions ) {
					this.$restrictions.append( this.createRestriction( 'default-and-others' ) );
				} else {
					this.$restrictions.append( this.createRestriction( 'default' ) );
				}
			}
		}

		/**
		 * Helper function that generates restriction labels
		 *
		 * @param {string} type Restriction type
		 * @return {jQuery} jQuery object of label
		 */
		createRestriction( type ) {
			const $label = $( '<span>' )
				.addClass( 'mw-mmv-label mw-mmv-restriction-label' )
				// Messages duplicated from above for linter
				// * multimediaviewer-restriction-2257
				// * multimediaviewer-restriction-aus-reserve
				// * multimediaviewer-restriction-communist
				// * multimediaviewer-restriction-costume
				// * multimediaviewer-restriction-currency
				// * multimediaviewer-restriction-design
				// * multimediaviewer-restriction-fan-art
				// * multimediaviewer-restriction-ihl
				// * multimediaviewer-restriction-insignia
				// * multimediaviewer-restriction-ita-mibac
				// * multimediaviewer-restriction-nazi
				// * multimediaviewer-restriction-personality
				// * multimediaviewer-restriction-trademarked
				// * multimediaviewer-restriction-default
				// * multimediaviewer-restriction-default-and-others
				.prop( 'title', mw.message( `multimediaviewer-restriction-${ type }` ).text() );

			$( '<span>' )
				// The following classes are used here:
				// * mw-mmv-restriction-2257
				// * mw-mmv-restriction-aus-reserve
				// * mw-mmv-restriction-communist
				// * mw-mmv-restriction-costume
				// * mw-mmv-restriction-currency
				// * mw-mmv-restriction-design
				// * mw-mmv-restriction-fan-art
				// * mw-mmv-restriction-ihl
				// * mw-mmv-restriction-insignia
				// * mw-mmv-restriction-ita-mibac
				// * mw-mmv-restriction-nazi
				// * mw-mmv-restriction-personality
				// * mw-mmv-restriction-trademarked:after
				// * mw-mmv-restriction-default
				.addClass( `mw-mmv-restriction-label-inner mw-mmv-restriction-${ type === 'default-and-others' ? 'default' : type }` )
				.text( mw.message( `multimediaviewer-restriction-${ type }` ).text() )
				.appendTo( $label );

			return $label;
		}

		/**
		 * Sets location data in the interface.
		 *
		 * @param {Image} imageData
		 */
		setLocationData( imageData ) {
			if ( !imageData.hasCoords() ) {
				return;
			}

			const latitude = imageData.latitude >= 0 ? imageData.latitude : imageData.latitude * -1;
			const latmsg = `multimediaviewer-geoloc-${ imageData.latitude >= 0 ? 'north' : 'south' }`;
			const latdeg = Math.floor( latitude );
			let latremain = latitude - latdeg;
			const latmin = Math.floor( ( latremain ) * 60 );

			const longitude = imageData.longitude >= 0 ? imageData.longitude : imageData.longitude * -1;
			const longmsg = `multimediaviewer-geoloc-${ imageData.longitude >= 0 ? 'east' : 'west' }`;
			const longdeg = Math.floor( longitude );
			let longremain = longitude - longdeg;
			const longmin = Math.floor( ( longremain ) * 60 );

			longremain -= longmin / 60;
			latremain -= latmin / 60;
			const latsec = Math.round( latremain * 100 * 60 * 60 ) / 100;
			const longsec = Math.round( longremain * 100 * 60 * 60 ) / 100;

			this.$location.text(
				mw.message( 'multimediaviewer-geolocation',
					mw.message(
						'multimediaviewer-geoloc-coords',

						mw.message(
							'multimediaviewer-geoloc-coord',
							mw.language.convertNumber( latdeg ),
							mw.language.convertNumber( latmin ),
							mw.language.convertNumber( latsec ),
							// The following messages are used here:
							// * multimediaviewer-geoloc-north
							// * multimediaviewer-geoloc-south
							mw.message( latmsg ).text()
						).text(),

						mw.message(
							'multimediaviewer-geoloc-coord',
							mw.language.convertNumber( longdeg ),
							mw.language.convertNumber( longmin ),
							mw.language.convertNumber( longsec ),
							// The following messages are used here:
							// * multimediaviewer-geoloc-east
							// * multimediaviewer-geoloc-west
							mw.message( longmsg ).text()
						).text()
					).text()
				).text()
			);

			this.$location.prop( 'href', (
				'https://geohack.toolforge.org/geohack.php?pagename=' +
				`File:${ imageData.title.getMain()
				}&params=${
					Math.abs( imageData.latitude ) }${ imageData.latitude >= 0 ? '_N_' : '_S_'
				}${ Math.abs( imageData.longitude ) }${ imageData.longitude >= 0 ? '_E_' : '_W_'
				}&language=${ encodeURIComponent( mw.config.get( 'wgUserLanguage' ) ) }`
			) );

			this.$locationLi.removeClass( 'empty' );
		}

		/**
		 * Set all the image information in the panel
		 *
		 * @param {LightboxImage} image
		 * @param {Image} imageData
		 * @param {Repo} repoData
		 */
		setImageInfo( image, imageData, repoData ) {
			if ( imageData.creationDateTime ) {
				this.$datetimeCreated.text(
					mw.message( 'multimediaviewer-datetime-created', this.formatDate( imageData.creationDateTime ) ).text()
				);
				this.$datetimeCreatedLi.removeClass( 'empty' );
			}
			if ( imageData.uploadDateTime ) {
				this.$datetimeUpdated.text(
					mw.message( 'multimediaviewer-datetime-uploaded', this.formatDate( imageData.uploadDateTime ) ).text()
				);
				this.$datetimeUpdatedLi.removeClass( 'empty' );
			}

			this.buttons.set( imageData, repoData );
			this.description.set( imageData.description, image.caption );

			this.setLicense( imageData.license, imageData.descriptionUrl );

			this.setFileName( imageData.title.getMainText() );

			// these handle text truncation and should be called when everything that can push text down
			// (e.g. floated buttons) has already been laid out
			this.setTitle( image, imageData );
			this.setCredit( imageData.attribution, imageData.source, imageData.author, imageData.authorCount, imageData.descriptionUrl );

			if ( imageData.permission ) {
				this.setPermission( imageData.permission );
			}

			if ( imageData.restrictions ) {
				this.setRestrictions( imageData.restrictions );
			}

			this.setLocationData( imageData );

			this.resetTruncatedText();
			this.scroller.unfreezeHeight();
		}

		/**
		 * Show an error message, in case the data could not be loaded
		 *
		 * @param {string} title image title
		 * @param {string} error error message
		 */
		showError( title, error ) {
			this.$credit.text( mw.message( 'multimediaviewer-metadata-error', error ).text() );
			this.$title.html( title );
		}

		/**
		 * Transforms a date string into localized, human-readable format.
		 * Unrecognized strings are returned unchanged.
		 *
		 * @param {string} dateString
		 * @return {string} formatted date
		 */
		formatDate( dateString ) {
			let lang = mw.config.get( 'wgUserLanguage' );
			if ( lang === 'en' || lang === 'qqx' ) {
				// prefer "D MMMM YYYY" format
				// avoid passing invalid "qqx" to native toLocaleString(),
				// which would cause developer's browser locale to be used,
				// and thus sometimes cause tests to fail.
				lang = 'en-GB';
			}
			if ( dateString.length === 4 ) {
				// assume yyyy
				return dateString;
			}
			const date = new Date( dateString );
			try {
				if ( date instanceof Date && !isNaN( date ) ) {
					return date.toLocaleString( lang, {
						day: 'numeric',
						month: 'long',
						year: 'numeric',
						timeZone: 'UTC'
					} );
				}
			} catch ( ignore ) { }
			// fallback to original date string
			return dateString;
		}

		/**
		 * Shows truncated text in the title and credit (this also rearranges the layout a bit).
		 */
		revealTruncatedText() {
			if ( this.$container.hasClass( 'mw-mmv-untruncated' ) ) {
				return;
			}
			this.$container.addClass( 'mw-mmv-untruncated' );
			this.title.grow();
			this.creditField.grow();
		}

		/**
		 * Undoes changes made by revealTruncatedText().
		 */
		hideTruncatedText() {
			if ( !this.$container.hasClass( 'mw-mmv-untruncated' ) ) {
				return;
			}
			this.title.shrink();
			this.creditField.shrink();
			this.$container.removeClass( 'mw-mmv-untruncated' );
		}

		/**
		 * Hide or reveal truncated text based on whether the panel is open. This is normally handled by
		 * MetadataPanelScroller, but when the panel is reset (e.g. on a prev/next event) sometimes the panel position can change without a panel , such as on a
		 * prev/next event; in such cases this function has to be called.
		 */
		resetTruncatedText() {
			if ( this.scroller.panelIsOpen() ) {
				this.revealTruncatedText();
			} else {
				this.hideTruncatedText();
			}
		}
	}

	/**
	 * Maximum number of restriction icons before default icon is used
	 *
	 * @property {number} MAX_RESTRICT
	 * @static
	 */
	MetadataPanel.MAX_RESTRICT = 4;

	module.exports = MetadataPanel;
}() );
