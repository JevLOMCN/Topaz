/*!
 * VisualEditor ContentEditable MWExtensionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki extension node.
 *
 * Configuration options for .update():
 * - extsrc: override the contents of the tag (string)
 * - attrs: override the attributes of the tag (object)
 *
 * @class
 * @abstract
 * @extends ve.ce.LeafNode
 * @mixins ve.ce.FocusableNode
 * @mixins ve.ce.GeneratedContentNode
 *
 * @constructor
 */
ve.ce.MWExtensionNode = function VeCeMWExtensionNode() {
	// Parent constructor
	ve.ce.MWExtensionNode.super.apply( this, arguments );

	// Mixin constructors
	ve.ce.FocusableNode.call( this, this.getFocusableElement() );
	ve.ce.GeneratedContentNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWExtensionNode, ve.ce.LeafNode );
OO.mixinClass( ve.ce.MWExtensionNode, ve.ce.FocusableNode );
OO.mixinClass( ve.ce.MWExtensionNode, ve.ce.GeneratedContentNode );

/* Static properties */

/**
 * Extension renders visible content when empty
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.ce.MWExtensionNode.static.rendersEmpty = false;

ve.ce.MWExtensionNode.static.iconWhenInvisible = 'markup';

ve.ce.MWExtensionNode.static.getDescription = function ( model ) {
	var body = ve.getProp( model.getAttribute( 'mw' ), 'body', 'extsrc' ) || '';
	return body.slice( 0, 100 ) + ( body.length > 100 ? '…' : '' );
};

/* Methods */

/**
 * @inheritdoc ve.ce.GeneratedContentNode
 */
ve.ce.MWExtensionNode.prototype.generateContents = function ( config ) {
	var deferred = ve.createDeferred(),
		mwData = ve.copy( this.getModel().getAttribute( 'mw' ) ),
		extsrc = config && config.extsrc !== undefined ? config.extsrc : ( ve.getProp( mwData, 'body', 'extsrc' ) || '' ),
		attrs = config && config.attrs || mwData.attrs,
		tagName = this.getModel().getExtensionName();

	// undefined means omit the attribute, not convert it to string 'undefined'
	for ( var attr in attrs ) {
		if ( attrs[ attr ] === undefined ) {
			delete attrs[ attr ];
		}
	}

	// XML-like tags in wikitext are not actually XML and don't expect their contents to be escaped.
	var wikitext = mw.html.element( tagName, attrs, new mw.html.Raw( extsrc ) );

	if ( this.constructor.static.rendersEmpty || extsrc.trim() !== '' ) {
		var xhr = ve.init.target.parseWikitextFragment( wikitext, false, this.getModel().getDocument() )
			.done( this.onParseSuccess.bind( this, deferred ) )
			.fail( this.onParseError.bind( this, deferred ) );
		return deferred.promise( { abort: xhr.abort } );
	} else {
		deferred.resolve( $( '<span>' ).text( '\u00a0' ).get() );
		return deferred.promise();
	}
};

/**
 * @inheritdoc
 */
ve.ce.MWExtensionNode.prototype.getRenderedDomElements = function () {
	// Parent method
	var elements = ve.ce.GeneratedContentNode.prototype.getRenderedDomElements.apply( this, arguments );

	if ( this.getModelHtmlDocument() ) {
		ve.init.platform.linkCache.styleParsoidElements(
			$( elements ),
			this.getModelHtmlDocument()
		);
	}
	return elements;
};

/**
 * Handle a successful response from the parser for the wikitext fragment.
 *
 * @param {jQuery.Deferred} deferred The Deferred object created by generateContents
 * @param {Object} response Response data
 */
ve.ce.MWExtensionNode.prototype.onParseSuccess = function ( deferred, response ) {
	var data = response.visualeditor,
		contentNodes = $( data.content ).get();
	deferred.resolve( contentNodes );
};

ve.ce.MWExtensionNode.prototype.afterRender = function () {
	var node = this,
		$images = this.$element
			.find( 'img:not([width]),img:not([height])' )
			.addBack( 'img:not([width]),img:not([height])' );

	// Mixin method
	ve.ce.GeneratedContentNode.prototype.afterRender.call( this );

	// Images missing a dimension change size after load
	// TODO: Ignore images which have dimensions defined in CSS, if performant
	if ( $images.length ) {
		$images.on( 'load', function () {
			// Mixin method
			ve.ce.GeneratedContentNode.prototype.afterRender.call( node );
		} );
	}
};

/**
 * Handle an unsuccessful response from the parser for the wikitext fragment.
 *
 * @param {jQuery.Deferred} deferred The promise object created by generateContents
 * @param {Object} response Response data
 */
ve.ce.MWExtensionNode.prototype.onParseError = function ( deferred ) {
	deferred.reject();
};
