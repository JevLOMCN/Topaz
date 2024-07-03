( function () {
	/**
	 * An inbox-type widget that encompases a dated notifications list with pagination
	 *
	 * @class
	 * @extends OO.ui.Widget
	 * @mixins OO.ui.mixin.PendingElement
	 *
	 * @constructor
	 * @param {mw.echo.Controller} controller Echo controller
	 * @param {mw.echo.dm.ModelManager} manager Model manager
	 * @param {Object} [config] Configuration object
	 * @cfg {number} [limit=25] Limit the number of notifications per page
	 * @cfg {string} [prefLink] Link to preferences page
	 * @cfg {jQuery} [$overlay] An overlay for the popup menus
	 */
	mw.echo.ui.NotificationsInboxWidget = function MwEchoUiNotificationsInboxWidget( controller, manager, config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.NotificationsInboxWidget.super.call( this, config );
		// Mixin constructors
		OO.ui.mixin.PendingElement.call( this, config );

		this.controller = controller;
		this.manager = manager;

		this.$overlay = config.$overlay || this.$element;
		this.limit = config.limit || 25;

		this.error = false;

		// A notice or error message widget
		this.noticeMessageWidget = new OO.ui.LabelWidget( {
			classes: [ 'mw-echo-ui-notificationsInboxWidget-notice' ]
		} );

		// Notifications list
		this.datedListWidget = new mw.echo.ui.DatedNotificationsWidget(
			this.controller,
			this.manager,
			{
				$overlay: this.$overlay,
				animateSorting: false
			}
		);
		this.setPendingElement( this.datedListWidget.$element );

		// Pagination
		this.topPaginationWidget = new mw.echo.ui.PaginationWidget(
			this.manager.getPaginationModel(),
			{
				itemsPerPage: this.limit
			}
		);
		this.bottomPaginationWidget = new mw.echo.ui.PaginationWidget(
			this.manager.getPaginationModel(),
			{
				itemsPerPage: this.limit
			}
		);

		// Settings menu
		this.settingsMenu = new mw.echo.ui.SpecialHelpMenuWidget(
			this.manager,
			{
				framed: true,
				prefLink: config.prefLink,
				$overlay: this.$overlay
			}
		);

		// Filter by read state
		this.readStateSelectWidget = new mw.echo.ui.ReadStateButtonSelectWidget();

		// Sidebar filters
		this.xwikiUnreadWidget = new mw.echo.ui.CrossWikiUnreadFilterWidget(
			this.controller,
			this.manager.getFiltersModel()
		);

		// Events
		this.readStateSelectWidget.connect( this, { filter: 'onReadStateFilter' } );
		this.xwikiUnreadWidget.connect( this, { filter: 'onSourcePageFilter' } );
		this.manager.connect( this, {
			modelItemUpdate: 'updatePaginationLabels',
			localCountChange: 'updatePaginationLabels'
		} );
		this.manager.getFiltersModel().connect( this, { update: 'updateReadStateSelectWidget' } );
		this.manager.getPaginationModel().connect( this, { update: 'updatePaginationLabels' } );
		this.topPaginationWidget.connect( this, { change: 'populateNotifications' } );
		this.bottomPaginationWidget.connect( this, { change: 'populateNotifications' } );
		this.settingsMenu.connect( this, { markAllRead: 'onSettingsMarkAllRead' } );

		this.topPaginationWidget.setDisabled( true );
		this.bottomPaginationWidget.setDisabled( true );

		this.$topToolbar =
			$( '<div>' )
				.addClass( 'mw-echo-ui-notificationsInboxWidget-main-toolbar-top' )
				.append(
					$( '<div>' )
						.addClass( 'mw-echo-ui-notificationsInboxWidget-row' )
						.append(
							$( '<div>' )
								.addClass( 'mw-echo-ui-notificationsInboxWidget-cell-placeholder' )
								.append(
									$( '<div>' )
										.addClass( 'mw-echo-ui-notificationsInboxWidget-main-toolbar-readState' )
										.addClass( 'mw-echo-ui-notificationsInboxWidget-cell' )
										.append( this.readStateSelectWidget.$element )
								),
							$( '<div>' )
								.addClass( 'mw-echo-ui-notificationsInboxWidget-main-toolbar-pagination' )
								.addClass( 'mw-echo-ui-notificationsInboxWidget-cell' )
								.append( this.topPaginationWidget.$element ),
							mw.user.isTemp() ? '' : $( '<div>' )
								.addClass( 'mw-echo-ui-notificationsInboxWidget-main-toolbar-settings' )
								.addClass( 'mw-echo-ui-notificationsInboxWidget-cell' )
								.append( this.settingsMenu.$element )
						)
				);

		this.$toolbarWrapper =
			$( '<div>' )
				.addClass( 'mw-echo-ui-notificationsInboxWidget-toolbarWrapper' )
				.append( this.$topToolbar );

		var $sidebar = $( '<div>' )
			.addClass( 'mw-echo-ui-notificationsInboxWidget-sidebar' )
			.append( this.xwikiUnreadWidget.$element );

		var $main = $( '<div>' )
			.addClass( 'mw-echo-ui-notificationsInboxWidget-main' )
			.append(
				this.$toolbarWrapper,
				this.noticeMessageWidget.$element,
				this.datedListWidget.$element
			);

		this.$element
			.addClass( 'mw-echo-ui-notificationsInboxWidget' )
			.append(
				$( '<div>' )
					.addClass( 'mw-echo-ui-notificationsInboxWidget-row' )
					.append(
						$sidebar
							.addClass( 'mw-echo-ui-notificationsInboxWidget-cell' ),
						$main
							.addClass( 'mw-echo-ui-notificationsInboxWidget-cell' )
					)
			);

		this.updateReadStateSelectWidget();
		this.xwikiUnreadWidget.populateSources();
		this.populateNotifications();

	};

	/* Initialization */

	OO.inheritClass( mw.echo.ui.NotificationsInboxWidget, OO.ui.Widget );
	OO.mixinClass( mw.echo.ui.NotificationsInboxWidget, OO.ui.mixin.PendingElement );

	/* Methods */

	/**
	 * Respond to filters update
	 */
	mw.echo.ui.NotificationsInboxWidget.prototype.updateReadStateSelectWidget = function () {
		this.readStateSelectWidget
			.findItemFromData( this.manager.getFiltersModel().getReadState() )
			.setSelected( true );
	};

	/**
	 * Update pagination messages
	 */
	mw.echo.ui.NotificationsInboxWidget.prototype.updatePaginationLabels = function () {
		this.resetMessageLabel();
		// Update the pagination label
		this.topPaginationWidget.updateLabel();
		this.bottomPaginationWidget.updateLabel();
	};

	/**
	 * Respond to mark all read for selected wiki
	 */
	mw.echo.ui.NotificationsInboxWidget.prototype.onSettingsMarkAllRead = function () {
		this.pushPending();
		this.controller.markAllRead()
			.always( this.popPending.bind( this ) );
	};

	/**
	 * Respond to unread page filter
	 *
	 * @param {string} source Source symbolic name
	 * @param {string} page Page name
	 */
	mw.echo.ui.NotificationsInboxWidget.prototype.onSourcePageFilter = function ( source, page ) {
		this.controller.setFilter( 'sourcePage', source, page );
		this.populateNotifications();
	};

	/**
	 * Respond to read state filter event
	 *
	 * @param {string} readState Read state 'all', 'read' or 'unread'
	 */
	mw.echo.ui.NotificationsInboxWidget.prototype.onReadStateFilter = function ( readState ) {
		this.controller.setFilter( 'readState', readState );
		this.populateNotifications();
	};

	/**
	 * Populate the notifications list
	 *
	 * @param {string} [direction] Direction to fetch from. 'prev' for previous page
	 *  or 'next' for the next page. If not given, the first page of results will be fetched.
	 * @return {jQuery.Promise} A promise that is resolved when the results
	 *  have been fetched.
	 */
	mw.echo.ui.NotificationsInboxWidget.prototype.populateNotifications = function ( direction ) {
		var widget = this;

		var fetchPromise;
		if ( direction === 'prev' ) {
			fetchPromise = this.controller.fetchPrevPageByDate();
		} else if ( direction === 'next' ) {
			fetchPromise = this.controller.fetchNextPageByDate();
		} else {
			fetchPromise = this.controller.fetchFirstPageByDate();
		}

		this.pushPending();
		this.error = false;
		return fetchPromise
			.then(
				// Success
				function () {
					// Fire initialization hook
					mw.hook( 'ext.echo.special.onInitialize' ).fire( widget.controller.manager.getTypeString(), widget.controller );

					widget.popPending();
					// Update seen time
					widget.controller.updateSeenTime();
				},
				// Failure
				function ( errObj ) {
					var msg;
					if ( errObj.errCode === 'notlogin-required' ) {
						// Login required message
						msg = mw.msg( 'echo-notification-loginrequired' );
					} else {
						// Generic API failure message
						msg = mw.msg( 'echo-api-failure' );
					}
					widget.error = true;
					widget.noticeMessageWidget.setLabel( msg );
					widget.displayMessage( true );
				}
			)
			.always( this.popPending.bind( this ) );
	};

	/**
	 * Extend the pushPending method to disable UI elements
	 */
	mw.echo.ui.NotificationsInboxWidget.prototype.pushPending = function () {
		this.noticeMessageWidget.toggle( false );
		this.topPaginationWidget.setDisabled( true );
		this.bottomPaginationWidget.setDisabled( true );

		// Mixin method
		OO.ui.mixin.PendingElement.prototype.pushPending.call( this );
	};

	/**
	 * Extend the popPending method to enable UI elements
	 */
	mw.echo.ui.NotificationsInboxWidget.prototype.popPending = function () {
		if ( !this.error ) {
			this.resetMessageLabel();
		}

		this.topPaginationWidget.setDisabled( false );
		this.bottomPaginationWidget.setDisabled( false );

		// Mixin method
		OO.ui.mixin.PendingElement.prototype.popPending.call( this );
	};

	/**
	 * Reset the text of the error message that displays in place of the list
	 * in case the list is empty.
	 */
	mw.echo.ui.NotificationsInboxWidget.prototype.resetMessageLabel = function () {
		var count = this.manager.getPaginationModel().getCurrentPageItemCount();

		if ( count === 0 ) {
			var label = this.manager.getFiltersModel().getReadState() === 'all' ?
				mw.msg( 'echo-notification-placeholder' ) :
				mw.msg( 'echo-notification-placeholder-filters' );

			this.noticeMessageWidget.setLabel( label );
		}

		this.displayMessage( count === 0 );
	};

	/**
	 * Display the error/notice message instead of the notifications list or vise versa.
	 *
	 * @private
	 * @param {boolean} displayMessage Display error message
	 */
	mw.echo.ui.NotificationsInboxWidget.prototype.displayMessage = function ( displayMessage ) {
		this.noticeMessageWidget.toggle( displayMessage );
		this.datedListWidget.toggle( !displayMessage );
	};

}() );
