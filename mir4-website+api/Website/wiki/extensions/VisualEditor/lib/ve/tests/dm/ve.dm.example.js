/*!
 * VisualEditor DataModel example data sets.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * @class
 * @singleton
 * @ignore
 */
ve.dm.example = {};

/* Methods */

ve.dm.example.singleLine = function ( strings, ...values ) {
	// Concatenate
	let output = '';
	for ( let i = 0; i < values.length; i++ ) {
		output += strings[ i ] + values[ i ];
	}
	output += strings[ values.length ];

	// Remove line-leading indentation (but not spaces)
	return output.replace( /\n\t+/g, '' ).trim();
};

/**
 * Convert arrays of shorthand annotations in a data fragment to AnnotationSets with real
 * annotation objects, and wraps the result in a ve.dm.ElementLinearData object.
 *
 * Shorthand notation for annotations is:
 * [ 'a', [ { type: 'link', attributes: { href: '…' } ] ]
 *
 * The actual storage format has an instance of ve.dm.LinkAnnotation instead of the plain object,
 * and an instance of ve.dm.AnnotationSet instead of the array.
 *
 * @param {Array} data Linear model data
 * @param {ve.dm.HashValueStore} [store] Hash-value store to use, creates one if undefined
 * @return {ve.dm.ElementLinearData} Linear data store
 * @throws {Error} Example data passed to preprocessAnnotations by reference
 */
ve.dm.example.preprocessAnnotations = function ( data, store ) {
	var i;

	// Sanity check to make sure ve.dm.example data has not been passed in
	// by reference. Always use ve#copy.
	for ( i in ve.dm.example ) {
		if ( data === ve.dm.example[ i ] ) {
			throw new Error( 'Example data passed to preprocessAnnotations by reference' );
		}
	}

	function preprocessOriginalDomElements( el ) {
		var originalDomElements = el.originalDomElements;
		if ( originalDomElements ) {
			el.originalDomElementsHash = store.hash( originalDomElements, originalDomElements.map( ve.getNodeHtml ).join( '' ) );
			delete el.originalDomElements;
		}
	}

	store = store || new ve.dm.HashValueStore();
	for ( i = 0; i < data.length; i++ ) {
		var key = data[ i ].annotations ? 'annotations' : 1;
		// Check for shorthand annotation objects in array
		if ( Array.isArray( data[ i ][ key ] ) && data[ i ][ key ][ 0 ].type ) {
			data[ i ][ key ].forEach( preprocessOriginalDomElements );
			data[ i ][ key ] = ve.dm.example.createAnnotationSet( store, data[ i ][ key ] ).getHashes();
		}
		preprocessOriginalDomElements( data[ i ] );
	}
	return new ve.dm.ElementLinearData( store, data );
};

/**
 * Convert real data back to shorthand notation. See #preprocessAnnotations.
 *
 * Any annotation that has originalDomElements will be shallow-cloned and have
 * originalDomElements removed.
 *
 * @param {Array} data Linear model data. Will be modified.
 * @param {ve.dm.HashValueStore} store Hash-value store to resolve annotations in
 * @param {boolean} [preserveDomElements] Preserve original DOM elements
 * @return {Array} The given `data` parameter.
 */
ve.dm.example.postprocessAnnotations = function ( data, store, preserveDomElements ) {
	for ( var i = 0; i < data.length; i++ ) {
		var key = data[ i ].annotations ? 'annotations' : 1;
		if ( Array.isArray( data[ i ][ key ] ) ) {
			data[ i ] = ve.extendObject( Array.isArray( data[ i ] ) ? [] : {}, data[ i ] );
			data[ i ][ key ] = new ve.dm.AnnotationSet( store, data[ i ][ key ] ).get();
			for ( var j = 0; j < data[ i ][ key ].length; j++ ) {
				data[ i ][ key ][ j ] = data[ i ][ key ][ j ].element;
				if ( !preserveDomElements && data[ i ][ key ][ j ].originalDomElementsHash !== undefined ) {
					// Make a shallow clone and remove originalDomElements from it
					data[ i ][ key ][ j ] = ve.extendObject( {}, data[ i ][ key ][ j ] );
					delete data[ i ][ key ][ j ].originalDomElementsHash;
				}
			}
		}
	}
	return data;
};

/**
 * Create an annotation object from shorthand notation.
 *
 * @param {Object} annotation Plain object with type and attributes properties
 * @param {ve.dm.HashValueStore} [store] Hash value store
 * @return {ve.dm.Annotation} Instance of the right ve.dm.Annotation subclass
 */
ve.dm.example.createAnnotation = function ( annotation, store ) {
	return ve.dm.annotationFactory.createFromElement( annotation, store );
};

/**
 * Create an AnnotationSet from an array of shorthand annotations.
 *
 * This calls ve.dm.example.createAnnotation() for each element and puts the result in an
 * AnnotationSet.
 *
 * @param {ve.dm.HashValueStore} store Hash-value store
 * @param {Object[]} annotations Array of annotations in shorthand format
 * @return {ve.dm.AnnotationSet}
 */
ve.dm.example.createAnnotationSet = function ( store, annotations ) {
	for ( var i = 0; i < annotations.length; i++ ) {
		annotations[ i ] = ve.dm.example.createAnnotation( annotations[ i ], store );
	}
	return new ve.dm.AnnotationSet( store, store.hashAll( annotations ) );
};

/* Some common annotations in shorthand format */
ve.dm.example.bold = { type: 'textStyle/bold', attributes: { nodeName: 'b' } };
ve.dm.example.italic = { type: 'textStyle/italic', attributes: { nodeName: 'i' } };
ve.dm.example.underline = { type: 'textStyle/underline', attributes: { nodeName: 'u' } };
ve.dm.example.span = { type: 'textStyle/span', attributes: { nodeName: 'span' } };
ve.dm.example.big = { type: 'textStyle/big', attributes: { nodeName: 'big' } };
ve.dm.example.code = { type: 'textStyle/code', attributes: { nodeName: 'code' } };
ve.dm.example.tt = { type: 'textStyle/code', attributes: { nodeName: 'tt' } };
ve.dm.example.strong = { type: 'textStyle/bold', attributes: { nodeName: 'strong' } };
ve.dm.example.link = function ( href ) {
	return { type: 'link', attributes: { href: href } };
};
ve.dm.example.language = function ( lang, dir, nodeName ) {
	return { type: 'meta/language', attributes: { nodeName: nodeName || 'span', lang: lang, dir: dir } };
};
ve.dm.example.boldWithStyle = ve.extendObject( {}, ve.dm.example.bold, { originalDomElements: $.parseHTML( '<b style="color:red;" />' ) } );

ve.dm.example.annHash = function ( tagName ) {
	var ann = ve.copy( {
		b: ve.dm.example.bold,
		i: ve.dm.example.italic,
		u: ve.dm.example.underline
	}[ tagName ] );

	ann.originalDomElementsHash = ve.dm.HashValueStore.prototype.hashOfValue( null, '<' + tagName + '></' + tagName + '>' );
	return ve.dm.HashValueStore.prototype.hashOfValue( ann );
};

// hash = store.hashOfValue( ve.dm.example.bold )
ve.dm.example.boldHash = 'h49981eab0f8056ff';
ve.dm.example.italicHash = 'hefd27ef3bf2041dd';
ve.dm.example.underlineHash = 'hf214c680fbc361da';
ve.dm.example.strongHash = 'ha5aaf526d1c3af54';

ve.dm.example.inlineSlug = '<span class="ve-ce-branchNode-slug ve-ce-branchNode-inlineSlug"></span>';
ve.dm.example.blockSlug = '<div class="ve-ce-branchNode-slug ve-ce-branchNode-blockSlug"></div>';

ve.dm.example.ceParagraph = '<p class="ve-ce-branchNode ve-ce-contentBranchNode ve-ce-paragraphNode">';
ve.dm.example.ceWrapperParagraph = '<p class="ve-ce-branchNode ve-ce-contentBranchNode ve-ce-paragraphNode ve-ce-generated-wrapper">';

ve.dm.example.textStyleClasses = 've-ce-annotation ve-ce-textStyleAnnotation';

ve.dm.example.commentNodePreview = function ( text ) {
	return '<span class="ve-ce-leafNode ve-ce-focusableNode ve-ce-commentNode ve-ce-focusableNode-invisible" contenteditable="false" title="' + text + '">' +
		( new OO.ui.ButtonWidget( {
			// Copied from ve.ce.FocusableNode#createInvisibleIcon
			classes: [ 've-ce-focusableNode-invisibleIcon' ],
			framed: false,
			tabIndex: null,
			icon: 'notice'
		} ).setLabel( text ) ).$element[ 0 ].outerHTML +
	'</span>';
};

/**
 * Creates a document from example data.
 *
 * Defaults to ve.dm.example.data if no name is supplied.
 *
 * @param {string} [name='data'] Named element of ve.dm.example
 * @param {ve.dm.HashValueStore} [store] A specific hash-value store to use, optionally.
 * @param {string} [base=ve.dm.example.baseUri] Base URL to use for the document
 * @return {ve.dm.Document}
 * @throws {Error} Example data not found
 */
ve.dm.example.createExampleDocument = function ( name, store, base ) {
	return ve.dm.example.createExampleDocumentFromObject( name, store, ve.dm.example, base );
};

/**
 * Helper function for ve.dm.createExampleDocument.
 *
 * @param {string} [name='data'] Named element of ve.dm.example
 * @param {ve.dm.HashValueStore} [store] A specific hash-value store to use, optionally.
 * @param {Object} object Collection of test documents, keyed by name
 * @param {string} [base=ve.dm.example.baseUri] Base URL to use for the document
 * @return {ve.dm.Document}
 * @throws {Error} Example data not found
 */
ve.dm.example.createExampleDocumentFromObject = function ( name, store, object, base ) {
	name = name || 'data';
	if ( object[ name ] === undefined ) {
		throw new Error( 'Example data \'' + name + '\' not found' );
	}
	return ve.dm.example.createExampleDocumentFromData( object[ name ], store, base );
};

ve.dm.example.createExampleDocumentFromData = function ( data, store, base ) {
	store = store || new ve.dm.HashValueStore();
	base = base || ve.dm.example.baseUri;
	var doc = new ve.dm.Document(
		ve.dm.example.preprocessAnnotations( ve.copy( data ), store )
	);
	// HACK internalList isn't populated when creating a document from data
	if ( data.internalItems ) {
		for ( var i = 0; i < data.internalItems.length; i++ ) {
			doc.internalList.queueItemHtml(
				data.internalItems[ i ].group,
				data.internalItems[ i ].key,
				data.internalItems[ i ].body
			);
		}
	}
	if ( data.internalListNextUniqueNumber ) {
		doc.setStorage( 'internallist-counter', data.internalListNextUniqueNumber );
	}
	doc.buildNodeTree();
	ve.fixBase( doc.getHtmlDocument(), doc.getHtmlDocument(), base );
	return doc;
};

/**
 * Looks up a value in a node tree.
 *
 * @param {ve.Node} root Root node to lookup from
 * @param {...number} [paths] Index path
 * @return {ve.Node} Node at given path
 */
ve.dm.example.lookupNode = function ( root ) {
	var node = root;
	for ( var i = 1; i < arguments.length; i++ ) {
		node = node.children[ arguments[ i ] ];
	}
	return node;
};

ve.dm.example.imgSrc = 'https://upload.wikimedia.org/wikipedia/commons/b/b3/Wikipedia-logo-v2-en.svg';

ve.dm.example.baseUri = 'http://example.org';

ve.dm.example.image = {
	html: '<img src="' + ve.dm.example.imgSrc + '" alt="Example" width="100" height="50">',
	data: {
		type: 'inlineImage',
		attributes: {
			src: ve.dm.example.imgSrc,
			alt: 'Example',
			width: 100,
			height: 50
		}
	}
};

ve.dm.example.blockImage = {
	html: ve.dm.example.singleLine`
		<figure class="ve-align-right"><img src="${ ve.dm.example.imgSrc }" alt="Example" width="100" height="50">
			<figcaption>foo <b style="color:red;">red</b></figcaption>
		</figure>
	`,
	data: [
		{
			type: 'blockImage',
			attributes: {
				src: ve.dm.example.imgSrc,
				alt: 'Example',
				width: 100,
				height: 50,
				originalClasses: 've-align-right',
				unrecognizedClasses: [],
				align: 'right'
			}
		},
		{ type: 'imageCaption' },
		{ type: 'paragraph', internal: { generated: 'wrapper' } },
		'f', 'o', 'o', ' ',
		[ 'r', [ ve.dm.example.boldWithStyle ] ],
		[ 'e', [ ve.dm.example.boldWithStyle ] ],
		[ 'd', [ ve.dm.example.boldWithStyle ] ],
		{ type: '/paragraph' },
		{ type: '/imageCaption' },
		{ type: '/blockImage' }
	],
	ceHtml: ve.dm.example.singleLine`
		<figure class="ve-ce-branchNode ve-ce-focusableNode ve-ce-imageNode ve-ce-blockImageNode" contenteditable="false">
			<img src="${ ve.dm.example.imgSrc }" alt="Example" style="width: 100px; height: 50px;">
			<figcaption class="ve-ce-branchNode ve-ce-activeNode" contenteditable="true" spellcheck="true">
				${ ve.dm.example.ceWrapperParagraph }
					foo
					 <b style="color:red;" class="${ ve.dm.example.textStyleClasses } ve-ce-boldAnnotation">red</b>
				</p>
			</figcaption>
		</figure>
	`
};

/**
 * Serialized HTML.
 *
 * This is what the parser will emit.
 * TODO remove some of the <p>s here to test automatic wrapping
 */
ve.dm.example.html = ve.dm.example.singleLine`
	<h1>a<b>b</b><i>c</i></h1>
	<table>
		${ /* Implicit <tbody> */'' }
		<tr>
			<td>
				<p>d</p>
				<ul>
					<li>
						<p>e</p>
						<ul>
							<li>
								<p>f</p>
							</li>
						</ul>
					</li>
				</ul>
				<ol>
					<li>
						<p>g</p>
					</li>
				</ol>
			</td>
		</tr>
	</table>
	<pre>h${ ve.dm.example.image.html }i</pre>
	<dl>
		<dt>
			<p>j</p>
		</dt>
		<dd>
			<p>k</p>
		</dd>
	</dl>
	<p>l</p>
	<p>m</p>
`;

/**
 * The offset path of the result of getNodeAndOffset for each offset
 *
 * @see ve.getOffsetPath
 */
ve.dm.example.offsetPaths = [
	[ 0, 0, 0 ],
	[ 0, 0, 0 ],
	[ 0, 0, 1 ],
	[ 0, 1, 0, 1 ],
	[ 0, 2, 0, 1 ],
	[ 0, 2, 0, 1 ],
	[ 0, 2, 0, 1 ],
	[ 0, 2, 0, 1 ],
	[ 0, 2, 0, 1 ],
	[ 0, 2, 0, 1 ],
	// 10
	[ 1, 0, 0, 0, 0, 0, 0 ],
	[ 1, 0, 0, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 1, 0, 0, 0, 0 ],
	[ 1, 0, 0, 0, 1, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 1, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 1, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 1, 0, 0, 0, 1 ],
	// 20
	[ 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0 ],
	[ 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 1, 0, 1, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 2, 0, 0, 0, 0 ],
	// 30
	[ 1, 0, 0, 0, 2, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 2, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 2, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 2, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 2, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 2, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 2, 0, 0, 0, 1 ],
	[ 1, 0, 0, 0, 2, 0, 0, 0, 1 ],
	[ 2, 0, 0 ],
	[ 2, 0, 1 ],
	// 40
	null,
	[ 2, 2, 0 ],
	[ 2, 2, 1 ],
	[ 2, 2, 1 ],
	[ 2, 2, 1 ],
	[ 2, 2, 1 ],
	[ 3, 0, 0, 0, 0 ],
	[ 3, 0, 0, 0, 1 ],
	[ 3, 0, 0, 0, 1 ],
	[ 3, 0, 0, 0, 1 ],
	// 50
	[ 3, 0, 0, 0, 1 ],
	[ 3, 1, 0, 0, 0 ],
	[ 3, 1, 0, 0, 1 ],
	[ 3, 1, 0, 0, 1 ],
	[ 3, 1, 0, 0, 1 ],
	[ 3, 1, 0, 0, 1 ],
	[ 4, 0, 0 ],
	[ 4, 0, 1 ],
	[ 4, 0, 1 ],
	[ 5, 0, 0 ],
	// 60
	[ 5, 0, 1 ]
];

/*
 * Linear data.
 *
 * This is what we convert serialized HTML from the parser into so we can work with it more easily.
 *
 * There are three types of components in content data:
 *
 *     {string} Plain text character
 *
 *     {Array} Annotated character
 *         0: {string} Character
 *         1: {Object} List of references to immutable annotation objects, keyed by JSON
 *            serializations of their values (hashes)
 *
 *     {Object} Opening or closing structural element
 *         type: {string} Symbolic node type name, if closing element first character will be "/"
 *         [attributes]: {Object} List of symbolic attribute name and literal value pairs
 */
