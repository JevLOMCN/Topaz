/*!
 * VisualEditor user interface MWMediaDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Dialog for inserting and editing MediaWiki media.
 *
 * @class
 * @extends ve.ui.NodeDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWMediaDialog = function VeUiMWMediaDialog( config ) {
	// Parent constructor
	ve.ui.MWMediaDialog.super.call( this, config );

	// Properties
	this.imageModel = null;
	this.isSettingUpModel = false;
	this.isInsertion = false;
	this.selectedImageInfo = null;
	this.searchCache = {};

	this.$element.addClass( 've-ui-mwMediaDialog' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMediaDialog, ve.ui.NodeDialog );

/* Static Properties */

ve.ui.MWMediaDialog.static.name = 'media';

ve.ui.MWMediaDialog.static.title =
	OO.ui.deferMsg( 'visualeditor-dialog-media-title' );

ve.ui.MWMediaDialog.static.size = 'medium';

ve.ui.MWMediaDialog.static.actions = [
	{
		action: 'done',
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-apply' ),
		flags: [ 'progressive', 'primary' ],
		modes: 'edit'
	},
	{
		action: 'insert',
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-insert' ),
		flags: [ 'primary', 'progressive' ],
		modes: 'insert'
	},
	{
		action: 'change',
		label: OO.ui.deferMsg( 'visualeditor-dialog-media-change-image' ),
		modes: [ 'edit', 'insert' ]
	},
	{
		action: 'choose',
		label: OO.ui.deferMsg( 'visualeditor-dialog-media-choose-image' ),
		flags: [ 'primary', 'progressive' ],
		modes: [ 'info' ]
	},
	{
		action: 'upload',
		label: OO.ui.deferMsg( 'visualeditor-dialog-media-upload' ),
		flags: [ 'primary', 'progressive' ],
		modes: [ 'upload-upload' ]
	},
	{
		action: 'save',
		label: OO.ui.deferMsg( 'visualeditor-dialog-media-save' ),
		flags: [ 'primary', 'progressive' ],
		modes: [ 'upload-info' ]
	},
	{
		action: 'cancelchoose',
		label: OO.ui.deferMsg( 'visualeditor-dialog-media-goback' ),
		flags: [ 'safe', 'back' ],
		modes: [ 'info' ]
	},
	{
		action: 'cancelupload',
		label: OO.ui.deferMsg( 'visualeditor-dialog-media-goback' ),
		flags: [ 'safe', 'back' ],
		modes: [ 'upload-info' ]
	},
	{
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-cancel' ),
		flags: [ 'safe', 'close' ],
		modes: [ 'readonly', 'edit', 'insert', 'select', 'search', 'upload-upload' ]
	},
	{
		action: 'back',
		label: OO.ui.deferMsg( 'visualeditor-dialog-media-goback' ),
		flags: [ 'safe', 'back' ],
		modes: [ 'change' ]
	}
];

ve.ui.MWMediaDialog.static.modelClasses = [ ve.dm.MWBlockImageNode, ve.dm.MWInlineImageNode ];

ve.ui.MWMediaDialog.static.includeCommands = null;

ve.ui.MWMediaDialog.static.excludeCommands = [
	// No formatting
	'paragraph',
	'heading1',
	'heading2',
	'heading3',
	'heading4',
	'heading5',
	'heading6',
	'preformatted',
	'blockquote',
	// TODO: Decide if tables tools should be allowed
	'tableCellHeader',
	'tableCellData',
	// No structure
	'bullet',
	'bulletWrapOnce',
	'number',
	'numberWrapOnce',
	'indent',
	'outdent'
];

/**
 * Get the import rules for the surface widget in the dialog
 *
 * @see ve.dm.ElementLinearData#sanitize
 * @return {Object} Import rules
 */
ve.ui.MWMediaDialog.static.getImportRules = function () {
	var rules = ve.copy( ve.init.target.constructor.static.importRules );
	return ve.extendObject(
		rules,
		{
			all: {
				blacklist: ve.extendObject(
					{
						// Tables (but not lists) are possible in wikitext with a leading
						// line break but we prevent creating these with the UI
						list: true,
						listItem: true,
						definitionList: true,
						definitionListItem: true,
						table: true,
						tableCaption: true,
						tableSection: true,
						tableRow: true,
						tableCell: true,
						mwTable: true,
						mwTransclusionTableCell: true
					},
					ve.getProp( rules, 'all', 'blacklist' )
				),
				// Headings are also possible, but discouraged
				conversions: ve.extendObject(
					{
						mwHeading: 'paragraph'
					},
					ve.getProp( rules, 'all', 'conversions' )
				)
			}
		}
	);
};

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWMediaDialog.prototype.getBodyHeight = function () {
	// FIXME: This should vary on panel.
	return 600;
};

/**
 * @inheritdoc
 */
