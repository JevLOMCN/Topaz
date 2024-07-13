( function () {
	/**
	 * Sub group list widget.
	 * This widget contains a list of notifications from a single source
	 * in a cross-wiki notifications group.
	 *
	 * @param {mw.echo.Controller} controller Notifications controller
	 * @param {mw.echo.dm.SortedList} listModel Notifications list model for this source
	 * @param {Object} config Configuration object
	 * @cfg {boolean} [showTitle=false] Show the title of this group
	 * @cfg {boolean} [showMarkAllRead=false] Show a mark all read button for this group
	 * @cfg {boolean} [animateSorting=false] Animate the sorting of items
	 * @cfg {jQuery} [$overlay] A jQuery element functioning as an overlay
	 *  for popups.
	 */
	mw.echo.ui.SubGroupListWidget = function MwEchoUiSubGroupListWidget( controller, listModel, config ) {
		this.$header = $( '<div>' )
			.addClass( 'mw-echo-ui-subGroupListWidget-header' );

		config = config || {};

		this.controller = controller;
		this.model = listModel;

		// Parent constructor
		mw.echo.ui.SubGroupListWidget.super.call( this, $.extend( { data: this.getSource() }, config ) );

		this.showTitle = !!config.showTitle;
		this.showMarkAllRead = !!config.showMarkAllRead;
		this.$overlay = config.$overlay || this.$element;

		this.listWidget = new mw.echo.ui.SortedListWidget(
			// Sorting callback
			config.sortingCallback || function ( a, b ) {
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
				$overlay: this.$overlay,
				animated: config.animateSorting
			}
		);

		var sourceURL = this.model.getSourceURL() ?
			this.model.getSourceURL().replace( '$1', 'Special:Notifications' ) :
			null;
		if ( sourceURL ) {
			this.title = new OO.ui.ButtonWidget( {
				framed: false,
				classes: [
					'mw-echo-ui-subGroupListWidget-header-row-title',
					'mw-echo-ui-subGroupListWidget-header-row-cell'
				],
				href: sourceURL
			} );
		} else {
			this.title = new OO.ui.LabelWidget( {
				classes: [
					'mw-echo-ui-subGroupListWidget-header-row-title',
					'mw-echo-ui-subGroupListWidget-header-row-cell'
				]
			} );
		}

		if ( this.model.getTitle() ) {
			this.title.setLabel( this.model.getTitle() );
		}
		this.title.toggle( this.showTitle );

		// Mark all as read button
		this.markAllReadButton = new OO.ui.ButtonWidget( {
			framed: true,
			icon: 'checkAll',
			label: mw.msg( 'echo-specialpage-section-markread' ),
			classes: [
				'mw-echo-ui-subGroupListWidget-header-row-markAllReadButton',
				'mw-echo-ui-subGroupListWidget-header-row-cell'
			]
		} );

		// Events
		this.model.connect( this, {
			// Cross-wiki items can be discarded when marked as read.
			// We need to differentiate this explicit action from the
			// action of 'remove' because 'remove' is also used when
			// an item is resorted by OO.SortedEmitterWidget before
			// it is re-added again
			discard: 'onModelDiscardItems',
			// Update all items
			update: 'resetItemsFromModel'
		} );
		this.model.connect( this, { itemUpdate: 'toggleMarkAllReadButton' } );
		this.markAllReadButton.connect( this, { click: 'onMarkAllReadButtonClick' } );

		// Initialize
		this.toggleMarkAllReadButton();

		this.$element
			.addClass( 'mw-echo-ui-subGroupListWidget' )
			.append(
				this.$header.append(
					$( '<div>' )
						.addClass( 'mw-echo-ui-subGroupListWidget-header-row' )
						.append(
							this.title.$element,
							this.markAllReadButton.$element
						)
				),
				this.listWidget.$element
			);

		// eslint-disable-next-line no-jquery/no-global-selector
		this.$pageContentText = $( '#mw-content-text' );
		$( window ).on( 'resize', this.resizeHeader.bind( this ) );

		// Resize the header after the stack finishes loading
		// so the widget is attached
		setTimeout( this.resizeHeader.bind( this ), 0 );
	};

	/* Initialization */

	OO.inheritClass( mw.echo.ui.SubGroupListWidget, OO.ui.Widget );

	/* Methods */

	/**
	 * Respond to window resize event
	 */
	mw.echo.ui.SubGroupListWidget.prototype.resizeHeader = function () {
		var contentWidth = this.$pageContentText.width(),
			screenTooNarrow = this.$header.width() > contentWidth;

		// Screen too narrow, put the button under the date
		this.title.$element.toggleClass(
			'mw-echo-ui-subGroupListWidget-header-row-cell',
			!screenTooNarrow
		);
		this.markAllReadButton.$element.toggleClass(
			'mw-echo-ui-subGroupListWidget-header-row-cell',
			!screenTooNarrow
		);
	};

	/**
	 * Destroy the widget and disconnect events
	 */
	mw.echo.ui.SubGroupListWidget.prototype.destroy = function () {
		$( window ).off( 'resize' );
	};

	/**
	 * Toggle the visibility of the mark all read button for this group
	 * based on whether there are unread notifications
	 */
	mw.echo.ui.SubGroupListWidget.prototype.toggleMarkAllReadButton = function () {
		this.markAllReadButton.toggle( this.showMarkAllRead && this.hasUnread() );
	};

	/**
	 * Respond to 'mark all as read' button click
	 */
	mw.echo.ui.SubGroupListWidget.prototype.onMarkAllReadButtonClick = function () {
		this.controller.markEntireListModelRead( this.model.getName() );
	};

	/**
	 * Check whether this sub group list has any unread notifications
	 *
	 * @return {boolean} Sub group has unread notifications
	 */
	mw.echo.ui.SubGroupListWidget.prototype.hasUnread = function () {
		var isUnread = function ( item ) {
				return !item.isRead();
			},
			items = this.model.getItems();

		return items.some( isUnread );
	};

	/**
	 * Reset the items and rebuild them according to the model.
	 *
	 * @param {mw.echo.dm.NotificationItem[]} [items] Item models that are added.
	 *  If this is empty, the widget will request all the items from the model.
	 */
	mw.echo.ui.SubGroupListWidget.prototype.resetItemsFromModel = function ( items ) {
		var itemWidgets = [],
			$elements = $();

		items = items || this.model.getItems();

		for ( var i = 0; i < items.length; i++ ) {
			var widget = new mw.echo.ui.SingleNotificationItemWidget(
				this.controller,
				items[ i ],
				{
					$overlay: this.$overlay,
					bundle: items[ i ].isBundled()
				}
			);
			itemWidgets.push( widget );
			$elements = $elements.add( widget.$element );
		}

		// Clear the current items if any exist
		this.getListWidget().clearItems();

		// fire render hook
		mw.hook( 'ext.echo.notifications.beforeRender' ).fire( this.$element, $elements );

		// Add the new items
		this.getListWidget().addItems( itemWidgets );
	};

	/**
	 * Respond to model remove event. This may happen when an item
	 * is marked as read.
	 *
	 * @param {mw.echo.dm.NotificationItem[]} items Notification item models
	 */
	mw.echo.ui.SubGroupListWidget.prototype.onModelDiscardItems = function ( items ) {
		var itemWidgets = [];

		for ( var i = 0; i < items.length; i++ ) {
			itemWidgets.push( this.listWidget.getItemFromId( items[ i ].getId() ) );
		}
		this.listWidget.removeItems( itemWidgets );
	};

	/**
	 * Get the associated list widget. This is useful to specifically
	 * add and/or remove items from the list.
	 *
	 * @return {mw.echo.ui.SortedListWidget} List widget
	 */
	mw.echo.ui.SubGroupListWidget.prototype.getListWidget = function () {
		return this.listWidget;
	};

	/**
	 * Get the timestamp for the list
	 *
	 * @return {number} Timestamp
	 */
	mw.echo.ui.SubGroupListWidget.prototype.getTimestamp = function () {
		return this.model.getTimestamp();
	};

	/**
	 * Toggle the visibility of the title
	 *
	 * @param {boolean} show Show the title
	 */
	mw.echo.ui.SubGroupListWidget.prototype.toggleTitle = function ( show ) {
		show = show !== undefined ? show : !this.showTitle;

		if ( this.showTitle !== show ) {
			this.showTitle = show;
			this.title.toggle( this.showTitle );
		}
	};

	/**
	 * Get a the source of this list.
	 *
	 * @return {string} Group source
	 */
	mw.echo.ui.SubGroupListWidget.prototype.getSource = function () {
		return this.model.getSource();
	};

	/**
	 * Get an array of IDs of all of the items in this group
	 *
	 * @return {number[]} Array of item IDs
	 */
	mw.echo.ui.SubGroupListWidget.prototype.getAllItemIDs = function () {
		return this.model.getAllItemIds();
	};

	/**
	 * Get an array of IDs of all of the items in this group that
	 * correspond to a specific type
	 *
	 * @param {string} type Item type
	 * @return {number[]} Array of item IDs
	 */
	mw.echo.ui.SubGroupListWidget.prototype.getAllItemIDsByType = function ( type ) {
		return this.model.getAllItemIdsByType( type );
	};

	/**
	 * Check whether this group is foreign
	 *
	 * @return {boolean} This group is foreign
	 */
	mw.echo.ui.SubGroupListWidget.prototype.isForeign = function () {
		return this.model.isForeign();
	};

	/**
	 * Get the group id, which is represented by its model symbolic name.
	 * This is meant for sorting callbacks that fallback on
	 * sorting by IDs.
	 *
	 * @return {string} Group source
	 */
	mw.echo.ui.SubGroupListWidget.prototype.getId = function () {
		return this.model.getName();
	};
}() );
