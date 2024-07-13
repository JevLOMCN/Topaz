<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext;

use Closure;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;

/**
 * A Parsoid extension module may register handlers for one or more
 * extension tags. The only method which is generally
 * required by all extension tags is `sourceToDom` (but Translate
 * doesn't even implement that).  All other methods have default do-nothing
 * implementations; override them iff you wish to implement those
 * features.  Default implementations consistently return `false`
 * to indicate not-implemented (in some cases `null` would be a
 * valid return value, and in other cases `null` would be a likely
 * "accidental" return value which we'd like to catch and flag).
 */
abstract class ExtensionTagHandler {

	/**
	 * Convert an extension tag's content to DOM
	 * @param ParsoidExtensionAPI $extApi
	 * @param string $src Extension tag content
	 * @param array $extArgs Extension tag arguments
	 *   The extension tag arguments should be treated as opaque objects
	 *   and any necessary inspection should be handled through the API.
	 * @return DocumentFragment|false|null
	 *   `DocumentFragment` if returning some parsed content
	 *   `false` to fallback to the default handler for the content
	 *   `null` to drop the instance completely
	 */
	public function sourceToDom(
		ParsoidExtensionAPI $extApi, string $src, array $extArgs
	) {
		return false; /* Use default wrapper */
	}

	/**
	 * Extensions might embed HTML in attributes in their own custom
	 * representation (whether in data-mw or elsewhere).
	 *
	 * Core Parsoid will need a way to traverse such content. This method
	 * is a way for extension tag handlers to provide this functionality.
	 * Parsoid will only call this method if the tag's config sets the
	 * options['wt2html']['embedsHTMLInAttributes'] property to true.
	 *
	 * @param ParsoidExtensionAPI $extApi
	 * @param Element $elt The node whose data attributes need to be examined
	 * @param Closure $proc The processor that will process the embedded HTML
	 *        Signature: (string) -> string
	 *        This processor will be provided the HTML string as input
	 *        and is expected to return a possibly modified string.
	 */
	public function processAttributeEmbeddedHTML(
		ParsoidExtensionAPI $extApi, Element $elt, Closure $proc
	): void {
		// Nothing to do by default
	}

	/**
	 * Lint handler for this extension.
	 *
	 * If the extension has lints it wants to expose, it should use $extApi
	 * to register those lints. Alternatively, the extension might simply
	 * inspect its DOM and invoke the default lint handler on a DOM tree
	 * that it wants inspected. For example, <ref> nodes often only have
	 * a pointer (the id attribute) to its content, and is lint handler would
	 * look up the DOM tree and invoke the default lint handler on that tree.
	 *
	 * FIXME: There is probably no reason for the lint handler to return anything.
	 * The caller should simply proceed with the next sibling of $rootNode
	 * after the lint handler returns.
	 *
	 * @param ParsoidExtensionAPI $extApi
	 * @param Element $rootNode Extension content's root node
	 * @param callable $defaultHandler Default lint handler
	 *    - Default lint handler has signature $defaultHandler( Element $elt ): void
	 * @return Node|null|false Return `false` to indicate that this
	 *   extension has no special lint handler (the default lint handler will
	 *   be used.  Return `null` to indicate linting should proceed with the
	 *   next sibling.  (Deprecated) A `Node` can be returned to indicate
	 *   the point in the tree where linting should resume.
	 */
	public function lintHandler(
		ParsoidExtensionAPI $extApi, Element $rootNode, callable $defaultHandler
	) {
		/* Use default linter */
		return false;
	}

	/**
	 * Serialize a DOM node created by this extension to wikitext.
	 * @param ParsoidExtensionAPI $extApi
	 * @param Element $node
	 * @param bool $wrapperUnmodified
	 * @return string|false Return false to use the default serialization.
	 */
	public function domToWikitext(
		ParsoidExtensionAPI $extApi, Element $node, bool $wrapperUnmodified
	) {
		/* Use default serialization */
		return false;
	}

	/**
	 * XXX: Experimental
	 *
	 * Call $domDiff on corresponding substrees of $origNode and $editedNode
	 *
	 * @param ParsoidExtensionAPI $extApi
	 * @param callable $domDiff
	 * @param Element $origNode
	 * @param Element $editedNode
	 * @return bool
	 */
	public function diffHandler(
		ParsoidExtensionAPI $extApi, callable $domDiff, Element $origNode,
		Element $editedNode
	): bool {
		return false;
	}
}
