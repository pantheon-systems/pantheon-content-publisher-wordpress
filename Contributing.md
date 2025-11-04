# Contributing

The best way to contribute to the development of this plugin is by participating in the GitHub project:

<https://github.com/pantheon-systems/pantheon-content-publisher-wordpress>

## Workflow

Development and releases are structured around two branches, `primary` and `release`.
The `primary` branch is the default branch for the repository, and is the source and destination for feature branches.

We prefer to squash commits (i.e., avoid merge PRs) from a feature branch into `primary` when merging and to include the PR number in the commit message. PRs to `primary` should also include any relevant updates to the changelog in readme.txt. For example, if a feature constitutes a minor or major version bump, that version update should be discussed and made as part of approving and merging the feature into `primary`.

`primary` should be stable and usable, though possibly a few commits ahead of the public release on wp.org.

The `release` branch matches the latest stable release deployed to [wp.org](wp.org).

## Release Process

1. Merge feature branches into `primary` with a PR. This PR should include any necessary updates to the changelog in readme.txt and README.md. Features should be _squash merged_.
1. From main, checkout a new branch `release_X.Y.Z`.
1. Make a release commit:
    * In `README.md`, `readme.txt`, and `pantheon-content-publisher.php`, remove the -dev from the version number. For the README files. the version number must be updated both at the top of the document as well as the changelog.
    * Add the date to the ** X.Y.X ** heading in the changelogs in README.md, readme.txt, and any other appropriate location.
    * Commit these changes with the message "Release X.Y.Z"
1. Push the release branch up.
1. Open a Pull Request to merge `release_X.Y.Z` into release. Your PR should consist of all commits to main since the last release, and one commit to update the version number. The PR name should also be Release X.Y.Z.
1. After all tests pass and you have received approvals from CODEOWNERs, merge the PR into `release`. A merge commit is needed in this case. **Never** squash to release.
1. After merging to the `release` branch, a draft Release will be automatically created by the [`build-tag-release`](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/.github/workflows/build-tag-release.yml) workflow. This draft release will be automatically pre-filled with release notes.
1. Confirm that the necessary assets are present in the newly created tag, and test on a WP install if desired.
1. Review the release notes, making any necessary changes, and publish the release.
1. Wait for the [_Release pantheon-advanced-page-cache plugin to wp.org_ action](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/.github/workflows/wordpress-plugin-deploy.yml) to finish deploying to the WordPress.org plugin repository. If all goes well, users with SVN commit access for that plugin will receive an email with a diff of the changes.
1. Check WordPress.org: Ensure that the changes are live on the plugin repository. This may take a few minutes.
1. Following the release, prepare the next dev version with the following steps:
    * `git checkout release`
    * `git pull origin release`
    * `git checkout primary`
    * `git rebase release`
    * Update the version number in all locations, incrementing the version by one patch version, and add the `-dev` flag (e.g. after releasing `1.2.3`, the new version will be `1.2.4-dev`)
    * Add a new `** X.Y.X-dev **` heading to the changelog in readme.txt and README.md
    * `git add -A .`
    * `git commit -m "Prepare X.Y.X-dev"`
    * `git push origin primary`
1. Publish a public release note on [Pantheon's Documentation](https://github.com/pantheon-systems/documentation/) with the changelog and any notes.
