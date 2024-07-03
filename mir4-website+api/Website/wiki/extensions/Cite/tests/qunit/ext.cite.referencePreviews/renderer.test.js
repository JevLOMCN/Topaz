let createReferencePreview;
const previewTypes = { TYPE_REFERENCE: 'reference' };

// TODO: Fix this test.  Currently failing on `document.getElementById`
QUnit.module.skip( 'ext.cite.referencePreviews#renderer', {
	before() {
		createReferencePreview = require( 'ext.cite.referencePreviews' ).private.createReferencePreview;
	},
	beforeEach() {
		mw.msg = ( key ) => `<${ key }>`;
		mw.message = ( key ) => {
			return { exists: () => !key.endsWith( 'generic' ), text: () => `<${ key }>` };
		};

		mw.html = {
			escape: ( str ) => str && str.replace( /'/g, '&apos;' ).replace( /</g, '&lt;' )
		};

		mw.track = () => {};

		global.navigator = {
			sendBeacon() {}
		};

		// Some tests below stub this function. Keep a copy so it can be restored.
		this.getElementById = document.getElementById;
	},
	afterEach() {
		// Restore getElementsById to its original state.
		document.getElementById = this.getElementById;
		mw.msg = null;
		mw.message = null;
		mw.html = null;
	}
} );

QUnit.test( 'createReferencePreview(model)', ( assert ) => {
	const model = {
			url: '#custom_id',
			extract: 'Custom <i>extract</i> with an <a href="/wiki/Internal">internal</a> and an <a href="//wikipedia.de" class="external">external</a> link',
			type: previewTypes.TYPE_REFERENCE,
			referenceType: 'web'
		},
		preview = createReferencePreview( model );

	assert.strictEqual( preview.hasThumbnail, false );
	assert.strictEqual( preview.isTall, false );

	assert.strictEqual(
		$( preview.el ).find( '.mwe-popups-title' ).text().trim(),
		'<popups-refpreview-web>'
	);
	assert.strictEqual(
		$( preview.el ).find( '.mw-parser-output' ).text().trim(),
		'Custom extract with an internal and an external link'
	);
	assert.strictEqual(
		$( preview.el ).find( 'a[target="_blank"]' ).length,
		1,
		'only external links open in new tabs'
	);
} );

QUnit.test( 'createReferencePreview default title', ( assert ) => {
	const model = {
			url: '',
			extract: '',
			type: previewTypes.TYPE_REFERENCE
		},
		preview = createReferencePreview( model );

	assert.strictEqual(
		$( preview.el ).find( '.mwe-popups-title' ).text().trim(),
		'<popups-refpreview-reference>'
	);
} );

QUnit.test( 'createReferencePreview updates fade-out effect on scroll', ( assert ) => {
	const model = {
			url: '',
			extract: '',
			type: previewTypes.TYPE_REFERENCE
		},
		preview = createReferencePreview( model ),
		$extract = $( preview.el ).find( '.mwe-popups-extract' );

	$extract.children()[ 0 ].dispatchEvent( new Event( 'scroll' ) );

	assert.false( $extract.children()[ 0 ].isScrolling );
	assert.false( $extract.hasClass( 'mwe-popups-fade-out' ) );
} );

QUnit.test( 'createReferencePreview collapsible/sortable handling', ( assert ) => {
	const model = {
			url: '',
			extract: '<table class="mw-collapsible"></table>' +
				'<table class="sortable"><th class="headerSort" tabindex="1" title="Click here"></th></table>',
			type: previewTypes.TYPE_REFERENCE
		},
		preview = createReferencePreview( model );

	assert.strictEqual( $( preview.el ).find( '.mw-collapsible, .sortable, .headerSort' ).length, 0 );
	assert.strictEqual( $( preview.el ).find( 'th' ).attr( 'tabindex' ), undefined );
	assert.strictEqual( $( preview.el ).find( 'th' ).attr( 'title' ), undefined );
	assert.strictEqual(
		$( preview.el ).find( '.mwe-collapsible-placeholder' ).text(),
		'<popups-refpreview-collapsible-placeholder>'
	);
} );
