/*!
 * VisualEditor user interface MWTransclusionDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Dialog for inserting and editing MediaWiki transclusions, i.e. a sequence of one or more template
 * invocations that strictly belong to each other (e.g. because they are unbalanced), possibly
 * mixed with raw wikitext snippets.
 *
 * Note the base class {@see ve.ui.MWTemplateDialog} alone does not allow to manage more than a
 * single template invocation. Most of the code for this feature set is exclusive to this subclass.
 *
 * @class
 * @extends ve.ui.MWTemplateDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWTransclusionDialog = function VeUiMWTransclusionDialog( config ) {
	// Parent constructor
	ve.ui.MWTransclusionDialog.super.call( this, config );

	// Properties
	this.isSidebarExpanded = null;

	this.hotkeyTriggers = {};
	this.$element.on( 'keydown', this.onKeyDown.bind( this ) );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWTransclusionDialog, ve.ui.MWTemplateDialog );

/* Static Properties */

ve.ui.MWTransclusionDialog.static.name = 'transclusion';

ve.ui.MWTransclusionDialog.static.size = 'larger';

ve.ui.MWTransclusionDialog.static.actions = ve.ui.MWTemplateDialog.static.actions.concat( [
	{
		action: 'mode',
		modes: [ 'edit', 'insert' ],
		// HACK: Will be set later, but we want measurements to be accurate in the mean time, this
		// will not be needed when T93290 is resolved
		label: $( document.createTextNode( '\u00a0' ) )
	},
	{
		action: 'back',
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-goback' ),
		modes: [ 'edit', 'insert' ],
		flags: [ 'safe', 'back' ]
	}
] );

ve.ui.MWTransclusionDialog.static.smallScreenMaxWidth = 540;

/* Static Methods */

/**
 * @return {boolean}
 */
ve.ui.MWTransclusionDialog.static.isSmallScreen = function () {
	return $( window ).width() <= ve.ui.MWTransclusionDialog.static.smallScreenMaxWidth;
};

/* Methods */

/**
 * Handle outline controls move events.
 *
 * @private
 * @param {number} places Number of places to move the selected item
 */
ve.ui.MWTransclusionDialog.prototype.onOutlineControlsMove = function ( places ) {
	var part = this.transclusionModel.getPartFromId( this.bookletLayout.getSelectedTopLevelPartId() );
	if ( !part ) {
		return;
	}

	var newPlace = this.transclusionModel.getParts().indexOf( part ) + places;
	if ( newPlace < 0 || newPlace >= this.transclusionModel.getParts().length ) {
		return;
	}

	// Move part to new location, and if dialog is loaded switch to new part page
	var promise = this.transclusionModel.addPart( part, newPlace );
	if ( this.loaded && !this.preventReselection ) {
		// FIXME: Should be handled internally {@see ve.ui.MWTwoPaneTransclusionDialogLayout}
		promise.done( this.bookletLayout.focusPart.bind( this.bookletLayout, part.getId() ) );
	}
};

/**
 * Handle outline controls remove events.
 *
 * @private
 */
ve.ui.MWTransclusionDialog.prototype.onOutlineControlsRemove = function () {
	var controls = this.bookletLayout.getOutlineControls();
	// T301914: Safe-guard for when a keyboard shortcut triggers this, instead of the actual button
	if ( !controls.isVisible() ||
		!controls.removeButton.isVisible() ||
		controls.removeButton.isDisabled()
	) {
		return;
	}

	var partId = this.bookletLayout.getSelectedTopLevelPartId(),
		part = this.transclusionModel.getPartFromId( partId );
	if ( part ) {
		this.transclusionModel.removePart( part );
	}
};

/**
 * Create a new template part at the end of the transclusion.
 *
 * @private
 */
ve.ui.MWTransclusionDialog.prototype.addTemplatePlaceholder = function () {
	this.addPart( new ve.dm.MWTemplatePlaceholderModel( this.transclusionModel ) );
};

/**
 * Handle add wikitext button click or hotkey events.
 *
 * @private
 */
ve.ui.MWTransclusionDialog.prototype.addWikitext = function () {
	this.addPart( new ve.dm.MWTransclusionContentModel( this.transclusionModel ) );
};

/**
 * Handle add parameter hotkey events.
 *
 * @private
 * @param {jQuery.Event} e Key down event
 */
