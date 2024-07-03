/*!
 * VisualEditor UserInterface AnnotationInspector class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Inspector for working with content annotations.
 *
 * @class
 * @abstract
 * @extends ve.ui.FragmentInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.AnnotationInspector = function VeUiAnnotationInspector() {
	// Parent constructor
	ve.ui.AnnotationInspector.super.apply( this, arguments );

	// Properties
	this.initialSelection = null;
	this.initialAnnotation = null;
	this.initialAnnotationIsCovering = false;
};

/* Inheritance */

OO.inheritClass( ve.ui.AnnotationInspector, ve.ui.FragmentInspector );

/**
 * Annotation models this inspector can edit.
 *
 * @static
 * @inheritable
 * @property {Function[]}
 */
ve.ui.AnnotationInspector.static.modelClasses = [];

/* Methods */

/**
 * Check if form is empty, which if saved should result in removing the annotation.
 *
 * Only override this if the form provides the user a way to blank out primary information, allowing
 * them to remove the annotation by clearing the form.
 *
 * @return {boolean} Form is empty
 */
ve.ui.AnnotationInspector.prototype.shouldRemoveAnnotation = function () {
	return false;
};

/**
 * Work out whether the teardown process should replace the current text of the fragment.
 *
 * Default behavior is to only do so if nothing was selected initially, in which case we
 * need *something* to apply the annotation to. If this returns true, getInsertionData had
 * better produce something.
 *
 * @return {boolean} Whether to insert text on teardown
 */
ve.ui.AnnotationInspector.prototype.shouldInsertText = function () {
	return !this.isEditing();
};

/**
 * Get data to insert if nothing was selected when the inspector opened.
 *
 * Defaults to using #getInsertionText.
 *
 * @return {string[]} Linear model content to insert
 */
ve.ui.AnnotationInspector.prototype.getInsertionData = function () {
	return this.getInsertionText().split( '' );
};

/**
 * Get text to insert if nothing was selected when the inspector opened.
 *
 * @return {string} Text to insert
 */
ve.ui.AnnotationInspector.prototype.getInsertionText = function () {
	if ( this.sourceMode ) {
		return OO.ui.resolveMsg( this.constructor.static.title );
	}
	return '';
};

/**
 * Get the annotation object to apply.
 *
 * This method is called when the inspector is closing, and should return the annotation to apply
 * to the text. If this method returns a falsey value like null, no annotation will be applied,
 * but existing annotations won't be removed either.
 *
 * @abstract
 * @return {ve.dm.Annotation} Annotation to apply
 */
ve.ui.AnnotationInspector.prototype.getAnnotation = null;

/**
 * Get an annotation object from a fragment.
 *
 * @abstract
 * @param {ve.dm.SurfaceFragment} fragment Surface fragment
 * @return {ve.dm.Annotation|null}
 */
ve.ui.AnnotationInspector.prototype.getAnnotationFromFragment = null;

/**
 * Get matching annotations within a fragment.
 *
 * @param {ve.dm.SurfaceFragment} fragment Fragment to get matching annotations within
 * @param {boolean} [all] Get annotations which only cover some of the fragment
 * @return {ve.dm.AnnotationSet} Matching annotations
 */
ve.ui.AnnotationInspector.prototype.getMatchingAnnotations = function ( fragment, all ) {
	var modelClasses = this.constructor.static.modelClasses;

	return fragment.getAnnotations( all ).filter( function ( annotation ) {
		return ve.isInstanceOfAny( annotation, modelClasses );
	} );
};

/**
 * @inheritdoc ve.ui.FragmentWindow
 */
ve.ui.AnnotationInspector.prototype.isEditing = function () {
	// If initialSelection isn't set yet, default to assume we are editing,
	// especially for the check in FragmentWindow#getSetupProcess
	return !this.initialSelection || !this.initialSelection.isCollapsed();
};

/**
 * Handle the inspector being setup.
 *
 * There are 4 scenarios:
 *
 * - Zero-length selection not near a word -> no change, text will be inserted on close
 * - Zero-length selection inside or adjacent to a word -> expand selection to cover word
 * - Selection covering non-annotated text -> trim selection to remove leading/trailing whitespace
 * - Selection covering annotated text -> expand selection to cover annotation
 *
 * @param {Object} [data] Inspector opening data
 * @param {boolean} [data.noExpand] Don't expand the selection when opening
 * @return {OO.ui.Process}
 */
