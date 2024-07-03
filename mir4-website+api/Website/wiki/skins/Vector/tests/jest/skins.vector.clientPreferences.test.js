const clientPreferences = require( '../../resources/skins.vector.clientPreferences/clientPreferences.js' );

let cp;
describe( 'clientPreferences', () => {
	beforeEach( () => {
		document.body.innerHTML = '';
		cp = document.createElement( 'div' );
		cp.id = 'cp';
		document.body.appendChild( cp );
		mw.requestIdleCallback = ( callback ) => callback();
		mw.user.clientPrefs = {
			get: jest.fn( () => '1' )
		};
		const portlet = document.createElement( 'div' );
		mw.util.addPortlet = jest.fn( () => portlet );
		mw.util.addPortletLink = jest.fn( () => {
			const li = document.createElement( 'li' );
			const a = document.createElement( 'a' );
			li.appendChild( a );
			portlet.appendChild( li );
			return li;
		} );
		mw.message = jest.fn( ( key ) => ( {
			text: () => `msg:${ key }`,
			exists: () => true
		} ) );
	} );

	test( 'render empty', () => {
		return clientPreferences.render( '#cp', {} ).then( () => {
			expect( cp.innerHTML ).toMatchSnapshot();
		} );
	} );

	test( 'render font size', () => {
		document.documentElement.setAttribute( 'class', 'vector-feature-custom-font-size-clientpref-2' );
		return clientPreferences.render( '#cp', {
			'vector-feature-custom-font-size': {
				options: [ '0', '1', '2' ],
				preferenceKey: 'vector-font-size'
			}
		} ).then( () => {
			expect( cp.innerHTML ).toMatchSnapshot();
		} );
	} );

	test( 'render toggle', () => {
		document.documentElement.setAttribute( 'class', 'expandAll-clientpref-1' );
		return clientPreferences.render( '#cp', {
			expandAll: {
				options: [ '0', '1' ],
				preferenceKey: 'expandAll',
				type: 'switch'
			}
		} ).then( () => {
			expect( cp.innerHTML ).toMatchSnapshot();
		} );
	} );
} );
