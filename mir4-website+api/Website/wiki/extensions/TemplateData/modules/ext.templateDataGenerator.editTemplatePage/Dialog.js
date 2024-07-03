var
	LanguageSearchWidget = require( './widgets/LanguageSearchWidget.js' ),
	Metrics = require( './Metrics.js' ),
	Model = require( 'ext.templateDataGenerator.data' ).Model,
	ParamImportWidget = require( './widgets/ParamImportWidget.js' ),
	ParamSelectWidget = require( './widgets/ParamSelectWidget.js' ),
	ParamWidget = require( './widgets/ParamWidget.js' );

/**
 * TemplateData Dialog
 *
 * @class
 * @extends OO.ui.ProcessDialog
 *
 * @constructor
 * @param {Object} config Dialog configuration object
 *
 * @external LanguageResultWidget
 */
function Dialog( config ) {
	// Parent constructor
	Dialog.parent.call( this, config );

	this.model = null;
	this.modified = false;
	this.language = null;
	this.availableLanguages = [];
	this.selectedParamKey = '';
	this.propInputs = {};
	this.propFieldLayout = {};
	this.isSetup = false;
	this.mapsCache = undefined;
	this.descriptionChanged = false;
	this.paramsReordered = false;
	this.paramPropertyChangeTracking = {};

	// Initialize
	this.$element.addClass( 'tdg-templateDataDialog' );
}

/* Inheritance */

OO.inheritClass( Dialog, OO.ui.ProcessDialog );

/* Static properties */
Dialog.static.name = 'TemplateDataDialog';
Dialog.static.title = mw.msg( 'templatedata-modal-title' );
Dialog.static.size = 'large';
Dialog.static.actions = [
	{
		action: 'apply',
		label: mw.msg( 'templatedata-modal-button-apply' ),
		flags: [ 'primary', 'progressive' ],
		modes: 'list'
	},
	{
		action: 'done',
		label: mw.msg( 'templatedata-modal-button-done' ),
		flags: [ 'primary', 'progressive' ],
		modes: [ 'edit', 'maps' ]
	},
	{
		action: 'add',
		label: mw.msg( 'templatedata-modal-button-addparam' ),
		icon: 'add',
		flags: [ 'progressive' ],
		modes: 'list'
	},
	{
		action: 'delete',
		label: mw.msg( 'templatedata-modal-button-delparam' ),
		modes: 'edit',
		flags: 'destructive'
	},
	{
		action: 'cancel',
		label: mw.msg( 'templatedata-modal-button-cancel' ),
		modes: 'maps',
		flags: 'destructive'
	},
	{
		label: mw.msg( 'templatedata-modal-button-cancel' ),
		flags: [ 'safe', 'close' ],
		modes: [ 'list', 'error' ]
	},
	{
		action: 'back',
		label: mw.msg( 'templatedata-modal-button-back' ),
		flags: [ 'safe', 'back' ],
		modes: [ 'language', 'add' ]
	}
];

/**
 * Initialize window contents.
 *
 * The first time the window is opened, #initialize is called so that changes to the window that
 * will persist between openings can be made. See #getSetupProcess for a way to make changes each
 * time the window opens.
 *
 * @throws {Error} If not attached to a manager
 * @chainable
 */
