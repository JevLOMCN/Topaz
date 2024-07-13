/*!
 * VisualEditor DataModel MWTransclusionModel class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Object literal
 *
 * @class ve.dm.MWTransclusionPartInstruction
 * @private
 */
/**
 * @property {ve.dm.MWTransclusionPartModel} [remove]
 * @property {ve.dm.MWTransclusionPartModel} [add]
 * @property {number} [index]
 * @property {jQuery.Deferred} [deferred]
 */

( function () {
	var hasOwn = Object.hasOwnProperty,
		specCache = {};

	/**
	 * Represents a MediaWiki transclusion, i.e. a sequence of one or more template invocations that
	 * strictly belong to each other (e.g. because they are unbalanced), possibly mixed with raw
	 * wikitext snippets. These individual "parts" are subclasses of ve.dm.MWTransclusionPartModel.
	 *
	 * @class
	 * @mixins OO.EventEmitter
	 *
	 * @constructor
	 * @param {ve.dm.Document} doc Document to use associate with API requests
	 */
	ve.dm.MWTransclusionModel = function VeDmMWTransclusionModel( doc ) {
		// Mixin constructors
		OO.EventEmitter.call( this );

		/**
		 * @property {ve.dm.MWTransclusionPartModel[]} parts
		 * @property {number} uid
		 * @property {jQuery.Promise[]} templateDataApiRequests Currently running API requests. The only
		 *  reason to keep these around is to be able to abort them earlier when the template dialog
		 *  closes or resets.
		 * @property {Object[]} changeQueue
		 */
		this.doc = doc;
		this.parts = [];
		this.uid = 0;
		this.templateDataApiRequests = [];
		this.changeQueue = [];
	};

	/* Inheritance */

	OO.mixinClass( ve.dm.MWTransclusionModel, OO.EventEmitter );

	/* Events */

	/**
	 * Emitted when a part is added, removed, replaced (e.g. a placeholder with an actual template),
	 * or an existing part changed position.
	 *
	 * @event replace
	 * @param {ve.dm.MWTransclusionPartModel|null} removed Removed part
	 * @param {ve.dm.MWTransclusionPartModel|null} added Added or moved part
	 * @param {number} [newPosition] Position the part was added or moved to
	 */

	/**
	 * Emitted when anything changed, including any changes in the content of the parts.
	 *
	 * @event change
	 */

	/* Methods */

	/**
	 * Insert transclusion at the end of a surface fragment.
	 *
	 * If forceType is not specified and this is used in async mode, users of this method
	 * should ensure the surface is not accessible while the type is being evaluated.
	 *
	 * @param {ve.dm.SurfaceFragment} surfaceFragment Surface fragment after which to insert.
	 * @param {string} [forceType] Force the type to 'inline' or 'block'. If not
	 *   specified it will be evaluated asynchronously.
	 * @return {jQuery.Promise} Promise which resolves when the node has been inserted. If
	 *   forceType was specified this will be instant.
	 */
	ve.dm.MWTransclusionModel.prototype.insertTransclusionNode = function ( surfaceFragment, forceType ) {
		var model = this,
			deferred = ve.createDeferred(),
			baseNodeClass = ve.dm.MWTransclusionNode;

		function insertNode( isInline, generatedContents ) {
			var type = isInline ? baseNodeClass.static.inlineType : baseNodeClass.static.blockType,
				data = [
					{
						type: type,
						attributes: {
							mw: model.getPlainObject()
						}
					},
					{ type: '/' + type }
				];

			// If we just fetched the generated contents, put them in the store
			// so we don't do a duplicate API call later.
			if ( generatedContents ) {
				var nodeClass = ve.dm.modelRegistry.lookup( type );
				var store = surfaceFragment.getDocument().getStore();
				var hash = OO.getHash( [ nodeClass.static.getHashObjectForRendering( data[ 0 ] ), undefined ] );
				store.hash( generatedContents, hash );
			}

			surfaceFragment.insertContent( data );

			deferred.resolve();
		}

		if ( forceType ) {
			insertNode( forceType === 'inline' );
		} else {
			ve.init.target.parseWikitextFragment(
				baseNodeClass.static.getWikitext( this.getPlainObject() ),
				true,
				surfaceFragment.getDocument()
			).then( function ( response ) {
				if ( ve.getProp( response, 'visualeditor', 'result' ) === 'success' ) {
					// This method is only ever run by a client, so it is okay to use jQuery
					// eslint-disable-next-line no-undef
					var contentNodes = $.parseHTML( response.visualeditor.content, surfaceFragment.getDocument().getHtmlDocument() ) || [];
					contentNodes = ve.ce.MWTransclusionNode.static.filterRendering( contentNodes );
					insertNode(
						baseNodeClass.static.isHybridInline( contentNodes, ve.dm.converter ),
						contentNodes
					);
				} else {
					// Request failed - just assume inline
					insertNode( true );
				}
			}, function () {
				insertNode( true );
			} );
		}
		return deferred.promise();
	};

	/**
	 * Update transclusion node in a document.
	 *
	 * @param {ve.dm.Surface} surfaceModel Surface model of main document
	 * @param {ve.dm.MWTransclusionNode} node Transclusion node to update
	 */
	ve.dm.MWTransclusionModel.prototype.updateTransclusionNode = function ( surfaceModel, node ) {
		var obj = this.getPlainObject();

		if ( obj !== null ) {
			surfaceModel.getLinearFragment( node.getOuterRange(), true )
				.changeAttributes( { mw: obj } );
		} else {
			surfaceModel.getLinearFragment( node.getOuterRange(), true )
				.removeContent();
		}
	};

	/**
	 * Load from transclusion data, and fetch spec from server.
	 *
	 * @param {Object} data Transclusion data
	 * @return {jQuery.Promise} Promise, resolved when spec is loaded
	 */
	ve.dm.MWTransclusionModel.prototype.load = function ( data ) {
		var promises = [];

		// Convert single part format to multi-part format
		// Parsoid doesn't use this format any more, but we accept it for backwards compatibility
		if ( data.params && data.target ) {
			data = { parts: [ { template: data } ] };
		}

		if ( Array.isArray( data.parts ) ) {
			for ( var i = 0; i < data.parts.length; i++ ) {
				var part = data.parts[ i ];
				var deferred;
				if ( part.template ) {
					deferred = ve.createDeferred();
					promises.push( deferred.promise() );
					this.changeQueue.push( {
						add: ve.dm.MWTemplateModel.newFromData( this, part.template ),
						deferred: deferred
					} );
				} else if ( typeof part === 'string' ) {
					deferred = ve.createDeferred();
					promises.push( deferred.promise() );
					this.changeQueue.push( {
						add: new ve.dm.MWTransclusionContentModel( this, part ),
						deferred: deferred
					} );
				}
			}
			setTimeout( this.processChangeQueue.bind( this ) );
		}

		return ve.promiseAll( promises );
	};

	/**
	 * Process one or more queue items.
	 *
	 * @private
	 * @param {ve.dm.MWTransclusionPartInstruction[]} queue List of objects containing parts to add and optionally
	 * indexes to add them at, if no index is given parts will be added at the end
	 * @fires replace For each item added
	 * @fires change
	 */
	ve.dm.MWTransclusionModel.prototype.resolveChangeQueue = function ( queue ) {
		var resolveQueue = [];

		for ( var i = 0; i < queue.length; i++ ) {
			var item = queue[ i ],
				remove = 0;

			if ( item.add instanceof ve.dm.MWTemplateModel ) {
				var title = item.add.getTemplateDataQueryTitle();
				if ( hasOwn.call( specCache, title ) && specCache[ title ] ) {
					item.add.getSpec().setTemplateData( specCache[ title ] );
				}
			}

			// Use specified index
			var index = item.index;
			// Auto-remove if already existing, preserving index
			var existing = this.parts.indexOf( item.add );
			if ( existing !== -1 ) {
				this.removePart( item.add );
				if ( index && existing + 1 < index ) {
					index--;
				}
			}
			// Derive index from removal if given
			if ( index === undefined && item.remove ) {
				index = this.parts.indexOf( item.remove );
				if ( index !== -1 ) {
					remove = 1;
				}
			}
			// Use last index as a last resort
			if ( index === undefined || index === -1 ) {
				index = this.parts.length;
			}

			this.parts.splice( index, remove, item.add );
			if ( item.add ) {
				// This forwards change events from the nested ve.dm.MWTransclusionPartModel upwards.
				// The array syntax is a way to call `this.emit( 'change' )`.
				item.add.connect( this, { change: [ 'emit', 'change' ] } );
			}
			if ( item.remove ) {
				item.remove.disconnect( this );
			}
			this.emit( 'replace', item.remove || null, item.add, index );

			// Resolve promises
			if ( item.deferred ) {
				resolveQueue.push( item.deferred );
			}
		}
		this.emit( 'change' );

		// We need to go back and resolve the deferreds after emitting change.
		// Otherwise we get silly situations like a single change event being
		// guaranteed after the transclusion loaded promise gets resolved.
		resolveQueue.forEach( function ( queueItem ) {
			queueItem.resolve();
		} );
	};

	/**
	 * @private
	 */
	ve.dm.MWTransclusionModel.prototype.processChangeQueue = function () {
		var templateNamespaceId = mw.config.get( 'wgNamespaceIds' ).template,
			titles = [];

		if ( !this.changeQueue.length ) {
			return;
		}

		var queue = this.changeQueue.slice();

		// Clear shared queue for future calls
		this.changeQueue.length = 0;

		// Get unique list of template titles that aren't already loaded
		for ( var i = 0; i < queue.length; i++ ) {
			var item = queue[ i ];
			if ( item.add instanceof ve.dm.MWTemplateModel ) {
				var title = item.add.getTemplateDataQueryTitle(),
					mwTitle = title ? mw.Title.newFromText( title, templateNamespaceId ) : null;
				if (
					// Skip titles that don't have a resolvable href
					mwTitle &&
					// Skip already cached data
					!hasOwn.call( specCache, title ) &&
					// Skip duplicate titles in the same batch
					titles.indexOf( title ) === -1
				) {
					titles.push( title );
				}
			}
		}

		// Bypass server for empty lists
		if ( !titles.length ) {
			setTimeout( this.resolveChangeQueue.bind( this, queue ) );
			return;
		}

		this.templateDataApiRequests.push( this.callTemplateDataApi( titles, queue ) );
	};

	/**
	 * @private
	 * @param {string[]} titles
	 * @param {ve.dm.MWTransclusionPartInstruction[]} queue
	 * @return {jQuery.Promise}
	 */
	ve.dm.MWTransclusionModel.prototype.callTemplateDataApi = function ( titles, queue ) {
		var xhr = ve.init.target.getContentApi( this.doc ).get( {
			action: 'templatedata',
			titles: titles,
			lang: mw.config.get( 'wgUserLanguage' ),
			includeMissingTitles: '1',
			redirects: '1'
		} ).done( this.cacheTemplateDataApiResponse.bind( this ) );
		xhr.always(
			this.markRequestAsDone.bind( this, xhr ),
			this.resolveChangeQueue.bind( this, queue )
		);
		return xhr;
	};

	/**
	 * @private
	 * @param {Object} [data]
	 * @param {Object.<number,ve.dm.MWTemplatePageMetadata>} [data.pages]
	 */
	ve.dm.MWTransclusionModel.prototype.cacheTemplateDataApiResponse = function ( data ) {
		if ( !data || !data.pages ) {
			return;
		}

		// Keep spec data on hand for future use
		for ( var id in data.pages ) {
			var title = data.pages[ id ].title;

			if ( data.pages[ id ].missing ) {
				// Remember templates that don't exist in the link cache
				// { title: { missing: true|false }
				var missingTitle = {};
				missingTitle[ title ] = { missing: true };
				ve.init.platform.linkCache.setMissing( missingTitle );
			} else if ( data.pages[ id ].notemplatedata && !OO.isPlainObject( data.pages[ id ].params ) ) {
				// (T243868) Prevent asking again for templates that have neither user-provided specs
				// nor automatically detected params
				specCache[ title ] = null;
			} else {
				specCache[ title ] = data.pages[ id ];
			}
		}

		// Follow redirects
		var aliasMap = data.redirects || [];
		// Follow MW's normalisation
		if ( data.normalized ) {
			ve.batchPush( aliasMap, data.normalized );
		}
		// Cross-reference aliased titles.
		for ( var i = 0; i < aliasMap.length; i++ ) {
			// Only define the alias if the target exists, otherwise
			// we create a new property with an invalid "undefined" value.
			if ( hasOwn.call( specCache, aliasMap[ i ].to ) ) {
				specCache[ aliasMap[ i ].from ] = specCache[ aliasMap[ i ].to ];
			}
		}
	};

	/**
	 * @private
	 * @param {jQuery.Promise} apiPromise
	 */
	ve.dm.MWTransclusionModel.prototype.markRequestAsDone = function ( apiPromise ) {
		// Prune completed request
		var index = this.templateDataApiRequests.indexOf( apiPromise );
		if ( index !== -1 ) {
			this.templateDataApiRequests.splice( index, 1 );
		}
	};

	ve.dm.MWTransclusionModel.prototype.abortAllApiRequests = function () {
		for ( var i = 0; i < this.templateDataApiRequests.length; i++ ) {
			this.templateDataApiRequests[ i ].abort();
		}
		this.templateDataApiRequests.length = 0;
	};

	/**
	 * Get plain object representation of template transclusion.
	 *
	 * @return {Object|null} Plain object representation, or null if empty
	 */
	ve.dm.MWTransclusionModel.prototype.getPlainObject = function () {
		var parts = [];

		for ( var i = 0; i < this.parts.length; i++ ) {
			var part = this.parts[ i ];
			var serialization = part.serialize();
			if ( serialization !== undefined && serialization !== '' ) {
				parts.push( serialization );
			}
		}

		return parts.length ? { parts: parts } : null;
	};

	/**
	 * @return {string} Next part ID, starting from "part_0", guaranteed to be unique for this
	 *  transclusion
	 */
	ve.dm.MWTransclusionModel.prototype.nextUniquePartId = function () {
		return 'part_' + this.uid++;
	};

	/**
	 * Replace asynchronously.
	 *
	 * @param {ve.dm.MWTransclusionPartModel} remove Part to remove
	 * @param {ve.dm.MWTransclusionPartModel} add Part to add
	 * @throws {Error} If part to remove is not valid
	 * @throws {Error} If part to add is not valid
	 * @return {jQuery.Promise} Promise, resolved when part is added
	 */
	ve.dm.MWTransclusionModel.prototype.replacePart = function ( remove, add ) {
		var deferred = ve.createDeferred();
		if (
			!( remove instanceof ve.dm.MWTransclusionPartModel ) ||
			!( add instanceof ve.dm.MWTransclusionPartModel )
		) {
			throw new Error( 'Invalid transclusion part' );
		}
		this.changeQueue.push( { remove: remove, add: add, deferred: deferred } );

		// Fetch on next yield to process items in the queue together, subsequent calls will
		// have no effect because the queue will be clear
		setTimeout( this.processChangeQueue.bind( this ) );

		return deferred.promise();
	};

	/**
	 * Added asynchronously, but order is preserved.
	 *
	 * @param {ve.dm.MWTransclusionPartModel} part
	 * @param {number} [index] Specific index to add content at, defaults to the end
	 * @throws {Error} If part is not valid
	 * @return {jQuery.Promise} Promise, resolved when part is added
	 */
	ve.dm.MWTransclusionModel.prototype.addPart = function ( part, index ) {
		var deferred = ve.createDeferred();
		if ( !( part instanceof ve.dm.MWTransclusionPartModel ) ) {
			throw new Error( 'Invalid transclusion part' );
		}
		this.changeQueue.push( { add: part, index: index, deferred: deferred } );

		// Fetch on next yield to process items in the queue together, subsequent calls to fetch will
		// have no effect because the queue will be clear
		setTimeout( this.processChangeQueue.bind( this ) );

		return deferred.promise();
	};

	/**
	 * @param {ve.dm.MWTransclusionPartModel} part
	 * @fires replace
	 */
	ve.dm.MWTransclusionModel.prototype.removePart = function ( part ) {
		var index = this.parts.indexOf( part );
		if ( index !== -1 ) {
			this.parts.splice( index, 1 );
			part.disconnect( this );
			this.emit( 'replace', part, null );
		}
	};

	/**
	 * @return {boolean} True if the transclusion is literally empty or contains only placeholders
	 */
	ve.dm.MWTransclusionModel.prototype.isEmpty = function () {
		return this.parts.every( function ( part ) {
			return part instanceof ve.dm.MWTemplatePlaceholderModel;
		} );
	};

	/**
	 * @return {boolean} True if this is a single template or template placeholder
	 */
	ve.dm.MWTransclusionModel.prototype.isSingleTemplate = function () {
		return this.parts.length === 1 && (
			this.parts[ 0 ] instanceof ve.dm.MWTemplateModel ||
			this.parts[ 0 ] instanceof ve.dm.MWTemplatePlaceholderModel
		);
	};

	/**
	 * @return {ve.dm.MWTransclusionPartModel[]} All parts in this transclusion
	 */
	ve.dm.MWTransclusionModel.prototype.getParts = function () {
		return this.parts;
	};

	/**
	 * Matching is performed against the first section of the `id`, delimited by a '/'.
	 *
	 * @param {string} [id] Any id, including slash-delimited template parameter ids
	 * @return {ve.dm.MWTransclusionPartModel|undefined} Part with matching ID, if found
	 */
	ve.dm.MWTransclusionModel.prototype.getPartFromId = function ( id ) {
		if ( !id ) {
			return;
		}

		// For ids from ve.dm.MWParameterModel, compare against the part id
		// of the parameter instead of the entire model id (e.g. "part_1" instead of "part_1/foo").
		var partId = id.split( '/', 1 )[ 0 ];

		for ( var i = 0; i < this.parts.length; i++ ) {
			if ( this.parts[ i ].getId() === partId ) {
				return this.parts[ i ];
			}
		}
	};

	/**
	 * Get the index of a part or parameter.
	 *
	 * Indexes are linear depth-first addresses in the transclusion tree.
	 *
	 * @param {ve.dm.MWTransclusionPartModel|ve.dm.MWParameterModel} model Part or parameter
	 * @return {number} Page index of model
	 */
	ve.dm.MWTransclusionModel.prototype.getIndex = function ( model ) {
		var parts = this.parts,
			index = 0;

		for ( var i = 0; i < parts.length; i++ ) {
			var part = parts[ i ];
			if ( part === model ) {
				return index;
			}
			index++;
			if ( part instanceof ve.dm.MWTemplateModel ) {
				var names = part.getOrderedParameterNames();
				for ( var j = 0; j < names.length; j++ ) {
					if ( part.getParameter( names[ j ] ) === model ) {
						return index;
					}
					index++;
				}
			}
		}
		return -1;
	};

	/**
	 * Add missing required and suggested parameters to each transclusion.
	 */
	ve.dm.MWTransclusionModel.prototype.addPromptedParameters = function () {
		this.parts.forEach( function ( part ) {
			if ( part instanceof ve.dm.MWTemplateModel ) {
				part.addPromptedParameters();
			}
		} );
	};

	/**
	 * @return {boolean} True if any transclusion part contains meaningful, non-default user input
	 */
	ve.dm.MWTransclusionModel.prototype.containsValuableData = function () {
		return this.parts.some( function ( part ) {
			return part.containsValuableData();
		} );
	};

	/**
	 * Resets the model's state.
	 */
	ve.dm.MWTransclusionModel.prototype.reset = function () {
		this.parts = [];
		this.uid = 0;
		this.templateDataApiRequests = [];
		this.changeQueue = [];
	};

	// Temporary compatibility for https://github.com/femiwiki/Sanctions/pull/118. Remove when not
	// needed any more.
	mw.log.deprecate( ve.dm.MWTransclusionModel.prototype, 'abortRequests',
		ve.dm.MWTransclusionModel.prototype.abortAllApiRequests,
		'Use "abortAllApiRequests" instead.'
	);

}() );
