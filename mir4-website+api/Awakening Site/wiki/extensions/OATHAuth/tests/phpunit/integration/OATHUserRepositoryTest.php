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
 */

namespace MediaWiki\Extension\OATHAuth\Tests\Integration;

use EmptyBagOStuff;
use MediaWiki\Extension\OATHAuth\Key\TOTPKey;
use MediaWiki\Extension\OATHAuth\OATHAuthServices;
use MediaWiki\Extension\OATHAuth\OATHUserRepository;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\CentralId\CentralIdLookupFactory;
use MediaWikiIntegrationTestCase;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\TestingAccessWrapper;

/**
 * @author Taavi Väänänen <hi@taavi.wtf>
 * @group Database
 * @coversDefaultClass \MediaWiki\Extension\OATHAuth\OATHUserRepository
 */
class OATHUserRepositoryTest extends MediaWikiIntegrationTestCase {
	/**
	 * @covers ::findByUser
	 * @covers ::createKey
	 * @covers ::updateKey
	 * @covers ::remove
	 */
	public function testLookupCreateRemoveKey(): void {
		$user = $this->getTestUser()->getUser();

		$dbProvider = $this->createMock( IConnectionProvider::class );
		$dbProvider->method( 'getPrimaryDatabase' )->with( 'virtual-oathauth' )->willReturn( $this->db );
		$dbProvider->method( 'getReplicaDatabase' )->with( 'virtual-oathauth' )->willReturn( $this->db );

		$moduleRegistry = OATHAuthServices::getInstance( $this->getServiceContainer() )->getModuleRegistry();
		$module = $moduleRegistry->getModuleByKey( 'totp' );

		$lookup = $this->createMock( CentralIdLookup::class );
		$lookup->method( 'centralIdFromLocalUser' )
			->with( $user )
			->willReturn( 12345 );
		$lookupFactory = $this->createMock( CentralIdLookupFactory::class );
		$lookupFactory->method( 'getLookup' )->willReturn( $lookup );

		$logger = $this->createMock( LoggerInterface::class );

		$repository = new OATHUserRepository(
			$dbProvider,
			new EmptyBagOStuff(),
			$moduleRegistry,
			$lookupFactory,
			$logger
		);

		$oathUser = $repository->findByUser( $user );
		$this->assertEquals( 12345, $oathUser->getCentralId() );
		$this->assertEquals( [], $oathUser->getKeys() );
		$this->assertNull( $oathUser->getModule() );

		/** @var TOTPKey $key */
		$key = $repository->createKey(
			$oathUser,
			$module,
			TOTPKey::newFromRandom()->jsonSerialize(),
			'127.0.0.1'
		);

		$this->assertNotEmpty(
			$this->db->newSelectQueryBuilder()
				->select( '1' )
				->from( 'oathauth_devices' )
				->where( [ 'oad_user' => $oathUser->getCentralId() ] )
		);

		$this->assertArrayEquals( [ $key ], $oathUser->getKeys() );
		$this->assertEquals( $module, $oathUser->getModule() );

		// Test looking it up again from the database
		$this->assertArrayEquals( [ $key ], $repository->findByUser( $user )->getKeys() );

		// Use a scratch code, which causes the key to be updated.
		TestingAccessWrapper::newFromObject( $key )->recoveryCodes = [ 'new scratch tokens' ];
		$repository->updateKey( $oathUser, $key );

		$this->assertEquals(
			[ 'new scratch tokens' ],
			$repository->findByUser( $user )->getKeys()[0]->getScratchTokens()
		);

		$repository->removeKey(
			$oathUser,
			$key,
			'127.0.0.1',
			true
		);

		$this->assertEquals( [], $oathUser->getKeys() );
		$this->assertNull( $oathUser->getModule() );
		$this->assertEquals( [], $repository->findByUser( $user )->getKeys() );
	}
}