Dialog.prototype.initialize = function () {
	// Parent method
	Dialog.super.prototype.initialize.call( this );

	this.$spinner = $( '<div>' ).addClass( 'tdg-spinner' ).text( 'working...' );
	this.$body.append( this.$spinner );

	this.noticeMessage = new OO.ui.MessageWidget();
	this.noticeMessage.toggle( false );

	this.panels = new OO.ui.StackLayout( { continuous: false } );

	this.listParamsPanel = new OO.ui.PanelLayout( { padded: true, scrollable: true } );
	this.editParamPanel = new OO.ui.PanelLayout( { padded: true } );
	this.languagePanel = new OO.ui.PanelLayout();
	this.addParamPanel = new OO.ui.PanelLayout( { padded: true } );
	this.editMapsPanel = new OO.ui.PanelLayout();

	// Language panel
	this.newLanguageSearch = new LanguageSearchWidget();

	// Add parameter panel
	this.newParamInput = new OO.ui.TextInputWidget( {
		placeholder: mw.msg( 'templatedata-modal-placeholder-paramkey' )
	} );
	this.addParamButton = new OO.ui.ButtonWidget( {
		label: mw.msg( 'templatedata-modal-button-addparam' ),
		flags: [ 'progressive', 'primary' ],
		disabled: true
	} );
	var addParamFieldlayout = new OO.ui.ActionFieldLayout(
		this.newParamInput,
		this.addParamButton,
		{
			align: 'top',
			label: mw.msg( 'templatedata-modal-title-addparam' )
		}
	);

	// Maps panel
	this.templateMapsInput = new OO.ui.MultilineTextInputWidget( {
		classes: [ 'mw-templateData-template-maps-input' ],
		autosize: true,
		rows: this.getBodyHeight() / 22.5,
		maxRows: this.getBodyHeight() / 22.5,
		placeholder: mw.msg( 'templatedata-modal-placeholder-mapinfo' ),
		scrollable: true
	} );
	this.removeMapButton = new OO.ui.ButtonWidget( {
		classes: [ 'mw-templateData-template-remove-map-button' ],
		label: mw.msg( 'templatedata-modal-button-removemap' ),
		icon: 'trash',
		flags: [ 'destructive' ]
	} );
	this.addNewMapButton = new OO.ui.ButtonWidget( {
		classes: [ 'mw-templateData-template-add-map-button' ],
		label: mw.msg( 'templatedata-modal-button-addmap' ),
		icon: 'add',
		framed: false,
		flags: [ 'progressive' ]
	} );
	this.newMapNameInput = new OO.ui.TextInputWidget( {
		value: '',
		placeholder: mw.msg( 'templatedata-modal-placeholder-prompt-map-name' ),
		classes: [ 'mw-templateData-template-map-prompter' ]
	} );
	this.cancelAddMapButton = new OO.ui.ButtonWidget( {
		label: mw.msg( 'templatedata-modal-button-cancel' ),
		framed: false,
		flags: [ 'destructive' ]
	} );
	this.saveAddMapButton = new OO.ui.ButtonWidget( {
		label: mw.msg( 'templatedata-modal-button-done' ),
		framed: false,
		flags: [ 'primary', 'progressive' ]
	} );
	this.mapsGroup = new OO.ui.OutlineSelectWidget( {
		classes: [ 'mw-templateData-template-map-group' ]
	} );
	var addNewMapButtonPanel = new OO.ui.PanelLayout( {
		classes: [ 'mw-templateData-template-add-map-button-panel' ],
		padded: true,
		expanded: true
	} );
	var mapsListPanel = new OO.ui.PanelLayout( {
		expanded: true,
		scrollable: true
	} );
	var mapsListMenuLayout = new OO.ui.MenuLayout( {
		classes: [ 'mw-templateData-template-map-list-menu-panel' ],
		menuPosition: 'top',
		expanded: true,
		contentPanel: mapsListPanel,
		menuPanel: addNewMapButtonPanel
	} );
	var mapsContentPanel = new OO.ui.PanelLayout( {
		padded: true,
		expanded: true
	} );
	var templateMapsMenuLayout = new OO.ui.MenuLayout( {
		contentPanel: mapsContentPanel,
		menuPanel: mapsListMenuLayout
	} );

	// Param list panel (main)
	this.languageDropdownWidget = new OO.ui.DropdownWidget();
	this.languagePanelButton = new OO.ui.ButtonWidget( {
		label: mw.msg( 'templatedata-modal-button-add-language' ),
		flags: [ 'progressive' ]
	} );

	var languageActionFieldLayout = new OO.ui.ActionFieldLayout(
		this.languageDropdownWidget,
		this.languagePanelButton,
		{
			align: 'left',
			label: mw.msg( 'templatedata-modal-title-language' )
		}
	);

	this.descriptionInput = new OO.ui.MultilineTextInputWidget( {
		autosize: true
	} );
	this.templateDescriptionFieldset = new OO.ui.FieldsetLayout( {
		items: [ this.descriptionInput ]
	} );
	// Add Maps panel button
	this.mapsPanelButton = new OO.ui.ButtonWidget( {
		label: mw.msg( 'templatedata-modal-button-map' ),
		classes: [ 'mw-templateData-maps-panel-button' ]
	} );
	this.paramListNoticeMessage = new OO.ui.MessageWidget();
	this.paramListNoticeMessage.toggle( false );

	this.paramSelect = new ParamSelectWidget();
	this.paramImport = new ParamImportWidget();
	var templateParamsFieldset = new OO.ui.FieldsetLayout( {
		label: mw.msg( 'templatedata-modal-title-templateparams' ),
		items: [ this.paramSelect, this.paramImport ]
	} );

	this.templateFormatSelectWidget = new OO.ui.ButtonSelectWidget();
	this.templateFormatSelectWidget.addItems( [
		new OO.ui.ButtonOptionWidget( {
			data: null,
			label: mw.msg( 'templatedata-modal-format-null' )
		} ),
		new OO.ui.ButtonOptionWidget( {
			data: 'inline',
			icon: 'template-format-inline',
			label: mw.msg( 'templatedata-modal-format-inline' )
		} ),
		new OO.ui.ButtonOptionWidget( {
			data: 'block',
			icon: 'template-format-block',
			label: mw.msg( 'templatedata-modal-format-block' )
		} ),
		new OO.ui.ButtonOptionWidget( {
			data: 'custom',
			icon: 'settings',
			label: mw.msg( 'templatedata-modal-format-custom' )
		} )
	] );
	this.templateFormatInputWidget = new OO.ui.TextInputWidget( {
		placeholder: mw.msg( 'templatedata-modal-format-placeholder' )
	} );

	var templateFormatFieldSet = new OO.ui.FieldsetLayout( {
		label: mw.msg( 'templatedata-modal-title-templateformat' ),
		items: [
			new OO.ui.FieldLayout( this.templateFormatSelectWidget ),
			new OO.ui.FieldLayout( this.templateFormatInputWidget, {
				align: 'top',
				label: mw.msg( 'templatedata-modal-title-templateformatstring' )
			} )
		]
	} );

	// Param details panel
	this.$paramDetailsContainer = $( '<div>' )
		.addClass( 'tdg-templateDataDialog-paramDetails' );

	this.listParamsPanel.$element
		.addClass( 'tdg-templateDataDialog-listParamsPanel' )
		.append(
			this.paramListNoticeMessage.$element,
			languageActionFieldLayout.$element,
			this.templateDescriptionFieldset.$element,
			new OO.ui.FieldLayout( this.mapsPanelButton ).$element,
			templateFormatFieldSet.$element,
			templateParamsFieldset.$element
		);
	this.paramEditNoticeMessage = new OO.ui.MessageWidget();
	this.paramEditNoticeMessage.toggle( false );
	// Edit panel
	this.editParamPanel.$element
		.addClass( 'tdg-templateDataDialog-editParamPanel' )
		.append(
			this.paramEditNoticeMessage.$element,
			this.$paramDetailsContainer
		);
	// Language panel
	this.languagePanel.$element
		.addClass( 'tdg-templateDataDialog-languagePanel' )
		.append(
			this.newLanguageSearch.$element
		);
	this.addParamPanel.$element
		.addClass( 'tdg-templateDataDialog-addParamPanel' )
		.append( addParamFieldlayout.$element );

	// Maps panel
	mapsListPanel.$element
		.addClass( 'tdg-templateDataDialog-mapsListPanel' )
		.append( this.mapsGroup.$element );
	this.newMapNameInput.$element.hide();
	this.cancelAddMapButton.$element.hide();
	this.saveAddMapButton.$element.hide();
	addNewMapButtonPanel.$element
		.addClass( 'tdg-templateDataDialog-addNewMapButtonPanel' )
		.append(
			this.addNewMapButton.$element,
			this.newMapNameInput.$element,
			this.cancelAddMapButton.$element,
			this.saveAddMapButton.$element
		);
	mapsContentPanel.$element
		.addClass( 'tdg-templateDataDialog-mapsContentPanel' )
		.append(
			this.removeMapButton.$element,
			this.templateMapsInput.$element
		);
	this.editMapsPanel.$element
		.addClass( 'tdg-templateDataDialog-editMapsPanel' )
		.append( templateMapsMenuLayout.$element );
	this.panels.addItems( [
		this.listParamsPanel,
		this.editParamPanel,
		this.languagePanel,
		this.addParamPanel,
		this.editMapsPanel
	] );
	this.panels.setItem( this.listParamsPanel );
	this.panels.$element.addClass( 'tdg-templateDataDialog-panels' );

	// Build param details panel
	this.$paramDetailsContainer.append( this.createParamDetails() );

	// Initialization
	this.$body.append(
		this.noticeMessage.$element,
		this.panels.$element
	);

	// Events
	this.newLanguageSearch.getResults().connect( this, { choose: 'onNewLanguageSearchResultsChoose' } );
	this.newParamInput.connect( this, { change: 'onAddParamInputChange', enter: 'onAddParamButtonClick' } );
	this.addParamButton.connect( this, { click: 'onAddParamButtonClick' } );
	this.descriptionInput.connect( this, { change: 'onDescriptionInputChange' } );
	this.languagePanelButton.connect( this, { click: 'onLanguagePanelButton' } );
	this.languageDropdownWidget.getMenu().connect( this, { select: 'onLanguageDropdownWidgetSelect' } );
	this.mapsPanelButton.connect( this, { click: 'onMapsPanelButton' } );
	this.addNewMapButton.connect( this, { click: 'onAddNewMapClick' } );
	this.cancelAddMapButton.connect( this, { click: 'onCancelAddingMap' } );
	this.saveAddMapButton.connect( this, { click: 'onEmbedNewMap' } );
	this.newMapNameInput.connect( this, { enter: 'onEmbedNewMap' } );
	this.mapsGroup.connect( this, { select: 'onMapsGroupSelect' } );
	this.removeMapButton.connect( this, { click: 'onMapItemRemove' } );
	this.templateMapsInput.connect( this, { change: 'onMapInfoChange' } );
	this.paramSelect.connect( this, {
		choose: 'onParamSelectChoose',
		reorder: 'onParamSelectReorder'
	} );
	this.paramImport.connect( this, { click: 'importParametersFromTemplateCode' } );
	this.templateFormatSelectWidget.connect( this, { choose: 'onTemplateFormatSelectWidgetChoose' } );
	this.templateFormatInputWidget.connect( this, {
		change: 'onTemplateFormatInputWidgetChange',
		enter: 'onTemplateFormatInputWidgetEnter'
	} );
};

