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
namespace MediaWiki\Extension\AbuseFilter;

use ExtensionRegistry;
use LogPage;
use ManualLogEntry;
use MediaWiki\CheckUser\Hooks as CUHooks;
use MediaWiki\Extension\AbuseFilter\Variables\UnsetVariableException;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Message;

/**
 * Filters blocked domains
 *
 * @ingroup SpecialPage
 */
class BlockedDomainFilter {
	public const SERVICE_NAME = 'AbuseFilterBlockedDomainFilter';
	private VariablesManager $variablesManager;
	private BlockedDomainStorage $blockedDomainStorage;

	/**
	 * @param VariablesManager $variablesManager
	 * @param BlockedDomainStorage $blockedDomainStorage
	 */
	public function __construct(
		VariablesManager $variablesManager,
		BlockedDomainStorage $blockedDomainStorage
	) {
		$this->variablesManager = $variablesManager;
		$this->blockedDomainStorage = $blockedDomainStorage;
	}

	/**
	 * @param VariableHolder $vars variables by the action
	 * @param User $user User that tried to add the domain, used for logging
	 * @param Title $title Title of the page that was attempted on, used for logging
	 * @return Status|bool Status if it's a match and false if not
	 */
	public function filter( VariableHolder $vars, $user, $title ) {
		global $wgAbuseFilterEnableBlockedExternalDomain;
		if ( !$wgAbuseFilterEnableBlockedExternalDomain ) {
			return false;
		}
		try {
			$urls = $this->variablesManager->getVar( $vars, 'added_links', VariablesManager::GET_STRICT );
		} catch ( UnsetVariableException $_ ) {
			return false;
		}

		$addedDomains = [];
		foreach ( $urls->toArray() as $addedUrl ) {
			$parsedHost = parse_url( (string)$addedUrl->getData(), PHP_URL_HOST );
			if ( !is_string( $parsedHost ) ) {
				continue;
			}
			// Given that we block subdomains of blocked domains too
			// pretend that all of higher-level domains are added as well
			// so for foo.bar.com, you will have three domains to check:
			// foo.bar.com, bar.com, and com
			// This saves string search in the large list of blocked domains
			// making it much faster.
			$domainString = '';
			$domainPieces = array_reverse( explode( '.', strtolower( $parsedHost ) ) );
			foreach ( $domainPieces as $domainPiece ) {
				if ( !$domainString ) {
					$domainString = $domainPiece;
				} else {
					$domainString = $domainPiece . '.' . $domainString;
				}
				// It should be a map, benchmark at https://phabricator.wikimedia.org/P48956
				$addedDomains[$domainString] = true;
			}
		}
		if ( !$addedDomains ) {
			return false;
		}
		$blockedDomains = $this->blockedDomainStorage->loadComputed();
		$blockedDomainsAdded = array_intersect_key( $addedDomains, $blockedDomains );
		if ( !$blockedDomainsAdded ) {
			return false;
		}
		$blockedDomainsAdded = array_keys( $blockedDomainsAdded );
		$error = Message::newFromSpecifier( 'abusefilter-blocked-domains-attempted' );
		$error->params( Message::listParam( $blockedDomainsAdded ) );

		$status = Status::newFatal( $error, 'blockeddomain', 'blockeddomain' );
		$status->value['blockeddomain'] = [ 'disallow' ];
		$this->logFilterHit(
			$user,
			$title,
			implode( ' ', $blockedDomainsAdded )
		);
		return $status;
	}

	/**
	 * Logs the filter hit to Special:Log
	 *
	 * @param User $user
	 * @param Title $title
	 * @param string $blockedDomain The blocked domain the user attempted to add
	 */
	private function logFilterHit( User $user, $title, $blockedDomain ) {
		$logEntry = new ManualLogEntry( 'abusefilterblockeddomainhit', 'hit' );
		$logEntry->setPerformer( $user );
		$logEntry->setTarget( $title );
		$logEntry->setParameters( [ '4::blocked' => $blockedDomain ] );
		$logid = $logEntry->insert();
		$log = new LogPage( 'abusefilterblockeddomainhit' );
		if ( $log->isRestricted() ) {
			// Make sure checkusers can see this action if the log is restricted
			// (which is the default)
			if ( ExtensionRegistry::getInstance()->isLoaded( 'CheckUser' ) ) {
				$rc = $logEntry->getRecentChange( $logid );
				CUHooks::updateCheckUserData( $rc );
			}
		} else {
			// If the log is unrestricted, publish normally to RC,
			// which will also update checkuser
			$logEntry->publish( $logid, "rc" );
		}
	}
}
