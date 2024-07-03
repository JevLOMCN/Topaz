( function () {
	/**
	 * A notifications list organized and separated by dates
	 *
	 * @class
	 * @extends OO.ui.Widget
	 * @mixins OO.ui.mixin.PendingElement
	 *
	 * @constructor
	 * @param {mw.echo.Controller} controller Echo controller
	 * @param {mw.echo.dm.ModelManager} modelManager Model manager
	 * @param {Object} [config] Configuration object
	 * @cfg {boolean} [animateSorting=false] Animate the sorting of items
	 * @cfg {jQuery} [$overlay] An overlay for the popup menus
	 */
	mw.echo.ui.DatedNotificationsWidget = function MwEchoUiDatedNotificationsListWidget( controller, modelManager, config ) {
		config = config || {};

		// Parent constructor
		mw.echo.ui.DatedNotificationsWidget.super.call( this, config );
		// Mixin constructors
		OO.ui.mixin.PendingElement.call( this, config );

		this.manager = modelManager;
		this.controller = controller;
		this.models = {};

		this.$overlay = config.$overlay || this.$element;
		this.animateSorting = !!config.animateSorting;

		this.listWidget = new mw.echo.ui.SortedListWidget(
			// Sorting callback
			function ( a, b ) {
				// Reverse sorting
				if ( b.getTimestamp() < a.getTimestamp() ) {
					return -1;
				} else if ( b.getTimestamp() > a.getTimestamp() ) {
					return 1;
				}
			},
			// Config
			{
				classes: [ 'mw-echo-ui-datedNotificationsWidget-group' ],
				$overlay: this.$overlay,
				animated: false
			}
		);

		// Events
		this.manager.connect( this, {
			update: 'populateFromModel',
			discard: 'onManagerDiscardModel'
		} );

		this.$element
			.addClass( 'mw-echo-ui-datedNotificationsWidget' )
			.append( this.listWidget.$element );

		// Initialization
		this.populateFromModel();
	};
	/* Initialization */

	OO.inheritClass( mw.echo.ui.DatedNotificationsWidget, OO.ui.Widget );
	OO.mixinClass( mw.echo.ui.DatedNotificationsWidget, OO.ui.mixin.PendingElement );

	mw.echo.ui.DatedNotificationsWidget.prototype.onManagerDiscardModel = function ( modelId ) {
		var model = this.models[ modelId ],
			list = this.getList();

		if ( model ) {
			var group = list.getItemFromId( model.getName() );
			list.removeItems( [ group ] );
		}
	};
	/**
	 * Respond to model removing source group
	 *
	 * @param {string} source Symbolic name of the source group
	 */
	mw.echo.ui.DatedNotificationsWidget.prototype.onModelRemoveSource = function ( source ) {
		var list = this.getList(),
			group = list.getItemFromId( source );

		list.removeItems( [ group ] );
	};

	/**
	 * Respond to model manager update event.
	 * This event means we are repopulating the entire list and the
	 * associated models within it.
	 *
	 * @param {Object} models List models, indexed by ID
	 */
	mw.echo.ui.DatedNotificationsWidget.prototype.populateFromModel = function ( models ) {
		var groupWidgets = [];

		// Detach all attached models
		for ( var modelId in this.models ) {
			this.detachModel( modelId );
		}

		for ( var model in models ) {
			// Create SubGroup widgets
			var subgroupWidget = new mw.echo.ui.DatedSubGroupListWidget(
				this.controller,
				models[ model ],
				{
					showTitle: true,
					showMarkAllRead: true,
					$overlay: this.$overlay,
					animated: this.animateSorting
				}
			);
			this.attachModel( model, models[ model ] );

			subgroupWidget.resetItemsFromModel();
			groupWidgets.push( subgroupWidget );
		}

		this.getList().getItems().forEach( function ( widget ) {
			// Destroy all available widgets
			widget.destroy();
		} );

		// Reset the list and re-add the items
		this.getList().clearItems();
		this.getList().addItems( groupWidgets );
	};

	/**
	 * Attach a model to the widget
	 *
	 * @param {string} modelId Symbolic name for the model
	 * @param {mw.echo.dm.SortedList} model Notifications list model
	 */
	mw.echo.ui.DatedNotificationsWidget.prototype.attachModel = function ( modelId, model ) {
		this.models[ modelId ] = model;
	};

	/**
	 * Detach a model from the widget
	 *
	 * @param {string} modelId Notifications list model
	 */
	mw.echo.ui.DatedNotificationsWidget.prototype.detachModel = function ( modelId ) {
		this.models[ modelId ].disconnect( this );
		delete this.models[ modelId ];
	};

	/**
	 * Get the list widget contained in this item
	 *
	 * @return {mw.echo.ui.SortedListWidget} List widget
	 */
	mw.echo.ui.DatedNotificationsWidget.prototype.getList = function () {
		return this.listWidget;
	};

	/**
	 * Get the number of all notifications in all sections of the widget
	 *
	 * @return {number} The number of all notifications
	 */
	mw.echo.ui.DatedNotificationsWidget.prototype.getAllNotificationCount = function () {
		var count = 0,
			groups = this.getList().getItems();

		for ( var i = 0; i < groups.length; i++ ) {
			count += groups[ i ].getListWidget().getItemCount();
		}

		return count;
	};

}() );
