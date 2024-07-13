/*!
 * VisualEditor MediaWiki-specific ContentEditable Document tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.Document (MW)' );

/* Tests */

QUnit.test( 'Converter tests', ( assert ) => {
	const cases = ve.dm.mwExample.domToDataCases;

	for ( const msg in cases ) {
		if ( cases[ msg ].ceHtml ) {
			const caseItem = ve.copy( cases[ msg ] );
			caseItem.base = caseItem.base || ve.dm.mwExample.baseUri;
			const model = ve.test.utils.getModelFromTestCase( caseItem );
			const view = new ve.ce.Document( model );
			const $documentElement = view.getDocumentNode().$element;
			// Simplify slugs
			$documentElement.find( '.ve-ce-branchNode-slug' ).contents().remove();
			assert.equalDomElement(
				// Wrap both in plain DIVs as we are only comparing the child nodes
				$( '<div>' ).append( $documentElement.contents() )[ 0 ],
				$( '<div>' ).html( caseItem.ceHtml )[ 0 ],
				msg
			);
		}
	}
} );
