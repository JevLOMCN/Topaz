<?php

/* phpcs:disable MediaWiki.Commenting.FunctionComment.MissingDocumentationPublic */
/* phpcs:disable Generic.Files.LineLength.TooLong */
/* phpcs:disable MediaWiki.Commenting.FunctionComment.WrongStyle */

/* ------------------------------------------------------------------------------
 * This script is meant to be used as a tool to process Cite's i18n messages used
 * for formatting Cite numbers and generate equivalent CSS rules that generate
 * almost equivalent output. These rules are meant to be tweaked before being added
 * to a wiki's MediaWiki:Common.css file.
 *
 * Run this script as: php tools/processCiteMessages.php <optional-args>
 *
 * This script assumes a checkout of the Cite extension in ../../core/extensions/Cite
 * If not, pass the cite directory path as ARGV[1]
 *
 * This script assumes the presence of a number of JSON files:
 * - JSON output of https://global-search.toolforge.org/?namespaces=8&q=.%2A&regex=1&title=Cite%20reference.%2A
 *   stored in "./cite.references.messages.json" OR passed in via ARGV[2]
 * - JSON output of https://global-search.toolforge.org/?namespaces=8&q=.%2A&regex=1&title=Cite%20link%20label.%2A
 *   stored in "/cite.link_label_groups.messages.json" OR passed in via ARGV[3]
 * - Localized separators stored in "./localized.separators.json" extracted
 *   from the separators arrays in the various language message files in
 *   mediawiki core repo.
 * - Message fallback chains in "./language.fallbacks.json"
 *
 * It dumps the CSS rules per wiki (found in the JSON files) to stdout.
 * ------------------------------------------------------------------------------ */

// Message fallback chain generated with this CLI in MW core's
// languages/messages/ directory and edited lightly.
//
// git grep '$fallback ' | sed 's/^Messages\(.*\)\..* '"'"'\(.*\).;.*/\L"\1":["\2"]/;s/_/-/;s/, /", "/g;s/$/,/g;' > /tmp/language.fallbacks.json

function wfJsonKeyToTitleSuffix( string $key ): string {
	return preg_replace( '/cite_/', '', $key );
}

function wfTitleToKey( string $title ): string {
	return strtr( preg_replace( '/^.*?:Cite /', '', $title ), ' ', '_' );
}

function wfFetchRevisionContent( string $wiki, string $title ): string {
	$out = [];
	$cache = __DIR__ . "/../cached_titles";
	$cachedFile = "$cache/$wiki." . wfTitleToKey( $title );
	if ( file_exists( $cachedFile ) ) {
		$res = file_get_contents( $cachedFile );
	} else {
		$path = __DIR__ . "/../FetchWt.php";
		$command = "php $path --domain $wiki.org --title '$title'";
		/* phpcs:disable MediaWiki.Usage.ForbiddenFunctions.exec */
		exec( $command, $out );
		$res = implode( $out );
		file_put_contents( $cachedFile, $res );
	}
	# error_log("GOT $res instead of missing for $wiki:$title");
	return $res;
}

function wfProcessRow( array $row, array &$wikiInfo ): void {
	$wiki = $row['wiki'];
# Debug
#	if ( $wiki !== "vec.wikisource" ) {
#		return;
#	}
	$msgKey = $row['title'];
	$content = trim( $row['source_text'] );
	if ( !$content ) {
		# error_log("MISSING content for $wiki:$msgKey");
		$content = wfFetchRevisionContent( $wiki, $msgKey );
	}
	$msgKey = wfTitleToKey( $msgKey );
	// \u0026amp;#32 => &#32 => ' '
	$content = preg_replace( '/\s+/', ' ',
		html_entity_decode(
			preg_replace( "#^<span[^>]*>([\s\S]*)</span>$#", "$1", html_entity_decode( $content ) )
		)
	);
#	error_log("wiki:$wiki; msg: $msgKey; content: $content");
	$wikiInfo[$wiki][$msgKey] = $content;
}

