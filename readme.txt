=== t1 Schema ===
Contributors: pvj7000
Tags: schema, json-ld, structured-data, seo, rich-snippets
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

High-performance Schema.org JSON-LD markup with a visual editor. Three-layer architecture: Global Schemas, Conditional Rules, and Per-Page Overrides.

== Description ==

t1 Schema is a Schema.org JSON-LD plugin for WordPress that gives you granular control over your structured data through three layers:

**Global Schemas** – Site-wide markup that fires on every page (Organization, WebSite, etc.)

**Schema Rules** – Conditional templates that target specific page types, archives, taxonomies, and custom post types. Rules support AND logic for precise targeting.

**Local Overrides** – Per-page schemas stored in post meta for granular control, with an override toggle for same-type resolution.

= Key Features =

* **Visual Schema Editor** — Build schemas with a property-by-property editor, live JSON-LD preview, and Rich Snippet preview.
* **Dynamic Variables** — Use `{{post_title}}`, `{{post_date}}`, `{{featured_image_url}}`, `{{meta:custom_key}}`, and 30+ variables that resolve at render time.
* **Custom Variables** — Define reusable site-wide constants (phone, address, logo) accessible as `{{custom.key}}` in any schema.
* **Schema Quality Score** — Dashboard shows an objective 0–100 quality metric based on coverage, health, depth, and type diversity.
* **Health Validation** — Every schema is validated against its type definition. Errors, warnings, and fix suggestions are shown inline.
* **Site Map** — Hierarchical view of every URL context on your site with schema coverage indicators and one-click rule creation.
* **Recommended Templates** — Sensible default rules (Article for posts, WebPage for pages, etc.) that you opt into — never auto-activated.
* **33 Built-In Schema Types** — Organization, Article, Product, FAQPage, HowTo, Event, VideoObject, Service, and more.
* **WP-CLI** — Full command suite: create, inspect, render, health-check, export, import, coverage audit, and diagnostics.
* **Admin Bar Indicator** — Shows active schema count on frontend pages with a dropdown listing each type.
* **Post Editor Meta Box** — Compact sidebar panel showing local schemas, health badges, and quick-add dropdown.
* **`@graph` Pattern** — Multiple schemas on the same page are output in a single `<script>` tag using the `@graph` array.
* **`@id`-Based Merging** — Schemas sharing the same `@id` are merged into a single node, eliminating duplicates.
* **Auto BreadcrumbList** — Hierarchical pages with ancestors automatically get a BreadcrumbList schema.
* **Developer Hooks** — Filters for capability, JSON-LD output, variable resolution, condition matching, and author data.

= Who Is This For? =

* **SEO professionals** who need precise control over structured data across complex WordPress sites.
* **Developers** managing multi-CPT architectures with conditional schema requirements.
* **Agencies** that need a scalable, rule-based approach to Schema.org markup.

= What This Plugin Does NOT Do =

* It does not auto-generate schemas from your content — you control what gets output.
* It does not add any visible output to your frontend — only a `<script type="application/ld+json">` tag in `<head>`.
* It does not phone home, track users, or load external assets. Everything runs locally.

= Source Code =

The React admin UI is built with Vite. Source code and build tools are available on [GitHub](https://github.com/pvj7000/t1-schema).

== Installation ==

1. Upload the `t1-schema` directory to `/wp-content/plugins/`, or install directly through the WordPress plugin screen.
2. Activate the plugin through the **Plugins** screen.
3. Navigate to **t1 Schema** in the admin sidebar.

On first activation, the plugin:

* Creates two database tables (`t1schema_globals` and `t1schema_rules`).
* Seeds a default **Organization** and **WebSite** schema using your site name and URL.

== Frequently Asked Questions ==

= Does this plugin work with any theme? =

Yes. t1 Schema outputs a `<script type="application/ld+json">` tag in `<head>` and has no dependency on any theme.

= Does it conflict with other SEO plugins? =

t1 Schema only outputs JSON-LD structured data. It does not modify meta tags, sitemaps, or other SEO elements. It coexists with Yoast SEO, Rank Math, and similar plugins — but make sure to disable their schema output to avoid duplicates.

= What happens when I deactivate the plugin? =

Your data (schemas, rules, custom variables) stays in the database. If you delete the plugin and have enabled the "Delete data on uninstall" setting, all data is removed.

= Can I use this with custom post types? =

Yes. All registered public post types appear automatically in the condition dropdown, the post type filter, and the Site Map.

= What are dynamic variables? =

Variables like `{{post_title}}` are placeholders that resolve to actual values at render time. This lets you create one rule that works across hundreds of pages. See the Help tab in the dashboard for the full variable reference.

= Can I export and import schemas between sites? =

Yes. Use `wp t1-schema export` and `wp t1-schema import` via WP-CLI, or export the JSON from the dashboard.

== Screenshots ==

1. Dashboard with Schema Quality Score and recommended rule templates.
2. Rule Builder with condition selector and property editor.
3. Global Schema Editor with live JSON-LD preview.
4. Site Map showing coverage across all URL contexts.
5. Post Editor meta box with health badges.

== Changelog ==

= 2.0.0 =
* First release on the WordPress.org Plugin Directory.
* **Breaking:** Conflict suppression is now opt-in and disabled by default. Previously t1 Schema removed another plugin's schema output automatically. Enable it under Help → Settings if you see duplicate JSON-LD.
* Feature: Settings panel in the Help tab, covering conflict suppression and data removal on uninstall.
* Fix: Uninstall now drops the schema rules table alongside the globals table.
* Security: Direct-access guards added to every PHP file.
* Security: Meta box form input is unslashed before sanitizing.
* Improvement: Removed the `?t1debug` diagnostic panel from the admin screen. Use `wp t1-schema doctor` instead.
* Improvement: Tested against WordPress 7.0.

= 1.5.0 =
* Security hardening, i18n, readme, and directory-compliance groundwork.
* Fix: Health validator false positives for CollectionPage types.
* Fix: Context-aware validation — recommended property warnings downgraded to infos for rules.
* Fix: Custom variables (`{{custom.*}}`) now resolve in admin preview panels.
* Improvement: All SQL queries use prepared statements.
* Improvement: Output escaping hardened across all PHP files.
* Improvement: All user-facing strings wrapped for translation.

= 1.4.9 =
* Feature: `@id` reference badge in property editor.
* Feature: `child_of_page` condition type — target all descendants of a parent page.
* Feature: Copy button for JSON-LD preview panel.
* Feature: Custom author resolution hooks (`t1schema_author_name`, etc.).
* Fix: Variable regex allows digits for `{{meta:_custom_key_123}}`.
* Fix: CollectionPage added to schema registry.
* Fix: Dashboard health resolves variables before `@id` matching.

= 1.4.6 =
* Initial release. Recursive schema builder with visual editor.

== Upgrade Notice ==

= 2.0.0 =
Conflict suppression is now opt-in. If you relied on t1 Schema removing another plugin's JSON-LD, enable it under Help → Settings after updating, or you may see duplicate structured data.

= 1.5.0 =
Security hardening and WordPress.org compliance. Recommended update for all users.
