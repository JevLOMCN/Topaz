/*!
 * VisualEditor DataModel Converter class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel converter.
 *
 * Converts between HTML DOM and VisualEditor linear data.
 *
 * @class
 * @constructor
 * @param {ve.dm.ModelRegistry} modelRegistry
 * @param {ve.dm.NodeFactory} nodeFactory
 * @param {ve.dm.AnnotationFactory} annotationFactory
 */
ve.dm.Converter = function VeDmConverter( modelRegistry, nodeFactory, annotationFactory ) {
	// Properties
	this.modelRegistry = modelRegistry;
	this.nodeFactory = nodeFactory;
	this.annotationFactory = annotationFactory;
	this.doc = null;
	this.documentData = null;
	this.store = null;
	this.internalList = null;
	this.mode = null;
	this.fromClipboard = null;
	this.contextStack = null;

	// Whitespace regexes
	var whitespaceList = this.constructor.static.whitespaceList;
	/* eslint-disable security/detect-non-literal-regexp */
	this.leadingWhitespaceRegex = new RegExp( '^[' + whitespaceList + ']' );
	this.leadingWhitespacesRegex = new RegExp( '^[' + whitespaceList + ']+' );
	this.trailingWhitespaceRegex = new RegExp( '[' + whitespaceList + ']$' );
	this.trailingWhitespacesRegex = new RegExp( '[' + whitespaceList + ']+$' );
	this.onlyWhitespaceRegex = new RegExp( '^[' + whitespaceList + ']+$' );
	this.trimWhitespaceRegex = new RegExp( '^([' + whitespaceList + ']*)([\\s\\S]*?)([' + whitespaceList + ']*)$' );
	/* eslint-enable security/detect-non-literal-regexp */
};

/* Inheritance */

OO.initClass( ve.dm.Converter );

/* Static Properties */

/**
 * List of HTML attribute names that {#renderHtmlAttributeList} should use computed values for.
 *
 * @type {string[]}
 */
ve.dm.Converter.static.computedAttributes = [ 'href', 'src' ];

/**
 * Pattern matching 'white space characters' as defined by the HTML spec only.
 *
 * All other whitespace should be treated as text, e.g. non-breaking spaces.
 *
 * See https://www.w3.org/TR/html4/struct/text.html#h-9.1
 *
 * @type {RegExp}
 */
ve.dm.Converter.static.whitespaceList = ' \\t\\f\\u200b\\r\\n';

ve.dm.Converter.static.PARSER_MODE = 0;
ve.dm.Converter.static.CLIPBOARD_MODE = 1;
ve.dm.Converter.static.PREVIEW_MODE = 2;

/* Static Methods */

/**
 * Get linear model data from a string optionally applying annotations
 *
 * @static
 * @param {string} text Plain text to convert
 * @param {ve.dm.AnnotationSet} [annotations] Annotations to apply
 * @return {Array} Linear model data, one element per character
 */
ve.dm.Converter.static.getDataContentFromText = function ( text, annotations ) {
	var characters = text.split( '' );

	if ( !annotations || annotations.isEmpty() ) {
		return characters;
	}
	// Apply annotations to characters
	for ( var i = 0, len = characters.length; i < len; i++ ) {
		// Just store the annotations' hashes from the hash-value store
		characters[ i ] = [ characters[ i ], annotations.getHashes().slice() ];
	}
	return characters;
};

/**
 * Utility function for annotation rendering. Transforms one set of annotations into another
 * by opening and closing annotations. Each time an annotation is opened or closed, the associated
 * callback is called with the annotation passed as a parameter.
 *
 * Note that currentSet will be modified, and will be equal to targetSet once this function returns.
 *
 * @static
 * @param {ve.dm.AnnotationSet} currentSet The set of annotations currently opened. Will be modified.
 * @param {ve.dm.AnnotationSet} targetSet The set of annotations we want to have.
 * @param {Function} open Callback called when an annotation is opened. Passed a ve.dm.Annotation.
 * @param {Function} close Callback called when an annotation is closed. Passed a ve.dm.Annotation.
 */
ve.dm.Converter.static.openAndCloseAnnotations = function ( currentSet, targetSet, open, close ) {
	var hash, i, len;
	// Close annotations as needed
	// Go through annotationStack from bottom to top (low to high),
	// and find the first annotation that's not in annotations.
	if ( currentSet.getLength() ) {
		var targetSetOpen = targetSet.clone();
		var startClosingAt;
		for ( i = 0, len = currentSet.getLength(); i < len; i++ ) {
			hash = currentSet.getHash( i );
			// containsComparableForSerialization is expensive,
			// so do a simple contains check first
			if (
				targetSetOpen.containsHash( hash ) ||
				targetSetOpen.containsComparableForSerialization( currentSet.get( i ) )
			) {
				targetSetOpen.removeHash( hash );
			} else {
				startClosingAt = i;
				break;
			}
		}
		if ( startClosingAt !== undefined ) {
			// Close all annotations from top to bottom (high to low)
			// until we reach startClosingAt
			for ( i = currentSet.getLength() - 1; i >= startClosingAt; i-- ) {
				close( currentSet.get( i ) );
				// Remove from currentClone
				currentSet.removeAt( i );
			}
		}
	}

	if ( targetSet.getLength() ) {
		var currentSetOpen = currentSet.clone();
		// Open annotations as needed
		for ( i = 0, len = targetSet.getLength(); i < len; i++ ) {
			hash = targetSet.getHash( i );
			// containsComparableForSerialization is expensive,
			// so do a simple contains check first
			if (
				currentSetOpen.containsHash( hash ) ||
				currentSetOpen.containsComparableForSerialization( targetSet.get( i ) )
			) {
				// If an annotation is already open remove it from the currentSetOpen list
				// as it may exist multiple times in the targetSet, and so may need to be
				// opened again
				currentSetOpen.removeHash( hash );
			} else {
				open( targetSet.get( i ) );
				// Add to currentClone
				currentSet.pushHash( hash );
			}
		}
	}
};

/**
 * Copy attributes from one set of DOM elements to another.
 *
 * @static
 * @param {HTMLElement[]} originalDomElements Array of DOM elements to render from
 * @param {HTMLElement[]} targetDomElements Array of DOM elements to render onto
 * @param {boolean|Function} [filter=true] Attribute filter
 * @param {boolean} [computed=false] If true, use the computed values of attributes where available
 * @param {boolean} [deep=false] Recurse into child nodes
 */
ve.dm.Converter.static.renderHtmlAttributeList = function ( originalDomElements, targetDomElements, filter, computed, deep ) {
	if ( filter === undefined ) {
		filter = true;
	}
	if ( filter === false ) {
		return;
	}

	for ( var i = 0, ilen = originalDomElements.length; i < ilen; i++ ) {
		if ( !targetDomElements[ i ] ) {
			continue;
		}
		var attrs = originalDomElements[ i ].attributes;
		if ( !attrs ) {
			continue;
		}
		for ( var j = 0, jlen = attrs.length; j < jlen; j++ ) {
			if (
				targetDomElements[ i ].nodeType === Node.ELEMENT_NODE &&
				!targetDomElements[ i ].hasAttribute( attrs[ j ].name ) &&
				( filter === true || filter( attrs[ j ].name ) )
			) {
				var value;
				if ( computed && this.computedAttributes.indexOf( attrs[ j ].name ) !== -1 ) {
					value = originalDomElements[ i ][ attrs[ j ].name ];
				} else {
					value = attrs[ j ].value;
				}
				targetDomElements[ i ].setAttribute( attrs[ j ].name, value );
			}
		}

		// Descend into element children only (skipping text nodes, comment nodes and nodes we just created)
		if ( deep && !targetDomElements[ i ].veFromDataElement && originalDomElements[ i ].children.length > 0 ) {
			this.renderHtmlAttributeList(
				originalDomElements[ i ].children,
				targetDomElements[ i ].children,
				filter,
				computed,
				true
			);
		}
	}
};

/**
 * Modify linear model data in-place to move inline meta items out of content context
 *
 * All branch node start items must have item.internal.metaItems = []
 * All inline meta items must have item.internal.isInlineMeta set to true
 *
 * After the method completes, each inline meta item will be moved downward to the nearest legal
 * block position (i.e. just after the close meta parent item), and has these properties:
 * item.internal.loadMetaParentHash - corresponding meta parent's item.originalDomElementsHash
 * item.internal.loadMetaParentOffset - offset at load time within the meta parent (0 for start).
 * Each meta item is appended to the corresponding meta parent's item.internal.metaItems .
 *
 * @param {Array} data Linear model data to modify in place
 */