function wfAddCSSForIBTagsAndProcessMsg( array &$cssRules, string &$msg ): void {
	if ( preg_match( "/<b>/", $msg ) ) {
		$cssRules[] = "font-weight: bold;";
	}
	if ( preg_match( "/<i>/", $msg ) ) {
		$cssRules[] = "font-style: italic;";
	}
	$msg = preg_replace( "/<\/?(i|b)>/", "", $msg );
}

function wfCheckIfAlphabetic( array $symbols ): array {
	$base = [];
	$prefix = "";
	$i = -1;
	foreach ( $symbols as $s ) {
		if ( mb_strlen( $s ) === 1 ) {
			$base[] = $s;
		} else {
			if ( $i === count( $base ) ) {
				return $base;
			}

			if ( $prefix === "" ) {
				$i = 0;
				$prefix = mb_substr( $s, 0, 1 );
				if ( !$base || mb_substr( $s, 1 ) !== $base[$i] ) {
					$base[] = $s;
					$prefix = "";
					continue;
				}
			} elseif ( $prefix !== mb_substr( $s, 0, 1 ) || mb_substr( $s, 1 ) !== $base[$i] ) {
				return [];
			}
			$i++;
		}
	}
	return $prefix !== "" ? $base : [];
}

/*
 * Next 2 arrays are extracted from https://www.w3.org/TR/predefined-counter-styles/
 * Intersected with https://www.w3.org/International/i18n-tests/results/predefined-counter-styles
 * where all major browsers have support with a green cell.
 */
$wgW3cDecimalCounters = [
	"decimal",
	"arabic-indic",
	"bengali",
	"cambodian",
	"devanagari",
	"gujarati",
	"gurmukhi",
	"kannada",
	"khmer",
	"lao",
	"lepcha",
	"malayalam",
	"myanmar", /* w3c page says suffix, but testing shows no suffix! */
	"mongolian",
	"oriya",
	"persian",
	"tamil",
	"telugu",
	"thai",
];

$wgW3cCounterTypeMaps = [
	/* numeric - no suffixes or prefixes */
	"0123456789" => "decimal",
	"٠١٢٣٤٥٦٧٨٩" => "arabic-indic",
	"০১২৩৪৫৬৭৮৯" => "bengali",
	"០១២៣៤៥៦៧៨៩" => "cambodian",
	"०१२३४५६७८९" => "devanagari",
	"૦૧૨૩૪૫૬૭૮૯" => "gujarati",
	"੦੧੨੩੪੫੬੭੮੯" => "gurmukhi",
	"೦೧೨೩೪೫೬೭೮೯" => "kannada",
	"០១២៣៤៥៦៧៨៩" => "khmer",
	"໐໑໒໓໔໕໖໗໘໙" => "lao",
	"᱀᱁᱂᱃᱄᱅᱆᱇᱈᱉" => "lepcha",
	"൦൧൨൩൪൫൬൭൮൯" => "malayalam",
	"၀၁၂၃၄၅၆၇၈၉" => "myanmar", /* w3c page says suffix, but testing shows no suffix! */
	"᠐᠑᠒᠓᠔᠕᠖᠗᠘᠙" => "mongolian",
	"୦୧୨୩୪୫୬୭୮୯" => "oriya",
	"۰۱۲۳۴۵۶۷۸۹" => "persian",
	"௦௧௨௩௪௫௬௭௮௯" => "tamil",
	"౦౧౨౩౪౫౬౭౮౯" => "telugu",
	"๐๑๒๓๔๕๖๗๘๙" => "thai",

	/* alphabetic - no suffixes or prefixes */
	"a-z" => "lower-alpha",
	"A-Z" => "upper-alpha",
	"αβγδεζηθικλμνξοπρστυφχψω" => "lower-greek",
	"abcdefghijklmnopqrstuvwxyz" => "lower-alpha",
	"ABCDEFGHIJKLMNOPQRSTUVWXYZ" => "upper-alpha",

/* ---- Not sure we can use these as is ----
	// alphabetic - WITH suffixes and/or prefixes.

	"子丑寅卯辰巳午未申酉戌亥"=>"cjk-earthly-branch",
	"甲乙丙丁戊己庚辛壬癸"=>"cjk-heavenly-stem",
	"㊀㊁㊂㊃㊄㊅㊆㊇㊈㊉"=>"circled-ideograph",
	"あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよらりるれろわゐゑをん"=>"hiragana",
	"いろはにほへとちりぬるをわかよたれそつねならむうゐのおくやまけふこえてあさきゆめみしゑひもせす"=>"hiragana-iroha",
	"アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヰヱヲン"=>"katakana",
	"イロハニホヘトチリヌルヲワカヨタレソツネナラムウヰノオクヤマケフコエテアサキユメミシヱヒモセス"=>"katakana-iroha",
*/
];

