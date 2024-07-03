( function () {
	mw.thanks = {
		// Keep track of which revisions and comments the user has already thanked for
		thanked: {
			maxHistory: 100,
			cookieName: 'thanks-thanked',

			/**
			 * Load thanked IDs from cookies
			 *
			 * @param {string} [cookieName] Cookie name to use, defaults to this.cookieName
			 * @return {string[]} Thanks IDs
			 */
			load: function ( cookieName ) {
				var cookie = mw.cookie.get( cookieName || this.cookieName );
				if ( cookie === null ) {
					return [];
				}
				return unescape( cookie ).split( ',' );
			},

			/**
			 * Record as ID as having been thanked
			 *
			 * @param {string} id Thanked ID
			 * @param {string} [cookieName] Cookie name to use, defaults to this.cookieName
			 */
			push: function ( id, cookieName ) {
				var saved = this.load();
				saved.push( id );
				if ( saved.length > this.maxHistory ) { // prevent forever growing
					saved = saved.slice( saved.length - this.maxHistory );
				}
				mw.cookie.set( cookieName || this.cookieName, escape( saved.join( ',' ) ) );
			},

			/**
			 * Check if an ID has already been thanked, according to the cookie
			 *
			 * @param {string} id Thanks ID
			 * @param {string} [cookieName] Cookie name to use, defaults to this.cookieName
			 * @return {boolean} ID has been thanked
			 */
			contains: function ( id, cookieName ) {
				return this.load( cookieName ).indexOf( id ) !== -1;
			}
		},

		/**
		 * Retrieve user gender
		 *
		 * @param {string} username Requested username
		 * @return {jQuery.Promise} A promise that resolves with the gender string, 'female', 'male', or 'unknown'
		 */
		getUserGender: function ( username ) {
			return new mw.Api().get( {
				action: 'query',
				list: 'users',
				ususers: username,
				usprop: 'gender'
			} )
				.then(
					function ( result ) {
						return (
							result.query.users[ 0 ] &&
							result.query.users[ 0 ].gender
						) || 'unknown';
					},
					function () {
						return 'unknown';
					}
				);
		}
	};

}() );
