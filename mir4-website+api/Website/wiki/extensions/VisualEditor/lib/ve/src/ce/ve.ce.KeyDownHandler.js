/*!
 * VisualEditor ContentEditable key down handler
 *
 * @copyright See AUTHORS.txt
 */

/* istanbul ignore next */
/**
 * Key down handler.
 *
 * @class
 * @abstract
 *
 * @constructor
 */
ve.ce.KeyDownHandler = function VeCeKeyDownHandler() {
};

/* Inheritance */

OO.initClass( ve.ce.KeyDownHandler );

/* Static properties */

/**
 * Symbolic name for this handler. Must be unique.
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.ce.KeyDownHandler.static.name = null;

/**
 * List of keys this handler matches
 *
 * @static
 * @property {string[]|null}
 * @inheritable
 */
ve.ce.KeyDownHandler.static.keys = [];

/**
 * List of selections this handler matches
 *
 * Null means all selections are matched.
 *
 * @static
 * @property {string[]|null}
 * @inheritable
 */
ve.ce.KeyDownHandler.static.supportedSelections = null;

/* Static methods */

/**
 * Execute the handler
 *
 * @abstract
 * @method
 * @param {ve.ce.Surface} surface
 * @param {jQuery.Event} e Key down event
 * @return {boolean} Whether an action was taken
 */
ve.ce.KeyDownHandler.static.execute = null;