/**
 * Respond to model change of description event
 *
 * @param {string} description New description
 */
Dialog.prototype.onModelChangeDescription = function ( description ) {
	this.descriptionInput.setValue( description );
};

/**
 * Respond to model change of map info event
 *
 * @param {Object|undefined} map
 */
Dialog.prototype.onModelChangeMapInfo = function ( map ) {
	var selectedItem = this.mapsGroup.findSelectedItem();
	map = map || {};
	this.mapsCache = OO.copy( map );
	if ( selectedItem ) {
		this.templateMapsInput.setValue( this.stringifyObject( map[ selectedItem.label ] ) );
	}
};

/**
 * Respond to add param input change.
 *
 * @param {string} value New parameter name
 */
Dialog.prototype.onAddParamInputChange = function ( value ) {
	var allProps = Model.static.getAllProperties( true );

	value = value.trim();
	if ( !value ||
		value.match( allProps.name.restrict ) ||
		(
			this.model.isParamExists( value ) &&
			!this.model.isParamDeleted( value )
		)
	) {
		// Disable the add button
		this.addParamButton.setDisabled( true );
	} else {
		this.addParamButton.setDisabled( false );
	}
};

/**
 * Respond to change of param order from the model
 *
 * @param {string[]} paramOrderArray The array of keys in order
 */
Dialog.prototype.onModelChangeParamOrder = function () {
	// Refresh the parameter widget
	this.repopulateParamSelectWidget();
};

/**
 * Respond to change of param property from the model
 *
 * @param {string} paramKey Parameter key
 * @param {string} prop Property name
 * @param {Mixed} value
 * @param {string} language
 */
Dialog.prototype.onModelChangeProperty = function ( paramKey, prop, value ) {
	// Refresh the parameter widget
	if ( paramKey === this.selectedParamKey && prop === 'name' ) {
		this.selectedParamKey = value;
	}
};

/**
 * Respond to a change in the model
 */
Dialog.prototype.onModelChange = function () {
	this.modified = true;
	this.updateActions();
};

/**
 * Set action abilities according to whether the model is modified
 */
Dialog.prototype.updateActions = function () {
	this.actions.setAbilities( { apply: this.modified } );
};

/**
 * Respond to param order widget reorder event
 *
 * @param {OO.ui.OptionWidget} item Item reordered
 * @param {number} newIndex New index of the item
 */
Dialog.prototype.onParamSelectReorder = function ( item, newIndex ) {
	if ( !this.paramsReordered ) {
		Metrics.logEvent( 'parameter-reorder' );
	}
	this.paramsReordered = true;

	this.model.reorderParamOrderKey( item.getData(), newIndex );
};

/**
 * Respond to description input change event
 *
 * @param {string} value Description value
 */
Dialog.prototype.onDescriptionInputChange = function ( value ) {
	if ( !this.descriptionChanged ) {
		Metrics.logEvent( 'template-description-change' );
	}
	this.descriptionChanged = true;

	if ( this.model.getTemplateDescription( this.language ) !== value ) {
		this.model.setTemplateDescription( value, this.language );
	}
};

/**
 * Create items for the returned maps and add them to the maps group
 *
 * @param {Object|undefined} mapsObject
 */
Dialog.prototype.populateMapsItems = function ( mapsObject ) {
	mapsObject = mapsObject || {};
	var mapKeysList = Object.keys( mapsObject );

	var items = mapKeysList.map( function ( mapKey ) {
		return new OO.ui.OutlineOptionWidget( {
			label: mapKey
		} );
	} );

	this.mapsGroup.clearItems();
	this.mapsGroup.addItems( items );

	// Maps is not empty anymore
	this.updateActions();
};

/**
 * Respond to edit maps input change event
 *
 * @param {string} value map info value
 */
Dialog.prototype.onMapInfoChange = function ( value ) {
	var selectedItem = this.mapsGroup.findSelectedItem();
	// Update map Info
	this.model.maps = this.model.getMapInfo() || {};
	if ( selectedItem ) {
		if ( this.model.getMapInfo()[ selectedItem.label ] !== value ) {
			// Disable Done button in case of invalid JSON
			try {
				// This parsing method keeps only the last key/value pair if duplicate keys are defined, and does not throw an error.
				// Our model will be updated with a valid maps object, but the user may lose their input if it has duplicate key.
				this.mapsCache[ selectedItem.label ] = JSON.parse( value );
				this.actions.setAbilities( { done: true } );
			} catch ( err ) {
				// Otherwise disable the done button if maps object is populated
				this.actions.setAbilities( { done: false } );
			} finally {
				if ( this.mapsGroup.items.length === 0 ) {
					this.actions.setAbilities( { done: true } );
					this.removeMapButton.setDisabled( true );
				}
			}
		}
	}
};

/**
 * Handle click event for Add new map button
 */
Dialog.prototype.onAddNewMapClick = function () {
	// Add new text input in maps elements to prompt the map name
	this.newMapNameInput.$element.show();
	this.cancelAddMapButton.$element.show();
	this.saveAddMapButton.$element.show();
	this.addNewMapButton.$element.hide();
	this.newMapNameInput.setValue( '' );
	this.newMapNameInput.focus();
	this.mapsGroup.selectItem( null );

	// Text-area show "adding a new map.." message in templateMapsInput and disable the input.
	this.templateMapsInput.setDisabled( true );
	this.templateMapsInput.setValue( mw.msg( 'templatedata-modal-placeholder-add-new-map-input' ) );

	// Disable the removing functionality for maps
	this.removeMapButton.setDisabled( true );

	// move the list panel down as add new map expanded
	this.editMapsPanel.$element.addClass( 'tdg-templateDataDialog-addingNewMap' );
};

/**
 * Handle clicking cancel button (for add new map panel)
 *
 * @param {OO.ui.OutlineOptionWidget} [highlightNext] item to be highlighted after adding a new map canceled/done
 */
