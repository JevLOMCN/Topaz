// In VE-MW, track events using MediaWiki's system instead of VisualEditor's
ve.track = mw.track;
ve.trackSubscribe = mw.trackSubscribe;

ve.trackSubscribe( 'activity.', function ( topic, data ) {
	mw.track( 'visualEditorFeatureUse', ve.extendObject( data, {
		feature: topic.split( '.' )[ 1 ]
	} ) );
} );
