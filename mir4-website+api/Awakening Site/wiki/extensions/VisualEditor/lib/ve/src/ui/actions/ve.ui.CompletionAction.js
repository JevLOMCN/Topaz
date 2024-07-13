/*!
 * VisualEditor UserInterface CompletionAction class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Completion action.
 *
 * This is a very specialized action, which mostly exists to be a data-provider to a
 * completion widget. Completion-types *must* override getSuggestions.
 *
 * @class
 * @abstract
 * @extends ve.ui.Action
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 * @param {string} [source]
 */
ve.ui.CompletionAction = function VeUiCompletionAction() {
	// Parent constructor
	ve.ui.CompletionAction.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.CompletionAction, ve.ui.Action );

/* Static Properties */

/**
 * Length to which to limit the list of returned completions
 *
 * @static
 * @property {number}
 */
ve.ui.CompletionAction.static.defaultLimit = 8;

/**
 * Length of the sequence which triggers the action
 *
 * This many characters will be stripped from the start of the current input by
 * CompletionWidget when triggered by a sequence, see #getSequenceLength.
 *
 * @static
 * @property {number}
 */
ve.ui.CompletionAction.static.sequenceLength = 1;

/**
 * Whether the current input should be included as a completion automatically
 *
 * @static
 * @property {boolean}
 */
ve.ui.CompletionAction.static.alwaysIncludeInput = true;

ve.ui.CompletionAction.static.methods = [ 'open' ];

/* Methods */

/**
 * Show the completions
 *
 * @param {boolean} [isolateInput] Isolate input from the surface
 * @return {boolean} Action was executed
 */
ve.ui.CompletionAction.prototype.open = function ( isolateInput ) {
	this.surface.completion.setup( this, isolateInput );

	return true;
};

/**
 * Retrieve suggested completions for the given input
 *
 * @abstract
 * @param {string} input
 * @param {number} [limit=20] Maximum number of results
 * @return {jQuery.Promise} Promise that resolves with list of suggestions.
 *  Suggestions are converted to menu itmes by getMenuItemForSuggestion.
 */
ve.ui.CompletionAction.prototype.getSuggestions = null;

/**
 * Get a label to show as the menu header
 *
 * This is called twice per input, once with the new user input
 * immediately after it is entered, and again later with the
 * same input and its resolved suggestions
 *
 * @param {string} input User input
 * @param {Array} [suggestions] Returned suggestions
 * @return {jQuery|string|OO.ui.HtmlSnippet|Function|null|undefined} Label. Use undefined
 *  to avoid updating the label, and null to clear it.
 */
ve.ui.CompletionAction.prototype.getHeaderLabel = function () {
	return null;
};

/**
 * Choose a specific item
 *
 * @param {OO.ui.MenuOptionWidget} item Chosen item
 * @param {ve.Range} range Current surface range
 */
ve.ui.CompletionAction.prototype.chooseItem = function ( item, range ) {
	var fragment = this.insertCompletion( item.getData(), range );
	fragment.collapseToEnd().select();
};

/**
 * Perform the insetion for the chosen suggestion
 *
 * @param {Object} data Whatever data was attached to the menu option widget
 * @param {ve.Range} range The range the widget is considering
 * @return {ve.dm.SurfaceFragment} The fragment containing the inserted content
 */
ve.ui.CompletionAction.prototype.insertCompletion = function ( data, range ) {
	return this.surface.getModel().getLinearFragment( range )
		.insertContent( data, true );
};

/**
 * Should the widget abandon trying to find matches given the current state?
 *
 * @param {string} input
 * @param {number} matches Number of matches before the input occurred
 * @return {boolean} Whether to abandon
 */
ve.ui.CompletionAction.prototype.shouldAbandon = function ( input, matches ) {
	// Abandon after adding whitespace if there are no active potential matches,
	// or if whitespace has been the sole input
	return /^\s+$/.test( input ) ||
		( matches === 0 && /\s$/.test( input ) );
};

/**
 * Get the length of the sequence which triggered this action
 *
 * @return {number} Length of the sequence
 */
ve.ui.CompletionAction.prototype.getSequenceLength = function () {
	return this.source === 'sequence' ? this.constructor.static.sequenceLength : 0;
};

// helpers

/**
 * Make a menu item for a given suggestion
 *
 * @protected
 * @param  {Mixed} suggestion Suggestion data, string by default
 * @return {OO.ui.MenuOptionWidget}
 */
ve.ui.CompletionAction.prototype.getMenuItemForSuggestion = function ( suggestion ) {
	return new OO.ui.MenuOptionWidget( { data: suggestion, label: suggestion } );
};

/**
 * Update the menu item list before adding, e.g. to add menu groups
 *
 * @protected
 * @param  {OO.ui.MenuOptionWidget[]} menuItems
 * @return {OO.ui.MenuOptionWidget[]}
 */
ve.ui.CompletionAction.prototype.updateMenuItems = function ( menuItems ) {
	return menuItems;
};

/**
 * Filter a suggestion list based on the current input
 *
 * This is for an implementor who has fetched/gathered a list of potential
 * suggestions and needs to trim them down to a viable set to display as
 * completion options for a given input.
 *
 * It restricts the selection to only suggestions that start with the input,
 * shortens the list to the configured defaultLimit, and adds the current
 * input to the list if alwaysIncludeInput and there wasn't an exact match.
 *
 * @protected
 * @param  {Mixed[]} suggestions List of valid completions, strings by default
 * @param  {string} input Input to filter the suggestions to
 * @return {Mixed[]}
 */
ve.ui.CompletionAction.prototype.filterSuggestionsForInput = function ( suggestions, input ) {
	var action = this;
	input = input.trim();

	var normalizedInput = input.toLowerCase().trim();

	var exact = false;
	suggestions = suggestions.filter( function ( suggestion ) {
		var result = action.compareSuggestionToInput( suggestion, normalizedInput );
		exact = exact || result.isExact;
		return result.isMatch;
	} );

	if ( this.constructor.static.defaultLimit < suggestions.length ) {
		suggestions.length = this.constructor.static.defaultLimit;
	}
	if ( !exact && this.constructor.static.alwaysIncludeInput && input.length ) {
		suggestions.push( this.createSuggestion( input ) );
	}
	return suggestions;
};

/**
 * Compare a suggestion to the normalized user input (lower case)
 *
 * @param {Mixed} suggestion Suggestion data, string by default
 * @param {string} normalizedInput Noramlized user input
 * @return {Object} Match object, containing two booleans, `isMatch` and `isExact`
 */
ve.ui.CompletionAction.prototype.compareSuggestionToInput = function ( suggestion, normalizedInput ) {
	var normalizedSuggestion = suggestion.toLowerCase();
	return {
		isMatch: normalizedSuggestion.slice( 0, normalizedInput.length ) === normalizedInput,
		isExact: normalizedSuggestion === normalizedInput
	};
};

/**
 * Create a suggestion from an input
 *
 * @param {string} input User input
 * @return {Mixed} Suggestion data, string by default
 */
ve.ui.CompletionAction.prototype.createSuggestion = function ( input ) {
	return input;
};