ve.dm.Converter.static.moveInlineMetaItems = function ( data ) {
	var ancestors = [],
		pendingMetaItems = [];

	function closestMetaParent() {
		for ( var n = ancestors.length - 1; n >= 0; n-- ) {
			var ancestor = ancestors[ n ];
			if ( ancestor.isMetaParent ) {
				return ancestor;
			}
		}
		return null;
	}

	var metaParent;
	for ( var i = 0; i < data.length; i++ ) {
		var item = data[ i ];
		if ( Array.isArray( item ) ) {
			// Ignore annotations
			item = item[ 0 ];
		}
		if ( !item.type ) {
			// Item is not a node
			continue;
		}
		if ( item.type[ 0 ] !== '/' ) {
			// Item is a node start
			if ( ve.getProp( item, 'internal', 'isInlineMeta' ) ) {
				// This is an inline meta item: move it
				delete item.internal.isInlineMeta;
				metaParent = closestMetaParent();
				if ( metaParent ) {
					metaParent.item.internal.metaItems.push( item );
					pendingMetaItems.push( {
						item: item,
						closeItem: data[ i + 1 ],
						metaParent: metaParent,
						offset: i - metaParent.offset - 1
					} );
					// Remove this item and the immediately following close item
					data.splice( i, 2 );
					// Prepare to rescan this index
					i--;
				} else {
					// Inline meta outside meta parent. This can happen if, say,
					// the document starts with a comment then a meta item.
					// Skip this item and the immediately following close item
					i++;
				}
			} else {
				ancestors.push( {
					item: item,
					offset: i,
					isMetaParent: !!ve.getProp( item, 'internal', 'metaItems' )
				} );
			}
		} else {
			// Item is a node end
			metaParent = ancestors.pop();
			if ( metaParent.isMetaParent ) {
				for ( var j = 0; j < pendingMetaItems.length; j++ ) {
					var pending = pendingMetaItems[ j ];
					if ( pending.metaParent.item !== metaParent.item ) {
						continue;
					}
					pending.item.internal.loadMetaParentHash = metaParent.item.originalDomElementsHash;
					pending.item.internal.loadMetaParentOffset = pending.offset;
					pendingMetaItems.splice( j, 1 );
					j--;
					// This will drop annotations on meta items; fine
					data.splice( i + 1, 0, pending.item, pending.closeItem );
					i += 2;
				}
			}
		}
	}
};

/* Methods */

/**
 * Check whether this converter instance is currently inside a getModelFromDom() conversion.
 *
 * @return {boolean} Whether we're converting
 */
ve.dm.Converter.prototype.isConverting = function () {
	return this.contextStack !== null;
};

/**
 * Get the HashValueStore used for the current conversion.
 *
 * @return {ve.dm.HashValueStore|null} Current store, or null if not converting
 */
ve.dm.Converter.prototype.getStore = function () {
	return this.store;
};

/**
 * Get the HTML document currently being converted
 *
 * @return {HTMLDocument|null} HTML document being converted, or null if not converting
 */
ve.dm.Converter.prototype.getHtmlDocument = function () {
	return this.doc;
};

/**
 * Get the HTML document we are converting data for
 *
 * @return {HTMLDocument|null} HTML document being converted for, or null if not converting
 */
ve.dm.Converter.prototype.getTargetHtmlDocument = function () {
	return this.targetDoc;
};

/**
 * Get the converter mode, one of PARSER_MODE, CLIPBOARD_MODE or PREVIEW_MODE
 *
 * @return {number} Converter mode
 */
ve.dm.Converter.prototype.getMode = function () {
	return this.mode;
};

/**
 * Checks if the current mode needs a full view rendering in the HTML
 *
 * @return {boolean} Mode needs a rendering
 */
ve.dm.Converter.prototype.doesModeNeedRendering = function () {
	return this.getMode() !== this.constructor.static.PARSER_MODE;
};

/**
 * Is the current conversion for the parser
 *
 * @return {boolean} The conversion is for the paser
 */
ve.dm.Converter.prototype.isForParser = function () {
	return this.getMode() === this.constructor.static.PARSER_MODE;
};

/**
 * Is the current conversion for the clipboard
 *
 * @return {boolean} The conversion is for the clipboard
 */
ve.dm.Converter.prototype.isForClipboard = function () {
	return this.getMode() === this.constructor.static.CLIPBOARD_MODE;
};

/**
 * Is the current conversion for the preview
 *
 * @return {boolean} The conversion is for the preview
 */
ve.dm.Converter.prototype.isForPreview = function () {
	return this.getMode() === this.constructor.static.PREVIEW_MODE;
};

/**
 * Is the current conversion from the clipboard
 *
 * @return {boolean|null} The conversion is from the clipboard, or null if not converting
 */
ve.dm.Converter.prototype.isFromClipboard = function () {
	return this.fromClipboard;
};

/**
 * Get the current conversion context. This is the recursion state of getDataFromDomSubtree().
 *
 * @return {Object|null} Context object, or null if not converting
 */
ve.dm.Converter.prototype.getCurrentContext = function () {
	return this.contextStack === null ? null : this.contextStack[ this.contextStack.length - 1 ];
};

/**
 * Get the annotations currently being applied by the converter. Note that this is specific to
 * the current recursion level.
 *
 * @return {ve.dm.AnnotationSet|null} Annotation set, or null if not converting
 */
ve.dm.Converter.prototype.getActiveAnnotations = function () {
	var context = this.getCurrentContext();
	return context ? context.annotations : null;
};

/**
 * Whether the converter is currently expecting content. Note that this is specific to the current
 * recursion level.
 *
 * @return {boolean|null} Boolean indicating whether content is expected, or null if not converting
 */
ve.dm.Converter.prototype.isExpectingContent = function () {
	var context = this.getCurrentContext();
	return context ? context.expectingContent : null;
};

/**
 * Whether the converter can currently accept a child node with the given type.
 *
 * @param {string} nodeType
 * @return {boolean|null} Whether the node type is valid, or null if not converting
 */
ve.dm.Converter.prototype.isValidChildNodeType = function ( nodeType ) {
	var context = this.getCurrentContext();
	if ( !context ) {
		return null;
	}
	var childTypes = this.nodeFactory.getChildNodeTypes( context.branchType );
	return ( childTypes === null || childTypes.indexOf( nodeType ) !== -1 );
};

/**
 * Whether the conversion is currently inside a wrapper paragraph generated by the converter.
 * Note that this is specific to the current recursion level.
 *
 * @return {boolean|null} Boolean indicating whether we're wrapping, or null if not converting
 */
ve.dm.Converter.prototype.isInWrapper = function () {
	var context = this.getCurrentContext();
	return context ? context.inWrapper : null;
};

/**
 * Whether the active wrapper can be closed. Note that this is specific to the current recursion
 * level. If there is no active wrapper, this returns false.
 *
 * @return {boolean|null} Boolean indicating whether the wrapper can be closed, or null if not converting
 */
ve.dm.Converter.prototype.canCloseWrapper = function () {
	var context = this.getCurrentContext();
	return context ? context.canCloseWrapper : null;
};

/**
 * Get the DOM element for a given linear model element.
 *
 * This invokes the toDomElements function registered for the element type.
 *
 * @param {Object|Array} dataElements Linear model element or data slice
 * @param {HTMLDocument} doc Document to create DOM elements in
 * @param {Node[]} [childDomElements] Array of child DOM elements to pass in (annotations only)
 * @return {Node[]|boolean} DOM elements, or false if the element cannot be converted.
 *  If the first DOMelement has a 'handledOwnChildren' property set, the converter treats it as if it
 *  were a handlesOwnChildren node.
 */
ve.dm.Converter.prototype.getDomElementsFromDataElement = function ( dataElements, doc, childDomElements ) {
	var dataElement = Array.isArray( dataElements ) ? dataElements[ 0 ] : dataElements,
		nodeClass = this.modelRegistry.lookup( dataElement.type );

	if ( !nodeClass ) {
		throw new Error( 'Attempting to convert unknown data element type ' + dataElement.type );
	}
	if ( nodeClass.static.isInternal ) {
		return false;
	}
	var domElements = nodeClass.static.toDomElements( dataElements, doc, this, childDomElements );
	if ( !Array.isArray( domElements ) && !( nodeClass.prototype instanceof ve.dm.Annotation ) ) {
		throw new Error( 'toDomElements() failed to return an array when converting element of type ' + dataElement.type );
	}
	var originalDomElements = this.store.value( dataElement.originalDomElementsHash );
	// Optimization: don't call renderHtmlAttributeList if returned domElements are equal to the originals
	if ( originalDomElements && !ve.isEqualDomElements( domElements, originalDomElements ) ) {
		this.constructor.static.renderHtmlAttributeList(
			originalDomElements,
			domElements,
			nodeClass.static.preserveHtmlAttributes,
			// computed
			false,
			// deep
			(
				!this.nodeFactory.lookup( dataElement.type ) ||
				!this.nodeFactory.canNodeHaveChildren( dataElement.type ) ||
				this.nodeFactory.doesNodeHandleOwnChildren( dataElement.type )
			)
		);
	}
	// TODO: This is only for the diff. Eventually should make a DiffConverter subclass
	if ( dataElement.internal && dataElement.internal.diff ) {
		Array.prototype.forEach.call( domElements, function ( domElement ) {
			for ( var key in dataElement.internal.diff ) {
				// toDomElements is a misnomer, it can actually return other nodes,
				// such as comment nodes or text nodes.
				if ( domElement.setAttribute ) {
					domElement.setAttribute( key, dataElement.internal.diff[ key ] );
				}
			}
		} );
	}
	// Mark branch nodes as generated from dataElement, so we don't try and descend into them in a deep renderHtmlAttributeList call
	if ( this.nodeFactory.lookup( dataElement.type ) && this.nodeFactory.canNodeHaveChildren( dataElement.type ) ) {
		var hasSignificantWhitespace = this.nodeFactory.doesNodeHaveSignificantWhitespace( dataElement.type );
		domElements.forEach( function ( domElement ) {
			domElement.veFromDataElement = true;
			if ( hasSignificantWhitespace ) {
				domElement.veHasSignificantWhitespace = true;
			}
		} );
	}
	return domElements;
};

/**
 * Create a data element from a DOM element.
 *
 * @param {ve.dm.Model} modelClass Model class to use for conversion
 * @param {Node[]} domElements DOM elements to convert
 * @return {Object|Array|null} Data element or array of linear model data, or null to alienate
 */
ve.dm.Converter.prototype.createDataElements = function ( modelClass, domElements ) {
	var dataElements = modelClass.static.toDataElement( domElements, this );

	if ( !dataElements ) {
		return null;
	}
	if ( !Array.isArray( dataElements ) ) {
		dataElements = [ dataElements ];
	}
	if ( dataElements.length ) {
		var serializer;
		if ( modelClass.prototype instanceof ve.dm.Annotation ) {
			serializer = function ( node ) {
				// Do not include childNodes; see T160839
				return node.cloneNode( false ).outerHTML;
			};
		} else {
			serializer = ve.getNodeHtml;
		}
		dataElements[ 0 ].originalDomElementsHash = this.store.hash(
			domElements,
			domElements.map( serializer ).join( '' )
		);
		if ( modelClass.prototype instanceof ve.dm.BranchNode && modelClass.static.childNodeTypes === null ) {
			// Set this item up as a meta parent
			ve.setProp( dataElements[ 0 ], 'internal', 'metaItems', [] );
			ve.setProp( dataElements[ 0 ], 'internal', 'changesSinceLoad', 0 );
		}
	}
	return dataElements;
};

/**
 * Build an HTML DOM node for a linear model annotation.
 *
 * @param {Object} dataAnnotation
 * @param {HTMLDocument} doc HTML document to create element with
 * @return {HTMLElement} HTML DOM node
 */
ve.dm.Converter.prototype.getDomElementFromDataAnnotation = function ( dataAnnotation, doc ) {
	var htmlData = dataAnnotation.toHtml(),
		domElement = doc.createElement( htmlData.tag );

	ve.setDomAttributes( domElement, htmlData.attributes );
	return domElement;
};

/**
 * Convert an HTML document to a document model.
 *
 * @param {HTMLDocument} doc HTML document to convert
 * @param {Object} [options] Conversion options
 * @param {HTMLDocument} [options.targetDoc=doc] Target HTML document we are converting for, if different from doc
 * @param {boolean} [options.fromClipboard=false] Conversion is from clipboard
 * @param {string} [options.lang] Document language code
 * @param {string} [options.dir] Document directionality (ltr/rtl)
 * @param {ve.dm.HashValueStore} [store] Hash value store
 * @return {ve.dm.Document} Document model
 */
ve.dm.Converter.prototype.getModelFromDom = function ( doc, options, store ) {
	var tmpDoc = new ve.dm.Document();
	var internalList = new ve.dm.InternalList( tmpDoc );

	store = store || new ve.dm.HashValueStore();
	options = options || {};

	// Set up the converter state
	this.doc = doc;
	this.targetDoc = options.targetDoc || doc;
	this.fromClipboard = options.fromClipboard;
	this.store = store;
	this.internalList = internalList;
	this.contextStack = [];
	// Possibly do things with doc and the head in the future

	// Generate data
	var data = this.getDataFromDomSubtree( doc.body );
	this.constructor.static.moveInlineMetaItems( data );

	var linearData = new ve.dm.ElementLinearData( store, data );
	var refData = this.internalList.convertToData( this, doc );
	linearData.batchSplice( linearData.getLength(), 0, refData );
	var innerWhitespace = this.getInnerWhitespace( linearData );

	// Clear the state
	this.doc = null;
	this.targetDoc = null;
	this.fromClipboard = null;
	this.store = null;
	this.internalList = null;
	this.contextStack = null;

	return new ve.dm.Document( linearData, doc, undefined, internalList, innerWhitespace, options.lang, options.dir, null, null, tmpDoc.getStorage() );
};

/**
 * Wrapper for getDataFromDom which resets contextStack before the call
 * and then set it back after the call.
 *
 * TODO: This is kind of a hack, better implementation would be more appropriate in near future.
 *
 * @param {HTMLElement} domElement HTML element to convert
 * @param {Object} [wrapperElement] Data element to wrap the returned data in
 * @param {ve.dm.AnnotationSet} [annotationSet] Override the set of annotations to use
 * @return {Array} Linear model data
 */
ve.dm.Converter.prototype.getDataFromDomClean = function ( domElement, wrapperElement, annotationSet ) {
	var contextStack = this.contextStack;
	this.contextStack = [];
	var result = this.getDataFromDomSubtree( domElement, wrapperElement, annotationSet );
	this.contextStack = contextStack;
	return result;
};

/**
 * Get linear model data from a DOM node. Called recursively. For internal use
 * and ve.dm.Model.static.toDataElement() implementations.
 *
 * @param {HTMLElement} domElement HTML element to convert
 * @param {Object} [wrapperElement] Data element to wrap the returned data in
 * @param {ve.dm.AnnotationSet} [annotationSet] Override the set of annotations to use
 * @return {Array} Linear model data
 */
