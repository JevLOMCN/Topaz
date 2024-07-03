require( './CommentTarget.js' );

/**
 * DiscussionTools TargetWidget class
 *
 * @class
 * @extends ve.ui.MWTargetWidget
 *
 * @constructor
 * @param {mw.dt.ReplyWidgetVisual} replyWidget
 * @param {Object} [config] Configuration options
 */
function CommentTargetWidget( replyWidget, config ) {
	var excludeCommands = [
		'blockquoteWrap', // T258194
		// Disable to allow Tab/Shift+Tab to move focus out of the widget (T172694)
		'indent',
		'outdent',
		// Save commands get loaded from articletarget module, which we load
		// to get the edit switching tool for mobile
		'showSave',
		'showChanges',
		'showPreview',
		'saveMinoredit',
		'saveWatchthis'
	];

	if ( !replyWidget.isNewTopic ) {
		excludeCommands = excludeCommands.concat( [
			// Disable commands for things whose wikitext markup doesn't work when indented
			'heading1',
			'heading2',
			'heading3',
			'heading4',
			'heading5',
			'heading6',
			'insertTable',
			'transclusionFromSequence', // T253667
			'preformatted'
		] );
	}

	config = $.extend( {}, {
		excludeCommands: excludeCommands
	}, config );

	this.replyWidget = replyWidget;
	this.authors = config.authors;

	// Parent constructor
	CommentTargetWidget.super.call( this, config );

	// Initialization
	this.$element.addClass( 'ext-discussiontools-ui-targetWidget' );
}

/* Inheritance */

OO.inheritClass( CommentTargetWidget, ve.ui.MWTargetWidget );

/**
 * @inheritdoc
 */
CommentTargetWidget.prototype.createTarget = function () {
	return ve.init.mw.targetFactory.create( 'discussionTools', this.replyWidget, {
		// A lot of places expect ve.init.target to exist...
		register: true,
		toolbarGroups: this.toolbarGroups,
		modes: this.modes,
		defaultMode: this.defaultMode
	} );
};

/**
 * @inheritdoc
 */
CommentTargetWidget.prototype.setDocument = function ( docOrHtml ) {
	var mode = this.target.getDefaultMode(),
		doc = ( mode === 'visual' && typeof docOrHtml === 'string' ) ?
			this.target.parseDocument( docOrHtml ) :
			docOrHtml,
		// TODO: This could be upstreamed:
		dmDoc = this.target.constructor.static.createModelFromDom( doc, mode );

	// Parent method
	CommentTargetWidget.super.prototype.setDocument.call( this, dmDoc );

	// Remove MW specific classes as the widget is already inside the content area
	this.getSurface().getView().$element.removeClass( 'mw-body-content' );
	this.getSurface().$placeholder.removeClass( 'mw-body-content' );

	// Fix jquery.ime position (T255191)
	this.getSurface().getView().getDocument().getDocumentNode().$element.addClass( 'ime-position-inside' );

	// HACK
	this.getSurface().authors = this.authors;
};

module.exports = CommentTargetWidget;
