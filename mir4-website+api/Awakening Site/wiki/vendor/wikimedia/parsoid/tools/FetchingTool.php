<?php

namespace Wikimedia\Parsoid\Tools;

use InvalidArgumentException;

require_once __DIR__ . '/Maintenance.php';

/**
 * Class FetchingTool
 * @package Wikimedia\Parsoid\Tools
 */
abstract class FetchingTool extends Maintenance {
	/** @var array Mapping between a prefix and its API URL */
	private $apiUrls = [];

	/** @var array Mapping between a domain and its prefix */
	private $domainsToPrefix = [];

	public function setup(): void {
		parent::setup();
		$this->loadSiteMatrixForApiConfig();
	}

	/** Loads ./data/wmf.sitematrix.json to get prefix -> domain and domain -> prefix
	 * mappings
	 * TODO: allow for parametrization of that file place
	 */
	private function loadSiteMatrixForApiConfig(): void {
		$matrixJson =
			json_decode( file_get_contents( realpath( __DIR__ .
				'/data/wmf.sitematrix.json' ) ) )->sitematrix;

		foreach ( $matrixJson as $key => $data ) {
			if ( is_numeric( $key ) ) {
				foreach ( $data->site as $site ) {
					$this->addMatrixEntry( $site->url, $site->dbname );
				}
			} elseif ( $key === 'specials' ) {
				foreach ( $data as $site ) {
					$this->addMatrixEntry( $site->url, $site->dbname );
				}
			}
		}
	}

	/** Add entries mapping prefix to API URL and domain to prefix
	 * @param string $url domain of the considered wiki
	 * @param string $prefix prefix of the considered wiki
	 */
	private function addMatrixEntry( string $url, string $prefix ): void {
		$this->apiUrls[$prefix] = $url . "/w/api.php";
		$domain = parse_url( $url )['host'];
		$this->domainsToPrefix[$domain] = $prefix;
	}

	/**
	 * Gets the domain and prefix from the CLI options
	 * @return array containing values for 'prefix' and 'domain' keys
	 */
	public function getDomainAndPrefix(): array {
		$prefix = $this->hasOption( 'prefix' ) ? $this->getOption( 'prefix' ) : null;
		$domain = $this->hasOption( 'domain' ) ? $this->getOption( 'domain' ) : null;

		if ( $this->hasOption( 'apiURL' ) ) {
			$prefix = 'customwiki';
			$url = $this->getOption( 'apiURL' );
			$domain = parse_url( $url )['host'];
			$this->apiUrls[$prefix] = $url;
			$this->domainsToPrefix[$domain] = $prefix;
		} elseif ( !isset( $prefix ) && !isset( $domain ) ) {
			$domain = 'en.wikipedia.org';
		}

		return [ 'domain' => $domain, 'prefix' => $prefix ];
	}

	/** Creates options for the API call depending on the domain and prefix, and filling in the
	 * API endpoint based on the $apiUrls
	 * @param array $opts array containing at least one value for the 'prefix' or 'domain' keys
	 * (the 'domain' key has priority)
	 * @return array containing the API call parameters
	 */
	public function getApiCall( array $opts ): array {
		$options = $opts ?? [];
		if ( array_key_exists( 'domain', $options ) && isset( $options['domain'] ) ) {
			$options['prefix'] = $this->domainsToPrefix[$options['domain']];
		}
		if ( !array_key_exists( 'prefix', $options ) ||
			!array_key_exists( $options['prefix'], $this->apiUrls ) ) {
			throw new InvalidArgumentException( 'No API URI available for prefix: ' .
				$options['prefix'] . '; domain: ' . $options['domain'] );
		}
		$options['apiEndpoint'] = $this->apiUrls[$options['prefix']];

		return $options;
	}

}
