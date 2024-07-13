function createStubUserSettings( expectEnabled ) {
	return {
		isPreviewTypeEnabled() {
			return expectEnabled !== false;
		}
	};
}

function createStubUser( isAnon, options ) {
	return {
		isNamed() {
			return !isAnon;
		},
		isAnon() {
			return isAnon;
		},
		options
	};
}

const options = { get: () => '1' };

( mw.loader.getModuleNames().indexOf( 'ext.popups.main' ) !== -1 ?
	QUnit.module :
	QUnit.module.skip )( 'ext.cite.referencePreviews#isReferencePreviewsEnabled' );

QUnit.test( 'all relevant combinations of flags', ( assert ) => {
	[
		{
			testCase: 'enabled for an anonymous user',
			wgPopupsReferencePreviews: true,
			wgPopupsConflictsWithRefTooltipsGadget: false,
			isMobile: false,
			isAnon: true,
			enabledByAnon: true,
			enabledByRegistered: false,
			expected: true
		},
		{
			testCase: 'turned off via the feature flag (anonymous user)',
			wgPopupsReferencePreviews: false,
			wgPopupsConflictsWithRefTooltipsGadget: false,
			isMobile: false,
			isAnon: true,
			enabledByAnon: true,
			enabledByRegistered: true,
			expected: null
		},
		{
			testCase: 'not available because of a conflicting gadget (anonymous user)',
			wgPopupsReferencePreviews: true,
			wgPopupsConflictsWithRefTooltipsGadget: true,
			isMobile: false,
			isAnon: true,
			enabledByAnon: true,
			enabledByRegistered: true,
			expected: null
		},
		{
			testCase: 'not available in the mobile skin (anonymous user)',
			wgPopupsReferencePreviews: true,
			wgPopupsConflictsWithRefTooltipsGadget: false,
			isMobile: true,
			isAnon: true,
			enabledByAnon: true,
			enabledByRegistered: true,
			expected: null
		},
		{
			testCase: 'manually disabled by the anonymous user',
			wgPopupsReferencePreviews: true,
			wgPopupsConflictsWithRefTooltipsGadget: false,
			isMobile: false,
			isAnon: true,
			enabledByAnon: false,
			enabledByRegistered: true,
			expected: false
		},
		{
			testCase: 'enabled for a registered user',
			wgPopupsReferencePreviews: true,
			wgPopupsConflictsWithRefTooltipsGadget: false,
			isMobile: false,
			isAnon: false,
			enabledByAnon: false,
			enabledByRegistered: true,
			expected: true
		},
		{
			testCase: 'turned off via the feature flag (registered user)',
			wgPopupsReferencePreviews: false,
			wgPopupsConflictsWithRefTooltipsGadget: false,
			isMobile: false,
			isAnon: false,
			enabledByAnon: true,
			enabledByRegistered: true,
			expected: null
		},
		{
			testCase: 'not available because of a conflicting gadget (registered user)',
			wgPopupsReferencePreviews: true,
			wgPopupsConflictsWithRefTooltipsGadget: true,
			isMobile: false,
			isAnon: false,
			enabledByAnon: true,
			enabledByRegistered: true,
			expected: null
		},
		{
			testCase: 'not available in the mobile skin (registered user)',
			wgPopupsReferencePreviews: true,
			wgPopupsConflictsWithRefTooltipsGadget: false,
			isMobile: true,
			isAnon: false,
			enabledByAnon: true,
			enabledByRegistered: true,
			expected: null
		},
		{
			// TODO: This combination will make much more sense when the server-side
			// wgPopupsReferencePreviews flag doesn't include the user's setting any more
			testCase: 'manually disabled by the registered user',
			wgPopupsReferencePreviews: true,
			wgPopupsConflictsWithRefTooltipsGadget: false,
			isMobile: false,
			isAnon: false,
			enabledByAnon: true,
			enabledByRegistered: false,
			expected: null
		}
	].forEach( ( data ) => {
		const user = {
				isNamed: () => !data.isAnon && !data.isIPMasked,
				isAnon: () => data.isAnon,
				options: {
					get: () => {}
				}
			},
			isPreviewTypeEnabled = () => {
				if ( !data.isAnon ) {
					assert.true( false, 'not expected to be called' );
				}
				return data.enabledByAnon;
			},
			config = {
				get: ( key ) => key === 'skin' && data.isMobile ? 'minerva' : data[ key ]
			};

		if ( data.isAnon ) {
			user.options.get = () => assert.true( false, 'not expected to be called 2' );
		} else {
			user.options.get = () => data.enabledByRegistered ? '1' : '0';
		}

		assert.strictEqual(
			require( 'ext.cite.referencePreviews' ).private.isReferencePreviewsEnabled( user, isPreviewTypeEnabled, config ),
			data.expected,
			data.testCase
		);
	} );
} );

QUnit.test( 'it should display reference previews when conditions are fulfilled', ( assert ) => {
	const user = createStubUser( false, options ),
		userSettings = createStubUserSettings( false ),
		config = new Map();

	config.set( 'wgPopupsReferencePreviews', true );
	config.set( 'wgPopupsConflictsWithRefTooltipsGadget', false );

	assert.true(
		require( 'ext.cite.referencePreviews' ).private.isReferencePreviewsEnabled( user, userSettings, config ),
		'If the user is logged in and the user is in the on group, then it\'s enabled.'
	);
} );

QUnit.test( 'it should handle the conflict with the Reference Tooltips Gadget', ( assert ) => {
	const user = createStubUser( false ),
		userSettings = createStubUserSettings( false ),
		config = new Map();

	config.set( 'wgPopupsReferencePreviews', true );
	config.set( 'wgPopupsConflictsWithRefTooltipsGadget', true );

	assert.strictEqual(
		require( 'ext.cite.referencePreviews' ).private.isReferencePreviewsEnabled( user, userSettings, config ),
		null,
		'Reference Previews is disabled.'
	);
} );

QUnit.test( 'it should not be enabled when the global is disabling it', ( assert ) => {
	const user = createStubUser( false ),
		userSettings = createStubUserSettings( false ),
		config = new Map();

	config.set( 'wgPopupsReferencePreviews', false );
	config.set( 'wgPopupsConflictsWithRefTooltipsGadget', false );

	assert.strictEqual(
		require( 'ext.cite.referencePreviews' ).private.isReferencePreviewsEnabled( user, userSettings, config ),
		null,
		'Reference Previews is disabled.'
	);
} );

QUnit.test( 'it should not be enabled when minerva skin used', ( assert ) => {
	const user = createStubUser( false ),
		userSettings = createStubUserSettings( false ),
		config = new Map();

	config.set( 'wgPopupsReferencePreviews', true );
	config.set( 'wgPopupsConflictsWithRefTooltipsGadget', false );
	config.set( 'skin', 'minerva' );

	assert.strictEqual(
		require( 'ext.cite.referencePreviews' ).private.isReferencePreviewsEnabled( user, userSettings, config ),
		null,
		'Reference Previews is disabled.'
	);
} );
