/*!
 * VisualEditor UserInterface MWWikitextLinkAnnotationInspector class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Inspector for applying and editing labeled MediaWiki internal and external links.
 *
 * @class
 * @extends ve.ui.MWLinkAnnotationInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWWikitextLinkAnnotationInspector = function VeUiMWWikitextLinkAnnotationInspector( config ) {
	// Parent constructor
	ve.ui.MWWikitextLinkAnnotationInspector.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWWikitextLinkAnnotationInspector, ve.ui.MWLinkAnnotationInspector );

/* Static properties */

ve.ui.MWWikitextLinkAnnotationInspector.static.name = 'wikitextLink';

ve.ui.MWWikitextLinkAnnotationInspector.static.modelClasses = [];

ve.ui.MWWikitextLinkAnnotationInspector.static.handlesSource = true;

// TODO: Support [[linktrail]]s & [[pipe trick|]]
ve.ui.MWWikitextLinkAnnotationInspector.static.internalLinkParser = ( function () {
	var openLink = '\\[\\[',
		closeLink = '\\]\\]',
		noCloseLink = '(?:(?!' + closeLink + ').)*',
		noCloseLinkOrPipe = '(?:(?!' + closeLink + ')[^|])*';

	return new RegExp(
		openLink +
			'(' + noCloseLinkOrPipe + ')' +
			'(?:\\|(' + noCloseLink + '))?' +
		closeLink,
		'g'
	);
}() );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWWikitextLinkAnnotationInspector.prototype.getSetupProcess = function ( data ) {
	// Annotation inspector stages the annotation, so call its parent
	// Call grand-parent
	return ve.ui.AnnotationInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var wgNamespaceIds = mw.config.get( 'wgNamespaceIds' ),
				internalLinkParser = this.constructor.static.internalLinkParser,
				fragment = this.getFragment();

			// Only supports linear selections
			if ( !( this.initialFragment && this.initialFragment.getSelection() instanceof ve.dm.LinearSelection ) ) {
				return ve.createDeferred().reject().promise();
			}

			var linkMatches;
			// Initialize range
			if ( !data.noExpand ) {
				if ( !fragment.getSelection().isCollapsed() ) {
					// Trim whitespace
					fragment = fragment.trimLinearSelection();
				}
				// Expand to existing link, if present
				// Find all links in the paragraph and see which one contains
				// the current selection.
				var contextFragment = fragment.expandLinearSelection( 'siblings' );
				var contextRange = contextFragment.getSelection().getCoveringRange();
				var range = fragment.getSelection().getCoveringRange();
				var text = contextFragment.getText();
				internalLinkParser.lastIndex = 0;
				var matches;
				while ( ( matches = internalLinkParser.exec( text ) ) !== null ) {
					var matchTitle = mw.Title.newFromText( matches[ 1 ] );
					if ( !matchTitle ) {
						continue;
					}
					var linkRange = new ve.Range(
						contextRange.start + matches.index,
						contextRange.start + matches.index + matches[ 0 ].length
					);
					var namespaceId = mw.Title.newFromText( matches[ 1 ] ).getNamespaceId();
					if (
						linkRange.containsRange( range ) && !(
							// Ignore File:/Category:, but not :File:/:Category:
							(
								namespaceId === wgNamespaceIds.file ||
								namespaceId === wgNamespaceIds.category
							) &&
							matches[ 1 ].indexOf( ':' ) !== 0
						)
					) {
						linkMatches = matches;
						fragment = fragment.getSurface().getLinearFragment( linkRange );
						break;
					}
				}
			}
			if ( !linkMatches ) {
				if ( !data.noExpand && fragment.getSelection().isCollapsed() ) {
					// expand to nearest word
					fragment = fragment.expandLinearSelection( 'word' );
				} else {
					// Trim whitespace
					fragment = fragment.trimLinearSelection();
				}
			}

			// Update selection
			fragment.select();

			this.initialSelection = fragment.getSelection();
			this.fragment = fragment;
			this.initialLabelText = this.fragment.getText();

			var title;
			if ( linkMatches ) {
				// Group 1 is the link target, group 2 is the label after | if present
				title = mw.Title.newFromText( linkMatches[ 1 ] );
				this.initialLabelText = linkMatches[ 2 ] || linkMatches[ 1 ];
				// HACK: Remove escaping probably added by this tool.
				// We should really do a full parse from wikitext to HTML if
				// we see any syntax
				this.initialLabelText = this.initialLabelText.replace( /<nowiki>(\]{2,})<\/nowiki>/g, '$1' );
			} else {
				title = mw.Title.newFromText( this.initialLabelText );
			}
			if ( title ) {
				this.initialAnnotation = this.newInternalLinkAnnotationFromTitle( title );
			}

			var inspectorTitle = ve.msg(
				this.isReadOnly() ?
					'visualeditor-linkinspector-title' : (
						!linkMatches ?
							'visualeditor-linkinspector-title-add' :
							'visualeditor-linkinspector-title-edit'
					)
			);

			this.title.setLabel( inspectorTitle ).setTitle( inspectorTitle );
			this.annotationInput.setReadOnly( this.isReadOnly() );

			this.actions.setMode( this.getMode() );
			this.linkTypeIndex.setTabPanel(
				this.initialAnnotation instanceof ve.dm.MWExternalLinkAnnotation ? 'external' : 'internal'
			);
			this.annotationInput.setAnnotation( this.initialAnnotation );

			this.updateActions();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWWikitextLinkAnnotationInspector.prototype.getTeardownProcess = function ( data ) {
	data = data || {};
	// Call grand-parent
	return ve.ui.FragmentInspector.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			var wgNamespaceIds = mw.config.get( 'wgNamespaceIds' ),
				annotation = this.getAnnotation(),
				fragment = this.getFragment(),
				insertion = this.getInsertionText();

			if ( data && data.action === 'done' && annotation ) {
				var insert = this.initialSelection.isCollapsed() && insertion.length;
				var labelText;
				if ( insert ) {
					fragment.insertContent( insertion );
					labelText = insertion;
				} else {
					labelText = this.initialLabelText;
				}

				// Build internal links locally
				if ( annotation instanceof ve.dm.MWInternalLinkAnnotation ) {
					if ( labelText.indexOf( ']]' ) !== -1 ) {
						labelText = labelText.replace( /(\]{2,})/g, '<nowiki>$1</nowiki>' );
					}
					var labelTitle = mw.Title.newFromText( labelText );
					var targetText;
					if ( !labelTitle || labelTitle.getPrefixedText() !== annotation.getAttribute( 'normalizedTitle' ) ) {
						targetText = annotation.getAttribute( 'normalizedTitle' ) + '|';
					} else {
						targetText = '';
					}
					var targetTitle = mw.Title.newFromText( annotation.getAttribute( 'normalizedTitle' ) );
					var namespaceId = targetTitle.getNamespaceId();
					var prefix;
					if (
						( targetText + labelText )[ 0 ] !== ':' && (
							namespaceId === wgNamespaceIds.file ||
							namespaceId === wgNamespaceIds.category
						)
					) {
						prefix = ':';
					} else {
						prefix = '';
					}

					fragment.insertContent( '[[' + prefix + targetText + labelText + ']]' );
				} else {
					// Annotating the surface will send the content to Parsoid before
					// it is inserted into the wikitext document. It is slower but it
					// will handle all cases.
					// Where possible we should generate the wikitext locally.
					fragment.annotateContent( 'set', annotation );
				}

				// Fix selection after annotating is complete
				fragment.getPending().then( function () {
					if ( insert ) {
						fragment.collapseToEnd().select();
					} else {
						fragment.select();
					}
				} );
			} else if ( !data.action ) {
				// Restore selection to what it was before we expanded it
				this.initialFragment.select();
			}
		}, this )
		.next( function () {
			// Reset state
			this.initialSelection = null;
			this.initialAnnotation = null;

			// Parent resets
			this.allowProtocolInInternal = false;
			this.internalAnnotationInput.setAnnotation( null );
			this.externalAnnotationInput.setAnnotation( null );
		}, this );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWWikitextLinkAnnotationInspector );

ve.ui.wikitextCommandRegistry.register(
	new ve.ui.Command(
		'link', 'window', 'open',
		{ args: [ 'wikitextLink' ], supportedSelections: [ 'linear' ] }
	)
);
