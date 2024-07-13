<?php

namespace MWParsoid;

class RegistrationHookHandler {
	public static function onRegistration() {
		global $wgRestAPIAdditionalRouteFiles;
		// phpcs:ignore MediaWiki.NamingConventions.ValidGlobalName.allowedPrefix
		global $IP;
		// Use globals instead of Config.
		// Accessing it so early blows up unrelated extensions (T267146)
		global $wgParsoidEnableREST;
		if ( $wgParsoidEnableREST ) {
			$wgRestAPIAdditionalRouteFiles[] = wfRelativePath(
				__DIR__ . '/../restRoutes.json', $IP
			);
		}
	}
}
