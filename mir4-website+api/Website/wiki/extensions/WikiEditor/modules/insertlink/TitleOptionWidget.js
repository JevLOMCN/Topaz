/**
 * A custom TitleOptionWidget that knows about external links.
 *
 * @class
 * @extends mw.widgets.TitleOptionWidget
 * @constructor
 * @param {Object} config Configuration options.
 * @cfg {boolean} [external] Page title is an external link.
 */
function InsertLinkTitleOptionWidget( config ) {
	this.external = config.external || false;
	if ( this.external ) {
		config.icon = 'linkExternal';
		config.description = mw.msg( 'wikieditor-toolbar-tool-link-int-target-status-external' );
		// Lowercase the first character; it was uppercased by the API.
		config.url = config.data.slice( 0, 1 ).toLowerCase() + config.data.slice( 1 );
		config.data = config.url;
		// Prepend http:// if there is no protocol (i.e. if it starts with "www.").
		// @TODO This is repeated when the link is inserted (in jquery.wikiEditor.dialogs.config.js).
		if ( !/^[a-z]+:\/\/./.test( config.url ) ) {
			config.url = 'http://' + config.url;
		}
		config.missing = false;
	}
	this.disambiguation = config.disambiguation || false;
	this.missing = config.missing || false;
	InsertLinkTitleOptionWidget.super.call( this, config );
}

OO.inheritClass( InsertLinkTitleOptionWidget, mw.widgets.TitleOptionWidget );

/**
 * @return {boolean}
 */
InsertLinkTitleOptionWidget.prototype.isExternal = function () {
	return this.external;
};

/**
 * @return {boolean}
 */
InsertLinkTitleOptionWidget.prototype.isMissing = function () {
	return this.missing;
};

/**
 * @return {boolean}
 */
InsertLinkTitleOptionWidget.prototype.isDisambiguation = function () {
	return this.disambiguation;
};

module.exports = InsertLinkTitleOptionWidget;
