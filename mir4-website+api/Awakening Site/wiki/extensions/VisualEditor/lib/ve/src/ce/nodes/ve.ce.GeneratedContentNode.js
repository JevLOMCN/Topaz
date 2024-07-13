/*!
 * VisualEditor ContentEditable GeneratedContentNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable generated content node.
 *
 * @class
 * @abstract
 *
 * @constructor
 */
ve.ce.GeneratedContentNode = function VeCeGeneratedContentNode() {
	// Properties
	this.generatingPromise = null;
	this.generatedContentsInvalid = null;

	// Events
	this.model.connect( this, { update: 'onGeneratedContentNodeUpdate' } );
	this.connect( this, { teardown: 'abortGenerating' } );

	// Initialization
	this.update();
};

/* Inheritance */

OO.initClass( ve.ce.GeneratedContentNode );

/* Events */

/**
 * @event setup
 */

/**
 * @event teardown
 */

/**
 * @event rerender
 */

/* Static members */

// We handle rendering ourselves, no need to render attributes from originalDomElements
ve.ce.GeneratedContentNode.static.renderHtmlAttributes = false;

/* Static methods */

/**
 * Wait for all content-generation within a given node to finish
 *
 * If no GeneratedContentNodes are within the node, a resolved promise will be
 * returned.
 *
 * @param  {ve.ce.View} view Any view node
 * @return {jQuery.Promise} Promise, resolved when content is generated
 */
ve.ce.GeneratedContentNode.static.awaitGeneratedContent = function ( view ) {
	var promises = [];

	function queueNode( node ) {
		if ( typeof node.generateContents === 'function' ) {
			if ( node.isGenerating() ) {
				var promise = ve.createDeferred();
				node.once( 'rerender', promise.resolve );
				promises.push( promise );
			}
		}
	}

	// Traverse children to see when they are all rerendered
	if ( view instanceof ve.ce.BranchNode ) {
		view.traverse( queueNode );
	} else {
		queueNode( view );
	}

	return ve.promiseAll( promises );
};

/* Abstract methods */

/**
 * Start a deferred process to generate the contents of the node.
 *
 * If successful, the returned promise must be resolved with the generated DOM elements passed
 * in as the first parameter, i.e. promise.resolve( domElements ); . Any other parameters to
 * .resolve() are ignored.
 *
 * If the returned promise object is abortable (has an .abort() method), .abort() will be called if
 * a newer update is started before the current update has finished. When a promise is aborted, it
 * should cease its work and shouldn't be resolved or rejected. If an outdated update's promise
 * is resolved or rejected anyway (which may happen if an aborted promise misbehaves, or if the
 * promise wasn't abortable), this is ignored and doneGenerating()/failGenerating() is not called.
 *
 * Additional data may be passed in the config object to instruct this function to render something
 * different than what's in the model. This data is implementation-specific and is passed through
 * by forceUpdate().
 *
 * @abstract
 * @method
 * @param {Object} [config] Optional additional data
 * @return {jQuery.Promise} Promise object, may be abortable
 */
ve.ce.GeneratedContentNode.prototype.generateContents = null;

/* Methods */

/**
 * Handler for the update event
 *
 * @param {boolean} staged Update happened in staging mode
 */
ve.ce.GeneratedContentNode.prototype.onGeneratedContentNodeUpdate = function ( staged ) {
	this.update( undefined, staged );
};

/**
 * Make an array of DOM elements suitable for rendering.
 *
 * Subclasses can override this to provide their own cleanup steps. This function takes an
 * array of DOM elements cloned within the source document and returns an array of DOM elements
 * cloned into the target document. If it's important that the DOM elements still be associated
 * with the original document, you should modify domElements before calling the parent
 * implementation, otherwise you should call the parent implementation first and modify its
 * return value.
 *
 * @param {Node[]} domElements Clones of the DOM elements from the store
 * @return {HTMLElement[]} Clones of the DOM elements in the right document, with modifications
 */
ve.ce.GeneratedContentNode.prototype.getRenderedDomElements = function ( domElements ) {
	var doc = this.getElementDocument();

	var rendering = this.filterRenderedDomElements(
		// Clone the elements into the target document
		ve.copyDomElements( domElements, doc )
	);

	if ( rendering.length ) {
		// Span wrap root text nodes so they can be measured
		rendering = rendering.map( function ( node ) {
			if ( node.nodeType === Node.TEXT_NODE ) {
				var span = document.createElement( 'span' );
				span.appendChild( node );
				return span;
			}
			return node;
		} );
		// Render the computed values of some attributes
		ve.resolveAttributes(
			rendering,
			domElements[ 0 ].ownerDocument,
			ve.dm.Converter.static.computedAttributes
		);
	} else {
		rendering = [ document.createElement( 'span' ) ];
	}

	if ( rendering.every( ve.isVoidElement ) ) {
		// Should contain at least one non-void element, e.g. for attaching
		// a visibility button in ve.ce.FocusableNode#updateInvisibleIconSync
		rendering.push( document.createElement( 'span' ) );
	}

	return rendering;
};

