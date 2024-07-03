let isTracking = false;

const LOGGING_SCHEMA = 'event.ReferencePreviewsPopups';

/**
 * Run once the preview is initialized.
 */
function initReferencePreviewsInstrumentation() {
	if ( mw.config.get( 'wgPopupsReferencePreviews' ) &&
		navigator.sendBeacon &&
		mw.config.get( 'wgIsArticle' ) &&
		!isTracking
	) {
		isTracking = true;
		mw.track( LOGGING_SCHEMA, { action: 'pageview' } );
	}
}

function isTrackingEnabled() {
	return isTracking;
}

module.exports = {
	LOGGING_SCHEMA,
	initReferencePreviewsInstrumentation,
	isTrackingEnabled
};
