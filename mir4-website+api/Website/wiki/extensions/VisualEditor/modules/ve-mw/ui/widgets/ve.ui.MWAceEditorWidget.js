/*!
 * VisualEditor UserInterface MWAceEditorWidget class.
 *
 * @copyright See AUTHORS.txt
 */

/* global ace */

/**
 * Text input widget which use an Ace editor instance when available
 *
 * For the most part this can be treated just like a TextInputWidget with
 * a few extra considerations:
 *
 * - For performance it is recommended to destroy the editor when
 *   you are finished with it, using #teardown. If you need to use
 *   the widget again let the editor can be restored with #setup.
 * - After setting an initial value the undo stack can be reset
 *   using clearUndoStack so that you can't undo past the initial
 *   state.
 *
 * @class
 * @extends ve.ui.WhitespacePreservingTextInputWidget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {string} [autocomplete='none'] Symbolic name of autocomplete
 * mode: 'none', 'basic' (requires the user to press Ctrl-Space) or
 * 'live' (shows a list of suggestions as the user types)
 * @cfg {Array} [autocompleteWordList=null] List of words to
 * autocomplete to
 */
ve.ui.MWAceEditorWidget = function VeUiMWAceEditorWidget( config ) {
	// Configuration
	config = config || {};

	this.autocomplete = config.autocomplete || 'none';
	this.autocompleteWordList = config.autocompleteWordList || null;

	this.$ace = $( '<div>' ).attr( 'dir', 'ltr' );
	this.editor = null;
	// Initialise to a rejected promise for the setValue call in the parent constructor
	this.loadingPromise = ve.createDeferred().reject().promise();
	this.styleHeight = null;

	// Parent constructor
	ve.ui.MWAceEditorWidget.super.call( this, config );

	// Clear the fake loading promise and setup properly
	this.loadingPromise = null;
	this.setup();

	this.$element
		.append( this.$ace )
		.addClass( 've-ui-mwAceEditorWidget' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWAceEditorWidget, ve.ui.WhitespacePreservingTextInputWidget );

/* Events */

/**
 * The editor has resized
 *
 * @event resize
 */

/* Methods */

/**
 * Setup the Ace editor instance
 */
ve.ui.MWAceEditorWidget.prototype.setup = function () {
	if ( !this.loadingPromise ) {
		this.loadingPromise = mw.loader.getState( 'ext.codeEditor.ace' ) ?
			mw.loader.using( 'ext.codeEditor.ace' ) :
			ve.createDeferred().reject().promise();
		// Resolved promises will run synchronously, so ensure #setupEditor
		// runs after this.loadingPromise is stored.
		this.loadingPromise.done( this.setupEditor.bind( this ) );
	}
};

/**
 * Destroy the Ace editor instance
 */
ve.ui.MWAceEditorWidget.prototype.teardown = function () {
	var widget = this;
	this.loadingPromise.done( function () {
		widget.$input.removeClass( 'oo-ui-element-hidden' );
		widget.editor.destroy();
		widget.editor = null;
	} ).always( function () {
		widget.loadingPromise = null;
	} );
};

/**
 * Setup the Ace editor
 *
 * @fires resize
 */
ve.ui.MWAceEditorWidget.prototype.setupEditor = function () {
	var widget = this;
	var basePath = mw.config.get( 'wgExtensionAssetsPath', '' );

	if ( basePath.slice( 0, 2 ) === '//' ) {
		// ACE uses web workers, which have importScripts, which don't like relative links.
		basePath = window.location.protocol + basePath;
	}
	ace.config.set( 'basePath', basePath + '/CodeEditor/modules/ace' );

	this.$input.addClass( 'oo-ui-element-hidden' );
	this.editor = ace.edit( this.$ace[ 0 ] );
	this.setMinRows( this.minRows );

	// Autocompletion
	this.editor.setOptions( {
		enableBasicAutocompletion: this.autocomplete !== 'none',
		enableLiveAutocompletion: this.autocomplete === 'live'
	} );
	if ( this.autocompleteWordList ) {
		var completer = {
			getCompletions: function ( editor, session, pos, prefix, callback ) {
				var wordList = widget.autocompleteWordList;
				callback( null, wordList.map( function ( word ) {
					return {
						caption: word,
						value: word,
						meta: 'static'
					};
				} ) );
			}
		};
		ace.require( 'ace/ext/language_tools' ).addCompleter( completer );
	}

	this.editor.getSession().on( 'change', this.onEditorChange.bind( this ) );
	this.editor.renderer.on( 'resize', this.onEditorResize.bind( this ) );
	this.setEditorValue( this.getValue() );
	// Force resize (T303964)
	// eslint-disable-next-line es-x/no-resizable-and-growable-arraybuffers
	this.editor.resize( true );
};

/**
 * Set the autocomplete property
 *
 * @param {string} mode Symbolic name of autocomplete mode
 * @return {ve.ui.MWAceEditorWidget}
 * @chainable
 */
ve.ui.MWAceEditorWidget.prototype.setAutocomplete = function ( mode ) {
	var widget = this;
	this.autocomplete = mode;
	this.loadingPromise.done( function () {
		widget.editor.renderer.setOptions( {
			enableBasicAutocompletion: widget.autocomplete !== 'none',
			enableLiveAutocompletion: widget.autocomplete === 'live'
		} );
	} );
	return this;
};

/**
 * @inheritdoc
 */
ve.ui.MWAceEditorWidget.prototype.setValue = function ( value ) {
	// Always do something synchronously so that getValue can be used immediately.
	// setEditorValue is called once when the loadingPromise resolves in setupEditor.
	if ( this.loadingPromise.state() === 'resolved' ) {
		this.setEditorValue( value );
	} else {
		ve.ui.MWAceEditorWidget.super.prototype.setValue.call( this, value );
	}
	return this;
};

/**
 * Set the value of the Ace editor widget
 *
 * @param {string} value
 * @return {ve.ui.MWAceEditorWidget}
 * @chainable
 */
ve.ui.MWAceEditorWidget.prototype.setEditorValue = function ( value ) {
	if ( value !== this.editor.getValue() ) {
		var selectionState = this.editor.session.selection.toJSON();
		this.editor.setValue( value );
		this.editor.session.selection.fromJSON( selectionState );
	}
	return this;
};

/**
 * Set the minimum number of rows in the Ace editor widget
 *
 * @param {number} minRows The minimum number of rows
 * @return {ve.ui.MWAceEditorWidget}
 * @chainable
 */
ve.ui.MWAceEditorWidget.prototype.setMinRows = function ( minRows ) {
	var widget = this;
	this.minRows = minRows;
	this.loadingPromise.done( function () {
		widget.editor.setOptions( {
			minLines: widget.minRows || 3,
			maxLines: widget.autosize ? widget.maxRows : widget.minRows || 3
		} );
	} );
	// TODO: Implement minRows setter for OO.ui.TextInputWidget
	// and call it here in loadingPromise.fail
	return this;
};

/**
 * @inheritdoc
 */
ve.ui.MWAceEditorWidget.prototype.setReadOnly = function ( readOnly ) {
	var widget = this;

	// Parent method
	ve.ui.MWAceEditorWidget.super.prototype.setReadOnly.call( this, readOnly );

	this.loadingPromise.done( function () {
		widget.editor.setReadOnly( widget.isReadOnly() );
	} );

	this.$element.toggleClass( 've-ui-mwAceEditorWidget-readOnly', !!this.isReadOnly() );
	return this;
};

/**
 * @inheritdoc
 */
ve.ui.MWAceEditorWidget.prototype.getRange = function () {
	var lines;
	function posToOffset( row, col ) {
		var offset = 0;

		for ( var r = 0; r < row; r++ ) {
			offset += lines[ r ].length;
			offset++; // for the newline character
		}
		return offset + col;
	}

	if ( this.editor ) {
		lines = this.editor.getSession().getDocument().getAllLines();

		var selection = this.editor.getSelection();
		var isBackwards = selection.isBackwards();
		var range = selection.getRange();
		var start = posToOffset( range.start.row, range.start.column );
		var end = posToOffset( range.end.row, range.end.column );

		return {
			from: isBackwards ? end : start,
			to: isBackwards ? start : end
		};
	} else {
		return ve.ui.MWAceEditorWidget.super.prototype.getRange.call( this );
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWAceEditorWidget.prototype.selectRange = function ( from, to ) {
	var widget = this;
	this.focus();
	this.loadingPromise.done( function () {
		var doc = widget.editor.getSession().getDocument(),
			lines = doc.getAllLines();

		to = to || from;

		function offsetToPos( offset ) {
			var row = 0,
				col,
				pos = 0;

			while ( row < lines.length && pos + lines[ row ].length < offset ) {
				pos += lines[ row ].length;
				pos++; // for the newline character
				row++;
			}
			col = offset - pos;
			return { row: row, column: col };
		}

		var fromOffset = offsetToPos( from );
		var toOffset = offsetToPos( to );

		var selection = widget.editor.getSelection();
		var range = selection.getRange();
		range.setStart( fromOffset.row, fromOffset.column );
		range.setEnd( toOffset.row, toOffset.column );
		selection.setSelectionRange( range );
	} ).fail( function () {
		ve.ui.MWAceEditorWidget.super.prototype.selectRange.call( widget, from, to );
	} );
	return this;
};

/**
 * Handle change events from the Ace editor
 */
ve.ui.MWAceEditorWidget.prototype.onEditorChange = function () {
	// Call setValue on the parent to keep the value property in sync with the editor
	ve.ui.MWAceEditorWidget.super.prototype.setValue.call( this, this.editor.getValue() );
};

/**
 * Handle resize events from the Ace editor
 *
 * @fires resize
 */
ve.ui.MWAceEditorWidget.prototype.onEditorResize = function () {
	// On the first setup the editor doesn't resize until the end of the cycle
	setTimeout( this.emit.bind( this, 'resize' ) );
};

/**
 * Clear the editor's undo stack
 *
 * @return {ve.ui.MWAceEditorWidget}
 * @chainable
 */
ve.ui.MWAceEditorWidget.prototype.clearUndoStack = function () {
	var widget = this;
	this.loadingPromise.done( function () {
		widget.editor.session.setUndoManager(
			new ace.UndoManager()
		);
	} );
	return this;
};

/**
 * Toggle the visibility of line numbers
 *
 * @param {boolean} visible
 * @return {ve.ui.MWAceEditorWidget}
 * @chainable
 */
ve.ui.MWAceEditorWidget.prototype.toggleLineNumbers = function ( visible ) {
	var widget = this;
	this.loadingPromise.done( function () {
		widget.editor.setOption( 'showLineNumbers', visible );
	} );
	return this;
};

/**
 * Toggle the visibility of the print margin
 *
 * @param {boolean} visible
 * @return {ve.ui.MWAceEditorWidget}
 * @chainable
 */
ve.ui.MWAceEditorWidget.prototype.togglePrintMargin = function ( visible ) {
	var widget = this;
	this.loadingPromise.done( function () {
		widget.editor.renderer.setShowPrintMargin( visible );
	} );
	return this;
};

/**
 * Set the language mode of the editor (programming language)
 *
 * @param {string} lang Language
 * @return {ve.ui.MWAceEditorWidget}
 * @chainable
 */
ve.ui.MWAceEditorWidget.prototype.setLanguage = function ( lang ) {
	var widget = this;
	this.loadingPromise.done( function () {
		ace.config.loadModule( 'ace/ext/modelist', function ( modelist ) {
			if ( !modelist || !modelist.modesByName[ lang ] ) {
				lang = 'text';
			}
			widget.editor.getSession().setMode( 'ace/mode/' + lang );
		} );
	} );
	return this;
};

/**
 * Focus the editor
 *
 * @return {ve.ui.MWAceEditorWidget}
 * @chainable
 */
ve.ui.MWAceEditorWidget.prototype.focus = function () {
	var widget = this;
	this.loadingPromise.done( function () {
		widget.editor.focus();
	} ).fail( function () {
		ve.ui.MWAceEditorWidget.super.prototype.focus.call( widget );
	} );
	return this;
};

/**
 * @inheritdoc
 * @param {boolean} [force=false] Force a resize call on Ace editor
 */
ve.ui.MWAceEditorWidget.prototype.adjustSize = function ( force ) {
	var widget = this;
	// If the editor has loaded, resize events are emitted from #onEditorResize
	// so do nothing here unless this is a user triggered resize, otherwise call the parent method.
	if ( force ) {
		this.loadingPromise.done( function () {
			// eslint-disable-next-line es-x/no-resizable-and-growable-arraybuffers
			widget.editor.resize();
		} );
	}
	this.loadingPromise.fail( function () {
		// Parent method
		ve.ui.MWAceEditorWidget.super.prototype.adjustSize.call( widget );
	} );
	return this;
};
