/*!
 * VisualEditor ContentEditable BranchNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable branch node.
 *
 * Branch nodes can have branch or leaf nodes as children.
 *
 * @class
 * @abstract
 * @extends ve.ce.Node
 * @mixins ve.BranchNode
 * @constructor
 * @param {ve.dm.BranchNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.BranchNode = function VeCeBranchNode( model ) {
	// Mixin constructor
	ve.BranchNode.call( this );

	// Parent constructor
	ve.ce.BranchNode.super.apply( this, arguments );

	// Properties
	this.tagName = this.$element.get( 0 ).nodeName.toLowerCase();
	this.slugNodes = [];

	// Events
	this.model.connect( this, { splice: 'onSplice' } );

	// Initialization
	this.onSplice.apply( this, [ 0, 0 ].concat( model.getChildren() ) );
};

/* Inheritance */

OO.inheritClass( ve.ce.BranchNode, ve.ce.Node );

OO.mixinClass( ve.ce.BranchNode, ve.BranchNode );

/* Static Properties */

/**
 * Inline slug template.
 *
 * @static
 * @property {HTMLElement}
 */
ve.ce.BranchNode.inlineSlugTemplate = ( function () {
	var profile = $.client.profile();
	var layout = profile.layout;
	var $img = $( '<img>' )
		.attr( {
			role: 'none',
			alt: ''
		} )
		.addClass( 've-ce-chimera' );
	if ( layout === 'webkit' || layout === 'gecko' ) {
		// The following classes are used here:
		// * ve-ce-chimera-gecko
		// * ve-ce-chimera-webkit
		$img.addClass( 've-ce-chimera-' + layout );
	}

	var $span = $( '<span>' )
		.addClass( 've-ce-branchNode-slug ve-ce-branchNode-inlineSlug' )
		.append( $img );

	// Support: Firefox <69
	// Firefox <=37 misbehaves if we don't set an src: https://bugzilla.mozilla.org/show_bug.cgi?id=989012
	// Firefox <69 misbehaves if we don't set an src and there is no sizing at node creation time: https://bugzilla.mozilla.org/show_bug.cgi?id=1267906
	// Setting an src in Chrome is slow, so only set it in affected versions of Firefox
	if ( layout === 'gecko' && profile.versionNumber < 69 ) {
		$img.prop( 'src', ve.ce.minImgDataUri );
	}
	return $span.get( 0 );
}() );

/**
 * Inline slug template for input debugging.
 *
 * @static
 * @property {HTMLElement}
 */
ve.ce.BranchNode.inputDebugInlineSlugTemplate = $( '<span>' )
	.addClass( 've-ce-branchNode-slug ve-ce-branchNode-inlineSlug' )
	.append(
		$( '<img>' )
			.addClass( 've-ce-chimera ve-ce-chimera-debug' )
			.attr( {
				src: ve.ce.chimeraImgDataUri,
				role: 'none',
				alt: ''
			} )
	)
	.get( 0 );

/**
 * Block slug template.
 *
 * @static
 * @property {HTMLElement}
 */
ve.ce.BranchNode.blockSlugTemplate = $( '<div>' )
	.addClass( 've-ce-branchNode-slug ve-ce-branchNode-blockSlug' )
	.get( 0 );

/* Methods */

/**
 * @inheritdoc
 */
ve.ce.BranchNode.prototype.initialize = function () {
	// Parent method
	ve.ce.BranchNode.super.prototype.initialize.call( this );

	this.$element.addClass( 've-ce-branchNode' );
};

/**
 * Update the DOM wrapper.
 *
 * WARNING: The contents, .data( 'view' ), the contentEditable property and any classes the wrapper
 * already has will be moved to  the new wrapper, but other attributes and any other information
 * added using $.data() will be lost upon updating the wrapper. To retain information added to the
 * wrapper, subscribe to the 'teardown' and 'setup' events, or override #initialize.
 *
 * @fires teardown
 * @fires setup
 */
ve.ce.BranchNode.prototype.updateTagName = function () {
	var tagName = this.getTagName();

	if ( tagName !== this.tagName ) {
		this.emit( 'teardown' );
		var wrapper = document.createElement( tagName );
		// Copy classes
		// eslint-disable-next-line mediawiki/class-doc
		wrapper.className = this.$element[ 0 ].className;
		// Copy contentEditable
		wrapper.contentEditable = this.$element[ 0 ].contentEditable;
		// Move contents
		while ( this.$element[ 0 ].firstChild ) {
			wrapper.appendChild( this.$element[ 0 ].firstChild );
		}
		// Swap elements
		if ( this.$element[ 0 ].parentNode ) {
			this.$element[ 0 ].parentNode.replaceChild( wrapper, this.$element[ 0 ] );
		}
		// Use new element from now on
		this.$element = $( wrapper );
		// Remember which tag name we are using now
		this.tagName = tagName;
		// Give subclasses the opportunity to touch the new element
		this.initialize();
		this.emit( 'setup' );

		// TODO fix the use of ve.ce.DocumentNode and getSurface
		if ( this.root instanceof ve.ce.DocumentNode ) {
			this.root.getSurface().setContentBranchNodeChanged();
		}
	}
};

/**
 * Handles model update events.
 *
 * @param {ve.dm.Transaction} transaction
 */
ve.ce.BranchNode.prototype.onModelUpdate = function ( transaction ) {
	this.emit( 'childUpdate', transaction );
};

/**
 * Handle splice events.
 *
 * ve.ce.Node objects are generated from the inserted ve.dm.Node objects, producing a view that's a
 * mirror of its model.
 *
 * @param {number} index Index to remove and or insert nodes at
 * @param {number} howmany Number of nodes to remove
 * @param {...ve.dm.BranchNode} [nodes] Variadic list of nodes to insert
 */
ve.ce.BranchNode.prototype.onSplice = function ( index ) {
	// attachedRoot and doc can be undefined in tests
	var dmDoc = this.getModel().getDocument(),
		attachedRoot = dmDoc && dmDoc.attachedRoot,
		isAllAttached = !attachedRoot || attachedRoot instanceof ve.dm.DocumentNode;

	var inAttachedRoot, upstreamOfAttachedRoot;
	if ( !isAllAttached ) {
		// Optimization: Skip traversal when whole doc is attached
		inAttachedRoot = this.getModel().isDownstreamOf( attachedRoot );
		upstreamOfAttachedRoot = attachedRoot.collectUpstream();
	}

	var i, length;
	var args = [];
	for ( i = 0, length = arguments.length; i < length; i++ ) {
		args.push( arguments[ i ] );
	}
	// Convert models to views and attach them to this node
	if ( args.length >= 3 ) {
		for ( i = 2, length = args.length; i < length; i++ ) {
			if (
				isAllAttached || inAttachedRoot || upstreamOfAttachedRoot.indexOf( args[ i ] ) !== -1 ||
				// HACK: An internal item node was requested directly, e.g. for preview (T228070)
				// TODO: Come up with a more explict way to skip the UnrenderedNode optimisation.
				args[ i ].findParent( ve.dm.InternalItemNode )
			) {
				args[ i ] = ve.ce.nodeFactory.createFromModel( args[ i ] );
				args[ i ].model.connect( this, { update: 'onModelUpdate' } );
			} else {
				args[ i ] = new ve.ce.UnrenderedNode( args[ i ] );
			}
		}
	}
	var removals = this.children.splice.apply( this.children, args );
	for ( i = 0, length = removals.length; i < length; i++ ) {
		removals[ i ].model.disconnect( this, { update: 'onModelUpdate' } );
		// Stop child listening to its model (e.g. for splice event)
		removals[ i ].model.disconnect( removals[ i ] );
		removals[ i ].setLive( false );
		removals[ i ].detach();
		removals[ i ].$element.detach();
		// And fare thee weel a while
		removals[ i ].destroy();
	}
	if ( args.length >= 3 ) {
		var fragment = document.createDocumentFragment();
		for ( i = args.length - 1; i >= 2; i-- ) {
			args[ i ].attach( this );
			for ( var j = args[ i ].$element.length - 1; j >= 0; j-- ) {
				fragment.insertBefore( args[ i ].$element[ j ], fragment.childNodes[ 0 ] || null );
			}
		}
		if ( fragment.childNodes.length ) {
			// Only calculate this if it's needed, this function looks expensive
			var position = this.getDomPosition( index );
			position.node.insertBefore(
				fragment,
				position.node.children[ position.offset ] || null
			);
		}
		for ( i = args.length - 1; i >= 2; i-- ) {
			if ( this.live !== args[ i ].isLive() ) {
				args[ i ].setLive( this.live );
			}
		}
	}

	// TODO: restructure to clarify the logic (exactly one of these is a no-op)
	this.setupBlockSlugs();
	this.setupInlineSlugs();
};

/**
 * Setup block slugs
 */
ve.ce.BranchNode.prototype.setupBlockSlugs = function () {
	// Only proceed if we are in a non-content node
	if ( this.canHaveChildrenNotContent() ) {
		this.setupSlugs( true );
	}
};

/**
 * Setup inline slugs
 */
ve.ce.BranchNode.prototype.setupInlineSlugs = function () {
	// Only proceed if we are in a content node
	if ( !this.canHaveChildrenNotContent() ) {
		this.setupSlugs( false );
	}
};

/**
 * Remove all slugs in this branch
 */
ve.ce.BranchNode.prototype.removeSlugs = function () {
	// Remove all slugs in this branch
	for ( var i in this.slugNodes ) {
		if ( this.slugNodes[ i ] !== undefined && this.slugNodes[ i ].parentNode ) {
			this.slugNodes[ i ].parentNode.removeChild( this.slugNodes[ i ] );
		}
		delete this.slugNodes[ i ];
	}
};

/**
 * Setup slugs where needed.
 *
 * Existing slugs will be removed before new ones are added.
 *
 * @param {boolean} isBlock Set up block slugs, otherwise setup inline slugs
 */
ve.ce.BranchNode.prototype.setupSlugs = function ( isBlock ) {
	// Source mode optimization
	if ( this.getModel().getDocument() && this.getModel().getDocument().sourceMode && isBlock ) {
		return;
	}

	var doc = this.getElementDocument();

	this.removeSlugs();

	var slugTemplate;
	if ( isBlock ) {
		slugTemplate = ve.ce.BranchNode.blockSlugTemplate;
	} else if ( ve.inputDebug ) {
		slugTemplate = ve.ce.BranchNode.inputDebugInlineSlugTemplate;
	} else {
		slugTemplate = ve.ce.BranchNode.inlineSlugTemplate;
	}

	for ( var i in this.getModel().slugPositions ) {
		var slugNode = doc.importNode( slugTemplate, true );
		// FIXME T126019: InternalListNode has an empty $element, so we assume that the slug goes
		// at the end instead. This is a hack and the internal list needs to die in a fire.
		if ( this.children[ i ] && this.children[ i ].$element[ 0 ] ) {
			var child = this.children[ i ].$element[ 0 ];
			// child.parentNode might not be equal to this.$element[ 0 ]: e.g. annotated inline nodes
			child.parentNode.insertBefore( slugNode, child );
		} else {
			this.$element[ 0 ].appendChild( slugNode );
		}
		this.slugNodes[ i ] = slugNode;
		if ( isBlock ) {
			var slugButton = new ve.ui.NoFocusButtonWidget( {
				tabIndex: -1,
				label: ve.msg( 'visualeditor-slug-insert' ),
				icon: 'add',
				framed: false
			} ).on( 'click', this.onSlugClick.bind( this, slugNode ) );
			$( slugNode ).append( slugButton.$element );
		}
	}
};

/**
 * Handle slug click events
 *
 * @param {HTMLElement} slugNode Slug node clicked
 */
ve.ce.BranchNode.prototype.onSlugClick = function ( slugNode ) {
	this.getRoot().getSurface().createSlug( slugNode );
};

/**
 * Get a slug at an offset.
 *
 * @param {number} offset Offset to get slug at
 * @return {HTMLElement|null}
 */
ve.ce.BranchNode.prototype.getSlugAtOffset = function ( offset ) {
	var startOffset = this.model.getOffset() + ( this.isWrapped() ? 1 : 0 );

	if ( offset === startOffset ) {
		return this.slugNodes[ 0 ] || null;
	}
	for ( var i = 0; i < this.children.length; i++ ) {
		startOffset += this.children[ i ].model.getOuterLength();
		if ( offset === startOffset ) {
			return this.slugNodes[ i + 1 ] || null;
		}
	}
	return null;
};

/**
 * Set live state on child nodes.
 *
 * @param {boolean} live New live state
 */
ve.ce.BranchNode.prototype.setLive = function ( live ) {
	// Parent method
	ve.ce.BranchNode.super.prototype.setLive.apply( this, arguments );

	for ( var i = 0; i < this.children.length; i++ ) {
		this.children[ i ].setLive( live );
	}
};

/**
 * Release all memory.
 */
ve.ce.BranchNode.prototype.destroy = function () {
	for ( var i = 0, len = this.children.length; i < len; i++ ) {
		this.children[ i ].destroy();
	}

	// Parent method
	ve.ce.BranchNode.super.prototype.destroy.call( this );
};

/**
 * @inheritdoc
 */
ve.ce.BranchNode.prototype.detach = function () {
	for ( var i = 0, len = this.children.length; i < len; i++ ) {
		this.children[ i ].detach();
	}

	// Parent method
	ve.ce.BranchNode.super.prototype.detach.call( this );
};

/**
 * Get the DOM position (node and offset) corresponding to a position in this node
 *
 * The node/offset have the same semantics as a DOM Selection focusNode/focusOffset
 *
 * @param {number} offset The offset inside this node of the required position
 * @return {Object|null} The DOM position
 * @return {Node} return.node DOM node; guaranteed to be this node's final DOM node
 * @return {number} return.offset DOM offset
 */
ve.ce.BranchNode.prototype.getDomPosition = function ( offset ) {
	var domNode = this.$element.last()[ 0 ];

	// Step backwards past empty nodes
	var ceNode;
	var i = offset - 1;
	while ( true ) {
		ceNode = this.children[ i-- ];
		if ( !ceNode ) {
			// No preceding children with DOM nodes
			return { node: domNode, offset: 0 };
		}
		if ( ceNode.$element && ceNode.$element.length > 0 ) {
			// Preceding child with a DOM node
			return {
				node: domNode,
				offset: Array.prototype.indexOf.call(
					domNode.childNodes,
					ceNode.$element.last()[ 0 ]
				) + 1
			};
		}
		if ( ceNode.getType() === 'text' ) {
			break;
		}
	}
	// Darn, we hit a text node. CE text nodes can contain varying annotations and so it is
	// difficult to calculate how many childNodes to skip. Let's try stepping forward instead.
	i = offset;
	while ( true ) {
		ceNode = this.children[ i++ ];
		if ( !ceNode ) {
			// No following children with DOM nodes
			return { node: domNode, offset: domNode.childNodes.length };
		}
		if ( ceNode.$element && ceNode.$element.length > 0 ) {
			// Following child with a DOM node
			return {
				node: domNode,
				offset: Array.prototype.indexOf.call(
					domNode.childNodes,
					ceNode.$element.first()[ 0 ]
				)
			};
		}
		if ( ceNode.getType() === 'text' ) {
			break;
		}
	}
	// Oh no, there's a text node in both directions
	throw new Error( 'Cannot calculate DOM position: adjacent text nodes' );
};
