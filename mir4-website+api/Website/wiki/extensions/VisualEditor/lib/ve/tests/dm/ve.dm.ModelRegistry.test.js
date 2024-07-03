/*!
 * VisualEditor DataModel ModelRegistry tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.ModelRegistry' );

/* Stubs */
ve.dm.example.checkForPickMe = function ( node ) {
	return node.hasAttribute && node.hasAttribute( 'pickme' );
};

/* Nothing set */
ve.dm.example.StubNothingSetAnnotation = function VeDmStubNothingSetAnnotation() {
	ve.dm.example.StubNothingSetAnnotation.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubNothingSetAnnotation, ve.dm.Annotation );
ve.dm.example.StubNothingSetAnnotation.static.name = 'stubnothingset';

/* Single tag */
ve.dm.example.StubSingleTagAnnotation = function VeDmStubSingleTagAnnotation() {
	ve.dm.example.StubSingleTagAnnotation.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubSingleTagAnnotation, ve.dm.Annotation );
ve.dm.example.StubSingleTagAnnotation.static.name = 'stubsingletag';
ve.dm.example.StubSingleTagAnnotation.static.matchTagNames = [ 'a' ];

/* Single type with any allowed */
ve.dm.example.StubSingleTypeWithAnyAllowedAnnotation = function VeDmStubSingleTypeWithAnyAllowedAnnotation() {
	ve.dm.example.StubSingleTypeWithAnyAllowedAnnotation.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubSingleTypeWithAnyAllowedAnnotation, ve.dm.Annotation );
ve.dm.example.StubSingleTypeWithAnyAllowedAnnotation.static.name = 'stubsingletypewithanyallowed';
ve.dm.example.StubSingleTypeWithAnyAllowedAnnotation.static.matchRdfaTypes = [ 'ext:foo' ];
ve.dm.example.StubSingleTypeWithAnyAllowedAnnotation.static.allowedRdfaTypes = null;

/* Single type with single allowed */
ve.dm.example.StubSingleTypeWithAllowedAnnotation = function VeDmStubSingleTypeWithAllowedAnnotation() {
	ve.dm.example.StubSingleTypeWithAllowedAnnotation.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubSingleTypeWithAllowedAnnotation, ve.dm.Annotation );
ve.dm.example.StubSingleTypeWithAllowedAnnotation.static.name = 'stubsingletypewithallowed';
ve.dm.example.StubSingleTypeWithAllowedAnnotation.static.matchRdfaTypes = [ 'ext:foo' ];
ve.dm.example.StubSingleTypeWithAllowedAnnotation.static.allowedRdfaTypes = [ 'bar' ];

/* Single type */
ve.dm.example.StubSingleTypeAnnotation = function VeDmStubSingleTypeAnnotation() {
	ve.dm.example.StubSingleTypeAnnotation.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubSingleTypeAnnotation, ve.dm.Annotation );
ve.dm.example.StubSingleTypeAnnotation.static.name = 'stubsingletype';
ve.dm.example.StubSingleTypeAnnotation.static.matchRdfaTypes = [ 'ext:foo' ];

/* Single tag and type */
ve.dm.example.StubSingleTagAndTypeAnnotation = function VeDmStubSingleTagAndTypeAnnotation() {
	ve.dm.example.StubSingleTagAndTypeAnnotation.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubSingleTagAndTypeAnnotation, ve.dm.Annotation );
ve.dm.example.StubSingleTagAndTypeAnnotation.static.name = 'stubsingletagandtype';
ve.dm.example.StubSingleTagAndTypeAnnotation.static.matchTagNames = [ 'a' ];
ve.dm.example.StubSingleTagAndTypeAnnotation.static.matchRdfaTypes = [ 'ext:foo' ];

/* Function */
ve.dm.example.StubFuncAnnotation = function VeDmStubFuncAnnotation() {
	ve.dm.example.StubFuncAnnotation.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubFuncAnnotation, ve.dm.Annotation );
ve.dm.example.StubFuncAnnotation.static.name = 'stubfunc';
ve.dm.example.StubFuncAnnotation.static.matchFunction = ve.dm.example.checkForPickMe;

/* Tag and function */
ve.dm.example.StubSingleTagAndFuncAnnotation = function VeDmStubSingleTagAndFuncAnnotation() {
	ve.dm.example.StubSingleTagAndFuncAnnotation.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubSingleTagAndFuncAnnotation, ve.dm.Annotation );
ve.dm.example.StubSingleTagAndFuncAnnotation.static.name = 'stubsingletagandfunc';
ve.dm.example.StubSingleTagAndFuncAnnotation.static.matchTagNames = [ 'a' ];
ve.dm.example.StubSingleTagAndFuncAnnotation.static.matchFunction = ve.dm.example.checkForPickMe;

/* Tag and function (allows extra types) */
ve.dm.example.StubSingleTagAndFuncExtraAnnotation = function VeDmStubSingleTagAndFuncExtraAnnotation() {
	ve.dm.example.StubSingleTagAndFuncExtraAnnotation.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubSingleTagAndFuncExtraAnnotation, ve.dm.Annotation );
ve.dm.example.StubSingleTagAndFuncExtraAnnotation.static.name = 'stubsingletagandfuncextra';
ve.dm.example.StubSingleTagAndFuncExtraAnnotation.static.matchTagNames = [ 'a' ];
ve.dm.example.StubSingleTagAndFuncExtraAnnotation.static.matchFunction = ve.dm.example.checkForPickMe;
ve.dm.example.StubSingleTagAndFuncExtraAnnotation.static.allowedRdfaTypes = [ 'ext:bar' ];

/* Type and function */
ve.dm.example.StubSingleTypeAndFuncAnnotation = function VeDmStubSingleTypeAndFuncAnnotation() {
	ve.dm.example.StubSingleTypeAndFuncAnnotation.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubSingleTypeAndFuncAnnotation, ve.dm.Annotation );
ve.dm.example.StubSingleTypeAndFuncAnnotation.static.name = 'stubsingletypeandfunc';
ve.dm.example.StubSingleTypeAndFuncAnnotation.static.matchRdfaTypes = [ 'ext:foo' ];
ve.dm.example.StubSingleTypeAndFuncAnnotation.static.matchFunction = ve.dm.example.checkForPickMe;

/* Tag, type and function */
ve.dm.example.StubSingleTagAndTypeAndFuncAnnotation = function VeDmStubSingleTagAndTypeAndFuncAnnotation() {
	ve.dm.example.StubSingleTagAndTypeAndFuncAnnotation.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubSingleTagAndTypeAndFuncAnnotation, ve.dm.Annotation );
ve.dm.example.StubSingleTagAndTypeAndFuncAnnotation.static.name = 'stubsingletagandtypeandfunc';
ve.dm.example.StubSingleTagAndTypeAndFuncAnnotation.static.matchTagNames = [ 'a' ];
ve.dm.example.StubSingleTagAndTypeAndFuncAnnotation.static.matchRdfaTypes = [ 'ext:foo' ];
ve.dm.example.StubSingleTagAndTypeAndFuncAnnotation.static.matchFunction = ve.dm.example.checkForPickMe;

/* Type 'bar' */
ve.dm.example.StubBarNode = function VeDmStubBarNode() {
	ve.dm.example.StubBarNode.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubBarNode, ve.dm.BranchNode );
ve.dm.example.StubBarNode.static.name = 'stub-bar';
ve.dm.example.StubBarNode.static.matchRdfaTypes = [ 'bar' ];
// HACK keep ve.dm.Converter happy for now
// TODO once ve.dm.Converter is rewritten, this can be removed
ve.dm.example.StubBarNode.static.toDataElement = function () {};
ve.dm.example.StubBarNode.static.toDomElements = function () {};

/* Tag 'abbr', type 'ext:abbr' */
ve.dm.example.StubAbbrNode = function VeDmStubAbbrNode() {
	ve.dm.example.StubAbbrNode.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubAbbrNode, ve.dm.BranchNode );
