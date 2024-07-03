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

namespace MediaWiki\Extension\OATHAuth\Tests\Integration\HTMLForm;

use Base32\Base32;
use jakobo\HOTP\HOTP;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\OATHAuth\HTMLForm\TOTPDisableForm;
use MediaWiki\Extension\OATHAuth\Key\TOTPKey;
use MediaWiki\Extension\OATHAuth\OATHAuthServices;
use MediaWikiIntegrationTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @author Taavi Väänänen <hi@taavi.wtf>
 * @group Database
 * @coversDefaultClass \MediaWiki\Extension\OATHAuth\HTMLForm\TOTPDisableForm
 */
class TOTPDisableFormTest extends MediaWikiIntegrationTestCase {
	/**
	 * @return array
	 * @phan-return array{0:TOTPDisableForm,1:TOTPKey,2:MediaWiki\Extension\OATHAuth\OATHUser}
	 */
	private function setupFormAndKey(): array {
		$user = $this->getTestUser()->getUser();
		$repository = OATHAuthServices::getInstance( $this->getServiceContainer() )
			->getUserRepository();
		$oathUser = $repository->findByUser( $user );
		$module = OATHAuthServices::getInstance( $this->getServiceContainer() )
			->getModuleRegistry()
			->getModuleByKey( 'totp' );

		$key = $repository->createKey(
			$oathUser,
			$module,
			TOTPKey::newFromRandom()->jsonSerialize(),
			'127.0.0.1'
		);

		$form = new TOTPDisableForm(
			$oathUser,
			$repository,
			$module,
			RequestContext::getMain(),
		);

		return [ $form, $key, $oathUser ];
	}

	/**
	 * @covers ::onSubmit
	 */
	public function testSubmitInvalidCode(): void {
		[ $form ] = $this->setupFormAndKey();
		$this->assertEquals(
			[ 'oathauth-failedtovalidateoath' ],
			$form->onSubmit( [ 'token' => 'wrong' ] ),
		);
	}

	/**
	 * @covers ::onSubmit
	 */
	public function testSubmitCorrectToken(): void {
		[ $form, $key, $oathUser ] = $this->setupFormAndKey();

		$secret = TestingAccessWrapper::newFromObject( $key )->secret;
		$correctToken = HOTP::generateByTime(
			Base32::decode( $secret['secret'] ),
			$secret['period'],
		)->toHOTP( 6 );

		$this->assertTrue( $form->onSubmit( [ 'token' => $correctToken ] ) );
		$this->assertEquals( [], $oathUser->getKeys() );
	}

	/**
	 * @covers ::onSubmit
	 */
	public function testSubmitScratchToken(): void {
		[ $form, $key, $oathUser ] = $this->setupFormAndKey();

		$this->assertTrue( $form->onSubmit( [ 'token' => $key->getScratchTokens()[0] ] ) );
		$this->assertEquals( [], $oathUser->getKeys() );
	}
}
