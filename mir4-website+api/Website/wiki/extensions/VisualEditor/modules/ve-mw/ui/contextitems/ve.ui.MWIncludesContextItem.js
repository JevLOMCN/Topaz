/*!
 * VisualEditor MWIncludesContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a MWIncludesContextItem.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @constructor
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWIncludesContextItem = function VeUiMWIncludesContextItem() {
	// Parent constructor
	ve.ui.MWIncludesContextItem.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwIncludesContextItem' );

	this.setLabel( this.getLabelMessage() );

	this.$actions.remove();
};

/* Inheritance */

OO.inheritClass( ve.ui.MWIncludesContextItem, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.MWIncludesContextItem.static.editable = false;

ve.ui.MWIncludesContextItem.static.name = 'mwIncludes';

ve.ui.MWIncludesContextItem.static.icon = 'markup';

ve.ui.MWIncludesContextItem.static.modelClasses = [
	ve.dm.MWIncludesNode
];

/* Methods */

/**
 * @return {string}
 */
ve.ui.MWIncludesContextItem.prototype.getLabelMessage = function () {
	var key = {
		'mw:Includes/NoInclude': 'visualeditor-includes-noinclude-start',
		'mw:Includes/NoInclude/End': 'visualeditor-includes-noinclude-end',
		'mw:Includes/OnlyInclude': 'visualeditor-includes-onlyinclude-start',
		'mw:Includes/OnlyInclude/End': 'visualeditor-includes-onlyinclude-end',
		'mw:Includes/IncludeOnly': 'visualeditor-includes-includeonly'
	}[ this.model.getAttribute( 'type' ) ];
	// eslint-disable-next-line mediawiki/msg-doc
	return key ? mw.message( key ).text() : '';
};

/**
 * @return {jQuery}
 */
ve.ui.MWIncludesContextItem.prototype.getDescriptionMessage = function () {
	var key = {
		'mw:Includes/NoInclude': 'visualeditor-includes-noinclude-description',
		'mw:Includes/OnlyInclude': 'visualeditor-includes-onlyinclude-description',
		'mw:Includes/IncludeOnly': 'visualeditor-includes-includeonly-description'
	}[ this.model.getAttribute( 'type' ) ];
	// eslint-disable-next-line mediawiki/msg-doc
	return key ? mw.message( key ).parseDom() : $( [] );
};

/**
 * @inheritdoc
 */
ve.ui.MWIncludesContextItem.prototype.renderBody = function () {
	this.$body.empty();

	var $desc = this.getDescriptionMessage();
	this.$body.append( $desc, $( document.createTextNode( mw.msg( 'word-separator' ) ) ) );

	if ( this.model.getAttribute( 'mw' ) ) {
		var wikitext = this.model.getAttribute( 'mw' ).src;
		// The opening and closing tags are included, eww
		wikitext = wikitext.replace( /^<includeonly>\s*([\s\S]*)\s*<\/includeonly>$/, '$1' );
		this.$body.append( $( '<pre>' )
			// The following classes are used here:
			// * mw-editfont-monospace
			// * mw-editfont-sans-serif
			// * mw-editfont-serif
			.addClass( 'mw-editfont-' + mw.user.options.get( 'editfont' ) )
			.text( wikitext )
		);
	}

	var $docMsg = mw.message( 'visualeditor-includes-documentation' ).parseDom();
	this.$body.append( $docMsg );
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWIncludesContextItem );
