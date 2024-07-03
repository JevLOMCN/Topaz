/*!
 * VisualEditor TableLineContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a table line (row or column) toolset.
 *
 * @class
 * @abstract
 * @extends ve.ui.ContextItem
 *
 * @param {ve.ui.TableLineContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Function} tool Tool class the item is based on
 * @param {Object} [config] Configuration options
 */
ve.ui.TableLineContextItem = function VeUiTableLineContextItem() {
	// Parent constructor
	ve.ui.TableLineContextItem.super.apply( this, arguments );

	this.actionButton = new OO.ui.ButtonWidget( {
		framed: false,
		classes: [ 've-ui-tableLineContextItem-actionButton' ]
	} );

	this.actionButton.connect( this, { click: 'onActionButtonClick' } );

	// Initialization
	this.$element
		.addClass( 've-ui-tableLineContextItem' )
		.append( this.actionButton.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.TableLineContextItem, ve.ui.ContextItem );

/* Static Properties */

ve.ui.TableLineContextItem.static.name = 'tableLine';

/**
 * Title to use for context item action
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.ui.TableLineContextItem.static.title = null;

/* Methods */

/**
 * Handle action button click events.
 *
 * @localdoc Executes the command related to #static-commandName on the context's surface
 *
 * @protected
 */
ve.ui.TableLineContextItem.prototype.onActionButtonClick = function () {
	var command = this.getCommand();

	if ( command ) {
		command.execute( this.context.getSurface() );
		this.emit( 'command' );
	}
};

/**
 * Get the title of the tool, used by the button label
 *
 * @return {jQuery|string|OO.ui.HtmlSnippet|Function} Tool title
 */
ve.ui.TableLineContextItem.prototype.getTitle = function () {
	return this.constructor.static.title;
};

/**
 * @inheritdoc
 */
ve.ui.TableLineContextItem.prototype.setup = function () {
	// Parent method
	ve.ui.TableLineContextItem.super.prototype.setup.call( this );

	this.actionButton
		.setIcon( this.constructor.static.icon )
		.setLabel( this.getTitle() );
};

/* Specific tools */

( function () {
	var modes = [ 'row', 'col' ],
		sides = [ 'before', 'after' ],
		modeNames = { row: 'Row', col: 'Column' },
		sideNames = { before: 'Before', after: 'After' };

	modes.forEach( function ( mode ) {
		var modeName = modeNames[ mode ];
		var className;

		sides.forEach( function ( side ) {
			var sideName = sideNames[ side ];

			className = 'Insert' + modeName + sideName + 'ContextItem';
			// The following classes are used here:
			// * ve.ui.InsertColumnBeforeContextItem
			// * ve.ui.InsertColumnAfterContextItem
			// * ve.ui.InsertRowBeforeContextItem
			// * ve.ui.InsertRowAfterContextItem
			ve.ui[ className ] = function VeUiInsertRowOrColumnContextItem() {
				ve.ui.TableLineContextItem.apply( this, arguments );
			};
			OO.inheritClass( ve.ui[ className ], ve.ui.TableLineContextItem );
			ve.ui[ className ].static.name = 'insert' + modeName + sideName;
			ve.ui[ className ].static.group = 'table-' + mode;
			ve.ui[ className ].static.icon = 'tableAdd' + modeName + sideName;
			// Messages used here:
			// * visualeditor-table-insert-col-before
			// * visualeditor-table-insert-col-after
			// * visualeditor-table-insert-row-before
			// * visualeditor-table-insert-row-after
			ve.ui[ className ].static.title =
				OO.ui.deferMsg( 'visualeditor-table-insert-' + mode + '-' + side );
			ve.ui[ className ].static.commandName = 'insert' + modeName + sideName;
			ve.ui.contextItemFactory.register( ve.ui[ className ] );

			className = 'Move' + modeName + sideName + 'ContextItem';
			// The following classes are used here:
			// * ve.ui.MoveColumnBeforeContextItem
			// * ve.ui.MoveColumnAfterContextItem
			// * ve.ui.MoveRowBeforeContextItem
			// * ve.ui.MoveRowAfterContextItem
			ve.ui[ className ] = function VeUiMoveRowOrColumnContextItem() {
				ve.ui.TableLineContextItem.apply( this, arguments );
			};
			OO.inheritClass( ve.ui[ className ], ve.ui.TableLineContextItem );
			ve.ui[ className ].static.name = 'move' + modeName + sideName;
			ve.ui[ className ].static.group = 'table-' + mode;
			ve.ui[ className ].static.icon = 'tableMove' + modeName + sideName;
			// Messages used here:
			// * visualeditor-table-move-col-before
			// * visualeditor-table-move-col-after
			// * visualeditor-table-move-row-before
			// * visualeditor-table-move-row-after
			ve.ui[ className ].static.title =
				OO.ui.deferMsg( 'visualeditor-table-move-' + mode + '-' + side );
			ve.ui[ className ].static.commandName = 'move' + modeName + sideName;
			ve.ui[ className ].prototype.setup = function () {
				// Parent method
				ve.ui.TableLineContextItem.prototype.setup.call( this );

				var selection = this.context.getSurface().getModel().getSelection();
				var documentModel = this.context.getSurface().getModel().getDocument();

				if ( !( selection instanceof ve.dm.TableSelection ) ) {
					this.actionButton.setDisabled( true );
					return;
				}

				if ( side === 'before' ) {
					this.actionButton.setDisabled(
						( mode === 'row' && selection.startRow === 0 ) ||
						( mode === 'col' && selection.startCol === 0 )
					);
				} else {
					var matrix = selection.getTableNode( documentModel ).getMatrix();
					this.actionButton.setDisabled(
						( mode === 'row' && selection.endRow === matrix.getRowCount() - 1 ) ||
						( mode === 'col' && selection.endCol === matrix.getMaxColCount() - 1 )
					);
				}
			};
			ve.ui.contextItemFactory.register( ve.ui[ className ] );
		} );

		className = 'Delete' + modeName + 'ContextItem';
		// The following classes are used here:
		// * ve.ui.DeleteColumnContextItem
		// * ve.ui.DeleteRowContextItem
		ve.ui[ className ] = function VeUiDeleteRowOrColumnContextItem() {
			ve.ui.TableLineContextItem.apply( this, arguments );

			this.actionButton.setFlags( { destructive: true } );
		};
		OO.inheritClass( ve.ui[ className ], ve.ui.TableLineContextItem );
		ve.ui[ className ].static.name = 'delete' + modeName;
		ve.ui[ className ].static.group = 'table-' + mode;
		ve.ui[ className ].static.icon = 'trash';
		ve.ui[ className ].static.commandName = 'delete' + modeName;
		ve.ui[ className ].prototype.getTitle = function () {
			var selection = this.context.getSurface().getModel().getSelection();

			var count;
			if ( !( selection instanceof ve.dm.TableSelection ) ) {
				count = 0;
			} else if ( mode === 'row' ) {
				count = selection.getRowCount();
			} else {
				count = selection.getColCount();
			}

			// The following messages are used here:
			// * visualeditor-table-delete-col
			// * visualeditor-table-delete-row
			return ve.msg( 'visualeditor-table-delete-' + mode, count );
		};
		ve.ui.contextItemFactory.register( ve.ui[ className ] );
	} );

	ve.ui.TablePropertiesContextItem = function VeUiTablePropertiesContextItem() {
		ve.ui.TableLineContextItem.apply( this, arguments );

		this.actionButton.setFlags( { progressive: true } );
	};
	OO.inheritClass( ve.ui.TablePropertiesContextItem, ve.ui.TableLineContextItem );
	ve.ui.TablePropertiesContextItem.static.name = 'tableProperties';
	ve.ui.TablePropertiesContextItem.static.group = 'table';
	ve.ui.TablePropertiesContextItem.static.icon = 'edit';
	ve.ui.TablePropertiesContextItem.static.commandName = 'table';
	ve.ui.TablePropertiesContextItem.static.title = OO.ui.deferMsg( 'visualeditor-table-contextitem-properties' );
	ve.ui.TablePropertiesContextItem.prototype.onActionButtonClick = function () {
		// Tweak the selection here:
		var command = this.context.getSurface().commandRegistry.lookup( 'exitTableCell' );
		if ( command ) {
			command.execute( this.context.getSurface() );
		}
		return ve.ui.TablePropertiesContextItem.super.prototype.onActionButtonClick.apply( this, arguments );
	};
	ve.ui.contextItemFactory.register( ve.ui.TablePropertiesContextItem );

	ve.ui.DeleteTableContextItem = function VeUiDeleteTableContextItem() {
		ve.ui.TableLineContextItem.apply( this, arguments );

		this.actionButton.setFlags( { destructive: true } );
	};
	OO.inheritClass( ve.ui.DeleteTableContextItem, ve.ui.TableLineContextItem );
	ve.ui.DeleteTableContextItem.static.name = 'deleteTable';
	ve.ui.DeleteTableContextItem.static.group = 'table';
	ve.ui.DeleteTableContextItem.static.icon = 'trash';
	ve.ui.DeleteTableContextItem.static.commandName = 'deleteTable';
	ve.ui.DeleteTableContextItem.static.title = OO.ui.deferMsg( 'visualeditor-contextitemwidget-label-remove' );
	ve.ui.contextItemFactory.register( ve.ui.DeleteTableContextItem );

	ve.ui.ToggleTableSelectionContextItem = function VeUiToggleTableSelectionContextItem() {
		ve.ui.TableLineContextItem.apply( this, arguments );

		this.actionButton.setFlags( { progressive: true } );
	};
	OO.inheritClass( ve.ui.ToggleTableSelectionContextItem, ve.ui.TableLineContextItem );
	ve.ui.ToggleTableSelectionContextItem.static.name = 'toggleTableEditing';
	ve.ui.ToggleTableSelectionContextItem.static.group = 'table';
	ve.ui.ToggleTableSelectionContextItem.static.icon = 'table';
	ve.ui.ToggleTableSelectionContextItem.prototype.getCommand = function () {
		var commandName = this.context.wasEditing ? 'exitTableCell' : 'enterTableCell';
		return this.context.getSurface().commandRegistry.lookup( commandName );
	};
	ve.ui.ToggleTableSelectionContextItem.prototype.getTitle = function () {
		var mode = 'cells',
			selection = this.context.getSurface().getModel().getSelection();
		if ( selection instanceof ve.dm.TableSelection ) {
			mode = 'contents';
		}
		// The following messages are used here:
		// * visualeditor-table-contextitem-selectionmode-cells
		// * visualeditor-table-contextitem-selectionmode-contents
		return ve.msg( 'visualeditor-table-contextitem-selectionmode-' + mode );
	};
	ve.ui.contextItemFactory.register( ve.ui.ToggleTableSelectionContextItem );

}() );
