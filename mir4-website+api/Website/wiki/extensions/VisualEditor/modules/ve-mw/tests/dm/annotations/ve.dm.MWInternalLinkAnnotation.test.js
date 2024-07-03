/*!
 * VisualEditor DataModel MWInternalLinkAnnotation tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.MWInternalLinkAnnotation', ve.test.utils.newMwEnvironment() );

QUnit.test( 'toDataElement', ( assert ) => {
	// The expected data depends on site configuration, so we need to generate the cases several times.
	const getCases = () => {
		const
			externalLink = ( href ) => {
				const link = document.createElement( 'a' );
				link.setAttribute( 'href', href );
				return link;
			},
			internalLink = ( pageTitle, params ) => {
				const link = document.createElement( 'a' );
				link.setAttribute( 'href', mw.Title.newFromText( pageTitle ).getUrl( params ) );
				return link;
			},
			parsoidLink = ( href ) => {
				const link = document.createElement( 'a' );
				if ( mw.config.get( 'wgArticlePath' ).includes( '?' ) ) {
					href = href.replace( './', './index.php?title=' );
				}
				link.setAttribute( 'href', href );
				return link;
			};
		return [
			{
				msg: 'Not an internal link',
				element: externalLink( 'http://example.com/' ),
				expected: {
					type: 'link/mwExternal',
					attributes: {
						href: 'http://example.com/'
					}
				}
			},
			{
				msg: 'Simple',
				element: internalLink( 'Foo' ),
				expected: {
					type: 'link/mwInternal',
					attributes: {
						lookupTitle: 'Foo',
						normalizedTitle: 'Foo',
						title: 'Foo'
					}
				}
			},
			{
				msg: 'Relative path',
				element: parsoidLink( './Foo' ),
				expected: {
					type: 'link/mwInternal',
					attributes: {
						lookupTitle: 'Foo',
						normalizedTitle: 'Foo',
						title: 'Foo'
					}
				}
			},
			{
				msg: 'History link',
				element: internalLink( 'Foo', { action: 'history' } ),
				expected: {
					type: 'link/mwExternal',
					attributes: {
						href: mw.Title.newFromText( 'Foo' ).getUrl( { action: 'history' } )
					}
				}
			},
			{
				msg: 'Diff link',
				element: internalLink( 'Foo', { diff: '3', oldid: '2' } ),
				expected: {
					type: 'link/mwExternal',
					attributes: {
						href: mw.Title.newFromText( 'Foo' ).getUrl( { diff: '3', oldid: '2' } )
					}
				}
			},
			{
				msg: 'Red link',
				element: internalLink( 'Foo', { action: 'edit', redlink: '1' } ),
				expected: {
					type: 'link/mwInternal',
					attributes: {
						lookupTitle: 'Foo',
						normalizedTitle: 'Foo',
						title: 'Foo'
					}
				}
			},
			{
				// Because percent-encoded URLs aren't valid titles, but what they decode to might be
				msg: 'Percent encoded characters',
				element: internalLink( 'Foo?' ),
				expected: {
					type: 'link/mwInternal',
					attributes: {
						lookupTitle: 'Foo?',
						normalizedTitle: 'Foo?',
						title: 'Foo?'
					}
				}
			},
			{
				// The fragment should make it into some parts of this, and not others
				msg: 'Fragments',
				element: internalLink( 'Foo#bar' ),
				expected: {
					type: 'link/mwInternal',
					attributes: {
						lookupTitle: 'Foo',
						normalizedTitle: 'Foo#bar',
						title: 'Foo#bar'
					}
				}
			},
			{
				// Question marks in the fragment shouldn't confuse this
				msg: 'Question marks in fragments',
				element: internalLink( 'Foo#bar?' ),
				expected: {
					type: 'link/mwInternal',
					attributes: {
						lookupTitle: 'Foo',
						normalizedTitle: 'Foo#' + mw.util.escapeIdForLink( 'bar?' ),
						title: 'Foo#' + mw.util.escapeIdForLink( 'bar?' )
					}
				}
			}
		];
	};

	const converter = new ve.dm.Converter( ve.dm.modelRegistry, ve.dm.nodeFactory, ve.dm.annotationFactory, ve.dm.metaItemFactory );

	const articlePaths = [
		{
			// MediaWiki config settings:
			// $wgScriptPath = '/w';
			// $wgUsePathInfo = false;
			msg: 'query string URL',
			config: {
				wgServer: 'http://example.org',
				wgScript: '/w/index.php',
				wgArticlePath: '/w/index.php?title=$1'
			},
			// Parsoid-generated <base href> given these MediaWiki config settings:
			base: 'http://example.org/w/'
		},
		{
			// MediaWiki config settings:
			// $wgScriptPath = '/w';
			// $wgUsePathInfo = true;
			msg: 'short URL using pathinfo',
			config: {
				wgServer: 'http://example.org',
				wgScript: '/w/index.php',
				wgArticlePath: '/w/index.php/$1'
			},
			// Parsoid-generated <base href> given these MediaWiki config settings:
			base: 'http://example.org/w/index.php/'
		},
		{
			// MediaWiki config settings:
			// $wgScriptPath = '/w';
			// $wgArticlePath = '/wiki/$1';
			msg: 'proper short URL',
			config: {
				wgServer: 'http://example.org',
				wgScript: '/w/index.php',
				wgArticlePath: '/wiki/$1'
			},
			// Parsoid-generated <base href> given these MediaWiki config settings:
			base: 'http://example.org/wiki/'
		}
	];

	articlePaths.forEach( function ( pathData ) {
		// Set up global state (site configuration)
		mw.config.set( pathData.config );

		const doc = ve.dm.mwExample.createExampleDocumentFromData( [], null, pathData.base );
		// toDataElement is called during a converter run, so we need to fake up a bit of state to test it.
		// This would normally be done by ve.dm.converter.getModelFromDom.
		converter.doc = doc.getHtmlDocument();
		converter.targetDoc = doc.getHtmlDocument();
		converter.store = doc.getStore();
		converter.internalList = doc.getInternalList();
		converter.contextStack = [];
		converter.fromClipboard = true;

		// Generate test cases for this site configuration
		const cases = getCases();
		for ( let i = 0; i < cases.length; i++ ) {
			assert.deepEqual(
				ve.dm.MWInternalLinkAnnotation.static.toDataElement( [ cases[ i ].element ], converter ),
				cases[ i ].expected,
				cases[ i ].msg + ': ' + pathData.msg
			);
		}
	} );
} );

QUnit.test( 'getFragment', ( assert ) => {
	const cases = [
			{
				msg: 'No fragment returns null',
				original: 'Foo',
				expected: null
			},
			{
				msg: 'Invalid title returns null',
				original: 'A%20B',
				expected: null
			},
			{
				msg: 'Blank fragment returns empty string',
				original: 'Foo#',
				expected: ''
			},
			{
				msg: 'Extant fragment returns same string',
				original: 'Foo#bar',
				expected: 'bar'
			},
			{
				msg: 'Hash-bang works returns full string',
				original: 'Foo#!bar',
				expected: '!bar'
			},
			{
				msg: 'Double-hash returns everything after the first hash',
				original: 'Foo##bar',
				expected: '#bar'
			},
			{
				msg: 'Multi-fragment returns everything after the first hash',
				original: 'Foo#bar#baz#bat',
				expected: 'bar#baz#bat'
			}
		];

	for ( let i = 0; i < cases.length; i++ ) {
		assert.strictEqual( ve.dm.MWInternalLinkAnnotation.static.getFragment( cases[ i ].original ), cases[ i ].expected, cases[ i ].msg );
	}
} );
