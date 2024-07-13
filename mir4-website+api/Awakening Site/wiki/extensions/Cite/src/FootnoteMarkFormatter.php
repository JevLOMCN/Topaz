<?php

namespace Cite;

use MediaWiki\Parser\Sanitizer;
use Parser;

/**
 * Footnote markers in the context of the Cite extension are the numbers in the article text, e.g.
 * [1], that can be hovered or clicked to be able to read the attached footnote.
 *
 * @license GPL-2.0-or-later
 */
class FootnoteMarkFormatter {

	/** @var array<string,string[]> In-memory cache for the cite_link_label_group-… link label lists */
	private array $linkLabels = [];

	private AnchorFormatter $anchorFormatter;
	private ErrorReporter $errorReporter;
	private ReferenceMessageLocalizer $messageLocalizer;

	public function __construct(
		ErrorReporter $errorReporter,
		AnchorFormatter $anchorFormatter,
		ReferenceMessageLocalizer $messageLocalizer
	) {
		$this->anchorFormatter = $anchorFormatter;
		$this->errorReporter = $errorReporter;
		$this->messageLocalizer = $messageLocalizer;
	}

	/**
	 * Generate a link (<sup ...) for the <ref> element from a key
	 * and return XHTML ready for output
	 *
	 * @suppress SecurityCheck-DoubleEscaped
	 * @param Parser $parser
	 * @param string $group
	 * @param ReferenceStackItem $ref
	 *
	 * @return string
	 */
	public function linkRef( Parser $parser, string $group, ReferenceStackItem $ref ): string {
		$label = $this->fetchCustomizedLinkLabel( $parser, $group, $ref->number );
		if ( $label === null ) {
			$label = $this->messageLocalizer->localizeDigits( (string)$ref->number );
			if ( $group !== Cite::DEFAULT_GROUP ) {
				$label = "$group $label";
			}
		}
		if ( isset( $ref->extendsIndex ) ) {
			$label .= '.' . $this->messageLocalizer->localizeDigits( (string)$ref->extendsIndex );
		}

		$key = $ref->name ?? $ref->key;
		// TODO: Use count without decrementing.
		$count = $ref->name ? $ref->key . '-' . ( $ref->count - 1 ) : null;
		$subkey = $ref->name ? '-' . $ref->key : null;

		return $parser->recursiveTagParse(
			$this->messageLocalizer->msg(
				'cite_reference_link',
				$this->anchorFormatter->backLinkTarget( $key, $count ),
				$this->anchorFormatter->jumpLink( $key . $subkey ),
				Sanitizer::safeEncodeAttribute( $label )
			)->plain()
		);
	}

	/**
	 * Generate a custom format link for a group given an offset, e.g.
	 * the second <ref group="foo"> is b if $this->mLinkLabels["foo"] =
	 * [ 'a', 'b', 'c', ...].
	 * Return an error if the offset > the # of array items
	 *
	 * @param Parser $parser
	 * @param string $group The group name
	 * @param int $number Expected to start at 1
	 *
	 * @return string|null Returns null if no custom labels for this group exist
	 */
	private function fetchCustomizedLinkLabel( Parser $parser, string $group, int $number ): ?string {
		if ( $group === Cite::DEFAULT_GROUP ) {
			return null;
		}

		$message = "cite_link_label_group-$group";
		if ( !array_key_exists( $group, $this->linkLabels ) ) {
			$msg = $this->messageLocalizer->msg( $message );
			$this->linkLabels[$group] = $msg->isDisabled() ? [] : preg_split( '/\s+/', $msg->plain() );
		}

		// Expected behavior for groups without custom labels
		if ( !$this->linkLabels[$group] ) {
			return null;
		}

		// Error message in case we run out of custom labels
		return $this->linkLabels[$group][$number - 1] ?? $this->errorReporter->plain(
			$parser,
			'cite_error_no_link_label_group',
			$group,
			$message
		);
	}

}
