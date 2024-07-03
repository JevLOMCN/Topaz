/*!
 * VisualEditor MWInternalLinkContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a MWInternalLink.
 *
 * @class
 * @extends ve.ui.LinkContextItem
 *
 * @constructor
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWInternalLinkContextItem = function VeUiMWInternalLinkContextItem() {
	// Parent constructor
	ve.ui.MWInternalLinkContextItem.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwInternalLinkContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWInternalLinkContextItem, ve.ui.LinkContextItem );

/* Static Properties */

ve.ui.MWInternalLinkContextItem.static.name = 'link/internal';

ve.ui.MWInternalLinkContextItem.static.modelClasses = [ ve.dm.MWInternalLinkAnnotation ];

/* Static methods */

/**
 * Generate the body of the link context item
 *
 * @param {ve.init.mw.LinkCache} linkCache The link cache to use
 * @param {ve.dm.MWInternalLinkAnnotation} model The annotation model
 * @param {HTMLDocument} htmlDoc The HTML document (for URL resolution)
 * @param {ve.ui.LinearContext} context Context (for resizing)
 * @return {jQuery} The jQuery object of the link context item
 */
ve.ui.MWInternalLinkContextItem.static.generateBody = function ( linkCache, model, htmlDoc, context ) {
	var lookupTitle = model.getAttribute( 'lookupTitle' ),
		normalizedTitle = model.getAttribute( 'normalizedTitle' ),
		href = model.getHref(),
		title = mw.Title.newFromText( mw.libs.ve.normalizeParsoidResourceName( href ) ),
		fragment = model.getFragment(),
		usePageImages = mw.config.get( 'wgVisualEditorConfig' ).usePageImages,
		usePageDescriptions = mw.config.get( 'wgVisualEditorConfig' ).usePageDescriptions,
		$wrapper = $( '<div>' ),
		$link = $( '<a>' )
			.addClass( 've-ui-linkContextItem-link' )
			.text( normalizedTitle )
			.attr( {
				target: '_blank',
				rel: 'noopener'
			} );

	// T322704
	ve.setAttributeSafe( $link[ 0 ], 'href', title.getUrl(), '#' );

	// Style based on link cache information
	ve.init.platform.linkCache.styleElement( lookupTitle, $link, fragment );
	// Don't style as a self-link in the context menu (but do elsewhere)
	$link.removeClass( 'mw-selflink' );

	var icon;
	if ( usePageImages ) {
		icon = new OO.ui.IconWidget( { icon: 'page-existing' } );
		$wrapper
			.addClass( 've-ui-mwInternalLinkContextItem-withImage' )
			.append( icon.$element );
	}

	$wrapper.append( $link );

	if ( usePageDescriptions ) {
		$wrapper.addClass( 've-ui-mwInternalLinkContextItem-withDescription' );
	}

	if ( usePageImages || usePageDescriptions ) {
		linkCache.get( lookupTitle ).then( function ( linkData ) {
			if ( usePageImages ) {
				if ( linkData.imageUrl ) {
					icon.$element
						.addClass( 've-ui-mwInternalLinkContextItem-hasImage mw-no-invert' )
						.css( 'background-image', 'url(' + linkData.imageUrl + ')' );
				} else {
					icon.setIcon( ve.init.platform.linkCache.constructor.static.getIconForLink( linkData ) );
				}
			}
			if ( usePageDescriptions && linkData.description ) {
				var $description = $( '<span>' )
					.addClass( 've-ui-mwInternalLinkContextItem-description' )
					.text( linkData.description );
				$wrapper.append( $description );
				// Multiline descriptions may make the context bigger (T183650)
				context.updateDimensions();
			}
		} );
	}
	return $wrapper;
};

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWInternalLinkContextItem.prototype.getDescription = function () {
	return this.model.getAttribute( 'normalizedTitle' );
};

/**
 * @inheritdoc
 */
ve.ui.MWInternalLinkContextItem.prototype.renderBody = function () {
	var $body = this.constructor.static.generateBody(
		ve.init.platform.linkCache,
		this.model,
		this.context.getSurface().getModel().getDocument().getHtmlDocument(),
		this.context
	);
	this.$body.empty().append( $body );
	if ( !this.context.isMobile() ) {
		this.$body.append( this.$labelLayout );
	}
	this.updateLabelPreview();
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWInternalLinkContextItem );
