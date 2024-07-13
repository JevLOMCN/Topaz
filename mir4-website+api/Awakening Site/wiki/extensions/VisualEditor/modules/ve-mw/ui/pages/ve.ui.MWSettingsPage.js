/*!
 * VisualEditor user interface MWSettingsPage class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki meta dialog settings page.
 *
 * @class
 * @extends OO.ui.PageLayout
 *
 * @constructor
 * @param {string} name Unique symbolic name of page
 * @param {Object} [config] Configuration options
 * @cfg {jQuery} [$overlay] Overlay to render dropdowns in
 */
ve.ui.MWSettingsPage = function VeUiMWSettingsPage( name, config ) {
	var settingsPage = this;

	// Parent constructor
	ve.ui.MWSettingsPage.super.apply( this, arguments );

	// Properties
	this.fragment = null;
	this.tocOptionTouched = false;
	this.redirectOptionsTouched = false;
	this.tableOfContentsTouched = false;
	this.label = ve.msg( 'visualeditor-dialog-meta-settings-section' );

	this.settingsFieldset = new OO.ui.FieldsetLayout( {
		label: ve.msg( 'visualeditor-dialog-meta-settings-label' ),
		icon: 'pageSettings'
	} );

	// Initialization

	// Table of Contents items
	this.tableOfContents = new OO.ui.FieldLayout(
		new OO.ui.ButtonSelectWidget( {
			classes: [ 've-test-page-settings-table-of-contents' ]
		} )
			.addItems( [
				new OO.ui.ButtonOptionWidget( {
					data: 'mw:PageProp/forcetoc',
					label: ve.msg( 'visualeditor-dialog-meta-settings-toc-force' )
				} ),
				new OO.ui.ButtonOptionWidget( {
					data: 'default',
					label: ve.msg( 'visualeditor-dialog-meta-settings-toc-default' )
				} ),
				new OO.ui.ButtonOptionWidget( {
					data: 'mw:PageProp/notoc',
					label: ve.msg( 'visualeditor-dialog-meta-settings-toc-disable' )
				} )
			] )
			.connect( this, { select: 'onTableOfContentsFieldChange' } ),
		{
			$overlay: config.$overlay,
			align: 'top',
			label: ve.msg( 'visualeditor-dialog-meta-settings-toc-label' ),
			help: ve.msg( 'visualeditor-dialog-meta-settings-toc-help' )
		}
	);

	// Redirect items
	this.enableRedirectInput = new OO.ui.CheckboxInputWidget();
	this.enableRedirectField = new OO.ui.FieldLayout(
		this.enableRedirectInput,
		{
			$overlay: config.$overlay,
			classes: [ 've-test-page-settings-enable-redirect' ],
			align: 'inline',
			label: ve.msg( 'visualeditor-dialog-meta-settings-redirect-label' ),
			help: ve.msg( 'visualeditor-dialog-meta-settings-redirect-help' )
		}
	);
	this.redirectTargetInput = new mw.widgets.TitleInputWidget( {
		placeholder: ve.msg( 'visualeditor-dialog-meta-settings-redirect-placeholder' ),
		$overlay: config.$overlay,
		api: ve.init.target.getContentApi()
	} );
	this.redirectTargetInput.$input.attr( 'aria-label', ve.msg( 'visualeditor-dialog-meta-settings-redirect-placeholder' ) );

	this.redirectTargetField = new OO.ui.FieldLayout(
		this.redirectTargetInput,
		{ align: 'top' }
	);
	this.enableStaticRedirectInput = new OO.ui.CheckboxInputWidget();
	this.enableStaticRedirectField = new OO.ui.FieldLayout(
		this.enableStaticRedirectInput,
		{
			$overlay: config.$overlay,
			classes: [ 've-test-page-settings-prevent-redirect' ],
			align: 'inline',
			label: ve.msg( 'visualeditor-dialog-meta-settings-redirect-staticlabel' ),
			help: ve.msg( 'visualeditor-dialog-meta-settings-redirect-statichelp' )
		}
	);
	this.enableRedirectInput.connect( this, { change: 'onEnableRedirectChange' } );
	this.redirectTargetInput.connect( this, { change: 'onRedirectTargetChange' } );
	this.enableStaticRedirectInput.connect( this, { change: 'onEnableStaticRedirectChange' } );

	this.metaItemCheckboxes = [
		{
			metaName: 'mwNoEditSection',
			label: ve.msg( 'visualeditor-dialog-meta-settings-noeditsection-label' ),
			help: ve.msg( 'visualeditor-dialog-meta-settings-noeditsection-help' ),
			classes: [ 've-test-page-settings-noeditsection' ]
		}
	].concat( ve.ui.MWSettingsPage.static.extraMetaCheckboxes );

	if ( mw.config.get( 'wgNamespaceNumber' ) === mw.config.get( 'wgNamespaceIds' ).category ) {
		this.metaItemCheckboxes.push(
			{
				metaName: 'mwHiddenCategory',
				label: ve.msg( 'visualeditor-dialog-meta-settings-hiddencat-label' ),
				help: ve.msg( 'visualeditor-dialog-meta-settings-hiddencat-help' )
			},
			{
				metaName: 'mwNoGallery',
				label: ve.msg( 'visualeditor-dialog-meta-settings-nogallery-label' ),
				help: ve.msg( 'visualeditor-dialog-meta-settings-nogallery-help' )
			}
		);
	}

	this.settingsFieldset.addItems( [
		this.enableRedirectField,
		this.redirectTargetField,
		this.enableStaticRedirectField,
		this.tableOfContents
	] );

	this.metaItemCheckboxes.forEach( function ( metaItemCheckbox ) {
		metaItemCheckbox.fieldLayout = new OO.ui.FieldLayout(
			new OO.ui.CheckboxInputWidget(),
			// See above for classes
			// eslint-disable-next-line mediawiki/class-doc
			{
				$overlay: config.$overlay,
				classes: metaItemCheckbox.classes,
				align: 'inline',
				label: metaItemCheckbox.label,
				help: metaItemCheckbox.help || ''
			}
		);
		settingsPage.settingsFieldset.addItems( [ metaItemCheckbox.fieldLayout ] );
	} );

	this.$element.append( this.settingsFieldset.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWSettingsPage, OO.ui.PageLayout );

/* Allow extra meta item checkboxes to be added by extensions etc. */
ve.ui.MWSettingsPage.static.extraMetaCheckboxes = [];

/**
 * Add a checkbox to the list of changeable page settings
 *
 * @param {string} metaName The name of the DM meta item
 * @param {string} label The label to show next to the checkbox
 */
ve.ui.MWSettingsPage.static.addMetaCheckbox = function ( metaName, label ) {
	this.extraMetaCheckboxes.push( { metaName: metaName, label: label } );
};

/* Methods */

/* Table of Contents methods */

/**
 * @inheritdoc
 */
ve.ui.MWSettingsPage.prototype.setupOutlineItem = function () {
	this.outlineItem
		.setIcon( 'pageSettings' )
		.setLabel( ve.msg( 'visualeditor-dialog-meta-settings-section' ) );
};

/**
 * Handle Table Of Contents display change events.
 */
ve.ui.MWSettingsPage.prototype.onTableOfContentsFieldChange = function () {
	this.tableOfContentsTouched = true;
};

/* Redirect methods */

/**
 * Handle redirect state change events.
 *
 * @param {boolean} value Whether a redirect is to be set for this page
 */
ve.ui.MWSettingsPage.prototype.onEnableRedirectChange = function ( value ) {
	var page = this;
	this.redirectTargetInput.setDisabled( !value );
	this.enableStaticRedirectInput.setDisabled( !value );
	if ( value ) {
		/*
		 * HACK: When editing a page which has a redirect, the meta dialog
		 * automatically opens with the settings page's redirect field focused.
		 * When this happens, we don't want the lookup dropdown to appear until
		 * the user actually does something.
		 * Using setTimeout because we need to defer this until after the
		 * dialog has opened - otherwise its internal lookupDisabled logic will
		 * fail to have any effect during the actual focusing and calling of
		 * OO.ui.LookupElement#onLookupInputFocus/OO.ui.LookupElement#populateLookupMenu.
		 * https://phabricator.wikimedia.org/T137309
		 */
		setTimeout( function () {
			page.redirectTargetInput.focus();
		} );
	} else {
		this.redirectTargetInput.setValue( '' );
		this.enableStaticRedirectInput.setSelected( false );
	}
	this.redirectOptionsTouched = true;
};

/**
 * @return {boolean} Whether redirect link is valid.
 */
ve.ui.MWSettingsPage.prototype.checkValidRedirect = function () {
	if ( this.enableRedirectInput.isSelected() ) {
		var title = this.redirectTargetInput.getValue();

		if ( !mw.Title.newFromText( title ) ) {
			/*
			 * TODO more precise error message. Modify the Title.newFromText method in Title.js
			 * my idea is to in the parse method instead of a boolean return a string with an error message (not an error code since the error string can have parameters),
			 * then in Title.newFromText instead of returning null, return the error string. Use that string there in setErrors.
			 * Problem: some methods might depend on it returning null.
			 * Solution: either make it a new method (Title.newFromTextThrow), or add a an optional parameter to return the error message.
			 */
			this.redirectTargetField.setErrors( [ mw.msg( 'visualeditor-title-error' ) ] );
			return false;

		} else {
			this.redirectTargetField.setErrors( [] );
		}
	} else {
		this.redirectTargetField.setErrors( [] );
	}

	return true;
};

/**
 * Handle redirect target change events.
 */
ve.ui.MWSettingsPage.prototype.onRedirectTargetChange = function () {
	this.redirectOptionsTouched = true;
};

/**
 * Handle static redirect state change events.
 */
ve.ui.MWSettingsPage.prototype.onEnableStaticRedirectChange = function () {
	this.redirectOptionsTouched = true;
};

/**
 * Get the first meta item of a given name
 *
 * @param {string} name Name of the meta item
 * @return {Object|null} Meta item, if any
 */
ve.ui.MWSettingsPage.prototype.getMetaItem = function ( name ) {
	return this.fragment.getDocument().getMetaList().getItemsInGroup( name )[ 0 ] || null;
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
ve.ui.MWSettingsPage.prototype.setup = function ( fragment, config ) {
	var settingsPage = this;

	this.fragment = fragment;

	// Table of Contents items
	var tableOfContentsField = this.tableOfContents.getField();
	var tableOfContentsMetaItem = this.getMetaItem( 'mwTOC' );
	var tableOfContentsMode = tableOfContentsMetaItem && tableOfContentsMetaItem.getAttribute( 'property' ) || 'default';
	tableOfContentsField
		.selectItemByData( tableOfContentsMode )
		.setDisabled( config.isReadOnly );
	this.tableOfContentsTouched = false;

	// Redirect items (disabled states set by change event)
	var redirectTargetItem = this.getMetaItem( 'mwRedirect' );
	var redirectTarget = redirectTargetItem && redirectTargetItem.getAttribute( 'title' ) || '';
	var redirectStatic = this.getMetaItem( 'mwStaticRedirect' );
	this.enableRedirectInput
		.setSelected( !!redirectTargetItem )
		.setDisabled( config.isReadOnly );
	this.redirectTargetInput
		.setValue( redirectTarget )
		.setDisabled( !redirectTargetItem )
		.setReadOnly( config.isReadOnly );
	this.enableStaticRedirectInput
		.setSelected( !!redirectStatic )
		.setDisabled( !redirectTargetItem || config.isReadOnly );
	this.redirectOptionsTouched = false;

	// Simple checkbox items
	this.metaItemCheckboxes.forEach( function ( metaItemCheckbox ) {
		var isSelected = !!settingsPage.getMetaItem( metaItemCheckbox.metaName );
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
ve.ui.MWSettingsPage.prototype.teardown = function ( data ) {
	var settingsPage = this;

	// Data initialisation
	data = data || {};
	if ( data.action !== 'done' ) {
		return;
	}

	// Table of Contents items
	var currentTableOfContents = this.getMetaItem( 'mwTOC' );
	var newTableOfContentsData = this.tableOfContents.getField().findSelectedItem();

	// Redirect items
	var currentRedirectTargetItem = this.getMetaItem( 'mwRedirect' );
	var newRedirectData = this.redirectTargetInput.getValue();
	var newRedirectItemData = { type: 'mwRedirect', attributes: { title: newRedirectData } };

	var currentStaticRedirectItem = this.getMetaItem( 'mwStaticRedirect' );
	var newStaticRedirectState = this.enableStaticRedirectInput.isSelected();

	// Alter the TOC option flag iff it's been touched & is actually different
	if ( this.tableOfContentsTouched ) {
		if ( newTableOfContentsData.data === 'default' ) {
			if ( currentTableOfContents ) {
				this.fragment.removeMeta( currentTableOfContents );
			}
		} else {
			var newTableOfContentsItem = { type: 'mwTOC', attributes: { property: newTableOfContentsData.data } };

			if ( !currentTableOfContents ) {
				this.fragment.insertMeta( newTableOfContentsItem );
			} else if ( currentTableOfContents.getAttribute( 'property' ) !== newTableOfContentsData.data ) {
				this.fragment.replaceMeta(
					currentTableOfContents,
					ve.extendObject( true, {}, currentTableOfContents.getElement(), newTableOfContentsItem )
				);
			}
		}
	}

	// Alter the redirect options iff they've been touched & are different
	if ( this.redirectOptionsTouched ) {
		if ( currentRedirectTargetItem ) {
			if ( newRedirectData ) {
				if ( currentRedirectTargetItem.getAttribute( 'title' ) !== newRedirectData ) {
					// There was a redirect and is a new one, but they differ, so replace
					this.fragment.replaceMeta(
						currentRedirectTargetItem,
						ve.extendObject( true, {},
							currentRedirectTargetItem.getElement(),
							newRedirectItemData
						)
					);
				}
			} else {
				// There was a redirect and is no new one, so remove
				this.fragment.removeMeta( currentRedirectTargetItem );
			}
		} else {
			if ( newRedirectData ) {
				// There's no existing redirect but there is a new one, so create
				// HACK: Putting this at position 0 so that it works – T63862
				this.fragment.insertMeta( newRedirectItemData, 0 );
			}
		}

		if ( currentStaticRedirectItem && ( !newStaticRedirectState || !newRedirectData ) ) {
			this.fragment.removeMeta( currentStaticRedirectItem );
		}
		if ( !currentStaticRedirectItem && newStaticRedirectState && newRedirectData ) {
			this.fragment.insertMeta( { type: 'mwStaticRedirect' } );
		}
	}

	this.metaItemCheckboxes.forEach( function ( metaItemCheckbox ) {
		var currentItem = settingsPage.getMetaItem( metaItemCheckbox.metaName ),
			isSelected = metaItemCheckbox.fieldLayout.getField().isSelected();

		if ( currentItem && !isSelected ) {
			this.fragment.removeMeta( currentItem );
		} else if ( !currentItem && isSelected ) {
			settingsPage.fragment.insertMeta( { type: metaItemCheckbox.metaName } );
		}
	} );

	this.fragment = null;
};

ve.ui.MWSettingsPage.prototype.getFieldsets = function () {
	return [
		this.settingsFieldset
	];
};
