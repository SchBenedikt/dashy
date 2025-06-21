import fs from 'node:fs';
import { transformWithEsbuild, formatPostcssSourceMap, createFilter } from 'vite';
import { createRequire } from 'node:module';
import path from 'node:path';
import { createHash } from 'node:crypto';

function resolveCompiler(root) {
  const compiler = tryRequire("vue/compiler-sfc", root) || tryRequire("vue/compiler-sfc");
  if (!compiler) {
    throw new Error(`Failed to resolve vue/compiler-sfc.
@vitejs/plugin-vue2 requires vue (>=2.7.0) to be present in the dependency tree.`);
  }
  return compiler;
}
const _require = createRequire(import.meta.url);
function tryRequire(id, from) {
  try {
    return from ? _require(_require.resolve(id, { paths: [from] })) : _require(id);
  } catch (e) {
  }
}

function parseVueRequest(id) {
  const [filename, rawQuery] = id.split(`?`, 2);
  const query = Object.fromEntries(new URLSearchParams(rawQuery));
  if (query.vue != null) {
    query.vue = true;
  }
  if (query.index != null) {
    query.index = Number(query.index);
  }
  if (query.raw != null) {
    query.raw = true;
  }
  if (query.scoped != null) {
    query.scoped = true;
  }
  return {
    filename,
    query
  };
}

var slash = path => {
	const isExtendedLengthPath = /^\\\\\?\\/.test(path);
	const hasNonAscii = /[^\u0000-\u0080]+/.test(path); // eslint-disable-line no-control-regex

	if (isExtendedLengthPath || hasNonAscii) {
		return path;
	}

	return path.replace(/\\/g, '/');
};

const cache = /* @__PURE__ */ new Map();
const prevCache = /* @__PURE__ */ new Map();
function createDescriptor(filename, source, { root, isProduction, sourceMap, compiler }) {
  let descriptor;
  let errors = [];
  try {
    descriptor = compiler.parse({
      source,
      filename,
      sourceMap
    });
  } catch (e) {
    errors = [e];
    descriptor = compiler.parse({ source: ``, filename });
  }
  const normalizedPath = slash(path.normalize(path.relative(root, filename)));
  descriptor.id = getHash(normalizedPath + (isProduction ? source : ""));
  cache.set(filename, descriptor);
  return { descriptor, errors };
}
function getPrevDescriptor(filename) {
  return prevCache.get(filename);
}
function setPrevDescriptor(filename, entry) {
  prevCache.set(filename, entry);
}
function getDescriptor(filename, options, createIfNotFound = true) {
  if (cache.has(filename)) {
    return cache.get(filename);
  }
  if (createIfNotFound) {
    const { descriptor, errors } = createDescriptor(filename, fs.readFileSync(filename, "utf-8"), options);
    if (errors.length) {
      throw errors[0];
    }
    return descriptor;
  }
}
function getSrcDescriptor(filename, query) {
  if (query.scoped) {
    return cache.get(`${filename}?src=${query.src}`);
  }
  return cache.get(filename);
}
function setSrcDescriptor(filename, entry, scoped) {
  if (scoped) {
    cache.set(`${filename}?src=${entry.id}`, entry);
    return;
  }
  cache.set(filename, entry);
}
function getHash(text) {
  return createHash("sha256").update(text).digest("hex").substring(0, 8);
}

const clientCache = /* @__PURE__ */ new WeakMap();
const ssrCache = /* @__PURE__ */ new WeakMap();
function getResolvedScript(descriptor, ssr) {
  return (ssr ? ssrCache : clientCache).get(descriptor);
}
function setResolvedScript(descriptor, script, ssr) {
  (ssr ? ssrCache : clientCache).set(descriptor, script);
}
function resolveScript(descriptor, options, ssr) {
  if (!descriptor.script && !descriptor.scriptSetup) {
    return null;
  }
  const cacheToUse = ssr ? ssrCache : clientCache;
  const cached = cacheToUse.get(descriptor);
  if (cached) {
    return cached;
  }
  const resolved = options.compiler.compileScript(descriptor, {
    ...options.script,
    id: descriptor.id,
    isProd: options.isProduction,
    sourceMap: options.sourceMap
  });
  cacheToUse.set(descriptor, resolved);
  return resolved;
}

function pad (hash, len) {
  while (hash.length < len) {
    hash = '0' + hash;
  }
  return hash;
}

function fold (hash, text) {
  var i;
  var chr;
  var len;
  if (text.length === 0) {
    return hash;
  }
  for (i = 0, len = text.length; i < len; i++) {
    chr = text.charCodeAt(i);
    hash = ((hash << 5) - hash) + chr;
    hash |= 0;
  }
  return hash < 0 ? hash * -2 : hash;
}

function foldObject (hash, o, seen) {
  return Object.keys(o).sort().reduce(foldKey, hash);
  function foldKey (hash, key) {
    return foldValue(hash, o[key], key, seen);
  }
}

function foldValue (input, value, key, seen) {
  var hash = fold(fold(fold(input, key), toString(value)), typeof value);
  if (value === null) {
    return fold(hash, 'null');
  }
  if (value === undefined) {
    return fold(hash, 'undefined');
  }
  if (typeof value === 'object' || typeof value === 'function') {
    if (seen.indexOf(value) !== -1) {
      return fold(hash, '[Circular]' + key);
    }
    seen.push(value);

    var objHash = foldObject(hash, value, seen);

    if (!('valueOf' in value) || typeof value.valueOf !== 'function') {
      return objHash;
    }

    try {
      return fold(objHash, String(value.valueOf()))
    } catch (err) {
      return fold(objHash, '[valueOf exception]' + (err.stack || err.message))
    }
  }
  return fold(hash, value.toString());
}

function toString (o) {
  return Object.prototype.toString.call(o);
}

function sum (o) {
  return pad(foldValue(0, o, '', []).toString(16), 8);
}

var hashSum = sum;

function createRollupError(id, error) {
  if ("msg" in error) {
    return {
      id,
      plugin: "vue",
      message: error.msg,
      name: "vue-compiler-error"
    };
  } else {
    return {
      id,
      plugin: "vue",
      message: error.message,
      name: error.name,
      stack: error.stack
    };
  }
}