ve.dm.example.data = [
	//  0 - Beginning of heading
	{ type: 'heading', attributes: { level: 1 } },
	//  1 - Plain "a"
	'a',
	//  2 - Bold "b"
	[ 'b', [ ve.dm.example.bold ] ],
	//  3 - Italic "c"
	[ 'c', [ ve.dm.example.italic ] ],
	//  4 - End of heading
	{ type: '/heading' },
	//  5 - Beginning of table
	{ type: 'table' },
	//  6 - Beginning of body
	{ type: 'tableSection', attributes: { style: 'body' } },
	//  7 - Beginning of row
	{ type: 'tableRow' },
	//  8 - Beginning of cell
	{ type: 'tableCell', attributes: { style: 'data' } },
	//  9 - Beginning of paragraph
	{ type: 'paragraph' },
	// 10 - Plain "d"
	'd',
	// 11 - End of paragraph
	{ type: '/paragraph' },
	// 12 - Beginning of bullet list
	{ type: 'list', attributes: { style: 'bullet' } },
	// 13 - Beginning of list item
	{ type: 'listItem' },
	// 14 - Beginning of paragraph
	{ type: 'paragraph' },
	// 15 - Plain "e"
	'e',
	// 16 - End of paragraph
	{ type: '/paragraph' },
	// 17 - Beginning of nested bullet list
	{ type: 'list', attributes: { style: 'bullet' } },
	// 18 - Beginning of nested bullet list item
	{ type: 'listItem' },
	// 19 - Beginning of paragraph
	{ type: 'paragraph' },
	// 20 - Plain "f"
	'f',
	// 21 - End of paragraph
	{ type: '/paragraph' },
	// 22 - End of nested bullet list item
	{ type: '/listItem' },
	// 23 - End of nested bullet list
	{ type: '/list' },
	// 24 - End of bullet list item
	{ type: '/listItem' },
	// 25 - End of bullet list
	{ type: '/list' },
	// 26 - Beginning of numbered list
	{ type: 'list', attributes: { style: 'number' } },
	// 27 - Beginning of numbered list item
	{ type: 'listItem' },
	// 28 - Beginning of paragraph
	{ type: 'paragraph' },
	// 29 - Plain "g"
	'g',
	// 30 - End of paragraph
	{ type: '/paragraph' },
	// 31 - End of item
	{ type: '/listItem' },
	// 32 - End of list
	{ type: '/list' },
	// 33 - End of cell
	{ type: '/tableCell' },
	// 34 - End of row
	{ type: '/tableRow' },
	// 35 - End of body
	{ type: '/tableSection' },
	// 36 - End of table
	{ type: '/table' },
	// 37 - Beginning of preformatted
	{ type: 'preformatted' },
	// 38 - Plain "h"
	'h',
	// 39 - Beginning of inline image
	ve.dm.example.image.data,
	// 40 - End of inline image
	{ type: '/inlineImage' },
	// 41 - Plain "i"
	'i',
	// 42 - End of preformatted
	{ type: '/preformatted' },
	// 43 - Beginning of definition list
	{ type: 'definitionList' },
	// 44 - Beginning of definition list term item
	{ type: 'definitionListItem', attributes: { style: 'term' } },
	// 45 - Beginning of paragraph
	{ type: 'paragraph' },
	// 46 - Plain "j"
	'j',
	// 47 - End of paragraph
	{ type: '/paragraph' },
	// 48 - End of definition list term item
	{ type: '/definitionListItem' },
	// 49 - Beginning of definition list definition item
	{ type: 'definitionListItem', attributes: { style: 'definition' } },
	// 50 - Beginning of paragraph
	{ type: 'paragraph' },
	// 51 - Plain "k"
	'k',
	// 52 - End of paragraph
	{ type: '/paragraph' },
	// 53 - End of definition list definition item
	{ type: '/definitionListItem' },
	// 54 - End of definition list
	{ type: '/definitionList' },
	// 55 - Beginning of paragraph
	{ type: 'paragraph' },
	// 56 - Plain "l"
	'l',
	// 57 - End of paragraph
	{ type: '/paragraph' },
	// 58 - Beginning of paragraph
	{ type: 'paragraph' },
	// 59 - Plain "m"
	'm',
	// 60 - End of paragraph
	{ type: '/paragraph' },
	// 61 - Beginning of internalList
	{ type: 'internalList' },
	// 62 - End of internalList
	{ type: '/internalList' }
	// 63 - End of document
];

ve.dm.example.alienData = [
	// 0
	{ type: 'alienBlock', originalDomElements: $.parseHTML( '<foobar />' ) },
	{ type: '/alienBlock' },
	// 2
	{ type: 'paragraph' },
	'a',
	// 4
	{ type: 'alienInline', originalDomElements: $.parseHTML( '<foobar />' ) },
	{ type: '/alienInline' },
	// 6
	'b',
	{ type: '/paragraph' },
	// 8
	{ type: 'alienBlock', originalDomElements: $.parseHTML( '<foobar />' ) },
	{ type: '/alienBlock' },
	// 10
	{ type: 'internalList' },
	{ type: '/internalList' }
];

ve.dm.example.alienWithEmptyData = [
	// 0
	{ type: 'paragraph' },
	{ type: '/paragraph' },
	// 2
	{ type: 'paragraph' },
	'a',
	// 4
	{ type: 'alienInline', originalDomElements: $.parseHTML( '<foobar />' ) },
	{ type: '/alienInline' },
	// 6
	{ type: '/paragraph' },
	// 7
	{ type: 'internalList' },
	{ type: '/internalList' }
];

ve.dm.example.internalData = [
	// 0
	{ type: 'paragraph' },
	'F', 'o', 'o',
	{ type: '/paragraph' },
	// 5
	{ type: 'internalList' },
	// 6
	{ type: 'internalItem' },
	// 7
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'B', 'a', 'r',
	{ type: '/paragraph' },
	// 12
	{ type: '/internalItem' },
	// 13
	{ type: 'internalItem' },
	// 14
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'B', 'a', 'z',
	{ type: '/paragraph' },
	// 19
	{ type: '/internalItem' },
	// 20
	{ type: '/internalList' },
	// 21
	{ type: 'paragraph' },
	'Q', 'u', 'u', 'x',
	{ type: '/paragraph' }
	// 27
];

ve.dm.example.internalData.internalItems = [
	{ group: 'test', key: 'bar', body: 'Bar' },
	{ group: 'test', key: 'baz', body: 'Baz' }
];

ve.dm.example.withMeta = [
	// 0
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<!-- No content conversion -->' )
	},
	{ type: '/alienMeta' },
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="foo" />' )
	},
	{ type: '/alienMeta' },
	{ type: 'paragraph' },
	// 5
	'F',
	'o',
	'o',
	{ type: '/paragraph' },
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<link rel="bar" href="baz" />' )
	},
	// 10
	{ type: '/alienMeta' },
	{ type: 'paragraph' },
	'B',
	'a',
	'r',
	// 15
	{ type: '/paragraph' },
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="foo" content="bar" />' )
	},
	{ type: '/alienMeta' },
	{ type: 'paragraph' },
	'B',
	// 20
	'a',
	'z',
	{ type: '/paragraph' },
	{
		type: 'removableAlienMeta',
		originalDomElements: $.parseHTML( '<b></b>' )
	},
	{ type: '/removableAlienMeta' },
	// 25
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="bar" content="baz" />' )
	},
	{ type: '/alienMeta' },
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<!--barbaz-->' )
	},
	{ type: '/alienMeta' },
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<link rel="foofoo" href="barbar" />' )
	},
	// 30
	{ type: '/alienMeta' },
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta typeof=bazquux" data-foo="foobar" />' )
	},
	{ type: '/alienMeta' },
	{ type: 'internalList' },
	{ type: '/internalList' }
];

ve.dm.example.listWithMeta = [
	//  0 - Beginning of list
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="one" />' )
	},
	{ type: '/alienMeta' },
	{ type: 'list' },
	//  1 - Beginning of first list item
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="two" />' )
	},
	{ type: '/alienMeta' },
	{ type: 'listItem', attributes: { styles: [ 'bullet' ] } },
	//  2 - Beginning of paragraph
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="three" />' )
	},
	{ type: '/alienMeta' },
	{ type: 'paragraph' },
	//  3 - Plain "a"
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="four" />' )
	},
	{ type: '/alienMeta' },
	'a',
	//  4 - End of paragraph
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="five" />' )
	},
	{ type: '/alienMeta' },
	{ type: '/paragraph' },
	//  5 - End of first list item
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="six" />' )
	},
	{ type: '/alienMeta' },
	{ type: '/listItem' },
	//  6 - Beginning of second list item
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="seven" />' )
	},
	{ type: '/alienMeta' },
	{ type: 'listItem', attributes: { styles: [ 'bullet' ] } },
	//  7 - Beginning of paragraph
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="eight" />' )
	},
	{ type: '/alienMeta' },
	{ type: 'paragraph' },
	//  8 - Plain "b"
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="nine" />' )
	},
	{ type: '/alienMeta' },
	'b',
	//  9 - End of paragraph
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="ten" />' )
	},
	{ type: '/alienMeta' },
	{ type: '/paragraph' },
	// 10 - End of second list item
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="eleven" />' )
	},
	{ type: '/alienMeta' },
	{ type: '/listItem' },
	// 11 - End of list
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="twelve" />' )
	},
	{ type: '/alienMeta' },
	{ type: '/list' },
	// 12 - Trailing metadata
	{
		type: 'alienMeta',
		originalDomElements: $.parseHTML( '<meta property="thirteen" />' )
	},
	{ type: '/alienMeta' },
	{ type: 'internalList' },
	{ type: '/internalList' }
];

ve.dm.example.mergedCellsHtml = ve.dm.example.singleLine`
	<table>
		<tr>
			<td>1</td><td>2</td><td>3</td><td rowspan="3">4</td><td>5</td><td>6</td>
		</tr>
		<tr>
			<td>7</td><td colspan="2">8</td><td rowspan="4">9</td><td>10</td>
		</tr>
		<tr>
			<td>11</td><td>12</td><td>13</td><td>14</td>
		</tr>
		<tr>
			<td>15</td><td rowspan="3" colspan="3">16</td><td>17</td>
		</tr>
		<tr>
			<td>18</td><td>19</td>
		</tr>
		<tr>
			<td>20</td><td colspan="2">21</td>
		</tr>
		<tr>
			<td>22</td><td>23</td><td>24</td><td>25</td><td>26</td><td>27</td>
		</tr>
	</table>
`;

ve.dm.example.mergedCells = [
	{ type: 'table' },
	{ type: 'tableSection', attributes: { style: 'body' } },
	{ type: 'tableRow' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'1',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'2',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'3',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{
		type: 'tableCell',
		attributes: {
			style: 'data',
			originalRowspan: '3',
			rowspan: 3
		}
	},
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'4',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'5',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'6',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: 'tableRow' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'7',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{
		type: 'tableCell',
		attributes: {
			style: 'data',
			colspan: 2,
			originalColspan: '2'
		}
	},
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'8',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{
		type: 'tableCell',
		attributes: {
			style: 'data',
			originalRowspan: '4',
			rowspan: 4
		}
	},
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'9',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'1', '0',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: 'tableRow' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'1', '1',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'1', '2',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'1', '3',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'1', '4',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: 'tableRow' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'1', '5',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{
		type: 'tableCell',
		attributes: {
			style: 'data',
			colspan: 3,
			originalColspan: '3',
			originalRowspan: '3',
			rowspan: 3
		}
	},
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'1', '6',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'1', '7',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: 'tableRow' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'1', '8',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'1', '9',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: 'tableRow' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'2', '0',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{
		type: 'tableCell',
		attributes: {
			style: 'data',
			colspan: 2,
			originalColspan: '2'
		}
	},
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'2', '1',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: 'tableRow' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'2', '2',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'2', '3',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'2', '4',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'2', '5',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'2', '6',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'2', '7',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: '/tableSection' },
	{ type: '/table' },
	{ type: 'internalList' },
	{ type: '/internalList' }
];

ve.dm.example.complexTableHtml = ve.dm.example.singleLine`
	<table>
		<caption>Foo</caption>
		<thead><tr><th rowspan="">Bar</th></tr></thead>
		<tfoot><tr><td colspan="2">Baz</td></tr></tfoot>
		<tbody><tr><td rowspan="02">Quux</td><td colspan="2 garbage">Whee</td></tr></tbody>
	</table>
`;

ve.dm.example.complexTable = [
	{ type: 'table' },
	{ type: 'tableCaption' },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'F',
	'o',
	'o',
	{ type: '/paragraph' },
	{ type: '/tableCaption' },
	{ type: 'tableSection', attributes: { style: 'header' } },
	{ type: 'tableRow' },
	{
		type: 'tableCell',
		attributes: {
			style: 'header',
			originalRowspan: ''
		}
	},
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'B',
	'a',
	'r',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: '/tableSection' },
	{ type: 'tableSection', attributes: { style: 'footer' } },
	{ type: 'tableRow' },
	{
		type: 'tableCell',
		attributes: {
			style: 'data',
			colspan: 2,
			originalColspan: '2'
		}
	},
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'B',
	'a',
	'z',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: '/tableSection' },
	{ type: 'tableSection', attributes: { style: 'body' } },
	{ type: 'tableRow' },
	{
		type: 'tableCell',
		attributes: {
			style: 'data',
			rowspan: 2,
			originalRowspan: '02'
		}
	},
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'Q',
	'u',
	'u',
	'x',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{
		type: 'tableCell',
		attributes: {
			style: 'data',
			originalColspan: '2 garbage'
		}
	},
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'W',
	'h',
	'e',
	'e',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: '/tableSection' },
	{ type: '/table' },
	{ type: 'internalList' },
	{ type: '/internalList' }
];

ve.dm.example.inlineAtEdges = [
	// 0
	{ type: 'paragraph' },
	// 1
	ve.dm.example.image.data,
	// 2
	{ type: '/inlineImage' },
	// 3
	'F', 'o', 'o',
	// 6
	{ type: 'alienInline', originalDomElements: $.parseHTML( '<foobar />' ) },
	// 7
	{ type: '/alienInline' },
	// 8
	{ type: '/paragraph' },
	// 9
	{ type: 'internalList' },
	{ type: '/internalList' }
];

ve.dm.example.annotatedTableHtml = ve.dm.example.singleLine`
	<table>
		<tr><td><b>Foo</b></td><td><strong>Bar</strong></td><td><i>Baz</i></td></tr>
		<tr><td><b><i>Quux</i></b></td><td><strong>Whee</strong></td><td><u>Yay</u></td></tr>
	</table>
`;

ve.dm.example.annotatedTable = [
	{ type: 'table' },
	{
		type: 'tableSection',
		attributes: { style: 'body' }
	},
	{ type: 'tableRow' },
	{
		type: 'tableCell',
		attributes: { style: 'data' }
	},
	{
		type: 'paragraph',
		internal: { generated: 'wrapper' }
	},
	[ 'F', [ ve.dm.example.bold ] ],
	[ 'o', [ ve.dm.example.bold ] ],
	[ 'o', [ ve.dm.example.bold ] ],
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{
		type: 'tableCell',
		attributes: { style: 'data' }
	},
	{
		type: 'paragraph',
		internal: { generated: 'wrapper' }
	},
	[ 'B', [ ve.dm.example.strong ] ],
	[ 'a', [ ve.dm.example.strong ] ],
	[ 'r', [ ve.dm.example.strong ] ],
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{
		type: 'tableCell',
		attributes: { style: 'data' }
	},
	{
		type: 'paragraph',
		internal: { generated: 'wrapper' }
	},
	[ 'B', [ ve.dm.example.italic ] ],
	[ 'a', [ ve.dm.example.italic ] ],
	[ 'z', [ ve.dm.example.italic ] ],
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: 'tableRow' },
	{
		type: 'tableCell',
		attributes: { style: 'data' }
	},
	{
		type: 'paragraph',
		internal: { generated: 'wrapper' }
	},
	[ 'Q', [ ve.dm.example.bold, ve.dm.example.italic ] ],
	[ 'u', [ ve.dm.example.bold, ve.dm.example.italic ] ],
	[ 'u', [ ve.dm.example.bold, ve.dm.example.italic ] ],
	[ 'x', [ ve.dm.example.bold, ve.dm.example.italic ] ],
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{
		type: 'tableCell',
		attributes: { style: 'data' }
	},
	{
		type: 'paragraph',
		internal: { generated: 'wrapper' }
	},
	[ 'W', [ ve.dm.example.strong ] ],
	[ 'h', [ ve.dm.example.strong ] ],
	[ 'e', [ ve.dm.example.strong ] ],
	[ 'e', [ ve.dm.example.strong ] ],
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{
		type: 'tableCell',
		attributes: { style: 'data' }
	},
	{
		type: 'paragraph',
		internal: { generated: 'wrapper' }
	},
	[ 'Y', [ ve.dm.example.underline ] ],
	[ 'a', [ ve.dm.example.underline ] ],
	[ 'y', [ ve.dm.example.underline ] ],
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: '/tableSection' },
	{ type: '/table' },
	{ type: 'internalList' },
	{ type: '/internalList' }
];

ve.dm.example.figcaptionHtml = ve.dm.example.singleLine`
	<p>a</p>
	<figure>
		${ ve.dm.example.image.html }
		<figcaption><p>b</p></figcaption>
	</figure>
	<p>c</p>
`;

