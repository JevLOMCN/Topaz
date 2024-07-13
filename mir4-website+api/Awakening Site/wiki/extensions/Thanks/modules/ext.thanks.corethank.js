( function () {
	'use strict';

	var attrName = 'data-revision-id';

	function reloadThankedState() {
		$( 'a.mw-thanks-thank-link' ).each( function ( idx, el ) {
			var $thankLink = $( el );
			if ( mw.thanks.thanked.contains( $thankLink.attr( attrName ) ) ) {
				$thankLink.before(
					$( '<span>' ).addClass( 'mw-thanks-thank-confirmation' ).text(
						mw.msg( 'thanks-thanked', mw.user, $thankLink.data( 'recipient-gender' ) ) )
				);
				$thankLink.remove();
			}
		} );
	}

	/**
	 * Send thanks
	 *
	 * @param {jQuery} $thankLink The element with the data-revision-id attribute
	 * @param {jQuery} $thankElement The element to be removed on success
	 */
	function sendThanks( $thankLink, $thankElement ) {
		if ( $thankLink.data( 'clickDisabled' ) ) {
			// Prevent double clicks while we haven't received a response from API request
			return;
		}
		$thankLink.data( 'clickDisabled', true );

		var source;
		// Determine the thank source (history, diff, or log).
		if ( mw.config.get( 'wgAction' ) === 'history' ) {
			source = 'history';
		} else if ( mw.config.get( 'wgCanonicalSpecialPageName' ) === 'Log' ) {
			source = 'log';
		} else {
			source = 'diff';
		}

		// Construct the API parameters.
		var apiParams = {
			action: 'thank',
			source: source
		};
		if ( $thankLink.data( 'log-id' ) ) {
			apiParams.log = $thankLink.data( 'log-id' );
		} else {
			apiParams.rev = $thankLink.data( 'revision-id' );
		}

		// Send the API request.
		( new mw.Api() ).postWithToken( 'csrf', apiParams )
			.then(
				// Success
				function () {
					$thankElement.before( mw.message( 'thanks-thanked', mw.user, $thankLink.data( 'recipient-gender' ) ).escaped() );
					$thankElement.remove();
					mw.thanks.thanked.push( $thankLink.attr( attrName ) );
				},
				// Fail
				function ( errorCode ) {
					// If error occurred, enable attempting to thank again
					$thankLink.data( 'clickDisabled', false );
					var msg;
					switch ( errorCode ) {
						case 'invalidrevision':
							msg = mw.msg( 'thanks-error-invalidrevision' );
							break;
						case 'ratelimited':
							msg = mw.msg( 'thanks-error-ratelimited', mw.user );
							break;
						case 'revdeleted':
							msg = mw.msg( 'thanks-error-revdeleted' );
							break;
						default:
							msg = mw.msg( 'thanks-error-undefined', errorCode );
					}
					OO.ui.alert( msg );
				}
			);
	}

	/**
	 * Add interactive handlers to all 'thank' links in $content
	 *
	 * @param {jQuery} $content
	 */
	function addActionToLinks( $content ) {
		var $thankLinks = $content.find( 'a.mw-thanks-thank-link' );
		if ( mw.config.get( 'thanks-confirmation-required' ) ) {
			$thankLinks.each( function () {
				var $thankLink = $( this );
				$thankLink.confirmable( {
					i18n: {
						confirm: mw.msg( 'thanks-confirmation2', mw.user ),
						no: mw.msg( 'cancel' ),
						noTitle: mw.msg( 'thanks-thank-tooltip-no', mw.user ),
						yes: mw.msg( 'thanks-button-thank', mw.user, $thankLink.data( 'recipient-gender' ) ),
						yesTitle: mw.msg( 'thanks-thank-tooltip-yes', mw.user )
					},
					handler: function ( e ) {
						e.preventDefault();
						sendThanks( $thankLink, $thankLink.closest( '.jquery-confirmable-wrapper' ) );
					}
				} );
			} );
		} else {
			$thankLinks.on( 'click', function ( e ) {
				var $thankLink = $( this );
				e.preventDefault();
				sendThanks( $thankLink, $thankLink );
			} );
		}
	}

	if ( $.isReady ) {
		// This condition is required for soft-reloads
		// to also trigger the reloadThankedState
		reloadThankedState();
	} else {
		$( reloadThankedState );
	}

	$( function () {
		addActionToLinks( $( 'body' ) );
	} );

	mw.hook( 'wikipage.diff' ).add( function ( $content ) {
		addActionToLinks( $content );
	} );
}() );
