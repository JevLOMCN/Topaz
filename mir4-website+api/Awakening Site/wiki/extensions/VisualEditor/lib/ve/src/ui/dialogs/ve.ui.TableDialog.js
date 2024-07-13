/*!
 * VisualEditor UserInterface TableDialog class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Dialog for table properties.
 *
 * @class
 * @extends ve.ui.FragmentDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.TableDialog = function VeUiTableDialog( config ) {
	// Parent constructor
	ve.ui.TableDialog.super.call( this, config );

	this.$element.addClass( 've-ui-tableDialog' );
};

/* Inheritance */

OO.inheritClass( ve.ui.TableDialog, ve.ui.FragmentDialog );

/* Static Properties */

ve.ui.TableDialog.static.name = 'table';

ve.ui.TableDialog.static.size = 'medium';

ve.ui.TableDialog.static.title = OO.ui.deferMsg( 'visualeditor-dialog-table-title' );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.TableDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.TableDialog.super.prototype.initialize.call( this );

	this.initialValues = null;

	this.panel = new OO.ui.PanelLayout( {
		padded: true,
		expanded: false,
		classes: [ 've-ui-tableDialog-panel' ]
	} );

	this.captionToggle = new OO.ui.ToggleSwitchWidget();
	this.captionField = new OO.ui.FieldLayout( this.captionToggle, {
		align: 'left',
		label: ve.msg( 'visualeditor-dialog-table-caption' )
	} );

	this.captionToggle.connect( this, { change: 'updateActions' } );

	this.panel.$element.append( this.captionField.$element );

	this.$body.append( this.panel.$element );
};

/**
 * Update the 'done' action according to whether there are changes
 */
ve.ui.TableDialog.prototype.updateActions = function () {
	this.actions.setAbilities( { done: !ve.compare( this.getValues(), this.initialValues ) } );
};

/**
 * Get object describing current form values.
 *
 * To be compared against this.initialValues
 *
 * @return {Object} Current form values
 */
ve.ui.TableDialog.prototype.getValues = function () {
	return {
		caption: this.captionToggle.getValue()
	};
};

/**
 * @inheritdoc
 */
ve.ui.TableDialog.prototype.getSetupProcess = function ( data ) {
	return ve.ui.TableDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var isReadOnly = this.isReadOnly();
			this.initialValues = {
				caption: !!this.getFragment().getSelection().getTableNode(
					this.getFragment().getDocument()
				).getCaptionNode()
			};
			this.captionToggle.setValue( this.initialValues.caption ).setDisabled( isReadOnly );
			this.closingFragment = null;
			this.updateActions();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.TableDialog.prototype.getActionProcess = function ( action ) {
	return ve.ui.TableDialog.super.prototype.getActionProcess.call( this, action )
		.next( function () {
			if ( action === 'done' ) {
				var surfaceModel = this.getFragment().getSurface();
				var selection = surfaceModel.getSelection();
				var captionNode = this.getFragment().getSelection().getTableNode(
					this.getFragment().getDocument()
				).getCaptionNode();
				if ( this.captionToggle.getValue() !== this.initialValues.caption ) {
					var fragment;
					if ( this.initialValues.caption ) {
						fragment = surfaceModel.getLinearFragment( captionNode.getOuterRange(), true );
						fragment.removeContent();
					} else {
						fragment = surfaceModel.getLinearFragment( new ve.Range( selection.tableRange.start + 1 ), true );

						fragment.insertContent( [
							{ type: 'tableCaption' },
							{ type: 'paragraph', internal: { generated: 'wrapper' } },
							{ type: '/paragraph' },
							{ type: '/tableCaption' }
						], false );
						// Don't change this.fragment immediately, wait until teardown process, as child
						// dialogs my want access to the original fragment
						this.closingFragment = fragment.collapseToStart().adjustLinearSelection( 2, 2 );
					}
				}
				this.close( { action: 'done' } );
			}
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.TableDialog.prototype.getTeardownProcess = function ( action ) {
	return ve.ui.TableDialog.super.prototype.getTeardownProcess.call( this, action )
		.first( function () {
			this.fragment = this.closingFragment || this.fragment;
		}, this );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.TableDialog );
