/*!
 * VisualEditor UserInterface LanguageVariantInspector class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Inspector for a ve.dm.MWLanguageVariantNode.
 *
 * @class
 * @extends ve.ui.NodeInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLanguageVariantInspector = function VeUiMWLanguageVariantInspector() {
	// Parent constructor
	ve.ui.MWLanguageVariantInspector.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLanguageVariantInspector, ve.ui.NodeInspector );

/* Static properties */

ve.ui.MWLanguageVariantInspector.static.name = 'mwLanguageVariant-disabled';

ve.ui.MWLanguageVariantInspector.static.title = OO.ui.deferMsg(
	'visualeditor-mwlanguagevariantinspector-title-disabled'
);

ve.ui.MWLanguageVariantInspector.static.modelClasses = [
	ve.dm.MWLanguageVariantBlockNode,
	ve.dm.MWLanguageVariantInlineNode,
	ve.dm.MWLanguageVariantHiddenNode
];

ve.ui.MWLanguageVariantInspector.static.size = 'large';

ve.ui.MWLanguageVariantInspector.static.actions = [
	{
		action: 'remove',
		label: OO.ui.deferMsg( 'visualeditor-inspector-remove-tooltip' ),
		flags: 'destructive',
		modes: 'edit'
	}
].concat( ve.ui.MWLanguageVariantInspector.super.static.actions );

ve.ui.MWLanguageVariantInspector.static.defaultVariantType =
	'mwLanguageVariantInline';

ve.ui.MWLanguageVariantInspector.static.includeCommands = null;