const HMR_RUNTIME_ID = "\0plugin-vue2:hmr-runtime";
const hmrRuntimeCode = `
var __VUE_HMR_RUNTIME__ = Object.create(null)
var map = Object.create(null)

__VUE_HMR_RUNTIME__.createRecord = function (id, options) {
  if(map[id]) { return }

  var Ctor = null
  if (typeof options === 'function') {
    Ctor = options
    options = Ctor.options
  }
  makeOptionsHot(id, options)
  map[id] = {
    Ctor: Ctor,
    options: options,
    instances: []
  }
}

__VUE_HMR_RUNTIME__.isRecorded = function (id) {
  return typeof map[id] !== 'undefined'
}

function makeOptionsHot(id, options) {
  if (options.functional) {
    var render = options.render
    options.render = function (h, ctx) {
      var instances = map[id].instances
      if (ctx && instances.indexOf(ctx.parent) < 0) {
        instances.push(ctx.parent)
      }
      return render(h, ctx)
    }
  } else {
    injectHook(options, 'beforeCreate', function() {
      var record = map[id]
      if (!record.Ctor) {
        record.Ctor = this.constructor
      }
      record.instances.push(this)
    })
    injectHook(options, 'beforeDestroy', function() {
      var instances = map[id].instances
      instances.splice(instances.indexOf(this), 1)
    })
  }
}

function injectHook(options, name, hook) {
  var existing = options[name]
  options[name] = existing
    ? Array.isArray(existing) ? existing.concat(hook) : [existing, hook]
    : [hook]
}

function tryWrap(fn) {
  return function (id, arg) {
    try {
      fn(id, arg)
    } catch (e) {
      console.error(e)
      console.warn(
        'Something went wrong during Vue component hot-reload. Full reload required.'
      )
    }
  }
}

function updateOptions (oldOptions, newOptions) {
  for (var key in oldOptions) {
    if (!(key in newOptions)) {
      delete oldOptions[key]
    }
  }
  for (var key$1 in newOptions) {
    oldOptions[key$1] = newOptions[key$1]
  }
}

__VUE_HMR_RUNTIME__.rerender = tryWrap(function (id, options) {
  var record = map[id]
  if (!options) {
    record.instances.slice().forEach(function (instance) {
      instance.$forceUpdate()
    })
    return
  }
  if (typeof options === 'function') {
    options = options.options
  }
  if(record.functional){
    record.render = options.render
    record.staticRenderFns = options.staticRenderFns
    __VUE_HMR_RUNTIME__.reload(id, record)
    return
  }
  if (record.Ctor) {
    record.Ctor.options.render = options.render
    record.Ctor.options.staticRenderFns = options.staticRenderFns
    record.instances.slice().forEach(function (instance) {
      instance.$options.render = options.render
      instance.$options.staticRenderFns = options.staticRenderFns
      // reset static trees
      // pre 2.5, all static trees are cached together on the instance
      if (instance._staticTrees) {
        instance._staticTrees = []
      }
      // 2.5.0
      if (Array.isArray(record.Ctor.options.cached)) {
        record.Ctor.options.cached = []
      }
      // 2.5.3
      if (Array.isArray(instance.$options.cached)) {
        instance.$options.cached = []
      }

      // post 2.5.4: v-once trees are cached on instance._staticTrees.
      // Pure static trees are cached on the staticRenderFns array
      // (both already reset above)

      // 2.6: temporarily mark rendered scoped slots as unstable so that
      // child components can be forced to update
      var restore = patchScopedSlots(instance)
      instance.$forceUpdate()
      instance.$nextTick(restore)
    })
  } else {
    // functional or no instance created yet
    record.options.render = options.render
    record.options.staticRenderFns = options.staticRenderFns

    // handle functional component re-render
    if (record.options.functional) {
      // rerender with full options
      if (Object.keys(options).length > 2) {
        updateOptions(record.options, options)
      } else {
        // template-only rerender.
        // need to inject the style injection code for CSS modules
        // to work properly.
        var injectStyles = record.options._injectStyles
        if (injectStyles) {
          var render = options.render
          record.options.render = function (h, ctx) {
            injectStyles.call(ctx)
            return render(h, ctx)
          }
        }
      }
      record.options._Ctor = null
      // 2.5.3
      if (Array.isArray(record.options.cached)) {
        record.options.cached = []
      }
      record.instances.slice().forEach(function (instance) {
        instance.$forceUpdate()
      })
    }
  }
})

__VUE_HMR_RUNTIME__.reload = tryWrap(function (id, options) {
  var record = map[id]
  if (options) {
    if (typeof options === 'function') {
      options = options.options
    }
    makeOptionsHot(id, options)
    if (record.Ctor) {
      var newCtor = record.Ctor.super.extend(options)
      // prevent record.options._Ctor from being overwritten accidentally
      newCtor.options._Ctor = record.options._Ctor
      record.Ctor.options = newCtor.options
      record.Ctor.cid = newCtor.cid
      record.Ctor.prototype = newCtor.prototype
      if (newCtor.release) {
        // temporary global mixin strategy used in < 2.0.0-alpha.6
        newCtor.release()
      }
    } else {
      updateOptions(record.options, options)
    }
  }
  record.instances.slice().forEach(function (instance) {
    if (instance.$vnode && instance.$vnode.context) {
      instance.$vnode.context.$forceUpdate()
    } else {
      console.warn(
        'Root or manually mounted instance modified. Full reload required.'
      )
    }
  })
})

// 2.6 optimizes template-compiled scoped slots and skips updates if child
// only uses scoped slots. We need to patch the scoped slots resolving helper
// to temporarily mark all scoped slots as unstable in order to force child
// updates.
function patchScopedSlots (instance) {
  if (!instance._u) { return }
  // https://github.com/vuejs/vue/blob/dev/src/core/instance/render-helpers/resolve-scoped-slots.js
  var original = instance._u
  instance._u = function (slots) {
    try {
      // 2.6.4 ~ 2.6.6
      return original(slots, true)
    } catch (e) {
      // 2.5 / >= 2.6.7
      return original(slots, null, true)
    }
  }
  return function () {
    instance._u = original
  }
}
export default __VUE_HMR_RUNTIME__
`;

