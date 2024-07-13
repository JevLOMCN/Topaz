/*!
 * VisualEditor UserInterface AnnotationTool classes.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface annotation tool.
 *
 * @class
 * @abstract
 * @extends ve.ui.Tool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.AnnotationTool = function VeUiAnnotationTool() {
	// Parent constructor
	ve.ui.AnnotationTool.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.AnnotationTool, ve.ui.Tool );

/* Static Properties */

/**
 * Annotation name and data the tool applies.
 *
 * @abstract
 * @static
 * @property {Object}
 * @inheritable
 */
ve.ui.AnnotationTool.static.annotation = { name: '' };

ve.ui.AnnotationTool.static.deactivateOnSelect = false;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.AnnotationTool.prototype.onUpdateState = function ( fragment ) {
	// Parent method
	ve.ui.AnnotationTool.super.prototype.onUpdateState.apply( this, arguments );

	this.setActive(
		fragment && fragment.getAnnotations().hasAnnotationWithName( this.constructor.static.annotation.name )
	);
};

/**
 * UserInterface bold tool.
 *
 * @class
 * @extends ve.ui.AnnotationTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.BoldAnnotationTool = function VeUiBoldAnnotationTool() {
	ve.ui.BoldAnnotationTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.BoldAnnotationTool, ve.ui.AnnotationTool );
ve.ui.BoldAnnotationTool.static.name = 'bold';
ve.ui.BoldAnnotationTool.static.group = 'textStyle';
ve.ui.BoldAnnotationTool.static.icon = 'bold';
ve.ui.BoldAnnotationTool.static.title =
	OO.ui.deferMsg( 'visualeditor-annotationbutton-bold-tooltip' );
ve.ui.BoldAnnotationTool.static.annotation = { name: 'textStyle/bold' };
ve.ui.BoldAnnotationTool.static.commandName = 'bold';
ve.ui.toolFactory.register( ve.ui.BoldAnnotationTool );

/**
 * UserInterface italic tool.
 *
 * @class
 * @extends ve.ui.AnnotationTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.ItalicAnnotationTool = function VeUiItalicAnnotationTool() {
	ve.ui.ItalicAnnotationTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.ItalicAnnotationTool, ve.ui.AnnotationTool );
ve.ui.ItalicAnnotationTool.static.name = 'italic';
ve.ui.ItalicAnnotationTool.static.group = 'textStyle';
ve.ui.ItalicAnnotationTool.static.icon = 'italic';
ve.ui.ItalicAnnotationTool.static.title =
	OO.ui.deferMsg( 'visualeditor-annotationbutton-italic-tooltip' );
ve.ui.ItalicAnnotationTool.static.annotation = { name: 'textStyle/italic' };
ve.ui.ItalicAnnotationTool.static.commandName = 'italic';
ve.ui.toolFactory.register( ve.ui.ItalicAnnotationTool );

/**
 * UserInterface code tool.
 *
 * @class
 * @extends ve.ui.AnnotationTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.CodeAnnotationTool = function VeUiCodeAnnotationTool() {
	ve.ui.CodeAnnotationTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.CodeAnnotationTool, ve.ui.AnnotationTool );
ve.ui.CodeAnnotationTool.static.name = 'code';
ve.ui.CodeAnnotationTool.static.group = 'textStyle';
ve.ui.CodeAnnotationTool.static.icon = 'code';
ve.ui.CodeAnnotationTool.static.title =
	OO.ui.deferMsg( 'visualeditor-annotationbutton-code-tooltip' );
ve.ui.CodeAnnotationTool.static.annotation = { name: 'textStyle/code' };
ve.ui.CodeAnnotationTool.static.commandName = 'code';
ve.ui.toolFactory.register( ve.ui.CodeAnnotationTool );

/**
 * UserInterface strikethrough tool.
 *
 * @class
 * @extends ve.ui.AnnotationTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.StrikethroughAnnotationTool = function VeUiStrikethroughAnnotationTool() {
	ve.ui.StrikethroughAnnotationTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.StrikethroughAnnotationTool, ve.ui.AnnotationTool );
ve.ui.StrikethroughAnnotationTool.static.name = 'strikethrough';
ve.ui.StrikethroughAnnotationTool.static.group = 'textStyle';
ve.ui.StrikethroughAnnotationTool.static.icon = 'strikethrough';
ve.ui.StrikethroughAnnotationTool.static.title =
	OO.ui.deferMsg( 'visualeditor-annotationbutton-strikethrough-tooltip' );
ve.ui.StrikethroughAnnotationTool.static.annotation = { name: 'textStyle/strikethrough' };
ve.ui.StrikethroughAnnotationTool.static.commandName = 'strikethrough';
ve.ui.toolFactory.register( ve.ui.StrikethroughAnnotationTool );

/**
 * UserInterface underline tool.
 *
 * @class
 * @extends ve.ui.AnnotationTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.UnderlineAnnotationTool = function VeUiUnderlineAnnotationTool() {
	ve.ui.UnderlineAnnotationTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.UnderlineAnnotationTool, ve.ui.AnnotationTool );
ve.ui.UnderlineAnnotationTool.static.name = 'underline';
ve.ui.UnderlineAnnotationTool.static.group = 'textStyle';
ve.ui.UnderlineAnnotationTool.static.icon = 'underline';
ve.ui.UnderlineAnnotationTool.static.title =
	OO.ui.deferMsg( 'visualeditor-annotationbutton-underline-tooltip' );
ve.ui.UnderlineAnnotationTool.static.annotation = { name: 'textStyle/underline' };
ve.ui.UnderlineAnnotationTool.static.commandName = 'underline';
ve.ui.toolFactory.register( ve.ui.UnderlineAnnotationTool );

/**
 * UserInterface superscript tool.
 *
 * @class
 * @extends ve.ui.AnnotationTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.SuperscriptAnnotationTool = function VeUiSuperscriptAnnotationTool() {
	ve.ui.SuperscriptAnnotationTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.SuperscriptAnnotationTool, ve.ui.AnnotationTool );
ve.ui.SuperscriptAnnotationTool.static.name = 'superscript';
ve.ui.SuperscriptAnnotationTool.static.group = 'textStyle';
ve.ui.SuperscriptAnnotationTool.static.icon = 'superscript';
ve.ui.SuperscriptAnnotationTool.static.title =
	OO.ui.deferMsg( 'visualeditor-annotationbutton-superscript-tooltip' );
ve.ui.SuperscriptAnnotationTool.static.annotation = { name: 'textStyle/superscript' };
ve.ui.SuperscriptAnnotationTool.static.commandName = 'superscript';
ve.ui.toolFactory.register( ve.ui.SuperscriptAnnotationTool );

/**
 * UserInterface subscript tool.
 *
 * @class
 * @extends ve.ui.AnnotationTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.SubscriptAnnotationTool = function VeUiSubscriptAnnotationTool() {
	ve.ui.SubscriptAnnotationTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.SubscriptAnnotationTool, ve.ui.AnnotationTool );
ve.ui.SubscriptAnnotationTool.static.name = 'subscript';
ve.ui.SubscriptAnnotationTool.static.group = 'textStyle';
ve.ui.SubscriptAnnotationTool.static.icon = 'subscript';
ve.ui.SubscriptAnnotationTool.static.title =
	OO.ui.deferMsg( 'visualeditor-annotationbutton-subscript-tooltip' );
ve.ui.SubscriptAnnotationTool.static.annotation = { name: 'textStyle/subscript' };
ve.ui.SubscriptAnnotationTool.static.commandName = 'subscript';
ve.ui.toolFactory.register( ve.ui.SubscriptAnnotationTool );

/**
 * UserInterface big tool.
 *
 * @class
 * @extends ve.ui.AnnotationTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.BigAnnotationTool = function VeUiBigAnnotationTool() {
	ve.ui.BigAnnotationTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.BigAnnotationTool, ve.ui.AnnotationTool );
ve.ui.BigAnnotationTool.static.name = 'big';
ve.ui.BigAnnotationTool.static.group = 'textStyle';
ve.ui.BigAnnotationTool.static.icon = 'bigger';
ve.ui.BigAnnotationTool.static.title =
	OO.ui.deferMsg( 'visualeditor-annotationbutton-big-tooltip' );
ve.ui.BigAnnotationTool.static.annotation = { name: 'textStyle/big' };
ve.ui.BigAnnotationTool.static.commandName = 'big';
ve.ui.toolFactory.register( ve.ui.BigAnnotationTool );

/**
 * UserInterface small tool.
 *
 * @class
 * @extends ve.ui.AnnotationTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.SmallAnnotationTool = function VeUiSmallAnnotationTool() {
	ve.ui.SmallAnnotationTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.SmallAnnotationTool, ve.ui.AnnotationTool );
ve.ui.SmallAnnotationTool.static.name = 'small';
ve.ui.SmallAnnotationTool.static.group = 'textStyle';
ve.ui.SmallAnnotationTool.static.icon = 'smaller';
ve.ui.SmallAnnotationTool.static.title =
	OO.ui.deferMsg( 'visualeditor-annotationbutton-small-tooltip' );
ve.ui.SmallAnnotationTool.static.annotation = { name: 'textStyle/small' };
ve.ui.SmallAnnotationTool.static.commandName = 'small';
ve.ui.toolFactory.register( ve.ui.SmallAnnotationTool );

/**
 * UserInterface more text styles tool.
 *
 * @class
 * @extends OO.ui.ToolGroupTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MoreTextStyleTool = function VeUiMoreTextStyleTool() {
	ve.ui.MoreTextStyleTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MoreTextStyleTool, OO.ui.ToolGroupTool );
ve.ui.MoreTextStyleTool.static.autoAddToCatchall = false;
ve.ui.MoreTextStyleTool.static.name = 'moreTextStyle';
ve.ui.MoreTextStyleTool.static.group = 'textStyleExpansion';
ve.ui.MoreTextStyleTool.static.title =
	OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' );
ve.ui.MoreTextStyleTool.static.groupConfig = {
	header: OO.ui.deferMsg( 'visualeditor-toolbar-text-style' ),
	icon: 'textStyle',
	indicator: 'down',
	title: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
	label: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
	invisibleLabel: true,
	include: [ { group: 'textStyle' }, 'language', 'clear' ],
	demote: [ 'strikethrough', 'code', 'underline', 'language', 'big', 'small', 'clear' ]
};
ve.ui.toolFactory.register( ve.ui.MoreTextStyleTool );
