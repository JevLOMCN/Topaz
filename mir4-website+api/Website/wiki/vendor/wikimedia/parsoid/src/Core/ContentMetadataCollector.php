<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Core;

/**
 * Interface for collecting the results of a parse.
 *
 * This class is used by Parsoid to record metainformation about a
 * particular bit of parsed content which is extracted during the
 * parse.  This includes (for example) table of contents information,
 * and lists of links/categories/templates/images present in the
 * content.  Expected cache lifetime of this parsed content is also
 * recorded here, as it is influenced by certain things which may
 * be encountered during the parse.
 *
 * In core this is implemented by ParserOutput.  Core uses
 * ParserOutput to record the rendered HTML (and rendered table of
 * contents HTML), but on the Parsoid side we're going to keep
 * rendered HTML DOM out of this interface (we use PageBundle for
 * this).
 */
interface ContentMetadataCollector {
	/*
	 * Internal implementation notes:
	 * This class was refactored out of ParserOutput in core.
	 *
	 * == Deliberately omitted ==
	 * ::get*()/::has*() and other getters
	 *   This is a builder-only interface.  This also avoids ordering
	 *   issues if/when Parsoid passes this class to sub-parses/extensions.
	 * ::setSpeculativeRevIdUsed()
	 * ::setRevisionTimestampUsed()
	 * ::setRevisionUsedSha1Base36()
	 * ::setSpeculativePageIdUsed()
	 *   T292865: these should be plumbed through direct from ParserOptions
	 *   or use the ::setOutputFlag() or addOutputData() mechanism.
	 * ::setTimestamp()
	 *   This is used by ParserCache and is a little optimization used to
	 *   show the correct 'article was last edited on blablablah' box on
	 *   page views.  Parsoid shouldn't need to worry about this; probably
	 *   part of T292865.
	 * ::addCacheMessage()
	 *   This is marked @internal in core.
	 *   Not clear yet whether Parsoid needs this.
	 * ::getText()/::setText()
	 *   T293512: rendered HTML doesn't belong in ParserOutput
	 * ::addWrapperDivClass()/::clearWrapperDivClass()
	 *   Has to do with ::getText() implementation, see above
	 * ::setTitleText()
	 *   Omited because it contains rendered HTML
	 *   (should become a method which takes a DOM tree instead?)
	 * ::setTOCHTML()
	 *   Omitted because it contains rendered HTML.
	 *   T293513 will remove this method from ParserOutput
	 * ::addHeadItem()
	 *   Not clear this is needed by Parsoid (but maybe some of the stuff
	 *   Parsoid adds to head could be refactored to use this interface).
	 *   Should be DOM not string data!
	 * ::addOutputPageMetadata()
	 *   OutputPage isn't a Parsoid interface, so this shouldn't be needed
	 *   by Parsoid.
	 * ::setDisplayTitle()
	 *   T293514: This desugars to calls to two other methods in
	 *   ContentOutputBuilder; callers can refactor to invoke those directly.
	 * ::unsetPageProperty()
	 *   If parse fragment A is setting a property
	 *   and parse fragment B is unsetting the property, we've introduced
	 *   an ordering dependency. We'd like to avoid that code pattern.
	 * ::resetParseStartTime()/::getTimeSinceStart()
	 *   Not needed by parsoid?
	 * ::finalizeAdaptiveCacheExpiry()
	 *   Same as above, can probably be invoked by caller of parsoid,
	 *   doesn't need to be in Parsoid library code.
	 * ::mergeInternalMetaDataFrom()
	 * ::mergeHtmlMetaDataFrom()
	 * ::mergeTrackingMetaDataFrom()
	 *   Rather than explicitly merging ContentMetadataCollectors, we'd
	 *   prefer to pass a single ContentOutputBuilder around to accumulate
	 *   results.  We're going to wait and see to what extent methods like
	 *   this are necessary.
	 *   (ParserOutput will implement a ::mergeTo(ContentMetadataCollector)
	 *   method, as it has read access to its own contents.)
	 * ::setNoGallery()/::setEnableOOUI()/::setNewSection()/::setHideNewSection()
	 * ::setPreventClickjacking()/::setIndexPolicy()/
	 *   Available via ::setOutputFlag() (see T292868)
	 * ::setCategories()
	 *   Doesn't seem necessary, we have ::addCategory().
	 *   (And adding the ability to overwrite categories would be bad.)
	 * ::addTrackingCategory()
	 *   This was moved to Parser / the TrackingCategories service, but
	 *   perhaps it would be helpful if we had a version of this available
	 *   from SiteConfig or something.
	 * ::isLinkInternal()
	 *   T296036: Should be non-public or at least @internal?
	 * ::addInterwikiLink()
	 *   invoked from ::addLink() if the link is external, we don't
	 *   need a separate entry point.
	 * ::setSections()
	 *   T296025: replaced with ::setTOCData()
	 * ::setLanguageLinks()
	 *   Deprecated; replaced with ::addLanguageLink()
	 * ::addExtraCSPDefaultSrc()
	 * ::addExtraCSPStyleSrc()
	 * ::addExtraCSPScriptSrc()
	 * ::updateRuntimeAdaptiveExpiry()
	 *   T296345: handled through ::appendOutputStrings()
	 *
	 * == Temporarily omitted ==
	 * ::addTemplate()
	 *   T296038: Requires page id and revision id.  In addition, this
	 *   interacts with user hooks.  The MediaWiki side should probably be
	 *   responsible for updating the Template dependencies not Parsoid.
	 *   OTOH, we need to return *something* like a Title back because
	 *   eventually Parsoid has to fetch the template to expand it.
	 * ::setTitleText()
	 *   T293514: This contains the title in HTML and is redundant with
	 *   ::setDisplayTitle()
	 */

