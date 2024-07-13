( function () {
	const
		mobile = require( 'mobile.startup' ),
		pageIssues = require( '../../../../resources/skins.minerva.scripts/page-issues/index.js' ),
		insertBannersOrNotice = pageIssues.test.insertBannersOrNotice,
		PageHTMLParser = mobile.PageHTMLParser,
		overlayManager = mobile.getOverlayManager(),
		$mockContainer = $(
			'<div id=\'bodyContent\'>' +
				'<table class=\'ambox ambox-content\'>' +
					'<tbody class=\'mbox-text\'>' +
						'<tr><td><span class=\'mbox-text-span\'> ambox text span </span></td></tr>' +
					'</tbody>' +
				'</table>' +
			'</div>'
		),
		labelText = 'label text',
		inline = true,
		SECTION = '0',
		processedAmbox = insertBannersOrNotice(
			new PageHTMLParser( $mockContainer ),
			labelText, SECTION, inline, overlayManager
		).ambox;

	QUnit.module( 'Minerva pageIssues' );

	QUnit.test( 'insertBannersOrNotice() should add a "learn more" message', function ( assert ) {
		assert.true( /⧼skin-minerva-issue-learn-more⧽/.test( processedAmbox.html() ) );
	} );

	QUnit.test( 'insertBannersOrNotice() should add an icon', function ( assert ) {
		assert.true( /(minerva-icon)/.test( processedAmbox.html() ) );
	} );
	QUnit.test( 'clicking on the product of insertBannersOrNotice() should trigger a URL change', function ( assert ) {
		processedAmbox.click();
		assert.strictEqual( window.location.hash, '#/issues/' + SECTION );
	} );
}() );
