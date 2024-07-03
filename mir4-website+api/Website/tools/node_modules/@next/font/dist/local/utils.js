"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.validateData = void 0;
const utils_1 = require("../utils");
const allowedDisplayValues = ['auto', 'block', 'swap', 'fallback', 'optional'];
const formatValues = (values) => values.map((val) => `\`${val}\``).join(', ');
const extToFormat = {
    woff: 'woff',
    woff2: 'woff2',
    ttf: 'truetype',
    otf: 'opentype',
    eot: 'embedded-opentype',
};
function validateData(functionName, fontData) {
    if (functionName) {
        (0, utils_1.nextFontError)(`@next/font/local has no named exports`);
    }
    let { src, display = 'optional', weight, style, fallback, preload = true, variable, adjustFontFallback, declarations, } = fontData || {};
    if (!allowedDisplayValues.includes(display)) {
        (0, utils_1.nextFontError)(`Invalid display value \`${display}\`.\nAvailable display values: ${formatValues(allowedDisplayValues)}`);
    }
    if (!src) {
        (0, utils_1.nextFontError)('Missing required `src` property');
    }
    if (!Array.isArray(src)) {
        src = [{ path: src, weight, style }];
    }
    else {
        if (src.length === 0) {
            (0, utils_1.nextFontError)('Unexpected empty `src` array.');
        }
    }
    src = src.map((fontFile) => {
        var _a;
        const ext = (_a = /\.(woff|woff2|eot|ttf|otf)$/.exec(fontFile.path)) === null || _a === void 0 ? void 0 : _a[1];
        if (!ext) {
            (0, utils_1.nextFontError)(`Unexpected file \`${fontFile.path}\``);
        }
        return {
            ...fontFile,
            ext,
            format: extToFormat[ext],
        };
    });
    if (Array.isArray(declarations)) {
        declarations.forEach((declaration) => {
            if ([
                'font-family',
                'src',
                'font-display',
                'font-weight',
                'font-style',
            ].includes(declaration === null || declaration === void 0 ? void 0 : declaration.prop)) {
                (0, utils_1.nextFontError)(`Invalid declaration prop: \`${declaration.prop}\``);
            }
        });
    }
    return {
        src,
        display,
        weight,
        style,
        fallback,
        preload,
        variable,
        adjustFontFallback,
        declarations,
    };
}
exports.validateData = validateData;
