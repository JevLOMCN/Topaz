<?php
/**
 * Body of LoginNotify extension
 *
 * @file
 * @ingroup Extensions
 */

namespace LoginNotify;

use BagOStuff;
use ExtensionRegistry;
use IBufferingStatsdDataFactory;
use JobQueueGroup;
use JobSpecification;
use LogicException;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\CentralAuth\User\CentralAuthUser;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\WebRequest;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\User;
use MediaWiki\WikiMap\WikiMap;
use MWCryptRand;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;
use Wikimedia\Assert\Assert;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\IMaintainableDatabase;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\Rdbms\LikeValue;

/**
 * Handle sending notifications on login from unknown source.
 *
 * @author Brian Wolff
 */
class LoginNotify implements LoggerAwareInterface {

	public const CONSTRUCTOR_OPTIONS = [
		'LoginNotifyAttemptsKnownIP',
		'LoginNotifyAttemptsNewIP',
		'LoginNotifyCacheLoginIPExpiry',
		'LoginNotifyCheckKnownIPs',
		'LoginNotifyCookieDomain',
		'LoginNotifyCookieExpire',
		'LoginNotifyEnableOnSuccess',
		'LoginNotifyExpiryKnownIP',
		'LoginNotifyExpiryNewIP',
		'LoginNotifyMaxCookieRecords',
		'LoginNotifySecretKey',
		'LoginNotifySeenBucketSize',
		'LoginNotifySeenExpiry',
		'LoginNotifyUseCheckUser',
		'LoginNotifyUseSeenTable',
		'LoginNotifyUseCentralId',
		'SecretKey',
		'UpdateRowsPerQuery'
	];

	private const COOKIE_NAME = 'loginnotify_prevlogins';

	// The following 3 constants specify outcomes of user search
	/** User's system is known to us */
	public const USER_KNOWN = 'known';
	/** User's system is new for us, based on our data */
	public const USER_NOT_KNOWN = 'not known';
	/** We don't have data to confirm or deny this is a known system */
	public const USER_NO_INFO = 'no info';

	/** @var BagOStuff */
	private $cache;
	/** @var ServiceOptions */
	private $config;
	/** @var LoggerInterface Usually instance of LoginNotify log */
	private $log;
	/** @var string Salt for cookie hash. DON'T USE DIRECTLY, use getSalt() */
	private $salt;
	/** @var string */
	private $secret;
	/** @var IBufferingStatsdDataFactory */
	private $stats;
	/** @var LBFactory */
	private $lbFactory;
	/** @var JobQueueGroup */
	private $jobQueueGroup;
	/** @var CentralIdLookup */
	private $centralIdLookup;
	/** @var AuthManager */
	private $authManager;
	/** @var int|null */
	private $fakeTime;

	public static function getInstance(): self {
		return MediaWikiServices::getInstance()->get( 'LoginNotify.LoginNotify' );
	}

	/**
	 * @param ServiceOptions $options
	 * @param BagOStuff $cache
	 * @param LoggerInterface $log
	 * @param IBufferingStatsdDataFactory $stats
	 * @param LBFactory $lbFactory
	 * @param JobQueueGroup $jobQueueGroup
	 * @param CentralIdLookup $centralIdLookup
	 * @param AuthManager $authManager
	 */
	public function __construct(
		ServiceOptions $options,
		BagOStuff $cache,
		LoggerInterface $log,
		IBufferingStatsdDataFactory $stats,
		LBFactory $lbFactory,
		JobQueueGroup $jobQueueGroup,
		CentralIdLookup $centralIdLookup,
		AuthManager $authManager
	) {
		$this->config = $options;
		$this->cache = $cache;

		if ( $this->config->get( 'LoginNotifySecretKey' ) !== null ) {
			$this->secret = $this->config->get( 'LoginNotifySecretKey' );
		} else {
			$globalSecret = $this->config->get( 'SecretKey' );
			$this->secret = hash( 'sha256', $globalSecret . 'LoginNotify' );
		}
		$this->log = $log;
		$this->stats = $stats;
		$this->lbFactory = $lbFactory;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->centralIdLookup = $centralIdLookup;
		$this->authManager = $authManager;
	}

	/**
	 * Set the logger.
	 * @param LoggerInterface $logger The logger object.
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->log = $logger;
	}

	/**
	 * Get just network part of an IP (assuming /24 or /64)
	 *
	 * It would be nice if we could use IPUtils::getSubnet(), which also gets
	 * the /24 or /64 network in support of a similar use case, but its
	 * behaviour is broken for IPv6 addresses, returning the hex range start
	 * rather than the prefix. (T344963)
	 *
	 * @param string $ip Either IPv4 or IPv6 address
	 * @return string Just the network part (e.g. 127.0.0.)
	 */
	private function getIPNetwork( $ip ) {
		$ip = IPUtils::sanitizeIP( $ip );
		if ( IPUtils::isIPv6( $ip ) ) {
			// Match against the /64
			$subnetRegex = '/[0-9A-F]+:[0-9A-F]+:[0-9A-F]+:[0-9A-F]+$/i';
		} elseif ( IPUtils::isIPv4( $ip ) ) {
			// match against the /24
			$subnetRegex = '/\d+$/';
		} else {
			throw new UnexpectedValueException( "Unrecognized IP address: $ip" );
		}
		$prefix = preg_replace( $subnetRegex, '', $ip );
		if ( !is_string( $prefix ) ) {
			throw new LogicException( __METHOD__ . " Regex failed on '$ip'!?" );
		}
		return $prefix;
	}

