<?php

namespace Wikimedia\RemexHtml\Tokenizer;

/**
 * This is the interface for handlers receiving events from the Tokenizer.
 * All events which consume characters give a source offset and length,
 * allowing for input stream patching. The offset and length are relative to
 * the preprocessed input, see Tokenizer::getPreprocessd
 */
interface TokenHandler {
	/**
	 * Called once at the start of the document (STATE_START)
	 *
	 * @param Tokenizer $tokenizer The Tokenizer which generated the event
	 * @param string|null $fragmentNamespace The fragment namespace, or null
	 *   to run in document mode.
	 * @param string|null $fragmentName The fragment tag name, or null to run
	 *   in document mode.
	 */
	public function startDocument( Tokenizer $tokenizer, $fragmentNamespace, $fragmentName );

	/**
	 * Called when the end of the input string is consumed
	 * @param int $pos The input position (past the end)
	 */
	public function endDocument( $pos );

	/**
	 * This is called for "parse errors" (as defined by the spec). The spec
	 * does not define names for error messages, so we just use some English
	 * text for now. The imagined audience is a developer reading validator
	 * output.
	 *
	 * @param string $text The error message
	 * @param int $pos The input position
	 */
	public function error( $text, $pos );

	/**
	 * A merged sequence of character tokens. We use the SAX-like convention of
	 * requiring the handler to do the substring operation, i.e. the actual
	 * text is substr( $text, $start, $length ), since this allows us to avoid
	 * some copying, at least if ignoreCharRefs and ignoreNulls are enabled.
	 *
	 * @param string $text The string which contains the emitted characters
	 * @param int $start The start of the range within $text to use
	 * @param int $length The length of the range within $text to use
	 * @param int $sourceStart The input position
	 * @param int $sourceLength The input length
	 */
	public function characters( $text, $start, $length, $sourceStart, $sourceLength );

	/**
	 * A start tag event. We call it a tag rather than an element since the
	 * start/end events are not balanced, so the relationship between tags
	 * and elements is complex. Errors emitted by attribute parsing will be
	 * not be received until $attrs is accessed by the handler.
	 *
	 * @param string $name The tag name
	 * @param Attributes $attrs The tag attributes
	 * @param bool $selfClose Whether there is a self-closing slash
	 * @param int $sourceStart The input position
	 * @param int $sourceLength The input length
	 */
	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength );

	/**
	 * An end tag event.
	 *
	 * @param string $name The tag name
	 * @param int $sourceStart The input position
	 * @param int $sourceLength The input length
	 */
	public function endTag( $name, $sourceStart, $sourceLength );

	/**
	 * A DOCTYPE declaration
	 *
	 * @param string|null $name The DOCTYPE name, or null if none was found
	 * @param string|null $public The public identifier, or null if none was found
	 * @param string|null $system The system identifier, or null if none was found
	 * @param bool $quirks What the spec calls the "force-quirks flag"
	 * @param int $sourceStart The input position
	 * @param int $sourceLength The input length
	 */
	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength );

	/**
	 * A comment.
	 *
	 * @param string $text The inner text of the comment
	 * @param int $sourceStart The input position
	 * @param int $sourceLength The input length
	 */
	public function comment( $text, $sourceStart, $sourceLength );
}
