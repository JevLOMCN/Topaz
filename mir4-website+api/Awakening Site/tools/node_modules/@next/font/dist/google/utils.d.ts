/// <reference types="node" />
declare type FontOptions = {
    fontFamily: string;
    weights: string[];
    styles: string[];
    display: string;
    preload: boolean;
    selectedVariableAxes?: string[];
    fallback?: string[];
    adjustFontFallback: boolean;
    variable?: string;
    subsets: string[];
};
export declare function validateData(functionName: string, data: any, config: any): FontOptions;
export declare function getUrl(fontFamily: string, axes: {
    wght?: string[];
    ital?: string[];
    variableAxes?: [string, string][];
}, display: string): string;
export declare function fetchCSSFromGoogleFonts(url: string, fontFamily: string): Promise<any>;
export declare function fetchFontFile(url: string): Promise<Buffer>;
export declare function getFontAxes(fontFamily: string, weights: string[], styles: string[], selectedVariableAxes?: string[]): {
    wght?: string[];
    ital?: string[];
    variableAxes?: [string, string][];
};
export {};
