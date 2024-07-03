( function () {
	/**
	 * An option widget for the page filter in PageFilterWidget
	 *
	 * @class
	 * @extends OO.ui.OptionWidget
	 * @mixins OO.ui.mixin.IconElement
	 * @mixins OO.ui.mixin.TitledElement
	 *
	 * @constructor
	 * @param {Object} [config] Configuration object
	 * @cfg {number} [count] Number of unread notifications
	 * @cfg {boolean} [isCapped] The count for this widget is capped
	 */
	mw.echo.ui.PageNotificationsOptionWidget = function MwEchoUiPageNotificationsOptionWidget( config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.PageNotificationsOptionWidget.super.call( this, config );
		// Mixin constructors
		OO.ui.mixin.IconElement.call( this, config );
		OO.ui.mixin.TitledElement.call( this, config );

		this.$label
			.addClass( 'mw-echo-ui-pageNotificationsOptionWidget-title-label' );

		this.count = config.count !== undefined ? config.count : 0;

		var countLabel = mw.language.convertNumber( this.count );
		countLabel = config.isCapped ?
			mw.msg( 'echo-badge-count', countLabel ) : countLabel;

		this.unreadCountLabel = new OO.ui.LabelWidget( {
			classes: [ 'mw-echo-ui-pageNotificationsOptionWidget-label-count' ],
			label: countLabel
		} );

		var $row = $( '<div>' )
			.addClass( 'mw-echo-ui-pageNotificationsOptionWidget-row' )
			.append(
				$( '<div>' )
					.addClass( 'mw-echo-ui-pageNotificationsOptionWidget-cell' )
					.addClass( 'mw-echo-ui-pageNotificationsOptionWidget-title' )
					.append( this.$label ),
				$( '<div>' )
					.addClass( 'mw-echo-ui-pageNotificationsOptionWidget-cell' )
					.addClass( 'mw-echo-ui-pageNotificationsOptionWidget-count' )
					.append( this.unreadCountLabel.$element )
			);

		// Initialization
		this.$element
			.addClass( 'mw-echo-ui-pageNotificationsOptionWidget' )
			.append(
				$( '<div>' )
					.addClass( 'mw-echo-ui-pageNotificationsOptionWidget-table' )
					.append( $row )
			);

		if ( this.getIcon() ) {
			$row.prepend(
				$( '<div>' )
					.addClass( 'mw-echo-ui-pageNotificationsOptionWidget-cell' )
					.addClass( 'mw-echo-ui-pageNotificationsOptionWidget-icon' )
					.append( this.$icon )
			);
		}
	};

	/* Initialization */

	OO.inheritClass( mw.echo.ui.PageNotificationsOptionWidget, OO.ui.OptionWidget );
	OO.mixinClass( mw.echo.ui.PageNotificationsOptionWidget, OO.ui.mixin.IconElement );
	OO.mixinClass( mw.echo.ui.PageNotificationsOptionWidget, OO.ui.mixin.TitledElement );

	/**
	 * Get the page count
	 *
	 * @return {number} Page count
	 */
	mw.echo.ui.PageNotificationsOptionWidget.prototype.getCount = function () {
		return this.count;
	};

	mw.echo.ui.PageNotificationsOptionWidget.prototype.setPressed = function ( state ) {
		mw.echo.ui.PageNotificationsOptionWidget.super.prototype.setPressed.call( this, state );
		if ( this.pressed ) {
			this.setFlags( 'progressive' );
		} else if ( !this.selected ) {
			this.clearFlags();
		}
		return this;
	};

	mw.echo.ui.PageNotificationsOptionWidget.prototype.setSelected = function ( state ) {
		mw.echo.ui.PageNotificationsOptionWidget.super.prototype.setSelected.call( this, state );
		if ( this.selected ) {
			this.setFlags( 'progressive' );
		} else {
			this.clearFlags();
		}
		return this;
	};

}() );