	/**
	 * Returns lazy-initialized salt
	 *
	 * @return string
	 */
	private function getSalt() {
		// Generate salt just once to avoid duplicate cookies
		if ( $this->salt === null ) {
			$this->salt = \Wikimedia\base_convert( MWCryptRand::generateHex( 8 ), 16, 36 );
		}

		return $this->salt;
	}

	/**
	 * Is the current computer known to be used by the current user (fast checks)
	 * To be used for checks that are fast enough to be run at the moment the user logs in.
	 *
	 * @param User $user User in question
	 * @param WebRequest $request
	 * @return string One of USER_* constants
	 */
	private function isKnownSystemFast( User $user, WebRequest $request ) {
		$logContext = [ 'user' => $user->getName() ];
		$result = $this->userIsInCookie( $user, $request );
		if ( $result === self::USER_KNOWN ) {
			$this->log->debug( 'Found user {user} in cookie', $logContext );
			return $result;
		}

		if ( $this->config->get( 'LoginNotifyUseSeenTable' ) ) {
			$id = $this->getMaybeCentralId( $user );
			$hash = $this->getSeenHash( $request, $id );
			$result = $this->mergeResults( $result, $this->userIsInSeenTable( $id, $hash ) );
			if ( $result === self::USER_KNOWN ) {
				$this->log->debug( 'Found user {user} in table', $logContext );
				return $result;
			}
		}

		// No need for caching unless CheckUser will be used
		if ( $this->config->get( 'LoginNotifyUseCheckUser' ) ) {
			$result = $this->mergeResults( $result, $this->userIsInCache( $user, $request ) );
			if ( $result === self::USER_KNOWN ) {
				$this->log->debug( 'Found user {user} in cache', $logContext );
				return $result;
			}
		} else {
			$result = self::USER_NOT_KNOWN;
		}

		$this->log->debug( 'Fast checks for {user}: {result}', [
			'user' => $user->getName(),
			'result' => $result,
		] );

		return $result;
	}

	/**
	 * Is the current computer known to be used by the current user (slow checks)
	 * These checks are slow enough to be run via the job queue
	 *
	 * @param User $user User in question
	 * @param string $subnet User's current subnet
	 * @param string $resultSoFar Value returned by isKnownSystemFast() or null if
	 *        not available.
	 * @return bool true if the user has used this computer before
	 */
	private function isKnownSystemSlow( User $user, $subnet, $resultSoFar ) {
		$result = $this->checkUserAllWikis( $user, $subnet );

		$this->log->debug( 'Checking user {user} from {subnet} (result so far: {soFar}): {result}',
			[
				'function' => __METHOD__,
				'user' => $user->getName(),
				'subnet' => $subnet,
				'result' => $result,
				'soFar' => json_encode( $resultSoFar ),
			]
		);

		$result = $this->mergeResults( $result, $resultSoFar );

		// If we have no CheckUser data for the user, and there was no cookie
		// supplied, then treat the computer as known.
		if ( $result === self::USER_NO_INFO ) {
			// We have to be careful here. Whether $cookieResult is
			// self::USER_NO_INFO, is under control of the attacker.
			// If checking CheckUser is disabled, then we should not
			// hit this branch.

			$this->log->info(
				"Assuming the user {user} is from a known IP since no info is available",
				[
					'method' => __METHOD__,
					'user' => $user->getName()
				]
			);
			return true;
		}

		return $result === self::USER_KNOWN;
	}

	/**
	 * Check if we cached this user's ip address from last login.
	 *
	 * @param User $user User in question
	 * @param WebRequest $request
	 * @return string One of USER_* constants
	 */
	private function userIsInCache( User $user, WebRequest $request ) {
		$ipPrefix = $this->getIPNetwork( $request->getIP() );
		$key = $this->getKey( $user, 'prevSubnet' );
		$res = $this->cache->get( $key );
		if ( $res !== false ) {
			return $res === $ipPrefix ? self::USER_KNOWN : self::USER_NOT_KNOWN;
		}
		return self::USER_NO_INFO;
	}

	/**
	 * Check if the user is in our own table in a non-expired bucket
	 *
	 * @param int $centralUserId
	 * @param int|string $hash
	 * @return string One of USER_* constants
	 */
	private function userIsInSeenTable( int $centralUserId, $hash ) {
		if ( !$centralUserId ) {
			return self::USER_NO_INFO;
		}
		$dbr = $this->getSeenPrimaryDb();
		$seen = $dbr->newSelectQueryBuilder()
			->select( '1' )
			->from( 'loginnotify_seen_net' )
			->where( [
				'lsn_user' => $centralUserId,
				'lsn_subnet' => $hash,
				$dbr->expr( 'lsn_time_bucket', '>=', $this->getMinBucket() )
			] )
			->caller( __METHOD__ )
			->fetchField();
		if ( $seen ) {
			return self::USER_KNOWN;
		} elseif ( $this->config->get( 'LoginNotifyUseCheckUser' ) ) {
			// We still need to check CheckUser
			return self::USER_NO_INFO;
		} else {
			return self::USER_NOT_KNOWN;
		}
	}