	/**
	 * Merge strategy to use for ContentMetadataCollector
	 * accumulators: "union" means that values are strings, stored as
	 * a set, and exposed as a PHP associative array mapping from
	 * values to `true`.
	 *
	 * This constant should be treated as @internal until we expose
	 * alternative merge strategies for external use.
	 * @internal
	 */
	public const MERGE_STRATEGY_UNION = 'union';

	/**
	 * Add a category, with the given sort key.
	 *
	 * @param LinkTarget $c Category name
	 * @param string $sort Sort key (pass the empty string to use the default)
	 */
	public function addCategory( $c, $sort = '' ): void;

	/**
	 * Record a local or interwiki inline link for saving in future link tables.
	 *
	 * @param LinkTarget $link (used to require Title until 1.38)
	 * @param int|null $id Optional known page_id so we can skip the lookup
	 *   (generally not used by Parsoid)
	 */
	public function addLink( LinkTarget $link, $id = null ): void;

	/**
	 * Register a file dependency for this output
	 * @param LinkTarget $name Title dbKey
	 * @param string|false|null $timestamp MW timestamp of file creation (or false if non-existing)
	 * @param string|false|null $sha1 Base 36 SHA-1 of file (or false if non-existing)
	 */
	public function addImage( LinkTarget $name, $timestamp = null, $sha1 = null ): void;

	/**
	 * Add a language link.
	 * @param LinkTarget $lt
	 */
	public function addLanguageLink( LinkTarget $lt ): void;

	/**
	 * Add a warning to the output for this page.
	 * @param string $msg The localization message key for the warning
	 * @param mixed ...$args Optional arguments for the message
	 */
	public function addWarningMsg( string $msg, ...$args ): void;

	/**
	 * @param string $url External link URL
	 */
	public function addExternalLink( string $url ): void;

	/**
	 * Provides a uniform interface to various boolean flags stored
	 * in the content metadata.  Flags internal to MediaWiki core should
	 * have names which are constants in ParserOutputFlags.  Extensions
	 * should use ::setExtensionData() rather than creating new flags
	 * with ::setOutputFlag() in order to prevent namespace conflicts.
	 *
	 * @param string $name A flag name
	 * @param bool $val
	 */
	public function setOutputFlag( string $name, bool $val = true ): void;

