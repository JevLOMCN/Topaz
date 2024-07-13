<?php

namespace MediaWiki\Extension\AbuseFilter;

use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;

/**
 * This service can be used to manage the list of keywords recognized by the Parser
 */
class KeywordsManager {
	public const SERVICE_NAME = 'AbuseFilterKeywordsManager';

	/**
	 * Operators and functions that can be used in AbuseFilter code.
	 * They are shown in the dropdown in the filter editor.
	 * Keys of translatable messages with their descriptions are
	 * based on keys of this array.
	 * When editing this list or the messages, keep the order
	 * consistent in both lists.
	 *
	 * @var array
	 */
	private const BUILDER_VALUES = [
		'op-arithmetic' => [
			// Generates abusefilter-edit-builder-op-arithmetic-addition
			'+' => 'addition',
			// Generates abusefilter-edit-builder-op-arithmetic-subtraction
			'-' => 'subtraction',
			// Generates abusefilter-edit-builder-op-arithmetic-multiplication
			'*' => 'multiplication',
			// Generates abusefilter-edit-builder-op-arithmetic-divide
			'/' => 'divide',
			// Generates abusefilter-edit-builder-op-arithmetic-modulo
			'%' => 'modulo',
			// Generates abusefilter-edit-builder-op-arithmetic-pow
			'**' => 'pow'
		],
		'op-comparison' => [
			// Generates abusefilter-edit-builder-op-comparison-equal
			'==' => 'equal',
			// Generates abusefilter-edit-builder-op-comparison-equal-strict
			'===' => 'equal-strict',
			// Generates abusefilter-edit-builder-op-comparison-notequal
			'!=' => 'notequal',
			// Generates abusefilter-edit-builder-op-comparison-notequal-strict
			'!==' => 'notequal-strict',
			// Generates abusefilter-edit-builder-op-comparison-lt
			'<' => 'lt',
			// Generates abusefilter-edit-builder-op-comparison-gt
			'>' => 'gt',
			// Generates abusefilter-edit-builder-op-comparison-lte
			'<=' => 'lte',
			// Generates abusefilter-edit-builder-op-comparison-gte
			'>=' => 'gte'
		],
		'op-bool' => [
			// Generates abusefilter-edit-builder-op-bool-not
			'!' => 'not',
			// Generates abusefilter-edit-builder-op-bool-and
			'&' => 'and',
			// Generates abusefilter-edit-builder-op-bool-or
			'|' => 'or',
			// Generates abusefilter-edit-builder-op-bool-xor
			'^' => 'xor'
		],
		'misc' => [
			// Generates abusefilter-edit-builder-misc-in
			'in' => 'in',
			// Generates abusefilter-edit-builder-misc-contains
			'contains' => 'contains',
			// Generates abusefilter-edit-builder-misc-like
			'like' => 'like',
			// Generates abusefilter-edit-builder-misc-stringlit
			'""' => 'stringlit',
			// Generates abusefilter-edit-builder-misc-rlike
			'rlike' => 'rlike',
			// Generates abusefilter-edit-builder-misc-irlike
			'irlike' => 'irlike',
			// Generates abusefilter-edit-builder-misc-tern
			'cond ? iftrue : iffalse' => 'tern',
			// Generates abusefilter-edit-builder-misc-cond
			'if cond then iftrue else iffalse end' => 'cond',
			// Generates abusefilter-edit-builder-misc-cond-short
			'if cond then iftrue end' => 'cond-short',
		],
		'funcs' => [
			// Generates abusefilter-edit-builder-funcs-length
			'length(string)' => 'length',
			// Generates abusefilter-edit-builder-funcs-lcase
			'lcase(string)' => 'lcase',
			// Generates abusefilter-edit-builder-funcs-ucase
			'ucase(string)' => 'ucase',
			// Generates abusefilter-edit-builder-funcs-ccnorm
			'ccnorm(string)' => 'ccnorm',
			// Generates abusefilter-edit-builder-funcs-ccnorm-contains-any
			'ccnorm_contains_any(haystack,needle1,needle2,..)' => 'ccnorm-contains-any',
			// Generates abusefilter-edit-builder-funcs-ccnorm-contains-all
			'ccnorm_contains_all(haystack,needle1,needle2,..)' => 'ccnorm-contains-all',
			// Generates abusefilter-edit-builder-funcs-rmdoubles
			'rmdoubles(string)' => 'rmdoubles',
			// Generates abusefilter-edit-builder-funcs-specialratio
			'specialratio(string)' => 'specialratio',
			// Generates abusefilter-edit-builder-funcs-norm
			'norm(string)' => 'norm',
			// Generates abusefilter-edit-builder-funcs-count
			'count(needle,haystack)' => 'count',
			// Generates abusefilter-edit-builder-funcs-rcount
			'rcount(needle,haystack)' => 'rcount',
			// Generates abusefilter-edit-builder-funcs-get_matches
			'get_matches(needle,haystack)' => 'get_matches',
			// Generates abusefilter-edit-builder-funcs-rmwhitespace
			'rmwhitespace(text)' => 'rmwhitespace',
			// Generates abusefilter-edit-builder-funcs-rmspecials
			'rmspecials(text)' => 'rmspecials',
			// Generates abusefilter-edit-builder-funcs-ip_in_range
			'ip_in_range(ip, range)' => 'ip_in_range',
			// Generates abusefilter-edit-builder-funcs-ip_in_ranges
			'ip_in_ranges(ip, range1, range2, ...)' => 'ip_in_ranges',
			// Generates abusefilter-edit-builder-funcs-contains-any
			'contains_any(haystack,needle1,needle2,...)' => 'contains-any',
			// Generates abusefilter-edit-builder-funcs-contains-all
			'contains_all(haystack,needle1,needle2,...)' => 'contains-all',
			// Generates abusefilter-edit-builder-funcs-equals-to-any
			'equals_to_any(haystack,needle1,needle2,...)' => 'equals-to-any',
			// Generates abusefilter-edit-builder-funcs-substr
			'substr(subject, offset, length)' => 'substr',
			// Generates abusefilter-edit-builder-funcs-strpos
			'strpos(haystack, needle)' => 'strpos',
			// Generates abusefilter-edit-builder-funcs-str_replace
			'str_replace(subject, search, replace)' => 'str_replace',
			// Generates abusefilter-edit-builder-funcs-str_replace_regexp
			'str_replace_regexp(subject, search, replace)' => 'str_replace_regexp',
			// Generates abusefilter-edit-builder-funcs-rescape
			'rescape(string)' => 'rescape',
			// Generates abusefilter-edit-builder-funcs-set_var
			'set_var(var,value)' => 'set_var',
			// Generates abusefilter-edit-builder-funcs-sanitize
			'sanitize(string)' => 'sanitize',
		],
		'vars' => [
			// Generates abusefilter-edit-builder-vars-timestamp
			'timestamp' => 'timestamp',
			// Generates abusefilter-edit-builder-vars-accountname
			'accountname' => 'accountname',
			// Generates abusefilter-edit-builder-vars-action
			'action' => 'action',
			// Generates abusefilter-edit-builder-vars-addedlines
			'added_lines' => 'addedlines',
			// Generates abusefilter-edit-builder-vars-delta
			'edit_delta' => 'delta',
			// Generates abusefilter-edit-builder-vars-diff
			'edit_diff' => 'diff',
			// Generates abusefilter-edit-builder-vars-newsize
			'new_size' => 'newsize',
			// Generates abusefilter-edit-builder-vars-oldsize
			'old_size' => 'oldsize',
			// Generates abusefilter-edit-builder-vars-new-content-model
			'new_content_model' => 'new-content-model',
			// Generates abusefilter-edit-builder-vars-old-content-model
			'old_content_model' => 'old-content-model',
			// Generates abusefilter-edit-builder-vars-removedlines
			'removed_lines' => 'removedlines',
			// Generates abusefilter-edit-builder-vars-summary
			'summary' => 'summary',
			// Generates abusefilter-edit-builder-vars-page-id
			'page_id' => 'page-id',
			// Generates abusefilter-edit-builder-vars-page-ns
			'page_namespace' => 'page-ns',
			// Generates abusefilter-edit-builder-vars-page-title
			'page_title' => 'page-title',
			// Generates abusefilter-edit-builder-vars-page-prefixedtitle
			'page_prefixedtitle' => 'page-prefixedtitle',
			// Generates abusefilter-edit-builder-vars-page-age
			'page_age' => 'page-age',
			// Generates abusefilter-edit-builder-vars-movedfrom-id
			'moved_from_id' => 'movedfrom-id',
			// Generates abusefilter-edit-builder-vars-movedfrom-ns
			'moved_from_namespace' => 'movedfrom-ns',
			// Generates abusefilter-edit-builder-vars-movedfrom-title
			'moved_from_title' => 'movedfrom-title',
			// Generates abusefilter-edit-builder-vars-movedfrom-prefixedtitle
			'moved_from_prefixedtitle' => 'movedfrom-prefixedtitle',
			// Generates abusefilter-edit-builder-vars-movedfrom-age
			'moved_from_age' => 'movedfrom-age',
			// Generates abusefilter-edit-builder-vars-movedto-id
			'moved_to_id' => 'movedto-id',
			// Generates abusefilter-edit-builder-vars-movedto-ns
			'moved_to_namespace' => 'movedto-ns',
			// Generates abusefilter-edit-builder-vars-movedto-title
			'moved_to_title' => 'movedto-title',
			// Generates abusefilter-edit-builder-vars-movedto-prefixedtitle
			'moved_to_prefixedtitle' => 'movedto-prefixedtitle',
			// Generates abusefilter-edit-builder-vars-movedto-age
			'moved_to_age' => 'movedto-age',
			// Generates abusefilter-edit-builder-vars-user-editcount
			'user_editcount' => 'user-editcount',
			// Generates abusefilter-edit-builder-vars-user-age
			'user_age' => 'user-age',
			// Generates abusefilter-edit-builder-vars-user-name
			'user_name' => 'user-name',
			// Generates abusefilter-edit-builder-vars-user-type
			'user_type' => 'user-type',
			// Generates abusefilter-edit-builder-vars-user-groups
			'user_groups' => 'user-groups',
			// Generates abusefilter-edit-builder-vars-user-rights
			'user_rights' => 'user-rights',
			// Generates abusefilter-edit-builder-vars-user-blocked
			'user_blocked' => 'user-blocked',
			// Generates abusefilter-edit-builder-vars-user-emailconfirm
			'user_emailconfirm' => 'user-emailconfirm',
			// Generates abusefilter-edit-builder-vars-old-wikitext
			'old_wikitext' => 'old-wikitext',
			// Generates abusefilter-edit-builder-vars-new-wikitext
			'new_wikitext' => 'new-wikitext',
			// Generates abusefilter-edit-builder-vars-added-links
			'added_links' => 'added-links',
			// Generates abusefilter-edit-builder-vars-removed-links
			'removed_links' => 'removed-links',
			// Generates abusefilter-edit-builder-vars-all-links
			'all_links' => 'all-links',
			// Generates abusefilter-edit-builder-vars-new-pst
			'new_pst' => 'new-pst',
			// Generates abusefilter-edit-builder-vars-diff-pst
			'edit_diff_pst' => 'diff-pst',
			// Generates abusefilter-edit-builder-vars-addedlines-pst
			'added_lines_pst' => 'addedlines-pst',
			// Generates abusefilter-edit-builder-vars-new-text
			'new_text' => 'new-text',
			// Generates abusefilter-edit-builder-vars-new-html
			'new_html' => 'new-html',
			// Generates abusefilter-edit-builder-vars-restrictions-edit
			'page_restrictions_edit' => 'restrictions-edit',
			// Generates abusefilter-edit-builder-vars-restrictions-move
			'page_restrictions_move' => 'restrictions-move',
			// Generates abusefilter-edit-builder-vars-restrictions-create
			'page_restrictions_create' => 'restrictions-create',
			// Generates abusefilter-edit-builder-vars-restrictions-upload
			'page_restrictions_upload' => 'restrictions-upload',
			// Generates abusefilter-edit-builder-vars-recent-contributors
			'page_recent_contributors' => 'recent-contributors',
			// Generates abusefilter-edit-builder-vars-first-contributor
			'page_first_contributor' => 'first-contributor',
			// Generates abusefilter-edit-builder-vars-movedfrom-restrictions-edit
			'moved_from_restrictions_edit' => 'movedfrom-restrictions-edit',
			// Generates abusefilter-edit-builder-vars-movedfrom-restrictions-move
			'moved_from_restrictions_move' => 'movedfrom-restrictions-move',
			// Generates abusefilter-edit-builder-vars-movedfrom-restrictions-create
			'moved_from_restrictions_create' => 'movedfrom-restrictions-create',
			// Generates abusefilter-edit-builder-vars-movedfrom-restrictions-upload
			'moved_from_restrictions_upload' => 'movedfrom-restrictions-upload',
			// Generates abusefilter-edit-builder-vars-movedfrom-recent-contributors
			'moved_from_recent_contributors' => 'movedfrom-recent-contributors',
			// Generates abusefilter-edit-builder-vars-movedfrom-first-contributor
			'moved_from_first_contributor' => 'movedfrom-first-contributor',
			// Generates abusefilter-edit-builder-vars-movedto-restrictions-edit
			'moved_to_restrictions_edit' => 'movedto-restrictions-edit',
			// Generates abusefilter-edit-builder-vars-movedto-restrictions-move
			'moved_to_restrictions_move' => 'movedto-restrictions-move',
			// Generates abusefilter-edit-builder-vars-movedto-restrictions-create
			'moved_to_restrictions_create' => 'movedto-restrictions-create',
			// Generates abusefilter-edit-builder-vars-movedto-restrictions-upload
			'moved_to_restrictions_upload' => 'movedto-restrictions-upload',
			// Generates abusefilter-edit-builder-vars-movedto-recent-contributors
			'moved_to_recent_contributors' => 'movedto-recent-contributors',
			// Generates abusefilter-edit-builder-vars-movedto-first-contributor
			'moved_to_first_contributor' => 'movedto-first-contributor',
			// Generates abusefilter-edit-builder-vars-old-links
			'old_links' => 'old-links',
			// Generates abusefilter-edit-builder-vars-file-sha1
			'file_sha1' => 'file-sha1',
			// Generates abusefilter-edit-builder-vars-file-size
			'file_size' => 'file-size',
			// Generates abusefilter-edit-builder-vars-file-mime
			'file_mime' => 'file-mime',
			// Generates abusefilter-edit-builder-vars-file-mediatype
			'file_mediatype' => 'file-mediatype',
			// Generates abusefilter-edit-builder-vars-file-width
			'file_width' => 'file-width',
			// Generates abusefilter-edit-builder-vars-file-height
			'file_height' => 'file-height',
			// Generates abusefilter-edit-builder-vars-file-bits-per-channel
			'file_bits_per_channel' => 'file-bits-per-channel',
			// Generates abusefilter-edit-builder-vars-wiki-name
			'wiki_name' => 'wiki-name',
			// Generates abusefilter-edit-builder-vars-wiki-language
			'wiki_language' => 'wiki-language',
		],
	];