	/**
	 * Check if the user is in our table in the current bucket
	 *
	 * @param int $centralUserId
	 * @param string $hash
	 * @param bool $usePrimary
	 * @return bool
	 */
	private function userIsInCurrentSeenBucket( int $centralUserId, $hash, $usePrimary = false ) {
		if ( !$centralUserId ) {
			return false;
		}
		if ( $usePrimary ) {
			$dbr = $this->getSeenPrimaryDb();
		} else {
			$dbr = $this->getSeenReplicaDb();
		}
		return (bool)$dbr->newSelectQueryBuilder()
			->select( '1' )
			->from( 'loginnotify_seen_net' )
			->where( [
				'lsn_user' => $centralUserId,
				'lsn_subnet' => $hash,
				'lsn_time_bucket' => $this->getCurrentBucket(),
			] )
			->caller( __METHOD__ )
			->fetchField();
	}

	/**
	 * Combine the user ID and IP prefix into a 64-bit hash. Return the hash
	 * as either an integer or a decimal string.
	 *
	 * @param WebRequest $request
	 * @param int $centralUserId
	 * @return int|string
	 */
	private function getSeenHash( WebRequest $request, int $centralUserId ) {
		$ipPrefix = $this->getIPNetwork( $request->getIP() );
		$hash = hash_hmac( 'sha1', "$centralUserId|$ipPrefix", $this->secret, true );
		// Truncate to 64 bits
		return self::packedSignedInt64ToDecimal( substr( $hash, 0, 8 ) );
	}

	/**
	 * Convert an 8-byte string to a 64-bit integer, and return it either as a
	 * native integer, or if PHP integers are 32 bits, as a decimal string.
	 *
	 * Signed 64-bit integers are a compact and portable way to store a 64-bit
	 * hash in a DBMS. On a 64-bit platform, PHP can easily generate and handle
	 * such integers, but on a 32-bit platform it is a bit awkward.
	 *
	 * @param string $str
	 * @return int|string
	 */
	private static function packedSignedInt64ToDecimal( $str ) {
		if ( PHP_INT_SIZE >= 8 ) {
			// The manual is confusing -- this does in fact return a signed number
			return unpack( 'Jv', $str )['v'];
		} else {
			// PHP has precious few facilities for manipulating 64-bit numbers on a
			// 32-bit platform. String bitwise operators are a nice hack though.
			if ( ( $str[0] & "\x80" ) !== "\x00" ) {
				// The number is negative. Find 2's complement and add minus sign.
				$sign = '-';
				$str = ~$str;
				$carry = 1;
				// Add with carry in big endian order
				for ( $i = 7; $i >= 0 && $carry; $i-- ) {
					$sum = ord( $str[$i] ) + $carry;
					$carry = ( $sum & 0x100 ) >> 8;
					$str[$i] = chr( $sum & 0xff );
				}
			} else {
				$sign = '';
			}
			return $sign . \Wikimedia\base_convert( bin2hex( $str ), 16, 10 );
		}
	}

	/**
	 * Get read a connection to the database holding the loginnotify_seen_net table.
	 *
	 * @return IReadableDatabase
	 */
	private function getSeenReplicaDb(): IReadableDatabase {
		return $this->lbFactory->getReplicaDatabase( 'virtual-LoginNotify' );
	}

	/**
	 * Get a write connection to the database holding the loginnotify_seen_net table.
	 *
	 * @return IDatabase
	 */
	private function getSeenPrimaryDb(): IDatabase {
		return $this->lbFactory->getPrimaryDatabase( 'virtual-LoginNotify' );
	}

	/**
	 * Get the lowest time bucket index which is not expired.
	 *
	 * @return int
	 */
	private function getMinBucket() {
		$now = $this->getCurrentTime();
		$expiry = $this->config->get( 'LoginNotifySeenExpiry' );
		$size = $this->config->get( 'LoginNotifySeenBucketSize' );
		return (int)( ( $now - $expiry ) / $size );
	}

	/**
	 * Get the current time bucket index.
	 *
	 * @return int
	 */
	private function getCurrentBucket() {
		return (int)( $this->getCurrentTime() / $this->config->get( 'LoginNotifySeenBucketSize' ) );
	}

	/**
	 * Get the current UNIX time
	 *
	 * @return int
	 */
	private function getCurrentTime() {
		return $this->fakeTime ?? time();
	}

	/**
	 * Set a fake time to be returned by getCurrentTime(), for testing.
	 *
	 * @param int|null $time
	 */
	public function setFakeTime( $time ) {
		$this->fakeTime = $time;
	}

	/**
	 * If LoginNotifyUseCentralId is true, indicating a shared table,
	 * get the central user ID. Otherwise, get the local user ID.
	 *
	 * If CentralAuth is not installed, $this->centralIdLookup will be a
	 * LocalIdLookup and the local user ID will be returned regardless. But
	 * using CentralIdLookup unconditionally can fail if CentralAuth is
	 * installed but no users are attached to it, as is the case in CI.
	 *
	 * @param User $user
	 * @return int
	 */
	private function getMaybeCentralId( User $user ) {
		if ( $this->config->get( 'LoginNotifyUseCentralId' ) ) {
			return $this->centralIdLookup->centralIdFromLocalUser( $user );
		} else {
			return $user->getId();
		}
	}

