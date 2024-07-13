/*!
 * VisualEditor UserInterface MWSyntaxHighlightWindow class.
 *
 * @copyright VisualEditor Team and others
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki syntax highlight window.
 *
 * @class
 * @abstract
 *
 * @constructor
 */
ve.ui.MWSyntaxHighlightWindow = function VeUiMWSyntaxHighlightWindow() {
};

/* Inheritance */

OO.initClass( ve.ui.MWSyntaxHighlightWindow );

/* Static properties */

ve.ui.MWSyntaxHighlightWindow.static.title = OO.ui.deferMsg( 'syntaxhighlight-visualeditor-mwsyntaxhighlightinspector-title' );

ve.ui.MWSyntaxHighlightWindow.static.dir = 'ltr';

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWSyntaxHighlightWindow.prototype.initialize = function () {
	var noneMsg = ve.msg( 'syntaxhighlight-visualeditor-mwsyntaxhighlightinspector-none' );

	this.languageValid = null;

	this.language = new OO.ui.ComboBoxInputWidget( {
		$overlay: this.$overlay,
		menu: {
			filterFromInput: true,
			items: ve.dm.MWSyntaxHighlightNode.static.getLanguages().map( function ( lang ) {
				return new OO.ui.MenuOptionWidget( { data: lang, label: lang || noneMsg } );
			} )
		},
		validate: function ( input ) {
			return ve.dm.MWSyntaxHighlightNode.static.isLanguageSupported( input );
		}
	} );

	this.showLinesCheckbox = new OO.ui.CheckboxInputWidget();

	this.startLineNumber = new OO.ui.NumberInputWidget( {
		min: 0,
		isInteger: true,
		showButtons: false
	} );

	// Events
	this.language.connect( this, { change: 'onLanguageInputChange' } );
	this.showLinesCheckbox.connect( this, { change: 'onShowLinesCheckboxChange' } );
	this.startLineNumber.connect( this, { change: 'onStartLineNumberChange' } );

	this.languageField = new OO.ui.FieldLayout( this.language, {
		classes: [ 've-ui-mwSyntaxHighlightWindow-languageField' ],
		align: 'top',
		label: ve.msg( 'syntaxhighlight-visualeditor-mwsyntaxhighlightinspector-language' )
	} );
	this.codeField = new OO.ui.FieldLayout( this.input, {
		align: 'top',
		label: ve.msg( 'syntaxhighlight-visualeditor-mwsyntaxhighlightinspector-code' )
	} );
	this.showLinesField = new OO.ui.FieldLayout( this.showLinesCheckbox, {
		align: 'inline',
		label: ve.msg( 'syntaxhighlight-visualeditor-mwsyntaxhighlightinspector-showlines' )
	} );
	this.startLineField = new OO.ui.FieldLayout( this.startLineNumber, {
		classes: [ 've-ui-mwSyntaxHighlightWindow-startLineField' ],
		align: 'left',
		label: ve.msg( 'syntaxhighlight-visualeditor-mwsyntaxhighlightinspector-startingline' )
	} );
};

/**
 * Handle input change events
 *
 * @param {string} value New value
 */
ve.ui.MWSyntaxHighlightWindow.prototype.onLanguageInputChange = function () {
	var inspector = this;
	var validity = this.language.getValidity();
	validity.always( function () {
		inspector.languageValid = validity.state() === 'resolved';
		inspector.updateActions();
	} );
};

/**
 * Handle change events from the show lines chechbox
 *
 * @param {boolean} value Widget value
 */
ve.ui.MWSyntaxHighlightWindow.prototype.onShowLinesCheckboxChange = function () {
	var showLines = this.showLinesCheckbox.isSelected();
	this.input.toggleLineNumbers( showLines );
	this.startLineNumber.setDisabled( !showLines );
	this.updateActions();
};

/**
 * Handle change events from the start line input
 *
 * @param {string} value Widget value
 */
ve.ui.MWSyntaxHighlightWindow.prototype.onStartLineNumberChange = function ( value ) {
	var inspector = this,
		input = this.input;

	input.loadingPromise.done( function () {
		input.editor.setOption( 'firstLineNumber', value !== '' ? +value : 1 );
	} ).always( function () {
		inspector.updateActions();
	} );
};

/**
 * @inheritdoc OO.ui.Window
 */
ve.ui.MWSyntaxHighlightWindow.prototype.getReadyProcess = function ( data, process ) {
	return process.next( function () {
		this.language.getMenu().toggle( false );
		if ( !this.language.getValue() ) {
			this.language.focus();
		} else {
			this.input.focus();
		}
	}, this );
};

/**
 * @inheritdoc OO.ui.Window
 */
ve.ui.MWSyntaxHighlightWindow.prototype.getSetupProcess = function ( data, process ) {
	return process.next( function () {
		var attrs = this.selectedNode ? this.selectedNode.getAttribute( 'mw' ).attrs : {},
			language = attrs.lang ? attrs.lang.toLowerCase() : '',
			showLines = attrs.line !== undefined,
			startLine = attrs.start,
			isReadOnly = this.isReadOnly();

		this.language.setValue( language ).setReadOnly( isReadOnly );

		this.showLinesCheckbox.setSelected( showLines ).setDisabled( isReadOnly );
		this.startLineNumber.setValue( startLine ).setReadOnly( isReadOnly );
	}, this );
};

/**
 * @inheritdoc OO.ui.Window
 */
ve.ui.MWSyntaxHighlightWindow.prototype.getTeardownProcess = function ( data, process ) {
	return process;
};

/**
 * @inheritdoc ve.ui.MWExtensionWindow
 */
ve.ui.MWSyntaxHighlightWindow.prototype.updateActions = function () {
	this.getActions().setAbilities( { done: this.languageValid && this.isModified() } );
};

/**
 * @inheritdoc ve.ui.MWExtensionWindow
 */
ve.ui.MWSyntaxHighlightWindow.prototype.updateMwData = function ( mwData ) {
	var language = this.language.getValue(),
		showLines = this.showLinesCheckbox.isSelected(),
		startLine = this.startLineNumber.getValue();

	mwData.attrs.lang = language || undefined;
	if ( !showLines ) {
		delete mwData.attrs.line;
	// Keep whatever value was there before to not cause dirty diffs
	} else if ( !( 'line' in mwData.attrs ) ) {
		mwData.attrs.line = '1';
	}
	mwData.attrs.start = startLine !== '' ? startLine : undefined;
};
