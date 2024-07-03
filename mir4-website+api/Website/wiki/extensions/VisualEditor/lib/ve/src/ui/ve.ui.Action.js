/*!
 * VisualEditor UserInterface Action class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Generic action.
 *
 * An action is built around a surface for one-time use. It is a generic way of extending the
 * functionality of a surface. Actions are accessible via {ve.ui.Surface.prototype.execute}.
 *
 * @class
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 * @param {string} [source] Label for the source of the action.
 *  One of 'trigger', 'sequence', 'tool', or 'context'
 */
ve.ui.Action = function VeUiAction( surface, source ) {
	// Properties
	this.surface = surface;
	this.source = source;
};

/* Inheritance */

OO.initClass( ve.ui.Action );

/* Static Properties */

/**
 * List of allowed methods for the action.
 *
 * To avoid use of methods not intended to be executed via surface.execute(), the allowed methods
 * must be listed here. This information is checked by ve.ui.Surface before executing an action.
 *
 * If a method returns a value, it will be cast to boolean and be used to determine if the action
 * was canceled. Not returning anything, or returning undefined will be treated the same as
 * returning true. A canceled action will yield to other default behavior. For example, when
 * triggering an action from a keystroke, a canceled action will allow normal insertion behavior to
 * be carried out.
 *
 * @static
 * @property {string[]}
 * @inheritable
 */
ve.ui.Action.static.methods = [];
