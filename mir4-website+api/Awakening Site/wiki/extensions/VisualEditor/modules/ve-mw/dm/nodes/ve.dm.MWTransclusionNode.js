/*!
 * VisualEditor DataModel MWTransclusionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki transclusion node.
 *
 * @class
 * @abstract
 * @extends ve.dm.LeafNode
 * @mixins ve.dm.GeneratedContentNode
 * @mixins ve.dm.FocusableNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWTransclusionNode = function VeDmMWTransclusionNode() {
	// Parent constructor
	ve.dm.MWTransclusionNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.GeneratedContentNode.call( this );
	ve.dm.FocusableNode.call( this );

	// Properties
	this.partsList = null;

	// Events
	this.connect( this, { attributeChange: 'onAttributeChange' } );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWTransclusionNode, ve.dm.LeafNode );

OO.mixinClass( ve.dm.MWTransclusionNode, ve.dm.GeneratedContentNode );

OO.mixinClass( ve.dm.MWTransclusionNode, ve.dm.FocusableNode );

/* Static members */

ve.dm.MWTransclusionNode.static.name = 'mwTransclusion';

ve.dm.MWTransclusionNode.static.matchTagNames = null;

ve.dm.MWTransclusionNode.static.matchRdfaTypes = [ 'mw:Transclusion' ];

// Transclusion nodes can contain other types, e.g. mw:PageProp/Category.
// Allow all other types (null) so they match to this node.
ve.dm.MWTransclusionNode.static.allowedRdfaTypes = null;

// HACK: This prevents any rules with higher specificity from matching,
// e.g. LanguageAnnotation which uses a match function
ve.dm.MWTransclusionNode.static.matchFunction = function () {
	return true;
};

ve.dm.MWTransclusionNode.static.enableAboutGrouping = true;

// We handle rendering ourselves, no need to render attributes from originalDomElements (T207325),
// except for data-parsoid/RESTBase ID (T207325)
ve.dm.MWTransclusionNode.static.preserveHtmlAttributes = function ( attribute ) {
	return [ 'data-parsoid', 'id' ].indexOf( attribute ) !== -1;
};

ve.dm.MWTransclusionNode.static.getHashObject = function ( dataElement ) {
	return {
		type: dataElement.type,
		mw: dataElement.attributes.mw
	};
};

ve.dm.MWTransclusionNode.static.isDiffComparable = function ( element, other ) {
	function getTemplateNames( parts ) {
		return parts.map( function ( part ) {
			return part.template ? part.template.target.wt : '';
		} ).join( '|' );
	}

	return ve.dm.MWTransclusionNode.super.static.isDiffComparable.call( this, element, other ) &&
		getTemplateNames( element.attributes.mw.parts ) === getTemplateNames( other.attributes.mw.parts );
};

/**
 * Node type to use when the transclusion is inline
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.dm.MWTransclusionNode.static.inlineType = 'mwTransclusionInline';

/**
 * Node type to use when the transclusion is a block
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.dm.MWTransclusionNode.static.blockType = 'mwTransclusionBlock';

/**
 * Node type to use when the transclusion is cellable
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.dm.MWTransclusionNode.static.cellType = 'mwTransclusionTableCell';

ve.dm.MWTransclusionNode.static.toDataElement = function ( domElements, converter ) {
	var mwDataJSON = domElements[ 0 ].getAttribute( 'data-mw' ),
		mwData = mwDataJSON ? JSON.parse( mwDataJSON ) : {},
		isInline = this.isHybridInline( domElements, converter ),
		type = isInline ? this.inlineType : this.blockType;

	var dataElement = {
		type: type,
		attributes: {
			mw: mwData,
			originalMw: mwDataJSON
		}
	};

	if ( domElements.length === 1 && [ 'td', 'th' ].indexOf( domElements[ 0 ].nodeName.toLowerCase() ) !== -1 ) {
		dataElement.type = this.cellType;
		ve.dm.TableCellableNode.static.setAttributes( dataElement.attributes, domElements );
	}

	if ( !domElements[ 0 ].getAttribute( 'data-ve-no-generated-contents' ) ) {
		this.storeGeneratedContents( dataElement, domElements, converter.getStore() );
	}

	return dataElement;
};

ve.dm.MWTransclusionNode.static.toDomElements = function ( dataElement, doc, converter ) {
	var store = converter.getStore(),
		originalMw = dataElement.attributes.originalMw,
		originalDomElements = store.value( dataElement.originalDomElementsHash );

	function wrapTextNode( node ) {
		if ( node.nodeType === Node.TEXT_NODE ) {
			var wrapper = doc.createElement( 'span' );
			wrapper.appendChild( node );
			return wrapper;
		}
		return node;
	}

	var els;
	// If the transclusion is unchanged just send back the
	// original DOM elements so selser can skip over it
	if (
		originalDomElements &&
		originalMw && ve.compare( dataElement.attributes.mw, JSON.parse( originalMw ) )
	) {
		// originalDomElements is also used for CE rendering so return a copy
		els = ve.copyDomElements( originalDomElements, doc );
	} else {
		var value;
		if (
			converter.doesModeNeedRendering() &&
			// Use getHashObjectForRendering to get the rendering from the store
			( value = store.value( store.hashOfValue( null, OO.getHash( [ this.getHashObjectForRendering( dataElement ), undefined ] ) ) ) )
		) {
			// For the clipboard use the current DOM contents so the user has something
			// meaningful to paste into external applications
			els = ve.copyDomElements( value, doc );
			els[ 0 ] = wrapTextNode( els[ 0 ] );
		} else if ( originalDomElements ) {
			els = [ doc.createElement( originalDomElements[ 0 ].nodeName ) ];
		} else if ( dataElement.type === this.cellType ) {
			els = [ doc.createElement( dataElement.attributes.style === 'header' ? 'th' : 'td' ) ];
		} else {
			els = [ doc.createElement( 'span' ) ];
		}
		// All we need to send back to Parsoid is the original transclusion marker, with a
		// reconstructed data-mw property.
		els[ 0 ].setAttribute( 'typeof', 'mw:Transclusion' );
		els[ 0 ].setAttribute( 'data-mw', JSON.stringify( dataElement.attributes.mw ) );
	}
	if ( converter.isForClipboard() ) {
		// If the first element is a <link>, <meta> or <style> tag, e.g. a category or TemplateStyles,
		// ensure it is not destroyed by copy-paste by replacing it with a span
		if ( els[ 0 ].tagName === 'LINK' || els[ 0 ].tagName === 'META' || els[ 0 ].tagName === 'STYLE' ) {
			var span = doc.createElement( 'span' );
			span.setAttribute( 'typeof', 'mw:Transclusion' );
			span.setAttribute( 'data-mw', els[ 0 ].getAttribute( 'data-mw' ) );
			els[ 0 ] = span;
		}

		// Empty spans can get thrown around by Chrome when pasting, so give them a space
		if ( els[ 0 ].innerHTML === '' ) {
			els[ 0 ].appendChild( doc.createTextNode( '\u00a0' ) );
		}

		// Mark the data-mw element as not having valid generated contents with it in case it is
		// inserted into another editor (e.g. via paste).
		els[ 0 ].setAttribute( 'data-ve-no-generated-contents', true );

		// ... and mark all but the first child as ignorable
		for ( var i = 1; i < els.length; i++ ) {
			// Wrap plain text nodes so we can give them an attribute
			els[ i ] = wrapTextNode( els[ i ] );
			els[ i ].setAttribute( 'data-ve-ignore', 'true' );
		}
	} else if ( converter.isForPreview() ) {
		var modelNode = ve.dm.nodeFactory.createFromElement( dataElement );
		modelNode.setDocument( converter.internalList.getDocument() );
		var viewNode = ve.ce.nodeFactory.createFromModel( modelNode );
		// HACK: Node must be attached to check for rendering
		viewNode.$element.appendTo( 'body' );
		if ( !viewNode.hasRendering() ) {
			viewNode.$element.detach();
			viewNode.onSetup();
			// HACK: Force the icon to render immediately
			viewNode.updateInvisibleIconSync( true );
			els = viewNode.$element.toArray();
		}
		viewNode.destroy();
		viewNode.$element.detach();
	}
	return els;
};

ve.dm.MWTransclusionNode.static.describeChanges = function ( attributeChanges ) {
	var descriptions = [ ve.msg( 'visualeditor-changedesc-mwtransclusion' ) ];

	// This method assumes that the behavior of isDiffComparable above remains
	// the same, so it doesn't have to consider whether the actual template
	// involved has changed.

	function getLabel( par ) {
		// If a parameter is an object with a wt key, we just want the value of that.
		if ( par && par.wt !== undefined ) {
			// Can be `''`, and we're okay with that
			return par.wt;
		}
		return par;
	}

	if ( attributeChanges.mw.from.parts.length === 1 && attributeChanges.mw.to.parts.length === 1 ) {
		// Single-template transclusion, before and after. Relatively easy to summarize.
		// TODO: expand this to well-represent transclusions that contain multiple templates.

		// The bits of a template we care about are deeply-nested inside an
		// attribute. We'll restructure this so that we can pretend template
		// params are the direct attributes of the template.
		var params = {};
		var param;
		for ( param in attributeChanges.mw.from.parts[ 0 ].template.params ) {
			params[ param ] = { from: getLabel( attributeChanges.mw.from.parts[ 0 ].template.params[ param ] ) };
		}
		for ( param in attributeChanges.mw.to.parts[ 0 ].template.params ) {
			params[ param ] = ve.extendObject(
				{ to: getLabel( attributeChanges.mw.to.parts[ 0 ].template.params[ param ] ) },
				params[ param ]
			);
		}
		var paramChanges;
		for ( param in params ) {
			// All we know is that *something* changed, without the normal
			// helpful just-being-given-the-changed-bits, so we have to filter
			// this ourselves.
			// Trim string values, and convert empty strings to undefined
			var from = ( params[ param ].from || '' ).trim() || undefined,
				to = ( params[ param ].to || '' ).trim() || undefined;
			if ( from !== to ) {
				var change = this.describeChange( param, { from: from, to: to } );
				if ( change ) {
					if ( !paramChanges ) {
						paramChanges = document.createElement( 'ul' );
						descriptions.push( paramChanges );
					}
					var listItem = document.createElement( 'li' );
					if ( typeof change === 'string' ) {
						listItem.appendChild( document.createTextNode( change ) );
					} else {
						// eslint-disable-next-line no-loop-func
						change.forEach( function ( node ) {
							listItem.appendChild( node );
						} );
					}
					paramChanges.appendChild( listItem );
				}
			}
		}
	}
	return descriptions;
};

/**
 * @inheritdoc ve.dm.Node
 */
