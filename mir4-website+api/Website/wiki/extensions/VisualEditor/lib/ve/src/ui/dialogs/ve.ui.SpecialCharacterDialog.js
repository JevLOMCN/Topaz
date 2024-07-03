/*!
 * VisualEditor UserInterface SpecialCharacterDialog class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Inspector for inserting special characters.
 *
 * @class
 * @extends ve.ui.ToolbarDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.SpecialCharacterDialog = function VeUiSpecialCharacterDialog() {
	// Parent constructor
	ve.ui.SpecialCharacterDialog.super.apply( this, arguments );

	this.characterListLoaded = false;

	this.$element.addClass( 've-ui-specialCharacterDialog' );
};

/* Inheritance */

OO.inheritClass( ve.ui.SpecialCharacterDialog, ve.ui.ToolbarDialog );

/* Static properties */

ve.ui.SpecialCharacterDialog.static.name = 'specialCharacter';

// Invisible title for accessibility
ve.ui.SpecialCharacterDialog.static.title =
	OO.ui.deferMsg( 'visualeditor-specialcharacter-button-tooltip' );

ve.ui.SpecialCharacterDialog.static.size = 'full';

ve.ui.SpecialCharacterDialog.static.padded = false;

ve.ui.SpecialCharacterDialog.static.activeSurface = true;

ve.ui.SpecialCharacterDialog.static.handlesSource = true;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.SpecialCharacterDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.SpecialCharacterDialog.super.prototype.initialize.call( this );

	this.characterListLayout = new ve.ui.SymbolListBookletLayout();
	this.characterListLayout.connect( this, {
		choose: 'onCharacterListChoose'
	} );
	// Character list is lazy-loaded the first time getSetupProcess runs

	this.$body.append( this.characterListLayout.$element );
};

/**
 * @inheritdoc
 */
ve.ui.SpecialCharacterDialog.prototype.getSetupProcess = function ( data ) {
	data = data || {};
	return ve.ui.SpecialCharacterDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var dialog = this;

			this.surface = data.surface;
			this.surface.getModel().connect( this, { contextChange: 'onContextChange' } );

			this.characterListLayout.$element.toggleClass( 've-ui-specialCharacterDialog-characterList-source', this.surface.getMode() === 'source' );

			if ( !this.characterListLoaded ) {
				this.characterListLoaded = true;

				ve.init.platform.fetchSpecialCharList()
					.then( function ( symbolData ) {
						dialog.characterListLayout.setSymbolData( symbolData );
						dialog.updateSize();
					} );
			}
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.SpecialCharacterDialog.prototype.getTeardownProcess = function ( data ) {
	data = data || {};
	return ve.ui.SpecialCharacterDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			this.surface.getModel().disconnect( this );
			this.surface = null;
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.SpecialCharacterDialog.prototype.getReadyProcess = function ( data ) {
	return ve.ui.SpecialCharacterDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			var surface = this.surface;
			// The dialog automatically receives focus after opening, move it back to the surface.
			// (Make sure an existing selection is preserved. Why does focus() reset the selection? 🤦)
			var previousSelection = surface.getModel().getSelection();
			// On deactivated surfaces (e.g. those using nullSelectionOnBlur), the native selection is
			// removed after a setTimeout to fix a bug in iOS (T293661, in ve.ce.Surface#deactivate).
			// Ensure that we restore the selection **after** this happens, otherwise the surface will
			// get re-blurred. (T318720)
			setTimeout( function () {
				surface.getView().focus();
				if ( !previousSelection.isNull() ) {
					surface.getModel().setSelection( previousSelection );
				}
			} );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.SpecialCharacterDialog.prototype.getActionProcess = function ( action ) {
	return new OO.ui.Process( function () {
		this.close( { action: action } );
	}, this );
};

/**
 * Handle context change events from the surface model
 */
ve.ui.SpecialCharacterDialog.prototype.onContextChange = function () {
	this.setDisabled( !( this.surface.getModel().getSelection() instanceof ve.dm.LinearSelection ) );
};

/**
 * Handle a character being chosen from the list
 *
 * @param {Object} character Character data
 */
ve.ui.SpecialCharacterDialog.prototype.onCharacterListChoose = function ( character ) {
	var fragment = this.surface.getModel().getFragment(),
		mode = this.surface.getMode();

	function encode( text ) {
		if ( mode === 'visual' && character.entities ) {
			return ve.init.platform.decodeEntities( text );
		} else {
			return text;
		}
	}

	if ( character ) {
		if ( typeof character === 'string' || character.string ) {
			fragment.insertContent( encode( character.string || character ), true ).collapseToEnd().select();
		} else if ( character.action.type === 'replace' ) {
			fragment.insertContent( encode( character.action.options.peri ), true ).collapseToEnd().select();
		} else if ( character.action.type === 'encapsulate' ) {
			fragment.collapseToStart().insertContent( encode( character.action.options.pre ), true );
			fragment.collapseToEnd().insertContent( encode( character.action.options.post ), true ).collapseToEnd().select();
		}

		ve.track(
			'activity.' + this.constructor.static.name,
			{ action: 'insert-' + this.characterListLayout.currentPageName }
		);
	}
};

ve.ui.SpecialCharacterDialog.prototype.getBodyHeight = function () {
	return 150;
};

ve.ui.SpecialCharacterDialog.prototype.getContentHeight = function () {
	// Skip slow complicated measurements that always return 0 for this window
	return this.getBodyHeight();
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.SpecialCharacterDialog );
