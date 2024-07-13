<?php

/**
 * A parser extension that adds two tags, <ref> and <references> for adding
 * citations to pages
 *
 * @ingroup Extensions
 *
 * Documentation
 * @link https://www.mediawiki.org/wiki/Extension:Cite/Cite.php
 *
 * <cite> definition in HTML
 * @link http://www.w3.org/TR/html4/struct/text.html#edef-CITE
 *
 * <cite> definition in XHTML 2.0
 * @link http://www.w3.org/TR/2005/WD-xhtml2-20050527/mod-text.html#edef_text_cite
 *
 * @bug https://phabricator.wikimedia.org/T6579
 *
 * @author Ævar Arnfjörð Bjarmason <avarab@gmail.com>
 * @copyright Copyright © 2005, Ævar Arnfjörð Bjarmason
 * @license GPL-2.0-or-later
 */

namespace Cite;

use LogicException;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use Parser;
use StatusValue;

/**
 * @license GPL-2.0-or-later
 */
class Cite {

	public const DEFAULT_GROUP = '';

	/**
	 * Wikitext attribute name for Book Referencing.
	 */
	public const BOOK_REF_ATTRIBUTE = 'extends';

	/**
	 * Page property key for the Book Referencing `extends` attribute.
	 */
	public const BOOK_REF_PROPERTY = 'ref-extends';

	private bool $isSectionPreview;
	private FootnoteMarkFormatter $footnoteMarkFormatter;
	private ReferenceListFormatter $referenceListFormatter;
	private ErrorReporter $errorReporter;

	/**
	 * True when a <ref> tag is being processed.
	 * Used to avoid infinite recursion
	 */
	private bool $inRefTag = false;

	/**
	 * @var null|string The current group name while parsing nested <ref> in <references>. Null when
	 *  parsing <ref> outside of <references>. Warning, an empty string is a valid group name!
	 */
	private ?string $inReferencesGroup = null;

	/**
	 * Error stack used when defining refs in <references>
	 */
	private StatusValue $mReferencesErrors;
	private ReferenceStack $referenceStack;

	public function __construct( Parser $parser ) {
		$this->isSectionPreview = $parser->getOptions()->getIsSectionPreview();
		$messageLocalizer = new ReferenceMessageLocalizer( $parser->getContentLanguage() );
		$this->errorReporter = new ErrorReporter( $messageLocalizer );
		$this->mReferencesErrors = StatusValue::newGood();
		$this->referenceStack = new ReferenceStack();
		$anchorFormatter = new AnchorFormatter();
		$this->footnoteMarkFormatter = new FootnoteMarkFormatter(
			$this->errorReporter,
			$anchorFormatter,
			$messageLocalizer
		);
		$this->referenceListFormatter = new ReferenceListFormatter(
			$this->errorReporter,
			$anchorFormatter,
			$messageLocalizer
		);
	}

	/**
	 * Callback function for <ref>
	 *
	 * @param Parser $parser
	 * @param ?string $text Raw, untrimmed wikitext content of the <ref> tag, if any
	 * @param string[] $argv Arguments as given in <ref name=…>, already trimmed
	 *
	 * @return string|null Null in case a <ref> tag is not allowed in the current context
	 */
	public function ref( Parser $parser, ?string $text, array $argv ): ?string {
		if ( $this->inRefTag ) {
			return null;
		}

		$this->inRefTag = true;
		$ret = $this->guardedRef( $parser, $text, $argv );
		$this->inRefTag = false;

		return $ret;
	}

