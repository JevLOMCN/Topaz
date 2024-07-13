<?php
// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2022 Kunal Mehta <legoktm@debian.org>

namespace MediaWiki\SecureLinkFixer;

return [
	'HSTSPreloadLookup' => static function (): HSTSPreloadLookup {
		return new HSTSPreloadLookup( __DIR__ . '/../domains.php' );
	}
];
