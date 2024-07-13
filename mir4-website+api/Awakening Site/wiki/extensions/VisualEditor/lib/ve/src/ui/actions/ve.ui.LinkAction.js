/*!
 * VisualEditor UserInterface LinkAction class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Link action.
 * This action transforms or inspects links (or potential links).
 *
 * @class
 * @extends ve.ui.Action
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 * @param {string} [source]
 */
ve.ui.LinkAction = function VeUiLinkAction() {
	// Parent constructor
	ve.ui.LinkAction.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.LinkAction, ve.ui.Action );

/* Static Properties */

ve.ui.LinkAction.static.name = 'link';

/**
 * RegExp matching an autolink + trailing space.
 *
 * @property {RegExp}
 * @private
 */
ve.ui.LinkAction.static.autolinkRegExp = null; // Initialized below.

ve.ui.LinkAction.static.methods = [ 'autolinkUrl' ];

/* Methods */

/**
 * Autolink the selected URL (which may have trailing whitespace).
 *
 * @return {boolean}
 *   True if the selection is a valid URL and the autolink action was
 *   executed; otherwise false.
 */
ve.ui.LinkAction.prototype.autolinkUrl = function () {
	return this.autolink( function ( linktext ) {
		// Make sure we still have a real URL after trail removal, and not
		// a bare protocol (or no protocol at all, if we stripped the last
		// colon from the protocol)
		return ve.ui.LinkAction.static.autolinkRegExp.test( linktext );
	} );
};

/**
 * Autolink the selection, which may have trailing whitespace.
 *
 * @private
 * @param {Function} validateFunc
 *   A function used to validate the given linktext.
 * @param {string} validateFunc.linktext
 *   Linktext with trailing whitespace and punctuation stripped.
 * @param {boolean} validateFunc.return
 *   True iff the given linktext is valid.  If false, no linking will be done.
 * @param {Function} [txFunc]
 *   An optional function to create a transaction to perform the autolink.
 *   If not provided, a transaction will be created which applies the
 *   annotations returned by {@link ve.ui.LinkAction#getLinkAnnotation}.
 * @param {ve.dm.Document} txFunc.documentModel
 *   The document model to modify.
 * @param {ve.Range} txFunc.range
 *   The range to autolink.
 * @param {string} txFunc.linktext
 *   The text string to autolink.
 * @param {ve.dm.Transaction} txFunc.return
 *   The transaction to perform the autolink operation.
 * @return {boolean} Selection was valid and link action was executed.
 */
ve.ui.LinkAction.prototype.autolink = function ( validateFunc, txFunc ) {
	var surfaceModel = this.surface.getModel(),
		selection = surfaceModel.getSelection();

	if ( !( selection instanceof ve.dm.LinearSelection ) ) {
		return false;
	}

	function isLinkAnnotation( annotation ) {
		return /^link/.test( annotation.name );
	}

	var range = selection.getRange();
	var rangeEnd = range.end;

	var documentModel = surfaceModel.getDocument();
	var linktext = documentModel.data.getText( true, range );

	// Eliminate trailing whitespace.
	linktext = linktext.replace( /\s+$/, '' );

	// Eliminate trailing punctuation.
	linktext = linktext.replace( this.getTrailingPunctuation( linktext ), '' );

	// Validate the stripped text.
	if ( !validateFunc( linktext ) ) {
		// Don't autolink this.
		return false;
	}

	// Shrink range to match new linktext.
	range = range.truncate( linktext.length );

	// If there are word characters (but not punctuation) immediately past the range, don't autolink.
	// The user did something silly like type a link in the middle of a word.
	if (
		range.end + 1 < documentModel.data.getLength() &&
		/\w/.test( documentModel.data.getText( true, new ve.Range( range.end, range.end + 1 ) ) )
	) {
		return false;
	}

	// Check that none of the range has an existing link annotation.
	// Otherwise we could autolink an internal link, which would be ungood.
	for ( var i = range.start; i < range.end; i++ ) {
		if ( documentModel.data.getAnnotationsFromOffset( i ).containsMatching( isLinkAnnotation ) ) {
			// Don't autolink this.
			return false;
		}
	}

	// Make sure `undo` doesn't expose the selected linktext.
	surfaceModel.setLinearSelection( new ve.Range( rangeEnd ) );

	// Annotate the (previous) range.
	if ( txFunc ) {
		// TODO: Change this API so that 'txFunc' is given a surface fragment
		// as an argument, and uses the fragment to directly edit the document.
		surfaceModel.change( txFunc( documentModel, range, linktext ) );
	} else {
		surfaceModel.getLinearFragment( range, true ).annotateContent( 'set', this.getLinkAnnotation( linktext ) );
	}

	return true;
};

/**
 * Return an appropriate "trailing punctuation" set, which will
 * get stripped from possible autolinks.
 *
 * @param {string} candidate
 *   The candidate text.  Some users may not wish to include closing
 *   brackets/braces/parentheses in the stripped character class if an
 *   opening bracket/brace/parenthesis in present in the candidate link
 *   text.
 * @return {RegExp}
 *   A regular expression matching trailing punctuation which will be
 *   stripped from an autolink.
 */
ve.ui.LinkAction.prototype.getTrailingPunctuation = function () {
	return /[,;.:!?)\]}"'”’»]+$/;
};

/**
 * Return an appropriate annotation for the given link text.
 *
 * @param {string} linktext The link text to annotate.
 * @return {ve.dm.LinkAnnotation} The annotation to use.
 */
ve.ui.LinkAction.prototype.getLinkAnnotation = function ( linktext ) {
	return new ve.dm.LinkAnnotation( {
		type: 'link',
		attributes: {
			href: linktext
		}
	} );
};

/* Registration */

ve.ui.actionFactory.register( ve.ui.LinkAction );

// Delayed initialization (wait until ve.init.platform exists)
ve.init.Platform.static.initializedPromise.then( function () {
	ve.ui.LinkAction.static.autolinkRegExp =
		// eslint-disable-next-line security/detect-non-literal-regexp
		new RegExp(
			'\\b' + ve.init.platform.getUnanchoredExternalLinkUrlProtocolsRegExp().source + '\\S+$',
			'i'
		);

	ve.ui.sequenceRegistry.register(
		new ve.ui.Sequence(
			'autolinkUrl', 'autolinkUrl', ve.ui.LinkAction.static.autolinkRegExp, 0,
			{
				setSelection: true,
				delayed: true
			}
		)
	);
} );
