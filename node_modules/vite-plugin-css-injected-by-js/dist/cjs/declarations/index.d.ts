import type { Plugin } from 'vite';
import type { PluginConfiguration } from './interface';
/**
 * Inject the CSS compiled with JS.
 *
 * @return {Plugin}
 */
export default function cssInjectedByJsPlugin({ cssAssetsFilterFunction, dev: { enableDev, removeStyleCode, removeStyleCodeFunction }, injectCode, injectCodeFunction, injectionCodeFormat, jsAssetsFilterFunction, preRenderCSSCode, relativeCSSInjection, styleId, suppressUnusedCssWarning, topExecutionPriority, useStrictCSP, }?: PluginConfiguration | undefined): Plugin[];
