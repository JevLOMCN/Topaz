import * as helpers from '../../utils/functions.helper.js';

const title = getTestString( 'CiteTest-title' );
const encodedTitle = encodeURIComponent( title );

function getTestString( prefix = '' ) {
	return prefix + Math.random().toString();
}

function skipTest( message ) {
	cy.log( message );
	// Dips into secret internals—stealing code from the skip plugin.
	const mochaContext = cy.state( 'runnable' ).ctx;
	return mochaContext.skip();
}

describe( 'Cite popups integration', () => {
	before( () => {
		cy.visit( '/index.php' );

		const wikiText = 'Lorem ipsum dolor.<ref>small reference</ref>' +
			'Reference with lots of text.<ref>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</ref>' +
			'Lorem ipsum dolor.<ref>reference inception{{#tag:ref|body}}</ref>';

		// Rely on the retry behavior of Cypress assertions to use this as a "wait"
		// until the specified conditions are met.
		cy.window().should( 'have.property', 'mw' ).and( 'have.property', 'loader' ).and( 'have.property', 'using' );
		// Create a new page containing a reference
		cy.window().then( async ( win ) => {
			await win.mw.loader.using( 'mediawiki.api' );
			const response = await new win.mw.Api().create( title, {}, wikiText );
			expect( response.result ).to.equal( 'Success' );

			await win.mw.loader.using( 'ext.popups.main', () => {}, () => skipTest( 'Popups not available' ) );
		} );

	} );

	beforeEach( () => {
		cy.visit( `/index.php?title=${ encodedTitle }` );
		cy.window()
			.should( 'have.property', 'mw' ).and( 'have.property', 'loader' ).and( 'have.property', 'getState' );
		cy.window().should( ( win ) => win.mw.loader.getState( 'ext.cite.referencePreviews' ) === 'ready' );
	} );

	it( 'simple popup on hover and hide on leave', () => {
		helpers.abandonReference( 'cite_ref-1' );
		helpers.dwellReference( 'cite_ref-1' );
		cy.get( '.mwe-popups-type-reference', { timeout: 1000 } )
			.should( 'be.visible' );
		helpers.assertPreviewIsScrollable( false );
		cy.get( '.mwe-popups-fade-out' ).should( 'not.exist' );

		helpers.abandonReference( 'cite_ref-1' );
		cy.get( '.mwe-popups-type-reference' )
			.should( 'not.exist' );
	} );

	it( 'includes scrollbar and fadeout on long previews', () => {
		helpers.abandonReference( 'cite_ref-2' );
		helpers.dwellReference( 'cite_ref-2' );
		cy.get( '.mwe-popups-type-reference', { timeout: 1000 } )
			.should( 'be.visible' );
		helpers.assertPreviewIsScrollable( true );
		cy.get( '.mwe-popups-fade-out' ).should( 'be.visible' );
	} );

	it( 'hovering nested reference', () => {
		helpers.abandonReference( 'cite_ref-3' );
		helpers.dwellReference( 'cite_ref-3' );
		cy.get( '.mwe-popups-type-reference', { timeout: 1000 } )
			.should( 'be.visible' );
		helpers.dwellReference( 'cite_ref-4' );
		// eslint-disable-next-line cypress/no-unnecessary-waiting
		cy.wait( 1000 );
		cy.get( '.mwe-popups-type-reference' )
			.should( 'include.text', 'reference inception' );
	} );

} );
