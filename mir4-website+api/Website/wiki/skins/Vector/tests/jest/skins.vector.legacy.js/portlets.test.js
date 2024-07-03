const portlets = require( '../../../resources/skins.vector.legacy.js/portlets.js' );
const mustache = require( 'mustache' );
const fs = require( 'fs' );
const menuTemplate = fs.readFileSync( 'includes/templates/LegacyMenu.mustache', 'utf8' );
const menuContentsTemplate = fs.readFileSync( 'includes/templates/MenuContents.mustache', 'utf8' );

describe( 'portlets', () => {
	test( 'portlets that go through the hook method should match the menu template HTML (non-dropdown version)', () => {
		const id = 'foo';
		const label = 'label text';
		const portletHTML = mustache.render( menuTemplate, {
			id, class: '', label
		}, {
			MenuContents: menuContentsTemplate
		} );

		const element = document.createElement( 'div' );
		element.id = id;
		if ( label ) {
			const labelElement = document.createElement( 'label' );
			labelElement.textContent = label;
			element.appendChild( labelElement );
		}
		element.appendChild( document.createElement( 'ul' ) );
		expect( portlets.addPortletHandler( element ).outerHTML.replace( /[\s\n]/gi, '' ) )
			.toBe(
				portletHTML.replace( /[\s\n]/gi, '' )
					// Known Difference 1: Portlets created via JS are `div` elements not `nav`
					.replace( /<nav/g, '<div' )
					.replace( /nav>/g, 'div>' )
					// Known Difference 2: Portlets created via JS have `div` elements
					// rather than `h3 > span`
					.replace( /<spanclass="vector-menu-heading-label">/g, '' )
					.replace( /<h3/g, '<div' )
					.replace( /<\/span><\/h3>/g, '</div>' )
			);
	} );

	test( 'portlet dropdowns that go through the hook method should match the menu template HTML (dropdown version)', () => {
		const id = 'foo';
		const label = 'label text';
		const portletHTML = mustache.render( menuTemplate, {
			id, class: 'vector-menu-dropdown', label,
			'is-dropdown': true
		}, {
			MenuContents: menuContentsTemplate
		} );

		const rNav = document.createElement( 'div' );
		rNav.id = 'right-navigation';
		const element = document.createElement( 'div' );
		element.id = id;
		if ( label ) {
			const labelElement = document.createElement( 'label' );
			labelElement.textContent = label;
			element.appendChild( labelElement );
		}
		element.appendChild( document.createElement( 'ul' ) );
		rNav.appendChild( element );
		expect( portlets.addPortletHandler( element ).outerHTML.replace( /[\s\n]/gi, '' ) )
			.toBe(
				portletHTML.replace( /[\s\n]/gi, '' )
					// Known Difference 1: Portlets created via JS are `div` elements not `nav`
					.replace( /<nav/g, '<div' )
					.replace( /nav>/g, 'div>' )
					// Known Difference 2: Portlets created via JS
					// have `div` elements rather than `label`
					.replace( /<spanclass="vector-menu-heading-label">/g, '' )
					.replace( /<label/g, '<div' )
					.replace( /<\/span><\/label>/g, '</div>' )
			);
	} );
} );
