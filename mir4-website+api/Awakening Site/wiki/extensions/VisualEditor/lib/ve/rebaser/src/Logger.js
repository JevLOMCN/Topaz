'use strict';

function Logger( logger ) {
	this.logger = logger;
	this.startTimestamp = null;
}

Logger.prototype.getRelativeTimestamp = function () {
	return Date.now() - this.startTimestamp;
};

/**
 * @param {Object} event The event to log
 * @param {string} [level] Log level
 */
Logger.prototype.logEvent = function ( event, level ) {
	this.logger.log( level || 'trace', event );
};

/**
 * Log a server event
 *
 * @param {Object} event The server event to log
 * @param {string} [level] Log level
 */
Logger.prototype.logServerEvent = function ( event, level ) {
	this.logEvent( Object.assign( { clientId: 'server' }, event ), level );
};

module.exports = Logger;
