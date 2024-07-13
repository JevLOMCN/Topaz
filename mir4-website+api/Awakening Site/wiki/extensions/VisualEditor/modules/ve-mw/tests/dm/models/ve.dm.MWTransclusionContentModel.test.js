{
	QUnit.module( 've.dm.MWTransclusionContentModel' );

	/**
	 * @return {ve.dm.MWTransclusionModel} but it's a mock
	 */
	const createTransclusionModel = function () {
		return {
			nextUniquePartId: () => 0
		};
	};

	[
		[ undefined, false ],
		[ null, false ],
		[ '', false ],
		[ ' ', true ],
		[ '0', true ],
		[ '\nfoo', true ]
	].forEach( ( [ wikitext, expected ] ) =>
		QUnit.test( 'containsValuableData: ' + wikitext, ( assert ) => {
			const model = new ve.dm.MWTransclusionContentModel( createTransclusionModel(), wikitext );

			assert.strictEqual( model.containsValuableData(), expected );
		} )
	);

}
