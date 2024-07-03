/*!
 * VisualEditor MediaWiki Initialization Target class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Initialization MediaWiki target.
 *
 * @class
 * @extends ve.init.Target
 *
 * @constructor
 * @param {Object} config
 * @cfg {string[]} [surfaceClasses=[]] Surface classes to apply
 */
ve.init.mw.Target = function VeInitMwTarget( config ) {
	this.surfaceClasses = config.surfaceClasses || [];

	// Parent constructor
	ve.init.mw.Target.super.call( this, config );

	this.active = false;
	this.pageName = mw.config.get( 'wgRelevantPageName' );
	this.recovered = false;
	this.fromEditedState = false;
	this.originalHtml = null;

	// Initialization
	this.$element.addClass( 've-init-mw-target' );
};

/* Inheritance */

OO.inheritClass( ve.init.mw.Target, ve.init.Target );

/* Static Properties */

/**
 * Symbolic name for this target class.
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.init.mw.Target.static.name = null;

ve.init.mw.Target.static.toolbarGroups = [
	{
		name: 'history',
		include: [ { group: 'history' } ]
	},
	{
		name: 'format',
		type: 'menu',
		title: OO.ui.deferMsg( 'visualeditor-toolbar-format-tooltip' ),
		include: [ { group: 'format' } ],
		promote: [ 'paragraph' ],
		demote: [ 'preformatted', 'blockquote', 'heading1' ]
	},
	{
		name: 'style',
		type: 'list',
		icon: 'textStyle',
		title: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
		label: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
		invisibleLabel: true,
		include: [ { group: 'textStyle' } ],
		forceExpand: [ 'bold', 'italic', 'clear' ],
		promote: [ 'bold', 'italic', 'superscript', 'subscript' ],
		demote: [ 'clear' ]
	},
	{
		name: 'link',
		include: [ 'link' ]
	},
	// Placeholder for reference tools (e.g. Cite and/or Citoid)
	{
		name: 'reference'
	},
	{
		name: 'structure',
		type: 'list',
		icon: 'listBullet',
		title: OO.ui.deferMsg( 'visualeditor-toolbar-structure' ),
		label: OO.ui.deferMsg( 'visualeditor-toolbar-structure' ),
		invisibleLabel: true,
		include: [ { group: 'structure' } ],
		demote: [ 'outdent', 'indent' ]
	},
	{
		name: 'insert',
		label: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		title: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		narrowConfig: {
			invisibleLabel: true,
			icon: 'add'
		},
		include: '*',
		forceExpand: [ 'media', 'transclusion', 'insertTable' ],
		promote: [ 'media', 'transclusion', 'insertTable' ]
	},
	{
		name: 'specialCharacter',
		include: [ 'specialCharacter' ]
	}
];

ve.init.mw.Target.static.importRules = ve.copy( ve.init.mw.Target.static.importRules );

ve.init.mw.Target.static.importRules.external.removeOriginalDomElements = true;

ve.init.mw.Target.static.importRules.external.blacklist = ve.extendObject( {
	// Annotations
	'textStyle/underline': true,
	'meta/language': true,
	'textStyle/datetime': true,
	'link/mwExternal': !mw.config.get( 'wgVisualEditorConfig' ).allowExternalLinkPaste,
	// Node
	article: true,
	section: true
}, ve.init.mw.Target.static.importRules.external.blacklist );

ve.init.mw.Target.static.importRules.external.htmlBlacklist.remove = ve.extendObject( {
	// TODO: Create a plugin system for extending the blacklist, so this code
	// can be moved to the Cite extension.
	// Remove reference numbers copied from MW read mode (T150418)
	'sup.reference:not( [typeof] )': true,
	// ...sometimes we need a looser match if the HTML has been mangled
	// in a third-party editor e.g. LibreOffice (T232461)
	'a[ href *= "#cite_note" ]': true
}, ve.init.mw.Target.static.importRules.external.htmlBlacklist.remove );

// This is required to prevent an invalid insertion (as mwHeading can only be at the root) (T339155)
// TODO: This should be handled by the DM based on ve.dm.MWHeadingNode.static.suggestedParentNodeTypes,
// rather than just throwing an exception.
// This would also not prevent pasting from a VE standalone editor as that is considered
// an internal paste.
ve.init.mw.Target.static.importRules.external.htmlBlacklist.unwrap = ve.extendObject( {
	'li h1, li h2, li h3, li h4, li h5, li h6': true,
	'blockquote h1, blockquote h2, blockquote h3, blockquote h4, blockquote h5, blockquote h6': true
}, ve.init.mw.Target.static.importRules.external.htmlBlacklist.unwrap );

/**
 * Type of integration. Used for event tracking.
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.init.mw.Target.static.integrationType = null;

/**
 * Type of platform. Used for event tracking.
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.init.mw.Target.static.platformType = null;

/* Static Methods */

