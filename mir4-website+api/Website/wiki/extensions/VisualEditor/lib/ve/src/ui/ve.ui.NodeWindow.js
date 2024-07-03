/*!
 * VisualEditor user interface NodeWindow class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Mixin for window for working with a node.
 *
 * Conceptually this extends a FragmentWindow, but as this and FragmentWindow
 * are both are mixins, we don't need to set up actual inheritance here,
 * that is handled by the concrete classes NodeDialog & NodeInspector. T264690
 *
 * @class
 * @abstract
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.NodeWindow = function VeUiNodeWindow() {
	// Properties
	this.selectedNode = null;
};

/* Inheritance */

OO.initClass( ve.ui.NodeWindow );

/* Static Properties */

/**
 * Node classes compatible with this dialog.
 *
 * @static
 * @property {Function}
 * @inheritable
 */
ve.ui.NodeWindow.static.modelClasses = [];

/* Methods */

/**
 * Check if the current node is editable by this window.
 *
 * @localdoc Returns true if the node being edited selects at least one model,
 *
 * @return {boolean} Node is editable by this window
 */
ve.ui.NodeWindow.prototype.isEditing = function () {
	return !!this.getSelectedNode();
};

/**
 * Get the selected node.
 *
 * Should only be called after setup and before teardown.
 * If no node is selected or the selected node is incompatible, null will be returned.
 *
 * @param {Object} [data] Window opening data
 * @return {ve.dm.Node|null} Selected node
 */
ve.ui.NodeWindow.prototype.getSelectedNode = function () {
	var modelClasses = this.constructor.static.modelClasses,
		selectedNode = this.getFragment().getSelectedNode();

	if (
		selectedNode &&
		modelClasses.some( function ( modelClass ) {
			return selectedNode instanceof modelClass;
		} )
	) {
		return selectedNode;
	}
	return null;
};

/**
 * @inheritdoc OO.ui.Window
 */
ve.ui.NodeWindow.prototype.getSetupProcess = function ( data, process ) {
	data = data || {};
	return process.next( function () {
		this.selectedNode = this.getSelectedNode( data );
	}, this );
};

/**
 * @inheritdoc OO.ui.Window
 */
ve.ui.NodeWindow.prototype.getTeardownProcess = function ( data, process ) {
	data = data || {};
	return process.next( function () {
		this.selectedNode = null;
	}, this );
};
