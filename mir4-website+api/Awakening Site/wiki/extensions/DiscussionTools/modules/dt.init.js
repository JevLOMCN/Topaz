var controller = require( './controller.js' ),
	url = new URL( location.href );

/**
 * @class mw.dt
 * @singleton
 */
mw.dt = {};

mw.dt.initState = {
	firstLoad: true
};

// A/B test for logged out users:
if ( mw.user.isAnon() && mw.config.get( 'wgDiscussionToolsABTest' ) && mw.config.get( 'wgDiscussionToolsABTestBucket' ) ) {
	var token = mw.cookie.get( 'DTABid', '', mw.user.generateRandomSessionId() );
	mw.cookie.set( 'DTAB', mw.config.get( 'wgDiscussionToolsABTestBucket' ), { path: '/', expires: 90 * 86400, prefix: '' } );
	mw.cookie.set( 'DTABid', token, { path: '/', expires: 90 * 86400, prefix: '' } );
}

if ( url.searchParams.get( 'dtrepliedto' ) ) {
	// If we had to reload the page to highlight the new comment, extract that data from the URL and
	// clean it up.
	mw.dt.initState.repliedTo = url.searchParams.get( 'dtrepliedto' );
	mw.dt.initState.tempUserCreated = url.searchParams.has( 'dttempusercreated' );
	url.searchParams.delete( 'dtrepliedto' );
	url.searchParams.delete( 'dttempusercreated' );
	window.history.replaceState( {}, '', url );
}

/**
 * Hook handler for `mw.hook( 'wikipage.content' )`.
 *
 * @param {jQuery} $container
 */
mw.dt.init = function ( $container ) {
	function reallyInit( $node ) {
		controller.init( $node, mw.dt.initState );
		mw.dt.initState = {};

		// TODO: This functionality could be provided by MediaWiki (T183720).
		$( document.documentElement ).addClass( 'ext-discussiontools-init-ready' );
	}

	// Only (re)initialize if the hook is being fired on the page content – not on e.g. a single image
	// in a gallery slideshow, or a preview in our own reply tool
	if ( $container.is( '#mw-content-text' ) || $container.find( '#mw-content-text' ).length ) {
		// eslint-disable-next-line no-jquery/no-global-selector
		reallyInit( $( '#mw-content-text' ) );
		return;
	}

	// Otherwise, if node is detached, wait to see what it actually is
	if ( !$container.closest( 'html' ).length ) {
		setTimeout( function () {
			if ( $container.closest( 'html' ).length ) {
				mw.dt.init( $container );
			}
		} );
		return;
	}

	// If it's a full page live preview, (re)initialize to support highlighting comments (T309423)
	// FIXME This really should not depend on implementation details of 2 different live previews
	// FIXME VisualEditor (2017WTE) preview can't be supported, because it messes with `id` attributes
	var livePreviewSelectors = '#wikiPreview, .ext-WikiEditor-realtimepreview-preview';
	if ( $container.parent().is( livePreviewSelectors ) ) {
		reallyInit( $container );
		return;
	}
};

if ( url.searchParams.get( 'dtdebug' ) ) {
	mw.loader.load( 'ext.discussionTools.debug' );
} else {
	// Don't use an anonymous function, because ReplyWidget needs to be able to remove this handler
	mw.hook( 'wikipage.content' ).add( mw.dt.init );
}

var topicSubscriptions;

if ( mw.config.get( 'wgCanonicalSpecialPageName' ) === 'TopicSubscriptions' ) {
	topicSubscriptions = require( './topicsubscriptions.js' );
	topicSubscriptions.initSpecialTopicSubscriptions();
}

if ( mw.config.get( 'wgAction' ) === 'history' ) {
	topicSubscriptions = require( './topicsubscriptions.js' );
	topicSubscriptions.initNewTopicsSubscription();
}

// Clean up old localStorage entries that were erroneously set with no expiration (T339042).
// We are no longer using these keys since T329299.
// TODO: Remove this code after a few weeks.
mw.requestIdleCallback( function () {
	try {
		for ( var key in localStorage ) {
			if ( key.startsWith( 'reply/' ) ) {
				localStorage.removeItem( key );
				localStorage.removeItem( '_EXPIRY_' + key );
			}
		}
	} catch ( err ) {}
} );

module.exports = {
	controller: controller,
	Parser: require( './Parser.js' ),
	parserData: require( './parser/data.json' ),
	modifier: require( './modifier.js' ),
	ThreadItem: require( './ThreadItem.js' ),
	HeadingItem: require( './HeadingItem.js' ),
	CommentItem: require( './CommentItem.js' ),
	utils: require( './utils.js' ),
	config: require( './config.json' )
};