	/**
	 * Is the subnet of the current IP in the CheckUser data for the user.
	 *
	 * If CentralAuth is installed, this will check not only the current wiki,
	 * but also the ten wikis where user has most edits on.
	 *
	 * @param User $user User in question
	 * @param string $subnet User's current subnet
	 * @return string One of USER_* constants
	 */
	private function checkUserAllWikis( User $user, $subnet ) {
		Assert::parameter( $user->isRegistered(), '$user', 'User must be logged in' );

		if ( !$this->config->get( 'LoginNotifyCheckKnownIPs' )
			|| !$this->isCheckUserInstalled()
		) {
			// CheckUser checks disabled.
			// Note: It's important this be USER_NOT_KNOWN and not USER_NO_INFO.
			return self::USER_NOT_KNOWN;
		}

		$dbr = $this->lbFactory->getReplicaDatabase();
		$result = $this->checkUserOneWiki( $user->getId(), $subnet, $dbr );
		if ( $result === self::USER_KNOWN ) {
			return $result;
		}

		if ( $result === self::USER_NO_INFO
			&& $this->userHasCheckUserData( $user->getId(), $dbr )
		) {
			$result = self::USER_NOT_KNOWN;
		}

		// Also check checkuser table on the top ten wikis where this user has
		// edited the most. We only do top ten, to limit the worst-case where the
		// user has accounts on 800 wikis.
		if ( ExtensionRegistry::getInstance()->isLoaded( 'CentralAuth' ) ) {
			$globalUser = CentralAuthUser::getInstance( $user );
			if ( $globalUser->exists() ) {
				// This is expensive, up to ~5 seconds (T167731)
				$info = $globalUser->queryAttached();
				// Already checked the local wiki.
				unset( $info[WikiMap::getCurrentWikiId()] );
				usort( $info,
					static function ( $a, $b ) {
						// descending order
						return $b['editCount'] - $a['editCount'];
					}
				);
				$count = 0;
				foreach ( $info as $localInfo ) {
					if ( !isset( $localInfo['id'] ) || !isset( $localInfo['wiki'] ) ) {
						break;
					}
					if ( $count > 10 || $localInfo['editCount'] < 1 ) {
						break;
					}

					$wiki = $localInfo['wiki'];
					$lb = $this->lbFactory->getMainLB( $wiki );
					$dbrLocal = $lb->getMaintenanceConnectionRef( DB_REPLICA, [], $wiki );

					if ( !$this->hasCheckUserTables( $dbrLocal ) ) {
						// Skip this wiki, no CheckUser table.
						continue;
					}
					$res = $this->checkUserOneWiki(
						$localInfo['id'],
						$subnet,
						$dbrLocal
					);

					if ( $res === self::USER_KNOWN ) {
						return $res;
					}
					if ( $result === self::USER_NO_INFO
						 && $this->userHasCheckUserData( $user->getId(), $dbr )
					) {
						$result = self::USER_NOT_KNOWN;
					}
					$count++;
				}
			}
		}
		return $result;
	}

	/**
	 * Actually do the query of the CheckUser table.
	 *
	 * @note This catches and ignores database errors.
	 * @param int $userId User ID number (Not necessarily for the local wiki)
	 * @param string $ipFragment Prefix to match against cuc_ip (from $this->getIPNetwork())
	 * @param IReadableDatabase $dbr A database connection (possibly foreign)
	 * @return string One of USER_* constants
	 */
	private function checkUserOneWiki( $userId, $ipFragment, IReadableDatabase $dbr ) {
		// The index is on (cuc_actor, cuc_ip, cuc_timestamp), instead of
		// cuc_ip_hex which would be ideal, but CheckUser was not designed for
		// this specific use case and we couldn't be bothered to update it.
		// Although it would be 100x faster to use a single global summary
		// table instead of connecting to the database of each wiki separately.
		$IPHasBeenUsedBefore = $dbr->newSelectQueryBuilder()
			->select( '1' )
			->from( 'cu_changes' )
			->join( 'actor', null, 'actor_id = cuc_actor' )
			->where( [
				'actor_user' => $userId,
				$dbr->expr( 'cuc_ip', IExpression::LIKE, new LikeValue(
					$ipFragment,
					$dbr->anyString()
				) )
			] )
			->caller( __METHOD__ )
			->fetchField();
		return $IPHasBeenUsedBefore ? self::USER_KNOWN : self::USER_NO_INFO;
	}

	/**
	 * Check if we have any CheckUser info for this user
	 *
	 * If we have no info for user, we maybe don't treat it as
	 * an unknown IP, since user has no known IPs.
	 *
	 * @param int $userId User id number (possibly on foreign wiki)
	 * @param IReadableDatabase $dbr DB connection (possibly to foreign wiki)
	 * @return bool
	 */
	private function userHasCheckUserData( $userId, IReadableDatabase $dbr ) {
		$haveIPInfo = $dbr->newSelectQueryBuilder()
			->select( '1' )
			->from( 'cu_changes' )
			->join( 'actor', null, 'actor_id = cuc_actor' )
			->where( [ 'actor_user' => $userId ] )
			->caller( __METHOD__ )
			->fetchField();

		return (bool)$haveIPInfo;
	}

