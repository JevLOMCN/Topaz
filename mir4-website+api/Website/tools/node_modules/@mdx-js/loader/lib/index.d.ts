/**
 * A Webpack (5+) loader for MDX.
 * See `webpack.cjs`, which wraps this, because Webpack loaders must currently
 * be CommonJS.
 *
 * @this {LoaderContext}
 * @param {string} value
 * @param {LoaderContext['callback']} callback
 */
export function loader(this: LoaderContext, value: string, callback: LoaderContext['callback']): void;
export type CompileOptions = import('@mdx-js/mdx').CompileOptions;
export type VFileCompatible = import('vfile').VFileCompatible;
export type VFile = import('vfile').VFile;
export type VFileMessage = import('vfile-message').VFileMessage;
export type LoaderContext = import('webpack').LoaderContext<unknown>;
export type WebpackCompiler = import('webpack').Compiler;
export type Defaults = Pick<CompileOptions, 'SourceMapGenerator'>;
/**
 * Configuration.
 */
export type Options = Omit<CompileOptions, 'SourceMapGenerator'>;
/**
 * Process.
 */
export type Process = (vfileCompatible: VFileCompatible) => Promise<VFile>;