ve.dm.example.figcaption = [
	// 0 - Beginning of paragraph
	{ type: 'paragraph' },
	// 1 - Plain "a"
	'a',
	// 2 - End of paragraph
	{ type: '/paragraph' },
	// 3 - Beginning of figure
	{ type: 'blockImage', attributes: ve.dm.example.image.data.attributes },
	// 4 - Beginning of figure caption
	{ type: 'imageCaption' },
	// 5 - Beginning of paragraph
	{ type: 'paragraph' },
	// 6 - Plain "b"
	'b',
	// 7 - End of paragraph
	{ type: '/paragraph' },
	// 8 - End of figure caption
	{ type: '/imageCaption' },
	// 9 - End of figure
	{ type: '/blockImage' },
	// 10 - Beginning of paragraph
	{ type: 'paragraph' },
	// 11 - Plain "c"
	'c',
	// 12 - End of paragraph
	{ type: '/paragraph' },
	// 13 - Beginning of internalList
	{ type: 'internalList' },
	// 14 - End of internalList
	{ type: '/internalList' }
	// 15 - End of document
];

ve.dm.example.emptyBranch = [
	{ type: 'table' },
	{ type: '/table' },
	{ type: 'internalList' },
	{ type: '/internalList' }
];

/**
 * Sample content data index.
 *
 * This is part of what a ve.dm.DocumentFragment generates when given linear data.
 *
 *  (21) branch nodes
 *     (01) document node
 *     (01) heading node
 *     (01) table node
 *     (01) tableRow node
 *     (01) tableCell node
 *     (06) paragraph nodes
 *     (03) list nodes
 *     (03) listItem nodes
 *     (01) preformatted node
 *     (01) definitionList node
 *     (02) definitionListItem nodes
 *  (10) leaf nodes
 *     (09) text nodes
 *     (01) image node
 */
ve.dm.example.tree = new ve.dm.DocumentNode( [
	// Heading with "abc"
	new ve.dm.HeadingNode( ve.dm.example.data[ 0 ], [ new ve.dm.TextNode( 3 ) ] ),
	new ve.dm.TableNode( ve.dm.example.data[ 5 ], [
		new ve.dm.TableSectionNode( ve.dm.example.data[ 6 ], [
			new ve.dm.TableRowNode( ve.dm.example.data[ 7 ], [
				new ve.dm.TableCellNode( ve.dm.example.data[ 8 ], [
					// Paragraph with "d"
					new ve.dm.ParagraphNode( ve.dm.example.data[ 9 ], [ new ve.dm.TextNode( 1 ) ] ),
					new ve.dm.ListNode( ve.dm.example.data[ 12 ], [
						// 1st level bullet list item with "e"
						new ve.dm.ListItemNode( ve.dm.example.data[ 13 ], [
							new ve.dm.ParagraphNode(
								ve.dm.example.data[ 14 ],
								[ new ve.dm.TextNode( 1 ) ]
							),
							new ve.dm.ListNode( ve.dm.example.data[ 17 ], [
								// 2nd level bullet list item with "f"
								new ve.dm.ListItemNode( ve.dm.example.data[ 18 ], [
									new ve.dm.ParagraphNode(
										ve.dm.example.data[ 19 ],
										[ new ve.dm.TextNode( 1 ) ]
									)
								] )
							] )
						] )
					] ),
					new ve.dm.ListNode( ve.dm.example.data[ 26 ], [
						// Numbered list item with "g"
						new ve.dm.ListItemNode( ve.dm.example.data[ 27 ], [
							new ve.dm.ParagraphNode(
								ve.dm.example.data[ 28 ],
								[ new ve.dm.TextNode( 1 ) ]
							)
						] )
					] )
				] )
			] )
		] )
	] ),
	// Preformatted with "h[example.png]i"
	new ve.dm.PreformattedNode( ve.dm.example.data[ 37 ], [
		new ve.dm.TextNode( 1 ),
		new ve.dm.InlineImageNode( ve.dm.example.data[ 39 ] ),
		new ve.dm.TextNode( 1 )
	] ),
	new ve.dm.DefinitionListNode( ve.dm.example.data[ 43 ], [
		// Definition list term item with "j"
		new ve.dm.DefinitionListItemNode( ve.dm.example.data[ 44 ], [
			new ve.dm.ParagraphNode( ve.dm.example.data[ 45 ], [ new ve.dm.TextNode( 1 ) ] )
		] ),
		// Definition list definition item with "k"
		new ve.dm.DefinitionListItemNode( ve.dm.example.data[ 49 ], [
			new ve.dm.ParagraphNode( ve.dm.example.data[ 50 ], [ new ve.dm.TextNode( 1 ) ] )
		] )
	] ),
	new ve.dm.ParagraphNode( ve.dm.example.data[ 55 ], [ new ve.dm.TextNode( 1 ) ] ),
	new ve.dm.ParagraphNode( ve.dm.example.data[ 58 ], [ new ve.dm.TextNode( 1 ) ] ),
	new ve.dm.InternalListNode( ve.dm.example.data[ 61 ] )
] );

