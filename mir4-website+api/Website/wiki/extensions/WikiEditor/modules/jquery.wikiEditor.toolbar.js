/**
 * Toolbar module for wikiEditor
 */
var toolbarModule = {

	/**
	 * API accessible functions
	 */
	api: {
		addToToolbar: function ( context, data ) {

			for ( var type in data ) {
				var i;
				switch ( type ) {
					case 'sections':
						var $sections = context.modules.toolbar.$toolbar.find( 'div.sections' );
						var $tabs = context.modules.toolbar.$toolbar.find( 'div.tabs' );
						for ( var section in data[ type ] ) {
							if ( section === 'main' || section === 'secondary' ) {
								// Section
								context.modules.toolbar.$toolbar.prepend(
									toolbarModule.fn.buildSection(
										context, section, data[ type ][ section ]
									)
								);
								continue;
							}
							// Section
							$sections.append(
								toolbarModule.fn.buildSection( context, section, data[ type ][ section ] )
							);
							// Tab
							$tabs.append(
								toolbarModule.fn.buildTab( context, section, data[ type ][ section ] )
							);
						}
						break;
					case 'groups':
						if ( !( 'section' in data ) ) {
							continue;
						}
						var $section = context.modules.toolbar.$toolbar.find( 'div[rel="' + data.section + '"].section' );
						for ( var group in data[ type ] ) {
							// Group
							$section.append(
								toolbarModule.fn.buildGroup( context, group, data[ type ][ group ] )
							);
						}
						break;
					case 'tools':
						if ( !( 'section' in data && 'group' in data ) ) {
							continue;
						}
						var $group = context.modules.toolbar.$toolbar.find(
							'div[rel="' + data.section + '"].section ' +
							'div[rel="' + data.group + '"].group'
						);
						for ( var tool in data[ type ] ) {
							// Tool
							$group.append( toolbarModule.fn.buildTool( context, tool, data[ type ][ tool ] ) );
						}
						if ( $group.children().length ) {
							$group.removeClass( 'empty' );
						}
						break;
					case 'pages':
						if ( !( 'section' in data ) ) {
							continue;
						}
						var $pages = context.modules.toolbar.$toolbar.find(
							'div[rel="' + data.section + '"].section .pages'
						);
						var $index = context.modules.toolbar.$toolbar.find(
							'div[rel="' + data.section + '"].section .index'
						);
						for ( var page in data[ type ] ) {
							// Page
							$pages.append( toolbarModule.fn.buildPage( context, page, data[ type ][ page ] ) );
							// Index
							$index.append(
								toolbarModule.fn.buildBookmark( context, page, data[ type ][ page ] )
							);
						}
						toolbarModule.fn.updateBookletSelection( context, data.section, $pages, $index );
						break;
					case 'rows':
						if ( !( 'section' in data && 'page' in data ) ) {
							continue;
						}
						var $table = context.modules.toolbar.$toolbar.find(
							'div[rel="' + data.section + '"].section ' +
							'div[rel="' + data.page + '"].page table'
						);
						for ( i = 0; i < data.rows.length; i++ ) {
							// Row
							$table.append( toolbarModule.fn.buildRow( context, data.rows[ i ] ) );
						}
						break;
					case 'characters':
						if ( !( 'section' in data && 'page' in data ) ) {
							continue;
						}
						var $characters = context.modules.toolbar.$toolbar.find(
							'div[rel="' + data.section + '"].section ' +
							'div[rel="' + data.page + '"].page div'
						);
						var actions = $characters.data( 'actions' );
						for ( i = 0; i < data.characters.length; i++ ) {
							// Character
							$characters.append(
								$( toolbarModule.fn.buildCharacter( data.characters[ i ], actions ) )
									.on( 'mousedown', function ( e ) {
										// No dragging!
										e.preventDefault();
										return false;
									} )
									.on( 'click', function ( e ) {
										toolbarModule.fn.doAction( $( this ).parent().data( 'context' ),
											$( this ).parent().data( 'actions' )[ $( this ).attr( 'rel' ) ] );
										e.preventDefault();
										return false;
									} )
							);
						}
						break;
					default: break;
				}
			}
		},
		removeFromToolbar: function ( context, data ) {
			if ( typeof data.section === 'string' ) {
				// Section
				var tab = 'div.tabs span[rel="' + data.section + '"].tab';
				var target = 'div[rel="' + data.section + '"].section';
				var group = null;
				if ( typeof data.group === 'string' ) {
					// Toolbar group
					target += ' div[rel="' + data.group + '"].group';
					if ( typeof data.tool === 'string' ) {
						// Save for later checking if empty
						group = target;
						// Tool
						target = target + ' [rel="' + data.tool + '"].tool';
					}
				} else if ( typeof data.page === 'string' ) {
					// Booklet page
					var index = target + ' div.index div[rel="' + data.page + '"]';
					target += ' div.pages div[rel="' + data.page + '"].page';
					if ( typeof data.character === 'string' ) {
						// Character
						target += ' span[rel="' + data.character + '"]';
					} else if ( typeof data.row === 'number' ) {
						// Table row
						target += ' table tr:not(:has(th)):eq(' + data.row + ')';
					} else {
						// Just a page, remove the index too!
						context.modules.toolbar.$toolbar.find( index ).remove();
						toolbarModule.fn.updateBookletSelection(
							context,
							data.section,
							context.modules.toolbar.$toolbar.find( target ),
							context.modules.toolbar.$toolbar.find( index )
						);
					}
				} else {
					// Just a section, remove the tab too!
					context.modules.toolbar.$toolbar.find( tab ).remove();
				}
				context.modules.toolbar.$toolbar.find( target ).remove();
				// Hide empty groups
				if ( group ) {
					var $group = context.modules.toolbar.$toolbar.find( group );
					if ( $group.children().length === 0 ) {
						$group.addClass( 'empty' );
					}
				}
			}
		}
	},

	/**
	 * Internally used functions
	 */
	fn: {
		/**
		 * Creates a toolbar module within a wikiEditor
		 *
		 * @param {Object} context Context object of editor to create module in
		 * @param {Object} config Configuration object to create module from
		 */
		create: function ( context, config ) {
			if ( '$toolbar' in context.modules.toolbar ) {
				return;
			}
			context.modules.toolbar.$toolbar = $( '<div>' )
				.addClass( 'wikiEditor-ui-toolbar' )
				.attr( 'id', 'wikiEditor-ui-toolbar' );
			toolbarModule.fn.build( context, config );
			context.$ui.find( '.wikiEditor-ui-top' ).append( context.modules.toolbar.$toolbar );
		},
		/**
		 * Performs an operation based on parameters
		 *
		 * @param {Object} context
		 * @param {Object} action
		 */
		doAction: function ( context, action ) {
			switch ( action.type ) {
				case 'replace':
				case 'encapsulate':
					if ( context.$textarea.prop( 'readonly' ) ) {
						break;
					}
					var parts = {
						pre: $.wikiEditor.autoMsg( action.options, 'pre' ),
						peri: $.wikiEditor.autoMsg( action.options, 'peri' ),
						post: $.wikiEditor.autoMsg( action.options, 'post' )
					};
					var replace = action.type === 'replace';
					if ( 'regex' in action.options && 'regexReplace' in action.options ) {
						var selection = context.$textarea.textSelection( 'getSelection' );
						if ( selection !== '' && action.options.regex.test( selection ) ) {
							parts.peri = selection.replace( action.options.regex,
								action.options.regexReplace );
							parts.pre = parts.post = '';
							replace = true;
						}
					}
					context.$textarea.textSelection(
						'encapsulateSelection',
						$.extend( {}, action.options, parts, { replace: replace } )
					);
					break;
				case 'callback':
					if ( typeof action.execute === 'function' ) {
						action.execute( context );
					}
					break;
				case 'dialog':
					context.fn.saveSelection();
					context.$textarea.wikiEditor( 'openDialog', action.module );
					break;
				default: break;
			}
		},
		buildGroup: function ( context, id, group ) {
			var $group = $( '<div>' ).attr( { class: 'group group-' + id, rel: id } ),
				label = $.wikiEditor.autoMsg( group, 'label' );
			if ( label ) {
				$( '<span>' ).addClass( 'label' ).text( label ).appendTo( $group );
			}
			var empty = true;
			if ( 'tools' in group ) {
				for ( var tool in group.tools ) {
					var $tool = toolbarModule.fn.buildTool( context, tool, group.tools[ tool ] );
					if ( $tool ) {
						// Consider a group with only hidden tools empty as well
						// .is( ':visible' ) always returns false because tool is not attached to the DOM yet
						empty = empty && $tool.css( 'display' ) === 'none';
						$group.append( $tool );
					}
				}
			}
			if ( empty ) {
				$group.addClass( 'empty' );
			}
			return $group;
		},
		buildTool: function ( context, id, tool ) {
			if ( 'filters' in tool ) {
				for ( var i = 0; i < tool.filters.length; i++ ) {
					if ( $( tool.filters[ i ] ).length === 0 ) {
						return null;
					}
				}
			}
			var label = $.wikiEditor.autoMsg( tool, 'label' );
			switch ( tool.type ) {
				case 'button':
				case 'toggle':
					var $button;
					if ( tool.oouiIcon ) {
						var config = {
							framed: false,
							classes: [ 'tool' ],
							icon: tool.oouiIcon,
							title: label
						};
						var oouiButton;
						if ( tool.type === 'button' ) {
							oouiButton = new OO.ui.ButtonWidget( config );
						} else if ( tool.type === 'toggle' ) {
							oouiButton = new OO.ui.ToggleButtonWidget( config );
						}
						$button = oouiButton.$element
							.attr( 'rel', id )
							.data( 'ooui', oouiButton );
					} else {
						$button = $( '<a>' )
							.attr( {
								tabindex: 0,
								title: label,
								rel: id,
								role: 'button',
								class: 'tool tool-button'
							} )
							.text( label );
						if ( tool.icon ) {
							var icon = $.wikiEditor.autoIcon(
								tool.icon,
								$.wikiEditor.imgPath + 'toolbar/'
							);
							$button.css( 'background-image', 'url(' + icon + ')' );
						}
					}
					$button.data( 'setActive', function ( active ) {
						$button.toggleClass( 'tool-active', active );

						// OOUI button
						if ( $button.data( 'ooui' ) && tool.type === 'toggle' ) {
							$button.data( 'ooui' ).setValue( active );
							// Use progressive icon in WMUI theme
							if ( OO.ui.WikimediaUITheme && OO.ui.theme instanceof OO.ui.WikimediaUITheme ) {
								// Wait for updateElementClasses to run
								setTimeout( function () {
									$button.data( 'ooui' ).$icon.toggleClass( 'oo-ui-image-progressive', active );
								} );
							}
						}
					} );
					if ( 'action' in tool ) {
						$button
							.data( 'action', tool.action )
							.data( 'context', context )
							.on( 'mousedown', function ( e ) {
								// No dragging!
								e.preventDefault();
								return false;
							} );
						if ( $button.data( 'ooui' ) ) {
							$button.data( 'ooui' ).on( 'click', function () {
								toolbarModule.fn.doAction(
									context, tool.action
								);
							} );
						} else {
							$button.on( 'click keydown', function ( e ) {
								if (
									e.type === 'click' ||
									e.type === 'keydown' && e.key === 'Enter'
								) {
									toolbarModule.fn.doAction(
										context, tool.action
									);
									e.preventDefault();
									return false;
								}
							} );
						}
					}
					return $button;
				case 'select':
					var menuId = 'menu-' + Date.now();
					var $select = $( '<div>' )
						.attr( { rel: id, class: 'tool tool-select' } );
					var $options = $( '<div>' ).addClass( 'options' );
					if ( 'list' in tool ) {
						for ( var option in tool.list ) {
							var optionLabel = $.wikiEditor.autoMsg( tool.list[ option ], 'label' );
							$options.append(
								$( '<a>' )
									.data( 'action', tool.list[ option ].action )
									.data( 'context', context )
									.on( 'mousedown', function ( e ) {
										// No dragging!
										e.preventDefault();
										return false;
									} )
									.on( 'click keydown', function ( e ) {
										if (
											e.type === 'click' ||
											e.type === 'keydown' && e.key === 'Enter'
										) {
											toolbarModule.fn.doAction(
												$( this ).data( 'context' ), $( this ).data( 'action' ), $( this )
											);
											// Hide the dropdown
											$( this ).closest( '.tool-select' ).removeClass( 'options-shown' );
											e.preventDefault();
											return false;
										}
									} )
									.text( optionLabel )
									.addClass( 'option' )
									.attr( { rel: option, tabindex: 0, role: 'menuitem' } )
							);
						}
					}
					$select.append( $( '<a>' )
						.addClass( 'label' )
						.text( label )
						.data( 'options', $options )
						.attr( { role: 'button', tabindex: 0, 'aria-expanded': false, 'aria-controls': menuId, 'aria-haspopup': 'menu' } )
						.on( 'mousedown', function ( e ) {
							// No dragging!
							e.preventDefault();
							return false;
						} )
						.on( 'click keydown', function ( e ) {
							if (
								e.type === 'click' ||
								e.type === 'keydown' && e.key === 'Enter'
							) {
								var $opts = $( this ).data( 'options' );
								// eslint-disable-next-line no-jquery/no-class-state
								var canShowOptions = !$opts.closest( '.tool-select' ).hasClass( 'options-shown' );
								$opts.closest( '.tool-select' ).toggleClass( 'options-shown', canShowOptions );
								$( this ).attr( 'aria-expanded', canShowOptions.toString() );
								e.preventDefault();
								return false;
							}
						} )
					);
					$select.append( $( '<div>' ).addClass( 'menu' ).append( $options ) );
					return $select;
				case 'element':
					// A raw 'element' type can be {htmlString|Element|Text|Array|jQuery|OO.ui.HTMLSnippet|function}.
					var $element;
					if ( tool.element instanceof OO.ui.HtmlSnippet ) {
						$element = tool.element.toString();
					} else if ( typeof tool.element === 'function' ) {
						$element = tool.element( context );
					} else {
						$element = tool.element;
					}
					return $( '<div>' )
						.attr( { rel: id, class: 'tool tool-element' } )
						.append( $element );
				default:
					return null;
			}
		},
		buildBookmark: function ( context, id, page ) {
			var label = $.wikiEditor.autoMsg( page, 'label' );
			return $( '<div>' )
				.text( label )
				.attr( {
					rel: id,
					role: 'option'
				} )
				.data( 'context', context )
				.on( 'mousedown', function ( e ) {
					// No dragging!
					e.preventDefault();
					return false;
				} )
				.on( 'click', function ( event ) {
					$( this ).parent().parent().find( '.page' ).hide();
					$( this ).parent().parent().find( '.page-' + $( this ).attr( 'rel' ) ).show().trigger( 'loadPage' );
					$( this )
						.addClass( 'current' )
						.siblings().removeClass( 'current' );
					var section = $( this ).parent().parent().attr( 'rel' );
					$.cookie(
						'wikiEditor-' + $( this ).data( 'context' ).instance + '-booklet-' + section + '-page',
						$( this ).attr( 'rel' ),
						{ expires: 30, path: '/' }
					);
					// No dragging!
					event.preventDefault();
					return false;
				} );
		},
		buildPage: function ( context, id, page, deferLoad ) {
			var $page = $( '<div>' ).attr( {
				class: 'page page-' + id,
				rel: id
			} );
			if ( deferLoad ) {
				$page.one( 'loadPage', function () {
					toolbarModule.fn.reallyBuildPage( context, id, page, $page );
				} );
			} else {
				toolbarModule.fn.reallyBuildPage( context, id, page, $page );
			}
			return $page;
		},
		reallyBuildPage: function ( context, id, page, $page ) {
			var i;
			switch ( page.layout ) {
				case 'table':
					$page.addClass( 'page-table' );
					var html = '';
					if ( 'headings' in page ) {
						html += toolbarModule.fn.buildHeading( context, page.headings );
					}
					if ( 'rows' in page ) {
						for ( i = 0; i < page.rows.length; i++ ) {
							html += toolbarModule.fn.buildRow( context, page.rows[ i ] );
						}
					}
					$page.html( '<table class="table-' + id + '">' + html + '</table>' );
					break;
				case 'characters':
					$page.addClass( 'page-characters' );
					var $characters = $( '<div>' ).data( 'context', context ).data( 'actions', {} );
					var actions = $characters.data( 'actions' );
					if ( 'language' in page ) {
						$characters.attr( 'lang', page.language );
					}
					if ( 'direction' in page ) {
						$characters.attr( 'dir', page.direction );
					} else {
						// By default it should be explicit ltr for all scripts.
						// Without this some conjoined ltr characters look
						// weird in rtl wikis.
						$characters.attr( 'dir', 'ltr' );
					}
					if ( 'characters' in page ) {
						html = '';
						for ( i = 0; i < page.characters.length; i++ ) {
							html += toolbarModule.fn.buildCharacter( page.characters[ i ], actions );
						}
						$characters
							.html( html )
							.children()
							.attr( 'role', 'option' )
							.on( 'mousedown', function ( e ) {
								// No dragging!
								e.preventDefault();
								return false;
							} )
							.on( 'click', function ( e ) {
								toolbarModule.fn.doAction(
									$( this ).parent().data( 'context' ),
									$( this ).parent().data( 'actions' )[ $( this ).attr( 'rel' ) ],
									$( this )
								);
								e.preventDefault();
								return false;
							} );
					}
					$page.append( $characters );
					break;
			}
		},
		buildHeading: function ( context, headings ) {
			var html = '';
			for ( var i = 0; i < headings.length; i++ ) {
				html += '<th>' + $.wikiEditor.autoSafeMsg( headings[ i ], [ 'html', 'text' ] ) + '</th>';
			}
			return '<tr>' + html + '</tr>';
		},
		buildRow: function ( context, row ) {
			var html = '';
			for ( var cell in row ) {
				// FIXME: This currently needs to use the "unsafe" .text() message because it embeds raw HTML
				// in the messages (as used exclusively by the 'help' toolbar panel).
				html += '<td class="cell cell-' + cell + '"><span>' +
					$.wikiEditor.autoMsg( row[ cell ], [ 'html', 'text' ] ) + '</span></td>';
			}
			return '<tr>' + html + '</tr>';
		},
		buildCharacter: function ( character, actions ) {
			if ( typeof character === 'string' ) {
				character = {
					label: character,
					action: {
						type: 'replace',
						options: {
							peri: character,
							selectPeri: false
						}
					}
				};
			// In some cases the label for the character isn't the same as the
			// character that gets inserted (e.g. Hebrew vowels)
			} else if ( character && 0 in character && 1 in character ) {
				character = {
					label: character[ 0 ],
					action: {
						type: 'replace',
						options: {
							peri: character[ 1 ],
							selectPeri: false
						}
					}
				};
			}
			if ( character && 'action' in character && 'label' in character ) {
				actions[ character.label ] = character.action;
				// eslint-disable-next-line mediawiki/msg-doc
				var title = character.titleMsg ? mw.msg( character.titleMsg ) : character.title;
				return mw.html.element(
					'span',
					{ rel: character.label, title: title || false },
					character.label
				);
			}
			mw.log( 'A character for the toolbar was undefined. This is not supposed to happen. Double check the config.' );
			// bug 31673; also an additional fix for bug 24208...
			return '';
		},
		buildTab: function ( context, id, section ) {
			var selected = $.cookie( 'wikiEditor-' + context.instance + '-toolbar-section' );
			// Re-save cookie
			if ( selected !== null ) {
				$.cookie( 'wikiEditor-' + context.instance + '-toolbar-section', selected, { expires: 30, path: '/' } );
			}
			var $link =
				$( '<a>' )
					.addClass( selected === id ? 'current' : null )
					.attr( {
						tabindex: 0,
						role: 'button',
						'aria-expanded': ( selected === id ).toString(),
						'aria-controls': 'wikiEditor-section-' + id
					} )
					.text( $.wikiEditor.autoMsg( section, 'label' ) )
					.data( 'context', context )
					.on( 'mouseup', function () {
						$( this ).trigger( 'blur' );
					} )
					.on( 'mousedown', function ( e ) {
						// No dragging!
						e.preventDefault();
						return false;
					} )
					.on( 'click keydown', function ( e ) {
						if (
							e.type !== 'click' &&
							( e.type !== 'keydown' || e.key !== 'Enter' )
						) {
							return;
						}
						// We have to set aria-pressed over here, as NVDA wont recognize it
						// if we do it in the below .each as it seems
						$( this ).attr( 'aria-pressed', 'true' );
						$( '.tab > a' ).each( function ( i, elem ) {
							if ( elem !== e.target ) {
								$( elem ).attr( 'aria-pressed', 'false' );
							}
						} );
						var $sections = $( this ).data( 'context' ).$ui.find( '.sections' );
						var $section = $sections.find( '.section-' + $( this ).parent().attr( 'rel' ) );
						// eslint-disable-next-line no-jquery/no-class-state
						var show = !$section.hasClass( 'section-visible' );
						$sections.find( '.section-visible' )
							.removeClass( 'section-visible' )
							.addClass( 'section-hidden' );

						$( this )
							.attr( 'aria-expanded', 'false' )
							.parent().parent().find( 'a' ).removeClass( 'current' );
						if ( show ) {
							$section
								.removeClass( 'section-hidden' )
								.attr( 'aria-expanded', 'true' )
								.addClass( 'section-visible' );

							$( this ).attr( 'aria-expanded', 'true' )
								.addClass( 'current' );
						}

						// Save the currently visible section
						$.cookie(
							'wikiEditor-' + $( this ).data( 'context' ).instance + '-toolbar-section',
							show ? $section.attr( 'rel' ) : null,
							{ expires: 30, path: '/' }
						);

						e.preventDefault();
						return false;
					} );
			return $( '<span>' )
				.attr( {
					class: 'tab tab-' + id,
					rel: id
				} )
				.append( $link );
		},
		buildSection: function ( context, id, section ) {
			var $section = $( '<div>' ).attr( {
				class: section.type + ' section section-' + id,
				rel: id,
				id: 'wikiEditor-section-' + id
			} );
			var selected = $.cookie( 'wikiEditor-' + context.instance + '-toolbar-section' );
			var show = selected === id;

			toolbarModule.fn.reallyBuildSection( context, id, section, $section, section.deferLoad );

			// Show or hide section
			if ( id !== 'main' && id !== 'secondary' ) {
				$section
					.attr( 'aria-expanded', show.toString() )
					.addClass( show ? 'section-visible' : 'section-hidden' );
			}
			return $section;
		},
		reallyBuildSection: function ( context, id, section, $section, deferLoad ) {
			context.$textarea.trigger( 'wikiEditor-toolbar-buildSection-' + $section.attr( 'rel' ), [ section ] );
			switch ( section.type ) {
				case 'toolbar':
					if ( 'groups' in section ) {
						for ( var group in section.groups ) {
							$section.append(
								toolbarModule.fn.buildGroup( context, group, section.groups[ group ] )
							);
						}
					}
					break;
				case 'booklet':
					var $pages = $( '<div>' )
						.addClass( 'pages' )
						.attr( {
							tabindex: '0',
							role: 'listbox'
						} )
						.on( 'keydown', function ( event ) {
							var $selected = $pages.children().filter( function () {
								return $( this ).css( 'display' ) !== 'none';
							} );
							$.wikiEditor.modules.toolbar.fn.handleKeyDown( $selected.children().first(), event, $pages );
						} );
					var $index = $( '<div>' )
						.addClass( 'index' )
						.attr( {
							tabindex: '0',
							role: 'listbox'
						} )
						.on( 'keydown', function ( event ) {
							$.wikiEditor.modules.toolbar.fn.handleKeyDown( $index, event, $index );
						} );
					if ( 'pages' in section ) {
						for ( var page in section.pages ) {
							$pages.append(
								toolbarModule.fn.buildPage( context, page, section.pages[ page ], deferLoad )
							);
							$index.append(
								toolbarModule.fn.buildBookmark( context, page, section.pages[ page ] )
							);
						}
					}
					$section.append( $index, $pages );
					toolbarModule.fn.updateBookletSelection( context, id, $pages, $index );
					break;
			}
		},
		updateBookletSelection: function ( context, id, $pages, $index ) {
			var cookie = 'wikiEditor-' + context.instance + '-booklet-' + id + '-page',
				selected = $.cookie( cookie );
			// Re-save cookie
			if ( selected !== null ) {
				$.cookie( cookie, selected, { expires: 30, path: '/' } );
			}
			var $selectedIndex = $index.find( '*[rel="' + selected + '"]' );
			if ( $selectedIndex.length === 0 ) {
				$selectedIndex = $index.children().eq( 0 );
				selected = $selectedIndex.attr( 'rel' );
			}
			$pages.children().hide();
			$pages.find( '*[rel="' + selected + '"]' ).show().trigger( 'loadPage' );
			$index.children().removeClass( 'current' );
			$selectedIndex.addClass( 'current' );
		},
		build: function ( context, config ) {
			var $tabs = $( '<div>' ).addClass( 'tabs' ).appendTo( context.modules.toolbar.$toolbar ),
				$sections = $( '<div>' ).addClass( 'sections' ).appendTo( context.modules.toolbar.$toolbar );
			context.modules.toolbar.$toolbar.append( $( '<div>' ).css( 'clear', 'both' ) );
			for ( var section in config ) {
				if ( section === 'main' || section === 'secondary' ) {
					context.modules.toolbar.$toolbar.prepend(
						toolbarModule.fn.buildSection( context, section, config[ section ] )
					);
				} else {
					$sections.append( toolbarModule.fn.buildSection( context, section, config[ section ] ) );
					$tabs.append( toolbarModule.fn.buildTab( context, section, config[ section ] ) );
				}
			}
			setTimeout( function () {
				context.$textarea.trigger( 'wikiEditor-toolbar-doneInitialSections' );
				// Use hook for attaching new toolbar tools to avoid race conditions
				mw.hook( 'wikiEditor.toolbarReady' ).fire( context.$textarea );
			} );
		},
		handleKeyDown: function ( $element, event, $parent ) {
			var $currentItem = $element.find( '.wikiEditor-character-highlighted' ),
				optionOffset = $parent.find( '.wikiEditor-character-highlighted' ).offset(),
				optionTop = optionOffset ? optionOffset.top : 0,
				selectTop = $parent.offset().top;

			var $nextItem;
			switch ( event.keyCode ) {
				// Up arrow
				case 38:
					if ( $currentItem.length ) {
						$currentItem.removeClass( 'wikiEditor-character-highlighted' );
						$nextItem = $currentItem.prev();
						$nextItem = $nextItem.length ? $nextItem : $currentItem;
						$nextItem.addClass( 'wikiEditor-character-highlighted' );
					} else {
						$element.children().first().addClass( 'wikiEditor-character-highlighted' );
					}
					event.preventDefault();
					event.stopPropagation();
					break;
				// Down arrow
				case 40:
					if ( $currentItem.length ) {
						$currentItem.removeClass( 'wikiEditor-character-highlighted' );
						$nextItem = $currentItem.next();
						$nextItem = $nextItem.length ? $nextItem : $currentItem;
						$nextItem.addClass( 'wikiEditor-character-highlighted' );
					} else {
						$element.children().first().addClass( 'wikiEditor-character-highlighted' );
					}
					event.preventDefault();
					event.stopPropagation();
					break;
				// Enter
				case 13:
					$currentItem.trigger( 'click' );
					break;
			}
			$parent.scrollTop( $parent.scrollTop() + ( optionTop - selectTop ) );
		}
	}
};

module.exports = toolbarModule;
