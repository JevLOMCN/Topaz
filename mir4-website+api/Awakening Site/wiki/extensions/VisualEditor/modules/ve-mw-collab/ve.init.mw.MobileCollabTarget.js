/*!
 * VisualEditor MediaWiki Initialization MobileCollabTarget class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

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
ve.init.mw.MobileCollabTarget = function VeInitMwMobileCollabTarget( title, rebaserUrl, config ) {
	// Parent constructor
	ve.init.mw.MobileCollabTarget.super.call( this, title, rebaserUrl, config );

	// Initialization
	this.$element.addClass( 've-init-mw-mobileArticleTarget ve-init-mw-mobileCollabTarget ve-init-mobileTarget' );

	$( document.body ).removeClass( 'ns-special' );
};

/* Inheritance */

OO.inheritClass( ve.init.mw.MobileCollabTarget, ve.init.mw.CollabTarget );

/* Static Properties */

ve.init.mw.MobileCollabTarget.static.toolbarGroups = [
	// Back
	{
		name: 'back',
		include: [ 'back' ]
	},
	// History
	{
		name: 'history',
		include: [ 'undo' ]
	},
	// Style
	{
		name: 'style',
		classes: [ 've-test-toolbar-style' ],
		type: 'list',
		icon: 'textStyle',
		title: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
		label: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
		invisibleLabel: true,
		include: [ { group: 'textStyle' }, 'language', 'clear' ],
		forceExpand: [ 'bold', 'italic', 'clear' ],
		promote: [ 'bold', 'italic' ],
		demote: [ 'strikethrough', 'code', 'underline', 'language', 'clear' ]
	},
	// Link
	{
		name: 'link',
		include: [ 'link' ]
	},
	// Placeholder for reference tools (e.g. Cite and/or Citoid)
	{
		name: 'reference'
	},
	{
		name: 'insert',
		header: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		title: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		label: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		invisibleLabel: true,
		type: 'list',
		icon: 'add',
		promote: [ 'commentAnnotation' ],
		include: '*',
		exclude: [ 'comment', 'indent', 'outdent', { group: 'format' } ]
	},
	{
		name: 'authorList',
		include: [ 'authorList' ]
	},
	{
		name: 'export',
		include: [ 'export' ]
	}
];

/* Methods */

/**
 * @inheritdoc
 */
ve.init.mw.MobileCollabTarget.prototype.getSurfaceClasses = function () {
	var classes = ve.init.mw.MobileCollabTarget.super.prototype.getSurfaceClasses.call( this );
	return classes.concat( [ 'content' ] );
};

/* Registration */

ve.init.mw.targetFactory.register( ve.init.mw.MobileCollabTarget );
