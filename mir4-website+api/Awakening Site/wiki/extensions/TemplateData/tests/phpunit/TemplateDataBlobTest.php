<?php

use MediaWiki\Extension\TemplateData\Api\ApiTemplateData;
use MediaWiki\Extension\TemplateData\TemplateDataBlob;
use MediaWiki\Extension\TemplateData\TemplateDataHtmlFormatter;
use MediaWiki\Extension\TemplateData\TemplateDataValidator;
use MediaWiki\Language\RawMessage;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Database
 * @covers \MediaWiki\Extension\TemplateData\TemplateDataBlob
 * @covers \MediaWiki\Extension\TemplateData\TemplateDataCompressedBlob
 * @covers \MediaWiki\Extension\TemplateData\TemplateDataNormalizer
 * @covers \MediaWiki\Extension\TemplateData\TemplateDataValidator
 * @license GPL-2.0-or-later
 */
class TemplateDataBlobTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( 'wgLanguageCode', 'qqx' );
	}

	/**
	 * Helper method to generate a string that gzip can't compress.
	 *
	 * Output is consistent when given the same seed.
	 * @param int $minLength
	 * @param int $seed
	 * @return string
	 */
	private function generatePseudorandomString( int $minLength, int $seed ): string {
		srand( $seed );
		$string = '';
		while ( strlen( $string ) < $minLength ) {
			$string .= str_shuffle( '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' );
		}
		return $string;
	}

	public static function provideParse() {
		$cases = [
			[
				'input' => '{',
				'status' => '(templatedata-invalid-parse)'
			],
			[
				'input' => '[]
				',
				'status' => '(templatedata-invalid-type: templatedata, object)'
			],
			[
				'input' => '{
					"params": {}
				}
				',
				'output' => '{
					"description": null,
					"params": {},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'status' => true,
				'msg' => 'Minimal valid blob'
			],
			[
				'input' => '{
					"params": {},
					"foo": "bar"
				}
				',
				'status' => '(templatedata-invalid-unknown: foo)',
				'msg' => 'Unknown properties'
			],
			[
				'input' => '{ "description": [], "params": {} }',
				'status' => '(templatedata-invalid-type: description, string|object)',
			],
			[
				'input' => '{}',
				'status' => '(templatedata-invalid-missing: params, object)',
				'msg' => 'Empty object'
			],
			[
				'input' => '{ "params": [] }',
				'status' => '(templatedata-invalid-type: params, object)',
			],
			[
				'input' => '{ "params": { "": {} } }',
				'status' => '(templatedata-invalid-unnamed-parameter)',
			],
			[
				'input' => '{ "params": { "a": [] } }',
				'status' => '(templatedata-invalid-type: params.a, object)',
			],
			[
				'input' => '{ "params": { "a": { "foo": "" } } }',
				'status' => '(templatedata-invalid-unknown: params.a.foo)',
			],
			[
				'input' => '{ "params": { "a": { "label": [] } } }',
				'status' => '(templatedata-invalid-type: params.a.label, string|object)',
			],
			[
				'input' => '{ "params": { "a": { "required": "" } } }',
				'status' => '(templatedata-invalid-type: params.a.required, boolean)',
			],
			[
				'input' => '{ "params": { "a": { "suggested": "" } } }',
				'status' => '(templatedata-invalid-type: params.a.suggested, boolean)',
			],
			[
				'input' => '{ "params": { "a": { "description": [] } } }',
				'status' => '(templatedata-invalid-type: params.a.description, string|object)',
			],
			[
				'input' => '{ "params": { "a": { "example": [] } } }',
				'status' => '(templatedata-invalid-type: params.a.example, string|object)',
			],
			[
				'input' => '{ "params": { "a": { "deprecated": [] } } }',
				'status' => '(templatedata-invalid-type: params.a.deprecated, boolean|string)',
			],
			[
				'input' => '{ "params": { "a": { "aliases": "" } } }',
				'status' => '(templatedata-invalid-type: params.a.aliases, array)',
			],
			[
				'input' => '{ "params": { "a": { "aliases": [ "1", 2, {} ] } } }',
				'status' => '(templatedata-invalid-type: params.a.aliases[2], int|string)',
			],
			[
				'input' => '{ "params": { "a": { "autovalue": [] } } }',
				'status' => '(templatedata-invalid-type: params.a.autovalue, string)',
			],
			[
				'input' => '{ "params": { "a": { "default": [] } } }',
				'status' => '(templatedata-invalid-type: params.a.default, string|object)',
			],
			[
				'input' => '{ "params": { "a": { "type": [] } } }',
				'status' => '(templatedata-invalid-type: params.a.type, string)',
			],
			[
				'input' => '{ "params": { "a": { "type": "" } } }',
				'status' => '(templatedata-invalid-value: params.a.type)',
			],
			[
				'input' => '{ "params": { "a": { "suggestedvalues": "" } } }',
				'status' => '(templatedata-invalid-type: params.a.suggestedvalues, array)',
			],
			[
				'input' => '{ "params": { "a": { "suggestedvalues": [ {} ] } } }',
				'status' => '(templatedata-invalid-type: params.a.suggestedvalues[0], string)',
			],
			[
				'input' => '{
					"params": {
						"foo": {}
					}
				}
				',
				'output' => '{
					"description": null,
					"params": {
						"foo": {
							"label": null,
							"description": null,
							"default": null,
							"example": null,
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"deprecated": false,
							"aliases": [],
							"type": "unknown",
							"autovalue": null
						}
					},
					"sets": [],
					"format": null,
					"maps": {}
				}
				',
				'msg' => 'Optional properties are added if missing'
			],
			[
				'input' => '{
					"params": {
						"comment": {
							"type": "string/line"
						}
					}
				}
				',
				'output' => '{
					"description": null,
					"params": {
						"comment": {
							"label": null,
							"description": null,
							"default": null,
							"example": null,
							"autovalue": null,
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"deprecated": false,
							"aliases": [],
							"type": "line"
						}
					},
					"sets": [],
					"format": null,
					"maps": {}
				}
				',
				'msg' => 'Old string/* types are mapped to the unprefixed versions'
			],
			[
				'input' => '{
					"description": "User badge MediaWiki developers.",
					"params": {
						"nickname": {
							"label": null,
							"description": "User name of user who owns the badge",
							"default": "Base page name of the host page",
							"example": null,
							"required": false,
							"suggested": true,
							"aliases": [ 1 ]
						}
					}
				}
				',
				'output' => '{
					"description": {
						"qqx": "User badge MediaWiki developers."
					},
					"params": {
						"nickname": {
							"label": null,
							"description": {
								"qqx": "User name of user who owns the badge"
							},
							"default": {
								"qqx": "Base page name of the host page"
							},
							"example": null,
							"required": false,
							"suggested": true,
							"suggestedvalues": [],
							"deprecated": false,
							"aliases": [
								"1"
							],
							"type": "unknown",
							"autovalue": null
						}
					},
					"sets": [],
					"format": null,
					"maps": {}
				}
				',
				'msg' => 'InterfaceText is expanded to langcode-keyed object, assuming content language'
			],
			[
				'input' => '{ "params": { "b": { "inherits": "a" } } }',
				'status' => '(templatedata-invalid-missing: params.a)',
			],
			[
				'input' => '{
					"description": "Document the documenter.",
					"params": {
						"1d": {
							"description": "Description of the template parameter",
							"required": true,
							"default": "example"
						},
						"2d": {
							"inherits": "1d",
							"default": "overridden"
						}
					}
				}
				',
				'output' => '{
					"description": {
						"qqx": "Document the documenter."
					},
					"params": {
						"1d": {
							"label": null,
							"description": {
								"qqx": "Description of the template parameter"
							},
							"example": null,
							"required": true,
							"suggested": false,
							"suggestedvalues": [],
							"default": {
								"qqx": "example"
							},
							"deprecated": false,
							"aliases": [],
							"type": "unknown",
							"autovalue": null
						},
						"2d": {
							"label": null,
							"description": {
								"qqx": "Description of the template parameter"
							},
							"example": null,
							"required": true,
							"suggested": false,
							"suggestedvalues": [],
							"default": {
								"qqx": "overridden"
							},
							"deprecated": false,
							"aliases": [],
							"type": "unknown",
							"autovalue": null
						}
					},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'msg' => 'The inherits property copies over properties from another parameter '
					. '(preserving overides)'
			],
			[
				'input' => '{ "params": {}, "sets": {} }',
				'status' => '(templatedata-invalid-type: sets, array)'
			],
			[
				'input' => '{ "params": {}, "sets": [ [] ] }',
				'status' => '(templatedata-invalid-value: sets.0)'
			],
			[
				'input' => '{
					"params": {},
					"sets": [
						{
							"label": "Example"
						}
					]
				}',
				'status' => '(templatedata-invalid-missing: sets.0.params, array)'
			],
			[
				'input' => '{ "params": {}, "sets": [ { "label": "", "params": {} } ] }',
				'status' => '(templatedata-invalid-type: sets.0.params, array)'
			],
			[
				'input' => '{ "params": {}, "sets": [ { "label": "", "params": [] } ] }',
				'status' => '(templatedata-invalid-empty-array: sets.0.params)'
			],
			[
				'input' => '{
					"params": {
						"foo": {
						}
					},
					"sets": [
						{
							"params": ["foo"]
						}
					]
				}',
				'status' => '(templatedata-invalid-missing: sets.0.label, string|object)'
			],
			[
				'input' => '{ "params": {}, "sets": [ { "label": [] } ] }',
				'status' => '(templatedata-invalid-type: sets.0.label, string|object)'
			],
			[
				'input' => '{
					"params": {
						"foo": {
						},
						"bar": {
						}
					},
					"sets": [
						{
							"label": "Foo with Quux",
							"params": ["foo", "quux"]
						}
					]
				}',
				'status' => '(templatedata-invalid-value: sets.0.params[1])'
			],
			[
				'input' => '{
					"params": {
						"foo": {
						},
						"bar": {
						},
						"quux": {
						}
					},
					"sets": [
						{
							"label": "Foo with Quux",
							"params": ["foo", "quux"]
						},
						{
							"label": "Bar with Quux",
							"params": ["bar", "quux"]
						}
					]
				}',
				'output' => '{
					"description": null,
					"params": {
						"foo": {
							"label": null,
							"required": false,
							"example": null,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"deprecated": false,
							"aliases": [],
							"default": null,
							"type": "unknown",
							"autovalue": null
						},
						"bar": {
							"label": null,
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"example": null,
							"deprecated": false,
							"aliases": [],
							"default": null,
							"type": "unknown",
							"autovalue": null
						},
						"quux": {
							"label": null,
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"example": null,
							"deprecated": false,
							"aliases": [],
							"default": null,
							"type": "unknown",
							"autovalue": null
						}
					},
					"sets": [
						{
							"label": {
								"qqx": "Foo with Quux"
							},
							"params": ["foo", "quux"]
						},
						{
							"label": {
								"qqx": "Bar with Quux"
							},
							"params": ["bar", "quux"]
						}
					],
					"format": null,
					"maps": {}
				}',
				'status' => true
			],
			[
				'input' => '{
					"description": "Testing some template description.",
					"params": {
						"bar": {
							"label": "Bar label",
							"description": "Bar description",
							"default": "Baz",
							"example": "Foo bar baz",
							"autovalue": "{{SomeTemplate}}",
							"required": true,
							"suggested": false,
							"suggestedvalues": [ "baz", "boo" ],
							"deprecated": false,
							"aliases": [ "foo", "baz" ],
							"type": "line"
						}
					}
				}
				',
				'output' => '{
					"description": {
						"qqx": "Testing some template description."
					},
					"params": {
						"bar": {
							"label": {
								"qqx": "Bar label"
							},
							"description": {
								"qqx": "Bar description"
							},
							"default": {
								"qqx": "Baz"
							},
							"example": {
								"qqx": "Foo bar baz"
							},
							"autovalue": "{{SomeTemplate}}",
							"required": true,
							"suggested": false,
							"suggestedvalues": [ "baz", "boo" ],
							"deprecated": false,
							"aliases": [ "foo", "baz" ],
							"type": "line"
						}
					},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'msg' => 'Parameter attributes preserve information.'
			],
			[
				'input' => '{ "params": {}, "maps": [] }',
				'status' => '(templatedata-invalid-type: maps, object)'
			],
			[
				'input' => '{ "params": {}, "maps": { "a": [] } }',
				'status' => '(templatedata-invalid-type: maps.a, object)'
			],
			[
				'input' => '{ "params": {}, "maps": { "a": { "b": "c" } } }',
				'status' => '(templatedata-invalid-param: c, maps.a.b)'
			],
			[
				'input' => '{ "params": {}, "maps": { "a": { "b": [ {} ] } } }',
				'status' => '(templatedata-invalid-type: maps.a.b[0], string|array)'
			],
			[
				'input' => '{ "params": {}, "maps": { "a": { "b": [ "c" ] } } }',
				'status' => '(templatedata-invalid-param: c, maps.a.b[0])'
			],
			[
				'input' => '{
					"params": {
						"foo": {
						},
						"bar": {
						}
					},
					"sets": [],
					"maps": {
						"application": {
							"things": [
								"foo",
								["bar", "quux"]
							]
						}
					}
				}',
				'status' => '(templatedata-invalid-param: quux, maps.application.things[1][1])'
			],
			[
				'input' => '{
					"params": {
						"foo": {
						},
						"bar": {
						}
					},
					"sets": [],
					"maps": {
						"application": {
							"things": {
								"appbar": "bar",
								"appfoo": "foo"
							}
						}
					}
				}',
				'status' => '(templatedata-invalid-type: maps.application.things, string|array)'
			],
			[
				'input' => '{
					"params": {
						"foo": {
						},
						"bar": {
						}
					},
					"sets": [],
					"maps": {
						"application": {
							"things": [
								[ true ]
							]
						}
					}
				}',
				'status' => '(templatedata-invalid-type: maps.application.things[0][0], string)'
			],
			[
				'input' => '{ "params": {}, "format": "" }',
				'status' => '(templatedata-invalid-format: format)'
			],
			[
				'input' => '{
					"params": {
						"foo": {}
					},
					"format": "meshuggah format"
				}',
				'status' => '(templatedata-invalid-format: format)'
			],
			[
				'input' => '{
					"params": {},
					"format": "inline"
				}',
				'output' => '{
					"description": null,
					"params": {},
					"sets": [],
					"format": "inline",
					"maps": {}
				}
				',
				'msg' => '"inline" is a valid format string',
				'status' => true
			],
			[
				'input' => '{
					"params": {},
					"format": "block"
				}',
				'output' => '{
					"description": null,
					"params": {},
					"sets": [],
					"format": "block",
					"maps": {}
				}
				',
				'msg' => '"block" is a valid format string',
				'status' => true
			],
			[
				'input' => '{
					"params": {},
					"format": "{{_ |\n ___ = _}}"
				}',
				'output' => '{
					"description": null,
					"params": {},
					"sets": [],
					"format": "{{_ |\n ___ = _}}",
					"maps": {}
				}
				',
				'msg' => 'Custom parameter format string (1)',
				'status' => true
			],
			[
				'input' => '{
					"params": {},
					"format": "{{_|_=_\n}}\n"
				}',
				'output' => '{
					"description": null,
					"params": {},
					"sets": [],
					"format": "{{_|_=_\n}}\n",
					"maps": {}
				}
				',
				'msg' => 'Custom parameter format string (2)',
				'status' => true
			],
		];

		foreach ( $cases as $case ) {
			yield [ $case ];
		}
	}

	private function getStatusText( Status $status ): string {
		$str = Parser::stripOuterParagraph( $status->getHtml() );
		// Unescape char references for things like "[, "]" and "|" for
		// cleaner test assertions and output
		$str = Sanitizer::decodeCharReferences( $str );
		return $str;
	}

	private function ksort( array &$input ): void {
		ksort( $input );
		foreach ( $input as &$value ) {
			if ( is_array( $value ) ) {
				$this->ksort( $value );
			}
		}
	}

	/**
	 * PHPUnit'a assertEquals does weak comparison, use strict instead.
	 *
	 * There is a built-in assertSame, but that only strictly compares
	 * the top level structure, not the invidual array values.
	 *
	 * so "array( 'a' => '' )" still equals "array( 'a' => null )"
	 * because empty string equals null in PHP's weak comparison.
	 *
	 * @param string $expected
	 * @param stdClass $actual
	 * @param string|null $message
	 */
	private function assertStrictJsonEquals( string $expected, stdClass $actual, string $message = null ): void {
		// Lazy recursive strict comparison: Serialise to JSON and compare that
		// Sort first to ensure key-order
		$expected = json_decode( $expected, /* assoc = */ true );
		$actual = json_decode( json_encode( $actual ), /* assoc = */ true );
		$this->ksort( $expected );
		$this->ksort( $actual );

		$this->assertSame(
			FormatJson::encode( $expected, true ),
			FormatJson::encode( $actual, true ),
			$message
		);
	}

	private function assertTemplateData( array $case ): void {
		// Expand defaults
		$case['status'] ??= true;
		$case['msg'] ??= $case['status'];

		$t = TemplateDataBlob::newFromJSON( $this->db, $case['input'] );
		$actual = $t->getData();
		$status = $t->getStatus();

		$this->assertSame(
			$case['status'],
			is_string( $case['status'] ) ? $this->getStatusText( $status ) : $status->isGood(),
			$case['msg'] . ' (status "' . $this->getStatusText( $status ) . '")'
		);

		if ( !isset( $case['output'] ) ) {
			$expected = is_string( $case['status'] )
				? '{ "description": null, "params": {}, "sets": [], "maps": {}, "format": null }'
				: $case['input'];
			$this->assertStrictJsonEquals( $expected, $actual, $case['msg'] );
		} else {
			$this->assertStrictJsonEquals( $case['output'], $actual, $case['msg'] );

			// Assert this case roundtrips properly by running through the output as input.
			$t = TemplateDataBlob::newFromJSON( $this->db, $case['output'] );
			$status = $t->getStatus();

			if ( !$status->isGood() ) {
				$this->assertSame( $case['status'], $this->getStatusText( $status ),
					'Roundtrip status: ' . $case['msg']
				);
			}
			$this->assertStrictJsonEquals( $case['output'], $t->getData(),
				'Roundtrip: ' . $case['msg']
			);
		}
	}

	/**
	 * @dataProvider provideParse
	 */
	public function testParse( array $case ) {
		$this->assertTemplateData( $case );
	}

	/**
	 * MySQL breaks if the input is too large even after compression
	 */
	public function testParseLongString() {
		if ( $this->db->getType() !== 'mysql' ) {
			$this->markTestSkipped( 'long compressed strings break on MySQL only' );
		}

		// Should be long enough to trigger this condition after gzipping.
		$json = '{
			"description": "' . $this->generatePseudorandomString( 100000, 42 ) . '",
			"params": {}
		}';
		$templateData = TemplateDataBlob::newFromJSON( $this->db, $json );

		$this->assertStringStartsWith(
			'(templatedata-invalid-length: ',
			$this->getStatusText( $templateData->getStatus() )
		);
	}

	/**
	 * @dataProvider provideInterfaceTexts
	 */
	public function testIsValidInterfaceText( $text, bool $expected ) {
		/** @var TemplateDataValidator $validator */
		$validator = TestingAccessWrapper::newFromObject(
			new TemplateDataValidator( [] )
		);
		$this->assertSame( $expected, $validator->isValidInterfaceText( $text ) );
	}

	public static function provideInterfaceTexts() {
		return [
			// Invalid stuff
			[ null, false ],
			[ [], false ],
			[ [ 'en' => 'example' ], false ],
			[ (object)[], false ],
			[ (object)[ null ], false ],
			[ (object)[ 'en' => null ], false ],

			[ 'example', true ],
			[ (object)[ 'de' => 'Beispiel', 'en' => 'example' ], true ],

			// Empty strings are allowed
			[ '', true ],
			[ (object)[ 'en' => '' ], true ],

			// Language code can not be empty
			[ (object)[ '' => 'example' ], false ],
			[ (object)[ ' ' => 'example' ], false ],
		];
	}

	/**
	 * Verify we can gzdecode() which came in PHP 5.4.0. MediaWiki needs a
	 * fallback function for it.
	 * If this test fail, we are most probably attempting to use gzdecode()
	 * with PHP before 5.4.
	 *
	 * @see bug T56058
	 *
	 * Some databases will not be able to store compressed data cleanly
	 * but the object will be initialized properly even if compressed
	 * data are provided
	 *
	 * @see bug T203850
	 */
	public function testGetJsonForDatabase() {
		// Compress JSON to trigger the code pass in newFromDatabase that ends
		// up calling gzdecode().
		$gzJson = gzencode( '{}' );
		$templateData = TemplateDataBlob::newFromDatabase( $this->db, $gzJson );
		$this->assertInstanceOf( TemplateDataBlob::class, $templateData );
	}

	public static function provideGetDataInLanguage() {
		$cases = [
			[
				'input' => '{
					"description": {
						"de": "German",
						"nl": "Dutch",
						"en": "English",
						"de-formal": "German (formal address)"
					},
					"params": {}
				}
				',
				'output' => '{
					"description": "German",
					"params": {},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'lang' => 'de',
				'msg' => 'Simple description'
			],
			[
				'input' => '{
					"description": "Hi",
					"params": {}
				}
				',
				'output' => '{
					"description": "Hi",
					"params": {},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'lang' => 'fr',
				'msg' => 'Non multi-language value returned as is (expands to { "en": value } for' .
					' content-lang, "fr" falls back to "en")'
			],
			[
				'input' => '{
					"description": {
						"nl": "Dutch",
						"de": "German"
					},
					"params": {}
				}
				',
				'output' => '{
					"description": "Dutch",
					"params": {},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'lang' => 'fr',
				'msg' => 'Try content language before giving up on user language and fallbacks'
			],
			[
				'input' => '{
					"description": {
						"es": "Spanish",
						"de": "German"
					},
					"params": {}
				}
				',
				'output' => '{
					"description": null,
					"params": {},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'lang' => 'fr',
				'msg' => 'Description is optional, use null if no suitable fallback'
			],
			[
				'input' => '{
					"description": {
						"de": "German",
						"nl": "Dutch",
						"en": "English"
					},
					"params": {}
				}
				',
				'output' => '{
					"description": "German",
					"params": {},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'lang' => 'de-formal',
				'msg' => '"de-formal" falls back to "de"'
			],
			[
				'input' => '{
					"params": {
						"foo": {
							"label": {
								"fr": "French",
								"en": "English"
							}
						}
					}
				}
				',
				'output' => '{
					"description": null,
					"params": {
						"foo": {
							"label": "French",
							"required": false,
							"example": null,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"deprecated": false,
							"aliases": [],
							"default": null,
							"type": "unknown",
							"autovalue": null
						}
					},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'lang' => 'fr',
				'msg' => 'Simple parameter label'
			],
			[
				'input' => '{
					"params": {
						"foo": {
							"default": {
								"fr": "French",
								"en": "English"
							}
						}
					}
				}
				',
				'output' => '{
					"description": null,
					"params": {
						"foo": {
							"default": "French",
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"deprecated": false,
							"aliases": [],
							"label": null,
							"type": "unknown",
							"autovalue": null,
							"example": null
						}
					},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'lang' => 'fr',
				'msg' => 'Simple parameter default value'
			],
			[
				'input' => '{
					"params": {
						"foo": {
							"label": {
								"es": "Spanish",
								"de": "German"
							}
						}
					}
				}
				',
				'output' => '{
					"description": null,
					"params": {
						"foo": {
							"label": null,
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"example": null,
							"deprecated": false,
							"aliases": [],
							"default": null,
							"type": "unknown",
							"autovalue": null
						}
					},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'lang' => 'fr',
				'msg' => 'Parameter label is optional, use null if no matching fallback'
			],
			[
				'input' => '{
					"params": {
						"foo": {}
					},
					"sets": [
						{
							"label": {
								"es": "Spanish",
								"de": "German"
							},
							"params": ["foo"]
						}
					]
				}
				',
				'output' => '{
					"description": null,
					"params": {
						"foo": {
							"label": null,
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"example": null,
							"deprecated": false,
							"aliases": [],
							"default": null,
							"type": "unknown",
							"autovalue": null
						}
					},
					"sets": [
						{
							"label": "Spanish",
							"params": ["foo"]
						}
					],
					"format": null,
					"maps": {}
				}
				',
				'lang' => 'fr',
				'msg' => 'Set label is not optional, choose first available key as final fallback'
			],
		];

		foreach ( $cases as $case ) {
			yield [ $case ];
		}
	}

	/**
	 * @dataProvider provideGetDataInLanguage
	 */
	public function testGetDataInLanguage( array $case ) {
		// Change content-language to be non-English so we can distinguish between the
		// last 'en' fallback and the content language in our tests
		$this->setContentLang( 'nl' );

		$t = TemplateDataBlob::newFromJSON( $this->db, $case['input'] );
		$status = $t->getStatus();

		$this->assertTrue(
			$status->isGood() ?: $this->getStatusText( $status ),
			'Status is good: ' . $case['msg']
		);

		$actual = $t->getDataInLanguage( $case['lang'] );
		$this->assertJsonStringEqualsJsonString(
			$case['output'],
			json_encode( $actual ),
			$case['msg']
		);
	}

	public static function provideParamOrder() {
		$cases = [
			[
				'input' => '{
					"params": {
						"foo": {},
						"bar": {},
						"baz": {}
					}
				}
				',
				'output' => '{
					"description": null,
					"params": {
						"foo": {
							"label": null,
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"example": null,
							"deprecated": false,
							"aliases": [],
							"default": null,
							"type": "unknown",
							"autovalue": null
						},
						"bar": {
							"label": null,
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"example": null,
							"deprecated": false,
							"aliases": [],
							"default": null,
							"type": "unknown",
							"autovalue": null
						},
						"baz": {
							"label": null,
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"example": null,
							"deprecated": false,
							"aliases": [],
							"default": null,
							"type": "unknown",
							"autovalue": null
						}
					},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'msg' => 'Normalisation adds paramOrder'
			],
			[
				'input' => '{
					"params": {
						"foo": {},
						"bar": {},
						"baz": {}
					},
					"paramOrder": ["baz", "foo", "bar"]
				}
				',
				'output' => '{
					"description": null,
					"params": {
						"foo": {
							"label": null,
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"example": null,
							"deprecated": false,
							"aliases": [],
							"default": null,
							"type": "unknown",
							"autovalue": null
						},
						"bar": {
							"label": null,
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"example": null,
							"deprecated": false,
							"aliases": [],
							"default": null,
							"type": "unknown",
							"autovalue": null
						},
						"baz": {
							"label": null,
							"required": false,
							"suggested": false,
							"suggestedvalues": [],
							"description": null,
							"example": null,
							"deprecated": false,
							"aliases": [],
							"default": null,
							"type": "unknown",
							"autovalue": null
						}
					},
					"paramOrder": ["baz", "foo", "bar"],
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'msg' => 'Custom paramOrder'
			],
			[
				'input' => '{ "params": {}, "paramOrder": {} }',
				'status' => '(templatedata-invalid-type: paramOrder, array)',
			],
			[
				'input' => '{
					"params": {
						"foo": {},
						"bar": {},
						"baz": {}
					},
					"paramOrder": ["foo", "bar"]
				}
				',
				'status' => '(templatedata-invalid-missing: paramOrder[ "baz" ])',
				'msg' => 'Incomplete paramOrder'
			],
			[
				'input' => '{
					"params": {}
				}
				',
				'output' => '{
					"description": null,
					"params": {},
					"sets": [],
					"format": null,
					"maps" : {}
				}
				',
				'msg' => 'Empty parameter object produces empty array paramOrder'
			],
			[
				'input' => '{
					"params": {
						"foo": {},
						"bar": {},
						"baz": {}
					},
					"paramOrder": ["foo", "bar", "baz", "quux"]
				}
				',
				'status' => '(templatedata-invalid-value: paramOrder[ "quux" ])',
				'msg' => 'Unknown params in paramOrder'
			],
			[
				'input' => '{
					"params": {
						"foo": {},
						"bar": {},
						"baz": {}
					},
					"paramOrder": ["foo", "bar", "baz", "bar"]
				}
				',
				'status' => '(templatedata-invalid-duplicate-value: paramOrder[3], paramOrder[1], bar)',
				'msg' => 'Duplicate params in paramOrder'
			],
		];

		foreach ( $cases as $case ) {
			yield [ $case ];
		}
	}

	/**
	 * @dataProvider provideParamOrder
	 */
	public function testParamOrder( array $case ) {
		$this->assertTemplateData( $case );
	}

	/**
	 * @covers \MediaWiki\Extension\TemplateData\Api\ApiTemplateData
	 * @dataProvider provideGetRawParams
	 */
	public function testGetRawParams( string $inputWikitext, array $expectedParams ) {
		/** @var ApiTemplateData $api */
		$api = TestingAccessWrapper::newFromObject( $this->createMock( ApiTemplateData::class ) );
		$params = $api->getRawParams( $inputWikitext );
		$this->assertArrayEquals( $expectedParams, $params, true, true );
	}

	public static function provideGetRawParams() {
		return [
			'No params' => [
				'Lorem ipsum {{tpl}}.',
				[]
			],
			'Two plain params' => [
				'Lorem {{{name}}} ipsum {{{surname}}}',
				[ 'name' => [], 'surname' => [] ]
			],
			'Param with multiple casing and default value' => [
				'Lorem {{{name|{{{Name|Default name}}}}}} ipsum',
				[ 'name' => [] ]
			],
			'Param name contains comment' => [
				'Lorem {{{name<!-- comment -->}}} ipsum',
				[ 'name' => [] ]
			],
			'Letter-case and underscore-space normalization' => [
				'Lorem {{{First name|{{{first_name}}}}}} ipsum {{{first-Name}}}',
				[ 'First name' => [] ]
			],
			'Dynamic param name' => [
				'{{{{{#if:{{{nominee|}}}|nominee|candidate}}|}}}',
				[ 'nominee' => [] ]
			],
			'More complicated dynamic param name' => [
				'{{{party{{#if:{{{party_election||}}}|_election||}}|}}}',
				[ 'party_election' => [] ]
			],
			'Bang in a param name' => [
				'{{{!}}} {{{foo!}}}',
				[ '!' => [], 'foo!' => [] ]
			],
			'Bang as a magic word in a table construct' => [
				'{{{!}} class=""',
				[]
			],
			'Params within comments and nowiki tags' => [
				'Lorem <!-- {{{name}}} --> ipsum <nowiki  > {{{middlename}}}' .
					'</nowiki> <pre>{{{pre}}}</pre> {{{surname}}}',
				[ 'surname' => [] ]
			],
			'Param within comments and param name outside with comment' => [
				'Lorem {{{name<!--comment-->}}} ipsum <!--{{{surname}}}-->',
				[ 'name' => [] ]
			],
			'safesubst: hack with an unnamed parameter' => [
				'{{ {{{|safesubst:}}}#invoke:…|{{{1}}}|{{{ 1 }}}}}',
				[ '1' => [] ]
			],
			'Characters impossible in parameter names' => [
				'{{test|a|b=c|d=e=f}} {{{a|b}}} {{{d=e}}}',
				[ 'a' => [] ]
			],
			'Characters that are, while technically possible, almost certainly a mistake' => [
				"{{{a{a}}} {{{b}b}}} {{{c\nc}}}",
				[]
			],
			'Table syntax escaped with {{!}}' => [
				'{{{!}}\n! This is table syntax\n{{!}}}',
				[]
			],
		];
	}

	public static function provideGetHtml() {
		// phpcs:disable Generic.Files.LineLength.TooLong
		yield 'No params' => [
			[ 'params' => [ (object)[] ] ],
			<<<HTML
<section class="mw-templatedata-doc-wrap">
<header><p class="mw-templatedata-doc-desc mw-templatedata-doc-muted">(templatedata-doc-desc-empty)</p></header>
<table class="wikitable mw-templatedata-doc-params">
	<caption>
		<p class="mw-templatedata-caption">(templatedata-doc-params)<mw:edittemplatedata page="Template:Test/doc"></mw:edittemplatedata></p>
	</caption>
	<thead><tr><th colspan="2">(templatedata-doc-param-name)</th><th>(templatedata-doc-param-desc)</th><th>(templatedata-doc-param-type)</th><th>(templatedata-doc-param-status)</th></tr></thead>
	<tbody>
		<tr>
			<td class="mw-templatedata-doc-muted" colspan="7">(templatedata-doc-no-params-set)</td>
		</tr>
	</tbody>
</table>
</section>
HTML
		];
		yield 'Basic params' => [
			[ 'params' => [ 'foo' => (object)[], 'bar' => [ 'required' => true ] ] ],
			<<<HTML
<section class="mw-templatedata-doc-wrap">
<header><p class="mw-templatedata-doc-desc mw-templatedata-doc-muted">(templatedata-doc-desc-empty)</p></header>
<table class="wikitable mw-templatedata-doc-params sortable">
	<caption>
		<p class="mw-templatedata-caption">(templatedata-doc-params)<mw:edittemplatedata page="Template:Test/doc"></mw:edittemplatedata></p>
	</caption>
	<thead><tr><th colspan="2">(templatedata-doc-param-name)</th><th>(templatedata-doc-param-desc)</th><th>(templatedata-doc-param-type)</th><th>(templatedata-doc-param-status)</th></tr></thead>
	<tbody>
		<tr>
			<th>foo</th>
			<td class="mw-templatedata-doc-param-name"><code>foo</code></td>
			<td><p class="mw-templatedata-doc-muted">(templatedata-doc-param-desc-empty)</p><dl></dl></td>
			<td class="mw-templatedata-doc-param-type mw-templatedata-doc-muted">(templatedata-doc-param-type-unknown)</td>
			<td class="mw-templatedata-doc-param-status-optional" data-sort-value="0">(templatedata-doc-param-status-optional)</td>
		</tr>
		<tr>
			<th>bar</th>
			<td class="mw-templatedata-doc-param-name"><code>bar</code></td>
			<td><p class="mw-templatedata-doc-muted">(templatedata-doc-param-desc-empty)</p><dl></dl></td>
			<td class="mw-templatedata-doc-param-type mw-templatedata-doc-muted">(templatedata-doc-param-type-unknown)</td>
			<td class="mw-templatedata-doc-param-status-required" data-sort-value="2">(templatedata-doc-param-status-required)</td>
		</tr>
	</tbody>
</table>
</section>
HTML
		];
		yield [
			[ 'description' => 'Template docs', 'params' => [
				'suggestedParam' => [
					'label' => 'Label',
					'description' => 'Param docs',
					'aliases' => [ 'Alias1', 'Alias2' ],
					'suggestedvalues' => [ 'Suggested1', 'Suggested2' ],
					'suggested' => true,
					'default' => 'Default docs',
					'example' => 'Example docs',
					'autovalue' => 'Auto value',
				],
				'deprecatedParam' => [ 'type' => 'date', 'deprecated' => true ],
			] ],
			<<<HTML
<section class="mw-templatedata-doc-wrap">
<header><p class="mw-templatedata-doc-desc">Template docs</p></header>
<table class="wikitable mw-templatedata-doc-params sortable">
	<caption>
		<p class="mw-templatedata-caption">(templatedata-doc-params)<mw:edittemplatedata page="Template:Test/doc"></mw:edittemplatedata></p>
	</caption>
	<thead><tr><th colspan="2">(templatedata-doc-param-name)</th><th>(templatedata-doc-param-desc)</th><th>(templatedata-doc-param-type)</th><th>(templatedata-doc-param-status)</th></tr></thead>
	<tbody>
		<tr>
			<th>Label</th>
			<td class="mw-templatedata-doc-param-name"><code>suggestedParam</code> <code class="mw-templatedata-doc-param-alias">Alias1</code> <code class="mw-templatedata-doc-param-alias">Alias2</code></td>
			<td>
				<p>Param docs</p>
				<dl>
					<dt>(templatedata-doc-param-suggestedvalues)</dt><dd><code>Suggested1</code> <code>Suggested2</code></dd>
					<dt>(templatedata-doc-param-default)</dt><dd>Default docs</dd>
					<dt>(templatedata-doc-param-example)</dt><dd>Example docs</dd>
					<dt>(templatedata-doc-param-autovalue)</dt><dd><code>Auto value</code></dd>
				</dl>
			</td>
			<td class="mw-templatedata-doc-param-type mw-templatedata-doc-muted">(templatedata-doc-param-type-unknown)</td>
			<td class="mw-templatedata-doc-param-status-suggested" data-sort-value="1">(templatedata-doc-param-status-suggested)</td>
		</tr>
		<tr>
			<th>deprecatedParam</th>
			<td class="mw-templatedata-doc-param-name"><code>deprecatedParam</code></td>
			<td><p class="mw-templatedata-doc-muted">(templatedata-doc-param-desc-empty)</p><dl></dl></td>
			<td class="mw-templatedata-doc-param-type">(templatedata-doc-param-type-date)</td>
			<td class="mw-templatedata-doc-param-status-deprecated" data-sort-value="-1">(templatedata-doc-param-status-deprecated)</td>
		</tr>
	</tbody>
</table>
</section>
HTML
		];
	}

	/**
	 * @covers \MediaWiki\Extension\TemplateData\TemplateDataHtmlFormatter
	 * @dataProvider provideGetHtml
	 */
	public function testGetHtml( array $data, string $expected ) {
		$t = TemplateDataBlob::newFromJSON( $this->db, json_encode( $data ) );
		$localizer = new class implements MessageLocalizer {

			public function msg( $key, ...$params ) {
				return new RawMessage( "($key)" );
			}

		};
		$title = Title::newFromText( 'Template:Test/doc' );
		$formatter = new TemplateDataHtmlFormatter( $localizer );
		$actual = $formatter->getHtml( $t, $title );
		$linedActual = preg_replace( '/>\s*</', ">\n<", $actual );

		$linedExpected = preg_replace( '/>\s*</', ">\n<", trim( $expected ) );

		$this->assertSame( $linedExpected, $linedActual );
	}

}