	/**
	 * Provides a uniform interface to various appendable lists of strings
	 * stored in the content metadata. Strings internal to MediaWiki core should
	 * have names which are constants in ParserOutputStrings.  Extensions
	 * should use ::setExtensionData() rather than creating new keys here
	 * in order to prevent namespace conflicts.
	 *
	 * @param string $name A string name
	 * @param string[] $value
	 */
	public function appendOutputStrings( string $name, array $value ): void;

	/**
	 * Set a page property to be stored in the page_props database table.
	 *
	 * page_props is a key-value store indexed by the page ID. This allows
	 * the parser to set a property on a page which can then be quickly
	 * retrieved given the page ID or via a DB join when given the page
	 * title.
	 *
	 * Since 1.23, page_props are also indexed by numeric value, to allow
	 * for efficient "top k" queries of pages wrt a given property.
	 * This only works if the value is passed as a int, float, or
	 * bool. Since 1.42 you should use ::setNumericPageProperty()
	 * if you want your page property value to be indexed, which will ensure
	 * that the value is of the proper type.
	 *
	 * setPageProperty() is thus used to propagate properties from the parsed
	 * page to request contexts other than a page view of the currently parsed
	 * article.
	 *
	 * Some applications examples:
	 *
	 *   * To implement hidden categories, hiding pages from category listings
	 *     by storing a page property.
	 *
	 *   * Overriding the displayed article title (ParserOutput::setDisplayTitle()).
	 *
	 *   * To implement image tagging, for example displaying an icon on an
	 *     image thumbnail to indicate that it is listed for deletion on
	 *     Wikimedia Commons.
	 *     This is not actually implemented, yet but would be pretty cool.
	 *
	 * @note Use of non-scalar values (anything other than
	 *  `string|int|float|bool`) has been deprecated in 1.42.
	 *  Although any JSON-serializable value can be stored/fetched in
	 *  ParserOutput, when the values are stored to the database
	 *  (in `deferred/LinksUpdate/PagePropsTable.php`) they will be
	 *  converted: booleans will be converted to '0' and '1', null
	 *  will become '', and everything else will be cast to string
	 *  (not JSON-serialized).  Page properties obtained from the
	 *  PageProps service will thus always be strings.
	 *
	 * @note The sort key stored in the database *will be NULL* unless
	 *  the value passed here is an `int|float|bool`.  If you *do not*
	 *  want your property *value* indexed and sorted (for example, the
	 *  value is a title string which can be numeric but only
	 *  incidentally, like when it gets retrieved from an array key)
	 *  be sure to cast to string or use
	 *  `::setUnsortedPageProperty()`.  If you *do* want your property
	 *  *value* indexed and sorted, you should use
	 *  `::setNumericPageProperty()` instead as this will ensure the
	 *  value type is correct. Note that either way it is possible to
	 *  efficiently look up all the pages with a certain property; we
	 *  are only talking about sorting the *values* assigned to the
	 *  property, for example for a "top N values of the property"
	 *  query.
	 *
	 * @note Note that `::getPageProperty()`/`::setPageProperty()` do
	 *  not do any conversions themselves; you should therefore be
	 *  careful to distinguish values returned from the PageProp
	 *  service (always strings) from values retrieved from a
	 *  ParserOutput.
	 *
	 * @note Do not use setPageProperty() to set a property which is only used
	 * in a context where the ParserOutput object itself is already available,
	 * for example a normal page view. There is no need to save such a property
	 * in the database since the text is already parsed; use
	 * ::setExtensionData() instead.
	 *
	 * @par Example:
	 * @code
	 *    $parser->getOutput()->setExtensionData( 'my_ext_foo', '...' );
	 * @endcode
	 *
	 * And then later, in OutputPageParserOutput or similar:
	 *
	 * @par Example:
	 * @code
	 *    $output->getExtensionData( 'my_ext_foo' );
	 * @endcode
	 *
	 * @note The use of `null` as a value is deprecated since 1.42; use
	 * the empty string instead if you need a placeholder value, or
	 * ::unsetPageProperty() if you mean to remove a page property.
	 *
	 * @note The use of non-string values is deprecated since 1.42; if you
	 * need an page property value with a sort index
	 * use ::setNumericPageProperty().
	 *
	 * @param string $name
	 * @param int|float|string|bool|null $value
	 * @since 1.38
	 */
	public function setPageProperty( string $name, $value ): void;

	/**
	 * Set a numeric page property whose *value* is intended to be sorted
	 * and indexed.  The sort key used for the property will be the value,
	 * coerced to a number.
	 *
	 * See `::setPageProperty()` for details.
	 *
	 * In the future, we may allow the value to be specified independent
	 * of sort key (T357783).
	 *
	 * @param string $propName The name of the page property
	 * @param int|float|string $numericValue the numeric value
	 * @since 1.42
	 */
	public function setNumericPageProperty( string $propName, $numericValue ): void;

	/**
	 * Set a page property whose *value* is not intended to be sorted and
	 * indexed.
	 *
	 * See `::setPageProperty()` for details.  It is recommended to
	 * use the empty string if you need a placeholder value (ie, if
	 * it is the *presence* of the property which is important, not
	 * the *value* the property is set to).
	 *
	 * It is still possible to efficiently look up all the pages with
	 * a certain property (the "presence" of it *is* indexed; see
	 * Special:PagesWithProp, list=pageswithprop).
	 *
	 * @param string $propName The name of the page property
	 * @param string $value Optional value; defaults to the empty string.
	 * @since 1.42
	 */
	public function setUnsortedPageProperty( string $propName, string $value = '' ): void;

	/**
	 * Attaches arbitrary data to this content. This can be used to
	 * store some information for later use during page output. The
	 * data will be cached along with the parsed page, but unlike data
	 * set using setPageProperty(), it is not recorded in the
	 * database.
	 *
	 * To use setExtensionData() to pass extension information from a
	 * hook inside the parser to a hook in the page output, use this
	 * in the parser hook:
	 *
	 * @par Example:
	 * @code
	 *    $parser->getOutput()->setExtensionData( 'my_ext_foo', '...' );
	 * @endcode
	 *
	 * And then later, in OutputPageParserOutput or similar:
	 *
	 * @par Example:
	 * @code
	 *    $output->getExtensionData( 'my_ext_foo' );
	 * @endcode
	 *
	 * @note Only scalar values, e.g. numbers, strings, arrays or
	 * MediaWiki\Json\JsonUnserializable instances are supported as a
	 * value. Attempt to set other class instance as a extension data
	 * will break ParserCache for the page.
	 *
	 * @note As with ::setJsConfigVar(), setting a page property to multiple
	 * conflicting values during the parse is not supported.
	 *
	 * @param string $key The key for accessing the data. Extensions
	 *   should take care to avoid conflicts in naming keys. It is
	 *   suggested to use the extension's name as a prefix.  Keys
	 *   beginning with `mw-` are reserved for use by mediawiki core.
	 *
	 * @param mixed $value The value to set.
	 *   Setting a value to null is equivalent to removing the value.
	 */
	public function setExtensionData( string $key, $value ): void;

	/**
	 * Appends arbitrary data to this ParserObject. This can be used
	 * to store some information in the ParserOutput object for later
	 * use during page output. The data will be cached along with the
	 * ParserOutput object, but unlike data set using
	 * setPageProperty(), it is not recorded in the database.
	 *
	 * See ::setExtensionData() for more details on rationale and use.
	 *
	 * In order to provide for out-of-order/asynchronous/incremental
	 * parsing, this method appends values to a set.  See
	 * ::setExtensionData() for the flag-like version of this method.
	 *
	 * @note Only values which can be array keys are currently supported
	 * as values.  Be aware that array keys which 'look like' numbers are
	 * converted to ints by PHP, and so if you put in `"0"` as a value you
	 * will get `[0=>true]` out.
	 *
	 * @param string $key The key for accessing the data. Extensions should take care to avoid
	 *   conflicts in naming keys. It is suggested to use the extension's name as a prefix.
	 *
	 * @param int|string $value The value to append to the list.
	 * @param string $strategy Merge strategy:
	 *  only MW_MERGE_STRATEGY_UNION is currently supported and external callers
	 *  should treat this parameter as @internal at this time and omit it.
	 */
	public function appendExtensionData(
		string $key,
		$value,
		string $strategy = self::MERGE_STRATEGY_UNION
	): void;

