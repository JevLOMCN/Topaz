/*!
 * VisualEditor DataModel MediaWiki-specific SurfaceFragment tests.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

QUnit.module( 've.dm.SurfaceFragment (MW)', ve.test.utils.newMwEnvironment() );

/* Tests */

QUnit.test( 'isolateAndUnwrap (MWheading)', ( assert ) => {
	ve.test.utils.runIsolateTest( assert, 'mwHeading', new ve.Range( 12, 20 ), ( data ) => {
		data.splice( 11, 0, { type: '/list' } );
		data.splice( 12, 1 );
		data.splice( 20, 1, { type: 'list', attributes: { style: 'bullet' } } );
	}, ve.dm.mwExample.baseUri, 'isolating paragraph in list item "Item 2" for MWheading' );

	ve.test.utils.runIsolateTest( assert, 'mwHeading', new ve.Range( 202, 212 ), ( data ) => {
		data.splice( 201, 1,
			{ type: '/list' }, { type: '/listItem' }, { type: '/list' }
		);
		data.splice( 214, 1,
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem' },
			{ type: 'list', attributes: { style: 'number' } }
		);
	}, ve.dm.mwExample.baseUri, 'isolating paragraph in list item "Nested 2" for MWheading' );
} );

QUnit.test( 'insertContent (MWheading)', ( assert ) => {
	const doc = new ve.dm.Document( [
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem' },
			{ type: 'paragraph' },
			'a',
			'b',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		] ),
		surface = new ve.dm.Surface( doc ),
		headingData = [ { type: 'mwHeading', attributes: { level: 3 } }, 'x', { type: '/mwHeading' } ];

	let fragment = surface.getLinearFragment( new ve.Range( 4, 4 ) );
	fragment.insertContent( headingData );
	assert.deepEqual(
		doc.getData( new ve.Range( 3, 14 ) ),
		[
			'a',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'mwHeading', attributes: { level: 3 } },
			'x',
			{ type: '/mwHeading' },
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem' },
			{ type: 'paragraph' },
			'b'
		],
		'inserting a mwheading into a listitem should isolate it from the list'
	);

	surface.undo();
	fragment = surface.getLinearFragment( new ve.Range( 8, 8 ) );
	fragment.insertContent( headingData );
	assert.deepEqual(
		doc.getData( new ve.Range( 7, 11 ) ),
		[
			{ type: '/list' },
			{ type: 'mwHeading', attributes: { level: 3 } },
			'x',
			{ type: '/mwHeading' }
		],
		'inserting a mwheading to the document root should not add any extra closing elements'
	);
} );
