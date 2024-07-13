/*!
 * VisualEditor test utilities.
 *
 * @copyright See AUTHORS.txt
 */

( function () {
	/**
	 * @class
	 * @singleton
	 * @ignore
	 */
	ve.test = { utils: {} };

	// Create a dummy platform and target so ve.init.platform/target are available
	function DummyPlatform() {
		DummyPlatform.super.apply( this, arguments );
		// Set this to true to test storage methods failing and returning false
		this.storageDisabled = false;
	}
	OO.inheritClass( DummyPlatform, ve.init.Platform );
	DummyPlatform.prototype.getUserLanguages = function () {
		return [ 'en' ];
	};
	DummyPlatform.prototype.getMessage = function () {
		return Array.prototype.join.call( arguments, ',' );
	};
	DummyPlatform.prototype.getHtmlMessage = function () {
		var $wrapper = $( '<div>' );
		Array.prototype.forEach.call( arguments, function ( arg, i, args ) {
			$wrapper.append( arg );
			if ( i < args.length - 1 ) {
				$wrapper.append( ',' );
			}
		} );
		// Merge text nodes
		$wrapper[ 0 ].normalize();
		return $wrapper.contents().toArray();
	};
	DummyPlatform.prototype.getLanguageName = function ( lang ) {
		return 'langname-' + lang;
	};
	DummyPlatform.prototype.getLanguageDirection = function () {
		return 'ltr';
	};
	DummyPlatform.prototype.getExternalLinkUrlProtocolsRegExp = function () {
		return /^https?:\/\//i;
	};
	DummyPlatform.prototype.getUnanchoredExternalLinkUrlProtocolsRegExp = function () {
		return /https?:\/\//i;
	};
	DummyPlatform.prototype.getUserConfig = function () {
		return undefined;
	};
	DummyPlatform.prototype.setUserConfig = function () {};
	DummyPlatform.prototype.createSafeStorage = function ( store ) {
		var platform = this;
		var EXPIRY_PREFIX = '_EXPIRY_';

		var MockSafeStorage = function ( s ) {
			this.store = s;
		};
		OO.initClass( MockSafeStorage );
		MockSafeStorage.prototype.get = function ( key ) {
			if ( platform.storageDisabled ) {
				return false;
			}
			return this.store.getItem( key );
		};
		MockSafeStorage.prototype.set = function ( key, value, expiry ) {
			if ( platform.storageDisabled || value === '__FAIL__' ) {
				return false;
			}
			this.store.setItem( key, value );
			this.setExpires( key, expiry );
			return true;
		};
		MockSafeStorage.prototype.remove = function ( key ) {
			if ( platform.storageDisabled ) {
				return false;
			}
			this.store.removeItem( key );
			this.setExpires( key );
			return true;
		};
		MockSafeStorage.prototype.getObject = function ( key ) {
			var json = this.get( key );

			if ( json === false ) {
				return false;
			}

			try {
				return JSON.parse( json );
			} catch ( e ) {}

			return null;
		};
		MockSafeStorage.prototype.setObject = function ( key, value ) {
			try {
				var json = JSON.stringify( value );
				return this.set( key, json );
			} catch ( e ) {}
			return false;
		};
		MockSafeStorage.prototype.setExpires = function ( key, expiry ) {
			if ( platform.storageDisabled ) {
				return false;
			}
			if ( expiry ) {
				this.store.setItem(
					EXPIRY_PREFIX + key,
					Math.floor( Date.now() / 1000 ) + expiry
				);
			} else {
				this.store.removeItem( EXPIRY_PREFIX + key );
			}
		};

		return new MockSafeStorage( store );
	};
	DummyPlatform.prototype.createLocalStorage = DummyPlatform.prototype.createSessionStorage = function ( store ) {
		store = store || {};

		var MockSystemStorage = function () {};
		OO.initClass( MockSystemStorage );
		MockSystemStorage.prototype.getItem = function ( key ) {
			return Object.prototype.hasOwnProperty.call( store, key ) ?
				store[ key ] :
				null;
		};
		MockSystemStorage.prototype.setItem = function ( key, value ) {
			store[ key ] = String( value );
		};
		MockSystemStorage.prototype.removeItem = function ( key ) {
			delete store[ key ];
		};
		MockSystemStorage.prototype.clear = function () {
			for ( var key in store ) {
				delete store[ key ];
			}
		};
		MockSystemStorage.prototype.key = function ( index ) {
			var keys = Object.keys( store );
			return index < keys.length ? keys[ index ] : null;
		};
		if ( !Object.prototype.hasOwnProperty.call( store, 'length' ) ) {
			Object.defineProperty( store, 'length', {
				get: function () {
					return Object.keys( store ).length;
				}
			} );
		}

		var storage = this.createSafeStorage( new MockSystemStorage( store ) );

		return ve.init.createConflictableStorage( storage );
	};

	ve.test.utils.DummyPlatform = DummyPlatform;

	function DummyTarget() {
		DummyTarget.super.apply( this, arguments );
	}
	OO.inheritClass( DummyTarget, ve.init.Target );
	DummyTarget.prototype.addSurface = function () {
		// Parent method
		var surface = DummyTarget.super.prototype.addSurface.apply( this, arguments );
		this.$element.append( surface.$element );
		if ( !this.getSurface() ) {
			this.setSurface( surface );
		}
		surface.initialize();
		return surface;
	};
	DummyTarget.prototype.setupToolbar = function () {};

	ve.test.utils.DummyTarget = DummyTarget;

	/* eslint-disable no-new */
	new ve.test.utils.DummyPlatform();
	new ve.test.utils.DummyTarget(); // Target gets appended to qunit-fixture in ve.qunit.local.js
	/* eslint-enable no-new */

	// Disable scroll animatinos
	ve.scrollIntoView = function () {
		return ve.createDeferred().resolve().promise();
	};

	var voidGroup = '(' + ve.elementTypes.void.join( '|' ) + ')';
	// eslint-disable-next-line security/detect-non-literal-regexp
	var voidRegexp = new RegExp( '(<' + voidGroup + '[^>]*?(/?))>', 'g' );
	var originalCreateDocumentFromHtml = ve.createDocumentFromHtml;
	/**
	 * Override ve.createDocumentFromHtml to validate HTML structure using an XML parser
	 *
	 * Some automatic fixes are applied to HTML to make it parseable,
	 * but some errors will still be thrown, for example:
	 * - Unescaped < or > in attributes
	 *
	 * If your test HTML is triggering a false positive warning, you can
	 * disable this check with the ignoreXmlWarnings param.
	 *
	 * @param {string} html
	 * @param {boolean} ignoreXmlWarnings Skip validation
	 * @return {HTMLDocument}
	 */
	ve.createDocumentFromHtml = function ( html, ignoreXmlWarnings ) {
		if ( html && !ignoreXmlWarnings ) {
			var xml = '<xml>' +
				html
					// Close open void tags
					.replace( voidRegexp, function () {
						return arguments[ 3 ] ?
							// self-closing - do nothing
							arguments[ 0 ] :
							// add close tag
							arguments[ 0 ] + '</' + arguments[ 2 ] + '>';
					} )
					// Remove entities, named ones not recognised
					.replace( /&[^;]+;/g, '' )
					// Remove doctype
					.replace( /<!doctype html>/i, '' ) +
			'</xml>';
			var xmlDoc;
			// Firefox additionally throws an error
			try {
				xmlDoc = ( new DOMParser() ).parseFromString( xml, 'application/xml' );
			} catch ( e ) {
			}
			if ( xmlDoc ) {
				var parserError = xmlDoc.querySelector( 'parsererror' );
				if ( parserError ) {
					// eslint-disable-next-line no-console
					console.warn( parserError.innerText, '\n', html, '\n', xml );
				}
			}
		}

		return originalCreateDocumentFromHtml( html );
	};

	function getSerializableData( model ) {
		return model.getFullData( undefined, 'roundTrip' );
	}

	/**
	 * Make a HTML `<base>` tag
	 *
	 * @param {string} base Base URL
	 * @return {string}
	 */
	ve.test.utils.makeBaseTag = function ( base ) {
		if ( /[<>&'"]/.test( base ) ) {
			throw new Error( 'Weird base' );
		}
		return '<base href="' + base + '">';
	};

	/**
	 * Add a `<base>` tag to `<head>` using HTML string splicing
	 *
	 * @param {string} docHtml Document HTML
	 * @param {string} base Base URL
	 * @return {string} Document HTML
	 */
	ve.test.utils.addBaseTag = function ( docHtml, base ) {
		if ( !ve.matchTag( docHtml, 'body' ) ) {
			docHtml = '<body>' + docHtml + '</body>';
		}
		return ve.addHeadTag( docHtml, ve.test.utils.makeBaseTag( base ) );
	};

	ve.test.utils.runIsolateTest = function ( assert, type, range, expected, base, label ) {
		var doc = ve.dm.example.createExampleDocument( 'isolationData', null, base ),
			surface = new ve.dm.Surface( doc ),
			fragment = surface.getLinearFragment( range );

		var data = ve.copy( getSerializableData( doc ) );
		fragment.isolateAndUnwrap( type );
		expected( data );

		assert.deepEqual( getSerializableData( doc ), data, label );
	};

	ve.test.utils.runFormatConverterTest = function ( assert, range, type, attributes, expectedRange, expectedData, msg ) {
		var surface = ve.test.utils.createModelOnlySurfaceFromHtml( ve.dm.example.isolationHtml ),
			formatAction = new ve.ui.FormatAction( surface ),
			data = ve.copy( getSerializableData( surface.getModel().getDocument() ) ),
			originalData = ve.copy( data );

		expectedData( data );

		surface.getModel().setLinearSelection( range );
		formatAction.convert( type, attributes );

		assert.equalLinearData( getSerializableData( surface.getModel().getDocument() ), data, msg + ': data models match' );
		assert.equalRange( surface.getModel().getSelection().getRange(), expectedRange, msg + ': ranges match' );

		surface.getModel().undo();

		assert.equalLinearData( getSerializableData( surface.getModel().getDocument() ), originalData, msg + ' (undo): data models match' );
		assert.equalRange( surface.getModel().getSelection().getRange(), range, msg + ' (undo): ranges match' );
	};

	ve.test.utils.runActionTest = function ( actionName, assert, html, createView, method, args, rangeOrSelection, msg, options ) {
		var surface = createView ?
				ve.test.utils.createViewOnlySurfaceFromHtml( html || ve.dm.example.html ) :
				ve.test.utils.createModelOnlySurfaceFromHtml( html || ve.dm.example.html ),
			action = ve.ui.actionFactory.create( actionName, surface ),
			data = ve.copy( getSerializableData( surface.getModel().getDocument() ) ),
			documentModel = surface.getModel().getDocument(),
			selection = ve.test.utils.selectionFromRangeOrSelection( documentModel, rangeOrSelection ),
			expectedSelection = options.expectedRangeOrSelection && ve.test.utils.selectionFromRangeOrSelection( documentModel, options.expectedRangeOrSelection );

		var originalData;
		if ( options.undo ) {
			originalData = ve.copy( data );
		}

		ve.dm.example.postprocessAnnotations( data, surface.getModel().getDocument().getStore() );

		if ( options.expectedData ) {
			options.expectedData( data, action );
		}

		surface.getModel().setSelection( selection );
		action[ method ].apply( action, args || [] );

		var actualData = getSerializableData( surface.getModel().getDocument() );
		ve.dm.example.postprocessAnnotations( actualData, surface.getModel().getDocument().getStore() );
		assert.equalLinearData( actualData, data, msg + ': data models match' );
		if ( expectedSelection ) {
			assert.equalHash( surface.getModel().getSelection(), expectedSelection, msg + ': selections match' );
		}

		if ( options.undo ) {
			if ( options.expectedOriginalData ) {
				options.expectedOriginalData( originalData, action );
			}

			surface.getModel().undo();

			assert.equalLinearData( getSerializableData( surface.getModel().getDocument() ), originalData, msg + ' (undo): data models match' );
			if ( expectedSelection ) {
				var expectedOriginalRangeOrSelection = options.expectedOriginalRangeOrSelection &&
					ve.test.utils.selectionFromRangeOrSelection( documentModel, options.expectedOriginalRangeOrSelection );
				assert.equalHash( surface.getModel().getSelection(), expectedOriginalRangeOrSelection || selection, msg + ' (undo): selections match' );
			}
		}
	};

	ve.test.utils.runGetModelFromDomTest = function ( assert, caseItem, msg ) {
		if ( caseItem.head !== undefined || caseItem.body !== undefined ) {
			var html = '<head>' + ( caseItem.head || '' ) + '</head><body>' + caseItem.body + '</body>';
			var htmlDoc = ve.createDocumentFromHtml( ve.test.utils.addBaseTag( html, caseItem.base ), caseItem.ignoreXmlWarnings );
			var model = ve.dm.converter.getModelFromDom( htmlDoc, { fromClipboard: !!caseItem.fromClipboard } );
			var actualDataReal = model.getFullData();
			var actualDataMeta = getSerializableData( model );

			// Round-trip here, check round-trip later
			if ( caseItem.modify ) {
				actualDataReal = ve.copy( actualDataReal );
				actualDataMeta = ve.copy( actualDataMeta );
				caseItem.modify( model );
			}
			var actualRtDoc = ve.dm.converter.getDomFromModel( model );

			// Normalize and verify data
			ve.dm.example.postprocessAnnotations( actualDataReal, model.getStore(), caseItem.preserveAnnotationDomElements );
			ve.dm.example.postprocessAnnotations( actualDataMeta, model.getStore(), caseItem.preserveAnnotationDomElements );

			assert.equalLinearData( actualDataReal, ( caseItem.realData || caseItem.data ), msg + ': real data' );
			assert.equalLinearData( actualDataMeta, caseItem.data, msg + ': data with metaItems restored' );

			assert.deepEqual( model.getInnerWhitespace(), caseItem.innerWhitespace || new Array( 2 ), msg + ': inner whitespace' );
			// Check storeItems have been added to store
			if ( caseItem.storeItems ) {
				for ( var hash in caseItem.storeItems ) {
					assert.deepEqualWithDomElements(
						model.getStore().value( hash ) || {},
						caseItem.storeItems[ hash ],
						msg + ': store item ' + hash + ' found'
					);
				}
			}
			// Check round-trip
			var expectedRtDoc = caseItem.normalizedBody ?
				ve.createDocumentFromHtml( ve.test.utils.addBaseTag( caseItem.normalizedBody, caseItem.base ), caseItem.ignoreXmlWarnings ) :
				htmlDoc;
			assert.equalDomElement( actualRtDoc.body, expectedRtDoc.body, msg + ': round-trip' );
		}
	};

	ve.test.utils.getModelFromTestCase = function ( caseItem ) {
		var store = new ve.dm.HashValueStore();

		// Load storeItems into store
		if ( caseItem.storeItems ) {
			for ( var hash in caseItem.storeItems ) {
				store.hashStore[ hash ] = ve.copy( caseItem.storeItems[ hash ] );
			}
		}
		var model = new ve.dm.Document( ve.dm.example.preprocessAnnotations( caseItem.data, store ) );
		ve.fixBase( model.getHtmlDocument(), model.getHtmlDocument(), caseItem.base );
		model.innerWhitespace = caseItem.innerWhitespace ? ve.copy( caseItem.innerWhitespace ) : new Array( 2 );
		if ( caseItem.modify ) {
			caseItem.modify( model );
		}
		return model;
	};

	ve.test.utils.runGetDomFromModelTest = function ( assert, caseItem, msg ) {
		var model = ve.test.utils.getModelFromTestCase( caseItem );
		var originalData = ve.copy( getSerializableData( model ) );
		var fromDataBody = caseItem.fromDataBody || caseItem.normalizedBody || caseItem.body;
		var html = '<body>' + fromDataBody + '</body>';
		var clipboardHtml = '<body>' + ( caseItem.clipboardBody || fromDataBody ) + '</body>';
		var previewHtml = '<body>' + ( caseItem.previewBody || fromDataBody ) + '</body>';
		assert.equalDomElement(
			ve.dm.converter.getDomFromModel( model ),
			ve.createDocumentFromHtml( html, caseItem.ignoreXmlWarnings ),
			msg
		);
		assert.equalDomElement(
			ve.dm.converter.getDomFromModel( model, ve.dm.Converter.static.CLIPBOARD_MODE ),
			ve.createDocumentFromHtml( clipboardHtml, caseItem.ignoreXmlWarnings ),
			msg + ' (clipboard mode)'
		);
		// Make this conditional on previewBody being present until downstream test-suites have been fixed.
		// This should be changed to:
		// if ( caseItem.previewBody !== false ) {
		if ( caseItem.previewBody ) {
			assert.equalDomElement(
				ve.dm.converter.getDomFromModel( model, ve.dm.Converter.static.PREVIEW_MODE ),
				ve.createDocumentFromHtml( previewHtml, caseItem.ignoreXmlWarnings ),
				msg + ' (preview mode)'
			);
		}
		assert.deepEqualWithDomElements( getSerializableData( model ), originalData, msg + ' (data hasn\'t changed)' );
	};

	ve.test.utils.runDiffElementTest = function ( assert, caseItem ) {
		var oldDoc = ve.dm.converter.getModelFromDom( ve.createDocumentFromHtml( caseItem.oldDoc ) ),
			newDoc = ve.dm.converter.getModelFromDom( ve.createDocumentFromHtml( caseItem.newDoc ) );
		// TODO: Differ expects newDoc to be derived from oldDoc and contain all its store data.
		// We may want to remove that assumption from the differ?
		newDoc.getStore().merge( oldDoc.getStore() );
		var visualDiff = new ve.dm.VisualDiff( oldDoc, newDoc, caseItem.forceTimeout ? -1 : undefined );
		var diffElement = new ve.ui.DiffElement( visualDiff );
		assert.equalDomElement( diffElement.$document[ 0 ], $( '<div>' ).addClass( 've-ui-diffElement-document' ).html( caseItem.expected )[ 0 ], caseItem.msg );
		assert.deepEqualWithDomElements(
			diffElement.descriptions.items.map( function ( item ) {
				return item.$label.contents().toArray();
			} ),
			( caseItem.expectedDescriptions || [] ).map( function ( expected ) {
				return $.parseHTML( expected );
			} ),
			caseItem.msg + ': sidebar'
		);
		assert.strictEqual(
			diffElement.$messages.children().length, caseItem.forceTimeout ? 1 : 0,
			'Timeout message ' + ( caseItem.forceTimeout ? 'shown' : 'not shown' )
		);
	};

	/**
	 * Create a UI surface from some HTML
	 *
	 * This is incredibly slow (>100ms) so consider creating just a ce.Surface
	 * or dm.Surface, or a mock surface using create(View|Model)OnlySurface*.
	 *
	 * @param {string} html
	 * @param {Object} config Surface config
	 * @return {ve.ui.Surface}
	 */
	ve.test.utils.createSurfaceFromHtml = function ( html, config ) {
		return this.createSurfaceFromDocument(
			ve.dm.converter.getModelFromDom( ve.createDocumentFromHtml( html ) ),
			config
		);
	};

	/**
	 * Create a UI surface from a document
	 *
	 * See warning in ve.test.utils.createSurfaceFromHtml.
	 *
	 * @param {ve.dm.Document} doc
	 * @param {Object} [config] Surface config
	 * @return {ve.ui.Surface}
	 */
	ve.test.utils.createSurfaceFromDocument = function ( doc, config ) {
		return ve.init.target.addSurface( doc, config );
	};

	/**
	 * Create a CE surface from some HTML
	 *
	 * @param {string} html
	 * @param {Object} config Surface config
	 * @return {ve.ce.Surface}
	 */
	ve.test.utils.createSurfaceViewFromHtml = function ( html, config ) {
		return this.createSurfaceViewFromDocument(
			ve.dm.converter.getModelFromDom( ve.createDocumentFromHtml( html ) ),
			config
		);
	};

	/**
	 * Create a CE surface from a document or surface model
	 *
	 * TODO: Rename to createSurfaceViewFromModel
	 *
	 * @param {ve.dm.Document|ve.dm.Surface} docOrSurface Document or surface model
	 * @param {Object} config Surface config
	 * @return {ve.ce.Surface}
	 */
	ve.test.utils.createSurfaceViewFromDocument = function ( docOrSurface, config ) {
		config = ve.init.target.getSurfaceConfig( config );

		var model, view;

		var mockSurface = {
			$blockers: $( '<div>' ),
			$selections: $( '<div>' ),
			$element: $( '<div>' ),
			$scrollContainer: $( document.documentElement ),
			$scrollListener: $( window ),
			isMobile: function () {
				return false;
			},
			isMultiline: function () {
				return true;
			},
			isReadOnly: function () {
				return false;
			},
			getBoundingClientRect: function () {
				return this.$element[ 0 ].getClientRects()[ 0 ] || null;
			},
			getViewportDimensions: function () {
				return null;
			},
			getImportRules: function () {
				return this.importRules;
			},
			getMode: function () {
				return config.mode || 'visual';
			},
			getModel: function () {
				return model;
			},
			getView: function () {
				return view;
			},
			getCommands: function () {
				return ve.ui.commandRegistry.getNames();
			},
			getContext: function () {
				return {
					toggle: function () {},
					updateDimensions: function () {}
				};
			},
			isDisabled: function () {
				return false;
			},
			emit: function () {},
			connect: function () {},
			disconnect: function () {},
			execute: ve.ui.Surface.prototype.execute,
			executeWithSource: ve.ui.Surface.prototype.executeWithSource,
			createView: ve.ui.Surface.prototype.createView,
			createModel: ve.ui.Surface.prototype.createModel,
			context: {
				// Used by ContentAction
				isVisible: function () {
					return false;
				}
			}
		};
		// Copied from ui.Surface constructor
		mockSurface.commandRegistry = config.commandRegistry || ve.ui.commandRegistry;
		mockSurface.sequenceRegistry = config.sequenceRegistry || ve.ui.sequenceRegistry;
		mockSurface.dataTransferHandlerFactory = config.dataTransferHandlerFactory || ve.ui.dataTransferHandlerFactory;
		mockSurface.commands = OO.simpleArrayDifference(
			config.includeCommands || mockSurface.commandRegistry.getNames(), config.excludeCommands || []
		);
		mockSurface.triggerListener = new ve.TriggerListener( mockSurface.commands, mockSurface.commandRegistry );
		mockSurface.importRules = config.importRules || {};

		model = docOrSurface instanceof ve.dm.Surface ? docOrSurface : mockSurface.createModel( docOrSurface );
		view = mockSurface.createView( model );

		view.surface = mockSurface;
		mockSurface.$element.append( view.$element );
		// eslint-disable-next-line no-jquery/no-global-selector
		$( '#qunit-fixture' ).append( mockSurface.$element );

		view.initialize();
		model.initialize();

		return view;
	};

	/**
	 * Create a view-only UI surface from some HTML
	 *
	 * @param {string} html Document HTML
	 * @param {Object} config Surface config
	 * @return {Object} Mock UI surface which only returns a real view (and its model)
	 */
	ve.test.utils.createViewOnlySurfaceFromHtml = function ( html, config ) {
		var surfaceView = ve.test.utils.createSurfaceViewFromDocument(
			ve.dm.converter.getModelFromDom( ve.createDocumentFromHtml( html ) ),
			config
		);

		return surfaceView.surface;
	};

	/**
	 * Create a model-only UI surface from some HTML
	 *
	 * @param {string} html Document HTML
	 * @param {Object} config Surface config
	 * @return {Object} Mock UI surface which only returns a real model
	 */
	ve.test.utils.createModelOnlySurfaceFromHtml = function ( html, config ) {
		var model = new ve.dm.Surface(
			ve.dm.converter.getModelFromDom( ve.createDocumentFromHtml( html ) ),
			null,
			config
		);
		return {
			getModel: function () {
				return model;
			},
			getView: function () {
				// Mock view
				return {
					focus: function () {}
				};
			}
		};
	};

	/**
	 * Create a DM selection from a range or a JSON selection
	 *
	 * @param {ve.dm.Document} doc
	 * @param {ve.Range|Object|string} rangeOrSelection Range or JSON selection
	 * @return {ve.dm.Selection}
	 */
	ve.test.utils.selectionFromRangeOrSelection = function ( doc, rangeOrSelection ) {
		return rangeOrSelection instanceof ve.Range ?
			new ve.dm.LinearSelection( rangeOrSelection ) :
			ve.dm.Selection.static.newFromJSON( rangeOrSelection );
	};

	/**
	 * Build a DOM from a JSON structure.
	 *
	 * @param {Object} data JSON structure
	 * @param {string} data.type Tag name or '#text' or '#comment'
	 * @param {string} [data.text] Node text, only used if type is '#text' or '#comment'
	 * @param {Object[]} [data.children] Node's children; array of objects like data
	 * @return {Node} DOM node corresponding to data
	 */
	ve.test.utils.buildDom = function buildDom( data ) {
		if ( data.type === '#text' ) {
			return document.createTextNode( data.text );
		}
		if ( data.type === '#comment' ) {
			return document.createComment( data.text );
		}
		var node = document.createElement( data.type );
		if ( data.children ) {
			for ( var i = 0; i < data.children.length; i++ ) {
				node.appendChild( buildDom( data.children[ i ] ) );
			}
		}
		return node;
	};

	/**
	 * Like a reduced outerHTML serialization, but with a position marker '|'.
	 *
	 * For clarity, also wraps each text node in a fake tag, and omits non-class attributes.
	 *
	 * @param {Node} rootNode The node to serialize
	 * @param {Object} position
	 * @param {Node} position.node The node at which the position marker lies
	 * @param {number} position.offset The offset at which the position marker lies
	 * @param {Object} [options]
	 * @param {Function|string} options.ignore Selector for nodes to omit from output
	 * @return {string} Serialization of the node and position
	 */
	ve.test.utils.serializePosition = function ( rootNode, position, options ) {
		var html = [];
		function add( node ) {
			if ( options && options.ignore && $( node ).is( options.ignore ) ) {
				return;
			} else if ( node.nodeType === Node.TEXT_NODE ) {
				html.push( '<#text>' );
				if ( node === position.node ) {
					html.push( ve.escapeHtml(
						node.textContent.slice( 0, position.offset ) +
						'|' +
						node.textContent.slice( position.offset )
					) );
				} else {
					html.push( ve.escapeHtml( node.textContent ) );
				}
				html.push( '</#text>' );
				return;
			} else if ( node.nodeType !== Node.ELEMENT_NODE ) {
				html.push( '<#unknown type=\'' + node.nodeType + '\'/>' );
				return;
			}
			// else node.nodeType === Node.ELEMENT_NODE

			html.push( '<', ve.escapeHtml( node.nodeName.toLowerCase() ) );
			if ( node.hasAttribute( 'class' ) ) {
				// Single quotes are less annoying for JSON escaping
				html.push(
					' class=\'',
					ve.escapeHtml( node.getAttribute( 'class' ) ),
					'\''
				);
			}
			html.push( '>' );
			for ( var i = 0, len = node.childNodes.length; i < len; i++ ) {
				if ( node === position.node && i === position.offset ) {
					html.push( '|' );
				}
				add( node.childNodes[ i ] );
			}
			if ( node === position.node && node.childNodes.length === position.offset ) {
				html.push( '|' );
			}
			html.push( '</', ve.escapeHtml( node.nodeName.toLowerCase() ), '>' );
		}
		add( rootNode );
		return html.join( '' );
	};

	/**
	 * Take control of EventSequencer timeouts
	 *
	 * Modifies an EventSequencer object in-place to allow setTimeout behaviour to be
	 * simulated by test code. Replaces `postpone` and `cancelPostponed` with methods that
	 * queue/unqueue to an array, and adds an `endLoop` method to unqueue and run every
	 * queued call.
	 *
	 * @param {ve.EventSequencer} eventSequencer The EventSequencer to hijack
	 */
	ve.test.utils.hijackEventSequencerTimeouts = function ( eventSequencer ) {
		eventSequencer.postponedCalls = [];

		eventSequencer.postpone = function ( f ) {
			this.postponedCalls.push( f );
			return this.postponedCalls.length - 1;
		};

		eventSequencer.cancelPostponed = function ( callId ) {
			this.postponedCalls[ callId ] = null;
		};

		eventSequencer.endLoop = function () {
			// Run every postponed call in order of postponement. Do not cache
			// list length, because postponed calls may add more postponed calls
			for ( var i = 0; i < this.postponedCalls.length; i++ ) {
				var f = this.postponedCalls[ i ];
				if ( f ) {
					// Exceptions thrown here will leave the postponed calls
					// list in an inconsistent state
					f();
				}
			}
			this.postponedCalls.length = 0;
		};
	};
}() );
