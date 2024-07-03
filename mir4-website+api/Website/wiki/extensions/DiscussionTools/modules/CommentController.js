var
	controller = require( './controller.js' ),
	modifier = require( './modifier.js' ),
	dtConf = require( './config.json' ),
	CommentItem = require( './CommentItem.js' ),
	scrollPadding = {
		// eslint-disable-next-line no-jquery/no-class-state
		top: 10 + ( $( document.documentElement ).hasClass( 'vector-feature-sticky-header-enabled' ) ? 50 : 0 ),
		bottom: 10
	},
	defaultVisual = controller.defaultVisual,
	enable2017Wikitext = controller.enable2017Wikitext;
/**
 * Handles setup, save and teardown of commenting widgets
 *
 * @param {jQuery} $pageContainer Page container
 * @param {ThreadItem} threadItem Thread item to attach new comment to
 * @param {ThreadItemSet} threadItemSet
 * @param {MemoryStorage} storage Storage object for autosave
 */
function CommentController( $pageContainer, threadItem, threadItemSet, storage ) {
	// Mixin constructors
	OO.EventEmitter.call( this );

	this.isTornDown = false;
	this.$pageContainer = $pageContainer;
	this.threadItem = threadItem;
	this.threadItemSet = threadItemSet;
	this.storage = storage;
	this.newListItem = null;
	this.replyWidgetPromise = null;
	this.newComments = [];
	this.parentRemoved = false;
	this.oldId = mw.config.get( 'wgRevisionId' );
	this.pollTimeout = null;
	this.onVisibilityChangeHandler = this.onVisibilityChange.bind( this );
}

OO.initClass( CommentController );
OO.mixinClass( CommentController, OO.EventEmitter );

/* CommentController private utilities */

/**
 * Get the latest revision ID of the page.
 *
 * @param {string} pageName
 * @return {jQuery.Promise}
 */
function getLatestRevId( pageName ) {
	return controller.getApi().get( {
		action: 'query',
		prop: 'revisions',
		rvprop: 'ids',
		rvlimit: 1,
		titles: pageName
	} ).then( function ( resp ) {
		return resp.query.pages[ 0 ].revisions[ 0 ].revid;
	} );
}

/**
 * Like #checkThreadItemOnPage, but assumes the comment was found on the current page,
 * and then follows transclusions to determine the source page where it is written.
 *
 * @return {jQuery.Promise} Promise which resolves with a CommentDetails object, or rejects with an error
 */
CommentController.prototype.getTranscludedFromSource = function () {
	var pageName = mw.config.get( 'wgRelevantPageName' ),
		oldId = mw.config.get( 'wgCurRevisionId' ),
		threadItem = this.getThreadItem();

	function followTransclusion( recursionLimit, code, data ) {
		var errorData;
		if ( recursionLimit > 0 && code === 'comment-is-transcluded' ) {
			errorData = data.errors[ 0 ].data;
			if ( errorData.follow && typeof errorData.transcludedFrom === 'string' ) {
				return getLatestRevId( errorData.transcludedFrom ).then( function ( latestRevId ) {
					// Fetch the transcluded page, until we cross the recursion limit
					return controller.checkThreadItemOnPage( errorData.transcludedFrom, latestRevId, threadItem )
						.catch( followTransclusion.bind( null, recursionLimit - 1 ) );
				} );
			}
		}
		return $.Deferred().reject( code, data );
	}

	// Arbitrary limit of 10 steps, which should be more than anyone could ever need
	// (there are reasonable use cases for at least 2)
	var promise = controller.checkThreadItemOnPage( pageName, oldId, threadItem )
		.catch( followTransclusion.bind( null, 10 ) );

	return promise;
};

/* Static properties */

CommentController.static.initType = 'page';

/* Events */

/**
 * The current widget has been torn down
 *
 * @event teardown
 * @param string mode Teardown mode. See dt.ui.ReplyWidget#teardown.
 */

/* Methods */

