/*!
 * VisualEditor DataModel Surface class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel surface.
 *
 * @class
 * @extends ve.ce.Surface
 *
 * @constructor
 * @param {ve.dm.Surface} model
 * @param {ve.ui.Surface} ui
 * @param {Object} [config]
 */
ve.ce.MWWikitextSurface = function VeCeMwWikitextSurface() {
	// Parent constructors
	ve.ce.MWWikitextSurface.super.apply( this, arguments );

	this.pasteTargetInput = new OO.ui.MultilineTextInputWidget();
};

/* Inheritance */

OO.inheritClass( ve.ce.MWWikitextSurface, ve.ce.Surface );

/**
 * @inheritdoc
 */
ve.ce.MWWikitextSurface.prototype.onCopy = function ( e ) {
	var view = this,
		clipboardData = e.originalEvent.clipboardData,
		text = this.getModel().getFragment().getText( true ).replace( /\n\n/g, '\n' );

	if ( !text ) {
		return;
	}

	if ( clipboardData ) {
		// Disable the default event so we can override the data
		e.preventDefault();
		clipboardData.setData( 'text/plain', text );
		// We're not going to set HTML, but for browsers that support custom data, set a clipboard key
		if ( ve.isClipboardDataFormatsSupported( e, true ) ) {
			var slice = this.model.documentModel.shallowCloneFromSelection( this.getModel().getSelection() );
			this.clipboardIndex++;
			var clipboardKey = this.clipboardId + '-' + this.clipboardIndex;
			this.clipboard = { slice: slice, hash: null };
			// Clone the elements in the slice
			slice.data.cloneElements( true );
			clipboardData.setData( 'text/xcustom', clipboardKey );

			// Explicitly store wikitext as text/x-wiki, so that wikitext-aware paste
			// contexts can accept it without having to do any content-
			// sniffing.
			clipboardData.setData( 'text/x-wiki', text );
		}
	} else {
		var originalSelection = new ve.SelectionState( this.nativeSelection );

		// Save scroll position before changing focus to "offscreen" paste target
		var scrollTop = this.surface.$scrollContainer.scrollTop();

		// Prevent surface observation due to native range changing
		this.surfaceObserver.disable();
		this.$pasteTarget.empty().append( this.pasteTargetInput.$element );
		this.pasteTargetInput.setValue( text ).select();

		// Restore scroll position after changing focus
		this.surface.$scrollContainer.scrollTop( scrollTop );

		// setTimeout: postpone until after the default copy action
		setTimeout( function () {
			// Change focus back
			view.$attachedRootNode[ 0 ].focus();
			view.showSelectionState( originalSelection );
			// Restore scroll position
			view.surface.$scrollContainer.scrollTop( scrollTop );
			view.surfaceObserver.clear();
			view.surfaceObserver.enable();
			// Detach input
			view.pasteTargetInput.$element.detach();
		} );
	}
};

/**
 * @inheritdoc
 */
ve.ce.MWWikitextSurface.prototype.afterPasteInsertExternalData = function ( targetFragment, pastedDocumentModel, contextRange ) {
	var wasSpecial = this.pasteSpecial,
		// TODO: This check returns true if the paste contains meaningful structure (tables, lists etc.)
		// but no annotations (bold, links etc.).
		wasPlain = wasSpecial || pastedDocumentModel.data.isPlainText( contextRange, true, undefined, true ),
		view = this;

	var plainPastedDocumentModel = pastedDocumentModel.shallowCloneFromRange( contextRange );
	plainPastedDocumentModel.data.sanitize( { plainText: true, keepEmptyContentBranches: true } );
	// We just turned this into plaintext, which probably
	// affected the content-length. Luckily, because of
	// the earlier clone, we know we just want the whole
	// document, and because of the major change to
	// plaintext, the difference between originalRange and
	// balancedRange don't really apply. As such, clear
	// out newDocRange. (Can't just make it undefined;
	// need to exclude the internal list, and since we're
	// from a paste we also have to exclude the
	// opening/closing paragraph.)
	var plainContextRange = new ve.Range( plainPastedDocumentModel.getDocumentRange().from + 1, plainPastedDocumentModel.getDocumentRange().to - 1 );
	this.pasteSpecial = true;

	// isPlainText is true but we still need sanitize (e.g. remove lists)
	var promise = ve.ce.MWWikitextSurface.super.prototype.afterPasteInsertExternalData.call( this, targetFragment, plainPastedDocumentModel, plainContextRange );
	if ( !wasPlain ) {
		promise.then( function () {
			// We need to wait for the selection change after paste as that triggers
			// a contextChange event. Really we should wait for the afterPaste promise to resolve.
			setTimeout( function () {
				var surface = view.getSurface(),
					context = surface.getContext();
				// Ensure surface is deactivated on mobile so context can be shown (T336073)
				if ( context.isMobile() ) {
					surface.getView().deactivate();
				}
				context.addPersistentSource( {
					embeddable: false,
					name: 'wikitextPaste',
					data: {
						doc: pastedDocumentModel,
						contextRange: contextRange,
						fragment: targetFragment
					}
				} );
				surface.getModel().once( 'select', function () {
					context.removePersistentSource( 'wikitextPaste' );
				} );
			} );
		} );
	}
	return promise;
};
