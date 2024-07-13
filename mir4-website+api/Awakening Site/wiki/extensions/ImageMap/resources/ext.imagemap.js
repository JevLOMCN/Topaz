/*!
 * Add file description links to ImageMap images.
 */
( function () {
	mw.hook( 'wikipage.content' ).add( function ( $content ) {
		$content.find(
			'figure[class*="mw-ext-imagemap-desc-"] > :not(figcaption) .mw-file-element'
		).each( function () {
			var resource = this.getAttribute( 'resource' );
			if ( !resource ) {
				return;
			}
			var inner = this.parentNode;
			inner.classList.add( 'mw-ext-imagemap-inner' );
			var desc = this.ownerDocument.createElement( 'a' );
			desc.setAttribute( 'href', resource );
			desc.classList.add( 'mw-ext-imagemap-desc-link' );
			inner.appendChild( desc );
		} );
	} );
}() );