Dialog.prototype.onCancelAddingMap = function ( highlightNext ) {
	// Remove the text-area input, cancel button, and show add new map button
	this.newMapNameInput.$element.hide();
	this.cancelAddMapButton.$element.hide();
	this.saveAddMapButton.$element.hide();
	this.addNewMapButton.$element.show();
	// move the list panel up back as add new map shrank
	this.editMapsPanel.$element.removeClass( 'tdg-templateDataDialog-addingNewMap' );
	this.removeMapButton.setDisabled( false );
	this.mapsGroup.selectItem( highlightNext || this.mapsGroup.findFirstSelectableItem() );
};

/**
 * Handle clicking Enter event for promptMapName
 *
 * @param {jQuery.Event} response response from Enter action on promptMapName
 */
Dialog.prototype.onEmbedNewMap = function ( response ) {
	var mapNameValue = response ? response.target.value : this.newMapNameInput.getValue();
	this.mapsCache = this.mapsCache || {};
	// Create a new empty map in maps object
	this.mapsCache[ mapNameValue ] = {};
	var newlyAddedMap = new OO.ui.OutlineOptionWidget( {
		label: mapNameValue
	} );
	// Add the new map item and select it
	if ( mapNameValue.length !== 0 ) {
		this.mapsGroup.addItems( [ newlyAddedMap ], 0 );
	} else {
		delete this.mapsCache[ mapNameValue ];
	}
	this.onCancelAddingMap( newlyAddedMap );
};

/**
 * Handle click event for the remove button
 */
Dialog.prototype.onMapItemRemove = function () {
	var item = this.mapsGroup.findSelectedItem();
	if ( item ) {
		this.mapsGroup.removeItems( [ item ] );
		// Remove the highlighted map from maps object
		delete this.mapsCache[ item.label ];
	}

	// Highlight another item, or show the search panel if the maps group is now empty
	this.onMapsGroupSelect();
};

/**
 * Respond to a map group being selected
 */
Dialog.prototype.onMapsGroupSelect = function () {
	// Highlight new item
	var item = this.mapsGroup.findSelectedItem();

	if ( !item ) {
		this.templateMapsInput.setDisabled( true );
		this.templateMapsInput.setValue( '' );
	} else {
		// Cancel the process of adding a map, Cannot call onCancelAddingMap because these two functions
		// cannot be called recursively
		// Remove the text-area input, cancel button, and show add new map button
		this.newMapNameInput.$element.hide();
		this.cancelAddMapButton.$element.hide();
		this.saveAddMapButton.$element.hide();
		this.addNewMapButton.$element.show();
		// move the list panel up back as add new map shrank
		this.editMapsPanel.$element.removeClass( 'tdg-templateDataDialog-addingNewMap' );
		this.removeMapButton.setDisabled( $.isEmptyObject( this.mapsCache ) );

		this.mapsGroup.selectItem( item );
		this.templateMapsInput.setDisabled( false );

		// Scroll item into view in menu
		OO.ui.Element.static.scrollIntoView( item.$element[ 0 ] );

		// Populate the mapsContentPanel
		this.mapsCache = this.mapsCache || {};
		var currentMapInfo = this.mapsCache[ item.label ];
		this.templateMapsInput.setValue( this.stringifyObject( currentMapInfo ) );
	}
};

/**
 * Stringify objects in the dialog with space of 4, mainly maps objects
 *
 * @param {Object} object maps object
 * @return {string} serialized form
 */
Dialog.prototype.stringifyObject = function ( object ) {
	return JSON.stringify( object, null, 4 );
};

/**
 * Respond to add language button click
 */
Dialog.prototype.onLanguagePanelButton = function () {
	this.switchPanels( this.languagePanel );
};

/**
 * Respond to language select widget select event
 *
 * @param {OO.ui.OptionWidget} item Selected item
 */
Dialog.prototype.onLanguageDropdownWidgetSelect = function ( item ) {
	var language = item ? item.getData() : this.language;

	// Change current language
	if ( language !== this.language ) {
		this.language = language;

		// Update description label
		this.templateDescriptionFieldset.setLabel( mw.msg( 'templatedata-modal-title-templatedesc', this.language ) );

		// Update description value
		this.descriptionInput.setValue( this.model.getTemplateDescription( language ) )
			.$input.attr( { lang: mw.language.bcp47( language ), dir: 'auto' } );

		// Update all param descriptions in the param select widget
		this.repopulateParamSelectWidget();

		// Update the parameter detail page
		this.updateParamDetailsLanguage();

		this.emit( 'change-language', this.language );
	}
};

/**
 * Handle choose events from the new language search widget
 *
 * @param {OO.ui.OptionWidget} item Chosen item
 */
Dialog.prototype.onNewLanguageSearchResultsChoose = function ( item ) {
	var newLanguage = item.getData().code;

	if ( newLanguage ) {
		if ( this.availableLanguages.indexOf( newLanguage ) === -1 ) {
			// Add new language
			this.availableLanguages.push( newLanguage );
			var languageButton = new OO.ui.MenuOptionWidget( {
				data: newLanguage,
				label: $.uls.data.getAutonym( newLanguage )
			} );
			this.languageDropdownWidget.getMenu().addItems( [ languageButton ] );
		}

		// Select the new item
		this.languageDropdownWidget.getMenu().selectItemByData( newLanguage );
	}

	// Go to the main panel
	this.switchPanels();
};

/**
 * Respond to edit maps button click
 */
Dialog.prototype.onMapsPanelButton = function () {
	var item = this.mapsGroup.findSelectedItem() || this.mapsGroup.findFirstSelectableItem();
	this.switchPanels( this.editMapsPanel );
	// Select first item
	this.mapsGroup.selectItem( item );
};

/**
 * Respond to add parameter button
 */
Dialog.prototype.onAddParamButtonClick = function () {
	if ( this.addParamButton.isDisabled() ) {
		return;
	}

	var newParamKey = this.newParamInput.getValue().trim();
	if ( this.model.isParamDeleted( newParamKey ) ) {
		this.model.emptyParamData( newParamKey );
	} else if ( !this.model.isParamExists( newParamKey ) ) {
		this.model.addParam( newParamKey );
		this.addParamToSelectWidget( newParamKey );
	}
	// Reset the input
	this.newParamInput.setValue( '' );

	// Go back to list
	this.switchPanels();
};

/**
 * Respond to choose event from the param select widget
 *
 * @param {OO.ui.OptionWidget} item Parameter item
 */
Dialog.prototype.onParamSelectChoose = function ( item ) {
	var paramKey = item.getData();

	this.selectedParamKey = paramKey;

	// The panel with the `propInputs` widgets must be made visible before changing their value.
	// Otherwise the autosize feature of MultilineTextInputWidget doesn't work.
	this.switchPanels( this.editParamPanel );
	// Fill in parameter detail
	this.getParameterDetails( paramKey );
};

/**
 * Respond to choose event from the template format select widget
 *
 * @param {OO.ui.OptionWidget} item Format item
 */
