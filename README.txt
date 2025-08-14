=== Pantheon Content Publisher ===
Contributors: getpantheon
Tags: pantheon
Requires at least: 5.7
Tested up to: 6.8.1
Stable tag: 1.2.7
Requires PHP: 8.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

**The Pantheon Content Publisher plugin for WordPress enables seamless content publishing from Google Drive and Google Docs directly to WordPress sites.**

Perfect for editorial teams who collaborate on content within Google Docs, this plugin ensures a smooth transition from document creation to web publishing, facilitating real-time previews and direct publishing options.

## Integration with Third-Party Services
**Important Disclosure**
This plugin integrates with Google Drive and Google Docs to facilitate document publishing to WordPress.
When enabled, it will access documents from these services for the purposes of rendering previews and enabling publishing functionality via the [Pantheon Content Publisher addon](https://pcc.pantheon.io/docs).

**Data Handling**
User documents from Google Drive are accessed and processed to generate content on WordPress.
No other personal data is shared with or stored on third-party services beyond this operational scope.

## Features
**Real-time Preview**
Experience seamless document previews within your WordPress environment as they would appear live on the web.

**One-click Publishing**
Enable direct publishing from Google Docs to WordPress, simplifying content management and streamlining workflows.

**Post or Page Support**
Choose to publish as either a WordPress post or page, adapting to your site's content structure.

## Installation
Download the Content Publisher WordPress plugin zip file.

Navigate to Plugins > Add New in your WordPress admin dashboard.

Click “Upload Plugin,” select the downloaded zip file, then “Install Now” and activate the plugin.

Set up your connection to Pantheon Content Publisher and Google Drive via the settings page in the WordPress admin dashboard.

== Frequently Asked Questions ==

= How do I connect Pantheon Content Publisher to Google Drive? =
Create a management token at https://content.pantheon.io/dashboard. Proceed to the Pantheon Content Publisher settings page in your WordPress admin dashboard and paste the token into the "Management token" field.
The connection will be established automatically.

= What happens if I disconnect Pantheon Content Publisher from my Google Drive? =
All posts/pages created with Pantheon Content Publisher will remain on your WordPress site. However, you will no longer be able to edit them from Google Docs.

== Changelog ==
= 1.2.7 =
* Fix: Resolves issue where duplicate posts would be created when publishing from Google Docs.
* Feature: Adds linking of posts to authors by default

= 1.2.6 =
* Feature: Add support for draft publishing level and versioning

= 1.2.6-dev =
* Compatibility: Supports PHP 8.4 

= 1.2.5 =
* Fix: Disables the plugin disconnecting itself when the site URL changes
* Fix: Resolves import issue with webhook handling

= 1.2.4 =
* Feature: Adds support for article.publish webhook event
* Fix: Adds support for linking between documents intra-site

= 1.2.3 =
* Feature: Display collection ID on Connected Content Collection page.
* Feature: Improve preview request handling.
* Fix: Disables caching of preview pages.
* Fix: Correct font size for documentation link text.
* Fix: Updates "Access token" mention in setup page to "Management token" to more accurately reflect the required token type.
* Compatibility: Update pcc-sdk-core dependency.

= 1.2.2 =
* Compatibility: Ensure adherence to WP Plugin guidelines
* Compatibility: Save <style> tag at the end of post content
* Stability: Improve edge case handling for PCC articles
= 1.2.1 =
* Fix: Ensure clean excerpts for PCC articles
* Compatibility: Improve image upload compatibility
= 1.2.0 =
* Feature: Add support for the title, description, tags, categories and featured image custom metadata fields
* Revert: Re-Enable the WordPress editor for PCC articles
= 1.1.2 =
* Feature: Add disconnect button on intermediary screens of auth/config flow
= 1.1.1 =
* Fix: Verify collection URL logic
= 1.1.0 =
* Feature: Check if plugin is correctly configured before hooking logic
* Feature: Disconnect collection when site URL changes
* Fix: enable style tags globally
= 1.0.1 =
* Fix: Update PCC PHP SDK dependency
= 1.0.0 =
Initial Release
