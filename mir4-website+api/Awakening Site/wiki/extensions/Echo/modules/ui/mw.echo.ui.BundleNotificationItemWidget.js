( function () {
	/**
	 * Bundle notification item widget.
	 * This widget is expandable and displays
	 * inner notifications that constitute the bundle.
	 *
	 * @class
	 * @extends mw.echo.ui.NotificationItemWidget
	 *
	 * @constructor
	 * @param {mw.echo.Controller} controller Echo notifications controller
	 * @param {mw.echo.dm.BundleNotificationItem} model Notification group model
	 * @param {Object} [config] Configuration object
	 * @cfg {boolean} [animateSorting=false] Animate the sorting of items
	 */
	mw.echo.ui.BundleNotificationItemWidget = function MwEchoUiBundleNotificationItemWidget( controller, model, config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.BundleNotificationItemWidget.super.call( this, controller, model, config );

		this.toggleMarkAsReadButtons( true );

		this.listWidget = new mw.echo.ui.SortedListWidget(
			// Sorting callback
			function ( a, b ) {
				// Reverse sorting
				if ( b.getTimestamp() < a.getTimestamp() ) {
					return -1;
				} else if ( b.getTimestamp() > a.getTimestamp() ) {
					return 1;
				}

				// Fallback on IDs
				return b.getId() - a.getId();
			},
			// Config
			{
				classes: [ 'mw-echo-ui-bundleNotificationItemWidget-group' ],
				timestamp: this.getTimestamp(),
				$overlay: this.$overlay,
				animated: !!config.animateSorting
			}
		);

		this.listWidget.$element
			// We have to manually set the display to 'none' here because
			// otherwise the 'slideUp' and 'slideDown' jQuery effects don't
			// work
			.css( 'display', 'none' );

		// Prevent clicks on the list padding area from activating the primary link
		this.listWidget.$element.on( 'click', function ( e ) {
			if ( e.target.closest( 'a' ) === this.$element[ 0 ] ) {
				e.preventDefault();
			}
		}.bind( this ) );

		// Initialize closed
		this.expanded = false;

		// Add "expand" button
		this.toggleExpandButton = new OO.ui.ButtonOptionWidget( {
			icon: 'expand',
			framed: false,
			classes: [ 'mw-echo-ui-notificationItemWidget-content-actions-button' ]
		} );
		this.updateExpandButton();
		this.actionsButtonSelectWidget.addItems( [ this.toggleExpandButton ], 0 );

		// Events
		this.toggleExpandButton.connect( this, { click: 'expand' } );

		if ( !this.model.getPrimaryUrl() ) {
			// If there's no primary link, make sure a click
			// triggers the 'expand' action
			this.$content.on( 'click', this.expand.bind( this ) );
		}

		// Initialization
		this.populateFromModel();
		this.toggleExpanded( false );
		this.toggleRead( false );
		this.$element
			.addClass( 'mw-echo-ui-bundleNotificationItemWidget' )
			.append(
				$( '<div>' )
					.addClass( 'mw-echo-ui-bundleNotificationItemWidget-separator' ),
				this.listWidget.$element
			);

		// Events
		this.model.connect( this, { update: 'updateDataFromModel' } );

		// Update read and seen states from the model
		this.updateDataFromModel();
	};

	/* Initialization */

	OO.inheritClass( mw.echo.ui.BundleNotificationItemWidget, mw.echo.ui.NotificationItemWidget );

	/* Methods */

	/**
	 * @inheritdoc
	 */
	mw.echo.ui.BundleNotificationItemWidget.prototype.markRead = function ( isRead ) {
		this.controller.markEntireListModelRead( this.model.getModelName(), isRead );
	};

	/**
	 * Populate the items in this widget according to the data
	 * in the model
	 */
	mw.echo.ui.BundleNotificationItemWidget.prototype.populateFromModel = function () {
		var widget = this;
		this.getList().addItems( this.model.getList().getItems().map( function ( singleNotifModel ) {
			return new mw.echo.ui.SingleNotificationItemWidget(
				widget.controller,
				singleNotifModel,
				{
					$overlay: widget.$overlay,
					bundle: true
				}
			);
		} ) );
	};

	/**
	 * Update item state when the item model changes.
	 */
	mw.echo.ui.BundleNotificationItemWidget.prototype.updateDataFromModel = function () {
		this.toggleRead( this.model.isRead() );
		this.toggleSeen( this.model.isSeen() );
		this.emit( 'sortChange' );
	};

	/**
	 * Toggle the visibility of the notification item list and the placeholder/error widget.
	 *
	 * @param {boolean} showList Show the list
	 */
	mw.echo.ui.BundleNotificationItemWidget.prototype.toggleListDisplay = function ( showList ) {
		this.listWidget.toggle( showList );
	};

	/**
	 * Expand the group and fetch the list of notifications.
	 * Only fetch the first time we expand.
	 */
	mw.echo.ui.BundleNotificationItemWidget.prototype.expand = function () {
		this.toggleExpanded( !this.expanded );
		this.updateExpandButton();

		this.$element.toggleClass( 'mw-echo-ui-bundleNotificationItemWidget-expanded', this.expanded );

		if ( !this.expanded ) {
			return;
		}
	};

	/**
	 * Toggle the expand/collapsed state of this group widget
	 *
	 * @param {boolean} show Show the widget expanded
	 */
	mw.echo.ui.BundleNotificationItemWidget.prototype.toggleExpanded = function ( show ) {
		this.expanded = show !== undefined ? !!show : !this.expanded;

		if ( show ) {
			// FIXME: Use CSS transition
			// eslint-disable-next-line no-jquery/no-slide
			this.getList().$element.slideDown();
		} else {
			// eslint-disable-next-line no-jquery/no-slide
			this.getList().$element.slideUp();
		}
	};

	/**
	 * Update the expand button label
	 */
	mw.echo.ui.BundleNotificationItemWidget.prototype.updateExpandButton = function () {
		this.toggleExpandButton.setLabel(
			this.expanded ?
				mw.msg( 'notification-link-text-collapse-all' ) :
				mw.msg( 'notification-link-text-expand-all' )
		);
		this.toggleExpandButton.setIcon(
			this.expanded ?
				'collapse' :
				'expand'
		);
		// T258706
		this.toggleExpandButton.setActive( false );
	};

	/**
	 * Get the list widget contained in this item
	 *
	 * @return {mw.echo.ui.SortedListWidget} List widget
	 */
	mw.echo.ui.BundleNotificationItemWidget.prototype.getList = function () {
		return this.listWidget;
	};
}() );
