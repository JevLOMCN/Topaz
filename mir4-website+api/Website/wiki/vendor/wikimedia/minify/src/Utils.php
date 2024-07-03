<?php
/**
 * Copyright 2023 Wikimedia Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @file
 * @license Apache-2.0
 * @license MIT
 * @license GPL-2.0-or-later
 * @license LGPL-2.1-or-later
 */

namespace Wikimedia\Minify;

/**
 * Shared static utility functions
 */
class Utils {
	/**
	 * Get the length of a string in UTF-16 code units
	 *
	 * @param string $s
	 * @return int
	 */
	public static function getJsLength( $s ) {
		return strlen( mb_convert_encoding( $s, 'UTF-16', 'UTF-8' ) ) / 2;
	}
}