ve.ui.MWTransclusionDialog.prototype.addParameter = function ( e ) {
	// Check if the focus was in e.g. a parameter list or filter input when the hotkey was pressed
	var partId = this.bookletLayout.sidebar.findPartIdContainingElement( e.target ),
		part = this.transclusionModel.getPartFromId( partId );

	if ( !( part instanceof ve.dm.MWTemplateModel ) ) {
		// Otherwise add to the template that's currently selected via its title or parameter
		partId = this.bookletLayout.getTopLevelPartIdForSelection();
		part = this.transclusionModel.getPartFromId( partId );
	}

	if ( this.transclusionModel.isSingleTemplate() ) {
		part = this.transclusionModel.getParts()[ 0 ];
	}

	if ( !( part instanceof ve.dm.MWTemplateModel ) ) {
		return;
	}

	// TODO: Use a distinct class for placeholder model rather than
	// these magical "empty" constants.
	var placeholderParameter = new ve.dm.MWParameterModel( part );
	part.addParameter( placeholderParameter );
	this.bookletLayout.focusPart( placeholderParameter.getId() );

	this.autoExpandSidebar();
};

/**
 * @inheritdoc
 */
ve.ui.MWTransclusionDialog.prototype.onReplacePart = function ( removed, added ) {
	ve.ui.MWTransclusionDialog.super.prototype.onReplacePart.call( this, removed, added );
	var parts = this.transclusionModel.getParts();

	if ( parts.length === 0 ) {
		this.addPart( new ve.dm.MWTemplatePlaceholderModel( this.transclusionModel ) );
	} else if ( parts.length > 1 ) {
		this.$element.removeClass( 've-ui-mwTransclusionDialog-single-transclusion' );
	}

	// multipart message
	this.bookletLayout.stackLayout.$element.prepend( this.multipartMessage.$element );
	this.multipartMessage.toggle( parts.length > 1 );

	this.autoExpandSidebar();
	this.updateModeActionState();
	this.updateActionSet();
};

/**
 * @private
 */
ve.ui.MWTransclusionDialog.prototype.setupHotkeyTriggers = function () {
	// Lower-case modifier and key names as specified in {@see ve.ui.Trigger}
	var isMac = ve.getSystemPlatform() === 'mac',
		meta = isMac ? 'meta+' : 'ctrl+';
	var hotkeys = {
		addTemplate: meta + 'd',
		addWikitext: meta + 'shift+y',
		addParameter: meta + 'shift+d',
		moveUp: meta + 'shift+up',
		moveDown: meta + 'shift+down',
		remove: meta + 'delete',
		removeBackspace: meta + 'backspace'
	};

	var notInTextFields = /^(?!INPUT|TEXTAREA)/i;
	this.connectHotKeyBinding( hotkeys.addTemplate, this.addTemplatePlaceholder.bind( this ) );
	this.connectHotKeyBinding( hotkeys.addWikitext, this.addWikitext.bind( this ) );
	this.connectHotKeyBinding( hotkeys.addParameter, this.addParameter.bind( this ) );
	this.connectHotKeyBinding( hotkeys.moveUp, this.onOutlineControlsMove.bind( this, -1 ), notInTextFields );
	this.connectHotKeyBinding( hotkeys.moveDown, this.onOutlineControlsMove.bind( this, 1 ), notInTextFields );
	this.connectHotKeyBinding( hotkeys.remove, this.onOutlineControlsRemove.bind( this ), notInTextFields );
	if ( isMac ) {
		this.connectHotKeyBinding( hotkeys.removeBackspace, this.onOutlineControlsRemove.bind( this ), notInTextFields );
	}

	var controls = this.bookletLayout.getOutlineControls();
	this.addHotkeyToTitle( controls.addTemplateButton, hotkeys.addTemplate );
	this.addHotkeyToTitle( controls.addWikitextButton, hotkeys.addWikitext );
	this.addHotkeyToTitle( controls.upButton, hotkeys.moveUp );
	this.addHotkeyToTitle( controls.downButton, hotkeys.moveDown );
	this.addHotkeyToTitle( controls.removeButton, hotkeys.remove );
};

/**
 * @private
 * @param {string} hotkey
 * @param {Function} handler
 * @param {RegExp} [validTypes]
 */
ve.ui.MWTransclusionDialog.prototype.connectHotKeyBinding = function ( hotkey, handler, validTypes ) {
	this.hotkeyTriggers[ hotkey ] = {
		handler: handler,
		validTypes: validTypes
	};
};

/**
 * @private
 * @param {OO.ui.mixin.TitledElement} element
 * @param {string} hotkey
 */