Dialog.prototype.onTemplateFormatSelectWidgetChoose = function ( item ) {
	var format = item.getData(),
		shortcuts = {
			inline: '{{_|_=_}}',
			block: '{{_\n| _ = _\n}}'
		};
	if ( format !== 'custom' ) {
		this.model.setTemplateFormat( format );
		this.templateFormatInputWidget.setDisabled( true );
		if ( format !== null ) {
			this.templateFormatInputWidget.setValue(
				this.formatToDisplay( shortcuts[ format ] )
			);
		}
	} else {
		this.templateFormatInputWidget.setDisabled( false );
		this.onTemplateFormatInputWidgetChange(
			this.templateFormatInputWidget.getValue()
		);
	}
};

Dialog.prototype.formatToDisplay = function ( s ) {
	// Use '↵' (\u21b5) as a fancy newline (which doesn't start a new line).
	return s.replace( /\n/g, '\u21b5' );
};
Dialog.prototype.displayToFormat = function ( s ) {
	// Allow user to type \n or \\n (literal backslash, n) for a new line.
	return s.replace( /\n|\\n|\u21b5/g, '\n' );
};

/**
 * Respond to change event from the template format input widget
 *
 * @param {string} value Input widget value
 */
Dialog.prototype.onTemplateFormatInputWidgetChange = function ( value ) {
	var item = this.templateFormatSelectWidget.findSelectedItem();
	if ( item.getData() === 'custom' ) {
		// Convert literal newlines or backslash-n to our fancy character
		// replacement.
		var format = this.displayToFormat( value );
		var normalized = this.formatToDisplay( format );
		if ( normalized !== value ) {
			this.templateFormatInputWidget.setValue( normalized );
			// Will recurse to actually set value in model.
		} else {
			this.model.setTemplateFormat( this.displayToFormat( value.trim() ) );
		}
	}
};

/**
 * Respond to enter event from the template format input widget
 */
Dialog.prototype.onTemplateFormatInputWidgetEnter = function () {
	/* Synthesize a '\n' when enter is pressed. */
	this.templateFormatInputWidget.insertContent(
		this.formatToDisplay( '\n' )
	);
};

Dialog.prototype.onParamPropertyInputChange = function ( propName, value ) {
	var $errors = $( [] ),
		allProps = Model.static.getAllProperties( true ),
		prop = allProps[ propName ],
		propInput = this.propInputs[ propName ],
		dependentField = prop.textValue;

	if ( propName === 'type' ) {
		var selected = propInput.getMenu().findSelectedItem();
		value = selected ? selected.getData() : prop.default;
		this.toggleSuggestedValues( value );
	}

	if ( propName === 'name' ) {
		if ( value.length === 0 ) {
			$errors = $errors.add( $( '<p>' ).text( mw.msg( 'templatedata-modal-errormsg', '|', '=', '}}' ) ) );
		}
		if ( value !== this.selectedParamKey && this.model.getAllParamNames().indexOf( value ) !== -1 ) {
			// We're changing the name. Make sure it doesn't conflict.
			$errors = $errors.add( $( '<p>' ).text( mw.msg( 'templatedata-modal-errormsg-duplicate-name' ) ) );
		}
	}

	if ( prop.type === 'array' ) {
		value = propInput.getValue();
	}

	if ( prop.restrict ) {
		if ( value.match( prop.restrict ) ) {
			// Error! Don't fix the model
			$errors = $errors.add( $( '<p>' ).text( mw.msg( 'templatedata-modal-errormsg', '|', '=', '}}' ) ) );
		}
	}

	propInput.$element.toggleClass( 'tdg-editscreen-input-error', !!$errors.length );

	// Check if there is a dependent input to activate
	if ( dependentField && this.propFieldLayout[ dependentField ] ) {
		// The textValue property depends on this property
		// toggle its view
		this.propFieldLayout[ dependentField ].toggle( !!value );
		this.propInputs[ dependentField ].setValue( this.model.getParamProperty( this.selectedParamKey, dependentField ) );
	}

	// Validate
	// FIXME: Don't read model information from the DOM
	// eslint-disable-next-line no-jquery/no-global-selector
	var anyInputError = !!$( '.tdg-templateDataDialog-paramInput.tdg-editscreen-input-error' ).length;

	// Disable the 'done' button if there are any errors in the inputs
	this.actions.setAbilities( { done: !anyInputError } );
	if ( $errors.length ) {
		this.toggleNoticeMessage( 'edit', true, 'error', $errors );
	} else {
		this.toggleNoticeMessage( 'edit', false );
		this.model.setParamProperty( this.selectedParamKey, propName, value, this.language );
	}

	// If we're changing the aliases and the name has an error, poke its change
	// handler in case that error was because of a duplicate name with its own
	// aliases.
	// FIXME: Don't read model information from the DOM
	// eslint-disable-next-line no-jquery/no-class-state
	if ( propName === 'aliases' && this.propInputs.name.$element.hasClass( 'tdg-editscreen-input-error' ) ) {
		this.onParamPropertyInputChange( 'name', this.propInputs.name.getValue() );
	}

	this.trackPropertyChange( propName );
};

Dialog.prototype.toggleSuggestedValues = function ( type ) {
	var suggestedValuesAllowedTypes = [
		'content',
		'line',
		'number',
		'string',
		'unbalanced-wikitext',
		'unknown'
	];

	// Don't show the suggested values field when the feature flag is
	// disabled, or for inapplicable types.
	this.propFieldLayout.suggestedvalues.toggle(
		suggestedValuesAllowedTypes.indexOf( type ) !== -1
	);
};

/**
 * Set the parameter details in the detail panel.
 *
 * @param {string} paramKey
 */
Dialog.prototype.getParameterDetails = function ( paramKey ) {
	var paramData = this.model.getParamData( paramKey );
	var allProps = Model.static.getAllProperties( true );

	this.stopParameterInputTracking();

	for ( var prop in this.propInputs ) {
		this.changeParamPropertyInput( paramKey, prop, paramData[ prop ], this.language );
		// Show/hide dependents
		if ( allProps[ prop ].textValue ) {
			this.propFieldLayout[ allProps[ prop ].textValue ].toggle( !!paramData[ prop ] );
		}
	}
	// Update suggested values field visibility
	this.toggleSuggestedValues( paramData.type || allProps.type.default );

	var status;
	// This accepts one of the three booleans only if the other two are false
	if ( paramData.deprecated ) {
		status = !paramData.required && !paramData.suggested && 'deprecated';
	} else if ( paramData.required ) {
		status = !paramData.deprecated && !paramData.suggested && 'required';
	} else if ( paramData.suggested ) {
		status = !paramData.deprecated && !paramData.required && 'suggested';
	} else {
		status = 'optional';
	}
	// Status is false at this point when more than one was set to true
	this.propFieldLayout.status.toggle( status );
	this.propFieldLayout.deprecated.toggle( !status );
	this.propFieldLayout.required.toggle( !status );
	this.propFieldLayout.suggested.toggle( !status );
	if ( !status ) {
		// No unambiguous status found, can't use the dropdown
		this.propInputs.status.getMenu().disconnect( this );
	} else {
		this.changeParamPropertyInput( paramKey, 'status', status );
		this.propInputs.status.getMenu().connect( this, {
			choose: function ( item ) {
				var selected = item.getData();
				// Forward selection from the dropdown to the hidden checkboxes, these get saved
				this.propInputs.deprecated.setSelected( selected === 'deprecated' );
				this.propInputs.required.setSelected( selected === 'required' );
				this.propInputs.suggested.setSelected( selected === 'suggested' );
			}
		} );
	}

	this.startParameterInputTracking( paramData );
};

