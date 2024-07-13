import * as helpers from './../../utils/functions.helper.js';
const title = helpers.getTestString( 'CiteTest-title' );

const refText1 = 'This is citation #1 for reference #1 and #2';
const refText2 = 'This is citation #2 for reference #3';

const wikiText = `This is reference #1: <ref name="a">${ refText1 }</ref><br> ` +
	'This is reference #2 <ref name="a" /><br>' +
	`This is reference #3 <ref>${ refText2 }</ref><br>` +
	'<references />';

describe( 'Visual Editor Cite Integration', () => {
	before( () => {
		cy.visit( '/index.php' );
		helpers.editPage( title, wikiText );
	} );

	beforeEach( () => {
		helpers.visitTitle( title );

		cy.window().then( async ( win ) => {
			win.localStorage.setItem( 've-beta-welcome-dialog', 1 );
		} );

		helpers.visitTitle( title, { veaction: 'edit' } );
		helpers.waitForVEToLoad();
	} );

	it( 'should edit and verify reference content in Visual Editor', () => {
		helpers.getVEFootnoteMarker( 'a', 1, 1 ).click();

		// Popup appears containing ref content
		helpers.getVEReferenceContextItem()
			.should( 'be.visible' )
			.should( 'contain.text', refText1 );

		// Open reference edit dialog
		helpers.getVEReferenceContextItemEdit().click();

		// Dialog appears with ref content
		helpers.getVEReferenceEditDialog()
			.should( 'be.visible' )
			.should( 'contain.text', refText1 );
	} );

	it( 'should display existing references in the Cite re-use dialog', () => {
		helpers.openVECiteReuseDialog();

		// Assert reference content for the first reference
		helpers.getCiteReuseDialogRefResultName( 1 ).should( 'have.text', 'a' );
		helpers.getCiteReuseDialogRefResultCitation( 1 ).should( 'have.text', '[1]' );
		helpers.getCiteReuseDialogRefText( 1 ).should( 'have.text', refText1 );

		// Assert reference content for the second reference
		helpers.getCiteReuseDialogRefResultName( 2 ).should( 'have.text', '' );
		helpers.getCiteReuseDialogRefResultCitation( 2 ).should( 'have.text', '[2]' );
		helpers.getCiteReuseDialogRefText( 2 ).should( 'have.text', refText2 );

	} );

} );
