( function () {
	/**
	 * A pagination widget allowing the user to go forward, backwards,
	 * and after a couple of pages, go back to home.
	 *
	 * @class
	 * @extends OO.ui.Widget
	 *
	 * @constructor
	 * @param {mw.echo.dm.PaginationModel} paginationModel Pagination model
	 * @param {Object} [config] Configuration object
	 * @cfg {number} [itemsPerPage=25] Number of items per page
	 * @cfg {number} [showFirstButton=true] Show a button that allows the user
	 *  to go back to the first page.
	 * @cfg {number} [showFirstButtonAfter=2] Pick the number of pages that it
	 *  takes to show the button that takes the user back to the first set
	 *  of results.
	 * @cfg {string} [startButtonLabel] The label used for the start button
	 */
	mw.echo.ui.PaginationWidget = function MwEchoUiPaginationWidget( paginationModel, config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.PaginationWidget.super.call( this, config );

		this.model = paginationModel;

		this.showFirstButton = config.showFirstButton === undefined ? true : !!config.showFirstButton;
		this.showFirstButtonAfter = config.showFirstButtonAfter || 2;
		this.itemsPerPage = config.itemsPerPage || 25;

		// Pagination elements
		this.labelWidget = new OO.ui.LabelWidget( {
			classes: [ 'mw-echo-ui-paginationWidget-label' ]
		} );

		this.startButton = new OO.ui.ButtonWidget( {
			classes: [ 'mw-echo-ui-paginationWidget-start' ],
			label: config.startButtonLabel || mw.msg( 'notification-timestamp-today' ),
			data: 'start'
		} );

		this.dirSelectWidget = new OO.ui.ButtonSelectWidget( {
			classes: [ 'mw-echo-ui-paginationWidget-direction' ],
			items: [
				new OO.ui.ButtonOptionWidget( {
					icon: 'previous',
					data: 'prev'
				} ),
				new OO.ui.ButtonOptionWidget( {
					icon: 'next',
					data: 'next'
				} )
			]
		} );

		// Events
		this.startButton.connect( this, { click: [ 'emit', 'change', 'start' ] } );
		this.dirSelectWidget.connect( this, { choose: 'onDirSelectWidgetChoose' } );
		this.model.connect( this, { update: 'updateWidgetState' } );

		// Initialization
		this.updateWidgetState();
		this.$element
			.addClass( 'mw-echo-ui-paginationWidget' )
			.append(
				$( '<div>' )
					.addClass( 'mw-echo-ui-paginationWidget-row' )
					.append(
						this.labelWidget.$element,
						this.startButton.$element,
						this.dirSelectWidget.$element
					)
			);
	};

	/* Initialization */

	OO.inheritClass( mw.echo.ui.PaginationWidget, OO.ui.Widget );

	/* Events */

	/**
	 * @event change
	 * @param {string} direction Direction of movement 'prev',
	 * 'next' or 'start'
	 *
	 * Pagination changed
	 */

	/* Methods */

	/**
	 * Respond to dir select widget choose event
	 *
	 * @param {OO.ui.ButtonOptionWidget} item Chosen button
	 * @fires change
	 */
	mw.echo.ui.PaginationWidget.prototype.onDirSelectWidgetChoose = function ( item ) {
		var dir = item && item.getData();

		if ( dir ) {
			this.emit( 'change', dir );
			item.setSelected( false );
		}
	};

	/**
	 * Update the state - disabled and visibility - of the sub widgets.
	 */
	mw.echo.ui.PaginationWidget.prototype.updateWidgetState = function () {
		this.dirSelectWidget.findItemFromData( 'prev' )
			.setDisabled( this.isDisabled() || !this.model.hasPrevPage() );
		this.dirSelectWidget.findItemFromData( 'next' )
			.setDisabled( this.isDisabled() || !this.model.hasNextPage() );

		this.startButton.toggle(
			!this.isDisabled() &&
			this.model.getCurrPageIndex() >= this.showFirstButtonAfter
		);

		// Only show pagination buttons if there's anywhere to go
		this.dirSelectWidget.toggle( this.model.hasPrevPage() || this.model.hasNextPage() );

		// Update label text and visibility
		this.updateLabel();
		this.labelWidget.toggle( !this.isDisabled() );
	};

	/**
	 * Set the 'disabled' state of the widget.
	 *
	 * @param {boolean} disabled Disable widget
	 * @chainable
	 */
	mw.echo.ui.PaginationWidget.prototype.setDisabled = function ( disabled ) {
		// Parent method
		mw.echo.ui.PaginationWidget.super.prototype.setDisabled.call( this, disabled );

		if (
			this.dirSelectWidget &&
			this.startButton &&
			this.labelWidget
		) {
			this.updateWidgetState();
		}

		return this;
	};

	/**
	 * Update the pagination label according to the page number, the amount of notifications
	 * per page, and the number of notifications on the current page.
	 */
	mw.echo.ui.PaginationWidget.prototype.updateLabel = function () {
		var itemsInPage = this.model.getCurrentPageItemCount(),
			firstNotifNum = this.model.getCurrPageIndex() * this.itemsPerPage,
			lastNotifNum = firstNotifNum + itemsInPage;

		var label;
		if ( itemsInPage === 0 ) {
			label = '';
		} else if ( !this.model.hasPrevPage() && !this.model.hasNextPage() ) {
			label = mw.msg(
				'echo-specialpage-pagination-numnotifications',
				mw.language.convertNumber( itemsInPage )
			);
		} else {
			label = mw.msg(
				'echo-specialpage-pagination-range',
				mw.language.convertNumber( firstNotifNum + 1 ),
				mw.language.convertNumber( lastNotifNum )
			);
		}

		this.labelWidget.setLabel( label );
	};
}() );