async function transformTemplateAsModule(code, descriptor, options, pluginContext, ssr) {
  let returnCode = compile(code, descriptor, options, pluginContext, ssr);
  if (options.devServer && options.devServer.config.server.hmr !== false && !ssr && !options.isProduction) {
    returnCode += `
import __VUE_HMR_RUNTIME__ from "${HMR_RUNTIME_ID}"`;
    returnCode += `
import.meta.hot.accept((updated) => {
      __VUE_HMR_RUNTIME__.rerender(${JSON.stringify(descriptor.id)}, updated)
    })`;
  }
  return returnCode + `
export { render, staticRenderFns }`;
}
function transformTemplateInMain(code, descriptor, options, pluginContext, ssr) {
  return compile(code, descriptor, options, pluginContext, ssr).replace(/var (render|staticRenderFns) =/g, "var _sfc_$1 =").replace(/(render._withStripped)/, "_sfc_$1");
}
function compile(code, descriptor, options, pluginContext, ssr) {
  const filename = descriptor.filename;
  const result = options.compiler.compileTemplate({
    ...resolveTemplateCompilerOptions(descriptor, options, ssr),
    source: code
  });
  if (result.errors.length) {
    result.errors.forEach((error) => pluginContext.error(typeof error === "string" ? { id: filename, message: error } : createRollupError(filename, error)));
  }
  if (result.tips.length) {
    result.tips.forEach((tip) => pluginContext.warn({
      id: filename,
      message: typeof tip === "string" ? tip : tip.msg
    }));
  }
  return transformRequireToImport(result.code);
}
function resolveTemplateCompilerOptions(descriptor, options, ssr) {
  const block = descriptor.template;
  if (!block) {
    return;
  }
  const resolvedScript = getResolvedScript(descriptor, ssr);
  const hasScoped = descriptor.styles.some((s) => s.scoped);
  const { id, filename } = descriptor;
  let preprocessOptions = block.lang && options.template?.preprocessOptions;
  if (block.lang === "pug") {
    preprocessOptions = {
      doctype: "html",
      ...preprocessOptions
    };
  }
  const transformAssetUrls = options.template?.transformAssetUrls ?? true;
  let assetUrlOptions;
  if (options.devServer) {
    if (filename.startsWith(options.root)) {
      assetUrlOptions = {
        base: (options.devServer.config.server?.origin ?? "") + options.devServer.config.base + slash(path.relative(options.root, path.dirname(filename)))
      };
    }
  } else if (transformAssetUrls !== false) {
    assetUrlOptions = {
      includeAbsolute: true
    };
  }
  return {
    transformAssetUrls,
    ...options.template,
    filename,
    isProduction: options.isProduction,
    isFunctional: !!block.attrs.functional,
    optimizeSSR: ssr,
    transformAssetUrlsOptions: {
      ...assetUrlOptions,
      ...options.template?.transformAssetUrlsOptions
    },
    preprocessLang: block.lang,
    preprocessOptions,
    bindings: resolvedScript ? resolvedScript.bindings : void 0,
    prettify: false,
    compilerOptions: {
      whitespace: "condense",
      outputSourceRange: true,
      ...options.template?.compilerOptions,
      scopeId: hasScoped ? `data-v-${id}` : void 0
    }
  };
}
function transformRequireToImport(code) {
  const imports = {};
  let strImports = "";
  code = code.replace(/require\(("(?:[^"\\]|\\.)+"|'(?:[^'\\]|\\.)+')\)/g, (_, name) => {
    if (!(name in imports)) {
      imports[name] = `__$_require_${hashSum(name)}__`;
      strImports += `import ${imports[name]} from ${name}
`;
    }
    return imports[name];
  });
  return strImports + code;
}

const directRequestRE = /(\?|&)direct\b/;
async function handleHotUpdate({ file, modules, read, server }, options) {
  const prevDescriptor = getDescriptor(file, options, false);
  if (!prevDescriptor) {
    return;
  }
  setPrevDescriptor(file, prevDescriptor);
  const content = await read();
  const { descriptor } = createDescriptor(file, content, options);
  let needRerender = false;
  const affectedModules = /* @__PURE__ */ new Set();
  const mainModule = modules.find((m) => !/type=/.test(m.url) || /type=script/.test(m.url));
  const templateModule = modules.find((m) => /type=template/.test(m.url));
  const scriptChanged = hasScriptChanged(prevDescriptor, descriptor);
  if (scriptChanged) {
    let scriptModule;
    if (descriptor.scriptSetup?.lang && !descriptor.scriptSetup.src || descriptor.script?.lang && !descriptor.script.src) {
      const scriptModuleRE = new RegExp(`type=script.*&lang.${descriptor.scriptSetup?.lang || descriptor.script?.lang}$`);
      scriptModule = modules.find((m) => scriptModuleRE.test(m.url));
    }
    affectedModules.add(scriptModule || mainModule);
  }
  if (!isEqualBlock(descriptor.template, prevDescriptor.template)) {
    if (!scriptChanged) {
      setResolvedScript(descriptor, getResolvedScript(prevDescriptor, false), false);
    }
    affectedModules.add(templateModule);
    needRerender = true;
  }
  const prevStyles = prevDescriptor.styles || [];
  const nextStyles = descriptor.styles || [];
  if (prevStyles.some((s) => s.scoped) !== nextStyles.some((s) => s.scoped)) {
    affectedModules.add(templateModule);
    affectedModules.add(mainModule);
  }
  for (let i = 0; i < nextStyles.length; i++) {
    const prev = prevStyles[i];
    const next = nextStyles[i];
    if (!prev || !isEqualBlock(prev, next)) {
      const mod = modules.find((m) => m.url.includes(`type=style&index=${i}`) && m.url.endsWith(`.${next.lang || "css"}`) && !directRequestRE.test(m.url));
      if (mod) {
        affectedModules.add(mod);
        if (mod.url.includes("&inline")) {
          affectedModules.add(mainModule);
        }
      } else {
        affectedModules.add(mainModule);
      }
    }
  }
  if (prevStyles.length > nextStyles.length) {
    affectedModules.add(mainModule);
  }
  const prevCustoms = prevDescriptor.customBlocks || [];
  const nextCustoms = descriptor.customBlocks || [];
  if (prevCustoms.length !== nextCustoms.length) {
    affectedModules.add(mainModule);
  } else {
    for (let i = 0; i < nextCustoms.length; i++) {
      const prev = prevCustoms[i];
      const next = nextCustoms[i];
      if (!prev || !isEqualBlock(prev, next)) {
        const mod = modules.find((m) => m.url.includes(`type=${prev.type}&index=${i}`));
        if (mod) {
          affectedModules.add(mod);
        } else {
          affectedModules.add(mainModule);
        }
      }
    }
  }
  if (needRerender) {
    if (!templateModule) {
      affectedModules.add(mainModule);
    } else if (mainModule && !affectedModules.has(mainModule)) {
      const styleImporters = [...mainModule.importers].filter((m) => /\.css($|\?)/.test(m.url));
      styleImporters.forEach((m) => affectedModules.add(m));
    }
  }
  return [...affectedModules].filter(Boolean);
}
function isEqualBlock(a, b) {
  if (!a && !b)
    return true;
  if (!a || !b)
    return false;
  if (a.src && b.src && a.src === b.src)
    return true;
  if (a.content !== b.content)
    return false;
  const keysA = Object.keys(a.attrs);
  const keysB = Object.keys(b.attrs);
  if (keysA.length !== keysB.length) {
    return false;
  }
  return keysA.every((key) => a.attrs[key] === b.attrs[key]);
}
function isOnlyTemplateChanged(prev, next) {
  return !hasScriptChanged(prev, next) && prev.styles.length === next.styles.length && prev.styles.every((s, i) => isEqualBlock(s, next.styles[i])) && prev.customBlocks.length === next.customBlocks.length && prev.customBlocks.every((s, i) => isEqualBlock(s, next.customBlocks[i]));
}
function hasScriptChanged(prev, next) {
  if (!isEqualBlock(prev.script, next.script)) {
    return true;
  }
  if (!isEqualBlock(prev.scriptSetup, next.scriptSetup)) {
    return true;
  }
  const prevResolvedScript = getResolvedScript(prev, false);
  const prevImports = prevResolvedScript?.imports;
  if (prevImports) {
    return next.shouldForceReload(prevImports);
  }
  return false;
}

