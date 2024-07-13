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

use MediaWiki\Skins\Vector\FeatureManagement\Requirements\LoggedInRequirement;
use MediaWiki\User\UserIdentity;
use PHPUnit\Framework\TestCase;

/**
 * @group Vector
 * @group FeatureManagement
 * @coversDefaultClass \MediaWiki\Skins\Vector\FeatureManagement\Requirements\LoggedInRequirement
 */
class LoggedInRequirementTest extends TestCase {

	public function userProvider(): array {
		return [
			'Logged-in user' => [
				// User is logged in
				true,
				// Expected isMet() result
				true
			],
			'Anonymous user' => [
				// User is not logged in
				false,
				// Expected isMet() result
				false
			]
		];
	}

	/**
	 * @covers ::getName
	 */
	public function testGetName() {
		// Mock the User object
		$userMock = $this->createMock( UserIdentity::class );
		$requirementName = 'mockedUserLoggedInRequirement';

		// Instantiate the LoggedInRequirement with the mock User object and the requirement name
		$loggedInRequirement = new LoggedInRequirement( $userMock, $requirementName );

		// Assert that the getName method returns the correct requirement name
		$this->assertEquals( $requirementName, $loggedInRequirement->getName(), 'Failed testing getName method.' );
	}

	/**
	 * @dataProvider userProvider
	 * @covers ::isMet
	 * @param bool $isUserLoggedIn
	 * @param bool $expectedResult
	 */
	public function testIsMet( bool $isUserLoggedIn, bool $expectedResult ) {
		// Mock the User object
		$userMock = $this->createMock( UserIdentity::class );
		// Setup the isRegistered method to return the login state
		$userMock->method( 'isRegistered' )->willReturn( $isUserLoggedIn );

		// Instantiate the LoggedInRequirement with the mock User object and a dummy name
		$loggedInRequirement = new LoggedInRequirement( $userMock, 'mockedUserLoggedInRequirement' );

		// Assert that the isMet method returns the expected boolean value
		$this->assertEquals( $expectedResult, $loggedInRequirement->isMet(), 'Failed testing isMet method.' );
	}

}
