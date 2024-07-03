"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.getFontAxes = exports.fetchFontFile = exports.fetchCSSFromGoogleFonts = exports.getUrl = exports.validateData = void 0;
const fs_1 = __importDefault(require("fs"));
// @ts-ignore
const node_fetch_1 = __importDefault(require("next/dist/compiled/node-fetch"));
const utils_1 = require("../utils");
const font_data_json_1 = __importDefault(require("./font-data.json"));
const allowedDisplayValues = ['auto', 'block', 'swap', 'fallback', 'optional'];
const formatValues = (values) => values.map((val) => `\`${val}\``).join(', ');
function validateData(functionName, data, config) {
    var _a;
    let { weight, style, preload = true, 
    // If preload is disabled set display to 'swap' by default.
    // If display is 'optional' and we don't preload, we will never fetch the font in time to display it, not even in dev.
    display = preload ? 'optional' : 'swap', axes, fallback, adjustFontFallback = true, variable, subsets: callSubsets, } = data[0] || {};
    const subsets = (_a = callSubsets !== null && callSubsets !== void 0 ? callSubsets : config === null || config === void 0 ? void 0 : config.subsets) !== null && _a !== void 0 ? _a : [];
    if (functionName === '') {
        (0, utils_1.nextFontError)(`@next/font/google has no default export`);
    }
    const fontFamily = functionName.replace(/_/g, ' ');
    const fontFamilyData = font_data_json_1.default[fontFamily];
    if (!fontFamilyData) {
        (0, utils_1.nextFontError)(`Unknown font \`${fontFamily}\``);
    }
    const availableSubsets = fontFamilyData.subsets;
    if (availableSubsets.length === 0) {
        // If the font doesn't have any preloaded subsets, disable preload and set display to 'swap'
        preload = false;
        display = 'swap';
    }
    else {
        if (preload && !callSubsets && !(config === null || config === void 0 ? void 0 : config.subsets)) {
            (0, utils_1.nextFontError)(`Missing selected subsets for font \`${fontFamily}\`. Please specify subsets in the function call or in your \`next.config.js\`. Read more: https://nextjs.org/docs/messages/google-fonts-missing-subsets`);
        }
        subsets.forEach((subset) => {
            if (!availableSubsets.includes(subset)) {
                (0, utils_1.nextFontError)(`Unknown subset \`${subset}\` for font \`${fontFamily}\`.\nAvailable subsets: ${formatValues(availableSubsets)}`);
            }
        });
    }
    const fontWeights = fontFamilyData.weights;
    const fontStyles = fontFamilyData.styles;
    const weights = !weight
        ? []
        : [...new Set(Array.isArray(weight) ? weight : [weight])];
    const styles = !style
        ? []
        : [...new Set(Array.isArray(style) ? style : [style])];
    if (weights.length === 0) {
        // Set variable as default, throw if not available
        if (fontWeights.includes('variable')) {
            weights.push('variable');
        }
        else {
            (0, utils_1.nextFontError)(`Missing weight for font \`${fontFamily}\`.\nAvailable weights: ${formatValues(fontWeights)}`);
        }
    }
    if (weights.length > 1 && weights.includes('variable')) {
        (0, utils_1.nextFontError)(`Unexpected \`variable\` in weight array for font \`${fontFamily}\`. You only need \`variable\`, it includes all available weights.`);
    }
    weights.forEach((selectedWeight) => {
        if (!fontWeights.includes(selectedWeight)) {
            (0, utils_1.nextFontError)(`Unknown weight \`${selectedWeight}\` for font \`${fontFamily}\`.\nAvailable weights: ${formatValues(fontWeights)}`);
        }
    });
    if (styles.length === 0) {
        if (fontStyles.length === 1) {
            styles.push(fontStyles[0]);
        }
        else {
            styles.push('normal');
        }
    }
    styles.forEach((selectedStyle) => {
        if (!fontStyles.includes(selectedStyle)) {
            (0, utils_1.nextFontError)(`Unknown style \`${selectedStyle}\` for font \`${fontFamily}\`.\nAvailable styles: ${formatValues(fontStyles)}`);
        }
    });
    if (!allowedDisplayValues.includes(display)) {
        (0, utils_1.nextFontError)(`Invalid display value \`${display}\` for font \`${fontFamily}\`.\nAvailable display values: ${formatValues(allowedDisplayValues)}`);
    }
    if (weights[0] !== 'variable' && axes) {
        (0, utils_1.nextFontError)('Axes can only be defined for variable fonts');
    }
    return {
        fontFamily,
        weights,
        styles,
        display,
        preload,
        selectedVariableAxes: axes,
        fallback,
        adjustFontFallback,
        variable,
        subsets,
    };
}
exports.validateData = validateData;
function getUrl(fontFamily, axes, display) {
    var _a, _b;
    // Variants are all combinations of weight and style, each variant will result in a separate font file
    const variants = [];
    if (axes.wght) {
        for (const wgth of axes.wght) {
            if (!axes.ital) {
                variants.push([['wght', wgth], ...((_a = axes.variableAxes) !== null && _a !== void 0 ? _a : [])]);
            }
            else {
                for (const ital of axes.ital) {
                    variants.push([
                        ['ital', ital],
                        ['wght', wgth],
                        ...((_b = axes.variableAxes) !== null && _b !== void 0 ? _b : []),
                    ]);
                }
            }
        }
    }
    else if (axes.variableAxes) {
        // Variable fonts might not have a range of weights, just add optional variable axes in that case
        variants.push([...axes.variableAxes]);
    }
    // Google api requires the axes to be sorted, starting with lowercase words
    if (axes.variableAxes) {
        variants.forEach((variant) => {
            variant.sort(([a], [b]) => {
                const aIsLowercase = a.charCodeAt(0) > 96;
                const bIsLowercase = b.charCodeAt(0) > 96;
                if (aIsLowercase && !bIsLowercase)
                    return -1;
                if (bIsLowercase && !aIsLowercase)
                    return 1;
                return a > b ? 1 : -1;
            });
        });
    }
    let url = `https://fonts.googleapis.com/css2?family=${fontFamily.replace(/ /g, '+')}`;
    if (variants.length > 0) {
        url = `${url}:${variants[0].map(([key]) => key).join(',')}@${variants
            .map((variant) => variant.map(([, val]) => val).join(','))
            .sort()
            .join(';')}`;
    }
    url = `${url}&display=${display}`;
    return url;
}
exports.getUrl = getUrl;
async function fetchCSSFromGoogleFonts(url, fontFamily) {
    let mockedResponse;
    if (process.env.NEXT_FONT_GOOGLE_MOCKED_RESPONSES) {
        const mockFile = require(process.env.NEXT_FONT_GOOGLE_MOCKED_RESPONSES);
        mockedResponse = mockFile[url];
        if (!mockedResponse) {
            (0, utils_1.nextFontError)('Missing mocked response for URL: ' + url);
        }
    }
    let cssResponse;
    if (mockedResponse) {
        cssResponse = mockedResponse;
    }
    else {
        const res = await (0, node_fetch_1.default)(url, {
            headers: {
                // The file format is based off of the user agent, make sure woff2 files are fetched
                'user-agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
            },
        });
        if (!res.ok) {
            (0, utils_1.nextFontError)(`Failed to fetch font  \`${fontFamily}\`.\nURL: ${url}`);
        }
        cssResponse = await res.text();
    }
    return cssResponse;
}
exports.fetchCSSFromGoogleFonts = fetchCSSFromGoogleFonts;
async function fetchFontFile(url) {
    if (process.env.NEXT_FONT_GOOGLE_MOCKED_RESPONSES) {
        if (url.startsWith('/')) {
            return fs_1.default.readFileSync(url);
        }
        return Buffer.from(url);
    }
    const arrayBuffer = await (0, node_fetch_1.default)(url).then((r) => r.arrayBuffer());
    return Buffer.from(arrayBuffer);
}
exports.fetchFontFile = fetchFontFile;
function getFontAxes(fontFamily, weights, styles, selectedVariableAxes) {
    const allAxes = font_data_json_1.default[fontFamily].axes;
    const hasItalic = styles.includes('italic');
    const hasNormal = styles.includes('normal');
    const ital = hasItalic ? [...(hasNormal ? ['0'] : []), '1'] : undefined;
    // Weights will always contain one element if it's a variable font
    if (weights[0] === 'variable') {
        if (selectedVariableAxes) {
            const defineAbleAxes = allAxes
                .map(({ tag }) => tag)
                .filter((tag) => tag !== 'wght');
            if (defineAbleAxes.length === 0) {
                (0, utils_1.nextFontError)(`Font \`${fontFamily}\` has no definable \`axes\``);
            }
            if (!Array.isArray(selectedVariableAxes)) {
                (0, utils_1.nextFontError)(`Invalid axes value for font \`${fontFamily}\`, expected an array of axes.\nAvailable axes: ${formatValues(defineAbleAxes)}`);
            }
            selectedVariableAxes.forEach((key) => {
                if (!defineAbleAxes.some((tag) => tag === key)) {
                    (0, utils_1.nextFontError)(`Invalid axes value \`${key}\` for font \`${fontFamily}\`.\nAvailable axes: ${formatValues(defineAbleAxes)}`);
                }
            });
        }
        let weightAxis;
        let variableAxes;
        for (const { tag, min, max } of allAxes) {
            if (tag === 'wght') {
                weightAxis = `${min}..${max}`;
            }
            else if (selectedVariableAxes === null || selectedVariableAxes === void 0 ? void 0 : selectedVariableAxes.includes(tag)) {
                if (!variableAxes) {
                    variableAxes = [];
                }
                variableAxes.push([tag, `${min}..${max}`]);
            }
        }
        return {
            wght: weightAxis ? [weightAxis] : undefined,
            ital,
            variableAxes,
        };
    }
    else {
        return {
            ital,
            wght: weights,
        };
    }
}
exports.getFontAxes = getFontAxes;
