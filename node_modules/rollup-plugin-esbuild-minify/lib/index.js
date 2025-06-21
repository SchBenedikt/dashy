import { transform, formatMessages } from 'esbuild'

export function minify({
  logLevel = 'warning',
  logLimit = 10,
  legalComments = 'none',
  minify = true
} = {}) {
  const warn = logLevel !== 'silent' && logLevel !== 'error'

  async function printWarnings(warnings) {
    const messages = await formatMessages(warnings, { kind: 'warning', color: true })
    let l = warnings.length
    /* c8 ignore next 3 */
    if (logLimit > 0 && logLimit < l) {
      l = logLimit
    }
    for (let i = 0; i < l; ++i) {
      const { location = {} } = warnings[i]
      const message = messages[i]
      this.warn(message, { column: location.column, line: location.line })
    }
  }

  return {
    name: 'esbuild-minify',

    async renderChunk(contents, _, { format, sourcemap }) {
      const { code, map, warnings } = await transform(contents, {
        // If the format `cjs` is not provided, module-local variables
        // will not be minified. Other formats are not affected.
        format: format === 'cjs' ? 'cjs' : undefined,
        loader: 'js',
        minify,
        legalComments,
        sourcemap: sourcemap ? 'external' : false,
        logLevel,
        logLimit
      })

      if (warn && warnings.length > 0) {
        await printWarnings.call(this, warnings)
      }

      return { code, map: map || null }
    }
  }
}