ve.dm.Converter.prototype.getDataFromDomSubtree = function ( domElement, wrapperElement, annotationSet ) {
	var wrappingParagraph,
		converter = this,
		modelRegistry = this.modelRegistry,
		data = [],
		nextWhitespace = '',
		wrappedWhitespace = '',
		wrappedWhitespaceIndex,
		wrappedMetaItems = [],
		context = {},
		prevContext = this.contextStack.length ?
			this.contextStack[ this.contextStack.length - 1 ] : null;

	/**
	 * Add whitespace to an element at a specific offset.
	 *
	 * @private
	 * @param {Array} element Data element
	 * @param {number} index Whitespace index, 0-3
	 * @param {string} whitespace Whitespace content
	 */
	function addWhitespace( element, index, whitespace ) {
		if ( !whitespace ) {
			return;
		}
		if ( !element.internal ) {
			element.internal = {};
		}
		// whitespace = [ outerPre, innerPre, innerPost, outerPost ]
		//         <tag>        text         </tag>         <nextTag>
		// ^^^^^^^^     ^^^^^^^^    ^^^^^^^^^      ^^^^^^^^^
		// outerPre     innerPre    innerPost      outerPost
		if ( !element.internal.whitespace ) {
			element.internal.whitespace = [];
		}
		element.internal.whitespace[ index ] = whitespace;
	}
	function processNextWhitespace( element ) {
		// This function uses and changes nextWhitespace in the outer function's scope,
		// which means it's not really a function but more of a shortcut.
		if ( nextWhitespace !== '' ) {
			addWhitespace( element, 0, nextWhitespace );
			nextWhitespace = '';
		}
	}
	// FIXME rewrite this horrible meta item / whitespace queueing/wrapping business
	function outputWrappedMetaItems( whitespaceTreatment ) {
		var toInsert = [],
			prev = wrappingParagraph;

		for ( var j = 0, len = wrappedMetaItems.length; j < len; j++ ) {
			if ( wrappedMetaItems[ j ].type && wrappedMetaItems[ j ].type.charAt( 0 ) !== '/' ) {
				if ( wrappedMetaItems[ j ].internal && wrappedMetaItems[ j ].internal.whitespace ) {
					if ( whitespaceTreatment === 'restore' ) {
						ve.batchPush( toInsert, converter.constructor.static.getDataContentFromText(
							wrappedMetaItems[ j ].internal.whitespace[ 0 ], context.annotations
						) );
						delete wrappedMetaItems[ j ].internal;
					} else if ( whitespaceTreatment === 'fixup' ) {
						addWhitespace( prev, 3, wrappedMetaItems[ j ].internal.whitespace[ 0 ] );
					}
				}
				prev = wrappedMetaItems[ j ];
			}
			toInsert.push( wrappedMetaItems[ j ] );
		}
		if ( wrappedWhitespace !== '' && whitespaceTreatment === 'restore' ) {
			// If we have wrapped whitespace, insert the wrapped meta items before it
			// This is horrible and this whole system desperately needs to be rewritten
			ve.batchSplice( data, wrappedWhitespaceIndex, 0, toInsert );
		} else {
			ve.batchPush( data, toInsert );
		}
		wrappedMetaItems = [];
	}
	function startWrapping() {
		// Mark this paragraph as having been generated by
		// us, so we can strip it on the way out
		wrappingParagraph = {
			type: 'paragraph',
			internal: { generated: 'wrapper', metaItems: [] }
		};
		data.push( wrappingParagraph );
		context.inWrapper = true;
		context.canCloseWrapper = true;
		context.expectingContent = true;
		processNextWhitespace( wrappingParagraph );
	}
	function stopWrapping() {
		if ( wrappedWhitespace !== '' ) {
			// Remove wrappedWhitespace from data
			data.splice( wrappedWhitespaceIndex, wrappedWhitespace.length );
			// Add whitespace to the last sibling: either the last meta item or the wrapper paragraph
			addWhitespace( wrappedMetaItems.length > 0 ? wrappedMetaItems[ wrappedMetaItems.length - 2 ] : wrappingParagraph, 3, wrappedWhitespace );
			nextWhitespace = wrappedWhitespace;
		}
		data.push( { type: '/paragraph' } );
		outputWrappedMetaItems( 'fixup' );
		wrappingParagraph = undefined;
		context.inWrapper = false;
		context.canCloseWrapper = false;
		context.expectingContent = context.originallyExpectingContent;
	}
	function getAboutGroup( node ) {
		var group = [ node ];

		if ( node.nodeType !== Node.ELEMENT_NODE || node.getAttribute( 'about' ) === null ) {
			return group;
		}
		var about = node.getAttribute( 'about' );
		while ( ( node = node.nextSibling ) !== null ) {
			if ( node.nodeType === Node.ELEMENT_NODE && node.getAttribute( 'about' ) === about ) {
				group.push( node );
			} else {
				break;
			}
		}
		return group;
	}
	function isAllInstanceOf( lienarData, targetClass ) {
		for ( var j = lienarData.length - 1; j >= 0; j-- ) {
			var type = ve.dm.LinearData.static.getType( lienarData[ j ] );
			if ( type ) {
				var itemClass = modelRegistry.lookup( type ) || ve.dm.AlienNode;
				if ( !( itemClass === targetClass || itemClass.prototype instanceof targetClass ) ) {
					return false;
				}
			} else {
				return false;
			}
		}
		return true;
	}

	context.annotations = annotationSet || (
		prevContext ? prevContext.annotations.clone() : new ve.dm.AnnotationSet( this.store )
	);
	context.branchType = wrapperElement ? wrapperElement.type : (
		prevContext ? prevContext.branchType : 'document'
	);
	context.branchHasContent = this.nodeFactory.canNodeContainContent( context.branchType );
	context.originallyExpectingContent = context.branchHasContent || !context.annotations.isEmpty();
	context.expectingContent = context.originallyExpectingContent;
	context.inWrapper = prevContext ? prevContext.inWrapper : false;
	context.canCloseWrapper = false;
	this.contextStack.push( context );

	// Open element
	if ( wrapperElement ) {
		data.push( wrapperElement );
	}
	// Add contents
	function setInlineMeta( element ) {
		ve.setProp( element, 'internal', 'isInlineMeta', true );
	}

	var prevElement;
	for ( var i = 0; i < domElement.childNodes.length; i++ ) {
		var childNode = domElement.childNodes[ i ];
		switch ( childNode.nodeType ) {
			case Node.ELEMENT_NODE:
			case Node.COMMENT_NODE:
				if (
					childNode.getAttribute &&
					childNode.getAttribute( 'data-ve-ignore' )
				) {
					continue;
				}
				var aboutGroup = getAboutGroup( childNode );
				var modelName = this.modelRegistry.matchElement( childNode, aboutGroup.length > 1 );
				var modelClass = this.modelRegistry.lookup( modelName ) || ve.dm.AlienNode;
				var childNodes;
				if ( modelClass.prototype instanceof ve.dm.Annotation ) {
					childNodes = [ childNode ];
				} else {
					// Node or meta item
					childNodes = modelClass.static.enableAboutGrouping ?
						aboutGroup : [ childNode ];
				}
				var childDataElements = this.createDataElements( modelClass, childNodes );

				if ( !childDataElements ) {
					// Alienate
					modelClass = ve.dm.AlienNode;
					childNodes = modelClass.static.enableAboutGrouping ?
						aboutGroup : [ childNode ];
					childDataElements = this.createDataElements( modelClass, childNodes );
				} else if ( childDataElements.length ) {
					// Update modelClass to reflect the type we got back
					modelClass = this.modelRegistry.lookup( childDataElements[ 0 ].type );
				} else {
					continue;
				}

				// If we're about to start wrapping for an annotation,
				// check paragraphs are actually allowed here.
				if (
					!context.inWrapper && !context.expectingContent &&
					modelClass.prototype instanceof ve.dm.Annotation &&
					!this.isValidChildNodeType( 'paragraph' )
				) {
					// Alienate (force block mode as we are replacing a wrapper)
					modelClass = ve.dm.AlienBlockNode;
					childNodes = modelClass.static.enableAboutGrouping ?
						aboutGroup : [ childNode ];
					childDataElements = this.createDataElements( modelClass, childNodes );
				}

				// Now take the appropriate action based on that
				if ( modelClass.prototype instanceof ve.dm.Annotation ) {
					var annotation = this.annotationFactory.createFromElement( childDataElements[ 0 ], this.store );
					// Start wrapping if needed
					if ( !context.inWrapper && !context.expectingContent ) {
						startWrapping();
						prevElement = wrappingParagraph;
					}
					// Append child element data
					var childAnnotations = context.annotations.clone();
					childAnnotations.push( annotation );

					childDataElements = this.getDataFromDomSubtree( childNode, undefined, childAnnotations );
					if ( !childDataElements.length || isAllInstanceOf( childDataElements, ve.dm.AlienMetaItem ) ) {
						// Empty annotation, create a meta item
						if ( !childDataElements.length || isAllInstanceOf( childDataElements, ve.dm.RemovableAlienMetaItem ) ) {
							childDataElements = this.createDataElements( ve.dm.RemovableAlienMetaItem, childNodes );
						} else {
							childDataElements = this.createDataElements( ve.dm.AlienMetaItem, childNodes );
						}
						childDataElements.push( { type: '/' + childDataElements[ 0 ].type } );
						// Annotate meta item
						if ( !context.annotations.isEmpty() ) {
							childDataElements[ 0 ].annotations = context.annotations.getHashes().slice();
						}
						// Mark meta items to be moved outside of content context, as we can't handle them here
						// (context.expectingContent is always true at this point)
						setInlineMeta( childDataElements[ 0 ] );
					}
					outputWrappedMetaItems( 'restore' );
					ve.batchPush( data, childDataElements );
					// Clear wrapped whitespace
					wrappedWhitespace = '';
				} else {
					// Node or meta item
					if ( modelClass.prototype instanceof ve.dm.MetaItem ) {
						if ( context.expectingContent ) {
							// Mark meta items to be moved outside of content context, as we can't handle them here
							childDataElements.forEach( setInlineMeta );
						}

						// No additional processing needed
						// Write to data and continue
						if ( childDataElements.length === 1 ) {
							childDataElements.push( { type: '/' + childDataElements[ 0 ].type } );
						}
						// Annotate meta item
						if ( !context.annotations.isEmpty() ) {
							childDataElements[ 0 ].annotations = context.annotations.getHashes().slice();
						}
						// Queue wrapped meta items only if it's actually possible for us to move them out
						// of the wrapper
						if ( context.inWrapper && context.canCloseWrapper ) {
							ve.batchPush( wrappedMetaItems, childDataElements );
							if ( wrappedWhitespace !== '' ) {
								data.splice( wrappedWhitespaceIndex, wrappedWhitespace.length );
								addWhitespace( childDataElements[ 0 ], 0, wrappedWhitespace );
								nextWhitespace = wrappedWhitespace;
								wrappedWhitespace = '';
							}
						} else {
							outputWrappedMetaItems( 'restore' );
							ve.batchPush( data, childDataElements );
							processNextWhitespace( childDataElements[ 0 ] );
							prevElement = childDataElements[ 0 ];
						}
						// In case we consumed multiple childNodes, adjust i accordingly
						i += childNodes.length - 1;
						break;
					}

					var childIsContent = this.nodeFactory.canNodeSerializeAsContent( childDataElements[ 0 ].type );

					// If childIsContent isn't what we expect, adjust
					if ( !context.expectingContent && childIsContent ) {
						startWrapping();
						prevElement = wrappingParagraph;
					} else if ( context.expectingContent && !childIsContent ) {
						if ( context.inWrapper && context.canCloseWrapper ) {
							stopWrapping();
						} else {
							// Alienate
							modelClass = ve.dm.AlienNode;
							childNodes = modelClass.static.enableAboutGrouping ?
								aboutGroup : [ childNode ];
							childDataElements = this.createDataElements( modelClass, childNodes );
							childIsContent = this.nodeFactory.canNodeSerializeAsContent( childDataElements[ 0 ].type );
						}
					}

					// If we're inserting content into a wrapper, any wrapped whitespace and meta
					// items up until this point are here to stay
					if ( context.inWrapper && childIsContent ) {
						outputWrappedMetaItems( 'restore' );
						wrappedWhitespace = '';
						// Don't record the wrapped whitespace as the child node's outer whitespace
						nextWhitespace = '';
					}

					// Annotate child
					if ( childIsContent && !context.annotations.isEmpty() ) {
						childDataElements[ 0 ].annotations = context.annotations.getHashes().slice();
					}

					// Output child and process children if needed
					if (
						childDataElements.length === 1 &&
						childNodes.length === 1 &&
						this.nodeFactory.canNodeHaveChildren( childDataElements[ 0 ].type ) &&
						!this.nodeFactory.doesNodeHandleOwnChildren( childDataElements[ 0 ].type )
					) {
						// Recursion
						// Opening and closing elements are added by the recursion too
						outputWrappedMetaItems( 'restore' );
						ve.batchPush( data,
							this.getDataFromDomSubtree( childNode, childDataElements[ 0 ],
								new ve.dm.AnnotationSet( this.store )
							)
						);
					} else {
						if ( childDataElements.length === 1 ) {
							childDataElements.push( { type: '/' + childDataElements[ 0 ].type } );
						}
						// Write childDataElements directly
						outputWrappedMetaItems( 'restore' );
						ve.batchPush( data, childDataElements );
					}
					processNextWhitespace( childDataElements[ 0 ] );
					prevElement = childDataElements[ 0 ];

					// In case we consumed multiple childNodes, adjust i accordingly
					i += childNodes.length - 1;
				}
				break;
			case Node.TEXT_NODE:
				var text = childNode.data;
				var matches;
				if ( text === '' ) {
					// Empty text node?!?
					break;
				}
				if ( !context.originallyExpectingContent ) {
					// Strip and store outer whitespace
					if ( this.onlyWhitespaceRegex.test( text ) ) {
						// This text node is whitespace only
						if ( context.inWrapper ) {
							// We're already wrapping, so output this whitespace
							// and store it in wrappedWhitespace (see
							// comment about wrappedWhitespace below)
							wrappedWhitespace = text;
							wrappedWhitespaceIndex = data.length;
							ve.batchPush( data,
								this.constructor.static.getDataContentFromText( wrappedWhitespace, context.annotations )
							);
						} else {
							// We're not in wrapping mode, store this whitespace
							if ( !prevElement ) {
								if ( wrapperElement ) {
									// First child, store as inner
									// whitespace in the parent
									addWhitespace( wrapperElement, 1, text );
								}
								// Else, WTF?!? This is not supposed to
								// happen, but it's not worth
								// throwing an exception over.
							} else {
								addWhitespace( prevElement, 3, text );
							}
							nextWhitespace = text;
							wrappedWhitespace = '';
							outputWrappedMetaItems( 'restore' );
						}
						// We're done, no actual text left to process
						break;
					} else {
						// This text node contains actual text
						// Separate the real text from the whitespace
						// HACK: '.' doesn't match newlines in JS, so use
						// [\s\S] to match any character
						matches = text.match( this.trimWhitespaceRegex );
						if ( !context.inWrapper ) {
							// Wrap the text in a paragraph and output it
							startWrapping();

							// Only store leading whitespace if we just
							// started wrapping
							if ( matches[ 1 ] !== '' ) {
								if ( !prevElement ) {
									if ( wrapperElement ) {
										// First child, store as inner
										// whitespace in the parent
										addWhitespace( wrapperElement, 1, matches[ 1 ] );
									}
									// Else, WTF?!? This is not supposed to
									// happen, but it's not worth
									// throwing an exception over.
								} else {
									addWhitespace( prevElement, 3, matches[ 1 ] );
								}
								addWhitespace( wrappingParagraph, 0, matches[ 1 ] );
							}
						} else {
							outputWrappedMetaItems( 'restore' );
							// We were already wrapping in a paragraph,
							// so the leading whitespace must be output
							ve.batchPush( data,
								this.constructor.static.getDataContentFromText( matches[ 1 ], context.annotations )
							);
						}
						// Output the text sans whitespace
						ve.batchPush( data,
							this.constructor.static.getDataContentFromText( matches[ 2 ], context.annotations )
						);

						// Don't store this in wrappingParagraph.internal.whitespace[3]
						// and nextWhitespace just yet. Instead, store it
						// in wrappedWhitespace. There might be more text
						// nodes after this one, so we output wrappedWhitespace
						// for now and undo that if it turns out this was
						// the last text node. We can't output it later
						// because we have to apply the correct annotations.
						wrappedWhitespace = matches[ 3 ];
						wrappedWhitespaceIndex = data.length;
						ve.batchPush( data,
							this.constructor.static.getDataContentFromText( wrappedWhitespace, context.annotations )
						);
						prevElement = wrappingParagraph;
						break;
					}
				}

				// Strip leading and trailing inner whitespace
				// (but only in non-annotation nodes)
				// and store it so it can be restored later.
				if (
					context.annotations.isEmpty() && i === 0 && wrapperElement &&
					!this.nodeFactory.doesNodeHaveSignificantWhitespace( wrapperElement.type )
				) {
					// Strip leading whitespace from the first child
					matches = text.match( this.leadingWhitespacesRegex );
					if ( matches && matches[ 0 ] !== '' ) {
						addWhitespace( wrapperElement, 1, matches[ 0 ] );
						text = text.slice( matches[ 0 ].length );
					}
				}
				if (
					context.annotations.isEmpty() &&
					i === domElement.childNodes.length - 1 &&
					wrapperElement &&
					!this.nodeFactory.doesNodeHaveSignificantWhitespace( wrapperElement.type )
				) {
					// Strip trailing whitespace from the last child
					matches = text.match( this.trailingWhitespacesRegex );
					if ( matches && matches[ 0 ] !== '' ) {
						addWhitespace( wrapperElement, 2, matches[ 0 ] );
						text = text.slice( 0, text.length - matches[ 0 ].length );
					}
				}

				// Annotate the text and output it
				ve.batchPush( data,
					this.constructor.static.getDataContentFromText( text, context.annotations )
				);
				break;
		}
	}
	// End auto-wrapping of bare content
	if ( context.inWrapper && context.canCloseWrapper ) {
		stopWrapping();
		// HACK: don't set context.inWrapper = false here because it's checked below
		context.inWrapper = true;
	}

	// If we're closing a node that doesn't have any children, but could contain a paragraph,
	// add a paragraph. This prevents things like empty list items
	if ( context.branchType !== 'paragraph' && wrapperElement && data[ data.length - 1 ] === wrapperElement &&
		!context.inWrapper && !this.nodeFactory.canNodeContainContent( context.branchType ) &&
		!this.nodeFactory.isNodeContent( context.branchType ) &&
		this.isValidChildNodeType( 'paragraph' )
	) {
		var wrapperParagraph = { type: 'paragraph', internal: { generated: 'wrapper' } };
		processNextWhitespace( wrapperParagraph );
		data.push( wrapperParagraph );
		data.push( { type: '/paragraph' } );
	}

	// Close element
	if ( wrapperElement ) {
		// Add the whitespace after the last child to the parent as innerPost
		// But don't do this if the parent is empty, because in that case we've already put that
		// whitespace in innerPre
		if ( nextWhitespace !== '' && data[ data.length - 1 ] !== wrapperElement ) {
			addWhitespace( wrapperElement, 2, nextWhitespace );
			nextWhitespace = '';
		}
		data.push( { type: '/' + wrapperElement.type } );
	}
	// Don't return an empty document
	if ( context.branchType === 'document' && isAllInstanceOf( data, ve.dm.MetaItem ) && !annotationSet ) {
		var emptyParagraph = { type: 'paragraph', internal: { generated: 'empty' } };
		processNextWhitespace( emptyParagraph );
		data.push( emptyParagraph );
		data.push( { type: '/paragraph' } );
	}

	this.contextStack.pop();
	return data;
};

