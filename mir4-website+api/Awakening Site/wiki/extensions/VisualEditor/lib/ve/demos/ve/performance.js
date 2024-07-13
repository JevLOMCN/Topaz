/*!
 * VisualEditor performance tests
 *
 * @copyright See AUTHORS.txt
 */

( function () {
	/* eslint-disable no-jquery/no-global-selector */
	var $instance = $( '.ve-instance' ),
		$output = $( '.ve-demo-output' );

	/* eslint-enable no-jquery/no-global-selector */
	// Set up the platform and wait for i18n messages to load
	new ve.init.sa.Platform( ve.messagePaths ).getInitializedPromise()
		.fail( function () {
			$instance.text( 'Sorry, this browser is not supported.' );
		} )
		.done( function () {
			// Create the target
			var target = new ve.init.sa.Target();

			// Append the target to the document
			$instance.append( target.$element );

			$.ajax( {
				url: 'pages/very long.html',
				dataType: 'text'
			} ).done( function ( html ) {
				var htmlDomTimes = 0, domDmTimes = 0, dmDomTimes = 0, txTimes = 0,
					n = 50,
					config = { lang: $.i18n().locale, dir: $( document.body ).css( 'direction' ) };

				for ( var i = 0; i <= n; i++ ) {
					var t0 = performance.now();
					var dom = ve.createDocumentFromHtml( html );
					htmlDomTimes += performance.now() - t0;

					t0 = performance.now();
					var dmDoc = ve.dm.converter.getModelFromDom( dom, config );
					domDmTimes += performance.now() - t0;

					var dmSurface = new ve.dm.Surface( dmDoc );

					t0 = performance.now();
					dom = ve.dm.converter.getDomFromModel( dmDoc, config );
					dmDomTimes += performance.now() - t0;

					t0 = performance.now();
					for ( var j = 0; j < 10; j++ ) {
						dmSurface.getLinearFragment( new ve.Range( 1 ) ).insertContent( 'hello' ).annotateContent( 'set', 'textStyle/bold' );
					}
					txTimes += performance.now() - t0;

					if ( !i ) {
						$output.append(
							$( '<h3>' ).text( 'First run (cold caches)' ),
							$( '<p>' ).text( 'Convert HTML>DOM: ' + htmlDomTimes + 'ms' ),
							$( '<p>' ).text( 'Convert DOM>DM: ' + domDmTimes + 'ms' ),
							$( '<p>' ).text( 'Convert DM>DOM: ' + dmDomTimes + 'ms' ),
							$( '<p>' ).text( 'Insert and annotate: ' + txTimes + 'ms' )
						);
					}
				}
				$output.append(
					$( '<h3>' ).text( 'Average (' + n + ' runs)' ),
					$( '<p>' ).text( 'Convert HTML>DOM: ' + htmlDomTimes / n + 'ms' ),
					$( '<p>' ).text( 'Convert DOM>DM: ' + domDmTimes / n + 'ms' ),
					$( '<p>' ).text( 'Convert DM>DOM: ' + dmDomTimes / n + 'ms' ),
					$( '<p>' ).text( 'Insert and annotate: ' + txTimes / n + 'ms' )
				);
			} );
		} );
}() );