ve.dm.example.StubAbbrNode.static.name = 'stub-abbr';
ve.dm.example.StubAbbrNode.static.matchTagNames = [ 'abbr' ];
ve.dm.example.StubAbbrNode.static.matchRdfaTypes = [ 'ext:abbr' ];

/* Tag 'abbr', type /^ext:/ */
ve.dm.example.StubRegExpNode = function VeDmStubRegExpNode() {
	ve.dm.example.StubRegExpNode.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.StubRegExpNode, ve.dm.BranchNode );
ve.dm.example.StubRegExpNode.static.name = 'stub-regexp';
ve.dm.example.StubRegExpNode.static.matchTagNames = [ 'abbr' ];
ve.dm.example.StubRegExpNode.static.matchRdfaTypes = [
	/^ext:/
];

/* Tests */

QUnit.test( 'register/unregister/matchElement', function ( assert ) {
	var registry = new ve.dm.ModelRegistry(),
		element = document.createElement( 'a' );

	assert.strictEqual( registry.matchElement( element ), null, 'matchElement() returns null if registry empty' );

	registry.register( ve.dm.example.StubNothingSetAnnotation );
	registry.register( ve.dm.example.StubSingleTagAnnotation );
	registry.register( ve.dm.example.StubSingleTypeWithAnyAllowedAnnotation );
	registry.register( ve.dm.example.StubSingleTypeWithAllowedAnnotation );
	registry.register( ve.dm.example.StubSingleTypeAnnotation );
	registry.register( ve.dm.example.StubSingleTagAndTypeAnnotation );
	registry.register( ve.dm.example.StubFuncAnnotation );
	registry.register( ve.dm.example.StubSingleTagAndFuncExtraAnnotation );
	registry.register( ve.dm.example.StubSingleTagAndFuncAnnotation );
	registry.register( ve.dm.example.StubSingleTypeAndFuncAnnotation );
	registry.register( ve.dm.example.StubSingleTagAndTypeAndFuncAnnotation );
	registry.register( ve.dm.example.StubBarNode );
	registry.register( ve.dm.example.StubAbbrNode );
	registry.register( ve.dm.example.StubRegExpNode );

	element = document.createElement( 'b' );
	assert.strictEqual( registry.matchElement( element ), 'stubnothingset', 'nothingset matches anything' );
	element.setAttribute( 'rel', 'ext:foo' );
	assert.strictEqual( registry.matchElement( element ), 'stubsingletype', 'type-only match' );
	element.setAttribute( 'rel', ' ext:foo ' );
	assert.strictEqual( registry.matchElement( element ), 'stubsingletype', 'type-only match (extra spaces)' );
	element.setAttribute( 'rel', 'ext:foo bar' );
	assert.strictEqual( registry.matchElement( element ), 'stubsingletypewithallowed', 'type-only match with extra allowed type' );
	element.setAttribute( 'rel', 'ext:foo  bar' );
	assert.strictEqual( registry.matchElement( element ), 'stubsingletypewithallowed', 'type-only match with extra allowed type (extra spaces)' );
	element.setAttribute( 'rel', 'ext:foo bar baz quux whee' );
	assert.strictEqual( registry.matchElement( element ), 'stubsingletypewithanyallowed', 'type-only match with many extra types' );
	element = document.createElement( 'a' );
	assert.strictEqual( registry.matchElement( element ), 'stubsingletag', 'tag-only match' );
	element.setAttribute( 'rel', 'ext:foo' );
	assert.strictEqual( registry.matchElement( element ), 'stubsingletagandtype', 'tag and type match' );
	element.setAttribute( 'pickme', 'true' );
	assert.strictEqual( registry.matchElement( element ), 'stubsingletagandtypeandfunc', 'tag, type and func match' );
	element.removeAttribute( 'rel' );
	assert.strictEqual( registry.matchElement( element ), 'stubsingletagandfunc', 'tag and func match' );
	element.setAttribute( 'rel', 'ext:bar' );
	assert.strictEqual( registry.matchElement( element ), 'stubsingletagandfuncextra', 'tag and func match (with allowed extra type)' );
	element = document.createElement( 'b' );
	element.setAttribute( 'pickme', 'true' );
	assert.strictEqual( registry.matchElement( element ), 'stubfunc', 'func-only match' );
	element.setAttribute( 'rel', 'ext:foo' );
	assert.strictEqual( registry.matchElement( element ), 'stubsingletypeandfunc', 'type and func match' );
	element = document.createElement( 'abbr' );
	element.setAttribute( 'rel', 'ext:baz' );
	assert.strictEqual( registry.matchElement( element ), 'stub-regexp', 'RegExp type match' );
	element.setAttribute( 'rel', 'ext:abbr' );
	assert.strictEqual( registry.matchElement( element ), 'stub-abbr', 'String match overrides RegExp match' );

	assert.strictEqual(
		registry.registrationOrder[ ve.dm.example.StubNothingSetAnnotation.static.name ],
		0,
		'Model given a registration order'
	);
	registry.register( ve.dm.example.StubNothingSetAnnotation );
	assert.strictEqual(
		registry.registrationOrder[ ve.dm.example.StubNothingSetAnnotation.static.name ],
		0,
		'Double registration is a no-op (doesn\'t affect registration order)'
	);

	registry.unregister( ve.dm.example.StubAbbrNode );
	element.removeAttribute( 'typeof' );
	element.setAttribute( 'rel', 'ext:abbr' );
	assert.strictEqual( registry.matchElement( element ), 'stub-regexp', 'RegExp type match after string match is unregistered' );

	assert.deepEqual(
		registry.modelsByTag[ 1 ].a,
		[ 'stubsingletagandtypeandfunc', 'stubsingletagandfunc', 'stubsingletagandfuncextra' ],
		'tag and func creates entries in modelsByTag[ 1 ]'
	);
	assert.deepEqual(
		registry.modelsByTypeAndTag[ 1 ][ 'ext:foo' ].a,
		[ 'stubsingletagandtypeandfunc' ],
		'tag and func creates entries in modelsByTypeAndTag[ 1 ]'
	);
	registry.unregister( ve.dm.example.StubSingleTagAndFuncAnnotation );
	registry.unregister( ve.dm.example.StubSingleTagAndFuncExtraAnnotation );
	registry.unregister( ve.dm.example.StubSingleTagAndTypeAndFuncAnnotation );
	assert.deepEqual(
		registry.modelsByTag[ 1 ][ ve.dm.example.StubSingleTagAndFuncAnnotation.static.matchTagNames[ 0 ] ],
		[],
		'unregister removes entries in modelsByTag[ 1 ]'
	);
	assert.deepEqual(
		registry.modelsByTypeAndTag[ 1 ][ 'ext:foo' ].a,
		[],
		'unregister removes entries in modelsByTypeAndTag[ 1 ]'
	);

} );

