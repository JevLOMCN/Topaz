/*!
 * Change the in-memory package version to contain the git HEAD
 */

'use strict';

module.exports = function ( grunt ) {

	grunt.registerTask( 'git-build', function () {
		const done = this.async();
		require( 'child_process' ).exec( 'git rev-parse HEAD', function ( err, stout, stderr ) {
			if ( !stout || err || stderr ) {
				grunt.log.error( err || stderr );
				done( false );
				return;
			}
			grunt.config.set( 'pkg.version', grunt.config( 'pkg.version' ) + '-pre (' + stout.slice( 0, 10 ) + ')' );
			grunt.verbose.writeln( 'Added git HEAD to pkg.version' );
			done();
		} );
	} );

};