/**
 * Fix the base URL from Parsoid if necessary.
 *
 * Absolutizes the base URL if it's relative, and sets a base URL based on wgArticlePath
 * if there was no base URL at all.
 *
 * @param {HTMLDocument} doc Parsoid document
 */
ve.init.mw.Target.static.fixBase = function ( doc ) {
	ve.fixBase( doc, document, ve.resolveUrl(
		// Don't replace $1 with the page name, because that'll break if
		// the page name contains a slash
		mw.config.get( 'wgArticlePath' ).replace( '$1', '' ),
		document
	) );
};

/**
 * @inheritdoc
 */
ve.init.mw.Target.static.createModelFromDom = function ( doc, mode, options ) {
	var conf = mw.config.get( 'wgVisualEditor' );

	options = ve.extendObject( {
		lang: conf.pageLanguageCode,
		dir: conf.pageLanguageDir
	}, options );

	// Parent method
	return ve.init.mw.Target.super.static.createModelFromDom.call( this, doc, mode, options );
};

// Deprecated alias
ve.init.mw.Target.prototype.createModelFromDom = function () {
	return this.constructor.static.createModelFromDom.apply( this.constructor.static, arguments );
};

/**
 * @inheritdoc
 * @param {string} documentString
 * @param {string} mode
 * @param {string|null} [section] Section. Use null to unwrap all sections.
 * @param {boolean} [onlySection=false] Only return the requested section, otherwise returns the
 *  whole document with just the requested section still wrapped (visual mode only).
 * @return {HTMLDocument|string} HTML document, or document string (source mode)
 */
ve.init.mw.Target.static.parseDocument = function ( documentString, mode, section, onlySection ) {
	var doc;
	if ( mode === 'source' ) {
		// Parent method
		doc = ve.init.mw.Target.super.static.parseDocument.call( this, documentString, mode );
	} else {
		doc = ve.createDocumentFromHtml( documentString );
		if ( section !== undefined ) {
			if ( onlySection ) {
				var sectionNode = doc.body.querySelector( '[data-mw-section-id="' + section + '"]' );
				doc.body.innerHTML = '';
				if ( sectionNode ) {
					doc.body.appendChild( sectionNode );
				}
			} else {
				// Strip Parsoid sections
				mw.libs.ve.unwrapParsoidSections( doc.body, section );
			}
		}
		// Strip legacy IDs, for example in section headings
		mw.libs.ve.stripParsoidFallbackIds( doc.body );
		// Re-duplicate deduplicated TemplateStyles, for correct rendering when editing a section or
		// when templates are removed during the edit
		mw.libs.ve.reduplicateStyles( doc.body );
		// Fix relative or missing base URL if needed
		this.fixBase( doc );
		// Test: Remove tags injected by plugins during parse (T298147)
		Array.prototype.forEach.call( doc.querySelectorAll( 'script' ), function ( element ) {
			function truncate( text, l ) {
				return text.length > l ? text.slice( 0, l ) + '…' : text;
			}
			var errorMessage = 'DOM content matching deny list found during parse:\n' + truncate( element.outerHTML, 100 ) +
				'\nContext:\n' + truncate( element.parentNode.outerHTML, 200 );
			mw.log.error( errorMessage );
			var err = new Error( errorMessage );
			err.name = 'VeDomDenyListWarning';
			mw.errorLogger.logError( err, 'error.visualeditor' );
			element.parentNode.removeChild( element );
		} );
	}

	return doc;
};

/* Methods */

/**
 * Handle both DOM and modules being loaded and ready.
 *
 * @param {HTMLDocument|string} doc HTML document or source text
 */
ve.init.mw.Target.prototype.documentReady = function ( doc ) {
	this.setupSurface( doc );
};

/**
 * Once surface is ready, initialize the UI
 *
 * @fires surfaceReady
 */
ve.init.mw.Target.prototype.surfaceReady = function () {
	this.emit( 'surfaceReady' );
};

/**
 * @deprecated Moved to mw.libs.ve.targetSaver.getHtml
 * @param {HTMLDocument} newDoc
 * @param {HTMLDocument} [oldDoc]
 * @return {string}
 */
