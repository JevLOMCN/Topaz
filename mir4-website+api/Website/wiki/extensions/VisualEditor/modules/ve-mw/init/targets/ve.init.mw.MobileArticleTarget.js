/*!
 * VisualEditor MediaWiki Initialization MobileArticleTarget class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * @class VisualEditorOverlay
 * TODO: Use @-external when we switch to JSDoc
 */

/**
 * MediaWiki mobile article target.
 *
 * @class
 * @extends ve.init.mw.ArticleTarget
 *
 * @constructor
 * @param {VisualEditorOverlay} overlay Mobile frontend overlay
 * @param {Object} [config] Configuration options
 * @cfg {Object} [toolbarConfig]
 * @cfg {string|null} [section] Number of the section target should scroll to
 */
ve.init.mw.MobileArticleTarget = function VeInitMwMobileArticleTarget( overlay, config ) {
	this.overlay = overlay;
	this.$overlay = overlay.$el;
	this.$overlaySurface = overlay.$el.find( '.surface' );

	config = config || {};
	config.toolbarConfig = ve.extendObject( {
		actions: false
	}, config.toolbarConfig );

	// Parent constructor
	ve.init.mw.MobileArticleTarget.super.call( this, config );

	// eslint-disable-next-line no-jquery/no-global-selector
	this.$editableContent = $( '#mw-content-text' );

	if ( config.section !== undefined ) {
		this.section = config.section;
	}

	// Initialization
	this.$element.addClass( 've-init-mw-mobileArticleTarget ve-init-mobileTarget' );
};

/* Inheritance */

OO.inheritClass( ve.init.mw.MobileArticleTarget, ve.init.mw.ArticleTarget );

/* Static Properties */

ve.init.mw.MobileArticleTarget.static.toolbarGroups = [
	// History
	{
		name: 'history',
		include: [ 'undo' ]
	},
	// Style
	{
		name: 'style',
		classes: [ 've-test-toolbar-style' ],
		type: 'list',
		icon: 'textStyle',
		title: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
		label: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
		invisibleLabel: true,
		include: [ { group: 'textStyle' }, 'language', 'clear' ],
		forceExpand: [ 'bold', 'italic', 'clear' ],
		promote: [ 'bold', 'italic' ],
		demote: [ 'strikethrough', 'code', 'underline', 'language', 'clear' ]
	},
	// Link
	{
		name: 'link',
		include: [ 'link' ]
	},
	// Placeholder for reference tools (e.g. Cite and/or Citoid)
	{
		name: 'reference'
	}
];

ve.init.mw.MobileArticleTarget.static.trackingName = 'mobile';

// FIXME Some of these users will be on tablets, check for this
ve.init.mw.MobileArticleTarget.static.platformType = 'phone';