/**
 * Get inner whitespace from linear data
 *
 * @param {ve.dm.ElementLinearData} data Linear model data
 * @return {(string|undefined)[]}
 */
ve.dm.Converter.prototype.getInnerWhitespace = function ( data ) {
	var innerWhitespace = new Array( 2 ),
		stack = 0,
		last = data.getLength() - 1;

	var whitespace;
	if ( data.isOpenElementData( 0 ) ) {
		whitespace = ve.getProp( data.getData( 0 ), 'internal', 'whitespace' );
		innerWhitespace[ 0 ] = whitespace ? whitespace[ 0 ] : undefined;
	}
	if ( data.isCloseElementData( last ) ) {
		// Find matching opening tag of the last close tag
		stack++;
		while ( --last ) {
			if ( data.isCloseElementData( last ) ) {
				stack++;
			} else if ( data.isOpenElementData( last ) ) {
				stack--;
				if ( stack === 0 && data.getType( last ) !== 'internalList' ) {
					break;
				}
			}
		}
		whitespace = ve.getProp( data.getData( last ), 'internal', 'whitespace' );
		innerWhitespace[ 1 ] = whitespace ? whitespace[ 3 ] : undefined;
	}
	return innerWhitespace;
};

/**
 * Convert document model to an HTML DOM
 *
 * @param {ve.dm.Document} model Document model
 * @param {number} [mode=PARSER_MODE] Conversion mode, defaults to PARSER_MODE
 * @return {HTMLDocument} Document containing the resulting HTML
 */
