( function () {
	/* global moment:false */
	/**
	 * A base widget for displaying notification items.
	 *
	 * @class
	 * @abstract
	 * @extends OO.ui.Widget
	 *
	 * @constructor
	 * @param {mw.echo.Controller} controller Echo controller
	 * @param {mw.echo.dm.NotificationItem} model Notification item model
	 * @param {Object} [config] Configuration options
	 * @cfg {jQuery} [$overlay] A jQuery element functioning as an overlay
	 *  for popups.
	 * @cfg {boolean} [bundle=false] This notification item is part of a bundle.
	 */
	mw.echo.ui.NotificationItemWidget = function MwEchoUiNotificationItemWidget( controller, model, config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.NotificationItemWidget.super.call( this, $.extend( { data: model.getId() }, config ) );

		this.controller = controller;
		this.model = model;

		this.$overlay = config.$overlay || this.$element;
		this.bundle = !!config.bundle;

		this.$content = $( '<div>' ).addClass( 'mw-echo-ui-notificationItemWidget-content' );
		this.$actions = $( '<div>' )
			.addClass( 'mw-echo-ui-notificationItemWidget-content-actions' );

		// Mark as read
		this.markAsReadButton = new mw.echo.ui.ToggleReadCircleButtonWidget( {
			framed: false,
			classes: [ 'mw-echo-ui-notificationItemWidget-markAsReadButton' ],
			markAsRead: !this.model.isRead()
		} );

		var $icon;
		// Icon
		if ( this.model.getIconURL() ) {
			$icon = $( '<div>' )
				.addClass( 'mw-echo-ui-notificationItemWidget-icon' )
				.append( $( '<img>' ).attr( {
					src: this.model.getIconURL(),
					role: 'presentation',
					alt: ' '
				} ) );
		}

		var $message = $( '<div>' ).addClass( 'mw-echo-ui-notificationItemWidget-content-message' );
		// Content
		$message.append(
			$( '<div>' )
				.addClass( 'mw-echo-ui-notificationItemWidget-content-message-header-wrapper' )
				.append(
					$( '<div>' )
						.addClass( 'mw-echo-ui-notificationItemWidget-content-message-header' )
						.append( this.model.getContentHeader() )
				)
		);
		if ( !this.bundle && this.model.getContentBody() ) {
			$message.append(
				$( '<div>' )
					.addClass( 'mw-echo-ui-notificationItemWidget-content-message-body' )
					.append( this.model.getContentBody() )
					// dir=auto has a similar effect to wrapping the content in <bdi>, but
					// makes text-overflow: ellipsis; behave less strangely
					.attr( 'dir', 'auto' )
			);
		}

		// Actions menu
		this.actionsButtonSelectWidget = new OO.ui.ButtonSelectWidget( {
			classes: [ 'mw-echo-ui-notificationItemWidget-content-actions-buttons' ],
			tabIndex: -1
		} );

		// Popup menu
		this.menuPopupButtonWidget = new mw.echo.ui.ActionMenuPopupWidget( {
			framed: false,
			icon: 'ellipsis',
			$overlay: this.$overlay,
			horizontalPosition: this.bundle ? 'end' : 'auto',
			title: mw.msg( 'echo-notification-more-options-tooltip' ),
			classes: [ 'mw-echo-ui-notificationItemWidget-content-actions-menu' ]
		} );

		// Timestamp
		// We want to use extra-short timestamp strings; we change the locale
		// to our echo-defined one and use that instead of the normal moment locale
		var echoMoment = moment.utc( this.model.getTimestamp() );
		echoMoment.locale( 'echo-shortRelativeTime' );
		echoMoment.local();

		this.timestampWidget = new OO.ui.LabelWidget( {
			classes: [ 'mw-echo-ui-notificationItemWidget-content-actions-timestamp' ],
			// Get the time 'fromNow' without the suffix 'ago'
			label: echoMoment.fromNow( true )
		} );

		// Build the actions line
		if ( this.bundle ) {
			// In a bundled item, the timestamp should go before the menu
			this.$actions.append(
				$( '<div>' )
					// We are wrapping the actions in a 'row' div so that the
					// internal pieces are also a table layout
					.addClass( 'mw-echo-ui-notificationItemWidget-content-actions-row' )
					.append(
						this.actionsButtonSelectWidget.$element,
						this.timestampWidget.$element,
						this.menuPopupButtonWidget.$element
					)
			);
		} else {
			this.$actions.append(
				this.actionsButtonSelectWidget.$element,
				this.menuPopupButtonWidget.$element,
				this.timestampWidget.$element
			);
		}

		// Actions
		var outsideMenuItemCounter = 0;
		var secondaryUrls = this.model.getSecondaryUrls();
		for ( var i = 0; i < secondaryUrls.length; i++ ) {
			var urlObj = secondaryUrls[ i ];

			// Items are placed outside the dotdotdot menu if they are
			// prioritized explicitly, *except* for items inside a bundle
			// (where all actions are inside the menu) or there are more than
			// two prioritized actions (all others go into the menu)
			var isOutsideMenu = !this.bundle &&
				(
					(
						// Make sure we don't have too many prioritized items
						urlObj.prioritized &&
						outsideMenuItemCounter < mw.echo.config.maxPrioritizedActions
					) ||
					// If the number of total items are equal to or less than the
					// maximum allowed, they all go outside the menu
					// mw.echo.config.maxPrioritizedActions is 2 on desktop and 1 on mobile.
					secondaryUrls.length <= mw.echo.config.maxPrioritizedActions
				);

			var linkButton = new mw.echo.ui.MenuItemWidget( {
				type: urlObj.type,
				actionData: urlObj.data,
				icon: urlObj.icon || 'next',
				label: urlObj.label,
				tooltip: urlObj.tooltip,
				description: urlObj.description,
				url: urlObj.url,
				prioritized: isOutsideMenu
			} );

			// Limit to 2 items outside the menu
			if ( isOutsideMenu ) {
				this.actionsButtonSelectWidget.addItems( [ linkButton ] );
				this.actionsButtonSelectWidget.setTabIndex( 0 );
				outsideMenuItemCounter++;
			} else {
				this.menuPopupButtonWidget.getMenu().addItems( [ linkButton ] );
			}
		}

		if ( this.bundle ) {
			// In a bundle, we have table layout, so the icon is
			// inserted into the content, and the 'mark as read'
			// button is not floating, and should be at the end
			this.$content.append(
				$icon,
				$message,
				this.$actions,
				this.markAsReadButton.$element
			);
			this.$element.append( this.$content );
		} else {
			this.$content.append(
				this.markAsReadButton.$element,
				$message,
				this.$actions
			);
			this.$element.append( $icon, this.$content );
		}

		// Events
		this.actionsButtonSelectWidget.connect( this, { choose: 'onPopupButtonWidgetChoose' } );
		this.menuPopupButtonWidget.getMenu().connect( this, { choose: 'onPopupButtonWidgetChoose' } );
		this.markAsReadButton.connect( this, { click: 'onMarkAsReadButtonClick' } );

		this.$element
			.addClass( 'mw-echo-ui-notificationItemWidget' )
			.toggleClass( 'mw-echo-ui-notificationItemWidget-initiallyUnseen', !this.model.isSeen() && !this.bundle )
			.toggleClass( 'mw-echo-ui-notificationItemWidget-bundled', this.bundle );

		if ( this.model.getPrimaryUrl() ) {
			this.$element
				.attr( 'href', this.model.getPrimaryUrl() )
				.on( 'click', this.onPrimaryLinkClick.bind( this ) );
		}
	};

	OO.inheritClass( mw.echo.ui.NotificationItemWidget, OO.ui.Widget );

	// Make the whole item a link to get native link behaviour
	mw.echo.ui.NotificationItemWidget.static.tagName = 'a';

	/**
	 * Respond to primary link click.
	 * Override this in the descendents.
	 *
	 * @return {boolean} true
	 */
	mw.echo.ui.NotificationItemWidget.prototype.onPrimaryLinkClick = function () {
		return true;
	};

	/**
	 * Manage a click on a dynamic secondary link.
	 * We can't know what the link intends us to do in the API, so we trust the 'apiParams'
	 * to tell the controller. When the link is clicked, we will pass the information on
	 * to the controller, which will manage whatever promise and action is needed.
	 *
	 * NOTE: The messages are parsed as HTML. If user-input is expected
	 * please make sure to properly escape it.
	 *
	 * @param {OO.ui.ButtonOptionWidget} item The selected item
	 */
	mw.echo.ui.NotificationItemWidget.prototype.onPopupButtonWidgetChoose = function ( item ) {
		if ( !( item instanceof mw.echo.ui.MenuItemWidget ) ) {
			// Other kinds of items may be added by subclasses
			return;
		}

		var actionData = item && item.getActionData(),
			messages = item && item.getConfirmationMessages(),
			widget = this;

		// Send to controller
		item.pushPending();
		this.controller.performDynamicAction( actionData, this.getModel().getSource() )
			.then( function () {
				var $title = $( '<p>' )
						.addClass( 'mw-echo-ui-notificationItemWidget-notify-title' )
						.append( $.parseHTML( messages.title ) ),
					$description = $( '<p>' )
						.addClass( 'mw-echo-ui-notificationItemWidget-notify-description' )
						.append( $.parseHTML( messages.description ) ),
					$confirmation;

				// Get rid of the button
				item.disconnect( this );
				if ( item.isPrioritized() ) {
					widget.actionsButtonSelectWidget.removeItems( [ item ] );
				} else {
					// It's inside the popup menu
					widget.menuPopupButtonWidget.getMenu().removeItems( [ item ] );
				}

				// Make sure to hide either piece if it is empty
				$title.toggle( !!$title.text() );
				$description.toggle( !!$description.text() );

				// Display confirmation
				$confirmation = $( '<div>' )
					.append( $title, $description );

				// Send to mw.notify
				mw.notify( $confirmation );
			} );
	};

	/**
	 * Respond to mark as read button click
	 */
	mw.echo.ui.NotificationItemWidget.prototype.onMarkAsReadButtonClick = function () {
		// If we're marking read or unread, the notification was already seen.
		// Remove the animation class
		this.$element.removeClass( 'mw-echo-ui-notificationItemWidget-initiallyUnseen' );
		this.markRead( !this.model.isRead() );
	};

	/**
	 * Mark this notification as read
	 *
	 * @method
	 * @abstract
	 * @param {boolean} [isRead=true] Notification is marked as read
	 */
	mw.echo.ui.NotificationItemWidget.prototype.markRead = null;

	/**
	 * Get the notification link
	 *
	 * @return {string} Notification link
	 */
	mw.echo.ui.NotificationItemWidget.prototype.getPrimaryUrl = function () {
		return this.model.getPrimaryUrl();
	};

	/**
	 * Get the item id
	 *
	 * @return {number} Notification id
	 */
	mw.echo.ui.NotificationItemWidget.prototype.getTimestamp = function () {
		return this.model.getTimestamp();
	};

	/**
	 * Get the notification Id
	 *
	 * @return {number} Notification id
	 */
	mw.echo.ui.NotificationItemWidget.prototype.getId = function () {
		return this.model.getId();
	};

	/**
	 * Check whether this item is seen.
	 *
	 * @return {boolean} Item is seen
	 */
	mw.echo.ui.NotificationItemWidget.prototype.isSeen = function () {
		return this.model.isSeen();
	};

	/**
	 * Check whether this item is read.
	 *
	 * @return {boolean} Item is read
	 */
	mw.echo.ui.NotificationItemWidget.prototype.isRead = function () {
		return this.model.isRead();
	};

	/**
	 * Check whether this item is foreign.
	 *
	 * @return {boolean} Item is foreign
	 */
	mw.echo.ui.NotificationItemWidget.prototype.isForeign = function () {
		return this.model.isForeign();
	};

	/**
	 * Toggle the function of the 'mark as read' buttons from 'mark as read' to 'mark as unread'
	 * and vice versa.
	 *
	 * @param {boolean} [showMarkAsRead] Show the 'mark as read' buttons
	 *  - "false" means that the item is marked as read, whereby we show the user 'mark unread'
	 *    buttons.
	 *  - "true" means that the item is marked as unread and we show the user 'mark as read'
	 *    buttons
	 */
	mw.echo.ui.NotificationItemWidget.prototype.toggleMarkAsReadButtons = function ( showMarkAsRead ) {
		showMarkAsRead = showMarkAsRead !== undefined ? showMarkAsRead : !this.model.isRead();

		this.markAsReadButton.toggleState( showMarkAsRead );
		this.menuPopupButtonWidget.toggle( !this.menuPopupButtonWidget.getMenu().isEmpty() );
	};

	/**
	 * Toggle the read state of the widget
	 *
	 * @param {boolean} [read] The current read state. If not given, the state will
	 *  become the opposite of its current state.
	 */
	mw.echo.ui.NotificationItemWidget.prototype.toggleRead = function ( read ) {
		this.read = read !== undefined ? read : !this.read;

		this.$element.toggleClass( 'mw-echo-ui-notificationItemWidget-unread', !this.read );
		this.toggleMarkAsReadButtons( !this.read );
	};

	/**
	 * Toggle the seen state of the widget
	 *
	 * @param {boolean} [seen] The current seen state. If not given, the state will
	 *  become the opposite of its current state.
	 */
	mw.echo.ui.NotificationItemWidget.prototype.toggleSeen = function ( seen ) {
		this.seen = seen !== undefined ? seen : !this.seen;

		this.$element
			.toggleClass( 'mw-echo-ui-notificationItemWidget-unseen', !this.seen );
	};

	/**
	 * Get the model associated with this widget.
	 *
	 * @return {mw.echo.dm.NotificationItem} Item model
	 */
	mw.echo.ui.NotificationItemWidget.prototype.getModel = function () {
		return this.model;
	};

	/**
	 * Disconnect events when widget is destroyed.
	 */
	mw.echo.ui.NotificationItemWidget.prototype.destroy = function () {
		this.model.disconnect( this );
	};

	/**
	 * Remove the 'initiallyUnseen' class, which was only used for the
	 * unseen animation when the user has first seen it.
	 */
	mw.echo.ui.NotificationItemWidget.prototype.resetInitiallyUnseen = function () {
		this.$element.removeClass( 'mw-echo-ui-notificationItemWidget-initiallyUnseen' );
	};

	/**
	 * Declares whether this widget is a cloned fake.
	 *
	 * @return {boolean} false
	 */
	mw.echo.ui.NotificationItemWidget.prototype.isFake = function () {
		return false;
	};
}() );