	/**
	 * @param Parser $parser
	 * @param ?string $text Raw, untrimmed wikitext content of the <ref> tag, if any
	 * @param string[] $argv Arguments as given in <ref name=…>, already trimmed
	 *
	 * @return string HTML
	 */
	private function guardedRef(
		Parser $parser,
		?string $text,
		array $argv
	): string {
		// Tag every page where Book Referencing has been used, whether or not the ref tag is valid.
		// This code and the page property will be removed once the feature is stable.  See T237531.
		if ( array_key_exists( self::BOOK_REF_ATTRIBUTE, $argv ) ) {
			$parser->getOutput()->setPageProperty( self::BOOK_REF_PROPERTY, '' );
		}

		$status = $this->parseArguments(
			$argv,
			[ 'group', 'name', self::BOOK_REF_ATTRIBUTE, 'follow', 'dir' ]
		);
		$arguments = $status->getValue();
		// Use the default group, or the references group when inside one.
		$arguments['group'] ??= $this->inReferencesGroup ?? self::DEFAULT_GROUP;

		$validator = new Validator(
			$this->referenceStack,
			$this->inReferencesGroup,
			$this->isSectionPreview,
			MediaWikiServices::getInstance()->getMainConfig()->get( 'CiteBookReferencing' )
		);
		// @phan-suppress-next-line PhanParamTooFewUnpack No good way to document it.
		$status->merge( $validator->validateRef( $text, ...array_values( $arguments ) ) );

		// Validation cares about the difference between null and empty, but from here on we don't
		if ( $text !== null && trim( $text ) === '' ) {
			$text = null;
		}

		if ( $this->inReferencesGroup !== null ) {
			if ( !$status->isGood() ) {
				// We know we are in the middle of a <references> tag and can't display errors in place
				$this->mReferencesErrors->merge( $status );
			} elseif ( $text !== null ) {
				// Validation made sure we always have group and name while in <references>
				$this->referenceStack->listDefinedRef( $arguments['group'], $arguments['name'], $text );
			}
			return '';
		}

		if ( !$status->isGood() ) {
			$this->referenceStack->pushInvalidRef();

			// FIXME: If we ever have multiple errors, these must all be presented to the user,
			//  so they know what to correct.
			// TODO: Make this nicer, see T238061
			return $this->errorReporter->firstError( $parser, $status );
		}

		// @phan-suppress-next-line PhanParamTooFewUnpack No good way to document it.
		$ref = $this->referenceStack->pushRef(
			$parser->getStripState(), $text, $argv, ...array_values( $arguments ) );
		return $ref
			? $this->footnoteMarkFormatter->linkRef( $parser, $arguments['group'], $ref )
			: '';
	}

	/**
	 * @param string[] $argv The argument vector
	 * @param string[] $allowedAttributes Allowed attribute names
	 *
	 * @return StatusValue Either an error, or has a value with the dictionary of field names and
	 * parsed or default values.  Missing attributes will be `null`.
	 */
	private function parseArguments( array $argv, array $allowedAttributes ): StatusValue {
		$expected = count( $allowedAttributes );
		$allValues = array_merge( array_fill_keys( $allowedAttributes, null ), $argv );
		if ( isset( $allValues['dir'] ) ) {
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullableInternal False positive
			$allValues['dir'] = strtolower( $allValues['dir'] );
		}

		$status = StatusValue::newGood( array_slice( $allValues, 0, $expected ) );

		if ( count( $allValues ) > $expected ) {
			// A <ref> must have a name (can be null), but <references> can't have one
			$status->fatal( in_array( 'name', $allowedAttributes, true )
				? 'cite_error_ref_too_many_keys'
				: 'cite_error_references_invalid_parameters'
			);
		}

		return $status;
	}

	/**
	 * Callback function for <references>
	 *
	 * @param Parser $parser
	 * @param ?string $text Raw, untrimmed wikitext content of the <references> tag, if any
	 * @param string[] $argv Arguments as given in <references …>, already trimmed
	 *
	 * @return string|null Null in case a <references> tag is not allowed in the current context
	 */
	public function references( Parser $parser, ?string $text, array $argv ): ?string {
		if ( $this->inRefTag || $this->inReferencesGroup !== null ) {
			return null;
		}

		$status = $this->parseArguments( $argv, [ 'group', 'responsive' ] );
		$arguments = $status->getValue();

		$this->inReferencesGroup = $arguments['group'] ?? self::DEFAULT_GROUP;

		$status->merge( $this->parseReferencesTagContent( $parser, $text ) );
		if ( !$status->isGood() ) {
			$ret = $this->errorReporter->firstError( $parser, $status );
		} else {
			$responsive = $arguments['responsive'];
			$ret = $this->formatReferences( $parser, $this->inReferencesGroup, $responsive );
			// Append errors collected while {@see parseReferencesTagContent} processed <ref> tags
			// in <references>
			$ret .= $this->formatReferencesErrors( $parser );
		}

		$this->inReferencesGroup = null;

		return $ret;
	}

