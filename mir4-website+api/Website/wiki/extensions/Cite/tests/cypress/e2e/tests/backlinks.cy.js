import * as helpers from '../utils/functions.helper.js';

const title = helpers.getTestString( 'CiteTest-title' );

describe( 'Cite backlinks test', () => {
	before( () => {
		helpers.visitTitle( '' );

		const wikiText = 'This is reference #1: <ref name="a">This is citation #1 for reference #1 and #2</ref><br> ' +
			'This is reference #2: <ref name="a" /><br>' +
			'This is reference #3: <ref>This is citation #2</ref><br>' +
			'<references />';

		helpers.editPage( title, wikiText );
	} );

	beforeEach( () => {
		helpers.visitTitle( title );

		cy.window().should( 'have.property', 'mw' ).and( 'have.property', 'loader' ).and( 'have.property', 'using' );
		cy.window().then( async ( win ) => {
			await win.mw.loader.using( 'mediawiki.base' ).then( async function () {
				await win.mw.hook( 'wikipage.content' ).add( function () {} );
			} );
		} );
	} );

	it( 'hides clickable up arrow by default when there are multiple backlinks', () => {
		helpers.getCiteMultiBacklink( 1 ).should( 'not.exist' );
	} );

	it( 'hides clickable up arrow when jumping back from multiple used references', () => {
		helpers.getReference( 2 ).click();
		helpers.getCiteMultiBacklink( 1 ).click();
		// The up-pointing arrow in the reference line is not linked
		helpers.getCiteMultiBacklink( 1 ).should( 'not.be.visible' );
	} );

	it( 'shows clickable up arrow when jumping to multiple used references', () => {
		helpers.getReference( 2 ).click();
		helpers.getCiteMultiBacklink( 1 ).should( 'be.visible' );

		helpers.getFragmentFromLink( helpers.getCiteMultiBacklink( 1 ) ).then( ( linkFragment ) => {
			helpers.getReference( 2 ).invoke( 'attr', 'id' ).should( 'eq', linkFragment );
		} );
	} );

	it( 'highlights backlink in the reference list for the clicked reference when there are multiple used references', () => {
		cy.get( '.mw-page-title-main' ).should( 'be.visible' );
		helpers.getReference( 2 ).click();
		helpers.getCiteSubBacklink( 2 ).should( 'have.class', 'mw-cite-targeted-backlink' );
	} );

	it( 'uses the last clicked target for the clickable up arrow on multiple used references', () => {
		helpers.getReference( 2 ).click();
		helpers.getReference( 1 ).click();

		helpers.getFragmentFromLink( helpers.getCiteMultiBacklink( 1 ) ).then( ( linkFragment ) => {
			helpers.getReference( 1 ).invoke( 'attr', 'id' ).then( ( referenceId ) => {
				expect( linkFragment ).to.equal( referenceId );

			} );
		} );
	} );

	it( 'retains backlink visibility for unnamed references when interacting with other references', () => {
		helpers.getReference( 3 ).click();
		helpers.getCiteSingleBacklink( 2 ).click();
		// Doesn' matter what is focused next, just needs to be something else
		helpers.getReference( 1 ).click();
		// The backlink of the unnamed reference is still visible
		helpers.getCiteSingleBacklink( 2 ).should( 'be.visible' );
	} );
} );
