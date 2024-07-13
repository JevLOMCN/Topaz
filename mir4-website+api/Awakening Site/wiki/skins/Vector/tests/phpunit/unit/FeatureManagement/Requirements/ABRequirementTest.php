<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 1.42
 */

namespace MediaWiki\Skins\Vector\Tests\Unit\FeatureManagement\Requirements;

use MediaWiki\Config\HashConfig;
use MediaWiki\Skins\Vector\FeatureManagement\Requirements\ABRequirement;
use MediaWiki\User\UserIdentity;
use PHPUnit\Framework\TestCase;

/**
 * @group Vector
 * @group FeatureManagement
 * @coversDefaultClass \MediaWiki\Skins\Vector\FeatureManagement\Requirements\ABRequirement
 */
class ABRequirementTest extends TestCase {

	private function createUserMock( $userId ) {
		$userMock = $this->createMock( UserIdentity::class );
		$userMock->method( 'getId' )->willReturn( $userId );
		return $userMock;
	}

	/**
	 * Provides different scenarios for AB test enrollment.
	 */
	public function enrollmentProvider(): array {
		return [
			'Experiment Disabled' => [
				[ 'enabled' => false, 'name' => 'mockExperiment' ],
				// User ID
				1,
				// Expected result
				true
			],
			'Experiment Enabled, Wrong Name' => [
				[ 'enabled' => true, 'name' => 'mockWrongExperiment' ],
				1,
				true
			],
			'Experiment Enabled, Correct Name, User in Control Group' => [
				[ 'enabled' => true, 'name' => 'mockExperiment' ],
				2,
				false
			],
			'Experiment Enabled, Correct Name, User in Test Group' => [
				[ 'enabled' => true, 'name' => 'mockExperiment' ],
				1,
				true
			],
		];
	}

	/**
	 * @dataProvider enrollmentProvider
	 * @covers ::isMet
	 */
	public function testABRequirement( $experimentConfig, $userId, $expectedResult ) {
		$config = new HashConfig( [
			'VectorWebABTestEnrollment' => $experimentConfig
		] );

		$user = $this->createUserMock( $userId );

		$abRequirement = new ABRequirement(
			$config,
			$user,
			'mockExperiment',
		);

		$this->assertEquals( $expectedResult, $abRequirement->isMet() );
	}

	/**
	 * @covers ::getName
	 */
	public function testGetName() {
		$config = new HashConfig();
		$user = $this->createUserMock( 0 );
		$experimentName = 'mockExperiment';
		$name = 'ABTestRequirement';

		$abRequirement = new ABRequirement(
			$config,
			$user,
			$experimentName,
			$name
		);

		$this->assertEquals( $name, $abRequirement->getName() );
	}
}