Dialog.prototype.stopParameterInputTracking = function () {
	this.paramPropertyChangeTracking = {};
};

/**
 * Temporary metrics to understand how properties are edited, see T260343.
 *
 * @param {Object} paramValues parameter property values at dialog open time
 */
Dialog.prototype.startParameterInputTracking = function ( paramValues ) {
	this.paramPropertyChangeTracking = {};
	for ( var prop in this.propInputs ) {
		// Set to true, unless one of the exceptions applies.
		this.paramPropertyChangeTracking[ prop ] = !(
			// Setting type when we already have a specific type.
			( prop === 'type' && paramValues[ prop ] !== undefined && paramValues[ prop ] !== 'unknown' ) ||

			// Setting priority but already required, suggested, or deprecated.
			( ( prop === 'required' || prop === 'suggested' || prop === 'deprecated' ) &&
				( paramValues.required || paramValues.suggested || paramValues.deprecated ) ) ||

			// Fields ignored by tracking.
			( prop === 'name' || prop === 'aliases' || prop === 'autovalue' || prop === 'deprecatedValue' )
		);
	}
};

Dialog.prototype.trackPropertyChange = function ( property ) {
	var eventKey = ( property === 'required' || property === 'suggested' || property === 'deprecated' ) ?
		'parameter-priority-change' : 'parameter-' + property + '-change';

	if ( this.paramPropertyChangeTracking[ property ] ) {
		Metrics.logEvent( eventKey );
	}
	this.paramPropertyChangeTracking[ property ] = false;

	// These properties form a conceptual group; suppress additional events.
	if ( property === 'required' || property === 'suggested' || property === 'deprecated' ) {
		this.paramPropertyChangeTracking.required =
			this.paramPropertyChangeTracking.suggested =
			this.paramPropertyChangeTracking.deprecated = false;
	}
};

/**
 * Reset contents on reload
 */
Dialog.prototype.reset = function () {
	this.language = null;
	this.availableLanguages = [];
	if ( this.paramSelect ) {
		this.paramSelect.clearItems();
		this.selectedParamKey = '';
	}

	if ( this.languageDropdownWidget ) {
		this.languageDropdownWidget.getMenu().clearItems();
	}
};

/**
 * Empty and repopulate the parameter select widget.
 */
Dialog.prototype.repopulateParamSelectWidget = function () {
	if ( !this.isSetup ) {
		return;
	}

	var missingParams = this.model.getMissingParams(),
		paramList = this.model.getParams(),
		paramOrder = this.model.getTemplateParamOrder();

	this.paramSelect.clearItems();

	// Update all param descriptions in the param select widget
	for ( var i in paramOrder ) {
		var paramKey = paramList[ paramOrder[ i ] ];
		if ( paramKey && !paramKey.deleted ) {
			this.addParamToSelectWidget( paramOrder[ i ] );
		}
	}

	// Check if there are potential parameters to add
	// from the template source code
	if ( missingParams.length > 0 ) {
		this.paramImport
			.toggle( true )
			.buildParamLabel( missingParams );
	} else {
		this.paramImport.toggle( false );
	}
};

/**
 * Change parameter property
 *
 * @param {string} paramKey Parameter key
 * @param {string} propName Property name
 * @param {Mixed} [value] Property value
 * @param {string} [lang] Language
 */
Dialog.prototype.changeParamPropertyInput = function ( paramKey, propName, value, lang ) {
	var allProps = Model.static.getAllProperties( true ),
		prop = allProps[ propName ],
		propInput = this.propInputs[ propName ];

	switch ( prop.type ) {
		case 'select':
			propInput = propInput.getMenu();
			propInput.selectItem( propInput.findItemFromData( value || prop.default ) );
			break;
		case 'boolean':
			propInput.setSelected( !!value );
			break;
		case 'array':
			value = value || [];
			propInput.setValue( value.map( function ( v ) {
				// TagMultiselectWidget accepts nothing but strings or objects with a .data property
				return v && v.data ? v : String( v );
			} ) );
			break;
		default:
			if ( typeof value === 'object' ) {
				value = value[ lang || this.language ];
			}
			propInput.setValue( value || '' );
	}
};

/**
 * Add parameter to the list
 *
 * @param {string} paramKey Parameter key in the model
 */
Dialog.prototype.addParamToSelectWidget = function ( paramKey ) {
	var data = this.model.getParamData( paramKey );
	this.paramSelect.addItems( [ new ParamWidget( {
		key: paramKey,
		label: this.model.getParamValue( paramKey, 'label', this.language ),
		aliases: data.aliases,
		description: this.model.getParamValue( paramKey, 'description', this.language )
	} )
		// Forward keyboard-triggered events from the OptionWidget to the SelectWidget
		.connect( this.paramSelect, { choose: [ 'emit', 'choose' ] } )
	] );
};

/**
 * Create the information page about individual parameters
 *
 * @return {jQuery} Editable details page for the parameter
 */
Dialog.prototype.createParamDetails = function () {
	var paramProperties = Model.static.getAllProperties( true );

	// Fieldset
	var paramFieldset = new OO.ui.FieldsetLayout();

	for ( var propName in paramProperties ) {
		var prop = paramProperties[ propName ];
		var propInput;
		var config = {};
		// Create the property inputs
		switch ( prop.type ) {
			case 'select':
				propInput = new OO.ui.DropdownWidget( config );
				var items = [];
				for ( var i in prop.children ) {
					items.push( new OO.ui.MenuOptionWidget( {
						data: prop.children[ i ],

						// The following messages are used here:
						// * templatedata-doc-param-status-optional
						// * templatedata-doc-param-status-deprecated
						// * templatedata-doc-param-status-required
						// * templatedata-doc-param-status-suggested
						// * templatedata-doc-param-type-boolean, templatedata-doc-param-type-content,
						// * templatedata-doc-param-type-date, templatedata-doc-param-type-line,
						// * templatedata-doc-param-type-number, templatedata-doc-param-type-string,
						// * templatedata-doc-param-type-unbalanced-wikitext, templatedata-doc-param-type-unknown,
						// * templatedata-doc-param-type-url, templatedata-doc-param-type-wiki-file-name,
						// * templatedata-doc-param-type-wiki-page-name, templatedata-doc-param-type-wiki-template-name,
						// * templatedata-doc-param-type-wiki-user-name
						label: mw.msg( 'templatedata-doc-param-' + propName + '-' + prop.children[ i ] )
					} ) );
				}
				propInput.getMenu().addItems( items );
				break;
			case 'boolean':
				propInput = new OO.ui.CheckboxInputWidget( config );
				break;
			case 'array':
				config.allowArbitrary = true;
				config.placeholder = mw.msg( 'templatedata-modal-placeholder-multiselect' );
				propInput = new OO.ui.TagMultiselectWidget( config );
				break;
			default:
				config.autosize = true;
				if ( !prop.multiline ) {
					config.rows = 1;
					config.allowLinebreaks = false;
				}
				propInput = new OO.ui.MultilineTextInputWidget( config );
				break;
		}

		this.propInputs[ propName ] = propInput;

		// The following classes are used here:
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-aliases
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-autovalue
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-default
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-deprecated
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-deprecatedValue
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-description
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-example
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-label
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-name
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-required
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-suggested
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-suggestedvalues
		// * tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-type
		propInput.$element
			.addClass( 'tdg-templateDataDialog-paramInput tdg-templateDataDialog-paramList-' + propName );

		this.propFieldLayout[ propName ] = new OO.ui.FieldLayout( propInput, {
			align: 'left',
			// The following messages are used here:
			// * templatedata-modal-table-param-aliases
			// * templatedata-modal-table-param-autovalue
			// * templatedata-modal-table-param-default
			// * templatedata-modal-table-param-deprecated
			// * templatedata-modal-table-param-deprecatedValue
			// * templatedata-modal-table-param-description
			// * templatedata-modal-table-param-example
			// * templatedata-modal-table-param-label
			// * templatedata-modal-table-param-name
			// * templatedata-modal-table-param-required
			// * templatedata-modal-table-param-suggested
			// * templatedata-modal-table-param-suggestedvalues
			// * templatedata-modal-table-param-type
			label: mw.msg( 'templatedata-modal-table-param-' + propName )
		} );

		// Event
		if ( propInput instanceof OO.ui.DropdownWidget ) {
			propInput.getMenu().connect( this, { choose: [ 'onParamPropertyInputChange', propName ] } );
		} else {
			propInput.connect( this, { change: [ 'onParamPropertyInputChange', propName ] } );
		}
		// Append to parameter section
		paramFieldset.$element.append( this.propFieldLayout[ propName ].$element );
	}
	return paramFieldset.$element;
};

/**
 * Update the labels for parameter property inputs that include language, so
 * they show the currently used language.
 */
Dialog.prototype.updateParamDetailsLanguage = function () {
	var languageProps = Model.static.getPropertiesWithLanguage();

	for ( var i = 0; i < languageProps.length; i++ ) {
		var prop = languageProps[ i ];
		// The following messages are used here:
		// * templatedata-modal-table-param-aliases
		// * templatedata-modal-table-param-autovalue
		// * templatedata-modal-table-param-default
		// * templatedata-modal-table-param-deprecated
		// * templatedata-modal-table-param-deprecatedValue
		// * templatedata-modal-table-param-description
		// * templatedata-modal-table-param-example
		// * templatedata-modal-table-param-label
		// * templatedata-modal-table-param-name
		// * templatedata-modal-table-param-required
		// * templatedata-modal-table-param-suggested
		// * templatedata-modal-table-param-suggestedvalues
		// * templatedata-modal-table-param-type
		var label = mw.msg( 'templatedata-modal-table-param-' + prop, this.language );
		this.propFieldLayout[ prop ].setLabel( label );
		this.propInputs[ prop ]
			.$input.attr( { lang: mw.language.bcp47( this.language ), dir: 'auto' } );
	}
};

/**
 * Override getBodyHeight to create a tall dialog relative to the screen.
 *
 * @return {number} Body height
 */
Dialog.prototype.getBodyHeight = function () {
	return window.innerHeight - 200;
};

/**
 * Show or hide the notice message in the dialog with a set message.
 *
 * Hides all other notices messages when called, not just the one specified.
 *
 * @param {string} [type='list'] Which notice label to show: 'list', 'edit' or 'global'
 * @param {boolean} [isShowing=false] Show or hide the message
 * @param {string} [noticeMessageType='notice'] Message type: 'notice', 'error', 'warning', 'success'
 * @param {jQuery|string|OO.ui.HtmlSnippet|Function|null} [noticeMessageLabel] The message to display
 */
Dialog.prototype.toggleNoticeMessage = function ( type, isShowing, noticeMessageType, noticeMessageLabel ) {
	// Hide all
	this.noticeMessage.toggle( false );
	this.paramEditNoticeMessage.toggle( false );
	this.paramListNoticeMessage.toggle( false );

	if ( noticeMessageLabel ) {
		// See which error to display
		var noticeReference;
		if ( type === 'global' ) {
			noticeReference = this.noticeMessage;
		} else if ( type === 'edit' ) {
			noticeReference = this.paramEditNoticeMessage;
		} else {
			noticeReference = this.paramListNoticeMessage;
		}
		// FIXME: Don't read model information from the DOM
		// eslint-disable-next-line no-jquery/no-sizzle
		isShowing = isShowing || !noticeReference.$element.is( ':visible' );

		noticeReference.setLabel( noticeMessageLabel );
		noticeReference.setType( noticeMessageType );
		noticeReference.toggle( isShowing );
	}
};

/**
 * Import parameters from the source code.
 */
Dialog.prototype.importParametersFromTemplateCode = function () {
	var $message = $( [] ),
		state = 'success',
		response = this.model.importSourceCodeParameters();
	// Repopulate the list
	this.repopulateParamSelectWidget();

	if ( response.imported.length === 0 ) {
		$message = $( '<p>' ).text( mw.msg( 'templatedata-modal-errormsg-import-noparams' ) );
		state = 'error';
	} else {
		$message = $message.add(
			$( '<p>' ).text(
				mw.msg( 'templatedata-modal-notice-import-numparams', response.imported.length, response.imported.join( mw.msg( 'comma-separator' ) ) )
			)
		);
	}

	this.toggleNoticeMessage( 'list', true, state, $message );
};

/**
 * Get a process for setting up a window for use.
 *
 * @param {Object} data Dialog opening data
 * @param {Model} data.model
 * @param {OO.ui.Element} data.editNoticeMessage
 * @return {OO.ui.Process} Setup process
 */