	/**
	 * Does this wiki have a CheckUser table?
	 *
	 * @param IMaintainableDatabase $dbr Database to check
	 * @return bool
	 */
	private function hasCheckUserTables( IMaintainableDatabase $dbr ) {
		if ( !$dbr->tableExists( 'cu_changes', __METHOD__ ) ) {
			$this->log->warning( "No CheckUser table on {wikiId}", [
				'method' => __METHOD__,
				'wikiId' => $dbr->getDomainID()
			] );
			return false;
		}
		return true;
	}

	/**
	 * Whether CheckUser extension is installed
	 * @return bool
	 */
	private function isCheckUserInstalled() {
		return ExtensionRegistry::getInstance()->isLoaded( 'CheckUser' );
	}

	/**
	 * Give the user a cookie saying that they've previously logged in from this computer.
	 *
	 * @note If user already has a cookie, this will refresh it.
	 * @param User $user User in question who just logged in.
	 */
	private function setLoginCookie( User $user ) {
		$cookie = $this->getPrevLoginCookie( $user->getRequest() );
		[ , $newCookie ] = $this->checkAndGenerateCookie( $user, $cookie );
		$expire = $this->getCurrentTime() + $this->config->get( 'LoginNotifyCookieExpire' );
		$resp = $user->getRequest()->response();
		$resp->setCookie(
			self::COOKIE_NAME,
			$newCookie,
			$expire,
			[
				'domain' => $this->config->get( 'LoginNotifyCookieDomain' ),
				// Allow sharing this cookie between wikis
				'prefix' => ''
			]
		);
	}

	/**
	 * Give the user a cookie and store the address in memcached and the DB.
	 *
	 * It is expected this be called upon successful log in.
	 *
	 * @param User $user The user in question.
	 */
	public function recordKnownWithCookie( User $user ) {
		if ( !$user->isNamed() ) {
			return;
		}
		$this->setLoginCookie( $user );
		$this->recordKnown( $user );
	}

	/**
	 * Store the user's IP address in memcached and the DB
	 *
	 * @param User $user
	 * @return void
	 */
	public function recordKnown( User $user ) {
		if ( !$user->isNamed() ) {
			return;
		}
		$this->cacheLoginIP( $user );
		$this->recordUserInSeenTable( $user );

		$this->log->debug( 'Recording user {user} as known',
			[
				'function' => __METHOD__,
				'user' => $user->getName(),
			]
		);
	}

	/**
	 * Cache the current IP subnet as being a known location for the given user.
	 *
	 * @param User $user The user.
	 */
	private function cacheLoginIP( User $user ) {
		// For simplicity, this only stores the last IP subnet used.
		// It's assumed that most of the time, we'll be able to rely on
		// the cookie or CheckUser data.
		$expiry = $this->config->get( 'LoginNotifyCacheLoginIPExpiry' );
		$useCU = $this->config->get( 'LoginNotifyUseCheckUser' );
		if ( $useCU && $expiry !== false ) {
			$ipPrefix = $this->getIPNetwork( $user->getRequest()->getIP() );
			$key = $this->getKey( $user, 'prevSubnet' );
			$this->cache->set( $key, $ipPrefix, $expiry );
		}
	}

	/**
	 * If the user/subnet combination is not already in the database, add it.
	 * Also queue a job to clean up expired rows, if necessary.
	 *
	 * @param User $user
	 * @return void
	 */
	private function recordUserInSeenTable( User $user ) {
		if ( !$this->config->get( 'LoginNotifyUseSeenTable' ) ) {
			return;
		}
		$id = $this->getMaybeCentralId( $user );
		if ( !$id ) {
			return;
		}

		$request = $user->getRequest();
		$hash = $this->getSeenHash( $request, $id );

		// Check if the user/hash is in the replica DB
		if ( $this->userIsInCurrentSeenBucket( $id, $hash ) ) {
			return;
		}

		// Check whether purging is required
		if ( !mt_rand( 0, (int)( $this->config->get( 'UpdateRowsPerQuery' ) / 4 ) ) ) {
			$minId = $this->getMinExpiredId();
			if ( $minId !== null ) {
				$this->log->debug( 'Queueing purge job starting from lsn_id={minId}',
					[ 'minId' => $minId ] );
				// Deferred call to purgeSeen()
				// removeDuplicates effectively limits concurrency to 1, since
				// no more work will be queued until the DELETE is committed.
				$job = new JobSpecification(
					'LoginNotifyPurgeSeen',
					[ 'minId' => $minId ],
					[ 'removeDuplicates' => true ]
				);
				$this->jobQueueGroup->push( $job );
			}
		}

		// Insert a row
		$dbw = $this->getSeenPrimaryDb();
		$fname = __METHOD__;
		$dbw->onTransactionCommitOrIdle(
			function () use ( $dbw, $id, $hash, $fname ) {
				$dbw->newInsertQueryBuilder()
					->insert( 'loginnotify_seen_net' )
					->ignore()
					->row( [
						'lsn_time_bucket' => $this->getCurrentBucket(),
						'lsn_user' => $id,
						'lsn_subnet' => $hash
					] )
					->caller( $fname )
					->execute();
			}
		);
	}

