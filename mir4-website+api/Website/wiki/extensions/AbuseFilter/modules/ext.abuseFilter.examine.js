/**
 * Check a filter against a change
 *
 * @author John Du Hart
 * @author Marius Hoch <hoo@online.de>
 */

( function () {
	'use strict';

	// @var {jQuery} Syntax result div
	var $syntaxResult;

	/**
	 * Processes the results of the filter test
	 *
	 * @param {Object} data The response of the API request
	 */
	function examinerTestProcess( data ) {
		$.removeSpinner( 'filter-check' );

		var msg, exClass;
		if ( data.abusefiltercheckmatch.result ) {
			exClass = 'mw-abusefilter-examine-match';
			msg = 'abusefilter-examine-match';
		} else {
			exClass = 'mw-abusefilter-examine-nomatch';
			msg = 'abusefilter-examine-nomatch';
		}
		$syntaxResult
			.attr( 'class', exClass )
			// Messages listed above
			// eslint-disable-next-line mediawiki/msg-doc
			.text( mw.msg( msg ) )
			.show();
	}

	/**
	 * Processes the results of the filter test in case of an error
	 *
	 * @param {string} error Error code returned from the AJAX request
	 * @param {Object} details Details about the error
	 */
	function examinerTestProcessFailure( error, details ) {
		$.removeSpinner( 'filter-check' );

		var msg;
		if ( error === 'badsyntax' ) {
			$syntaxResult.attr(
				'class', 'mw-abusefilter-syntaxresult-error'
			);
			msg = 'abusefilter-examine-syntaxerror';
		} else if ( error === 'nosuchrcid' || error === 'nosuchlogid' ) {
			msg = 'abusefilter-examine-notfound';
		} else if ( error === 'permissiondenied' ) {
			// The 'abusefilter-modify' or 'abusefilter-view-private' right is needed
			// to use this API
			msg = 'abusefilter-mustviewprivateoredit';
		} else if ( error === 'http' ) {
			msg = 'abusefilter-http-error';
		} else {
			msg = 'unknown-error';
		}

		$syntaxResult
			// Messages listed above
			// eslint-disable-next-line mediawiki/msg-doc
			.text( mw.msg( msg, details && details.exception ) )
			.show();
	}

	/**
	 * Tests the filter against an rc event or abuse log entry.
	 *
	 * @this HTMLElement
	 * @param {jQuery.Event} e The event fired when the function is called
	 */
	function examinerTestFilter() {
		var filter = $( '#wpFilterRules' ).val(),
			examine = mw.config.get( 'abuseFilterExamine' ),
			params = {
				action: 'abusefiltercheckmatch',
				filter: filter
			};

		$( this ).injectSpinner( { id: 'filter-check', size: 'large' } );

		params[ examine.type === 'rc' ? 'rcid' : 'logid' ] = examine.id;

		// Use post due to the rather large amount of data
		new mw.Api().post( params )
			.done( examinerTestProcess )
			.fail( examinerTestProcessFailure );
	}

	$( function initialize() {
		$syntaxResult = $( '#mw-abusefilter-syntaxresult' );
		$( '#mw-abusefilter-examine-test' ).on( 'click', examinerTestFilter );
	} );
}() );
