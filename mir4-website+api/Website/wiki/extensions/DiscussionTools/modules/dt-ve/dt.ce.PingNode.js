/**
 * PingNode view.
 *
 * @class
 * @extends ve.ce.LeafNode
 * @mixes ve.ce.FocusableNode
 *
 * @constructor
 * @param {DmMWPingNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
function CeMWPingNode() {
	// Parent constructor
	CeMWPingNode.super.apply( this, arguments );

	// Mixin constructor
	ve.ce.FocusableNode.call( this );
}

/* Inheritance */

OO.inheritClass( CeMWPingNode, ve.ce.LeafNode );

OO.mixinClass( CeMWPingNode, ve.ce.FocusableNode );

/* Static Properties */

CeMWPingNode.static.name = 'mwPing';

CeMWPingNode.static.tagName = 'span';

CeMWPingNode.static.deleteCommandName = 'insertAndOpenMWUsernameCompletions';

CeMWPingNode.static.getDescription = function ( model ) {
	return model.getAttribute( 'user' );
};

/* Methods */

/**
 * @inheritdoc
 */
CeMWPingNode.prototype.initialize = function () {
	var model = this.getModel(),
		prefix = mw.msg( 'discussiontools-replywidget-mention-prefix' ),
		suffix = mw.msg( 'discussiontools-replywidget-mention-suffix' ),
		user = model.getAttribute( 'user' ),
		title = mw.Title.makeTitle( mw.config.get( 'wgNamespaceIds' ).user, user );

	// Parent method
	CeMWPingNode.super.prototype.initialize.call( this );

	// DOM changes
	var $link = $( '<a>' )
		.addClass( 'ext-discussiontools-ce-mwPingNode' )
		.attr( {
			href: title.getUrl(),
			title: user
		} )
		.text( model.getAttribute( 'user' ) );

	ve.init.platform.linkCache.styleElement(
		title.getPrefixedText(),
		$link
	);

	this.$element.append(
		prefix,
		$link,
		suffix
	);
};

/* Registration */

ve.ce.nodeFactory.register( CeMWPingNode );
