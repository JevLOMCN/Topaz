<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants;

/**
 * This class contains the string how tags are written
 * Changing this will remove mathjax specifics.
 */
class Tag {
	public const ALIGN = "data-mjx-script-align";
	public const ALTERNATE = "data-mjx-alternate";
	public const SCRIPTTAG = "data-mjx-pseudoscript";

	// Example exchange value: "texClass"
	public const CLASSTAG = "data-mjx-texclass";

	// This is some tag in addition to mathvariant
	public const MJXVARIANT = "data-mjx-variant";
}
