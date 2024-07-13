<?php

namespace Cite;

use LogicException;
use StripState;

/**
 * Encapsulates most of Cite state during parsing.  This includes metadata about each ref tag,
 * and a rollback stack to correct confusion caused by lost context when `{{#tag` is used.
 *
 * @license GPL-2.0-or-later
 */
class ReferenceStack {

	/**
	 * Data structure representing all <ref> tags parsed so far, indexed by group name (an empty
	 * string for the default group) and reference name.
	 *
	 * References without a name get a numeric index, starting from 0. Conflicts are avoided by
	 * disallowing numeric names (e.g. <ref name="1">) in {@see Validator::validateRef}.
	 *
	 * @var array<string,array<string|int,ReferenceStackItem>>
	 */
	private array $refs = [];

	/**
	 * Auto-incrementing sequence number for all <ref>, no matter which group
	 */
	private int $refSequence = 0;

	/** @var int[] Counter for the number of refs in each group */
	private array $groupRefSequence = [];

	/**
	 * <ref> call stack
	 * Used to cleanup out of sequence ref calls created by #tag
	 * See description of function rollbackRef.
	 *
	 * @var (array|false)[]
	 * @phan-var array<array{0:string,1:int,2:string,3:?string,4:?string,5:?string,6:array}|false>
	 */
	private array $refCallStack = [];

	private const ACTION_ASSIGN = 'assign';
	private const ACTION_INCREMENT = 'increment';
	private const ACTION_NEW_FROM_PLACEHOLDER = 'new-from-placeholder';
	private const ACTION_NEW = 'new';

	/**
	 * Leave a mark in the stack which matches an invalid ref tag.
	 */
	public function pushInvalidRef(): void {
		$this->refCallStack[] = false;
	}

