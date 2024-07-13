/**
 * More information about a comment obtained from various APIs, rather than parsed from the page.
 *
 * @class CommentDetails
 * @constructor
 * @param {string} pageName Page name the reply is being saved to
 * @param {number} oldId Revision ID of page at time of editing
 * @param {Object.<string,string>} notices Edit notices for the page where the reply is being saved.
 *     Keys are message names; values are HTML to display.
 * @param {boolean} wouldAutoCreate Whether saving the comment would automatically create a
 *     temporary account if the user is logged out
 * @param {string} preloadContent Preload content, may be wikitext or HTML depending on `preloadContentMode`
 * @param {string} preloadContentMode 'source' or 'visual'
 */
function CommentDetails( pageName, oldId, notices, wouldAutoCreate, preloadContent, preloadContentMode ) {
	this.pageName = pageName;
	this.oldId = oldId;
	this.notices = notices;
	this.wouldAutoCreate = wouldAutoCreate;
	this.preloadContent = preloadContent;
	this.preloadContentMode = preloadContentMode;
}

OO.initClass( CommentDetails );

module.exports = CommentDetails;
