( function () {
	// To allow users to cancel a thanks in the event of an accident, the action is delayed.
	var THANKS_DELAY = 2000,
		msgOptions = {
			// tag ensures that only one message in workflow is shown at any time
			tag: 'thanks'
		};
	/**
	 * Attempt to execute a thank operation for a given edit
	 *
	 * @param {string} name The username of the user who made the edit
	 * @param {string} revision The revision the user created
	 * @param {string} recipientGender The gender of the user who made the edit
	 * @return {jQuery.Promise} The thank operation's status.
	 */
	function thankUser( name, revision, recipientGender ) {
		return ( new mw.Api() ).postWithToken( 'csrf', {
			action: 'thank',
			rev: revision,
			source: 'mobilediff'
		} ).then( function () {
			mw.notify( mw.msg( 'thanks-button-action-completed', name, recipientGender, mw.user ),
				msgOptions );
		}, function ( errorCode ) {
			var msg;
			switch ( errorCode ) {
				case 'invalidrevision':
					msg = mw.msg( 'thanks-error-invalidrevision' );
					break;
				case 'ratelimited':
					msg = mw.msg( 'thanks-error-ratelimited', recipientGender );
					break;
				default:
					msg = mw.msg( 'thanks-error-undefined', errorCode );
			}
			mw.notify( msg, msgOptions );
		} );
	}

	/**
	 * Disables the thank button marking the user as thanked
	 *
	 * @param {jQuery} $button used for thanking
	 * @param {string} gender The gender of the user who made the edit
	 * @return {jQuery} $button now disabled
	 */
	function disableThanks( $button, gender ) {
		$button
			.addClass( 'thanked' )
			.prop( 'disabled', true );
		$button.find( 'span' ).eq( 1 )
			.text( mw.msg( 'thanks-button-thanked', mw.user, gender ) );
		return $button;
	}

	/**
	 * Create a thank button for a given edit
	 *
	 * @param {string} name The username of the user who made the edit
	 * @param {string} rev The revision the user created
	 * @param {string} gender The gender of the user who made the edit
	 * @return {jQuery|null} The HTML of the button.
	 */
	function createThankLink( name, rev, gender ) {
		const button = document.createElement( 'button' ),
			label = document.createElement( 'span' ),
			icon = document.createElement( 'span' );
		let timeout;

		// https://doc.wikimedia.org/codex/latest/components/demos/button.html#css-only-version
		button.classList.add(
			'cdx-button',
			'mw-mf-action-button',
			'cdx-button--action-progressive',
			'cdx-button--weight-primary'
		);
		label.textContent = mw.msg( 'thanks-button-thank', mw.user, gender );
		icon.classList.add( 'mw-thanks-icon', 'cdx-button__icon' );
		icon.setAttribute( 'aria-hidden', 'true' );
		button.appendChild( icon );
		button.appendChild( label );
		const $button = $( button );

		// Don't make thank button for self
		if ( name === mw.config.get( 'wgUserName' ) ) {
			return null;
		}
		// See if user has already been thanked for this edit
		if ( mw.config.get( 'wgThanksAlreadySent' ) ) {
			return disableThanks( $button, gender );
		}

		function cancelThanks( $btn ) {
			// Hide the notification
			$( '.mw-notification' ).hide();
			// Clear the queued thanks!
			clearTimeout( timeout );
			timeout = null;
			$btn.prop( 'disabled', false );
		}

		function queueThanks( $btn ) {
			var $msg = $( '<div>' ).addClass( 'mw-thanks-notification' )
				.text( mw.msg( 'thanks-button-action-queued', name, gender ) )
				.append( $( '<a>' ).text( mw.msg( 'thanks-button-action-cancel' ) )
					.on( 'click', function () {
						cancelThanks( $btn );
					} )
				);
			mw.notify( $msg, msgOptions );
			timeout = setTimeout( function () {
				timeout = null;
				thankUser( name, rev, gender ).then( function () {
					disableThanks( $btn, gender );
				} );
			}, THANKS_DELAY );
		}

		return $button.on( 'click', function () {
			var $this = $( this );
			$this.prop( 'disabled', true );
			// eslint-disable-next-line no-jquery/no-class-state
			if ( !$this.hasClass( 'thanked' ) && !timeout ) {
				queueThanks( $this );
			}
		} );
	}

	/**
	 * Initialise a thank button in the given container.
	 *
	 * @param {jQuery} $user existing element with data attributes associated describing a user.
	 * @param {jQuery} $container to render button in
	 */
	function init( $user, $container ) {
		var username = $user.data( 'user-name' ),
			rev = $user.data( 'revision-id' ),
			gender = $user.data( 'user-gender' );

		var $thankBtn = createThankLink( username, rev, gender );
		if ( $thankBtn ) {
			$thankBtn.prependTo( $container );
		}

	}

	$( function () {
		init( $( '.mw-mf-user' ), $( '#mw-mf-userinfo' ) );
	} );

	// Expose for testing purposes
	mw.thanks = $.extend( {}, mw.thanks || {}, {
		_mobileDiffInit: init
	} );
}() );
