# Contributing to Pantheon Content Publisher

Looking to contribute something to Pantheon Content Publisher? **Here's how you can help.**

Please take a moment to review this document in order to make the contribution
process easy and effective for everyone involved.

Following these guidelines helps to communicate that you respect the time of
the developers managing and developing this open source project. In return,
they should reciprocate that respect in addressing your issue or assessing
patches and features.

## Using the issue tracker

The [issue tracker](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/issues) is
the preferred channel for [bug reports](#bug-reports), [features requests](#feature-requests)
and [submitting pull requests](#pull-requests), but please respect the following
restrictions:

* Please **do not** derail or troll issues. Keep the discussion on topic and
  respect the opinions of others.

* Please **do not** post comments consisting solely of "+1" or ":thumbsup:".
  Use [GitHub's "reactions" feature](https://blog.github.com/2016-03-10-add-reactions-to-pull-requests-issues-and-comments/)
  instead. We reserve the right to delete comments which violate this rule.

## Issues and labels

Our bug tracker utilizes several labels to help organize and identify issues. Here's what they represent and how we use
them:

- `good first issue` - GitHub will help potential first-time contributors discover issues that have this label.
- `bug` - Issues that have been confirmed with a reduced test case and identify a bug in Pantheon Content Publisher.
- `docs` - Issues for improving or updating our documentation.
- `feature` - Issues asking for a new feature to be added, or an existing one to be extended or modified.
- `help wanted` - Issues we need or would love help from the community to resolve.
- `js` - Issues stemming from our compiled or source JavaScript files.
- `meta` - Issues with the project itself or our GitHub repository.
- `duplicate` - This issue or pull request already exists.
- `question` - General support/questions issue bucket.

For a complete look at our labels, see
the [project labels page](https://github.com/pantheon-systems/pantheon-content-publisher-wordpress/labels).

## Bug reports

A bug is a _demonstrable problem_ that is caused by the code in the repository.
Good bug reports are extremely helpful, so thanks!

Guidelines for bug reports:

0. **Validate your code** to ensure your
   problem isn't caused by a simple error in your own code.

1. **Use the GitHub issue search** &mdash; check if the issue has already been
   reported.

2. **Check if the issue has been fixed** &mdash; try to reproduce it using the
   latest `primary` or `develop` branch in the repository.

3. **Isolate the problem** &mdash; ideally create or record a live example.

A good bug report shouldn't leave others needing to chase you up for more
information. Please try to be as detailed as possible in your report. What is
your environment(installed plugins, WordPress version, plugin version)? What steps will reproduce the issue? What
browser(s) and serverstack
experience the problem? What
would you expect to be the outcome? All these details will help people to fix
any potential bugs.

Example:

> Short and descriptive example bug report title
>
> A summary of the issue and the browser/serverstack/WordPress environment in which it occurs. If
> suitable, include the steps required to reproduce the bug.
>
> 1. This is the first step
> 2. This is the second step
> 3. Further steps, etc.
>
>
> Any other information you want to share that is relevant to the issue being
> reported. This might include the lines of code that you have identified as
> causing the bug, and potential solutions (and your opinions on their
> merits).

## Feature requests

Feature requests are welcome. But take a moment to find out whether your idea
fits with the scope and aims of the project. It's up to *you* to make a strong
case to convince the project's developers of the merits of this feature. Please
provide as much detail and context as possible.

## Pull requests

Good pull requests—patches, improvements, new features—are a fantastic
help. They should remain focused in scope and avoid containing unrelated
commits.

**Please ask first** before embarking on any significant pull request (e.g.
implementing features, refactoring code), otherwise you risk spending
a lot of time working on something that the project's developers
might not want to merge into the project.

Please adhere to the [coding guidelines](#code-guidelines) used throughout the
project (indentation, accurate comments, etc.) and any other requirements
(such as test coverage).

**Do not edit compiled assets or vendor dependencies directly!**
Those files are automatically generated (`build` folder).
You should edit the source files found in the `assets` folder.

## Code guidelines

### PHP

Pantheon Content Publisher follows an extended [PSR12 ruleset](../phpcs.xml) that includes WordPress Security sniffs
with the added change of preffering tabs instead of spaces for indentation.
**Your code should adhere to this ruleset.**

### JS

We adhere to the [AirBnB JavaScript Style Guide](https://github.com/airbnb/javascript) for project JavaScript
code. [All rules apply](https://github.com/airbnb/javascript/blob/master/README.md#table-of-contents) with the following
exceptions:

- Ignore rules related to IE8 support. We don't generally do it, and sometimes it can be useful to use a normal property
  overridden into a different one (such as a CSS styles object to pass to jQuery).
- Put all functions at the end of scope, possibly after a return. Doing this comes from the Angular guide, and makes it
  easier to follow a narrative flow of the code. We see the behaviors, then can quickly dig into the implementation if
  we wish to. (Credit to [John Papa's Angular Style guide](https://github.com/johnpapa/angular-styleguide) for this
  idea)

### Checking coding style

Run `composer run php:lint:report` before committing to ensure your changes follow our coding standards.
