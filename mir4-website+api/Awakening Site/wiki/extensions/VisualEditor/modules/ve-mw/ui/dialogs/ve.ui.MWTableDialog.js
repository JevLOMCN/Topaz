/*!
 * VisualEditor UserInterface MWTableDialog class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Dialog for table properties.
 *
 * @class
 * @extends ve.ui.TableDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWTableDialog = function VeUiMWTableDialog( config ) {
	// Parent constructor
	ve.ui.MWTableDialog.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWTableDialog, ve.ui.TableDialog );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWTableDialog.prototype.getValues = function () {
	// Parent method
	var values = ve.ui.MWTableDialog.super.prototype.getValues.call( this );
	return ve.extendObject( values, {
		wikitable: this.wikitableToggle.getValue(),
		sortable: this.sortableToggle.getValue(),
		collapsible: this.collapsibleToggle.getValue(),
		collapsed: this.collapsedToggle.getValue()
	} );
};

/**
 * @inheritdoc
 */
ve.ui.MWTableDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.MWTableDialog.super.prototype.initialize.call( this );

	this.wikitableToggle = new OO.ui.ToggleSwitchWidget();
	var wikitableField = new OO.ui.FieldLayout( this.wikitableToggle, {
		align: 'left',
		label: ve.msg( 'visualeditor-dialog-table-wikitable' )
	} );

	this.sortableToggle = new OO.ui.ToggleSwitchWidget();
	var sortableField = new OO.ui.FieldLayout( this.sortableToggle, {
		align: 'left',
		label: ve.msg( 'visualeditor-dialog-table-sortable' )
	} );

	this.collapsibleToggle = new OO.ui.ToggleSwitchWidget();
	var collapsibleField = new OO.ui.FieldLayout( this.collapsibleToggle, {
		align: 'left',
		label: ve.msg( 'visualeditor-dialog-table-collapsible' )
	} );

	this.collapsedToggle = new OO.ui.ToggleSwitchWidget();
	var collapsedField = new OO.ui.FieldLayout( this.collapsedToggle, {
		align: 'left',
		label: ve.msg( 'visualeditor-dialog-table-collapsed' )
	} );

	this.wikitableToggle.connect( this, { change: 'updateActions' } );
	this.sortableToggle.connect( this, { change: 'updateActions' } );
	this.collapsibleToggle.connect( this, { change: 'onCollapsibleChange' } );
	this.collapsedToggle.connect( this, { change: 'updateActions' } );

	this.panel.$element.append( wikitableField.$element, sortableField.$element, collapsibleField.$element, collapsedField.$element );
};

/**
 * @inheritdoc
 */
ve.ui.MWTableDialog.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWTableDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var tableNode = this.getFragment().getSelection().getTableNode(
					this.getFragment().getDocument()
				),
				wikitable = !!tableNode.getAttribute( 'wikitable' ),
				sortable = !!tableNode.getAttribute( 'sortable' ),
				collapsible = !!tableNode.getAttribute( 'collapsible' ),
				collapsed = !!tableNode.getAttribute( 'collapsed' ),
				hasExpandedAttrs = !!tableNode.getAttribute( 'hasExpandedAttrs' ),
				isReadOnly = this.isReadOnly();

			// These toggles are disabled if hasExpandedAttrs, but the inherited "Caption"
			// toggle will still work, as it isn't a real table node property.
			// TODO: Show a message explaining why these toggles are disabled.
			this.wikitableToggle.setValue( wikitable ).setDisabled( isReadOnly || hasExpandedAttrs );
			this.sortableToggle.setValue( sortable ).setDisabled( isReadOnly || hasExpandedAttrs );
			this.collapsibleToggle.setValue( collapsible ).setDisabled( isReadOnly || hasExpandedAttrs );
			this.collapsedToggle.setValue( collapsed ).setDisabled( isReadOnly || hasExpandedAttrs );

			ve.extendObject( this.initialValues, {
				wikitable: wikitable,
				sortable: sortable,
				collapsible: collapsible,
				collapsed: collapsed
			} );

			this.onCollapsibleChange( collapsible );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWTableDialog.prototype.getActionProcess = function ( action ) {
	return ve.ui.MWTableDialog.super.prototype.getActionProcess.call( this, action )
		.next( function () {
			if ( action === 'done' ) {
				var surfaceModel = this.getFragment().getSurface();
				var fragment = surfaceModel.getLinearFragment( this.getFragment().getSelection().tableRange, true );
				fragment.changeAttributes( {
					wikitable: this.wikitableToggle.getValue(),
					sortable: this.sortableToggle.getValue(),
					collapsible: this.collapsibleToggle.getValue(),
					collapsed: this.collapsedToggle.getValue()
				} );
			}
		}, this );
};

/**
 * Handle change events from the collapsible toggle
 *
 * @param {boolean} collapsible New toggle value
 */
ve.ui.MWTableDialog.prototype.onCollapsibleChange = function ( collapsible ) {
	this.collapsedToggle.setDisabled( !collapsible );
	if ( !collapsible ) {
		this.collapsedToggle.setValue( false );
	}
	this.updateActions();
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWTableDialog );
