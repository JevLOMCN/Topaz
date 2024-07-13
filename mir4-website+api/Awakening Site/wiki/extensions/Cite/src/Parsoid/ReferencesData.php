<?php
declare( strict_types = 1 );

namespace Cite\Parsoid;

use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

/**
 * @license GPL-2.0-or-later
 */
class ReferencesData {

	private int $index = 0;
	/** @var array<string,RefGroup> indexed by group name */
	private array $refGroups = [];
	/** @var array<string,array[]> */
	public array $embeddedErrors = [];
	/** @var string[] */
	private array $inEmbeddedContent = [];
	public string $referencesGroup = '';

	public function inReferencesContent(): bool {
		return $this->inEmbeddedContent( 'references' );
	}

	public function inEmbeddedContent( ?string $needle = null ): bool {
		if ( $needle ) {
			return in_array( $needle, $this->inEmbeddedContent, true );
		} else {
			return $this->inEmbeddedContent !== [];
		}
	}

	public function pushEmbeddedContentFlag( string $needle = 'embed' ): void {
		array_unshift( $this->inEmbeddedContent, $needle );
	}

	public function popEmbeddedContentFlag() {
		array_shift( $this->inEmbeddedContent );
	}

	public function getRefGroup(
		string $groupName, bool $allocIfMissing = false
	): ?RefGroup {
		if ( $allocIfMissing ) {
			$this->refGroups[$groupName] ??= new RefGroup( $groupName );
		}
		return $this->refGroups[$groupName] ?? null;
	}

	public function removeRefGroup( string $groupName ): void {
		// '' is a valid group (the default group)
		unset( $this->refGroups[$groupName] );
	}

	/**
	 * Normalizes and sanitizes a reference key
	 *
	 * @param string $key
	 * @return string
	 */
	private function normalizeKey( string $key ): string {
		$ret = Sanitizer::escapeIdForLink( $key );
		$ret = preg_replace( '/[_\s]+/u', '_', $ret );
		return $ret;
	}

	public function add(
		ParsoidExtensionAPI $extApi, string $groupName, string $refName, string $refDir
	): RefGroupItem {
		$group = $this->getRefGroup( $groupName, true );
		$hasRefName = $refName !== '';

		// The ids produced Cite.php have some particulars:
		// Simple refs get 'cite_ref-' + index
		// Refs with names get 'cite_ref-' + name + '_' + index + (backlink num || 0)
		// Notes (references) whose ref doesn't have a name are 'cite_note-' + index
		// Notes whose ref has a name are 'cite_note-' + name + '-' + index
		$n = $this->index;
		$refKey = strval( 1 + $n );

		$refNameSanitized = $this->normalizeKey( $refName );

		$refIdBase = 'cite_ref-' . ( $hasRefName ? $refNameSanitized . '_' . $refKey : $refKey );
		$noteId = 'cite_note-' . ( $hasRefName ? $refNameSanitized . '-' . $refKey : $refKey );

		// bump index
		$this->index++;

		$ref = new RefGroupItem();
		$ref->dir = $refDir;
		$ref->group = $group->name;
		$ref->groupIndex = count( $group->refs ) + 1;
		$ref->index = $n;
		$ref->key = $refIdBase;
		$ref->id = $hasRefName ? $refIdBase . '-0' : $refIdBase;
		$ref->name = $refName;
		$ref->target = $noteId;

		$group->refs[] = $ref;

		if ( $hasRefName ) {
			$group->indexByName[$refName] = $ref;
		}

		return $ref;
	}

	/**
	 * @return RefGroup[]
	 */
	public function getRefGroups(): array {
		return $this->refGroups;
	}
}
