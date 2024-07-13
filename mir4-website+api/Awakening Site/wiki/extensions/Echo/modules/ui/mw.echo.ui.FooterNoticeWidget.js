( function () {
	/**
	 * Footer notice widget.
	 *
	 * @class
	 * @extends OO.ui.Widget
	 *
	 * @constructor
	 * @param {Object} [config] Configuration object
	 * @cfg {string} [iconUrl] The source URL of the feedback icon
	 * @cfg {string} [message] The message to display, in HTML or jQuery object.
	 *  The message should already be formatted properly.
	 */
	mw.echo.ui.FooterNoticeWidget = function MwEchoUiFooterNoticeWidget( config ) {

		config = config || {};

		// Parent constructor
		mw.echo.ui.FooterNoticeWidget.super.call( this, config );

		var $row = $( '<div>' ).addClass( 'mw-echo-ui-footerNoticeWidget-row' );

		if ( config.iconUrl ) {
			var $icon = $( '<div>' )
				.addClass( 'mw-echo-ui-footerNoticeWidget-icon' )
				.append( $( '<img>' ).attr( { src: config.iconUrl, width: 30, height: 30 } ) );

			$row.append( $icon );
		}

		var label = new OO.ui.LabelWidget( {
			label: $( '<span>' ).append( $.parseHTML( config.message ) ).contents(),
			classes: [ 'mw-echo-ui-footerNoticeWidget-label' ]
		} );

		var dismissButton = new OO.ui.ButtonWidget( {
			icon: 'close',
			framed: false,
			classes: [ 'mw-echo-ui-footerNoticeWidget-dismiss' ]
		} );

		// Events
		dismissButton.connect( this, { click: 'onDismissButtonClick' } );

		this.$element
			.addClass( 'mw-echo-ui-footerNoticeWidget' )
			.append(
				$row
					.append(
						label.$element,
						dismissButton.$element
					)
			);
	};

	/* Initialization */

	OO.inheritClass( mw.echo.ui.FooterNoticeWidget, OO.ui.Widget );

	/* Events */

	/**
	 * The notice was dismissed.
	 *
	 * @event dismiss
	 */

	/* Methods */

	/**
	 * Respond to dismiss button click.
	 *
	 * @fires dismiss
	 */
	mw.echo.ui.FooterNoticeWidget.prototype.onDismissButtonClick = function () {
		this.toggle( false );
		this.emit( 'dismiss' );
	};
}() );
