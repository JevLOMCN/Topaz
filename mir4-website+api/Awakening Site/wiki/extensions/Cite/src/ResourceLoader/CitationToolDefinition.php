<?php

namespace Cite\ResourceLoader;

use MediaWiki\ResourceLoader as RL;

/**
 * Callback to deliver cite-tool-definition.json and related messages.
 *
 * Temporary hack since 2015 for T93800.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */
class CitationToolDefinition {

	public static function makeScript( RL\Context $context ): string {
		$citationDefinition = json_decode(
			$context->msg( 'cite-tool-definition.json' )
				->inContentLanguage()
				->plain()
		);

		$citationDefinition ??= json_decode(
			$context->msg( 'visualeditor-cite-tool-definition.json' )
				->inContentLanguage()
				->plain()
		);

		$citationTools = [];
		if ( is_array( $citationDefinition ) ) {
			foreach ( $citationDefinition as $tool ) {
				// Skip incomplete entries that don't even have a name
				if ( empty( $tool->name ) || !is_string( $tool->name ) ) {
					continue;
				}

				// Users can hard-code titles in MediaWiki:Cite-tool-definition.json if they want
				if ( empty( $tool->title ) || !is_string( $tool->title ) ) {
					// The following messages are generated here:
					// * visualeditor-cite-tool-name-book
					// * visualeditor-cite-tool-name-journal
					// * visualeditor-cite-tool-name-news
					// * visualeditor-cite-tool-name-web
					$msg = $context->msg( 'visualeditor-cite-tool-name-' . $tool->name );
					// Fall back to the raw name if there is no message
					$tool->title = $msg->isDisabled() ? $tool->name : $msg->text();
				}

				$citationTools[] = $tool;
			}
		}

		// TODO: When this custom module is converted to adopt packageFiles, this data
		// can be exported via a callback as a virtual "tools.json" file. Then the JS
		// in MWReference.init.js can do `ve.ui.mwCitationTools = require( "./tools.json" )`

		// Limit and expose
		$limit = 5;
		$citationTools = array_slice( $citationTools, 0, $limit );
		return 've.ui.mwCitationTools = ' . $context->encodeJson( $citationTools ) . ';';
	}
}