	/**
	 * Add a variable to be set in mw.config in JavaScript.
	 *
	 * In order to ensure the result is independent of the parse order, the values
	 * set here must be unique -- that is, you can pass the same $key
	 * multiple times but ONLY if the $value is identical each time.
	 * If you want to collect multiple pieces of data under a single key,
	 * use ::appendJsConfigVar().
	 *
	 * @param string $key Key to use under mw.config
	 * @param mixed|null $value Value of the configuration variable.
	 */
	public function setJsConfigVar( string $key, $value ): void;

	/**
	 * Append a value to a variable to be set in mw.config in JavaScript.
	 *
	 * In order to ensure the result is independent of the parse order,
	 * the value of this key will be an associative array, mapping all of
	 * the values set under that key to true.  (The array is implicitly
	 * ordered in PHP, but you should treat it as unordered.)
	 * If you want a non-array type for the key, and can ensure that only
	 * a single value will be set, you should use ::setJsConfigVar() instead.
	 *
	 * @note Only values which can be array keys are currently supported
	 * as values.  Be aware that array keys which 'look like' numbers are
	 * converted to ints by PHP, and so if you put in `"0"` as a value you
	 * will get `[0=>true]` out.
	 *
	 * @param string $key Key to use under mw.config
	 * @param string $value Value to append to the configuration variable.
	 * @param string $strategy Merge strategy:
	 *  only MW_MERGE_STRATEGY_UNION is currently supported and external callers
	 *  should treat this parameter as @internal at this time and omit it.
	 */
	public function appendJsConfigVar(
		string $key,
		string $value,
		string $strategy = self::MERGE_STRATEGY_UNION
	): void;

	/**
	 * @see OutputPage::addModules
	 * @param string[] $modules
	 */
	public function addModules( array $modules ): void;

	/**
	 * @see OutputPage::addModuleStyles
	 * @param string[] $modules
	 */
	public function addModuleStyles( array $modules ): void;

	/**
	 * Sets parser limit report data for a key
	 *
	 * The key is used as the prefix for various messages used for formatting:
	 *  - $key: The label for the field in the limit report
	 *  - $key-value-text: Message used to format the value in the "NewPP limit
	 *      report" HTML comment. If missing, uses $key-format.
	 *  - $key-value-html: Message used to format the value in the preview
	 *      limit report table. If missing, uses $key-format.
	 *  - $key-value: Message used to format the value. If missing, uses "$1".
	 *
	 * Note that all values are interpreted as wikitext, and so should be
	 * encoded with htmlspecialchars() as necessary, but should avoid complex
	 * HTML for sanity of display in the "NewPP limit report" comment.
	 *
	 * @param string $key Message key
	 * @param mixed $value Appropriate for Message::params()
	 */
	public function setLimitReportData( string $key, $value ): void;

	/**
	 * Sets Table of Contents data for this page.
	 *
	 * Note that merging of TOCData is not supported; exactly one fragment
	 * should set TOCData.
	 *
	 * @param TOCData $tocData
	 */
	public function setTOCData( TOCData $tocData ): void;

	/**
	 * Set the content for an indicator.
	 *
	 * @param string $name
	 * @param string $content
	 * @param-taint $content exec_html
	 */
	public function setIndicator( $name, $content ): void;

	/**
	 * @return array<string,string>
	 */
	public function getIndicators(): array;
}
