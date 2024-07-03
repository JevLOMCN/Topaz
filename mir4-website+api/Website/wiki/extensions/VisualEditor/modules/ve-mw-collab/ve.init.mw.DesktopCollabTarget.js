/*!
 * VisualEditor MediaWiki Initialization DesktopCollabTarget class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/* eslint-disable no-jquery/no-global-selector */

/**
 * MediaWiki mobile article target.
 *
 * @class
 * @extends ve.init.mw.Target
 *
 * @constructor
 * @param {mw.Title} title Page sub-title
 * @param {string} rebaserUrl Rebaser server URL
 * @param {Object} [config] Configuration options
 * @cfg {mw.Title} [importTitle] Title to import
 */
ve.init.mw.DesktopCollabTarget = function VeInitMwDesktopCollabTarget( title, rebaserUrl, config ) {
	// Parent constructor
	ve.init.mw.DesktopCollabTarget.super.call( this, title, rebaserUrl, config );

	this.$originalContent = $( '<div>' ).addClass( 've-init-mw-desktopArticleTarget-originalContent' );
	this.$editableContent = $( '#mw-content-text' );

	// Initialization
	this.$element.addClass( 've-init-mw-desktopArticleTarget' ).append( this.$originalContent );
};

/* Inheritance */

OO.inheritClass( ve.init.mw.DesktopCollabTarget, ve.init.mw.CollabTarget );

/* Methods */

/**
 * Page modifications after editor load.
 */
ve.init.mw.DesktopCollabTarget.prototype.transformPage = function () {
	this.$originalContent.append( this.$element.siblings() );
	var title;
	if ( ( title = this.getImportTitle() ) ) {
		// ve.htmlMsg returns `Node[]`
		// eslint-disable-next-line no-jquery/no-html
		$( '#contentSub' ).html(
			ve.htmlMsg(
				'collabpad-import-subtitle',
				$( '<a>' ).attr( 'href', title.getUrl() ).text( title.getMainText() )
			)
		);
		ve.targetLinksToNewWindow( $( '#contentSub' )[ 0 ] );
	} else {
		$( '#contentSub' ).empty();
	}
};

/**
 * Page modifications after editor teardown.
 */
ve.init.mw.DesktopCollabTarget.prototype.restorePage = function () {
	this.$element.parent().append( this.$originalContent.children() );
	$( '#contentSub' ).empty();
};

/**
 * @inheritdoc
 */
ve.init.mw.DesktopCollabTarget.prototype.attachToolbar = function () {
	var toolbar = this.getToolbar();

	// Parent method
	ve.init.mw.DesktopCollabTarget.super.prototype.attachToolbar.apply( this, arguments );

	toolbar.$element.addClass(
		've-init-mw-desktopArticleTarget-toolbar ve-init-mw-desktopArticleTarget-toolbar-open'
	);
	this.$element.prepend( toolbar.$element );
};

/**
 * @inheritdoc
 */
ve.init.mw.DesktopCollabTarget.prototype.setSurface = function ( surface ) {
	if ( surface !== this.surface ) {
		this.$editableContent.after( surface.$element );
	}

	// Parent method
	ve.init.mw.DesktopCollabTarget.super.prototype.setSurface.apply( this, arguments );
};

/* Registration */

ve.init.mw.targetFactory.register( ve.init.mw.DesktopCollabTarget );