ve.ui.MWMediaDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.MWMediaDialog.super.prototype.initialize.call( this );

	// Main layout
	this.panels = new OO.ui.StackLayout();

	// Settings panels
	this.mediaSettingsLayout = new OO.ui.IndexLayout( {
		classes: [ 've-ui-mwMediaDialog-panel-settings' ]
	} );
	this.generalSettingsPanel = new OO.ui.TabPanelLayout( 'general', {
		label: ve.msg( 'visualeditor-dialog-media-page-general' )
	} );
	this.advancedSettingsPanel = new OO.ui.TabPanelLayout( 'advanced', {
		label: ve.msg( 'visualeditor-dialog-media-page-advanced' )
	} );

	// General settings panel

	// Filename
	this.filenameFieldset = new OO.ui.FieldsetLayout( {
		label: ve.msg( 'visualeditor-dialog-media-content-filename' ),
		icon: 'image'
	} );

	// Caption
	this.captionTarget = ve.init.target.createTargetWidget( {
		includeCommands: this.constructor.static.includeCommands,
		excludeCommands: this.constructor.static.excludeCommands,
		importRules: this.constructor.static.getImportRules(),
		inDialog: this.constructor.static.name,
		multiline: false
	} );
	var captionField = new OO.ui.FieldLayout( this.captionTarget, {
		align: 'top'
	} );
	this.captionFieldset = new OO.ui.FieldsetLayout( {
		$overlay: this.$overlay,
		label: ve.msg( 'visualeditor-dialog-media-content-section' ),
		help: ve.msg( 'visualeditor-dialog-media-content-section-help' ),
		classes: [ 've-ui-mwMediaDialog-caption-fieldset' ]
	} );
	this.captionFieldset.addItems( [ captionField ] );

	// Alt text
	this.altTextInput = new OO.ui.MultilineTextInputWidget( {
		spellcheck: true,
		classes: [ 've-ui-mwMediaDialog-altText' ],
		autosize: true,
		rows: 1,
		allowLinebreaks: false
	} );
	var altTextField = new OO.ui.FieldLayout( this.altTextInput, {
		align: 'top'
	} );
	var altTextFieldset = new OO.ui.FieldsetLayout( {
		$overlay: this.$overlay,
		label: ve.msg( 'visualeditor-dialog-media-alttext-section' ),
		help: ve.msg( 'visualeditor-dialog-media-alttext-section-help' )
	} );
	altTextFieldset.addItems( [ altTextField ] );

	// Advanced settings

	// Position
	this.positionSelect = new ve.ui.AlignWidget( {
		dir: this.getDir()
	} );
	var positionSelectField = new OO.ui.FieldLayout( this.positionSelect );
	this.positionCheckbox = new OO.ui.CheckboxInputWidget();
	var positionCheckboxField = new OO.ui.FieldLayout( this.positionCheckbox, {
		$overlay: this.$overlay,
		align: 'inline',
		label: ve.msg( 'visualeditor-dialog-media-position-checkbox' ),
		help: ve.msg( 'visualeditor-dialog-media-position-checkbox-help' )
	} );
	var positionFieldset = new OO.ui.FieldsetLayout( {
		$overlay: this.$overlay,
		label: ve.msg( 'visualeditor-dialog-media-position-section' ),
		help: ve.msg( 'visualeditor-dialog-media-position-section-help' )
	} );
	positionFieldset.addItems( [
		positionCheckboxField,
		positionSelectField
	] );

	// Type
	this.typeSelectDropdown = new OO.ui.DropdownWidget( { $overlay: this.$overlay } );
	this.typeSelect = this.typeSelectDropdown.getMenu();
	this.typeSelect.addItems( [
		// TODO: Inline images require a bit of further work, will be coming soon
		new OO.ui.MenuOptionWidget( {
			data: 'thumb',
			icon: 'imageLayoutThumbnail',
			label: ve.msg( 'visualeditor-dialog-media-type-thumb' )
		} ),
		new OO.ui.MenuOptionWidget( {
			data: 'frameless',
			icon: 'imageLayoutFrameless',
			label: ve.msg( 'visualeditor-dialog-media-type-frameless' )
		} ),
		new OO.ui.MenuOptionWidget( {
			data: 'frame',
			icon: 'imageLayoutFrame',
			label: ve.msg( 'visualeditor-dialog-media-type-frame' )
		} ),
		new OO.ui.MenuOptionWidget( {
			data: 'none',
			icon: 'imageLayoutBasic',
			label: ve.msg( 'visualeditor-dialog-media-type-none' )
		} )
	] );
	var typeSelectField = new OO.ui.FieldLayout( this.typeSelectDropdown, {
		align: 'top'
	} );
	this.borderCheckbox = new OO.ui.CheckboxInputWidget();
	var borderField = new OO.ui.FieldLayout( this.borderCheckbox, {
		align: 'inline',
		label: ve.msg( 'visualeditor-dialog-media-type-border' )
	} );
	this.typeFieldset = new OO.ui.FieldsetLayout( {
		$overlay: this.$overlay,
		label: ve.msg( 'visualeditor-dialog-media-type-section' ),
		help: ve.msg( 'visualeditor-dialog-media-type-section-help' )
	} );
	this.typeFieldset.addItems( [
		typeSelectField,
		borderField
	] );

	// Size
	this.sizeWidget = new ve.ui.MediaSizeWidget( undefined, {
		dimensionsAlign: 'top'
	} );
	var sizeWidgetField = new OO.ui.FieldLayout( this.sizeWidget );
	this.sizeFieldset = new OO.ui.FieldsetLayout( {
		$overlay: this.$overlay,
		label: ve.msg( 'visualeditor-dialog-media-size-section' ),
		help: ve.msg( 'visualeditor-dialog-media-size-section-help' )
	} );
	this.sizeFieldset.addItems( [
		sizeWidgetField
	] );

	// Search, upload and info layouts
	this.mediaSearchPanel = new OO.ui.TabPanelLayout( {
		classes: [ 've-ui-mwMediaDialog-panel-search' ],
		scrollable: true
	} );
	if ( mw.ForeignStructuredUpload && mw.ForeignStructuredUpload.BookletLayout ) {
		this.mediaUploadBooklet = new mw.ForeignStructuredUpload.BookletLayout( {
			$overlay: this.$overlay
		} );
	}
	this.mediaImageInfoPanel = new OO.ui.TabPanelLayout( {
		classes: [ 've-ui-mwMediaDialog-panel-imageinfo' ],
		scrollable: false
	} );
	this.$infoPanelWrapper = $( '<div>' ).addClass( 've-ui-mwMediaDialog-panel-imageinfo-wrapper' );

	// Search and upload panels
	this.searchTabs = new OO.ui.IndexLayout();
	var searchPanel = new OO.ui.TabPanelLayout( 'search', {
		label: ve.msg( 'visualeditor-dialog-media-search-tab-search' )
	} );
	var uploadPanel;
	if ( this.mediaUploadBooklet ) {
		uploadPanel = new OO.ui.TabPanelLayout( 'upload', {
			label: ve.msg( 'visualeditor-dialog-media-search-tab-upload' ),
			content: [ this.mediaUploadBooklet ]
		} );
	}

	// Search widget
	this.search = new mw.widgets.MediaSearchWidget( {
		rowHeight: OO.ui.isMobile() ? 120 : 200
	} );

	// Events
	this.positionCheckbox.connect( this, { change: 'onPositionCheckboxChange' } );
	this.borderCheckbox.connect( this, { change: 'onBorderCheckboxChange' } );
	this.positionSelect.connect( this, { choose: 'onPositionSelectChoose' } );
	this.typeSelect.connect( this, { choose: 'onTypeSelectChoose' } );
	this.search.getQuery().connect( this, { change: 'onSearchQueryChange' } );
	this.search.getQuery().$indicator.on( 'mousedown', this.onSearchQueryClear.bind( this ) );
	this.search.getResults().connect( this, { choose: 'onSearchResultsChoose' } );
	this.captionTarget.connect( this, { change: 'checkChanged' } );
	this.altTextInput.connect( this, { change: 'onAlternateTextChange' } );
	this.searchTabs.connect( this, { set: 'onSearchTabsSet' } );
	if ( this.mediaUploadBooklet ) {
		this.mediaUploadBooklet.connect( this, {
			set: 'onMediaUploadBookletSet',
			uploadValid: 'onUploadValid',
			infoValid: 'onInfoValid'
		} );
	}

	// Append panels
	searchPanel.$element.append( this.search.$element );
	this.searchTabs.addTabPanels( [ searchPanel ] );
	if ( this.mediaUploadBooklet ) {
		this.searchTabs.addTabPanels( [ uploadPanel ] );
	}
	this.mediaSearchPanel.$element.append(
		this.searchTabs.$element
	);
	this.generalSettingsPanel.$element.append(
		this.filenameFieldset.$element,
		this.captionFieldset.$element,
		altTextFieldset.$element
	);
	this.advancedSettingsPanel.$element.append(
		positionFieldset.$element,
		this.typeFieldset.$element,
		this.sizeFieldset.$element
	);
	this.mediaSettingsLayout.addTabPanels( [
		this.generalSettingsPanel,
		this.advancedSettingsPanel
	] );
	this.panels.addItems( [
		this.mediaSearchPanel,
		this.mediaImageInfoPanel,
		this.mediaSettingsLayout
	] );
	this.$body.append( this.panels.$element );
};

/**
 * Handle set events from the search tabs
 *
 * @param {OO.ui.TabPanelLayout} tabPanel Current tabPanel
 */
ve.ui.MWMediaDialog.prototype.onSearchTabsSet = function ( tabPanel ) {
	var name = tabPanel.getName();

	this.actions.setMode( name );

	switch ( name ) {
		case 'search':
			this.setSize( 'larger' );
			break;

		case 'upload':
			// Initialize and reset the upload booklet if it hasn't
			// been initiailized since setup.
			if ( !this.mediaUploadBookletInit ) {
				this.mediaUploadBookletInit = true;
				this.mediaUploadBooklet.initialize();
			}
			this.setSize( 'medium' );
			this.uploadPageNameSet( 'upload' );
			break;
	}
};

/**
 * Handle panelNameSet events from the upload stack
 *
 * @param {OO.ui.PageLayout} page Current page
 */
ve.ui.MWMediaDialog.prototype.onMediaUploadBookletSet = function ( page ) {
	this.uploadPageNameSet( page.getName() );
};

/**
 * The upload booklet's page name has changed
 *
 * @param {string} pageName Page name
 */
ve.ui.MWMediaDialog.prototype.uploadPageNameSet = function ( pageName ) {
	if ( pageName === 'insert' ) {
		var imageInfo = this.mediaUploadBooklet.upload.getImageInfo();
		this.chooseImageInfo( imageInfo );
	} else {
		// Hide the tabs after the first page
		this.searchTabs.toggleMenu( pageName === 'upload' );

		this.actions.setMode( 'upload-' + pageName );
	}
};

/**
 * Handle uploadValid events
 *
 * @param {boolean} isValid The panel is complete and valid
 */
ve.ui.MWMediaDialog.prototype.onUploadValid = function ( isValid ) {
	this.actions.setAbilities( { upload: isValid } );
};

/**
 * Handle infoValid events
 *
 * @param {boolean} isValid The panel is complete and valid
 */
ve.ui.MWMediaDialog.prototype.onInfoValid = function ( isValid ) {
	this.actions.setAbilities( { save: isValid } );
};

/**
 * Build the image info panel from the information in the API.
 * Use the metadata info if it exists.
 * Note: Some information in the metadata object needs to be safely
 * stripped from its html wrappers.
 *
 * @param {Object} imageinfo Image info
 */
ve.ui.MWMediaDialog.prototype.buildMediaInfoPanel = function ( imageinfo ) {
	var contentDirection = this.getFragment().getDocument().getDir(),
		imageTitleText = imageinfo.title || imageinfo.canonicaltitle,
		imageTitle = new OO.ui.LabelWidget( {
			label: mw.Title.newFromText( imageTitleText ).getNameText()
		} ),
		metadata = imageinfo.extmetadata,
		// Field configuration (in order)
		apiDataKeysConfig = [
			{
				name: 'ImageDescription',
				value: ve.getProp( metadata, 'ImageDescription', 'value' ),
				format: 'html',
				view: {
					type: 'description',
					primary: true,
					descriptionHeight: '5em'
				}
			},
			{
				name: '$fileDetails',
				// Real value is set later
				value: '',
				format: 'html',
				view: { icon: 'image' }
			},
			{
				name: 'LicenseShortName',
				value: ve.getProp( metadata, 'LicenseShortName', 'value' ),
				format: 'html-remove-formatting',
				view: {
					href: ve.getProp( metadata, 'LicenseUrl', 'value' ),
					icon: this.getLicenseIcon( ve.getProp( metadata, 'LicenseShortName', 'value' ) )
				}
			},
			{
				name: 'Artist',
				value: ve.getProp( metadata, 'Artist', 'value' ),
				format: 'html-remove-formatting',
				view: {
					// "Artist" label
					labelMsg: 'visualeditor-dialog-media-info-meta-artist',
					icon: 'userAvatar'
				}
			},
			{
				name: 'Credit',
				value: ve.getProp( metadata, 'Credit', 'value' ),
				format: 'html-remove-formatting',
				view: { icon: 'userAvatar' }
			},
			{
				name: 'user',
				value: imageinfo.user,
				format: 'plaintext',
				view: {
					icon: 'userAvatar',
					// This is 'uploaded by'
					labelMsg: 'visualeditor-dialog-media-info-artist'
				}
			},
			{
				name: 'timestamp',
				value: imageinfo.timestamp,
				format: 'plaintext',
				view: {
					icon: 'clock',
					labelMsg: 'visualeditor-dialog-media-info-uploaded',
					isDate: true
				}
			},
			{
				name: 'DateTimeOriginal',
				value: ve.getProp( metadata, 'DateTimeOriginal', 'value' ),
				format: 'html-remove-formatting',
				view: {
					icon: 'clock',
					labelMsg: 'visualeditor-dialog-media-info-created'
				}
			},
			{
				name: 'moreinfo',
				value: ve.msg( 'visualeditor-dialog-media-info-moreinfo' ),
				format: 'plaintext',
				view: {
					icon: 'info',
					href: imageinfo.descriptionurl
				}
			}
		],
		fields = {},
		// Store clean API data
		apiData = {},
		fileType = this.getFileType( imageinfo.url ),
		$thumbContainer = $( '<div>' )
			.addClass( 've-ui-mwMediaDialog-panel-imageinfo-thumb' ),
		$main = $( '<div>' )
			.addClass( 've-ui-mwMediaDialog-panel-imageinfo-main' ),
		$details = $( '<div>' )
			.addClass( 've-ui-mwMediaDialog-panel-imageinfo-details' ),
		$image = $( '<img>' );

	// Main section - title
	$main.append(
		imageTitle.$element
			.addClass( 've-ui-mwMediaDialog-panel-imageinfo-title' )
	);

	var i, field;
	// Clean data from the API responses
	for ( i = 0; i < apiDataKeysConfig.length; i++ ) {
		field = apiDataKeysConfig[ i ].name;
		if ( apiDataKeysConfig[ i ].format === 'html' ) {
			apiData[ field ] = new OO.ui.HtmlSnippet( apiDataKeysConfig[ i ].value );

		} else if ( apiDataKeysConfig[ i ].format === 'html-remove-formatting' ) {
			apiData[ field ] = this.cleanAPIresponse( apiDataKeysConfig[ i ].value );

		} else if ( apiDataKeysConfig[ i ].format === 'plaintext' ) {
			apiData[ field ] = apiDataKeysConfig[ i ].value;

		} else {
			throw new Error( 'Unexpected metadata field format' );
		}
	}

	// Add sizing info for non-audio images
	if ( imageinfo.mediatype === 'AUDIO' ) {
		// Label this file as an audio
		apiData.$fileDetails = $( '<span>' )
			.text( ve.msg( 'visualeditor-dialog-media-info-audiofile' ) );
	} else {
		// Build the display for image size and type
		apiData.$fileDetails = $( '<div>' )
			.append(
				$( '<span>' ).text(
					imageinfo.width +
					'\u00a0' +
					ve.msg( 'visualeditor-dimensionswidget-times' ) +
					'\u00a0' +
					imageinfo.height +
					ve.msg( 'visualeditor-dimensionswidget-px' )
				),
				$( '<span>' )
					.addClass( 've-ui-mwMediaDialog-panel-imageinfo-separator' )
					.text( ve.msg( 'visualeditor-dialog-media-info-separator' ) ),
				$( '<span>' ).text( fileType )
			);
	}

	// Attach all fields in order
	for ( i = 0; i < apiDataKeysConfig.length; i++ ) {
		field = apiDataKeysConfig[ i ].name;
		if ( apiData[ field ] ) {
			var $section = apiDataKeysConfig[ i ].view.primary ? $main : $details;

			fields[ field ] = new ve.ui.MWMediaInfoFieldWidget( apiData[ field ], apiDataKeysConfig[ i ].view );
			$section.append( fields[ field ].$element );
		}
	}

	// Build the info panel
	var $info = $( '<div>' )
		.addClass( 've-ui-mwMediaDialog-panel-imageinfo-info' )
		.append(
			$main.prop( 'dir', contentDirection ),
			$details
		);
	ve.targetLinksToNewWindow( $info[ 0 ] );

	// Initialize thumb container
	$thumbContainer
		.append( $image.prop( 'src', imageinfo.thumburl ) );

	this.$infoPanelWrapper.append(
		$thumbContainer,
		$info
	);

	// Force a scrollbar to the screen before we measure it
	this.mediaImageInfoPanel.$element.css( 'overflow-y', 'scroll' );
	var windowWidth = this.mediaImageInfoPanel.$element.width();

	// Define thumbnail size
	var newDimensions;
	if ( imageinfo.mediatype === 'AUDIO' ) {
		// HACK: We are getting the wrong information from the
		// API about audio files. Set their thumbnail to square
		newDimensions = {
			width: imageinfo.thumbwidth,
			height: imageinfo.thumbwidth
		};
	} else {
		// For regular images, calculate a bigger image dimensions
		newDimensions = ve.dm.MWImageNode.static.resizeToBoundingBox(
			// Original image dimensions
			{
				width: imageinfo.width,
				height: imageinfo.height
			},
			// Bounding box -- the size of the dialog, minus padding
			{
				width: windowWidth,
				height: this.getBodyHeight() - 120
			}
		);
	}
	// Resize the image
	$image.css( {
		width: newDimensions.width,
		height: newDimensions.height
	} );

	// Call for a bigger image
	this.fetchThumbnail( imageTitleText, newDimensions )
		.done( function ( thumburl ) {
			if ( thumburl ) {
				$image.prop( 'src', thumburl );
			}
		} );

	var isPortrait = newDimensions.width < ( windowWidth * 3 / 5 );
	this.mediaImageInfoPanel.$element.toggleClass( 've-ui-mwMediaDialog-panel-imageinfo-portrait', isPortrait );
	this.mediaImageInfoPanel.$element.append( this.$infoPanelWrapper );
	if ( isPortrait ) {
		$info.outerWidth( Math.floor( windowWidth - $thumbContainer.outerWidth( true ) - 15 ) );
	}

	// Initialize fields
	for ( field in fields ) {
		fields[ field ].initialize();
	}
	// Let the scrollbar appear naturally if it should
	this.mediaImageInfoPanel.$element.css( 'overflow', '' );
};

