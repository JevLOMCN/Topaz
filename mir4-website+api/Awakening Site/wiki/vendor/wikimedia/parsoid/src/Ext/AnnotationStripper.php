<?php

namespace Wikimedia\Parsoid\Ext;

/**
 * A Parsoid extension module defining annotations should define an AnnotationStripper
 * that allows Parsoid to strip annotation markup from an arbitrary string, typically in
 * the content of non-wikitext extensions (such as SyntaxHighlight) in the wt2html direction.
 */
interface AnnotationStripper {
	/**
	 * Strip annotation markup from the provided string $s
	 * @param string $s
	 * @return string
	 */
	public function stripAnnotations( string $s ): string;

}