ve.ui.MWTransclusionDialog.prototype.addHotkeyToTitle = function ( element, hotkey ) {
	// Separated with a space as in {@see OO.ui.Tool.updateTitle}
	element.setTitle( element.getTitle() + ' ' + new ve.ui.Trigger( hotkey ).getMessage() );
};

/**
 * Handles key down events.
 *
 * @protected
 * @param {jQuery.Event} e Key down event
 */
ve.ui.MWTransclusionDialog.prototype.onKeyDown = function ( e ) {
	var hotkey = new ve.ui.Trigger( e ).toString(),
		trigger = this.hotkeyTriggers[ hotkey ];

	if ( trigger && ( !trigger.validTypes || trigger.validTypes.test( e.target.nodeName ) ) ) {
		trigger.handler( e );
		e.preventDefault();
		e.stopPropagation();
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWTransclusionDialog.prototype.getPageFromPart = function ( part ) {
	var page = ve.ui.MWTransclusionDialog.super.prototype.getPageFromPart.call( this, part );
	if ( !page && part instanceof ve.dm.MWTransclusionContentModel ) {
		return new ve.ui.MWTransclusionContentPage( part, part.getId(), { $overlay: this.$overlay, isReadOnly: this.isReadOnly() } );
	}
	return page;
};

/**
 * Automatically expand or collapse the sidebar according to default logic.
 *
 * @protected
 */
ve.ui.MWTransclusionDialog.prototype.autoExpandSidebar = function () {
	var expandSidebar;

	var isSmallScreen = this.constructor.static.isSmallScreen();

	var showOtherActions = isSmallScreen ||
		this.actions.getOthers().some( function ( action ) {
			// Check for unknown actions, show the toolbar if any are available.
			return action.action !== 'mode';
		} );
	this.actions.forEach( { actions: [ 'mode' ] }, function ( action ) {
		action.toggle( isSmallScreen );
	} );
	this.$otherActions.toggleClass( 'oo-ui-element-hidden', !showOtherActions );

	if ( isSmallScreen && this.transclusionModel.isEmpty() ) {
		expandSidebar = false;
	} else if ( isSmallScreen &&
		// eslint-disable-next-line no-jquery/no-class-state
		this.$content.hasClass( 've-ui-mwTransclusionDialog-small-screen' )
	) {
		// We did this already. If the sidebar is visible or not is now the user's decision.
		return;
	} else {
		expandSidebar = !isSmallScreen;
	}

	this.$content.toggleClass( 've-ui-mwTransclusionDialog-small-screen', isSmallScreen );

	this.toggleSidebar( expandSidebar );
};

/**
 * Set if the sidebar is visible (which means the dialog is expanded), or collapsed.
 *
 * @param {boolean} expandSidebar
 * @private
 */
ve.ui.MWTransclusionDialog.prototype.toggleSidebar = function ( expandSidebar ) {
	if ( this.isSidebarExpanded === expandSidebar ) {
		return;
	}

	this.isSidebarExpanded = expandSidebar;
	this.$content
		.toggleClass( 've-ui-mwTransclusionDialog-collapsed', !expandSidebar )
		.toggleClass( 've-ui-mwTransclusionDialog-expanded', expandSidebar );

	this.bookletLayout.toggleOutline( expandSidebar );
	this.updateTitle();
	this.updateModeActionState();

	// HACK blur any active input so that its dropdown will be hidden and won't end
	// up being mispositioned
	this.$content.find( 'input:focus' ).trigger( 'blur' );

	if ( this.loaded && this.constructor.static.isSmallScreen() ) {
		var dialog = this;

		// Updates the page sizes when the menu is toggled using the button. This needs
		// to happen after the animation when the panel is visible.
		setTimeout( function () {
			dialog.bookletLayout.stackLayout.getItems().forEach( function ( page ) {
				if ( page instanceof ve.ui.MWParameterPage ) {
					page.updateSize();
				}
			} );
		}, OO.ui.theme.getDialogTransitionDuration() );

		// Reapply selection and scrolling when switching between panes.
		var selectedPage = this.bookletLayout.getCurrentPage();
		if ( selectedPage ) {
			var name = selectedPage.getName();
			// Align whichever panel is becoming visible, after animation completes.
			// TODO: Should hook onto an animation promise—but is this possible when pure CSS?
			setTimeout( function () {
				if ( expandSidebar ) {
					dialog.sidebar.setSelectionByPageName( name );
				} else {
					selectedPage.scrollElementIntoView( { alignToTop: true, padding: { top: 20 } } );
					if ( !OO.ui.isMobile() ) {
						selectedPage.focus();
					}
				}
			}, OO.ui.theme.getDialogTransitionDuration() );
		}
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWTransclusionDialog.prototype.updateTitle = function () {
	if ( !this.transclusionModel.isSingleTemplate() ) {
		this.title.setLabel( ve.msg( 'visualeditor-dialog-transclusion-title-edit-transclusion' ) );
	} else {
		// Parent method
		ve.ui.MWTransclusionDialog.super.prototype.updateTitle.call( this );
	}
};

/**
 * Update the state of the 'mode' action
 *
 * @private
 */
ve.ui.MWTransclusionDialog.prototype.updateModeActionState = function () {
	var isExpanded = this.isSidebarExpanded,
		label = ve.msg( isExpanded ?
			'visualeditor-dialog-transclusion-collapse-options' :
			'visualeditor-dialog-transclusion-expand-options' );

	this.actions.forEach( { actions: [ 'mode' ] }, function ( action ) {
		action.setLabel( label );
		action.$button.attr( 'aria-expanded', isExpanded ? 1 : 0 );
	} );

	// The button is only visible on very narrow screens, {@see autoExpandSidebar}.
	// It's always needed, except in the initial placeholder state.
	var isInitialState = !isExpanded && this.transclusionModel.isEmpty(),
		canCollapse = !isInitialState;
	this.actions.setAbilities( { mode: canCollapse } );
};

/**
 * Add a part to the transclusion.
 *
 * @param {ve.dm.MWTransclusionPartModel} part Part to add
 */
ve.ui.MWTransclusionDialog.prototype.addPart = function ( part ) {
	var parts = this.transclusionModel.getParts(),
		partId = this.bookletLayout.getTopLevelPartIdForSelection(),
		selectedPart = this.transclusionModel.getPartFromId( partId );
	// Insert after selected part, or at the end if nothing is selected
	var index = selectedPart ? parts.indexOf( selectedPart ) + 1 : parts.length;
	// Add the part, and if dialog is loaded switch to part page
	var promise = this.transclusionModel.addPart( part, index );
	if ( this.loaded && !this.preventReselection ) {
		promise.done( this.bookletLayout.focusPart.bind( this.bookletLayout, part.getId() ) );
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWTransclusionDialog.prototype.getActionProcess = function ( action ) {
	var willLoseProgress = this.getMode() === 'insert' ?
		// A new template with no parameters is not considered valuable.
		this.transclusionModel.containsValuableData() :
		// The user has changed a parameter, and is not on the template search page.
		( this.altered && !this.transclusionModel.isEmpty() );

	switch ( action ) {
		case 'back':
			return new OO.ui.Process( function () {
				if ( willLoseProgress ) {
					ve.ui.MWConfirmationDialog.static.confirm(
						'visualeditor-dialog-transclusion-back-confirmation-prompt',
						this.resetDialog.bind( this )
					);
				} else {
					this.resetDialog();
				}
			}, this );
		case 'mode':
			return new OO.ui.Process( function () {
				this.toggleSidebar( !this.isSidebarExpanded );
			}, this );
		case '':
			// close action
			if ( willLoseProgress ) {
				return new OO.ui.Process( function () {
					ve.ui.MWConfirmationDialog.static.confirm(
						'visualeditor-dialog-transclusion-close-confirmation-prompt',
						this.close.bind( this ) );
				}, this );
			}
	}
	return ve.ui.MWTransclusionDialog.super.prototype.getActionProcess.call( this, action );
};

/**
 * Update the widgets in the dialog's action bar.
 *
 * @private
 */
ve.ui.MWTransclusionDialog.prototype.updateActionSet = function () {
	var backButton = this.actions.get( { flags: [ 'back' ] } ).pop(),
		saveButton = this.actions.get( { actions: [ 'done' ] } ).pop();

	if ( saveButton && this.getMode() === 'edit' ) {
		saveButton.setLabel( ve.msg( 'visualeditor-dialog-transclusion-action-save' ) );
	}

	var closeButton = this.actions.get( { flags: [ 'close' ] } ).pop(),
		canGoBack = this.getMode() === 'insert' && this.canGoBack && !this.transclusionModel.isEmpty();

	closeButton.toggle( !canGoBack );
	backButton.toggle( canGoBack );
};

/**
 * Revert the dialog back to its initial state.
 *
 * @private
 */
ve.ui.MWTransclusionDialog.prototype.resetDialog = function () {
	var target = this;
	this.transclusionModel.reset();
	this.bookletLayout.clearPages();
	var placeholderPage = new ve.dm.MWTemplatePlaceholderModel( this.transclusionModel );
	this.transclusionModel.addPart( placeholderPage )
		.done( function () {
			target.bookletLayout.focusPart( placeholderPage.getId() );
			target.autoExpandSidebar();
		} );
};

/**
 * @inheritdoc
 */
ve.ui.MWTransclusionDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.MWTransclusionDialog.super.prototype.initialize.call( this );

	this.setupHotkeyTriggers();

	// multipart message gets attached in onReplacePart()
	this.multipartMessage = new OO.ui.MessageWidget( {
		label: mw.message( 'visualeditor-dialog-transclusion-multipart-message' ).parseDom(),
		classes: [ 've-ui-mwTransclusionDialog-multipart-message' ]
	} );
	ve.targetLinksToNewWindow( this.multipartMessage.$element[ 0 ] );

	var helpPopup = new ve.ui.MWFloatingHelpElement( {
		label: mw.message( 'visualeditor-dialog-transclusion-help-title' ).text(),
		title: mw.message( 'visualeditor-dialog-transclusion-help-title' ).text(),
		$message: new OO.ui.FieldsetLayout( {
			items: [
				new OO.ui.LabelWidget( {
					label: mw.message( 'visualeditor-dialog-transclusion-help-message' ).text()
				} ),
				this.getMessageButton( 'visualeditor-dialog-transclusion-help-page-help', 'helpNotice' ),
				this.getMessageButton( 'visualeditor-dialog-transclusion-help-page-shortcuts', 'keyboard' )
			],
			classes: [ 've-ui-mwTransclusionDialog-floatingHelpElement-fieldsetLayout' ]
		} ).$element
	} );
	helpPopup.$element.addClass( 've-ui-mwTransclusionDialog-floatingHelpElement' );
	helpPopup.$element.appendTo( this.$body );

	// Events
	this.getManager().connect( this, { resize: ve.debounce( this.onWindowResize.bind( this ) ) } );
	this.bookletLayout.getOutlineControls().connect( this, {
		addTemplate: 'addTemplatePlaceholder',
		addWikitext: 'addWikitext',
		move: 'onOutlineControlsMove',
		remove: 'onOutlineControlsRemove'
	} );
};

/**
 * @inheritdoc
 */
ve.ui.MWTransclusionDialog.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWTransclusionDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.bookletLayout.getOutlineControls().toggle( !this.transclusionModel.isSingleTemplate() );
			this.$element.toggleClass(
				've-ui-mwTransclusionDialog-single-transclusion',
				this.transclusionModel.isSingleTemplate()
			);

			this.updateModeActionState();
			this.autoExpandSidebar();

			if ( !this.transclusionModel.isSingleTemplate() ) {
				this.sidebar.hideAllUnusedParameters();
			}
			// We can do this only after the widget is visible on screen
			this.sidebar.initializeAllStickyHeaderHeights();
		}, this );
};

/**
 * @private
 */
ve.ui.MWTransclusionDialog.prototype.onWindowResize = function () {
	if ( this.transclusionModel ) {
		this.autoExpandSidebar();

		this.bookletLayout.getPagesOrdered().forEach( function ( page ) {
			if ( page instanceof ve.ui.MWParameterPage ) {
				page.updateSize();
			}
		} );
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWTransclusionDialog.prototype.getSizeProperties = function () {
	return ve.extendObject(
		{ height: '90%' },
		ve.ui.MWTransclusionDialog.super.prototype.getSizeProperties.call( this )
	);
};

/**
 * Converts a message link into an OO.ui.ButtonWidget with an icon.
 *
 * @private
 * @param {string} message i18n message key
 * @param {string} icon icon name
 * @return {OO.ui.ButtonWidget}
 */
ve.ui.MWTemplateDialog.prototype.getMessageButton = function ( message, icon ) {
	// Messages that can be used here:
	// * visualeditor-dialog-transclusion-help-page-help
	// * visualeditor-dialog-transclusion-help-page-shortcuts
	var $link = mw.message( message ).parseDom(),
		button = new OO.ui.ButtonWidget( {
			label: $link.text(),
			href: $link.attr( 'href' ),
			target: '_blank',
			flags: 'progressive',
			icon: icon,
			framed: false
		} );
	button.$button.attr( 'role', 'link' );
	return button;
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWTransclusionDialog );
