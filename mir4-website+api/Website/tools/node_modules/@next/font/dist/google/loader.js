"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || function (mod) {
    if (mod && mod.__esModule) return mod;
    var result = {};
    if (mod != null) for (var k in mod) if (k !== "default" && Object.prototype.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
    __setModuleDefault(result, mod);
    return result;
};
Object.defineProperty(exports, "__esModule", { value: true });
// @ts-ignore
const font_utils_1 = require("next/dist/server/font-utils");
// @ts-ignore
const Log = __importStar(require("next/dist/build/output/log"));
const utils_1 = require("./utils");
const utils_2 = require("../utils");
const cssCache = new Map();
const fontCache = new Map();
// regexp is based on https://github.com/sindresorhus/escape-string-regexp
const reHasRegExp = /[|\\{}()[\]^$+*?.-]/;
const reReplaceRegExp = /[|\\{}()[\]^$+*?.-]/g;
function escapeStringRegexp(str) {
    // see also: https://github.com/lodash/lodash/blob/2da024c3b4f9947a48517639de7560457cd4ec6c/escapeRegExp.js#L23
    if (reHasRegExp.test(str)) {
        return str.replace(reReplaceRegExp, '\\$&');
    }
    return str;
}
const downloadGoogleFonts = async ({ functionName, data, config, emitFontFile, isDev, isServer, loaderContext, }) => {
    var _a, _b, _c;
    const { fontFamily, weights, styles, display, preload, selectedVariableAxes, fallback, adjustFontFallback, variable, subsets, } = (0, utils_1.validateData)(functionName, data, config);
    const fontAxes = (0, utils_1.getFontAxes)(fontFamily, weights, styles, selectedVariableAxes);
    const url = (0, utils_1.getUrl)(fontFamily, fontAxes, display);
    // Find fallback font metrics
    let adjustFontFallbackMetrics;
    if (adjustFontFallback) {
        try {
            const { ascent, descent, lineGap, fallbackFont, sizeAdjust } = (0, font_utils_1.calculateSizeAdjustValues)(require('next/dist/server/google-font-metrics.json')[fontFamily]);
            adjustFontFallbackMetrics = {
                fallbackFont,
                ascentOverride: `${ascent}%`,
                descentOverride: `${descent}%`,
                lineGapOverride: `${lineGap}%`,
                sizeAdjust: `${sizeAdjust}%`,
            };
        }
        catch {
            Log.error(`Failed to find font override values for font \`${fontFamily}\``);
        }
    }
    const result = {
        fallbackFonts: fallback,
        weight: weights.length === 1 && weights[0] !== 'variable'
            ? weights[0]
            : undefined,
        style: styles.length === 1 ? styles[0] : undefined,
        variable,
        adjustFontFallback: adjustFontFallbackMetrics,
    };
    try {
        const hasCachedCSS = cssCache.has(url);
        let fontFaceDeclarations = hasCachedCSS
            ? cssCache.get(url)
            : await (0, utils_1.fetchCSSFromGoogleFonts)(url, fontFamily).catch(() => null);
        if (!hasCachedCSS) {
            cssCache.set(url, fontFaceDeclarations);
        }
        else {
            cssCache.delete(url);
        }
        if (fontFaceDeclarations === null) {
            (0, utils_2.nextFontError)(`Failed to fetch \`${fontFamily}\` from Google Fonts.`);
        }
        // CSS Variables may be set on a body tag, ignore them to keep the CSS module pure
        fontFaceDeclarations = fontFaceDeclarations.split('body {')[0];
        // Find font files to download
        const fontFiles = [];
        let currentSubset = '';
        for (const line of fontFaceDeclarations.split('\n')) {
            // Each @font-face has the subset above it in a comment
            const newSubset = (_a = /\/\* (.+?) \*\//.exec(line)) === null || _a === void 0 ? void 0 : _a[1];
            if (newSubset) {
                currentSubset = newSubset;
            }
            else {
                const googleFontFileUrl = (_b = /src: url\((.+?)\)/.exec(line)) === null || _b === void 0 ? void 0 : _b[1];
                if (googleFontFileUrl &&
                    !fontFiles.some((foundFile) => foundFile.googleFontFileUrl === googleFontFileUrl)) {
                    fontFiles.push({
                        googleFontFileUrl,
                        preloadFontFile: !!preload && subsets.includes(currentSubset),
                    });
                }
            }
        }
        // Download font files
        const downloadedFiles = await Promise.all(fontFiles.map(async ({ googleFontFileUrl, preloadFontFile }) => {
            const hasCachedFont = fontCache.has(googleFontFileUrl);
            const fontFileBuffer = hasCachedFont
                ? fontCache.get(googleFontFileUrl)
                : await (0, utils_1.fetchFontFile)(googleFontFileUrl).catch(() => null);
            if (!hasCachedFont) {
                fontCache.set(googleFontFileUrl, fontFileBuffer);
            }
            else {
                fontCache.delete(googleFontFileUrl);
            }
            if (fontFileBuffer === null) {
                (0, utils_2.nextFontError)(`Failed to fetch \`${fontFamily}\` from Google Fonts.`);
            }
            const ext = /\.(woff|woff2|eot|ttf|otf)$/.exec(googleFontFileUrl)[1];
            // Emit font file to .next/static/media
            const selfHostedFileUrl = emitFontFile(fontFileBuffer, ext, preloadFontFile);
            return {
                googleFontFileUrl,
                selfHostedFileUrl,
            };
        }));
        // Replace @font-face sources with self-hosted files
        let updatedCssResponse = fontFaceDeclarations;
        for (const { googleFontFileUrl, selfHostedFileUrl } of downloadedFiles) {
            updatedCssResponse = updatedCssResponse.replace(new RegExp(escapeStringRegexp(googleFontFileUrl), 'g'), selfHostedFileUrl);
        }
        return {
            ...result,
            css: updatedCssResponse,
        };
    }
    catch (err) {
        loaderContext.cacheable(false);
        if (isDev) {
            if (isServer) {
                console.error(err);
                Log.error(`Failed to download \`${fontFamily}\` from Google Fonts. Using fallback font instead.`);
            }
            // In dev we should return the fallback font instead of throwing an error
            let css = `@font-face {
  font-family: '${fontFamily} Fallback';
  src: local("${(_c = adjustFontFallbackMetrics === null || adjustFontFallbackMetrics === void 0 ? void 0 : adjustFontFallbackMetrics.fallbackFont) !== null && _c !== void 0 ? _c : 'Arial'}");`;
            if (adjustFontFallbackMetrics) {
                css += `
  ascent-override:${adjustFontFallbackMetrics.ascentOverride};
  descent-override:${adjustFontFallbackMetrics.descentOverride};
  line-gap-override:${adjustFontFallbackMetrics.lineGapOverride};
  size-adjust:${adjustFontFallbackMetrics.sizeAdjust};`;
            }
            css += '\n}';
            return {
                ...result,
                css,
            };
        }
        else {
            throw err;
        }
    }
};
exports.default = downloadGoogleFonts;
