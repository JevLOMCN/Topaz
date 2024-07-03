/*!
 * VisualEditor DataModel Document tests.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

QUnit.module( 've.dm.Document (MW)' );

/* Tests */

// FIXME runner copypasted from core, use data provider
QUnit.test( 'getRelativeRange (mwBlockImage / mwInlineImage)', ( assert ) => {
	const store = new ve.dm.HashValueStore(),
		storeItems = [
			ve.dm.mwExample.MWBlockImage.storeItems,
			ve.dm.mwExample.MWInlineImage.storeItems
		],
		tests = [
			{
				data: [
					// 0
					ve.copy( ve.dm.mwExample.MWBlockImage.data[ 0 ] ),
					// 1
					{ type: '/mwBlockImage' }
				],
				cases: [
					{
						direction: 1,
						expand: false,
						given: new ve.Range( 0 ),
						expected: new ve.Range( 0, 2 )
					},
					{
						direction: 1,
						expand: false,
						given: new ve.Range( 0, 2 ),
						expected: new ve.Range( 2 )
					},
					{
						direction: 1,
						expand: true,
						given: new ve.Range( 0 ),
						expected: new ve.Range( 0, 2 )
					},
					{
						direction: 1,
						expand: true,
						given: new ve.Range( 0, 2 ),
						expected: new ve.Range( 0, 2 )
					},
					{
						direction: -1,
						expand: false,
						given: new ve.Range( 2 ),
						expected: new ve.Range( 2, 0 )
					},
					{
						direction: -1,
						expand: false,
						given: new ve.Range( 2, 0 ),
						expected: new ve.Range( 0 )
					},
					{
						direction: -1,
						expand: false,
						given: new ve.Range( 0, 2 ),
						expected: new ve.Range( 0 )
					},
					{
						direction: -1,
						expand: true,
						given: new ve.Range( 2 ),
						expected: new ve.Range( 2, 0 )
					},
					{
						direction: -1,
						expand: true,
						given: new ve.Range( 2, 0 ),
						expected: new ve.Range( 2, 0 )
					},
					{
						direction: -1,
						expand: true,
						given: new ve.Range( 0, 2 ),
						expected: new ve.Range( 0 )
					}
				]
			},
			{
				data: [
					// 0
					ve.copy( ve.dm.mwExample.MWBlockImage.data[ 0 ] ),
					// 1
					{ type: '/mwBlockImage' },
					// 2
					ve.copy( ve.dm.mwExample.MWBlockImage.data[ 0 ] ),
					// 3
					{ type: '/mwBlockImage' }
				],
				cases: [
					{
						direction: 1,
						expand: false,
						given: new ve.Range( 0, 2 ),
						expected: new ve.Range( 2, 4 )
					},
					{
						direction: 1,
						expand: false,
						given: new ve.Range( 2, 4 ),
						expected: new ve.Range( 4 )
					},
					{
						direction: 1,
						expand: true,
						given: new ve.Range( 0, 2 ),
						expected: new ve.Range( 0, 4 )
					},
					{
						direction: -1,
						expand: true,
						given: new ve.Range( 4, 2 ),
						expected: new ve.Range( 4, 0 )
					},
					{
						direction: -1,
						expand: true,
						given: new ve.Range( 2, 4 ),
						expected: new ve.Range( 2 )
					}
				]
			},
			{
				data: [
					// 0
					{ type: 'alienBlock' },
					// 1
					{ type: '/alienBlock' },
					// 2
					ve.copy( ve.dm.mwExample.MWBlockImage.data[ 0 ] ),
					// 3
					{ type: '/mwBlockImage' },
					// 4
					{ type: 'alienBlock' },
					// 5
					{ type: '/alienBlock' }
				],
				cases: [
					{
						direction: 1,
						expand: false,
						given: new ve.Range( 0 ),
						expected: new ve.Range( 0, 2 )
					},
					{
						direction: 1,
						expand: false,
						given: new ve.Range( 2 ),
						expected: new ve.Range( 2, 4 )
					},
					{
						direction: 1,
						expand: false,
						given: new ve.Range( 2, 4 ),
						expected: new ve.Range( 4 )
					},
					{
						direction: 1,
						expand: false,
						given: new ve.Range( 4 ),
						expected: new ve.Range( 4, 6 )
					},
					{
						direction: 1,
						expand: true,
						given: new ve.Range( 0 ),
						expected: new ve.Range( 0, 2 )
					},
					{
						direction: 1,
						expand: true,
						given: new ve.Range( 0, 2 ),
						expected: new ve.Range( 0, 4 )
					},
					{
						direction: 1,
						expand: true,
						given: new ve.Range( 0, 4 ),
						expected: new ve.Range( 0, 6 )
					}
				]
			},
			{
				data: [
					// 0
					{ type: 'paragraph' },
					// 1
					{ type: 'alienInline' },
					// 2
					{ type: '/alienInline' },
					// 3
					ve.copy( ve.dm.mwExample.MWInlineImage.data ),
					// 4
					{ type: '/mwInlineImage' },
					// 5
					{ type: 'alienInline' },
					// 6
					{ type: '/alienInline' },
					// 7
					{ type: '/paragraph' }
				],
				cases: [
					{
						direction: 1,
						expand: false,
						given: new ve.Range( 1 ),
						expected: new ve.Range( 1, 3 )
					},
					{
						direction: 1,
						expand: false,
						given: new ve.Range( 5 ),
						expected: new ve.Range( 5, 7 )
					}
				]
			}
		];

	for ( let i = 0; i < storeItems.length; i++ ) {
		for ( let j = 0; j < storeItems[ i ].length; j++ ) {
			store.hash( storeItems[ i ][ j ].value, storeItems[ i ][ j ].hash );
		}
	}
	for ( let i = 0; i < tests.length; i++ ) {
		const documentModel = new ve.dm.Document( new ve.dm.ElementLinearData( store, tests[ i ].data ) );
		for ( let j = 0; j < tests[ i ].cases.length; j++ ) {
			assert.equalRange(
				documentModel.getRelativeRange(
					tests[ i ].cases[ j ].given,
					tests[ i ].cases[ j ].direction,
					'character',
					tests[ i ].cases[ j ].expand
				),
				tests[ i ].cases[ j ].expected,
				'Test document ' + i +
				', range ' + tests[ i ].cases[ j ].given.toJSON() +
				', direction ' + tests[ i ].cases[ j ].direction
			);
		}
	}
} );
