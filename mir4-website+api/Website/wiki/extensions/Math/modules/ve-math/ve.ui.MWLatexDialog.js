/*!
 * VisualEditor user interface MWLatexDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * Abstract dialog for inserting and editing different formulas
 * provided by the Math extension.
 *
 * @abstract
 * @class
 * @extends ve.ui.MWExtensionPreviewDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLatexDialog = function VeUiMWLatexDialog( config ) {
	// Parent constructor
	ve.ui.MWLatexDialog.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLatexDialog, ve.ui.MWExtensionPreviewDialog );

/* Static properties */

ve.ui.MWLatexDialog.static.size = 'larger';

ve.ui.MWLatexDialog.static.dir = 'ltr';

ve.ui.MWLatexDialog.static.symbolsModule = null;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWLatexDialog.prototype.initialize = function () {
	const dialog = this;

	// Parent method
	ve.ui.MWLatexDialog.super.prototype.initialize.call( this );

	// Layout for the formula inserter (formula tab panel) and options form (options tab panel)
	this.indexLayout = new OO.ui.IndexLayout();

	const formulaTabPanel = new OO.ui.TabPanelLayout( 'formula', {
		label: ve.msg( 'math-visualeditor-mwlatexdialog-card-formula' ),
		padded: true,
		classes: [ 'latex-dialog-formula-panel' ]
	} );
	const optionsTabPanel = new OO.ui.TabPanelLayout( 'options', {
		label: ve.msg( 'math-visualeditor-mwlatexdialog-card-options' ),
		padded: true,
		classes: [ 'latex-dialog-options-panel' ]
	} );

	this.indexLayout.addTabPanels( [
		formulaTabPanel,
		optionsTabPanel
	] );

	// Layout for symbol picker (menu) and input and preview (content)
	this.menuLayout = new OO.ui.MenuLayout( {
		menuPosition: 'bottom',
		classes: [ 've-ui-mwLatexDialog-menuLayout' ]
	} );

	this.previewElement.$element.addClass(
		've-ui-mwLatexDialog-preview'
	);

	this.input = new ve.ui.MWAceEditorWidget( {
		rows: 1, // This will be recalculated later in onWindowManagerResize
		autocomplete: 'live',
		autocompleteWordList: this.constructor.static.autocompleteWordList
	} ).setLanguage( 'latex' );

	this.input.togglePrintMargin( false );

	this.displaySelect = new OO.ui.ButtonSelectWidget( {
		items: [
			new OO.ui.ButtonOptionWidget( {
				data: 'default',
				icon: 'mathematicsDisplayDefault',
				label: ve.msg( 'math-visualeditor-mwlatexinspector-display-default' )
			} ),
			new OO.ui.ButtonOptionWidget( {
				data: 'inline',
				icon: 'mathematicsDisplayInline',
				label: ve.msg( 'math-visualeditor-mwlatexinspector-display-inline' )
			} ),
			new OO.ui.ButtonOptionWidget( {
				data: 'block',
				icon: 'mathematicsDisplayBlock',
				label: ve.msg( 'math-visualeditor-mwlatexinspector-display-block' )
			} )
		]
	} );

	this.idInput = new OO.ui.TextInputWidget();
	this.qidInput = new mw.widgets.MathWbEntitySelector();

	const inputField = new OO.ui.FieldLayout( this.input, {
		align: 'top',
		classes: [ 'latex-dialog-formula-field' ],
		label: ve.msg( 'math-visualeditor-mwlatexdialog-card-formula' )
	} );
	const displayField = new OO.ui.FieldLayout( this.displaySelect, {
		align: 'top',
		classes: [ 'latex-dialog-display-field' ],
		label: ve.msg( 'math-visualeditor-mwlatexinspector-display' )
	} );
	const idField = new OO.ui.FieldLayout( this.idInput, {
		align: 'top',
		classes: [ 'latex-dialog-id-field' ],
		label: ve.msg( 'math-visualeditor-mwlatexinspector-id' )
	} );
	const qidField = new OO.ui.FieldLayout( this.qidInput, {
		align: 'top',
		classes: [ 'latex-dialog-qid-field' ],
		label: ve.msg( 'math-visualeditor-mwlatexinspector-qid' )
	} );

	const formulaPanel = new OO.ui.PanelLayout( {
		scrollable: true,
		padded: true
	} );

	// Layout for the symbol picker
	this.bookletLayout = new ve.ui.SymbolListBookletLayout( {
		classes: [ 've-ui-mwLatexDialog-symbols' ]
	} );
	this.pages = [];
	this.symbolsPromise = mw.loader.using( this.constructor.static.symbolsModule ).done( function ( require ) {
		// eslint-disable-next-line security/detect-non-literal-require
		const symbols = require( dialog.constructor.static.symbolsModule );
		const symbolData = {};
		for ( const category in symbols ) {
			const symbolList = symbols[ category ].filter( function ( symbol ) {
				if ( symbol.notWorking || symbol.duplicate ) {
					return false;
				}
				const tex = symbol.tex || symbol.insert;
				const classes = [ 've-ui-mwLatexDialog-symbol' ];
				classes.push(
					've-ui-mwLatexSymbol-' + tex.replace( /[^\w]/g, function ( c ) {
						return '_' + c.charCodeAt( 0 ) + '_';
					} )
				);
				if ( symbol.width ) {
					// The following classes are used here:
					// * ve-ui-mwLatexDialog-symbol-wide
					// * ve-ui-mwLatexDialog-symbol-wider
					// * ve-ui-mwLatexDialog-symbol-widest
					classes.push( 've-ui-mwLatexDialog-symbol-' + symbol.width );
				}
				if ( symbol.contain ) {
					classes.push( 've-ui-mwLatexDialog-symbol-contain' );
				}
				if ( symbol.largeLayout ) {
					classes.push( 've-ui-mwLatexDialog-symbol-largeLayout' );
				}
				symbol.label = '';
				symbol.classes = classes;

				return true;
			} );
			symbolData[ category ] = {
				// eslint-disable-next-line mediawiki/msg-doc
				label: ve.msg( category ),
				symbols: symbolList
			};
		}
		dialog.bookletLayout.setSymbolData( symbolData );
		dialog.bookletLayout.connect( dialog, {
			choose: 'onSymbolChoose'
		} );

		// Append everything
		formulaPanel.$element.append(
			dialog.previewElement.$element,
			inputField.$element
		);
		dialog.menuLayout.setMenuPanel( dialog.bookletLayout );
		dialog.menuLayout.setContentPanel( formulaPanel );

		formulaTabPanel.$element.append(
			dialog.menuLayout.$element
		);
		optionsTabPanel.$element.append(
			displayField.$element,
			idField.$element,
			qidField.$element
		);

		dialog.$body
			.addClass( 've-ui-mwLatexDialog-content' )
			.append( dialog.indexLayout.$element );
	} );

};

