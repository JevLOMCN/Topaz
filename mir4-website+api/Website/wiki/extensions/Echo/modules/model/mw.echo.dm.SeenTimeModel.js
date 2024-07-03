( function () {
	/**
	 * SeenTime model for Echo notifications
	 *
	 * @param {Object} [config] Configuration
	 * @cfg {string|string[]} [types='alert','message'] The types of notifications
	 *  that this model handles
	 */
	mw.echo.dm.SeenTimeModel = function MwEchoSeenTimeModel( config ) {
		config = config || {};

		// Mixin constructor
		OO.EventEmitter.call( this );

		this.types = [ 'alert', 'message' ];
		if ( config.types ) {
			this.types = Array.isArray( config.types ) ? config.types : [ config.types ];
		}

		this.seenTime = mw.config.get( 'wgEchoSeenTime' ) || {};
	};

	/* Initialization */

	OO.initClass( mw.echo.dm.SeenTimeModel );
	OO.mixinClass( mw.echo.dm.SeenTimeModel, OO.EventEmitter );

	/* Events */

	/**
	 * @event update
	 * @param {string} time Seen time, as a full UTC ISO 8601 timestamp.
	 *
	 * Seen time has been updated for the given source
	 */

	/* Methods */

	/**
	 * Get the global seenTime value
	 *
	 * @return {string} Seen time, as a full UTC ISO 8601 timestamp.
	 */
	mw.echo.dm.SeenTimeModel.prototype.getSeenTime = function () {
		return this.seenTime[ this.getTypes()[ 0 ] ] || 0;
	};

	/**
	 * Set the seen time value for the source
	 *
	 * @private
	 * @param {string} time Seen time, as a full UTC ISO 8601 timestamp.
	 * @fires update
	 */
	mw.echo.dm.SeenTimeModel.prototype.setSeenTime = function ( time ) {
		var model = this,
			hasChanged = false;

		this.getTypes().forEach( function ( type ) {
			if ( model.seenTime[ type ] !== time ) {
				model.seenTime[ type ] = time;
				hasChanged = true;
			}
		} );

		if ( hasChanged ) {
			this.emit( 'update', time );
		}
	};

	/**
	 * Get the types associated with this model
	 *
	 * @private
	 * @return {string[]} Types for this model; an array of 'alert', 'message' or both.
	 */
	mw.echo.dm.SeenTimeModel.prototype.getTypes = function () {
		return this.types;
	};

}() );
