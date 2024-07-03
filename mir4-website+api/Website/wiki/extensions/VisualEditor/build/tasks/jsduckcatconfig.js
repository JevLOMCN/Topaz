/*!
 * Merge jsduck configuration files with a downstream one
 */

'use strict';

module.exports = function ( grunt ) {
	const _ = grunt.util._;

	grunt.registerMultiTask( 'jsduckcatconfig', function () {
		const targetFile = this.data.target,
			from = this.data.from,
			output = [];

		from.forEach( function ( src ) {
			if ( typeof src === 'string' ) {
				src = {
					file: src
				};
			}

			const srcCategories = grunt.file.readJSON( src.file );

			if ( !src.include && !src.aggregate ) {
				// Default to a straight inclusion
				output.push.apply( output, srcCategories );
				return;
			}

			if ( src.aggregate ) {
				_.forIn( src.aggregate, function ( targetCat, targetCatName ) {
					const targetGroups = [];
					// For each of the target category groups...
					targetCat.forEach( function ( targetGroupName ) {
						// ... find the category in the aggregate source
						srcCategories.forEach( function ( aggrCat ) {
							if ( aggrCat.name === targetGroupName ) {
								const targetGroup = {
									name: targetGroupName,
									classes: []
								};
								aggrCat.groups.forEach( function ( group ) {
									targetGroup.classes = targetGroup.classes.concat( group.classes );
								} );
								targetGroups.push( targetGroup );
							}
						} );
					} );
					output.push( {
						name: targetCatName,
						groups: targetGroups
					} );
				} );

			}

			if ( src.include ) {
				src.include.forEach( function ( targetCatName ) {
					srcCategories.forEach( function ( aggrCat ) {
						if ( aggrCat.name === targetCatName ) {
							output.push( aggrCat );
						}
					} );
				} );
			}

		} );

		grunt.file.write( targetFile, JSON.stringify( output, null, '\t' ) + '\n' );
		grunt.log.ok( 'File "' + targetFile + '" written.' );
	} );
};
