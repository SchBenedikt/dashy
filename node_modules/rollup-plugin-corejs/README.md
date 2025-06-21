<!-- 
SPDX-FileCopyrightText: 2023 Ferdinand Thiessen <rpm@fthiessen.de>
SPDX-License-Identifier: EUPL-1.2
--->
[![NPM package version](https://img.shields.io/npm/v/rollup-plugin-corejs)](https://www.npmjs.com/package/rollup-plugin-corejs)
[![GitHub Workflow Status (main branch)](https://img.shields.io/github/actions/workflow/status/susnux/rollup-plugin-corejs/node.yml?branch=main)](https://github.com/susnux/rollup-plugin-corejs/actions/workflows/node.yml)
[![license](https://img.shields.io/npm/l/rollup-plugin-corejs?color=blue)](https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12)

# rollup-plugin-corejs
A rollup plugin for injecting [core-js](https://github.com/zloirock/core-js) polyfills based
on your browserslist configuration.

This plugin is essentially a wrapper for core-js-builder for rollup.

One usecase is that you are not using babel, but [esbuild](https://github.com/privatenumber/esbuild-loader)
for transpiling, e.g. using the [rollup-plugin-esbuild](https://www.npmjs.com/package/rollup-plugin-esbuild)
with your supported browsers is much faster then using Babel, but it only transpiles the syntax and does not add any polyfills.
So you would need to add e.g. *core-js* polyfills manually... or... use this plugin.

### Compatibility
This version should work with *rollup 3*.

## Getting started
### 🚀 Installation

```shell
npm i -D rollup-plugin-corejs
```

### 🔧 Configuration
If you are using CommonJS, then use this in your `rollup.config.js`:
```js
const { corejsPlugin } = require('rollup-plugin-corejs')

module.exports = {
  //...
  plugins: [corejsPlugin({
    // options
  })]
  // ...
}
```

Or if you are using module JS (e.g. `rollup.config.mjs`):
```js
import { corejsPlugin } from 'rollup-plugin-corejs'

export default {
  //...
  plugins: [
    corejsPlugin({
        // Options
    }),
    // ...
  ]
  // ...
}
```

### 🛠️ Options
All options are optionally, if no options are given the default is to use `{ modules: 'core-js/es' }`.

You can omit setting `targets` in this case browserslist is used (`package.json` or `.browserslistrc`).

```ts
{
  /** CoreJS modules to use, defaults to 'core-js/es' */
  modules?: string | readonly string[] | readonly RegExp[],
  /** CoreJS modules to exclude */
  exclude?: string | readonly string[] | readonly RegExp[],
  /** Overide browserslist targets */
  targets?: string | readonly string[] | Record<string, string | readonly string[]>
  /** Only include polyfills for APIs used by your code (dependencies not included) */
  usage?: boolean
}
```

## Changelog
See [CHANGELOG](CHANGELOG.md)

## License
[EUPL-1.2](LICENSES/EUPL-1.2.txt)