ve.dm.MWTransclusionNode.static.cloneElement = function () {
	// Parent method
	var clone = ve.dm.MWTransclusionNode.super.static.cloneElement.apply( this, arguments );
	delete clone.attributes.originalMw;
	return clone;
};

/**
 * Escape a template parameter. Helper function for #getWikitext.
 *
 * @static
 * @param {string} param Parameter value
 * @return {string} Escaped parameter value
 */
ve.dm.MWTransclusionNode.static.escapeParameter = function ( param ) {
	var input = param,
		output = '',
		inNowiki = false,
		bracketStack = 0,
		linkStack = 0;

	while ( input.length > 0 ) {
		var match = input.match( /\[\[|]]|{{|}}|\|+|<\/?nowiki>|<nowiki\s*\/>/ );
		if ( !match ) {
			output += input;
			break;
		}
		output += input.slice( 0, match.index );
		input = input.slice( match.index + match[ 0 ].length );

		if ( inNowiki ) {
			if ( match[ 0 ] === '</nowiki>' ) {
				inNowiki = false;
			}
			output += match[ 0 ];
			continue;
		}

		var needsNowiki = true;
		switch ( match[ 0 ].charAt( 0 ) ) {
			case '<':
				if ( match[ 0 ] === '<nowiki>' ) {
					inNowiki = true;
				}
				needsNowiki = false;
				break;
			case '[':
				linkStack++;
				needsNowiki = false;
				break;
			case ']':
				if ( linkStack > 0 ) {
					linkStack--;
					needsNowiki = false;
				}
				break;
			case '{':
				bracketStack++;
				needsNowiki = false;
				break;
			case '}':
				if ( bracketStack > 0 ) {
					bracketStack--;
					needsNowiki = false;
				}
				break;
			case '|':
				if ( bracketStack > 0 || linkStack > 0 ) {
					needsNowiki = false;
				}
				break;
		}

		output += needsNowiki ?
			'<nowiki>' + match[ 0 ] + '</nowiki>' :
			match[ 0 ];
	}
	return output;
};

/**
 * Recreate the wikitext for this transclusion, possibly containing multiple template invocations,
 * mixed with raw wikitext snippets.
 *
 * @static
 * @param {Object} content MW data content
 * @return {string} Wikitext
 */
ve.dm.MWTransclusionNode.static.getWikitext = function ( content ) {
	var wikitext = '';

	// Normalize to multi template format
	if ( content.params ) {
		content = { parts: [ { template: content } ] };
	}
	// Build wikitext from content
	for ( var i = 0, len = content.parts.length; i < len; i++ ) {
		var part = content.parts[ i ];
		if ( part.template ) {
			// Template
			var template = part.template;
			wikitext += '{{' + template.target.wt;
			for ( var param in template.params ) {
				wikitext += '|' + param + '=' +
					this.escapeParameter( template.params[ param ].wt );
			}
			wikitext += '}}';
		} else {
			// Plain wikitext
			wikitext += part;
		}
	}
	return wikitext;
};

/* Methods */

/**
 * Handle attribute change events.
 *
 * @param {string} key Attribute key
 * @param {string} from Old value
 * @param {string} to New value
 */
ve.dm.MWTransclusionNode.prototype.onAttributeChange = function ( key ) {
	if ( key === 'mw' ) {
		this.partsList = null;
	}
};

/**
 * Check if transclusion contains only a single template.
 *
 * @param {string|string[]} [allowedTemplates] Names of templates to allow, omit to allow any template name
 * @return {boolean} Transclusion only contains a single template, which is one of the ones in templates
 */
ve.dm.MWTransclusionNode.prototype.isSingleTemplate = function ( allowedTemplates ) {
	var templateNS = mw.config.get( 'wgNamespaceIds' ).template,
		parts = this.getPartsList();

	function normalizeTemplateTitle( name ) {
		var title = mw.Title.newFromText( name, templateNS );
		return title ? title.getPrefixedText() : name;
	}

	// Bail out as early as possible when no filter is given, or it's not a single part anyway
	var isSingle = parts.length === 1;
	if ( !isSingle || !allowedTemplates ) {
		return isSingle;
	}

	var singlePart = parts[ 0 ];
	// It's not a template but e.g. a parser function or raw wikitext content
	if ( !singlePart.templatePage ) {
		return false;
	}

	if ( typeof allowedTemplates === 'string' ) {
		allowedTemplates = [ allowedTemplates ];
	}
	return allowedTemplates.some( function ( template ) {
		return singlePart.templatePage === normalizeTemplateTitle( template );
	} );
};

/**
 * Get a simplified description of the transclusion's parts.
 *
 * @return {Object[]} List of objects with either template or content properties
 */
ve.dm.MWTransclusionNode.prototype.getPartsList = function () {
	if ( !this.partsList ) {
		this.partsList = [];
		var content = this.getAttribute( 'mw' );
		for ( var i = 0; i < content.parts.length; i++ ) {
			var part = content.parts[ i ];
			// A template as serialized by {@see ve.dm.MWTemplateModel.serialize}
			if ( part.template ) {
				var href = part.template.target.href,
					page = href ? mw.libs.ve.normalizeParsoidResourceName( href ) : null;
				this.partsList.push( {
					template: part.template.target.wt,
					templatePage: page
				} );
			} else {
				// Raw wikitext as serialized by {@see ve.dm.MWTransclusionContentModel.serialize}
				this.partsList.push( { content: part } );
			}
		}
	}

	return this.partsList;
};

/**
 * Wrapper for static method, {@see ve.dm.MWTransclusionNode.static.getWikitext} above.
 *
 * @return {string} Wikitext
 */
ve.dm.MWTransclusionNode.prototype.getWikitext = function () {
	return this.constructor.static.getWikitext( this.getAttribute( 'mw' ) );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWTransclusionNode );
