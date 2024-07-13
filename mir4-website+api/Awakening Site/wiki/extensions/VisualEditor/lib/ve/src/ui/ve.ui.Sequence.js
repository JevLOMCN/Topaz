/*!
 * VisualEditor UserInterface Sequence class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Key sequence.
 *
 * @class
 *
 * @constructor
 * @param {string} name Symbolic name
 * @param {string} commandName Command name this sequence executes
 * @param {string|Array|RegExp} data Data to match. String, linear data array, or regular expression.
 *         When using a RegularExpression always match the end of the sequence with a '$' so that
 *         only sequences next to the user's cursor match.
 * @param {number} [strip=0] Number of data elements to strip after execution
 *         (from the right)
 * @param {Object} [config] [description]
 * @cfg {boolean} [setSelection=false] Whether to set the selection to the
 *       range matching the sequence before executing the command.
 * @cfg {boolean} [delayed=false] Whether to wait for the user to stop typing matching content
 *       before executing the command. When the sequence matches typed text, it will not be executed
 *       immediately, but only after more non-matching text is added afterwards or the selection is
 *       changed. This is useful for variable-length sequences (defined with RegExps).
 * @cfg {boolean} [checkOnPaste=false] Whether the sequence should also be matched after paste.
 * @cfg {boolean} [checkOnDelete=false] Whether the sequence should also be matched after delete.
 */
ve.ui.Sequence = function VeUiSequence( name, commandName, data, strip, config ) {
	this.name = name;
	this.commandName = commandName;
	this.data = data;
	this.strip = strip || 0;
	if ( typeof config === 'object' ) {
		// TODO: Add `config = config || {};` when variadic fallback is dropped.
		this.setSelection = !!config.setSelection;
		this.delayed = !!config.delayed;
		this.checkOnPaste = !!config.checkOnPaste;
		this.checkOnDelete = !!config.checkOnDelete;
	} else {
		// Backwards compatibility with variadic arguments
		this.setSelection = !!arguments[ 4 ];
		this.delayed = !!arguments[ 5 ];
		this.checkOnPaste = !!arguments[ 6 ];
		this.checkOnDelete = !!arguments[ 7 ];
	}
};

/* Inheritance */

OO.initClass( ve.ui.Sequence );

/* Methods */

/**
 * Check if the sequence matches a given offset in the data
 *
 * @param {ve.dm.ElementLinearData} data String or linear data
 * @param {number} offset
 * @param {string} plaintext Plain text of data
 * @return {ve.Range|null} Range corresponding to the match, or else null
 */
ve.ui.Sequence.prototype.match = function ( data, offset, plaintext ) {
	var i, j = offset - 1;

	if ( this.data instanceof RegExp ) {
		i = plaintext.search( this.data );
		return ( i < 0 ) ? null :
			new ve.Range( offset - plaintext.length + i, offset );
	}
	for ( i = this.data.length - 1; i >= 0; i--, j-- ) {
		if ( typeof this.data[ i ] === 'string' ) {
			if ( this.data[ i ] !== data.getCharacterData( j ) ) {
				return null;
			}
		} else if ( !ve.compare( this.data[ i ], data.getData( j ), true ) ) {
			return null;
		}
	}
	return new ve.Range( offset - this.data.length, offset );
};

/**
 * Execute the command associated with the sequence
 *
 * @param {ve.ui.Surface} surface
 * @param {ve.Range} range Range to set
 * @return {boolean} The command executed
 */
ve.ui.Sequence.prototype.execute = function ( surface, range ) {
	var surfaceModel = surface.getModel();

	if ( surface.getCommands().indexOf( this.getCommandName() ) === -1 ) {
		return false;
	}

	var command = surface.commandRegistry.lookup( this.getCommandName() );

	if ( !command ) {
		return false;
	}

	var stripFragment;
	if ( this.strip ) {
		var stripRange = surfaceModel.getSelection().getRange();
		stripFragment = surfaceModel.getLinearFragment(
			// noAutoSelect = true, excludeInsertions = true
			new ve.Range( stripRange.end, stripRange.end - this.strip ), true, true
		);
	}

	surfaceModel.breakpoint();

	// Use SurfaceFragment rather than Selection to automatically adjust the selection for any changes
	// (additions, removals) caused by executing the command
	var originalSelectionFragment = surfaceModel.getFragment();
	if ( this.setSelection ) {
		surfaceModel.setLinearSelection( range );
	}

	var args;
	// For sequences that trigger dialogs, pass an extra flag so the window knows
	// to un-strip the sequence if it is closed without action. See ve.ui.WindowAction.
	if ( command.getAction() === 'window' && command.getMethod() === 'open' ) {
		args = ve.copy( command.args );
		args[ 1 ] = args[ 1 ] || {};
		args[ 1 ].strippedSequence = !!this.strip;
	}

	if ( stripFragment ) {
		// Strip the typed text. This will be undone if the action triggered was
		// window/open and the window is dismissed
		stripFragment.removeContent();
	}

	// `args` can be passed undefined, and the defaults will be used
	var executed = command.execute( surface, args, 'sequence' );

	// Restore user's selection if:
	// * This sequence was not executed after all
	// * This sequence is delayed, so it only executes after the user changed the selection
	if ( !executed || this.delayed ) {
		originalSelectionFragment.select();
	}

	if ( stripFragment && !executed ) {
		surfaceModel.undo();
		// Prevent redoing (which would remove the typed text)
		surfaceModel.truncateUndoStack();
		surfaceModel.emit( 'history' );
	}

	return executed;
};

/**
 * Get the symbolic name of the sequence
 *
 * @return {string} Symbolic name
 */
ve.ui.Sequence.prototype.getName = function () {
	return this.name;
};

/**
 * Get the command name which the sequence will execute
 *
 * @return {string} Command name
 */
ve.ui.Sequence.prototype.getCommandName = function () {
	return this.commandName;
};

/**
 * Get a representation of the sequence useful for display
 *
 * What this means depends a bit on how the sequence was defined:
 * - It strips out undisplayable things like the paragraph-start marker.
 * - Regexps are just returned as a toString of the regexp.
 *
 * @param {boolean} explode Whether to return the message split up into some
 *        reasonable sequence of inputs required to trigger the sequence (regexps
 *        in sequences will be considered a single "input" as a toString of
 *        the regexp, because they're hard to display no matter what…)
 * @return {string} Message for display
 */
ve.ui.Sequence.prototype.getMessage = function ( explode ) {
	var data;
	if ( typeof this.data === 'string' ) {
		data = this.data.split( '' );
	} else if ( this.data instanceof RegExp ) {
		data = [ this.data.toString() ];
	} else {
		data = this.data.filter( function ( key ) {
			return !ve.isPlainObject( key );
		} );
	}
	return explode ? data : data.join( '' );
};