ve.init.mw.Target.prototype.getHtml = function ( newDoc, oldDoc ) {
	OO.ui.warnDeprecation( 've.init.mw.Target#getHtml is deprecated. Use mw.libs.ve.targetSaver.getHtml.' );
	return mw.libs.ve.targetSaver.getHtml( newDoc, oldDoc );
};

/**
 * Track an event
 *
 * @param {string} name Event name
 */
ve.init.mw.Target.prototype.track = function () {};

/**
 * Get a list of CSS classes to be added to surfaces, and target widget surfaces
 *
 * @return {string[]} CSS classes
 */
ve.init.mw.Target.prototype.getSurfaceClasses = function () {
	return this.surfaceClasses;
};

/**
 * @inheritdoc
 */
ve.init.mw.Target.prototype.createTargetWidget = function ( config ) {
	return new ve.ui.MWTargetWidget( ve.extendObject( {
		// Reset to visual mode for target widgets
		modes: [ 'visual' ],
		defaultMode: 'visual',
		toolbarGroups: this.toolbarGroups.filter( function ( group ) {
			return group.align !== 'after';
		} ),
		surfaceClasses: this.getSurfaceClasses()
	}, config ) );
};

/**
 * @inheritdoc
 */
ve.init.mw.Target.prototype.createSurface = function ( dmDoc, config ) {
	if ( config && config.mode === 'source' ) {
		var importRules = ve.copy( this.constructor.static.importRules );
		importRules.all = importRules.all || {};
		// Preserve empty linebreaks on paste in source editor
		importRules.all.keepEmptyContentBranches = true;
		config = this.getSurfaceConfig( ve.extendObject( {}, config, {
			importRules: importRules
		} ) );
		return new ve.ui.MWWikitextSurface( this, dmDoc, config );
	}

	return new ve.ui.MWSurface( this, dmDoc, this.getSurfaceConfig( config ) );
};

/**
 * @inheritdoc
 */
ve.init.mw.Target.prototype.getSurfaceConfig = function ( config ) {
	// If we're not asking for a specific mode's config, use the default mode.
	config = ve.extendObject( { mode: this.defaultMode }, config );
	// eslint-disable-next-line mediawiki/class-doc
	return ve.init.mw.Target.super.prototype.getSurfaceConfig.call( this, ve.extendObject( {
		// Provide the wikitext versions of the registries, if we're using source mode
		commandRegistry: config.mode === 'source' ? ve.ui.wikitextCommandRegistry : ve.ui.commandRegistry,
		sequenceRegistry: config.mode === 'source' ? ve.ui.wikitextSequenceRegistry : ve.ui.sequenceRegistry,
		dataTransferHandlerFactory: config.mode === 'source' ? ve.ui.wikitextDataTransferHandlerFactory : ve.ui.dataTransferHandlerFactory,
		classes: this.getSurfaceClasses()
	}, config ) );
};

/**
 * Switch to editing mode.
 *
 * @param {HTMLDocument|string} doc HTML document or source text
 */
ve.init.mw.Target.prototype.setupSurface = function ( doc ) {
	var target = this;
	setTimeout( function () {
		// Build model
		target.track( 'trace.convertModelFromDom.enter' );
		var dmDoc = target.constructor.static.createModelFromDom( doc, target.getDefaultMode() );
		target.track( 'trace.convertModelFromDom.exit' );

		// Build DM tree now (otherwise it gets lazily built when building the CE tree)
		target.track( 'trace.buildModelTree.enter' );
		dmDoc.buildNodeTree();
		target.track( 'trace.buildModelTree.exit' );

		setTimeout( function () {
			target.addSurface( dmDoc );
		} );
	} );
};

/**
 * @inheritdoc
 */
ve.init.mw.Target.prototype.addSurface = function () {
	var target = this;

	// Clear dummy surfaces
	// TODO: Move to DesktopArticleTarget
	this.clearSurfaces();

	// Create ui.Surface (also creates ce.Surface and dm.Surface and builds CE tree)
	this.track( 'trace.createSurface.enter' );
	// Parent method
	var surface = ve.init.mw.Target.super.prototype.addSurface.apply( this, arguments );
	// Add classes specific to surfaces attached directly to the target,
	// as opposed to TargetWidget surfaces
	if ( !surface.inTargetWidget ) {
		surface.$element.addClass( 've-init-mw-target-surface' );
	}
	this.track( 'trace.createSurface.exit' );

	this.setSurface( surface );

	setTimeout( function () {
		// Initialize surface
		target.track( 'trace.initializeSurface.enter' );

		target.active = true;
		// Now that the surface is attached to the document and ready,
		// let it initialize itself
		surface.initialize();

		target.track( 'trace.initializeSurface.exit' );
		target.surfaceReady();
	} );

	return surface;
};

