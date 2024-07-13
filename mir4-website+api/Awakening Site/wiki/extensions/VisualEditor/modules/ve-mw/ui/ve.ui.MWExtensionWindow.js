/*!
 * VisualEditor UserInterface MWExtensionWindow class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Mixin for windows for editing generic MediaWiki extensions.
 *
 * @class
 * @abstract
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWExtensionWindow = function VeUiMWExtensionWindow() {
	this.whitespace = null;
	this.input = null;
	this.originalMwData = null;

	this.onChangeHandler = ve.debounce( this.onChange.bind( this ) );
};

/* Inheritance */

OO.initClass( ve.ui.MWExtensionWindow );

/* Static properties */

/**
 * Extension is allowed to have empty contents
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.ui.MWExtensionWindow.static.allowedEmpty = false;

/**
 * Tell Parsoid to self-close tags when the body is empty
 *
 * i.e. `<foo></foo>` -> `<foo/>`
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.ui.MWExtensionWindow.static.selfCloseEmptyBody = false;

/**
 * Inspector's directionality, 'ltr' or 'rtl'
 *
 * Leave as null to use the directionality of the current fragment.
 *
 * @static
 * @property {string|null}
 * @inheritable
 */
ve.ui.MWExtensionWindow.static.dir = null;

/* Methods */

/**
 * @inheritdoc OO.ui.Window
 */
ve.ui.MWExtensionWindow.prototype.initialize = function () {
	this.input = new ve.ui.WhitespacePreservingTextInputWidget( {
		limit: 1,
		classes: [ 've-ui-mwExtensionWindow-input' ]
	} );
};

/**
 * Get the placeholder text for the content input area.
 *
 * @return {string} Placeholder text
 */
ve.ui.MWExtensionWindow.prototype.getInputPlaceholder = function () {
	return '';
};

/**
 * @inheritdoc OO.ui.Window
 */
ve.ui.MWExtensionWindow.prototype.getSetupProcess = function ( data, process ) {
	data = data || {};
	return process.next( function () {
		// Initialization
		this.whitespace = [ '', '' ];

		if ( this.selectedNode ) {
			var mwData = this.selectedNode.getAttribute( 'mw' );
			// mwData.body can be null in <selfclosing/> extensions
			this.input.setValueAndWhitespace( ( mwData.body && mwData.body.extsrc ) || '' );
			this.originalMwData = mwData;
		} else {
			if ( !this.constructor.static.modelClasses[ 0 ].static.isContent ) {
				// New nodes should use linebreaks for blocks
				this.input.setWhitespace( [ '\n', '\n' ] );
			}
			this.input.setValue( '' );
		}

		this.input.$input.attr( 'placeholder', this.getInputPlaceholder() );

		var dir = this.constructor.static.dir || data.dir;
		this.input.setDir( dir );
		this.input.setReadOnly( this.isReadOnly() );

		this.actions.setAbilities( { done: false } );
		this.input.connect( this, { change: 'onChangeHandler' } );
	}, this );
};

/**
 * @inheritdoc OO.ui.Window
 */
ve.ui.MWExtensionWindow.prototype.getReadyProcess = function ( data, process ) {
	return process;
};

/**
 * @inheritdoc OO.ui.Window
 */
ve.ui.MWExtensionWindow.prototype.getTeardownProcess = function ( data, process ) {
	return process.next( function () {
		// Don't hold on to the original data, it's only refreshed on setup for existing nodes
		this.originalMwData = null;
		this.input.disconnect( this, { change: 'onChangeHandler' } );
	}, this );
};

/**
 * @inheritdoc OO.ui.Dialog
 */
ve.ui.MWExtensionWindow.prototype.getActionProcess = function ( action, process ) {
	return process.first( function () {
		if ( action === 'done' ) {
			if ( this.constructor.static.allowedEmpty || this.input.getValue() !== '' ) {
				this.insertOrUpdateNode();
			} else if ( this.selectedNode && !this.constructor.static.allowedEmpty ) {
				// Content has been emptied on a node which isn't allowed to
				// be empty, so delete it.
				this.removeNode();
			}
		}
	}, this );
};

/**
 * Handle change event.
 */
ve.ui.MWExtensionWindow.prototype.onChange = function () {
	this.updateActions();
};

/**
 * Update the 'done' action according to whether there are changes
 */
ve.ui.MWExtensionWindow.prototype.updateActions = function () {
	this.actions.setAbilities( { done: this.isModified() } );
};

/**
 * Check if mwData would be modified if window contents were applied
 *
 * @return {boolean} mwData would be modified
 */
ve.ui.MWExtensionWindow.prototype.isModified = function () {
	var modified;
	if ( this.originalMwData ) {
		var mwDataCopy = ve.copy( this.originalMwData );
		this.updateMwData( mwDataCopy );
		modified = !ve.compare( this.originalMwData, mwDataCopy );
	} else {
		modified = true;
	}
	return modified;
};

/**
 * Create an new data element for the model class associated with this inspector
 *
 * @return {Object} Element data
 */
ve.ui.MWExtensionWindow.prototype.getNewElement = function () {
	// Extension inspectors which create elements should either match
	// a single modelClass or override this method.
	var modelClass = this.constructor.static.modelClasses[ 0 ];
	return {
		type: modelClass.static.name,
		attributes: {
			mw: {
				name: modelClass.static.extensionName,
				attrs: {},
				body: {
					extsrc: ''
				}
			}
		}
	};
};

/**
 * Insert or update the node in the document model from the new values
 */
ve.ui.MWExtensionWindow.prototype.insertOrUpdateNode = function () {
	var surfaceModel = this.getFragment().getSurface();
	if ( this.selectedNode ) {
		var mwData = ve.copy( this.selectedNode.getAttribute( 'mw' ) );
		this.updateMwData( mwData );
		surfaceModel.change(
			ve.dm.TransactionBuilder.static.newFromAttributeChanges(
				surfaceModel.getDocument(),
				this.selectedNode.getOuterRange().start,
				{ mw: mwData }
			)
		);
	} else {
		var element = this.getNewElement();
		this.updateMwData( element.attributes.mw );
		// Collapse returns a new fragment, so update this.fragment
		this.fragment = this.getFragment().collapseToEnd();
		this.getFragment().insertContent( [
			element,
			{ type: '/' + element.type }
		] );
	}
};

/**
 * Remove the node form the document model
 */
ve.ui.MWExtensionWindow.prototype.removeNode = function () {
	this.getFragment().removeContent();
};

/**
 * Update mwData object with the new values from the inspector or dialog
 *
 * @param {Object} mwData MediaWiki data object
 */
ve.ui.MWExtensionWindow.prototype.updateMwData = function ( mwData ) {
	var tagName = mwData.name,
		value = this.input.getValueAndWhitespace();

	// XML-like tags in wikitext are not actually XML and don't expect their contents to be escaped.
	// This means that it is not possible for a tag '<foo>…</foo>' to contain the string '</foo>'.
	// Prevent that by escaping the first angle bracket '<' to '&lt;'. (T59429)
	value = value.replace( new RegExp( '<(/' + tagName + '\\s*>)', 'gi' ), '&lt;$1' );

	if ( value.trim() === '' && this.constructor.static.selfCloseEmptyBody ) {
		delete mwData.body;
	} else {
		mwData.body = mwData.body || {};
		mwData.body.extsrc = value;
	}
};
