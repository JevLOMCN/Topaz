function LedeSectionDialog() {
	// Parent constructor
	LedeSectionDialog.super.apply( this, arguments );
}

/* Inheritance */
OO.inheritClass( LedeSectionDialog, OO.ui.ProcessDialog );

LedeSectionDialog.static.name = 'ledeSection';

LedeSectionDialog.static.size = 'larger';

LedeSectionDialog.static.title = OO.ui.deferMsg( 'discussiontools-ledesection-title' );

LedeSectionDialog.static.actions = [
	{
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-done' ),
		flags: [ 'safe', 'close' ]
	}
];

LedeSectionDialog.prototype.initialize = function () {
	// Parent method
	LedeSectionDialog.super.prototype.initialize.call( this );

	this.contentLayout = new OO.ui.PanelLayout( {
		padded: true,
		expanded: false,
		classes: [ 'ext-discussiontools-ui-ledeSectionDialog-content', 'mw-parser-output', 'content' ]
	} );

	this.$body.append( this.contentLayout.$element );
};

LedeSectionDialog.prototype.getSetupProcess = function ( data ) {
	return LedeSectionDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var dialog = this;
			this.contentLayout.$element.empty().append( data.$content );

			// Enable collapsible content (T323639), which is normally not handled on mobile (T111565).
			// It's safe to do this twice if that changes (makeCollapsible() checks if each element was
			// already handled). Using the same approach as in 'mediawiki.page.ready' in MediaWiki core.
			var $collapsible = this.contentLayout.$element.find( '.mw-collapsible' );
			if ( $collapsible.length ) {
				// This module is also preloaded in PageHooks to avoid visual jumps when things collapse.
				mw.loader.using( 'jquery.makeCollapsible' ).then( function () {
					$collapsible.makeCollapsible();
					$collapsible.on( 'afterExpand.mw-collapsible afterCollapse.mw-collapsible', function () {
						dialog.updateSize();
					} );
				} );
			}
		}, this );
};

module.exports = LedeSectionDialog;
