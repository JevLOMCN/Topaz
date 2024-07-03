/*!
 * VisualEditor user interface MWCategoriesPage class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki meta dialog categories page.
 *
 * @class
 * @extends OO.ui.PageLayout
 *
 * @constructor
 * @param {string} name Unique symbolic name of page
 * @param {Object} [config] Configuration options
 * @cfg {jQuery} [$overlay] Overlay to render dropdowns in
 */
ve.ui.MWCategoriesPage = function VeUiMWCategoriesPage( name, config ) {
	// Configuration initialization
	config = config || {};

	// Parent constructor
	ve.ui.MWCategoriesPage.super.apply( this, arguments );

	// Properties
	this.fragment = null;
	this.defaultSortKeyTouched = false;
	this.fallbackDefaultSortKey = mw.Title.newFromText( ve.init.target.getPageName() ).getMainText();
	this.categoriesFieldset = new OO.ui.FieldsetLayout( {
		label: ve.msg( 'visualeditor-dialog-meta-categories-data-label' ),
		icon: 'tag'
	} );

	this.categoryOptionsFieldset = new OO.ui.FieldsetLayout( {
		label: ve.msg( 'visualeditor-dialog-meta-categories-options' ),
		icon: 'settings'
	} );

	this.categoryWidget = new ve.ui.MWCategoryWidget( {
		$overlay: config.$overlay
	} );

	this.addCategory = new OO.ui.FieldLayout(
		this.categoryWidget,
		{
			$overlay: config.$overlay,
			align: 'top',
			label: ve.msg( 'visualeditor-dialog-meta-categories-addcategory-label' )
		}
	);

	this.defaultSortInput = new OO.ui.TextInputWidget( {
		placeholder: this.fallbackDefaultSortKey
	} );

	this.defaultSortInput.$element.addClass( 've-ui-mwCategoriesPage-defaultsort' );

	this.defaultSort = new OO.ui.FieldLayout(
		this.defaultSortInput,
		{
			$overlay: config.$overlay,
			align: 'top',
			label: ve.msg( 'visualeditor-dialog-meta-categories-defaultsort-label' ),
			help: ve.msg( 'visualeditor-dialog-meta-categories-defaultsort-help' )
		}
	);

	// Events
	this.categoryWidget.connect( this, {
		newCategory: 'onNewCategory',
		updateSortkey: 'onUpdateSortKey'
	} );
	this.defaultSortInput.connect( this, {
		change: 'onDefaultSortChange'
	} );

	// Initialization
	this.categoriesFieldset.addItems( [ this.addCategory ] );
	this.categoryOptionsFieldset.addItems( [ this.defaultSort ] );
	this.$element.append( this.categoriesFieldset.$element, this.categoryOptionsFieldset.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWCategoriesPage, OO.ui.PageLayout );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWCategoriesPage.prototype.setupOutlineItem = function () {
	this.outlineItem
		.setIcon( 'tag' )
		.setLabel( ve.msg( 'visualeditor-dialog-meta-categories-section' ) );
};

/**
 * Handle category default sort change events.
 *
 * @param {string} value Default sort value
 */
ve.ui.MWCategoriesPage.prototype.onDefaultSortChange = function ( value ) {
	this.categoryWidget.setDefaultSortKey( value === '' ? this.fallbackDefaultSortKey : value );
	this.defaultSortKeyTouched = true;
};

/**
 * Inserts new category into meta list
 *
 * @param {Object} item
 * @param {ve.dm.MWCategoryMetaItem} [beforeMetaItem] Meta item to insert before,
 *  or undefined to go at the end
 */
ve.ui.MWCategoriesPage.prototype.onNewCategory = function ( item, beforeMetaItem ) {
	this.fragment.insertMeta(
		this.getCategoryItemForInsertion( item ),
		beforeMetaItem ? beforeMetaItem.getOffset() : undefined
	);
};

/**
 * Removes and re-inserts updated category widget item
 *
 * @param {Object} item
 */
ve.ui.MWCategoriesPage.prototype.onUpdateSortKey = function ( item ) {
	// Replace meta item with updated one
	this.fragment.replaceMeta( item.metaItem, this.getCategoryItemForInsertion( item, item.metaItem.getElement() ) );
};

/**
 * Bound to MetaList insert event for adding meta dialog components.
 *
 * @param {ve.dm.MetaItem} metaItem
 */
ve.ui.MWCategoriesPage.prototype.onMetaListInsert = function ( metaItem ) {
	// Responsible for adding UI components
	if ( metaItem.element.type === 'mwCategory' ) {
		var index = this.fragment.getDocument().getMetaList().getItemsInGroup( 'mwCategory' ).indexOf( metaItem );
		this.categoryWidget.addItems(
			[ this.getCategoryItemFromMetaListItem( metaItem ) ],
			index
		);
	}
};

/**
 * Bound to MetaList insert event for removing meta dialog components.
 *
 * @param {ve.dm.MetaItem} metaItem
 */
ve.ui.MWCategoriesPage.prototype.onMetaListRemove = function ( metaItem ) {
	if ( metaItem.element.type === 'mwCategory' ) {
		var item = this.categoryWidget.categories[ this.getCategoryItemFromMetaListItem( metaItem ).value ];
		this.categoryWidget.removeItems( [ item ] );
	}
};

/**
 * Get default sort key item.
 *
 * @return {Object} Default sort key item
 */
ve.ui.MWCategoriesPage.prototype.getDefaultSortKeyItem = function () {
	return this.fragment.getDocument().getMetaList().getItemsInGroup( 'mwDefaultSort' )[ 0 ] || null;
};

/**
 * Get array of category items from meta list
 *
 * @return {Object[]} items
 */
ve.ui.MWCategoriesPage.prototype.getCategoryItems = function () {
	var items = [],
		categories = this.fragment.getDocument().getMetaList().getItemsInGroup( 'mwCategory' );

	// Loop through MwCategories and build out items
	for ( var i = 0; i < categories.length; i++ ) {
		items.push( this.getCategoryItemFromMetaListItem( categories[ i ] ) );
	}
	return items;
};

/**
 * Gets category item from meta list item
 *
 * @param {ve.dm.MWCategoryMetaItem} metaItem
 * @return {Object} item
 */
ve.ui.MWCategoriesPage.prototype.getCategoryItemFromMetaListItem = function ( metaItem ) {
	var title = mw.Title.newFromText( metaItem.element.attributes.category ),
		value = title ? title.getMainText() : '';

	return {
		name: metaItem.element.attributes.category,
		value: value,
		// TODO: sortkey is lcase, make consistent throughout CategoryWidget
		sortKey: metaItem.element.attributes.sortkey,
		metaItem: metaItem
	};
};

/**
 * Get metaList like object to insert from item
 *
 * @param {Object} item category widget item
 * @param {Object} [oldData] Metadata object that was previously associated with this item, if any
 * @return {Object} metaBase
 */
ve.ui.MWCategoriesPage.prototype.getCategoryItemForInsertion = function ( item, oldData ) {
	var newData = {
		attributes: { category: item.name, sortkey: item.sortKey || '' },
		type: 'mwCategory'
	};
	if ( oldData ) {
		return ve.extendObject( {}, oldData, newData );
	}
	return newData;
};

/**
 * Setup categories page.
 *
 * @param {ve.dm.SurfaceFragment} fragment Surface fragment
 * @param {Object} config
 * @param {Object} [config.data] Dialog setup data
 * @param {boolean} [config.isReadOnly=false] Dialog is in read-only mode
 * @return {jQuery.Promise}
 */
ve.ui.MWCategoriesPage.prototype.setup = function ( fragment, config ) {
	var page = this;

	this.fragment = fragment;
	this.fragment.getDocument().getMetaList().connect( this, {
		insert: 'onMetaListInsert',
		remove: 'onMetaListRemove'
	} );

	var defaultSortKeyItem = this.getDefaultSortKeyItem();

	this.categoryWidget.setFragment( fragment );
	var promise = this.categoryWidget.addItems( this.getCategoryItems() ).then( function () {
		page.categoryWidget.setDisabled( config.isReadOnly );
	} );

	this.defaultSortInput.setValue(
		defaultSortKeyItem ? defaultSortKeyItem.getAttribute( 'sortkey' ) : this.fallbackDefaultSortKey
	).setReadOnly( config.isReadOnly );
	this.defaultSortKeyTouched = false;

	// Update input position after transition
	setTimeout( function () {
		page.categoryWidget.fitInput();
	}, OO.ui.theme.getDialogTransitionDuration() );

	return promise;
};

/**
 * @inheritdoc
 */
ve.ui.MWCategoriesPage.prototype.focus = function () {
	this.categoryWidget.focus();
};

/**
 * Tear down the page. This is called when the MWMetaDialog is torn down.
 *
 * @param {Object} [data] Dialog tear down data
 */
ve.ui.MWCategoriesPage.prototype.teardown = function ( data ) {
	var currentDefaultSortKeyItem = this.getDefaultSortKeyItem(),
		newDefaultSortKey = this.defaultSortInput.getValue();

	if ( data && data.action === 'done' ) {
		// Alter the default sort key iff it's been touched & is actually different
		if ( this.defaultSortKeyTouched ) {
			if ( newDefaultSortKey === '' || newDefaultSortKey === this.fallbackDefaultSortKey ) {
				if ( currentDefaultSortKeyItem ) {
					this.fragment.removeMeta( currentDefaultSortKeyItem );
				}
			} else {
				var newDefaultSortKeyData = {
					type: 'mwDefaultSort',
					attributes: {
						sortkey: newDefaultSortKey
					}
				};
				if ( !currentDefaultSortKeyItem ) {
					var firstCategory = this.fragment.getDocument().getMetaList().getItemsInGroup( 'mwCategory' )[ 0 ],
						offset = firstCategory && firstCategory.getOffset();
					this.fragment.insertMeta( newDefaultSortKeyData, offset );
				} else if ( currentDefaultSortKeyItem.getAttribute( 'sortkey' ) !== newDefaultSortKey ) {
					this.fragment.replaceMeta(
						currentDefaultSortKeyItem,
						ve.extendObject( true, {},
							currentDefaultSortKeyItem.getElement(),
							newDefaultSortKeyData
						)
					);
				}
			}
		}
	}

	this.categoryWidget.clearItems();
	this.categoryWidget.setFragment( null );
	this.fragment.getDocument().getMetaList().disconnect( this );
	this.fragment = null;
};

ve.ui.MWCategoriesPage.prototype.getFieldsets = function () {
	return [
		this.categoriesFieldset,
		this.categoryOptionsFieldset
	];
};
