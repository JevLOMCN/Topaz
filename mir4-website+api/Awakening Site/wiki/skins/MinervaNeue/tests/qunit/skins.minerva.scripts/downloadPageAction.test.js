( function () {
	const VALID_UA = 'Mozilla/5.0 (Linux; Android 5.1.1; Nexus 6 Build/LYZ28E) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Mobile Safari/537.36';
	const VALID_SUPPORTED_NAMESPACES = [ 0 ];
	const spinner = () => ( {
		$el: $( '<span>' )
	} );
	const Deferred = $.Deferred;
	const windowChrome = { chrome: true };
	const windowNotChrome = {};
	const downloadAction = require( '../../../resources/skins.minerva.scripts/downloadPageAction.js' );
	const onClick = downloadAction.test.onClick;
	const isAvailable = downloadAction.test.isAvailable;

	class Page {
		constructor( options ) {
			this.isMissing = options.isMissing;
			this._isMainPage = !!options.isMainPage;
		}

		isMainPage() {
			return this._isMainPage;
		}

		getNamespaceId() {
			return 0;
		}
	}
	QUnit.module( 'Minerva DownloadIcon', {
		beforeEach: function () {
			this.getOnClickHandler = function ( onLoadAllImages ) {
				const portletLink = document.createElement( 'li' );

				return function () {
					onClick( portletLink, spinner(), onLoadAllImages );
				};
			};
		}
	} );

	QUnit.test( '#getOnClickHandler (print after image download)', function ( assert ) {
		const
			d = Deferred(),
			handler = this.getOnClickHandler( () => d.resolve() ),
			spy = this.sandbox.stub( window, 'print' );

		handler();
		d.then( function () {
			assert.strictEqual( spy.callCount, 1, 'Print occurred.' );
		} );

		return d;
	} );

	QUnit.test( '#getOnClickHandler (print via timeout)', function ( assert ) {
		const
			d = Deferred(),
			handler = this.getOnClickHandler( () => d.resolve() ),
			spy = this.sandbox.stub( window, 'print' );

		window.setTimeout( function () {
			d.resolve();
		}, 3400 );

		handler();
		d.then( function () {
			assert.strictEqual( spy.callCount, 1,
				'Print was called once despite loadImages resolving after MAX_PRINT_TIMEOUT' );
		} );

		return d;
	} );

	QUnit.test( '#getOnClickHandler (multiple clicks)', function ( assert ) {
		const d = Deferred(),
			handler = this.getOnClickHandler( () => d.resolve() ),
			spy = this.sandbox.stub( window, 'print' );

		window.setTimeout( function () {
			d.resolve();
		}, 3400 );

		handler();
		handler();
		d.then( function () {
			assert.strictEqual( spy.callCount, 1,
				'Print was called once despite multiple clicks' );
		} );

		return d;
	} );

	QUnit.module( 'isAvailable()', {
		beforeEach: function () {
			const page = new Page( {
				id: 0,
				title: 'Test',
				isMissing: false,
				isMainPage: false
			} );
			this.page = page;
			this.isAvailable = function ( ua ) {
				return isAvailable( windowChrome, page, ua,
					VALID_SUPPORTED_NAMESPACES );
			};
			this.notChromeIsAvailable = function ( ua ) {
				return isAvailable( windowNotChrome, page, ua,
					VALID_SUPPORTED_NAMESPACES );
			};
		}
	} );

	QUnit.test( 'isAvailable() handles properly correct namespace', function ( assert ) {
		assert.true( this.isAvailable( VALID_UA ) );
	} );

	QUnit.test( 'isAvailable() handles properly not supported namespace', function ( assert ) {
		assert.false( isAvailable( windowChrome, this.page, VALID_UA, [ 9999 ] ) );
	} );

	QUnit.test( 'isAvailable() handles missing pages', function ( assert ) {
		const page = new Page( {
			id: 0,
			title: 'Missing',
			isMissing: true
		} );
		assert.false( isAvailable( windowChrome, page, VALID_UA, VALID_SUPPORTED_NAMESPACES ) );
	} );

	QUnit.test( 'isAvailable() handles properly main page', function ( assert ) {
		const page = new Page( {
			id: 0,
			title: 'Test',
			isMissing: false,
			isMainPage: true
		} );
		assert.false( isAvailable( windowChrome, page, VALID_UA, VALID_SUPPORTED_NAMESPACES ) );
	} );

	QUnit.test( 'isAvailable() returns false for iOS', function ( assert ) {
		assert.false( this.isAvailable( 'ipad' ) );
	} );

	QUnit.test( 'isAvailable() uses window.chrome to filter certain chrome-like browsers', function ( assert ) {
		// Dolphin
		assert.false( this.notChromeIsAvailable( ' Mozilla/5.0 (Linux; Android 7.0; SM-G950U1 Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/61.0.3163.98 Mobile Safari/537.36' ) );
		// Opera
		assert.false( this.notChromeIsAvailable( 'Mozilla/5.0 (Linux; Android 7.0; SM-G950U1 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.116 Mobile Safari/537.36 OPR/44.1.2246.123029' ) );
		// Maxthon
		assert.false( this.notChromeIsAvailable( 'Mozilla/5.0 (Linux; Android 7.0; SM-G950U1 Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/63.0.3239.111 Mobile Safari/537.36 MxBrowser/4.5.10.1300' ) );
	} );

	QUnit.test( 'isAvailable() handles properly browsers', function ( assert ) {
		// IPhone 6 Safari
		assert.false( this.isAvailable( 'Mozilla/5.0 (iPhone; CPU iPhone OS 9_0_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13A405 Safari/601.1' ) );
		// Nokia Lumia 930 Windows Phone 8.1
		assert.false( this.isAvailable( 'Mozilla/5.0 (Mobile; Windows Phone 8.1; Android 4.0; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; Microsoft; Virtual) like iPhone OS 7_0_3 Mac OS X AppleWebKit/537 (KHTML, like Gecko) Mobile Safari/537' ) );
		// Firefox @ Ubuntu
		assert.false( this.isAvailable( 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:56.0) Gecko/20100101 Firefox/56.0' ) );
	} );

	QUnit.test( 'isAvailable() handles properly non-chrome browsers', function ( assert ) {
		// IPhone 6 Safari
		assert.false( this.notChromeIsAvailable( 'Mozilla/5.0 (iPhone; CPU iPhone OS 9_0_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13A405 Safari/601.1' ) );
		// Nokia Lumia 930 Windows Phone 8.1
		assert.false( this.notChromeIsAvailable( 'Mozilla/5.0 (Mobile; Windows Phone 8.1; Android 4.0; ARM; Trident/7.0; Touch; rv:11.0; IEMobile/11.0; Microsoft; Virtual) like iPhone OS 7_0_3 Mac OS X AppleWebKit/537 (KHTML, like Gecko) Mobile Safari/537' ) );
		// Firefox @ Ubuntu
		assert.false( this.notChromeIsAvailable( 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:56.0) Gecko/20100101 Firefox/56.0' ) );
	} );

	QUnit.test( 'isAvailable() handles properly old devices', function ( assert ) {
		// Samsung Galaxy S5, Android 4.4, Chrome 28
		assert.false( this.isAvailable( 'Mozilla/5.0 (Linux; Android 4.4.2; en-us; SAMSUNG SM-G900F Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/1.6 Chrome/28.0.1500.94 Mobile Safari/537.36' ) );
		// Samsung Galaxyu S1, Android 4.2.2 Cyanogenmod + built in Samsung Browser
		assert.false( this.isAvailable( 'Mozilla/5.0 (Linux; U; Android 4.2.2; en-ca; GT-I9000 Build/JDQ39E) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30 CyanogenMod/10.1.0/galaxysmtd' ) );
		// Samsung Galaxy S3
		assert.false( this.isAvailable( 'Mozilla/5.0 (Linux; Android 4.3; GT-I9300 Build/JSS15J) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.84 Mobile Safari/537.36' ) );
	} );

	QUnit.test( 'isAvailable() handles properly supported browsers', function ( assert ) {
		// Samsung Galaxy S7, Android 6, Chrome 44
		assert.true( this.isAvailable( 'Mozilla/5.0 (Linux; Android 6.0.1; SAMSUNG SM-G930F Build/MMB29K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/4.0 Chrome/44.0.2403.133 Mobile Safari/537.36' ) );
		// Samsung Galaxy A5, Android 7, Samsung Browser 5.2
		assert.true( this.isAvailable( 'Mozilla/5.0 (Linux; Android 7.0; SAMSUNG SM-A510F Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/5.2 Chrome/51.0.2704.106 Mobile Safari/537.36' ) );
		// Galaxy J2, Android 5, Chrome 65
		assert.true( this.isAvailable( 'Mozilla/5.0 (Linux; Android 5.1.1; SM-J200G Build/LMY47X) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3320.0 Mobile Safari/537.36' ) );
		// Desktop, Chrome 63
		assert.true( this.isAvailable( 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36' ) );
		// Desktop, Ubuntu, Chromium 61
		assert.true( this.isAvailable( 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/61.0.3163.100 Chrome/61.0.3163.100 Safari/537.36' ) );
		// Galaxy S8, Android 8, Samsung Browser 6.2
		assert.true( this.isAvailable( 'Mozilla/5.0 (Linux; Android 7.0; SAMSUNG SM-G950U1 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/6.2 Chrome/56.0.2924.87 Mobile Safari/537.36' ) );
	} );

}() );
