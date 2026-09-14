=== Inkbound ===
Contributors: doodersrage
Donate link: https://github.com/doodersrage/inkbound
Tags: fiction, serial, webnovel, chapters, reading
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Serialized fiction tools for WordPress: stories, chapter management, reader follows, update mail, and reading progress.

== Description ==

Inkbound turns WordPress into a home for serialized fiction and web novels. Publish stories and numbered chapters, let readers follow along, and email them when a new chapter goes live.

= Features =

* Stories and numbered chapters with table of contents, scheduling, and author's notes
* Reader follows (accounts) and email subscriptions (guests or logged-in readers)
* On-site update inbox plus queued chapter notification mail
* Reading progress and continue-reading for guests and accounts
* Dedicated catalog and chapter reader templates
* Optional demo content via WP-CLI or an admin action (never auto-seeded)

= Email delivery =

Inkbound uses WordPress `wp_mail()` to send confirmation and chapter update emails. For reliable delivery on production sites, configure an SMTP plugin or transactional mail service. From name, from address, full-chapter vs excerpt mode, and double opt-in are available under **Inkbound → Settings**.

= Privacy =

Inkbound may store:

* Email addresses and confirmation / unsubscribe tokens for subscriptions
* Follow relationships and on-site notices for logged-in readers
* A guest cookie (`inkbound_rid`) used only to restore reading progress
* Optional mail delivery log entries for troubleshooting

Suggested privacy policy text is available under **Settings → Privacy**. See `uninstall.php` for what is removed when the plugin is deleted.

= Support =

Support is provided through the plugin's GitHub repository: https://github.com/doodersrage/inkbound

== Installation ==

1. Upload the `inkbound` folder to `/wp-content/plugins/`, or install the zip via **Plugins → Add New → Upload Plugin**.
2. Activate Inkbound through the **Plugins** screen.
3. Create a **Story**, then add **Chapters** from the story editor.
4. Optional: open **Inkbound → Settings** to place the catalog on the homepage and configure mail options.
5. Optional: load demo stories from **Inkbound → Overview** (administrators only) or `wp inkbound seed`.

== Frequently Asked Questions ==

= Does Inkbound replace my theme? =

On Inkbound catalog, story, chapter, library, and inbox pages, the plugin loads its own templates and styles so the reading experience stays consistent. Other site pages continue to use your theme.

= Will chapter emails send reliably? =

Inkbound queues mail and sends through `wp_mail()`. Shared hosting often needs SMTP or a transactional provider for consistent delivery. Use double opt-in in production when collecting guest emails.

= Can guests subscribe without an account? =

Yes. Guests can subscribe by email. When confirmation is enabled, they must confirm before chapter mail begins. Logged-in readers can follow stories and optionally enable email.

= Does the plugin phone home or load remote assets? =

No. Inkbound does not call external APIs and does not load remote fonts or scripts. Mail is sent from your WordPress site.

= How do I remove all plugin data? =

Delete the plugin from the WordPress admin. `uninstall.php` drops Inkbound tables and cleans related options and meta.

== Screenshots ==

1. Story catalog with covers, status, and continue-reading links.
2. Story page with follow / email subscribe and chapter list.
3. Chapter reader with progress tracking and navigation.
4. Inkbound admin overview for stories, chapters, and seeding.

== Changelog ==

= 1.0.0 =
* First public release.
* Stories, chapters, follows, email subscriptions, progress, and catalog templates.
* Privacy policy helper, subscribe rate limiting, and WordPress.org packaging polish.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
