QUnit.module( 'ext.wikiEditor.toolbar', function ( hooks ) {
	hooks.beforeEach( function () {
		var $target = $( '<textarea>' )
			.attr( 'id', 'wpTextBox1' )
			.appendTo( '#qunit-fixture' );
		this.$target = $target;
		$target.wikiEditor( 'addModule', 'toolbar' );
		this.$ui = $target.data( 'wikiEditor-context' ).$ui;
	} );

	QUnit.test( 'Toolbars', function ( assert ) {
		// Add toolbar section
		var data = {
			sections: {
				emoticons: {
					type: 'toolbar',
					label: 'Emoticons'
				}
			}
		};
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section' ).length, 0, 'Before adding toolbar section' );
		this.$target.wikiEditor( 'addToToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section' ).length, 1, 'After adding toolbar section' );

		// Add toolbar group
		data = {
			section: 'emoticons',
			groups: {
				faces: {
					label: 'Faces'
				}
			}
		};
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section *[rel="faces"].group' ).length, 0, 'Before adding toolbar group' );
		this.$target.wikiEditor( 'addToToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section *[rel="faces"].group' ).length, 1, 'After adding toolbar group' );

		// Add button tool
		data = {
			section: 'emoticons',
			group: 'faces',
			tools: {
				smile: {
					label: 'Smile!',
					type: 'button',
					icon: 'http://upload.wikimedia.org/wikipedia/commons/thumb/a/a4/Gnome-face-smile.svg/22px-Gnome-face-smile.svg.png',
					action: {
						type: 'encapsulate',
						options: {
							pre: ':)'
						}
					}
				}
			}
		};
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section *[rel="faces"].group *[rel="smile"].tool' ).length, 0, 'Before adding button' );
		this.$target.wikiEditor( 'addToToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section *[rel="faces"].group *[rel="smile"].tool' ).length, 1, 'After adding button' );

		// Remove button tool
		data = {
			section: 'emoticons',
			group: 'faces',
			tool: 'smile'
		};
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section *[rel="faces"].group *[rel="smile"].tool' ).length, 1, 'Before removing button' );
		this.$target.wikiEditor( 'removeFromToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section *[rel="faces"].group *[rel="smile"].tool' ).length, 0, 'After removing button' );

		// Add select tool
		data = {
			section: 'emoticons',
			group: 'faces',
			tools: {
				icons: {
					label: 'Icons',
					type: 'select',
					list: {
						wink: {
							label: 'Wink',
							action: {
								type: 'encapsulate',
								options: {
									pre: ';)'
								}
							}
						},
						frown: {
							label: 'Frown',
							action: {
								type: 'encapsulate',
								options: {
									pre: ':('
								}
							}
						},
						bigSmile: {
							label: 'Big smile',
							action: {
								type: 'encapsulate',
								options: {
									pre: ':D'
								}
							}
						}
					}
				}
			}
		};
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section *[rel="faces"].group *[rel="icons"].tool' ).length, 0, 'Before adding select' );
		this.$target.wikiEditor( 'addToToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section *[rel="faces"].group *[rel="icons"].tool' ).length, 1, 'After adding select' );

		// Remove select tool
		data = {
			section: 'emoticons',
			group: 'faces',
			tool: 'icons'
		};
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section *[rel="faces"].group *[rel="icons"].tool' ).length, 1, 'Before removing select' );
		this.$target.wikiEditor( 'removeFromToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section *[rel="faces"].group *[rel="icons"].tool' ).length, 0, 'After removing select' );

		// Remove toolbar group
		data = {
			section: 'emoticons',
			group: 'faces'
		};
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section *[rel="faces"].group' ).length, 1, 'Before removing toolbar group' );
		this.$target.wikiEditor( 'removeFromToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section *[rel="faces"].group' ).length, 0, 'After removing toolbar group' );

		// Remove toolbar section
		data = {
			section: 'emoticons'
		};
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section' ).length, 1, 'Before removing toolbar section' );
		this.$target.wikiEditor( 'removeFromToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="emoticons"].section' ).length, 0, 'After removing toolbar section' );
	} );

	QUnit.test( 'Booklets', function ( assert ) {
		// Add booklet section
		var data = {
			sections: {
				info: {
					type: 'booklet',
					label: 'Info'
				}
			}
		};
		assert.strictEqual( this.$ui.find( '*[rel="info"].section' ).length, 0, 'Before adding booklet section' );
		this.$target.wikiEditor( 'addToToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="info"].section' ).length, 1, 'After adding booklet section' );

		// Add table page
		data = {
			section: 'info',
			pages: {
				colors: {
					layout: 'table',
					label: 'Colors',
					headings: [
						{ text: 'Name' },
						{ text: 'Temperature' },
						{ text: 'Swatch' }
					]
				}
			}
		};
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="colors"].page' ).length, 0, 'Before adding table page' );
		this.$target.wikiEditor( 'addToToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="colors"].page' ).length, 1, 'After adding table page' );

		// Add table rows
		data = {
			section: 'info',
			page: 'colors',
			rows: [
				{
					name: { text: 'Red' },
					temp: { text: 'Warm' },
					swatch: { html: '<div style="width: 10px; height: 10px; background-color: red;">' }
				},
				{
					name: { text: 'Blue' },
					temp: { text: 'Cold' },
					swatch: { html: '<div style="width: 10px; height: 10px; background-color: blue;">' }
				},
				{
					name: { text: 'Silver' },
					temp: { text: 'Neutral' },
					swatch: { html: '<div style="width: 10px; height: 10px; background-color: silver;">' }
				}
			]
		};
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="colors"].page tr td' ).length, 0, 'Before adding table rows' );
		this.$target.wikiEditor( 'addToToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="colors"].page tr td' ).length, 9, 'After adding table rows' );

		// Remove table row
		data = {
			section: 'info',
			page: 'colors',
			row: 0
		};
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="colors"].page tr td' ).length, 9, 'Before removing table row' );
		this.$target.wikiEditor( 'removeFromToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="colors"].page tr td' ).length, 6, 'After removing table row' );

		// Remove table page
		data = {
			section: 'info',
			page: 'colors'
		};
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="colors"].page' ).length, 1, 'Before removing table page' );
		this.$target.wikiEditor( 'removeFromToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="colors"].page' ).length, 0, 'After removing table page' );

		// Add character page
		data = {
			section: 'info',
			pages: {
				emoticons: {
					layout: 'characters',
					label: 'Emoticons'
				}
			}
		};
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="emoticons"].page' ).length, 0, 'Before adding character page' );
		this.$target.wikiEditor( 'addToToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="emoticons"].page' ).length, 1, 'After adding character page' );

		// Add characters
		data = {
			section: 'info',
			page: 'emoticons',
			characters: [
				':)', ':))', ':(', '<3', ';)',
				{
					label: ':-s',
					title: 'unsure-face',
					action: {
						type: 'replace',
						options: {
							peri: ':-s',
							selectPeri: false
						}
					}
				} ]
		};
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="emoticons"].page *[rel=":))"]' ).length, 0, 'Before adding characters' );
		this.$target.wikiEditor( 'addToToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="emoticons"].page *[rel=":))"]' ).length, 1, 'After adding characters' );
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="emoticons"].page *[title="unsure-face"]' ).length, 1, 'After adding characters find the set title' );

		// Remove character
		data = {
			section: 'info',
			page: 'emoticons',
			character: ':))'
		};
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="emoticons"].page *[rel=":))"]' ).length, 1, 'Before removing character' );
		this.$target.wikiEditor( 'removeFromToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="emoticons"].page *[rel=":))"]' ).length, 0, 'After removing character' );

		// Remove character page
		data = {
			section: 'info',
			page: 'emoticons'
		};
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="emoticons"].page' ).length, 1, 'Before removing character page' );
		this.$target.wikiEditor( 'removeFromToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="info"].section *[rel="emoticons"].page' ).length, 0, 'After removing character page' );

		// Remove booklet section
		data = {
			section: 'info'
		};
		assert.strictEqual( this.$ui.find( '*[rel="info"].section' ).length, 1, 'Before removing booklet section' );
		this.$target.wikiEditor( 'removeFromToolbar', data );
		assert.strictEqual( this.$ui.find( '*[rel="info"].section' ).length, 0, 'After removing booklet section' );
	} );

} );
