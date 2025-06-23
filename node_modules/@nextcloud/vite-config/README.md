# `@nextcloud/vite-config`
[![npm last version](https://img.shields.io/npm/v/@nextcloud/vite-config.svg?style=flat-square)](https://www.npmjs.com/package/@nextcloud/vite-config) [![Project documentation](https://img.shields.io/badge/documentation-online-blue?style=flat-square)](https://nextcloud-libraries.github.io/nextcloud-vite-config/)

Shared Vite ⚡ config for Nextcloud apps and libraries, which also can be easily extended.

## API reference

The full API reference can be found on the [documentation](https://nextcloud-libraries.github.io/nextcloud-vite-config/).

## How to use
If your app uses an entry point for the main app and one for the settings page, then your default project tree will look like this:
```
css/
js/
lib/
src/
  |- ...
  |- main.js
  |- settings.js
package.json
vite.config.js
```

And your `vite.config.js` should look like this:
```js
import { createAppConfig } from '@nextcloud/vite-config'

export default createAppConfig({
    // entry points: {name: script}
    main: 'src/main.js',
    settings: 'src/settings.js',
})
```

You can also modify the configuration, for example if you want to set an include path for the scss preprocessor:

```js
import { createAppConfig } from '@nextcloud/vite-config'
import { defineConfig } from 'vite'
import path from 'node:path'

const yourOverrides = defineConfig({
    css: {
        preprocessorOptions: {
            scss: {
                includePaths: [
                    path.resolve(__dirname, './src/assets'),
                ],
            },
        },
    }
})

export default createAppConfig({
    // entry points
    main: 'src/main.js',
    settings: 'src/settings.js',
}, {
    // options
    config: yourOverrides
})
```

### Use with a library
There is also a configuration for libraries, this configuration will handle the output directory correctly and automatically mark all `dependencies` as external:

```js
import { createLibConfig } from '@nextcloud/vite-config'

const translations = //...

export default createLibConfig({
    index: 'src/index.js',
}, {
    replace: {
        TRANSLATIONS: translations,
    },
})
```

### Notes

#### Inlining / injecting CSS
You can enable inlining CSS code, but please note that this is handled differently for apps and libraries.
* Apps will inline the CSS by dynamically inject it as `script` tags
* Libraries will extract the CSS to the dist/assets directory and import it in the entry point

For apps any styles can be injected in the JS by dynamically inject the styles in the document (creating `<style>` tags).
But this only works in DOM environments, so for libraries this might not work (e.g. while testing in the Node environment).

So for libraries the CSS will still be extracted by Vite, but the extracted CSS assets will be imported.
This way the library user can decide how to handle the imported CSS without relying on a DOM environment.

## Development

### 📤 Releasing a new version

- Pull the latest changes from `main` or `stableX`
- Checkout a new branch with the tag name (e.g `v4.0.1`): `git checkout -b v<version>`
- Run `npm version patch --no-git-tag-version` (`npm version minor --no-git-tag-version` if minor).
  This will return a new version name, make sure it matches what you expect
- Generate the changelog content from the [release](https://github.com/nextcloud-libraries/nextcloud-vite-config/releases) page.
  Create a draft release, select the previous tag, click `generate` then paste the content to the `CHANGELOG.md` file
  1. adjust the links to the merged pull requests and authors so that the changelog also works outside of GitHub
     by running `npm run prerelease:format-changelog`.
     This will apply this regex: `by @([^ ]+) in ((https://github.com/)nextcloud-libraries/nextcloud-vite-config/pull/(\d+))`
     Which this as the replacement: `[\#$4]($2) \([$1]($3$1)\)`
  2. use the the version as tag AND title (e.g `v4.0.1`)
  3. add the changelog content as description (https://github.com/nextcloud-libraries/nextcloud-vite-config/releases)
- Commit, push and create PR
- Get your PR reviewed and merged
- Create a milestone with the follow-up version at https://github.com/nextcloud-libraries/nextcloud-vite-config/milestones
- Move all open tickets and PRs to the follow-up
- Close the milestone of the version you release
- Publish the previously drafted release on GitHub
  ![image](https://user-images.githubusercontent.com/14975046/124442568-2a952500-dd7d-11eb-82a2-402f9170231a.png)
