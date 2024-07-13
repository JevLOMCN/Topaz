/*!
 * VisualEditor UserInterface FragmentWindowTool classes.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface fragment window tool.
 *
 * @abstract
 * @class
 * @extends ve.ui.WindowTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.FragmentWindowTool = function VeUiFragmentWindowTool() {
	// Parent constructor
	ve.ui.FragmentWindowTool.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.FragmentWindowTool, ve.ui.WindowTool );

/* Static Properties */

/**
 * Annotation or node models this tool is related to.
 *
 * Used by #isCompatibleWith.
 *
 * @static
 * @property {Function[]}
 * @inheritable
 */
ve.ui.FragmentWindowTool.static.modelClasses = [];

ve.ui.FragmentWindowTool.static.deactivateOnSelect = false;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.FragmentWindowTool.static.isCompatibleWith = function ( model ) {
	return ve.isInstanceOfAny( model, this.modelClasses );
};

/**
 * @inheritdoc
 */
ve.ui.FragmentWindowTool.prototype.onUpdateState = function ( fragment ) {
	this.setActive( false );

	// Grand-parent method
	// Parent method is skipped because it set's active state based on which windows
	// are open, which we override in this implementation
	ve.ui.FragmentWindowTool.super.super.prototype.onUpdateState.apply( this, arguments );

	var models = this.getSelectedModels( fragment );

	for ( var i = 0, len = models.length; i < len; i++ ) {
		if ( this.constructor.static.isCompatibleWith( models[ i ] ) ) {
			this.setActive( true );
			break;
		}
	}
};

/**
 * Get list of selected nodes and annotations.
 *
 * @param {ve.dm.SurfaceFragment|null} fragment Surface fragment
 * @return {ve.dm.Model[]} Selected models
 */
ve.ui.FragmentWindowTool.prototype.getSelectedModels = function ( fragment ) {
	return fragment ? fragment.getSelectedModels() : [];
};
