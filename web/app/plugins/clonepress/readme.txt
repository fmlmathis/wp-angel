=== ClonePress - Duplicate Pages, Posts & Custom Post Types ===
Contributors: ilmosys, mahdiali
Donate link: https://ilmosys.com
Tags: duplicate, clone, posts, pages, custom post types
Tested up to: 6.7
Stable tag: 1.0.2
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Easily duplicate posts, pages, and custom post types with a single click.

== Description ==

ClonePress is a simple and lightweight plugin that allows you to duplicate posts, pages, and custom post types with just one click. This is especially helpful for content creators, website administrators, and developers who want to quickly create drafts or templates from existing content.

### Key Features
- Duplicate posts, pages, and custom post types.
- Easy-to-use interface with seamless WordPress integration.
- Choose between draft or published status for duplicated content.
- Maintains all meta data and taxonomies.

See the tutorial on how to duplicate a page or post – quick and easy! By Tutsflow.
[youtube https://youtu.be/yMjVz-FdgpA]

### Supported Post Types
- Post
- Page
- EDD Download
- Elementor
- Custom Post Type (including any registered post type)

Whether you're managing a small blog or a large website, ClonePress helps streamline your workflow by allowing you to clone content effortlessly.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/clonepress` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to Settings > ClonePress to configure the plugin settings.
4. Navigate to your posts, pages, or custom post types, and look for the "Duplicate" option under each item.

== Frequently Asked Questions ==

= Does this plugin support custom post types? =
Yes, ClonePress supports duplicating custom post types automatically.

= Can I choose the status of duplicated content? =
Yes, you can choose whether duplicated content should be saved as a draft or published immediately through the plugin settings.

= Does it copy all post meta and taxonomies? =
Yes, ClonePress copies all meta data and taxonomy terms associated with the original content.

== Screenshots ==

1. The duplicate link appears in the post/page list
2. Settings page where you can configure the duplicate post status

== Changelog ==

= 1.0.2 =
* Added settings option for customizing the suffix for duplicated posts.
* Added settings option for redirecting after duplication to either the post list or the edit page.
* Improved sanitization and security checks for user inputs.
* Updated the duplication process to use a single SQL query for duplicating post metadata.
* Fixed issue with Elementor-related meta keys not being properly duplicated.
* Ensured proper nonce verification and sanitization of query parameters.

= 1.0.1 =
* Added functionality to change the "Duplicate" label via the settings

= 1.0.0 =
* Initial release