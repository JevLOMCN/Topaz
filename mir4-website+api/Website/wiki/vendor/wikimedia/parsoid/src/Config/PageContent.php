<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Config;

use InvalidArgumentException;

/**
 * Page content data object
 */
abstract class PageContent {

	/**
	 * Return the roles available in this page
	 * @return string[]
	 */
	abstract public function getRoles(): array;

	/**
	 * Determine whether the page contains a role
	 * @param string $role
	 * @return bool
	 */
	abstract public function hasRole( string $role ): bool;

	/**
	 * Fetch the content model for a role
	 * @param string $role
	 * @return string
	 * @throws InvalidArgumentException if the role doesn't exist
	 */
	abstract public function getModel( string $role ): string;

	/**
	 * Fetch the content format for a role
	 * @param string $role
	 * @return string
	 * @throws InvalidArgumentException if the role doesn't exist
	 */
	abstract public function getFormat( string $role ): string;

	/**
	 * Fetch the content for a role
	 * @param string $role
	 * @return string
	 * @throws InvalidArgumentException if the role doesn't exist
	 */
	abstract public function getContent( string $role ): string;

}
