<?php
/**
 * Copyright 2022 Wikimedia Foundation
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
 * A stateful minifier for JavaScript without source map support.
 *
 * Use the factory JavaScriptMinifier::createMinifier()
 */
class JavaScriptMinifierState extends MinifierState {
	/**
	 * @param string $source
	 * @return string
	 */
	protected function minify( string $source ): string {
		return JavaScriptMinifier::minifyInternal( $source );
	}
}
