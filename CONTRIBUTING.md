# Contributing

The best way to contribute to the development of this plugin is by participating in the GitHub project:

<https://github.com/pantheon-systems/pantheon-content-publisher-wordpress>

## Workflow

Development and releases are structured around two branches, `main` and `release`.
The `main` branch is the default branch for the repository, and is the source and destination for feature branches.

We prefer to squash commits (i.e., avoid merge PRs) from a feature branch into `main` when merging and to include the PR number in the commit message. PRs to `main` should also include any relevant updates to the changelog in readme.txt. For example, if a feature constitutes a minor or major version bump, that version update should be discussed and made as part of approving and merging the feature into `main`.

`main` should be stable and usable, though possibly a few commits ahead of the public release on wp.org.

The `release` branch matches the latest stable release deployed to [wp.org](wp.org).

## Release Process

### Automation overview

Three GitHub Actions workflows assist with releases:

- **Draft Release PR** (`release-pr.yml`) — On every push to `main`, automatically creates or updates a draft PR from `main` to `release`. This PR accumulates all changes since the last release.
- **Build, Tag & Release** (`build-tag-release.yml`) — On push to `release`, automatically creates a draft GitHub Release with compiled assets and generated release notes.
- **WordPress.org Deploy** (`wordpress-plugin-deploy.yml`) — When a GitHub Release is published (non-prerelease), automatically deploys to the WordPress.org plugin repository.

### Preparing a release

1. Merge your feature branch into `main` with a PR. This PR should include any necessary updates to the changelog in readme.txt and README.md. Features should be squash merged.
1. If the next release warrants a minor or major version bump, update the `-dev` version on `main` before proceeding (e.g., change `1.3.6-dev` to `1.4.0-dev`). The `-dev` version is a placeholder — it determines the version number the automation will use.
1. On every push to `main`, the `release-pr.yml` workflow automatically:
    * Creates a `release-X.Y.Z` branch from `main`
    * Strips the `-dev` suffix and updates the version in all files
    * Commits the changes as `Release X.Y.Z`
    * Opens a draft PR from `release-X.Y.Z` to `release`
1. Find the draft Release PR in the open pull requests. Add the release date to the changelog heading in `readme.txt` if the automation did not.
1. After all tests pass and you have received approval from a CODEOWNER (including resolving any merge conflicts), merge the PR into `release`. Use a "merge" commit, do not rebase or squash. If the GitHub UI doesn't offer a "Merge commit" option (only showing "Squash and merge" or "Rebase and merge"), merge from the terminal instead:
    `git checkout release`
    `git merge release_X.Y.Z`
    `git push origin release`

### Publishing the release

1. After merging to the `release` branch, a draft Release will be automatically created by the `build-tag-release.yml` workflow. This draft release will be automatically pre-filled with release notes.
1. Confirm that the necessary assets are present in the newly created tag, and test on a WP install if desired.
1. Review the release notes, making any necessary changes, and publish the release.
1. The `wordpress-plugin-deploy.yml` workflow will automatically deploy to the WordPress.org plugin repository.
1. If all goes well, users with SVN commit access for that plugin will receive an email with a diff of the changes.
1. Check WordPress.org: Ensure that the changes are live on the plugin repository. This may take a few minutes.

### Post-release: reconcile branches and prepare next dev version

After publishing a release, `main` and `release` will have diverged due to merge commits on `release`. Follow these steps to reconcile them and prepare the next development cycle:

1. Rebase `main` on `release`:
    ```
    git checkout release && git pull origin release
    git checkout main && git rebase release
    ```
1. Increment the version to the next **patch** version with a `-dev` flag (e.g., after releasing `1.3.5`, set `1.3.6-dev`). This is a placeholder — the actual release version is determined at release time.
    * Update the version in: `package.json`, `package-lock.json`, `README.md`, `readme.txt`, and `pantheon-content-publisher.php`
    * Add a new empty `** X.Y.Z-dev **` heading to the changelog in `readme.txt`
1. Commit and push via a PR branch to trigger CI:
    ```
    git add -A .
    git commit -m "Prepare X.Y.Z-dev"
    git checkout -b release-XYZ-dev
    git push origin release-XYZ-dev
    ```
1. Create a pull request on the GitHub UI from `release-XYZ-dev` to `main` to trigger all required status checks.
1. Wait for CI to pass and get approval from a CODEOWNER. Then push to main from the terminal:
    ```
    git checkout main && git push origin main
    ```
    _Note: `main` is protected, but having an approved PR with passing checks allows a direct push from the terminal. This is preferred over merging from the GitHub UI because the terminal push replaces remote `main` with the locally rebased version, reconciling it with `release`. A UI merge would add the commit on top of the old, un-reconciled remote `main`, leaving the branches still diverged._
