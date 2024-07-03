( function () {
	/* global moment:false */
	/**
	 * A sub group widget that displays notifications divided by dates.
	 *
	 * @class
	 * @extends mw.echo.ui.SubGroupListWidget
	 *
	 * @constructor
	 * @param {mw.echo.Controller} controller Notifications controller
	 * @param {mw.echo.dm.SortedList} listModel Notifications list model for this source
	 * @param {Object} [config] Configuration object
	 */
	mw.echo.ui.DatedSubGroupListWidget = function MwEchoUiDatedSubGroupListWidget( controller, listModel, config ) {
		var now = moment(),
			$primaryDate = $( '<span>' )
				.addClass( 'mw-echo-ui-datedSubGroupListWidget-title-primary' ),
			$secondaryDate = $( '<span>' )
				.addClass( 'mw-echo-ui-datedSubGroupListWidget-title-secondary' ),
			$title = $( '<h2>' )
				.addClass( 'mw-echo-ui-datedSubGroupListWidget-title' )
				.append( $primaryDate, $secondaryDate );

		// Parent constructor
		mw.echo.ui.DatedSubGroupListWidget.super.call( this, controller, listModel, $.extend( {
			// Since this widget is defined as a dated list, we sort
			// its items according to timestamp without consideration
			// of read state or foreignness.
			sortingCallback: function ( a, b ) {
				// Reverse sorting
				if ( b.getTimestamp() < a.getTimestamp() ) {
					return -1;
				} else if ( b.getTimestamp() > a.getTimestamp() ) {
					return 1;
				}

				// Fallback on IDs
				return b.getId() - a.getId();
			}
		}, config ) );

		// Round all dates to the day they're in, as if they all happened at 00:00h
		var stringTimestamp = moment.utc( this.model.getTimestamp() ).local().format( 'YYYY-MM-DD' );
		var momentTimestamp = moment( stringTimestamp );
		var diff = now.diff( momentTimestamp, 'weeks' );
		var fullDate = momentTimestamp.format( 'LL' );

		$primaryDate.text( fullDate );
		if ( diff === 0 ) {
			$secondaryDate.text( fullDate );
			momentTimestamp.locale( 'echo-shortRelativeTime' );
			$primaryDate.text( momentTimestamp.calendar() );
		}

		this.title.setLabel( $title );

		this.$element
			.addClass( 'mw-echo-ui-datedSubGroupListWidget' );
	};

	/* Initialization */

	OO.inheritClass( mw.echo.ui.DatedSubGroupListWidget, mw.echo.ui.SubGroupListWidget );
}() );
