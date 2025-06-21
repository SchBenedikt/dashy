import { ViteDevServer, Plugin } from 'vite';
import * as _compiler from 'vue/compiler-sfc';
import { SFCScriptCompileOptions, SFCTemplateCompileOptions, SFCStyleCompileOptions } from 'vue/compiler-sfc';

interface VueQuery {
    vue?: boolean;
    src?: string;
    type?: 'script' | 'template' | 'style' | 'custom';
    index?: number;
    lang?: string;
    raw?: boolean;
    scoped?: boolean;
}
declare function parseVueRequest(id: string): {
    filename: string;
    query: VueQuery;
};

interface Options {
    include?: string | RegExp | (string | RegExp)[];
    exclude?: string | RegExp | (string | RegExp)[];
    isProduction?: boolean;
    script?: Partial<Pick<SFCScriptCompileOptions, 'babelParserPlugins'>>;
    template?: Partial<Pick<SFCTemplateCompileOptions, 'compiler' | 'compilerOptions' | 'preprocessOptions' | 'transpileOptions' | 'transformAssetUrls' | 'transformAssetUrlsOptions'>>;
    style?: Partial<Pick<SFCStyleCompileOptions, 'trim'>>;
    compiler?: typeof _compiler;
}
interface ResolvedOptions extends Options {
    compiler: typeof _compiler;
    root: string;
    sourceMap: boolean;
    cssDevSourcemap: boolean;
    devServer?: ViteDevServer;
    devToolsEnabled?: boolean;
}
declare function vuePlugin(rawOptions?: Options): Plugin;

export { Options, ResolvedOptions, VueQuery, vuePlugin as default, parseVueRequest };
