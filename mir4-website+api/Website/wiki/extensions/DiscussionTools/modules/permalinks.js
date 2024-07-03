function init( $pageContainer ) {
	$pageContainer.find( '.ext-discussiontools-init-timestamplink' ).on( 'click', function () {
		copyLink( this.href );
	} ).attr( 'data-event-name', 'discussiontools.permalink-copied' );
}

function copyLink( link ) {
	var $win = $( window );
	var scrollTop = $win.scrollTop();

	var $tmpInput = $( '<input>' )
		.val( link )
		.addClass( 'noime' )
		.css( {
			position: 'fixed',
			top: 0
		} )
		.appendTo( 'body' )
		.trigger( 'focus' );
	$tmpInput[ 0 ].setSelectionRange( 0, link.length );
	var copied;
	try {
		copied = document.execCommand( 'copy' );
	} catch ( err ) {
		copied = false;
	}
	if ( copied ) {
		mw.notify( mw.msg( 'discussiontools-permalink-comment-copied' ) );
	}
	$tmpInput.remove();

	// Restore scroll position, can be changed by setSelectionRange, or hash navigation
	function afterNextScroll() {
		// On desktop we can restore scroll immediately after the scroll
		// event, preventing a scroll flicker.
		$win.scrollTop( scrollTop );
		// On mobile, we need to wait another execution cycle (setTimeout)
		// before the scroll is rendered (and not requestAnimationFrame).
		setTimeout( function () {
			$win.scrollTop( scrollTop );
		} );
	}
	// Restore scroll position when the scroll event fires.
	// setTimeout does't reliably wait long enough for the native
	// scroll to happen.
	$win.one( 'scroll', afterNextScroll );
	// If we happened to be in the exact correct position, 'scroll' won't fire,
	// so clear the listener after a short delay
	setTimeout( function () {
		$win.off( 'scroll', afterNextScroll );
	}, 1000 );
}

module.exports = {
	init: init
};