// This is very similar to the exclude list in ve.ui.MWMediaDialog
ve.ui.MWLanguageVariantInspector.static.excludeCommands = [
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
 * Get the import rules for embedded target widgets in this inspector.
 *
 * @see ve.ui.MWMediaDialog#getImportRules
 * @return {Object} Import rules
 */
ve.ui.MWLanguageVariantInspector.static.getImportRules = function () {
	return ve.extendObject(
		ve.copy( ve.init.target.constructor.static.importRules ),
		{
			// TODO: We might want to include some of the
			// conversion/sanitization done by ve.ui.MWMediaDialog
		}
	);
};

/* Methods */

/**
 * Return a valid `variantInfo` object which will be used when a new
 * node of this subclass is inserted.  For instance,
 * ve.ui.MWLanguageVariantDisabledInspector will return the appropriate
 * object to use when the equivalent of wikitext `-{}-` is inserted
 * in the document.
 *
 * @return {Object}
 */
ve.ui.MWLanguageVariantInspector.prototype.getDefaultVariantInfo = null;

/**
 * Convert the current inspector state to new content which can be used
 * to update the ve.dm.SurfaceFragment backing this inspector.
 *
 * @param {Object} An existing variantInfo object for this node, which will be
 *  mutated to update it with the latest content from this inspector.
 * @return {string|Array} New content for the ve.dm.SurfaceFragment
 *  being inspected/updated.
 */
ve.ui.MWLanguageVariantInspector.prototype.getContentFromInspector = null;

// Helper functions for creating sub-document editors for embedded HTML

/**
 * Helper function to create a subdocument editor for HTML embedded in the
 * language variant node.
 *
 * @param {string} [placeholder] Placeholder text for this editor.
 * @return {ve.ui.TargetWidget}
 */
ve.ui.MWLanguageVariantInspector.prototype.createTextTarget = function ( placeholder ) {
	return ve.init.target.createTargetWidget( {
		toolbarGroups: [],
		includeCommands: this.constructor.static.includeCommands,
		excludeCommands: this.constructor.static.excludeCommands,
		importRules: this.constructor.static.getImportRules(),
		inDialog: this.constructor.static.name,
		placeholder: placeholder || null
	} );
};

/**
 * Helper function to initialize a ve.ui.TargetWidget with a given HTML
 * string extracted from the language variant node.
 *
 * @param {ve.ui.TargetWidget} [textTarget] A subdocument editor widget
 *   created by ve.ui.MWLanguageVariantInspector#createTextTarget.
 * @param {string} [htmlString] The HTML string extracted from this node.
 * @return {ve.dm.Document} The document model now backing the widget.
 */
ve.ui.MWLanguageVariantInspector.prototype.setupTextTargetDoc = function ( textTarget, htmlString ) {
	var doc = this.variantNode.getDocument().newFromHtml( htmlString );
	textTarget.setDocument( doc );
	return doc;
};

/**
 * Helper function to serialize the document backing a `ve.ui.TargetWidget`
 * back into HTML which can be embedded into the language variant node.
 * This method needs to do a bit of hackery to remove unnecessary p-wrapping
 * and (TODO) determine if an inline node needs to be converted to a
 * block node or vice-versa.
 *
 * @param {ve.dm.Document} doc The document backing an editor widget, as returned
 *  by ve.ui.MWLanguageVariantInspector#setupTextTargetDoc.
 * @return {string} An HTML string appropriate for embedding into a
 *  language variant node.
 */
ve.ui.MWLanguageVariantInspector.prototype.getHtmlForDoc = function ( doc ) {
	var surface = new ve.dm.Surface( doc );

	// Remove outermost p-wrapping, if present
	try {
		surface.change(
			ve.dm.TransactionBuilder.static.newFromWrap( doc, new ve.Range( 0, doc.data.countNonInternalElements() ), [], [], [ { type: 'paragraph' } ], [] )
		);
	} catch ( e ) {
		// Sometimes there is no p-wrapping, for example: "* foo"
		// Sometimes there are multiple <p> tags in the output.
		// That's okay: ignore the error and use what we've got.
	}
	// XXX return a flag to indicate whether contents are now inline or block?
	var targetHtmlDoc = ve.dm.converter.getDomFromModel( doc );
	return ve.properInnerHtml( targetHtmlDoc.body );
};

// Inspector implementation

/**
 * Handle frame ready events.
 */
ve.ui.MWLanguageVariantInspector.prototype.initialize = function () {
	// Parent method
	ve.ui.MWLanguageVariantInspector.super.prototype.initialize.call( this );
	this.$content.addClass( 've-ui-mwLanguageVariantInspector-content' );
};

/**
 * @inheritdoc
 */
ve.ui.MWLanguageVariantInspector.prototype.getActionProcess = function ( action ) {
	if ( action === 'remove' || action === 'insert' ) {
		return new OO.ui.Process( function () {
			this.close( { action: action } );
		}, this );
	}
	return ve.ui.MWLanguageVariantInspector.super.prototype.getActionProcess.call( this, action );
};

/**
 * Handle the inspector being setup.
 *
 * @param {Object} [data] Inspector opening data
 * @return {OO.ui.Process}
 */
ve.ui.MWLanguageVariantInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWLanguageVariantInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.getFragment().getSurface().pushStaging();

			this.variantNode = this.getSelectedNode();
			if ( !this.variantNode ) {
				this.getFragment().insertContent( [
					{
						type: this.constructor.static.defaultVariantType,
						attributes: {
							variantInfo: this.getDefaultVariantInfo()
						}
					},
					{ type: '/' + this.constructor.static.defaultVariantType }
				] ).select();
				this.variantNode = this.getSelectedNode();
			}
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWLanguageVariantInspector.prototype.getTeardownProcess = function ( data ) {
	data = data || {};
	return ve.ui.MWLanguageVariantInspector.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			var surfaceModel = this.getFragment().getSurface();

			if ( data.action === 'remove' ) {
				surfaceModel.popStaging();
				// If popStaging removed the node then this will be a no-op
				this.getFragment().removeContent();
			} else if ( data.action === 'done' ) {
				// Edit language variant node
				var newContent = this.getContentFromInspector(
					ve.copy( this.variantNode.getVariantInfo() )
				);
				if ( newContent[ 0 ].type === this.variantNode.getType() ) {
					this.getFragment().changeAttributes( {
						variantInfo: newContent[ 0 ].attributes.variantInfo
					} );
				} else {
					this.getFragment().removeContent();
					this.getFragment().insertContent( newContent ).select();
					this.variantNode = this.getSelectedNode();
				}
				surfaceModel.applyStaging();
			} else {
				surfaceModel.popStaging();
			}

		}, this );
};