	/**
	 * Populate $this->refs and $this->refCallStack based on input and arguments to <ref>
	 *
	 * @param StripState $stripState
	 * @param ?string $text Content from the <ref> tag
	 * @param string[] $argv
	 * @param string $group
	 * @param ?string $name
	 * @param ?string $extends
	 * @param ?string $follow Guaranteed to not be a numeric string
	 * @param ?string $dir ref direction
	 *
	 * @return ?ReferenceStackItem ref structure, or null if no footnote marker should be rendered
	 */
	public function pushRef(
		StripState $stripState,
		?string $text,
		array $argv,
		string $group,
		?string $name,
		?string $extends,
		?string $follow,
		?string $dir
	): ?ReferenceStackItem {
		$this->refs[$group] ??= [];
		$this->groupRefSequence[$group] ??= 0;

		$ref = new ReferenceStackItem();
		$ref->count = 1;
		$ref->dir = $dir;
		// TODO: Read from this group field or deprecate it.
		$ref->group = $group;
		$ref->name = $name;
		$ref->text = $text;

		if ( $follow ) {
			if ( !isset( $this->refs[$group][$follow] ) ) {
				// Mark an incomplete follow="…" as such. This is valid e.g. in the Page:… namespace
				// on Wikisource.
				$ref->follow = $follow;
				$ref->key = $this->nextRefSequence();
				$this->refs[$group][] = $ref;
				$this->refCallStack[] = [ self::ACTION_NEW, $ref->key, $group, $name, $text, $argv ];
			} elseif ( $text !== null ) {
				// We know the parent already, so just perform the follow="…" and bail out
				$this->resolveFollow( $group, $follow, $text );
			}
			// A follow="…" never gets its own footnote marker
			return null;
		}

		if ( !$name ) {
			// This is an anonymous reference, which will be given a numeric index.
			$this->refs[$group][] = &$ref;
			$ref->key = $this->nextRefSequence();
			$action = self::ACTION_NEW;
		} elseif ( !isset( $this->refs[$group][$name] ) ) {
			// Valid key with first occurrence
			$this->refs[$group][$name] = &$ref;
			$ref->key = $this->nextRefSequence();
			$action = self::ACTION_NEW;
		} elseif ( $this->refs[$group][$name]->placeholder ) {
			// Populate a placeholder.
			$ref->extendsCount = $this->refs[$group][$name]->extendsCount;
			$ref->key = $this->nextRefSequence();
			$ref->number = $this->refs[$group][$name]->number;
			$this->refs[$group][$name] =& $ref;
			$action = self::ACTION_NEW_FROM_PLACEHOLDER;
		} else {
			// Change an existing entry.
			$ref = &$this->refs[$group][$name];
			$ref->count++;

			if ( $ref->dir && $dir && $ref->dir !== $dir ) {
				$ref->warnings[] = [ 'cite_error_ref_conflicting_dir', $name ];
			}

			if ( $ref->text === null && $text !== null ) {
				// If no text was set before, use this text
				$ref->text = $text;
				// Use the dir parameter only from the full definition of a named ref tag
				$ref->dir = $dir;
				$action = self::ACTION_ASSIGN;
			} else {
				if ( $text !== null
					// T205803 different strip markers might hide the same text
					&& $stripState->unstripBoth( $text )
					!== $stripState->unstripBoth( $ref->text )
				) {
					// two refs with same name and different text
					$ref->warnings[] = [ 'cite_error_references_duplicate_key', $name ];
				}
				$action = self::ACTION_INCREMENT;
			}
		}

		$ref->number ??= ++$this->groupRefSequence[$group];

		// Do not mess with a known parent a second time
		if ( $extends && !isset( $ref->extendsIndex ) ) {
			$parentRef =& $this->refs[$group][$extends];
			if ( !isset( $parentRef ) ) {
				// Create a new placeholder and give it the current sequence number.
				$parentRef = new ReferenceStackItem();
				$parentRef->name = $extends;
				$parentRef->number = $ref->number;
				$parentRef->placeholder = true;
			} else {
				$ref->number = $parentRef->number;
				// Roll back the group sequence number.
				--$this->groupRefSequence[$group];
			}
			$parentRef->extendsCount ??= 0;
			$ref->extends = $extends;
			$ref->extendsIndex = ++$parentRef->extendsCount;
		} elseif ( $extends && $ref->extends !== $extends ) {
			// TODO: Change the error message to talk about "conflicting content or parent"?
			$ref->warnings[] = [ 'cite_error_references_duplicate_key', $name ];
		}

		$this->refCallStack[] = [ $action, $ref->key, $group, $name, $text, $argv ];
		return $ref;
	}

	/**
	 * Undo the changes made by the last $count ref tags.  This is used when we discover that the
	 * last few tags were actually inside of a references tag.
	 *
	 * @param int $count
	 *
	 * @return array[] Refs to restore under the correct context, as a list of [ $text, $argv ]
	 * @phan-return array<array{0:?string,1:array}>
	 */
	public function rollbackRefs( int $count ): array {
		$redoStack = [];
		while ( $count-- && $this->refCallStack ) {
			$call = array_pop( $this->refCallStack );
			if ( $call ) {
				// @phan-suppress-next-line PhanParamTooFewUnpack
				$redoStack[] = $this->rollbackRef( ...$call );
			}
		}

		// Drop unused rollbacks, this group is finished.
		$this->refCallStack = [];

		return array_reverse( $redoStack );
	}

