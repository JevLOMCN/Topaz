/*!
 * VisualEditor DataModel AnnotationSet tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.AnnotationSet' );

/* Tests */

QUnit.test( 'Basic usage', function ( assert ) {
	var store = new ve.dm.HashValueStore(),
		bold = new ve.dm.BoldAnnotation(),
		italic = new ve.dm.ItalicAnnotation(),
		underline = new ve.dm.UnderlineAnnotation(),
		annotationSet = new ve.dm.AnnotationSet( store, store.hashAll( [ bold, italic ] ) ),
		annotationSet2 = new ve.dm.AnnotationSet( store, store.hashAll( [ italic, underline ] ) ),
		emptySet = new ve.dm.AnnotationSet( store );

	assert.strictEqual( annotationSet.getLength(), 2, 'getLength is 2' );
	assert.strictEqual( annotationSet.isEmpty(), false, 'isEmpty is false' );
	assert.deepEqual( annotationSet.get( 0 ), bold, 'get(0) is bold' );
	assert.strictEqual( annotationSet.contains( italic ), true, 'contains italic' );
	assert.strictEqual( annotationSet.contains( underline ), false, 'doesn\'t contain underline' );
	assert.strictEqual( annotationSet.containsHash( store.hashOfValue( italic ) ), true, 'contains italic by hash' );
	assert.strictEqual( annotationSet.containsHash( store.hashOfValue( underline ) ), false, 'doesn\'t contain underline by hash' );
	assert.strictEqual( annotationSet.containsAnyOf( annotationSet2 ), true, 'containsAnyOf set2 is true' );
	assert.strictEqual( annotationSet.containsAnyOf( emptySet ), false, 'containsAnyOf empty set is false' );
	assert.strictEqual( annotationSet.containsAllOf( annotationSet2 ), false, 'containsAllOf set2 set is false' );
	assert.strictEqual( annotationSet.containsAllOf( annotationSet ), true, 'containsAllOf self is true' );
	assert.strictEqual( annotationSet.offsetOf( italic ), 1, 'offsetOf italic is 1' );
	assert.strictEqual( annotationSet.offsetOf( underline ), -1, 'offsetOf underline is -1' );
	assert.deepEqual(
		annotationSet.filter( function ( annotation ) {
			return annotation.name === 'textStyle/bold';
		} ).get(),
		[ bold ], 'filter for name=textStyle/bold returns just bold annotation'
	);
	assert.strictEqual( annotationSet.hasAnnotationWithName( 'textStyle/bold' ), true, 'hasAnnotationWithName textStyle/bold is true' );
	assert.strictEqual( annotationSet.hasAnnotationWithName( 'textStyle/underline' ), false, 'hasAnnotationWithName underline is false' );

	annotationSet2.add( bold, 1 );
	assert.strictEqual( annotationSet2.offsetOf( bold ), 1, 'set2 contains bold at 1 after add at 1' );
	annotationSet2.remove( bold );
	assert.strictEqual( annotationSet2.contains( bold ), false, 'set2 doesn\'t contain bold after remove' );
	annotationSet2.add( bold, 0 );
	assert.strictEqual( annotationSet2.offsetOf( bold ), 0, 'set2 contains bold at 0 after add at 0' );
	annotationSet2.add( bold, 0 );
	assert.strictEqual( annotationSet2.getLength(), 3, 'adding existing annotation doesn\'t change length' );
	// Set is now [ bold, italic, underline ]
	annotationSet2.removeAt( 2 );
	assert.strictEqual( annotationSet2.contains( underline ), false, 'set2 doesn\'t contain underline after removeAt 2' );
	annotationSet2.removeAll();
	assert.strictEqual( annotationSet2.isEmpty(), true, 'set2 is empty after removeAll' );
	annotationSet2.addSet( annotationSet );
	assert.strictEqual( annotationSet.getLength(), 2, 'set2 has length 2 after addSet' );
	annotationSet2.removeSet( annotationSet );
	assert.strictEqual( annotationSet2.isEmpty(), true, 'set2 is empty after removeSet' );
	annotationSet2.push( bold );
	annotationSet2.push( italic );
	assert.deepEqual( annotationSet2.get(), [ bold, italic ], 'set2 contains bold then italic after two pushes' );

	annotationSet2 = new ve.dm.AnnotationSet( store, store.hashAll( [ italic, underline ] ) );
	annotationSet2.removeNotInSet( annotationSet );
	assert.strictEqual( annotationSet.contains( italic ) && !annotationSet.contains( underline ), true, 'contains italic not underline after removeNotInSet' );
	annotationSet2.add( underline, 1 );
	var annotationSet3 = annotationSet2.reversed();
	assert.strictEqual( annotationSet3.offsetOf( underline ), 0, 'underline has offsetOf 0 after reverse' );
	annotationSet3 = annotationSet.mergeWith( annotationSet2 );
	assert.strictEqual( annotationSet3.getLength(), 3, 'set merged with set2 has length 3' );
	annotationSet3 = annotationSet.diffWith( annotationSet2 );
	assert.strictEqual( annotationSet3.getLength(), 1, 'set diffed with set2 has length 1' );
	assert.strictEqual( annotationSet3.contains( bold ), true, 'set diffed with set2 contains bold' );
	annotationSet3 = annotationSet.intersectWith( annotationSet2 );
	assert.strictEqual( annotationSet3.getLength(), 1, 'set intersected with set2 has length 1' );
	assert.strictEqual( annotationSet3.contains( italic ), true, 'set intersected with set2 contains italic' );
} );

QUnit.test( 'Comparable', function ( assert ) {
	var store = new ve.dm.HashValueStore(),
		bold = new ve.dm.BoldAnnotation(),
		italic = new ve.dm.ItalicAnnotation(),
		strong = new ve.dm.BoldAnnotation( { type: 'textStyle/bold', attributes: { nodeName: 'strong' } } ),
		underline = new ve.dm.UnderlineAnnotation(),
		annotationSet = new ve.dm.AnnotationSet( store, store.hashAll( [ bold, italic ] ) ),
		annotationSet2 = new ve.dm.AnnotationSet( store, store.hashAll( [ strong, underline ] ) ),
		emptySet = new ve.dm.AnnotationSet( store );

	assert.strictEqual( annotationSet.containsComparable( strong ), true, '[b,i] contains comparable strong' );
	assert.strictEqual( annotationSet.containsComparable( bold ), true, '[b,i] contains comparable b' );
	assert.strictEqual( annotationSet.containsComparable( underline ), false, '[b,i] doesn\'t contain comparable u' );

	var annotationSet3 = new ve.dm.AnnotationSet( store, store.hashAll( [ bold ] ) );
	assert.deepEqual( annotationSet.getComparableAnnotations( strong ), annotationSet3, '[b,i] get comparable strong returns [b]' );
	assert.deepEqual( annotationSet.getComparableAnnotations( underline ), emptySet, '[b,i] get comparable underline returns []' );

	annotationSet3 = new ve.dm.AnnotationSet( store, store.hashAll( [ bold ] ) );
	assert.deepEqual( annotationSet.getComparableAnnotationsFromSet( annotationSet2 ), annotationSet3, '[b,i] get comparable from set [strong,u] returns just [b]' );

	annotationSet3 = new ve.dm.AnnotationSet( store, store.hashAll( [ italic, strong ] ) );
	assert.strictEqual( annotationSet.compareTo( annotationSet3 ), true, '[b,i] compares to [i,strong]' );

} );