/**
 * Create and setup the reply widget
 *
 * @param {string} [mode] Optionally force a mode, 'visual' or 'source'
 * @param {boolean} [hideErrors] Suppress errors, e.g. when restoring auto-save
 * @param {boolean} [suppressNotifications] Don't notify the user if recovering auto-save
 */
CommentController.prototype.setup = function ( mode, hideErrors, suppressNotifications ) {
	var threadItem = this.getThreadItem(),
		commentController = this;

	if ( mode === undefined ) {
		mode = mw.user.options.get( 'discussiontools-editmode' ) ||
			( defaultVisual ? 'visual' : 'source' );
	}

	mw.track( 'editAttemptStep', {
		action: 'init',
		type: this.constructor.static.initType || 'page',
		mechanism: 'click',
		integration: 'discussiontools',
		// eslint-disable-next-line camelcase
		editor_interface: mode === 'visual' ? 'visualeditor' :
			( enable2017Wikitext ? 'wikitext-2017' : 'wikitext' )
	} );

	if ( !this.replyWidgetPromise ) {
		this.replyWidgetPromise = this.getTranscludedFromSource().then( function ( commentDetails ) {
			return commentController.createReplyWidget( commentDetails, { mode: mode } );
		}, function ( code, data ) {
			commentController.onReplyWidgetTeardown();

			if ( !hideErrors ) {
				OO.ui.alert(
					code instanceof Error ? code.toString() : controller.getApi().getErrorMessage( data ),
					{ size: 'medium' }
				);
				mw.track( 'dt.commentSetupError', code );
			}

			mw.track( 'editAttemptStep', {
				action: 'abort',
				type: 'preinit'
			} );

			commentController.replyWidgetPromise = null;

			return $.Deferred().reject();
		} );

		// On first load, add a placeholder list item
		commentController.newListItem = modifier.addListItem( threadItem, dtConf.replyIndentation );
		if ( commentController.newListItem.tagName.toLowerCase() === 'li' ) {
			// When using bullet syntax, hide the marker. (T259864#7634107)
			$( commentController.newListItem ).addClass( 'ext-discussiontools-init-noMarker' );
		}
		$( commentController.newListItem ).append(
			// Microsoft Edge's built-in translation feature replaces the entire element when it finishes
			// translating it, which often happens after our interface has loaded, clobbering it, unless
			// we wrap this loading message in another element.
			$( '<span>' ).text( mw.msg( 'discussiontools-replywidget-loading' ) )
		);
		var scrollPaddingCollapsed = OO.copy( scrollPadding );
		// We don't know exactly how tall the widge will be, but leave room for one line
		// of preview in source mode (~270px). Visual mode is ~250px.
		scrollPaddingCollapsed.bottom += 270;
		OO.ui.Element.static.scrollIntoView( commentController.newListItem, {
			padding: scrollPaddingCollapsed
		} );

		// Disable section collapsing on mobile. If the section were collapsed it would be hard to
		// find your comment again. The "Return to reply" tool is broken by section collapsing as
		// the reply widget is hidden and therefore not measureable. It's also possible the page is
		// not long enough to trigger the "Return to reply" tool.
		$( this.newListItem ).parents( '.collapsible-block' ).prev().addClass( 'collapsible-heading-disabled' );
	}

	if (
		this.threadItem instanceof CommentItem &&
		this.threadItem.getSubscribableHeading()
	) {
		// Use the revision ID of the content on the page, not wgCurRevisionId
		// This means you will more likely get a refresh warning when deliberately
		// viewing old revisions, which is helpful.
		this.startPoll();
		$( document ).on( 'visibilitychange', this.onVisibilityChangeHandler );
	}

	this.replyWidgetPromise.then( function ( replyWidget ) {
		if ( !commentController.newListItem ) {
			// On subsequent loads, there's no list item yet, so create one now
			commentController.newListItem = modifier.addListItem( threadItem, dtConf.replyIndentation );
			if ( commentController.newListItem.tagName.toLowerCase() === 'li' ) {
				// When using bullet syntax, hide the marker. (T259864#7634107)
				$( commentController.newListItem ).addClass( 'ext-discussiontools-init-noMarker' );
			}
		}
		$( commentController.newListItem ).empty().append( replyWidget.$element );

		commentController.setupReplyWidget( replyWidget, {}, suppressNotifications );

		commentController.showAndFocus();

		mw.track( 'editAttemptStep', { action: 'ready' } );
		mw.track( 'editAttemptStep', { action: 'loaded' } );
	} );
};

