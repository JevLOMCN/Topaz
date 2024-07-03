/*!
 * VisualEditor UserInterface Trigger class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Key trigger.
 *
 * @class
 *
 * @constructor
 * @param {jQuery.Event|string} [e] Event or string to create trigger from
 * @param {boolean} [allowInvalidPrimary] Allow invalid primary keys
 */
ve.ui.Trigger = function VeUiTrigger( e, allowInvalidPrimary ) {
	var keyAliases = ve.ui.Trigger.static.keyAliases,
		primaryKeys = ve.ui.Trigger.static.primaryKeys,
		primaryKeyMap = ve.ui.Trigger.static.primaryKeyMap;

	// Properties
	this.modifiers = {
		meta: false,
		ctrl: false,
		alt: false,
		shift: false
	};
	this.primary = false;

	// Initialization
	if ( e instanceof $.Event ) {
		this.modifiers.meta = e.metaKey || false;
		this.modifiers.ctrl = e.ctrlKey || false;
		this.modifiers.alt = e.altKey || false;
		this.modifiers.shift = e.shiftKey || false;
		this.primary = primaryKeyMap[ e.which ] || false;
	} else if ( typeof e === 'string' ) {
		// Normalization: remove whitespace and force lowercase
		var parts = e.replace( /\s+/g, '' ).toLowerCase().split( '+' );
		for ( var i = 0, len = parts.length; i < len; i++ ) {
			var key = parts[ i ];
			// Resolve key aliases
			if ( Object.prototype.hasOwnProperty.call( keyAliases, key ) ) {
				key = keyAliases[ key ];
			}
			// Apply key to trigger
			if ( Object.prototype.hasOwnProperty.call( this.modifiers, key ) ) {
				// Modifier key
				this.modifiers[ key ] = true;
			} else if ( primaryKeys.indexOf( key ) !== -1 || allowInvalidPrimary ) {
				// WARNING: Only the last primary key will be used
				this.primary = key;
			}
		}
	}
};

/* Inheritance */

OO.initClass( ve.ui.Trigger );

/* Static Properties */

/**
 * Symbolic modifier key names.
 *
 * The order of this array affects the canonical order of a trigger string.
 *
 * @static
 * @property {string[]}
 * @inheritable
 */
ve.ui.Trigger.static.modifierKeys = [ 'meta', 'ctrl', 'alt', 'shift' ];

/**
 * Symbolic primary key names.
 *
 * @static
 * @property {string[]}
 * @inheritable
 */
ve.ui.Trigger.static.primaryKeys = [
	// Special keys
	'backspace',
	'tab',
	'enter',
	'escape',
	'space',
	'page-up',
	'page-down',
	'end',
	'home',
	'left',
	'up',
	'right',
	'down',
	'insert',
	'delete',
	'clear',
	// Numbers
	'0',
	'1',
	'2',
	'3',
	'4',
	'5',
	'6',
	'7',
	'8',
	'9',
	// Letters
	'a',
	'b',
	'c',
	'd',
	'e',
	'f',
	'g',
	'h',
	'i',
	'j',
	'k',
	'l',
	'm',
	'n',
	'o',
	'p',
	'q',
	'r',
	's',
	't',
	'u',
	'v',
	'w',
	'x',
	'y',
	'z',
	// Numpad special keys
	'multiply',
	'add',
	'subtract',
	'decimal',
	'divide',
	// Function keys
	'f1',
	'f2',
	'f3',
	'f4',
	'f5',
	'f6',
	'f7',
	'f8',
	'f9',
	'f10',
	'f11',
	'f12',
	// Punctuation
	';',
	'=',
	',',
	'-',
	'.',
	'/',
	'`',
	'[',
	'\\',
	']',
	'\''
];

/**
 * Mappings to use when rendering string for a specific platform.
 *
 * @static
 * @property {Object}
 * @inheritable
 */
ve.ui.Trigger.static.platformMapping = {
	mac: {
		alt: '⌥',
		backspace: '⌫',
		ctrl: '^',
		delete: '⌦',
		down: '↓',
		end: '↗',
		// Technically 'enter' is ⌤, but JS doesn't distinguish between 'enter' and
		// 'return', and the return-arrow is better known
		enter: '⏎',
		escape: '⎋',
		home: '↖',
		left: '←',
		meta: '⌘',
		'page-down': '⇟',
		'page-up': '⇞',
		right: '→',
		shift: '⇧',
		space: '␣',
		tab: '⇥',
		up: '↑'
	}
};

/**
 * Symbol to use when concatenating keys in a sequence.
 *
 * @static
 * @property {Object}
 * @inheritable
 */
ve.ui.Trigger.static.platformStringJoiners = {
	default: '+',
	mac: ''
};

/**
 * Special keys which have i18n messages
 *
 * @static
 * @property {string[]}
 * @inheritable
 */
ve.ui.Trigger.static.translatableKeys = [
	'alt',
	'backspace',
	'ctrl',
	'delete',
	'down',
	'end',
	'enter',
	'escape',
	'home',
	'insert',
	'left',
	'meta',
	'page-down',
	'page-up',
	'right',
	'shift',
	'space',
	'tab',
	'up'
];

/**
 * Aliases for modifier or primary key names.
 *
 * @static
 * @property {Object}
 * @inheritable
 */
ve.ui.Trigger.static.keyAliases = {
	// Platform differences
	command: 'meta',
	apple: 'meta',
	windows: 'meta',
	option: 'alt',
	return: 'enter',
	// Shorthand
	esc: 'escape',
	cmd: 'meta',
	del: 'delete',
	// Longhand
	control: 'ctrl',
	alternate: 'alt',
	// Symbols
	'⌘': 'meta',
	'⎇': 'alt',
	'⇧': 'shift',
	'⏎': 'enter',
	'⌫': 'backspace',
	'⎋': 'escape'
};

