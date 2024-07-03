/*!
 * VisualEditor user interface MWAdvancedSettingsPage class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki meta dialog advanced settings page.
 *
 * @class
 * @extends OO.ui.PageLayout
 *
 * @constructor
 * @param {string} name Unique symbolic name of page
 * @param {Object} [config] Configuration options
 * @cfg {jQuery} [$overlay] Overlay to render dropdowns in
 */
ve.ui.MWAdvancedSettingsPage = function VeUiMWAdvancedSettingsPage( name, config ) {
	var advancedSettingsPage = this;

	// Parent constructor
	ve.ui.MWAdvancedSettingsPage.super.apply( this, arguments );

	// Properties
	this.fragment = null;
	this.indexingOptionTouched = false;
	this.newSectionEditLinkOptionTouched = false;

	this.advancedSettingsFieldset = new OO.ui.FieldsetLayout( {
		label: ve.msg( 'visualeditor-dialog-meta-advancedsettings-label' ),
		icon: 'settings'
	} );

	// Initialization

	// Indexing items
	this.indexing = new OO.ui.FieldLayout(
		new OO.ui.ButtonSelectWidget()
			.addItems( [
				new OO.ui.ButtonOptionWidget( {
					data: 'mw:PageProp/index',
					label: ve.msg( 'visualeditor-dialog-meta-settings-index-force' )
				} ),
				new OO.ui.ButtonOptionWidget( {
					data: 'default',
					label: ve.msg( 'visualeditor-dialog-meta-settings-index-default' )
				} ),
				new OO.ui.ButtonOptionWidget( {
					data: 'mw:PageProp/noindex',
					label: ve.msg( 'visualeditor-dialog-meta-settings-index-disable' )
				} )
			] )
			.connect( this, { select: 'onIndexingOptionChange' } ),
		{
			$overlay: config.$overlay,
			align: 'top',
			label: ve.msg( 'visualeditor-dialog-meta-settings-index-label' ),
			help: ve.msg( 'visualeditor-dialog-meta-settings-index-help' )
		}
	);

	// New edit section link items
	this.newEditSectionLink = new OO.ui.FieldLayout(
		new OO.ui.ButtonSelectWidget()
			.addItems( [
				new OO.ui.ButtonOptionWidget( {
					data: 'mw:PageProp/newsectionlink',
					label: ve.msg( 'visualeditor-dialog-meta-settings-newsectioneditlink-force' )
				} ),
				new OO.ui.ButtonOptionWidget( {
					data: 'default',
					label: ve.msg( 'visualeditor-dialog-meta-settings-newsectioneditlink-default' )
				} ),
				new OO.ui.ButtonOptionWidget( {
					data: 'mw:PageProp/nonewsectionlink',
					label: ve.msg( 'visualeditor-dialog-meta-settings-newsectioneditlink-disable' )
				} )
			] )
			.connect( this, { select: 'onNewSectionEditLinkOptionChange' } ),
		{
			$overlay: config.$overlay,
			align: 'top',
			label: ve.msg( 'visualeditor-dialog-meta-settings-newsectioneditlink-label' ),
			// eslint-disable-next-line no-jquery/no-global-selector
			help: ve.msg( 'visualeditor-dialog-meta-settings-newsectioneditlink-help', $( '#ca-edit' ).text() )
		}
	);

	this.displayTitleTouched = false;
	this.displayTitleInput = new OO.ui.TextInputWidget();
	this.displayTitleInput.connect( this, { change: 'onDisplayTitleInputChange' } );
	this.displayTitleField = new OO.ui.FieldLayout(
		this.displayTitleInput,
		{
			$overlay: config.$overlay,
			align: 'top',
			label: ve.msg( 'visualeditor-dialog-meta-settings-displaytitle' ),
			help: ve.msg( 'visualeditor-dialog-meta-settings-displaytitle-help' )
		}
	);

	this.advancedSettingsFieldset.addItems( [ this.indexing, this.newEditSectionLink, this.displayTitleField ] );

	this.metaItemCheckboxes = [];
	if ( mw.config.get( 'wgVariantArticlePath' ) ) {
		this.metaItemCheckboxes.push(
			{
				metaName: 'mwNoContentConvert',
				label: ve.msg( 'visualeditor-dialog-meta-settings-nocontentconvert-label' ),
				help: ve.msg( 'visualeditor-dialog-meta-settings-nocontentconvert-help' )
			},
			{
				metaName: 'mwNoTitleConvert',
				label: ve.msg( 'visualeditor-dialog-meta-settings-notitleconvert-label' ),
				help: ve.msg( 'visualeditor-dialog-meta-settings-notitleconvert-help' )
			}
		);
	}

	this.metaItemCheckboxes.forEach( function ( metaItemCheckbox ) {
		metaItemCheckbox.fieldLayout = new OO.ui.FieldLayout(
			new OO.ui.CheckboxInputWidget(),
			{
				$overlay: config.$overlay,
				align: 'inline',
				label: metaItemCheckbox.label,
				help: metaItemCheckbox.help
			}
		);
		advancedSettingsPage.advancedSettingsFieldset.addItems( [ metaItemCheckbox.fieldLayout ] );
	} );

	this.$element.append( this.advancedSettingsFieldset.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWAdvancedSettingsPage, OO.ui.PageLayout );

/* Methods */

/**
 * Handle display title input change events.
 *
 * @param {string} value Current value of input widget
 */
ve.ui.MWAdvancedSettingsPage.prototype.onDisplayTitleInputChange = function () {
	this.displayTitleTouched = true;
};

/**
 * @inheritdoc
 */
ve.ui.MWAdvancedSettingsPage.prototype.setupOutlineItem = function () {
	this.outlineItem
		.setIcon( 'settings' )
		.setLabel( ve.msg( 'visualeditor-dialog-meta-advancedsettings-section' ) );
};

/* Indexing option methods */

/**
 * Handle indexing option state change events.
 */
ve.ui.MWAdvancedSettingsPage.prototype.onIndexingOptionChange = function () {
	this.indexingOptionTouched = true;
};

/**
 * Get the first meta item of a given name
 *
 * @param {string} name Name of the meta item
 * @return {Object|null} Meta item, if any
 */
ve.ui.MWAdvancedSettingsPage.prototype.getMetaItem = function ( name ) {
	return this.fragment.getDocument().getMetaList().getItemsInGroup( name )[ 0 ] || null;
};

/* New edit section link option methods */

/**
 * Handle new edit section link change events.
 */
ve.ui.MWAdvancedSettingsPage.prototype.onNewSectionEditLinkOptionChange = function () {
	this.newSectionEditLinkOptionTouched = true;
};

/**
 * Setup settings page.
 *
 * @param {ve.dm.SurfaceFragment} fragment Surface fragment
 * @param {Object} config
 * @param {Object} [config.data] Dialog setup data
 * @param {boolean} [config.isReadOnly=false] Dialog is in read-only mode
 * @return {jQuery.Promise}
 */
ve.ui.MWAdvancedSettingsPage.prototype.setup = function ( fragment, config ) {
	var advancedSettingsPage = this;

	this.fragment = fragment;

	// Indexing items
	var indexingField = this.indexing.getField();
	var indexingOption = this.getMetaItem( 'mwIndex' );
	var indexingType = indexingOption && indexingOption.getAttribute( 'property' ) || 'default';
	indexingField
		.selectItemByData( indexingType )
		.setDisabled( config.isReadOnly );
	this.indexingOptionTouched = false;

	// New section edit link items
	var newSectionEditField = this.newEditSectionLink.getField();
	var newSectionEditLinkOption = this.getMetaItem( 'mwNewSectionEdit' );
	var newSectionEditLinkType = newSectionEditLinkOption && newSectionEditLinkOption.getAttribute( 'property' ) || 'default';
	newSectionEditField
		.selectItemByData( newSectionEditLinkType )
		.setDisabled( config.isReadOnly );
	this.newSectionEditLinkOptionTouched = false;

	// Display title items
	var displayTitleItem = this.getMetaItem( 'mwDisplayTitle' );
	var displayTitle = displayTitleItem && displayTitleItem.getAttribute( 'content' ) || '';
	if ( !displayTitle ) {
		displayTitle = mw.Title.newFromText( ve.init.target.getPageName() ).getPrefixedText();
	}
	this.displayTitleInput
		.setValue( displayTitle )
		.setReadOnly( config.isReadOnly );
	this.displayTitleTouched = false;

	// Simple checkbox items
	this.metaItemCheckboxes.forEach( function ( metaItemCheckbox ) {
		var isSelected = !!advancedSettingsPage.getMetaItem( metaItemCheckbox.metaName );
		metaItemCheckbox.fieldLayout.getField()
			.setSelected( isSelected )
			.setDisabled( config.isReadOnly );
	} );

	return ve.createDeferred().resolve().promise();
};

/**
 * Tear down settings page.
 *
 * @param {Object} [data] Dialog tear down data
 */
ve.ui.MWAdvancedSettingsPage.prototype.teardown = function ( data ) {
	var advancedSettingsPage = this;

	// Data initialization
	data = data || {};
	if ( data.action !== 'done' ) {
		return;
	}

	// Indexing items
	var currentIndexingItem = this.getMetaItem( 'mwIndex' );
	var newIndexingData = this.indexing.getField().findSelectedItem();

	// Alter the indexing option flag iff it's been touched & is actually different
	if ( this.indexingOptionTouched ) {
		if ( newIndexingData.data === 'default' ) {
			if ( currentIndexingItem ) {
				this.fragment.removeMeta( currentIndexingItem );
			}
		} else {
			var newIndexingItem = { type: 'mwIndex', attributes: { property: newIndexingData.data } };

			if ( !currentIndexingItem ) {
				this.fragment.insertMeta( newIndexingItem );
			} else if ( currentIndexingItem.getAttribute( 'property' ) !== newIndexingData.data ) {
				this.fragment.replaceMeta(
					currentIndexingItem,
					ve.extendObject( true, {}, currentIndexingItem.getElement(), newIndexingItem )
				);
			}
		}
	}

	// New section edit link items
	var currentNewSectionEditLinkItem = this.getMetaItem( 'mwNewSectionEdit' );
	var newNewSectionEditLinkData = this.newEditSectionLink.getField().findSelectedItem();

	// Alter the new section edit option flag iff it's been touched & is actually different
	if ( this.newSectionEditLinkOptionTouched ) {
		if ( newNewSectionEditLinkData.data === 'default' ) {
			if ( currentNewSectionEditLinkItem ) {
				this.fragment.removeMeta( currentNewSectionEditLinkItem );
			}
		} else {
			var newNewSectionEditLinkItem = { type: 'mwNewSectionEdit', attributes: { property: newNewSectionEditLinkData.data } };

			if ( !currentNewSectionEditLinkItem ) {
				this.fragment.insertMeta( newNewSectionEditLinkItem );
			} else if ( currentNewSectionEditLinkItem.getAttribute( 'property' ) !== newNewSectionEditLinkData.data ) {
				this.fragment.replaceMeta(
					currentNewSectionEditLinkItem,
					ve.extendObject( true, {}, currentNewSectionEditLinkItem.getElement(), newNewSectionEditLinkItem )
				);
			}
		}
	}

	// Display title items
	var currentDisplayTitleItem = this.getMetaItem( 'mwDisplayTitle' );
	var newDisplayTitle = this.displayTitleInput.getValue();
	if ( newDisplayTitle === mw.Title.newFromText( ve.init.target.getPageName() ).getPrefixedText() ) {
		newDisplayTitle = '';
	}
	var newDisplayTitleItem = { type: 'mwDisplayTitle', attributes: { content: newDisplayTitle } };

	// Alter the display title flag iff it's been touched & is actually different
	if ( this.displayTitleTouched ) {
		if ( currentDisplayTitleItem ) {
			if ( newDisplayTitle ) {
				if ( currentDisplayTitleItem.getAttribute( 'content' ) !== newDisplayTitle ) {
					// There was a display title and is a new one, but they differ, so replace
					this.fragment.replaceMeta(
						currentDisplayTitleItem,
						ve.extendObject( true, {},
							currentDisplayTitleItem.getElement(),
							newDisplayTitleItem
						)
					);
				}
			} else {
				// There was a display title and is no new one, so remove
				this.fragment.removeMeta( currentDisplayTitleItem );
			}
		} else {
			if ( newDisplayTitle ) {
				// There's no existing display title but there is a new one, so create
				// HACK: Putting this at position 0 so that it works – T63862
				this.fragment.insertMeta( newDisplayTitleItem, 0 );
			}
		}
	}

	this.metaItemCheckboxes.forEach( function ( metaItemCheckbox ) {
		var currentItem = advancedSettingsPage.getMetaItem( metaItemCheckbox.metaName ),
			isSelected = metaItemCheckbox.fieldLayout.getField().isSelected();

		if ( currentItem && !isSelected ) {
			advancedSettingsPage.fragment.removeMeta( currentItem );
		} else if ( !currentItem && isSelected ) {
			advancedSettingsPage.fragment.insertMeta( { type: metaItemCheckbox.metaName } );
		}
	} );

	this.fragment = null;
};

ve.ui.MWAdvancedSettingsPage.prototype.getFieldsets = function () {
	return [
		this.advancedSettingsFieldset
	];
};
