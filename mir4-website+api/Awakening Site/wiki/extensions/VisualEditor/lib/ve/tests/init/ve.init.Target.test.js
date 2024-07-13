/*!
 * VisualEditor tests for ve.init.Target.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.init.Target' );

QUnit.test( 'createModelFromDom/parseDocument (source mode)', function ( assert ) {
	const testCases = [
		{
			name: 'empty',
			sourceText: '',
			expectedParsedDocument: '',
			expectedModel: [ { type: 'paragraph' }, { type: '/paragraph' }, { type: 'internalList' }, { type: '/internalList' } ]
		},
		{
			name: 'basic',
			sourceText: 'A? B! CD.',
			expectedParsedDocument: 'A? B! CD.',
			expectedModel: [ { type: 'paragraph' }, 'A', '?', ' ', 'B', '!', ' ', 'C', 'D', '.', { type: '/paragraph' }, { type: 'internalList' }, { type: '/internalList' } ]
		},
		{
			name: 'complex',
			sourceText: 'A?\nB\n<!-- C! -->\n\nD.',
			expectedParsedDocument: 'A?\nB\n<!-- C! -->\n\nD.',
			expectedModel: [ { type: 'paragraph' }, 'A', '?', { type: '/paragraph' }, { type: 'paragraph' }, 'B', { type: '/paragraph' }, { type: 'paragraph' }, '<', '!', '-', '-', ' ', 'C', '!', ' ', '-', '-', '>', { type: '/paragraph' }, { type: 'paragraph' }, { type: '/paragraph' }, { type: 'paragraph' }, 'D', '.', { type: '/paragraph' }, { type: 'internalList' }, { type: '/internalList' } ]
		},
		{
			name: 'unicode',
			sourceText: '維基百科ㅋㅏ난다韓國語',
			expectedParsedDocument: '維基百科ㅋㅏ난다韓國語',
			expectedModel: [ { type: 'paragraph' }, '維', '基', '百', '科', 'ㅋ', 'ㅏ', '난', '다', '韓', '國', '語', { type: '/paragraph' }, { type: 'internalList' }, { type: '/internalList' } ]
		}
	];

	for ( const testCase of testCases ) {
		const sourceDoc = ve.init.Target.static.createModelFromDom( testCase.sourceText, 'source' );

		assert.true(
			sourceDoc instanceof ve.dm.Document,
			'Source "' + testCase.name + '" document returns a Document'
		);
		assert.deepEqual(
			sourceDoc.getData(),
			testCase.expectedModel,
			'Source "' + testCase.name + '" document has the correct content'
		);

		const parsedSourceDoc = ve.init.Target.static.parseDocument( testCase.sourceText, 'source' );
		assert.strictEqual(
			parsedSourceDoc,
			testCase.expectedParsedDocument,
			'Source "' + testCase.name + '" document is returned intact by parseDocument'
		);
	}
} );
