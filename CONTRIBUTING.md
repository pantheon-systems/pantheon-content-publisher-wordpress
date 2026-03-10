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

### Preparing a release

1. Merge your feature branch into `main` with a PR. This PR should include any necessary updates to the changelog in readme.txt and README.md. Features should be squash merged.
1. The `-dev` version on `main` determines the version number the release automation will use. If the next release warrants a minor or major bump (e.g., `1.3.6-dev` should become `1.4.0`), include the version change in the last feature PR merged to `main` before releasing. Update the version in `package.json`, `package-lock.json`, `README.md`, `readme.txt`, and `pantheon-content-publisher.php`.
1. The `release-pr.yml` workflow automatically creates a `release-X.Y.Z` branch and a draft PR from it to `release`. The branch is updated on every subsequent push to `main`, stripping the `-dev` suffix and updating the version in all files.
1. Find the draft Release PR in the open pull requests. Add the release date to the changelog heading in `readme.txt` (the automation does not add it).
1. After all tests pass and you have received approval from a CODEOWNER (including resolving any merge conflicts), merge the PR into `release`. Use a "merge" commit, do not rebase or squash. If the GitHub UI doesn't offer a "Merge commit" option (only showing "Squash and merge" or "Rebase and merge"), merge from the terminal instead:
    `git checkout release`
    `git merge release_X.Y.Z`
    `git push origin release`

### Publishing the release

1. After merging to the `release` branch, a draft Release will be automatically created by the `build-tag-release.yml` workflow. This draft release will be automatically pre-filled with release notes.
1. Confirm that the necessary assets are present in the newly created tag, and test on a WP install if desired.
1. Review the release notes, making any necessary changes. Remove CI and automation changes (dependency bumps, workflow updates, etc.) from the release notes — only include user-facing changes. Publish the release.
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
1. Create a pull request on the GitHub UI from `release-XYZ-dev` to `main`. This PR serves two purposes: triggering CI status checks and getting CODEOWNER approval. **Do not merge this PR from the UI.**
1. Once CI passes and a CODEOWNER has approved, push to main from the terminal:
    ```
    git checkout main && git push origin main
    ```
    The PR will auto-close when GitHub detects the commits have landed on `main`.

    _Why not merge from the UI? The terminal push replaces remote `main` with the locally rebased version, reconciling it with `release`. A UI merge would add the commit on top of the old, un-reconciled remote `main`, leaving the branches still diverged._