	/**
	 * Old vars which aren't in use anymore.
	 * The translatable messages that are based
	 * on them are not shown in the filter editor,
	 * but may still be shown in the log descriptions of
	 * filter actions that were taken by filters
	 * that used them.
	 *
	 * @var array
	 */
	private const DISABLED_VARS = [
		// Generates abusefilter-edit-builder-vars-old-text
		'old_text' => 'old-text',
		// Generates abusefilter-edit-builder-vars-old-html
		'old_html' => 'old-html',
		// Generates abusefilter-edit-builder-vars-minor-edit
		'minor_edit' => 'minor-edit'
	];

	private const DEPRECATED_VARS = [
		'article_text' => 'page_title',
		'article_prefixedtext' => 'page_prefixedtitle',
		'article_namespace' => 'page_namespace',
		'article_articleid' => 'page_id',
		'article_restrictions_edit' => 'page_restrictions_edit',
		'article_restrictions_move' => 'page_restrictions_move',
		'article_restrictions_create' => 'page_restrictions_create',
		'article_restrictions_upload' => 'page_restrictions_upload',
		'article_recent_contributors' => 'page_recent_contributors',
		'article_first_contributor' => 'page_first_contributor',
		'moved_from_text' => 'moved_from_title',
		'moved_from_prefixedtext' => 'moved_from_prefixedtitle',
		'moved_from_articleid' => 'moved_from_id',
		'moved_to_text' => 'moved_to_title',
		'moved_to_prefixedtext' => 'moved_to_prefixedtitle',
		'moved_to_articleid' => 'moved_to_id',
	];

