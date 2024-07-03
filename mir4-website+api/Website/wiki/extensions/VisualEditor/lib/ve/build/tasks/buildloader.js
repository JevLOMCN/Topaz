/*!
 * Build a static loader file from a template
 */

'use strict';

module.exports = function ( grunt ) {

	grunt.registerMultiTask( 'buildloader', function () {
		const styles = [],
			scripts = [],
			loadedModules = [],
			targetFile = this.data.targetFile,
			pathPrefix = this.data.pathPrefix || '',
			i18n = this.data.i18n || [],
			demoPages = this.data.demoPages,
			indent = this.data.indent || '',
			modules = this.data.modules,
			load = this.data.load,
			run = this.data.run || [],
			env = this.data.env || {},
			placeholders = this.data.placeholders || {},
			dir = this.data.dir,
			langList = this.data.langList !== undefined ? this.data.langList : true,
			done = this.async(),
			moduleUtils = require( '../moduleUtils' ),
			stringifyObject = require( 'stringify-object' );
		let text = grunt.file.read( this.data.template );

		/**
		 * Build a script tag
		 *
		 * @param {string} src The script URL
		 * @return {string} Script tag, indented as appropriate
		 */
		function scriptTag( src ) {
			return indent + '<script src="' + pathPrefix + src.file + '"></script>';
		}

		/**
		 * Build a stylesheet link tag
		 *
		 * @param {string} [group] Group for the stylesheet
		 * @param {string} src The stylesheet URL
		 * @return {string} Stylesheet link tag, indented as appropriate
		 */
		function styleTag( group, src ) {
			const rtlFilepath = src.file.replace( /\.css$/, '.rtl.css' );

			const rel = src.file.endsWith( '.less' ) ? 'stylesheet/less' : 'stylesheet';

			if ( rtlFilepath !== src.file && grunt.file.exists( rtlFilepath ) ) {
				if ( !dir ) {
					return indent + '<link rel="' + rel + '" href="' + pathPrefix + src.file + '" class="stylesheet-ltr' +
						( group ? ' stylesheet-' + group : '' ) + '">\n' +
						indent + '<link rel="' + rel + '" href="' + pathPrefix + rtlFilepath + '" class="stylesheet-rtl' +
						( group ? ' stylesheet-' + group : '' ) + '">';
				} else if ( dir === 'rtl' ) {
					return indent + '<link rel="' + rel + '" href="' + pathPrefix + rtlFilepath + '"' +
						( group ? ' class="stylesheet-' + group + '"' : '' ) + '>';
				}
			}
			return indent + '<link rel="' + rel + '" href="' + pathPrefix + src.file + '"' +
				( group ? ' class="stylesheet-' + group + '"' : '' ) + '>';
		}

		/**
		 * Expand string file path into a file description object { file: src }
		 *
		 * Do nothing if already expanded.
		 *
		 * @param {string|Object} src File path, or file description object
		 * @return {Object} File description object
		 */
		function expand( src ) {
			return typeof src === 'string' ? { file: src } : src;
		}

		/**
		 * Filter out debug files, depending on env.test / env.debug
		 *
		 * @param {string} [type] The type of the file
		 * @param {Object} src File description object
		 * @return {boolean} Whether to include the file
		 */
		function filter( type, src ) {
			if ( src.debug && !env.debug ) {
				return false;
			}
			if ( type === 'styles' && env.test && !src.test ) {
				return false;
			}

			return true;
		}

		/**
		 * Substitute placeholder text
		 *
		 * @param {string} input The input text, maybe containing placeholders
		 * @param {string} id The id to replace
		 * @param {string|Function} replacement Replacement string, or function returning one
		 * @param {Function} callback Will be called with the substituted output
		 */
		function placeholder( input, id, replacement, callback ) {
			// eslint-disable-next-line security/detect-non-literal-regexp
			const rComment = new RegExp( '<!-- ' + id + ' -->', 'm' );
			if ( typeof replacement === 'function' ) {
				replacement( function ( response ) {
					const output = input.replace( rComment, response );
					callback( output );
				} );
			} else {
				const output = input.replace( rComment, replacement );
				callback( output );
			}
		}

		/**
		 * Push modules into loadedModules
		 *
		 * @param {string[]} l Modules specified in the buildLoader config (see Gruntfile.js)
		 */
		function addModules( l ) {
			const dependencies = moduleUtils.buildDependencyList( modules, l );
			for ( const dependency in dependencies ) {
				const module = dependencies[ dependency ];
				if ( loadedModules.includes( module ) ) {
					continue;
				}
				loadedModules.push( module );
				if ( modules[ module ].scripts ) {
					const moduleScripts = modules[ module ].scripts
						.map( expand ).filter( filter.bind( this, 'scripts' ) ).map( scriptTag )
						.join( '\n' );
					if ( moduleScripts ) {
						scripts.push( indent + '<!-- ' + module + ' -->\n' + moduleScripts );
					}
				}
				if ( modules[ module ].styles ) {
					const moduleStyles = modules[ module ].styles
						.map( expand ).filter( filter.bind( this, 'styles' ) ).map( styleTag.bind( styleTag, modules[ module ].styleGroup ) )
						.join( '\n' );
					if ( moduleStyles ) {
						styles.push( indent + '<!-- ' + module + ' -->\n' + moduleStyles );
					}
				}
			}
		}

		addModules( load );

		if ( i18n.length || demoPages ) {
			let configScript = indent + '<script>\n';

			if ( i18n.length ) {
				configScript +=
					indent + '\tve.messagePaths = ' +
					stringifyObject(
						i18n.map( function ( path ) {
							return pathPrefix + path;
						} )
					).replace( /\n/g, '\n\t' + indent ) + ';\n';

				if ( langList ) {
					configScript += indent + '\tve.availableLanguages = ' +
						stringifyObject(
							Array.from( new Set(
								grunt.file.expand(
									i18n.map( function ( path ) {
										return path + '*.json';
									} )
								).map( function ( file ) {
									return file.split( '/' ).pop().slice( 0, -5 );
								} )
							) ).sort()
						).replace( /\n\t*/g, ' ' ) +
						';\n';
				}
			}
			if ( demoPages ) {
				configScript += indent + '\tve.demoPages = ' + stringifyObject( demoPages ).replace( /\n/g, '\n\t' + indent ) + ';\n';
			}

			configScript +=
				indent + '</script>';
			scripts.push( configScript );
		}

		addModules( run );

		placeholders.styles = styles.join( '\n\n' );
		placeholders.scripts = scripts.join( '\n\n' );
		placeholders.dir = dir;

		grunt.util.async.forEachSeries(
			Object.keys( placeholders ),
			function ( id, next ) {
				placeholder( text, id.toUpperCase(), placeholders[ id ], function ( newText ) {
					text = newText;
					next();
				} );
			},
			function () {
				grunt.file.write( targetFile, text );
				grunt.log.ok( 'File "' + targetFile + '" written.' );

				done();
			}
		);

	} );

};