	/**
	 * Estimate the minimum lsn_id which has an expired time bucket.
	 *
	 * The primary key is approximately monotonic in time. Guess whether
	 * purging is required by looking at the first row ordered by
	 * primary key. If this check misses a row, it will be cleaned up
	 * when the next bucket expires.
	 *
	 * @return int|null
	 */
	public function getMinExpiredId() {
		$minRow = $this->getSeenPrimaryDb()->newSelectQueryBuilder()
			->select( [ 'lsn_id', 'lsn_time_bucket' ] )
			->from( 'loginnotify_seen_net' )
			->orderBy( 'lsn_id' )
			->limit( 1 )
			->caller( __METHOD__ )
			->fetchRow();

		if ( $minRow && ( $minRow->lsn_time_bucket < $this->getMinBucket() ) ) {
			return (int)$minRow->lsn_id;
		}

		return null;
	}

	/**
	 * Purge rows from the loginnotify_seen_net table that are expired.
	 *
	 * @param int $minId The lsn_id to start at
	 * @return int|null The lsn_id to continue at, or null if no more expired
	 *   rows are expected.
	 */
	public function purgeSeen( $minId ) {
		$dbw = $this->getSeenPrimaryDb();
		$maxId = $minId + $this->config->get( 'UpdateRowsPerQuery' );

		$dbw->newDeleteQueryBuilder()
			->delete( 'loginnotify_seen_net' )
			->where( [
				$dbw->expr( 'lsn_id', '>=', $minId ),
				$dbw->expr( 'lsn_id', '<', $maxId ),
				$dbw->expr( 'lsn_time_bucket', '<', $this->getMinBucket() )
			] )
			->caller( __METHOD__ )
			->execute();

		// If there were affected rows, tell the maintenance script to keep looking
		if ( $dbw->affectedRows() ) {
			return $maxId;
		} else {
			return null;
		}
	}

	/**
	 * Merges results of various isKnownSystem*() checks
	 *
	 * @param string $x One of USER_* constants
	 * @param string $y One of USER_* constants
	 * @return string
	 */
	private function mergeResults( $x, $y ) {
		if ( $x === self::USER_KNOWN || $y === self::USER_KNOWN ) {
			return self::USER_KNOWN;
		}
		if ( $x === self::USER_NOT_KNOWN || $y === self::USER_NOT_KNOWN ) {
			return self::USER_NOT_KNOWN;
		}
		return self::USER_NO_INFO;
	}

	/**
	 * Check if a certain user is in the cookie.
	 *
	 * @param User $user User in question
	 * @param WebRequest $request
	 * @return string One of USER_* constants
	 */
	private function userIsInCookie( User $user, WebRequest $request ) {
		$cookie = $this->getPrevLoginCookie( $request );

		if ( $cookie === '' ) {
			$result = self::USER_NO_INFO;
		} else {
			[ $userKnown, ] = $this->checkAndGenerateCookie( $user, $cookie );
			$result = $userKnown ? self::USER_KNOWN : self::USER_NOT_KNOWN;
		}

		return $result;
	}

	/**
	 * Get the cookie with previous login names in it
	 *
	 * @param WebRequest $req
	 * @return string The cookie. Empty string if no cookie.
	 */
	private function getPrevLoginCookie( WebRequest $req ) {
		return $req->getCookie( self::COOKIE_NAME, '', '' );
	}

	/**
	 * Check if user is in cookie, and generate a new cookie with user record
	 *
	 * When generating a new cookie, it will add the current user to the top,
	 * remove any previous instances of the current user, and remove older user
	 * references, if there are too many records.
	 *
	 * @param User $user User that person is attempting to log in as.
	 * @param string $cookie A cookie, which has records separated by '.'.
	 * @return array Element 0 is boolean (user seen before?), 1 is the new cookie value.
	 */
	private function checkAndGenerateCookie( User $user, $cookie ) {
		$userSeenBefore = false;
		if ( $cookie === '' ) {
			$cookieRecords = [];
		} else {
			$cookieRecords = explode( '.', $cookie );
		}
		$newCookie = $this->generateUserCookieRecord( $user->getName() );
		$maxCookieRecords = $this->config->get( 'LoginNotifyMaxCookieRecords' );

		foreach ( $cookieRecords as $i => $cookieRecord ) {
			if ( !$this->validateCookieRecord( $cookieRecord ) ) {
				// Skip invalid or old cookie records.
				continue;
			}
			$curUser = $this->isUserRecordGivenCookie( $user, $cookieRecord );
			$userSeenBefore = $userSeenBefore || $curUser;
			if ( $i < $maxCookieRecords && !$curUser ) {
				$newCookie .= '.' . $cookieRecord;
			}
		}
		return [ $userSeenBefore, $newCookie ];
	}

	/**
	 * See if a specific cookie record is for a specific user.
	 *
	 * Cookie record format is: Year - 32-bit salt - hash
	 * where hash is sha1-HMAC of username + | + year + salt
	 * Salt and hash is base 36 encoded.
	 *
	 * The point of the salt is to ensure that a given user creates
	 * different cookies on different machines, so that nobody
	 * can after the fact figure out a single user has used both
	 * machines.
	 *
	 * @param User $user
	 * @param string $cookieRecord
	 * @return bool
	 */
	private function isUserRecordGivenCookie( User $user, $cookieRecord ) {
		if ( !$this->validateCookieRecord( $cookieRecord ) ) {
			// Most callers will probably already check this, but
			// doesn't hurt to be careful.
			return false;
		}
		$parts = explode( "-", $cookieRecord, 3 );
		$hash = $this->generateUserCookieRecord( $user->getName(), $parts[0], $parts[1] );
		return hash_equals( $hash, $cookieRecord );
	}