/* Subclasses */

/**
 * Editor for "disabled" rules.
 *
 * @class
 * @extends ve.ui.MWLanguageVariantInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLanguageVariantDisabledInspector = function VeUiMWLanguageVariantDisabledInspector() {
	ve.ui.MWLanguageVariantDisabledInspector.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLanguageVariantDisabledInspector, ve.ui.MWLanguageVariantInspector );

/* Static properties */

ve.ui.MWLanguageVariantDisabledInspector.static.name = 'mwLanguageVariant-disabled';

ve.ui.MWLanguageVariantDisabledInspector.static.title = OO.ui.deferMsg(
	'visualeditor-mwlanguagevariantinspector-title-disabled'
);

/* Methods */

ve.ui.MWLanguageVariantDisabledInspector.prototype.initialize = function () {
	ve.ui.MWLanguageVariantDisabledInspector.super.prototype.initialize.call( this );
	this.textTarget = this.createTextTarget( OO.ui.msg(
		'visualeditor-mwlanguagevariantinspector-disabled-placeholder'
	) );
	this.form.$element.append( this.textTarget.$element );
};

ve.ui.MWLanguageVariantDisabledInspector.prototype.getDefaultVariantInfo = function () {
	return { disabled: { t: '' } };
};

ve.ui.MWLanguageVariantDisabledInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWLanguageVariantDisabledInspector.super.prototype.getSetupProcess.call( this, data ).next( function () {
		var variantInfo = this.variantNode.getVariantInfo();
		this.textTargetDoc = this.setupTextTargetDoc(
			this.textTarget,
			variantInfo.disabled.t
		);
	}, this );
};

ve.ui.MWLanguageVariantDisabledInspector.prototype.getContentFromInspector = function ( variantInfo ) {
	// TODO should allow type to depend on targetHtmlDoc, maybe switch
	// from inline to block.
	var type = this.variantNode.getType();
	variantInfo.disabled.t = this.getHtmlForDoc( this.textTargetDoc );
	return [
		{
			type: type,
			attributes: { variantInfo: variantInfo }
		},
		{
			type: '/' + type
		}
	];
};

ve.ui.MWLanguageVariantDisabledInspector.prototype.getReadyProcess = function ( data ) {
	return ve.ui.MWLanguageVariantDisabledInspector.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			this.textTarget.focus();
		}, this );
};

ve.ui.MWLanguageVariantDisabledInspector.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWLanguageVariantDisabledInspector.super.prototype.getTeardownProcess.call( this, data )
		.next( function () {
			// Reset inspector
			this.textTarget.clear();
			this.textTargetDoc = null;
		}, this );
};

/**
 * Editor for "name" rules.
 *
 * @class
 * @extends ve.ui.MWLanguageVariantInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLanguageVariantNameInspector = function VeUiMWLanguageVariantNameInspector() {
	ve.ui.MWLanguageVariantNameInspector.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLanguageVariantNameInspector, ve.ui.MWLanguageVariantInspector );

/* Static properties */

ve.ui.MWLanguageVariantNameInspector.static.name = 'mwLanguageVariant-name';

ve.ui.MWLanguageVariantNameInspector.static.title = OO.ui.deferMsg(
	'visualeditor-mwlanguagevariantinspector-title-name'
);

/* Methods */

ve.ui.MWLanguageVariantNameInspector.prototype.initialize = function () {
	ve.ui.MWLanguageVariantNameInspector.super.prototype.initialize.call( this );
	this.languageInput = new ve.ui.LanguageInputWidget( {
		dialogManager: this.manager.getSurface().getDialogs(),
		dirInput: 'none'
	} );
	this.form.$element.append( this.languageInput.$element );
};