const NORMALIZER_ID = "\0plugin-vue2:normalizer";
const normalizerCode = `
export default function normalizeComponent (
    scriptExports,
    render,
    staticRenderFns,
    functionalTemplate,
    injectStyles,
    scopeId,
    moduleIdentifier, /* server only */
    shadowMode /* vue-cli only */
) {
  // Vue.extend constructor export interop
  var options = typeof scriptExports === 'function'
      ? scriptExports.options
      : scriptExports

  // render functions
  if (render) {
    options.render = render
    options.staticRenderFns = staticRenderFns
    options._compiled = true
  }

  // functional template
  if (functionalTemplate) {
    options.functional = true
  }

  // scopedId
  if (scopeId) {
    options._scopeId = 'data-v-' + scopeId
  }

  var hook
  if (moduleIdentifier) { // server build
    hook = function (context) {
      // 2.3 injection
      context =
          context || // cached call
          (this.$vnode && this.$vnode.ssrContext) || // stateful
          (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional
      // 2.2 with runInNewContext: true
      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {
        context = __VUE_SSR_CONTEXT__
      }
      // inject component styles
      if (injectStyles) {
        injectStyles.call(this, context)
      }
      // register component module identifier for async chunk inference
      if (context && context._registeredComponents) {
        context._registeredComponents.add(moduleIdentifier)
      }
    }
    // used by ssr in case component is cached and beforeCreate
    // never gets called
    options._ssrRegister = hook
  } else if (injectStyles) {
    hook = shadowMode
        ? function () {
          injectStyles.call(
              this,
              (options.functional ? this.parent : this).$root.$options.shadowRoot
          )
        }
        : injectStyles
  }

  if (hook) {
    if (options.functional) {
      // for template-only hot-reload because in that case the render fn doesn't
      // go through the normalizer
      options._injectStyles = hook
      // register for functional component in vue file
      var originalRender = options.render
      options.render = function renderWithStyleInjection (h, context) {
        hook.call(context)
        return originalRender(h, context)
      }
    } else {
      // inject component registration as beforeCreate hook
      var existing = options.beforeCreate
      options.beforeCreate = existing
          ? [].concat(existing, hook)
          : [hook]
    }
  }

  return {
    exports: scriptExports,
    options: options
  }
}`;

async function transformMain(code, filename, options, pluginContext, ssr) {
  const { devServer, isProduction, devToolsEnabled } = options;
  const prevDescriptor = getPrevDescriptor(filename);
  const { descriptor, errors } = createDescriptor(filename, code, options);
  if (errors.length) {
    errors.forEach((error) => pluginContext.error(createRollupError(filename, error)));
    return null;
  }
  const hasScoped = descriptor.styles.some((s) => s.scoped);
  const hasCssModules = descriptor.styles.some((s) => s.module);
  const hasFunctional = descriptor.template && descriptor.template.attrs.functional;
  const { code: scriptCode, map: scriptMap } = await genScriptCode(descriptor, options, pluginContext, ssr);
  const templateCode = await genTemplateCode(descriptor, options, pluginContext, ssr);
  const stylesCode = await genStyleCode(descriptor, pluginContext);
  const customBlocksCode = await genCustomBlockCode(descriptor, pluginContext);
  const output = [
    scriptCode,
    templateCode,
    stylesCode,
    customBlocksCode
  ];
  output.push(`/* normalize component */
import __normalizer from "${NORMALIZER_ID}"
var __component__ = /*#__PURE__*/__normalizer(
  _sfc_main,
  _sfc_render,
  _sfc_staticRenderFns,
  ${hasFunctional ? "true" : "false"},
  ${hasCssModules ? `_sfc_injectStyles` : `null`},
  ${hasScoped ? JSON.stringify(descriptor.id) : "null"},
  null,
  null
)`);
  if (devToolsEnabled || devServer && !isProduction) {
    output.push(`__component__.options.__file = ${JSON.stringify(isProduction ? path.basename(filename) : filename)}`);
  }
  if (devServer && devServer.config.server.hmr !== false && !ssr && !isProduction) {
    const id = JSON.stringify(descriptor.id);
    output.push(`import __VUE_HMR_RUNTIME__ from "${HMR_RUNTIME_ID}"`, `if (!__VUE_HMR_RUNTIME__.isRecorded(${id})) {`, `  __VUE_HMR_RUNTIME__.createRecord(${id}, __component__.options)`, `}`);
    if (hasFunctional || prevDescriptor && isOnlyTemplateChanged(prevDescriptor, descriptor)) {
      output.push(`export const _rerender_only = true`);
    }
    output.push(`import.meta.hot.accept(mod => {`, `  if (!mod) return`, `  const { default: updated, _rerender_only } = mod`, `  if (_rerender_only) {`, `    __VUE_HMR_RUNTIME__.rerender(${id}, updated)`, `  } else {`, `    __VUE_HMR_RUNTIME__.reload(${id}, updated)`, `  }`, `})`);
  }
  let resolvedMap = scriptMap;
  output.push(`export default __component__.exports`);
  let resolvedCode = output.join("\n");
  if ((descriptor.script?.lang === "ts" || descriptor.scriptSetup?.lang === "ts") && !descriptor.script?.src) {
    const { code: code2, map } = await transformWithEsbuild(resolvedCode, filename, {
      loader: "ts",
      target: "esnext",
      sourcemap: options.sourceMap
    }, resolvedMap);
    resolvedCode = code2;
    resolvedMap = resolvedMap ? map : resolvedMap;
  }
  return {
    code: resolvedCode,
    map: resolvedMap || {
      mappings: ""
    },
    meta: {
      vite: {
        lang: descriptor.script?.lang || descriptor.scriptSetup?.lang || "js"
      }
    }
  };
}
async function genTemplateCode(descriptor, options, pluginContext, ssr) {
  const template = descriptor.template;
  if (!template) {
    return "const _sfc_render = null; const _sfc_staticRenderFns = null";
  }
  const hasScoped = descriptor.styles.some((style) => style.scoped);
  if (!template.lang && !template.src) {
    return transformTemplateInMain(template.content, descriptor, options, pluginContext, ssr);
  } else {
    if (template.src) {
      await linkSrcToDescriptor(template.src, descriptor, pluginContext, hasScoped);
    }
    const src = template.src || descriptor.filename;
    const srcQuery = template.src ? hasScoped ? `&src=${descriptor.id}` : "&src=true" : "";
    const scopedQuery = hasScoped ? `&scoped=${descriptor.id}` : ``;
    const attrsQuery = attrsToQuery(template.attrs, "js", true);
    const query = `?vue&type=template${srcQuery}${scopedQuery}${attrsQuery}`;
    const request = JSON.stringify(src + query);
    return `import { render as _sfc_render, staticRenderFns as _sfc_staticRenderFns } from ${request}`;
  }
}
async function genScriptCode(descriptor, options, pluginContext, ssr) {
  let scriptCode = `const _sfc_main = {}`;
  let map;
  const script = resolveScript(descriptor, options, ssr);
  if (script) {
    if ((!script.lang || script.lang === "ts" && options.devServer) && !script.src) {
      const userPlugins = options.script?.babelParserPlugins || [];
      const defaultPlugins = script.lang === "ts" ? userPlugins.includes("decorators") ? ["typescript"] : ["typescript", "decorators-legacy"] : [];
      scriptCode = options.compiler.rewriteDefault(script.content, "_sfc_main", [...defaultPlugins, ...userPlugins]);
      map = script.map;
    } else {
      if (script.src) {
        await linkSrcToDescriptor(script.src, descriptor, pluginContext, false);
      }
      const src = script.src || descriptor.filename;
      const langFallback = script.src && path.extname(src).slice(1) || "js";
      const attrsQuery = attrsToQuery(script.attrs, langFallback);
      const srcQuery = script.src ? `&src=true` : ``;
      const query = `?vue&type=script${srcQuery}${attrsQuery}`;
      const request = JSON.stringify(src + query);
      scriptCode = `import _sfc_main from ${request}
export * from ${request}`;
    }
  }
  return {
    code: scriptCode,
    map
  };
}
async function genStyleCode(descriptor, pluginContext) {
  let stylesCode = ``;
  let cssModulesMap;
  if (descriptor.styles.length) {
    for (let i = 0; i < descriptor.styles.length; i++) {
      const style = descriptor.styles[i];
      if (style.src) {
        await linkSrcToDescriptor(style.src, descriptor, pluginContext, style.scoped);
      }
      const src = style.src || descriptor.filename;
      const attrsQuery = attrsToQuery(style.attrs, "css");
      const srcQuery = style.src ? style.scoped ? `&src=${descriptor.id}` : "&src=true" : "";
      const directQuery = ``;
      const scopedQuery = style.scoped ? `&scoped=${descriptor.id}` : ``;
      const query = `?vue&type=style&index=${i}${srcQuery}${directQuery}${scopedQuery}`;
      const styleRequest = src + query + attrsQuery;
      if (style.module) {
        const [importCode, nameMap] = genCSSModulesCode(i, styleRequest, style.module);
        stylesCode += importCode;
        Object.assign(cssModulesMap || (cssModulesMap = {}), nameMap);
      } else {
        stylesCode += `
import ${JSON.stringify(styleRequest)}`;
      }
    }
  }
  if (cssModulesMap) {
    const mappingCode = Object.entries(cssModulesMap).reduce((code, [key, value]) => code + `"${key}":${value},
`, "{\n") + "}";
    stylesCode += `
const __cssModules = ${mappingCode}`;
    stylesCode += `
function _sfc_injectStyles(ctx) {
      for (var key in __cssModules) {
        this[key] = __cssModules[key]
      }
    }`;
  }
  return stylesCode;
}
function genCSSModulesCode(index, request, moduleName) {
  const styleVar = `style${index}`;
  const exposedName = typeof moduleName === "string" ? moduleName : "$style";
  const moduleRequest = request.replace(/\.(\w+)$/, ".module.$1");
  return [
    `
import ${styleVar} from ${JSON.stringify(moduleRequest)}`,
    { [exposedName]: styleVar }
  ];
}
async function genCustomBlockCode(descriptor, pluginContext) {
  let code = "";
  for (let index = 0; index < descriptor.customBlocks.length; index++) {
    const block = descriptor.customBlocks[index];
    if (block.src) {
      await linkSrcToDescriptor(block.src, descriptor, pluginContext, false);
    }
    const src = block.src || descriptor.filename;
    const attrsQuery = attrsToQuery(block.attrs, block.type);
    const srcQuery = block.src ? `&src=true` : ``;
    const query = `?vue&type=${block.type}&index=${index}${srcQuery}${attrsQuery}`;
    const request = JSON.stringify(src + query);
    code += `import block${index} from ${request}
`;
    code += `if (typeof block${index} === 'function') block${index}(_sfc_main)
`;
  }
  return code;
}
async function linkSrcToDescriptor(src, descriptor, pluginContext, scoped) {
  const srcFile = (await pluginContext.resolve(src, descriptor.filename))?.id || src;
  setSrcDescriptor(srcFile.replace(/\?.*$/, ""), descriptor, scoped);
}
const ignoreList = ["id", "index", "src", "type", "lang", "module", "scoped"];
function attrsToQuery(attrs, langFallback, forceLangFallback = false) {
  let query = ``;
  for (const name in attrs) {
    const value = attrs[name];
    if (!ignoreList.includes(name)) {
      query += `&${encodeURIComponent(name)}${value ? `=${encodeURIComponent(value)}` : ``}`;
    }
  }
  if (langFallback || attrs.lang) {
    query += `lang` in attrs ? forceLangFallback ? `&lang.${langFallback}` : `&lang.${attrs.lang}` : `&lang.${langFallback}`;
  }
  return query;
}