QUnit.test( 'isAnnotation', function ( assert ) {
	var i, len, node,
		allAnnotationTags = [ 'a', 'abbr', 'b', 'big', 'code', 'dfn', 'font', 'i', 'kbd', 'mark', 'q', 's', 'samp', 'small', 'span', 'sub', 'sup', 'time', 'u', 'var' ],
		nonAnnotationTags = [ 'h1', 'p', 'ul', 'li', 'table', 'tr', 'td' ];

	for ( i = 0, len = allAnnotationTags.length; i < len; i++ ) {
		node = document.createElement( allAnnotationTags[ i ] );
		assert.strictEqual(
			ve.dm.modelRegistry.isAnnotation( node ),
			true,
			allAnnotationTags[ i ] + ' annotation'
		);
	}

	for ( i = 0, len = nonAnnotationTags.length; i < len; i++ ) {
		node = document.createElement( nonAnnotationTags[ i ] );
		assert.strictEqual(
			ve.dm.modelRegistry.isAnnotation( node ),
			false,
			allAnnotationTags[ i ] + ' non-annotation'
		);
	}

	node = document.createElement( 'span' );
	node.setAttribute( 'rel', 've:Alien' );
	assert.strictEqual( ve.dm.modelRegistry.isAnnotation( node ), false, 'alien span' );
	node.removeAttribute( 'rel' );
	assert.strictEqual( ve.dm.modelRegistry.isAnnotation( node ), true, 'non-alien span' );
} );