ve.dm.Converter.prototype.getDomFromModel = function ( model, mode ) {
	// Backwards compatibility with 'forClipboard' argument
	if ( typeof mode === 'boolean' ) {
		mode = mode ? this.constructor.static.CLIPBOARD_MODE : this.constructor.static.PARSER_MODE;
	}
	mode = mode || this.constructor.static.PARSER_MODE;

	var doc = ve.createDocumentFromHtml( '' );
	this.getDomSubtreeFromModel( model, doc.body, mode );

	return doc;
};

/**
 * Convert model node to an HTML DOM
 *
 * @param {ve.dm.Node} node Model node
 * @param {number} [mode=PARSER_MODE] Conversion mode, defaults to PARSER_MODE
 * @return {HTMLDocument} Document containing the resulting HTML
 */
ve.dm.Converter.prototype.getDomFromNode = function ( node, mode ) {
	// Backwards compatibility with 'forClipboard' argument
	if ( typeof mode === 'boolean' ) {
		mode = mode ? this.constructor.static.CLIPBOARD_MODE : this.constructor.static.PARSER_MODE;
	}
	mode = mode || this.constructor.static.PARSER_MODE;
	return this.getDomFromModel(
		node.getDocument().shallowCloneFromRange( node.isInternal() ? node.getRange() : node.getOuterRange() ),
		mode
	);
};

/**
 * Convert document model to an HTML DOM subtree and add it to a container element.
 *
 * @param {ve.dm.Document} model Document model
 * @param {HTMLElement} container DOM element to add the generated elements to. Should be empty.
 * @param {number} [mode=PARSER_MODE] Conversion mode, defaults to PARSER_MODE
 */
ve.dm.Converter.prototype.getDomSubtreeFromModel = function ( model, container, mode ) {
	if ( typeof mode === 'boolean' ) {
		mode = mode ? this.constructor.static.CLIPBOARD_MODE : this.constructor.static.PARSER_MODE;
	}
	mode = mode || this.constructor.static.PARSER_MODE;
	// Set up the converter state
	this.documentData = model.getFullData( undefined, 'roundTrip' );
	this.store = model.getStore();
	this.internalList = model.getInternalList();
	// Internal list of the doc this was cloned from, or itself if not cloned
	this.originalDocInternalList = model.getOriginalDocument() ? model.getOriginalDocument().getInternalList() : this.internalList;
	this.mode = mode;

	this.getDomSubtreeFromData( this.documentData, container, model.getInnerWhitespace() );

	// Clear the state
	this.documentData = null;
	this.store = null;
	this.internalList = null;
	this.originalDocInternalList = null;
	this.mode = null;
};

/**
 * Convert linear model data to an HTML DOM subtree and add it to a container element.
 *
 * @param {Array} data Linear model data
 * @param {HTMLElement} container DOM element to add the generated elements to. Should be empty.
 * @param {(string|undefined)[]} [innerWhitespace] Inner whitespace if the container is the body
 * @throws Unbalanced data: looking for closing /type
 */
