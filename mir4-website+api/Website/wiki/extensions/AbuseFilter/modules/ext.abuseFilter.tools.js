/**
 * JavaScript for AbuseFilter tools
 *
 * @author John Du Hart
 * @author Marius Hoch <hoo@online.de>
 */

( function () {
	'use strict';

	/**
	 * Submits the expression to be evaluated.
	 *
	 * @this HTMLElement
	 * @param {jQuery.Event} e The event fired when the function is called
	 */
	function doExprSubmit( e ) {
		var expr = $( '#wpFilterRules' ).val();

		$( this ).injectSpinner( { id: 'abusefilter-expr', size: 'large' } );

		new mw.Api().post( {
			action: 'abusefilterevalexpression',
			expression: expr,
			prettyprint: 1
		} )
			.fail( function showError( error, details ) {
				// TODO This might use api.getErrorMessage()
				var msg;
				if ( error === 'http' ) {
					msg = 'abusefilter-http-error';
				} else if ( error === 'abusefilter-tools-syntax-error' ) {
					msg = 'abusefilter-tools-syntax-error';
				} else {
					msg = 'unknown-error';
				}
				$.removeSpinner( 'abusefilter-expr' );
				$( '#mw-abusefilter-expr-result' )
					// Message keys are listed above
					// eslint-disable-next-line mediawiki/msg-doc
					.text( mw.msg( msg, details.exception ) )
					.addClass( 'mw-abusefilter-tools-error' )
					.show();
			} )
			.done( function showResult( data ) {
				$.removeSpinner( 'abusefilter-expr' );

				$( '#mw-abusefilter-expr-result' )
					.text( data.abusefilterevalexpression.result )
					.removeClass( 'mw-abusefilter-tools-error' )
					.show();
			} );
		e.preventDefault();
	}

	/**
	 * Processes the result of the unblocking autopromotions for a user
	 *
	 * @param {Object} data The response of the API request
	 */
	function processReautoconfirm( data ) {
		mw.notify(
			mw.message(
				'abusefilter-reautoconfirm-done',
				data.abusefilterunblockautopromote.user
			).toString()
		);

		$.removeSpinner( 'abusefilter-reautoconfirm' );
	}

	/**
	 * Processes the result of the unblocking autopromotions for a user in case of an error
	 *
	 * @param {string} errorCode Identifier of the error
	 * @param {Object} data The response of the API request
	 */
	function processReautoconfirmFailure( errorCode, data ) {
		var msg;

		switch ( errorCode ) {
			case 'permissiondenied':
				msg = mw.msg( 'abusefilter-reautoconfirm-notallowed' );
				break;
			case 'http':
				msg = mw.msg( 'abusefilter-http-error', data && data.exception );
				break;
			case 'notsuspended':
				msg = data.error.info;
				break;
			default:
				msg = mw.msg( 'unknown-error' );
				break;
		}
		mw.notify( msg );

		$.removeSpinner( 'abusefilter-reautoconfirm' );
	}

	/**
	 * Submits a call to reautoconfirm a user.
	 *
	 * @this HTMLElement
	 * @param {jQuery.Event} e The event fired when the function is called
	 * @return {boolean} False to prevent form submission
	 */
	function doReautoSubmit() {
		var nameField = OO.ui.infuse( $( '#reautoconfirm-user' ) ),
			name = nameField.getValue();

		if ( name === '' ) {
			return false;
		}

		$( this ).injectSpinner( { id: 'abusefilter-reautoconfirm', size: 'large' } );

		new mw.Api().post( {
			action: 'abusefilterunblockautopromote',
			user: name,
			token: mw.user.tokens.get( 'csrfToken' )
		} )
			.done( processReautoconfirm )
			.fail( processReautoconfirmFailure );
		return false;
	}

	$( function initialize() {
		$( '#mw-abusefilter-submitexpr' ).on( 'click', doExprSubmit );
		$( '#mw-abusefilter-reautoconfirmsubmit' ).on( 'click', doReautoSubmit );
	} );
}() );
