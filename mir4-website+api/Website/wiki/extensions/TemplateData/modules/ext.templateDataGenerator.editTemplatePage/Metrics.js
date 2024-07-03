function logEvent( eventName ) {
	/* eslint-disable camelcase */
	var event = {
		action: eventName,
		page_id: mw.config.get( 'wgArticleId' ),
		page_title: mw.config.get( 'wgTitle' ),
		page_namespace: mw.config.get( 'wgNamespaceNumber' ),
		rev_id: mw.config.get( 'wgCurRevisionId' ),
		user_edit_count: mw.config.get( 'wgUserEditCount', 0 ),
		user_id: mw.user.isNamed() ? mw.user.getId() : 0
	};

	var editCountBucket = mw.config.get( 'wgUserEditCountBucket' );
	if ( editCountBucket !== null ) {
		event.user_edit_count_bucket = editCountBucket;
	}
	/* eslint-enable camelcase */

	mw.track( 'event.TemplateDataEditor', event );
}

module.exports = {
	logEvent: logEvent
};