	/**
	 * Check if cookie is valid (Is not too old, has 3 fields)
	 *
	 * @param string $cookieRecord Cookie record
	 * @return bool true if valid
	 */
	private function validateCookieRecord( $cookieRecord ) {
		$parts = explode( "-", $cookieRecord, 3 );
		if ( count( $parts ) !== 3 || strlen( $parts[0] ) !== 4 ) {
			$this->log->warning( "Got cookie with invalid format",
				[
					'method' => __METHOD__,
					'cookieRecord' => $cookieRecord
				]
			);
			return false;
		}
		if ( (int)$parts[0] < (int)gmdate( 'Y' ) - 3 ) {
			// Record is too old. If user hasn't logged in from this
			// computer in two years, should probably not consider it trusted.
			return false;
		}
		return true;
	}

	/**
	 * Generate a single record for use in the previous login cookie
	 *
	 * The format is YYYY-SSSSSSS-HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH
	 * where Y is the year, S is a 32-bit salt, H is an sha1-hmac.
	 * Both S and H are base-36 encoded. The actual cookie consists
	 * of several of these records separated by a ".".
	 *
	 * When checking if a hash is valid, provide all three arguments.
	 * When generating a new hash, only use the first argument.
	 *
	 * @param string $username Username,
	 * @param string|false $year [Optional] Year. Default to current year
	 * @param string|false $salt [Optional] Salt (expected to be base-36 encoded)
	 * @return string A record for the cookie
	 */
	private function generateUserCookieRecord( $username, $year = false, $salt = false ) {
		if ( $year === false ) {
			$year = gmdate( 'Y' );
		}

		if ( $salt === false ) {
			$salt = $this->getSalt();
		}

		// TODO: would be nice to truncate the hash, but we would need b/c
		$res = hash_hmac( 'sha1', $username . '|' . $year . $salt, $this->secret );
		'@phan-var string|false $res';
		if ( !is_string( $res ) ) {
			// Throws ValueError under php8 in case of error, remove this when mininum is php8
			throw new UnexpectedValueException( "Hash failed" );
		}
		$encoded = $year . '-' . $salt . '-' . \Wikimedia\base_convert( $res, 16, 36 );
		return $encoded;
	}

	/**
	 * Get the cache key for the counter.
	 *
	 * @param User $user
	 * @param string $type 'known' or 'new'
	 * @return string The cache key
	 */
	private function getKey( User $user, $type ) {
		$userHash = \Wikimedia\base_convert( sha1( $user->getName() ), 16, 36, 31 );
		return $this->cache->makeGlobalKey(
			'loginnotify', $type, $userHash
		);
	}

	/**
	 * Increment hit counters for a failed login from an unknown computer.
	 *
	 * If a sufficient number of hits have accumulated, send an echo notice.
	 *
	 * @param User $user
	 */
	private function recordLoginFailureFromUnknownSystem( User $user ) {
		$key = $this->getKey( $user, 'new' );
		$count = $this->checkAndIncKey(
			$key,
			$this->config->get( 'LoginNotifyAttemptsNewIP' ),
			$this->config->get( 'LoginNotifyExpiryNewIP' )
		);
		$message = '{count} failed login attempts for {user} from an unknown system';
		if ( $count ) {
			$this->incrStats( 'fail.unknown.notifications' );
			$this->sendNotice( $user, 'login-fail-new', $count );
			$message .= ', sending notification';
		}

		$this->log->debug( $message,
			[
				'function' => __METHOD__,
				'count' => $count,
				'user' => $user->getName(),
			]
		);
	}

	/**
	 * Increment hit counters for a failed login from a known computer.
	 *
	 * If a sufficient number of hits have accumulated, send an echo notice.
	 *
	 * @param User $user
	 */
	private function recordLoginFailureFromKnownSystem( User $user ) {
		$key = $this->getKey( $user, 'known' );
		$count = $this->checkAndIncKey(
			$key,
			$this->config->get( 'LoginNotifyAttemptsKnownIP' ),
			$this->config->get( 'LoginNotifyExpiryKnownIP' )
		);
		if ( $count ) {
			$this->incrStats( 'fail.known.notifications' );
			$this->sendNotice( $user, 'login-fail-known', $count );
		}
	}

	/**
	 * Send a notice about login attempts
	 *
	 * @param User $user The account in question
	 * @param string $type 'login-fail-new' or 'login-fail-known'
	 * @param int|null $count [Optional] How many failed attempts
	 */
	private function sendNotice( User $user, $type, $count = null ) {
		$extra = [];
		if ( $count !== null ) {
			$extra['count'] = $count;
		}
		Event::create( [
			'type' => $type,
			'extra' => $extra,
			'agent' => $user,
		] );

		$this->log->info( 'Sending a {notificationtype} notification to {user}',
			[
				'function' => __METHOD__,
				'notificationtype' => $type,
				'user' => $user->getName(),
			]
		);
	}

	/**
	 * Check if we've reached the limit, and increment the cache key.
	 *
	 * @param string $key Cache key
	 * @param int $interval The interval of one to send notice
	 * @param int $expiry When to expire cache key.
	 * @return false|int false to not send notice, or number of hits
	 */
	private function checkAndIncKey( $key, $interval, $expiry ) {
		$cache = $this->cache;

		$cur = $cache->incrWithInit( $key, $expiry );
		if ( $cur % $interval === 0 ) {
			return $cur;
		}
		return false;
	}

