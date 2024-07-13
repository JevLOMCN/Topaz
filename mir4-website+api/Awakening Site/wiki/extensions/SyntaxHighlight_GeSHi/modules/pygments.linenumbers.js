$( function () {

	/**
	 * Parse a line ID, e.g. "L-18"
	 *
	 * @param {string} id Line ID fragment
	 * @return {Object} Object with a string prefix and number
	 */
	function parseId( id ) {
		const matches = id.match( /(.*)-([0-9]+)/ );
		return {
			prefix: matches[ 1 ],
			number: +matches[ 2 ]
		};
	}

	/**
	 * Build a line ID from a parsed ID
	 *
	 * @param {Object} parsedId See #parseId
	 * @return {string} ID fragment
	 */
	function buildId( parsedId ) {
		return parsedId.prefix + '-' + parsedId.number;
	}

	/**
	 * Get a line element from an ID
	 *
	 * @param {string} id ID
	 * @return {HTMLElement|null} Line element, or null if not found (or the element is not a line)
	 */
	function getLineElement( id ) {
		const line = mw.util.getTargetFromFragment( id );
		// Support IE 11, Edge 14, Safari 7: Can't use unprefixed Element.matches('… *') yet.
		if ( !$( line ).closest( '.mw-highlight' ).length ) {
			// Element not in a highlight block
			return null;
		}
		return line;
	}

	let lastLines, lastAnchorLine;

	/**
	 * Handle hash change events
	 *
	 * @param {boolean} scrollIntoView Scroll the selected lines into view
	 */
	function onHashChange( scrollIntoView ) {
		const hash = location.hash.slice( 1 );

		const lines = [];
		let anchorLine, focusLine;
		const parts = hash.split( '--' );
		if ( parts.length === 2 ) {
			anchorLine = getLineElement( parts[ 0 ] );
			focusLine = getLineElement( parts[ 1 ] );
			if ( anchorLine && focusLine ) {
				const anchorId = parseId( parts[ 0 ] );
				const focusId = parseId( parts[ 1 ] );
				if ( anchorId.prefix === focusId.prefix ) {
					for ( let i = Math.min( anchorId.number, focusId.number ); i <= Math.max( anchorId.number, focusId.number ); i++ ) {
						lines.push( mw.util.getTargetFromFragment( buildId( { prefix: anchorId.prefix, number: i } ) ) );
					}
					if ( scrollIntoView ) {
						// A line range will not automatically scroll into view
						lines[ 0 ].scrollIntoView();
					}
				}
			}
		} else {
			anchorLine = getLineElement();
			if ( anchorLine ) {
				lines.push( anchorLine );
			}
		}

		lastAnchorLine = anchorLine;

		if ( lastLines ) {
			lastLines.forEach( ( line ) => line.classList.remove( 'hll' ) );
		}
		lines.forEach( ( line ) => line.classList.add( 'hll' ) );

		lastLines = lines;
	}

	window.addEventListener( 'hashchange', onHashChange );

	$( document.body ).on( 'click', '.mw-highlight .linenos', ( e ) => {
		e.preventDefault();

		const targetUrl = new URL( e.target.parentNode.href );

		if ( e.shiftKey && lastAnchorLine ) {
			const anchorId = parseId( lastAnchorLine.getAttribute( 'id' ) );
			const focusId = parseId( targetUrl.hash.slice( 1 ) );
			if ( anchorId.prefix === focusId.prefix ) {
				const hash = buildId( anchorId ) + '--' + buildId( focusId );
				history.replaceState( null, '', '#' + hash );
			} else {
				history.replaceState( null, '', targetUrl );
			}
		} else {
			history.replaceState( null, '', targetUrl );
		}

		onHashChange();
	} );

	// Check hash on load
	onHashChange( true );

}() );