ve.ui.MWLanguageVariantNameInspector.prototype.getDefaultVariantInfo = function () {
	return { name: { t: mw.config.get( 'wgUserVariant' ) || 'en' } };
};

ve.ui.MWLanguageVariantNameInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWLanguageVariantNameInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var variantInfo = this.variantNode.getVariantInfo();
			this.languageInput.setLangAndDir(
				variantInfo.name.t,
				'auto'
			);
		}, this );
};

ve.ui.MWLanguageVariantNameInspector.prototype.getContentFromInspector = function ( variantInfo ) {
	var type = this.variantNode.getType();
	variantInfo.name.t = this.languageInput.getLang();
	return [
		{
			type: type,
			attributes: { variantInfo: variantInfo }
		},
		{
			type: '/' + type
		}
	];
};

/**
 * Editor for "filter" rules.
 *
 * @class
 * @extends ve.ui.MWLanguageVariantInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLanguageVariantFilterInspector = function VeUiMWLanguageVariantFilterInspector() {
	ve.ui.MWLanguageVariantFilterInspector.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLanguageVariantFilterInspector, ve.ui.MWLanguageVariantInspector );

/* Static properties */

ve.ui.MWLanguageVariantFilterInspector.static.name = 'mwLanguageVariant-filter';

ve.ui.MWLanguageVariantFilterInspector.static.title = OO.ui.deferMsg(
	'visualeditor-mwlanguagevariantinspector-title-filter'
);

/* Methods */

ve.ui.MWLanguageVariantFilterInspector.prototype.initialize = function () {
	ve.ui.MWLanguageVariantFilterInspector.super.prototype.initialize.call( this );
	this.textTarget = this.createTextTarget( OO.ui.msg(
		'visualeditor-mwlanguagevariantinspector-filter-text-placeholder'
	) );

	this.langWidget = new OO.ui.TagMultiselectWidget( {
		allowArbitary: false,
		allowDisplayInvalidTags: true,
		allowedValues: ve.init.platform.getLanguageCodes().sort(),
		placeholder: OO.ui.msg(
			'visualeditor-mwlanguagevariantinspector-filter-langs-placeholder'
		),
		icon: 'language'
	} );
	this.langWidget.createTagItemWidget = function ( data, label ) {
		var name = ve.init.platform.getLanguageName( data.toLowerCase() );
		label = label || ( name ? ( name + ' (' + data + ')' ) : data );
		return OO.ui.TagMultiselectWidget.prototype.createTagItemWidget.call(
			this, data, label
		);
	};

	this.form.$element.append(
		new OO.ui.FieldLayout( this.langWidget, {
			align: 'top',
			label: OO.ui.msg( 'visualeditor-mwlanguagevariantinspector-filter-langs-label' )
		} ).$element
	);
	this.form.$element.append(
		new OO.ui.FieldLayout( this.textTarget, {
			align: 'top',
			label: OO.ui.msg( 'visualeditor-mwlanguagevariantinspector-filter-text-label' )
		} ).$element
	);
};

ve.ui.MWLanguageVariantFilterInspector.prototype.getDefaultVariantInfo = function () {
	return { filter: { l: [ mw.config.get( 'wgUserVariant' ) ], t: '' } };
};

ve.ui.MWLanguageVariantFilterInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWLanguageVariantFilterInspector.super.prototype.getSetupProcess.call( this, data ).next( function () {
		var variantInfo = this.variantNode.getVariantInfo();
		this.textTargetDoc = this.setupTextTargetDoc(
			this.textTarget,
			variantInfo.filter.t
		);
		this.langWidget.setValue( variantInfo.filter.l );
	}, this );
};

