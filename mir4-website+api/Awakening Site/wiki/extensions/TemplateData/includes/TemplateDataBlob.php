<?php

namespace MediaWiki\Extension\TemplateData;

use MediaWiki\MediaWikiServices;
use MediaWiki\Status\Status;
use stdClass;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * Represents the information about a template,
 * coming from the JSON blob in the <templatedata> tags
 * on wiki pages.
 * @license GPL-2.0-or-later
 */
class TemplateDataBlob {

	protected string $json;
	protected Status $status;

	/**
	 * Parse and validate passed JSON and create a blob handling
	 * instance.
	 * Accepts and handles user-provided data.
	 *
	 * @param IReadableDatabase $db
	 * @param string $json
	 * @return TemplateDataBlob
	 */
	public static function newFromJSON( IReadableDatabase $db, string $json ): TemplateDataBlob {
		if ( $db->getType() === 'mysql' ) {
			$tdb = new TemplateDataCompressedBlob( $json );
		} else {
			$tdb = new TemplateDataBlob( $json );
		}
		return $tdb;
	}

	/**
	 * Parse and validate passed JSON (possibly gzip-compressed) and create a blob handling
	 * instance.
	 *
	 * @param IReadableDatabase $db
	 * @param string $json
	 * @return TemplateDataBlob
	 */
	public static function newFromDatabase( IReadableDatabase $db, string $json ): TemplateDataBlob {
		// Handle GZIP compression. \037\213 is the header for GZIP files.
		if ( substr( $json, 0, 2 ) === "\037\213" ) {
			$json = gzdecode( $json );
		}
		return self::newFromJSON( $db, $json );
	}

	protected function __construct( string $json ) {
		$deprecatedTypes = array_keys( TemplateDataNormalizer::DEPRECATED_PARAMETER_TYPES );
		$validator = new TemplateDataValidator( $deprecatedTypes );
		$this->status = $validator->validate( json_decode( $json ) );

		// If data is invalid, replace with the minimal valid blob.
		// This is to make sure that, if something forgets to check the status first,
		// we don't end up with invalid data in the database.
		$value = $this->status->getValue() ?? (object)[ 'params' => (object)[] ];

		$lang = MediaWikiServices::getInstance()->getContentLanguage();
		$normalizer = new TemplateDataNormalizer( $lang->getCode() );
		$normalizer->normalize( $value );

		// Don't bother storing the decoded object, it will always be cloned anyway
		$this->json = json_encode( $value );
	}

	/**
	 * Get a single localized string from an InterfaceText object.
	 *
	 * Uses the preferred language passed to this function, or one of its fallbacks,
	 * or the site content language, or its fallbacks.
	 *
	 * @param stdClass $text An InterfaceText object
	 * @param string $langCode Preferred language
	 * @return null|string Text value from the InterfaceText object or null if no suitable
	 *  match was found
	 */
	private function getInterfaceTextInLanguage( stdClass $text, string $langCode ): ?string {
		if ( isset( $text->$langCode ) ) {
			return $text->$langCode;
		}

		[ $userlangs, $sitelangs ] = MediaWikiServices::getInstance()->getLanguageFallback()
			->getAllIncludingSiteLanguage( $langCode );

		foreach ( $userlangs as $lang ) {
			if ( isset( $text->$lang ) ) {
				return $text->$lang;
			}
		}

		foreach ( $sitelangs as $lang ) {
			if ( isset( $text->$lang ) ) {
				return $text->$lang;
			}
		}

		// If none of the languages are found fallback to null. Alternatively we could fallback to
		// reset( $text ) which will return whatever key there is, but we should't give the user a
		// "random" language with no context (e.g. could be RTL/Hebrew for an LTR/Japanese user).
		return null;
	}

	public function getStatus(): Status {
		return $this->status;
	}

	/**
	 * @return stdClass
	 */
	public function getData() {
		// Return deep clone so callers can't modify data. Needed for getDataInLanguage().
		return json_decode( $this->json );
	}

	/**
	 * Get data with all InterfaceText objects resolved to a single string to the
	 * appropriate language.
	 *
	 * @param string $langCode Preferred language
	 * @return stdClass
	 */
	public function getDataInLanguage( string $langCode ): stdClass {
		$data = $this->getData();

		// Root.description
		if ( $data->description !== null ) {
			$data->description = $this->getInterfaceTextInLanguage( $data->description, $langCode );
		}

		foreach ( $data->params as $param ) {
			// Param.label
			if ( $param->label !== null ) {
				$param->label = $this->getInterfaceTextInLanguage( $param->label, $langCode );
			}

			// Param.description
			if ( $param->description !== null ) {
				$param->description = $this->getInterfaceTextInLanguage( $param->description, $langCode );
			}

			// Param.default
			if ( $param->default !== null ) {
				$param->default = $this->getInterfaceTextInLanguage( $param->default, $langCode );
			}

			// Param.example
			if ( $param->example !== null ) {
				$param->example = $this->getInterfaceTextInLanguage( $param->example, $langCode );
			}
		}

		foreach ( $data->sets as $setObj ) {
			$label = $this->getInterfaceTextInLanguage( $setObj->label, $langCode );
			if ( $label === null ) {
				// Contrary to other InterfaceTexts, set label is not optional. If we're here it
				// means the template data from the wiki doesn't contain either the user language,
				// site language or any of its fallbacks. Wikis should fix data that is in this
				// condition (TODO: Disallow during saving?). For now, fallback to whatever we can
				// get that does exist in the text object.
				$arr = (array)$setObj->label;
				$label = reset( $arr );
			}

			$setObj->label = $label;
		}

		return $data;
	}

	/**
	 * @return string JSON
	 */
	public function getJSONForDatabase(): string {
		return $this->json;
	}

}