/**
 * @inheritdoc
 */
ve.init.mw.Target.prototype.setSurface = function ( surface ) {
	if ( !surface.$element.parent().length ) {
		this.$element.append( surface.$element );
	}

	// Parent method
	ve.init.mw.Target.super.prototype.setSurface.apply( this, arguments );
};

/**
 * Intialise autosave, recovering changes if applicable
 *
 * @param {Object} [config] Configuration options
 * @cfg {boolean} [suppressNotification=false] Don't notify the user if changes are recovered
 * @cfg {string} [docId] Document ID for storage grouping
 * @cfg {ve.init.SafeStorage} [storage] Storage interface
 * @cfg {number} [storageExpiry] Storage expiry time in seconds (optional)
 */
ve.init.mw.Target.prototype.initAutosave = function ( config ) {
	var target = this;

	// Old function signature
	// TODO: Remove after fixed downstream
	if ( typeof config === 'boolean' ) {
		config = { suppressNotification: config };
	} else {
		config = config || {};
	}

	var surfaceModel = this.getSurface().getModel();

	if ( config.docId ) {
		surfaceModel.setAutosaveDocId( config.docId );
	}

	if ( config.storage ) {
		surfaceModel.setStorage( config.storage, config.storageExpiry );
	}

	if ( this.recovered ) {
		// Restore auto-saved transactions if document state was recovered
		try {
			surfaceModel.restoreChanges();
			if ( !config.suppressNotification ) {
				ve.init.platform.notify(
					ve.msg( 'visualeditor-autosave-recovered-text' ),
					ve.msg( 'visualeditor-autosave-recovered-title' )
				);
			}
		} catch ( e ) {
			mw.log.warn( e );
			ve.init.platform.notify(
				ve.msg( 'visualeditor-autosave-not-recovered-text' ),
				ve.msg( 'visualeditor-autosave-not-recovered-title' ),
				{ type: 'error' }
			);
		}
	} else {
		// ...otherwise store this document state for later recovery
		if ( this.fromEditedState ) {
			// Store immediately if the document was previously edited
			// (e.g. in a different mode)
			this.storeDocState( this.originalHtml );
		} else {
			// Only store after the first change if this is an unmodified document
			surfaceModel.once( 'undoStackChange', function () {
				// Check the surface hasn't been destroyed
				if ( target.getSurface() ) {
					target.storeDocState( target.originalHtml );
				}
			} );
		}
	}
	// Start auto-saving transactions
	surfaceModel.startStoringChanges();
	// TODO: Listen to autosaveFailed event to notify user
};

/**
 * Store a snapshot of the current document state.
 *
 * @param {string} [html] Document HTML, will generate from current state if not provided
 */
ve.init.mw.Target.prototype.storeDocState = function ( html ) {
	var mode = this.getSurface().getMode();
	this.getSurface().getModel().storeDocState( { mode: mode }, html );
};

/**
 * Clear any stored document state
 */
ve.init.mw.Target.prototype.clearDocState = function () {
	if ( this.getSurface() ) {
		this.getSurface().getModel().removeDocStateAndChanges();
	}
};

/**
 * @inheritdoc
 */
ve.init.mw.Target.prototype.teardown = function () {
	// If target is closed cleanly (after save or deliberate close) then remove autosave state
	this.clearDocState();

	// Parent method
	return ve.init.mw.Target.super.prototype.teardown.call( this );
};

/**
 * Refresh our knowledge about the logged-in user.
 *
 * This should be called in response to a user assertion error, to look up
 * the new user name, and update the current user variables in mw.config.
 *
 * @param {ve.dm.Document} [doc] Document to associate with the API request
 * @return {jQuery.Promise} Promise resolved with new username, or null if anonymous
 */
