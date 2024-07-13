/**
 * A list of all custom Minerva pageObjects.
 * To simplify imports in world.js.
 */

'use strict';

module.exports = {
	ArticlePage: require( './article_page' ),
	ArticlePageWithEditorOverlay: require( './article_page_with_editor_overlay' ),
	SpecialHistoryPage: require( './special_history_page' ),
	SpecialMobileDiffPage: require( './special_mobilediff_page' )
};