/**
 * Fetch a bigger image thumbnail from the API.
 *
 * @param {string} imageName Image source
 * @param {Object} dimensions Image dimensions
 * @return {jQuery.Promise} Thumbnail promise that resolves with new thumb url
 */
ve.ui.MWMediaDialog.prototype.fetchThumbnail = function ( imageName, dimensions ) {
	var dialog = this,
		params = {
			action: 'query',
			prop: 'imageinfo',
			iiprop: 'url',
			titles: imageName
		};

	// Check cache first
	if ( this.searchCache[ imageName ] ) {
		return ve.createDeferred().resolve( this.searchCache[ imageName ] );
	}

	if ( dimensions.width ) {
		params.iiurlwidth = dimensions.width;
	}
	if ( dimensions.height ) {
		params.iiurlheight = dimensions.height;
	}
	return ve.init.target.getContentApi( this.getFragment().getDocument() ).get( params )
		.then( function ( response ) {
			var thumburl = ve.getProp( response.query.pages[ 0 ], 'imageinfo', 0, 'thumburl' );
			// Cache
			dialog.searchCache[ imageName ] = thumburl;
			return thumburl;
		} );
};

/**
 * Clean the API responses and return it in plaintext. If needed, truncate.
 *
 * @param {string} html Raw response from the API
 * @return {string} Plaintext clean response
 */
ve.ui.MWMediaDialog.prototype.cleanAPIresponse = function ( html ) {
	var text = $( $.parseHTML( html ) ).text();

	// Check if the string should be truncated
	var charLimit = 50;
	if ( text.length > charLimit ) {
		var ellipsis = ve.msg( 'visualeditor-dialog-media-info-ellipsis' );
		text = text.slice( 0, charLimit ) + ellipsis;
	}

	return text;
};

/**
 * Get the file type from the suffix of the url
 *
 * @param {string} url Full file url
 * @return {string} File type
 */
ve.ui.MWMediaDialog.prototype.getFileType = function ( url ) {
	// TODO: Validate these types, and work with icons
	// SVG, PNG, JPEG, GIF, TIFF, XCF;
	// OGA, OGG, MIDI, WAV;
	// WEBM, OGV, OGX;
	// APNG;
	// PDF, DJVU
	return url.split( '.' ).pop().toUpperCase();
};

/**
 * Get the proper icon for the license if it is recognized
 * or general info icon if it is not recognized.
 *
 * @param {string} license License short name
 * @return {string} Icon name
 */
ve.ui.MWMediaDialog.prototype.getLicenseIcon = function ( license ) {
	if ( !license ) {
		return 'info';
	}

	var normalized = license.toLowerCase().replace( /[-_]/g, ' ' );

	// FIXME: Structured data from Commons will make this properly
	// multilingual. For now, this is the limit of what is sensible.
	if ( /^((cc )?pd|public domain)/.test( normalized ) ) {
		return 'public-domain';
	} else if ( /^cc (by|sa)?/.test( normalized ) ) {
		return 'logoCC';
	} else {
		return 'info';
	}
};

/**
 * Handle search results choose event.
 *
 * @param {mw.widgets.MediaResultWidget} item Chosen item
 */
ve.ui.MWMediaDialog.prototype.onSearchResultsChoose = function ( item ) {
	this.chooseImageInfo( item.getData() );

	ve.track( 'activity.' + this.constructor.static.name, {
		action: 'search-choose-image'
	} );
};

/**
 * Handle query change events from the search input widget
 *
 * @param {string} query
 */
ve.ui.MWMediaDialog.prototype.onSearchQueryChange = function ( query ) {
	if ( query === '' ) {
		return;
	}

	ve.track( 'activity.' + this.constructor.static.name, {
		action: 'search-change-query'
	} );
};

/**
 * Handle clearing of search query by user clicking on indicator
 */
ve.ui.MWMediaDialog.prototype.onSearchQueryClear = function () {
	ve.track( 'activity.' + this.constructor.static.name, {
		action: 'search-clear-query'
	} );
};

/**
 * Choose image info for editing
 *
 * @param {Object} info Image info
 */
ve.ui.MWMediaDialog.prototype.chooseImageInfo = function ( info ) {
	this.$infoPanelWrapper.empty();
	// Switch panels
	this.selectedImageInfo = info;
	this.switchPanels( 'imageInfo' );
	// Build info panel
	this.buildMediaInfoPanel( info );
};

