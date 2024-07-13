/*!
 * VisualEditor UserInterface MWLinkAction class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Link action.
 *
 * Opens either MWLinkAnnotationInspector or MWLinkNodeInspector depending on what is selected.
 *
 * @class
 * @extends ve.ui.LinkAction
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 */
ve.ui.MWLinkAction = function VeUiMWLinkAction( surface ) {
	// Parent constructor
	ve.ui.MWLinkAction.super.call( this, surface );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLinkAction, ve.ui.LinkAction );

/* Static Properties */

ve.ui.MWLinkAction.static.methods = ve.ui.MWLinkAction.super.static.methods.concat( [ 'open', 'autolinkMagicLink' ] );

/* Static methods */

/**
 * Get a link annotation from specified link text
 *
 * This is a static version of the method that can be used in the converter.
 *
 * @static
 * @param {string} linktext Link text
 * @param {HTMLDocument} doc Document
 * @return {ve.dm.MWExternalLinkAnnotation|ve.dm.MWInternalLinkAnnotation} The annotation to use
 */
ve.ui.MWLinkAction.static.getLinkAnnotation = function ( linktext, doc ) {
	var href = linktext;

	// Is this a "magic link"?
	if ( ve.dm.MWMagicLinkNode.static.validateContent( linktext ) ) {
		return ve.dm.MWMagicLinkNode.static.annotationFromContent( linktext );
	}
	// Is this an internal link?
	var targetData = mw.libs.ve.getTargetDataFromHref( href, doc );
	if ( targetData.isInternal ) {
		var title = mw.Title.newFromText( targetData.title );
		return ve.dm.MWInternalLinkAnnotation.static.newFromTitle( title );
	}
	// It's an external link.
	return new ve.dm.MWExternalLinkAnnotation( {
		type: 'link/mwExternal',
		attributes: { href: href }
	} );
};

/* Methods */

/**
 * Match the trailing punctuation set used for autolinks in wikitext.
 * Closing parens are only stripped if open parens are missing from the
 * candidate text, so that URLs with embedded matched parentheses (like
 * wiki articles with disambiguation text) autolink nicely.
 *
 * @inheritdoc
 */
ve.ui.MWLinkAction.prototype.getTrailingPunctuation = function ( candidate ) {
	// This is:
	// * the "trailing punctuation" character set from
	//   Parse.php::makeFreeExternalLink(): [,;.:!?] and sometimes [)]
	// * extended with characters banned by EXT_LINK_URL_CLASS: []<>"
	// * further extended with international close quotes: "'”’›»“‘‹«」』
	//   https://en.wikipedia.org/wiki/Quotation_mark

	// We could unescape '\[' but better to keep it balanced with '\]'
	/* eslint-disable no-useless-escape */
	return /\(/.test( candidate ) ?
		/[,;.:!?\[\]<>"'”’›»“‘‹«」』]+$/ :
		/[,;.:!?\[\]<>"'”’›»“‘‹«」』)]+$/;
	/* eslint-enable no-useless-escape */
};

/**
 * @inheritdoc
 * @return {ve.dm.MWExternalLinkAnnotation|ve.dm.MWInternalLinkAnnotation} The annotation to use
 */
ve.ui.MWLinkAction.prototype.getLinkAnnotation = function ( linktext ) {
	return this.constructor.static.getLinkAnnotation( linktext, this.surface.getModel().getDocument().getHtmlDocument() );
};

/**
 * Autolink the selected RFC/PMID/ISBN, which may have trailing punctuation
 * followed by whitespace.
 *
 * @see ve.ui.LinkAction#autolinkUrl
 * @return {boolean}
 *   True if the selection is a valid RFC/PMID/ISBN and the autolink action
 *   was executed; otherwise false.
 */
ve.ui.MWLinkAction.prototype.autolinkMagicLink = function () {
	return this.autolink( function ( linktext ) {
		return ve.dm.MWMagicLinkNode.static.validateContent( linktext );
	}, function ( doc, range, linktext ) {
		var annotations = doc.data.getAnnotationsFromRange( range ),
			data = new ve.dm.ElementLinearData( annotations.store, [
				{
					type: 'link/mwMagic',
					attributes: {
						content: linktext
					}
				},
				{
					type: '/link/mwMagic'
				}
			] );
		// Apply annotations which covered the range.
		// Before we get here #autolink has guaranteed that the annotations
		// do not contain any link annotations.
		data.setAnnotationsAtOffset( 0, annotations );
		return ve.dm.TransactionBuilder.static.newFromReplacement(
			doc, range, data.getData()
		);
	} );
};

/**
 * Open either the 'link' or 'linkNode' window, depending on what is selected.
 *
 * @return {boolean} Action was executed
 */
ve.ui.MWLinkAction.prototype.open = function () {
	var fragment = this.surface.getModel().getFragment(),
		selectedNode = fragment.getSelectedNode(),
		windowName = 'link';

	if ( selectedNode instanceof ve.dm.MWNumberedExternalLinkNode ) {
		windowName = 'linkNode';
	} else if ( selectedNode instanceof ve.dm.MWMagicLinkNode ) {
		windowName = 'linkMagicNode';
	}
	this.surface.executeWithSource( 'window', 'open', this.source, windowName );
	return true;
};

/* Registration */

ve.ui.actionFactory.register( ve.ui.MWLinkAction );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'autolinkMagicLink', ve.ui.MWLinkAction.static.name, 'autolinkMagicLink',
		{ supportedSelections: [ 'linear' ] }
	)
);

// The regexps don't have to be precise; we'll validate the magic
// link in #autolinkMagicLink above.
ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence(
		'autolinkMagicLinkIsbn10', 'autolinkMagicLink', /\bISBN\s+(?!97[89])([0-9][ -]?){9}[0-9Xx]$/, 0,
		{
			setSelection: true,
			delayed: false,
			checkOnPaste: true
		}
	)
);
ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence(
		'autolinkMagicLinkIsbn13', 'autolinkMagicLink', /\bISBN\s+(97[89])[ -]?([0-9][ -]?){9}[0-9Xx]$/, 0,
		{
			setSelection: true,
			delayed: false,
			checkOnPaste: true
		}
	)
);
ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence(
		'autolinkMagicLinkIsbn', 'autolinkMagicLink', /\bISBN\s+(97[89][ -]?)?([0-9][ -]?){9}[0-9Xx]$/, 0,
		{
			setSelection: true,
			delayed: true,
			checkOnPaste: true
		}
	)
);
ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence(
		'autolinkMagicLinkRfcPmid', 'autolinkMagicLink', /\b(RFC|PMID)\s+[0-9]+$/, 0,
		{
			setSelection: true,
			delayed: true,
			checkOnPaste: true
		}
	)
);