ve.init.mw.Target.prototype.refreshUser = function ( doc ) {
	return this.getContentApi( doc ).get( {
		action: 'query',
		meta: 'userinfo'
	} ).then( function ( data ) {
		var userInfo = data.query && data.query.userinfo;

		if ( userInfo.anon !== undefined ) {
			// New session is an anonymous user
			mw.config.set( {
				// wgUserId is unset for anonymous users, not set to null
				wgUserId: undefined,
				// wgUserName is explicitly set to null for anonymous users,
				// functions like mw.user.isAnon rely on this.
				wgUserName: null
			} );

			// Call this only after clearing wgUserId, otherwise it does nothing
			return mw.user.acquireTempUserName();
		} else {
			// New session is a logged in user (or a temporary user)
			mw.config.set( {
				wgUserId: userInfo.id,
				wgUserName: userInfo.name
			} );

			return mw.user.getName();
		}
	} );
};

/**
 * Get a wikitext fragment from a document
 *
 * @param {ve.dm.Document} doc
 * @param {boolean} [useRevision=true] Whether to use the revision ID + ETag
 * @return {jQuery.Promise} Abortable promise which resolves with a wikitext string
 */
ve.init.mw.Target.prototype.getWikitextFragment = function ( doc, useRevision ) {
	// Shortcut for empty document
	if ( !doc.data.hasContent() ) {
		return ve.createDeferred().resolve( '' );
	}

	var params = {
		action: 'visualeditoredit',
		paction: 'serialize',
		html: mw.libs.ve.targetSaver.getHtml(
			ve.dm.converter.getDomFromModel( doc )
		),
		page: this.getPageName()
	};

	if ( useRevision === undefined || useRevision ) {
		params.oldid = this.revid;
		params.etag = this.etag;
	}

	var xhr = this.getContentApi( doc ).postWithToken( 'csrf',
		params,
		{ contentType: 'multipart/form-data' }
	);

	return xhr.then( function ( response ) {
		if ( response.visualeditoredit ) {
			return response.visualeditoredit.content;
		}
		return ve.createDeferred().reject();
	} ).promise( { abort: xhr.abort } );
};

/**
 * Parse a fragment of wikitext into HTML
 *
 * @param {string} wikitext
 * @param {boolean} pst Perform pre-save transform
 * @param {ve.dm.Document} [doc] Parse for a specific document, defaults to current surface's
 * @return {jQuery.Promise} Abortable promise
 */
ve.init.mw.Target.prototype.parseWikitextFragment = function ( wikitext, pst, doc ) {
	var target = this;
	var abortable, aborted;
	var abortedPromise = ve.createDeferred().reject( 'http',
		{ textStatus: 'abort', exception: 'abort' } ).promise();

	function abort() {
		aborted = true;
		if ( abortable && abortable.abort ) {
			abortable.abort();
		}
	}

	// Acquire a temporary user username before previewing or diffing, so that signatures and
	// user-related magic words display the temp user instead of IP user in the preview. (T331397)
	var tempUserNamePromise;
	if ( pst ) {
		tempUserNamePromise = mw.user.acquireTempUserName();
	} else {
		tempUserNamePromise = ve.createDeferred().resolve( null );
	}

	return tempUserNamePromise
		.then( function () {
			if ( aborted ) {
				return abortedPromise;
			}
			return ( abortable = target.getContentApi( doc ).post( {
				action: 'visualeditor',
				paction: 'parsefragment',
				page: target.getPageName( doc ),
				wikitext: wikitext,
				pst: pst
			} ) );
		} )
		.promise( { abort: abort } );
};

/**
 * Get the page name associated with a specific document
 *
 * @param {ve.dm.Document} [doc] Document, defaults to current surface's
 * @return {string} Page name
 */
ve.init.mw.Target.prototype.getPageName = function () {
	return this.pageName;
};

/**
 * Get an API object associated with the wiki where the document
 * content is hosted.
 *
 * This would be overridden if editing content on another wiki.
 *
 * @param {ve.dm.Document} [doc] API for a specific document, should default to document of current surface.
 * @param {Object} [options] API options
 * @param {Object} [options.parameters] Default query parameters for all API requests. Defaults
 *  include action=query, format=json, and formatversion=2 if not specified otherwise.
 * @return {mw.Api}
 */
ve.init.mw.Target.prototype.getContentApi = function ( doc, options ) {
	options = options || {};
	options.parameters = ve.extendObject( { formatversion: 2 }, options.parameters );
	return new mw.Api( options );
};

/**
 * Get an API object associated with the local wiki.
 *
 * For example you would always use getLocalApi for actions
 * associated with the current user.
 *
 * @param {Object} [options] API options
 * @return {mw.Api}
 */
ve.init.mw.Target.prototype.getLocalApi = function ( options ) {
	options = options || {};
	options.parameters = ve.extendObject( { formatversion: 2 }, options.parameters );
	return new mw.Api( options );
};