ve.ui.AnnotationInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.AnnotationInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var fragment = this.getFragment(),
				surfaceModel = fragment.getSurface(),
				// Partial annotations will be expanded later
				annotation = this.getMatchingAnnotations( fragment, true ).get( 0 );

			surfaceModel.pushStaging();

			// Only supports linear selections
			if ( !( this.initialFragment && this.initialFragment.getSelection() instanceof ve.dm.LinearSelection ) ) {
				return ve.createDeferred().reject().promise();
			}

			// Initialize range
			if ( !annotation ) {
				// No matching annotations found:
				// If collapsed and at a content offset, try to expand the selection
				if (
					fragment.getSelection().isCollapsed() &&
					fragment.getDocument().data.isContentOffset( fragment.getSelection().getRange().start )
				) {
					// Expand to nearest word
					if ( !data.noExpand ) {
						fragment = fragment.expandLinearSelection( 'word' );
						// If we expanded, check for matching annotations again
						if ( !fragment.getSelection().isCollapsed() ) {
							annotation = this.getMatchingAnnotations( fragment, true ).get( 0 );
						}
					}
					// TODO: We should review how getMatchingAnnotation works in light of the fact
					// that in the case of a collapsed range, the method falls back to retrieving
					// insertion annotations.
				} else {
					// New expanded selection: trim whitespace
					fragment = fragment.trimLinearSelection();
				}

				// Selection expanded, but still no annotation, create one from the selection
				if ( !fragment.getSelection().isCollapsed() && !annotation ) {
					this.isNew = true;
					annotation = this.getAnnotationFromFragment( fragment );
					if ( annotation ) {
						fragment.annotateContent( 'set', annotation );
					}
				}
			}

			// Existing annotation only partially selection: expand to cover annotation
			if ( annotation && !data.noExpand ) {
				fragment = fragment.expandLinearSelection( 'annotation', annotation );
			}

			// Update selection
			fragment.select();
			this.initialSelection = fragment.getSelection();

			// The initial annotation is the first matching annotation in the fragment
			this.initialAnnotation = this.getMatchingAnnotations( fragment, true ).get( 0 );
			var initialCoveringAnnotation = this.getMatchingAnnotations( fragment ).get( 0 );
			// Fallback to a default annotation
			if ( !this.initialAnnotation ) {
				this.isNew = true;
				this.initialAnnotation = this.getAnnotationFromFragment( fragment );
			} else if (
				initialCoveringAnnotation &&
				initialCoveringAnnotation.compareTo( this.initialAnnotation )
			) {
				// If the initial annotation doesn't cover the fragment, record this as we'll need
				// to forcefully apply it to the rest of the fragment later
				this.initialAnnotationIsCovering = true;
			}

			// Update fragment property
			this.fragment = fragment;

			// Duplicate calls from FragmentWindow#getSetupProcess after
			// changing the fragment
			this.actions.setMode( this.getMode() );
			// isEditing is true when we are applying a new annotation because a
			// stub is applied immediately, so use isNew instead
			if ( this.isNew && this.isReadOnly() ) {
				return ve.createDeferred().reject().promise();
			}
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.AnnotationInspector.prototype.getTeardownProcess = function ( data ) {
	data = data || {};
	return ve.ui.AnnotationInspector.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			var inspector = this,
				insertionAnnotation = false,
				replace = false,
				annotation = this.getAnnotation(),
				remove = data.action === 'done' && this.shouldRemoveAnnotation(),
				surfaceModel = this.fragment.getSurface(),
				surfaceView = this.manager.getSurface().getView(),
				fragment = surfaceModel.getFragment( this.initialSelection, false ),
				isEditing = this.isEditing(),
				insertText = !remove && this.shouldInsertText();

			var annotations;
			var insertion;

			function clear() {
				// Clear all existing annotations
				annotations = inspector.getMatchingAnnotations( fragment, true ).get();
				for ( var i = 0, len = annotations.length; i < len; i++ ) {
					fragment.annotateContent( 'clear', annotations[ i ] );
				}
			}

			if ( remove ) {
				surfaceModel.popStaging();
				if ( !isEditing ) {
					return;
				}
				clear();
			} else {
				if ( data.action !== 'done' ) {
					surfaceModel.popStaging();
					if ( this.initialFragment ) {
						this.initialFragment.select();
					}
					return;
				}
				if ( annotation ) {
					// Check if the initial annotation has changed, or didn't cover the whole fragment
					// to begin with
					if (
						!this.initialAnnotationIsCovering ||
						!this.initialAnnotation ||
						!this.initialAnnotation.compareTo( annotation )
					) {
						replace = true;
					}
				}
				if ( replace || insertText ) {
					surfaceModel.popStaging();
					if ( insertText ) {
						insertion = this.getInsertionData();
						if ( insertion.length ) {
							fragment.insertContent( insertion, true );
							if ( !isEditing ) {
								// Move cursor to the end of the inserted content, even if back button is used
								this.initialFragment = fragment.collapseToEnd();
							} else {
								this.initialFragment = fragment;
							}
						}
					}
					// If we are setting a new annotation, clear any annotations the inspector may have
					// applied up to this point. Otherwise keep them.
					if ( replace ) {
						clear();
						// Apply new annotation
						if ( fragment.getSelection().isCollapsed() ) {
							insertionAnnotation = true;
						} else {
							fragment.annotateContent( 'set', annotation );
						}
					}
				} else {
					surfaceModel.applyStaging();
				}
			}

			// HACK: ui.WindowAction unsets initialFragment in source mode,
			// so we can't rely on it existing.
			var selection;
			if ( this.initialFragment && ( !data.action || insertText ) ) {
				// Restore selection to what it was before we expanded it
				selection = this.initialFragment.getSelection();
			} else {
				selection = fragment.getSelection();
			}

			if ( data.action ) {
				// Place the selection after the inserted text. If the inserted content is actually an
				// element and not text, keep it selected, so that the context menu for it appears.
				if ( !( insertion && insertion.length && ve.dm.LinearData.static.isElementData( insertion[ 0 ] ) ) ) {
					surfaceModel.setSelection( selection );
				}
				// Update active annotations from model as the document may be deactivated
				surfaceView.updateActiveAnnotations( true );
				// Update previousActiveAnnotations so the annotation stays active
				// after re-activation
				surfaceView.previousActiveAnnotations = surfaceView.activeAnnotations;
				if ( OO.ui.isMobile() ) {
					// Restore context-only view on mobile
					surfaceView.deactivate( false, false, true );
				} else {
					// We can't rely on the selection being placed inside the annotation
					// so force it based on the model annotations. T265166
					surfaceView.selectAnnotation( function ( annView ) {
						return ve.isInstanceOfAny( annView.getModel(), inspector.constructor.static.modelClasses );
					} );
				}
			}

			if ( insertionAnnotation ) {
				surfaceModel.addInsertionAnnotations( annotation );
			}
		}, this )
		.next( function () {
			// Reset state
			this.initialSelection = null;
			this.initialAnnotation = null;
			this.initialAnnotationIsCovering = false;
			this.isNew = false;
		}, this );
};
