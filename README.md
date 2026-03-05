# Pantheon Content Publisher for WordPress

**Contributors:** [getpantheon](https://profiles.wordpress.org/getpantheon/), [a11rew](https://profiles.wordpress.org/a11rew), [anaispantheor](https://profiles.wordpress.org/anaispantheor/), [roshnykunjappan](https://profiles.wordpress.org/roshnykunjappan/), [mklasen](https://profiles.wordpress.org/mklasen/), [jazzs3quence](https://profiles.wordpress.org/jazzs3quence/), [swb1192](https://profiles.wordpress.org/swb1192)
**Tags:** pantheon, content, google docs, embeds
**Requires at least:** 5.7  
**Tested up to:** 6.9  
**Stable tag:** 1.3.5-dev  
**Requires PHP:** 8.1.0  
**License:** GPLv2 or later  
**License URI:** <http://www.gnu.org/licenses/gpl-2.0.html>

[![Actively Maintained](https://img.shields.io/badge/Pantheon-Actively_Maintained-yellow?logo=pantheon&color=FFDC28)](https://pantheon.io/docs/oss-support-levels#actively-maintained-support)

<p align="center">
  <a target="_blank" href="https://pcc.pantheon.io/">
    <img src="assets/images/pantheon-fist-logo.svg" alt="Plugin Logo" width="72" height="72">
  </a>
</p>

<h3 align="center">Pantheon Content Publisher</h3>


<p align="center">
  <i>Publish WordPress content from Google Docs with Pantheon Content Cloud.</i>
  <br>
  <a href="https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/issues/new?template=bug_report.md&labels=bug">Report bug</a>
  ·
  <a href="https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/issues/new?template=feature_request.md&labels=feature">Request feature</a>
  ·
  <a href="https://pcc.pantheon.io/docs" target="_blank">Check out PCC Docs</a>
</p>

<div align="center">

[![Style Lint](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/actions/workflows/php-style-lint.yml/badge.svg)](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/actions/workflows/php-style-lint.yml)
[![PHP Compatibility 8.x](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/actions/workflows/php-version-compatibility.yml/badge.svg)](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/actions/workflows/php-version-compatibility.yml)

</div>

## Table of contents

- [Table of contents](#table-of-contents)
- [Quick start](#quick-start)
- [Custom post types](#custom-post-types)
- [Smart Components](#smart-components)
- [Development](#development)
- [Repository Actions](#repository-actions)
- [Requirements](#requirements)
- [Bugs and feature requests](#bugs-and-feature-requests)
- [Documentation](#documentation)
- [Versioning](#versioning)
- [Changelog](#changelog)

## Quick start

The Pantheon Content Publisher plugin can be installed like any other WordPress Plugin, from your WordPress Dashboard, go to Plugins -> Add Plugin and search for: Pantheon Content Publisher, click the Install Now button and then click Activate. 

After the plugin is active, set up your connection to Pantheon Content Publisher and Google Drive via the settings page in the WordPress admin dashboard.

Alternately you can download and install the plugin manually:

- [Download the latest release.](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/releases/)

or

- Clone the repo: `git clone https://github.com/pantheon-systems/pantheon-content-publisher-wordpress.git` in
  your `wp-content/plugins`
  folder

**_or soon_**

- Install via Composer: `composer require pantheon-systems/pantheon-content-publisher-wordpress`

**_If installing from source, make sure to follow the build instructions in the [Development](#development) section
below_**

## Custom post types

The plugin supports publishing content to any public post type registered on your WordPress site, not just the default Post and Page types.

For more details on configuring WordPress with Content Publisher, see the [WordPress Tutorial](https://docs.content.pantheon.io/wordpress-tutorial#h.dsdditst2j75).

### Selecting a post type

When creating or editing a collection in the plugin settings, you can choose which WordPress post type the collection's documents should be published as. The dropdown lists all public post types available on your WordPress site.

You can also select **"Chosen by the author"** to allow document authors to control the post type on a per-document basis using the `wp-post-type` metadata field.

### The `wp-post-type` metadata field

When a collection is configured with "Chosen by the author", the plugin reads a metadata field called **`wp-post-type`** from each document to determine which post type to use.

To set the post type for a document, add a metadata field in the Content Publisher dashboard (or the Google Docs add-on) with the system name `wp-post-type`. The value should be the slug of the desired post type (e.g. `post`, `page`).

The list of allowed values must be configured manually by the administrator in the Content Publisher dashboard or the Google Docs add-on. The plugin does not automatically synchronize the available post types.

### Fallback behavior

The plugin falls back to the default `post` type in the following cases:

1. The collection is set to "Chosen by the author" but the document does not have a `wp-post-type` metadata field.
2. The `wp-post-type` metadata field is present but empty.
3. The `wp-post-type` value does not match any public post type registered on the WordPress site.

Changing the post type setting on a collection only affects future publishes and does not modify previously published content.

## Smart Components

Smart Components let authors embed rich media directly from Google Docs. When a document is published, these components are rendered as native WordPress embeds.

### Adding a component in Google Docs

1. Open your document in Google Docs with the Content Publisher add-on enabled.
2. Type the '@' symbol in the document, a pop-up will show in which you can search for integrations, search for "Pantheon" and choose the "Pantheon Component". You will then see a pop-up.
3. Select **Media Embed** from the list of available components.
4. Enter the media URL (e.g. a YouTube or Vimeo link).
5. Optionally set custom **width** and **height** values (in px or %).
6. A live preview of the embed appears in the sidebar.
7. When the document is published, the component renders as a native WordPress embed in the post content.

### Supported providers

Media Embed supports any provider recognized by WordPress oEmbed, including:

- YouTube
- Vimeo
- Spotify
- DailyMotion
- Flickr
- Twitter / X
- Instagram

If a URL is not recognized by any WordPress oEmbed provider, the plugin falls back to rendering a plain `<iframe>`.

Default dimensions are **100% width** and **400px height**. Authors can override these per component.

### Registering custom components

Developers can register custom smart components using the `cpub_register_smart_components` action hook. Each component must implement the `SmartComponentInterface` (`Pantheon\ContentPublisher\Interfaces\SmartComponentInterface`), which requires four methods:

- `type(): string` — A unique identifier (e.g. `'MY_COMPONENT'`).
- `schema(): array` — The field schema exposed to the Google Docs add-on.
- `render(array $attrs): string` — Returns the HTML output for the component.
- `allowedHtmlTags(): array` — HTML tags and attributes required by the component for `wp_kses`.

```php
add_action('cpub_register_smart_components', function ($registry) {
    $registry->register(new My_Custom_Component());
});
```

### Limitations

- Components must be placed in their own paragraph in Google Docs. Inline placement within other text can cause issues.

## Development

1. `composer i && npm i` to install dependencies.
2. `npm run watch` / `npm run dev` / `npm run prod` to build assets.
Since version 1.3.0 `npm run build:vite` to build assets and dist/build folder.
3. Read through
   our [contributing guidelines](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/blob/main/.github/CONTRIBUTING.md)
   for additional information. Included are directions for opening issues, coding standards and miscellaneous notes.

## Repository Actions

This repository takes advantage of the following workflows to automate the release & testing processes:

- [PHPCS](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/blob/main/.github/workflows/php-style-lint.yml)
- [PHPCompatibility](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/blob/main/.github/workflows/php-version-compatibility.yml)
- [Release Drafter](https://github.com/marketplace/actions/release-drafter)
- [PR Labeler](https://github.com/marketplace/actions/pr-labeler)
- [A custom workflow that builds release artifacts](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/blob/main/.github/workflows/release-artifact.yml)

These workflows will build a release draft and keep it up-to-date as new PRs are merged. Once a release is published, a
ready-to-install zip file will be generated and attached to the newly-published release.
To take advantage of these automations, make sure to read the available config files and workflow recipes, available in
the `.github` folder in the root of this repository.

Examples: _(read the config files for full configuration)_

- `feature/<branch-name>` or `feat/<branch-name>` adds the `feature` label to your PR
- PRs labeled `feature` will be categorized in the "🚀 Features" section of the release
- PRs labeled `major`/`minor`/`patch` will bump the major/minor/patch version number of the release

## Requirements

Pantheon Content Publisher is dependent on:

- Minimum **PHP** version **8.1**
- Minimum **WordPress** version **5.7**

## Bugs and feature requests

Have a bug or a feature request? Please first read
the [issue guidelines](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/blob/main/.github/CONTRIBUTING.md#using-the-issue-tracker)
and search for existing and closed issues. If your problem or idea is not addressed
yet, [please open a new issue](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/issues/new).

## Documentation

Documentation is available at [pcc.pantheon.io/docs](https://pcc.pantheon.io/docs).

## Versioning

For transparency into our release cycle and in striving to maintain backward compatibility, Pantheon Content Publisher
is maintained under [the Semantic Versioning guidelines](http://semver.org/). Sometimes we screw up, but we
adhere to those rules whenever possible.

## Changelog

You may find changelogs for each version of Pantheon Content Publisher released
in [the Releases section](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/releases) of this
repository.