/**
 * Handle document visibilitychange events
 *
 * This allows us to pause polling when the user switches to another tab
 */
CommentController.prototype.onVisibilityChange = function () {
	if ( document.hidden ) {
		this.stopPoll();
	} else if ( !this.pollTimeout ) {
		this.pollTimeout = setTimeout( this.startPoll.bind( this ), 5000 );
	}
};

CommentController.prototype.startPoll = function () {
	var threadItemId = this.threadItem.id;
	var subscribableHeadingId = this.threadItem.getSubscribableHeading().id;
	var commentController = this;

	this.pollApiRequest = controller.getApi().get( {
		action: 'discussiontoolscompare',
		fromrev: this.oldId,
		totitle: mw.config.get( 'wgRelevantPageName' )
	} );
	this.pollApiRequest.then( function ( response ) {
		function relevantCommentFilter( cmt ) {
			return cmt.subscribableHeadingId === subscribableHeadingId &&
				// Ignore posts by yourself, if logged in
				cmt.author !== mw.user.getName();
		}

		var result = OO.getProp( response, 'discussiontoolscompare' ) || {};
		var addedComments = result.addedcomments.filter( relevantCommentFilter );
		var removedComments = result.removedcomments.filter( relevantCommentFilter );

		if ( addedComments.length || removedComments.length ) {
			commentController.updateNewCommentsWarning( addedComments, removedComments );
		}

		// Parent comment was deleted
		var isParentRemoved = result.removedcomments.some( function ( cmt ) {
			return cmt.id === threadItemId;
		} );
		// Parent comment was deleted then added back (e.g. reverted vandalism)
		var isParentAdded = result.addedcomments.some( function ( cmt ) {
			return cmt.id === threadItemId;
		} );

		if ( isParentAdded ) {
			commentController.setParentRemoved( false );
		} else if ( isParentRemoved ) {
			commentController.setParentRemoved( true );
		}

		commentController.oldId = result.torevid;
	} ).always( function () {
		if ( commentController.isTornDown ) {
			return;
		}
		commentController.pollTimeout = setTimeout( commentController.startPoll.bind( commentController ), 5000 );
	} );
};

CommentController.prototype.stopPoll = function () {
	if ( this.pollTimeout ) {
		clearTimeout( this.pollTimeout );
		this.pollTimeout = null;
	}
	if ( this.pollApiRequest ) {
		this.pollApiRequest.abort();
		this.pollApiRequest = null;
	}
};

/**
 * Get thread item this controller is attached to
 *
 * @return {ThreadItem} Thread item
 */
CommentController.prototype.getThreadItem = function () {
	return this.threadItem;
};

/**
 * Get the reply widget class to use in this controller
 *
 * @param {boolean} visual Prefer the VE-based class
 * @return {jQuery.Promise} Promise which resolves with a Function: the reply widget class
 */
CommentController.prototype.getReplyWidgetClass = function ( visual ) {
	// If 2017WTE mode is enabled, always use ReplyWidgetVisual.
	visual = visual || enable2017Wikitext;

	return mw.loader.using( controller.getReplyWidgetModules() ).then( function () {
		return require( 'ext.discussionTools.ReplyWidget' )[ visual ? 'ReplyWidgetVisual' : 'ReplyWidgetPlain' ];
	} );
};

/**
 * Create a reply widget
 *
 * @param {CommentDetails} commentDetails
 * @param {Object} config
 * @return {jQuery.Promise} Promise resolved with a ReplyWidget
 */