Dialog.prototype.getSetupProcess = function ( data ) {
	return Dialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.isSetup = false;

			this.reset();

			// The dialog must be supplied with a reference to a model
			this.model = data.model;
			this.modified = false;

			// Hide the panels and display a spinner
			this.$spinner.show();
			this.panels.$element.hide();
			this.toggleNoticeMessage( 'global', false );
			this.toggleNoticeMessage( 'list', false );

			// Start with parameter list
			this.switchPanels();

			// Events
			this.model.connect( this, {
				'change-description': 'onModelChangeDescription',
				'change-map': 'onModelChangeMapInfo',
				'change-paramOrder': 'onModelChangeParamOrder',
				'change-property': 'onModelChangeProperty',
				change: 'onModelChange'
			} );

			// Setup the dialog
			this.setupDetailsFromModel();

			this.newLanguageSearch.addResults();

			var items = [],
				defaultLanguage = this.model.getDefaultLanguage(),
				languages = this.model.getExistingLanguageCodes();

			// Bring in the editNoticeMessage from the main page
			this.listParamsPanel.$element.prepend(
				data.editNoticeMessage.$element
			);

			// Fill up the language selection
			if (
				languages.length === 0 ||
				languages.indexOf( defaultLanguage ) === -1
			) {
				// Add the default language
				items.push( new OO.ui.MenuOptionWidget( {
					data: defaultLanguage,
					label: $.uls.data.getAutonym( defaultLanguage )
				} ) );
				this.availableLanguages.push( defaultLanguage );
			}

			// Add all available languages
			for ( var i = 0; i < languages.length; i++ ) {
				items.push( new OO.ui.MenuOptionWidget( {
					data: languages[ i ],
					label: $.uls.data.getAutonym( languages[ i ] )
				} ) );
				// Store available languages
				this.availableLanguages.push( languages[ i ] );
			}
			this.languageDropdownWidget.getMenu().addItems( items );
			// Trigger the initial language choice
			this.languageDropdownWidget.getMenu().selectItemByData( defaultLanguage );

			this.isSetup = true;

			this.repopulateParamSelectWidget();

			// Show the panel
			this.$spinner.hide();
			this.panels.$element.show();

			this.actions.setAbilities( { apply: false } );
		}, this );
};

/**
 * Set up the list of parameters from the model. This should happen
 * after initialization of the model.
 */
Dialog.prototype.setupDetailsFromModel = function () {
	// Set up description
	this.descriptionInput.setValue( this.model.getTemplateDescription( this.language ) );

	// set up maps
	this.populateMapsItems( this.model.getMapInfo() );
	this.mapsCache = OO.copy( this.model.getMapInfo() );
	this.onMapsGroupSelect();
	if ( this.model.getMapInfo() !== undefined ) {
		var firstMapItem = Object.keys( this.model.getMapInfo() )[ 0 ];
		this.templateMapsInput.setValue( this.stringifyObject( this.model.getMapInfo()[ firstMapItem ] ) );
	} else {
		this.templateMapsInput.setValue( '' );
		this.templateMapsInput.setDisabled( true );
	}

	// Set up format
	var format = this.model.getTemplateFormat();
	if ( format === 'inline' || format === 'block' || format === null ) {
		this.templateFormatSelectWidget.selectItemByData( format );
		this.templateFormatInputWidget.setDisabled( true );
	} else {
		this.templateFormatSelectWidget.selectItemByData( 'custom' );
		this.templateFormatInputWidget.setValue( this.formatToDisplay( format ) );
		this.templateFormatInputWidget.setDisabled( false );
	}

	// Repopulate the parameter list
	this.repopulateParamSelectWidget();

	Metrics.logEvent( this.model.getOriginalTemplateDataObject() ?
		'dialog-open-edit' : 'dialog-open-create' );
};

/**
 * Switch between stack layout panels
 *
 * @param {OO.ui.PanelLayout} [panel] Panel to switch to, defaults to the first panel
 */
Dialog.prototype.switchPanels = function ( panel ) {
	panel = panel || this.listParamsPanel;

	this.panels.setItem( panel );
	this.listParamsPanel.$element.toggle( panel === this.listParamsPanel );
	this.editParamPanel.$element.toggle( panel === this.editParamPanel );
	this.languagePanel.$element.toggle( panel === this.languagePanel );
	this.addParamPanel.$element.toggle( panel === this.addParamPanel );
	this.editMapsPanel.$element.toggle( panel === this.editMapsPanel );

	switch ( panel ) {
		case this.listParamsPanel:
			this.actions.setMode( 'list' );
			// Reset message
			this.toggleNoticeMessage( 'list', false );
			// Deselect parameter
			this.paramSelect.selectItem( null );
			// Repopulate the list to account for any changes
			if ( this.model ) {
				this.repopulateParamSelectWidget();
			}
			break;
		case this.editParamPanel:
			this.actions.setMode( 'edit' );
			// Deselect parameter
			this.paramSelect.selectItem( null );
			this.editParamPanel.focus();
			break;
		case this.addParamPanel:
			this.actions.setMode( 'add' );
			this.newParamInput.focus();
			break;
		case this.editMapsPanel:
			this.actions.setMode( 'maps' );
			this.templateMapsInput.adjustSize( true ).focus();
			break;
		case this.languagePanel:
			this.actions.setMode( 'language' );
			this.newLanguageSearch.query.focus();
			break;
	}
};

/**
 * Get a process for taking action.
 *
 * @param {string} [action] Symbolic name of action
 * @return {OO.ui.Process} Action process
 */
Dialog.prototype.getActionProcess = function ( action ) {
	if ( action === 'add' ) {
		return new OO.ui.Process( function () {
			this.switchPanels( this.addParamPanel );
		}, this );
	}
	if ( action === 'done' ) {
		return new OO.ui.Process( function () {
			// setMapInfo with the value and keep the done button active
			this.model.setMapInfo( this.mapsCache );
			this.model.originalMaps = OO.copy( this.mapsCache );
			this.switchPanels();
		}, this );
	}
	if ( action === 'back' ) {
		return new OO.ui.Process( function () {
			this.switchPanels();
		}, this );
	}
	if ( action === 'maps' ) {
		return new OO.ui.Process( function () {
			this.switchPanels( this.editMapsPanel );
		}, this );
	}
	if ( action === 'cancel' ) {
		return new OO.ui.Process( function () {
			this.mapsCache = OO.copy( this.model.getOriginalMapsInfo() );
			this.model.restoreOriginalMaps();
			this.populateMapsItems( this.mapsCache );
			this.onCancelAddingMap();
			this.switchPanels();
		}, this );
	}
	if ( action === 'delete' ) {
		return new OO.ui.Process( function () {
			this.model.deleteParam( this.selectedParamKey );
			this.switchPanels();
		}, this );
	}
	if ( action === 'apply' ) {
		return new OO.ui.Process( function () {
			Metrics.logEvent( this.model.getOriginalTemplateDataObject() ?
				'save-page-edit' : 'save-page-create' );

			this.emit( 'apply', this.model.outputTemplateData() );
			this.close( { action: action } );
		}, this );
	}
	if ( !action && this.modified ) {
		return new OO.ui.Process( function () {
			var dialog = this;
			return OO.ui.confirm( mw.msg( 'templatedata-modal-confirmcancel' ) )
				.then( function ( result ) {
					if ( result ) {
						dialog.close();
					} else {
						return $.Deferred().resolve().promise();
					}
				} );
		}, this );
	}
	// Fallback to parent handler
	return Dialog.super.prototype.getActionProcess.call( this, action );
};

module.exports = Dialog;
