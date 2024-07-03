/*!
 * VisualEditor UserInterface EditCheckInspector class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Edit check inspector
 *
 * @class
 * @extends ve.ui.FragmentInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.EditCheckInspector = function VeUiEditCheckInspector( config ) {
	// Parent constructor
	ve.ui.EditCheckInspector.super.call( this, config );

	// Pre-initialization
	this.$element.addClass( 've-ui-editCheckInspector' );
};

/* Inheritance */

OO.inheritClass( ve.ui.EditCheckInspector, ve.ui.FragmentInspector );

ve.ui.EditCheckInspector.static.name = 'editCheckReferencesInspector';

// ve.ui.EditCheckInspector.static.title = OO.ui.deferMsg( 'editcheck-dialog-title' );
ve.ui.EditCheckInspector.static.title = OO.ui.deferMsg( 'editcheck-dialog-addref-title' );

// ve.ui.EditCheckInspector.static.size = 'context';

ve.ui.EditCheckInspector.static.actions = [
	{
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-cancel' ),
		flags: [ 'safe', 'back' ],
		modes: [ 'mobile', 'desktop' ]
	},
	{
		action: 'continue',
		icon: 'next',
		flags: [ 'primary', 'progressive' ],
		modes: [ 'mobile' ]
	}
];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.EditCheckInspector.prototype.initialize = function () {
	// Parent method
	ve.ui.EditCheckInspector.super.prototype.initialize.call( this );

	// Survey panel
	this.answerRadioSelect = new OO.ui.RadioSelectWidget( {
		items: [
			new OO.ui.RadioOptionWidget( {
				data: 'uncertain',
				label: ve.msg( 'editcheck-dialog-addref-reject-uncertain' )
			} ),
			new OO.ui.RadioOptionWidget( {
				data: 'common-knowledge',
				label: ve.msg( 'editcheck-dialog-addref-reject-common-knowledge' )
			} ),
			new OO.ui.RadioOptionWidget( {
				data: 'irrelevant',
				label: ve.msg( 'editcheck-dialog-addref-reject-irrelevant' )
			} ),
			new OO.ui.RadioOptionWidget( {
				data: 'other',
				label: ve.msg( 'editcheck-dialog-addref-reject-other' )
			} )
		]
	} );
	this.answerRadioSelect.connect( this, { select: 'updateActions' } );

	this.answerConfirm = new OO.ui.ButtonWidget( {
		flags: [ 'progressive' ],
		framed: false,
		label: ve.msg( 'ooui-dialog-process-continue' ),
		disabled: true
	} );
	this.answerConfirm.toggle( !OO.ui.isMobile() );
	this.answerConfirm.connect( this, { click: [ 'executeAction', 'continue' ] } );

	this.form.addItems(
		new OO.ui.FieldsetLayout( {
			label: ve.msg( 'editcheck-dialog-addref-reject-question' ),
			items: [
				new OO.ui.FieldLayout( this.answerRadioSelect, {
					label: ve.msg( 'editcheck-dialog-addref-reject-description' ),
					align: 'top'
				} ),
				new OO.ui.FieldLayout( this.answerConfirm, {
					align: 'left'
				} )
			]
		} )
	);
};

ve.ui.EditCheckInspector.prototype.updateActions = function () {
	this.answerConfirm.setDisabled( !this.answerRadioSelect.findSelectedItem() );
};

/**
 * @inheritdoc
 */
ve.ui.EditCheckInspector.prototype.getSetupProcess = function ( data ) {
	data = data || {};
	return ve.ui.EditCheckInspector.super.prototype.getSetupProcess.call( this, data )
		.first( function () {
			this.surface = data.surface;
			this.saveProcessDeferred = data.saveProcessDeferred;
			this.answerRadioSelect.selectItem( null );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.EditCheckInspector.prototype.getReadyProcess = function ( data ) {
	return ve.ui.EditCheckInspector.super.prototype.getReadyProcess.call( this, data )
		.first( function () {
			this.actions.setMode( OO.ui.isMobile() ? 'mobile' : 'desktop' );
		}, this );
};

ve.ui.EditCheckInspector.prototype.getActionProcess = function ( action ) {
	if ( action === '' ) {
		return new OO.ui.Process( function () {
			this.close();
		}, this );
	}

	if ( action === 'continue' ) {
		return new OO.ui.Process( function () {
			this.close( { action: 'reject', reason: this.answerRadioSelect.findSelectedItem().getData() } );
			ve.track( 'activity.editCheckReferences', { action: 'dialog-choose-' + this.answerRadioSelect.findSelectedItem().getData() } );
		}, this );
	}

	return ve.ui.EditCheckInspector.super.prototype.getActionProcess.call( this, action );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.EditCheckInspector );
