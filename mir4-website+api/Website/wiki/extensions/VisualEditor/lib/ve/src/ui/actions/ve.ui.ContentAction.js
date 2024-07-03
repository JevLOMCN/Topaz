/*!
 * VisualEditor UserInterface ContentAction class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Content action.
 *
 * @class
 * @extends ve.ui.Action
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 * @param {string} [source]
 */
ve.ui.ContentAction = function VeUiContentAction() {
	// Parent constructor
	ve.ui.ContentAction.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.ContentAction, ve.ui.Action );

/* Static Properties */

ve.ui.ContentAction.static.name = 'content';

ve.ui.ContentAction.static.methods = [ 'insert', 'remove', 'select', 'pasteSpecial', 'selectAll', 'changeDirectionality', 'submit', 'cancel', 'focusContext' ];

/* Methods */

/**
 * Insert content.
 *
 * @param {string|Array} content Content to insert, can be either a string or array of data
 * @param {boolean} [annotate] Content should be automatically annotated to match surrounding content
 * @param {boolean} [collapseToEnd] Collapse selection to end after inserting
 * @return {boolean} Action was executed
 */
ve.ui.ContentAction.prototype.insert = function ( content, annotate, collapseToEnd ) {
	var fragment = this.surface.getModel().getFragment();
	fragment.insertContent( content, annotate );
	if ( collapseToEnd ) {
		fragment.collapseToEnd().select();
	}
	return true;
};

/**
 * Remove content.
 *
 * @param {string} [key] Trigger remove as if a key were pressed, either 'backspace' or 'delete'
 * @return {boolean} Action was executed
 */
ve.ui.ContentAction.prototype.remove = function ( key ) {
	var defaultPrevented = false;
	if ( key ) {
		var e = {
			keyCode: key === 'delete' ? OO.ui.Keys.DELETE : OO.ui.Keys.BACKSPACE,
			preventDefault: function () {
				defaultPrevented = true;
			}
		};
		ve.ce.keyDownHandlerFactory.executeHandlersForKey(
			e.keyCode,
			this.surface.getModel().getSelection().getName(),
			this.surface.getView(),
			e
		);
		return defaultPrevented;
	} else {
		this.surface.getModel().getFragment().removeContent();
		return true;
	}
};

/**
 * Select content.
 *
 * @param {ve.dm.Selection} selection
 * @return {boolean} Action was executed
 */
ve.ui.ContentAction.prototype.select = function ( selection ) {
	this.surface.getModel().setSelection( selection );
	return true;
};

/**
 * Select all content.
 *
 * @return {boolean} Action was executed
 */
ve.ui.ContentAction.prototype.selectAll = function () {
	this.surface.getView().selectAll();
	return true;
};

/**
 * Paste special.
 *
 * @return {boolean} Action was executed
 */
ve.ui.ContentAction.prototype.pasteSpecial = function () {
	this.surface.getView().pasteSpecial = true;
	// Return false to allow the paste event to occur
	return false;
};

/**
 * Change directionality
 *
 * @return {boolean} Action was executed
 */
ve.ui.ContentAction.prototype.changeDirectionality = function () {
	var documentView = this.surface.getView().getDocument();
	documentView.setDir( documentView.getDir() === 'ltr' ? 'rtl' : 'ltr' );
	this.surface.getModel().emit( 'contextChange' );
	this.surface.getView().emit( 'position' );
	return true;
};

/**
 * Emit a surface submit event
 *
 * @return {boolean} Action was executed
 */
ve.ui.ContentAction.prototype.submit = function () {
	this.surface.emit( 'submit' );
	return true;
};

/**
 * Emit a surface cancel event
 *
 * @return {boolean} Action was executed
 */
ve.ui.ContentAction.prototype.cancel = function () {
	if ( this.surface.context.isVisible() ) {
		// T97350
		this.surface.context.hide();
	} else {
		this.surface.emit( 'cancel' );
	}
	return true;
};

/**
 * Move keyboard focus to the context menu.
 *
 * @return {boolean} Action was executed
 */
ve.ui.ContentAction.prototype.focusContext = function () {
	if ( this.surface.getContext().isVisible() ) {
		// Disable $focusTrapBefore so it doesn't get matched as the first
		// focusable item.
		this.surface.getContext().$focusTrapBefore.prop( 'disabled', true );
		var $focusable = OO.ui.findFocusable( this.surface.getContext().$element );
		this.surface.getContext().$focusTrapBefore.prop( 'disabled', false );
		if ( $focusable.length ) {
			this.surface.getView().deactivate();
			$focusable[ 0 ].focus();
			return true;
		}
	}
	return false;
};

/* Registration */

ve.ui.actionFactory.register( ve.ui.ContentAction );
