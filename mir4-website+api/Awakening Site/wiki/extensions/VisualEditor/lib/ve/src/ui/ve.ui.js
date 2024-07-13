/*!
 * VisualEditor UserInterface namespace.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Namespace for all VisualEditor UserInterface classes, static methods and static properties.
 *
 * @class
 * @singleton
 */
ve.ui = {
	// 'actionFactory' instantiated in ve.ui.ActionFactory.js
	// 'commandRegistry' instantiated in ve.ui.CommandRegistry.js
	// 'commandHelpRegistry' instantiated in ve.ui.CommandHelpRegistry.js
	// 'triggerRegistry' instantiated in ve.ui.TriggerRegistry.js
	// 'toolFactory' instantiated in ve.ui.ToolFactory.js
	// 'contextItemFactory' instantiated in ve.ui.ContextItemFactory.js
	// 'dataTransferHandlerFactory' instantiated in ve.ui.DataTransferHandlerFactory.js
	windowFactory: new OO.Factory()
};

ve.ui.windowFactory.register( OO.ui.MessageDialog );
