( function () {
	/**
	 * Cross-wiki notification item widget.
	 * This widget is expandable and displays groups of
	 * notification item lists by their sources.
	 *
	 * TODO: When we have local bundles (without groups of lists)
	 * we can separate the "expand" functionality and UI to another mixin
	 * so we can use it with both widgets.
	 *
	 * @class
	 * @extends mw.echo.ui.NotificationItemWidget
	 * @mixins OO.ui.mixin.PendingElement
	 *
	 * @constructor
	 * @param {mw.echo.Controller} controller Echo notifications controller
	 * @param {mw.echo.dm.CrossWikiNotificationItem} model Notification group model
	 * @param {Object} [config] Configuration object
	 * @cfg {boolean} [animateSorting=false] Animate the sorting of items
	 */
	mw.echo.ui.CrossWikiNotificationItemWidget = function MwEchoUiCrossWikiNotificationItemWidget( controller, model, config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.CrossWikiNotificationItemWidget.super.call( this, controller, model, config );
		// Mixin constructors
		OO.ui.mixin.PendingElement.call( this, config );

		// In cross-wiki groups we only have 'mark as read'
		this.toggleMarkAsReadButtons( true );

		this.listWidget = new mw.echo.ui.SortedListWidget(
			// Sorting callback
			function ( a, b ) {
				// Define the sorting order.
				// This will go by the lists' timestamp, which in turn
				// take the latest timestamp in their items
				if ( b.getTimestamp() < a.getTimestamp() ) {
					return -1;
				} else if ( b.getTimestamp() > a.getTimestamp() ) {
					return 1;
				}

				// Fallback on IDs
				return b.getSource() - a.getSource();
			},
			// Config
			{
				classes: [ 'mw-echo-ui-crossWikiNotificationItemWidget-group' ],
				timestamp: this.getTimestamp(),
				$overlay: this.$overlay,
				animated: config.animateSorting
			}
		);

		this.listWidget.$element
			// We have to manually set the display to 'none' here because
			// otherwise the 'slideUp' and 'slideDown' jQuery effects don't
			// work
			.css( 'display', 'none' );
		this.setPendingElement( this.listWidget.$element );

		this.errorWidget = new mw.echo.ui.PlaceholderItemWidget();
		this.errorWidget.toggle( false );

		// Initialize closed
		this.showTitles = true;
		this.expanded = false;
		this.fetchedOnce = false;

		// Add "expand" button
		this.toggleExpandButton = new OO.ui.ButtonOptionWidget( {
			icon: 'expand',
			framed: false,
			classes: [ 'mw-echo-ui-notificationItemWidget-content-actions-button' ]
		} );
		this.updateExpandButton();
		this.actionsButtonSelectWidget.addItems( [ this.toggleExpandButton ] );

		// Events
		this.model.connect( this, { discard: 'onModelDiscard' } );
		this.toggleExpandButton.connect( this, { click: 'expand' } );
		this.$content.on( 'click', this.expand.bind( this ) );

		// TODO: Handle cases where the group became empty or a case where the group only has 1 item left.
		// Note: Right now this code works primarily for cross-wiki notifications. These act differently
		// than local bundles. Cross-wiki notifications, when they "lose" their items for being read, they
		// vanish from the list. Unlike them, the plan for local bundles is that read sub-items go outside
		// the bundle and become their own items in the general notificationsWidget, and when the local bundle
		// has 1 notification left, the group will actually transform into that last notification item.
		// We don't listen to the empty event right now, because the entire item is deleted in cross-wiki
		// notifications. When we work on local bundles, we will have to add that event listener per item.

		// Initialization
		this.populateFromModel();
		this.toggleExpanded( false );
		this.toggleRead( false );
		this.toggleTitles( true );
		this.$element
			.addClass( 'mw-echo-ui-crossWikiNotificationItemWidget' )
			.append(
				$( '<div>' )
					.addClass( 'mw-echo-ui-crossWikiNotificationItemWidget-separator' ),
				this.listWidget.$element,
				this.errorWidget.$element
			);
	};

	/* Initialization */

	OO.inheritClass( mw.echo.ui.CrossWikiNotificationItemWidget, mw.echo.ui.NotificationItemWidget );
	OO.mixinClass( mw.echo.ui.CrossWikiNotificationItemWidget, OO.ui.mixin.PendingElement );

	/* Methods */

	/**
	 * Respond to model removing source group
	 *
	 * @param {string} groupName Symbolic name of the group
	 */
	mw.echo.ui.CrossWikiNotificationItemWidget.prototype.onModelDiscard = function ( groupName ) {
		var list = this.getList(),
			group = list.getItemFromId( groupName );

		list.removeItems( [ group ] );

		if ( list.isEmpty() ) {
			this.controller.removeCrossWikiItem();
		}
	};

	/**
	 * @inheritdoc
	 */
	mw.echo.ui.CrossWikiNotificationItemWidget.prototype.markRead = function () {
		// Cross wiki notification is always only marked as read, never as
		// unread. The original parameter is unneeded
		this.controller.markEntireCrossWikiItemAsRead();
	};

	/**
	 * Populate the items in this widget according to the data
	 * in the model
	 */
	mw.echo.ui.CrossWikiNotificationItemWidget.prototype.populateFromModel = function () {
		var groupWidgets = [],
			groups = this.model.getList().getItems();

		for ( var i = 0; i < groups.length; i++ ) {
			// Create SubGroup widgets
			groupWidgets.push(
				new mw.echo.ui.SubGroupListWidget(
					this.controller,
					groups[ i ],
					{
						$overlay: this.$overlay,
						showTitle: this.showTitles
					}
				)
			);
		}
		this.getList().addItems( groupWidgets );
	};

	/**
	 * Toggle the visibility of the titles
	 *
	 * @param {boolean} [show] Show titles
	 */
	mw.echo.ui.CrossWikiNotificationItemWidget.prototype.toggleTitles = function ( show ) {
		var items = this.getList().getItems();

		show = show === undefined ? !this.showTitles : show;

		if ( this.showTitles !== show ) {
			this.showTitles = show;
			for ( var i = 0; i < items.length; i++ ) {
				items[ i ].toggleTitle( show );
			}
		}
	};

	/**
	 * Check whether the titles should be shown and toggle them
	 * in each of the group lists.
	 */
	mw.echo.ui.CrossWikiNotificationItemWidget.prototype.checkShowTitles = function () {
		this.toggleTitles( this.getList().getItemCount() > 1 );
	};

	/**
	 * Toggle the visibility of the notification item list and the placeholder/error widget.
	 *
	 * @param {boolean} showList Show the list
	 */
	mw.echo.ui.CrossWikiNotificationItemWidget.prototype.toggleListDisplay = function ( showList ) {
		this.errorWidget.toggle( !showList );
		this.listWidget.toggle( showList );
	};

	/**
	 * Show an error message
	 *
	 * @param {string} label Error label
	 * @param {string} link Error link
	 */
	mw.echo.ui.CrossWikiNotificationItemWidget.prototype.showErrorMessage = function ( label, link ) {
		this.errorWidget.setLabel( label || '' );
		this.errorWidget.setLink( link || '' );

		this.toggleListDisplay( false );
	};

	/**
	 * Expand the group and fetch the list of notifications.
	 * Only fetch the first time we expand.
	 */
	mw.echo.ui.CrossWikiNotificationItemWidget.prototype.expand = function () {
		var widget = this;

		this.toggleExpanded( !this.expanded );
		this.updateExpandButton();

		if ( !this.expanded ) {
			return;
		}

		if ( !this.fetchedOnce ) {
			// Expand
			this.pushPending();
			this.toggleListDisplay( true );
			// Query all sources
			this.controller.fetchCrossWikiNotifications()
				.catch(
					function ( result ) {
						var loginPageTitle = mw.Title.newFromText( 'Special:UserLogin' );
						// If failure, check if the failure is due to login
						// so we can display a more comprehensive error
						// message in that case
						if ( result.errCode === 'notlogin-required' ) {
							// Login error
							widget.showErrorMessage(
								mw.message( 'echo-notification-popup-loginrequired' ),
								// Set the option link to the login page
								loginPageTitle.getUrl()
							);
						} else {
							// General error
							widget.showErrorMessage( mw.msg( 'echo-api-failure' ) );
						}
					}
				)
				.always( this.popPending.bind( this ) );

			// Only run the fetch notifications action once
			this.fetchedOnce = true;
		}
	};

	/**
	 * Toggle the expand/collapsed state of this group widget
	 *
	 * @param {boolean} show Show the widget expanded
	 */
	mw.echo.ui.CrossWikiNotificationItemWidget.prototype.toggleExpanded = function ( show ) {
		this.expanded = show !== undefined ? !!show : !this.expanded;

		this.$element.toggleClass( 'mw-echo-ui-crossWikiNotificationItemWidget-expanded', this.expanded );

		if ( this.expanded ) {
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
	mw.echo.ui.CrossWikiNotificationItemWidget.prototype.updateExpandButton = function () {
		var type = this.model.getType();

		this.toggleExpandButton.setLabel(
			this.expanded ?
				mw.msg( 'notification-link-text-collapse-all' ) :
				// Messages that appear here are:
				// * notification-link-text-expand-alert-count
				// * notification-link-text-expand-notice-count
				mw.msg(
					'notification-link-text-expand-' +
						( type === 'message' ? 'notice' : type ) +
						'-count',
					mw.language.convertNumber( this.model.getCount() )
				)
		);
		this.toggleExpandButton.setIcon(
			this.expanded ?
				'collapse' :
				'expand'
		);
	};

	/**
	 * Get the list widget contained in this item
	 *
	 * @return {mw.echo.ui.SortedListWidget} List widget
	 */
	mw.echo.ui.CrossWikiNotificationItemWidget.prototype.getList = function () {
		return this.listWidget;
	};
}() );
