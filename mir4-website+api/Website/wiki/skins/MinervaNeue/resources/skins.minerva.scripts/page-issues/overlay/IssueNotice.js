const
	mobile = require( 'mobile.startup' ),
	View = mobile.View;

/**
 * IssueNotice
 *
 * @class IssueNotice
 * @extends View
 *
 * @param {IssueSummary} props
 */
function IssueNotice( props ) {
	View.call( this, props );
}
OO.inheritClass( IssueNotice, View );
IssueNotice.prototype.tagName = 'li';
IssueNotice.prototype.template = mw.template.get( 'skins.minerva.scripts', 'IssueNotice.mustache' );
IssueNotice.prototype.postRender = function () {
	View.prototype.postRender.apply( this, arguments );
	this.$el.find( '.issue-notice' ).prepend( this.options.issue.iconElement );
};
module.exports = IssueNotice;
