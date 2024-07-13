<?php

namespace MediaWiki\Extension\Nuke\Tests;

use MediaWiki\Extension\Nuke\SpecialNuke;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Request\FauxRequest;
use SpecialPageTestBase;

/**
 * @group Database
 * @covers \MediaWiki\Extension\Nuke\SpecialNuke
 */
class SpecialNukeTest extends SpecialPageTestBase {

	protected function newSpecialPage(): SpecialNuke {
		$services = $this->getServiceContainer();

		return new SpecialNuke(
			$services->getJobQueueGroup(),
			$services->getDBLoadBalancerFactory(),
			$services->getPermissionManager(),
			$services->getRepoGroup(),
			$services->getUserFactory(),
			$services->getUserNamePrefixSearch(),
			$services->getUserNameUtils()
		);
	}

	public function testExecutePattern() {
		// Test that matching wildcards works, and that escaping wildcards works as documented
		// at https://www.mediawiki.org/wiki/Help:Extension:Nuke
		$this->editPage( '%PositiveNukeTest123', 'test' );
		$this->editPage( 'NegativeNukeTest123', 'test' );

		$user = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => 'submit',
			'pattern' => '\\%PositiveNukeTest%',
			'wpFormIdentifier' => 'massdelete',
			'wpEditToken' => $user->getEditToken(),
		], true );
		$performer = new UltimateAuthority( $user );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString( 'PositiveNukeTest123', $html );
		$this->assertStringNotContainsString( 'NegativeNukeTest123', $html );
	}

}