	/**
	 * Clear attempt counter for user.
	 *
	 * When a user successfully logs in, we start back from 0, as
	 * otherwise a mistake here and there will trigger the warning.
	 *
	 * @param User $user The user for whom to clear the attempt counter.
	 */
	public function clearCounters( User $user ) {
		$cache = $this->cache;
		$keyKnown = $this->getKey( $user, 'known' );
		$keyNew = $this->getKey( $user, 'new' );

		$cache->delete( $keyKnown );
		$cache->delete( $keyNew );
	}

	/**
	 * On login failure, record failure and maybe send notice
	 *
	 * @param User $user User in question
	 */
	public function recordFailure( User $user ) {
		$this->incrStats( 'fail.total' );

		if ( $user->isAnon() ) {
			// Login failed because user doesn't exist
			// skip this user.
			$this->log->debug( "Skipping recording failure for {user} - no account",
				[ 'user' => $user->getName() ]
			);
			return;
		}

		// No need to notify if the user can't authenticate (e.g. system or temporary users)
		if ( !$this->authManager->userCanAuthenticate( $user->getName() ) ) {
			$this->log->debug( "Skipping recording failure for user {user} - can't authenticate",
				[ 'user' => $user->getName() ]
			);
			return;
		}

		$known = $this->isKnownSystemFast( $user, $user->getRequest() );
		if ( $known === self::USER_KNOWN ) {
			$this->recordLoginFailureFromKnownSystem( $user );
		} elseif ( $this->config->get( 'LoginNotifyUseCheckUser' ) ) {
			$this->createJob( DeferredChecksJob::TYPE_LOGIN_FAILED,
				$user, $user->getRequest(), $known
			);
		} else {
			$this->recordLoginFailureFromUnknownSystem( $user );
		}
	}

	/**
	 * Asynchronous part of recordFailure(), to be called from DeferredChecksJob
	 *
	 * @param User $user User in question
	 * @param string $subnet User's current subnet
	 * @param string $resultSoFar Value returned by isKnownSystemFast()
	 */
	public function recordFailureDeferred( User $user, $subnet, $resultSoFar ) {
		$isKnown = $this->isKnownSystemSlow( $user, $subnet, $resultSoFar );
		if ( !$isKnown ) {
			$this->recordLoginFailureFromUnknownSystem( $user );
		} else {
			$this->recordLoginFailureFromKnownSystem( $user );
		}
	}

	/**
	 * Send a notice on successful login from an unknown IP
	 *
	 * @param User $user User account in question.
	 */
	public function sendSuccessNotice( User $user ) {
		if ( !$this->config->get( 'LoginNotifyEnableOnSuccess' ) ) {
			return;
		}
		$this->incrStats( 'success.total' );
		$result = $this->isKnownSystemFast( $user, $user->getRequest() );
		if ( $result === self::USER_KNOWN ) {
			// No need to notify
		} elseif ( $this->config->get( 'LoginNotifyUseCheckUser' ) ) {
			$this->createJob( DeferredChecksJob::TYPE_LOGIN_SUCCESS,
				$user, $user->getRequest(), $result
			);
		} elseif ( $result === self::USER_NOT_KNOWN ) {
			$this->incrStats( 'success.notifications' );
			$this->sendNotice( $user, 'login-success' );
		}
	}

	/**
	 * Asynchronous part of sendSuccessNotice(), to be called from DeferredChecksJob
	 *
	 * @param User $user User in question
	 * @param string $subnet User's current subnet
	 * @param string $resultSoFar Value returned by isKnownSystemFast()
	 */
	public function sendSuccessNoticeDeferred( User $user, $subnet, $resultSoFar ) {
		$isKnown = $this->isKnownSystemSlow( $user, $subnet, $resultSoFar );
		if ( $isKnown ) {
			$this->log->debug( 'Found data for user {user} from {subnet}',
				[
					'function' => __METHOD__,
					'user' => $user->getName(),
					'subnet' => $subnet,
				]
			);
		} else {
			$this->incrStats( 'success.notifications' );
			$this->sendNotice( $user, 'login-success' );
		}
	}

	/**
	 * Create and enqueue a job to do asynchronous processing of user login success/failure
	 *
	 * @param string $type Job type, one of DeferredChecksJob::TYPE_* constants
	 * @param User $user User in question
	 * @param WebRequest $request
	 * @param string $resultSoFar Value returned by isKnownSystemFast()
	 */
	private function createJob( $type, User $user, WebRequest $request, $resultSoFar ) {
		$subnet = $this->getIPNetwork( $request->getIP() );
		$job = new JobSpecification( 'LoginNotifyChecks',
			[
				'checkType' => $type,
				'userId' => $user->getId(),
				'subnet' => $subnet,
				'resultSoFar' => $resultSoFar,
			]
		);
		$this->jobQueueGroup->lazyPush( $job );

		$this->log->debug( 'Login {status}, creating a job to verify {user}, result so far: {result}',
			[
				'function' => __METHOD__,
				'status' => $type,
				'user' => $user->getName(),
				'result' => $resultSoFar,
			]
		);
	}

	/**
	 * Increments the given statistic
	 *
	 * @param string $metric
	 */
	private function incrStats( $metric ) {
		$this->stats->increment( "loginnotify.$metric" );
	}
}
