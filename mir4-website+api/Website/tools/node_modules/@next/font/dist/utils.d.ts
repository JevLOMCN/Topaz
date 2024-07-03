import type { Font } from 'fontkit';
import type { AdjustFontFallback } from 'next/font';
export declare function calculateFallbackFontValues(font: Font, category?: string): AdjustFontFallback;
export declare function nextFontError(message: string): never;
