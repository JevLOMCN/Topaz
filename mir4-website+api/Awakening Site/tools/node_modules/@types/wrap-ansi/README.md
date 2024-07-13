# Installation
> `npm install --save @types/wrap-ansi`

# Summary
This package contains type definitions for wrap-ansi (https://www.npmjs.com/package/wrap-ansi).

# Details
Files were exported from https://github.com/DefinitelyTyped/DefinitelyTyped/tree/master/types/wrap-ansi.
## [index.d.ts](https://github.com/DefinitelyTyped/DefinitelyTyped/tree/master/types/wrap-ansi/index.d.ts)
````ts
// Type definitions for wrap-ansi 8.0
// Project: https://www.npmjs.com/package/wrap-ansi
// Definitions by: Klaus Reimer <https://github.com/kayahr>
//                 Piotr Błażejewicz <https://github.com/peterblazejewicz>
// Definitions: https://github.com/DefinitelyTyped/DefinitelyTyped

/**
 * Wrap words to the specified column width.
 *
 * @param input   String with ANSI escape codes. Like one styled by chalk.
 * @param columns Number of columns to wrap the text to.
 * @param options By default the wrap is soft, meaning long words may extend past the column width. Setting
 *                this to true will make it hard wrap at the column width.
 */
declare function wrapAnsi(input: string, columns: number, options?: wrapAnsi.Options): string;

declare namespace wrapAnsi {
    interface Options {
        /** @default false */
        hard?: boolean | undefined;
        /** @default true */
        trim?: boolean | undefined;
        /** @default true */
        wordWrap?: boolean | undefined;
    }
}

export default wrapAnsi;

````

### Additional Details
 * Last updated: Fri, 02 Jul 2021 18:06:02 GMT
 * Dependencies: none
 * Global values: none

# Credits
These definitions were written by [Klaus Reimer](https://github.com/kayahr), and [Piotr Błażejewicz](https://github.com/peterblazejewicz).
