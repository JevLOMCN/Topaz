/*!
 * VisualEditor user interface MWMetaDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Dialog for editing MediaWiki page information.
 *
 * @class
 * @extends ve.ui.FragmentDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWMetaDialog = function VeUiMWMetaDialog( config ) {
	// Parent constructor
	ve.ui.MWMetaDialog.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMetaDialog, ve.ui.FragmentDialog );

/* Static Properties */

ve.ui.MWMetaDialog.static.name = 'meta';

ve.ui.MWMetaDialog.static.title =
	OO.ui.deferMsg( 'visualeditor-dialog-meta-title' );

ve.ui.MWMetaDialog.static.size = 'large';

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWMetaDialog.prototype.getBodyHeight = function () {
	return 400;
};

/**
 * @inheritdoc
 */
ve.ui.MWMetaDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.MWMetaDialog.super.prototype.initialize.call( this );

	// Properties
	this.panels = new OO.ui.StackLayout();
	this.bookletLayout = new OO.ui.BookletLayout( { outlined: true } );
	this.categoriesPage = new ve.ui.MWCategoriesPage( 'categories', { $overlay: this.$overlay } );
	this.settingsPage = new ve.ui.MWSettingsPage( 'settings', { $overlay: this.$overlay } );
	this.advancedSettingsPage = new ve.ui.MWAdvancedSettingsPage( 'advancedSettings', { $overlay: this.$overlay } );
	this.languagesPage = new ve.ui.MWLanguagesPage( 'languages', { $overlay: this.$overlay } );
	this.templatesUsedPage = new ve.ui.MWTemplatesUsedPage( 'templatesUsed', { $overlay: this.$overlay } );

	// Initialization
	this.$body.append( this.panels.$element );
	this.panels.addItems( [ this.bookletLayout ] );
	this.bookletLayout.addPages( [
		this.categoriesPage,
		this.settingsPage,
		this.advancedSettingsPage,
		this.languagesPage,
		this.templatesUsedPage
	] );

	this.bookletLayout.$menu.find( '[role=listbox]' ).first().attr( 'aria-label', OO.ui.deferMsg( 'visualeditor-dialog-meta-title' ) );
	this.oldSettings = null;
	this.widgetList = this.getAllWidgets();
};

/**
 * @return {boolean} Whether settings were changed.
 */
ve.ui.MWMetaDialog.prototype.compareSettings = function () {
	var newSettings = this.extractSettings();
	return !ve.compare( newSettings, this.oldSettings );
};

/**
 * @return {Object[]} An array of objects
 * {
 *     widget: OO.ui.Widget,
 *     name: string,
 *     hasChildren: boolean
 * }
 */
ve.ui.MWMetaDialog.prototype.getAllWidgets = function () {
	var widgetList = [];

	// eslint-disable-next-line no-jquery/no-each-util
	$.each( this.bookletLayout.pages, function ( pageName, page ) {
		var fieldsets = page.getFieldsets();
		fieldsets.forEach( function ( fieldset, fieldsetIndex ) {
			fieldset.items.forEach( function ( item, itemIndex ) {
				var widget = item.fieldWidget;
				// we can recheck the value
				widgetList.push( {
					widget: widget,
					name: pageName + '/' + fieldsetIndex + '/' + itemIndex,
					hasChildren: widget.items !== undefined
				} );
			} );
		} );
	} );

	return widgetList;
};

/**
 * Assigns updateActions to all widget updates.
 */
ve.ui.MWMetaDialog.prototype.assignEvents = function () {
	var widgetList = this.getAllWidgets(),
		dialog = this;

	widgetList.forEach( function ( value ) {
		value.widget.connect( dialog, {
			change: 'updateActions',
			select: 'updateActions'
		} );
	} );
};

/**
 * @param {Object} field Widget
 * @return {string|boolean} Value of the field
 */
ve.ui.MWMetaDialog.prototype.extractValue = function ( field ) {
	if ( field instanceof OO.ui.TextInputWidget ) {
		return field.getValue();
	} else if ( field instanceof OO.ui.CheckboxInputWidget ) {
		return field.isSelected();
	} else if ( field instanceof OO.ui.ButtonOptionWidget ) {
		return field.selected;
	} else if ( field instanceof ve.ui.MWCategoryItemWidget ) {
		return {
			value: field.value,
			sortKey: field.sortKey };
	} else {
		throw new Error( 'Unhandled widget type', field );
	}
};

/**
 * @return {Object[]} An array of all widgets with their current value.
 * {
 *     name:string,
 *     value:string|boolean
 * }
 */
ve.ui.MWMetaDialog.prototype.extractSettings = function () {
	var ret = [],
		dialog = this; // return value

	this.widgetList.forEach( function ( value ) {
		if ( value.hasChildren ) {
			value.widget.items.forEach( function ( item, index ) {
				ret.push( {
					name: item.name + '/' + index,
					value: dialog.extractValue( item )
				} );
			} );
		} else {
			ret.push( {
				name: value.name,
				value: dialog.extractValue( value.widget )
			} );
		}
	} );

	return ret;
};

/**
 * Compares oldSetting with new settings and toggles the apply button accordingly.
 */
ve.ui.MWMetaDialog.prototype.updateActions = function () {
	this.actions.setAbilities( {
		done: this.settingsPage.checkValidRedirect() && this.compareSettings()
	} );
};

/**
 * @inheritdoc ve.ui.FragmentWindow
 */
ve.ui.MWMetaDialog.prototype.isEditing = function () {
	// Always in editing mode, used for setting 'done' button label.
	return true;
};

/**
 * @inheritdoc
 */
ve.ui.MWMetaDialog.prototype.getActionProcess = function ( action ) {
	var surfaceModel = this.getFragment().getSurface();

	if ( action === 'done' ) {
		return new OO.ui.Process( function () {
			surfaceModel.applyStaging();
			this.close( { action: action } );
		}, this );
	}

	return ve.ui.MWMetaDialog.super.prototype.getActionProcess.call( this, action )
		.next( function () {
			surfaceModel.popStaging();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMetaDialog.prototype.getSetupProcess = function ( data ) {
	data = data || {};
	return ve.ui.MWMetaDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var surfaceModel = this.getFragment().getSurface(),
				promises = [],
				selectWidget = this.bookletLayout.outlineSelectWidget,
				visualOnlyPages = [ 'categories', 'settings', 'advancedSettings', 'languages' ],
				isSource = ve.init.target.getSurface().getMode() === 'source';

			visualOnlyPages.forEach( function ( page ) {
				selectWidget.findItemFromData( page ).setDisabled( isSource );
			} );

			if ( isSource && visualOnlyPages.indexOf( data.page || 'categories' ) !== -1 ) {
				data.page = 'templatesUsed';
			}

			// Force all previous transactions to be separate from this history state
			surfaceModel.pushStaging();

			var config = {
				data: data,
				isReadOnly: this.isReadOnly()
			};

			// Let each page set itself up ('languages' page doesn't need this yet)
			promises.push( this.categoriesPage.setup( surfaceModel.getFragment(), config ) );
			promises.push( this.settingsPage.setup( surfaceModel.getFragment(), config ) );
			promises.push( this.advancedSettingsPage.setup( surfaceModel.getFragment(), config ) );
			return ve.promiseAll( promises );
		}, this )
		.next( function () {
			if ( data.page && this.bookletLayout.getPage( data.page ) ) {
				// HACK: Prevent the setPage() call from focussing stuff in the selected page. For the
				// 'categories' page, this causes a dropdown to appear, and if it's done in the setup
				// process, the dropdown will be misaligned (T185944). We don't pass `autoFocus: false`
				// in the config because we want the auto-focus behavior when the user changes the page
				// after the dialog is open. We focus in getReadyProcess() anyway.
				this.bookletLayout.autoFocus = false;
				this.bookletLayout.setPage( data.page );
				this.bookletLayout.autoFocus = true;
			}

			if ( this.oldSettings === null ) {
				this.assignEvents();
			}
			this.oldSettings = this.extractSettings(); // setting that were just loaded

			this.actions.setAbilities( { done: false } );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMetaDialog.prototype.getReadyProcess = function ( data ) {
	data = data || {};
	return ve.ui.MWMetaDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			if ( data.page && this.bookletLayout.getPage( data.page ) ) {
				this.bookletLayout.getPage( data.page ).focus();
			}
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMetaDialog.prototype.getTeardownProcess = function ( data ) {
	data = data || {};
	return ve.ui.MWMetaDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			// Let each page tear itself down ('languages' page doesn't need this yet)
			this.categoriesPage.teardown( { action: data.action } );
			this.settingsPage.teardown( { action: data.action } );
			this.advancedSettingsPage.teardown( { action: data.action } );

			this.bookletLayout.setPage( 'categories' );
			this.bookletLayout.resetScroll();
		}, this );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWMetaDialog );
