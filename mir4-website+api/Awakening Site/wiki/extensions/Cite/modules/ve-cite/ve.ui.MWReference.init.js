'use strict';

/*!
 * VisualEditor MediaWiki Cite initialisation code.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

( function () {
	function fixTarget( target ) {
		const toolGroups = target.static.toolbarGroups;

		if ( mw.config.get( 'wgCiteVisualEditorOtherGroup' ) ) {
			for ( let i = 0; i < toolGroups.length; i++ ) {
				const toolGroup = toolGroups[ i ];
				if ( toolGroup.name === 'insert' && ( !toolGroup.demote || toolGroup.demote.indexOf( 'reference' ) === -1 ) ) {
					toolGroup.demote = toolGroup.demote || [];
					toolGroup.demote.push( { group: 'cite' }, 'reference', 'reference/existing' );
				}
			}
		} else {
			// Find the reference placeholder group and replace it
			for ( let i = 0; i < toolGroups.length; i++ ) {
				if ( toolGroups[ i ].name === 'reference' ) {
					const group = {
						// Change the name so it isn't replaced twice
						name: 'cite',
						type: 'list',
						indicator: 'down',
						include: [ { group: 'cite' }, 'reference', 'reference/existing' ],
						demote: [ 'reference', 'reference/existing' ]
					};
					const label = OO.ui.deferMsg( 'cite-ve-toolbar-group-label' );
					// Treat mobile targets differently
					if ( target === ve.init.mw.MobileArticleTarget ) {
						group.header = label;
						group.title = label;
						group.icon = 'reference';
					} else {
						group.label = label;
					}
					toolGroups[ i ] = group;
					break;
				}
			}
		}
	}

	for ( const n in ve.init.mw.targetFactory.registry ) {
		fixTarget( ve.init.mw.targetFactory.lookup( n ) );
	}

	ve.init.mw.targetFactory.on( 'register', function ( name, target ) {
		fixTarget( target );
	} );

	/**
	 * Add reference insertion tools from on-wiki data.
	 *
	 * By adding a definition in JSON to
	 * MediaWiki:Visualeditor-cite-tool-definition, the cite menu can be populated
	 * with tools that create refrences containing a specific templates. The
	 * content of the definition should be an array containing a series of
	 * objects, one for each tool. Each object must contain a `name`, `icon` and
	 * `template` property. An optional `title` property can also be used to
	 * define the tool title in plain text. The `name` property is a unique
	 * identifier for the tool, and also provides a fallback title for the tool by
	 * being transformed into a message key. The name is prefixed with
	 * `visualeditor-cite-tool-name-`, and messages can be defined on Wiki. Some
	 * common messages are pre-defined for tool names such as `web`, `book`,
	 * `news` and `journal`.
	 *
	 * Example:
	 * [ { "name": "web", "icon": "browser", "template": "Cite web" }, ... ]
	 *
	 */
	( function () {
		const deprecatedIcons = {
				'ref-cite-book': 'book',
				'ref-cite-journal': 'journal',
				'ref-cite-news': 'newspaper',
				'ref-cite-web': 'browser',
				'reference-existing': 'referenceExisting'
			},
			defaultIcons = {
				book: 'book',
				journal: 'journal',
				news: 'newspaper',
				web: 'browser'
			};

		// This is assigned server-side by CitationToolDefinition.php, before this file runs.
		// Ensure it has a fallback, just in case.
		ve.ui.mwCitationTools = ve.ui.mwCitationTools || [];

		ve.ui.mwCitationTools.forEach( function ( item ) {
			const hasOwn = Object.prototype.hasOwnProperty;
			const data = { template: item.template, title: item.title };

			if ( !item.icon && hasOwn.call( defaultIcons, item.name ) ) {
				item.icon = defaultIcons[ item.name ];
			}

			if ( hasOwn.call( deprecatedIcons, item.icon ) ) {
				item.icon = deprecatedIcons[ item.icon ];
			}

			// Generate citation tool
			const name = 'cite-' + item.name;
			if ( !ve.ui.toolFactory.lookup( name ) ) {
				const tool = function GeneratedMWCitationDialogTool() {
					ve.ui.MWCitationDialogTool.apply( this, arguments );
				};
				OO.inheritClass( tool, ve.ui.MWCitationDialogTool );
				tool.static.group = 'cite';
				tool.static.name = name;
				tool.static.icon = item.icon;
				if ( mw.config.get( 'wgCiteVisualEditorOtherGroup' ) ) {
					tool.static.title = mw.msg( 'cite-ve-othergroup-item', item.title );
				} else {
					tool.static.title = item.title;
				}
				tool.static.commandName = name;
				tool.static.template = item.template;
				tool.static.autoAddToCatchall = false;
				tool.static.autoAddToGroup = true;
				tool.static.associatedWindows = [ name ];
				ve.ui.toolFactory.register( tool );
				ve.ui.commandRegistry.register(
					new ve.ui.Command(
						name, 'mwcite', 'open', { args: [ data ], supportedSelections: [ 'linear' ] }
					)
				);
			}

			// Generate citation context item
			if ( !ve.ui.contextItemFactory.lookup( name ) ) {
				const contextItem = function GeneratedMWCitationContextItem() {
					// Parent constructor
					ve.ui.MWCitationContextItem.apply( this, arguments );
				};
				OO.inheritClass( contextItem, ve.ui.MWCitationContextItem );
				contextItem.static.name = name;
				contextItem.static.icon = item.icon;
				contextItem.static.label = item.title;
				contextItem.static.commandName = name;
				contextItem.static.template = item.template;
				// If the grand-parent class (ve.ui.MWReferenceContextItem) is extended
				// and re-registered (e.g. by Citoid), then the inheritance chain is
				// broken, and the generic 'reference' context item would show. Instead
				// manually specify that that context should never show when a more
				// specific context item is shown.
				contextItem.static.suppresses = [ 'reference' ];
				ve.ui.contextItemFactory.register( contextItem );
			}
		} );
	}() );

}() );