/**
 * Handle new image being chosen.
 *
 * @param {mw.widgets.MediaResultWidget|null} item Selected item
 */
ve.ui.MWMediaDialog.prototype.confirmSelectedImage = function () {
	var obj = {},
		info = this.selectedImageInfo;

	if ( info ) {
		var imageTitleText = info.title || info.canonicaltitle;
		// Run title through mw.Title so the File: prefix is localised
		var title = mw.Title.newFromText( imageTitleText ).getPrefixedText();
		if ( !this.imageModel ) {
			// Create a new image model based on default attributes
			this.imageModel = ve.dm.MWImageModel.static.newFromImageAttributes(
				{
					// Per https://www.mediawiki.org/w/?diff=931265&oldid=prev
					href: './' + title,
					src: info.url,
					resource: './' + title,
					width: info.thumbwidth,
					height: info.thumbheight,
					mediaType: info.mediatype,
					type: 'thumb',
					align: 'default',
					defaultSize: true,
					imageClassAttr: 'mw-file-element'
				},
				this.getFragment().getDocument()
			);
			this.attachImageModel();
			this.resetCaption();
		} else {
			// Update the current image model with the new image source
			this.imageModel.changeImageSource(
				{
					mediaType: info.mediatype,
					href: './' + title,
					src: info.url,
					resource: './' + title
				},
				info
			);
			this.updateFilenameFieldset();
		}

		// Cache
		// We're trimming the stored data down to be consistent with what
		// ImageInfoCache.getRequestPromise fetches.
		obj[ imageTitleText ] = {
			size: info.size,
			width: info.width,
			height: info.height,
			mediatype: info.mediatype
		};
		ve.init.platform.imageInfoCache.set( obj );

		this.checkChanged();
		this.switchPanels( 'edit' );

		ve.track( 'activity.' + this.constructor.static.name, {
			action: 'search-confirm-image'
		} );
	}
};

/**
 * Update the filename fieldset (link to media page)
 */
ve.ui.MWMediaDialog.prototype.updateFilenameFieldset = function () {
	var title = mw.Title.newFromText( mw.libs.ve.normalizeParsoidResourceName( this.imageModel.getResourceName() ) );
	this.filenameFieldset.setLabel(
		$( '<span>' ).append(
			$( document.createTextNode( this.imageModel.getFilename() + ' ' ) ),
			$( '<a>' )
				.addClass( 've-ui-mwMediaDialog-description-link' )
				.attr( 'href', title.getUrl() )
				.attr( 'target', '_blank' )
				.attr( 'rel', 'noopener' )
				.text( ve.msg( 'visualeditor-dialog-media-content-description-link' ) )
		)
	);
};

/**
 * Handle image model alignment change
 *
 * @param {string} alignment Image alignment
 */
ve.ui.MWMediaDialog.prototype.onImageModelAlignmentChange = function ( alignment ) {
	alignment = alignment || 'none';

	// Select the item without triggering the 'choose' event
	this.positionSelect.selectItemByData( alignment !== 'none' ? alignment : undefined );

	this.positionCheckbox.setSelected( alignment !== 'none' );
	this.checkChanged();
};

/**
 * Handle image model type change
 *
 * @param {string} type Image type
 */
ve.ui.MWMediaDialog.prototype.onImageModelTypeChange = function ( type ) {
	this.typeSelect.selectItemByData( type );

	this.borderCheckbox.setDisabled(
		!this.imageModel.isBorderable()
	);

	this.borderCheckbox.setSelected(
		this.imageModel.isBorderable() && this.imageModel.hasBorder()
	);
	this.checkChanged();
};

/**
 * Handle change event on the positionCheckbox element.
 *
 * @param {boolean} isSelected Checkbox status
 */
ve.ui.MWMediaDialog.prototype.onPositionCheckboxChange = function ( isSelected ) {
	var currentModelAlignment = this.imageModel.getAlignment();

	this.positionSelect.setDisabled( !isSelected );
	this.checkChanged();
	// Only update the model if the current value is different than that
	// of the image model
	if (
		( currentModelAlignment === 'none' && isSelected ) ||
		( currentModelAlignment !== 'none' && !isSelected )
	) {
		if ( isSelected ) {
			// Picking a floating alignment value will create a block image
			// no matter what the type is, so in here we want to calculate
			// the default alignment of a block to set as our initial alignment
			// in case the checkbox is clicked but there was no alignment set
			// previously.
			var newPositionValue = this.imageModel.getDefaultDir( 'mwBlockImage' );
			this.imageModel.setAlignment( newPositionValue );
		} else {
			// If we're unchecking the box, always set alignment to none and unselect the position widget
			this.imageModel.setAlignment( 'none' );
		}
	}
};

/**
 * Handle change event on the positionCheckbox element.
 *
 * @param {boolean} isSelected Checkbox status
 */
ve.ui.MWMediaDialog.prototype.onBorderCheckboxChange = function ( isSelected ) {
	// Only update if the value is different than the model
	if ( this.imageModel.hasBorder() !== isSelected ) {
		// Update the image model
		this.imageModel.toggleBorder( isSelected );
		this.checkChanged();
	}
};

/**
 * Handle change event on the positionSelect element.
 *
 * @param {OO.ui.ButtonOptionWidget} item Selected item
 */
ve.ui.MWMediaDialog.prototype.onPositionSelectChoose = function ( item ) {
	var position = item.getData();

	// Only update if the value is different than the model
	if ( this.imageModel.getAlignment() !== position ) {
		this.imageModel.setAlignment( position );
		this.checkChanged();
	}
};

/**
 * Handle change event on the typeSelect element.
 *
 * @param {OO.ui.MenuOptionWidget} item Selected item
 */