ve.ui.MWLanguageVariantFilterInspector.prototype.getContentFromInspector = function ( variantInfo ) {
	// TODO should allow type to depend on targetHtmlDoc, maybe switch
	// from inline to block.
	var type = this.variantNode.getType();
	variantInfo.filter.t = this.getHtmlForDoc( this.textTargetDoc );
	variantInfo.filter.l = this.langWidget.getValue();
	return [
		{
			type: type,
			attributes: { variantInfo: variantInfo }
		},
		{
			type: '/' + type
		}
	];
};

ve.ui.MWLanguageVariantFilterInspector.prototype.getReadyProcess = function ( data ) {
	return ve.ui.MWLanguageVariantFilterInspector.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			this.textTarget.focus();
		}, this );
};

ve.ui.MWLanguageVariantFilterInspector.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWLanguageVariantFilterInspector.super.prototype.getTeardownProcess.call( this, data )
		.next( function () {
			// Reset inspector
			this.langWidget.clearInput();
			this.langWidget.clearItems();
			this.textTarget.clear();
			this.textTargetDoc = null;
		}, this );
};

/**
 * Editor for "two-way" rules.
 *
 * @class
 * @extends ve.ui.MWLanguageVariantInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLanguageVariantTwoWayInspector = function VeUiMWLanguageVariantTwoWayInspector() {
	ve.ui.MWLanguageVariantTwoWayInspector.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLanguageVariantTwoWayInspector, ve.ui.MWLanguageVariantInspector );

/* Static properties */

ve.ui.MWLanguageVariantTwoWayInspector.static.name = 'mwLanguageVariant-twoway';

ve.ui.MWLanguageVariantTwoWayInspector.static.title = OO.ui.deferMsg(
	'visualeditor-mwlanguagevariantinspector-title-twoway'
);

/* Methods */

ve.ui.MWLanguageVariantTwoWayInspector.prototype.initialize = function () {
	ve.ui.MWLanguageVariantTwoWayInspector.super.prototype.initialize.call( this );
	this.items = [];
	this.layout = new OO.ui.FieldsetLayout( { } );
	this.form.$element.append( this.layout.$element );

	this.addButton = new OO.ui.ButtonInputWidget( {
		label: OO.ui.msg( 'visualeditor-mwlanguagevariantinspector-twoway-add-button' ),
		icon: 'add'
	} );
	this.form.$element.append( this.addButton.$element );

	// Events
	this.addButton.connect( this, { click: 'onAddButtonClick' } );
};

ve.ui.MWLanguageVariantTwoWayInspector.prototype.getDefaultVariantInfo = function () {
	return { twoway: [ { l: mw.config.get( 'wgUserVariant' ), t: '' } ] };
};

ve.ui.MWLanguageVariantTwoWayInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWLanguageVariantTwoWayInspector.super.prototype.getSetupProcess.call( this, data ).next( function () {
		var variantInfo = this.variantNode.getVariantInfo();
		this.layout.clearItems();
		this.items = [];
		variantInfo.twoway.forEach( function ( tw, idx ) {
			this.items[ idx ] = this.createItem( tw.l, tw.t );
			this.layout.addItems( [ this.items[ idx ].layout ] );
		}, this );
	}, this );
};

/**
 * Create widgets corresponding to a given mapping given by this rule.
 *
 * @param {string} [lang] The language code for the content text.
 * @param {string} [content] The HTML content text.
 * @return {Object} An object containing the required widgets and backing
 *  documents for this mapping item.
 */
ve.ui.MWLanguageVariantTwoWayInspector.prototype.createItem = function ( lang, content ) {
	var languageInput = new ve.ui.LanguageInputWidget( {
		dialogManager: this.manager.getSurface().getDialogs(),
		dirInput: 'none'
	} );
	var textTarget = this.createTextTarget( OO.ui.msg(
		'visualeditor-mwlanguagevariantinspector-twoway-text-placeholder'
	) );
	var clearButton = new OO.ui.ButtonInputWidget( {
		icon: 'clear',
		title: OO.ui.deferMsg(
			'visualeditor-mwlanguagevariantinspector-twoway-clear-button'
		),
		framed: false
	} );
	var layout = new OO.ui.FieldLayout(
		new OO.ui.Widget( {
			content: [
				new OO.ui.ActionFieldLayout(
					languageInput,
					clearButton
				),
				textTarget
			]
		} ), {}
	);
	var item = {
		languageInput: languageInput,
		textTarget: textTarget,
		clearButton: clearButton,
		layout: layout
	};

	// Initialize
	item.textTargetDoc = this.setupTextTargetDoc( textTarget, content );
	languageInput.setLangAndDir( lang, 'auto' );
	clearButton.connect( this, { click: [ 'onClearButtonClick', item ] } );
	return item;
};