ve.dm.Converter.prototype.getDomSubtreeFromData = function ( data, container, innerWhitespace ) {
	var i, text, annotatedDomElements, annotatedDomElementStack,
		whitespaceHtmlChars = ve.visibleWhitespaceCharacters,
		isForPreview = this.isForPreview(),
		dataLen = data.length,
		converter = this,
		doc = container.ownerDocument,
		domElement = container;

	// TODO this whole function should be rewritten with a domElementStack and ascend() and
	// descend() functions, to build the whole DOM bottom-up rather than top-down. That would make
	// unwrapping easier and will hopefully result in fewer DOM operations.

	function openAnnotation() {
		// Add text if needed
		if ( text.length > 0 ) {
			annotatedDomElements.push( doc.createTextNode( text ) );
			text = '';
		}
		annotatedDomElements = [];
		annotatedDomElementStack.push( annotatedDomElements );
	}

	function closeAnnotation( annotation ) {
		var leading = '',
			trailing = '',
			originalDomElements = annotation.getOriginalDomElements( converter.store ),
			origElementText = originalDomElements[ 0 ] &&
				originalDomElements[ 0 ].textContent ||
				'';

		// Add text if needed
		if ( text.length > 0 ) {
			annotatedDomElements.push( doc.createTextNode( text ) );
			text = '';
		}

		var annotatedChildDomElements = annotatedDomElementStack.pop();
		annotatedDomElements = annotatedDomElementStack[ annotatedDomElementStack.length - 1 ];

		// HACK: Move any leading and trailing whitespace out of the annotation, but only if the
		// annotation didn't originally have leading/trailing whitespace
		if ( annotation.constructor.static.trimWhitespace ) {
			var matches;
			var first = annotatedChildDomElements[ 0 ];
			while (
				first &&
				first.nodeType === Node.TEXT_NODE &&
				( matches = first.data.match( converter.leadingWhitespacesRegex ) ) &&
				!converter.leadingWhitespaceRegex.test( origElementText )
			) {
				leading += matches[ 0 ];
				first.deleteData( 0, matches[ 0 ].length );
				if ( first.data.length !== 0 ) {
					break;
				}
				// Remove empty text node
				annotatedChildDomElements.shift();
				// Process next text node to see if it also has whitespace
				first = annotatedChildDomElements[ 0 ];
			}
			var last = annotatedChildDomElements[ annotatedChildDomElements.length - 1 ];
			while (
				last &&
				last.nodeType === Node.TEXT_NODE &&
				( matches = last.data.match( converter.trailingWhitespacesRegex ) ) &&
				!converter.trailingWhitespaceRegex.test( origElementText )
			) {
				trailing = matches[ 0 ] + trailing;
				last.deleteData( last.data.length - matches[ 0 ].length, matches[ 0 ].length );
				if ( last.data.length !== 0 ) {
					break;
				}
				// Remove empty text node
				annotatedChildDomElements.pop();
				// Process next text node to see if it also has whitespace
				last = annotatedChildDomElements[ annotatedChildDomElements.length - 1 ];
			}
		}

		var annotationElement;
		if ( annotatedChildDomElements.length ) {
			annotationElement = converter.getDomElementsFromDataElement(
				annotation.getElement(), doc, annotatedChildDomElements
			)[ 0 ];
		}

		if ( leading ) {
			annotatedDomElements.push( doc.createTextNode( leading ) );
		}
		var n, len;
		if ( annotationElement ) {
			for ( n = 0, len = annotatedChildDomElements.length; n < len; n++ ) {
				annotationElement.appendChild( annotatedChildDomElements[ n ] );
			}
			annotatedDomElements.push( annotationElement );
		} else {
			for ( n = 0, len = annotatedChildDomElements.length; n < len; n++ ) {
				annotatedDomElements.push( annotatedChildDomElements[ n ] );
			}
		}
		if ( trailing ) {
			annotatedDomElements.push( doc.createTextNode( trailing ) );
		}
	}

	function findEndOfNode( k ) {
		var n, depth;
		for ( n = k + 1, depth = 1; n < dataLen && depth > 0; n++ ) {
			if ( data[ n ].type ) {
				depth += data[ n ].type.charAt( 0 ) === '/' ? -1 : 1;
			}
		}
		if ( depth !== 0 ) {
			throw new Error( 'Unbalanced data: ' + depth + ' element(s) left open.' );
		}
		return n;
	}

	function getDataElementOrSlice() {
		var dataSlice;
		if (
			ve.dm.nodeFactory.lookup( data[ i ].type ) &&
			ve.dm.nodeFactory.doesNodeHandleOwnChildren( data[ i ].type )
		) {
			dataSlice = data.slice( i, findEndOfNode( i ) );
		} else {
			dataSlice = data[ i ];
		}
		return dataSlice;
	}

	function getChar( char ) {
		if (
			isForPreview &&
			!domElement.veHasSignificantWhitespace &&
			Object.prototype.hasOwnProperty.call( whitespaceHtmlChars, char )
		) {
			char = whitespaceHtmlChars[ char ];
		}
		return char;
	}

	var dataElementOrSlice, childDomElements, parentDomElement, j;
	var annotationStack = new ve.dm.AnnotationSet( this.store );

	for ( i = 0; i < dataLen; i++ ) {
		if ( typeof data[ i ] === 'string' ) {
			// Text
			text = '';
			var isStart = i > 0 &&
				ve.dm.LinearData.static.isOpenElementData( data[ i - 1 ] ) &&
				!ve.dm.nodeFactory.doesNodeHaveSignificantWhitespace(
					ve.dm.LinearData.static.getType( data[ i - 1 ] )
				);
			// Continue forward as far as the plain text goes
			while ( typeof data[ i ] === 'string' ) {
				// HACK: Skip over leading whitespace (T53462/T142132) in non-whitespace-preserving tags
				// This should possibly be handled by Parsoid or in the UI.
				if ( !( isStart && this.onlyWhitespaceRegex.test( data[ i ] ) && this.isForParser() ) ) {
					text += getChar( data[ i ] );
					isStart = false;
				}
				i++;
			}
			// i points to the first non-text thing, go back one so we don't skip this later
			i--;
			// Add text
			if ( text.length > 0 ) {
				domElement.appendChild( doc.createTextNode( text ) );
			}
		} else if (
			Array.isArray( data[ i ] ) ||
			(
				data[ i ].annotations !== undefined &&
				this.nodeFactory.canNodeSerializeAsContent( data[ i ].type )
			)
		) {
			// Annotated text, nodes or meta
			text = '';
			annotatedDomElements = [];
			annotatedDomElementStack = [ annotatedDomElements ];
			while (
				data[ i ] !== undefined && (
					Array.isArray( data[ i ] ) ||
					(
						data[ i ].annotations !== undefined &&
						this.nodeFactory.canNodeSerializeAsContent( data[ i ].type )
					)
				)
			) {
				var annotations = new ve.dm.AnnotationSet(
					this.store, data[ i ].annotations || data[ i ][ 1 ]
				);
				this.constructor.static.openAndCloseAnnotations( annotationStack, annotations,
					openAnnotation, closeAnnotation
				);

				if ( data[ i ].annotations === undefined ) {
					// Annotated text
					text += getChar( data[ i ][ 0 ] );
				} else {
					// Annotated node
					// Add text if needed
					if ( text.length > 0 ) {
						annotatedDomElements.push( doc.createTextNode( text ) );
						text = '';
					}
					// Insert the elements
					dataElementOrSlice = getDataElementOrSlice();
					childDomElements = this.getDomElementsFromDataElement( dataElementOrSlice, doc );
					for ( j = 0; j < childDomElements.length; j++ ) {
						annotatedDomElements.push( childDomElements[ j ] );
					}
					if ( Array.isArray( dataElementOrSlice ) ) {
						i += dataElementOrSlice.length - 1;
					} else {
						i++; // Skip the closing
					}
				}
				i++;
			}
			// We're now at the first non-annotated thing, go back one so we don't skip this later
			i--;

			// Add any gathered text
			if ( text.length > 0 ) {
				annotatedDomElements.push( doc.createTextNode( text ) );
				text = '';
			}
			// Close any remaining annotations
			this.constructor.static.openAndCloseAnnotations( annotationStack, new ve.dm.AnnotationSet( this.store ),
				openAnnotation, closeAnnotation
			);
			// Put the annotated nodes in the DOM
			for ( j = 0; j < annotatedDomElements.length; j++ ) {
				domElement.appendChild( annotatedDomElements[ j ] );
			}
		} else if ( data[ i ].type !== undefined ) {
			var dataElement = data[ i ];
			var isContentNode, ours, theirs, textNode;
			// Element
			if ( dataElement.type.charAt( 0 ) === '/' ) {
				// Close element
				parentDomElement = domElement.parentNode;
				var type = data[ i ].type.slice( 1 );
				isContentNode = this.nodeFactory.isNodeContent( type );
				// Process whitespace
				// whitespace = [ outerPre, innerPre, innerPost, outerPost ]
				var oldLastOuterPost = parentDomElement.lastOuterPost;
				if (
					!isContentNode &&
					domElement.veInternal &&
					domElement.veInternal.whitespace
				) {
					// Process inner whitespace. innerPre is for sure legitimate
					// whitespace that should be inserted; if it was a duplicate
					// of our child's outerPre, we would have cleared it.
					var pre = domElement.veInternal.whitespace[ 1 ];
					if ( pre ) {
						if (
							domElement.firstChild &&
							domElement.firstChild.nodeType === Node.TEXT_NODE
						) {
							// First child is a TextNode, prepend to it
							domElement.firstChild.insertData( 0, pre );
						} else {
							// Prepend a TextNode
							textNode = doc.createTextNode( pre );
							textNode.veIsWhitespace = true;
							domElement.insertBefore(
								textNode,
								domElement.firstChild
							);
						}
					}
					var lastChild = domElement.veInternal.childDomElements ?
						domElement.veInternal
							.childDomElements[ domElement.veInternal.childDomElements.length - 1 ]
							.lastChild :
						domElement.lastChild;
					ours = domElement.veInternal.whitespace[ 2 ];
					if ( domElement.lastOuterPost === undefined ) {
						// This node didn't have any structural children
						// (i.e. it's a content-containing node), so there's
						// nothing to check innerPost against
						theirs = ours;
					} else {
						theirs = domElement.lastOuterPost;
					}
					if ( ours && ours === theirs ) {
						if ( lastChild && lastChild.nodeType === Node.TEXT_NODE ) {
							// Last child is a TextNode, append to it
							domElement.lastChild.appendData( ours );
						} else {
							// Append a TextNode
							textNode = doc.createTextNode( ours );
							textNode.veIsWhitespace = true;
							domElement.appendChild(
								textNode
							);
						}
					}
					// Tell the parent about our outerPost
					parentDomElement.lastOuterPost = domElement.veInternal.whitespace[ 3 ] || '';
				} else if ( !isContentNode ) {
					// Use empty string, because undefined means there were no
					// structural children
					parentDomElement.lastOuterPost = '';
				}
				// else don't touch lastOuterPost

				// Logic to unwrap empty & wrapper nodes.
				// It would be nicer if we could avoid generating in the first
				// place, but then remembering where we have to skip ascending
				// to the parent would be tricky.
				var doUnwrap = false;
				if ( domElement.veInternal ) {
					switch ( domElement.veInternal.generated ) {
						case 'slug':
							// 'slug' elements - remove if they are still empty
							if ( domElement.childNodes.length === 0 ) {
								doUnwrap = true;
							}
							break;
						case 'empty':
							// 'empty' elements - first ensure they are actually empty
							if (
								domElement.childNodes.length === 0 &&
								(
									// then check that we are the last child
									// before unwrapping (and therefore destroying)
									data[ i + 1 ] === undefined ||
									data[ i + 1 ].type.charAt( 0 ) === '/' ||
									// Document ends when we encounter the internal list
									(
										data[ i + 1 ].type &&
										this.nodeFactory.isNodeInternal( data[ i + 1 ].type )
									)
								)
							) {
								doUnwrap = true;
							}
							break;
						case 'wrapper':
							// 'wrapper' elements - ensure there is a block level
							// element between this element and the previous sibling
							// wrapper or parent node
							doUnwrap = true;
							var previousSiblings = domElement.parentNode.childNodes;
							// Note: previousSiblings includes the current element
							// so we only go up to length - 2
							for ( j = previousSiblings.length - 2; j >= 0; j-- ) {
								var sibling = previousSiblings[ j ];
								if ( ve.isBlockElement( sibling ) ) {
									// Stop searching early when we get to a block element.
									break;
								}
								// If we find content, don't unwrap.
								if (
									// Text node content (non-whitespace)
									( sibling.nodeType === Node.TEXT_NODE && !sibling.veIsWhitespace ) ||
									// Inline content tag
									( sibling.nodeType === Node.ELEMENT_NODE && sibling.tagName !== 'META' && sibling.tagName !== 'LINK' )
								) {
									// we've found unwrapped content so don't unwrap
									doUnwrap = false;
									break;
								}
							}
							break;
					}
				}
				if ( doUnwrap ) {
					if ( domElement.childNodes.length ) {
						// If domElement has children, append them to parentDomElement
						while ( domElement.firstChild ) {
							parentDomElement.insertBefore(
								domElement.firstChild,
								domElement
							);
						}
					} else {
						// If domElement has no children, it's as if it was never there at all,
						// so set lastOuterPost back to what it was, except that we need to
						// change undefined to '' , since undefined means there were no children.
						parentDomElement.lastOuterPost = oldLastOuterPost || '';
					}
					parentDomElement.removeChild( domElement );
				}

				delete domElement.veInternal;
				delete domElement.lastOuterPost;
				// Ascend to parent node, except if this is an internal node
				// TODO: It's not covered with unit tests.
				if ( !ve.dm.nodeFactory.lookup( type ) || !ve.dm.nodeFactory.isNodeInternal( type ) ) {
					domElement = parentDomElement;
				}
			} else {
				// Create node from data
				if ( this.nodeFactory.isNodeInternal( data[ i ].type ) ) {
					// Reached the internal list, finish
					break;
				}
				isContentNode = this.nodeFactory.isNodeContent( data[ i ].type );

				dataElementOrSlice = getDataElementOrSlice();
				childDomElements = this.getDomElementsFromDataElement( dataElementOrSlice, doc );
				if ( childDomElements && !childDomElements.length ) {
					// Support toDomElements returning an empty array
					i = findEndOfNode( i ) - 1;
					continue;
				} else if ( childDomElements ) {
					// Add clone of internal data; we use a clone rather than a reference because
					// we modify .veInternal.whitespace[1] in some cases
					childDomElements[ 0 ].veInternal = ve.extendObject(
						{ childDomElements: childDomElements },
						dataElement.internal ? ve.copy( dataElement.internal ) : {}
					);
					// Add elements
					for ( j = 0; j < childDomElements.length; j++ ) {
						domElement.appendChild( childDomElements[ j ] );
					}
					// Descend into the first child node
					parentDomElement = domElement;
					domElement = childDomElements[ 0 ];

					// Process outer whitespace
					// Every piece of outer whitespace is duplicated somewhere:
					// each node's outerPost is duplicated as the next node's
					// outerPre, the first node's outerPre is the parent's
					// innerPre, and the last node's outerPost is the parent's
					// innerPost. For each piece of whitespace, we verify that
					// the duplicate matches. If it doesn't, we take that to
					// mean the user has messed with it and don't output any
					// whitespace.
					if ( domElement.veInternal && domElement.veInternal.whitespace ) {
						// Process this node's outerPre
						ours = domElement.veInternal.whitespace[ 0 ];
						theirs = undefined;
						if ( domElement.previousSibling ) {
							// Get previous sibling's outerPost
							theirs = parentDomElement.lastOuterPost;
						} else if ( parentDomElement === container ) {
							// outerPre of the very first node in the document, check against body innerWhitespace
							theirs = innerWhitespace ? innerWhitespace[ 0 ] : ours;
						} else {
							// First child, get parent's innerPre
							if (
								parentDomElement.veInternal &&
								parentDomElement.veInternal.whitespace
							) {
								theirs = parentDomElement.veInternal.whitespace[ 1 ];
								// Clear parent's innerPre so it's not used again
								parentDomElement.veInternal.whitespace[ 1 ] = undefined;
							}
							// else theirs=undefined
						}
						if ( ours && ours === theirs ) {
							// Matches the duplicate, insert a TextNode
							textNode = doc.createTextNode( ours );
							textNode.veIsWhitespace = true;
							parentDomElement.insertBefore(
								textNode,
								domElement
							);
						}
					} else if (
						!isContentNode &&
						!domElement.previousSibling &&
						parentDomElement.veInternal &&
						parentDomElement.veInternal.whitespace
					) {
						// The parent's innerPre should not be used, because it doesn't match
						// outerPre (since we didn't have any whitespace set at all).
						// Except if this is a content node, because content nodes
						// don't have whitespace annotated on them *sigh*
						parentDomElement.veInternal.whitespace[ 1 ] = undefined;
					}
				}

				if ( Array.isArray( dataElementOrSlice ) ) {
					i += dataElementOrSlice.length - 2;
				} else if ( childDomElements && childDomElements.length && childDomElements[ 0 ].handledOwnChildren ) {
					i = findEndOfNode( i ) - 2;
				}
			}
		}
	}
	// Check outerPost whitespace of the very last node against body innerWhitespace
	if (
		container.lastOuterPost !== undefined &&
		( !innerWhitespace || container.lastOuterPost === innerWhitespace[ 1 ] )
	) {
		if ( container.lastChild && container.lastChild.nodeType === Node.TEXT_NODE ) {
			// Last child is a TextNode, append to it
			container.lastChild.appendData( container.lastOuterPost );
		} else if ( container.lastOuterPost.length > 0 ) {
			// Append a TextNode
			container.appendChild( doc.createTextNode( container.lastOuterPost ) );
		}
		delete container.lastOuterPost;
	}
	// Get rid of excess text nodes
	container.normalize();
};

/* Initialization */

ve.dm.converter = new ve.dm.Converter( ve.dm.modelRegistry, ve.dm.nodeFactory, ve.dm.annotationFactory );