CommentController.prototype.createReplyWidget = function ( commentDetails, config ) {
	var commentController = this;

	return this.getReplyWidgetClass( config.mode === 'visual' ).then( function ( ReplyWidget ) {
		return new ReplyWidget( commentController, commentDetails, config );
	} );
};

CommentController.prototype.setupReplyWidget = function ( replyWidget, data, suppressNotifications ) {
	replyWidget.connect( this, {
		teardown: 'onReplyWidgetTeardown',
		reloadPage: this.emit.bind( this, 'reloadPage' )
	} );

	replyWidget.setup( data, suppressNotifications );
	replyWidget.updateNewCommentsWarning( this.newComments );
	replyWidget.updateParentRemovedError( this.parentRemoved );

	this.replyWidget = replyWidget;
};

CommentController.prototype.storeEditSummary = function () {
	if ( this.replyWidget ) {
		this.replyWidget.storage.set( 'summary', this.replyWidget.getEditSummary() );
	}
};

/**
 * Focus the first input field inside the controller.
 */
CommentController.prototype.focus = function () {
	this.replyWidget.focus();
};

/**
 * Scroll the widget into view and focus it
 */
CommentController.prototype.showAndFocus = function () {
	var commentController = this;
	if ( this.replyWidgetPromise ) {
		this.replyWidgetPromise.then( function ( replyWidget ) {
			replyWidget.scrollElementIntoView( { padding: scrollPadding } )
				.then( function () {
					commentController.focus();
				} );
		} );
	}
};

/**
 * Try to tear down the reply widget, if it is setup
 *
 * @return {jQuery.Promise} Resolves when the widget is torn down, rejects if it fails.
 */
CommentController.prototype.tryTeardown = function () {
	if ( this.replyWidgetPromise ) {
		return this.replyWidgetPromise.then( function ( replyWidget ) {
			return replyWidget.tryTeardown();
		} );
	}
	return $.Deferred().resolve().promise();
};

/**
 * Handle teardown events from the reply widget
 *
 * @param {string} mode Teardown mode. See dt.ui.ReplyWidget#teardown
 */
CommentController.prototype.onReplyWidgetTeardown = function ( mode ) {
	$( this.newListItem ).parents( '.collapsible-block' ).prev().removeClass( 'collapsible-heading-disabled' );

	if ( mode === 'refresh' ) {
		$( this.newListItem ).empty().append(
			$( '<span>' ).text( mw.msg( 'discussiontools-replywidget-loading' ) )
		);
	} else {
		modifier.removeAddedListItem( this.newListItem );
		this.newListItem = null;
	}

	this.stopPoll();
	$( document ).off( 'visibilitychange', this.onVisibilityChangeHandler );

	this.isTornDown = true;
	this.emit( 'teardown', mode );
};

/**
 * Get the parameters of the API query that can be used to post this comment.
 *
 * @param {string} pageName Title of the page to post on
 * @param {Object} checkboxes Value of the promise returned by controller#getCheckboxesPromise
 * @return {Object.<string,string>} API query data
 */
CommentController.prototype.getApiQuery = function ( pageName, checkboxes ) {
	var threadItem = this.getThreadItem();
	var replyWidget = this.replyWidget;
	var sameNameComments = this.threadItemSet.findCommentsByName( threadItem.name );

	var mode = replyWidget.getMode();
	var tags = [
		'discussiontools',
		'discussiontools-reply',
		'discussiontools-' + mode
	];

	if ( mode === 'source' && enable2017Wikitext ) {
		tags.push( 'discussiontools-source-enhanced' );
	}

	var data = {
		action: 'discussiontoolsedit',
		paction: 'addcomment',
		page: pageName,
		commentname: threadItem.name,
		// Only specify this if necessary to disambiguate, to avoid errors if the parent changes
		commentid: sameNameComments.length > 1 ? threadItem.id : undefined,
		summary: replyWidget.getEditSummary(),
		formtoken: replyWidget.getFormToken(),
		assert: mw.user.isAnon() ? 'anon' : 'user',
		assertuser: mw.user.getName() || undefined,
		uselang: mw.config.get( 'wgUserLanguage' ),
		// Pass through dtenable query string param from original request
		dtenable: new URLSearchParams( location.search ).get( 'dtenable' ) ? '1' : undefined,
		dttags: tags.join( ',' )
	};

	if ( replyWidget.getMode() === 'source' ) {
		data.wikitext = replyWidget.getValue();
	} else {
		data.html = replyWidget.getValue();
	}

	var captchaInput = replyWidget.captchaInput;
	if ( captchaInput ) {
		data.captchaid = captchaInput.getCaptchaId();
		data.captchaword = captchaInput.getCaptchaWord();
	}

	if ( checkboxes.checkboxesByName.wpWatchthis ) {
		data.watchlist = checkboxes.checkboxesByName.wpWatchthis.isSelected() ?
			'watch' :
			'unwatch';
	}

	return data;
};

