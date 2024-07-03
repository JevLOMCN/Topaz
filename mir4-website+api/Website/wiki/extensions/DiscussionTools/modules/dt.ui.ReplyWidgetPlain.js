var utils = require( 'ext.discussionTools.init' ).utils;

/**
 * DiscussionTools ReplyWidgetPlain class
 *
 * @class mw.dt.ReplyWidgetPlain
 * @extends mw.dt.ReplyWidget
 * @constructor
 * @param {CommentController} commentController
 * @param {CommentDetails} commentDetails
 * @param {Object} [config]
 */
function ReplyWidgetPlain() {
	var widget = this;

	// Parent constructor
	ReplyWidgetPlain.super.apply( this, arguments );

	if ( OO.ui.isMobile() ) {
		var toolFactory = new OO.ui.ToolFactory(),
			toolGroupFactory = new OO.ui.ToolGroupFactory();

		toolFactory.register( mw.libs.ve.MWEditModeVisualTool );
		toolFactory.register( mw.libs.ve.MWEditModeSourceTool );
		this.switchToolbar = new OO.ui.Toolbar( toolFactory, toolGroupFactory, {
			classes: [ 'ext-discussiontools-ui-replyWidget-editSwitch' ]
		} );

		this.switchToolbar.on( 'switchEditor', function ( mode ) {
			widget.switch( mode );
		} );

		this.switchToolbar.setup( [ {
			name: 'editMode',
			type: 'list',
			icon: 'edit',
			title: mw.msg( 'visualeditor-mweditmode-tooltip' ),
			label: mw.msg( 'visualeditor-mweditmode-tooltip' ),
			invisibleLabel: true,
			include: [ 'editModeVisual', 'editModeSource' ]
		} ] );

		this.switchToolbar.emit( 'updateState' );

		this.$headerWrapper.append( this.switchToolbar.$element );
	}

	this.$element.addClass( 'ext-discussiontools-ui-replyWidget-plain' );
}

/* Inheritance */

OO.inheritClass( ReplyWidgetPlain, require( './dt.ui.ReplyWidget.js' ) );

/* Methods */

/**
 * @inheritdoc
 */
ReplyWidgetPlain.prototype.createReplyBodyWidget = function ( config ) {
	var textInput = new OO.ui.MultilineTextInputWidget( $.extend( {
		rows: 3,
		// TODO: Fix upstream to support a value meaning no max limit (e.g. Infinity)
		maxRows: 999,
		autosize: true,
		// The following classes are used here:
		// * mw-editfont-monospace
		// * mw-editfont-sans-serif
		// * mw-editfont-serif
		classes: [ 'mw-editfont-' + mw.user.options.get( 'editfont' ) ]
	}, config ) );
	textInput.$input.attr( 'aria-label', config.placeholder );
	// Fix jquery.ime position (T255191)
	textInput.$input.addClass( 'ime-position-inside' );

	return textInput;
};

/**
 * @inheritdoc
 */
ReplyWidgetPlain.prototype.focus = function () {
	this.replyBodyWidget.focus();

	return this;
};

/**
 * @inheritdoc
 */
ReplyWidgetPlain.prototype.clear = function ( preserveStorage ) {
	this.replyBodyWidget.setValue( '' );

	if ( !preserveStorage ) {
		this.storage.remove( 'body' );
	}

	// Parent method
	ReplyWidgetPlain.super.prototype.clear.apply( this, arguments );
};

/**
 * @inheritdoc
 */
ReplyWidgetPlain.prototype.isEmpty = function () {
	return utils.htmlTrim( this.replyBodyWidget.getValue() ) === '';
};

/**
 * @inheritdoc
 */
ReplyWidgetPlain.prototype.getMode = function () {
	return 'source';
};

/**
 * @inheritdoc
 */
ReplyWidgetPlain.prototype.onInputChange = function () {
	if ( this.isTornDown ) {
		// Ignore calls after teardown, which would clear the auto-save or crash
		return;
	}

	// Parent method
	ReplyWidgetPlain.super.prototype.onInputChange.apply( this, arguments );

	var wikitext = this.getValue();
	this.storage.set( 'body', wikitext );
};

/**
 * @inheritdoc
 */
ReplyWidgetPlain.prototype.setup = function ( data ) {
	var autosaveValue = this.storage.get( 'body' );

	data = data || {};

	// Parent method
	ReplyWidgetPlain.super.prototype.setup.apply( this, arguments );

	// Events
	this.replyBodyWidget.connect( this, { change: this.onInputChangeThrottled } );
	this.replyBodyWidget.$input.on( 'focus', this.emit.bind( this, 'bodyFocus' ) );

	this.replyBodyWidget.setValue( data.value || autosaveValue );

	// needs to bind after the initial setValue:
	this.replyBodyWidget.once( 'change', this.onFirstChange.bind( this ) );

	this.afterSetup();

	return this;
};

/**
 * @inheritdoc
 */
ReplyWidgetPlain.prototype.teardown = function () {
	this.replyBodyWidget.disconnect( this );
	this.replyBodyWidget.off( 'change' );

	// Parent method
	return ReplyWidgetPlain.super.prototype.teardown.apply( this, arguments );
};

/**
 * @inheritdoc
 */
ReplyWidgetPlain.prototype.getValue = function () {
	return this.replyBodyWidget.getValue();
};

module.exports = ReplyWidgetPlain;
