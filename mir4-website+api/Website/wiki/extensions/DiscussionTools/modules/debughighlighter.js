'use strict';

var initialOffset, indentWidth, firstMarker;
var updaters = [];
// eslint-disable-next-line no-jquery/no-global-selector
var isRtl = $( 'html' ).attr( 'dir' ) === 'rtl';

function markTimestamp( parser, node, match ) {
	var dfParsers = parser.getLocalTimestampParsers();

	var newNode = node.splitText( match.matchData.index );
	newNode.splitText( match.matchData[ 0 ].length );

	var wrapper = document.createElement( 'span' );
	wrapper.className = 'ext-discussiontools-debughighlighter-timestamp';
	// We might need to actually port all the date formatting code from MediaWiki's PHP code
	// if we want to support displaying dates in all the formats available in user preferences
	// (which include formats in several non-Gregorian calendars).
	var date = dfParsers[ match.parserIndex ]( match.matchData ).date;
	wrapper.title = date.format() + ' / ' + date.fromNow();
	wrapper.appendChild( newNode );
	node.parentNode.insertBefore( wrapper, node.nextSibling );
}

function markSignature( sigNodes ) {
	var
		where = sigNodes[ 0 ],
		wrapper = document.createElement( 'span' );
	wrapper.className = 'ext-discussiontools-debughighlighter-signature';
	where.parentNode.insertBefore( wrapper, where );
	while ( sigNodes.length ) {
		wrapper.appendChild( sigNodes.pop() );
	}
}

function fixFakeFirstHeadingRect( rect, comment ) {
	// If the page has comments before the first section heading, they are connected to a "fake"
	// heading with an empty range. Visualize the page title as the heading for that section.
	if ( rect.x === 0 && rect.y === 0 && comment.type === 'heading' ) {
		var node = document.getElementsByClassName( 'firstHeading' )[ 0 ];
		return node.getBoundingClientRect();
	}
	return rect;
}

function calculateSizes() {
	// eslint-disable-next-line no-jquery/no-global-selector
	var $content = $( '#mw-content-text' );
	var $test = $( '<dd>' ).appendTo( $( '<dl>' ).appendTo( $content ) );
	var rect = $content[ 0 ].getBoundingClientRect();

	initialOffset = isRtl ? document.body.scrollWidth - rect.left - rect.width : rect.left;
	indentWidth = parseFloat( $test.css( isRtl ? 'margin-right' : 'margin-left' ) ) +
		parseFloat( $test.parent().css( isRtl ? 'margin-right' : 'margin-left' ) );

	$test.parent().remove();
}

function markComment( comment ) {
	var marker = document.createElement( 'div' );
	marker.className = 'ext-discussiontools-debughighlighter-comment';

	if ( !firstMarker ) {
		firstMarker = marker;
	}

	var marker2 = null;
	if ( comment.parent ) {
		marker2 = document.createElement( 'div' );
		marker2.className = 'ext-discussiontools-debughighlighter-comment-ruler';
	}

	var markerWarnings = null;
	if ( comment.warnings && comment.warnings.length ) {
		markerWarnings = document.createElement( 'div' );
		markerWarnings.className = 'ext-discussiontools-debughighlighter-comment-warnings';
		markerWarnings.innerText = comment.warnings.join( '\n' );
	}

	var update = function () {
		var rect = fixFakeFirstHeadingRect(
			comment.getRange().getBoundingClientRect(),
			comment
		);
		var scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
		var scrollLeft = document.documentElement.scrollLeft || document.body.scrollLeft;

		marker.style.top = ( rect.top + scrollTop ) + 'px';
		marker.style.height = ( rect.height ) + 'px';
		marker.style.left = ( rect.left + scrollLeft ) + 'px';
		marker.style.width = ( rect.width ) + 'px';

		if ( marker2 ) {
			var parentRect = comment.parent.getRange().getBoundingClientRect();
			parentRect = fixFakeFirstHeadingRect( parentRect, comment.parent );
			if ( comment.parent.level === 0 ) {
				// Twiddle so that it looks nice
				parentRect = $.extend( {}, parentRect );
				parentRect.height -= 10;
			}

			marker2.style.top = ( parentRect.top + parentRect.height + scrollTop ) + 'px';
			marker2.style.height = ( rect.top - ( parentRect.top + parentRect.height ) + 10 ) + 'px';
			if ( isRtl ) {
				marker2.style.right = ( initialOffset - indentWidth / 2 + comment.parent.level * indentWidth ) + 'px';
				marker2.style.width = ( ( comment.level - comment.parent.level ) * indentWidth - indentWidth / 2 ) - 2 + 'px';
			} else {
				marker2.style.left = ( initialOffset - indentWidth / 2 + comment.parent.level * indentWidth ) + 'px';
				marker2.style.width = ( ( comment.level - comment.parent.level ) * indentWidth - indentWidth / 2 ) - 2 + 'px';
			}
		}

		if ( markerWarnings ) {
			markerWarnings.style.cssText = marker.style.cssText;
		}
	};

	updaters.push( update );
	update();

	document.body.appendChild( marker );
	if ( marker2 ) {
		document.body.appendChild( marker2 );
	}
	if ( markerWarnings ) {
		// Group warnings at the top as we use nth-child selectors
		// to alternate color of markers.
		document.body.insertBefore( markerWarnings, firstMarker );
	}

	comment.replies.forEach( markComment );
}

function markThreads( threads ) {
	calculateSizes();
	threads.forEach( markComment );
	// Reverse order so that box-shadows look right
	// eslint-disable-next-line no-jquery/no-global-selector
	$( 'body' ).append( $( '.ext-discussiontools-debughighlighter-comment-ruler' ).get().reverse() );
}

function updateAll() {
	updaters.forEach( function ( update ) {
		calculateSizes();
		update();
	} );
}

window.addEventListener( 'resize', OO.ui.debounce( updateAll, 500 ) );

module.exports = {
	markThreads: markThreads,
	markTimestamp: markTimestamp,
	markSignature: markSignature
};