	/** @var string[][] Final list of builder values */
	private $builderValues;

	/** @var string[] Final list of deprecated vars */
	private $deprecatedVars;

	/** @var AbuseFilterHookRunner */
	private $hookRunner;

	/**
	 * @param AbuseFilterHookRunner $hookRunner
	 */
	public function __construct( AbuseFilterHookRunner $hookRunner ) {
		$this->hookRunner = $hookRunner;
	}

	/**
	 * @return array
	 */
	public function getDisabledVariables(): array {
		return self::DISABLED_VARS;
	}

	/**
	 * @return array
	 */
	public function getDeprecatedVariables(): array {
		if ( $this->deprecatedVars === null ) {
			$this->deprecatedVars = self::DEPRECATED_VARS;
			$this->hookRunner->onAbuseFilter_deprecatedVariables( $this->deprecatedVars );
		}
		return $this->deprecatedVars;
	}

	/**
	 * @return array
	 */
	public function getBuilderValues(): array {
		if ( $this->builderValues === null ) {
			$this->builderValues = self::BUILDER_VALUES;
			$this->hookRunner->onAbuseFilter_builder( $this->builderValues );
		}
		return $this->builderValues;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function isVarDisabled( string $name ): bool {
		return array_key_exists( $name, self::DISABLED_VARS );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function isVarDeprecated( string $name ): bool {
		return array_key_exists( $name, $this->getDeprecatedVariables() );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function isVarInUse( string $name ): bool {
		return array_key_exists( $name, $this->getVarsMappings() );
	}

	/**
	 * Check whether the given name corresponds to a known variable.
	 * @param string $name
	 * @return bool
	 */
	public function varExists( string $name ): bool {
		return $this->isVarInUse( $name ) ||
			$this->isVarDisabled( $name ) ||
			$this->isVarDeprecated( $name );
	}

	/**
	 * Get the message for a builtin variable; takes deprecated variables into account.
	 * Returns null for non-builtin variables.
	 *
	 * @param string $var
	 * @return string|null
	 */
	public function getMessageKeyForVar( string $var ): ?string {
		if ( !$this->varExists( $var ) ) {
			return null;
		}
		if ( $this->isVarDeprecated( $var ) ) {
			$var = $this->getDeprecatedVariables()[$var];
		}

		$key = self::DISABLED_VARS[$var] ??
			$this->getVarsMappings()[$var];
		return "abusefilter-edit-builder-vars-$key";
	}

	/**
	 * @return array
	 */
	public function getVarsMappings(): array {
		return $this->getBuilderValues()['vars'];
	}

	/**
	 * Get a list of core variables, i.e. variables defined in AbuseFilter (ignores hooks).
	 * You usually want to use getVarsMappings(), not this one.
	 * @return string[]
	 */
	public function getCoreVariables(): array {
		return array_keys( self::BUILDER_VALUES['vars'] );
	}
}
