"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
// @ts-ignore
// eslint-disable-next-line import/no-extraneous-dependencies
const fontkit_1 = __importDefault(require("@next/font/dist/fontkit"));
const util_1 = require("util");
const utils_1 = require("./utils");
const utils_2 = require("../utils");
const NORMAL_WEIGHT = 400;
const BOLD_WEIGHT = 700;
function getWeightNumber(weight) {
    // Weight can be 'normal', 'bold' or a number https://developer.mozilla.org/en-US/docs/Web/CSS/@font-face/font-weight
    return weight === 'normal'
        ? NORMAL_WEIGHT
        : weight === 'bold'
            ? BOLD_WEIGHT
            : Number(weight);
}
function getDistanceFromNormalWeight(weight) {
    if (!weight)
        return 0;
    const [firstWeight, secondWeight] = weight
        .trim()
        .split(/ +/)
        .map(getWeightNumber);
    if (Number.isNaN(firstWeight) || Number.isNaN(secondWeight)) {
        (0, utils_2.nextFontError)(`Invalid weight value in src array: \`${weight}\`.\nExpected \`normal\`, \`bold\` or a number.`);
    }
    // Not a variable font
    if (!secondWeight) {
        return firstWeight - NORMAL_WEIGHT;
    }
    // Normal weight is within variable font range
    if (firstWeight <= NORMAL_WEIGHT && secondWeight >= NORMAL_WEIGHT) {
        return 0;
    }
    // Return the distance of normal weight to the variable font range
    const firstWeightDistance = firstWeight - NORMAL_WEIGHT;
    const secondWeightDistance = secondWeight - NORMAL_WEIGHT;
    if (Math.abs(firstWeightDistance) < Math.abs(secondWeightDistance)) {
        return firstWeightDistance;
    }
    return secondWeightDistance;
}
const fetchFonts = async ({ functionName, variableName, data, emitFontFile, resolve, loaderContext, }) => {
    const { src, display, fallback, preload, variable, adjustFontFallback, declarations, weight: defaultWeight, style: defaultStyle, } = (0, utils_1.validateData)(functionName, data[0]);
    const fontFiles = await Promise.all(src.map(async ({ path, style, weight, ext, format }) => {
        const resolved = await resolve(path);
        const fileBuffer = await (0, util_1.promisify)(loaderContext.fs.readFile)(resolved);
        const fontUrl = emitFontFile(fileBuffer, ext, preload);
        let fontMetadata;
        try {
            fontMetadata = (0, fontkit_1.default)(fileBuffer);
        }
        catch (e) {
            console.error(`Failed to load font file: ${resolved}\n${e}`);
        }
        const fontFaceProperties = [
            ...(declarations
                ? declarations.map(({ prop, value }) => [prop, value])
                : []),
            ['font-family', variableName],
            ['src', `url(${fontUrl}) format('${format}')`],
            ['font-display', display],
            ...((weight !== null && weight !== void 0 ? weight : defaultWeight)
                ? [['font-weight', weight !== null && weight !== void 0 ? weight : defaultWeight]]
                : []),
            ...((style !== null && style !== void 0 ? style : defaultStyle)
                ? [['font-style', style !== null && style !== void 0 ? style : defaultStyle]]
                : []),
        ];
        return {
            css: `@font-face {
${fontFaceProperties
                .map(([property, value]) => `${property}: ${value};`)
                .join('\n')}
}\n`,
            fontMetadata,
            weight,
            style,
        };
    }));
    // Add fallback font
    let adjustFontFallbackMetrics;
    if (adjustFontFallback !== false) {
        // Pick the font file to generate a fallback font from.
        // Prefer the file closest to normal weight, this will typically make up most of the text on a page.
        const fallbackFontFile = fontFiles.reduce((usedFontFile, currentFontFile) => {
            if (!usedFontFile)
                return currentFontFile;
            const usedFontDistance = getDistanceFromNormalWeight(usedFontFile.weight);
            const currentFontDistance = getDistanceFromNormalWeight(currentFontFile.weight);
            // Prefer normal style if they have the same weight
            if (usedFontDistance === currentFontDistance &&
                (typeof currentFontFile.style === 'undefined' ||
                    currentFontFile.style === 'normal')) {
                return currentFontFile;
            }
            const absUsedDistance = Math.abs(usedFontDistance);
            const absCurrentDistance = Math.abs(currentFontDistance);
            // Use closest absolute distance to normal weight
            if (absCurrentDistance < absUsedDistance)
                return currentFontFile;
            // Prefer the thinner font if both are the same absolute distance from normal weight
            if (absUsedDistance === absCurrentDistance &&
                currentFontDistance < usedFontDistance) {
                return currentFontFile;
            }
            return usedFontFile;
        });
        if (fallbackFontFile.fontMetadata) {
            adjustFontFallbackMetrics = (0, utils_2.calculateFallbackFontValues)(fallbackFontFile.fontMetadata, adjustFontFallback === 'Times New Roman' ? 'serif' : 'sans-serif');
        }
    }
    return {
        css: fontFiles.map(({ css }) => css).join('\n'),
        fallbackFonts: fallback,
        weight: src.length === 1 ? src[0].weight : undefined,
        style: src.length === 1 ? src[0].style : undefined,
        variable,
        adjustFontFallback: adjustFontFallbackMetrics,
    };
};
exports.default = fetchFonts;
