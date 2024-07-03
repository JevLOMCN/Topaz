/*!
 * VisualEditor DataModel TestRebaseClient class
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Rebase client used for testing
 *
 * @class
 * @extends ve.dm.RebaseServer
 *
 * @constructor
 */
ve.dm.TestRebaseServer = function VeDmRebaseServer() {
	ve.dm.RebaseServer.apply( this );

	this.incoming = [];
};

OO.inheritClass( ve.dm.TestRebaseServer, ve.dm.RebaseServer );

ve.dm.TestRebaseServer.static.fakeDocName = 'foo';

ve.dm.TestRebaseServer.prototype.getHistorySummary = function historySummary() {
	return ve.dm.TestRebaseClient.static.historySummary(
		this.getDocState( this.constructor.static.fakeDocName ).history
	);
};