/**
 * Save the comment in the comment controller
 *
 * @param {string} pageName Page title
 * @return {jQuery.Promise} Promise which resolves when the save is complete
 */
CommentController.prototype.save = function ( pageName ) {
	var replyWidget = this.replyWidget,
		commentController = this,
		threadItem = this.getThreadItem();

	return this.replyWidget.checkboxesPromise.then( function ( checkboxes ) {
		var data = commentController.getApiQuery( pageName, checkboxes );

		if (
			// We're saving the first comment on a page that previously didn't exist.
			// Don't fetch the new revision's HTML content, because we will reload the whole page.
			!mw.config.get( 'wgRelevantArticleId' ) ||
			// We're saving a comment on a different page than the one being viewed.
			// Don't fetch the new revision's HTML content, because we can't use it anyway.
			pageName !== mw.config.get( 'wgRelevantPageName' )
		) {
			data.nocontent = true;
		}

		if ( replyWidget.commentDetails.wouldAutoCreate ) {
			// This means that we might need to redirect to an opaque URL,
			// so we must set up query parameters we want ahead of time.
			data.returnto = pageName;
			var params = new URLSearchParams();
			params.set( 'dtrepliedto', commentController.getThreadItem().id );
			params.set( 'dttempusercreated', '1' );
			data.returntoquery = params.toString();
		}

		// No timeout. Huge talk pages can take a long time to save, and falsely reporting an error
		// could result in duplicate messages if the user retries. (T249071)
		var defaults = OO.copy( controller.getApi().defaults );
		defaults.ajax.timeout = 0;
		var noTimeoutApi = new mw.Api( defaults );

		return mw.libs.ve.targetSaver.postContent(
			data, { api: noTimeoutApi }
		).catch( function ( code, responseData ) {
			// Better user-facing error messages
			if ( code === 'editconflict' ) {
				return $.Deferred().reject( code, { errors: [ {
					code: code,
					html: mw.message( 'discussiontools-error-comment-conflict' ).parse()
				} ] } ).promise();
			}
			if (
				code === 'discussiontools-commentid-notfound' ||
				code === 'discussiontools-commentname-ambiguous' ||
				code === 'discussiontools-commentname-notfound'
			) {
				return $.Deferred().reject( code, { errors: [ {
					code: code,
					html: mw.message( 'discussiontools-error-comment-disappeared' ).parse()
				} ] } ).promise();
			}
			return $.Deferred().reject( code, responseData ).promise();
		} ).then( function ( responseData ) {
			controller.update( responseData, threadItem, pageName, replyWidget );
		} );
	} );
};

/**
 * Add a list of comment objects that are new on the page since it was last refreshed
 *
 * @param {Object[]} addedComments Array of JSON-serialized CommentItem's
 * @param {Object[]} removedComments Array of JSON-serialized CommentItem's
 */
