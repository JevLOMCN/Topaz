/*!
 * VisualEditor UserInterface FragmentInspector class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Inspector for working with fragments of content.
 *
 * @class
 * @extends OO.ui.ProcessDialog
 * @mixins ve.ui.FragmentWindow
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {boolean} [padded=true] Inspector form area has padding,
 *      set to false for edge-to-edge layouts, e.g. IndexLayout
 */
ve.ui.FragmentInspector = function VeUiFragmentInspector( config ) {
	config = config || {};

	// Parent constructor
	ve.ui.FragmentInspector.super.call( this, config );

	// Mixin constructor
	ve.ui.FragmentWindow.call( this, config );

	// Properties
	this.initialFragment = null;
	this.previousSelection = null;
	this.padded = config.padded !== false;
};

/* Inheritance */

OO.inheritClass( ve.ui.FragmentInspector, OO.ui.ProcessDialog );

OO.mixinClass( ve.ui.FragmentInspector, ve.ui.FragmentWindow );

/* Static Properties */

ve.ui.FragmentInspector.static.actions = [
	{
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-cancel' ),
		flags: [ 'safe', 'close' ],
		modes: [ 'readonly', 'edit', 'insert' ]
	},
	{
		action: 'done',
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-done' ),
		flags: [ 'progressive', 'primary' ],
		modes: 'edit'
	},
	{
		action: 'done',
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-insert' ),
		flags: [ 'progressive', 'primary' ],
		modes: 'insert'
	}
];

ve.ui.FragmentInspector.static.size = 'large';

/* Methods */

/**
 * Handle form submit events.
 *
 * Executes the 'done' action when the user presses enter in the form.
 */
ve.ui.FragmentInspector.prototype.onFormSubmit = function () {
	this.executeAction( 'done' );
};

/**
 * @inheritdoc
 */
ve.ui.FragmentInspector.prototype.initialize = function () {
	// Parent method
	ve.ui.FragmentInspector.super.prototype.initialize.call( this );

	// Properties
	this.container = new OO.ui.PanelLayout( {
		classes: [ 've-ui-fragmentInspector-container' ],
		scrollable: true,
		expanded: false,
		padded: this.padded
	} );
	this.form = new OO.ui.FormLayout( {
		classes: [ 've-ui-fragmentInspector-form' ]
	} );

	// Events
	this.form.connect( this, { submit: 'onFormSubmit' } );

	// Initialization
	this.$element.addClass( 've-ui-fragmentInspector' );
	this.$content.addClass( 've-ui-fragmentInspector-content' );
	this.container.$element.append( this.form.$element );
	this.$body.append( this.container.$element );

	this.tabIndexScope = new ve.ui.TabIndexScope( {
		root: this.$content
	} );
};

/**
 * @inheritdoc
 */
ve.ui.FragmentInspector.prototype.getActionProcess = function ( action ) {
	if ( action === 'done' ) {
		return new OO.ui.Process( function () {
			this.close( { action: 'done' } );
		}, this );
	}
	return ve.ui.FragmentInspector.super.prototype.getActionProcess.call( this, action );
};

/**
 * @inheritdoc OO.ui.Dialog
 */
ve.ui.FragmentInspector.prototype.getActionWidgetConfig = function ( config ) {
	// Mixin method
	config = ve.ui.FragmentWindow.prototype.getActionWidgetConfig.call( this, config );
	// Parent method
	return ve.ui.FragmentInspector.super.prototype.getActionWidgetConfig.call( this, config );
};

/**
 * @inheritdoc
 */
ve.ui.FragmentInspector.prototype.getSetupProcess = function ( data ) {
	// Parent method
	var process = ve.ui.FragmentInspector.super.prototype.getSetupProcess.call( this, data );
	// Mixin method
	return ve.ui.FragmentWindow.prototype.getSetupProcess.call( this, data, process );
};

/**
 * @inheritdoc
 */
ve.ui.FragmentInspector.prototype.getTeardownProcess = function ( data ) {
	// Parent method
	var process = ve.ui.FragmentInspector.super.prototype.getTeardownProcess.call( this, data );
	// Mixin method
	return ve.ui.FragmentWindow.prototype.getTeardownProcess.call( this, data, process );
};

/**
 * @inheritdoc
 */
ve.ui.FragmentInspector.prototype.getReadyProcess = function ( data ) {
	return ve.ui.FragmentInspector.super.prototype.getReadyProcess.call( this, data )
		// Add a 0ms timeout before doing anything. Because… Internet Explorer :(
		.first( 0 );
};

/**
 * @inheritdoc
 */
ve.ui.FragmentInspector.prototype.getBodyHeight = function () {
	return Math.ceil( this.container.$element[ 0 ].scrollHeight );
};
