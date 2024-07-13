<?php

namespace MediaWiki\Extension\Math;

use ParserOptions;
use ValueFormatters\FormatterOptions;
use ValueParsers\StringParser;
use Wikibase\Repo\Parsers\WikibaseStringValueNormalizer;
use Wikibase\Repo\Rdf\DedupeBag;
use Wikibase\Repo\Rdf\EntityMentionListener;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

class WikibaseHook {

	/**
	 * Add Datatype "Math" to the Wikibase Repository
	 * @param array[] &$dataTypeDefinitions
	 */
	public static function onWikibaseRepoDataTypes( array &$dataTypeDefinitions ) {
		global $wgMathEnableWikibaseDataType;

		if ( !$wgMathEnableWikibaseDataType ) {
			return;
		}

		$dataTypeDefinitions['PT:math'] = [
			'value-type'                 => 'string',
			'validator-factory-callback' => static function () {
				// load validator builders
				$factory = WikibaseRepo::getDefaultValidatorBuilders();

				// initialize an array with string validators
				// returns an array of validators
				// that add basic string validation such as preventing empty strings
				$validators = $factory->buildStringValidators();
				$validators[] = new MathValidator();
				return $validators;
			},
			'parser-factory-callback' => static function ( ParserOptions $options ) {
				$normalizer = new WikibaseStringValueNormalizer( WikibaseRepo::getStringNormalizer() );
				return new StringParser( $normalizer );
			},
			'formatter-factory-callback' => static function ( $format, FormatterOptions $options ) {
				return new MathFormatter( $format );
			},
			'rdf-builder-factory-callback' => static function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new MathMLRdfBuilder();
			},
		];
	}

	/**
	 * Add Datatype "Math" to the Wikibase Client
	 * @param array[] &$dataTypeDefinitions
	 */
	public static function onWikibaseClientDataTypes( array &$dataTypeDefinitions ) {
		global $wgMathEnableWikibaseDataType;

		if ( !$wgMathEnableWikibaseDataType ) {
			return;
		}

		$dataTypeDefinitions['PT:math'] = [
			'value-type'                 => 'string',
			'formatter-factory-callback' => static function ( $format, FormatterOptions $options ) {
				return new MathFormatter( $format );
			},
		];
	}

}