function wfIsNonDecimalCounter( string $counterName ): bool {
	global $wgW3cDecimalCounters;

	return array_search( $counterName, $wgW3cDecimalCounters, false ) === false;
}

function wfGetCSS( string $selector, array $rules ): string {
	return "$selector {\n\t" . implode( "\n\t", $rules ) . "\n}";
}

function wfEmitCSS( string $selector, array $rules ): void {
	print wfGetCSS( $selector, $rules ) . "\n";
}

function wfLoadJson( string $file ): array {
	return json_decode( file_get_contents( $file ), true );
}

function wfProcessMessages( string $file, ?array &$wikiInfo ): void {
	$jsonObj = wfLoadJson( $file );
	foreach ( $jsonObj["hits"] as $row ) {
		wfProcessRow( $row, $wikiInfo );
	}
}

function wfDetectCounterType( string $msg ): ?string {
	global $wgW3cCounterTypeMaps;

	/* Hacky heuristic that should work */
	if ( preg_match( '/^([αβγδεζηθικλμνξοπρστυφχψω]*( |$))*$/u', $msg ) ) {
		return "lower-greek";
	} elseif ( preg_match( '/^([ivxlcdm]*( |$))*$/u', $msg ) ) {
		return "lower-roman";
	} elseif ( preg_match( '/^([IVXLCDM]*( |$))*$/u', $msg ) ) {
		return "upper-roman";
	} else {
		foreach ( $wgW3cCounterTypeMaps as $digits => $type ) {
			if ( preg_match( "/^([$digits]*( |$))*$/u", $msg ) ) {
				return $type;
			}
		}
		return null;
	}
}

function wfBackfill( string $citeI18nDir, string $lang, array &$messages ): void {
	$jsonMsgKeys = [
		"cite_reference_link",
		"cite_references_link_one",
		"cite_references_link_many_format_backlink_labels",
		"cite_references_link_many_format",
		"cite_references_link_many",
		"cite_references_link_many_and",
		"cite_references_link_many_sep",
	];
	$langJsonFile = "$citeI18nDir$lang.json";
	if ( file_exists( $langJsonFile ) ) {
		$langMsgs = wfLoadJson( $langJsonFile );
		foreach ( $jsonMsgKeys as $key ) {
			$msgTitleKey = wfJsonKeyToTitleSuffix( $key );
			if ( !isset( $messages[$msgTitleKey] ) && isset( $langMsgs[$key] ) ) {
				$messages[$msgTitleKey] = $langMsgs[$key];
			}
		}
	}
}

$wikiInfo = [];
$citeRepo = $argv[1] ?? ( __DIR__ . "/../../../core/extensions/Cite" );

// This file is the exported JSON of https://global-search.toolforge.org/?namespaces=8&q=.%2A&regex=1&title=Cite%20reference.%2A
$jsonFile = $argv[2] ?? ( __DIR__ . "/cite.reference.messages.json" );
wfProcessMessages( $jsonFile, $wikiInfo );

