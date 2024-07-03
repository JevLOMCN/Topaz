( function () {
	/**
	 * Create a link element that opens the issues overlay.
	 *
	 * @param {string} labelText The text value of the element
	 * @return {jQuery}
	 */
	function newPageIssueLink( labelText ) {
		return $( '<a>' ).addClass( 'cleanup mw-mf-cleanup' ).text( labelText );
	}

	module.exports = newPageIssueLink;
}() );
