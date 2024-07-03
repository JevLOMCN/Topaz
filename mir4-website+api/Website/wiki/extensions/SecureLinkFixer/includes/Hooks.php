<?php
/**
 * Copyright (C) 2018 Kunal Mehta <legoktm@debian.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace MediaWiki\SecureLinkFixer;

use MediaWiki\Hook\LinkerMakeExternalLinkHook;

class Hooks implements LinkerMakeExternalLinkHook {

	private HSTSPreloadLookup $lookup;

	public function __construct( HSTSPreloadLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * Hook: LinkerMakeExternalLink
	 *
	 * Changes the scheme of the URL to HTTPS if necessary
	 *
	 * @param string &$url Link URL
	 * @param string &$text Link text
	 * @param string &$link New link HTML (if returning false)
	 * @param string[] &$attribs Attributes to be applied
	 * @param string $linkType External link type
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onLinkerMakeExternalLink(
		&$url, &$text, &$link, &$attribs, $linkType
	) {
		if ( str_starts_with( $url, 'https://' ) ) {
			// Already HTTPS
			return;
		}

		$parsed = wfParseUrl( $url );
		if ( !$parsed ) {
			return;
		}

		if ( $parsed['scheme'] !== 'http' && $parsed['scheme'] !== '' ) {
			// We only want http:// and proto-rel
			return;
		}

		if ( $this->lookup->isPreloaded( $parsed['host'] ) ) {
			$parsed['scheme'] = 'https';
			$parsed['delimiter'] = '://';
			$url = wfAssembleUrl( $parsed );
		}
	}
}
