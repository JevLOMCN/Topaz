const portlets = require( '../../../resources/skins.vector.js/portlets.js' );
const mustache = require( 'mustache' );
const fs = require( 'fs' );
const menuTemplate = fs.readFileSync( 'includes/templates/Menu.mustache', 'utf8' );
const menuContentsTemplate = fs.readFileSync( 'includes/templates/MenuContents.mustache', 'utf8' );
const dropdownOpen = fs.readFileSync( 'includes/templates/Dropdown/Open.mustache', 'utf8' );
const dropdownClose = fs.readFileSync( 'includes/templates/Dropdown/Close.mustache', 'utf8' );

describe( 'portlets', () => {
	beforeEach( () => {
		const marker = document.querySelector( '#vector-page-tools-dropdown' );
		if ( marker ) {
			marker.remove();
		}
	} );
	test( 'portlets that go through the hook method should match the menu template HTML', () => {
		const id = 'foo';
		const label = 'label text';
		const portletHTML = mustache.render( menuTemplate, {
			id, class: '', label
		}, {
			MenuContents: menuContentsTemplate
		} );

		const element = document.createElement( 'div' );
		element.id = id;
		const labelElement = document.createElement( 'label' );
		labelElement.textContent = label;
		element.appendChild( labelElement );
		element.appendChild( document.createElement( 'ul' ) );
		expect( portlets.addPortletHandler( element ).outerHTML.replace( /[\s\n]/gi, '' ) )
			.toBe( portletHTML.replace( /[\s\n]/gi, '' ) );
	} );

	test( 'portlets that go through the hook method should match the menu template HTML (dropdowns)', () => {
		const id = 'foo';
		const label = 'dropdown label text';
		const marker = document.createElement( 'div' );
		marker.id = 'vector-page-tools-dropdown';
		document.body.appendChild( marker );
		const dropdownHTML = mustache.render( dropdownOpen, {
			id: `${ id }-dropdown`,
			class: 'foo-dropdown',
			label
		}, {} ) + mustache.render( dropdownClose, {}, {} );

		const element = document.createElement( 'div' );
		element.id = id;
		const labelElement = document.createElement( 'label' );
		labelElement.textContent = label;
		element.appendChild( labelElement );
		element.appendChild( document.createElement( 'ul' ) );
		document.body.appendChild( element );

		const transformedPortlet = portlets.addPortletHandler( element, '#p-cactions' );
		const dropdown = transformedPortlet.closest( '.vector-dropdown' );
		// removing the portlet should give us an empty dropdown
		transformedPortlet.remove();

		expect( dropdown.outerHTML.replace( /[\s\n]/gi, '' ) )
			.toBe( dropdownHTML.replace( /[\s\n]/gi, '' ) );
	} );
} );
