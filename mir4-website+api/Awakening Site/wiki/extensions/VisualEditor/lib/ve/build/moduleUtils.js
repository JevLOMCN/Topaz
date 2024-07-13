/*!
 * Utility methods for interpreting the modules.json manifest.
 *
 * Code shared with the OOjs UI project
 */

'use strict';

const self = module.exports = {
	/**
	 * Expand an array of file paths and variant-objects into
	 * a flattened list by variant.
	 *
	 *     input = [
	 *       'foo.js',
	 *       'bar.js',
	 *       { default: 'baz-fallback.js', svg: 'baz-svg.js', }.
	 *       'quux.js'
	 *     ]
	 *
	 *     output = {
	 *         default: [
	 *             'foo.js',
	 *             'bar.js'
	 *             'baz-fallback.js'
	 *             'quux.js'
	 *         ],
	 *         svg: [
	 *             'foo.js',
	 *             'bar.js'
	 *             'baz-svg.js'
	 *             'quux.js'
	 *         ]
	 *    ]
	 *
	 * @param {Array} resources List of expandable resources
	 * @return {Object.<string,string[]>}
	 */
	expandResources: function ( resources ) {
		// Figure out what the different css targets will be,
		// we need this to be shared between the recess task
		// (which will compile the less code) and the concat task
		// (which will prepend intro.css without it being stripped
		// like recess would).
		const targets = { default: [] };
		resources.forEach( function ( filepath ) {
			if ( typeof filepath !== 'object' ) {
				filepath = { default: filepath };
			}
			// Fetch copy of buffer before filepath/variant loop, otherwise
			// it can incorrectly include the default file in a non-default variant.
			const buffer = targets.default.slice();
			for ( const variant in filepath ) {
				if ( !targets[ variant ] ) {
					targets[ variant ] = buffer.slice();
				}
				targets[ variant ].push( filepath[ variant ] );
			}
		} );
		return targets;
	},

	/**
	 * Create a build list
	 *
	 * @param {Array} modules List of modules and their dependencies
	 * @param {string[]} targets List of target modules to load including any dependencies
	 * @return {Object} An object containing arrays of the scripts and styles
	 */
	makeBuildList: function ( modules, targets ) {
		/**
		 * Given a list of modules and targets, returns an object splitting the scripts
		 * and styles.
		 *
		 * @param {string[]} buildlist List of targets to work through
		 * @param {Object|null} filelist Object to extend
		 * @return {Object} Object of two arrays listing the file paths
		 */
		function expandBuildList( buildlist, filelist ) {
			filelist = filelist || {};
			filelist.scripts = filelist.scripts || [];
			filelist.styles = filelist.styles || [];

			for ( const build in buildlist ) {
				const moduleName = buildlist[ build ];

				for ( const script in modules[ moduleName ].scripts ) {
					if ( !modules[ moduleName ].scripts[ script ].debug ) {
						filelist.scripts.push( modules[ moduleName ].scripts[ script ] );
					}
				}

				for ( const style in modules[ moduleName ].styles ) {
					if ( !modules[ moduleName ].styles[ style ].debug ) {
						filelist.styles.push( modules[ moduleName ].styles[ style ] );
					}
				}
			}
			return filelist;
		}

		return expandBuildList( self.buildDependencyList( modules, targets ) );
	},

	/**
	 * Expands an array of arrays of file paths with dependencies into an ordered
	 * lit of dependencies stemming from one or more given top-level modules.
	 *
	 * @param {Array} modules List of modules and their dependencies
	 * @param {string[]} load List of targets to return and their dependencies
	 * @param {string[]|null} list Extant flat list of file paths to extend
	 * @return {string[]} Flat list of file paths
	 * @throws {Error} Dependency not found
	 */
	buildDependencyList: function ( modules, load, list ) {
		list = list || [];

		for ( let i = 0; i < load.length; i++ ) {
			const module = load[ i ];

			if ( !Object.prototype.hasOwnProperty.call( modules, module ) ) {
				throw new Error( 'Dependency ' + module + ' not found' );
			}

			// Add in any dependencies
			if ( modules[ module ].dependencies ) {
				self.buildDependencyList( modules, modules[ module ].dependencies, list );
			}

			// Append target load module to the end of the current list
			if ( !list.includes( module ) ) {
				list.push( module );
			}
		}

		return list;
	}
};
