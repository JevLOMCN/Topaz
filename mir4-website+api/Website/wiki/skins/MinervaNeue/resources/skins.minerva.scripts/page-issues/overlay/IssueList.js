const
	mobile = require( 'mobile.startup' ),
	View = mobile.View,
	IssueNotice = require( './IssueNotice.js' );

/**
 * IssueList
 *
 * @class IssueList
 * @extends View
 *
 * @param {IssueSummary} issues
 */
function IssueList( issues ) {
	this.issues = issues;
	View.call( this, { className: 'cleanup' } );
}
OO.inheritClass( IssueList, View );
IssueList.prototype.tagName = 'ul';
IssueList.prototype.postRender = function () {
	View.prototype.postRender.apply( this, arguments );
	this.append(
		( this.issues || [] ).map( function ( issue ) {
			return new IssueNotice( issue ).$el;
		} )
	);
};
module.exports = IssueList;
