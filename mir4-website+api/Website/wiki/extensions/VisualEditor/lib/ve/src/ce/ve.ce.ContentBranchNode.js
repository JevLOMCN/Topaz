/*!
 * VisualEditor ContentEditable ContentBranchNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable content branch node.
 *
 * Content branch nodes can only have content nodes as children.
 *
 * @abstract
 * @extends ve.ce.BranchNode
 * @constructor
 * @param {ve.dm.BranchNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.ContentBranchNode = function VeCeContentBranchNode() {
	// Properties
	this.lastTransaction = null;
	// Parent constructor calls renderContents, so this must be set first
	this.rendered = false;
	this.unicornAnnotations = null;
	this.unicorns = null;

	// Parent constructor
	ve.ce.ContentBranchNode.super.apply( this, arguments );

	this.onClickHandler = this.onClick.bind( this );

	// Events
	this.connect( this, { childUpdate: 'onChildUpdate' } );
	this.model.connect( this, { detach: 'onModelDetach' } );
	// Some browsers allow clicking links inside contenteditable, such as in iOS Safari when the
	// keyboard is closed
	this.$element.on( 'click', this.onClickHandler );
};

/* Inheritance */

OO.inheritClass( ve.ce.ContentBranchNode, ve.ce.BranchNode );

/* Static Members */

/**
 * Whether Enter splits this node type. Must be true for ContentBranchNodes.
 *
 * Warning: overriding this to false in a subclass will cause crashes on Enter key handling.
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.ce.ContentBranchNode.static.splitOnEnter = true;

ve.ce.ContentBranchNode.static.autoFocus = true;

/* Static Methods */

/**
 * Append the return value of #getRenderedContents to a DOM element.
 *
 * @param {HTMLElement} container DOM element
 * @param {HTMLElement} wrapper Wrapper returned by #getRenderedContents
 */
ve.ce.ContentBranchNode.static.appendRenderedContents = function ( container, wrapper ) {
	function resolveOriginals( domElement ) {
		for ( var i = 0, len = domElement.childNodes.length; i < len; i++ ) {
			var child = domElement.childNodes[ i ];
			if ( child.veOrigNode ) {
				domElement.replaceChild( child.veOrigNode, child );
			} else if ( child.childNodes && child.childNodes.length ) {
				resolveOriginals( child );
			}
		}
	}

	/* Resolve references to the original nodes. */
	resolveOriginals( wrapper );
	while ( wrapper.firstChild ) {
		container.appendChild( wrapper.firstChild );
	}
};

/* Methods */

/**
 * @inheritdoc
 */
ve.ce.ContentBranchNode.prototype.initialize = function () {
	// Parent method
	ve.ce.ContentBranchNode.super.prototype.initialize.call( this );
	this.$element.addClass( 've-ce-contentBranchNode' );
};

/**
 * @inheritdoc
 */
ve.ce.ContentBranchNode.prototype.onSetup = function () {
	// Parent method
	ve.ce.ContentBranchNode.super.prototype.onSetup.apply( this, arguments );

	// DOM changes (duplicated from constructor in case this.$element is replaced)
	this.$element.addClass( 've-ce-contentBranchNode' );
};

/**
 * Handle click events.
 *
 * @param {jQuery.Event} e Click event
 */
ve.ce.ContentBranchNode.prototype.onClick = function ( e ) {
	if (
		// Only block clicks on links
		( e.target !== this.$element[ 0 ] && e.target.nodeName.toUpperCase() === 'A' ) &&
		// Don't prevent a modified click, which in some browsers deliberately opens the link
		( !e.altKey && !e.ctrlKey && !e.metaKey && !e.shiftKey )
	) {
		e.preventDefault();
	}
};

/**
 * Handle childUpdate events.
 *
 * Rendering is only done once per transaction. If a paragraph has multiple nodes in it then it's
 * possible to receive multiple `childUpdate` events for a single transaction such as annotating
 * across them. State is tracked by storing and comparing the length of the surface model's complete
 * history.
 *
 * This is used to automatically render contents.
 *
 * @param {ve.dm.Transaction} transaction
 */
ve.ce.ContentBranchNode.prototype.onChildUpdate = function ( transaction ) {
	if ( transaction === null || transaction === this.lastTransaction ) {
		this.lastTransaction = transaction;
		return;
	}
	this.renderContents();
};

/**
 * @inheritdoc
 */
ve.ce.ContentBranchNode.prototype.onSplice = function ( index, howmany ) {
	// Parent method
	ve.ce.ContentBranchNode.super.prototype.onSplice.apply( this, arguments );

	// FIXME T126025: adjust slugNodes indexes if isRenderingLocked. This should be
	// sufficient to keep this.slugNodes valid - only text changes can occur, which
	// cannot create a requirement for a new slug (it can make an existing slug
	// redundant, but it is harmless to leave it there).
	// TODO fix the use of ve.ce.DocumentNode and getSurface
	if (
		this.root instanceof ve.ce.DocumentNode &&
		this.root.getSurface().isRenderingLocked
	) {
		this.slugNodes.splice.apply( this.slugNodes, [ index, howmany ].concat( new Array( arguments.length - 2 ) ) );
	}

	// Rerender to make sure annotations are applied correctly
	this.renderContents();
};

/**
 * @inheritdoc
 */
ve.ce.ContentBranchNode.prototype.setupBlockSlugs = function () {
	// Respect render lock
	// TODO: Can this check be moved into the parent method?
	// TODO fix the use of ve.ce.DocumentNode and getSurface
	if (
		this.root instanceof ve.ce.DocumentNode &&
		this.root.getSurface().isRenderingLocked()
	) {
		return;
	}
	// Parent method
	ve.ce.ContentBranchNode.super.prototype.setupBlockSlugs.apply( this, arguments );
};

/**
 * @inheritdoc
 */
ve.ce.ContentBranchNode.prototype.setupInlineSlugs = function () {
	// Respect render lock
	// TODO: Can this check be moved into the parent method?
	// TODO fix the use of ve.ce.DocumentNode and getSurface
	if (
		this.root instanceof ve.ce.DocumentNode &&
		this.root.getSurface().isRenderingLocked()
	) {
		return;
	}
	// Parent method
	ve.ce.ContentBranchNode.super.prototype.setupInlineSlugs.apply( this, arguments );
};

/**
 * Get an HTML rendering of the contents.
 *
 * If you are actually going to append the result to a DOM, you need to
 * do this with #appendRenderedContents, which resolves the cloned
 * nodes returned by this function back to their originals.
 *
 * @return {HTMLElement} Wrapper containing rendered contents
 * @return {Object} return.unicornInfo Unicorn information
 */
ve.ce.ContentBranchNode.prototype.getRenderedContents = function () {
	var annotationsChanged,
		store = this.model.doc.getStore(),
		annotationSet = new ve.dm.AnnotationSet( store ),
		annotatedHtml = [],
		doc = this.getElementDocument(),
		wrapper = doc.createElement( 'div' ),
		current = wrapper,
		annotationStack = [],
		nodeStack = [],
		unicornInfo = {
			hasCursor: false,
			annotations: null,
			unicorns: null
		},
		buffer = '',
		node = this;

	// Source mode optimization
	if ( this.getModel().getDocument().sourceMode ) {
		wrapper.appendChild(
			document.createTextNode(
				this.getModel().getDocument().getDataFromNode( this.getModel() ).join( '' )
			)
		);
		wrapper.unicornInfo = unicornInfo;
		return wrapper;
	}

	function openAnnotation( annotation ) {
		var ann;
		annotationsChanged = true;
		if ( buffer !== '' ) {
			if ( current.nodeType === Node.TEXT_NODE ) {
				current.textContent += buffer;
			} else {
				current.appendChild( doc.createTextNode( buffer ) );
			}
			buffer = '';
		}
		// Create a new DOM node and descend into it
		annotation.store = store;
		ann = ve.ce.annotationFactory.create( annotation.getType(), annotation, node );
		ann.appendTo( current );
		annotationStack.push( ann );
		nodeStack.push( current );
		current = ann.getContentContainer();
	}

	function closeAnnotation() {
		var ann;
		annotationsChanged = true;
		if ( buffer !== '' ) {
			if ( current.nodeType === Node.TEXT_NODE ) {
				current.textContent += buffer;
			} else {
				current.appendChild( doc.createTextNode( buffer ) );
			}
			buffer = '';
		}
		// Traverse up
		ann = annotationStack.pop();
		ann.attachContents();
		current = nodeStack.pop();
	}

	var i, ilen;
	// Gather annotated HTML from the child nodes
	for ( i = 0, ilen = this.children.length; i < ilen; i++ ) {
		annotatedHtml = annotatedHtml.concat( this.children[ i ].getAnnotatedHtml() );
	}

	// Set relCursor to collapsed selection offset, or -1 if none
	// (in which case we don't need to worry about preannotation)
	var relCursor = -1;
	var dmSurface;
	if ( this.getRoot() ) {
		var ceSurface = this.getRoot().getSurface();
		dmSurface = ceSurface.getModel();
		// TODO: dmSurface is used again later but might not be set
		var dmSelection = dmSurface.getTranslatedSelection();
		if ( dmSelection instanceof ve.dm.LinearSelection && dmSelection.isCollapsed() ) {
			// Subtract 1 for CBN opening tag
			relCursor = dmSelection.getRange().start - this.getOffset() - 1;
		}
	}

	var unicorn;
	// Set cursor status for renderContents. If hasCursor, splice unicorn marker at the
	// collapsed selection offset. It will be rendered later if it is needed, else ignored
	if ( relCursor < 0 || relCursor > this.getLength() ) {
		unicornInfo.hasCursor = false;
	} else {
		unicornInfo.hasCursor = true;
		var offset = 0;
		for ( i = 0, ilen = annotatedHtml.length; i < ilen; i++ ) {
			var htmlItem = annotatedHtml[ i ][ 0 ];
			var childLength = ( typeof htmlItem === 'string' ) ? 1 : 2;
			if ( offset <= relCursor && relCursor < offset + childLength ) {
				unicorn = [
					{}, // Unique object, for testing object equality later
					dmSurface.getInsertionAnnotations().storeHashes
				];
				annotatedHtml.splice( i, 0, unicorn );
				break;
			}
			offset += childLength;
		}
		// Special case for final position
		if ( i === ilen && offset === relCursor ) {
			unicorn = [
				{}, // Unique object, for testing object equality later
				dmSurface.getInsertionAnnotations().storeHashes
			];
			annotatedHtml.push( unicorn );
		}
	}

	// Render HTML with annotations
	for ( i = 0, ilen = annotatedHtml.length; i < ilen; i++ ) {
		var item;
		var itemAnnotations;
		if ( Array.isArray( annotatedHtml[ i ] ) ) {
			item = annotatedHtml[ i ][ 0 ];
			itemAnnotations = new ve.dm.AnnotationSet( store, annotatedHtml[ i ][ 1 ] );
		} else {
			item = annotatedHtml[ i ];
			itemAnnotations = new ve.dm.AnnotationSet( store );
		}

		// annotationsChanged gets set to true by openAnnotation and closeAnnotation
		annotationsChanged = false;
		ve.dm.Converter.static.openAndCloseAnnotations( annotationSet, itemAnnotations,
			openAnnotation, closeAnnotation
		);

		// Handle the actual item
		if ( typeof item === 'string' ) {
			buffer += item;
		} else if ( unicorn && item === unicorn[ 0 ] ) {
			if ( annotationsChanged ) {
				if ( buffer !== '' ) {
					current.appendChild( doc.createTextNode( buffer ) );
					buffer = '';
				}
				var preUnicorn = doc.createElement( 'img' );
				var postUnicorn = doc.createElement( 'img' );
				preUnicorn.className = 've-ce-unicorn ve-ce-pre-unicorn';
				postUnicorn.className = 've-ce-unicorn ve-ce-post-unicorn';
				$( preUnicorn ).data( 'modelOffset', ( this.getOffset() + 1 + i ) );
				$( postUnicorn ).data( 'modelOffset', ( this.getOffset() + 1 + i ) );
				if ( ve.inputDebug ) {
					preUnicorn.setAttribute( 'src', ve.ce.unicornImgDataUri );
					postUnicorn.setAttribute( 'src', ve.ce.unicornImgDataUri );
					preUnicorn.className += ' ve-ce-unicorn-debug';
					postUnicorn.className += ' ve-ce-unicorn-debug';
				} else {
					preUnicorn.setAttribute( 'src', ve.ce.minImgDataUri );
					postUnicorn.setAttribute( 'src', ve.ce.minImgDataUri );
				}
				current.appendChild( preUnicorn );
				current.appendChild( postUnicorn );
				unicornInfo.annotations = dmSurface.getInsertionAnnotations();
				unicornInfo.unicorns = [ preUnicorn, postUnicorn ];
			} else {
				unicornInfo.annotations = null;
				unicornInfo.unicorns = null;
			}
		} else {
			if ( buffer !== '' ) {
				current.appendChild( doc.createTextNode( buffer ) );
				buffer = '';
			}
			// DOM equivalent of $( current ).append( item.clone() );
			for ( var j = 0, jlen = item.length; j < jlen; j++ ) {
				// Append a clone so as to not relocate the original node
				var clone = item[ j ].cloneNode( true );
				// Store a reference to the original node in a property
				clone.veOrigNode = item[ j ];
				current.appendChild( clone );
			}
		}
	}
	if ( buffer !== '' ) {
		current.appendChild( doc.createTextNode( buffer ) );
		buffer = '';
	}
	while ( annotationStack.length > 0 ) {
		closeAnnotation();
	}
	wrapper.unicornInfo = unicornInfo;
	return wrapper;
};

ve.ce.ContentBranchNode.prototype.onModelDetach = function () {
	// TODO fix the use of ve.ce.DocumentNode and getSurface
	if ( this.root instanceof ve.ce.DocumentNode ) {
		this.root.getSurface().setContentBranchNodeChanged();
	}
};

/**
 * Render contents.
 *
 * @return {boolean} Whether the contents have changed
 */
ve.ce.ContentBranchNode.prototype.renderContents = function () {
	var node = this;
	// TODO fix the use of ve.ce.DocumentNode and getSurface
	if (
		this.root instanceof ve.ce.DocumentNode &&
		this.root.getSurface().isRenderingLocked()
	) {
		return false;
	}

	if ( this.root instanceof ve.ce.DocumentNode ) {
		this.root.getSurface().setContentBranchNodeChanged();
	}

	var rendered = this.getRenderedContents();
	var unicornInfo = rendered.unicornInfo;

	// Return if unchanged. Test by building the new version and checking DOM-equality.
	// However we have to normalize to cope with consecutive text nodes. We can't normalize
	// the attached version, because that would close IMEs. As an optimization, don't perform
	// this checking if this node has never rendered before.

	if ( this.rendered ) {
		var oldWrapper = this.$element[ 0 ].cloneNode( true );
		$( oldWrapper )
			.find( '.ve-ce-annotation-active' )
			.removeClass( 've-ce-annotation-active' );
		$( oldWrapper )
			.find( '.ve-ce-branchNode-inlineSlug' )
			.children()
			.unwrap()
			.filter( '.ve-ce-chimera' )
			.remove();
		var newWrapper = this.$element[ 0 ].cloneNode( false );
		while ( rendered.firstChild ) {
			newWrapper.appendChild( rendered.firstChild );
		}
		oldWrapper.normalize();
		newWrapper.normalize();
		if ( newWrapper.isEqualNode( oldWrapper ) ) {
			return false;
		}
		rendered = newWrapper;
	}
	this.rendered = true;

	this.unicornAnnotations = unicornInfo.annotations || null;
	this.unicorns = unicornInfo.unicorns || null;

	// Detach all child nodes from this.$element
	for ( var i = 0, len = this.$element.length; i < len; i++ ) {
		var element = this.$element[ i ];
		while ( element.firstChild ) {
			element.removeChild( element.firstChild );
		}
	}

	// Reattach nodes
	this.constructor.static.appendRenderedContents( this.$element[ 0 ], rendered );

	// Set unicorning status
	if ( this.getRoot() ) {
		if ( !unicornInfo.hasCursor ) {
			this.getRoot().getSurface().setNotUnicorning( this );
		} else if ( this.unicorns ) {
			this.getRoot().getSurface().setUnicorning( this );
		} else {
			this.getRoot().getSurface().setNotUnicorningAll( this );
		}
	}

	// Add slugs
	this.setupInlineSlugs();

	// Highlight the node in debug mode
	if ( ve.inputDebug ) {
		this.$element.css( 'backgroundColor', '#eee' );
		setTimeout( function () {
			node.$element.css( 'backgroundColor', '' );
		}, 300 );
	}

	return true;
};

/**
 * @inheritdoc
 */
ve.ce.ContentBranchNode.prototype.detach = function () {
	if ( this.getRoot() ) {
		// This should be true, as the root is removed in the parent detach
		// method which hasn't run yet. However, just in case a node gets
		// double-detached…
		this.getRoot().getSurface().setNotUnicorning( this );
	}

	// Parent method
	ve.ce.ContentBranchNode.super.prototype.detach.call( this );
};

/**
 * @inheritdoc
 */
ve.ce.ContentBranchNode.prototype.destroy = function () {
	this.$element.off( 'click', this.onClickHandler );

	// Parent method
	ve.ce.ContentBranchNode.super.prototype.destroy.call( this );
};
