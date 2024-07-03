/*!
 * VisualEditor MediaWiki CommandRegistry registrations.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/* MW-specific over-rides of core command registrations */

ve.ui.commandRegistry.register(
	new ve.ui.Command( 'insertTable', 'table', 'create',
		{
			args: [ {
				caption: true,
				header: true,
				rows: 3,
				cols: 4,
				type: 'mwTable',
				attributes: { wikitable: true }
			} ],
			supportedSelections: [ 'linear' ]
		}
	)
);

ve.ui.commandRegistry.register(
	new ve.ui.Command( 'mwNonBreakingSpace', 'content', 'insert', {
		args: [
			[
				{ type: 'mwEntity', attributes: { character: '\u00a0' } },
				{ type: '/mwEntity' }
			],
			// annotate
			true,
			// collapseToEnd
			true
		],
		supportedSelections: [ 'linear' ]
	} )
);
ve.ui.triggerRegistry.register(
	'mwNonBreakingSpace', {
		mac: [],
		pc: new ve.ui.Trigger( 'ctrl+shift+space' )
	}
);
