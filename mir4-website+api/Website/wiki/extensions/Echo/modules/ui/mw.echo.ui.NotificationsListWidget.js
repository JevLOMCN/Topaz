( function () {
	/**
	 * Notifications list widget.
	 * All of its items must be of the mw.echo.ui.NotificationItem type.
	 *
	 * @class
	 * @extends mw.echo.ui.SortedListWidget
	 *
	 * @constructor
	 * @param {mw.echo.Controller} controller Echo notifications controller
	 * @param {mw.echo.dm.ModelManager} manager Model manager
	 * @param {Object} [config] Configuration object
	 *  marked as read when they are seen.
	 * @cfg {string} [timestamp=0] A timestamp representing the latest item in
	 *  the list.
	 * @cfg {jQuery} [$overlay] A jQuery element functioning as an overlay
	 *  for popups.
	 */
	mw.echo.ui.NotificationsListWidget = function MwEchoUiNotificationsListWidget( controller, manager, config ) {
		config = config || {};
		// Parent constructor
		mw.echo.ui.NotificationsListWidget.super.call(
			this,
			// Sorting callback
			function ( a, b ) {
				if ( !a.isRead() && b.isRead() ) {
					return -1; // Unread items are always above read items
				} else if ( a.isRead() && !b.isRead() ) {
					return 1;
				} else if ( !a.isForeign() && b.isForeign() ) {
					return -1;
				} else if ( a.isForeign() && !b.isForeign() ) {
					return 1;
				}

				// Reverse sorting
				if ( b.getTimestamp() < a.getTimestamp() ) {
					return -1;
				} else if ( b.getTimestamp() > a.getTimestamp() ) {
					return 1;
				}

				// Fallback on IDs
				return b.getId() - a.getId();
			},
			config
		);

		// Initialize models
		this.controller = controller;
		this.manager = manager;
		this.models = {};

		// Properties
		this.$overlay = config.$overlay || this.$element;
		this.timestamp = config.timestamp || 0;

		// Dummy 'loading' option widget
		this.loadingOptionWidget = new mw.echo.ui.PlaceholderItemWidget();

		this.resetLoadingOption();

		this.manager.connect( this, {
			update: 'resetDataFromModel',
			discard: 'onModelManagerDiscard'
		} );

		this.$element
			.addClass( 'mw-echo-ui-notificationsListWidget' );
	};

	/* Initialization */

	OO.inheritClass( mw.echo.ui.NotificationsListWidget, mw.echo.ui.SortedListWidget );

	/* Events */

	/**
	 * @event modified
	 *
	 * The content of this list has changed.
	 * This event is to state that not only has the content changed
	 * but the actual DOM has been manipulated.
	 */

	/* Methods */

	mw.echo.ui.NotificationsListWidget.prototype.onModelManagerDiscard = function ( modelName ) {
		var items = this.getItems();

		// For the moment, this is only relevant for xwiki bundles.
		// Local single items will not get their entire model removed, but
		// local bundles may - when that happens, the condition below should
		// also deal with local bundles and removing them specifically
		if ( modelName === 'xwiki' ) {
			for ( var i = 0; i < items.length; i++ ) {
				if ( items[ i ] instanceof mw.echo.ui.CrossWikiNotificationItemWidget ) {
					this.removeItems( [ items[ i ] ] );
					this.checkForEmptyNotificationsList();
					return;
				}
			}
		}

		this.emit( 'modified' );
	};

	/**
	 * Respond to model manager update event.
	 * This event means we are repopulating the entire list and the
	 * associated models within it.
	 *
	 * @param {Object} models Object of new models to populate the
	 *  list.
	 * @fires modified
	 */
	mw.echo.ui.NotificationsListWidget.prototype.resetDataFromModel = function ( models ) {
		var itemWidgets = [],
			$elements = $();

		var modelId;
		// Detach all attached models
		for ( modelId in this.models ) {
			this.detachModel( modelId );
		}

		// Attach and process new models
		for ( modelId in models ) {
			var model = models[ modelId ];
			this.attachModel( modelId, model );

			var widget;
			// Build widgets based on the data in the model
			if ( model.isGroup() ) {
				if ( model.isForeign() ) {
					// One Widget to Rule Them All
					widget = new mw.echo.ui.CrossWikiNotificationItemWidget(
						this.controller,
						model,
						{
							$overlay: this.$overlay,
							animateSorting: this.animated
						}
					);
				} else {
					// local bundle
					widget = new mw.echo.ui.BundleNotificationItemWidget(
						this.controller,
						model,
						{
							$overlay: this.$overlay,
							bundle: false,
							animateSorting: this.animated
						}
					);
				}
				itemWidgets.push( widget );
				$elements = $elements.add( widget.$element );
			} else {
				var subItems = model.getItems();
				// Separate widgets per item
				for ( var i = 0; i < subItems.length; i++ ) {
					var subItem = subItems[ i ];
					widget = new mw.echo.ui.SingleNotificationItemWidget(
						this.controller,
						subItem,
						{
							$overlay: this.$overlay,
							bundle: false
						}
					);
					itemWidgets.push( widget );
					$elements = $elements.add( widget.$element );
				}
			}
		}

		// Reset the current items and re-add the new item widgets
		this.clearItems();

		// fire render hook
		mw.hook( 'ext.echo.notifications.beforeRender' ).fire( this.$element, $elements );

		this.addItems( itemWidgets );

		this.checkForEmptyNotificationsList();

		this.emit( 'modified' );
	};

	/**
	 * Attach a model to the widget
	 *
	 * @param {string} modelId Symbolic name for the model
	 * @param {mw.echo.dm.SortedList} model Notifications list model
	 */
	mw.echo.ui.NotificationsListWidget.prototype.attachModel = function ( modelId, model ) {
		this.models[ modelId ] = model;
	};

	/**
	 * Detach a model from the widget
	 *
	 * @param {string} modelId Notifications list model
	 */
	mw.echo.ui.NotificationsListWidget.prototype.detachModel = function ( modelId ) {
		this.models[ modelId ].disconnect( this );
		delete this.models[ modelId ];
	};

	/**
	 * Reset the loading 'dummy' option widget
	 *
	 * @param {string} [label] Label for the option widget
	 * @param {string} [link] Link for the option widget
	 * @fires modified
	 */
	mw.echo.ui.NotificationsListWidget.prototype.resetLoadingOption = function ( label, link ) {
		this.loadingOptionWidget.setLabel( label || '' );
		this.loadingOptionWidget.setLink( link || '' );
		if ( this.isEmpty() ) {
			this.addItems( [ this.loadingOptionWidget ] );
		}
		this.emit( 'modified' );
	};

	/**
	 * Check if the list of notifications is empty and udpate the placeholder
	 * widget as needed.
	 */
	mw.echo.ui.NotificationsListWidget.prototype.checkForEmptyNotificationsList = function () {
		this.resetLoadingOption( this.isEmpty() ? mw.msg( 'echo-notification-placeholder' ) : '' );
	};

	/**
	 * Reset the 'initiallyUnseen' state of all items
	 */
	mw.echo.ui.NotificationsListWidget.prototype.resetInitiallyUnseenItems = function () {
		var itemWidgets = this.getItems();

		for ( var i = 0; i < itemWidgets.length; i++ ) {
			itemWidgets[ i ].resetInitiallyUnseen();
		}
	};
}() );