/**
 * @inheritdoc
 */
ve.ui.MWLatexDialog.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWLatexDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			const attributes = this.selectedNode && this.selectedNode.getAttribute( 'mw' ).attrs,
				display = attributes && attributes.display || 'default',
				id = attributes && attributes.id || '',
				qid = attributes && attributes.qid || '',
				isReadOnly = this.isReadOnly();

			// Populate form
			// TODO: This widget is not readable when disabled
			this.displaySelect.selectItemByData( display ).setDisabled( isReadOnly );
			this.idInput.setValue( id ).setReadOnly( isReadOnly );
			this.qidInput.setValue( qid ).setReadOnly( isReadOnly );

			// Add event handlers
			this.input.on( 'change', this.onChangeHandler );
			this.displaySelect.on( 'choose', this.onChangeHandler );
			this.idInput.on( 'change', this.onChangeHandler );
			this.qidInput.on( 'change', this.onChangeHandler );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWLatexDialog.prototype.getReadyProcess = function ( data ) {
	mw.hook( 've.ui.MwLatexDialogReadyProcess' ).fire();
	return ve.ui.MWLatexDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			return this.symbolsPromise;
		}, this )
		.next( function () {
			// Resize the input once the dialog has been appended
			this.input.adjustSize( true ).focus().moveCursorToEnd();
			this.getManager().connect( this, { resize: 'onWindowManagerResize' } );
			this.onWindowManagerResize();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWLatexDialog.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWLatexDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			this.input.off( 'change', this.onChangeHandler );
			this.displaySelect.off( 'choose', this.onChangeHandler );
			this.idInput.off( 'change', this.onChangeHandler );
			this.qidInput.off( 'change', this.onChangeHandler );
			this.getManager().disconnect( this );
			this.indexLayout.setTabPanel( 'formula' );
			this.indexLayout.resetScroll();
			this.menuLayout.resetScroll();
			this.bookletLayout.resetScroll();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWLatexDialog.prototype.updateMwData = function ( mwData ) {
	// Parent method
	ve.ui.MWLatexDialog.super.prototype.updateMwData.call( this, mwData );

	// Get data from dialog
	const display = this.displaySelect.findSelectedItem().getData();
	const id = this.idInput.getValue();
	const qid = this.qidInput.getValue();

	// Update attributes
	mwData.attrs.display = display !== 'default' ? display : undefined;
	mwData.attrs.id = id || undefined;
	mwData.attrs.qid = qid || undefined;
};

/**
 * @inheritdoc
 */
ve.ui.MWLatexDialog.prototype.getBodyHeight = function () {
	return 600;
};

/**
 * Handle the window resize event
 */
ve.ui.MWLatexDialog.prototype.onWindowManagerResize = function () {
	const dialog = this;
	this.input.loadingPromise.always( function () {
		// Toggle short mode as necessary
		// NB a change of mode triggers a transition...
		dialog.menuLayout.$element.toggleClass(
			've-ui-mwLatexDialog-menuLayout-short', dialog.menuLayout.$element.height() < 450
		);

		// ...So wait for the possible menuLayout transition to finish
		setTimeout( function () {
			// Give the input the right number of rows to fit the space
			const availableSpace = dialog.menuLayout.$content.height() - dialog.input.$element.position().top;
			// TODO: Compute this line height from the skin
			const singleLineHeight = 21;
			const border = 1;
			const padding = 3;
			const borderAndPadding = 2 * ( border + padding );
			const maxInputHeight = availableSpace - borderAndPadding;
			const minRows = Math.floor( maxInputHeight / singleLineHeight );
			dialog.input.loadingPromise.done( function () {
				dialog.input.setMinRows( minRows );
			} ).fail( function () {
				dialog.input.$input.attr( 'rows', minRows );
			} );
		}, OO.ui.theme.getDialogTransitionDuration() );
	} );
};

/**
 * Handle a symbol being chosen from the list
 *
 * @param {Object} symbol
 */
ve.ui.MWLatexDialog.prototype.onSymbolChoose = function ( symbol ) {
	if ( this.isReadOnly() ) {
		return;
	}

	const encapsulate = symbol.encapsulate;

	if ( encapsulate ) {
		const range = this.input.getRange();
		if ( range.from === range.to ) {
			this.input.insertContent( encapsulate.placeholder );
			this.input.selectRange( range.from, range.from + encapsulate.placeholder.length );
		}
		this.input.encapsulateContent( encapsulate.pre, encapsulate.post );
	} else {
		const insert = symbol.insert;
		this.input.insertContent( insert );
	}
};