ve.ui.MWMediaDialog.prototype.onTypeSelectChoose = function ( item ) {
	var type = item.getData();

	// Only update if the value is different than the model
	if ( this.imageModel.getType() !== type ) {
		this.imageModel.setType( type );
		this.checkChanged();
	}

	// If type is 'frame', custom size is ignored
	if ( type === 'frame' ) {
		this.sizeWidget.setSizeType( 'default' );
	}
};

/**
 * Handle changeSizeType events from the MediaSizeWidget
 *
 * @param {string} sizeType Size type
 */
ve.ui.MWMediaDialog.prototype.onChangeSizeType = function ( sizeType ) {
	// type=frame is not resizeable, so change it to type=thumb
	if ( sizeType === 'custom' && this.imageModel.getType() === 'frame' ) {
		this.imageModel.setType( 'thumb' );
	}

	this.checkChanged();
};

/**
 * Respond to change in alternate text
 *
 * @param {string} text New alternate text
 */
ve.ui.MWMediaDialog.prototype.onAlternateTextChange = function ( text ) {
	this.imageModel.setAltText( text );
	this.checkChanged();
};

/**
 * When changes occur, enable the apply button.
 */
ve.ui.MWMediaDialog.prototype.checkChanged = function () {
	var captionChanged = false;

	// Only check 'changed' status after the model has finished
	// building itself
	if ( !this.isSettingUpModel ) {
		captionChanged = !!this.captionTarget && this.captionTarget.hasBeenModified();

		if (
			this.imageModel &&
			// Activate or deactivate the apply/insert buttons
			// Make sure sizes are valid first
			this.sizeWidget.isValid() &&
			(
				// Check that the model or caption changed
				this.isInsertion ||
				captionChanged ||
				this.imageModel.hasBeenModified()
			)
		) {
			this.actions.setAbilities( { insert: true, done: true } );
		} else {
			this.actions.setAbilities( { insert: false, done: false } );
		}
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWMediaDialog.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWMediaDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var isReadOnly = this.isReadOnly();

			// Set language for search results
			this.search.setLang( this.getFragment().getDocument().getLang() );

			if ( this.selectedNode ) {
				this.isInsertion = false;
				// Create image model
				this.imageModel = ve.dm.MWImageModel.static.newFromImageNode( this.selectedNode );
				this.attachImageModel();

				if ( !this.imageModel.isDefaultSize() ) {
					// To avoid dirty diff in case where only the image changes,
					// we will store the initial bounding box, in case the image
					// is not defaultSize
					this.imageModel.setBoundingBox( this.imageModel.getCurrentDimensions() );
				}
				// Store initial hash to compare against
				this.imageModel.storeInitialHash( this.imageModel.getHashObject() );
			} else {
				this.isInsertion = true;
			}

			this.search.setup();
			// Try to populate with user uploads
			this.search.queryMediaQueue();
			this.resetCaption();

			this.altTextInput.setReadOnly( isReadOnly );
			this.positionCheckbox.setDisabled( isReadOnly );
			// TODO: This widget is not readable when disabled
			this.positionSelect.setDisabled( isReadOnly );
			this.typeSelectDropdown.setDisabled( isReadOnly );
			this.borderCheckbox.setDisabled( isReadOnly );
			this.sizeWidget.setDisabled( isReadOnly );

			// Pass `true` to avoid focussing. If we focus the image caption widget during dialog
			// opening, and it wants to display a context menu, it will be mispositioned.
			this.switchPanels( this.selectedNode ? 'edit' : 'search', true );

			this.actions.setAbilities( { upload: false, save: false, insert: false, done: false } );

			this.mediaUploadBookletInit = false;
			if ( data.file && this.mediaUploadBooklet ) {
				this.searchTabs.setTabPanel( 'upload' );
				this.mediaUploadBooklet.setFile( data.file );
			}
		}, this );
};

/**
 * Switch between the edit and insert/search panels
 *
 * @param {string} panel Panel name
 * @param {boolean} [noFocus=false] Do not put focus into the default field of the panel
 */
ve.ui.MWMediaDialog.prototype.switchPanels = function ( panel, noFocus ) {
	switch ( panel ) {
		case 'edit':
			this.setSize( this.constructor.static.size );
			// Set the edit panel
			this.panels.setItem( this.mediaSettingsLayout );
			// Focus the general settings page
			this.mediaSettingsLayout.setTabPanel( 'general' );
			// Parent functionality (edit/insert/readonly)
			this.actions.setMode( this.getMode() );
			if ( !noFocus ) {
				// Focus the caption surface
				this.captionTarget.focus();
			}
			// Auto-sized alt text field is populated while hidden,
			// so force a manual resize now.
			this.altTextInput.adjustSize( true );
			break;
		case 'search':
			this.setSize( 'larger' );
			this.selectedImageInfo = null;
			// Set the edit panel
			this.panels.setItem( this.mediaSearchPanel );
			this.searchTabs.setTabPanel( 'search' );
			this.searchTabs.toggleMenu( true );
			this.actions.setMode( this.imageModel ? 'change' : 'select' );
			if ( !noFocus ) {
				this.search.getQuery().focus().select();
			}
			// Layout pending items
			this.search.runLayoutQueue();
			break;
		default:
		case 'imageInfo':
			this.setSize( 'larger' );
			// Hide/show buttons
			this.actions.setMode( 'info' );
			// Hide/show the panels
			this.panels.setItem( this.mediaImageInfoPanel );
			break;
	}
	this.currentPanel = panel || 'imageinfo';
};

/**
 * Attach the image model to the dialog
 */
