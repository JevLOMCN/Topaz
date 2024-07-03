0.11.0 / 2019-12-20
===================
This is the LAST release of (the JS implementation of) Parsoid. Parsoid has been
ported from JS to PHP and will be more closely integrated into MediaWiki and will
not need its own debian package henceforth.

This release contains the entire working port of Parsoid to PHP, but we are not
providing any support for this setup. By the next release of MediaWiki, Parsoid
will be better integrated there and will be available as part of MediaWiki.

  Notable changes
  * Parsoid HTML version bumped to 2.1.0
  * Code cleanup
    - Use ES6 class syntax with one class export per file
    - Removed dead code
    - Removed most uses of .bind()
    - Removed circular dependencies
    - Replaced promises with async/yield in most places in the codebase
  * Language converter
    - Bulk of the code moved out of Parsoid into its own repo
    - Crimean Tatar, zh language converters added
  * Code improvements
    - Extract a token transform interface that individual handlers implement
    - Extract a DOMHandler interface for html2wt DOM handlers
  * Move media info code to a post-processing batch pass to eliminate
    imageinfo API requests during token processing
  * Move data out of DOM nodes into a bag-on-the-side
  * Eliminate serialize/parse of JSON data attributes when tree building
  * Move redlink and language converter code into lib/parse.js - Parsoid's
    entry point class
  * Use wikipeg (our erstwhile pegjs fork) native rule parameters and get rid of
    our homegrown stop/flag stacks and update PEG grammar
  * Ensure protocol-relative URLs are used for media
  * A number of bug fixes during the port of Parsoid from JS to PHP

0.10.0 / 2018-12-05
===================
  Notable wt -&gt; html changes
  * Parsoid HTML version bumped to 2.0.0
  * Support for the Content Negotiation Protocol for negotiating HTML content versions
  * Implement RFC T157418: Trim whitespace in wikitext headings, lists, tables
  * Added support for template styles
  * Expose content inside &lt;includeonly&gt; to editors via data-mw attribute
  * Support directionality for references
  * A bunch of cleanup and bug fixes in paragraph wrapping for improved compliance
    with PHP parser output. Move to DOM based p-wrapping of unwrapped bare text
  * Remove `html5-legacy` mode of ID generation
  * Media-related:
    - Use &lt;audio&gt; elements for rendering audio files
    - Use `resource` attribute for [[Media:....]] links
    - Image alt and link options can contain arbitrary wikitext which is stripped
    - Support more link types in file alt/link options
    - Increase the default height of mw:Audio to 32px
    - Stop adding valign classes to block media
    - Parse more block constructs in media captions
  * A number of bug fixes and crasher fixes

  Notable html -&gt; wt changes:
  * Scrub DOM to convert &lt;p&gt;&lt;/p&gt; sequences to NL-emitting normal forms
    This now translates empty lines entered in VE to empty lines in wikitext
  * Add new lines before and after lists in wikitext
  * Improve templatedata spec compliance wrt leading and trailing newlines
  * Avoid piped links in more cases by moving formatting outside the link
  * Handle | chars in hrefs
  * Handle } in table cells
  * Serialize empty table cells with a single whitespace character
  * Don't force paragraphs inside blockquotes to serialize on a new line
  * Distinguish between inserted & deleted diff markers

  Infrastructure:
  * Make the Sanitizer "static" and decouple it from the parsing pipeline
  * Removed &lt; node v6 compatibility
  * Migrate to jsdoc instead of jsduck for documentation
  * Allow users to dynamically configure new wikis
  * Addressed nsp-triggered security advisories
  * Updated domino and other dependencies

  Extensions
  * Cleanup of the extension API to reduce exposure of Parsoid internals
  * Migrated native Parsoid extensions to the updated extension API
  * Native Parsoid implementation of &lt;poem&gt;

  Performance fixes:
  * Mostly minor tweaks:
    - Performance improvements in the TokenTransformManager
    - Add a fast path to avoid unnecessarily retokenizing the extlink href
    - Test for a valid protocol before attempting to tokenize extlink content
    - Ensure ref.cachedHtml isn't being regenerated needlessly
    - Suppress autoInsertedEnd flags where not required

  Cleanup:
  * Split up large utility classes into smaller functional groups
  * More native ES6 classes
  * More native ES6 syntax (let, const, yield)
  * A whole bunch of dead code removed
  * Cleanup return types in Token Transformers

0.9.0 / 2018-03-23
==================
  Notable wt -&gt; html changes
  * Parsoid HTML version bumped to 1.6.1
  * T114072: Add &lt;section&gt; wrappers to Parsoid output
  * T118520: Use figure-inline instead of span for inline media
  * Update Parsoid to generate modern HTML5 IDs w/ legacy fallback
  * T58756: External links class= now setting free, text and autonumber
  * T45094: Replace &lt;span&gt; with &lt;sup&gt; for references
  * T97093: Use mw:WikiLink/Interwiki for interwiki links
  * Permit extension tags in xmlish attribute values
  * A number of bug fixes and crasher fixes

  Notable html -&gt; wt changes:
  * Preserve original transclusion's parameter order
  * T180930: Selser shouldn't reuse orig sep for autoinserted tags

  Infrastructure:
  * This release requires clients (VE, etc.) to return a 1.6.0 and
    greater HTML version string in the header. If not, Parsoid will
    return a HTTP 406. This can be fixed by updating VE (or relevant
    clients) to a more recent version.
  * T66003: Make strictSSL configurable per wiki as well
  * Use pure compute workers for the request processing
  * T123446: Bring back request timeouts
  * Lots of changes to wikitext linting code including new
    linter categories.

  Extensions
  * Match core's parsing of gallery dimensions
  * Added &lt;section&gt; and &lt;indicator&gt; extension handling.

  Performance fixes:
  * Don't process token attributes unnecessarily
  * T176728: Use replaceChild instead of insertBefore
  * Performance fixes to domino, the html + dom library used in Parsoid

  Dependencies:
  * Upgrade eslint, domino, service-runner, request and many other
    dev and non-dev dependencies

  Cleanup:
  * Get rid of the handleUnbalancedTables DOM pass
  * The `normalize` post processor isn't needed any more
  * More use of arrow functions, promises, async/yield, ES6 classes
    in the codebase
  * Switch from jsduck to jsdoc3 for documentation and use
    new jsdoc-wmf-theme for documentation

0.8.0 / 2017-10-24
==================
  Notable wt -&gt; html changes:
  * T43716: Parse and serialize language converter markup
  * T64270: Support video and audio content
  * T39902, T149794: Markup red links, disambiguation links in Parsoid HTML
  * T122965: Support HTML5 elements in older browsers
  * T173384: Improve handling of tokens in parser function targets
  * T153885: Handle templated template names
  * T151277: Handle [[Media:Foo.jpg]] syntax correctly
  * Generalize removal of useless p-wrappers
  * More permissive attribute name parsing
    + match PHP parser's attribute sanitizer
  * Remove dependence on native parser functions
  * Stop using usePHPPreProcessor as a proxy for an existing mw api to parse extensions
  * Several bug fixes

  Notable html -&gt; wt changes:
  * T135667, T138492: Use improved format specifier for TemplateData enabling templates
    to control formatting of transclusions after VE edits
  * T153107: Fix unhandled detection of modified link content
  * T136653: Handle interwiki shortcuts
  * T177784: Update reverse interwiki map to prefer language prefixes over others
  * Cleanup in separator handling in the wikitext serializer
  * Several bug fixes

  API:
  * Remove support for pb2html in the http api

  Extensions:
  * Cite:
    - T159894: Add support for Cite's `responsive` parameter
  * Gallery:
    - Remove inline styling for vertical alignment in traditional galleries
    - All media should scale in gallery

  Dependencies:
  * Upgrade service-runner, mediawiki-title
  * Use uuid instead of node-uuid
  * Upgrade several dependencies to deal with security advisories
  * Limit core-js shimming to what we need

  Infrastructure:
  * Migrate from jshint to eslint

  Notable wikitext linting changes:
  * Move linter config properties to the linter config object
  * Only lint pages that have wikitext contentmodel
  * Lint multiple colon escaped links (incorrect usage)
  * Add an API endpoint to get lint errors for wikitext
  * Turn off ignored-table-attr output
  * Add detection for several wikitext patterns that render differently
    in Tidy compared to a HTML5 based parser (Parsoid, RemexHTML).
    This is only relevant if you want to fix pages before replacing
    Tidy or if you want to use Parsoid HTML for non-edit purposes.

  Other:
  * Add code of conduct file to the repo

0.7.1 / 2017-04-05
==================
  No changes.  New release to update nodejs dependency in the deb package.

