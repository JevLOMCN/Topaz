( function () {

	const AB = require( '../../../resources/skins.minerva.scripts/AB.js' );
	const defaultConfig = {
		testName: 'WME.MinervaABTest',
		samplingRate: 0.5,
		sessionId: mw.user.generateRandomSessionId()
	};

	QUnit.module( 'Minerva AB-test' );

	QUnit.test( 'Bucketing test', function ( assert ) {
		const userBuckets = {
			unsampled: 0,
			control: 0,
			treatment: 0
		};
		const maxUsers = 1000;

		for ( let i = 0; i < maxUsers; i++ ) {
			const config = Object.assign( {}, defaultConfig, {
				sessionId: mw.user.generateRandomSessionId()
			} );
			const bucketingTest = new AB( config );
			if ( bucketingTest.isControl() ) {
				++userBuckets.control;
			} else if ( bucketingTest.isTreatment() ) {
				++userBuckets.treatment;
			} else if ( !bucketingTest.isSampled() ) {
				++userBuckets.unsampled;
			} else {
				throw new Error( 'Unknown bucket!' );
			}
		}

		assert.strictEqual(
			( userBuckets.unsampled / maxUsers > 0.3 ) &&
			( userBuckets.unsampled / maxUsers < 0.7 ),
			true, 'test unsampled group is about 50% (' + userBuckets.unsampled / 10 + '%)' );

		assert.strictEqual(
			( userBuckets.control / maxUsers > 0.1 ) &&
			( userBuckets.control / maxUsers < 0.4 ),
			true, 'test control group is about 25% (' + userBuckets.control / 10 + '%)' );

		assert.strictEqual(
			( userBuckets.treatment / maxUsers > 0.1 ) &&
			( userBuckets.treatment / maxUsers < 0.4 ),
			true, 'test new treatment group is about 25% (' + userBuckets.treatment / 10 + '%)' );
	} );

}() );