ve.ui.MWMediaDialog.prototype.attachImageModel = function () {
	if ( this.imageModel ) {
		this.imageModel.disconnect( this );
		this.sizeWidget.disconnect( this );
	}

	// Events
	this.imageModel.connect( this, {
		alignmentChange: 'onImageModelAlignmentChange',
		typeChange: 'onImageModelTypeChange',
		sizeDefaultChange: 'checkChanged'
	} );

	// Set up
	// Ignore the following changes in validation while we are
	// setting up the initial tools according to the model state
	this.isSettingUpModel = true;

	// Filename
	this.updateFilenameFieldset();

	// Size widget
	this.sizeWidget.setScalable( this.imageModel.getScalable() );
	this.sizeWidget.connect( this, {
		changeSizeType: 'onChangeSizeType',
		change: 'checkChanged',
		valid: 'checkChanged'
	} );

	// Initialize size
	this.sizeWidget.setSizeType( this.imageModel.isDefaultSize() ? 'default' : 'custom' );

	// Update default dimensions
	this.sizeWidget.updateDefaultDimensions();

	// Set initial alt text
	this.altTextInput.setValue( this.imageModel.getAltText() );

	// Set initial alignment
	this.positionSelect.setDisabled( !this.imageModel.isAligned() );
	this.positionSelect.selectItemByData( this.imageModel.isAligned() && this.imageModel.getAlignment() );
	this.positionCheckbox.setSelected( this.imageModel.isAligned() );

	// Border flag
	this.borderCheckbox.setDisabled( !this.imageModel.isBorderable() );
	this.borderCheckbox.setSelected( this.imageModel.isBorderable() && this.imageModel.hasBorder() );

	// Type select
	this.typeSelect.selectItemByData( this.imageModel.getType() || 'none' );

	this.isSettingUpModel = false;
};

/**
 * Reset the caption surface
 */
ve.ui.MWMediaDialog.prototype.resetCaption = function () {
	var doc = this.getFragment().getDocument();

	// Get existing caption. We only do this in setup, because the caption
	// should not reset to original if the image is replaced or edited.
	//
	// If the selected node is a block image and the caption already exists,
	// store the initial caption and set it as the caption document
	if (
		this.imageModel &&
		this.selectedNode &&
		this.selectedNode.getDocument() &&
		this.selectedNode instanceof ve.dm.MWBlockImageNode
	) {
		var captionNode = this.selectedNode.getCaptionNode();
		if ( captionNode && captionNode.getLength() > 0 ) {
			this.imageModel.setCaptionDocument(
				this.selectedNode.getDocument().cloneFromRange( captionNode.getRange() )
			);
		}
	}

	var captionDocument;
	if ( this.imageModel ) {
		captionDocument = this.imageModel.getCaptionDocument();
	} else {
		captionDocument = doc.cloneWithData( [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		] );
	}

	// Set document
	this.captionTarget.setDocument( captionDocument );
	this.captionTarget.setReadOnly( this.isReadOnly() );
};

/**
 * @inheritdoc
 */
ve.ui.MWMediaDialog.prototype.getReadyProcess = function ( data ) {
	return ve.ui.MWMediaDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			if ( !data.file ) {
				this.switchPanels( this.selectedNode ? 'edit' : 'search' );
			}
			// Revalidate size
			this.sizeWidget.validateDimensions();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMediaDialog.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWMediaDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			this.mediaSettingsLayout.resetScroll();
			// Cleanup
			this.search.getQuery().setValue( '' );
			this.search.teardown();
			if ( this.imageModel ) {
				this.imageModel.disconnect( this );
				this.sizeWidget.disconnect( this );
			}
			this.captionTarget.clear();
			this.imageModel = null;
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMediaDialog.prototype.getActionProcess = function ( action ) {
	var handler;

	switch ( action ) {
		case 'change':
			handler = function () {
				this.switchPanels( 'search' );
			};

			ve.track( 'activity.' + this.constructor.static.name, {
				action: 'search-change-image'
			} );
			break;
		case 'back':
			handler = function () {
				this.switchPanels( 'edit' );
			};
			break;
		case 'choose':
			handler = function () {
				this.confirmSelectedImage();
				this.switchPanels( 'edit' );
			};
			break;
		case 'cancelchoose':
			handler = function () {
				this.switchPanels( 'search' );
				if ( this.mediaUploadBooklet ) {
					// Reset upload booklet, in case we got here by uploading a file
					return this.mediaUploadBooklet.initialize();
				}
			};
			ve.track( 'activity.' + this.constructor.static.name, {
				action: 'search-change-image'
			} );
			break;
		case 'cancelupload':
			handler = function () {
				this.searchTabs.setTabPanel( 'upload' );
				this.searchTabs.toggleMenu( true );
				return this.mediaUploadBooklet.initialize();
			};
			break;
		case 'upload':
			ve.track( 'activity.' + this.constructor.static.name, {
				action: 'search-upload-image'
			} );
			return new OO.ui.Process( this.mediaUploadBooklet.uploadFile() );
		case 'save':
			return new OO.ui.Process( this.mediaUploadBooklet.saveFile() );
		case 'done':
		case 'insert':
			handler = function () {
				var surfaceModel = this.getFragment().getSurface();

				// Update from the form
				this.imageModel.setAltText( this.altTextInput.getValue() );
				this.imageModel.setCaptionDocument(
					this.captionTarget.getSurface().getModel().getDocument()
				);

				if (
					// There was an initial node
					this.selectedNode &&
					// And we didn't change the image type block/inline or vice versa
					this.selectedNode.type === this.imageModel.getImageNodeType() &&
					// And we didn't change the image itself
					this.selectedNode.getAttribute( 'src' ) ===
						this.imageModel.getImageSource()
				) {
					// We only need to update the attributes of the current node
					this.imageModel.updateImageNode( this.selectedNode, surfaceModel );
				} else {
					// Replacing an image or inserting a brand new one
					this.fragment = this.imageModel.insertImageNode( this.getFragment() );
				}

				this.close( { action: action } );
			};
			break;
		default:
			return ve.ui.MWMediaDialog.super.prototype.getActionProcess.call( this, action );
	}

	return new OO.ui.Process( handler, this );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWMediaDialog );
