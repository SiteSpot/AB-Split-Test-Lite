=== AB Split Test Lite ===
Contributors: tomsitespot
Donate link: https://absplittest.com
Tags: a/b testing, split testing, conversion optimization, heatmap, mcp
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted A/B and split testing for WordPress: unlimited tests, heatmaps, page-builder controls, MCP and REST.

== Description ==

**A/B testing that runs entirely on your own WordPress site. No external account, no traffic meter, and no visitor data leaving your server.**

AB Split Test Lite is a **self-hosted** A/B and split testing plugin for WordPress. Tests, visits, conversions and heatmaps are all stored in your own database. Run as many tests as you like, on as much traffic as your site serves.

It is built on the WordPress **Abilities API**, so AI agents such as Claude Code, Cursor and Windsurf can create and run tests over **MCP (Model Context Protocol)**.

= Features =

* **Unlimited tests.** Run as many A/B tests at once as you need. Each test compares your original with one variation, and traffic is split evenly between them.
* **Self-hosted.** Tests, tracking and reporting all run on your server.
* **No traffic meter.** No "tested page view" limit and no per-visitor billing.
* **Heatmaps on every page.** Click maps and scroll maps, stored on your own server. You choose how many days of data to keep.
* **Built for AI agents.** An MCP integration and a matching REST API let Claude, Cursor or any MCP client create, launch and read tests on your site.
* **Page-builder integrations.** Elementor, Bricks, Beaver Builder, Oxygen, Breakdance, WP Bakery and Gutenberg all get native test controls inside the builder.
* **Privacy-first.** No device fingerprinting and no third-party services. Visitors are assigned to variations with first-party cookies on your own domain, and you can wait for cookie consent before anything is stored.
* **Cache-friendly.** Test scripts are marked for exclusion in WP Rocket, NitroPack, LiteSpeed Cache, SiteGround Optimizer and other optimization plugins, so tests don't flicker or break.

= Four ways to build a test =

**Point-and-click (Magic Bar).** Open any page, click the headline, button, image or section you want to test, type your variation, and go. No selectors to hunt for and no code to write.

**Full-page (split URL).** Send traffic between completely different pages and measure which one converts.

**On-page.** Tag a section or block as the original and another as the variation, inside your page builder or the block editor.

**CSS.** Test design changes: visitors get either the original or the variation class on the page body, which your theme's CSS can style.

= What you can test =

* Headlines, sub-headlines and body copy
* Buttons and calls to action
* Images and hero sections
* Pricing and product copy
* Entire page layouts
* Design and CSS changes
* Anything you can click on with the Magic Bar

= Page-builder integrations =

AB Split Test adds **native controls inside each builder**, so you build variations in the interface you already design in:

* **Elementor**: tag any element as a test variation in the Elementor editor.
* **Bricks**: variation tagging in the element settings.
* **Breakdance**: a "Split Test" settings section on every element.
* **Beaver Builder**: split-test settings in row and module settings.
* **Oxygen**: split-test options in component options.
* **WP Bakery**: test and variation controls on rows and sections.
* **Gutenberg**: split-test attributes on every block, managed from the block editor.

Each builder can also create a new test without leaving the editor.

= Conversion goals =

Pick what counts as a win for each test:

* **Page visit**: a visitor in the test reaches the page you choose, for example your thank-you, order-received or sign-up confirmation page.
* **Element click**: a visitor clicks an element you choose with a CSS selector, such as a buy, sign-up or call button.

= Heatmaps and scroll maps =

See *why* a variation wins. Click heatmaps show where visitors click, and scroll maps show how far down each page they read. Everything is recorded on every page of your site and stored on your own server. Data is kept for 3 days by default; change it under **Settings → Heatmaps**.

= Trustworthy results =

Results use Bayesian statistical analysis, so you know when a winner is really a winner and not just noise. A winner needs enough visits and conversions before it is called. Filter results by device to see how each variation performs.

= The A/B testing plugin your AI agent can drive =

AB Split Test registers WordPress Abilities. Install the official WordPress MCP Adapter and they appear as **MCP tools**. The same actions are available over the **REST API**.

* `create-test`: create an A/B test
* `list-tests`: list tests and their status
* `get-test-details`: read a test's full configuration
* `get-test-results`: pull visits, conversions and statistical confidence
* `update-test-status`: start, pause or complete a test
* `update-test-settings`: change the goal, targeting and variation
* `get-heatmap-data`: read aggregated click and scroll data for a page
* `list-heatmap-pages`: list the pages that have heatmap data

= What users say =

> "Makes A/B testing as simple as can be. It's truly point-and-click easy with the WordPress plugin." (Verified User, Legal Services)

> "Smooth, super powerful and easy to integrate. It runs fast, has separate tables, the db is not clogged, and it runs smoothly even on cheap hosting." (digitalfastmind.com)

> "As a WordPress developer, this plugin immediately stood out. It runs fast without bloating the site and integrates cleanly into a standard WordPress workflow." (Stacey W., CodeInk)

> "It worked especially well with Elementor, letting me test anything from minor aesthetic variations all the way to total page variations." (Geoff, medlmobile.com)

> "I didn't have to read the manual in order to set up a test." (David McCan, WebTNG)

= AB Split Test Pro =

AB Split Test Pro is a separate plugin, available from [absplittest.com](https://absplittest.com/repo-up/?utm_source=wporg-lite&utm_medium=readme&utm_campaign=pro), with multivariate tests (several variations per test) and more ways to measure a win: form submission goals, WooCommerce, Easy Digital Downloads and FluentCart purchase and revenue tracking, sub-goals for multi-step funnels, audiences and location targeting, automatic winner selection, AI test ideas, webhooks, email reports and WP-CLI commands. Your tests and results carry over if you switch.

= Third-party libraries =

This plugin bundles the following open-source libraries:

* Chart.js 4.5.1, MIT License, https://github.com/chartjs/Chart.js
* canvas-confetti 1.9.4, ISC License, https://github.com/catdad/canvas-confetti
* Select2 4.1.0, MIT License, https://github.com/select2/select2
* Shepherd (shepherd.js) 11.2.0, MIT License, https://github.com/shipshapecode/shepherd
* Tabulator 6.3.1, MIT License, https://github.com/olifolkerd/tabulator
* heatmap.js 2.0.5, MIT License, https://github.com/pa7/heatmap.js
* Awesomplete 1.1.5, MIT License, https://github.com/LeaVerou/awesomplete
* modern-screenshot 4.x, MIT License, https://github.com/qq15725/modern-screenshot

= Help translate =

AB Split Test is translation-ready. Contribute a translation for your language at [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/ab-split-test-lite/).

== Installation ==

1. In your WordPress admin, go to **Plugins → Add New Plugin**.
2. Search for **AB Split Test Lite**.
3. Click **Install Now**, then **Activate**.
4. Open **Split Test** in the admin menu and create your first test.
5. To test an element on a page, open the page on the front end and launch the **Magic Bar** editor from the admin bar.

**Enable AI-agent (MCP) control (optional):**

1. Install and activate the official **WordPress MCP Adapter** plugin. The plugin's settings **Developer** tab walks you through it.
2. Create an application password for your user under **Users → Profile**.
3. Copy the ready-made client configuration from the Developer tab into Claude, Cursor, Windsurf or another MCP client.
4. The `absplittest/*` tools are now available. The same actions are also on the REST API under `/wp-json/bt-bb-ab/v1/`.

== Frequently Asked Questions ==

= Is AB Split Test a self-hosted A/B testing plugin? =

Yes. The testing engine, visitor tracking, heatmaps and reporting all run inside your WordPress install and store data on your own server. There is no external account and no SaaS backend. Heatmap data and debug logs use PHP-protected files in your uploads directory. Your web server must execute PHP files or deny direct access to them; Apache access rules provide additional protection. Existing text logs migrate automatically, and collection pauses with an admin notice if journey files cannot be protected.

= How many tests can I run? =

As many as you like, at the same time, with no limit on traffic. Each test is an A/B test: your original against one variation.

= Can an AI agent like Claude run A/B tests on WordPress? =

AB Split Test registers WordPress Abilities, which the official WordPress MCP Adapter exposes as MCP tools. The same actions are on the REST API. An agent authenticated as a user with the right permissions can create tests, list them, read results, read heatmaps, and start, pause or complete tests.

= How do I A/B test an Elementor, Bricks or Beaver Builder page? =

AB Split Test adds native controls to Elementor, Bricks, Beaver Builder, Oxygen, Breakdance, WP Bakery and Gutenberg. The point-and-click Magic Bar and full-page tests work with any theme or builder, because they operate on the rendered page.

= What counts as a conversion? =

A visit to the page you choose, such as your thank-you page, or a click on an element you choose with a CSS selector, such as a buy button. To count form submissions or WooCommerce purchases, choose the page visitors land on afterwards (the form's thank-you page, or the WooCommerce order-received page).

= How long is heatmap data kept? =

3 days by default. You can change the number of days under **Settings → Heatmaps**.

= Will it slow down my site or fight my caching plugin? =

The tracking script is small, loads from your own site, and makes no requests to third-party servers. It is automatically excluded from minification and delay in WP Rocket, NitroPack, LiteSpeed Cache, SiteGround Optimizer and other optimization plugins.

Tests work on cached pages, including host-level page caches, because each visitor is assigned a variation in their own browser and visits and conversions are recorded from the browser too. You don't need to exclude tested pages from your cache.

= Does it use cookies? Is it GDPR friendly? =

AB Split Test uses first-party cookies and browser storage on your own domain to remember which variation a visitor saw. It does not fingerprint devices and sends nothing to a third party.

If you need consent first, turn on **Wait for cookie consent**. Tests still run, but nothing is stored or sent until the visitor agrees. It works with Cookiebot, CookieConsent and any consent plugin that supports the WP Consent API (such as Complianz or CookieYes), or call `setAbstApprovalStatus(true)` from your own banner after confirming current consent on each page load. Call `setAbstApprovalStatus(false)` when consent is withdrawn; this clears tracking identifiers and pending history. Until consent is confirmed, assignments stay in page memory. As with any analytics tool, check your own consent requirements.

= What happens to my data if I delete the plugin? =

Your tests and results are kept by default, so nothing is lost by accident. Developers who want a full clean-up can set the `abst_delete_data_on_uninstall` option to `1` before deleting the plugin.

= Do I need to know how to code? =

No. The Magic Bar lets you build tests by clicking elements on the page. Developers who want more control get the REST API and MCP tools.

= Where can I get help or report a bug? =

Use the [WordPress.org support forum](https://wordpress.org/support/plugin/ab-split-test-lite/) or visit [absplittest.com](https://absplittest.com) for documentation.

== Screenshots ==

1. The point-and-click Magic Bar: click any element on your page, type a variation, and choose the goal, such as a click on a button you pick on the page.
2. Test results with uplift, confidence and conversion rates, calculated on your own server.
3. Click heatmaps built from your own visitors, recorded on every page.
4. Scroll maps show how far down each page your visitors actually read.
5. Elementor: tag any element as the original or the variation from its Advanced tab.
6. The block editor: an AB Split Test panel on every block, under Advanced.
7. Bricks: AB Split Test controls on every element, under Style.
8. Beaver Builder: split-test settings in each module's Advanced tab. Oxygen, Breakdance and WP Bakery have native controls too.
9. Every test at a glance: visits, conversions and conversion rate for the original and the variation, with its status.
10. The Developer tab: the MCP tools and REST API endpoints your AI agent can use.
11. Creating a test: choose point-and-click, full-page, on-page elements or code.

== Changelog ==

= 1.0.0 =
* First WordPress.org release of AB Split Test Lite, built on the AB Split Test engine used since 2019.
* Self-hosted point-and-click, full-page, on-page and CSS A/B tests, with no limit on the number of tests.
* MCP tools (via the WordPress Abilities API) and REST API for AI-agent and programmatic control.
* Click heatmaps and scroll maps on every page, with configurable data retention.
* Native page-builder controls for Elementor, Bricks, Beaver Builder, Oxygen, Breakdance, WP Bakery and Gutenberg.
* Bayesian statistical analysis with device breakdown.

== Upgrade Notice ==

= 1.0.0 =
First public release of AB Split Test Lite: self-hosted A/B testing with unlimited tests, page-visit and click goals, AI-agent (MCP) control, point-and-click editing and heatmaps.
