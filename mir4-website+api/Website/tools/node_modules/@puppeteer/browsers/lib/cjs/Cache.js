"use strict";
/**
 * Copyright 2023 Google Inc. All rights reserved.
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
 */
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.Cache = void 0;
const fs_1 = __importDefault(require("fs"));
const path_1 = __importDefault(require("path"));
const browser_data_js_1 = require("./browser-data/browser-data.js");
/**
 * The cache used by Puppeteer relies on the following structure:
 *
 * - rootDir
 *   -- <browser1> | browserRoot(browser1)
 *   ---- <platform>-<buildId> | installationDir()
 *   ------ the browser-platform-buildId
 *   ------ specific structure.
 *   -- <browser2> | browserRoot(browser2)
 *   ---- <platform>-<buildId> | installationDir()
 *   ------ the browser-platform-buildId
 *   ------ specific structure.
 *   @internal
 */
class Cache {
    #rootDir;
    constructor(rootDir) {
        this.#rootDir = rootDir;
    }
    browserRoot(browser) {
        return path_1.default.join(this.#rootDir, browser);
    }
    installationDir(browser, platform, buildId) {
        return path_1.default.join(this.browserRoot(browser), `${platform}-${buildId}`);
    }
    clear() {
        fs_1.default.rmSync(this.#rootDir, {
            force: true,
            recursive: true,
            maxRetries: 10,
            retryDelay: 500,
        });
    }
    uninstall(browser, platform, buildId) {
        fs_1.default.rmSync(this.installationDir(browser, platform, buildId), {
            force: true,
            recursive: true,
            maxRetries: 10,
            retryDelay: 500,
        });
    }
    getInstalledBrowsers() {
        if (!fs_1.default.existsSync(this.#rootDir)) {
            return [];
        }
        const types = fs_1.default.readdirSync(this.#rootDir);
        const browsers = types.filter((t) => {
            return Object.values(browser_data_js_1.Browser).includes(t);
        });
        return browsers.flatMap(browser => {
            const files = fs_1.default.readdirSync(this.browserRoot(browser));
            return files
                .map(file => {
                const result = parseFolderPath(path_1.default.join(this.browserRoot(browser), file));
                if (!result) {
                    return null;
                }
                return {
                    path: path_1.default.join(this.browserRoot(browser), file),
                    browser,
                    platform: result.platform,
                    buildId: result.buildId,
                };
            })
                .filter((item) => {
                return item !== null;
            });
        });
    }
}
exports.Cache = Cache;
function parseFolderPath(folderPath) {
    const name = path_1.default.basename(folderPath);
    const splits = name.split('-');
    if (splits.length !== 2) {
        return;
    }
    const [platform, buildId] = splits;
    if (!buildId || !platform) {
        return;
    }
    return { platform, buildId };
}
//# sourceMappingURL=Cache.js.map