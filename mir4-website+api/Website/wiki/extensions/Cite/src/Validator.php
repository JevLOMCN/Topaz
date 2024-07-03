<?php

namespace Cite;

use MediaWiki\Parser\Sanitizer;
use StatusValue;

/**
 * Context-aware, detailed validation of the arguments and content of a <ref> tag.
 *
 * @license GPL-2.0-or-later
 */
class Validator {

	private ReferenceStack $referenceStack;
	private ?string $inReferencesGroup;
	private bool $isSectionPreview;
	private bool $isExtendsEnabled;

	/**
	 * @param ReferenceStack $referenceStack
	 * @param string|null $inReferencesGroup Group name of the <references> context to consider
	 *  during validation. Null if we are currently not in a <references> context.
	 * @param bool $isSectionPreview Validation is relaxed when previewing parts of a page
	 * @param bool $isExtendsEnabled Temporary feature flag
	 */
	public function __construct(
		ReferenceStack $referenceStack,
		?string $inReferencesGroup = null,
		bool $isSectionPreview = false,
		bool $isExtendsEnabled = false
	) {
		$this->referenceStack = $referenceStack;
		$this->inReferencesGroup = $inReferencesGroup;
		$this->isSectionPreview = $isSectionPreview;
		$this->isExtendsEnabled = $isExtendsEnabled;
	}

	public function validateRef(
		?string $text,
		string $group,
		?string $name,
		?string $extends,
		?string $follow,
		?string $dir
	): StatusValue {
		if ( ctype_digit( (string)$name )
			|| ctype_digit( (string)$extends )
			|| ctype_digit( (string)$follow )
		) {
			// Numeric names mess up the resulting id's, potentially producing
			// duplicate id's in the XHTML.  The Right Thing To Do
			// would be to mangle them, but it's not really high-priority
			// (and would produce weird id's anyway).
			return StatusValue::newFatal( 'cite_error_ref_numeric_key' );
		}

		if ( $extends ) {
			// Temporary feature flag until mainstreamed, see T236255
			if ( !$this->isExtendsEnabled ) {
				return StatusValue::newFatal( 'cite_error_ref_too_many_keys' );
			}

			$groupRefs = $this->referenceStack->getGroupRefs( $group );
			// @phan-suppress-next-line PhanTypeMismatchDimFetchNullable false positive
			if ( isset( $groupRefs[$name] ) && !isset( $groupRefs[$name]->extends ) ) {
				// T242141: A top-level <ref> can't be changed into a sub-reference
				return StatusValue::newFatal( 'cite_error_references_duplicate_key', $name );
			} elseif ( isset( $groupRefs[$extends]->extends ) ) {
				// A sub-reference can not be extended a second time (no nesting)
				return StatusValue::newFatal( 'cite_error_ref_nested_extends', $extends,
					$groupRefs[$extends]->extends );
			}
		}

		if ( $follow && ( $name || $extends ) ) {
			return StatusValue::newFatal( 'cite_error_ref_follow_conflicts' );
		}

		if ( $dir !== null && $dir !== 'rtl' && $dir !== 'ltr' ) {
			return StatusValue::newFatal( 'cite_error_ref_invalid_dir', $dir );
		}

		return $this->inReferencesGroup === null ?
			$this->validateRefOutsideOfReferenceList( $text, $name ) :
			$this->validateRefInReferenceList( $text, $group, $name );
	}

	private function validateRefOutsideOfReferenceList(
		?string $text,
		?string $name
	): StatusValue {
		if ( !$name ) {
			if ( $text === null ) {
				// Completely empty ref like <ref /> is forbidden.
				return StatusValue::newFatal( 'cite_error_ref_no_key' );
			} elseif ( trim( $text ) === '' ) {
				// Must have content or reuse another ref by name.
				return StatusValue::newFatal( 'cite_error_ref_no_input' );
			}
		}

		if ( $text !== null && preg_match(
				'/<ref(erences)?\b[^>]*+>/i',
				preg_replace( '#<(\w++)[^>]*+>.*?</\1\s*>|<!--.*?-->#s', '', $text )
			) ) {
			// (bug T8199) This most likely implies that someone left off the
			// closing </ref> tag, which will cause the entire article to be
			// eaten up until the next <ref>.  So we bail out early instead.
			// The fancy regex above first tries chopping out anything that
			// looks like a comment or SGML tag, which is a crude way to avoid
			// false alarms for <nowiki>, <pre>, etc.
			//
			// Possible improvement: print the warning, followed by the contents
			// of the <ref> tag.  This way no part of the article will be eaten
			// even temporarily.
			return StatusValue::newFatal( 'cite_error_included_ref' );
		}

		return StatusValue::newGood();
	}

	private function validateRefInReferenceList(
		?string $text,
		string $group,
		?string $name
	): StatusValue {
		if ( $group !== $this->inReferencesGroup ) {
			// <ref> and <references> have conflicting group attributes.
			return StatusValue::newFatal( 'cite_error_references_group_mismatch',
				Sanitizer::safeEncodeAttribute( $group ) );
		}

		if ( !$name ) {
			// <ref> calls inside <references> must be named
			return StatusValue::newFatal( 'cite_error_references_no_key' );
		}

		if ( $text === null || trim( $text ) === '' ) {
			// <ref> called in <references> has no content.
			return StatusValue::newFatal(
				'cite_error_empty_references_define',
				Sanitizer::safeEncodeAttribute( $name ),
				Sanitizer::safeEncodeAttribute( $group )
			);
		}

		// Section previews are exempt from some rules.
		if ( !$this->isSectionPreview ) {
			$groupRefs = $this->referenceStack->getGroupRefs( $group );

			if ( !isset( $groupRefs[$name] ) ) {
				// No such named ref exists in this group.
				return StatusValue::newFatal( 'cite_error_references_missing_key',
					Sanitizer::safeEncodeAttribute( $name ) );
			}
		}

		return StatusValue::newGood();
	}

}