	/**
	 * Partially undoes the effect of calls to stack()
	 *
	 * The option to define <ref> within <references> makes the
	 * behavior of <ref> context dependent.  This is normally fine
	 * but certain operations (especially #tag) lead to out-of-order
	 * parser evaluation with the <ref> tags being processed before
	 * their containing <reference> element is read.  This leads to
	 * stack corruption that this function works to fix.
	 *
	 * This function is not a total rollback since some internal
	 * counters remain incremented.  Doing so prevents accidentally
	 * corrupting certain links.
	 *
	 * @param string $action
	 * @param int $key Autoincrement counter for this ref.
	 * @param string $group
	 * @param ?string $name The name attribute passed in the ref tag.
	 * @param ?string $text
	 * @param array $argv
	 *
	 * @return array [ $text, $argv ] Ref redo item.
	 */
	private function rollbackRef(
		string $action,
		int $key,
		string $group,
		?string $name,
		?string $text,
		array $argv
	): array {
		if ( !$this->hasGroup( $group ) ) {
			throw new LogicException( "Cannot roll back ref with unknown group \"$group\"." );
		}

		$lookup = $name ?: null;
		if ( $lookup === null ) {
			// Find anonymous ref by key.
			foreach ( $this->refs[$group] as $k => $v ) {
				if ( $this->refs[$group][$k]->key === $key ) {
					$lookup = $k;
					break;
				}
			}
		}

		// Obsessive sanity checks that the specified element exists.
		if ( $lookup === null ) {
			throw new LogicException( "Cannot roll back unknown ref by key $key." );
		} elseif ( !isset( $this->refs[$group][$lookup] ) ) {
			throw new LogicException( "Cannot roll back missing named ref \"$lookup\"." );
		} elseif ( $this->refs[$group][$lookup]->key !== $key ) {
			throw new LogicException(
				"Cannot roll back corrupt named ref \"$lookup\" which should have had key $key." );
		}
		$ref =& $this->refs[$group][$lookup];

		switch ( $action ) {
			case self::ACTION_NEW:
				// Rollback the addition of new elements to the stack
				unset( $this->refs[$group][$lookup] );
				if ( !$this->refs[$group] ) {
					$this->popGroup( $group );
				} elseif ( isset( $this->groupRefSequence[$group] ) ) {
					$this->groupRefSequence[$group]--;
				}
				if ( $ref->extends ) {
					$this->refs[$group][$ref->extends]->extendsCount--;
				}
				break;
			case self::ACTION_NEW_FROM_PLACEHOLDER:
				$ref->placeholder = true;
				$ref->count = 0;
				break;
			case self::ACTION_ASSIGN:
				// Rollback assignment of text to pre-existing elements
				$ref->text = null;
				$ref->count--;
				break;
			case self::ACTION_INCREMENT:
				// Rollback increase in named ref occurrences
				$ref->count--;
				break;
			default:
				throw new LogicException( "Unknown call stack action \"$action\"" );
		}
		return [ $text, $argv ];
	}

	/**
	 * Clear state for a single group.
	 *
	 * @param string $group
	 *
	 * @return array<string|int,ReferenceStackItem> The references from the removed group
	 */
	public function popGroup( string $group ): array {
		$refs = $this->getGroupRefs( $group );
		unset( $this->refs[$group] );
		unset( $this->groupRefSequence[$group] );
		return $refs;
	}

	/**
	 * Returns true if the group exists and contains references.
	 */
	public function hasGroup( string $group ): bool {
		return isset( $this->refs[$group] ) && $this->refs[$group];
	}

	/**
	 * Returns a list of all groups with references.
	 *
	 * @return string[]
	 */
	public function getGroups(): array {
		$groups = [];
		foreach ( $this->refs as $group => $refs ) {
			if ( $refs ) {
				$groups[] = $group;
			}
		}
		return $groups;
	}

	/**
	 * Return all references for a group.
	 *
	 * @param string $group
	 *
	 * @return array<string|int,ReferenceStackItem>
	 */
	public function getGroupRefs( string $group ): array {
		return $this->refs[$group] ?? [];
	}

	private function resolveFollow( string $group, string $follow, string $text ): void {
		$previousRef =& $this->refs[$group][$follow];
		$previousRef->text ??= '';
		$previousRef->text .= " $text";
	}

	public function listDefinedRef( string $group, string $name, string $text ): void {
		$ref =& $this->refs[$group][$name];
		$ref ??= new ReferenceStackItem();
		$ref->placeholder = false;
		if ( !isset( $ref->text ) ) {
			$ref->text = $text;
		} elseif ( $ref->text !== $text ) {
			// two refs with same key and different content
			$ref->warnings[] = [ 'cite_error_references_duplicate_key', $name ];
		}
	}

	private function nextRefSequence() {
		return ++$this->refSequence;
	}

}