	/**
	 * @param Parser $parser
	 * @param ?string $text Raw, untrimmed wikitext content of the <references> tag, if any
	 *
	 * @return StatusValue
	 */
	private function parseReferencesTagContent( Parser $parser, ?string $text ): StatusValue {
		// Nothing to parse in an empty <references /> tag
		if ( $text === null || trim( $text ) === '' ) {
			return StatusValue::newGood();
		}

		if ( preg_match( '{' . preg_quote( Parser::MARKER_PREFIX ) . '-(?i:references)-}', $text ) ) {
			return StatusValue::newFatal( 'cite_error_included_references' );
		}

		// Detect whether we were sent already rendered <ref>s. Mostly a side effect of using
		// {{#tag:references}}. The following assumes that the parsed <ref>s sent within the
		// <references> block were the most recent calls to <ref>. This assumption is true for
		// all known use cases, but not strictly enforced by the parser. It is possible that
		// some unusual combination of #tag, <references> and conditional parser functions could
		// be created that would lead to malformed references here.
		preg_match_all( '{' . preg_quote( Parser::MARKER_PREFIX ) . '-(?i:ref)-}', $text, $matches );
		$count = count( $matches[0] );

		// Undo effects of calling <ref> while unaware of being contained in <references>
		foreach ( $this->referenceStack->rollbackRefs( $count ) as $call ) {
			// Rerun <ref> call with the <references> context now being known
			$this->guardedRef( $parser, ...$call );
		}

		// Parse the <references> content to process any unparsed <ref> tags, but drop the resulting
		// HTML
		$parser->recursiveTagParse( $text );

		return StatusValue::newGood();
	}

	private function formatReferencesErrors( Parser $parser ): string {
		$html = '';
		foreach ( $this->mReferencesErrors->getErrors() as $error ) {
			if ( $html ) {
				$html .= "<br />\n";
			}
			$html .= $this->errorReporter->halfParsed( $parser, $error['message'], ...$error['params'] );
		}
		$this->mReferencesErrors = StatusValue::newGood();
		return $html ? "\n$html" : '';
	}

	/**
	 * @param Parser $parser
	 * @param string $group
	 * @param string|null $responsive Defaults to $wgCiteResponsiveReferences when not set
	 *
	 * @return string HTML
	 */
	private function formatReferences(
		Parser $parser,
		string $group,
		string $responsive = null
	): string {
		global $wgCiteResponsiveReferences;

		return $this->referenceListFormatter->formatReferences(
			$parser,
			$this->referenceStack->popGroup( $group ),
			$responsive !== null ? $responsive !== '0' : $wgCiteResponsiveReferences,
			$this->isSectionPreview
		);
	}

	/**
	 * Called at the end of page processing to append a default references
	 * section, if refs were used without a main references tag. If there are references
	 * in a custom group, and there is no references tag for it, show an error
	 * message for that group.
	 * If we are processing a section preview, this adds the missing
	 * references tags and does not add the errors.
	 *
	 * @param Parser $parser
	 * @param bool $isSectionPreview
	 *
	 * @return string HTML
	 */
	public function checkRefsNoReferences( Parser $parser, bool $isSectionPreview ): string {
		$s = '';
		foreach ( $this->referenceStack->getGroups() as $group ) {
			if ( $group === self::DEFAULT_GROUP || $isSectionPreview ) {
				$s .= $this->formatReferences( $parser, $group );
			} else {
				$s .= '<br />' . $this->errorReporter->halfParsed(
					$parser,
					'cite_error_group_refs_without_references',
					Sanitizer::safeEncodeAttribute( $group )
				);
			}
		}
		if ( $isSectionPreview && $s !== '' ) {
			$headerMsg = wfMessage( 'cite_section_preview_references' );
			if ( !$headerMsg->isDisabled() ) {
				$s = Html::element(
					'h2',
					[ 'id' => 'mw-ext-cite-cite_section_preview_references_header' ],
					$headerMsg->text()
				) . $s;
			}
			// provide a preview of references in its own section
			$s = Html::rawElement(
				'div',
				[ 'class' => 'mw-ext-cite-cite_section_preview_references' ],
				$s
			);
		}
		return $s !== '' ? "\n" . $s : '';
	}

	/**
	 * @see https://phabricator.wikimedia.org/T240248
	 * @return never
	 */
	public function __clone() {
		throw new LogicException( 'Create a new instance please' );
	}

}