CommentController.prototype.updateNewCommentsWarning = function ( addedComments, removedComments ) {
	var commentController = this;
	// Add new comments
	this.newComments.push.apply( this.newComments, addedComments );

	// Delete any comments which have since been deleted (e.g. posted then reverted)
	var removedCommentIds = removedComments.filter( function ( cmt ) {
		return cmt.id;
	} );
	this.newComments = this.newComments.filter( function ( cmt ) {
		// If comment ID is not in removedCommentIds, keep it
		return removedCommentIds.indexOf( cmt.id ) === -1;
	} );

	this.replyWidgetPromise.then( function ( replyWidget ) {
		replyWidget.updateNewCommentsWarning( commentController.newComments );
	} );
};

/**
 * Record whether the parent thread item has been removed
 *
 * @param {boolean} parentRemoved
 */
CommentController.prototype.setParentRemoved = function ( parentRemoved ) {
	var commentController = this;
	this.parentRemoved = parentRemoved;

	this.replyWidgetPromise.then( function ( replyWidget ) {
		replyWidget.updateParentRemovedError( commentController.parentRemoved );
	} );
};

/**
 * Switch reply widget to wikitext input
 *
 * @return {jQuery.Promise} Promise which resolves when switch is complete
 */
CommentController.prototype.switchToWikitext = function () {
	var oldWidget = this.replyWidget,
		target = oldWidget.replyBodyWidget.target,
		oldShowAdvanced = oldWidget.showAdvanced,
		oldEditSummary = oldWidget.getEditSummary(),
		previewDeferred = $.Deferred(),
		commentController = this;

	// TODO: We may need to pass oldid/etag when editing is supported
	var wikitextPromise = target.getWikitextFragment( target.getSurface().getModel().getDocument() );
	this.replyWidgetPromise = this.createReplyWidget(
		oldWidget.commentDetails,
		{ mode: 'source' }
	);

	return $.when( wikitextPromise, this.replyWidgetPromise ).then( function ( wikitext, replyWidget ) {
		// To prevent the "Reply" / "Cancel" buttons from shifting when the preview loads,
		// wait for the preview (but no longer than 500 ms) before swithing the editors.
		replyWidget.preparePreview( wikitext ).then( previewDeferred.resolve );
		setTimeout( previewDeferred.resolve, 500 );

		return previewDeferred.then( function () {
			// Teardown the old widget
			oldWidget.disconnect( commentController );
			oldWidget.teardown();

			// Swap out the DOM nodes
			oldWidget.$element.replaceWith( replyWidget.$element );

			commentController.setupReplyWidget( replyWidget, {
				value: wikitext,
				showAdvanced: oldShowAdvanced,
				editSummary: oldEditSummary
			} );

			// Focus the editor
			replyWidget.focus();
		} );
	} );
};

/**
 * Remove empty lines and add indent characters to convert the paragraphs in given wikitext to
 * definition list items, as customary in discussions.
 *
 * @param {string} wikitext
 * @param {string} indent Indent character, ':' or '*'
 * @return {string}
 */
CommentController.prototype.doIndentReplacements = function ( wikitext, indent ) {
	wikitext = modifier.sanitizeWikitextLinebreaks( wikitext );

	wikitext = wikitext.split( '\n' ).map( function ( line ) {
		return indent + line;
	} ).join( '\n' );

	return wikitext;
};

/**
 * Turn definition list items, customary in discussions, back into normal paragraphs, suitable for
 * the editing interface.
 *
 * @param {Node} rootNode Node potentially containing definition lists (modified in-place)
 */
CommentController.prototype.undoIndentReplacements = function ( rootNode ) {
	var children = Array.prototype.slice.call( rootNode.childNodes );
	// There may be multiple lists when some lines are template generated
	children.forEach( function ( child ) {
		if ( child.nodeType === Node.ELEMENT_NODE ) {
			// Unwrap list
			modifier.unwrapList( child );
		}
	} );
};

/**
 * Get the list of selectors that match nodes that can't be inserted in the comment. (We disallow
 * things that generate wikitext syntax that may conflict with list item syntax.)
 *
 * @return {Object.<string,string>} Map of type used for error messages (string) to CSS selector (string)
 */