// This file is the exported JSON of https://global-search.toolforge.org/?namespaces=8&q=.%2A&regex=1&title=Cite%20link%20label.%2A
$jsonFile = $argv[3] ?? ( __DIR__ . "/cite.link_label_groups.messages.json" );
wfProcessMessages( $jsonFile, $wikiInfo );

// Language fallback chain
$langFallbacks = wfLoadJson( __DIR__ . "/language.fallbacks.json" );

// Localized digits & separators
$localizedDigits = wfLoadJson( __DIR__ . "/localized.digits.json" ); // Extracted from languages/messages/Message$lang.php
$localizedSeps = wfLoadJson( __DIR__ . "/localized.separators.json" ); // Extracted from languages/messages/Message$lang.php
$digitLocalizationEnabled = wfLoadJson( __DIR__ . "/digits.localization.config.json" ); // Extracted from wmf-config/$wgTranslateNumerals

// Wiki language exceptions
$wikiLangExceptions = wfLoadJson( __DIR__ . "/wikilang.exceptions.json" );
foreach ( $wikiLangExceptions as $wiki => $lang ) {
	$wikiInfo[$wiki] ??= []; // init
}

// Backfill from lang-specific i18n file
$citeI18nDir = $citeRepo . "/i18n/";
foreach ( $wikiInfo as $wiki => &$messages ) {
	$lang = $wikiLangExceptions[$wiki] ?? preg_replace( "/\..*$/", "", $wiki );
	wfBackfill( $citeI18nDir, $lang, $messages );
}

// Backfill from language fallback chains
foreach ( $wikiInfo as $wiki => &$messages ) {
	$lang = $wikiLangExceptions[$wiki] ?? preg_replace( "/\..*$/", "", $wiki );
	if ( $lang !== 'en' ) {
		// last fallback is 'en'
		$len = count( $langFallbacks[$lang] ?? [] );
		if ( $len === 0 || ( $langFallbacks[$lang][$len - 1] !== 'en' ) ) {
			$langFallbacks[$lang][] = 'en';
		}
		foreach ( $langFallbacks[$lang] ?? [] as $fbLang ) {
			wfBackfill( $citeI18nDir, $fbLang, $messages );
			if ( !isset( $localizedDigits[$lang] ) && isset( $localizedDigits[$fbLang] ) ) {
				$localizedDigits[$lang] = $localizedDigits[$fbLang];
			}
			if ( !isset( $localizedSeps[$lang] ) && isset( $localizedSeps[$fbLang] ) ) {
				$localizedSeps[$lang] = $localizedSeps[$fbLang];
			}
		}
	}
}

// Process messages
foreach ( $wikiInfo as $wiki => &$messages ) {
	# error_log( "$wiki has " . count($messages) . " messages ");

	// Preprocess all messages
	foreach ( $messages as $key => $msg ) {
		$msg = preg_replace( "/strong/", "b", $msg );
		$msg = preg_replace( "/'''([^']+)'''/", "<b>$1</b>", $msg );
		$msg = preg_replace( "/''([^']+)''/", "<i>$1</i>", $msg );
		$msg = html_entity_decode( $msg );

		switch ( $key ) {
			case "reference_link":
				$msg = preg_replace( "/<(span|nowiki|sup)[^>]*>|<\/(span|sup|nowiki)>/", "", $msg );
				$msg = preg_replace( "/\[\[\#\\$2\||\]\]/", "", $msg );
				break;

			case "references_link_one":
				$msg = preg_replace( "/<(li|span)[^>]*>|<\/(li|span)>/", "", $msg );
				$msg = preg_replace( "/\[\[\#\\$2\||\]\]|\\$3/", "", $msg );
				break;

			case "references_link_many":
				$msg = preg_replace( "/<(li|span|nowiki)[^>]*>|<\/(li|span|nowiki)>/", "", $msg );
				$msg = preg_replace( "/\\$2 \\$3/", "", $msg );
				break;

			case "references_link_many_format":
				$msg = preg_replace( "/\[\[\#\\$1\||<(span|sup)[^>]*>|<\/(span|sup)>|\]\]/", "", $msg );
				break;

			case "references_link_many_sep":
				$msg = preg_replace( "/|<sup[^>]*>|<\/sup>/", "", $msg );
				break;

			case "references_link_many_and":
				$msg = preg_replace( "/|<sup[^>]*>|<\/sup>/", "", $msg );
				$msg = preg_replace( "/|<sup[^>]*>|<\/sup>/", "", $msg );
				break;

			default:
				break;
		}
		$messages[$key] = $msg;
		# error_log("$key = " . json_encode($msg) );
	}

	print "--- EMITTING CSS for $wiki ---\n";

	// Generate any required custom counters first
	$lang = $wikiLangExceptions[$wiki] ?? preg_replace( "/\..*$/", "", $wiki );
	$digits = $localizedDigits[$lang] ?? null;
	if ( $digits === null ) {
		$langCounterType = "decimal"; // default
	} else {
		$langCounterType = wfDetectCounterType( implode( ' ', $digits ) ) ?? "$lang-counter";
	}
	// check if localization is disabled!
	if ( $langCounterType !== 'decimal' && !( $digitLocalizationEnabled[$wiki] ?? true ) ) {
		$refCounterType = 'decimal';
		$linkbackCounterType = 'decimal';
		$langSep = '.';
		$resetRefCounterTypes = true;
	} else {
		$refCounterType = $langCounterType;
		$linkbackCounterType = $langCounterType;
		$langSep = $localizedSeps[$lang] ?? '.'; // default
		$resetRefCounterTypes = false;
	}

	$keys = array_keys( $messages );
	$groupLabels = [];
	$initCustomLinkbackCounter = '';
	foreach ( $messages as $key => $msg ) {
		// Skip error-test groups
		if ( preg_match( '/error-test/', $key ) ) {
			continue;
		}

		if ( preg_match( '/^link_label_group-/', $key ) ) {
			$group = substr( $key, 17 /* strlen( "link_label_group-" ) */ );
			$groupCounterType = wfDetectCounterType( $msg );
			if ( !$groupCounterType ) {
				// emit custom counter definitions
				$groupCounterType = "custom-group-label-$group";
				$cssSel = "@counter-style $groupCounterType";
				$cssRules = [];
				$symbols = preg_split( "/\s+/", $msg );
				$alphabeticBase = wfCheckIfAlphabetic( $symbols );
				if ( $alphabeticBase ) {
					$cssRules[] = "system: alphabetic;";
					$cssRules[] = "symbols: " . preg_replace( "/([^\s]+)/", "'$1'", implode( " ", $alphabeticBase ) ) . ";";
				} else {
					$cssRules[] = "system: fixed;";
					$cssRules[] = "symbols: " . preg_replace( "/([^\s]+)/", "'$1'", $msg ) . ";";
				}
				wfEmitCSS( $cssSel, $cssRules );
			}
			$groupLabels[$group] = $groupCounterType;
		} elseif ( $key === "references_link_many_format_backlink_labels" ) {
			$linkbackCounterType = wfDetectCounterType( $msg );
			if ( !$linkbackCounterType ) {
				$linkbackCounterType = "custom-backlink";
				$cssSel = "@counter-style custom-backlink";
				$cssRules = [];
				$symbols = preg_split( "/\s+/", $msg );
				$alphabeticBase = wfCheckIfAlphabetic( $symbols );
				if ( $alphabeticBase ) {
					$cssRules[] = "system: alphabetic;";
					$cssRules[] = "symbols: " . preg_replace( "/([^\s]+)/", "'$1'", implode( " ", $alphabeticBase ) ) . ";";
				} else {
					$cssRules[] = "system: fixed;";
					$cssRules[] = "symbols: " . preg_replace( "/([^\s]+)/", "'$1'", $msg ) . ";";
				}

				// Some wikis may define custom messages for backlinks
				// but not actually use them in references_link_many_format
				// by using default messages! So, we cannot init them here!
				$initCustomLinkbackCounter = wfGetCSS( $cssSel, $cssRules );
			}
		}
	}

	// Generate other CSS rules
	foreach ( $messages as $key => $msg ) {
		switch ( $key ) {
			case "reference_link":
				// "[$3]" is the effective default Parsoid CSS output
				if ( $msg !== "[$3]" ) {
					$cssSel = '.mw-ref > a::after';
					$parts = preg_split( "/\\$3/", $msg );
					$rule = "content:";
					if ( $parts[0] !== "" ) {
						$rule .= " '" . $parts[0] . "'";
					}
					// FIXME: the counter is language-specific
					// but editors can fix this or we can edit it manually
					$rule .= " counter( mw-Ref, $refCounterType )";
					if ( $parts[1] !== "" ) {
						$rule .= " '" . $parts[1] . "'";
					}
					$rule .= ";";
					wfEmitCSS( $cssSel, [ $rule ] );

					// Add default CSS rule for groups
					$cssSel = ".mw-ref > a[data-mw-group]::after";
					$baseRule = $rule;
					$newRule = preg_replace( "/counter\(/", "attr(data-mw-group) ' ' counter(", $baseRule );
					wfEmitCSS( $cssSel, [ $newRule ] );

					// Add CSS rules for groups with custom counters
					$baseRule = $rule;
					foreach ( $groupLabels as $group => $groupCounterType ) {
						$cssSel = ".mw-ref > a[data-mw-group=$group]::after";
						$newRule = preg_replace( "/$refCounterType/", "$groupCounterType", $baseRule );
						wfEmitCSS( $cssSel, [ $newRule ] );
					}

					/**
					 * // This won't execute because $refCountertype is known to be 'decimal'
					 * // but leaving behind as documentation
					 * if ( $resetRefCounterTypes ) {
					 * 	$cssSel = ".mw-ref > a[ data-mw-group ]::after";
					 * 	$rule = preg_replace( "/$refCounterType/", "decimal", $baseRule );
					 * 	wfEmitCSS( $cssSel, [ $rule ] );
					 * }
					 */
				} else {
					if ( $resetRefCounterTypes ) {
						wfEmitCSS(
							".mw-ref > a::after",
							[ "content: '[' counter( mw-Ref, decimal ) ']';" ]
						);

						wfEmitCSS(
							".mw-ref > a[ data-mw-group ]::after",
							[ "content: '[' attr( data-mw-group ) ' ' counter( mw-Ref, decimal ) ']';" ]
						);
					}
					// Add CSS rules for ref-groups
					foreach ( $groupLabels as $group => $groupCounterType ) {
						$cssSel = ".mw-ref > a[data-mw-group=$group]::after";
						$rule = "content: '[' counter( mw-Ref, $groupCounterType ) ']';";
						wfEmitCSS( $cssSel, [ $rule ] );
					}
				}
				break;

			case "references_link_one":
				// "↑ " is the effective default Parsoid CSS output
				$msg = rtrim( $msg );
				if ( $msg !== "↑" ) {
					$cssSel = 'a[ rel="mw:referencedBy" ]::before';
					$cssRules = [];
					wfAddCSSForIBTagsAndProcessMsg( $cssRules, $msg );
					$cssRules[] = 'content: "' . $msg . '";';
					wfEmitCSS( $cssSel, $cssRules );
				}
				break;

			case "references_link_many":
				if ( preg_match( "/<sup>/", $msg ) ) {
					$cssSel = 'span[ rel="mw:referencedBy" ] > a::after';
					$cssRules = [ 'font-size: smaller;' ];
					wfEmitCSS( $cssSel, $cssRules );

					$cssSel = 'span[ rel="mw:referencedBy" ] > a:nth-last-child(2)::after';
					$cssRules = [ "vertical-align: super;" ];
					wfEmitCSS( $cssSel, $cssRules );
				}

				$msg = preg_replace( "/<\/?sup[^>]*>/", "", $msg );
				$msg = preg_replace( "/\\$2 \\$3/", "", $msg );

				if ( $msg !== "↑ " ) { // "↑ " is the default
					$cssSel = 'span[ rel="mw:referencedBy" ]::before';
					$cssRules = [];
					wfAddCSSForIBTagsAndProcessMsg( $cssRules, $msg );
					$cssRules[] = 'content: "' . $msg . '";';
					wfEmitCSS( $cssSel, $cssRules );
				}
				break;

			case "references_link_many_format":
				// "$2" is the effective default Parsoid CSS output
				// with decimal as the counter type and "." as the separator.
				if ( $msg !== "$2" || ( $langCounterType !== "decimal" && $langSep !== "." ) ) {
					$cssSel = 'span[ rel="mw:referencedBy" ] > a::before';
					$cssRules = [];
					wfAddCSSForIBTagsAndProcessMsg( $cssRules, $msg );

					$counterRef = preg_match( "/\\$2/", $msg ) ? "\\$2" : "\\$3";
					$parts = preg_split( "/$counterRef/", $msg );
					$rule = 'content:';
					if ( $parts[0] !== "" ) {
						$rule .= " '" . $parts[0] . "'";
					}

					// Default is Y where Y is mw-ref-linkback counter
					if ( $counterRef === "\\$2" ) {
						$counter = $resetRefCounterTypes ? "decimal" : $langCounterType;
						// X.Y where X is mw-references counter and Y is mw-ref-linkback counter
						$linkbackRule =
							"counter( mw-references, $counter )" . " '$langSep' " .
							"counter( mw-ref-linkback, $counter )";
					} else { // implicilty assumed to be "\\$3"
						// Init custom counter types
						if ( $initCustomLinkbackCounter ) {
							print $initCustomLinkbackCounter . "\n";
						}
						// These aren't 2.0, 2.1 ... etc. style counters
						// but more like 1,2,3 style counters so we need
						// to override counter-reset to 0.
						wfEmitCSS(
							'span[ rel="mw:referencedBy" ]',
							[ 'counter-reset: mw-ref-linkback 0;' ]
						);
						$linkbackRule = "counter( mw-ref-linkback, $linkbackCounterType )";
					}

					$rule .= " $linkbackRule";
					if ( count( $parts ) > 1 && $parts[1] !== "" ) {
						$rule .= " '" . $parts[1] . "'";
					}
					$rule .= ";";
					$cssRules[] = $rule;
					wfEmitCSS( $cssSel, $cssRules );
				} elseif ( $resetRefCounterTypes ) {
					$cssSel = 'span[ rel="mw:referencedBy" ] > a::before';
					$linkbackRule = 'content: ' .
						"counter( mw-references, decimal )" . " '$langSep' " .
						"counter( mw-ref-linkback, decimal )";
					$cssRules = [ $linkbackRule ];
					wfEmitCSS( $cssSel, $cssRules );
				}
				break;

			case "references_link_many_and":
				if ( $msg !== ' ' ) { // ' ' is the default
					$cssSel = 'span[ rel="mw:referencedBy" ] > a:nth-last-child(2)::after';
					$cssRules = [];
					wfAddCSSForIBTagsAndProcessMsg( $cssRules, $msg );
					$cssRules[] = 'content: "' . $msg . '";';
					wfEmitCSS( $cssSel, $cssRules );
				}
				break;

			case "references_link_many_sep":
				if ( $msg !== ' ' ) { // ' ' is the default
					$cssSel = 'span[ rel="mw:referencedBy" ] > a::after';
					$cssRules = [];
					wfAddCSSForIBTagsAndProcessMsg( $cssRules, $msg );
					$cssRules[] = 'content: "' . $msg . '";';
					wfEmitCSS( $cssSel, $cssRules );
				}
				break;

			default:
				break;
		}
	}
}