ve.ui.MWLanguageVariantTwoWayInspector.prototype.getContentFromInspector = function ( variantInfo ) {
	// TODO should allow type to depend on targetHtmlDoc, maybe switch
	// from inline to block.
	var type = this.variantNode.getType();
	variantInfo.twoway = this.items.map( function ( item ) {
		return {
			l: item.languageInput.getLang(),
			t: this.getHtmlForDoc( item.textTargetDoc )
		};
	}, this );
	return [
		{
			type: type,
			attributes: { variantInfo: variantInfo }
		},
		{
			type: '/' + type
		}
	];
};

/**
 * Create a new mapping item in the inspector.
 */
ve.ui.MWLanguageVariantTwoWayInspector.prototype.onAddButtonClick = function () {
	var idx = this.items.length;
	this.items[ idx ] = this.createItem( mw.config.get( 'wgUserVariant' ), '' );
	this.layout.addItems( [ this.items[ idx ].layout ] );
};

/**
 * Remove a mapping item from the inspector.
 *
 * @param {Object} item
 */
ve.ui.MWLanguageVariantTwoWayInspector.prototype.onClearButtonClick = function ( item ) {
	var idx = this.items.indexOf( item );
	this.items.splice( idx, 1 );
	this.layout.removeItems( [ item.layout ] );
	item.clearButton.disconnect( this );
};

/**
 * Editor for "one-way" rules.
 *
 * @class
 * @extends ve.ui.MWLanguageVariantInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLanguageVariantOneWayInspector = function VeUiMWLanguageVariantOneWayInspector() {
	ve.ui.MWLanguageVariantOneWayInspector.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLanguageVariantOneWayInspector, ve.ui.MWLanguageVariantInspector );

/* Static properties */

ve.ui.MWLanguageVariantOneWayInspector.static.name = 'mwLanguageVariant-oneway';

ve.ui.MWLanguageVariantOneWayInspector.static.title = OO.ui.deferMsg(
	'visualeditor-mwlanguagevariantinspector-title-oneway'
);

/* Methods */

ve.ui.MWLanguageVariantOneWayInspector.prototype.initialize = function () {
	ve.ui.MWLanguageVariantOneWayInspector.super.prototype.initialize.call( this );
	this.items = [];
	this.layout = new OO.ui.FieldsetLayout( { } );
	this.form.$element.append( this.layout.$element );

	this.addButton = new OO.ui.ButtonInputWidget( {
		label: OO.ui.msg( 'visualeditor-mwlanguagevariantinspector-oneway-add-button' ),
		icon: 'add'
	} );
	this.form.$element.append( this.addButton.$element );

	// Events
	this.addButton.connect( this, { click: 'onAddButtonClick' } );
};

ve.ui.MWLanguageVariantOneWayInspector.prototype.getDefaultVariantInfo = function () {
	return { oneway: [ { f: '', l: mw.config.get( 'wgUserVariant' ), t: '' } ] };
};

ve.ui.MWLanguageVariantOneWayInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWLanguageVariantOneWayInspector.super.prototype.getSetupProcess.call( this, data ).next( function () {
		var variantInfo = this.variantNode.getVariantInfo();
		this.layout.clearItems();
		this.items = [];
		variantInfo.oneway.forEach( function ( ow, idx ) {
			this.items[ idx ] = this.createItem( ow.f, ow.l, ow.t );
			this.layout.addItems( [ this.items[ idx ].layout ] );
		}, this );
	}, this );
};