/**
 * Mapping of key codes and symbolic key names.
 *
 * @static
 * @property {Object}
 * @inheritable
 */
ve.ui.Trigger.static.primaryKeyMap = {
	// Special keys
	8: 'backspace',
	9: 'tab',
	12: 'clear',
	13: 'enter',
	27: 'escape',
	32: 'space',
	33: 'page-up',
	34: 'page-down',
	35: 'end',
	36: 'home',
	37: 'left',
	38: 'up',
	39: 'right',
	40: 'down',
	45: 'insert',
	46: 'delete',
	// Numbers
	48: '0',
	49: '1',
	50: '2',
	51: '3',
	52: '4',
	53: '5',
	54: '6',
	55: '7',
	56: '8',
	57: '9',
	// Punctuation
	59: ';',
	61: '=',
	// Letters
	65: 'a',
	66: 'b',
	67: 'c',
	68: 'd',
	69: 'e',
	70: 'f',
	71: 'g',
	72: 'h',
	73: 'i',
	74: 'j',
	75: 'k',
	76: 'l',
	77: 'm',
	78: 'n',
	79: 'o',
	80: 'p',
	81: 'q',
	82: 'r',
	83: 's',
	84: 't',
	85: 'u',
	86: 'v',
	87: 'w',
	88: 'x',
	89: 'y',
	90: 'z',
	// Numpad numbers
	96: '0',
	97: '1',
	98: '2',
	99: '3',
	100: '4',
	101: '5',
	102: '6',
	103: '7',
	104: '8',
	105: '9',
	// Numpad special keys
	106: 'multiply',
	107: 'add',
	109: 'subtract',
	110: 'decimal',
	111: 'divide',
	// Function keys
	112: 'f1',
	113: 'f2',
	114: 'f3',
	115: 'f4',
	116: 'f5',
	117: 'f6',
	118: 'f7',
	119: 'f8',
	120: 'f9',
	121: 'f10',
	122: 'f11',
	123: 'f12',
	// Punctuation
	186: ';',
	187: '=',
	188: ',',
	189: '-',
	190: '.',
	191: '/',
	192: '`',
	219: '[',
	220: '\\',
	221: ']',
	222: '\''
};

/* Methods */

/**
 * Check if trigger is complete.
 *
 * For a trigger to be complete, there must be a valid primary key.
 *
 * @return {boolean} Trigger is complete
 */
ve.ui.Trigger.prototype.isComplete = function () {
	return this.primary !== false;
};

/**
 * Get a trigger string.
 *
 * Trigger strings are canonical representations of triggers made up of the symbolic names of all
 * active modifier keys and the primary key joined together with a '+' sign.
 *
 * To normalize a trigger string simply create a new trigger from a string and then run this method.
 *
 * An incomplete trigger will return an empty string.
 *
 * @return {string} Canonical trigger string
 */
ve.ui.Trigger.prototype.toString = function () {
	var modifierKeys = ve.ui.Trigger.static.modifierKeys,
		keys = [];
	// Add modifier keywords in the correct order
	for ( var i = 0, len = modifierKeys.length; i < len; i++ ) {
		if ( this.modifiers[ modifierKeys[ i ] ] ) {
			keys.push( modifierKeys[ i ] );
		}
	}
	// Check that there were modifiers and the primary key is whitelisted
	if ( this.primary ) {
		// Add a symbolic name for the primary key
		keys.push( this.primary );
		return keys.join( '+' );
	}
	// Alternatively return an empty string
	return '';
};

/**
 * Get a trigger message.
 *
 * This is similar to #toString but the resulting string will be formatted in a way that makes it
 * appear more native for the platform, and special keys will be translated.
 *
 * @param {boolean} explode Whether to return the message split up into some
 *        reasonable sequence of inputs required
 * @return {string[]|string} Seprate key messages, or a joined string
 */
ve.ui.Trigger.prototype.getMessage = function ( explode ) {
	var keys = this.toString().split( '+' ),
		hasOwn = Object.prototype.hasOwnProperty,
		translatableKeys = this.constructor.static.translatableKeys,
		platformMapping = this.constructor.static.platformMapping,
		platform = ve.getSystemPlatform();

	// Platform mappings
	if ( hasOwn.call( platformMapping, platform ) ) {
		keys = keys.map( function ( key ) {
			return hasOwn.call( platformMapping[ platform ], key ) ? platformMapping[ platform ][ key ] : key;
		} );
	}

	// i18n
	keys = keys.map( function ( key ) {
		// The following messages are used here:
		// * visualeditor-key-alt
		// * visualeditor-key-backspace
		// * visualeditor-key-ctrl
		// * visualeditor-key-delete
		// * visualeditor-key-down
		// * visualeditor-key-end
		// * visualeditor-key-enter
		// * visualeditor-key-escape
		// * visualeditor-key-home
		// * visualeditor-key-insert
		// * visualeditor-key-left
		// * visualeditor-key-meta
		// * visualeditor-key-page-down
		// * visualeditor-key-page-up
		// * visualeditor-key-right
		// * visualeditor-key-shift
		// * visualeditor-key-space
		// * visualeditor-key-tab
		// * visualeditor-key-up
		return translatableKeys.indexOf( key ) !== -1 ? ve.msg( 'visualeditor-key-' + key ) : key.toUpperCase();
	} );

	// Concatenation
	if ( explode ) {
		return keys;
	} else {
		var joiners = this.constructor.static.platformStringJoiners;
		var joiner = hasOwn.call( joiners, platform ) ? joiners[ platform ] : joiners.default;
		return keys.join( joiner );
	}
};
