/*!
 * VisualEditor UserInterface IndentationCommand class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface indentation command.
 *
 * @class
 * @extends ve.ui.Command
 *
 * @constructor
 * @param {string} name
 * @param {string} method
 */
ve.ui.IndentationCommand = function VeUiIndentationCommand( name, method ) {
	// Parent constructor
	ve.ui.IndentationCommand.super.call(
		this, name, 'indentation', method,
		{ supportedSelections: [ 'linear' ] }
	);
};

/* Inheritance */

OO.inheritClass( ve.ui.IndentationCommand, ve.ui.Command );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.IndentationCommand.prototype.isExecutable = function ( fragment ) {
	// Parent method
	if ( !ve.ui.IndentationCommand.super.prototype.isExecutable.apply( this, arguments ) ) {
		return false;
	}
	return fragment.hasMatchingAncestor( 'listItem' );
};

/* Registration */

ve.ui.commandRegistry.register( new ve.ui.IndentationCommand( 'indent', 'increase' ) );

ve.ui.commandRegistry.register( new ve.ui.IndentationCommand( 'outdent', 'decrease' ) );
