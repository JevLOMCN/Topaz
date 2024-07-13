function createStubTitle( fragment = null ) {
	return {
		getFragment() {
			return fragment;
		}
	};
}

( mw.loader.getModuleNames().indexOf( 'ext.popups.main' ) !== -1 ?
	QUnit.module :
	QUnit.module.skip )( 'ext.cite.referencePreviews#createReferenceGateway', {
	beforeEach() {
		// FIXME: Is this needed?
		// global.CSS = {
		// escape: ( str ) => $.escapeSelector( str )
		// };
		mw.msg = ( key ) => `<${ key }>`;
		mw.message = ( key ) => {
			return { exists: () => !key.endsWith( 'generic' ), text: () => `<${ key }>` };
		};

		this.$sourceElement = $( '<a>' ).appendTo(
			$( '<sup>' ).attr( 'id', 'cite_ref-1' ).appendTo( document.body )
		);

		this.$references = $( '<ul>' ).append(
			$( '<li>' ).attr( 'id', 'cite_note-1' ).append(
				$( '<span>' ).addClass( 'mw-reference-text' ).text( 'Footnote 1' )
			),
			$( '<li>' ).attr( 'id', 'cite_note-2' ).append(
				$( '<span>' ).addClass( 'reference-text' ).append(
					$( '<cite>' ).addClass( 'journal web unknown' ).text( 'Footnote 2' )
				)
			),
			$( '<li>' ).attr( 'id', 'cite_note-3' ).append(
				$( '<span>' ).addClass( 'reference-text' ).append(
					$( '<cite>' ).addClass( 'news' ).text( 'Footnote 3' ),
					$( '<cite>' ).addClass( 'news citation' ),
					$( '<cite>' ).addClass( 'citation' )
				)
			),
			$( '<li>' ).attr( 'id', 'cite_note-4' ).append(
				$( '<span>' ).addClass( 'reference-text' ).append(
					$( '<cite>' ).addClass( 'news' ).text( 'Footnote 4' ),
					$( '<cite>' ).addClass( 'web' )
				)
			),
			$( '<li>' ).attr( 'id', 'cite_note-5' ).append(
				$( '<span>' ).addClass( 'mw-reference-text' ).html( '&nbsp;' )
			)
		).appendTo( document.body );
	},
	afterEach() {
		mw.msg = null;
		mw.message = null;
		this.$sourceElement.parent().remove();
		this.$references.remove();
	}
} );

QUnit.test( 'Reference preview gateway returns the correct data', function ( assert ) {
	const gateway = require( 'ext.cite.referencePreviews' ).private.createReferenceGateway(),
		title = createStubTitle( 'cite note-1' );

	return gateway.fetchPreviewForTitle( title, this.$sourceElement[ 0 ] ).then( ( result ) => {
		assert.propEqual(
			result,
			{
				url: '#cite_note-1',
				extract: 'Footnote 1',
				type: 'reference',
				referenceType: null,
				sourceElementId: 'cite_ref-1'
			}
		);
	} );
} );

QUnit.test( 'Reference preview gateway accepts alternative text node class name', function ( assert ) {
	const gateway = require( 'ext.cite.referencePreviews' ).private.createReferenceGateway(),
		title = createStubTitle( 'cite note-2' );

	return gateway.fetchPreviewForTitle( title, this.$sourceElement[ 0 ] ).then( ( result ) => {
		assert.propEqual(
			result,
			{
				url: '#cite_note-2',
				extract: '<cite class="journal web unknown">Footnote 2</cite>',
				type: 'reference',
				referenceType: 'web',
				sourceElementId: 'cite_ref-1'
			}
		);
	} );
} );

QUnit.test( 'Reference preview gateway accepts duplicated types', function ( assert ) {
	const gateway = require( 'ext.cite.referencePreviews' ).private.createReferenceGateway(),
		title = createStubTitle( 'cite note-3' );

	return gateway.fetchPreviewForTitle( title, this.$sourceElement[ 0 ] ).then( ( result ) => {
		assert.propEqual(
			result,
			{
				url: '#cite_note-3',
				extract: '<cite class="news">Footnote 3</cite><cite class="news citation"></cite><cite class="citation"></cite>',
				type: 'reference',
				referenceType: 'news',
				sourceElementId: 'cite_ref-1'
			}
		);
	} );
} );

QUnit.test( 'Reference preview gateway ignores conflicting types', function ( assert ) {
	const gateway = require( 'ext.cite.referencePreviews' ).private.createReferenceGateway(),
		title = createStubTitle( 'cite note-4' );

	return gateway.fetchPreviewForTitle( title, this.$sourceElement[ 0 ] ).then( ( result ) => {
		assert.propEqual(
			result,
			{
				url: '#cite_note-4',
				extract: '<cite class="news">Footnote 4</cite><cite class="web"></cite>',
				type: 'reference',
				referenceType: 'news',
				sourceElementId: 'cite_ref-1'
			}
		);
	} );
} );

QUnit.test( 'Reference preview gateway returns source element id', function ( assert ) {
	const gateway = require( 'ext.cite.referencePreviews' ).private.createReferenceGateway(),
		title = createStubTitle( 'cite note-1' );

	return gateway.fetchPreviewForTitle( title, this.$sourceElement[ 0 ] ).then( ( result ) => {
		assert.propEqual(
			result,
			{
				url: '#cite_note-1',
				extract: 'Footnote 1',
				type: 'reference',
				referenceType: null,
				sourceElementId: 'cite_ref-1'
			}
		);
	} );
} );

QUnit.test( 'Reference preview gateway rejects non-existing references', function ( assert ) {
	const gateway = require( 'ext.cite.referencePreviews' ).private.createReferenceGateway(),
		title = createStubTitle( 'undefined' );

	return gateway.fetchPreviewForTitle( title, this.$sourceElement[ 0 ] ).then( () => {
		assert.true( false, 'It should not resolve' );
	} ).catch( ( result ) => {
		assert.propEqual( result, { textStatus: 'abort', textContext: 'Footnote not found or empty', xhr: { readyState: 0 } } );
	} );
} );

QUnit.test( 'Reference preview gateway rejects all-whitespace references', function ( assert ) {
	const gateway = require( 'ext.cite.referencePreviews' ).private.createReferenceGateway(),
		title = createStubTitle( 'cite note-5' );

	return gateway.fetchPreviewForTitle( title, this.$sourceElement[ 0 ] ).then( () => {
		assert.true( false, 'It should not resolve' );
	} ).catch( ( result ) => {
		assert.propEqual( result, { textStatus: 'abort', textContext: 'Footnote not found or empty', xhr: { readyState: 0 } } );
	} );
} );

QUnit.test( 'Reference preview gateway is abortable', function ( assert ) {
	const gateway = require( 'ext.cite.referencePreviews' ).private.createReferenceGateway(),
		title = createStubTitle( 'cite note-1' ),
		promise = gateway.fetchPreviewForTitle( title, this.$sourceElement[ 0 ] );

	assert.strictEqual( typeof promise.abort, 'function' );
} );