ve.dm.example.domToDataCases = {
	'paragraph with plain text': {
		body: '<p>abc</p>',
		data: [
			{ type: 'paragraph' },
			'a',
			'b',
			'c',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'annotated text with bold, italic, underline formatting': {
		body: '<p><b>a</b><i>b</i><u>c</u></p>',
		data: [
			{ type: 'paragraph' },
			[ 'a', [ ve.dm.example.bold ] ],
			[ 'b', [ ve.dm.example.italic ] ],
			[ 'c', [ ve.dm.example.underline ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		ceHtml: ve.dm.example.singleLine`
			${ ve.dm.example.ceParagraph }
				<b class="${ ve.dm.example.textStyleClasses } ve-ce-boldAnnotation">a</b>
				<i class="${ ve.dm.example.textStyleClasses } ve-ce-italicAnnotation">b</i>
				<u class="${ ve.dm.example.textStyleClasses } ve-ce-underlineAnnotation">c</u>
			</p>
		`
	},
	'annotation from data': {
		data: [
			{ type: 'paragraph' },
			// Annotation without nodeName
			[ 'a', [ { type: 'textStyle/bold' } ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<p><b>a</b></p>'
	},
	'equivalent annotations': {
		body: '<p><code>a</code>b<tt>c</tt>d<code>e</code><tt>f</tt></p>',
		data: [
			{ type: 'paragraph' },
			[ 'a', [ ve.dm.example.code ] ],
			'b',
			[ 'c', [ ve.dm.example.tt ] ],
			'd',
			[ 'e', [ ve.dm.example.code ] ],
			[ 'f', [ ve.dm.example.tt ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<p><code>a</code>b<tt>c</tt>d<code>ef</code></p>'
	},
	'additive annotations': {
		body: '<p><big>a<big>b</big>c</big><b>d<b>e</b>f</b></p>',
		data: [
			{ type: 'paragraph' },
			[ 'a', [ ve.dm.example.big ] ],
			[ 'b', [ ve.dm.example.big, ve.dm.example.big ] ],
			[ 'c', [ ve.dm.example.big ] ],
			[ 'd', [ ve.dm.example.bold ] ],
			[ 'e', [ ve.dm.example.bold, ve.dm.example.bold ] ],
			[ 'f', [ ve.dm.example.bold ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'additive annotations overlapping other annotations': {
		body: '<p><i><big>a<big><b>b</b></big><b>c</b></big></i></p>',
		data: [
			{ type: 'paragraph' },
			[ 'a', [ ve.dm.example.italic, ve.dm.example.big ] ],
			[ 'b', [ ve.dm.example.italic, ve.dm.example.big, ve.dm.example.big, ve.dm.example.bold ] ],
			[ 'c', [ ve.dm.example.italic, ve.dm.example.big, ve.dm.example.bold ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'annotations normalised on import': {
		body: '<p><em>Foo</em><strong>bar</strong></p>',
		fromClipboard: true,
		data: [
			{ type: 'paragraph' },
			[ 'F', [ ve.dm.example.italic ] ],
			[ 'o', [ ve.dm.example.italic ] ],
			[ 'o', [ ve.dm.example.italic ] ],
			[ 'b', [ ve.dm.example.bold ] ],
			[ 'a', [ ve.dm.example.bold ] ],
			[ 'r', [ ve.dm.example.bold ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		normalizedBody: '<p><i>Foo</i><b>bar</b></p>'
	},
	'annotation merging': {
		body: '<p><b>abc</b>X<b>def</b><i>ghi</i></p>',
		data: [
			{ type: 'paragraph' },
			[ 'a', [ ve.dm.example.bold ] ],
			[ 'b', [ ve.dm.example.bold ] ],
			[ 'c', [ ve.dm.example.bold ] ],
			'X',
			[ 'd', [ ve.dm.example.bold ] ],
			[ 'e', [ ve.dm.example.bold ] ],
			[ 'f', [ ve.dm.example.bold ] ],
			[ 'g', [ ve.dm.example.italic ] ],
			[ 'h', [ ve.dm.example.italic ] ],
			[ 'i', [ ve.dm.example.italic ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		modify: function ( doc ) {
			doc.commit( ve.dm.TransactionBuilder.static.newFromRemoval(
				doc,
				new ve.Range( 4, 5 )
			) );
		},
		normalizedBody: '<p><b>abcdef</b><i>ghi</i></p>',
		fromDataBody: '<p><b>abcdef</b><i>ghi</i></p>'
	},
	'language annotation': {
		body: ve.dm.example.singleLine`
			<p>
				<span lang="en">ten</span>
				<span lang="fr" dir="ltr">dix</span>
				<bdo lang="cy" dir="ltr">deg</bdo>
				<span dir="rtl">12</span>
				<span dir="RtL">34</span>
			</p>
		`,
		data: [
			{ type: 'paragraph' },
			[ 't', [ ve.dm.example.language( 'en', null ) ] ],
			[ 'e', [ ve.dm.example.language( 'en', null ) ] ],
			[ 'n', [ ve.dm.example.language( 'en', null ) ] ],
			[ 'd', [ ve.dm.example.language( 'fr', 'ltr' ) ] ],
			[ 'i', [ ve.dm.example.language( 'fr', 'ltr' ) ] ],
			[ 'x', [ ve.dm.example.language( 'fr', 'ltr' ) ] ],
			[ 'd', [ ve.dm.example.language( 'cy', 'ltr', 'bdo' ) ] ],
			[ 'e', [ ve.dm.example.language( 'cy', 'ltr', 'bdo' ) ] ],
			[ 'g', [ ve.dm.example.language( 'cy', 'ltr', 'bdo' ) ] ],
			[ '1', [ ve.dm.example.language( null, 'rtl' ) ] ],
			[ '2', [ ve.dm.example.language( null, 'rtl' ) ] ],
			[ '3', [ ve.dm.example.language( null, 'RtL' ) ] ],
			[ '4', [ ve.dm.example.language( null, 'RtL' ) ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		ceHtml: ve.dm.example.singleLine`
			${ ve.dm.example.ceParagraph }
				<span class="${ ve.dm.example.textStyleClasses } ve-ce-languageAnnotation ve-ce-bidi-isolate" lang="en" title="visualeditor-languageannotation-description,langname-en">ten</span>
				<span class="${ ve.dm.example.textStyleClasses } ve-ce-languageAnnotation ve-ce-bidi-isolate" lang="fr" dir="ltr" title="visualeditor-languageannotation-description,langname-fr">dix</span>
				<bdo class="${ ve.dm.example.textStyleClasses } ve-ce-languageAnnotation ve-ce-bidi-isolate" lang="cy" dir="ltr" title="visualeditor-languageannotation-description,langname-cy">deg</bdo>
				<span class="${ ve.dm.example.textStyleClasses } ve-ce-languageAnnotation ve-ce-bidi-isolate" dir="rtl" title="visualeditor-languageannotation-description-with-dir,langname-,RTL">12</span>
				<span class="${ ve.dm.example.textStyleClasses } ve-ce-languageAnnotation ve-ce-bidi-isolate" dir="RtL" title="visualeditor-languageannotation-description-with-dir,langname-,RTL">34</span>
			</p>
		`
	},
	'datetime annotation': {
		body:
			ve.dm.example.singleLine`
			<p>
				<time>a</time>
				<time datetime="2001-05-15T19:00">b</time>
			</p>
		`,
		data: [
			{ type: 'paragraph' },
			[ 'a', [ { type: 'textStyle/datetime', attributes: { nodeName: 'time', datetime: null } } ] ],
			[ 'b', [ { type: 'textStyle/datetime', attributes: { nodeName: 'time', datetime: '2001-05-15T19:00' } } ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		ceHtml: ve.dm.example.singleLine`
			${ ve.dm.example.ceParagraph }
				<time class="${ ve.dm.example.textStyleClasses } ve-ce-datetimeAnnotation">a</time>
				<time class="${ ve.dm.example.textStyleClasses } ve-ce-datetimeAnnotation">b</time>
			</p>
		`
	},
	'comment annotation': {
		body: ve.dm.example.singleLine`
			<p>
				<span rel="ve:CommentAnnotation" data-text="Test">a</span>
			</p>
		`,
		data: [
			{ type: 'paragraph' },
			[ 'a', [ { type: 'commentAnnotation', attributes: { text: 'Test' } } ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		ceHtml: ve.dm.example.singleLine`
			${ ve.dm.example.ceParagraph }
				<span class="ve-ce-annotation ve-ce-commentAnnotation">a</span>
			</p>
		`,
		normalizedBody: '<p>a</p>',
		clipboardBody: ve.dm.example.singleLine`
			<p>
				<span rel="ve:CommentAnnotation" data-text="Test">a</span>
			</p>
		`,
		previewBody: '<p>a</p>'
	},
	'other textStyle annotations': {
		body: ve.dm.example.singleLine`
			<p>
				<abbr>a</abbr>
				<var>b</var>
				<kbd>c</kbd>
				<q>d</q>
				<samp>e</samp>
				<dfn>f</dfn>
				<mark>g</mark>
				<font>h</font>
				<bdi>i</bdi>
			</p>
		`,
		data: [
			{ type: 'paragraph' },
			[ 'a', [ { type: 'textStyle/abbreviation', attributes: { nodeName: 'abbr' } } ] ],
			[ 'b', [ { type: 'textStyle/variable', attributes: { nodeName: 'var' } } ] ],
			[ 'c', [ { type: 'textStyle/userInput', attributes: { nodeName: 'kbd' } } ] ],
			[ 'd', [ { type: 'textStyle/quotation', attributes: { nodeName: 'q' } } ] ],
			[ 'e', [ { type: 'textStyle/codeSample', attributes: { nodeName: 'samp' } } ] ],
			[ 'f', [ { type: 'textStyle/definition', attributes: { nodeName: 'dfn' } } ] ],
			[ 'g', [ { type: 'textStyle/highlight', attributes: { nodeName: 'mark' } } ] ],
			[ 'h', [ { type: 'textStyle/font', attributes: { nodeName: 'font' } } ] ],
			[ 'i', [ { type: 'textStyle/bidi', attributes: { nodeName: 'bdi' } } ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		ceHtml: ve.dm.example.singleLine`
			${ ve.dm.example.ceParagraph }
				<abbr class="${ ve.dm.example.textStyleClasses } ve-ce-abbreviationAnnotation">a</abbr>
				<var class="${ ve.dm.example.textStyleClasses } ve-ce-variableAnnotation">b</var>
				<kbd class="${ ve.dm.example.textStyleClasses } ve-ce-userInputAnnotation">c</kbd>
				<q class="${ ve.dm.example.textStyleClasses } ve-ce-quotationAnnotation">d</q>
				<samp class="${ ve.dm.example.textStyleClasses } ve-ce-codeSampleAnnotation">e</samp>
				<dfn class="${ ve.dm.example.textStyleClasses } ve-ce-definitionAnnotation">f</dfn>
				<mark class="${ ve.dm.example.textStyleClasses } ve-ce-highlightAnnotation">g</mark>
				<font class="${ ve.dm.example.textStyleClasses } ve-ce-fontAnnotation">h</font>
				<bdi class="${ ve.dm.example.textStyleClasses } ve-ce-bidiAnnotation">i</bdi>
			</p>
		`
	},
	'check list': {
		body: ve.dm.example.singleLine`
			<ul rel="ve:checkList">
				<li rel="ve:checkList" data-checked="checked"><p>foo</p></li>
				<li rel="ve:checkList">bar</li>
			</ul>
		`,
		data: [
			{ type: 'checkList' },
			{ type: 'checkListItem', attributes: { checked: true } },
			{ type: 'paragraph' },
			'f', 'o', 'o',
			{ type: '/paragraph' },
			{ type: '/checkListItem' },
			{ type: 'checkListItem', attributes: { checked: false } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'b', 'a', 'r',
			{ type: '/paragraph' },
			{ type: '/checkListItem' },
			{ type: '/checkList' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		ceHtml: ve.dm.example.singleLine`
			${ ve.dm.example.blockSlug }
			<ul class="ve-ce-branchNode ve-ce-checkListNode">
				<li class="ve-ce-branchNode ve-ce-checkListItemNode ve-ce-checkListItemNode-checked">
					${ ve.dm.example.ceParagraph }foo</p>
				</li>
				<li class="ve-ce-branchNode ve-ce-checkListItemNode">
					${ ve.dm.example.ceWrapperParagraph }bar</p>
				</li>
			</ul>
		`,
		normalizedBody: ve.dm.example.singleLine`
			<ul rel="ve:checkList">
				<li rel="ve:checkList" data-checked="checked"><p>☑ foo</p></li>
				<li rel="ve:checkList">☐ bar</li>
			</ul>
		`,
		clipboardBody: ve.dm.example.singleLine`
			<ul rel="ve:checkList">
				<li rel="ve:checkList" data-checked="checked" style="list-style: none;"><p><span data-ve-ignore="true">☑</span> foo</p></li>
				<li rel="ve:checkList" style="list-style: none;"><span data-ve-ignore="true">☐</span> bar</li>
			</ul>
		`
	},
	'strip leading whitespace in non-whitespace preserving nodes': {
		// T53462/T142132
		data: [
			{ type: 'paragraph' },
			' ', 'f', 'o', 'o',
			{ type: '/paragraph' },
			{ type: 'paragraph' },
			' ', '\t', ' ', '\t', 'b', 'a', 'r',
			{ type: '/paragraph' },
			{ type: 'heading', attributes: { level: 2 } },
			' ', ' ', 'b', 'a', 'z',
			{ type: '/heading' },
			{ type: 'preformatted' },
			' ', '\t', 'q', 'u', 'u', 'x',
			{ type: '/preformatted' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		normalizedBody: '<p>foo</p><p>bar</p><h2>baz</h2><pre> \tquux</pre>',
		clipboardBody: '<p> foo</p><p> \t \tbar</p><h2>  baz</h2><pre> \tquux</pre>',
		previewBody: '<p> foo</p><p> ➞ ➞bar</p><h2>  baz</h2><pre> \tquux</pre>'
	},
	image: {
		body: ve.dm.example.image.html,
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			ve.dm.example.image.data,
			{ type: '/inlineImage' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		ceHtml: ve.dm.example.singleLine`
			${ ve.dm.example.ceWrapperParagraph }
				${ ve.dm.example.inlineSlug }
				<img class="ve-ce-leafNode ve-ce-focusableNode ve-ce-imageNode ve-ce-inlineImageNode" contenteditable="false" alt="Example"
				 src="${ ve.dm.example.imgSrc }" style="width: 100px; height: 50px;">
				${ ve.dm.example.inlineSlug }
			</p>
		`
	},
	'block images': {
		body: ve.dm.example.blockImage.html + ve.dm.example.blockImage.html,
		data: ve.dm.example.blockImage.data.concat(
			ve.dm.example.blockImage.data,
			[
				{ type: 'internalList' },
				{ type: '/internalList' }
			]
		),
		ceHtml: ve.dm.example.blockSlug +
			ve.dm.example.blockImage.ceHtml +
			// No block slug between two floated images
			ve.dm.example.blockImage.ceHtml +
			ve.dm.example.blockSlug
	},
	'block image modified': {
		body: ve.dm.example.blockImage.html,
		data: ve.dm.example.blockImage.data.concat( [
			{ type: 'internalList' },
			{ type: '/internalList' }
		] ),
		modify: function ( doc ) {
			doc.commit( ve.dm.TransactionBuilder.static.newFromAnnotation(
				doc,
				new ve.Range( 3, 6 ),
				'set',
				ve.dm.example.createAnnotation( ve.dm.example.bold, doc.getStore() )
			) );
		},
		normalizedBody: ve.dm.example.singleLine`
			<figure class="ve-align-right"><img src="${ ve.dm.example.imgSrc }" width="100" height="50" alt="Example">
				<figcaption>
					<b>foo</b> <b style="color:red;">red</b>
				</figcaption>
			</figure>
		`
	},
	'block image with no caption': {
		body: '<figure><img></figure>',
		data: [
			{
				type: 'blockImage',
				attributes: {
					width: null,
					height: null,
					src: null,
					alt: null
				}
			},
			{ type: 'imageCaption' },
			{ type: '/imageCaption' },
			{ type: '/blockImage' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'paragraph with alienInline inside': {
		body: '<p>a<foobar class="foo">b</foobar>c</p>',
		data: [
			{ type: 'paragraph' },
			'a',
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<foobar class="foo">b</foobar>' )
			},
			{ type: '/alienInline' },
			'c',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'paragraphs with an alienBlock between them': {
		body: '<p>abc</p><div rel="ve:Alien">abc</div><p>def</p>',
		data: [
			{ type: 'paragraph' },
			'a',
			'b',
			'c',
			{ type: '/paragraph' },
			{ type: 'alienBlock', originalDomElements: $.parseHTML( '<div rel="ve:Alien">abc</div>' ) },
			{ type: '/alienBlock' },
			{ type: 'paragraph' },
			'd',
			'e',
			'f',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'annotated inline nodes': {
		body: ve.dm.example.singleLine`
			<p>
				a
				<b>
					<foobar class="foo">b</foobar>
					<i>
						<foobar class="bar">c</foobar>
					</i>
				</b>
				<i><br/>d</i>
				e
			</p>
		`,
		data: [
			{ type: 'paragraph' },
			'a',
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<foobar class="foo">b</foobar>' ),
				annotations: [ ve.dm.example.bold ]
			},
			{ type: '/alienInline' },
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<foobar class="bar">c</foobar>' ),
				annotations: [ ve.dm.example.bold, ve.dm.example.italic ]
			},
			{ type: '/alienInline' },
			{
				type: 'break',
				annotations: [ ve.dm.example.italic ]
			},
			{ type: '/break' },
			[ 'd', [ ve.dm.example.italic ] ],
			'e',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'annotated comments': {
		body: '<p><b><!--foo-->bar<!--baz--></b></p>',
		data: [
			{ type: 'paragraph' },
			{
				type: 'comment',
				annotations: [ ve.dm.example.bold ],
				attributes: {
					text: 'foo'
				}
			},
			{ type: '/comment' },
			[ 'b', [ ve.dm.example.bold ] ],
			[ 'a', [ ve.dm.example.bold ] ],
			[ 'r', [ ve.dm.example.bold ] ],
			{
				type: 'comment',
				annotations: [ ve.dm.example.bold ],
				attributes: {
					text: 'baz'
				}
			},
			{ type: '/comment' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		clipboardBody: ve.dm.example.singleLine`
			<p>
				<b>
					<span rel="ve:Comment" data-ve-comment="foo">&nbsp;</span>
					bar
					<span rel="ve:Comment" data-ve-comment="baz">&nbsp;</span>
				</b>
			</p>
		`,
		previewBody: ve.dm.example.singleLine`
			<p>
				<b>
					${ ve.dm.example.commentNodePreview( 'foo' ) }
					bar
					${ ve.dm.example.commentNodePreview( 'baz' ) }
				</b>
			</p>
		`,
		ceHtml: ve.dm.example.singleLine`
			${ ve.dm.example.ceParagraph }
				<b class="${ ve.dm.example.textStyleClasses } ve-ce-boldAnnotation">
					${ ve.dm.example.inlineSlug }
					<span class="ve-ce-leafNode ve-ce-focusableNode ve-ce-commentNode" contenteditable="false"></span>
					bar
					<span class="ve-ce-leafNode ve-ce-focusableNode ve-ce-commentNode" contenteditable="false"></span>
				</b>
				${ ve.dm.example.inlineSlug }
			</p>
		`
	},
	'annotated metadata': {
		body: '<p><b><meta />bar<meta /></b></p>',
		data: [
			{ type: 'paragraph' },
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			[ 'b', [ ve.dm.example.bold ] ],
			[ 'a', [ ve.dm.example.bold ] ],
			[ 'r', [ ve.dm.example.bold ] ],
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'paragraph' },
			[ 'b', [ ve.dm.example.bold ] ],
			[ 'a', [ ve.dm.example.bold ] ],
			[ 'r', [ ve.dm.example.bold ] ],
			{ type: '/paragraph' },
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'annotated metadata in a wrapper': {
		body: '<b><meta />bar<meta />quux<meta /></b>',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			[ 'b', [ ve.dm.example.bold ] ],
			[ 'a', [ ve.dm.example.bold ] ],
			[ 'r', [ ve.dm.example.bold ] ],
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			[ 'q', [ ve.dm.example.bold ] ],
			[ 'u', [ ve.dm.example.bold ] ],
			[ 'u', [ ve.dm.example.bold ] ],
			[ 'x', [ ve.dm.example.bold ] ],
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			[ 'b', [ ve.dm.example.bold ] ],
			[ 'a', [ ve.dm.example.bold ] ],
			[ 'r', [ ve.dm.example.bold ] ],
			[ 'q', [ ve.dm.example.bold ] ],
			[ 'u', [ ve.dm.example.bold ] ],
			[ 'u', [ ve.dm.example.bold ] ],
			[ 'x', [ ve.dm.example.bold ] ],
			{ type: '/paragraph' },
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'annotated element metadata in a wrapper with content': {
		body: '<b><link />foo<link /></b>',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<link />' )
			},
			{ type: '/alienMeta' },
			[ 'f', [ ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.bold ] ],
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<link />' )
			},
			{ type: '/alienMeta' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			[ 'f', [ ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.bold ] ],
			{ type: '/paragraph' },
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<link />' )
			},
			{ type: '/alienMeta' },
			{
				type: 'alienMeta',
				annotations: [ ve.dm.example.bold ],
				originalDomElements: $.parseHTML( '<link />' )
			},
			{ type: '/alienMeta' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'metadata in a wrapper followed by annotated text': {
		body: 'Foo<meta /><b>Baz</b>',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'F',
			'o',
			'o',
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			[ 'B', [ ve.dm.example.bold ] ],
			[ 'a', [ ve.dm.example.bold ] ],
			[ 'z', [ ve.dm.example.bold ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'F',
			'o',
			'o',
			[ 'B', [ ve.dm.example.bold ] ],
			[ 'a', [ ve.dm.example.bold ] ],
			[ 'z', [ ve.dm.example.bold ] ],
			{ type: '/paragraph' },
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'wrapping of bare content': {
		body: 'abc',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'a',
			'b',
			'c',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'wrapping of bare content with inline node': {
		body: '1<br/>2',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'1',
			{ type: 'break' },
			{ type: '/break' },
			'2',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'wrapping of bare content starting with inline node': {
		body: ve.dm.example.image.html + '12',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			ve.dm.example.image.data,
			{ type: '/inlineImage' },
			'1',
			'2',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'wrapping of bare content with inline alien': {
		body: '1<foobar class="bar">baz</foobar>2',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'1',
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<foobar class="bar">baz</foobar>' )
			},
			{ type: '/alienInline' },
			'2',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'wrapping of bare content with block alien': {
		body: '1<div rel="ve:Alien" class="bar">baz</div>2',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'1',
			{ type: '/paragraph' },
			{
				type: 'alienBlock',
				originalDomElements: $.parseHTML( '<div rel="ve:Alien" class="bar">baz</div>' )
			},
			{ type: '/alienBlock' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'2',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'wrapping of bare content starting with inline alien': {
		body: '<foobar class="bar">Foo</foobar>Bar',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<foobar class="bar">Foo</foobar>' )
			},
			{ type: '/alienInline' },
			'B',
			'a',
			'r',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'wrapping of bare content ending with inline alien': {
		body: 'Foo<foobar class="bar">Bar</foobar>',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'F',
			'o',
			'o',
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<foobar class="bar">Bar</foobar>' )
			},
			{ type: '/alienInline' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'wrapping of bare content with about group': {
		body: '1<foobar about="#vet1">foo</foobar><foobar about="#vet1">bar</foobar>2',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'1',
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<foobar about="#vet1">foo</foobar><foobar about="#vet1">bar</foobar>' )
			},
			{ type: '/alienInline' },
			'2',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'wrapping of bare content between structural nodes': {
		body: '<table></table>abc<table></table>',
		data: [
			{ type: 'table' },
			{ type: '/table' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'a',
			'b',
			'c',
			{ type: '/paragraph' },
			{ type: 'table' },
			{ type: '/table' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'wrapping of bare content between paragraphs': {
		body: '<p>abc</p>def<p></p>',
		data: [
			{ type: 'paragraph' },
			'a',
			'b',
			'c',
			{ type: '/paragraph' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'd',
			'e',
			'f',
			{ type: '/paragraph' },
			{ type: 'paragraph' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'wrapping prevents empty list items': {
		body: '<ul><li></li></ul>',
		data: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		// Inserting content doesn't result in a real <p> node
		modify: function ( doc ) {
			doc.commit( ve.dm.TransactionBuilder.static.newFromInsertion(
				doc,
				3,
				'Foo'
			) );
		},
		normalizedBody: '<ul><li>Foo</li></ul>',
		fromDataBody: '<ul><li>Foo</li></ul>'
	},
	'empty document': {
		body: '',
		data: [
			{ type: 'paragraph', internal: { generated: 'empty' } },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'empty document with meta': {
		body: '<meta />',
		data: [
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			{ type: 'paragraph', internal: { generated: 'empty' } },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'empty document with comment': {
		body: '<!-- comment -->',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{
				type: 'comment',
				attributes: {
					text: ' comment '
				}
			},
			{ type: '/comment' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		clipboardBody: '<span rel="ve:Comment" data-ve-comment=" comment ">&nbsp;</span>',
		previewBody: ve.dm.example.commentNodePreview( ' comment ' )
	},
	'empty document with content added by the editor': {
		data: [
			{ type: 'paragraph', internal: { generated: 'empty' } },
			'F',
			'o',
			'o',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		normalizedBody: '<p>Foo</p>'
	},
	'empty list item with content added by the editor': {
		data: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem' },
			{ type: 'paragraph', internal: { generated: 'empty' } },
			'F',
			'o',
			'o',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		normalizedBody: '<ul><li><p>Foo</p></li></ul>'
	},
	'slug paragraph added between two nodes that had whitespace': {
		data: [
			{ type: 'table', internal: { whitespace: [ undefined, undefined, undefined, '\n' ] } },
			{ type: '/table' },
			{ type: 'paragraph', internal: { generated: 'slug' } },
			{ type: '/paragraph' },
			{ type: 'table', internal: { whitespace: [ '\n' ] } },
			{ type: '/table' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		normalizedBody: '<table></table>\n<table></table>'
	},
	'example document': {
		body: ve.dm.example.html,
		data: ve.dm.example.data
	},
	'example document (figure/figcaption)': {
		body: ve.dm.example.figcaptionHtml,
		data: ve.dm.example.figcaption
	},
	'empty annotation': {
		body: '<p>Foo<span id="anchorTarget"></span>Bar</p>',
		data: [
			{ type: 'paragraph' },
			'F', 'o', 'o',
			{
				type: 'removableAlienMeta',
				originalDomElements: $.parseHTML( '<span id="anchorTarget"></span>' )
			},
			{ type: '/removableAlienMeta' },
			'B', 'a', 'r',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'paragraph' },
			'F', 'o', 'o',
			'B', 'a', 'r',
			{ type: '/paragraph' },
			{
				type: 'removableAlienMeta',
				originalDomElements: $.parseHTML( '<span id="anchorTarget"></span>' )
			},
			{ type: '/removableAlienMeta' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'empty annotation in wrapper paragraph': {
		body: 'Foo<span id="anchorTarget"></span>Bar',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'F', 'o', 'o',
			{
				type: 'removableAlienMeta',
				originalDomElements: $.parseHTML( '<span id="anchorTarget"></span>' )
			},
			{ type: '/removableAlienMeta' },
			'B', 'a', 'r',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'F', 'o', 'o',
			'B', 'a', 'r',
			{ type: '/paragraph' },
			{
				type: 'removableAlienMeta',
				originalDomElements: $.parseHTML( '<span id="anchorTarget"></span>' )
			},
			{ type: '/removableAlienMeta' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'annotation next to wrapper paragraph': {
		data: [
			{ type: 'table' },
			{ type: 'tableSection', attributes: { style: 'body' } },
			{ type: 'tableRow' },
			{ type: 'tableCell', attributes: { style: 'data' } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			[ 'F', [ ve.dm.example.link( 'Foo' ) ] ],
			[ 'o', [ ve.dm.example.link( 'Foo' ) ] ],
			[ 'o', [ ve.dm.example.link( 'Foo' ) ] ],
			{ type: '/paragraph' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'B', 'a', 'r',
			{ type: '/paragraph' },
			{ type: '/tableCell' },
			{ type: '/tableRow' },
			{ type: '/tableSection' },
			{ type: '/table' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<table><tr><td><a href="Foo">Foo</a><p>Bar</p></td></tr></table>'
	},
	'nested empty annotation': {
		body: '<p>Foo<i><b><u></u></b></i>Bar</p>',
		data: [
			{ type: 'paragraph' },
			'F', 'o', 'o',
			{
				type: 'removableAlienMeta',
				originalDomElements: $.parseHTML( '<i><b><u></u></b></i>' )
			},
			{ type: '/removableAlienMeta' },
			'B', 'a', 'r',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'paragraph' },
			'F', 'o', 'o',
			'B', 'a', 'r',
			{ type: '/paragraph' },
			{
				type: 'removableAlienMeta',
				originalDomElements: $.parseHTML( '<i><b><u></u></b></i>' )
			},
			{ type: '/removableAlienMeta' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'empty annotation inside nonempty annotation': {
		body: '<p><i>Foo<b></b></i></p>',
		data: [
			{ type: 'paragraph' },
			[ 'F', [ ve.dm.example.italic ] ],
			[ 'o', [ ve.dm.example.italic ] ],
			[ 'o', [ ve.dm.example.italic ] ],
			{
				type: 'removableAlienMeta',
				originalDomElements: $.parseHTML( '<b></b>' ),
				annotations: [ ve.dm.example.italic ]
			},
			{ type: '/removableAlienMeta' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'paragraph' },
			[ 'F', [ ve.dm.example.italic ] ],
			[ 'o', [ ve.dm.example.italic ] ],
			[ 'o', [ ve.dm.example.italic ] ],
			{ type: '/paragraph' },
			{
				type: 'removableAlienMeta',
				originalDomElements: $.parseHTML( '<b></b>' ),
				annotations: [ ve.dm.example.italic ]
			},
			{ type: '/removableAlienMeta' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'empty annotation with comment': {
		body: '<p>Foo<b><!-- Bar --></b>Baz</p>',
		data: [
			{ type: 'paragraph' },
			'F', 'o', 'o',
			{
				type: 'comment',
				annotations: [ ve.dm.example.bold ],
				attributes: {
					text: ' Bar '
				}
			},
			{ type: '/comment' },
			'B', 'a', 'z',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		clipboardBody: '<p>Foo<b><span rel="ve:Comment" data-ve-comment=" Bar ">&nbsp;</span></b>Baz</p>',
		previewBody: '<p>Foo<b>' + ve.dm.example.commentNodePreview( ' Bar ' ) + '</b>Baz</p>'
	},
	'empty annotation with metadata': {
		body: '<p>Foo<b><meta /></b>Baz</p>',
		data: [
			{ type: 'paragraph' },
			'F', 'o', 'o',
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<b><meta /></b>' )
			},
			{ type: '/alienMeta' },
			'B', 'a', 'z',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'paragraph' },
			'F', 'o', 'o',
			'B', 'a', 'z',
			{ type: '/paragraph' },
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<b><meta /></b>' )
			},
			{ type: '/alienMeta' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'adjacent identical annotations': {
		body: ve.dm.example.singleLine`
			<p>
				<b>Foo</b>
				<b>bar</b>
				<strong>baz</strong>
			</p>
			<p>
				<a href="quux">Foo</a>
				<a href="quux">bar</a>
				<a href="whee">baz</a>
			</p>
		`,
		normalizedBody: ve.dm.example.singleLine`
			<p>
				<b>Foobar</b>
				<strong>baz</strong>
			</p>
			<p>
				<a href="quux">Foobar</a>
				<a href="whee">baz</a>
			</p>
		`,
		data: [
			{ type: 'paragraph' },
			[ 'F', [ ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.bold ] ],
			[ 'b', [ ve.dm.example.bold ] ],
			[ 'a', [ ve.dm.example.bold ] ],
			[ 'r', [ ve.dm.example.bold ] ],
			[ 'b', [ ve.dm.example.strong ] ],
			[ 'a', [ ve.dm.example.strong ] ],
			[ 'z', [ ve.dm.example.strong ] ],
			{ type: '/paragraph' },
			{ type: 'paragraph' },
			[ 'F', [ ve.dm.example.link( 'quux' ) ] ],
			[ 'o', [ ve.dm.example.link( 'quux' ) ] ],
			[ 'o', [ ve.dm.example.link( 'quux' ) ] ],
			[ 'b', [ ve.dm.example.link( 'quux' ) ] ],
			[ 'a', [ ve.dm.example.link( 'quux' ) ] ],
			[ 'r', [ ve.dm.example.link( 'quux' ) ] ],
			[ 'b', [ ve.dm.example.link( 'whee' ) ] ],
			[ 'a', [ ve.dm.example.link( 'whee' ) ] ],
			[ 'z', [ ve.dm.example.link( 'whee' ) ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: ve.dm.example.singleLine`
			<p>
				<b>Foobarbaz</b>
			</p>
			<p>
				<a href="quux">Foobar</a>
				<a href="whee">baz</a>
			</p>
		`
	},
	'adjacent identical annotations with identical content': {
		body: '<p><b>x</b><b>x</b></p>',
		normalizedBody: '<p><b>xx</b></p>',
		data: [
			{ type: 'paragraph' },
			[ 'x', [ ve.dm.example.bold ] ],
			[ 'x', [ ve.dm.example.bold ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<p><b>xx</b></p>'
	},
	'plain href-less anchors (e.g. on paste) are converted to spans': {
		body: '<a name="foo">ab</a>',
		data: [
			{
				type: 'paragraph',
				internal: {
					generated: 'wrapper'
				}
			},
			[
				'a',
				[ {
					type: 'textStyle/span',
					attributes: { nodeName: 'a' }
				} ]
			],
			[
				'b',
				[ {
					type: 'textStyle/span',
					attributes: { nodeName: 'a' }
				} ]
			],
			{
				type: '/paragraph'
			},
			{
				type: 'internalList'
			},
			{
				type: '/internalList'
			}
		],
		fromDataBody: '<a>ab</a>'
	},
	'list item with space followed by link': {
		body: '<ul><li><p> <a href="Foobar">bar</a></p></li></ul>',
		base: 'http://example.com/Foo',
		data: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem' },
			{ type: 'paragraph', internal: { whitespace: [ undefined, ' ' ] } },
			[ 'b', [ ve.dm.example.link( 'Foobar' ) ] ],
			[ 'a', [ ve.dm.example.link( 'Foobar' ) ] ],
			[ 'r', [ ve.dm.example.link( 'Foobar' ) ] ],
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace between unwrapped inline nodes': {
		body: '<foobar>c</foobar> <foobar>d</foobar>\n<foobar>e</foobar>',
		data: [
			{
				type: 'paragraph',
				internal: {
					generated: 'wrapper'
				}
			},
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<foobar>c</foobar>' )
			},
			{ type: '/alienInline' },
			' ',
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<foobar>d</foobar>' )
			},
			{ type: '/alienInline' },
			'\n',
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<foobar>e</foobar>' )
			},
			{ type: '/alienInline' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation in headings': {
		body: '<h2>Foo</h2><h2> Bar</h2><h2>Baz </h2><h2>  Quux   </h2>',
		data: [
			{ type: 'heading', attributes: { level: 2 } },
			'F',
			'o',
			'o',
			{ type: '/heading' },
			{
				type: 'heading',
				attributes: { level: 2 },
				internal: { whitespace: [ undefined, ' ' ] }
			},
			'B',
			'a',
			'r',
			{ type: '/heading' },
			{
				type: 'heading',
				attributes: { level: 2 },
				internal: { whitespace: [ undefined, undefined, ' ' ] }
			},
			'B',
			'a',
			'z',
			{ type: '/heading' },
			{
				type: 'heading',
				attributes: { level: 2 },
				internal: { whitespace: [ undefined, '  ', '   ' ] }
			},
			'Q',
			'u',
			'u',
			'x',
			{ type: '/heading' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation in list items': {
		body: '<ul><li>Foo</li><li> Bar</li><li>Baz </li><li>  Quux   </li></ul>',
		data: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'F',
			'o',
			'o',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: 'listItem', internal: { whitespace: [ undefined, ' ' ] } },
			{ type: 'paragraph', internal: { whitespace: [ ' ' ], generated: 'wrapper' } },
			'B',
			'a',
			'r',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: 'listItem', internal: { whitespace: [ undefined, undefined, ' ' ] } },
			{ type: 'paragraph', internal: { whitespace: [ undefined, undefined, undefined, ' ' ], generated: 'wrapper' } },
			'B',
			'a',
			'z',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: 'listItem', internal: { whitespace: [ undefined, '  ', '   ' ] } },
			{
				type: 'paragraph',
				internal: { whitespace: [ '  ', undefined, undefined, '   ' ], generated: 'wrapper' }
			},
			'Q',
			'u',
			'u',
			'x',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation with annotations': {
		body: '<p> <i>  Foo   </i>    </p>',
		data: [
			{
				type: 'paragraph',
				internal: { whitespace: [ undefined, ' ', '    ' ] }
			},
			[ ' ', [ ve.dm.example.italic ] ],
			[ ' ', [ ve.dm.example.italic ] ],
			[ 'F', [ ve.dm.example.italic ] ],
			[ 'o', [ ve.dm.example.italic ] ],
			[ 'o', [ ve.dm.example.italic ] ],
			[ ' ', [ ve.dm.example.italic ] ],
			[ ' ', [ ve.dm.example.italic ] ],
			[ ' ', [ ve.dm.example.italic ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<p>   <i>Foo</i>       </p>'
	},
	'outer whitespace preservation in a list with bare text and a wrapper paragraph': {
		body: '\n<ul>\n\n<li>\n\n\nBa re\n\n\n\n</li>\n\n\n\n\n<li>\t<p>\t\tP\t\t\t</p>\t\t\t\t</li>\t\n</ul>\t\n\t\n',
		data: [
			{ type: 'list', attributes: { style: 'bullet' }, internal: { whitespace: [ '\n', '\n\n', '\t\n', '\t\n\t\n' ] } },
			{ type: 'listItem', internal: { whitespace: [ '\n\n', '\n\n\n', '\n\n\n\n', '\n\n\n\n\n' ] } },
			{ type: 'paragraph', internal: { generated: 'wrapper', whitespace: [ '\n\n\n', undefined, undefined, '\n\n\n\n' ] } },
			'B',
			'a',
			' ',
			'r',
			'e',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: 'listItem', internal: { whitespace: [ '\n\n\n\n\n', '\t', '\t\t\t\t', '\t\n' ] } },
			{ type: 'paragraph', internal: { whitespace: [ '\t', '\t\t', '\t\t\t', '\t\t\t\t' ] } },
			'P',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		innerWhitespace: [ '\n', '\t\n\t\n' ]
	},
	'outer whitespace preservation in a list with bare text and a sublist': {
		body: '<ul>\n<li>\n\nBa re\n\n\n<ul>\n\n\n\n<li> <p>  P   </p>    </li>\t</ul>\t\t</li>\t\t\t</ul>',
		data: [
			{ type: 'list', attributes: { style: 'bullet' }, internal: { whitespace: [ undefined, '\n', '\t\t\t' ] } },
			{ type: 'listItem', internal: { whitespace: [ '\n', '\n\n', '\t\t', '\t\t\t' ] } },
			{ type: 'paragraph', internal: { generated: 'wrapper', whitespace: [ '\n\n', undefined, undefined, '\n\n\n' ] } },
			'B',
			'a',
			' ',
			'r',
			'e',
			{ type: '/paragraph' },
			{ type: 'list', attributes: { style: 'bullet' }, internal: { whitespace: [ '\n\n\n', '\n\n\n\n', '\t', '\t\t' ] } },
			{ type: 'listItem', internal: { whitespace: [ '\n\n\n\n', ' ', '    ', '\t' ] } },
			{ type: 'paragraph', internal: { whitespace: [ ' ', '  ', '   ', '    ' ] } },
			'P',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation leaves non-edge content whitespace alone': {
		body: '<p> A  B   <b>    C\t</b>\t\tD\t\t\t</p>\nE\n\nF\n\n\n<b>\n\n\n\nG </b>  H   ',
		data: [
			{ type: 'paragraph', internal: { whitespace: [ undefined, ' ', '\t\t\t', '\n' ] } },
			'A',
			' ',
			' ',
			'B',
			' ',
			' ',
			' ',
			[ ' ', [ ve.dm.example.bold ] ],
			[ ' ', [ ve.dm.example.bold ] ],
			[ ' ', [ ve.dm.example.bold ] ],
			[ ' ', [ ve.dm.example.bold ] ],
			[ 'C', [ ve.dm.example.bold ] ],
			[ '\t', [ ve.dm.example.bold ] ],
			'\t',
			'\t',
			'D',
			{ type: '/paragraph' },
			{ type: 'paragraph', internal: { generated: 'wrapper', whitespace: [ '\n', undefined, undefined, '   ' ] } },
			'E',
			'\n',
			'\n',
			'F',
			'\n',
			'\n',
			'\n',
			[ '\n', [ ve.dm.example.bold ] ],
			[ '\n', [ ve.dm.example.bold ] ],
			[ '\n', [ ve.dm.example.bold ] ],
			[ '\n', [ ve.dm.example.bold ] ],
			[ 'G', [ ve.dm.example.bold ] ],
			[ ' ', [ ve.dm.example.bold ] ],
			' ',
			' ',
			'H',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		innerWhitespace: [ undefined, '   ' ],
		fromDataBody: '<p> A  B       <b>C</b>\t\t\tD\t\t\t</p>\nE\n\nF\n\n\n\n\n\n\n<b>G</b>   H   '
	},
	'whitespace preservation with non-edge content whitespace with nested annotations': {
		body: '<p> A  B   <b>    C\t<i>\t\tD\t\t\t</i>\t\t\t\tE\n</b>\n\nF\n\n\n</p>',
		data: [
			{ type: 'paragraph', internal: { whitespace: [ undefined, ' ', '\n\n\n' ] } },
			'A',
			' ',
			' ',
			'B',
			' ',
			' ',
			' ',
			[ ' ', [ ve.dm.example.bold ] ],
			[ ' ', [ ve.dm.example.bold ] ],
			[ ' ', [ ve.dm.example.bold ] ],
			[ ' ', [ ve.dm.example.bold ] ],
			[ 'C', [ ve.dm.example.bold ] ],
			[ '\t', [ ve.dm.example.bold ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ 'D', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold ] ],
			[ '\t', [ ve.dm.example.bold ] ],
			[ '\t', [ ve.dm.example.bold ] ],
			[ '\t', [ ve.dm.example.bold ] ],
			[ 'E', [ ve.dm.example.bold ] ],
			[ '\n', [ ve.dm.example.bold ] ],
			'\n',
			'\n',
			'F',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<p> A  B       <b>C\t\t\t<i>D</i>\t\t\t\t\t\t\tE</b>\n\n\nF\n\n\n</p>'
	},
	'whitespace preservation with tightly nested annotations': {
		body: '<p> A  B   <b><i>\t\tC\t\t\t</i></b>\n\nD\n\n\n</p>',
		data: [
			{ type: 'paragraph', internal: { whitespace: [ undefined, ' ', '\n\n\n' ] } },
			'A',
			' ',
			' ',
			'B',
			' ',
			' ',
			' ',
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ 'C', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			'\n',
			'\n',
			'D',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<p> A  B   \t\t<b><i>C</i></b>\t\t\t\n\nD\n\n\n</p>'
	},
	'whitespace preservation with nested annotations with whitespace on the left side': {
		body: '<p> A  B   <b>\n\t<i>\t\tC\t\t\t</i></b>\n\nD\n\n\n</p>',
		data: [
			{ type: 'paragraph', internal: { whitespace: [ undefined, ' ', '\n\n\n' ] } },
			'A',
			' ',
			' ',
			'B',
			' ',
			' ',
			' ',
			[ '\n', [ ve.dm.example.bold ] ],
			[ '\t', [ ve.dm.example.bold ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ 'C', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			'\n',
			'\n',
			'D',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<p> A  B   \n\t\t\t<b><i>C</i></b>\t\t\t\n\nD\n\n\n</p>'
	},
	'whitespace preservation with nested annotations with whitespace on the right side': {
		body: '<p> A  B   <b><i>\t\tC\t\t\t</i>\n\t</b>\n\nD\n\n\n</p>',
		data: [
			{ type: 'paragraph', internal: { whitespace: [ undefined, ' ', '\n\n\n' ] } },
			'A',
			' ',
			' ',
			'B',
			' ',
			' ',
			' ',
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ 'C', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\n', [ ve.dm.example.bold ] ],
			[ '\t', [ ve.dm.example.bold ] ],
			'\n',
			'\n',
			'D',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<p> A  B   \t\t<b><i>C</i></b>\t\t\t\n\t\n\nD\n\n\n</p>'
	},
	'whitespace preservation with aliens': {
		body: ' <div rel="ve:Alien">  <br>   </div>    <p>\tFoo\t\t<foobar>\t\t\tBar\t\t\t\t</foobar>\nBaz\n\n<foobar>\n\n\nQuux\n\n\n\n</foobar> \tWhee \n</p>\t\n<div rel="ve:Alien">\n\tYay \t </div> \n ',
		data: [
			{
				type: 'alienBlock',
				originalDomElements: $.parseHTML( '<div rel="ve:Alien">  <br>   </div>' ),
				internal: {
					whitespace: [ ' ', undefined, undefined, '    ' ]
				}
			},
			{ type: '/alienBlock' },
			{ type: 'paragraph', internal: { whitespace: [ '    ', '\t', ' \n', '\t\n' ] } },
			'F',
			'o',
			'o',
			'\t',
			'\t',
			{ type: 'alienInline', originalDomElements: $.parseHTML( '<foobar>\t\t\tBar\t\t\t\t</foobar>' ) },
			{ type: '/alienInline' },
			'\n',
			'B',
			'a',
			'z',
			'\n',
			'\n',
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<foobar>\n\n\nQuux\n\n\n\n</foobar>' )
			},
			{ type: '/alienInline' },
			' ',
			'\t',
			'W',
			'h',
			'e',
			'e',
			{ type: '/paragraph' },
			{
				type: 'alienBlock',
				originalDomElements: $.parseHTML( '<div rel="ve:Alien">\n\tYay \t </div>' ),
				internal: {
					whitespace: [ '\t\n', undefined, undefined, ' \n ' ]
				}
			},
			{ type: '/alienBlock' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		innerWhitespace: [ ' ', ' \n ' ]
	},
	'whitespace preservation not triggered inside <pre>': {
		body: '\n<pre>\n\n\nFoo\n\n\nBar\n\n\n\n</pre>\n\n\n\n\n',
		data: [
			{ type: 'preformatted', internal: { whitespace: [ '\n', undefined, undefined, '\n\n\n\n\n' ] } },
			'\n',
			'\n',
			'F',
			'o',
			'o',
			'\n',
			'\n',
			'\n',
			'B',
			'a',
			'r',
			'\n',
			'\n',
			'\n',
			'\n',
			{ type: '/preformatted' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		innerWhitespace: [ '\n', '\n\n\n\n\n' ]
	},
	'whitespace preservation in table cell starting with text and ending with annotation': {
		body: '<table><tbody><tr><td>Foo <b>Bar</b></td></tr></tbody></table>',
		data: [
			{ type: 'table' },
			{ type: 'tableSection', attributes: { style: 'body' } },
			{ type: 'tableRow' },
			{ type: 'tableCell', attributes: { style: 'data' } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'F',
			'o',
			'o',
			' ',
			[ 'B', [ ve.dm.example.bold ] ],
			[ 'a', [ ve.dm.example.bold ] ],
			[ 'r', [ ve.dm.example.bold ] ],
			{ type: '/paragraph' },
			{ type: '/tableCell' },
			{ type: '/tableRow' },
			{ type: '/tableSection' },
			{ type: '/table' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation with wrapped text and metas': {
		body: '<meta /> <meta />\nFoo',
		data: [
			{
				type: 'alienMeta',
				internal: {
					whitespace: [
						undefined,
						undefined,
						undefined,
						' '
					]
				},
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			{
				type: 'alienMeta',
				internal: {
					whitespace: [
						' ',
						undefined,
						undefined,
						'\n'
					]
				},
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			{
				type: 'paragraph',
				internal: {
					whitespace: [
						'\n'
					],
					generated: 'wrapper'
				}
			},
			'F',
			'o',
			'o',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation with wrapped text and comments': {
		body: '<!-- Foo --> <!-- Bar -->\nFoo',
		data: [
			{
				type: 'paragraph',
				internal: {
					generated: 'wrapper'
				}
			},
			{
				type: 'comment',
				attributes: {
					text: ' Foo '
				}
			},
			{ type: '/comment' },
			' ',
			{
				type: 'comment',
				attributes: {
					text: ' Bar '
				}
			},
			{ type: '/comment' },
			'\n',
			'F',
			'o',
			'o',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		clipboardBody: '<span rel="ve:Comment" data-ve-comment=" Foo ">&nbsp;</span> <span rel="ve:Comment" data-ve-comment=" Bar ">&nbsp;</span>\nFoo',
		previewBody: ve.dm.example.commentNodePreview( ' Foo ' ) + ' ' + ve.dm.example.commentNodePreview( ' Bar ' ) + '↵Foo'
	},
	'whitespace preservation with comments at end of wrapper paragraph': {
		body: '<ul><li> bar<!-- baz -->quux </li></ul>',
		data: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{
				type: 'listItem',
				internal: {
					whitespace: [
						undefined,
						' ',
						' '
					]
				}
			},
			{
				type: 'paragraph',
				internal: {
					generated: 'wrapper',
					whitespace: [
						' ',
						undefined,
						undefined,
						' '
					]
				}
			},
			'b', 'a', 'r',
			{
				type: 'comment',
				attributes: {
					text: ' baz '
				}
			},
			{ type: '/comment' },
			'q', 'u', 'u', 'x',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		clipboardBody: '<ul><li> bar<span rel="ve:Comment" data-ve-comment=" baz ">&nbsp;</span>quux </li></ul>',
		previewBody: '<ul><li> bar' + ve.dm.example.commentNodePreview( ' baz ' ) + 'quux </li></ul>'
	},
	'whitespace preservation with metadata and space at end of wrapper paragraph': {
		body: '<ul><li> bar<meta />quux </li></ul>',
		data: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{
				type: 'listItem',
				internal: {
					whitespace: [
						undefined,
						' ',
						' '
					]
				}
			},
			{
				type: 'paragraph',
				internal: {
					generated: 'wrapper',
					whitespace: [
						' ',
						undefined,
						undefined,
						' '
					]
				}
			},
			'b', 'a', 'r',
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			'q', 'u', 'u', 'x',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{
				type: 'listItem',
				internal: {
					whitespace: [
						undefined,
						' ',
						' '
					]
				}
			},
			{
				type: 'paragraph',
				internal: {
					generated: 'wrapper',
					whitespace: [
						' ',
						undefined,
						undefined,
						' '
					]
				}
			},
			'b', 'a', 'r',
			'q', 'u', 'u', 'x',
			{ type: '/paragraph' },
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation with meta at end of wrapper paragraph': {
		body: '<ul><li> bar<meta /> </li></ul>',
		data: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{
				type: 'listItem',
				internal: {
					whitespace: [
						undefined,
						' ',
						' '
					]
				}
			},
			{
				type: 'paragraph',
				internal: {
					generated: 'wrapper',
					whitespace: [
						' '
					]
				}
			},
			'b', 'a', 'r',
			{ type: '/paragraph' },
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' ),
				internal: {
					whitespace: [
						undefined,
						undefined,
						undefined,
						' '
					]
				}
			},
			{ type: '/alienMeta' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{
				type: 'listItem',
				internal: {
					whitespace: [
						undefined,
						' ',
						' '
					]
				}
			},
			{
				type: 'paragraph',
				internal: {
					generated: 'wrapper',
					whitespace: [
						' '
					]
				}
			},
			'b', 'a', 'r',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			// FIXME! <alienMeta></alienMeta> should be before </listItem>. T189826
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' ),
				internal: {
					whitespace: [
						undefined,
						undefined,
						undefined,
						' '
					]
				}
			},
			{ type: '/alienMeta' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation with multiple metas at end of wrapper paragraph': {
		body: '<ul><li> foo <meta /> <meta /> </li></ul>',
		data: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{
				type: 'listItem',
				internal: {
					whitespace: [
						undefined,
						' ',
						' '
					]
				}
			},
			{
				type: 'paragraph',
				internal: {
					generated: 'wrapper',
					whitespace: [
						' ',
						undefined,
						undefined,
						' '
					]
				}
			},
			'f', 'o', 'o',
			{ type: '/paragraph' },
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' ),
				internal: {
					whitespace: [
						' ',
						undefined,
						undefined,
						' '
					]
				}
			},
			{ type: '/alienMeta' },
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' ),
				internal: {
					whitespace: [
						' ',
						undefined,
						undefined,
						' '
					]
				}
			},
			{ type: '/alienMeta' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{
				type: 'listItem',
				internal: {
					whitespace: [
						undefined,
						' ',
						' '
					]
				}
			},
			{
				type: 'paragraph',
				internal: {
					generated: 'wrapper',
					whitespace: [
						' ',
						undefined,
						undefined,
						' '
					]
				}
			},
			'f', 'o', 'o',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			// FIXME! <alienMeta></alienMeta> should be before </listItem>. T189826
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' ),
				internal: {
					whitespace: [
						' ',
						undefined,
						undefined,
						' '
					]
				}
			},
			{ type: '/alienMeta' },
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' ),
				internal: {
					whitespace: [
						' ',
						undefined,
						undefined,
						' '
					]
				}
			},
			{ type: '/alienMeta' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation with comment at start or end of element': {
		body: '<p> <!-- foo -->bar<!-- baz --> </p>',
		data: [
			{
				type: 'paragraph',
				internal: {
					whitespace: [
						undefined,
						' ',
						' '
					]
				}
			},
			{
				type: 'comment',
				attributes: {
					text: ' foo '
				}
			},
			{ type: '/comment' },
			'b', 'a', 'r',
			{
				type: 'comment',
				attributes: {
					text: ' baz '
				}
			},
			{ type: '/comment' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		clipboardBody: '<p> <span rel="ve:Comment" data-ve-comment=" foo ">&nbsp;</span>bar<span rel="ve:Comment" data-ve-comment=" baz ">&nbsp;</span> </p>',
		previewBody: '<p> ' + ve.dm.example.commentNodePreview( ' foo ' ) + 'bar' + ve.dm.example.commentNodePreview( ' baz ' ) + ' </p>'
	},
	'non-breaking spaces not treated as whitespace': {
		body: '<p>  &nbsp;&nbsp;foo&nbsp;\t</p>',
		data: [
			{ type: 'paragraph', internal: { whitespace: [ undefined, '  ', '\t' ] } },
			'\u00a0', '\u00a0', 'f', 'o', 'o', '\u00a0',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace surrounding metadata in a wrapper': {
		body: '<b>Foo</b> <meta />\n<i>Bar</i>',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			[ 'F', [ ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.bold ] ],
			' ',
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			'\n',
			[ 'B', [ ve.dm.example.italic ] ],
			[ 'a', [ ve.dm.example.italic ] ],
			[ 'r', [ ve.dm.example.italic ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace surrounding metadata in a wrapper followed by inline node': {
		body: '<b>Foo</b> <meta />\n<span rel="ve:Alien"></span>',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			[ 'F', [ ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.bold ] ],
			' ',
			{
				type: 'alienMeta',
				originalDomElements: $.parseHTML( '<meta />' )
			},
			{ type: '/alienMeta' },
			'\n',
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<span rel="ve:Alien"></span>' )
			},
			{ type: '/alienInline' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation in empty branch node': {
		body: '<table>\n\n</table>',
		data: [
			{ type: 'table', internal: { whitespace: [ undefined, '\n\n' ] } },
			{ type: '/table' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation in empty list item': {
		body: '<ul><li>\n\t</li></ul>',
		data: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem', internal: { whitespace: [ undefined, '\n\t' ] } },
			{ type: 'paragraph', internal: { generated: 'wrapper', whitespace: [ '\n\t' ] } },
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation in body with only plain text': {
		body: '  Hello\n\t',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper', whitespace: [ '  ', undefined, undefined, '\n\t' ] } },
			'H', 'e', 'l', 'l', 'o',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		innerWhitespace: [ '  ', '\n\t' ]
	},
	'whitespace preservation in empty body': {
		body: '\n\t',
		data: [
			{ type: 'paragraph', internal: { generated: 'empty', whitespace: [ '\n\t' ] } },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		innerWhitespace: [ '\n\t', undefined ]
	},
	'mismatching whitespace data is ignored': {
		data: [
			{ type: 'list', attributes: { style: 'bullet' }, internal: { whitespace: [ ' ', '  ', '   ', '    ' ] } },
			{ type: 'listItem', internal: { whitespace: [ ' ', '  ', '   ', '    ' ] } },
			{ type: 'paragraph', internal: { whitespace: [ ' ', '\t', '\n', '  ' ] } },
			'A',
			{ type: '/paragraph' },
			{ type: 'paragraph', internal: { whitespace: [ '  ' ] } },
			'B',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: 'listItem', internal: { whitespace: [ undefined, ' ', '\n' ] } },
			{ type: 'paragraph', internal: { generated: 'empty' } },
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		innerWhitespace: [ '\t', '\n' ],
		normalizedBody: '<ul><li><p>\tA\n</p>  <p>B</p></li><li></li></ul>'
	},
	'whitespace is trimmed from the edges of annotations when serializing': {
		body: '<p>A <b> B <i> C\t</i> </b><u>\nD\t</u></p>',
		data: [
			{ type: 'paragraph' },
			'A',
			' ',
			[ ' ', [ ve.dm.example.bold ] ],
			[ 'B', [ ve.dm.example.bold ] ],
			[ ' ', [ ve.dm.example.bold ] ],
			[ ' ', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ 'C', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ '\t', [ ve.dm.example.bold, ve.dm.example.italic ] ],
			[ ' ', [ ve.dm.example.bold ] ],
			[ '\n', [ ve.dm.example.underline ] ],
			[ 'D', [ ve.dm.example.underline ] ],
			[ '\t', [ ve.dm.example.underline ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<p>A  <b>B  <i>C</i></b>\t \n<u>D</u>\t</p>'
	},
	'annotation whitespace trimming does not create empty annotations': {
		body: '<p>A<b> </b> <b>B</b></p>',
		data: [
			{ type: 'paragraph' },
			'A',
			[ ' ', [ ve.dm.example.bold ] ],
			' ',
			[ 'B', [ ve.dm.example.bold ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<p>A  <b>B</b></p>'
	},
	'order of nested annotations is preserved': {
		body: '<p><b><u><i>Foo</i></u></b></p>',
		data: [
			{ type: 'paragraph' },
			[
				'F',
				[
					ve.dm.example.bold,
					ve.dm.example.underline,
					ve.dm.example.italic
				]
			],
			[
				'o',
				[
					ve.dm.example.bold,
					ve.dm.example.underline,
					ve.dm.example.italic

				]
			],
			[
				'o',
				[
					ve.dm.example.bold,
					ve.dm.example.underline,
					ve.dm.example.italic

				]
			],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'nested annotations are closed and reopened in the correct order': {
		body: '<p><a href="Foo">F<b>o<i>o</i></b><i>b</i></a><i>a<b>r</b>b<u>a</u>z</i></p>',
		base: 'http://example.com/Bar/Baz',
		data: [
			{ type: 'paragraph' },
			[ 'F', [ ve.dm.example.link( 'Foo' ) ] ],
			[ 'o', [ ve.dm.example.link( 'Foo' ), ve.dm.example.bold ] ],
			[ 'o', [ ve.dm.example.link( 'Foo' ), ve.dm.example.bold, ve.dm.example.italic ] ],
			[ 'b', [ ve.dm.example.link( 'Foo' ), ve.dm.example.italic ] ],
			[ 'a', [ ve.dm.example.italic ] ],
			[ 'r', [ ve.dm.example.italic, ve.dm.example.bold ] ],
			[ 'b', [ ve.dm.example.italic ] ],
			[ 'a', [ ve.dm.example.italic, ve.dm.example.underline ] ],
			[ 'z', [ ve.dm.example.italic ] ],
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'about grouping': {
		body: ve.dm.example.singleLine`
			<div rel="ve:Alien" about="#vet1">Foo</div>
			<div rel="ve:Alien" about="#vet1">Bar</div>
			<div rel="ve:Alien" about="#vet2">Baz</div>
			<foobar about="#vet2">Quux</foobar>
			<p>Whee</p>
			<foobar about="#vet2">Yay</foobar>
			<div rel="ve:Alien" about="#vet2">Blah</div>
			<foobar about="#vet3">Meh</foobar>
		`,
		data: [
			{
				type: 'alienBlock',
				originalDomElements: $.parseHTML( ve.dm.example.singleLine`
					<div rel="ve:Alien" about="#vet1">Foo</div>
					<div rel="ve:Alien" about="#vet1">Bar</div>
				` )
			},
			{ type: '/alienBlock' },
			{
				type: 'alienBlock',
				originalDomElements: $.parseHTML( ve.dm.example.singleLine`
					<div rel="ve:Alien" about="#vet2">Baz</div>
					<foobar about="#vet2">Quux</foobar>
				` )
			},
			{ type: '/alienBlock' },
			{ type: 'paragraph' },
			'W',
			'h',
			'e',
			'e',
			{ type: '/paragraph' },
			{
				type: 'alienBlock',
				originalDomElements: $.parseHTML( ve.dm.example.singleLine`
					<foobar about="#vet2">Yay</foobar>
					<div rel="ve:Alien" about="#vet2">Blah</div>
				` )
			},
			{ type: '/alienBlock' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<foobar about="#vet3">Meh</foobar>' )
			},
			{ type: '/alienInline' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'about group separated by whitespace is split': {
		body: '<div rel="ve:Alien" about="#vet1">Foo</div>\t<div rel="ve:Alien" about="#vet1">Bar</div>',
		data: [
			{
				type: 'alienBlock',
				originalDomElements: $.parseHTML( '<div rel="ve:Alien" about="#vet1">Foo</div>' ),
				internal: {
					whitespace: [ undefined, undefined, undefined, '\t' ]
				}
			},
			{ type: '/alienBlock' },
			{
				type: 'alienBlock',
				originalDomElements: $.parseHTML( '<div rel="ve:Alien" about="#vet1">Bar</div>' ),
				internal: {
					whitespace: [ '\t' ]
				}
			},
			{ type: '/alienBlock' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'about group separated by text is split': {
		body: '<p><span rel="ve:Alien" about="#vet1">Foo</span>X<span rel="ve:Alien" about="#vet1">Bar</span></p>',
		data: [
			{ type: 'paragraph' },
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<span rel="ve:Alien" about="#vet1">Foo</span>' )
			},
			{ type: '/alienInline' },
			'X',
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<span rel="ve:Alien" about="#vet1">Bar</span>' )
			},
			{ type: '/alienInline' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace preservation with an about group': {
		body: ' <div rel="ve:Alien" about="#vet1">\tFoo\t\t</div>' +
			'<div rel="ve:Alien" about="#vet1">  Bar   </div>    ',
		data: [
			{
				type: 'alienBlock',
				originalDomElements: $.parseHTML( '<div rel="ve:Alien" about="#vet1">\tFoo\t\t</div>' +
						'<div rel="ve:Alien" about="#vet1">  Bar   </div>' ),
				internal: {
					whitespace: [ ' ', undefined, undefined, '    ' ]
				}
			},
			{ type: '/alienBlock' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		innerWhitespace: [ ' ', '    ' ]
	},
	'block node inside annotation node is alienated': {
		body: '<span>\n<p>Bar</p></span>',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			[ '\n', [ ve.dm.example.span ] ],
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<p>Bar</p>' ),
				annotations: [ ve.dm.example.span ]
			},
			{ type: '/alienInline' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '\n<span><p>Bar</p></span>'
	},
	'block node inside annotation node surrounded by tables': {
		body: '<table></table><span>\n<p>Bar</p></span><table></table>',
		data: [
			{ type: 'table' },
			{ type: '/table' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			[ '\n', [ ve.dm.example.span ] ],
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<p>Bar</p>' ),
				annotations: [ ve.dm.example.span ]
			},
			{ type: '/alienInline' },
			{ type: '/paragraph' },
			{ type: 'table' },
			{ type: '/table' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<table></table>\n<span><p>Bar</p></span><table></table>'
	},
	'block node inside annotation node is alienated and continues wrapping': {
		body: 'Foo<span>\n<p>Bar</p></span>Baz',
		data: [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'F',
			'o',
			'o',
			[ '\n', [ ve.dm.example.span ] ],
			{
				type: 'alienInline',
				originalDomElements: $.parseHTML( '<p>Bar</p>' ),
				annotations: [ ve.dm.example.span ]
			},
			{ type: '/alienInline' },
			'B',
			'a',
			'z',
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: 'Foo\n<span><p>Bar</p></span>Baz'
	},
	'list item wrapped in annotation is alienated': {
		body: '<ul><li>Foo</li><s><li>Bar</li></s><li>Baz</li></ul>',
		data: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'F',
			'o',
			'o',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{
				type: 'alienBlock',
				originalDomElements: $.parseHTML( '<s><li>Bar</li></s>' )
			},
			{ type: '/alienBlock' },
			{ type: 'listItem' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'B',
			'a',
			'z',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'whitespace before meta node in wrapping mode': {
		body: '<table><tbody><tr><td>Foo\n<meta content="bar" /></td></tr></tbody></table>',
		data: [
			{ type: 'table' },
			{ type: 'tableSection', attributes: { style: 'body' } },
			{ type: 'tableRow' },
			{
				type: 'tableCell',
				attributes: { style: 'data' },
				internal: { whitespace: [ undefined, undefined, '\n' ] }
			},
			{
				type: 'paragraph',
				internal: {
					generated: 'wrapper',
					whitespace: [ undefined, undefined, undefined, '\n' ]
				}
			},
			'F',
			'o',
			'o',
			{ type: '/paragraph' },
			{
				type: 'alienMeta',
				internal: { whitespace: [ '\n' ] },
				originalDomElements: $.parseHTML( '<meta content="bar" />' )
			},
			{ type: '/alienMeta' },
			{ type: '/tableCell' },
			{ type: '/tableRow' },
			{ type: '/tableSection' },
			{ type: '/table' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'table' },
			{ type: 'tableSection', attributes: { style: 'body' } },
			{ type: 'tableRow' },
			{
				type: 'tableCell',
				attributes: { style: 'data' },
				internal: { whitespace: [ undefined, undefined, '\n' ] }
			},
			{
				type: 'paragraph',
				internal: {
					generated: 'wrapper',
					whitespace: [ undefined, undefined, undefined, '\n' ]
				}
			},
			'F',
			'o',
			'o',
			{ type: '/paragraph' },
			{ type: '/tableCell' },
			// FIXME! <alienMeta></alienMeta> should be before </tableCell>. T189826
			{
				type: 'alienMeta',
				internal: { whitespace: [ '\n' ] },
				originalDomElements: $.parseHTML( '<meta content="bar" />' )
			},
			{ type: '/alienMeta' },
			{ type: '/tableRow' },
			{ type: '/tableSection' },
			{ type: '/table' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'table with merged cells': {
		body: ve.dm.example.mergedCellsHtml,
		data: ve.dm.example.mergedCells
	},
	'table with caption, head, foot and body': {
		body: ve.dm.example.complexTableHtml,
		data: ve.dm.example.complexTable
	},
	'div set to RTL with paragraph inside': {
		body: '<div style="direction: rtl;"><p>a<b>b</b>c<i>d</i>e</p></div>',
		data: [
			{ type: 'div' },
			{ type: 'paragraph' },
			'a',
			[ 'b', [ ve.dm.example.bold ] ],
			'c',
			[ 'd', [ ve.dm.example.italic ] ],
			'e',
			{ type: '/paragraph' },
			{ type: '/div' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		fromDataBody: '<div><p>a<b>b</b>c<i>d</i>e</p></div>'
	},
	'comment escaping': {
		body: '<p><!--&#x2D;Foo&#x2D;bar&#x2D;&#x2D;&#x3E;b&#x26;r&#x2D;--></p>',
		ignoreXmlWarnings: true,
		data: [
			{ type: 'paragraph' },
			{ type: 'comment', attributes: { text: '-Foo-bar-->b&r-' } },
			{ type: '/comment' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		clipboardBody: '<p><span rel="ve:Comment" data-ve-comment="-Foo-bar-->b&amp;r-">&nbsp;</span></p>',
		previewBody: '<p>' + ve.dm.example.commentNodePreview( '-Foo-bar-->b&r-' ) + '</p>'
	},
	'comment escaping is normalized': {
		body: '<p><!--&gt;Foo-bar--&gt;b&amp;r---></p>',
		ignoreXmlWarnings: true,
		data: [
			{ type: 'paragraph' },
			{ type: 'comment', attributes: { text: '>Foo-bar-->b&r-' } },
			{ type: '/comment' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		normalizedBody: '<p><!--&#x3E;Foo&#x2D;bar&#x2D;&#x2D;&#x3E;b&#x26;r&#x2D;--></p>',
		clipboardBody: '<p><span rel="ve:Comment" data-ve-comment=">Foo-bar-->b&amp;r-">&nbsp;</span></p>',
		previewBody: '<p>' + ve.dm.example.commentNodePreview( '>Foo-bar-->b&r-' ) + '</p>'
	},
	'comment as a child of node that can not handle comments (list)': {
		body: '<ul><li>foo</li><!--bar--></ul>',
		data: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'f',
			'o',
			'o',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			{ type: 'commentMeta', attributes: { text: 'bar' } },
			{ type: '/commentMeta' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'list', attributes: { style: 'bullet' } },
			{ type: 'listItem' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'f',
			'o',
			'o',
			{ type: '/paragraph' },
			{ type: '/listItem' },
			// FIXME! 'commentMeta' should not be a child of 'list'. T189543
			{ type: 'commentMeta', attributes: { text: 'bar' } },
			{ type: '/commentMeta' },
			{ type: '/list' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'comment as a child of node that can not handle comments (table row)': {
		body: '<table><tbody><tr><td>foo</td><!--bar--></tr></tbody></table>',
		data: [
			{ type: 'table' },
			{ type: 'tableSection', attributes: { style: 'body' } },
			{ type: 'tableRow' },
			{ type: 'tableCell', attributes: { style: 'data' } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'f',
			'o',
			'o',
			{ type: '/paragraph' },
			{ type: '/tableCell' },
			{ type: 'commentMeta', attributes: { text: 'bar' } },
			{ type: '/commentMeta' },
			{ type: '/tableRow' },
			{ type: '/tableSection' },
			{ type: '/table' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		realData: [
			{ type: 'table' },
			{ type: 'tableSection', attributes: { style: 'body' } },
			{ type: 'tableRow' },
			{ type: 'tableCell', attributes: { style: 'data' } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'f',
			'o',
			'o',
			{ type: '/paragraph' },
			{ type: '/tableCell' },
			// FIXME! 'commentMeta' should not be a child of 'tableRow'. T189543
			{ type: 'commentMeta', attributes: { text: 'bar' } },
			{ type: '/commentMeta' },
			{ type: '/tableRow' },
			{ type: '/tableSection' },
			{ type: '/table' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		]
	},
	'article and sections': {
		body: '<article><header>Foo</header><section>Bar</section><footer>Baz</footer></article>',
		data: [
			{ type: 'article' },
			{ type: 'section', attributes: { style: 'header' } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'F', 'o', 'o',
			{ type: '/paragraph' },
			{ type: '/section' },
			{ type: 'section', attributes: { style: 'section' } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'B', 'a', 'r',
			{ type: '/paragraph' },
			{ type: '/section' },
			{ type: 'section', attributes: { style: 'footer' } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'B', 'a', 'z',
			{ type: '/paragraph' },
			{ type: '/section' },
			{ type: '/article' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		ceHtml: ve.dm.example.singleLine`
			<article class="ve-ce-branchNode ve-ce-articleNode" contenteditable="false">
			<header class="ve-ce-branchNode ve-ce-activeNode ve-ce-sectionNode" contenteditable="true" spellcheck="true">
				${ ve.dm.example.ceWrapperParagraph }Foo</p>
			</header>
			<section class="ve-ce-branchNode ve-ce-activeNode ve-ce-sectionNode" contenteditable="true" spellcheck="true">
				${ ve.dm.example.ceWrapperParagraph }Bar</p>
			</section>
			<footer class="ve-ce-branchNode ve-ce-activeNode ve-ce-sectionNode" contenteditable="true" spellcheck="true">
				${ ve.dm.example.ceWrapperParagraph }Baz</p>
			</footer>
			</article>
		`
	},
	'other block nodes': {
		body: '<center>Foo</center><hr><blockquote>Bar</blockquote>',
		data: [
			{ type: 'center' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'F', 'o', 'o',
			{ type: '/paragraph' },
			{ type: '/center' },
			{ type: 'horizontalRule' },
			{ type: '/horizontalRule' },
			{ type: 'blockquote' },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'B', 'a', 'r',
			{ type: '/paragraph' },
			{ type: '/blockquote' },
			{ type: 'internalList' },
			{ type: '/internalList' }
		],
		ceHtml: ve.dm.example.singleLine`
			${ ve.dm.example.blockSlug }
			<center class="ve-ce-branchNode">
				${ ve.dm.example.ceWrapperParagraph }Foo</p>
			</center>
			${ ve.dm.example.blockSlug }
			<div class="ve-ce-focusableNode ve-ce-horizontalRuleNode" contenteditable="false">
				<hr class="ve-ce-leafNode">
			</div>
			${ ve.dm.example.blockSlug }
			<blockquote class="ve-ce-branchNode">
				${ ve.dm.example.ceWrapperParagraph }Bar</p>
			</blockquote>
			${ ve.dm.example.blockSlug }
		`
	}
};

ve.dm.example.isolationHtml = ve.dm.example.singleLine`
	<ul>
		<li>Item 1</li>
		<li>Item 2</li>
		<li>Item 3</li>
	</ul>
	Paragraph
	<ul>
		<li>Item 4</li>
		<li>Item 5</li>
		<li>Item 6</li>
	</ul>
	<table><tbody>
		<tr>
			<td>Cell 1</td><td>Cell 2</td><td>Cell 3</td></tr><tr><td>Cell 4</td>
		</tr>
	</tbody></table>
	Not allowed by dm:
	<ul>
		<li><h1>Title in list</h1></li>
		<li><pre>Preformatted in list</pre></li>
	</ul>
	<ul>
		<li>
			<ol>
				<li>Nested 1</li>
				<li>Nested 2</li>
				<li>Nested 3</li>
			</ol>
		</li>
	</ul>
	<ul>
		<li>
			<p>P1</p>
			<p>P2</p>
			<p>P3</p>
		</li>
	</ul>
`;

ve.dm.example.isolationData = [
	// 0
	{ type: 'list', attributes: { style: 'bullet' } },
	{ type: 'listItem' },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'I', 't', 'e', 'm', ' ', '1',
	{ type: '/paragraph' },
	// 10
	{ type: '/listItem' },
	{ type: 'listItem' },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'I', 't', 'e', 'm', ' ', '2',
	{ type: '/paragraph' },
	// 20
	{ type: '/listItem' },
	{ type: 'listItem' },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'I', 't', 'e', 'm', ' ', '3',
	{ type: '/paragraph' },
	// 30
	{ type: '/listItem' },
	{ type: '/list' },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'P', 'a', 'r', 'a', 'g', 'r', 'a',
	// 40
	'p', 'h',
	{ type: '/paragraph' },
	{ type: 'list', attributes: { style: 'bullet' } },
	{ type: 'listItem' },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'I', 't', 'e', 'm',
	// 50
	' ', '4',
	{ type: '/paragraph' },
	{ type: '/listItem' },
	{ type: 'listItem' },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'I', 't', 'e', 'm',
	// 60
	' ', '5',
	{ type: '/paragraph' },
	{ type: '/listItem' },
	{ type: 'listItem' },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'I', 't', 'e', 'm',
	// 70
	' ', '6',
	{ type: '/paragraph' },
	{ type: '/listItem' },
	{ type: '/list' },
	{ type: 'table' },
	{ type: 'tableSection', attributes: { style: 'body' } },
	{ type: 'tableRow' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	// 80
	'C', 'e', 'l', 'l', ' ', '1',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	// 90
	'C', 'e', 'l', 'l', ' ', '2',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	// 100
	'C', 'e', 'l', 'l', ' ', '3',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	{ type: '/tableRow' },
	{ type: 'tableRow' },
	// 110
	{ type: 'tableCell', attributes: { style: 'data' } },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'C', 'e', 'l', 'l', ' ', '4',
	{ type: '/paragraph' },
	{ type: '/tableCell' },
	// 120
	{ type: '/tableRow' },
	{ type: '/tableSection' },
	{ type: '/table' },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'N', 'o', 't', ' ', 'a', 'l',
	// 130
	'l', 'o', 'w', 'e', 'd', ' ', 'b', 'y', ' ', 'd',
	// 140
	'm', ':',
	{ type: '/paragraph' },
	{ type: 'list', attributes: { style: 'bullet' } },
	{ type: 'listItem' },
	{ type: 'heading', attributes: { level: 1 } },
	'T', 'i', 't', 'l',
	// 150
	'e', ' ', 'i', 'n', ' ', 'l', 'i', 's', 't',
	{ type: '/heading' },
	// 160
	{ type: '/listItem' },
	{ type: 'listItem' },
	{ type: 'preformatted' },
	'P', 'r', 'e', 'f', 'o', 'r', 'm',
	// 170
	'a', 't', 't', 'e', 'd', ' ', 'i', 'n', ' ', 'l',
	// 180
	'i', 's', 't',
	{ type: '/preformatted' },
	{ type: '/listItem' },
	{ type: '/list' },
	{ type: 'list', attributes: { style: 'bullet' } },
	{ type: 'listItem' },
	{ type: 'list', attributes: { style: 'number' } },
	{ type: 'listItem' },
	// 190
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'N', 'e', 's', 't', 'e', 'd', ' ', '1',
	{ type: '/paragraph' },
	// 200
	{ type: '/listItem' },
	{ type: 'listItem' },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'N', 'e', 's', 't', 'e', 'd', ' ',
	// 210
	'2',
	{ type: '/paragraph' },
	{ type: '/listItem' },
	{ type: 'listItem' },
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'N', 'e', 's', 't', 'e',
	// 220
	'd', ' ', '3',
	{ type: '/paragraph' },
	{ type: '/listItem' },
	{ type: '/list' },
	{ type: '/listItem' },
	{ type: '/list' },
	{ type: 'list', attributes: { style: 'bullet' } },
	{ type: 'listItem' },
	// 230
	{ type: 'paragraph' },
	'P', '1',
	{ type: '/paragraph' },
	{ type: 'paragraph' },
	'P', '2',
	{ type: '/paragraph' },
	{ type: 'paragraph' },
	'P',
	// 240
	'3',
	{ type: '/paragraph' },
	{ type: '/listItem' },
	{ type: '/list' },
	{ type: 'internalList' },
	{ type: '/internalList' }
	// 246
];

ve.dm.example.RDFaDoc = ve.dm.converter.getModelFromDom( ve.createDocumentFromHtml( ve.dm.example.singleLine`
	<p content="b" datatype="c" resource="f" rev="g"
		${ /* Non-RDFa attribute */'' }
		 class="i">
		Foo
	</p>
` ) );

ve.dm.example.UnboldableNode = function () {
	// Parent constructor
	ve.dm.example.UnboldableNode.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.UnboldableNode, ve.dm.LeafNode );
ve.dm.example.UnboldableNode.static.name = 'exampleUnboldable';
ve.dm.example.UnboldableNode.static.isContent = true;
ve.dm.example.UnboldableNode.static.disallowedAnnotationTypes = [ 'textStyle/bold' ];
ve.dm.example.UnboldableNode.static.matchTagNames = [];
ve.dm.modelRegistry.register( ve.dm.example.UnboldableNode );

ve.dm.example.IgnoreChildrenNode = function () {
	// Parent constructor
	ve.dm.example.IgnoreChildrenNode.super.apply( this, arguments );
};
OO.inheritClass( ve.dm.example.IgnoreChildrenNode, ve.dm.BranchNode );
ve.dm.example.IgnoreChildrenNode.static.name = 'exampleIgnoreChildren';
ve.dm.example.IgnoreChildrenNode.static.ignoreChildren = true;
ve.dm.example.IgnoreChildrenNode.static.matchTagNames = [];
ve.dm.modelRegistry.register( ve.dm.example.IgnoreChildrenNode );

ve.dm.example.annotationData = [
	{ type: 'paragraph' },
	'F',
	'o',
	'o',
	{ type: 'exampleUnboldable' },
	// 5
	{ type: '/exampleUnboldable' },
	'B',
	'a',
	'r',
	{ type: '/paragraph' },
	// 10
	{ type: 'exampleIgnoreChildren' },
	{ type: 'paragraph' },
	'B',
	{ type: '/paragraph' },
	{ type: 'exampleIgnoreChildren' },
	// 15
	{ type: 'paragraph' },
	'a',
	{ type: '/paragraph' },
	{ type: '/exampleIgnoreChildren' },
	{ type: 'paragraph' },
	// 20
	'r',
	{ type: '/paragraph' },
	{ type: '/exampleIgnoreChildren' },
	{ type: 'paragraph' },
	'B',
	// 25
	'a',
	'z',
	{ type: '/paragraph' },
	{ type: 'internalList' },
	{ type: '/internalList' }
	// 30
];

ve.dm.example.selectNodesCases = [
	{
		range: new ve.Range( 1 ),
		mode: 'branches',
		expected: [
			// heading
			{
				node: [ 0 ],
				range: new ve.Range( 1 ),
				index: 0,
				nodeRange: new ve.Range( 1, 4 ),
				nodeOuterRange: new ve.Range( 0, 5 ),
				parentOuterRange: new ve.Range( 0, 63 )
			}
		]
	},
	{
		range: new ve.Range( 10 ),
		mode: 'branches',
		expected: [
			// table/tableSection/tableRow/tableCell/paragraph
			{
				node: [ 1, 0, 0, 0, 0 ],
				range: new ve.Range( 10 ),
				index: 0,
				nodeRange: new ve.Range( 10, 11 ),
				nodeOuterRange: new ve.Range( 9, 12 ),
				parentOuterRange: new ve.Range( 8, 34 )
			}
		]
	},
	{
		range: new ve.Range( 20 ),
		mode: 'branches',
		expected: [
			// table/tableSection/tableRow/tableCell/list/listItem/list/listItem/paragraph
			{
				node: [ 1, 0, 0, 0, 1, 0, 1, 0, 0 ],
				range: new ve.Range( 20 ),
				index: 0,
				nodeRange: new ve.Range( 20, 21 ),
				nodeOuterRange: new ve.Range( 19, 22 ),
				parentOuterRange: new ve.Range( 18, 23 )
			}
		]
	},
	{
		range: new ve.Range( 1, 20 ),
		mode: 'branches',
		expected: [
			// heading
			{
				node: [ 0 ],
				range: new ve.Range( 1, 4 ),
				index: 0,
				nodeRange: new ve.Range( 1, 4 ),
				nodeOuterRange: new ve.Range( 0, 5 ),
				parentOuterRange: new ve.Range( 0, 63 )
			},

			// table/tableSection/tableRow/tableCell/paragraph
			{
				node: [ 1, 0, 0, 0, 0 ],
				index: 0,
				nodeRange: new ve.Range( 10, 11 ),
				nodeOuterRange: new ve.Range( 9, 12 ),
				parentOuterRange: new ve.Range( 8, 34 )
			},

			// table/tableSection/tableRow/tableCell/list/listItem/paragraph
			{
				node: [ 1, 0, 0, 0, 1, 0, 0 ],
				index: 0,
				nodeRange: new ve.Range( 15, 16 ),
				nodeOuterRange: new ve.Range( 14, 17 ),
				parentOuterRange: new ve.Range( 13, 25 )
			},

			// table/tableSection/tableRow/tableCell/list/listItem/list/listItem/paragraph
			{
				node: [ 1, 0, 0, 0, 1, 0, 1, 0, 0 ],
				range: new ve.Range( 20 ),
				index: 0,
				nodeRange: new ve.Range( 20, 21 ),
				nodeOuterRange: new ve.Range( 19, 22 ),
				parentOuterRange: new ve.Range( 18, 23 )
			}
		]
	},
	{
		range: new ve.Range( 1 ),
		mode: 'branches',
		expected: [
			// heading
			{
				node: [ 0 ],
				range: new ve.Range( 1 ),
				index: 0,
				nodeRange: new ve.Range( 1, 4 ),
				nodeOuterRange: new ve.Range( 0, 5 ),
				parentOuterRange: new ve.Range( 0, 63 )
			}
		]
	},
	{
		range: new ve.Range( 0, 3 ),
		mode: 'leaves',
		expected: [
			// heading/text
			{
				node: [ 0, 0 ],
				range: new ve.Range( 1, 3 ),
				index: 0,
				nodeRange: new ve.Range( 1, 4 ),
				nodeOuterRange: new ve.Range( 1, 4 ),
				parentOuterRange: new ve.Range( 0, 5 )
			}
		],
		msg: 'partial leaf results have ranges with global offsets'
	},
	{
		range: new ve.Range( 0, 11 ),
		mode: 'leaves',
		expected: [
			// heading/text
			{
				node: [ 0, 0 ],
				index: 0,
				nodeRange: new ve.Range( 1, 4 ),
				nodeOuterRange: new ve.Range( 1, 4 ),
				parentOuterRange: new ve.Range( 0, 5 )
			},
			// table/tableSection/tableRow/tableCell/paragraph/text
			{
				node: [ 1, 0, 0, 0, 0, 0 ],
				index: 0,
				nodeRange: new ve.Range( 10, 11 ),
				nodeOuterRange: new ve.Range( 10, 11 ),
				parentOuterRange: new ve.Range( 9, 12 )
			}
		],
		msg: 'leaf nodes do not have ranges, leaf nodes from different levels'
	},
	{
		range: new ve.Range( 29, 43 ),
		mode: 'leaves',
		expected: [
			// table/tableSection/tableRow/tableCell/list/listItem/paragraph/text
			{
				node: [ 1, 0, 0, 0, 2, 0, 0, 0 ],
				index: 0,
				nodeRange: new ve.Range( 29, 30 ),
				nodeOuterRange: new ve.Range( 29, 30 ),
				parentOuterRange: new ve.Range( 28, 31 )
			},
			// preformatted/text
			{
				node: [ 2, 0 ],
				index: 0,
				nodeRange: new ve.Range( 38, 39 ),
				nodeOuterRange: new ve.Range( 38, 39 ),
				parentOuterRange: new ve.Range( 37, 43 )
			},
			// preformatted/image
			{
				node: [ 2, 1 ],
				index: 1,
				nodeRange: new ve.Range( 40 ),
				nodeOuterRange: new ve.Range( 39, 41 ),
				parentOuterRange: new ve.Range( 37, 43 )
			},
			// preformatted/text
			{
				node: [ 2, 2 ],
				index: 2,
				nodeRange: new ve.Range( 41, 42 ),
				nodeOuterRange: new ve.Range( 41, 42 ),
				parentOuterRange: new ve.Range( 37, 43 )
			}
		],
		msg: 'leaf nodes that are not text nodes'
	},
	{
		range: new ve.Range( 2, 16 ),
		mode: 'siblings',
		expected: [
			// heading
			{
				node: [ 0 ],
				range: new ve.Range( 2, 4 ),
				index: 0,
				nodeRange: new ve.Range( 1, 4 ),
				nodeOuterRange: new ve.Range( 0, 5 ),
				parentOuterRange: new ve.Range( 0, 63 )
			},
			// table
			{
				node: [ 1 ],
				range: new ve.Range( 6, 16 ),
				index: 1,
				nodeRange: new ve.Range( 6, 36 ),
				nodeOuterRange: new ve.Range( 5, 37 ),
				parentOuterRange: new ve.Range( 0, 63 )
			}
		],
		msg: 'siblings at the document level'
	},
	{
		range: new ve.Range( 2, 51 ),
		mode: 'siblings',
		expected: [
			// heading
			{
				node: [ 0 ],
				range: new ve.Range( 2, 4 ),
				index: 0,
				nodeRange: new ve.Range( 1, 4 ),
				nodeOuterRange: new ve.Range( 0, 5 ),
				parentOuterRange: new ve.Range( 0, 63 )
			},
			// table
			{
				node: [ 1 ],
				index: 1,
				nodeRange: new ve.Range( 6, 36 ),
				nodeOuterRange: new ve.Range( 5, 37 ),
				parentOuterRange: new ve.Range( 0, 63 )
			},
			// preformatted
			{
				node: [ 2 ],
				index: 2,
				nodeRange: new ve.Range( 38, 42 ),
				nodeOuterRange: new ve.Range( 37, 43 ),
				parentOuterRange: new ve.Range( 0, 63 )
			},
			// definitionList
			{
				node: [ 3 ],
				range: new ve.Range( 44, 51 ),
				index: 3,
				nodeRange: new ve.Range( 44, 54 ),
				nodeOuterRange: new ve.Range( 43, 55 ),
				parentOuterRange: new ve.Range( 0, 63 )
			}
		],
		msg: 'more than 2 siblings at the document level'
	},
	{
		range: new ve.Range( 1 ),
		mode: 'leaves',
		expected: [
			// heading/text
			{
				node: [ 0, 0 ],
				range: new ve.Range( 1 ),
				index: 0,
				nodeRange: new ve.Range( 1, 4 ),
				nodeOuterRange: new ve.Range( 1, 4 ),
				parentOuterRange: new ve.Range( 0, 5 )
			}
		],
		msg: 'zero-length range at the start of a text node returns text node rather than parent'
	},
	{
		range: new ve.Range( 4 ),
		mode: 'leaves',
		expected: [
			// heading/text
			{
				node: [ 0, 0 ],
				range: new ve.Range( 4 ),
				index: 0,
				nodeRange: new ve.Range( 1, 4 ),
				nodeOuterRange: new ve.Range( 1, 4 ),
				parentOuterRange: new ve.Range( 0, 5 )
			}
		],
		msg: 'zero-length range at the end of a text node returns text node rather than parent'
	},
	{
		range: new ve.Range( 2, 3 ),
		mode: 'leaves',
		expected: [
			// heading/text
			{
				node: [ 0, 0 ],
				range: new ve.Range( 2, 3 ),
				index: 0,
				nodeRange: new ve.Range( 1, 4 ),
				nodeOuterRange: new ve.Range( 1, 4 ),
				parentOuterRange: new ve.Range( 0, 5 )
			}
		],
		msg: 'range entirely within one leaf node'
	},
	{
		range: new ve.Range( 5 ),
		mode: 'leaves',
		expected: [
			// document
			{
				node: [],
				range: new ve.Range( 5 ),
				// No 'index' because documentNode has no parent
				indexInNode: 1,
				nodeRange: new ve.Range( 0, 63 ),
				nodeOuterRange: new ve.Range( 0, 63 )
			}
		],
		msg: 'zero-length range between two children of the document'
	},
	{
		range: new ve.Range( 0 ),
		mode: 'leaves',
		expected: [
			// document
			{
				node: [],
				range: new ve.Range( 0 ),
				// No 'index' because documentNode has no parent
				indexInNode: 0,
				nodeRange: new ve.Range( 0, 63 ),
				nodeOuterRange: new ve.Range( 0, 63 )
			}
		],
		msg: 'zero-length range at the start of the document'
	},
	{
		range: new ve.Range( 32, 39 ),
		mode: 'leaves',
		expected: [
			// table/tableSection/tableRow/tableCell/list
			{
				node: [ 1, 0, 0, 0, 2 ],
				range: new ve.Range( 32 ),
				index: 2,
				indexInNode: 1,
				nodeRange: new ve.Range( 27, 32 ),
				nodeOuterRange: new ve.Range( 26, 33 )
			},
			// preformatted/text
			{
				node: [ 2, 0 ],
				// No 'range' because the text node is covered completely
				index: 0,
				nodeRange: new ve.Range( 38, 39 ),
				nodeOuterRange: new ve.Range( 38, 39 ),
				parentOuterRange: new ve.Range( 37, 43 )
			}
		],
		msg: 'range with 5 closings and a text node'
	},
	{
		range: new ve.Range( 2, 57 ),
		mode: 'covered',
		expected: [
			// heading/text
			{
				node: [ 0, 0 ],
				range: new ve.Range( 2, 4 ),
				index: 0,
				nodeRange: new ve.Range( 1, 4 ),
				nodeOuterRange: new ve.Range( 1, 4 ),
				parentOuterRange: new ve.Range( 0, 5 )
			},
			// table
			{
				node: [ 1 ],
				// no 'range' because the table is covered completely
				index: 1,
				nodeRange: new ve.Range( 6, 36 ),
				nodeOuterRange: new ve.Range( 5, 37 ),
				parentOuterRange: new ve.Range( 0, 63 )
			},
			// preformatted
			{
				node: [ 2 ],
				// No 'range' because the node is covered completely
				index: 2,
				nodeRange: new ve.Range( 38, 42 ),
				nodeOuterRange: new ve.Range( 37, 43 ),
				parentOuterRange: new ve.Range( 0, 63 )
			},
			// definitionList
			{
				node: [ 3 ],
				// No 'range' because the node is covered completely
				index: 3,
				nodeRange: new ve.Range( 44, 54 ),
				nodeOuterRange: new ve.Range( 43, 55 ),
				parentOuterRange: new ve.Range( 0, 63 )
			},
			// paragraph/text
			{
				node: [ 4, 0 ],
				// No 'range' because the text node is covered completely
				index: 0,
				nodeRange: new ve.Range( 56, 57 ),
				nodeOuterRange: new ve.Range( 56, 57 ),
				parentOuterRange: new ve.Range( 55, 58 )
			}
		],
		msg: 'range from the first heading into the second-to-last paragraph, in covered mode'
	},
	{
		range: new ve.Range( 14 ),
		mode: 'siblings',
		expected: [
			// table/tableSection/tableRow/tableCell/list/listItem
			{
				node: [ 1, 0, 0, 0, 1, 0 ],
				range: new ve.Range( 14 ),
				index: 0,
				indexInNode: 0,
				nodeRange: new ve.Range( 14, 24 ),
				nodeOuterRange: new ve.Range( 13, 25 )
			}
		],
		msg: 'zero-length range at the beginning of a listItem, in siblings mode'
	},
	{
		range: new ve.Range( 25, 27 ),
		mode: 'covered',
		expected: [
			// table/tableSection/tableRow/tableCell/list
			{
				node: [ 1, 0, 0, 0, 1 ],
				range: new ve.Range( 25 ),
				index: 1,
				indexInNode: 1,
				nodeRange: new ve.Range( 13, 25 ),
				nodeOuterRange: new ve.Range( 12, 26 )
			},
			// table/tableSection/tableRow/tableCell/list
			{
				node: [ 1, 0, 0, 0, 2 ],
				range: new ve.Range( 27 ),
				index: 2,
				indexInNode: 0,
				nodeRange: new ve.Range( 27, 32 ),
				nodeOuterRange: new ve.Range( 26, 33 )
			}
		],
		msg: 'range covering a list closing and a list opening'
	},
	{
		range: new ve.Range( 39 ),
		mode: 'leaves',
		expected: [
			// preformatted/text
			{
				node: [ 2, 0 ],
				range: new ve.Range( 39 ),
				index: 0,
				nodeRange: new ve.Range( 38, 39 ),
				nodeOuterRange: new ve.Range( 38, 39 ),
				parentOuterRange: new ve.Range( 37, 43 )
			}
		],
		msg: 'zero-length range in text node before inline node'
	},
	{
		range: new ve.Range( 41 ),
		mode: 'leaves',
		expected: [
			// preformatted/text
			{
				node: [ 2, 2 ],
				range: new ve.Range( 41 ),
				index: 2,
				nodeRange: new ve.Range( 41, 42 ),
				nodeOuterRange: new ve.Range( 41, 42 ),
				parentOuterRange: new ve.Range( 37, 43 )
			}
		],
		msg: 'zero-length range in text node after inline node'
	},
	{
		doc: 'emptyBranch',
		range: new ve.Range( 1 ),
		mode: 'leaves',
		expected: [
			// table
			{
				node: [ 0 ],
				range: new ve.Range( 1 ),
				index: 0,
				indexInNode: 0,
				nodeRange: new ve.Range( 1 ),
				nodeOuterRange: new ve.Range( 0, 2 ),
				parentOuterRange: new ve.Range( 0, 4 )
			}
		],
		msg: 'zero-length range in empty branch node'
	},
	{
		doc: 'internalData',
		range: new ve.Range( 6, 13 ),
		mode: 'leaves',
		expected: [
			// internal item
			{
				node: [ 1, 0 ],
				index: 0,
				nodeRange: new ve.Range( 7, 12 ),
				nodeOuterRange: new ve.Range( 6, 13 ),
				parentOuterRange: new ve.Range( 5, 21 )
			}
		],
		msg: 'range covering ignoreChildren node doesn\'t descend'
	}
];
