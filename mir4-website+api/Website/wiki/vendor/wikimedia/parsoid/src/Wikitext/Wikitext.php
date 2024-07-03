<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wikitext;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Wt2Html\PegTokenizer;

/**
 * This class represents core wikitext concepts that are currently represented
 * as methods of Parser.php (in core) OR Parsoid.php (here) or other classes.
 * Care should be taken to have this class represent first-class wikitext
 * concepts and operations and not so much implementation concepts, but that is
 * understandably a hard line to draw. Given that, this suggestion is more of a
 * guideline to help with code hygiene.
 */
class Wikitext {
	/**
	 * Equivalent of 'preprocess' from Parser.php in core.
	 * - expands templates
	 * - replaces magic variables
	 *
	 * Notably, this doesn't support replacing template args from a frame,
	 * i.e. the preprocessing here is of *standalone wikitext*, not in
	 * reference to something else which is where a frame would be used.
	 *
	 * This does not run any Parser hooks either, but support for which
	 * could eventually be added that is triggered by input options.
	 *
	 * This also updates resource usage and returns an error if limits
	 * are breached.
	 *
	 * @param Env $env
	 * @param string $wt
	 * @return array
	 *  - 'error' did we hit resource limits?
	 *  - 'src' expanded wikitext OR error message to print
	 *     FIXME: Maybe error message should be localizable
	 */
	public static function preprocess( Env $env, string $wt ): array {
		$start = microtime( true );
		$ret = $env->getDataAccess()->preprocessWikitext( $env->getPageConfig(), $env->getMetadata(), $wt );

		// FIXME: Should this bump be len($ret) - len($wt)?
		// I could argue both ways.
		if ( !$env->bumpWt2HtmlResourceUse( 'wikitextSize', strlen( $ret ) ) ) {
			return [
				'error' => true,
				'src' => "wt2html: wikitextSize limit exceeded",
			];
		}

		if ( $env->profiling() ) {
			$profile = $env->getCurrentProfile();
			$profile->bumpMWTime( "Template", 1000 * ( microtime( true ) - $start ), "api" );
			$profile->bumpCount( "Template" );
		}

		return [
			'error' => false,
			'src' => $ret,
		];
	}

	/**
	 * Perform pre-save transformations
	 *
	 * @param Env $env
	 * @param string $wt
	 * @param bool $substTLTemplates Prefix each top-level template with 'subst'
	 * @return string
	 */
	public static function pst( Env $env, string $wt, bool $substTLTemplates = false ) {
		if ( $substTLTemplates ) {
			// To make sure we do this for the correct templates, tokenize the
			// starting wikitext and use that to detect top-level templates.
			// Then, substitute each starting '{{' with '{{subst' using the
			// template token's tsr.
			$tokenizer = new PegTokenizer( $env );
			$tokens = $tokenizer->tokenizeSync( $wt );
			$tsrIncr = 0;
			foreach ( $tokens as $token ) {
				/** @var Token $token */
				if ( $token->getName() === 'template' ) {
					$tsr = $token->dataParsoid->tsr;
					$wt = substr( $wt, 0, $tsr->start + $tsrIncr )
						. '{{subst:' . substr( $wt, $tsr->start + $tsrIncr + 2 );
					$tsrIncr += 6;
				}
			}
		}
		return $env->getDataAccess()->doPst( $env->getPageConfig(), $wt );
	}
}