0.7.0 / 2017-04-04
==================

  wt -&gt; html changes:
  * T102209: Assign ids to H[1-6] tags that match PHP parser's assignment
  * T150112: Munge link fragments and element ids as in the php parser
  * T59603: T133267: Escape extlink content when containing ] anywhere
  * T156296: Update cached wiki configs for several wikimedia wikis
  * T50900: Improved error output for extensions, missing images
  * T109897: Remove implicit_table_data_tag rule
  * T98960: Accept entities in extlink href and url links
  * T113044: Complete templatearg representation in spec
  * T104523: Prevent infinite recursion in template expansion
  * T104662: Allow nested ref tags only in templates
  * Support extension tags which shadows "block level" HTML elements
  * A bunch of cleanup and edge case fixes in the PEG tokenizer
  * Don't accept pipe unconditionally in extlink
  * Percent-encode modules link in the HEAD section
  * Update CSS modules in HEAD section
  * Remove special-case non-void semantics for SOURCE
  * Fixup redirect-detecting regular expressions in multiple places
  * Edge case bug fixes to title handling code
  * Edge case bug fixes in aynsc token transformation pipeline
  * Several fixes to the linting code to support the PHP Linter extension

  html -&gt; wt changes:
  * T149209: Handle newlines in TD and TH cells
  * T160207: Fix serializing multi-line indent-pre w/ sol wt syntax
  * T133267: Escape extlink content when containing ] anywhere
  * T152633: Fix crasher from ConstrainedText
  * T112043: Handle anchors without hrefs
  * Fix and cleanup domdiff annotations which fixes some edge case bugs

  Extensions:
  * T110910: Implement gallery extension natively inside Parsoid
  * T58381, T108216: Treat NOWIKI and html PRE as extension tags
  * Cite: T102134: Fix hrefs to render properly
  * Cite: Escape cite ids with Sanitizer.escapeId
  * Move section handling to the LST extension
  * Extension API improvements for the ProofreadPage extension
  * Normalize all extension options

  Infrastructure changes:
  * Update parser tests syncing scripts to let us sync PHP extension tests
    from to/from Parsoid.
  * Several fixes to parserTests scripts to improve output and processing of
    test options, among other things.
  * Bump domino, service-runner, minor versions of some deps,
    and some dev deps.
  * Switch to npm@3

  API changes:
  * In dev-api mode, add ?follow_redirects=true support to wt2html
    API end points to get Parsoid to return a HTTP 302 response for
    redirect pages. This lets 302-following clients to render the
    target page.

  Other fixes:
  * T153797: ApiRequest: Clone the request options before modifying them
  * T150213: Suppress logs for known unknown contentmodels
  * Code cleanup and refactoring for upcoming audio/video support.
  * Code cleanup and refactoring in template handling for upcoming support
    for templated template names. This also fixes some edge case bugs.

0.6.1 / 2016-11-14
==================

  * Fix broken 0.6.0 debian package

0.6.0 / 2016-11-07
==================

  wt -&gt; html changes:
  * T147742: Trim template target after stripping comments
  * T142617: Handle invalid titles in transclusions
  * Handle caption-like text outside tables
  * migrateTrailingNLs DOM pass: Code simplifications and
    some subtle edge case bug fixes
  * Handle HTML tags in attribute text properly
  * A bunch of cleanup and fixes in the PEG tokenizer

  html -&gt; wt changes:
  * T134389: Serialize content in HTML tables using HTML tags
  * T125419: Fix selser issues serializing first table row
  * T137406: Emit |- between thead/tbody/tfoot
  * T139388: Ensure that edits to content nested in elements
             with templated attributes is not lost by the
             selective serializer.
  * T142998: Fix crasher in DOM normalization code
  * Normalize all lists to not mix wikitext and HTML list syntax
  * Always emit canonical wikitext for url links
  * Emit url-links where appropriate no matter what rel attribute says

  Infrastructure changes:
  * T96195 : Remove node 0.8 support
  * T113322: Use the mediawiki-title library instead of
             Parsoid-homegrown title normalization code.
  * Remove html5 treebuilder in favour of domino's
  * service-runner:
    * T90668 : Replace custom server.js with service-runner
    * T141370: Use service-runner's logger as a backend to
               Parsoid's logger
    * Use service-runner's metrics reporter in the http api
  * Extensions:
    * T48580, T133320: Allow extensions to handle specific contentmodels
    * Let native extensions add stylesheets
  * Lots of wikitext linter fixes / features.

  API changes:
  * T130638: Add data-mw as a separate JSON blob in the pagebundle
  * T135596: Return client error for missing data attributes
  * T114413: Provide HTML2HTML endpoint in Parsoid
  * T100681: Remove deprecated v1/v2 HTTP APIs
  * T143356: Separate data-mw API semantics
  * Add a page/wikitext/:title route to GET wikitext for a page
  * Updates in preparation for supporting version 2.x content
    in the future -- should be no-op for version 1.x content
  * Don't expose dev routes in production
  * Cleanup http redirects
  * Send error responses in the requested format

  Performance fixes:
  * Template wrapping: Eliminate pathological tpl-range nesting scenario
  * computeDSR: Fix source of pathological O(n^2) behavior

  Other fixes:
  * Make the http connect timeout configurable
  * Prevent JSON.stringify circular refs in template wrapping
    trace/error logs
  * Fix processing listeners in node v7.x

0.5.3 / 2016-11-01
==================

  * T149504: SECURITY: Fix reflected XSS

0.5.2 / 2016-07-05
==================

  * Fix npm package

0.5.1 / 2015-05-02
==================

  * Fix debian package

0.5.0 / 2015-05-02
==================

  * Thar be dragons
