/*!
 * VisualEditor UserInterface MWExportWikitextDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Dialog for exportWikitexting CollabTarget pages
 *
 * @class
 * @extends OO.ui.ProcessDialog
 *
 * @constructor
 * @param {Object} [config] Config options
 */
ve.ui.MWExportWikitextDialog = function VeUiMwExportWikitextDialog( config ) {
	// Parent constructor
	ve.ui.MWExportWikitextDialog.super.call( this, config );

	// Initialization
	this.$element.addClass( 've-ui-mwExportWikitextDialog' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWExportWikitextDialog, OO.ui.ProcessDialog );

/* Static Properties */

ve.ui.MWExportWikitextDialog.static.name = 'mwExportWikitext';

ve.ui.MWExportWikitextDialog.static.title = ve.msg( 'visualeditor-rebase-client-export' );

ve.ui.MWExportWikitextDialog.static.actions = [
	{
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-done' ),
		flags: [ 'safe', 'close' ]
	}
];

ve.ui.MWExportWikitextDialog.static.size = 'larger';

/**
 * @inheritdoc
 */
ve.ui.MWExportWikitextDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.MWExportWikitextDialog.super.prototype.initialize.call( this );

	this.titleInput = new mw.widgets.TitleInputWidget( {
		value: ve.init.target.getImportTitle()
	}, { api: ve.init.target.getContentApi() } );
	this.titleButton = new OO.ui.ButtonWidget( {
		label: ve.msg( 'visualeditor-rebase-client-export-start' ),
		flags: [ 'primary', 'progressive' ]
	} );
	this.titleField = new OO.ui.ActionFieldLayout( this.titleInput, this.titleButton, {
		align: 'top',
		label: ve.msg( 'visualeditor-rebase-client-import-name' ),
		help: ve.msg( 'visualeditor-rebase-client-title-help' ),
		helpInline: true
	} );

	this.titleButton.on( 'click', this.export.bind( this ) );

	this.wikitextLayout = new mw.widgets.CopyTextLayout( {
		align: 'top',
		label: ve.msg( 'visualeditor-savedialog-review-wikitext' ),
		multiline: true,
		textInput: {
			// The following classes are used here:
			// * mw-editfont-monospace
			// * mw-editfont-sans-serif
			// * mw-editfont-serif
			classes: [ 'mw-editfont-' + mw.user.options.get( 'editfont' ) ],
			autosize: true,
			readOnly: true,
			// Height will be trimmed in getReadyProcess
			rows: 99
		}
	} );

	// TODO: Move to CSS
	this.titleField.$element.css( 'max-width', 'none' );
	this.titleInput.$element.css( 'max-width', 'none' );
	this.wikitextLayout.$element.css( 'max-width', 'none' );
	this.wikitextLayout.$field.css( 'max-width', 'none' );
	this.wikitextLayout.textInput.$element.css( 'max-width', 'none' );

	var $content = $( '<div>' );
	$content.append(
		this.titleField.$element,
		this.wikitextLayout.$element
	);

	var panel = new OO.ui.PanelLayout( {
		padded: true,
		expanded: false,
		$content: $content
	} );
	this.$body.append( panel.$element );
};

/**
 * @inheritdoc
 */
ve.ui.MWExportWikitextDialog.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWExportWikitextDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var dialog = this,
				surface = ve.init.target.getSurface(),
				wikitextInput = this.wikitextLayout.textInput;
			this.titleButton.setDisabled( true );
			this.wikitextLayout.textInput.pushPending();
			ve.init.target.getWikitextFragment( surface.getModel().getDocument() ).then( function ( wikitext ) {
				wikitextInput.setValue( wikitext.trim() );
				wikitextInput.$input.scrollTop( 0 );
				wikitextInput.popPending();
				dialog.titleButton.setDisabled( false );
				dialog.updateSize();
			}, function () {
				// TODO: Display API errors
				wikitextInput.popPending();
			} );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWExportWikitextDialog.prototype.getReadyProcess = function ( data ) {
	return ve.ui.MWExportWikitextDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			this.titleInput.focus();

			// Fix height of wikitext input
			this.wikitextLayout.textInput.$input.css( 'max-height', '' );
			var overflow = this.$body[ 0 ].scrollHeight - this.$body[ 0 ].clientHeight;
			if ( overflow > 0 ) {
				// If body is too tall, take the excess height off the wikitext input
				this.wikitextLayout.textInput.$input.css(
					'max-height',
					Math.max(
						this.wikitextLayout.textInput.$input[ 0 ].clientHeight - overflow,
						50 // minimum height
					)
				);
			}

		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWExportWikitextDialog.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWExportWikitextDialog.super.prototype.getTeardownProcess.call( this, data )
		.next( function () {
			this.wikitextLayout.textInput.setValue( '' );
		}, this );
};

/**
 * Export the document to a specific title
 */
ve.ui.MWExportWikitextDialog.prototype.export = function () {
	var wikitext = this.wikitextLayout.textInput.getValue(),
		title = this.titleInput.getMWTitle(),
		importTitle = ve.init.target.getImportTitle();

	var $form = $( '<form>' ).attr( { method: 'post', enctype: 'multipart/form-data' } ).addClass( 'oo-ui-element-hidden' );
	var params = {
		format: 'text/x-wiki',
		model: 'wikitext',
		wpTextbox1: wikitext,
		wpEditToken: mw.user.tokens.get( 'csrfToken' ),
		// MediaWiki function-verification parameters, mostly relevant to the
		// classic editpage, but still required here:
		wpUnicodeCheck: 'ℳ𝒲♥𝓊𝓃𝒾𝒸ℴ𝒹ℯ',
		wpUltimateParam: true,
		wpDiff: true
	};
	if (
		importTitle && title &&
		importTitle.toString() === title.toString()
	) {
		params = ve.extendObject( {
			oldid: ve.init.target.revid,
			basetimestamp: ve.init.target.baseTimeStamp,
			starttimestamp: ve.init.target.startTimeStamp
		}, params );
	}
	// Add params as hidden fields
	for ( var key in params ) {
		$form.append( $( '<input>' ).attr( { type: 'hidden', name: key, value: params[ key ] } ) );
	}
	// Submit the form, mimicking a traditional edit
	// Firefox requires the form to be attached
	var submitUrl = title.getUrl( {
		action: 'submit',
		veswitched: '1'
	} );
	$form.attr( 'action', submitUrl ).appendTo( 'body' ).trigger( 'submit' );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWExportWikitextDialog );
