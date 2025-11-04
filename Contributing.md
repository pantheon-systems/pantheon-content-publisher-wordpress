# Contributing

The best way to contribute to the development of this plugin is by participating on the GitHub project:

<https://github.com/pantheon-systems/pantheon-content-publisher-wordpress>

## Workflow

Development and releases are structured around two branches, `primary` and `release`.
The `primary` branch is the default branch for the repository, and is the source and destination for feature branches.

We prefer to squash commits (i.e. avoid merge PRs) from a feature branch into `primary` when merging, and to include the PR # in the commit message. PRs to `primary` should also include any relevent updates to the changelog in readme.txt. For example, if a feature constitutes a minor or major version bump, that version update should be discussed and made as part of approving and merging the feature into `primary`.

`primary` should be stable and usable, though possibly a few commits ahead of the public release on wp.org.

The `release` branch matches the latest stable release deployed to [wp.org](wp.org).

## Release Process

1. Merge your feature branch into `primary` with a PR. This PR should include any necessary updates to the changelog in readme.txt and README.md.
1. After merging the release PR to the `release` branch, a draft Release will be automatically be created by the [`build-tag-release`](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/.github/workflows/build-tag-release.yml) workflow. This draft release will be automatically pre-filled with release notes.
1. Confirm that the necessary assets are present in the newly created tag, and test on a WP install if desired.
1. Review the release notes making any necessary changes and publish the release.
1. Wait for the [_Release pantheon-advanced-page-cache plugin to wp.org_ action](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/.github/workflows/wordpress-plugin-deploy.yml) to finish deploying to the WordPress.org plugin repository. If all goes well, users with SVN commit access for that plugin will receive an emailed diff of changes.
1. Check WordPress.org: Ensure that the changes are live on the plugin repository. This may take a few minutes.
1. Following the release, prepare the next dev version with the following steps:
    * `git checkout release`
    * `git pull origin release`
    * `git checkout primary`
    * `git rebase release`
    * Update the version number in all locations, incrementing the version by one patch version, and add the `-dev` flag (e.g. after releasing `1.2.3`, the new verison will be `1.2.4-dev`)
    * Add a new `** X.Y.X-dev **` heading to the changelog in readme.txt and README.md
    * `git add -A .`
    * `git commit -m "Prepare X.Y.X-dev"`
    * `git push origin primary`