/**
 * Filter out elements from the rendered content which we don't want to display in the CE.
 *
 * @param {Node[]} domElements Clones of the DOM elements from the store, already copied into the document
 * @return {Node[]} DOM elements to keep
 */
ve.ce.GeneratedContentNode.prototype.filterRenderedDomElements = function ( domElements ) {
	return ve.filterMetaElements( domElements );
};

/**
 * Rerender the contents of this node.
 *
 * @param {Object|string|Array} generatedContents Generated contents, in the default case an HTMLElement array
 * @param {boolean} [staged] Update happened in staging mode
 * @fires setup
 * @fires teardown
 */
ve.ce.GeneratedContentNode.prototype.render = function ( generatedContents, staged ) {
	var node = this;
	if ( this.live ) {
		this.emit( 'teardown' );
	}
	var $newElements = $( this.getRenderedDomElements( ve.copyDomElements( generatedContents ) ) );
	this.generatedContentsInvalid = !this.validateGeneratedContents( $( generatedContents ) );
	if ( !staged || !this.generatedContentsInvalid ) {
		if ( !this.$element[ 0 ].parentNode ) {
			// this.$element hasn't been attached yet, so just overwrite it
			this.$element = $newElements;
		} else {
			// Switch out this.$element (which can contain multiple siblings) in place
			var lengthChange = this.$element.length !== $newElements.length;
			this.$element.first().replaceWith( $newElements );
			this.$element.remove();
			this.$element = $newElements;
			if ( lengthChange ) {
				// Changing the DOM node count can move the cursor, so re-apply
				// the cursor position from the model (T231094).
				setTimeout( function () {
					if ( node.getRoot() && node.getRoot().getSurface() ) {
						node.getRoot().getSurface().showModelSelection();
					}
				} );
			}
		}
	} else {
		this.generatedContentsValid = false;
		this.model.emit( 'generatedContentsError', $newElements );
	}

	// Prevent tabbing to focusable elements inside the editable surface
	this.preventTabbingInside();

	// Update focusable and resizable elements if necessary
	// TODO: Move these method definitions to their respective mixins.
	if ( this.$focusable ) {
		this.$focusable = this.getFocusableElement();
		this.$bounding = this.getBoundingElement();
	}
	if ( this.$resizable ) {
		this.$resizable = this.getResizableElement();
	}

	this.initialize();
	if ( this.live ) {
		this.emit( 'setup' );
	}

	this.afterRender();
};

/**
 * Prevent tabbing to focusable elements inside the editable surface, because it conflicts with
 * allowing tabbing out of the surface. (The surface takes the focus back when it moves to an
 * element inside it.)
 *
 * In the future, this might be implemented using the `inert` property, currently not supported by
 * any browser: https://html.spec.whatwg.org/multipage/interaction.html#inert-subtrees
 * https://caniuse.com/mdn-api_htmlelement_inert
 *
 * @private
 */
ve.ce.GeneratedContentNode.prototype.preventTabbingInside = function () {
	// Like OO.ui.findFocusable(), but find *all* such nodes rather than the first one.
	var selector = 'input, select, textarea, button, object, a, area, [contenteditable], [tabindex]',
		$focusableCandidates = this.$element.find( selector ).addBack( selector );

	$focusableCandidates.each( function () {
		var $this = $( this );
		if ( OO.ui.isFocusableElement( $this ) ) {
			$this.attr( 'tabindex', -1 );
		}
	} );
};

/**
 * Trigger rerender events after rendering the contents of the node.
 *
 * Nodes may override this method if the rerender event needs to be deferred (e.g. until images have loaded)
 *
 * @fires rerender
 */
ve.ce.GeneratedContentNode.prototype.afterRender = function () {
	this.emit( 'rerender' );
};

/**
 * Check whether the response HTML contains an error.
 *
 * The default implementation always returns true.
 *
 * @param {jQuery} $element The generated element
 * @return {boolean} There is no error
 */
ve.ce.GeneratedContentNode.prototype.validateGeneratedContents = function () {
	return true;
};

/**
 * Update the contents of this node based on the model and config data. If this combination of
 * model and config data has been rendered before, the cached rendering in the store will be used.
 *
 * @param {Object} [config] Optional additional data to pass to generateContents()
 * @param {boolean} [staged] Update happened in staging mode
 */
ve.ce.GeneratedContentNode.prototype.update = function ( config, staged ) {
	var store = this.model.doc.getStore(),
		contents = store.value( store.hashOfValue( null, OO.getHash( [ this.model.getHashObjectForRendering(), config ] ) ) );
	if ( contents ) {
		this.render( contents, staged );
	} else {
		this.forceUpdate( config, staged );
	}
};

/**
 * Force the contents to be updated. Like update(), but bypasses the store.
 *
 * @param {Object} [config] Optional additional data to pass to generateContents()
 * @param {boolean} [staged] Update happened in staging mode
 */
ve.ce.GeneratedContentNode.prototype.forceUpdate = function ( config, staged ) {
	if ( this.generatingPromise ) {
		// Abort the currently pending generation process if possible
		this.abortGenerating();
	} else {
		// Only call startGenerating if we weren't generating before
		this.startGenerating();
	}

	var node = this;
	// Create a new promise
	var promise = this.generatingPromise = this.generateContents( config );
	promise
		// If this promise is no longer the currently pending one, ignore it completely
		.done( function ( generatedContents ) {
			if ( node.generatingPromise === promise ) {
				node.doneGenerating( generatedContents, config, staged );
			}
		} )
		.fail( function () {
			if ( node.generatingPromise === promise ) {
				node.failGenerating();
			}
		} );
};

/**
 * Called when the node starts generating new content.
 *
 * This function is only called when the node wasn't already generating content. If a second update
 * comes in, this function will only be called if the first update has already finished (i.e.
 * doneGenerating or failGenerating has already been called).
 */
ve.ce.GeneratedContentNode.prototype.startGenerating = function () {
	this.$element.addClass( 've-ce-generatedContentNode-generating' );
};

/**
 * Abort the currently pending generation, if any, and remove the generating CSS class.
 *
 * This invokes .abort() on the pending promise if the promise has that method. It also ensures
 * that if the promise does get resolved or rejected later, this is ignored.
 */
ve.ce.GeneratedContentNode.prototype.abortGenerating = function () {
	var promise = this.generatingPromise;
	if ( promise ) {
		// Unset this.generatingPromise first so that if the promise is resolved or rejected
		// from within .abort(), this is ignored as it should be
		this.generatingPromise = null;
		if ( typeof promise.abort === 'function' ) {
			promise.abort();
		}
	}
	this.$element.removeClass( 've-ce-generatedContentNode-generating' );
};

/**
 * Called when the node successfully finishes generating new content.
 *
 * @param {Object|string|Array} generatedContents Generated contents
 * @param {Object} [config] Config object passed to forceUpdate()
 * @param {boolean} [staged] Update happened in staging mode
 */
ve.ce.GeneratedContentNode.prototype.doneGenerating = function ( generatedContents, config, staged ) {
	this.$element.removeClass( 've-ce-generatedContentNode-generating' );
	this.generatingPromise = null;

	// Because doneGenerating is invoked asynchronously, the model node may have become detached
	// in the meantime. Handle this gracefully.
	if ( this.model && this.model.doc ) {
		var store = this.model.doc.getStore();
		var hash = OO.getHash( [ this.model.getHashObjectForRendering(), config ] );
		store.hash( generatedContents, hash );
		this.render( generatedContents, staged );
	}
};

/**
 * Called when the GeneratedContentNode has failed to generate new content.
 */
ve.ce.GeneratedContentNode.prototype.failGenerating = function () {
	this.$element.removeClass( 've-ce-generatedContentNode-generating' );
	this.generatingPromise = null;
};

/**
 * Check whether this GeneratedContentNode is currently generating new content.
 *
 * @return {boolean} Whether we're generating
 */
ve.ce.GeneratedContentNode.prototype.isGenerating = function () {
	return !!this.generatingPromise;
};

/**
 * Get the focusable element
 *
 * @return {jQuery} Focusable element
 */
ve.ce.GeneratedContentNode.prototype.getFocusableElement = function () {
	return this.$element;
};

/**
 * Get the bounding element
 *
 * @return {jQuery} Bounding element
 */
ve.ce.GeneratedContentNode.prototype.getBoundingElement = function () {
	return this.$element;
};

/**
 * Get the resizable element
 *
 * @return {jQuery} Resizable element
 */
ve.ce.GeneratedContentNode.prototype.getResizableElement = function () {
	return this.$element;
};
