<?php
/**
 * SpamBlacklist extension API
 *
 * Copyright © 2013 Wikimedia Foundation
 * Based on code by Ian Baker, Victor Vasiliev, Bryan Tong Minh, Roan Kattouw,
 * Alex Z., and Jackmcbarn
 *
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
 */

namespace MediaWiki\Extension\SpamBlacklist;

use ApiBase;
use ApiResult;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Query module check a URL against the blacklist
 *
 * @ingroup API
 * @ingroup Extensions
 */
class ApiSpamBlacklist extends ApiBase {
	public function execute() {
		$params = $this->extractRequestParams();
		$matches = BaseBlacklist::getSpamBlacklist()->filter(
			$params['url'],
			null,
			$this->getUser(),
			true
		);
		$res = $this->getResult();

		if ( $matches !== false ) {
			// this url is blacklisted.
			$res->addValue( 'spamblacklist', 'result', 'blacklisted' );
			ApiResult::setIndexedTagName( $matches, 'match' );
			$res->addValue( 'spamblacklist', 'matches', $matches );
		} else {
			// not blacklisted
			$res->addValue( 'spamblacklist', 'result', 'ok' );
		}
	}

	public function getAllowedParams() {
		return [
			'url' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_ISMULTI => true,
			]
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=spamblacklist&url=http://www.example.com/|http://www.example.org/'
				=> 'apihelp-spamblacklist-example-1',
		];
	}

	public function getHelpUrls() {
		return [ 'https://www.mediawiki.org/wiki/Extension:SpamBlacklist/API' ];
	}
}
