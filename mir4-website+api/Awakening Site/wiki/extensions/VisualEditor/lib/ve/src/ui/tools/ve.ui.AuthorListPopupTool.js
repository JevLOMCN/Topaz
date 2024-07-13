/*!
 * VisualEditor UserInterface AuthorListPopupTool class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * UserInterface AuthorListPopupTool
 *
 * @class
 * @extends OO.ui.PopupTool
 *
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config]
 */
ve.ui.AuthorListPopupTool = function VeUiAuthorListPopupTool( toolGroup, config ) {
	this.$authorList = $( '<div>' );

	// Parent constructor
	ve.ui.AuthorListPopupTool.super.call( this, toolGroup, ve.extendObject( {
		popup: {
			classes: [ 've-ui-authorListWidget-listPopup' ],
			$content: this.$authorList,
			padded: true,
			align: 'backwards'
		}
	}, config ) );

	// Events
	this.toolbar.connect( this, { surfaceChange: 'onSurfaceChange' } );

	this.$element.addClass( 've-ui-authorListPopupTool' );
};

/* Inheritance */

OO.inheritClass( ve.ui.AuthorListPopupTool, OO.ui.PopupTool );

/* Methods */

/**
 * Handle surfaceChange event fromt the toolbar
 *
 * @param {ve.dm.Surface|null} oldSurface Old surface
 * @param {ve.dm.Surface|null} newSurface New surface
 */
ve.ui.AuthorListPopupTool.prototype.onSurfaceChange = function ( oldSurface, newSurface ) {
	// TODO: Disconnect oldSurface. Currently in the CollabTarget life-cycle the surface is never changed.
	this.setup( newSurface );
};

/**
 * @inheritdoc
 */
ve.ui.AuthorListPopupTool.prototype.onPopupToggle = function ( visible ) {
	// Parent method
	ve.ui.AuthorListPopupTool.super.prototype.onPopupToggle.apply( this, arguments );

	if ( visible ) {
		this.selfItem.focus();
	}
};

/**
 * Setup the popup which a specific surface
 *
 * @param {ve.ui.Surface} surface
 */
ve.ui.AuthorListPopupTool.prototype.setup = function ( surface ) {
	this.oldName = '';
	this.updatingName = false;
	this.synchronizer = surface.getModel().synchronizer;
	this.authorItems = {};

	this.surface = surface;

	if ( !this.synchronizer ) {
		this.setDisabled( true );
		return;
	}

	// TODO: Unbind from an existing surface if one is set

	this.changeNameDebounced = ve.debounce( this.changeName.bind( this ), 250 );

	this.selfItem = new ve.ui.AuthorItemWidget(
		this.synchronizer,
		this.popup.$element,
		{ editable: true, authorId: this.synchronizer.getAuthorId() }
	);
	this.$authorList.prepend( this.selfItem.$element );
	this.selfItem.connect( this, {
		change: 'onSelfItemChange',
		changeColor: 'onSelfItemChangeColor'
	} );

	this.synchronizer.connect( this, {
		authorChange: 'onSynchronizerAuthorUpdate',
		authorDisconnect: 'onSynchronizerAuthorDisconnect'
	} );

	for ( var authorId in this.synchronizer.authors ) {
		this.onSynchronizerAuthorUpdate( +authorId );
	}
};

/**
 * Handle change events from the user's authorItem
 *
 * @param {string} value
 */
ve.ui.AuthorListPopupTool.prototype.onSelfItemChange = function () {
	if ( !this.updatingName ) {
		this.changeNameDebounced();
	}
};

/**
 * Handle change color events from the user's authorItem
 *
 * @param {string} color
 */
ve.ui.AuthorListPopupTool.prototype.onSelfItemChangeColor = function ( color ) {
	this.synchronizer.changeAuthor( { color: color } );
};

/**
 * Notify the server of a name change
 */
ve.ui.AuthorListPopupTool.prototype.changeName = function () {
	this.synchronizer.changeAuthor( { name: this.selfItem.getName() } );
};

/**
 * Update the user count
 */
ve.ui.AuthorListPopupTool.prototype.updateAuthorCount = function () {
	this.setTitle( ( Object.keys( this.authorItems ).length + 1 ).toString() );
};

/**
 * Called when the synchronizer receives a remote author selection or name change
 *
 * @param {number} authorId The author ID
 */
ve.ui.AuthorListPopupTool.prototype.onSynchronizerAuthorUpdate = function ( authorId ) {
	var authorItem = this.authorItems[ authorId ];

	if ( authorId !== this.synchronizer.getAuthorId() ) {
		if ( !authorItem ) {
			authorItem = new ve.ui.AuthorItemWidget( this.synchronizer, this.popup.$element, { authorId: authorId } );
			this.authorItems[ authorId ] = authorItem;
			this.updateAuthorCount();
			this.$authorList.append( authorItem.$element );
		} else {
			authorItem.update();
		}
	} else {
		// Don't update nameInput if the author is still changing it
		if ( this.selfItem.getName() === this.oldName ) {
			// Don't send this "new" name back to the server
			this.updatingName = true;
			try {
				this.selfItem.setAuthorId( this.synchronizer.getAuthorId() );
				this.selfItem.update();
			} finally {
				this.updatingName = false;
			}
		}
	}
	this.oldName = this.synchronizer.getAuthorData( authorId ).name;
};

/**
 * Called when the synchronizer receives a remote author disconnect
 *
 * @param {number} authorId The author ID
 */
ve.ui.AuthorListPopupTool.prototype.onSynchronizerAuthorDisconnect = function ( authorId ) {
	var authorItem = this.authorItems[ authorId ];
	if ( authorItem ) {
		authorItem.$element.remove();
		delete this.authorItems[ authorId ];
		this.updateAuthorCount();
	}
};

/* Static Properties */

ve.ui.AuthorListPopupTool.static.name = 'authorList';
ve.ui.AuthorListPopupTool.static.group = 'users';
ve.ui.AuthorListPopupTool.static.icon = 'userAvatar';
ve.ui.AuthorListPopupTool.static.title = '1';
ve.ui.AuthorListPopupTool.static.autoAddToCatchall = false;
ve.ui.AuthorListPopupTool.static.autoAddToGroup = false;
ve.ui.AuthorListPopupTool.static.displayBothIconAndLabel = true;

/* Registration */

ve.ui.toolFactory.register( ve.ui.AuthorListPopupTool );
