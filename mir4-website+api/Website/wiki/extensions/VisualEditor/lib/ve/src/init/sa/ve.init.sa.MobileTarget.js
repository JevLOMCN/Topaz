/*!
 * VisualEditor Standalone Initialization Mobile Target class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Initialization standalone mobile target.
 *
 * @class
 * @extends ve.init.sa.Target
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {Object} [toolbarConfig] Configuration options for the toolbar
 */
ve.init.sa.MobileTarget = function VeInitSaMobileTarget( config ) {
	// Parent constructor
	ve.init.sa.MobileTarget.super.call( this, config );

	this.$element.addClass( 've-init-mobileTarget' );
};

/* Inheritance */

OO.inheritClass( ve.init.sa.MobileTarget, ve.init.sa.Target );

/* Static Properties */

ve.init.sa.MobileTarget.static.toolbarGroups = [
	{
		// Message visualeditor-toolbar-history exists in case we want
		// to make this a list group in future, to expose 'redo'.
		name: 'history',
		include: [ 'undo' ]
	},
	{
		name: 'style',
		header: OO.ui.deferMsg( 'visualeditor-toolbar-text-style' ),
		title: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
		label: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
		invisibleLabel: true,
		type: 'list',
		icon: 'textStyle',
		include: [ { group: 'textStyle' }, 'language', 'clear' ],
		forceExpand: [ 'bold', 'italic', 'clear' ],
		promote: [ 'bold', 'italic' ],
		demote: [ 'strikethrough', 'code', 'underline', 'language', 'clear' ]
	},
	{
		name: 'link',
		include: [ 'link' ]
	},
	{
		name: 'structure',
		header: OO.ui.deferMsg( 'visualeditor-toolbar-structure' ),
		title: OO.ui.deferMsg( 'visualeditor-toolbar-structure' ),
		label: OO.ui.deferMsg( 'visualeditor-toolbar-structure' ),
		invisibleLabel: true,
		type: 'list',
		icon: 'listBullet',
		include: [ { group: 'structure' } ],
		demote: [ 'outdent', 'indent' ]
	},
	{
		name: 'insert',
		header: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		title: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		label: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		invisibleLabel: true,
		type: 'list',
		icon: 'add',
		include: '*'
	}
];
