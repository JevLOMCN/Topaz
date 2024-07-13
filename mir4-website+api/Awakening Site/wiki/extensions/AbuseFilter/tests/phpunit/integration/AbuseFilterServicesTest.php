<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration;

use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Tests\ExtensionServicesTestBase;

/**
 * @group Test
 * @group AbuseFilter
 * @covers \MediaWiki\Extension\AbuseFilter\AbuseFilterServices
 */
class AbuseFilterServicesTest extends ExtensionServicesTestBase {

	/** @inheritDoc */
	protected string $className = AbuseFilterServices::class;

	/** @inheritDoc */
	protected string $serviceNamePrefix = 'AbuseFilter';

	/** @inheritDoc */
	protected array $serviceNamesWithoutMethods = [
		'AbuseFilterRunnerFactory',
	];

}