/**
 * Create widgets corresponding to a given mapping given by this rule.
 *
 * @param {string} [from] The HTML source text.
 * @param {string} [lang] The language code for the destination text.
 * @param {string} [to] The HTML destination text.
 * @return {Object} An object containing the required widgets and backing
 *  documents for this mapping item.
 */
ve.ui.MWLanguageVariantOneWayInspector.prototype.createItem = function ( from, lang, to ) {
	var fromTextTarget = this.createTextTarget( OO.ui.msg(
		'visualeditor-mwlanguagevariantinspector-oneway-from-text-placeholder'
	) );
	var languageInput = new ve.ui.LanguageInputWidget( {
		dialogManager: this.manager.getSurface().getDialogs(),
		dirInput: 'none'
	} );
	var toTextTarget = this.createTextTarget( OO.ui.msg(
		'visualeditor-mwlanguagevariantinspector-oneway-to-text-placeholder'
	) );
	var clearButton = new OO.ui.ButtonInputWidget( {
		icon: 'clear',
		title: OO.ui.deferMsg(
			'visualeditor-mwlanguagevariantinspector-oneway-clear-button'
		),
		framed: false
	} );
	var layout = new OO.ui.FieldLayout(
		new OO.ui.Widget( {
			content: [
				new OO.ui.ActionFieldLayout(
					fromTextTarget,
					clearButton
				),
				languageInput,
				toTextTarget
			]
		} ), {}
	);
	var item = {
		fromTextTarget: fromTextTarget,
		languageInput: languageInput,
		toTextTarget: toTextTarget,
		clearButton: clearButton,
		layout: layout
	};

	// Initialize
	item.fromTextTargetDoc = this.setupTextTargetDoc( fromTextTarget, from );
	item.toTextTargetDoc = this.setupTextTargetDoc( toTextTarget, to );
	languageInput.setLangAndDir( lang, 'auto' );
	clearButton.connect( this, { click: [ 'onClearButtonClick', item ] } );
	return item;
};

ve.ui.MWLanguageVariantOneWayInspector.prototype.getContentFromInspector = function ( variantInfo ) {
	// TODO should allow type to depend on targetHtmlDoc, maybe switch
	// from inline to block.
	var type = this.variantNode.getType();
	variantInfo.oneway = this.items.map( function ( item ) {
		return {
			f: this.getHtmlForDoc( item.fromTextTargetDoc ),
			l: item.languageInput.getLang(),
			t: this.getHtmlForDoc( item.toTextTargetDoc )
		};
	}, this );
	return [
		{
			type: type,
			attributes: { variantInfo: variantInfo }
		},
		{
			type: '/' + type
		}
	];
};

/**
 * Create a new mapping item in the inspector.
 */
ve.ui.MWLanguageVariantOneWayInspector.prototype.onAddButtonClick = function () {
	var idx = this.items.length;
	this.items[ idx ] = this.createItem( '', mw.config.get( 'wgUserVariant' ), '' );
	this.layout.addItems( [ this.items[ idx ].layout ] );
};

/**
 * Remove a mapping item from the inspector.
 *
 * @param {Object} item
 */
ve.ui.MWLanguageVariantOneWayInspector.prototype.onClearButtonClick = function ( item ) {
	var idx = this.items.indexOf( item );
	this.items.splice( idx, 1 );
	this.layout.removeItems( [ item.layout ] );
	item.clearButton.disconnect( this );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWLanguageVariantDisabledInspector );
ve.ui.windowFactory.register( ve.ui.MWLanguageVariantNameInspector );
ve.ui.windowFactory.register( ve.ui.MWLanguageVariantFilterInspector );
ve.ui.windowFactory.register( ve.ui.MWLanguageVariantTwoWayInspector );
ve.ui.windowFactory.register( ve.ui.MWLanguageVariantOneWayInspector );
