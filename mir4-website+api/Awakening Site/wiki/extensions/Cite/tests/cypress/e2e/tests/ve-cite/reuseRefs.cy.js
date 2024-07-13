import * as helpers from './../../utils/functions.helper.js';

const refText1 = 'This is citation #1 for reference #1 and #2';
const refText2 = 'This is citation #2 for reference #3';

const wikiText = `This is reference #1: <ref name="a">${ refText1 }</ref><br> ` +
'This is reference #2 <ref name="a" /><br>' +
`This is reference #3 <ref>${ refText2 }</ref><br>` +
'<references />';

describe( 'Re-using refs in Visual Editor', () => {
	beforeEach( () => {
		const title = helpers.getTestString( 'CiteTest-reuseRefs' );

		cy.clearCookies();
		cy.visit( '/index.php' );

		helpers.editPage( title, wikiText );
		cy.window().then( async ( win ) => {
			win.localStorage.setItem( 've-beta-welcome-dialog', 1 );
		} );

		helpers.visitTitle( title );

		helpers.waitForMWLoader();
		cy.window().then( async ( win ) => {
			await win.mw.loader.using( 'mediawiki.base' ).then( async function () {
				await win.mw.hook( 'wikipage.content' ).add( function () {} );
			} );
		} );

		helpers.visitTitle( title, { veaction: 'edit' } );

		helpers.waitForVEToLoad();
	} );

	it( 'should display re-used reference in article with correct footnote number and notification in context dialog', () => {
		// Currently there are 3 refs in the article
		helpers.getRefsFromArticleSection().should( 'have.length', 3 );

		// Place cursor next to ref #2 in order to add re-use ref next to it
		cy.contains( '.mw-reflink-text', '[2]' ).type( '{rightarrow}' );

		helpers.openVECiteReuseDialog();

		// Re-use second ref
		helpers.getCiteReuseDialogRefResult( 2 ).click();
		// The context dialog on one of the references shows it's being used twice
		cy.get( '.mw-reflink-text' ).contains( '[2]' ).click();
		cy.get( '.oo-ui-popupWidget-popup .ve-ui-mwReferenceContextItem-muted' ).should( 'have.text', 'This reference is used twice on this page.' );

		helpers.getVEUIToolbarSaveButton().click();
		helpers.getSaveChangesDialogConfirmButton().click();

		helpers.getMWSuccessNotification().should( 'be.visible' );

		// ARTICLE SECTION
		// Ref has been added to article, there are now 4 refs in the article
		helpers.getRefsFromArticleSection().should( 'have.length', 4 );
		// Ref #2 now appears twice in the article with corresponding IDs matching the backlinks in
		// the references section
		helpers.backlinksIdShouldMatchFootnoteId( 2, 0, 2 );
		helpers.backlinksIdShouldMatchFootnoteId( 3, 1, 2 );

		// Both references have the same footnote number
		cy.get( '#mw-content-text p sup a' ).eq( 2 ).should( 'have.text', '[2]' );
		cy.get( '#mw-content-text p sup a' ).eq( 3 ).should( 'have.text', '[2]' );

		// REFERENCES SECTION
		// References section contains a list item for each reference
		helpers.getRefsFromReferencesSection().should( 'have.length', 2 );

		// Ref content should match
		helpers.getRefFromReferencesSection( 2 ).find( '.reference-text' ).should( 'have.text', refText2 );
	} );

	it( 'should display correct ref content and name attribute for re-used ref with existing name attribute', () => {
		// Place cursor next to ref #1 in order to add re-used ref next to it
		cy.contains( '.mw-reflink-text', '[1]' ).first().type( '{rightarrow}' );

		helpers.openVECiteReuseDialog();
		// reuse first ref which has the name 'a'
		helpers.getCiteReuseDialogRefText( 1 ).should( 'have.text', refText1 );
		helpers.getCiteReuseDialogRefResultName( 1 ).should( 'have.text', 'a' );
		helpers.getCiteReuseDialogRefResult( 1 ).click();

		helpers.getVEUIToolbarSaveButton().click();
		helpers.getSaveChangesDialogConfirmButton().click();

		helpers.getMWSuccessNotification().should( 'be.visible' );

		// ARTICLE SECTION
		// Ref name 'a' has been added correctly
		helpers.articleSectionRefMarkersContainCorrectRefName( '1' );

		// REFERENCES SECTION
		// Ref content from re-used ref is displayed correctly in backlink reference
		helpers.getRefFromReferencesSection( 1 ).should( 'contain', refText1 );
		// Ref name a has been added to backlink
		helpers.verifyBacklinkHrefContent( 'a', 1, 1 );

		// ref #1 has reference name a assigned to its id
		helpers.referenceSectionRefIdContainsRefName( 1, 'a' );
		// ref #2 has no name, if there is no ref name its skipped
		helpers.referenceSectionRefIdContainsRefName( 2, null );
	} );
} );