/* Methods */

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.deactivateSurfaceForToolbar = function () {
	// Parent method
	ve.init.mw.MobileArticleTarget.super.prototype.deactivateSurfaceForToolbar.call( this );

	if ( this.wasSurfaceActive && ve.init.platform.constructor.static.isIos() ) {
		this.prevScrollPosition = this.$scrollContainer.scrollTop();
	}
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.activateSurfaceForToolbar = function () {
	// Parent method
	ve.init.mw.MobileArticleTarget.super.prototype.activateSurfaceForToolbar.call( this );

	if ( this.wasSurfaceActive && ve.init.platform.constructor.static.isIos() ) {
		// Setting the cursor can cause unwanted scrolling on iOS, so manually
		// restore the scroll offset from before the toolbar was opened (T218650).
		this.$scrollContainer.scrollTop( this.prevScrollPosition );
	}
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.clearSurfaces = function () {
	if ( ve.init.platform.constructor.static.isIos() && this.viewportZoomHandler ) {
		this.viewportZoomHandler.detach();
		this.viewportZoomHandler = null;
	}
	// Parent method
	ve.init.mw.MobileArticleTarget.super.prototype.clearSurfaces.call( this );
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.onContainerScroll = function () {
	var target = this,
		// Editor may not have loaded yet, in which case `this.surface` is undefined
		surfaceView = this.surface && this.surface.getView(),
		isActiveWithKeyboard = surfaceView && surfaceView.isFocused() && !surfaceView.isDeactivated();

	// On iOS Safari, when the keyboard is open, the layout viewport reported by the browser is not
	// updated to match the real viewport reduced by the keyboard (diagram: T218414#5027607). On all
	// modern non-iOS browsers the layout viewport is updated to match real viewport.
	//
	// This allows the fixed toolbar to be scrolled out of view, ignoring `position: fixed` (because
	// it refers to the layout viewport).
	//
	// When this happens, bring it back in by scrolling down a bit and back up until the top of the
	// fake viewport is aligned with the top of the real viewport.

	clearTimeout( this.onContainerScrollTimer );
	if ( !isActiveWithKeyboard ) {
		return;
	}

	// Wait until after the scroll, because 'scroll' events are not emitted for every frame the
	// browser paints, so the toolbar would lag behind in a very unseemly manner. Additionally,
	// getBoundingClientRect returns incorrect values during scrolling, so make sure to calculate
	// it only after the scrolling ends (https://openradar.appspot.com/radar?id=6668472289329152).
	var animateToolbarIntoView;
	this.onContainerScrollTimer = setTimeout( animateToolbarIntoView = function () {
		if ( target.toolbarAnimating ) {
			// We can't do this while the 'transform' transition is happening, because
			// getBoundingClientRect() returns values that reflect that (and are negative).
			return;
		}

		var $header = target.overlay.$el.find( '.overlay-header-container' );

		// Check if toolbar is offscreen. In a better world, this would reject all negative values
		// (pos >= 0), but getBoundingClientRect often returns funny small fractional values after
		// this function has done its job (which triggers another 'scroll' event) and before the
		// user scrolled again. If we allowed it to run, it would trigger a hilarious loop! Toolbar
		// being 1px offscreen is not a big deal anyway.
		var pos = $header[ 0 ].getBoundingClientRect().top;
		if ( pos >= -1 ) {
			return;
		}

		// We don't know how much we have to scroll because we don't know how large the real
		// viewport is. This value is bigger than the screen height of all iOS devices.
		var viewportHeight = 2000;
		// OK so this one is really weird. Normally on iOS, the scroll position is set on <body>.
		// But on our sites, when using iOS 13, it's on <html> instead - maybe due to some funny
		// CSS we set on html and body? Anyway, this seems to work...
		var scrollY = document.body.scrollTop || document.documentElement.scrollTop;
		var scrollX = document.body.scrollLeft || document.documentElement.scrollLeft;

		// Prevent the scrolling we're about to do from triggering this event handler again.
		target.toolbarAnimating = true;

		var $overlaySurface = target.$overlaySurface;
		// Scroll down and translate the surface by the same amount, otherwise the content at new
		// scroll position visibly flashes.
		$overlaySurface.css( 'transform', 'translateY( ' + viewportHeight + 'px )' );
		window.scroll( scrollX, scrollY + viewportHeight );

		// Prepate to animate toolbar sliding into view
		$header.removeClass( 'toolbar-shown toolbar-shown-done' );
		var headerHeight = $header[ 0 ].offsetHeight;
		var headerTranslateY = Math.max( -headerHeight, pos );
		$header.css( 'transform', 'translateY( ' + headerTranslateY + 'px )' );

		// The scroll back up must be after a delay, otherwise no scrolling happens and the
		// viewports are not aligned.
		setTimeout( function () {
			// Scroll back up
			$overlaySurface.css( 'transform', '' );
			window.scroll( scrollX, scrollY );

			// Animate toolbar sliding into view
			$header.addClass( 'toolbar-shown' ).css( 'transform', '' );
			setTimeout( function () {
				$header.addClass( 'toolbar-shown-done' );
				// Wait until the animation is done before allowing this event handler to trigger again
				target.toolbarAnimating = false;
				// Re-check after the animation is done, in case the user scrolls in the meantime.
				animateToolbarIntoView();
				// The animation takes 250ms but we need to wait longer for some reason…
				// 'transitionend' event also doesn't seem to work reliably.
			}, 300 );
			// If the delays below are made any smaller, the weirdest graphical glitches happen,
			// so don't mess with them
		}, 50 );
	}, 250 );
};

/**
 * Handle surface scroll events
 */
ve.init.mw.MobileArticleTarget.prototype.onSurfaceScroll = function () {
	if ( ve.init.platform.constructor.static.isIos() && this.getSurface() ) {
		// iOS has a bug where if you change the scroll offset of a
		// contentEditable or textarea with a cursor visible, it disappears.
		// This function works around it by removing and reapplying the selection.
		var nativeSelection = this.getSurface().getView().nativeSelection;
		if ( nativeSelection.rangeCount && document.activeElement.contentEditable === 'true' ) {
			var range = nativeSelection.getRangeAt( 0 );
			nativeSelection.removeAllRanges();
			nativeSelection.addRange( range );
		}
	}
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.createSurface = function ( dmDoc, config ) {
	if ( this.overlay.isNewPage ) {
		config = ve.extendObject( {
			placeholder: this.overlay.options.placeholder
		}, config );
	}

	// Parent method
	var surface = ve.init.mw.MobileArticleTarget
		.super.prototype.createSurface.call( this, dmDoc, config );

	surface.connect( this, { scroll: 'onSurfaceScroll' } );

	return surface;
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.getSurfaceClasses = function () {
	var classes = ve.init.mw.MobileArticleTarget.super.prototype.getSurfaceClasses.call( this );
	return classes.concat( [ 'content' ] );
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.setSurface = function ( surface ) {
	var changed = surface !== this.surface;

	// Parent method
	// FIXME This actually skips ve.init.mw.Target.prototype.setSurface. Why?
	ve.init.mw.Target.super.prototype.setSurface.apply( this, arguments );

	if ( changed ) {
		this.$overlaySurface.append( surface.$element );
	}
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.surfaceReady = function () {
	if ( this.teardownPromise ) {
		// Loading was cancelled, the overlay is already closed at this point. Do nothing.
		// Otherwise e.g. scrolling from #goToHeading would kick in and mess things up.
		return;
	}

	// Deactivate the surface so any initial selection set in surfaceReady
	// listeners doesn't cause the keyboard to be shown.
	this.getSurface().getView().deactivate( false );

	// Parent method
	ve.init.mw.MobileArticleTarget.super.prototype.surfaceReady.apply( this, arguments );

	// If no selection has been set yet, set it to the start of the document.
	if ( this.getSurface().getModel().getSelection().isNull() ) {
		this.getSurface().getView().selectFirstSelectableContentOffset();
	}

	this.events.trackActivationComplete();

	if ( ve.init.platform.constructor.static.isIos() ) {
		if ( this.viewportZoomHandler ) {
			this.viewportZoomHandler.detach();
		}
		this.viewportZoomHandler = new ve.init.mw.ViewportZoomHandler();
		this.viewportZoomHandler.attach( this.getSurface() );
	}
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.afterSurfaceReady = function () {
	this.adjustContentPadding();

	// Parent method
	ve.init.mw.MobileArticleTarget.super.prototype.afterSurfaceReady.apply( this, arguments );
};

/**
 * Match the content padding to the toolbar height
 */
ve.init.mw.MobileArticleTarget.prototype.adjustContentPadding = function () {
	var surface = this.getSurface(),
		surfaceView = surface.getView(),
		toolbarHeight = this.getToolbar().$element[ 0 ].clientHeight;

	surface.setPadding( {
		top: toolbarHeight
	} );
	surfaceView.$attachedRootNode.css( 'padding-top', toolbarHeight );
	surface.$placeholder.css( 'padding-top', toolbarHeight );
	surfaceView.emit( 'position' );
	surface.scrollSelectionIntoView();
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.switchToFallbackWikitextEditor = function ( modified ) {
	var dataPromise;
	if ( modified ) {
		dataPromise = this.getWikitextDataPromiseForDoc( modified ).then( function ( response ) {
			var content = ve.getProp( response, 'visualeditoredit', 'content' );
			return { text: content };
		} );
	}
	this.overlay.switchToSourceEditor( dataPromise );
	return dataPromise;
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.save = function () {
	// Parent method
	var promise = ve.init.mw.MobileArticleTarget.super.prototype.save.apply( this, arguments );

	this.overlay.log( {
		action: 'saveAttempt'
	} );

	return promise;
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.showSaveDialog = function () {
	// Parent method
	ve.init.mw.MobileArticleTarget.super.prototype.showSaveDialog.apply( this, arguments );

	this.overlay.log( {
		action: 'saveIntent'
	} );
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.replacePageContent = function (
	html, categoriesHtml, displayTitle, lastModified /* , contentSub, sections */
) {
	// Parent method
	ve.init.mw.MobileArticleTarget.super.prototype.replacePageContent.apply( this, arguments );

	if ( lastModified ) {
		// TODO: Update the last-modified-bar with the correct info
		// eslint-disable-next-line no-jquery/no-global-selector
		$( '.last-modified-bar' ).remove();
	}
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.saveComplete = function ( data ) {
	// Set 'saved' flag before teardown (which is called in parent method) to avoid prompts
	// This is set in this.overlay.onSaveComplete, but we can't call that until we have
	// computed the fragment.
	this.overlay.saved = true;

	// Parent method
	ve.init.mw.MobileArticleTarget.super.prototype.saveComplete.apply( this, arguments );

	var fragment = this.getSectionHashFromPage().slice( 1 );

	this.overlay.sectionId = fragment;
	this.overlay.onSaveComplete( data.newrevid, data.tempusercreatedredirect, data.tempusercreated );
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.saveFail = function ( doc, saveData, code, data ) {
	// parent method
	ve.init.mw.MobileArticleTarget.super.prototype.saveFail.apply( this, arguments );

	this.overlay.onSaveFailure( data );
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.tryTeardown = function () {
	this.overlay.onExitClick( $.Event() );
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.setupToolbar = function ( surface ) {
	var originalToolbarGroups = this.toolbarGroups;

	// We don't want any of these tools to show up in subordinate widgets, so we
	// temporarily add them here. We need to do it _here_ rather than in their
	// own static variable to make sure that other tools which meddle with
	// toolbarGroups (Cite, mostly) have a chance to do so.
	this.toolbarGroups = [].concat(
		[
			// Back
			{
				name: 'back',
				include: [ 'back' ]
			}
		],
		this.toolbarGroups,
		[
			{
				name: 'editMode',
				type: 'list',
				icon: 'edit',
				title: ve.msg( 'visualeditor-mweditmode-tooltip' ),
				label: ve.msg( 'visualeditor-mweditmode-tooltip' ),
				invisibleLabel: true,
				include: [ { group: 'editMode' } ]
			},
			{
				name: 'save',
				type: 'bar',
				include: [ 'showSave' ]
			}
		]
	);

	// Parent method
	ve.init.mw.MobileArticleTarget.super.prototype.setupToolbar.call( this, surface );

	this.toolbarGroups = originalToolbarGroups;

	this.toolbar.$element.addClass( 've-init-mw-mobileArticleTarget-toolbar' );
	this.toolbar.$popups.addClass( 've-init-mw-mobileArticleTarget-toolbar-popups' );
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.attachToolbar = function () {
	// Move the toolbar to the overlay header
	this.overlay.$el.find( '.overlay-header > .toolbar' ).append( this.toolbar.$element );
	this.toolbar.initialize();
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.setupToolbarSaveButton = function () {
	this.toolbarSaveButton = this.toolbar.getToolGroupByName( 'save' ).items[ 0 ];
};

/**
 * @inheritdoc
 */
ve.init.mw.MobileArticleTarget.prototype.goToHeading = function ( headingNode ) {
	this.scrollToHeading( headingNode );
};

/**
 * Done with the editing toolbar
 */
ve.init.mw.MobileArticleTarget.prototype.done = function () {
	this.getSurface().getModel().setNullSelection();
	this.getSurface().getView().blur();
};

/* Registration */

ve.init.mw.targetFactory.register( ve.init.mw.MobileArticleTarget );
