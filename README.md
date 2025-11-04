# Pantheon Content Publisher for WordPress

**Contributors:** [getpantheon](https://profiles.wordpress.org/getpantheon/) <!-- TODO: Add more contributors. -->   
**Tags:** pantheon, content, google docs  
**Requires at least:** 5.7  
**Tested up to:** 6.8.1  
**Stable tag:** 1.3.2  
**Requires PHP:** 8.0.0  
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
  <a href="https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/issues/new?template=bug_report.md&labels=bug">Report bug</a>·
  <a href="https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/issues/new?template=feature_request.md&labels=feature">Request feature</a>·
  <a href="https://pcc.pantheon.io/docs" target="_blank">Check out PCC Docs</a>
</p>

<div align="center">

[![Style Lint](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/actions/workflows/php-style-lint.yml/badge.svg)](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/actions/workflows/php-style-lint.yml)
[![PHP Compatibility 8.x](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/actions/workflows/php-version-compatibility.yml/badge.svg)](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/actions/workflows/php-version-compatibility.yml)

</div>

## Table of contents

- [Table of contents](#table-of-contents)
- [Quick start](#quick-start)
- [Development](#development)
- [Repository Actions](#repository-actions)
- [Requirements](#requirements)
- [Bugs and feature requests](#bugs-and-feature-requests)
- [Documentation](#documentation)
- [Versioning](#versioning)
- [Changelog](#changelog)

## Quick start

This is a WordPress plugin. It can be installed via the usual WordPress Dashboard workflow.

- [Download the latest release.](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/releases/)

or

- Clone the repo: `git clone https://github.com/pantheon-systems/pantheon-content-publisher-wordpress.git` in
  your `wp-content/plugins`
  folder

**_or soon_**

- Install via Composer: `composer require pantheon-systems/pantheon-content-publisher-wordpress`

**_If installing from source, make sure to follow the build instructions in the [Development](#development) section
below_**

## Development

1. `composer i && npm i` to install dependencies.
2. `npm run watch` / `npm run dev` / `npm run prod` to build assets.
Since version 1.3.0 `npm run build:vite` to build assets and dist/build folder.
3. Read through our [contributing guidelines](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/blob/primary/.github/CONTRIBUTING.md) for additional information. Included are directions for opening issues, coding standards and miscellaneous notes.

## Repository Actions

This repository takes advantage of the following workflows to automate the release & testing processes:

- [PHPCS](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/blob/primary/.github/workflows/php-style-lint.yml)
- [PHPCompatibility](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/blob/primary/.github/workflows/php-version-compatibility.yml)
- [Release Drafter](https://github.com/marketplace/actions/release-drafter)
- [PR Labeler](https://github.com/marketplace/actions/pr-labeler)
- [A custom workflow that builds release artifacts](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/blob/primary/.github/workflows/release-artifact.yml)

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

- Minimum **PHP** version **8.0**
- Minimum **WordPress** version **5.7**

## Bugs and feature requests

Have a bug or a feature request? Please first read
the [issue guidelines](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/blob/primary/.github/CONTRIBUTING.md#using-the-issue-tracker)
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
