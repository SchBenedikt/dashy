# rollup-plugin-esbuild-minify

[![Latest version](https://img.shields.io/npm/v/rollup-plugin-esbuild-minify)
 ![Dependency status](https://img.shields.io/librariesio/release/npm/rollup-plugin-esbuild-minify)
](https://www.npmjs.com/package/rollup-plugin-esbuild-minify)
[![Coverage](https://codecov.io/gh/prantlf/rollup-plugin-esbuild-minify/branch/master/graph/badge.svg)](https://codecov.io/gh/prantlf/rollup-plugin-esbuild-minify)

[Rollup] plugin to minify or clean up generated bundles using [esbuild].

Simpler than [rollup-plugin-esbuild] and [rollup-plugin-esbuild-transform], focusing only on minification of the bundled JavaScript output. Faster than [@rollup/plugin-terser]. See also [benchmark results of various minifiers].

## Synopsis

```js
import { minify } from 'rollup-plugin-esbuild-minify'

export default {
  plugins: [minify()]
  // the rest of the configuration
}
```

## Installation

Make sure that you use [Node.js] 14 or newer and [Rollup] 2 or newer. Use your favourite package manager - [NPM], [PNPM] or [Yarn]:

```sh
npm i -D rollup-plugin-esbuild-minify
pnpm i -D rollup-plugin-esbuild-minify
yarn add -D rollup-plugin-esbuild-minify
```

## Usage

Create a `rollup.config.js` [configuration file] and import the plugin:

```js
import { minify } from 'rollup-plugin-esbuild-minify'

export default {
  input: 'src/index.js',
  output: { file: 'dist/main.js', format: 'iife', sourcemap: true },
  plugins: [
    minify({ logLevel: 'debug', logLimit: 100 })
  ]
}
```

Then call `rollup` either via the [command-line] or [programmatically].

## Options

The following options can be passed in an object to the plugin function to change the default values.

### `logLevel`

Type: `'silent' | 'error' | 'warning' | 'info' | 'debug' | 'verbose'`<br>
Default: `'warning'`

Controls the number and detail of progress messages logged on the console.

See also the [original documentation](https://esbuild.github.io/api/#log-level).

### `logLimit`

Type: `Integer`<br>
Default: `10`

Maximum number of logged messages. If zero (`0`) is provided, all messages will be logged.

See also the [original documentation](https://esbuild.github.io/api/#log-limit).

### `legalComments`

Type: `'none' | 'inline' | 'eof' | 'linked' | 'external'`<br>
Default: `'none'`

Allows including or extracting legal comments. A "legal comment" is considered to be any statement-level comment that contains `@license` or `@preserve` or that starts with `//!` or `/*!`.

See also the [original documentation](https://esbuild.github.io/api/#legal-comments).

### `minify`

Type: `Boolean`<br>
Default: `true`

Allows disabling the minification. The code will be pretty-printed instead. It can be used to clean up the code.

See also the [original documentation](https://esbuild.github.io/api/#minify).

## Contributing

In lieu of a formal styleguide, take care to maintain the existing coding style. Lint and test your code.

## License

Copyright (C) 2022-2024 Ferdinand Prantl

Licensed under the [MIT License].

[MIT License]: http://en.wikipedia.org/wiki/MIT_License
[Rollup]: https://rollupjs.org/
[Node.js]: https://nodejs.org/
[NPM]: https://www.npmjs.com/
[PNPM]: https://pnpm.io/
[Yarn]: https://yarnpkg.com/
[configuration file]: https://www.rollupjs.org/guide/en/#configuration-files
[command-line]: https://www.rollupjs.org/guide/en/#command-line-reference
[programmatically]: https://www.rollupjs.org/guide/en/#javascript-api
[rollup-plugin-esbuild]: https://www.npmjs.com/package/rollup-plugin-esbuild
[rollup-plugin-esbuild-transform]: https://www.npmjs.com/package/rollup-plugin-esbuild-transform
[@rollup/plugin-terser]: https://www.npmjs.com/package/@rollup/plugin-terser
[esbuild]: https://esbuild.github.io/
[benchmark results of various minifiers]: https://github.com/prantlf/minification-benchmarks#-results