CommentController.prototype.getUnsupportedNodeSelectors = function () {
	return {
		// Tables are almost always multi-line
		table: 'table',
		// Headings are converted to plain text before we can detect them:
		// `:==h2==` -> `<p>==h2==</p>`
		// heading: 'h1, h2, h3, h4, h5, h6',
		// Templates can be multiline
		template: '[typeof*="mw:Transclusion"]',
		// Extensions (includes references) can be multiline, could be supported later (T251633)
		extension: '[typeof*="mw:Extension"]'
		// Images are probably fine unless a multi-line caption was used (rare)
		// image: 'figure, figure-inline'
	};
};

/**
 * Switch reply widget to visual input
 *
 * @return {jQuery.Promise} Promise which resolves when switch is complete
 */
CommentController.prototype.switchToVisual = function () {
	var oldWidget = this.replyWidget,
		oldShowAdvanced = oldWidget.showAdvanced,
		oldEditSummary = oldWidget.getEditSummary(),
		wikitext = oldWidget.getValue(),
		commentController = this;

	// Replace wikitext signatures with a special marker recognized by DtDmMWSignatureNode
	// to render them as signature nodes in visual mode.
	wikitext = wikitext.replace(
		// Replace ~~~~ (four tildes), but not ~~~~~ (five tildes)
		/([^~]|^)~~~~([^~]|$)/g,
		'$1<span data-dtsignatureforswitching="1"></span>$2'
	);

	var parsePromise;
	if ( wikitext ) {
		wikitext = this.doIndentReplacements( wikitext, dtConf.replyIndentation === 'invisible' ? ':' : '*' );

		// Based on ve.init.mw.Target#parseWikitextFragment
		parsePromise = controller.getApi().post( {
			action: 'visualeditor',
			paction: 'parsefragment',
			page: oldWidget.pageName,
			wikitext: wikitext,
			pst: true
		} ).then( function ( response ) {
			return response && response.visualeditor.content;
		} );
	} else {
		parsePromise = $.Deferred().resolve( '' ).promise();
	}
	this.replyWidgetPromise = this.createReplyWidget(
		oldWidget.commentDetails,
		{ mode: 'visual' }
	);

	return $.when( parsePromise, this.replyWidgetPromise ).then( function ( html, replyWidget ) {
		var unsupportedSelectors = commentController.getUnsupportedNodeSelectors();

		var doc;
		if ( html ) {
			doc = replyWidget.replyBodyWidget.target.parseDocument( html );
			// Remove RESTBase IDs (T253584)
			mw.libs.ve.stripRestbaseIds( doc );
			// Check for tables, headings, images, templates
			for ( var type in unsupportedSelectors ) {
				if ( doc.querySelector( unsupportedSelectors[ type ] ) ) {
					var $msg = $( '<div>' ).html(
						mw.message(
							'discussiontools-error-noswitchtove',
							// The following messages are used here:
							// * discussiontools-error-noswitchtove-extension
							// * discussiontools-error-noswitchtove-table
							// * discussiontools-error-noswitchtove-template
							mw.msg( 'discussiontools-error-noswitchtove-' + type )
						).parse()
					);
					$msg.find( 'a' ).attr( {
						target: '_blank',
						rel: 'noopener'
					} );
					OO.ui.alert(
						$msg.contents(),
						{
							title: mw.msg( 'discussiontools-error-noswitchtove-title' ),
							size: 'medium'
						}
					);
					mw.track( 'visualEditorFeatureUse', {
						feature: 'editor-switch',
						action: 'dialog-prevent-show'
					} );

					return $.Deferred().reject().promise();
				}
			}
			commentController.undoIndentReplacements( doc.body );
		}

		// Teardown the old widget
		oldWidget.disconnect( commentController );
		oldWidget.teardown();

		// Swap out the DOM nodes
		oldWidget.$element.replaceWith( replyWidget.$element );

		commentController.setupReplyWidget( replyWidget, {
			value: doc,
			showAdvanced: oldShowAdvanced,
			editSummary: oldEditSummary
		} );

		// Focus the editor
		replyWidget.focus();
	} );
};

module.exports = CommentController;