async function transformStyle(code, descriptor, index, options, pluginContext, filename) {
  const block = descriptor.styles[index];
  const result = await options.compiler.compileStyleAsync({
    ...options.style,
    filename: descriptor.filename,
    id: `data-v-${descriptor.id}`,
    isProd: options.isProduction,
    source: code,
    scoped: !!block.scoped,
    ...options.cssDevSourcemap ? {
      postcssOptions: {
        map: {
          from: filename,
          inline: false,
          annotation: false
        }
      }
    } : {}
  });
  if (result.errors.length) {
    result.errors.forEach((error) => {
      if (error.line && error.column) {
        error.loc = {
          file: descriptor.filename,
          line: error.line + getLine(descriptor.source, block.start),
          column: error.column
        };
      }
      pluginContext.error(error);
    });
    return null;
  }
  const map = result.map ? await formatPostcssSourceMap(result.map, filename) : { mappings: "" };
  return {
    code: result.code,
    map
  };
}
function getLine(source, start) {
  const lines = source.split(/\r?\n/g);
  let cur = 0;
  for (let i = 0; i < lines.length; i++) {
    cur += lines[i].length;
    if (cur >= start) {
      return i;
    }
  }
}

function vuePlugin(rawOptions = {}) {
  const {
    include = /\.vue$/,
    exclude
  } = rawOptions;
  const filter = createFilter(include, exclude);
  let options = {
    isProduction: process.env.NODE_ENV === "production",
    compiler: null,
    ...rawOptions,
    include,
    exclude,
    root: process.cwd(),
    sourceMap: true,
    cssDevSourcemap: false,
    devToolsEnabled: process.env.NODE_ENV !== "production"
  };
  return {
    name: "vite:vue2",
    handleHotUpdate(ctx) {
      if (!filter(ctx.file)) {
        return;
      }
      return handleHotUpdate(ctx, options);
    },
    configResolved(config) {
      options = {
        ...options,
        root: config.root,
        isProduction: config.isProduction,
        sourceMap: config.command === "build" ? !!config.build.sourcemap : true,
        cssDevSourcemap: config.css?.devSourcemap ?? false,
        devToolsEnabled: !config.isProduction
      };
      if (!config.resolve.alias.some(({ find }) => find === "vue")) {
        config.resolve.alias.push({
          find: "vue",
          replacement: "vue/dist/vue.runtime.esm.js"
        });
      }
    },
    configureServer(server) {
      options.devServer = server;
    },
    buildStart() {
      options.compiler = options.compiler || resolveCompiler(options.root);
    },
    async resolveId(id) {
      if (id === NORMALIZER_ID || id === HMR_RUNTIME_ID) {
        return id;
      }
      if (parseVueRequest(id).query.vue) {
        return id;
      }
    },
    load(id, opt) {
      const ssr = opt?.ssr === true;
      if (id === NORMALIZER_ID) {
        return normalizerCode;
      }
      if (id === HMR_RUNTIME_ID) {
        return hmrRuntimeCode;
      }
      const { filename, query } = parseVueRequest(id);
      if (query.vue) {
        if (query.src) {
          return fs.readFileSync(filename, "utf-8");
        }
        const descriptor = getDescriptor(filename, options);
        let block;
        if (query.type === "script") {
          block = getResolvedScript(descriptor, ssr);
        } else if (query.type === "template") {
          block = descriptor.template;
        } else if (query.type === "style") {
          block = descriptor.styles[query.index];
        } else if (query.index != null) {
          block = descriptor.customBlocks[query.index];
        }
        if (block) {
          return {
            code: block.content,
            map: block.map
          };
        }
      }
    },
    async transform(code, id, opt) {
      const ssr = opt?.ssr === true;
      const { filename, query } = parseVueRequest(id);
      if (query.raw) {
        return;
      }
      if (!filter(filename) && !query.vue) {
        return;
      }
      if (!query.vue) {
        return transformMain(code, filename, options, this, ssr);
      } else {
        const descriptor = query.src ? getSrcDescriptor(filename, query) : getDescriptor(filename, options);
        if (query.type === "template") {
          return {
            code: await transformTemplateAsModule(code, descriptor, options, this, ssr),
            map: {
              mappings: ""
            }
          };
        } else if (query.type === "style") {
          return transformStyle(code, descriptor, Number(query.index), options, this, filename);
        }
      }
    }
  };
}

export { vuePlugin as default, parseVueRequest };
