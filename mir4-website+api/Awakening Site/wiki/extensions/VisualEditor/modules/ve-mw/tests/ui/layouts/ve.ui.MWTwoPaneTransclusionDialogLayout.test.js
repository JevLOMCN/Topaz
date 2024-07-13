QUnit.module( 've.ui.MWTwoPaneTransclusionDialogLayout', ve.test.utils.newMwEnvironment() );

const createLayout = function () {
    return new ve.ui.MWTwoPaneTransclusionDialogLayout( { continuous: true } );
};

const createTemplate = function () {
    const transclusion = new ve.dm.MWTransclusionModel();
    return new ve.dm.MWTemplateModel( transclusion, {} );
};

const createParameterPage = function ( name, template ) {
    const parameter = new ve.dm.MWParameterModel( template, name );
    return new ve.ui.MWParameterPage( parameter );
};

QUnit.test( 'can add and remove multiple pages', ( assert ) => {
    const layout = createLayout(),
        template = createTemplate(),
        parameterPageA = createParameterPage( 'Parameter A', template ),
        parameterPageB = createParameterPage( 'Parameter B', template ),
        parameterPageC = createParameterPage( 'Parameter C', template );

    layout.addPages( [ parameterPageA, parameterPageB, parameterPageC ] );
    assert.strictEqual( layout.getPage( parameterPageA.getName() ), parameterPageA );
    assert.strictEqual( layout.getPage( parameterPageB.getName() ), parameterPageB );
    assert.strictEqual( layout.getPage( parameterPageC.getName() ), parameterPageC );

    layout.removePages( [ parameterPageA.getName(), parameterPageB.getName() ] );
    assert.strictEqual( layout.getPage( parameterPageA.getName() ), undefined );
    assert.strictEqual( layout.getPage( parameterPageB.getName() ), undefined );
    assert.strictEqual( layout.getPage( parameterPageC.getName() ), parameterPageC );
} );

QUnit.test( 'can add a page at a specific index', ( assert ) => {
    const layout = createLayout(),
        template = createTemplate(),
        parameterPageA = createParameterPage( 'Parameter A', template ),
        parameterPageB = createParameterPage( 'Parameter B', template ),
        parameterPageC = createParameterPage( 'Parameter C', template );

    layout.addPages( [ parameterPageA, parameterPageB ] );
    layout.addPages( [ parameterPageC ], 1 );

    assert.strictEqual( layout.stackLayout.getItemIndex( parameterPageA ), 0 );
    assert.strictEqual( layout.stackLayout.getItemIndex( parameterPageC ), 1 );
    assert.strictEqual( layout.stackLayout.getItemIndex( parameterPageB ), 2 );
} );
