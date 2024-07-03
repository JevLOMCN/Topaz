/*
 * Bucketing wrapper for creating AB-tests.
 *
 * Given a test name, sampling rate, and session ID, provides a class that buckets a user into
 * a predefined bucket ("unsampled", "control", or "treatment") and starts an AB-test.
 */
( function ( mwExperiments ) {
	const bucket = {
		UNSAMPLED: 'unsampled', // Old treatment: not sampled and not instrumented.
		CONTROL: 'control', // Old treatment: sampled and instrumented.
		TREATMENT: 'treatment' // New treatment: sampled and instrumented.
	};

	/**
	 * Buckets users based on params and exposes an `isSampled` and `getBucket` method.
	 *
	 * @param {Object} config Configuration object for AB test.
	 * @param {string} config.testName
	 * @param {number} config.samplingRate Sampling rate for the AB-test.
	 * @param {number} config.sessionId Session ID for user bucketing.
	 * @constructor
	 */
	function AB( config ) {
		const testName = config.testName;
		const samplingRate = config.samplingRate;
		const sessionId = config.sessionId;
		const test = {
			name: testName,
			enabled: !!samplingRate,
			buckets: {
				unsampled: 1 - samplingRate,
				control: samplingRate / 2,
				treatment: samplingRate / 2
			}
		};

		/**
		 * Gets the users AB-test bucket.
		 *
		 * A boolean instead of an enum is usually a code smell. However, the nature of A/B testing
		 * is to compare an A group's performance to a B group's so a boolean seems natural, even
		 * in the long term, and preferable to showing bucketing encoding ("unsampled", "control",
		 * "treatment") to callers which is necessary if getBucket(). The downside is that now two
		 * functions exist where one would suffice.
		 *
		 * @private
		 * @return {string} AB-test bucket, `bucket.UNSAMPLED` by default, `bucket.CONTROL` or
		 *                  `bucket.TREATMENT` buckets otherwise.
		 */
		function getBucket() {
			return mwExperiments.getBucket( test, sessionId );
		}

		function isControl() {
			return getBucket() === bucket.CONTROL;
		}

		function isTreatment() {
			return getBucket() === bucket.TREATMENT;
		}

		/**
		 * Checks whether or not a user is in the AB-test,
		 *
		 * @private
		 * @return {boolean}
		 */
		function isSampled() {
			return getBucket() !== bucket.UNSAMPLED; // I.e., `isControl() || isTreatment()`
		}

		return {
			isControl: isControl,
			isTreatment: isTreatment,
			isSampled: isSampled
		};
	}

	module.exports = AB;

}( mw.experiments ) );
